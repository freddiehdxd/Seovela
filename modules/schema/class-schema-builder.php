<?php
/**
 * Schema Builder
 *
 * Coordinates schema generation and validation
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schema Builder Class
 */
class Seovela_Schema_Builder {

    /**
     * Available schema types
     *
     * @var array
     */
    private static $schema_types = array(
        'Article',
        'FAQ',
        'HowTo',
        'LocalBusiness',
        'Person',
        'Product',
    );

    /**
     * Initialize schema builder
     */
    public static function init() {
        // Load schema type classes
        self::load_schema_types();
    }

    /**
     * Load all schema type classes
     */
    private static function load_schema_types() {
        $types_dir = dirname( __FILE__ ) . '/types/';
        
        foreach ( self::$schema_types as $type ) {
            $file = $types_dir . 'class-' . strtolower( $type ) . '-schema.php';
            if ( file_exists( $file ) ) {
                require_once $file;
            }
        }
    }

    /**
     * Get all available schema types
     *
     * @return array Schema types with display names and descriptions
     */
    public static function get_available_types() {
        $types = array();

        foreach ( self::$schema_types as $type ) {
            $class = 'Seovela_' . $type . '_Schema';
            
            if ( class_exists( $class ) ) {
                $types[ $type ] = array(
                    'name'        => $class::get_display_name(),
                    'description' => $class::get_description(),
                    'class'       => $class,
                );
            }
        }

        return apply_filters( 'seovela_available_schema_types', $types );
    }

    /**
     * Get default schema type for post
     *
     * @param int $post_id Post ID
     * @return string|null Schema type or null
     */
    public static function get_default_schema_type( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return null;
        }

        // Check for manual override first
        $manual_type = get_post_meta( $post_id, '_seovela_schema_type', true );
        if ( ! empty( $manual_type ) && $manual_type !== 'auto' ) {
            return $manual_type;
        }

        // Auto-detect based on post type
        $post_type = get_post_type( $post_id );

