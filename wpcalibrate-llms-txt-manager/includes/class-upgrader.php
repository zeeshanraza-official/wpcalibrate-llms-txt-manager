<?php
/**
 * Handles database options migrations and version upgrades.
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
 * Class Upgrader
 *
 * Manages idempotent version migrations for options and internal schemas.
 */
class Upgrader {

	/**
	 * Run upgrade checks.
	 */
	public function check_and_upgrade(): void {
		$installed_schema = (string) get_option( Options::OPTION_SCHEMA_VERSION, '0.0.0' );

		if ( version_compare( $installed_schema, WPCLLM_SCHEMA_VERSION, '<' ) ) {
			$this->run_migrations( $installed_schema );
			update_option( Options::OPTION_SCHEMA_VERSION, WPCLLM_SCHEMA_VERSION, true );
		}
	}

	/**
	 * Execute sequential migrations.
	 *
	 * @param string $from_version Starting schema version.
	 */
	private function run_migrations( string $from_version ): void {
		if ( version_compare( $from_version, '1.0.0', '<' ) ) {
			// Initial schema migration: ensure default options are set.
			$settings = Options::get_settings();
			Options::update_settings( $settings );

			$router = new Router();
			$router->add_rewrite_rules();
			flush_rewrite_rules( false );
		}
	}
}
