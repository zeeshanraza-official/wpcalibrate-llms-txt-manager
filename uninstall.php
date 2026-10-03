<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WPCalibrate\LlmsTxtManager
 */

declare(strict_types=1);

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Check if the administrator explicitly enabled data deletion on uninstall.
 * Default behavior is to PRESERVE user data.
 */
$settings = get_option( 'wpcllm_settings', [] );

if ( is_array( $settings ) && ! empty( $settings['delete_on_uninstall'] ) ) {
	// Delete plugin options.
	delete_option( 'wpcllm_settings' );
	delete_option( 'wpcllm_draft' );
	delete_option( 'wpcllm_published' );
	delete_option( 'wpcllm_schema_version' );
	delete_option( 'wpcllm_installed_at' );

	// Delete plugin transients/caches.
	delete_transient( 'wpcllm_cached_llms_txt' );
	delete_transient( 'wpcllm_physical_file_status' );
	delete_transient( 'wpcllm_self_check_result' );

	// Flush rewrite rules on uninstall if necessary.
	flush_rewrite_rules( false );
}