        switch ( $post_type ) {
            case 'post':
                return 'Article'; // or BlogPosting for backwards compat

            case 'page':
                return 'Article';

            default:
                // No default for custom post types
                return null;
        }
    }

    /**
     * Get selected schema types for a post
     *
     * @param int $post_id Post ID
     * @return array Array of schema type names
     */
    public static function get_selected_schema_types( $post_id ) {
        $primary_type = get_post_meta( $post_id, '_seovela_schema_type', true );
        
        // If auto or empty, use default
        if ( empty( $primary_type ) || $primary_type === 'auto' ) {
            $primary_type = self::get_default_schema_type( $post_id );
        }

        $types = array();
        if ( ! empty( $primary_type ) ) {
            $types[] = $primary_type;
        }

        // Check for additional compatible types
        $additional_types = get_post_meta( $post_id, '_seovela_schema_additional_types', true );
        if ( ! empty( $additional_types ) && is_array( $additional_types ) ) {
            foreach ( $additional_types as $type ) {
                if ( ! in_array( $type, $types, true ) ) {
                    // Verify compatibility
                    if ( self::are_types_compatible( $primary_type, $type ) ) {
                        $types[] = $type;
                    }
                }
            }
        }

        return apply_filters( 'seovela_selected_schema_types', $types, $post_id );
    }

    /**
     * Check if two schema types are compatible
     *
     * @param string $type1 First schema type
     * @param string $type2 Second schema type
     * @return bool
     */
    public static function are_types_compatible( $type1, $type2 ) {
        if ( empty( $type1 ) || empty( $type2 ) || $type1 === $type2 ) {
            return false;
        }

        $class1 = 'Seovela_' . $type1 . '_Schema';
        
        if ( ! class_exists( $class1 ) ) {
            return false;
        }

        return $class1::is_compatible_with( $type2 );
    }

    /**
     * Generate schema for post
     *
     * @param int $post_id Post ID
     * @return array Array of schema objects
     */
    public static function generate_schemas( $post_id ) {
        $types = self::get_selected_schema_types( $post_id );
        $schemas = array();

        foreach ( $types as $type ) {
            $schema = self::generate_single_schema( $type, $post_id );
            
            if ( ! empty( $schema ) ) {
                $schemas[] = $schema;
            }
        }

        return apply_filters( 'seovela_generated_schemas', $schemas, $post_id );
    }

    /**
     * Generate a single schema type
     *
     * @param string $type Schema type
     * @param int    $post_id Post ID
     * @return array Schema array
     */
    public static function generate_single_schema( $type, $post_id ) {
        $class = 'Seovela_' . $type . '_Schema';
        
        if ( ! class_exists( $class ) || ! method_exists( $class, 'generate' ) ) {
            return array();
        }

        try {
            $schema = $class::generate( $post_id );
            return $schema;
        } catch ( Exception $e ) {
            // Log error but don't break the site
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Seovela Schema Error: ' . $e->getMessage() );
            }
            return array();
        }
    }

    /**
     * Validate schema array
     *
     * @param array $schema Schema array
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public static function validate_schema( $schema ) {
        if ( empty( $schema ) ) {
            return new WP_Error( 'empty_schema', __( 'Schema is empty', 'seovela' ) );
        }

        if ( ! is_array( $schema ) ) {
            return new WP_Error( 'invalid_schema', __( 'Schema must be an array', 'seovela' ) );
        }

        // Check required fields
        if ( ! isset( $schema['@context'] ) ) {
            return new WP_Error( 'missing_context', __( 'Schema missing @context', 'seovela' ) );
        }

        if ( ! isset( $schema['@type'] ) ) {
            return new WP_Error( 'missing_type', __( 'Schema missing @type', 'seovela' ) );
        }

        // Validate JSON encoding
        $json = wp_json_encode( $schema );
        if ( $json === false ) {
            return new WP_Error( 'invalid_json', __( 'Schema cannot be encoded as JSON', 'seovela' ) );
        }

        return true;
    }

    /**
     * Get compatibility warnings for selected types
     *
     * @param array $types Array of schema type names
     * @return array Array of warning messages
     */
    public static function get_compatibility_warnings( $types ) {
        $warnings = array();

        if ( count( $types ) < 2 ) {
            return $warnings;
        }

        $primary = $types[0];

        for ( $i = 1; $i < count( $types ); $i++ ) {
            if ( ! self::are_types_compatible( $primary, $types[ $i ] ) ) {
                $warnings[] = sprintf(
                    /* translators: 1: First schema type, 2: Second schema type */
                    __( '%1$s and %2$s schemas may not be compatible. Choose one primary schema type.', 'seovela' ),
                    $primary,
                    $types[ $i ]
                );
            }
        }

        return $warnings;
    }

    /**
     * Get Google Rich Results testing URL
     *
     * @param int $post_id Post ID
     * @return string Testing URL
     */
    public static function get_rich_results_test_url( $post_id ) {
        $permalink = get_permalink( $post_id );
        return 'https://search.google.com/test/rich-results?url=' . urlencode( $permalink );
    }

    /**
     * Get schema preview JSON
     *
     * @param int $post_id Post ID
     * @return string JSON string
     */
    public static function get_schema_preview( $post_id ) {
        $schemas = self::generate_schemas( $post_id );
        
        if ( empty( $schemas ) ) {
            return '';
        }

        // If single schema, return as-is
        if ( count( $schemas ) === 1 ) {
            return wp_json_encode( $schemas[0] );
        }

        // Multiple schemas: wrap in array
        return wp_json_encode( $schemas );
    }

    /**
     * Output schema JSON-LD script tags
     *
     * @param array $schemas Array of schema objects
     */
    public static function output_schemas( $schemas ) {
        if ( empty( $schemas ) ) {
            return;
        }

        foreach ( $schemas as $schema ) {
            // Validate before output
            $validation = self::validate_schema( $schema );
            if ( is_wp_error( $validation ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Seovela Schema Validation Error: ' . $validation->get_error_message() );
                }
                continue;
            }

            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );
            echo "\n" . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD structured data must not be HTML-escaped; wp_json_encode with JSON_HEX_TAG prevents XSS.
        }
    }

    /**
     * Check if LocalBusiness schema is properly configured
     *
     * @return bool
     */
    public static function is_local_business_configured() {
        if ( ! class_exists( 'Seovela_LocalBusiness_Schema' ) ) {
            return false;
        }

        return Seovela_LocalBusiness_Schema::is_configured();
    }
}

// Initialize
Seovela_Schema_Builder::init();

