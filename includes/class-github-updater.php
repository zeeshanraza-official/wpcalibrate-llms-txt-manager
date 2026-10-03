<?php
/**
 * GitHub In-Dashboard Release Updater.
 *
 * Enables seamless one-click plugin updates directly from the WordPress dashboard
 * whenever a new release is published on GitHub.
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
 * Class GitHub_Updater
 */
class GitHub_Updater {

	/**
	 * GitHub repository owner.
	 *
	 * @var string
	 */
	private string $repo_owner;

	/**
	 * GitHub repository name.
	 *
	 * @var string
	 */
	private string $repo_name;

	/**
	 * Transient cache key for GitHub release data.
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'wpcllm_github_release_info';

	/**
	 * Cache TTL in seconds (6 hours).
	 *
	 * @var int
	 */
	private const CACHE_TTL = 21600;

	/**
	 * Constructor.
	 *
	 * @param string $repo_owner GitHub owner/organization.
	 * @param string $repo_name  GitHub repository slug.
	 */
	public function __construct( string $repo_owner = 'wpcalibrate', string $repo_name = 'wpcalibrate-llms-txt-manager' ) {
		$this->repo_owner = $repo_owner;
		$this->repo_name  = $repo_name;

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_plugin_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_info_popup' ], 20, 3 );
		add_filter( 'upgrader_post_install', [ $this, 'upgrader_post_install_cleanup' ], 10, 3 );
	}

	/**
	 * Fetch latest release from GitHub API or transient cache.
	 *
	 * @param bool $force_refresh Whether to bypass transient cache.
	 * @return array<string, mixed>|null
	 */
	public function get_latest_release( bool $force_refresh = false ): ?array {
		if ( ! $force_refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$api_url = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->repo_owner, $this->repo_name );

		global $wp_version;
		$response = wp_remote_get(
			$api_url,
			[
				'timeout'    => 10,
				'headers'    => [
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . ( $wp_version ?? '6.0' ) . '; WPCalibrate-LLMsTxtManager/' . WPCLLM_VERSION,
				],
				'sslverify'  => true,
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			return null;
		}

		set_transient( self::CACHE_KEY, $body, self::CACHE_TTL );
		return $body;
	}

	/**
	 * Hook into WordPress update transient to inject new version details.
	 *
	 * @param mixed $transient Update transient data.
	 * @return mixed
	 */
	public function check_for_plugin_update( mixed $transient ): mixed {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( null === $release ) {
			return $transient;
		}

		$remote_version = ltrim( (string) $release['tag_name'], 'v' );
		if ( version_compare( $remote_version, WPCLLM_VERSION, '<=' ) ) {
			return $transient;
		}

		// Find zip asset attached to release, or fall back to zipball.
		$download_url = (string) ( $release['zipball_url'] ?? '' );
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( isset( $asset['name'] ) && str_ends_with( (string) $asset['name'], '.zip' ) ) {
					$download_url = (string) $asset['browser_download_url'];
					break;
				}
			}
		}

		if ( empty( $download_url ) ) {
			return $transient;
		}

		$obj              = new \stdClass();
		$obj->id          = 'wpcalibrate-llms-txt-manager';
		$obj->slug        = 'wpcalibrate-llms-txt-manager';
		$obj->plugin      = WPCLLM_PLUGIN_BASENAME;
		$obj->new_version = $remote_version;
		$obj->url         = (string) ( $release['html_url'] ?? 'https://github.com/' . $this->repo_owner . '/' . $this->repo_name );
		$obj->package     = $download_url;
		$obj->tested      = '6.7';
		$obj->requires    = WPCLLM_MIN_WP_VERSION;
		$obj->requires_php = WPCLLM_MIN_PHP_VERSION;

		$transient->response[ WPCLLM_PLUGIN_BASENAME ] = $obj;

		return $transient;
	}

	/**
	 * Provide modal details for the "View version X details" popup.
	 *
	 * @param mixed  $result Current API result.
	 * @param string $action API action.
	 * @param object $args   Arguments object.
	 * @return mixed
	 */
	public function plugin_info_popup( mixed $result, string $action, object $args ): mixed {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'wpcalibrate-llms-txt-manager' !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( null === $release ) {
			return $result;
		}

		$remote_version = ltrim( (string) $release['tag_name'], 'v' );
		$info           = new \stdClass();
		$info->name     = 'WPCalibrate LLMs.txt Manager';
		$info->slug     = 'wpcalibrate-llms-txt-manager';
		$info->version  = $remote_version;
		$info->author   = '<a href="https://wpcalibrate.com">WPCalibrate</a>';
		$info->homepage = 'https://github.com/' . $this->repo_owner . '/' . $this->repo_name;
		$info->requires = WPCLLM_MIN_WP_VERSION;
		$info->requires_php = WPCLLM_MIN_PHP_VERSION;
		$info->tested   = '6.7';
		$info->sections = [
			'description' => esc_html__( 'Create, generate, edit, import, validate, and serve site /llms.txt content natively and virtually through WordPress.', 'wpcalibrate-llms-txt-manager' ),
			'changelog'   => nl2br( esc_html( (string) ( $release['body'] ?? 'See GitHub release notes.' ) ) ),
		];

		// Attach download link
		$download_url = (string) ( $release['zipball_url'] ?? '' );
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( isset( $asset['name'] ) && str_ends_with( (string) $asset['name'], '.zip' ) ) {
					$download_url = (string) $asset['browser_download_url'];
					break;
				}
			}
		}
		$info->download_link = $download_url;

		return $info;
	}

	/**
	 * Ensure extracted folder is cleanly named 'wpcalibrate-llms-txt-manager'.
	 *
	 * When downloading from GitHub zipballs, GitHub names the root folder
	 * '{owner}-{repo}-{hash}/'. This hook renames the folder so WordPress
	 * doesn't deactivate or duplicate the plugin.
	 *
	 * @param bool|array<string, mixed> $response Installation response.
	 * @param array<string, mixed>      $hook_extra Extra hook information.
	 * @param array<string, mixed>      $result Installation result.
	 * @return array<string, mixed>
	 */
	public function upgrader_post_install_cleanup( mixed $response, array $hook_extra, array $result ): array {
		if ( empty( $hook_extra['plugin'] ) || WPCLLM_PLUGIN_BASENAME !== $hook_extra['plugin'] ) {
			return is_array( $result ) ? $result : [];
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			\WP_Filesystem();
		}

		$proper_destination = WP_PLUGIN_DIR . '/wpcalibrate-llms-txt-manager';
		$current_source     = $result['destination'] ?? '';

		if ( ! empty( $current_source ) && $current_source !== $proper_destination && $wp_filesystem->exists( $current_source ) ) {
			$wp_filesystem->move( $current_source, $proper_destination, true );
			$result['destination'] = $proper_destination;
		}

		return $result;
	}
}
