<?php
/**
 * Admin coordinator and screen manager.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin;

use WPCalibrate\LlmsTxtManager\Includes\Content_Repository;
use WPCalibrate\LlmsTxtManager\Includes\Generator;
use WPCalibrate\LlmsTxtManager\Includes\Options;
use WPCalibrate\LlmsTxtManager\Includes\Physical_File_Detector;
use WPCalibrate\LlmsTxtManager\Includes\Validator;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 *
 * Orchestrates administrative interfaces, asset enqueuing, AJAX dispatchers, and request handlers.
 */
class Admin {

	public const NONCE_ACTION = 'wpcllm_admin_action';
	public const NONCE_NAME   = 'wpcllm_nonce';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Flash message storage.
	 *
	 * @var array{type: string, message: string, errors?: list<string>, warnings?: list<string>}|null
	 */
	private ?array $flash_notice = null;

	/**
	 * Menu instance.
	 *
	 * @var Menu
	 */
	private Menu $menu;

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
	public function __construct() {
		$this->menu = new Menu();
	}

	/**
	 * Register admin hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this->menu, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'handle_form_submissions' ] );

		// Register AJAX actions.
		add_action( 'wp_ajax_wpcllm_search_posts', [ $this, 'ajax_search_posts' ] );
		add_action( 'wp_ajax_wpcllm_generate_suggestions', [ $this, 'ajax_generate_suggestions' ] );
		add_action( 'wp_ajax_wpcllm_validate_content', [ $this, 'ajax_validate_content' ] );
		add_action( 'wp_ajax_wpcllm_preview_output', [ $this, 'ajax_preview_output' ] );
		add_action( 'wp_ajax_wpcllm_self_check', [ $this, 'ajax_self_check' ] );
	}

	/**
	 * Check if current page is the plugin screen.
	 *
	 * @param string|null $hook Current screen hook.
	 * @return bool
	 */
	public function is_plugin_screen( ?string $hook = null ): bool {
		// First verify that the current page query parameter belongs to this plugin.
		if ( ! Menu::is_current_page() ) {
			return false;
		}

		if ( null === $hook ) {
			$screen = get_current_screen();
			$hook   = $screen ? $screen->id : '';
		}

		return str_contains( $hook, Menu::SUBMENU_SLUG )
			|| ( Menu::owns_parent() && str_contains( $hook, Menu::PARENT_SLUG ) );
	}

	/**
	 * Enqueue assets strictly on the plugin admin screen.
	 *
	 * @param string $hook Screen hook identifier.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! $this->is_plugin_screen( $hook ) ) {
			return;
		}

		// CSS.
		wp_enqueue_style(
			'wpcllm-admin-css',
			WPCLLM_PLUGIN_URL . 'assets/css/admin.css',
			[],
			WPCLLM_VERSION
		);

		// JS.
		wp_enqueue_script(
			'wpcllm-admin-js',
			WPCLLM_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WPCLLM_VERSION,
			true
		);

		// Localize script data.
		wp_localize_script(
			'wpcllm-admin-js',
			'wpcllmConfig',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( self::NONCE_ACTION ),
				'homeUrl'   => home_url( '/' ),
				'publicUrl' => home_url( '/llms.txt' ),
				'i18n'      => [
					'confirmReset'           => __( 'Are you sure you want to reset your draft? Any unsaved edits will be replaced with starting defaults.', 'wpcalibrate-llms-txt-manager' ),
					'confirmRemovePhysical'  => __( 'Are you sure you want to remove the physical llms.txt file from your server? A backup will be saved.', 'wpcalibrate-llms-txt-manager' ),
					'confirmImportOverwrite' => __( 'Importing this file will replace your current draft content. Proceed?', 'wpcalibrate-llms-txt-manager' ),
					'confirmSwitchRaw'       => __( 'Switching to Raw Markdown mode will generate Markdown from your structured sections. Any custom raw formatting will take priority. Continue?', 'wpcalibrate-llms-txt-manager' ),
					'confirmSwitchBuilder'   => __( 'Switching to Builder mode will parse your Raw Markdown. Any non-standard markdown may be normalized. Continue?', 'wpcalibrate-llms-txt-manager' ),
					'copied'                 => __( 'Copied to clipboard!', 'wpcalibrate-llms-txt-manager' ),
					'searching'              => __( 'Searching WordPress content...', 'wpcalibrate-llms-txt-manager' ),
					'noResults'              => __( 'No public content matching your query was found.', 'wpcalibrate-llms-txt-manager' ),
					'runningCheck'           => __( 'Testing public endpoint...', 'wpcalibrate-llms-txt-manager' ),
					'addSection'             => __( 'New Section', 'wpcalibrate-llms-txt-manager' ),
					'addResource'            => __( 'New Resource', 'wpcalibrate-llms-txt-manager' ),
				],
			]
		);
	}

	/**
	 * Handle admin POST submissions.
	 */
	public function handle_form_submissions(): void {
		if ( empty( $_POST['wpcllm_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// Check export GET action.
			if ( ! empty( $_GET['wpcllm_export'] ) && isset( $_GET['_wpnonce'] ) ) {
				if ( ! current_user_can( Menu::get_capability() ) ) {
					wp_die( esc_html__( 'Unauthorized request.', 'wpcalibrate-llms-txt-manager' ) );
				}
				if ( ! wp_verify_nonce( sanitize_text_field( (string) $_GET['_wpnonce'] ), self::NONCE_ACTION ) ) {
					wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'wpcalibrate-llms-txt-manager' ) );
				}
				$source = sanitize_key( (string) $_GET['wpcllm_export'] );
				$import = new Import();
				$import->handle_export( $source );
			}
			return;
		}

		if ( ! current_user_can( Menu::get_capability() ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'wpcalibrate-llms-txt-manager' ) );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$action    = sanitize_key( (string) $_POST['wpcllm_action'] );
		$post_data = wp_unslash( $_POST );

		switch ( $action ) {
			case 'builder_save_draft':
				$builder            = new Builder();
				$this->flash_notice = $builder->handle_save( $post_data, 'save_draft' );
				break;

			case 'builder_publish':
				$builder            = new Builder();
				$this->flash_notice = $builder->handle_save( $post_data, 'publish' );
				break;

			case 'raw_save_draft':
				$raw_editor         = new Raw_Editor();
				$this->flash_notice = $raw_editor->handle_save( $post_data, 'save_draft' );
				break;

			case 'raw_publish':
				$raw_editor         = new Raw_Editor();
				$this->flash_notice = $raw_editor->handle_save( $post_data, 'publish' );
				break;

			case 'reset_draft':
				Options::reset_draft();
				$this->flash_notice = [
					'success'  => true,
					'message'  => __( 'Draft content was reset to WordPress starting defaults.', 'wpcalibrate-llms-txt-manager' ),
					'errors'   => [],
					'warnings' => [],
				];
				break;

			case 'save_settings':
				$settings_ctrl      = new Settings();
				$this->flash_notice = $settings_ctrl->handle_save( $post_data );
				break;

			case 'import_file':
				if ( empty( $_FILES['llms_file'] ) || ! is_array( $_FILES['llms_file'] ) ) {
					$this->flash_notice = [
						'success'  => false,
						'message'  => __( 'No file was selected for upload.', 'wpcalibrate-llms-txt-manager' ),
						'errors'   => [ __( 'Please choose a valid .txt or .md file.', 'wpcalibrate-llms-txt-manager' ) ],
						'warnings' => [],
					];
				} else {
					$import             = new Import();
					$this->flash_notice = $import->handle_file_upload( $_FILES['llms_file'] );
				}
				break;

			case 'import_physical':
				$import             = new Import();
				$this->flash_notice = $import->handle_physical_import();
				break;

			case 'remove_physical':
				$import             = new Import();
				$this->flash_notice = $import->handle_physical_remove();
				break;
		}
	}

