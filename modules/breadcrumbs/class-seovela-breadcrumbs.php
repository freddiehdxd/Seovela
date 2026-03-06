<?php
/**
 * Seovela Breadcrumbs Class
 *
 * Generates breadcrumbs with BreadcrumbList schema
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Breadcrumbs Class
 */
class Seovela_Breadcrumbs {

	/**
	 * Breadcrumb items
	 *
	 * @var array
	 */
	private $items = array();

	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_settings();
	}

	/**
	 * Load breadcrumb settings
	 */
	private function load_settings() {
		$defaults = array(
			'enabled'           => true,
			'separator'         => '/',
			'home_text'         => __( 'Home', 'seovela' ),
			'show_home'         => true,
			'show_on_front'     => false,
			'show_post_type'    => true,
			'show_taxonomy'     => true,
			'prefix'            => '',
			'bold_last'         => true,
			'auto_insert'       => false,
			'auto_insert_hook'  => 'seovela_before_content',
			'container_class'   => 'seovela-breadcrumbs',
			'list_class'        => 'breadcrumb-list',
			'item_class'        => 'breadcrumb-item',
			'separator_class'   => 'breadcrumb-separator',
			'link_class'        => 'breadcrumb-link',
			'current_class'     => 'breadcrumb-current',
		);

		$settings = get_option( 'seovela_breadcrumbs_settings', array() );
		$this->settings = wp_parse_args( $settings, $defaults );
	}

	/**
	 * Generate breadcrumbs
	 *
	 * @return array Breadcrumb items
	 */
	public function generate() {
		$this->items = array();

		// Add home
		if ( $this->settings['show_home'] ) {
			$this->add_item(
				$this->settings['home_text'],
				home_url( '/' ),
				true
			);
		}

		// Determine current page type and generate breadcrumbs
		if ( is_front_page() ) {
			// Front page - only show home
			return $this->items;
		} elseif ( is_home() ) {
			// Blog page
			$this->generate_blog_breadcrumbs();
		} elseif ( is_singular() ) {
			// Single post, page, or custom post type
			$this->generate_singular_breadcrumbs();
		} elseif ( is_archive() ) {
			// Archive pages (category, tag, date, author, etc.)
			$this->generate_archive_breadcrumbs();
		} elseif ( is_search() ) {
			// Search results
			$this->generate_search_breadcrumbs();
		} elseif ( is_404() ) {
			// 404 page
			$this->generate_404_breadcrumbs();
		}

		return apply_filters( 'seovela_breadcrumbs_items', $this->items );
	}

	/**
	 * Generate breadcrumbs for blog page
	 */
	private function generate_blog_breadcrumbs() {
		$posts_page_id = get_option( 'page_for_posts' );
		if ( $posts_page_id ) {
			$this->add_item(
				get_the_title( $posts_page_id ),
				get_permalink( $posts_page_id ),
				false
			);
		} else {
			$this->add_item( __( 'Blog', 'seovela' ), '', false );
		}
	}

	/**
	 * Generate breadcrumbs for singular posts/pages
	 */
	private function generate_singular_breadcrumbs() {
		global $post;

		if ( ! $post ) {
			return;
		}

		$post_type = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		// Add post type archive link
		if ( $post_type !== 'post' && $post_type !== 'page' && $this->settings['show_post_type'] ) {
			if ( $post_type_object && $post_type_object->has_archive ) {
				$this->add_item(
					$post_type_object->labels->name,
					get_post_type_archive_link( $post_type ),
					true
				);
			}
		}

		// Add taxonomy breadcrumbs for posts
		if ( $post_type === 'post' && $this->settings['show_taxonomy'] ) {
			$this->add_taxonomy_breadcrumbs( $post->ID, 'category' );
		}

		// Add parent pages
		if ( $post->post_parent ) {
			$this->add_parent_pages( $post->post_parent );
		}

		// Add current post/page
		$this->add_item( get_the_title( $post ), '', false );
	}

	/**
	 * Generate breadcrumbs for archive pages
	 */
	private function generate_archive_breadcrumbs() {
		if ( is_category() || is_tag() || is_tax() ) {
			$this->generate_taxonomy_archive_breadcrumbs();
		} elseif ( is_post_type_archive() ) {
			$this->generate_post_type_archive_breadcrumbs();
		} elseif ( is_author() ) {
			$this->generate_author_breadcrumbs();
		} elseif ( is_date() ) {
			$this->generate_date_breadcrumbs();
		}
	}

	/**
	 * Generate breadcrumbs for taxonomy archives
	 */
	private function generate_taxonomy_archive_breadcrumbs() {
		$term = get_queried_object();

		if ( ! $term ) {
			return;
		}

		// Add parent terms
		if ( $term->parent ) {
			$this->add_parent_terms( $term->parent, $term->taxonomy );
		}

		// Add current term
		$this->add_item( $term->name, '', false );
	}

	/**
	 * Generate breadcrumbs for post type archives
	 */
	private function generate_post_type_archive_breadcrumbs() {
		$post_type = get_query_var( 'post_type' );
		
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( $post_type_object ) {
			$this->add_item( $post_type_object->labels->name, '', false );
		}
	}

	/**
	 * Generate breadcrumbs for author archives
	 */
	private function generate_author_breadcrumbs() {
		$author = get_queried_object();

		if ( $author ) {
			/* translators: %s: Author name */
			$this->add_item( sprintf( __( 'Author: %s', 'seovela' ), $author->display_name ), '', false );
		}
	}

	/**
	 * Generate breadcrumbs for date archives
	 */
	private function generate_date_breadcrumbs() {
		if ( is_year() ) {
			$this->add_item( get_the_date( 'Y' ), '', false );
		} elseif ( is_month() ) {
			$year_link = get_year_link( get_the_date( 'Y' ) );
			$this->add_item( get_the_date( 'Y' ), $year_link, true );
			$this->add_item( get_the_date( 'F' ), '', false );
		} elseif ( is_day() ) {
			$year_link = get_year_link( get_the_date( 'Y' ) );
			$month_link = get_month_link( get_the_date( 'Y' ), get_the_date( 'm' ) );
			$this->add_item( get_the_date( 'Y' ), $year_link, true );
			$this->add_item( get_the_date( 'F' ), $month_link, true );
			$this->add_item( get_the_date( 'j' ), '', false );
		}
	}

	/**
	 * Generate breadcrumbs for search results
	 */
	private function generate_search_breadcrumbs() {
		/* translators: %s: Search query */
		$this->add_item( sprintf( __( 'Search Results for: %s', 'seovela' ), get_search_query() ), '', false );
	}

	/**
	 * Generate breadcrumbs for 404 page
	 */
	private function generate_404_breadcrumbs() {
		$this->add_item( __( '404 - Page Not Found', 'seovela' ), '', false );
	}

	/**
	 * Add taxonomy breadcrumbs for a post
	 *
	 * @param int    $post_id  Post ID
	 * @param string $taxonomy Taxonomy name
	 */
	private function add_taxonomy_breadcrumbs( $post_id, $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return;
		}

		// Get the first term (or primary term if available)
		$term = $terms[0];

		// Add parent terms
		if ( $term->parent ) {
			$this->add_parent_terms( $term->parent, $taxonomy );
		}

		// Add current term
		$this->add_item( $term->name, get_term_link( $term ), true );
	}

	/**
	 * Add parent terms recursively
	 *
	 * @param int    $term_id  Term ID
	 * @param string $taxonomy Taxonomy name
	 */
	private function add_parent_terms( $term_id, $taxonomy ) {
		$term = get_term( $term_id, $taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		// Recursively add parent terms
		if ( $term->parent ) {
			$this->add_parent_terms( $term->parent, $taxonomy );
		}

		// Add this term
		$this->add_item( $term->name, get_term_link( $term ), true );
	}

	/**
	 * Add parent pages recursively
	 *
	 * @param int $page_id Page ID
	 */
	private function add_parent_pages( $page_id ) {
		$page = get_post( $page_id );

		if ( ! $page ) {
			return;
		}

		// Recursively add parent pages
		if ( $page->post_parent ) {
			$this->add_parent_pages( $page->post_parent );
		}

		// Add this page
		$this->add_item( get_the_title( $page ), get_permalink( $page ), true );
	}

	/**
	 * Add a breadcrumb item
	 *
	 * @param string $title Title
	 * @param string $url   URL (empty for current page)
	 * @param bool   $link  Whether to make it a link
	 */
	private function add_item( $title, $url = '', $link = true ) {
		$this->items[] = array(
			'title' => $title,
			'url'   => $url,
			'link'  => $link && ! empty( $url ),
		);
	}

	/**
	 * Get breadcrumbs HTML
	 *
	 * @param array $args Optional arguments to override settings
	 * @return string Breadcrumbs HTML
	 */
	public function get_html( $args = array() ) {
		$args = wp_parse_args( $args, $this->settings );

		// Generate breadcrumbs if not already generated
		if ( empty( $this->items ) ) {
			$this->generate();
		}

		if ( empty( $this->items ) ) {
			return '';
		}

		$html = '<nav class="' . esc_attr( $args['container_class'] ) . '" aria-label="' . esc_attr__( 'Breadcrumb', 'seovela' ) . '">';
		
		if ( ! empty( $args['prefix'] ) ) {
			$html .= '<span class="breadcrumb-prefix">' . esc_html( $args['prefix'] ) . '</span>';
		}

		$html .= '<ol class="' . esc_attr( $args['list_class'] ) . '" itemscope itemtype="https://schema.org/BreadcrumbList">';

		$total = count( $this->items );
		foreach ( $this->items as $index => $item ) {
			$position = $index + 1;
			$is_last = ( $position === $total );

			$html .= '<li class="' . esc_attr( $args['item_class'] ) . ( $is_last ? ' ' . esc_attr( $args['current_class'] ) : '' ) . '" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';

			if ( $item['link'] && ! $is_last ) {
				$html .= '<a href="' . esc_url( $item['url'] ) . '" class="' . esc_attr( $args['link_class'] ) . '" itemprop="item">';
				$html .= '<span itemprop="name">' . esc_html( $item['title'] ) . '</span>';
				$html .= '</a>';
			} else {
				if ( $args['bold_last'] && $is_last ) {
					$html .= '<strong itemprop="name">' . esc_html( $item['title'] ) . '</strong>';
				} else {
					$html .= '<span itemprop="name">' . esc_html( $item['title'] ) . '</span>';
				}
			}

			$html .= '<meta itemprop="position" content="' . esc_attr( $position ) . '" />';

			// Add separator (except for last item)
			if ( ! $is_last ) {
				$html .= '<span class="' . esc_attr( $args['separator_class'] ) . '" aria-hidden="true"> ' . esc_html( $args['separator'] ) . ' </span>';
			}

			$html .= '</li>';
		}

		$html .= '</ol>';
		$html .= '</nav>';

		return apply_filters( 'seovela_breadcrumbs_html', $html, $this->items, $args );
	}

	/**
	 * Get BreadcrumbList schema
	 *
	 * @return array Schema array
	 */
	public function get_schema() {
		// Generate breadcrumbs if not already generated
		if ( empty( $this->items ) ) {
			$this->generate();
		}

		if ( empty( $this->items ) ) {
			return array();
		}

		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(),
		);

		foreach ( $this->items as $index => $item ) {
			$position = $index + 1;

			$list_item = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => $item['title'],
			);

			if ( $item['link'] && ! empty( $item['url'] ) ) {
				$list_item['item'] = $item['url'];
			}

			$schema['itemListElement'][] = $list_item;
		}

		return apply_filters( 'seovela_breadcrumbs_schema', $schema, $this->items );
	}

	/**
	 * Output breadcrumbs
	 *
	 * @param array $args Optional arguments
	 */
	public function output( $args = array() ) {
		echo $this->get_html( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

