<?php
/**
 * Raw Markdown Editor admin controller.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin;

use WPCalibrate\LlmsTxtManager\Includes\Cache;
use WPCalibrate\LlmsTxtManager\Includes\Importer;
use WPCalibrate\LlmsTxtManager\Includes\Options;
use WPCalibrate\LlmsTxtManager\Includes\Validator;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Raw_Editor
 *
 * Handles raw Markdown editing, validation, and persistence.
 */
class Raw_Editor {

	/**
	 * Process form submission from Raw Markdown Editor.
	 *
	 * @param array<string, mixed> $post_data Unslashed $_POST data.
	 * @param string               $action    Action type: 'save_draft' or 'publish'.
	 * @return array{success: bool, message: string, errors: list<string>, warnings: list<string>}
	 */
	public function handle_save( array $post_data, string $action = 'save_draft' ): array {
		$raw_markdown = isset( $post_data['raw_markdown'] ) ? (string) $post_data['raw_markdown'] : '';

		// Normalize line endings and strip BOM.
		$importer = new Importer();
		$cleaned  = $importer->strip_bom( $raw_markdown );
		$cleaned  = str_replace( [ "\r\n", "\r" ], "\n", $cleaned );

		// Validate content.
		$validator = new Validator();
		$val_res   = $validator->validate( $cleaned );

		// Parse to structured data to keep builder synchronization available.
		$structured = $importer->parse_to_structured( $cleaned );

		$draft_data = [
			'mode'            => 'raw',
			'title'           => $structured['title'],
			'summary'         => $structured['summary'],
			'additional_info' => $structured['additional_info'],
			'sections'        => $structured['sections'],
			'raw_markdown'    => $cleaned,
			'hash'            => hash( 'sha256', $cleaned ),
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
				'message'  => __( 'Raw Markdown published successfully to /llms.txt!', 'wpcalibrate-llms-txt-manager' ),
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
