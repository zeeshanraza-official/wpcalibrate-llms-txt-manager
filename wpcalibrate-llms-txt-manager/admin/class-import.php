<?php
/**
 * Import & physical file admin controller.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin;

use WPCalibrate\LlmsTxtManager\Includes\Importer;
use WPCalibrate\LlmsTxtManager\Includes\Options;
use WPCalibrate\LlmsTxtManager\Includes\Physical_File_Detector;
use WPCalibrate\LlmsTxtManager\Includes\Validator;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Import
 *
 * Handles file uploads, physical file importing/removal, and configuration exports.
 */
class Import {

	/**
	 * Handle uploaded file import.
	 *
	 * @param array<string, mixed> $file Uploaded $_FILES['llms_file'] item.
	 * @return array{success: bool, message: string, errors: list<string>, warnings: list<string>}
	 */
	public function handle_file_upload( array $file ): array {
		$importer = new Importer();
		$content  = $importer->handle_file_upload( $file );

		if ( is_wp_error( $content ) ) {
			return [
				'success'  => false,
				'message'  => $content->get_error_message(),
				'errors'   => [ $content->get_error_message() ],
				'warnings' => [],
			];
		}

		return $this->import_markdown_content( $content, __( 'File imported successfully into draft.', 'wpcalibrate-llms-txt-manager' ) );
	}

	/**
	 * Handle import of detected physical llms.txt.
	 *
	 * @return array{success: bool, message: string, errors: list<string>, warnings: list<string>}
	 */
	public function handle_physical_import(): array {
		$detector = new Physical_File_Detector();
		$content  = $detector->read_content();

		if ( is_wp_error( $content ) ) {
			return [
				'success'  => false,
				'message'  => $content->get_error_message(),
				'errors'   => [ $content->get_error_message() ],
				'warnings' => [],
			];
		}

		return $this->import_markdown_content( $content, __( 'Physical file imported successfully into draft.', 'wpcalibrate-llms-txt-manager' ) );
	}

	/**
	 * Handle safe removal of detected physical llms.txt file.
	 *
	 * @return array{success: bool, message: string, errors: list<string>, warnings: list<string>}
	 */
	public function handle_physical_remove(): array {
		$detector = new Physical_File_Detector();
		$result   = $detector->remove_file();

		if ( is_wp_error( $result ) ) {
			return [
				'success'  => false,
				'message'  => $result->get_error_message(),
				'errors'   => [ $result->get_error_message() ],
				'warnings' => [],
			];
		}

		return [
			'success'  => true,
			'message'  => __( 'Physical llms.txt file was safely removed. A backup of the contents was saved.', 'wpcalibrate-llms-txt-manager' ),
			'errors'   => [],
			'warnings' => [],
		];
	}

	/**
	 * Export content as a downloadable .txt file.
	 *
	 * @param string $source 'draft' or 'published'.
	 */
	public function handle_export( string $source = 'draft' ): void {
		$data     = 'published' === $source ? Options::get_published() : Options::get_draft();
		$markdown = isset( $data['raw_markdown'] ) ? (string) $data['raw_markdown'] : '';

		if ( empty( $markdown ) ) {
			$markdown = "# Site Documentation\n> No content available.";
		}

		$filename = 'llms-' . $source . '-' . gmdate( 'Y-m-d' ) . '.txt';

		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . esc_attr( $filename ) . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Shared markdown import logic.
	 *
	 * @param string $content         Markdown text.
	 * @param string $success_message Message on success.
	 * @return array{success: bool, message: string, errors: list<string>, warnings: list<string>}
	 */
	private function import_markdown_content( string $content, string $success_message ): array {
		$importer   = new Importer();
		$validator  = new Validator();
		$structured = $importer->parse_to_structured( $content );
		$val_res    = $validator->validate( $content );

		$draft_data = [
			'mode'            => 'builder',
			'title'           => $structured['title'],
			'summary'         => $structured['summary'],
			'additional_info' => $structured['additional_info'],
			'sections'        => $structured['sections'],
			'raw_markdown'    => $content,
			'hash'            => hash( 'sha256', $content ),
		];

		Options::save_draft( $draft_data );

		return [
			'success'  => true,
			'message'  => $success_message,
			'errors'   => $val_res['errors'],
			'warnings' => $val_res['warnings'],
		];
	}
}
