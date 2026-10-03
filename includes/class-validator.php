<?php
/**
 * llms.txt content and structure validator.
 *
 * @package WPCalibrate\LlmsTxtManager\Includes
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Includes;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Validator
 *
 * Validates llms.txt Markdown against the v2 specification, separating publication-blocking
 * errors from non-blocking advisory warnings.
 */
class Validator {

	/**
	 * Validate Markdown content.
	 *
	 * @param string               $markdown Raw Markdown content to validate.
	 * @param array<string, mixed> $context  Additional context (mode, structured data, etc.).
	 * @return array{
	 *     valid: bool,
	 *     errors: list<string>,
	 *     warnings: list<string>,
	 *     stats: array{
	 *         bytes: int,
	 *         chars: int,
	 *         lines: int,
	 *         h1_count: int,
	 *         h2_count: int,
	 *         resource_count: int
	 *     }
	 * } Validation result structure.
	 */
	public function validate( string $markdown, array $context = [] ): array {
		$errors   = [];
		$warnings = [];

		// 1. UTF-8 Encoding Check.
		if ( function_exists( 'mb_check_encoding' ) && ! mb_check_encoding( $markdown, 'UTF-8' ) ) {
			$errors[] = __( 'Content is not valid UTF-8 text.', 'wpcalibrate-llms-txt-manager' );
		}

		$trimmed = trim( $markdown );

		// 2. Empty Content Check.
		if ( '' === $trimmed ) {
			$errors[] = __( 'Content is empty. An llms.txt file requires at least an H1 heading with the site/project name.', 'wpcalibrate-llms-txt-manager' );
			return $this->format_result( false, $errors, $warnings, $markdown, 0, 0, 0 );
		}

		// 3. Size Limits Check.
		$byte_size = strlen( $markdown );
		if ( $byte_size > 2 * 1024 * 1024 ) { // 2MB hard ceiling.
			$errors[] = sprintf(
				/* translators: %s: size in megabytes */
				__( 'File size (%s MB) exceeds the maximum allowed limit of 2MB.', 'wpcalibrate-llms-txt-manager' ),
				number_format( $byte_size / ( 1024 * 1024 ), 2 )
			);
		} elseif ( $byte_size > 512 * 1024 ) { // 512KB advisory warning.
			$warnings[] = sprintf(
				/* translators: %s: size in kilobytes */
				__( 'File size (%s KB) is unusually large for an llms.txt file. LLMs prefer concise, curated files under 250KB.', 'wpcalibrate-llms-txt-manager' ),
				number_format( $byte_size / 1024, 1 )
			);
		}

		// 4. HTML Tag Detection.
		if ( preg_match( '/<\s*(script|style|div|span|p|a|iframe|object|embed|form|table)[^>]*>/i', $markdown ) ) {
			$errors[] = __( 'Content contains raw HTML tags. llms.txt must consist purely of plain Markdown.', 'wpcalibrate-llms-txt-manager' );
		}

		// 5. Line-by-line parsing & AST metrics.
		$lines          = preg_split( '/\r\n|\r|\n/', $markdown );
		$h1_titles      = [];
		$h2_titles      = [];
		$resource_urls  = [];
		$has_blockquote = false;
		$line_count     = is_array( $lines ) ? count( $lines ) : 0;

		if ( is_array( $lines ) ) {
			foreach ( $lines as $index => $line ) {
				$line_num     = $index + 1;
				$trimmed_line = trim( $line );

				if ( '' === $trimmed_line ) {
					continue;
				}

				// Check H1 headings (# Title).
				if ( preg_match( '/^#\s+(.+)$/u', $trimmed_line, $matches ) ) {
					$h1_title = trim( $matches[1] );
					if ( '' === $h1_title ) {
						$errors[] = sprintf(
							/* translators: %d: line number */
							__( 'Line %d: H1 heading cannot be empty.', 'wpcalibrate-llms-txt-manager' ),
							$line_num
						);
					} else {
						$h1_titles[] = $h1_title;
					}
					continue;
				}

				// Check H2 headings (## Section).
				if ( preg_match( '/^##\s+(.+)$/u', $trimmed_line, $matches ) ) {
					$h2_title = trim( $matches[1] );
					if ( '' === $h2_title ) {
						$errors[] = sprintf(
							/* translators: %d: line number */
							__( 'Line %d: H2 section heading cannot be empty.', 'wpcalibrate-llms-txt-manager' ),
							$line_num
						);
					} else {
						$h2_titles[] = $h2_title;
					}
					continue;
				}

				// Check blockquote (> Summary).
				if ( str_starts_with( $trimmed_line, '>' ) ) {
					$has_blockquote = true;
					continue;
				}

				// Check resource items (- [Title](URL): Description).
				if ( preg_match( '/^[-*]\s+\[(.*?)\]\((.*?)\)(?::\s*(.*))?$/u', $trimmed_line, $matches ) ) {
					$res_title = trim( $matches[1] );
					$res_url   = trim( $matches[2] );
					$res_desc  = isset( $matches[3] ) ? trim( $matches[3] ) : '';

					if ( '' === $res_url ) {
						$errors[] = sprintf(
							/* translators: %d: line number */
							__( 'Line %d: Resource URL is missing in link entry.', 'wpcalibrate-llms-txt-manager' ),
							$line_num
						);
						continue;
					}

					// Validate URL scheme: must be absolute http or https.
					$parsed_url = wp_parse_url( $res_url );
					if ( empty( $parsed_url['scheme'] ) || ! in_array( strtolower( $parsed_url['scheme'] ), [ 'http', 'https' ], true ) || empty( $parsed_url['host'] ) ) {
						$errors[] = sprintf(
							/* translators: 1: line number, 2: invalid URL */
							__( 'Line %1$d: Resource URL "%2$s" must be an absolute HTTP or HTTPS URL.', 'wpcalibrate-llms-txt-manager' ),
							$line_num,
							esc_html( $res_url )
						);
					}

					// Check duplicates.
					$normalized_url = strtolower( rtrim( $res_url, '/' ) );
					if ( isset( $resource_urls[ $normalized_url ] ) ) {
						$warnings[] = sprintf(
							/* translators: 1: line number, 2: original line number, 3: duplicate URL */
							__( 'Line %1$d: Duplicate resource URL detected (first seen on line %2$d): %3$s', 'wpcalibrate-llms-txt-manager' ),
							$line_num,
							$resource_urls[ $normalized_url ],
							esc_html( $res_url )
						);
					} else {
						$resource_urls[ $normalized_url ] = $line_num;
					}
				}
			}
		}

		// 6. H1 Validation (Required primary element).
		$h1_count = count( $h1_titles );
		if ( 0 === $h1_count ) {
			$errors[] = __( 'Missing required H1 heading (# Site/Project Name). The llms.txt v2 specification requires exactly one top-level H1.', 'wpcalibrate-llms-txt-manager' );
		} elseif ( $h1_count > 1 ) {
			$errors[] = sprintf(
				/* translators: %d: number of H1 headings found */
				__( 'Found %d H1 headings. The llms.txt v2 specification requires exactly ONE H1 project heading.', 'wpcalibrate-llms-txt-manager' ),
				$h1_count
			);
		}

		// 7. Advisory checks.
		if ( ! $has_blockquote ) {
			$warnings[] = __( 'No short summary blockquote (> Summary) was found. Adding a concise site summary is recommended for LLM context.', 'wpcalibrate-llms-txt-manager' );
		}

		$h2_count       = count( $h2_titles );
		$resource_count = count( $resource_urls );

		if ( $h2_count > 0 && 0 === $resource_count ) {
			$warnings[] = __( 'Sections were defined, but no resource links (- [Title](URL)) were found.', 'wpcalibrate-llms-txt-manager' );
		}

		// 8. If structured context is passed, verify WordPress post statuses.
		if ( ! empty( $context['structured_data']['sections'] ) && is_array( $context['structured_data']['sections'] ) ) {
			foreach ( $context['structured_data']['sections'] as $sec ) {
				if ( empty( $sec['resources'] ) || ! is_array( $sec['resources'] ) ) {
					continue;
				}
				foreach ( $sec['resources'] as $res ) {
					$post_id = isset( $res['post_id'] ) ? absint( $res['post_id'] ) : 0;
					if ( $post_id > 0 ) {
						$post = get_post( $post_id );
						if ( ! $post || 'publish' !== $post->post_status || ! empty( $post->post_password ) ) {
							$warnings[] = sprintf(
								/* translators: 1: post ID, 2: resource title */
								__( 'Resource "%2$s" (Post ID %1$d) is currently not a publicly published post.', 'wpcalibrate-llms-txt-manager' ),
								$post_id,
								esc_html( $res['title'] ?? (string) $post_id )
							);
						}
					}
				}
			}
		}

		$is_valid = empty( $errors );

		return $this->format_result( $is_valid, $errors, $warnings, $markdown, $h1_count, $h2_count, $resource_count );
	}

