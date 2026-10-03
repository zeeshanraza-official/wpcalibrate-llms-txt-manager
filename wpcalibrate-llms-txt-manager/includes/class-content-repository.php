<?php
/**
 * Content repository for querying and suggesting WordPress resources.
 *
 * @package WPCalibrate\LlmsTxtManager\Includes
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Includes;

use WP_Query;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Repository
 *
 * Provides safe, performant search and intelligent suggestion generation of public WordPress content.
 */
class Content_Repository {

	/**
	 * Get list of eligible public post types for selection/generation.
	 *
	 * @return array<string, string> Associative array of post type name => label.
	 */
	public function get_eligible_post_types(): array {
		$types = get_post_types(
			[
				'public' => true,
			],
			'objects'
		);

		$allowed = [];
		foreach ( $types as $name => $obj ) {
			// Exclude attachments and internal post types.
			if ( in_array( $name, [ 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset' ], true ) ) {
				continue;
			}
			$allowed[ $name ] = $obj->labels->singular_name ?: $obj->label ?: $name;
		}

		/**
		 * Filter eligible post types for llms.txt generation.
		 *
		 * @param array<string, string> $allowed Post types.
		 */
		return (array) apply_filters( 'wpcllm_eligible_post_types', $allowed );
	}

	/**
	 * Search public WordPress content.
	 *
	 * @param string      $search_term Search query string.
	 * @param array<string> $post_types  Post types to search.
	 * @param int         $limit       Max results to return.
	 * @return list<array{
	 *     id: int,
	 *     title: string,
	 *     url: string,
	 *     post_type: string,
	 *     post_type_label: string,
	 *     description: string
	 * }> Search results.
	 */
	public function search_content( string $search_term = '', array $post_types = [], int $limit = 20 ): array {
		$eligible_types = array_keys( $this->get_eligible_post_types() );
		$types_to_query = ! empty( $post_types ) ? array_intersect( $post_types, $eligible_types ) : $eligible_types;

		if ( empty( $types_to_query ) ) {
			$types_to_query = [ 'page', 'post' ];
		}

		$args = [
			'post_type'              => $types_to_query,
			'post_status'            => 'publish',
			'has_password'           => false,
			'posts_per_page'         => max( 1, min( 50, $limit ) ),
			'orderby'                => 'relevance',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		if ( '' !== trim( $search_term ) ) {
			$args['s'] = sanitize_text_field( $search_term );
		} else {
			$args['orderby'] = 'date';
		}

		$query   = new WP_Query( $args );
		$results = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$title     = get_the_title();
				$permalink = get_permalink( $post_id );

				if ( ! $permalink ) {
					continue;
				}

				// Extract clean excerpt for suggested description.
				$raw_excerpt = get_the_excerpt( $post_id );
				$clean_desc  = wp_strip_all_tags( $raw_excerpt );
				$clean_desc  = trim( (string) preg_replace( '/\s+/', ' ', $clean_desc ) );

				$pt_obj   = get_post_type_object( get_post_type( $post_id ) );
				$pt_label = $pt_obj ? ( $pt_obj->labels->singular_name ?: $pt_obj->label ) : get_post_type( $post_id );

				$results[] = [
					'id'              => $post_id,
					'title'           => $title ?: sprintf( __( 'Post #%d', 'wpcalibrate-llms-txt-manager' ), $post_id ),
					'url'             => $permalink,
					'post_type'       => get_post_type( $post_id ),
					'post_type_label' => $pt_label,
					'description'     => $clean_desc,
				];
			}
			wp_reset_postdata();
		}

		return $results;
	}

	/**
	 * Generate starting suggestions based on transparent priority heuristics.
	 *
	 * @return array<string, mixed> Structured suggestions ready for review.
	 */
	public function generate_starting_suggestions(): array {
		$site_title = get_bloginfo( 'name' ) ?: 'Site Documentation';
		$site_desc  = get_bloginfo( 'description' ) ?: 'Curated documentation and primary resources for large language models and agents.';

		$core_resources     = [];
		$article_resources  = [];
		$optional_resources = [];
		$included_ids       = [];

		// 1. Front Page.
		$front_id = (int) get_option( 'page_on_front' );
		if ( $front_id > 0 && 'publish' === get_post_status( $front_id ) ) {
			$core_resources[] = $this->format_resource_from_post( $front_id, __( 'Home Page', 'wpcalibrate-llms-txt-manager' ) );
			$included_ids[]   = $front_id;
		} else {
			// Fallback home URL.
			$core_resources[] = [
				'id'          => 'res_' . wp_generate_password( 8, false ),
				'title'       => __( 'Home Page', 'wpcalibrate-llms-txt-manager' ),
				'url'         => home_url( '/' ),
				'description' => __( 'Official website overview and landing page.', 'wpcalibrate-llms-txt-manager' ),
				'post_id'     => 0,
			];
		}

		// 2. Posts Page if set.
		$posts_page_id = (int) get_option( 'page_for_posts' );
		if ( $posts_page_id > 0 && 'publish' === get_post_status( $posts_page_id ) ) {
			$core_resources[] = $this->format_resource_from_post( $posts_page_id, __( 'Blog / Articles', 'wpcalibrate-llms-txt-manager' ) );
			$included_ids[]   = $posts_page_id;
		}

		// 3. Search for high-value public pages.
		$priority_keywords = [
			'about'         => __( 'About', 'wpcalibrate-llms-txt-manager' ),
			'contact'       => __( 'Contact', 'wpcalibrate-llms-txt-manager' ),
			'services'      => __( 'Services', 'wpcalibrate-llms-txt-manager' ),
			'documentation' => __( 'Documentation', 'wpcalibrate-llms-txt-manager' ),
			'docs'          => __( 'Docs', 'wpcalibrate-llms-txt-manager' ),
			'faq'           => __( 'FAQ', 'wpcalibrate-llms-txt-manager' ),
			'pricing'       => __( 'Pricing', 'wpcalibrate-llms-txt-manager' ),
		];

		foreach ( $priority_keywords as $keyword => $label ) {
			$matches = get_posts(
				[
					'post_type'              => 'page',
					'post_status'            => 'publish',
					's'                      => $keyword,
					'posts_per_page'         => 1,
					'post__not_in'           => $included_ids,
					'has_password'           => false,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			if ( ! empty( $matches ) ) {
				$matched_post     = $matches[0];
				$core_resources[] = $this->format_resource_from_post( $matched_post->ID );
				$included_ids[]   = $matched_post->ID;
			}
		}

		// 4. Look for Privacy Policy / Legal for the Optional section.
		$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $privacy_page_id > 0 && 'publish' === get_post_status( $privacy_page_id ) ) {
			$optional_resources[] = $this->format_resource_from_post( $privacy_page_id );
			$included_ids[]       = $privacy_page_id;
		}

		// 5. Query top recent published articles/posts (up to 5).
		$recent_posts = get_posts(
			[
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'posts_per_page'         => 5,
				'post__not_in'           => $included_ids,
				'has_password'           => false,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		foreach ( $recent_posts as $post ) {
			$article_resources[] = $this->format_resource_from_post( $post->ID );
			$included_ids[]      = $post->ID;
		}

		$sections = [
			[
				'id'        => 'sec_' . wp_generate_password( 8, false ),
				'title'     => 'Main Documentation',
				'resources' => $core_resources,
			],
		];

		if ( ! empty( $article_resources ) ) {
			$sections[] = [
				'id'        => 'sec_' . wp_generate_password( 8, false ),
				'title'     => 'Featured Articles',
				'resources' => $article_resources,
			];
		}

		$sections[] = [
			'id'        => 'sec_optional',
			'title'     => 'Optional',
			'resources' => $optional_resources,
		];

		$suggestions = [
			'title'           => $site_title,
			'summary'         => $site_desc,
			'additional_info' => '',
			'sections'        => $sections,
		];

		/**
		 * Filter automatic suggestions.
		 *
		 * @param array<string, mixed> $suggestions Structured suggestions.
		 */
		return (array) apply_filters( 'wpcllm_suggested_sections', $suggestions );
	}

	/**
	 * Format a resource item array from a WordPress Post ID.
	 *
	 * @param int         $post_id Post ID.
	 * @param string|null $fallback_title Optional fallback title.
	 * @return array{
	 *     id: string,
	 *     title: string,
	 *     url: string,
	 *     description: string,
	 *     post_id: int
	 * }
	 */
	public function format_resource_from_post( int $post_id, ?string $fallback_title = null ): array {
		$post  = get_post( $post_id );
		$title = $fallback_title ?: ( $post ? get_the_title( $post ) : '' );
		$url   = get_permalink( $post_id ) ?: home_url( '/' );

		$excerpt = $post ? get_the_excerpt( $post ) : '';
		$desc    = wp_strip_all_tags( $excerpt );
		$desc    = trim( (string) preg_replace( '/\s+/', ' ', $desc ) );

		return [
			'id'          => 'res_' . wp_generate_password( 8, false ),
			'title'       => $title ?: sprintf( __( 'Page #%d', 'wpcalibrate-llms-txt-manager' ), $post_id ),
			'url'         => $url,
			'description' => $desc,
			'post_id'     => $post_id,
		];
	}
}
