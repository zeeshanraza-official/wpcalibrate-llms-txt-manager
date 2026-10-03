<?php
/**
 * Main plugin orchestration class.
 *
 * @package WPCalibrate\LlmsTxtManager\Includes
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Includes;

use WPCalibrate\LlmsTxtManager\Admin\Admin;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Coordinates initialization, routing, admin interface, and lifecycle hooks.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	private Router $router;

	/**
	 * Upgrader instance.
	 *
	 * @var Upgrader
	 */
	private Upgrader $upgrader;

	/**
	 * Admin instance.
	 *
	 * @var Admin|null
	 */
	private ?Admin $admin = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->router   = new Router();
		$this->upgrader = new Upgrader();

		$this->init_hooks();
	}

	/**
	 * Initialize plugin hooks.
	 */
	private function init_hooks(): void {
		// Translation loading.
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Schema upgrade check on admin init.
		add_action( 'admin_init', [ $this->upgrader, 'check_and_upgrade' ] );

		// Register public virtual route.
		$this->router->register();

		// Admin interface initialization.
		if ( is_admin() ) {
			$this->admin = new Admin();
			$this->admin->register();

			// GitHub in-dashboard auto-updater.
			try {
				new GitHub_Updater();
			} catch ( \Throwable $e ) {
				error_log( 'WPCalibrate LLMs.txt Manager GitHub Updater error: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Load plugin localization files.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wpcalibrate-llms-txt-manager',
			false,
			dirname( WPCLLM_PLUGIN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Get Router instance.
	 *
	 * @return Router
	 */
	public function get_router(): Router {
		return $this->router;
	}

	/**
	 * Get Admin instance.
	 *
	 * @return Admin|null
	 */
	public function get_admin(): ?Admin {
		return $this->admin;
	}
}