	/**
	 * Format validation result structure.
	 *
	 * @param bool          $is_valid       Whether content is publication-valid.
	 * @param list<string>  $errors         List of error messages.
	 * @param list<string>  $warnings       List of warning messages.
	 * @param string        $markdown       Evaluated content.
	 * @param int           $h1_count       Count of H1 tags.
	 * @param int           $h2_count       Count of H2 tags.
	 * @param int           $resource_count Count of resources.
	 * @return array{
	 *     valid: bool,
	 *     errors: list<string>,
	 *     warnings: list<string>,
	 *     stats: array{
	 *         bytes: int,
	 *         chars: int,
	 *         lines: int,
	 *         h1_count: int,
	 *         h2_count: int,
	 *         resource_count: int
	 *     }
	 * }
	 */
	private function format_result(
		bool $is_valid,
		array $errors,
		array $warnings,
		string $markdown,
		int $h1_count,
		int $h2_count,
		int $resource_count
	): array {
		$char_count = function_exists( 'mb_strlen' ) ? mb_strlen( $markdown, 'UTF-8' ) : strlen( $markdown );
		$lines      = preg_split( '/\r\n|\r|\n/', $markdown );

		return [
			'valid'    => $is_valid,
			'errors'   => array_values( array_unique( $errors ) ),
			'warnings' => array_values( array_unique( $warnings ) ),
			'stats'    => [
				'bytes'          => strlen( $markdown ),
				'chars'          => $char_count,
				'lines'          => is_array( $lines ) ? count( $lines ) : 0,
				'h1_count'       => $h1_count,
				'h2_count'       => $h2_count,
				'resource_count' => $resource_count,
			],
		];
	}
}
