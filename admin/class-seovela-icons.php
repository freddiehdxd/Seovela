<?php
/**
 * Seovela SVG Icon System
 *
 * Premium custom SVG icons that replace generic Dashicons and emojis.
 * Each icon is hand-crafted for crisp rendering at any size.
 *
 * @package Seovela
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Seovela_Icons {

    /**
     * Get an SVG icon by name.
     *
     * @param string $name    Icon name.
     * @param int    $size    Icon size in px (default 24).
     * @param string $class   Additional CSS class.
     * @return string SVG markup.
     */
    public static function get( $name, $size = 24, $class = '' ) {
        $icons = self::get_all_icons();

        if ( ! isset( $icons[ $name ] ) ) {
            return '';
        }

        $css_class = 'seovela-icon seovela-icon-' . esc_attr( $name );
        if ( $class ) {
            $css_class .= ' ' . esc_attr( $class );
        }

        return sprintf(
            '<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">%s</svg>',
            $css_class,
            (int) $size,
            (int) $size,
            $icons[ $name ]
        );
    }

    /**
     * Echo an SVG icon.
     *
     * @param string $name    Icon name.
     * @param int    $size    Icon size in px.
     * @param string $class   Additional CSS class.
     */
    public static function render( $name, $size = 24, $class = '' ) {
        $allowed = array(
            'svg'      => array( 'class' => true, 'width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'xmlns' => true ),
            'path'     => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
            'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true ),
            'rect'     => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true ),
            'line'     => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true ),
            'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true ),
            'polygon'  => array( 'points' => true, 'fill' => true, 'stroke' => true ),
        );
        echo wp_kses( self::get( $name, $size, $class ), $allowed );
    }

    /**
     * Get all icon definitions (path data only).
     *
     * @return array Associative array of icon name => SVG inner markup.
     */
    private static function get_all_icons() {
        return array(

            // ─── Module Icons (Duotone/Filled for maximum visual impact) ──

            'meta' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" fill="currentColor" opacity="0.2"/>'
                    . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '<path d="M14 2v6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '<path d="M9 15l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'sitemap' => '<path d="M12 7.5V12M12 12L5 16.5M12 12L19 16.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
                       . '<circle cx="12" cy="5" r="3" fill="currentColor" opacity="0.25"/>'
                       . '<circle cx="12" cy="5" r="3" stroke="currentColor" stroke-width="2"/>'
                       . '<circle cx="5" cy="19" r="3" fill="currentColor" opacity="0.25"/>'
                       . '<circle cx="5" cy="19" r="3" stroke="currentColor" stroke-width="2"/>'
                       . '<circle cx="19" cy="19" r="3" fill="currentColor" opacity="0.25"/>'
                       . '<circle cx="19" cy="19" r="3" stroke="currentColor" stroke-width="2"/>',

            'schema' => '<path d="M16 18l6-6-6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
                      . '<path d="M8 6l-6 6 6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
                      . '<path d="M14.5 4l-5 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'optimizer' => '<path d="M12 2L2 7l10 5 10-5-10-5z" fill="currentColor" opacity="0.2"/>'
                         . '<path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                         . '<path d="M2 17l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                         . '<path d="M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'redirects' => '<path d="M18 8l4 4-4 4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
                         . '<path d="M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
                         . '<path d="M6 16l-4-4 4-4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>',

            '404-monitor' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" fill="currentColor" opacity="0.2"/>'
                           . '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                           . '<path d="M12 9v4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>'
                           . '<circle cx="12" cy="17" r="1.2" fill="currentColor"/>',

            'ai' => '<circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.15"/>'
                  . '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>'
                  . '<path d="M8 14s1.5 2 4 2 4-2 4-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
                  . '<circle cx="9" cy="9.5" r="1.5" fill="currentColor"/>'
                  . '<circle cx="15" cy="9.5" r="1.5" fill="currentColor"/>',

            'internal-links' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
                              . '<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'image-seo' => '<rect x="3" y="3" width="18" height="18" rx="3" fill="currentColor" opacity="0.15"/>'
                         . '<rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="2"/>'
                         . '<circle cx="8.5" cy="8.5" r="2" fill="currentColor"/>'
                         . '<path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'gsc-integration' => '<path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                               . '<path d="M7 16l4-8 4 4 5-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
                               . '<circle cx="20" cy="7" r="2" fill="currentColor"/>',

            'llms-txt' => '<rect x="4" y="4" width="16" height="16" rx="2" fill="currentColor" opacity="0.15"/>'
                        . '<rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="2"/>'
                        . '<path d="M8 8h3v3H8z" fill="currentColor" opacity="0.5"/>'
                        . '<path d="M13 8h3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
                        . '<path d="M13 11h3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
                        . '<path d="M8 15h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
                        . '<path d="M8 18h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            // ─── Dashboard / Stats Icons ─────────────────────────────

            'total-content' => '<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" fill="currentColor" opacity="0.15"/>'
                             . '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                             . '<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                             . '<path d="M8 7h8M8 11h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'optimized' => '<circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.15"/>'
                         . '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                         . '<path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'modules' => '<rect x="3" y="3" width="7" height="7" rx="2" fill="currentColor" opacity="0.25"/>'
                       . '<rect x="14" y="3" width="7" height="7" rx="2" fill="currentColor" opacity="0.25"/>'
                       . '<rect x="3" y="14" width="7" height="7" rx="2" fill="currentColor" opacity="0.25"/>'
                       . '<rect x="14" y="14" width="7" height="7" rx="2" fill="currentColor" opacity="0.25"/>'
                       . '<rect x="3" y="3" width="7" height="7" rx="2" stroke="currentColor" stroke-width="2"/>'
                       . '<rect x="14" y="3" width="7" height="7" rx="2" stroke="currentColor" stroke-width="2"/>'
                       . '<rect x="3" y="14" width="7" height="7" rx="2" stroke="currentColor" stroke-width="2"/>'
                       . '<rect x="14" y="14" width="7" height="7" rx="2" stroke="currentColor" stroke-width="2"/>',

            'free' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor" opacity="0.2"/>'
                    . '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            // ─── Action / UI Icons ───────────────────────────────────

            'lightbulb' => '<path d="M12 3a6 6 0 0 0-4 10.5V17h8v-3.5A6 6 0 0 0 12 3z" fill="currentColor" opacity="0.15"/>'
                         . '<path d="M9 21h6M12 3a6 6 0 0 0-4 10.5V17h8v-3.5A6 6 0 0 0 12 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                         . '<path d="M10 17v1a2 2 0 1 0 4 0v-1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'rocket' => '<path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11.5L12 15z" fill="currentColor" opacity="0.15"/>'
                      . '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                      . '<path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11.5L12 15z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                      . '<path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                      . '<path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'bolt' => '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" fill="currentColor" opacity="0.2"/>'
                    . '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'warning' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" fill="currentColor" opacity="0.15"/>'
                       . '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2"/>'
                       . '<path d="M12 9v4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>'
                       . '<circle cx="12" cy="17" r="1.2" fill="currentColor"/>',

            'pencil' => '<path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z" fill="currentColor" opacity="0.15"/>'
                      . '<path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'map' => '<path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z" fill="currentColor" opacity="0.15"/>'
                   . '<path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                   . '<path d="M8 2v16M16 6v16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'refresh' => '<path d="M23 4v6h-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                       . '<path d="M1 20v-6h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                       . '<path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'settings' => '<circle cx="12" cy="12" r="3" fill="currentColor" opacity="0.15"/>'
                        . '<circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>'
                        . '<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="currentColor" stroke-width="1.8"/>',

            'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" fill="currentColor" opacity="0.12"/>'
                        . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                        . '<path d="M14 2v6h6M8 13h8M8 17h8M8 9h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'page' => '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-7-7z" fill="currentColor" opacity="0.12"/>'
                    . '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-7-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '<path d="M13 2v7h7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'book' => '<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" fill="currentColor" opacity="0.12"/>'
                    . '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'chat' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" fill="currentColor" opacity="0.12"/>'
                    . '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '<path d="M8 9h8M8 13h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'star' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor" opacity="0.2"/>'
                    . '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill="currentColor" opacity="0.2"/>'
                     . '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'external' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                        . '<path d="M15 3h6v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                        . '<path d="M10 14L21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'arrow-right' => '<path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'close' => '<path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'plus' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>',

            'search' => '<circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>'
                      . '<path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'flag' => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" fill="currentColor" opacity="0.15"/>'
                    . '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '<path d="M4 22v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'check-circle' => '<circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.12"/>'
                            . '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                            . '<path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'trash' => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
                    . '<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'check' => '<path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                        . '<path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                        . '<path d="M12 15V3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',

            'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                      . '<path d="M17 8l-5-5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                      . '<path d="M12 3v12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',

            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                      . '<path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'globe' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>'
                     . '<path d="M2 12h20" stroke="currentColor" stroke-width="1.5"/>'
                     . '<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="1.5"/>',

            'location' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="1.5"/>'
                        . '<circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.5"/>',

            'share' => '<circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="1.5"/>'
                     . '<circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>'
                     . '<circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="1.5"/>'
                     . '<path d="M8.59 13.51l6.83 3.98M15.41 6.51l-6.82 3.98" stroke="currentColor" stroke-width="1.5"/>',

            'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '<path d="M9 22V12h6v10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'post' => '<path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'category' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'tag' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                   . '<circle cx="7" cy="7" r="1.5" fill="currentColor"/>',

            'image' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="1.5"/>'
                     . '<circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>'
                     . '<path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="currentColor" stroke-width="1.5"/>'
                    . '<path d="M14 2v6h6M8 13h8M8 17h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',

            'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="1.5"/>'
                    . '<path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',

            'info' => '<circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.1"/>'
                    . '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>'
                    . '<path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>',

            'tool' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'plugin' => '<path d="M12 2v6M8 6h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
                      . '<rect x="4" y="8" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/>'
                      . '<path d="M9 14v2M15 14v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',

            'question' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>'
                        . '<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
                        . '<circle cx="12" cy="17" r="1" fill="currentColor"/>',

            'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'breadcrumb' => '<path d="M3 12h2l3-3 3 3 3-3 3 3h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                          . '<circle cx="3" cy="12" r="1.5" fill="currentColor"/>'
                          . '<circle cx="21" cy="12" r="1.5" fill="currentColor"/>',

            'building' => '<rect x="4" y="2" width="16" height="20" rx="2" stroke="currentColor" stroke-width="1.5"/>'
                        . '<path d="M9 22v-4h6v4M9 6h.01M15 6h.01M9 10h.01M15 10h.01M9 14h.01M15 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

            'cart' => '<circle cx="9" cy="21" r="1" stroke="currentColor" stroke-width="1.5"/>'
                    . '<circle cx="20" cy="21" r="1" stroke="currentColor" stroke-width="1.5"/>'
                    . '<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '<circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/>',

            'archive' => '<path d="M21 8v13H3V8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                       . '<path d="M1 3h22v5H1z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                       . '<path d="M10 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',

            'yes' => '<path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',

            'no' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>'
                  . '<path d="M15 9l-6 6M9 9l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',

            'sparkle' => '<path d="M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                       . '<path d="M19 15l.5 2 2 .5-2 .5-.5 2-.5-2-2-.5 2-.5.5-2z" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" opacity="0.6"/>'
                       . '<path d="M5 3l.5 1.5L7 5l-1.5.5L5 7l-.5-1.5L3 5l1.5-.5L5 3z" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" opacity="0.6"/>',

            'indexing' => '<circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/>'
                        . '<path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
                        . '<path d="M8 8h6M8 11h4M8 14h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',

            'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',

            'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" fill="currentColor" opacity="0.1"/>'
                    . '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '<path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        );
    }

    /**
     * Get the premium Seovela admin menu icon as a base64 data URI.
     *
     * A distinctive "S" lettermark with signal/analytics motif.
     *
     * @return string Base64 data URI for use in add_menu_page().
     */
    public static function get_menu_icon() {
        // Clean upward-trending chart icon — filled style, crisp at 20x20.
        // Uses a8b2d1 (light slate) as the base fill so it blends with
        // WordPress admin default and colour schemes without looking "black".
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">'
            // Upward trend line
            . '<path d="M2 15l4.5-5 3 3L17 5" fill="none" stroke="#a0aec0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            // Arrow head at peak
            . '<path d="M13 5h4v4" fill="none" stroke="#a0aec0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode( $svg );
    }
}
