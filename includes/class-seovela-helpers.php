<?php
/**
 * Seovela Helper Functions
 *
 * Utility functions used throughout the plugin
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Helpers Class
 */
class Seovela_Helpers {

    /**
     * Check if all features are available
     *
     * Since Seovela is now fully free and open-source, this always returns true.
     *
     * @return bool
     */
    public static function is_pro_active() {
        return true;
    }

    /**
     * Truncate text to specific length
     *
     * @param string $text Text to truncate
     * @param int    $length Maximum length
     * @return string
     */
    public static function truncate( $text, $length = 160 ) {
        if ( strlen( $text ) <= $length ) {
            return $text;
        }
        return substr( $text, 0, $length ) . '...';
    }

    /**
     * Get character count with color coding
     *
     * @param int $count Character count
     * @param int $ideal Ideal count
     * @param int $max Maximum count
     * @return string CSS class
     */
    public static function get_count_class( $count, $ideal, $max ) {
        if ( $count < $ideal ) {
            return 'seovela-count-short';
        } elseif ( $count > $max ) {
            return 'seovela-count-long';
        } else {
            return 'seovela-count-good';
        }
    }

    /**
     * Sanitize array of post types
     *
     * @param array $post_types Array of post type slugs
     * @return array
     */
    public static function sanitize_post_types( $post_types ) {
        if ( ! is_array( $post_types ) ) {
            return array();
        }
        $valid_post_types = get_post_types( array( 'public' => true ) );
        return array_values( array_intersect( array_map( 'sanitize_key', $post_types ), $valid_post_types ) );
    }

    /**
     * Sanitize taxonomy slugs array
     *
     * Validates each slug against registered taxonomies.
     *
     * @param array $taxonomies Array of taxonomy slugs.
     * @return array Sanitized array of valid taxonomy slugs.
     */
    public static function sanitize_taxonomies( $taxonomies ) {
        if ( ! is_array( $taxonomies ) ) {
            return array();
        }
        $valid_taxonomies = get_taxonomies( array( 'public' => true ) );
        return array_values( array_intersect( array_map( 'sanitize_key', $taxonomies ), $valid_taxonomies ) );
    }

    /**
     * Get all public post types
     *
     * @return array
     */
    public static function get_public_post_types() {
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        $output = array();

        foreach ( $post_types as $post_type ) {
            if ( $post_type->name !== 'attachment' ) {
                $output[ $post_type->name ] = $post_type->label;
            }
        }

        return $output;
    }

    /**
     * Get all public taxonomies
     *
     * @return array
     */
    public static function get_public_taxonomies() {
        $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
        $output = array();

        foreach ( $taxonomies as $taxonomy ) {
            $output[ $taxonomy->name ] = $taxonomy->label;
        }

        return $output;
    }

    /**
     * Format date for display
     *
     * @param string $date Date string
     * @return string
     */
    public static function format_date( $date ) {
        if ( empty( $date ) ) {
            return __( 'N/A', 'seovela' );
        }
        return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
    }

    /**
     * Get plugin settings URL
     *
     * @return string
     */
    public static function get_settings_url() {
        return admin_url( 'admin.php?page=seovela-settings' );
    }

    /**
     * Encrypt a value for secure storage
     *
     * Uses AES-256-CBC with a key derived from WordPress AUTH_KEY and AUTH_SALT.
     * Falls back to base64 encoding if OpenSSL is not available.
     *
     * @param string $value The plaintext value to encrypt.
     * @return string The encrypted value (base64-encoded with IV prepended), or the original value on failure.
     */
    public static function encrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        // Don't double-encrypt
        if ( self::is_encrypted( $value ) ) {
            return $value;
        }

        if ( ! function_exists( 'openssl_encrypt' ) ) {
            // Fallback: base64 encode with a marker
            return 'enc:b64:' . base64_encode( $value );
        }

        $key    = self::get_encryption_key();
        $method = 'aes-256-cbc';
        $iv_len = openssl_cipher_iv_length( $method );
        $iv     = openssl_random_pseudo_bytes( $iv_len );

        $encrypted = openssl_encrypt( $value, $method, $key, OPENSSL_RAW_DATA, $iv );

        if ( false === $encrypted ) {
            return $value; // Return original on failure
        }

        // Prepend IV and base64 encode, with a marker prefix
        return 'enc:aes:' . base64_encode( $iv . $encrypted );
    }

    /**
     * Decrypt a value that was encrypted with self::encrypt()
     *
     * @param string $value The encrypted value.
     * @return string The decrypted plaintext value, or the original value if not encrypted.
     */
    public static function decrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        if ( ! self::is_encrypted( $value ) ) {
            return $value; // Not encrypted, return as-is (backward compatibility)
        }

        // Handle base64 fallback
        if ( strpos( $value, 'enc:b64:' ) === 0 ) {
            $decoded = base64_decode( substr( $value, 8 ) );
            return false !== $decoded ? $decoded : '';
        }

        // Handle AES encryption
        if ( strpos( $value, 'enc:aes:' ) === 0 ) {
            if ( ! function_exists( 'openssl_decrypt' ) ) {
                return ''; // Cannot decrypt without OpenSSL
            }

            $key    = self::get_encryption_key();
            $method = 'aes-256-cbc';
            $iv_len = openssl_cipher_iv_length( $method );
            $raw    = base64_decode( substr( $value, 8 ) );

            if ( false === $raw || strlen( $raw ) < $iv_len ) {
                return '';
            }

            $iv        = substr( $raw, 0, $iv_len );
            $encrypted = substr( $raw, $iv_len );

            $decrypted = openssl_decrypt( $encrypted, $method, $key, OPENSSL_RAW_DATA, $iv );

            return false !== $decrypted ? $decrypted : '';
        }

        return $value;
    }

    /**
     * Check if a value is encrypted
     *
     * @param string $value The value to check.
     * @return bool
     */
    public static function is_encrypted( $value ) {
        return strpos( $value, 'enc:aes:' ) === 0 || strpos( $value, 'enc:b64:' ) === 0;
    }

    /**
     * Mask an API key for display (show first 4 and last 4 characters)
     *
     * @param string $key The API key (plaintext or encrypted).
     * @return string The masked key (e.g., "sk-a...z1B2").
     */
    public static function mask_api_key( $key ) {
        // Decrypt first if encrypted
        $plain = self::decrypt( $key );

        if ( empty( $plain ) ) {
            return '';
        }

        $len = strlen( $plain );

        if ( $len <= 8 ) {
            return str_repeat( '*', $len );
        }

        return substr( $plain, 0, 4 ) . str_repeat( '*', $len - 8 ) . substr( $plain, -4 );
    }

    /**
     * Get the encryption key derived from WordPress salts
     *
     * @return string A 32-byte key for AES-256.
     */
    private static function get_encryption_key() {
        $salt = '';

        if ( defined( 'AUTH_KEY' ) ) {
            $salt .= AUTH_KEY;
        }
        if ( defined( 'AUTH_SALT' ) ) {
            $salt .= AUTH_SALT;
        }

        // Fallback if constants aren't defined
        if ( empty( $salt ) ) {
            $salt = 'seovela-default-encryption-key-' . md5( wp_salt( 'auth' ) );
        }

        return hash( 'sha256', $salt, true ); // 32 bytes for AES-256
    }
}
