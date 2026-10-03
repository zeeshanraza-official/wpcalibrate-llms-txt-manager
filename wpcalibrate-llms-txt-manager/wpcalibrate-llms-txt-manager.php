<?php
/**
 * Plugin Name:       WPCalibrate LLMs.txt Manager
 * Plugin URI:        https://wpcalibrate.com
 * Description:       Create, generate, edit, import, validate, and serve site /llms.txt content natively and virtually through WordPress.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-llms-txt-manager
 * Domain Path:       /languages
 * Update URI:        https://github.com/zeeshanraza-official/wpcalibrate-llms-txt-manager
 *
 * @package WPCalibrate\LlmsTxtManager
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'WPCLLM_VERSION', '1.0.0' );
define( 'WPCLLM_SCHEMA_VERSION', '1.0.0' );
define( 'WPCLLM_MIN_PHP_VERSION', '8.2.0' );
define( 'WPCLLM_MIN_WP_VERSION', '6.2' );
define( 'WPCLLM_PLUGIN_FILE', __FILE__ );
define( 'WPCLLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPCLLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCLLM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Early PHP version check.
 */
if ( version_compare( PHP_VERSION, WPCLLM_MIN_PHP_VERSION, '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: Required PHP version, 2: Current PHP version */
							__( 'WPCalibrate LLMs.txt Manager requires PHP version %1$s or higher. Your server is currently running PHP %2$s. Please upgrade your PHP version.', 'wpcalibrate-llms-txt-manager' ),
							WPCLLM_MIN_PHP_VERSION,
							PHP_VERSION
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

/**
 * Autoloader for plugin classes.
 *
 * Maps namespace WPCalibrate\LlmsTxtManager to includes/ and admin/ directories.
 * Follows WordPress class naming conventions (class-*.php).
 *
 * @param string $class Fully qualified class name.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'WPCalibrate\\LlmsTxtManager\\';

		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );
		$parts          = explode( '\\', $relative_class );
		$class_name     = array_pop( $parts );

		// Convert Class_Name or ClassName to class-name.
		$dashed    = str_replace( '_', '-', $class_name );
		$dashed    = (string) preg_replace( '/([a-z])([A-Z])/', '$1-$2', $dashed );
		$file_name = 'class-' . strtolower( $dashed ) . '.php';

		// If namespace parts exist, map directly to directory (e.g. Includes -> includes/, Admin -> admin/).
		if ( ! empty( $parts ) ) {
			$sub_dir = strtolower( implode( DIRECTORY_SEPARATOR, $parts ) ) . DIRECTORY_SEPARATOR;
			$path    = WPCLLM_PLUGIN_DIR . $sub_dir . $file_name;
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}

		// Fallback check in includes/ and admin/.
		$paths = [
			WPCLLM_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . $file_name,
			WPCLLM_PLUGIN_DIR . 'admin' . DIRECTORY_SEPARATOR . $file_name,
		];

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
);

/**
 * Activation and deactivation hooks.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		Includes\Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		Includes\Deactivator::deactivate();
	}
);

/**
 * Bootstrap the plugin.
 */
function wpcllm_init_plugin(): Includes\Plugin {
	return Includes\Plugin::get_instance();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\wpcllm_init_plugin' );
