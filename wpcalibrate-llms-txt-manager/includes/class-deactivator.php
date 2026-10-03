<?php
/**
 * Fired during plugin deactivation.
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
 * Class Deactivator
 *
 * Preserves user data while safely cleaning up transients and rewrite rules upon deactivation.
 */
class Deactivator {

	/**
	 * Run deactivation tasks.
	 */
	public static function deactivate(): void {
		// Clean up transients.
		$cache = new Cache();
		$cache->invalidate();

		$detector = new Physical_File_Detector();
		$detector->invalidate();

		delete_transient( 'wpcllm_self_check_result' );

		// Flush rewrite rules once on deactivation.
		flush_rewrite_rules( false );
	}
}
