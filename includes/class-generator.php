<?php
/**
 * Deterministic llms.txt v2 Markdown generator.
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
 * Class Generator
 *
 * Generates deterministic, standard-compliant llms.txt v2 Markdown from structured data.
 */
class Generator {

	/**
	 * Generate llms.txt Markdown from structured data.
	 *
	 * @param array<string, mixed> $data Structured builder data.
	 * @return string Deterministic Markdown output.
	 */
	public function generate( array $data ): string {
		$lines = [];

		// 1. Primary H1 Title (Required).
		$title = isset( $data['title'] ) ? trim( (string) $data['title'] ) : '';
		if ( empty( $title ) ) {
			$title = get_bloginfo( 'name' );
			if ( empty( $title ) ) {
				$title = 'Site Documentation';
			}
		}
		// Sanitize title to single line without markdown formatting breakage.
		$title   = str_replace( [ "\r", "\n" ], ' ', $title );
		$lines[] = '# ' . $title;

		// 2. Short Summary Blockquote (Optional).
		$summary = isset( $data['summary'] ) ? trim( (string) $data['summary'] ) : '';
		if ( ! empty( $summary ) ) {
			$lines[] = '';
			// Handle multi-line blockquotes gracefully.
			$summary_lines = preg_split( '/\r\n|\r|\n/', $summary );
			if ( is_array( $summary_lines ) ) {
				foreach ( $summary_lines as $s_line ) {
					$trimmed = trim( $s_line );
					if ( '' !== $trimmed ) {
						// Strip existing leading '>' if user already typed it.
						$clean_line = ltrim( $trimmed, '> ' );
						$lines[]    = '> ' . $clean_line;
					}
				}
			}
		}

		// 3. Additional Descriptive Markdown Content (Optional).
		$additional_info = isset( $data['additional_info'] ) ? trim( (string) $data['additional_info'] ) : '';
		if ( ! empty( $additional_info ) ) {
			$lines[]           = '';
			$normalized_add_info = str_replace( [ "\r\n", "\r" ], "\n", $additional_info );
			$lines[]           = $normalized_add_info;
		}

		// 4. Resource Sections (H2).
		$sections = isset( $data['sections'] ) && is_array( $data['sections'] ) ? $data['sections'] : [];

		foreach ( $sections as $section ) {
			$section_title = isset( $section['title'] ) ? trim( (string) $section['title'] ) : '';
			if ( empty( $section_title ) ) {
				continue;
			}

			// Clean section title.
			$section_title = str_replace( [ "\r", "\n" ], ' ', $section_title );
			$section_title = ltrim( $section_title, '# ' );

			$lines[] = '';
			$lines[] = '## ' . $section_title;

			$resources = isset( $section['resources'] ) && is_array( $section['resources'] ) ? $section['resources'] : [];

			foreach ( $resources as $resource ) {
				$res_title = isset( $resource['title'] ) ? trim( (string) $resource['title'] ) : '';
				$res_url   = isset( $resource['url'] ) ? trim( (string) $resource['url'] ) : '';
				$res_desc  = isset( $resource['description'] ) ? trim( (string) $resource['description'] ) : '';

				if ( empty( $res_url ) ) {
					continue;
				}

				if ( empty( $res_title ) ) {
					$res_title = $res_url;
				}

				// Clean title and url.
				$res_title = str_replace( [ '[', ']', "\r", "\n" ], ' ', $res_title );
				$res_title = trim( (string) preg_replace( '/\s+/', ' ', $res_title ) );
				$res_url   = str_replace( [ ' ', "\r", "\n" ], '', $res_url );

				// Format: - [Title](URL): Description or - [Title](URL).
				$item = '- [' . $res_title . '](' . $res_url . ')';

				if ( ! empty( $res_desc ) ) {
					// Clean description to single line.
					$clean_desc = str_replace( [ "\r", "\n" ], ' ', $res_desc );
					$clean_desc = trim( (string) preg_replace( '/\s+/', ' ', $clean_desc ) );
					if ( '' !== $clean_desc ) {
						// Strip leading colon if already provided.
						$clean_desc = ltrim( $clean_desc, ': ' );
						$item      .= ': ' . $clean_desc;
					}
				}

				$lines[] = $item;
			}
		}

		// Ensure single trailing newline.
		$markdown = implode( "\n", $lines ) . "\n";

		/**
		 * Filter generated Markdown before returning.
		 *
		 * @param string               $markdown Generated deterministic Markdown.
		 * @param array<string, mixed> $data     Source structured data.
		 */
		return (string) apply_filters( 'wpcllm_generated_markdown', $markdown, $data );
	}
}
