<?php
/**
 * Safe text file importer and llms.txt Markdown parser.
 *
 * @package WPCalibrate\LlmsTxtManager\Includes
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Includes;

use WP_Error;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Importer
 *
 * Handles defensive file uploads and parses standard llms.txt v2 Markdown into structured data.
 */
class Importer {

	/**
	 * Process an uploaded plain text file safely.
	 *
	 * @param array<string, mixed> $file Uploaded $_FILES entry.
	 * @return string|WP_Error Raw normalized UTF-8 string on success, WP_Error on failure.
	 */
	public function handle_file_upload( array $file ): string|WP_Error {
		// 1. Check upload error code.
		if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== $file['error'] ) {
			$code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
			return new WP_Error(
				'upload_failed',
				$this->get_upload_error_message( (int) $code )
			);
		}

		// 2. Validate temporary file exists and is an uploaded file.
		$tmp_path = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		if ( empty( $tmp_path ) || ! is_uploaded_file( $tmp_path ) || ! file_exists( $tmp_path ) ) {
			return new WP_Error(
				'invalid_upload',
				__( 'The uploaded file could not be verified on the server.', 'wpcalibrate-llms-txt-manager' )
			);
		}

		// 3. Enforce file size ceiling.
		$settings     = Options::get_settings();
		$max_kb       = (int) ( $settings['max_import_size_kb'] ?? 256 );
		$max_bytes    = $max_kb * 1024;
		$actual_bytes = filesize( $tmp_path );

		if ( false === $actual_bytes || $actual_bytes > $max_bytes ) {
			@unlink( $tmp_path );
			return new WP_Error(
				'file_too_large',
				sprintf(
					/* translators: 1: uploaded size in KB, 2: max allowed size in KB */
					__( 'Uploaded file is too large (%1$d KB). Maximum allowed size is %2$d KB.', 'wpcalibrate-llms-txt-manager' ),
					(int) ( ( $actual_bytes ?: 0 ) / 1024 ),
					$max_kb
				)
			);
		}

