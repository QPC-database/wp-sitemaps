<?php
/**
 * Posts sitemap.
 *
 * @package Core_Sitemaps
 */

/**
 * Class Core_Sitemaps_Posts.
 * Builds the sitemap pages for Posts.
 */
class Core_Sitemaps_Posts extends Core_Sitemaps_Provider {
	/**
	 * Core_Sitemaps_Posts constructor.
	 */
	public function __construct() {
		$this->object_type = 'post';
		$this->route       = '^sitemap-posts-([A-z]+)-?([0-9]+)?\.xml$';
		$this->slug        = 'posts';
	}

	/**
	 * Produce XML to output.
	 *
	 * @noinspection PhpUnused
	 */
	public function render_sitemap() {
		global $wp_query;

		$sitemap  = get_query_var( 'sitemap' );
		$sub_type = get_query_var( 'sub_type' );
		$paged    = get_query_var( 'paged' );

		if ( $this->slug === $sitemap ) {
			if ( empty( $paged ) ) {
				$paged = 1;
			}

			$sub_types = $this->get_object_sub_types();

			if ( ! isset( $sub_types[ $sub_type ] ) ) {
				// Invalid sub type.
				$wp_query->set_404();
				status_header( 404 );

				return;
			}

			$this->sub_type = $sub_types[ $sub_type ]->name;

			$url_list = $this->get_url_list( $paged );
			$renderer = new Core_Sitemaps_Renderer();
			$renderer->render_sitemap( $url_list );
			exit;
		}
	}

	/**
	 * Return the public post types, which excludes nav_items and similar types.
	 * Attachments are also excluded. This includes custom post types with public = true
	 *
	 * @return array $post_types List of registered object sub types.
	 */
	public function get_object_sub_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $post_types['attachment'] );

		/**
		 * Filter the list of post object sub types available within the sitemap.
		 *
		 * @since 0.1.0
		 * @param array $post_types List of registered object sub types.
		 */
		return apply_filters( 'core_sitemaps_post_types', $post_types );
	}

	/**
	 * Query for the Posts add_rewrite_rule.
	 *
	 * @return string Valid add_rewrite_rule query.
	 */
	public function rewrite_query() {
		return 'index.php?sitemap=' . $this->slug . '&sub_type=$matches[1]&paged=$matches[2]';
	}

	public function get_sitemaps() {
		$sitemaps = array();

		foreach ( $this->get_object_sub_types() as $type ) {
			$query = $this->index_query( $type->name );

			$total = isset( $query->max_num_pages ) ? $query->max_num_pages : 1;
			for ( $i = 1; $i <= $total; $i ++ ) {
				$slug       = implode( '-', array_filter( array( $this->slug, $type->name, (string) $i ) ) );
				$sitemaps[] = $slug;
			}
		}

		return $sitemaps;
	}
}
