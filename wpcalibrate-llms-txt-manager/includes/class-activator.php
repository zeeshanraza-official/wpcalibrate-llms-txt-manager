<?php
/**
 * Fired during plugin activation.
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
 * Class Activator
 *
 * Handles initialization of options, rewrite rules, and state on activation.
 */
class Activator {

	/**
	 * Run activation tasks.
	 */
	public static function activate(): void {
		// 1. Initialize options if not present.
		if ( false === get_option( Options::OPTION_SETTINGS ) ) {
			add_option( Options::OPTION_SETTINGS, Options::get_default_settings(), '', true );
		}

		if ( false === get_option( Options::OPTION_SCHEMA_VERSION ) ) {
			add_option( Options::OPTION_SCHEMA_VERSION, WPCLLM_SCHEMA_VERSION, '', true );
		}

		if ( false === get_option( Options::OPTION_DRAFT ) ) {
			Options::reset_draft();
		}

		if ( false === get_option( 'wpcllm_installed_at' ) ) {
			add_option( 'wpcllm_installed_at', time(), '', false );
		}

		// 2. Register rewrite rules and flush once.
		$router = new Router();
		$router->add_rewrite_rules();
		flush_rewrite_rules( false );

		// 3. Inspect physical file status (cached).
		$detector = new Physical_File_Detector();
		$detector->detect( true );
	}
}
