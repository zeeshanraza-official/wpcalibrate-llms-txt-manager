<?php
/**
 * Diagnostics, status, self-check, and support controller.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin;

use WPCalibrate\LlmsTxtManager\Includes\Cache;
use WPCalibrate\LlmsTxtManager\Includes\Options;
use WPCalibrate\LlmsTxtManager\Includes\Physical_File_Detector;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Status
 *
 * Compiles diagnostic telemetry, manages public self-checks, and provides support metadata.
 */
class Status {

	public const SELF_CHECK_TRANSIENT = 'wpcllm_self_check_result';

	/**
	 * Run HTTP self-check against the public /llms.txt endpoint.
	 *
	 * @return array{
	 *     success: bool,
	 *     status_code: int|null,
	 *     content_type: string,
	 *     matches_published: bool,
	 *     message: string,
	 *     checked_at: int
	 * }
	 */
	public function run_self_check(): array {
		$target_url = home_url( '/llms.txt' );
		$published  = Options::get_published();

		$response = wp_remote_get(
			$target_url,
			[
				'timeout'     => 5,
				'redirection' => 2,
				'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
				'headers'     => [
					'Accept' => 'text/plain',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			$result = [
				'success'           => false,
				'status_code'       => null,
				'content_type'      => '',
				'matches_published' => false,
				'message'           => sprintf(
					/* translators: %s: error message */
					__( 'Loopback request failed: %s. This may occur if your web server blocks internal loopback connections.', 'wpcalibrate-llms-txt-manager' ),
					$response->get_error_message()
				),
				'checked_at'        => time(),
			];
			set_transient( self::SELF_CHECK_TRANSIENT, $result, 300 );
			return $result;
		}

		$status_code  = (int) wp_remote_retrieve_response_code( $response );
		$content_type = (string) wp_remote_retrieve_header( $response, 'content-type' );
		$body         = (string) wp_remote_retrieve_body( $response );
		$server_hash  = hash( 'sha256', $body );

		$pub_hash = $published['hash'] ?? '';
		$matches  = ( '' !== $pub_hash && hash_equals( $pub_hash, $server_hash ) );

		$detector  = new Physical_File_Detector();
		$phys_info = $detector->detect();

		$message = '';
		$success = false;

		if ( 200 === $status_code ) {
			if ( $matches ) {
				$success = true;
				$message = __( 'Public file verified! The server is actively serving your published llms.txt content.', 'wpcalibrate-llms-txt-manager' );
			} elseif ( $phys_info['exists'] ) {
				$message = __( 'The endpoint returned HTTP 200, but the content does not match your published draft. The server is likely serving your physical llms.txt file from disk instead of WordPress.', 'wpcalibrate-llms-txt-manager' );
			} else {
				$message = __( 'The endpoint returned HTTP 200, but content hash differs. A page cache or CDN may be serving an earlier revision.', 'wpcalibrate-llms-txt-manager' );
			}
		} elseif ( 404 === $status_code ) {
			$message = __( 'The endpoint returned HTTP 404 Not Found. Please verify your permalink configuration or ensure the endpoint is enabled in Settings.', 'wpcalibrate-llms-txt-manager' );
		} else {
			$message = sprintf(
				/* translators: %d: HTTP status code */
				__( 'The endpoint returned unexpected HTTP status %d.', 'wpcalibrate-llms-txt-manager' ),
				$status_code
			);
		}

		$result = [
			'success'           => $success,
			'status_code'       => $status_code,
			'content_type'      => $content_type,
			'matches_published' => $matches,
			'message'           => $message,
			'checked_at'        => time(),
		];

		set_transient( self::SELF_CHECK_TRANSIENT, $result, 300 );
		return $result;
	}

	/**
	 * Get cached self-check result.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_cached_self_check(): ?array {
		$cached = get_transient( self::SELF_CHECK_TRANSIENT );
		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * Compile environment diagnostics.
	 *
	 * @return array<string, mixed>
	 */
	public function get_diagnostics(): array {
		global $wp_version, $wp_rewrite;

		$settings  = Options::get_settings();
		$draft     = Options::get_draft();
		$published = Options::get_published();

		$detector  = new Physical_File_Detector();
		$phys_info = $detector->detect();

		$cache = new Cache();

		return [
			'plugin_version'       => WPCLLM_VERSION,
			'schema_version'       => (string) get_option( Options::OPTION_SCHEMA_VERSION, '1.0.0' ),
			'wp_version'           => $wp_version,
			'php_version'          => PHP_VERSION,
			'server_software'      => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
			'site_url'             => site_url(),
			'home_url'             => home_url(),
			'public_url'           => home_url( '/llms.txt' ),
			'permalink_structure'  => get_option( 'permalink_structure' ) ?: 'Plain (?p=123)',
			'rewrite_rules_loaded' => ! empty( $wp_rewrite->rules ),
			'endpoint_enabled'     => ! empty( $settings['enabled'] ),
			'cache_enabled'        => ! empty( $settings['enable_cache'] ),
			'is_cached'            => $cache->is_cached(),
			'draft_mode'           => $draft['mode'] ?? 'builder',
			'draft_updated_at'     => $draft['updated_at'] ?? 0,
			'is_published'         => ! empty( $published ),
			'published_at'         => $published['published_at'] ?? 0,
			'physical_file_exists' => $phys_info['exists'],
			'physical_file_path'   => $phys_info['path'] ?: 'None',
			'physical_file_size'   => $phys_info['size'],
			'physical_readable'    => $phys_info['readable'],
			'physical_writable'    => $phys_info['writable'],
			'license_type'         => 'Free (GPL-2.0-or-later)',
			'license_activation'   => 'Not required',
		];
	}

	/**
	 * Generate copyable markdown report for support.
	 *
	 * @return string Plain text report.
	 */
	public function generate_support_report(): string {
		$diag = $this->get_diagnostics();

		$lines = [
			'### WPCalibrate LLMs.txt Manager Diagnostics',
			'- Plugin Version: ' . $diag['plugin_version'],
			'- WordPress Version: ' . $diag['wp_version'],
			'- PHP Version: ' . $diag['php_version'],
			'- Server Software: ' . $diag['server_software'],
			'- Home URL: ' . $diag['home_url'],
			'- Public llms.txt URL: ' . $diag['public_url'],
			'- Permalink Structure: ' . $diag['permalink_structure'],
			'- Endpoint Enabled: ' . ( $diag['endpoint_enabled'] ? 'Yes' : 'No' ),
			'- Output Cache Enabled: ' . ( $diag['cache_enabled'] ? 'Yes' : 'No' ),
			'- Is Output Cached: ' . ( $diag['is_cached'] ? 'Yes' : 'No' ),
			'- Published: ' . ( $diag['is_published'] ? 'Yes (' . gmdate( 'Y-m-d H:i:s', (int) $diag['published_at'] ) . ' UTC)' : 'No' ),
			'- Physical llms.txt Detected: ' . ( $diag['physical_file_exists'] ? 'Yes (' . $diag['physical_file_path'] . ')' : 'No' ),
			'- License: ' . $diag['license_type'] . ' (' . $diag['license_activation'] . ')',
		];

		return implode( "\n", $lines );
	}
}
