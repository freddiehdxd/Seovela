<?php
/**
 * Seovela Schema Module
 *
 * Handles structured data (JSON-LD) output as a single unified @graph.
 * All schema entities are combined into one JSON-LD block with cross-references via @id.
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Schema Class
 *
 * Builds a unified JSON-LD @graph containing:
 * - Organization/Person (knowledge graph)
 * - WebSite (with SearchAction)
 * - WebPage (current page)
 * - Article/BlogPosting (singular posts)
 * - BreadcrumbList
 * - Post-specific schemas (FAQ, HowTo, Product, etc.)
 */
class Seovela_Schema {

    /**
     * Home URL cached for the request
     *
     * @var string
     */
    private $home_url = '';

    /**
     * Current page URL cached for the request
     *
     * @var string
     */
    private $current_url = '';

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_dependencies();

        // Single hook for all schema output
        add_action( 'wp_head', array( $this, 'output_schema' ), 5 );
    }

    /**
     * Load schema dependencies
     */
    private function load_dependencies() {
        require_once dirname( __FILE__ ) . '/class-schema-builder.php';
    }

    /**
     * Get a plugin option via the cache helper
     *
     * @param string $option_name Option name (with or without seovela_ prefix)
     * @param mixed  $default     Default value
     * @return mixed
     */
    private function get_option( $option_name, $default = false ) {
        return Seovela_Cache::get_option( $option_name, $default );
    }

    /**
     * Resolve the current page URL once per request
     *
     * @return string
     */
    private function get_current_url() {
        if ( empty( $this->current_url ) ) {
            global $wp;
            $this->current_url = home_url( add_query_arg( array(), $wp->request ) );
        }
        return $this->current_url;
    }

    /**
     * Resolve home URL once per request
     *
     * @return string
     */
    private function get_home_url() {
        if ( empty( $this->home_url ) ) {
            $this->home_url = home_url();
        }
        return $this->home_url;
    }

    // ------------------------------------------------------------------
    // Main output
    // ------------------------------------------------------------------

    /**
     * Build and output the unified JSON-LD @graph
     */
    public function output_schema() {
        // Allow short-circuiting the entire schema output
        if ( apply_filters( 'seovela_disable_schema_output', false ) ) {
            return;
        }

        // Check per-post disable flag
        if ( is_singular() ) {
            global $post;
            if ( get_post_meta( $post->ID, '_seovela_disable_schema', true ) === 'yes' ) {
                return;
            }
        }

        // Collect all graph pieces
        $pieces = array();

        $pieces[] = $this->get_organization_or_person();
        $pieces[] = $this->get_website();
        $pieces[] = $this->get_webpage();

        if ( is_singular() ) {
            global $post;

            $article = $this->get_article( $post );
            if ( ! empty( $article ) ) {
                $pieces[] = $article;
            }

            // Post-specific schemas from the schema builder (FAQ, HowTo, Product, etc.)
            $extra = $this->get_post_specific_schemas( $post->ID );
            if ( ! empty( $extra ) ) {
                $pieces = array_merge( $pieces, $extra );
            }
        }

        $breadcrumb = $this->get_breadcrumb_list();
        if ( ! empty( $breadcrumb ) ) {
            $pieces[] = $breadcrumb;
        }

        /**
         * Filter the array of graph pieces before they are assembled.
         *
         * Each piece is an associative array representing a single schema entity.
         * You may add, remove, or modify pieces.
         *
         * @param array $pieces Graph pieces
         */
        $pieces = apply_filters( 'seovela_schema_graph_pieces', $pieces );

        // Remove any empty/null pieces
        $pieces = array_values( array_filter( $pieces ) );

        if ( empty( $pieces ) ) {
            return;
        }

        // Assemble the full graph
        $graph = array(
            '@context' => 'https://schema.org',
            '@graph'   => $pieces,
        );

        /**
         * Filter the complete JSON-LD graph before encoding.
         *
         * @param array $graph Full graph including @context and @graph
         */
        $graph = apply_filters( 'seovela_schema_graph', $graph );

        // Output a single JSON-LD script tag
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $graph );
        echo "\n" . '</script>' . "\n";
    }

    // ------------------------------------------------------------------
    // Individual graph pieces
    // ------------------------------------------------------------------

    /**
     * Organization or Person entity (knowledge graph settings)
     *
     * @return array
     */
    private function get_organization_or_person() {
        $url  = $this->get_home_url();
        $type = $this->get_option( 'seovela_knowledge_graph_type', 'person' );
        $name = $this->get_option( 'seovela_knowledge_graph_name', get_bloginfo( 'name' ) );
        $logo = $this->get_option( 'seovela_knowledge_graph_logo' );

        $schema_type = ( $type === 'company' ) ? 'Organization' : 'Person';

        $entity = array(
            '@type' => $schema_type,
            '@id'   => $url . '/#/schema/organization',
            'name'  => $name,
            'url'   => $url,
        );

        if ( ! empty( $logo ) ) {
            $logo_id = $url . '/#/schema/logo';

            $entity['logo'] = array(
                '@type'      => 'ImageObject',
                '@id'        => $logo_id,
                'url'        => $logo,
                'contentUrl' => $logo,
                'caption'    => $name,
            );
            $entity['image'] = array( '@id' => $logo_id );
        }

        // Social profiles
        $same_as = array();
        $social_keys = array(
            'seovela_social_facebook',
            'seovela_social_twitter',
            'seovela_social_instagram',
            'seovela_social_linkedin',
            'seovela_social_youtube',
            'seovela_social_pinterest',
        );
        foreach ( $social_keys as $key ) {
            $val = $this->get_option( $key );
            if ( ! empty( $val ) ) {
                $same_as[] = $val;
            }
        }
        if ( ! empty( $same_as ) ) {
            $entity['sameAs'] = $same_as;
        }

        /**
         * Filter the Organization / Person schema piece.
         *
         * @param array  $entity      The schema entity
         * @param string $schema_type 'Organization' or 'Person'
         */
        return apply_filters( 'seovela_schema_organization', $entity, $schema_type );
    }

    /**
     * WebSite entity with SearchAction
     *
     * @return array
     */
    private function get_website() {
        $url = $this->get_home_url();

        $website = array(
            '@type'       => 'WebSite',
            '@id'         => $url . '/#/schema/website',
            'url'         => $url,
            'name'        => get_bloginfo( 'name' ),
            'description' => get_bloginfo( 'description' ),
            'publisher'   => array( '@id' => $url . '/#/schema/organization' ),
            'inLanguage'  => get_locale(),
            'potentialAction' => array(
                array(
                    '@type'       => 'SearchAction',
                    'target'      => array(
                        '@type'        => 'EntryPoint',
                        'urlTemplate'  => $url . '/?s={search_term_string}',
                    ),
                    'query-input' => 'required name=search_term_string',
                ),
            ),
        );

        /**
         * Filter the WebSite schema piece.
         *
         * @param array $website The schema entity
         */
        return apply_filters( 'seovela_schema_website', $website );
    }

    /**
     * WebPage entity for the current page
     *
     * @return array
     */
    private function get_webpage() {
        $url         = $this->get_home_url();
        $current_url = $this->get_current_url();

        $webpage = array(
            '@type'      => $this->get_webpage_type(),
            '@id'        => $current_url . '/#/schema/webpage',
            'url'        => $current_url,
            'name'       => wp_get_document_title(),
            'isPartOf'   => array( '@id' => $url . '/#/schema/website' ),
            'inLanguage' => get_locale(),
        );

        if ( is_front_page() ) {
            $webpage['about'] = array( '@id' => $url . '/#/schema/organization' );
        }

        // Description
        if ( is_singular() ) {
            global $post;
            $desc = get_post_meta( $post->ID, '_seovela_meta_description', true );
            if ( ! empty( $desc ) ) {
                $webpage['description'] = sanitize_text_field( $desc );
            }

            $webpage['datePublished'] = get_the_date( 'c' );
            $webpage['dateModified']  = get_the_modified_date( 'c' );

            // Link to breadcrumb
            $webpage['breadcrumb'] = array( '@id' => $current_url . '/#/schema/breadcrumb' );

            // Primary image
            if ( has_post_thumbnail( $post->ID ) ) {
                $image_url = get_the_post_thumbnail_url( $post->ID, 'full' );
                if ( $image_url ) {
                    $primary_image_id = $current_url . '/#/schema/primaryimage';
                    $image_id   = get_post_thumbnail_id( $post->ID );
                    $image_meta = wp_get_attachment_metadata( $image_id );

                    $webpage['primaryImageOfPage'] = array(
                        '@type'      => 'ImageObject',
                        '@id'        => $primary_image_id,
                        'url'        => $image_url,
                        'contentUrl' => $image_url,
                    );
                    if ( isset( $image_meta['width'], $image_meta['height'] ) ) {
                        $webpage['primaryImageOfPage']['width']  = $image_meta['width'];
                        $webpage['primaryImageOfPage']['height'] = $image_meta['height'];
                    }
                }
            }
        }

        /**
         * Filter the WebPage schema piece.
         *
         * @param array $webpage The schema entity
         */
        return apply_filters( 'seovela_schema_webpage', $webpage );
    }

    /**
     * Determine the WebPage sub-type based on current context
     *
     * @return string
     */
    private function get_webpage_type() {
        if ( is_search() ) {
            return 'SearchResultsPage';
        }
        if ( is_author() ) {
            return 'ProfilePage';
        }
        if ( is_singular( 'post' ) ) {
            return 'WebPage';
        }
        if ( is_archive() || is_home() ) {
            return 'CollectionPage';
        }
        return 'WebPage';
    }

    /**
     * Article / BlogPosting entity for singular posts
     *
     * @param WP_Post $post Current post object
     * @return array|null
     */
    private function get_article( $post ) {
        $post_type = get_post_type( $post );

        // Only generate for posts and pages by default
        $allowed_types = apply_filters( 'seovela_schema_article_post_types', array( 'post', 'page' ) );
        if ( ! in_array( $post_type, $allowed_types, true ) ) {
            return null;
        }

        $url         = $this->get_home_url();
        $current_url = $this->get_current_url();

        // Determine schema type from post meta or auto-detect
        $schema_type = get_post_meta( $post->ID, '_seovela_schema_type', true );
        if ( empty( $schema_type ) || $schema_type === 'auto' ) {
            $schema_type = ( $post_type === 'post' ) ? 'Article' : 'Article';
        }

        $article = array(
            '@type'             => $schema_type,
            '@id'               => $current_url . '/#/schema/article',
            'isPartOf'          => array( '@id' => $current_url . '/#/schema/webpage' ),
            'headline'          => get_the_title( $post->ID ),
            'datePublished'     => get_the_date( 'c', $post->ID ),
            'dateModified'      => get_the_modified_date( 'c', $post->ID ),
            'mainEntityOfPage'  => array( '@id' => $current_url . '/#/schema/webpage' ),
            'publisher'         => array( '@id' => $url . '/#/schema/organization' ),
            'inLanguage'        => get_locale(),
        );

        // Author
        $author_id   = $post->post_author;
        $author_name = get_the_author_meta( 'display_name', $author_id );
        $author_url  = get_author_posts_url( $author_id );

        $article['author'] = array(
            '@type' => 'Person',
            '@id'   => $author_url . '#/schema/person',
            'name'  => $author_name,
            'url'   => $author_url,
        );

        // Description
        $description = get_post_meta( $post->ID, '_seovela_meta_description', true );
        if ( empty( $description ) ) {
            $description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
        }
        if ( ! empty( $description ) ) {
            $article['description'] = sanitize_text_field( $description );
        }

        // Featured image
        if ( has_post_thumbnail( $post->ID ) ) {
            $image_url = get_the_post_thumbnail_url( $post->ID, 'full' );
            if ( $image_url ) {
                $image_id   = get_post_thumbnail_id( $post->ID );
                $image_meta = wp_get_attachment_metadata( $image_id );

                $article['image'] = array(
                    '@type'      => 'ImageObject',
                    '@id'        => $current_url . '/#/schema/primaryimage',
                    'url'        => $image_url,
                    'contentUrl' => $image_url,
                );
                if ( isset( $image_meta['width'], $image_meta['height'] ) ) {
                    $article['image']['width']  = $image_meta['width'];
                    $article['image']['height'] = $image_meta['height'];
                }
            }
        }

        // Word count
        $word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
        if ( $word_count > 0 ) {
            $article['wordCount'] = $word_count;
        }

        // Article section (primary category)
        if ( $post_type === 'post' ) {
            $categories = get_the_category( $post->ID );
            if ( ! empty( $categories ) ) {
                $article['articleSection'] = array();
                foreach ( $categories as $cat ) {
                    $article['articleSection'][] = $cat->name;
                }
            }

            // Keywords from tags
            $tags = get_the_tags( $post->ID );
            if ( ! empty( $tags ) ) {
                $keywords = array();
                foreach ( $tags as $tag ) {
                    $keywords[] = $tag->name;
                }
                $article['keywords'] = implode( ', ', $keywords );
            }
        }

        /**
         * Filter the Article schema piece.
         *
         * @param array   $article The schema entity
         * @param WP_Post $post    The post object
         */
        return apply_filters( 'seovela_schema_article', $article, $post );
    }

    /**
     * BreadcrumbList entity
     *
     * @return array|null
     */
    private function get_breadcrumb_list() {
        // Don't output breadcrumbs on front page
        if ( is_front_page() ) {
            return null;
        }

        $current_url = $this->get_current_url();
        $url         = $this->get_home_url();
        $items       = array();
        $position    = 1;

        // Home
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => __( 'Home', 'seovela' ),
            'item'     => $url,
        );

        if ( is_singular() ) {
            global $post;

            // Category breadcrumb for posts
            if ( get_post_type( $post ) === 'post' ) {
                $categories = get_the_category( $post->ID );
                if ( ! empty( $categories ) ) {
                    // Use primary category (first)
                    $cat = $categories[0];
                    $items[] = array(
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => $cat->name,
                        'item'     => get_category_link( $cat->term_id ),
                    );
                }
            }

            // Parent pages for hierarchical post types
            if ( is_post_type_hierarchical( get_post_type( $post ) ) && $post->post_parent ) {
                $ancestors = array_reverse( get_post_ancestors( $post->ID ) );
                foreach ( $ancestors as $ancestor_id ) {
                    $items[] = array(
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => get_the_title( $ancestor_id ),
                        'item'     => get_permalink( $ancestor_id ),
                    );
                }
            }

            // Current page
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => get_the_title( $post->ID ),
                'item'     => get_permalink( $post->ID ),
            );

        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( $term ) {
                // Parent terms
                if ( ! empty( $term->parent ) ) {
                    $ancestors = array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) );
                    foreach ( $ancestors as $ancestor_id ) {
                        $ancestor = get_term( $ancestor_id, $term->taxonomy );
                        if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                            $items[] = array(
                                '@type'    => 'ListItem',
                                'position' => $position++,
                                'name'     => $ancestor->name,
                                'item'     => get_term_link( $ancestor ),
                            );
                        }
                    }
                }

                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => $term->name,
                    'item'     => get_term_link( $term ),
                );
            }

        } elseif ( is_post_type_archive() ) {
            $post_type_obj = get_queried_object();
            if ( $post_type_obj ) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => $post_type_obj->label,
                    'item'     => get_post_type_archive_link( $post_type_obj->name ),
                );
            }

        } elseif ( is_author() ) {
            $author = get_queried_object();
            if ( $author ) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => $author->display_name,
                    'item'     => get_author_posts_url( $author->ID ),
                );
            }

        } elseif ( is_search() ) {
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => sprintf( __( 'Search results for "%s"', 'seovela' ), get_search_query() ),
                'item'     => get_search_link(),
            );
        }

        // Need at least 2 items for a meaningful breadcrumb
        if ( count( $items ) < 2 ) {
            return null;
        }

        $breadcrumb = array(
            '@type'           => 'BreadcrumbList',
            '@id'             => $current_url . '/#/schema/breadcrumb',
            'itemListElement' => $items,
        );

        /**
         * Filter the BreadcrumbList schema piece.
         *
         * @param array $breadcrumb The schema entity
         */
        return apply_filters( 'seovela_schema_breadcrumblist', $breadcrumb );
    }

    /**
     * Collect post-specific schemas from the schema builder (FAQ, HowTo, Product, etc.)
     *
     * These are returned as standalone graph pieces (without their own @context)
     * so they integrate cleanly into the @graph array.
     *
     * @param int $post_id Post ID
     * @return array Array of schema pieces (may be empty)
     */
    private function get_post_specific_schemas( $post_id ) {
        $schemas = Seovela_Schema_Builder::generate_schemas( $post_id );

        if ( empty( $schemas ) ) {
            return array();
        }

        $current_url = $this->get_current_url();
        $pieces      = array();

        foreach ( $schemas as $schema ) {
            if ( empty( $schema ) || ! is_array( $schema ) ) {
                continue;
            }

            // Skip Article-type schemas — we already build our own richer one above
            $type = isset( $schema['@type'] ) ? $schema['@type'] : '';
            if ( in_array( $type, array( 'Article', 'BlogPosting', 'NewsArticle', 'TechArticle' ), true ) ) {
                continue;
            }

            // Strip @context — it will be provided by the top-level graph
            unset( $schema['@context'] );

            // Ensure an @id so it can be cross-referenced
            if ( ! isset( $schema['@id'] ) ) {
                $schema['@id'] = $current_url . '/#/schema/' . strtolower( sanitize_title( $type ) );
            }

            // Cross-reference to the parent webpage
            if ( ! isset( $schema['mainEntityOfPage'] ) && ! isset( $schema['isPartOf'] ) ) {
                $schema['isPartOf'] = array( '@id' => $current_url . '/#/schema/webpage' );
            }

            /**
             * Filter an individual post-specific schema piece by its type.
             *
             * Dynamic filter name: seovela_schema_{lowercase_type}
             * Example: seovela_schema_faqpage, seovela_schema_howto, seovela_schema_product
             *
             * @param array $schema  The schema entity
             * @param int   $post_id The post ID
             */
            $filter_type = strtolower( str_replace( ' ', '', $type ) );
            $schema = apply_filters( "seovela_schema_{$filter_type}", $schema, $post_id );

            if ( ! empty( $schema ) ) {
                $pieces[] = $schema;
            }
        }

        return $pieces;
    }
}
