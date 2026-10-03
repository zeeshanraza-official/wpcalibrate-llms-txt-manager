<?php
/**
 * Output caching and invalidation manager.
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
 * Class Cache
 *
 * Handles lightweight, deterministic caching of published /llms.txt output.
 */
class Cache {

	public const TRANSIENT_KEY = 'wpcllm_cached_llms_txt';

	/**
	 * Get cached output if available and caching is enabled.
	 *
	 * @return string|null Cached plain text or null.
	 */
	public function get(): ?string {
		$settings = Options::get_settings();
		if ( empty( $settings['enable_cache'] ) ) {
			return null;
		}

		$cached = get_transient( self::TRANSIENT_KEY );
		if ( ! is_string( $cached ) || '' === $cached ) {
			return null;
		}

		return $cached;
	}

	/**
	 * Set cached output with configured TTL.
	 *
	 * @param string $content Plain text Markdown content to cache.
	 * @return bool True on success.
	 */
	public function set( string $content ): bool {
		$settings = Options::get_settings();
		if ( empty( $settings['enable_cache'] ) ) {
			return false;
		}

		$ttl = absint( $settings['cache_ttl'] ?? 3600 );
		if ( 0 === $ttl ) {
			$ttl = 3600;
		}

		return set_transient( self::TRANSIENT_KEY, $content, $ttl );
	}

	/**
	 * Invalidate all cached llms.txt representations immediately.
	 *
	 * @return bool True on success.
	 */
	public function invalidate(): bool {
		return delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Check if cache currently holds valid published data.
	 *
	 * @return bool True if cached.
	 */
	public function is_cached(): bool {
		return false !== get_transient( self::TRANSIENT_KEY );
	}
}
