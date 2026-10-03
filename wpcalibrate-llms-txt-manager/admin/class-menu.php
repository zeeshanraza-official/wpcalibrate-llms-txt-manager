<?php
/**
 * Shared WPCalibrate admin menu handler.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Menu
 *
 * Handles registration of the shared WPCalibrate parent menu and LLMs.txt Manager submenu.
 */
class Menu {

	public const PARENT_SLUG = 'wpcalibrate';
	public const SUBMENU_SLUG = 'wpcalibrate-llms-txt-manager';

	/**
	 * Tracks whether this plugin instance registered the shared parent menu.
	 *
	 * @var bool
	 */
	private static bool $owns_parent = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_head', [ $this, 'render_menu_icon_styles' ] );
	}

	/**
	 * Whether this plugin registered the shared WPCalibrate top-level menu.
	 *
	 * @return bool
	 */
	public static function owns_parent(): bool {
		return self::$owns_parent;
	}

	/**
	 * Check whether the current admin request belongs to this plugin.
	 *
	 * @return bool
	 */
	public static function is_current_page(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : '';

		if ( self::SUBMENU_SLUG === $page ) {
			return true;
		}

		if ( self::PARENT_SLUG === $page && self::$owns_parent ) {
			return true;
		}

		return false;
	}

	/**
	 * Generate canonical admin URL for WPCalibrate LLMs.txt Manager.
	 *
	 * Always anchors to self::SUBMENU_SLUG ('wpcalibrate-llms-txt-manager') so
	 * that internal links, tabs, and action redirects never collide with sibling
	 * WPCalibrate plugins sharing the parent 'wpcalibrate' menu.
	 *
	 * @param array<string, mixed> $query_args Additional query parameters.
	 * @return string Fully qualified admin URL.
	 */
	public static function get_admin_url( array $query_args = [] ): string {
		$args = array_merge( [ 'page' => self::SUBMENU_SLUG ], $query_args );
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Get required capability to access the management interface.
	 *
	 * @return string Capability name.
	 */
	public static function get_capability(): string {
		/**
		 * Filter the capability required to manage LLMs.txt.
		 *
		 * @param string $capability Default capability 'manage_options'.
		 */
		return (string) apply_filters( 'wpcllm_manage_capability', 'manage_options' );
	}

	/**
	 * Register admin menus.
	 *
	 * Hooked to admin_menu with priority 10 to cooperate with other WPCalibrate plugins.
	 */
	public function register_menus(): void {
		global $menu;
		$capability = self::get_capability();

		// Check if WPCalibrate parent menu has already been registered by a sibling plugin.
		$parent_exists = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && self::PARENT_SLUG === $item[2] ) {
					$parent_exists = true;
					break;
				}
			}
		}

		// Register parent if not existing yet.
		if ( ! $parent_exists ) {
			self::$owns_parent = true;

			// Brand icon for admin menu (20x20 transparent).
			$icon_url = WPCLLM_PLUGIN_URL . 'branding/icon-white.png';

			add_menu_page(
				__( 'WPCalibrate', 'wpcalibrate-llms-txt-manager' ),
				__( 'WPCalibrate', 'wpcalibrate-llms-txt-manager' ),
				$capability,
				self::PARENT_SLUG,
				[ $this, 'render_main_page' ],
				$icon_url,
				59 // Position right before Separator 2 / WooCommerce / Settings.
			);

			// Rename default duplicate submenu item to "LLMs.txt Manager".
			add_submenu_page(
				self::PARENT_SLUG,
				__( 'LLMs.txt Manager &lsaquo; WPCalibrate', 'wpcalibrate-llms-txt-manager' ),
				__( 'LLMs.txt Manager', 'wpcalibrate-llms-txt-manager' ),
				$capability,
				self::PARENT_SLUG,
				[ $this, 'render_main_page' ]
			);

			// Also register the canonical submenu slug so links to 'wpcalibrate-llms-txt-manager'
			// are always recognized by WordPress and never throw permission errors.
			add_submenu_page(
				null,
				__( 'LLMs.txt Manager &lsaquo; WPCalibrate', 'wpcalibrate-llms-txt-manager' ),
				__( 'LLMs.txt Manager', 'wpcalibrate-llms-txt-manager' ),
				$capability,
				self::SUBMENU_SLUG,
				[ $this, 'render_main_page' ]
			);
		} else {
			self::$owns_parent = false;

			// Parent exists: register our specific submenu under the shared parent.
			add_submenu_page(
				self::PARENT_SLUG,
				__( 'LLMs.txt Manager &lsaquo; WPCalibrate', 'wpcalibrate-llms-txt-manager' ),
				__( 'LLMs.txt Manager', 'wpcalibrate-llms-txt-manager' ),
				$capability,
				self::SUBMENU_SLUG,
				[ $this, 'render_main_page' ]
			);
		}
	}

	/**
	 * Render the main plugin interface.
	 */
	public function render_main_page(): void {
		if ( ! current_user_can( self::get_capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpcalibrate-llms-txt-manager' ) );
		}

		Admin::get_instance()->render_screen();
	}

	/**
	 * Render scoped CSS in admin_head to strictly constrain the sidebar menu icon to 20x20px.
	 *
	 * Prevents high-resolution branding images (e.g. 400x400) from overflowing the WordPress admin menu.
	 */
	public function render_menu_icon_styles(): void {
		?>
		<style id="wpcllm-admin-menu-icon-css">
			#adminmenu li.toplevel_page_wpcalibrate .wp-menu-image img,
			#adminmenu li.toplevel_page_wpcalibrate div.wp-menu-image img,
			#adminmenu li.toplevel_page_wpcalibrate-llms-txt-manager .wp-menu-image img,
			#adminmenu li.toplevel_page_wpcalibrate-llms-txt-manager div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate-llms-txt-manager .wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding: 7px 0 0 0 !important;
				object-fit: contain !important;
				box-sizing: content-box !important;
				display: inline-block !important;
				vertical-align: top !important;
				opacity: 0.7;
				transition: opacity 0.15s ease-in-out;
			}
			#adminmenu li.toplevel_page_wpcalibrate:hover .wp-menu-image img,
			#adminmenu li.toplevel_page_wpcalibrate.wp-has-current-submenu .wp-menu-image img,
			#adminmenu li.toplevel_page_wpcalibrate.current .wp-menu-image img,
			#adminmenu li.toplevel_page_wpcalibrate-llms-txt-manager:hover .wp-menu-image img,
			#adminmenu li.toplevel_page_wpcalibrate-llms-txt-manager.wp-has-current-submenu .wp-menu-image img,
			#adminmenu li.toplevel_page_wpcalibrate-llms-txt-manager.current .wp-menu-image img {
				opacity: 1 !important;
			}
			@media screen and (max-width: 782px) {
				#adminmenu li.toplevel_page_wpcalibrate .wp-menu-image img,
				#adminmenu li.toplevel_page_wpcalibrate-llms-txt-manager .wp-menu-image img {
					width: 20px !important;
					height: 20px !important;
					max-width: 20px !important;
					max-height: 20px !important;
					padding: 7px 0 0 0 !important;
				}
			}
		</style>
		<?php
	}
}