	/**
	 * AJAX: Search WordPress public posts/pages.
	 */
	public function ajax_search_posts(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Menu::get_capability() ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'wpcalibrate-llms-txt-manager' ) ], 403 );
		}

		$query      = isset( $_POST['query'] ) ? sanitize_text_field( (string) $_POST['query'] ) : '';
		$post_types = isset( $_POST['post_types'] ) && is_array( $_POST['post_types'] )
			? array_map( 'sanitize_key', $_POST['post_types'] )
			: [];

		$repo    = new Content_Repository();
		$results = $repo->search_content( $query, $post_types, 20 );

		wp_send_json_success( [ 'items' => $results ] );
	}

	/**
	 * AJAX: Generate intelligent starting suggestions.
	 */
	public function ajax_generate_suggestions(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Menu::get_capability() ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'wpcalibrate-llms-txt-manager' ) ], 403 );
		}

		$repo        = new Content_Repository();
		$suggestions = $repo->generate_starting_suggestions();

		wp_send_json_success( [ 'suggestions' => $suggestions ] );
	}

	/**
	 * AJAX: Validate content.
	 */
	public function ajax_validate_content(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Menu::get_capability() ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'wpcalibrate-llms-txt-manager' ) ], 403 );
		}

		$markdown = isset( $_POST['markdown'] ) ? wp_unslash( (string) $_POST['markdown'] ) : '';

		$validator = new Validator();
		$result    = $validator->validate( $markdown );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Preview output.
	 */
	public function ajax_preview_output(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Menu::get_capability() ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'wpcalibrate-llms-txt-manager' ) ], 403 );
		}

		$source = isset( $_POST['source'] ) ? sanitize_key( (string) $_POST['source'] ) : 'draft';

		if ( 'published' === $source ) {
			$pub = Options::get_published();
			$md  = $pub['raw_markdown'] ?? __( '# No content currently published.', 'wpcalibrate-llms-txt-manager' );
		} else {
			$draft = Options::get_draft();
			$md    = $draft['raw_markdown'] ?? '';
		}

		$validator = new Validator();
		$val_res   = $validator->validate( $md );

		wp_send_json_success(
			[
				'markdown'   => $md,
				'validation' => $val_res,
			]
		);
	}

	/**
	 * AJAX: Execute public self-check.
	 */
	public function ajax_self_check(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Menu::get_capability() ) ) {
			wp_send_json_error( [ 'message' => __( 'Forbidden', 'wpcalibrate-llms-txt-manager' ) ], 403 );
		}

		$status = new Status();
		$result = $status->run_self_check();

		wp_send_json_success( $result );
	}

	/**
	 * Render the administrative screen.
	 */
	public function render_screen(): void {
		// Determine current active tab.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'dashboard';
		$valid_tabs = [ 'dashboard', 'builder', 'raw_editor', 'import', 'settings', 'status' ];

		if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
			$active_tab = 'dashboard';
		}

		// Retrieve data models.
		$settings  = Options::get_settings();
		$draft     = Options::get_draft();
		$published = Options::get_published();

		// Physical file status.
		$detector  = new Physical_File_Detector();
		$phys_info = $detector->detect();

		// Notice to display.
		$notice = $this->flash_notice;

		// Include main layout view.
		include WPCLLM_PLUGIN_DIR . 'admin/views/layout.php';
	}
}
