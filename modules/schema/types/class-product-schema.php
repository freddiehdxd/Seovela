<?php
/**
 * Product Schema Type
 *
 * Basic product schema (non-WooCommerce)
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product Schema Class
 */
class Seovela_Product_Schema {

    /**
     * Generate Product schema
     *
     * @param int $post_id Post ID
     * @return array Schema array
     */
    public static function generate( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Product',
            'name'     => get_the_title( $post_id ),
        );

        // Description
        $description = get_post_meta( $post_id, '_seovela_meta_description', true );
        if ( empty( $description ) ) {
            $description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
        }
        if ( ! empty( $description ) ) {
            $schema['description'] = sanitize_text_field( $description );
        }

        // Image
        if ( has_post_thumbnail( $post_id ) ) {
            $image_url = get_the_post_thumbnail_url( $post_id, 'full' );
            if ( $image_url ) {
                $schema['image'] = esc_url( $image_url );
            }
        }

        // SKU
        $sku = get_post_meta( $post_id, '_seovela_product_sku', true );
        if ( ! empty( $sku ) ) {
            $schema['sku'] = sanitize_text_field( $sku );
        }

        // Brand
        $brand = get_post_meta( $post_id, '_seovela_product_brand', true );
        if ( ! empty( $brand ) ) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name'  => sanitize_text_field( $brand ),
            );
        }

        // Offers (price, availability)
        $price = get_post_meta( $post_id, '_seovela_product_price', true );
        $currency = get_post_meta( $post_id, '_seovela_product_currency', true );
        
        if ( ! empty( $price ) ) {
            $schema['offers'] = array(
                '@type'         => 'Offer',
                'price'         => floatval( $price ),
                'priceCurrency' => ! empty( $currency ) ? sanitize_text_field( $currency ) : 'USD',
            );

            // Availability
            $availability = get_post_meta( $post_id, '_seovela_product_availability', true );
            $availability_map = array(
                'in_stock'    => 'https://schema.org/InStock',
                'out_of_stock' => 'https://schema.org/OutOfStock',
                'preorder'    => 'https://schema.org/PreOrder',
                'discontinued' => 'https://schema.org/Discontinued',
            );

            if ( ! empty( $availability ) && isset( $availability_map[ $availability ] ) ) {
                $schema['offers']['availability'] = $availability_map[ $availability ];
            } else {
                $schema['offers']['availability'] = 'https://schema.org/InStock';
            }

            // URL (product page)
            $schema['offers']['url'] = get_permalink( $post_id );

            // Price valid until (optional)
            $price_valid_until = get_post_meta( $post_id, '_seovela_product_price_valid_until', true );
            if ( ! empty( $price_valid_until ) ) {
                $schema['offers']['priceValidUntil'] = sanitize_text_field( $price_valid_until );
            }
        }

        // Aggregate rating (optional)
        $rating = get_post_meta( $post_id, '_seovela_product_rating', true );
        $review_count = get_post_meta( $post_id, '_seovela_product_review_count', true );

        if ( ! empty( $rating ) && ! empty( $review_count ) ) {
            $schema['aggregateRating'] = array(
                '@type'       => 'AggregateRating',
                'ratingValue' => floatval( $rating ),
                'reviewCount' => intval( $review_count ),
            );

            // Best/Worst rating (standard is 1-5)
            $schema['aggregateRating']['bestRating'] = 5;
            $schema['aggregateRating']['worstRating'] = 1;
        }

        // Reviews (optional, basic support)
        $reviews = get_post_meta( $post_id, '_seovela_product_reviews', true );
        if ( ! empty( $reviews ) && is_array( $reviews ) ) {
            $schema['review'] = array();
            
            foreach ( $reviews as $review ) {
                if ( empty( $review['author'] ) || empty( $review['rating'] ) ) {
                    continue;
                }

                $review_schema = array(
                    '@type'        => 'Review',
                    'author'       => array(
                        '@type' => 'Person',
                        'name'  => sanitize_text_field( $review['author'] ),
                    ),
                    'reviewRating' => array(
                        '@type'       => 'Rating',
                        'ratingValue' => floatval( $review['rating'] ),
                        'bestRating'  => 5,
                        'worstRating' => 1,
                    ),
                );

                if ( ! empty( $review['body'] ) ) {
                    $review_schema['reviewBody'] = sanitize_text_field( $review['body'] );
                }

                if ( ! empty( $review['date'] ) ) {
                    $review_schema['datePublished'] = sanitize_text_field( $review['date'] );
                }

                $schema['review'][] = $review_schema;
            }
        }

        return apply_filters( 'seovela_product_schema', $schema, $post_id );
    }

    /**
     * Check if this schema type is compatible with another
     *
     * @param string $other_type Other schema type
     * @return bool
     */
    public static function is_compatible_with( $other_type ) {
        // Product can work with FAQ and HowTo
        $compatible = array( 'FAQ', 'HowTo' );
        return in_array( $other_type, $compatible, true );
    }

    /**
     * Get schema type display name
     *
     * @return string
     */
    public static function get_display_name() {
        return __( 'Product', 'seovela' );
    }

    /**
     * Get schema type description
     *
     * @return string
     */
    public static function get_description() {
        return __( 'For product pages. Shows price, availability, and ratings in search results. Not for WooCommerce (use WooCommerce schema).', 'seovela' );
    }
}

