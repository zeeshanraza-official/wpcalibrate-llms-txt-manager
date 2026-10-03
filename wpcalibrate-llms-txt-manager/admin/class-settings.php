<?php
/**
 * Settings admin controller.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin;

use WPCalibrate\LlmsTxtManager\Includes\Cache;
use WPCalibrate\LlmsTxtManager\Includes\Options;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * Handles settings sanitization and persistence.
 */
class Settings {

	/**
	 * Process form submission from Settings screen.
	 *
	 * @param array<string, mixed> $post_data Unslashed $_POST data.
	 * @return array{success: bool, message: string}
	 */
	public function handle_save( array $post_data ): array {
		$raw_settings = isset( $post_data['settings'] ) && is_array( $post_data['settings'] ) ? $post_data['settings'] : [];

		// Handle checkbox values explicitly.
		$raw_settings['enabled']             = ! empty( $raw_settings['enabled'] );
		$raw_settings['enable_cache']        = ! empty( $raw_settings['enable_cache'] );
		$raw_settings['delete_on_uninstall'] = ! empty( $raw_settings['delete_on_uninstall'] );

		Options::update_settings( $raw_settings );

		// Invalidate cache when settings change.
		$cache = new Cache();
		$cache->invalidate();

		return [
			'success' => true,
			'message' => __( 'Plugin settings updated successfully.', 'wpcalibrate-llms-txt-manager' ),
		];
	}
}
