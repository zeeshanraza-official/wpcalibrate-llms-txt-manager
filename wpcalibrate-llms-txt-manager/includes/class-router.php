<?php
/**
 * Virtual /llms.txt router and endpoint server.
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
 * Class Router
 *
 * Registers the virtual /llms.txt route and handles template interception to output pure plain-text Markdown.
 */
class Router {

	public const QUERY_VAR = 'wpcllm_endpoint';

	/**
	 * Register hooks for routing.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'handle_endpoint_request' ], 0 );
	}

	/**
	 * Register rewrite rule for /llms.txt.
	 */
	public function add_rewrite_rules(): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Register query variable.
	 *
	 * @param list<string> $vars Existing query vars.
	 * @return list<string> Updated query vars.
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Handle request interception when /llms.txt is requested.
	 */
	public function handle_endpoint_request(): void {
		if ( ! $this->is_llms_txt_request() ) {
			return;
		}

		$settings = Options::get_settings();

		// If management/serving is disabled, do not serve content. Allow WP to 404 naturally.
		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		$published = Options::get_published();

		// If no published content exists, allow WP to 404 naturally.
		if ( ! $published || empty( $published['raw_markdown'] ) ) {
			return;
		}

		// Retrieve content (from cache if active, otherwise from published record).
		$cache   = new Cache();
		$content = $cache->get();

		if ( null === $content ) {
			$content = (string) $published['raw_markdown'];
			$cache->set( $content );
		}

		/**
		 * Filter final llms.txt content served publicly.
		 *
		 * @param string               $content   Raw plain-text Markdown content.
		 * @param array<string, mixed> $published Published record data.
		 */
		$content = (string) apply_filters( 'wpcllm_serve_llms_txt_content', $content, $published );

		$etag = '"' . md5( $content ) . '"';

		// Handle client-side conditional request (304 Not Modified).
		if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && trim( (string) $_SERVER['HTTP_IF_NONE_MATCH'] ) === $etag ) {
			status_header( 304 );
			exit;
		}

		// Send appropriate HTTP headers.
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'ETag: ' . $etag );

		$robots = (string) apply_filters( 'wpcllm_robots_tag', 'index, follow' );
		if ( '' !== $robots ) {
			header( 'X-Robots-Tag: ' . $robots );
		}

		if ( ! empty( $settings['enable_cache'] ) ) {
			$ttl = absint( $settings['cache_ttl'] ?? 3600 );
			header( 'Cache-Control: public, max-age=' . $ttl );
		} else {
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
		}

		if ( ! empty( $published['published_at'] ) ) {
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', (int) $published['published_at'] ) . ' GMT' );
		}

		// Output exact content without theme wrappers and terminate cleanly.
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text output intended.
		exit;
	}

	/**
	 * Determine if current request matches /llms.txt.
	 *
	 * @return bool True if this is an /llms.txt request.
	 */
	public function is_llms_txt_request(): bool {
		// 1. Check query var from rewrite engine.
		if ( '1' === (string) get_query_var( self::QUERY_VAR ) ) {
			return true;
		}

		// 2. Direct URI inspection fallback for plain permalinks or non-rewritten requests.
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$req_path  = (string) wp_parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			$home_path = (string) wp_parse_url( home_url(), PHP_URL_PATH );
			$rel_path  = trim( substr( $req_path, strlen( $home_path ) ), '/' );

			if ( 'llms.txt' === strtolower( $rel_path ) ) {
				return true;
			}
		}

		return false;
	}
}
