<?php
/**
 * Safe detection and inspection of physical llms.txt files.
 *
 * @package WPCalibrate\LlmsTxtManager\Includes
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Includes;

use WP_Error;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Physical_File_Detector
 *
 * Detects physical llms.txt files in the site document root/ABSPATH safely without destructive side-effects.
 */
class Physical_File_Detector {

	public const STATUS_TRANSIENT = 'wpcllm_physical_file_status';

	/**
	 * Detect if a physical llms.txt file exists.
	 *
	 * @param bool $force_fresh Bypass transient cache.
	 * @return array{
	 *     exists: bool,
	 *     path: string,
	 *     size: int,
	 *     readable: bool,
	 *     writable: bool,
	 *     modified_at: int
	 * }
	 */
	public function detect( bool $force_fresh = false ): array {
		if ( ! $force_fresh ) {
			$cached = get_transient( self::STATUS_TRANSIENT );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$paths_to_check = $this->get_candidate_paths();
		$found_info     = [
			'exists'      => false,
			'path'        => '',
			'size'        => 0,
			'readable'    => false,
			'writable'    => false,
			'modified_at' => 0,
		];

		foreach ( $paths_to_check as $path ) {
			if ( file_exists( $path ) && is_file( $path ) ) {
				$found_info = [
					'exists'      => true,
					'path'        => $path,
					'size'        => (int) filesize( $path ),
					'readable'    => is_readable( $path ),
					'writable'    => is_writable( $path ),
					'modified_at' => (int) filemtime( $path ),
				];
				break;
			}
		}

		// Cache status for 10 minutes.
		set_transient( self::STATUS_TRANSIENT, $found_info, 600 );

		return $found_info;
	}

	/**
	 * Invalidate physical detection transient.
	 */
	public function invalidate(): void {
		delete_transient( self::STATUS_TRANSIENT );
	}

	/**
	 * Read the physical file content safely.
	 *
	 * @return string|WP_Error
	 */
	public function read_content(): string|WP_Error {
		$info = $this->detect( true );
		if ( ! $info['exists'] || empty( $info['path'] ) ) {
			return new WP_Error( 'file_not_found', __( 'No physical llms.txt file was found to read.', 'wpcalibrate-llms-txt-manager' ) );
		}

		if ( ! $info['readable'] ) {
			return new WP_Error( 'file_unreadable', __( 'The physical llms.txt file is not readable due to server file permissions.', 'wpcalibrate-llms-txt-manager' ) );
		}

		$content = file_get_contents( $info['path'] );
		if ( false === $content ) {
			return new WP_Error( 'read_failed', __( 'Could not read physical llms.txt content.', 'wpcalibrate-llms-txt-manager' ) );
		}

		$importer = new Importer();
		return $importer->strip_bom( $content );
	}

	/**
	 * Safely remove physical file using WordPress Filesystem API.
	 * Advanced operation requiring explicit confirmation.
	 *
	 * @return true|WP_Error
	 */
	public function remove_file(): true|WP_Error {
		$info = $this->detect( true );
		if ( ! $info['exists'] || empty( $info['path'] ) ) {
			return new WP_Error( 'not_found', __( 'No physical llms.txt file exists to remove.', 'wpcalibrate-llms-txt-manager' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return new WP_Error( 'fs_init_failed', __( 'Could not initialize WordPress Filesystem.', 'wpcalibrate-llms-txt-manager' ) );
		}

		// Backup content before removing.
		$backup_content = $this->read_content();
		if ( is_string( $backup_content ) ) {
			update_option( 'wpcllm_physical_backup', [
				'content'    => $backup_content,
				'removed_at' => time(),
				'path'       => $info['path'],
			], false );
		}

		$deleted = $wp_filesystem->delete( $info['path'] );
		$this->invalidate();

		if ( ! $deleted ) {
			return new WP_Error( 'delete_failed', __( 'Failed to remove physical llms.txt. Please verify file permissions.', 'wpcalibrate-llms-txt-manager' ) );
		}

		return true;
	}

	/**
	 * Get candidate filesystem paths where llms.txt might reside.
	 *
	 * @return list<string> Candidate paths.
	 */
	private function get_candidate_paths(): array {
		$paths = [];

		if ( function_exists( 'get_home_path' ) ) {
			$home_path = get_home_path();
			if ( ! empty( $home_path ) ) {
				$paths[] = trailingslashit( $home_path ) . 'llms.txt';
			}
		}

		if ( defined( 'ABSPATH' ) ) {
			$paths[] = trailingslashit( ABSPATH ) . 'llms.txt';
		}

		if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$paths[] = trailingslashit( (string) $_SERVER['DOCUMENT_ROOT'] ) . 'llms.txt';
		}

		return array_values( array_unique( $paths ) );
	}
}
