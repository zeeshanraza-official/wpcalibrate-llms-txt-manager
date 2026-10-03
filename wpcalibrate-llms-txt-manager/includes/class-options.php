<?php
/**
 * Options management class.
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
 * Class Options
 *
 * Provides centralized, type-safe access to plugin options and state.
 */
class Options {

	public const OPTION_SETTINGS       = 'wpcllm_settings';
	public const OPTION_DRAFT          = 'wpcllm_draft';
	public const OPTION_PUBLISHED      = 'wpcllm_published';
	public const OPTION_SCHEMA_VERSION = 'wpcllm_schema_version';

	/**
	 * Get default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_default_settings(): array {
		return [
			'enabled'               => true,
			'editor_mode'           => 'builder', // 'builder' or 'raw'
			'max_import_size_kb'    => 256,
			'enable_cache'          => true,
			'cache_ttl'             => 3600, // 1 hour
			'delete_on_uninstall'   => false,
			'generation_post_types' => [ 'page', 'post' ],
			'max_auto_resources'    => 20,
		];
	}

	/**
	 * Get plugin settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$saved = get_option( self::OPTION_SETTINGS, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		return array_merge( self::get_default_settings(), $saved );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param array<string, mixed> $settings Raw or sanitized settings array.
	 * @return bool True on success, false on failure.
	 */
	public static function update_settings( array $settings ): bool {
		$sanitized = self::sanitize_settings( $settings );
		return update_option( self::OPTION_SETTINGS, $sanitized, true );
	}

	/**
	 * Sanitize plugin settings.
	 *
	 * @param array<string, mixed> $input Input settings.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public static function sanitize_settings( array $input ): array {
		$defaults = self::get_default_settings();
		$output   = [];

		$output['enabled'] = ! empty( $input['enabled'] );

		$editor_mode           = isset( $input['editor_mode'] ) ? sanitize_key( (string) $input['editor_mode'] ) : 'builder';
		$output['editor_mode'] = in_array( $editor_mode, [ 'builder', 'raw' ], true ) ? $editor_mode : 'builder';

		$max_size                     = isset( $input['max_import_size_kb'] ) ? absint( $input['max_import_size_kb'] ) : 256;
		$output['max_import_size_kb'] = max( 16, min( 2048, $max_size ) ); // 16KB to 2MB.

		$output['enable_cache'] = ! empty( $input['enable_cache'] );

		$cache_ttl          = isset( $input['cache_ttl'] ) ? absint( $input['cache_ttl'] ) : 3600;
		$output['cache_ttl'] = max( 60, min( 86400 * 7, $cache_ttl ) ); // 1 minute to 7 days.

		$output['delete_on_uninstall'] = ! empty( $input['delete_on_uninstall'] );

		// Generation post types.
		$post_types = [];
		if ( isset( $input['generation_post_types'] ) && is_array( $input['generation_post_types'] ) ) {
			foreach ( $input['generation_post_types'] as $pt ) {
				$pt_sanitized = sanitize_key( (string) $pt );
				if ( post_type_exists( $pt_sanitized ) ) {
					$post_types[] = $pt_sanitized;
				}
			}
		}
		if ( empty( $post_types ) ) {
			$post_types = [ 'page', 'post' ];
		}
		$output['generation_post_types'] = array_values( array_unique( $post_types ) );

		$max_resources               = isset( $input['max_auto_resources'] ) ? absint( $input['max_auto_resources'] ) : 20;
		$output['max_auto_resources'] = max( 5, min( 100, $max_resources ) );

		return $output;
	}

	/**
	 * Get draft data.
	 *
	 * @return array<string, mixed> Draft structure.
	 */
	public static function get_draft(): array {
		$draft = get_option( self::OPTION_DRAFT, null );
		if ( ! is_array( $draft ) ) {
			return self::get_initial_draft_data();
		}
		return $draft;
	}

	/**
	 * Save draft data without autoloading.
	 *
	 * @param array<string, mixed> $data Draft data.
	 * @return bool True on success.
	 */
	public static function save_draft( array $data ): bool {
		$data['updated_at'] = time();
		$data['updated_by'] = get_current_user_id();
		return update_option( self::OPTION_DRAFT, $data, false );
	}

	/**
	 * Get published data.
	 *
	 * @return array<string, mixed>|null Published structure or null if not published.
	 */
	public static function get_published(): ?array {
		$published = get_option( self::OPTION_PUBLISHED, null );
		if ( ! is_array( $published ) || empty( $published['raw_markdown'] ) ) {
			return null;
		}
		return $published;
	}

	/**
	 * Save published data without autoloading.
	 *
	 * @param array<string, mixed> $data Published data.
	 * @return bool True on success.
	 */
	public static function save_published( array $data ): bool {
		$data['published_at'] = time();
		$data['published_by'] = get_current_user_id();
		return update_option( self::OPTION_PUBLISHED, $data, false );
	}

	/**
	 * Clear published state.
	 *
	 * @return bool True on success.
	 */
	public static function clear_published(): bool {
		return delete_option( self::OPTION_PUBLISHED );
	}

	/**
	 * Reset draft to initial WordPress defaults.
	 *
	 * @return bool True on success.
	 */
	public static function reset_draft(): bool {
		return self::save_draft( self::get_initial_draft_data() );
	}

	/**
	 * Generate initial draft structure with sensible WordPress site defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_initial_draft_data(): array {
		$site_title = get_bloginfo( 'name' );
		if ( empty( $site_title ) ) {
			$site_title = 'My Website';
		}

		$site_desc = get_bloginfo( 'description' );
		if ( empty( $site_desc ) ) {
			$site_desc = 'Curated documentation and primary resources for large language models and agents.';
		}

		$initial_sections = [
			[
				'id'        => 'sec_' . wp_generate_password( 8, false ),
				'title'     => 'Main Documentation',
				'resources' => [
					[
						'id'          => 'res_' . wp_generate_password( 8, false ),
						'title'       => 'Home Page',
						'url'         => home_url( '/' ),
						'description' => 'Official website overview and landing page.',
						'post_id'     => 0,
					],
				],
			],
			[
				'id'        => 'sec_optional',
				'title'     => 'Optional',
				'resources' => [],
			],
		];

		$generator = new Generator();
		$markdown  = $generator->generate(
			[
				'title'           => $site_title,
				'summary'         => $site_desc,
				'additional_info' => '',
				'sections'        => $initial_sections,
			]
		);

		return [
			'mode'            => 'builder',
			'title'           => $site_title,
			'summary'         => $site_desc,
			'additional_info' => '',
			'sections'        => $initial_sections,
			'raw_markdown'    => $markdown,
			'hash'            => hash( 'sha256', $markdown ),
			'updated_at'      => time(),
			'updated_by'      => get_current_user_id(),
		];
	}
}
