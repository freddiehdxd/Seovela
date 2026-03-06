<?php
/**
 * Seovela Frontend Class
 *
 * Handles frontend SEO output
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Frontend Class
 */
class Seovela_Frontend {

    /**
     * Cached options for this request
     *
     * @var array
     */
    private $options = array();

    /**
     * Cached meta description for reuse across output methods
     *
     * @var string|null
     */
    private $cached_description = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Pre-load all plugin options in a single batch
        $this->options = Seovela_Cache::get_all_plugin_options();

        // Remove WordPress default canonical to avoid duplicates
        remove_action( 'wp_head', 'rel_canonical' );

        // Handle Titles
        add_filter( 'document_title_parts', array( $this, 'filter_title_parts' ), 20 );
        
        // Handle Meta Tags
        add_action( 'wp_head', array( $this, 'output_meta_tags' ), 1 );
    }

    /**
     * Get option from cached options
     *
     * @param string $option_name Option name (without seovela_ prefix)
     * @param mixed  $default Default value
     * @return mixed Option value
     */
    private function get_option( $option_name, $default = false ) {
        // Add seovela_ prefix if not present
        if ( strpos( $option_name, 'seovela_' ) !== 0 ) {
            $option_name = 'seovela_' . $option_name;
        }

        return isset( $this->options[ $option_name ] ) ? $this->options[ $option_name ] : $default;
    }

    /**
     * Filter document title parts
     *
     * @param array $title_parts Title parts
     * @return array Modified title parts
     */
    public function filter_title_parts( $title_parts ) {
        if ( is_front_page() && is_home() ) {
            // Default homepage (latest posts)
            $title = $this->get_option( 'seovela_home_title' );
            if ( ! empty( $title ) ) {
                $title_parts['title'] = $this->replace_vars( $title );
                unset( $title_parts['tagline'] ); // Usually we want full control
                unset( $title_parts['site'] );
            }
        } elseif ( is_front_page() ) {
            // Static homepage
            $title = $this->get_option( 'seovela_home_title' );
            if ( ! empty( $title ) ) {
                $title_parts['title'] = $this->replace_vars( $title );
                unset( $title_parts['tagline'] );
                unset( $title_parts['site'] );
            }
        } elseif ( is_home() ) {
            // Blog page (if separate from front page)
            // Use post type archive settings or similar? For now stick to default WP behavior or custom if needed
        } elseif ( is_singular() ) {
            global $post;
            
            // Check for individual post meta override
            $meta_title = get_post_meta( $post->ID, '_seovela_meta_title', true );
            
            if ( ! empty( $meta_title ) ) {
                $title_parts['title'] = $this->replace_vars( $meta_title );
                unset( $title_parts['site'] );
                unset( $title_parts['tagline'] );
            } else {
                // Fallback to post type default
                $post_type = get_post_type();
                $default_title = '';
                
                if ( $post_type === 'post' ) {
                    $default_title = $this->get_option( 'seovela_titles_post_title' );
                } elseif ( $post_type === 'page' ) {
                    $default_title = $this->get_option( 'seovela_titles_page_title' );
                }
                
                if ( ! empty( $default_title ) ) {
                    $title_parts['title'] = $this->replace_vars( $default_title );
                    unset( $title_parts['site'] );
                }
            }
        } elseif ( is_category() || is_tag() || is_tax() ) {
            // Taxonomies
            $term_id = get_queried_object_id();
            // Check for term meta override (not implemented in UI yet, but good to prep)
            
            // Fallback to default
            $default_title = '';
            if ( is_category() ) {
                $default_title = $this->get_option( 'seovela_titles_category_title' );
            } elseif ( is_tag() ) {
                $default_title = $this->get_option( 'seovela_titles_post_tag_title' );
            }
            
            if ( ! empty( $default_title ) ) {
                $title_parts['title'] = $this->replace_vars( $default_title );
                unset( $title_parts['site'] );
            }
        }

        return $title_parts;
    }

    /**
     * Output meta tags in head
     */
    public function output_meta_tags() {
        echo "\n<!-- Seovela SEO Plugin -->\n";
        
        $this->output_description();
        $this->output_robots();
        $this->output_canonical();
        $this->output_opengraph();
        $this->output_twitter();
        // JSON-LD schema is handled entirely by Seovela_Schema module (modules/schema/class-seovela-schema.php)
        
        echo "<!-- /Seovela SEO Plugin -->\n\n";
    }

    /**
     * Get the meta description for the current page (cached).
     *
     * @return string
     */
    private function get_description() {
        if ( null !== $this->cached_description ) {
            return $this->cached_description;
        }

        $description = '';

        if ( is_front_page() ) {
            $description = $this->get_option( 'seovela_home_description' );
        } elseif ( is_singular() ) {
            global $post;
            $description = get_post_meta( $post->ID, '_seovela_meta_description', true );
            
            if ( empty( $description ) ) {
                // Fallback to default templates
                $post_type = get_post_type();
                $template = '';
                if ( $post_type === 'post' ) {
                    $template = $this->get_option( 'seovela_titles_post_description' );
                } elseif ( $post_type === 'page' ) {
                    $template = $this->get_option( 'seovela_titles_page_description' );
                }
                
                if ( ! empty( $template ) ) {
                    $description = $this->replace_vars( $template );
                }
            }
        } elseif ( is_category() ) {
            $description = $this->get_option( 'seovela_titles_category_description' );
            if ( ! empty( $description ) ) {
                $description = $this->replace_vars( $description );
            }
        } elseif ( is_tag() ) {
            $description = $this->get_option( 'seovela_titles_post_tag_description' );
            if ( ! empty( $description ) ) {
                $description = $this->replace_vars( $description );
            }
        }

        $this->cached_description = $description;
        return $description;
    }

    /**
     * Output meta description
     */
    private function output_description() {
        $description = $this->get_description();

        if ( ! empty( $description ) ) {
            echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
        }
    }

    /**
     * Output robots meta
     */
    private function output_robots() {
        $robots = array();
        
        // Global defaults
        $global_index = $this->get_option( 'seovela_robots_index', 'index' );
        if ( $global_index === 'noindex' ) {
            $robots['noindex'] = 'noindex';
        }

        // Context specific overrides
        if ( is_front_page() ) {
            // Usually uses global, but could have specific
        } elseif ( is_singular() ) {
            global $post;
            $post_noindex = get_post_meta( $post->ID, '_seovela_noindex', true );
            $post_nofollow = get_post_meta( $post->ID, '_seovela_nofollow', true );
            
            if ( $post_noindex ) {
                $robots['noindex'] = 'noindex';
            }
            if ( $post_nofollow ) {
                $robots['nofollow'] = 'nofollow';
            }
            
            // If not set on post, check type defaults
            if ( empty( $post_noindex ) ) {
                $post_type = get_post_type();
                $default_robots = '';
                if ( $post_type === 'post' ) {
                    $default_robots = $this->get_option( 'seovela_titles_post_robots', 'index' );
                } elseif ( $post_type === 'page' ) {
                    $default_robots = $this->get_option( 'seovela_titles_page_robots', 'index' );
                }
                
                if ( $default_robots === 'noindex' ) {
                    $robots['noindex'] = 'noindex';
                }
            }
        }

        // Additional global directives
        if ( $this->get_option( 'seovela_robots_nofollow' ) ) $robots['nofollow'] = 'nofollow';
        if ( $this->get_option( 'seovela_robots_noarchive' ) ) $robots['noarchive'] = 'noarchive';
        if ( $this->get_option( 'seovela_robots_noimageindex' ) ) $robots['noimageindex'] = 'noimageindex';
        if ( $this->get_option( 'seovela_robots_nosnippet' ) ) $robots['nosnippet'] = 'nosnippet';

        // Advanced
        $max_snippet = $this->get_option( 'seovela_robots_max_snippet', '-1' );
        if ( $max_snippet !== '' ) $robots[] = 'max-snippet:' . $max_snippet;
        
        $max_video = $this->get_option( 'seovela_robots_max_video_preview', '-1' );
        if ( $max_video !== '' ) $robots[] = 'max-video-preview:' . $max_video;
        
        $max_image = $this->get_option( 'seovela_robots_max_image_preview', 'large' );
        if ( $max_image !== 'none' ) $robots[] = 'max-image-preview:' . $max_image;

        if ( ! empty( $robots ) ) {
            echo '<meta name="robots" content="' . esc_attr( implode( ', ', $robots ) ) . '" />' . "\n";
        }
    }

    /**
     * Get the canonical URL for the current page.
     *
     * Uses wp_get_canonical_url() as a base, with post meta override support.
     *
     * @return string Canonical URL or empty string.
     */
    private function get_canonical_url() {
        $canonical = '';

        if ( is_singular() ) {
            global $post;
            // Check for a user-defined canonical override
            $override = get_post_meta( $post->ID, '_seovela_canonical_url', true );
            if ( ! empty( $override ) ) {
                $canonical = $override;
            } else {
                // Use WordPress's built-in canonical URL helper
                $canonical = wp_get_canonical_url( $post->ID );
            }
        } elseif ( is_front_page() || is_home() ) {
            $canonical = home_url( '/' );
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( $term ) {
                $canonical = get_term_link( $term );
                if ( is_wp_error( $canonical ) ) {
                    $canonical = '';
                }
            }
        } elseif ( is_post_type_archive() ) {
            $canonical = get_post_type_archive_link( get_queried_object()->name );
        } elseif ( is_author() ) {
            $canonical = get_author_posts_url( get_queried_object_id() );
        }

        return $canonical ? $canonical : '';
    }

    /**
     * Output canonical URL
     */
    private function output_canonical() {
        $canonical = $this->get_canonical_url();

        if ( ! empty( $canonical ) ) {
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
        }
    }

    /**
     * Output OpenGraph tags
     */
    private function output_opengraph() {
        global $post;
        
        echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '" />' . "\n";
        echo '<meta property="og:type" content="' . ( is_singular() ? 'article' : 'website' ) . '" />' . "\n";
        
        // Title
        $title = wp_get_document_title();
        echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
        
        // Description - reuse the cached description
        $description = $this->get_description();
        if ( ! empty( $description ) ) {
            echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
        }
        
        // URL - reuse canonical URL helper
        $canonical = $this->get_canonical_url();
        if ( ! empty( $canonical ) ) {
            echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
        }
        
        // Site Name
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
        
        // Image
        $image_url = '';
        if ( is_singular() && has_post_thumbnail( $post->ID ) ) {
            $image_url = get_the_post_thumbnail_url( $post->ID, 'full' );
        } else {
            $image_url = $this->get_option( 'seovela_default_og_image' );
        }
        
        if ( ! empty( $image_url ) ) {
            echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
        }
    }

    /**
     * Output Twitter cards
     */
    private function output_twitter() {
        $card_type = $this->get_option( 'seovela_twitter_card_type', 'summary_large_image' );
        echo '<meta name="twitter:card" content="' . esc_attr( $card_type ) . '" />' . "\n";
        
        // Title & Desc are usually picked up from OG, but good to be explicit
    }

    /**
     * Replace variables in string
     *
     * @param string $string
     * @return string
     */
    private function replace_vars( $string ) {
        if ( empty( $string ) ) return '';

        $sep = $this->get_option( 'seovela_separator_character', '-' );
        $sitename = get_bloginfo( 'name' );
        $tagline = get_bloginfo( 'description' );

        $replacements = array(
            '%sep%' => $sep,
            '%separator%' => $sep,
            '%sitename%' => $sitename,
            '%blog_title%' => $sitename,
            '%tagline%' => $tagline,
            '%date%' => wp_date( get_option( 'date_format' ) ),
            '%currentdate%' => wp_date( get_option( 'date_format' ) ),
            '%year%' => wp_date( 'Y' ),
            '%currentyear%' => wp_date( 'Y' ),
            '%month%' => wp_date( 'F' ),
            '%currentmonth%' => wp_date( 'F' ),
        );

        // Context specific
        if ( is_singular() ) {
            global $post;
            $replacements['%title%'] = get_the_title( $post->ID );
            $replacements['%excerpt%'] = get_the_excerpt( $post->ID );
            $replacements['%id%'] = $post->ID;
            
            // Categories
            $cats = get_the_category( $post->ID );
            if ( $cats ) {
                $replacements['%category%'] = $cats[0]->name;
            }
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            $replacements['%term_title%'] = $term->name;
            $replacements['%term_description%'] = $term->description;
        }

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $string );
    }
}