		// 4. Validate filename extension.
		$filename = isset( $file['name'] ) ? sanitize_file_name( (string) $file['name'] ) : '';
		$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, [ 'txt', 'md' ], true ) ) {
			@unlink( $tmp_path );
			return new WP_Error(
				'invalid_extension',
				__( 'Only plain-text .txt and .md files are supported for import.', 'wpcalibrate-llms-txt-manager' )
			);
		}

		// 5. Inspect MIME type defensively.
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		if ( false !== $finfo ) {
			$mime = finfo_file( $finfo, $tmp_path );
			finfo_close( $finfo );

			$allowed_mimes = [
				'text/plain',
				'text/markdown',
				'text/x-markdown',
				'application/octet-stream', // Some servers report text files with BOM as octet-stream.
			];

			if ( ! in_array( $mime, $allowed_mimes, true ) ) {
				@unlink( $tmp_path );
				return new WP_Error(
					'invalid_mime_type',
					sprintf(
						/* translators: %s: detected MIME type */
						__( 'Unsupported file type (%s). Only plain text files are allowed.', 'wpcalibrate-llms-txt-manager' ),
						esc_html( (string) $mime )
					)
				);
			}
		}

		// 6. Read content directly into memory from tmp file.
		$raw_content = file_get_contents( $tmp_path );
		@unlink( $tmp_path ); // Clean up temporary file immediately.

		if ( false === $raw_content ) {
			return new WP_Error(
				'read_error',
				__( 'Failed to read the uploaded file contents.', 'wpcalibrate-llms-txt-manager' )
			);
		}

		// 7. Strip UTF-8 BOM if present.
		$cleaned = $this->strip_bom( $raw_content );

		// 8. Validate UTF-8.
		if ( function_exists( 'mb_check_encoding' ) && ! mb_check_encoding( $cleaned, 'UTF-8' ) ) {
			return new WP_Error(
				'encoding_error',
				__( 'Uploaded file is not encoded in valid UTF-8 format.', 'wpcalibrate-llms-txt-manager' )
			);
		}

		// 9. Normalize line endings.
		return str_replace( [ "\r\n", "\r" ], "\n", $cleaned );
	}

	/**
	 * Strip UTF-8 BOM from string.
	 *
	 * @param string $content Input content.
	 * @return string Content with BOM removed.
	 */
	public function strip_bom( string $content ): string {
		if ( str_starts_with( $content, "\xEF\xBB\xBF" ) ) {
			return substr( $content, 3 );
		}
		return $content;
	}

	/**
	 * Parse llms.txt v2 Markdown into structured data.
	 *
	 * @param string $markdown Plain text Markdown.
	 * @return array<string, mixed> Structured data for builder.
	 */
	public function parse_to_structured( string $markdown ): array {
		$clean_markdown = $this->strip_bom( $markdown );
		$lines          = preg_split( '/\r\n|\r|\n/', $clean_markdown );

		$title           = '';
		$summary_lines   = [];
		$add_info_lines  = [];
		$sections        = [];
		$current_section = null;
		$seen_h1         = false;
		$seen_first_h2   = false;

		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				$trimmed = trim( $line );

				// H1 Title.
				if ( preg_match( '/^#\s+(.+)$/u', $trimmed, $m ) && ! $seen_h1 ) {
					$title   = trim( $m[1] );
					$seen_h1 = true;
					continue;
				}

				// H2 Section Heading.
				if ( preg_match( '/^##\s+(.+)$/u', $trimmed, $m ) ) {
					if ( null !== $current_section ) {
						$sections[] = $current_section;
					}

					$sec_title       = trim( $m[1] );
					$current_section = [
						'id'        => 'sec_' . wp_generate_password( 8, false ),
						'title'     => $sec_title,
						'resources' => [],
					];
					$seen_first_h2   = true;
					continue;
				}

				// If we are inside an H2 section, parse resource links.
				if ( null !== $current_section ) {
					if ( preg_match( '/^[-*]\s+\[(.*?)\]\((.*?)\)(?::\s*(.*))?$/u', $trimmed, $m ) ) {
						$current_section['resources'][] = [
							'id'          => 'res_' . wp_generate_password( 8, false ),
							'title'       => trim( $m[1] ),
							'url'         => trim( $m[2] ),
							'description' => isset( $m[3] ) ? trim( $m[3] ) : '',
							'post_id'     => 0,
						];
					}
					continue;
				}

				// If before the first H2 section, parse summary blockquote or additional text.
				if ( ! $seen_first_h2 && $seen_h1 ) {
					if ( str_starts_with( $trimmed, '>' ) ) {
						$summary_lines[] = trim( ltrim( $trimmed, '> ' ) );
					} elseif ( '' !== $trimmed ) {
						$add_info_lines[] = $line;
					}
				}
			}
		}

		if ( null !== $current_section ) {
			$sections[] = $current_section;
		}

		if ( empty( $title ) ) {
			$title = get_bloginfo( 'name' ) ?: 'Site Documentation';
		}

		// Ensure an Optional section exists if not already present.
		$has_optional = false;
		foreach ( $sections as $sec ) {
			if ( strcasecmp( $sec['title'], 'Optional' ) === 0 ) {
				$has_optional = true;
				break;
			}
		}
		if ( ! $has_optional ) {
			$sections[] = [
				'id'        => 'sec_optional',
				'title'     => 'Optional',
				'resources' => [],
			];
		}

		return [
			'title'           => $title,
			'summary'         => implode( "\n", $summary_lines ),
			'additional_info' => trim( implode( "\n", $add_info_lines ) ),
			'sections'        => $sections,
		];
	}

	/**
	 * Get human-readable message for standard PHP upload error codes.
	 *
	 * @param int $code Upload error code.
	 * @return string Error message.
	 */
	private function get_upload_error_message( int $code ): string {
		return match ( $code ) {
			UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'wpcalibrate-llms-txt-manager' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.', 'wpcalibrate-llms-txt-manager' ),
			UPLOAD_ERR_PARTIAL    => __( 'The uploaded file was only partially uploaded.', 'wpcalibrate-llms-txt-manager' ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'wpcalibrate-llms-txt-manager' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder on the server.', 'wpcalibrate-llms-txt-manager' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'wpcalibrate-llms-txt-manager' ),
			UPLOAD_ERR_EXTENSION  => __( 'A PHP extension stopped the file upload.', 'wpcalibrate-llms-txt-manager' ),
			default               => __( 'An unknown error occurred during file upload.', 'wpcalibrate-llms-txt-manager' ),
		};
	}
}
