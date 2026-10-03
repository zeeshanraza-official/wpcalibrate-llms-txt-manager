<?php
/**
 * Structured Builder admin controller.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin;

use WPCalibrate\LlmsTxtManager\Includes\Cache;
use WPCalibrate\LlmsTxtManager\Includes\Generator;
use WPCalibrate\LlmsTxtManager\Includes\Options;
use WPCalibrate\LlmsTxtManager\Includes\Validator;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Builder
 *
 * Handles processing and formatting structured builder forms.
 */
class Builder {

	/**
	 * Process form submission from Structured Builder.
	 *
	 * @param array<string, mixed> $post_data Unslashed $_POST data.
	 * @param string               $action    Action type: 'save_draft' or 'publish'.
	 * @return array{success: bool, message: string, errors: list<string>, warnings: list<string>}
	 */
	public function handle_save( array $post_data, string $action = 'save_draft' ): array {
		$title           = isset( $post_data['title'] ) ? sanitize_text_field( (string) $post_data['title'] ) : '';
		$summary         = isset( $post_data['summary'] ) ? sanitize_textarea_field( (string) $post_data['summary'] ) : '';
		$additional_info = isset( $post_data['additional_info'] ) ? wp_unslash( (string) $post_data['additional_info'] ) : '';

		// Sanitize sections and resources.
		$sections = [];
		if ( isset( $post_data['sections'] ) && is_array( $post_data['sections'] ) ) {
			foreach ( $post_data['sections'] as $sec_raw ) {
				$sec_title = isset( $sec_raw['title'] ) ? sanitize_text_field( (string) $sec_raw['title'] ) : '';
				if ( '' === $sec_title ) {
					continue;
				}

				$resources = [];
				if ( isset( $sec_raw['resources'] ) && is_array( $sec_raw['resources'] ) ) {
					foreach ( $sec_raw['resources'] as $res_raw ) {
						$res_url = isset( $res_raw['url'] ) ? esc_url_raw( trim( (string) $res_raw['url'] ) ) : '';
						if ( '' === $res_url ) {
							continue;
						}

						$res_title = isset( $res_raw['title'] ) ? sanitize_text_field( (string) $res_raw['title'] ) : '';
						if ( '' === $res_title ) {
							$res_title = $res_url;
						}

						$res_desc = isset( $res_raw['description'] ) ? sanitize_text_field( (string) $res_raw['description'] ) : '';
						$post_id  = isset( $res_raw['post_id'] ) ? absint( $res_raw['post_id'] ) : 0;

						$resources[] = [
							'id'          => 'res_' . wp_generate_password( 8, false ),
							'title'       => $res_title,
							'url'         => $res_url,
							'description' => $res_desc,
							'post_id'     => $post_id,
						];
					}
				}

				$sections[] = [
					'id'        => 'sec_' . wp_generate_password( 8, false ),
					'title'     => $sec_title,
					'resources' => $resources,
				];
			}
		}

		$structured_data = [
			'title'           => $title,
			'summary'         => $summary,
			'additional_info' => $additional_info,
			'sections'        => $sections,
		];

		// Generate deterministic Markdown.
		$generator = new Generator();
		$markdown  = $generator->generate( $structured_data );

		// Validate content.
		$validator = new Validator();
		$val_res   = $validator->validate( $markdown, [ 'structured_data' => $structured_data ] );

		// Assemble draft data structure.
		$draft_data = [
			'mode'            => 'builder',
			'title'           => $title,
			'summary'         => $summary,
			'additional_info' => $additional_info,
			'sections'        => $sections,
			'raw_markdown'    => $markdown,
			'hash'            => hash( 'sha256', $markdown ),
		];

		// Always update draft.
		Options::save_draft( $draft_data );

		if ( 'publish' === $action ) {
			if ( ! $val_res['valid'] ) {
				return [
					'success'  => false,
					'message'  => __( 'Draft saved, but publication was blocked due to validation errors.', 'wpcalibrate-llms-txt-manager' ),
					'errors'   => $val_res['errors'],
					'warnings' => $val_res['warnings'],
				];
			}

			// Copy valid draft to published option.
			Options::save_published( $draft_data );

			// Invalidate output cache.
			$cache = new Cache();
			$cache->invalidate();

			return [
				'success'  => true,
				'message'  => __( 'LLMs.txt configuration published successfully!', 'wpcalibrate-llms-txt-manager' ),
				'errors'   => [],
				'warnings' => $val_res['warnings'],
			];
		}

		return [
			'success'  => true,
			'message'  => __( 'Draft saved successfully.', 'wpcalibrate-llms-txt-manager' ),
			'errors'   => $val_res['errors'],
			'warnings' => $val_res['warnings'],
		];
	}
}
