<?php
/**
 * Breadcrumbs Initialization
 *
 * Registers shortcode and template functions
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load breadcrumbs class
require_once dirname( __FILE__ ) . '/class-seovela-breadcrumbs.php';

/**
 * Get breadcrumbs instance
 *
 * @return Seovela_Breadcrumbs
 */
function seovela_get_breadcrumbs() {
	static $instance = null;
	
	if ( null === $instance ) {
		$instance = new Seovela_Breadcrumbs();
	}
	
	return $instance;
}

/**
 * Display breadcrumbs (template function)
 *
 * @param array $args Optional arguments
 */
function seovela_breadcrumbs( $args = array() ) {
	$breadcrumbs = seovela_get_breadcrumbs();
	$breadcrumbs->output( $args );
}

/**
 * Get breadcrumbs HTML (template function)
 *
 * @param array $args Optional arguments
 * @return string Breadcrumbs HTML
 */
function seovela_get_breadcrumbs_html( $args = array() ) {
	$breadcrumbs = seovela_get_breadcrumbs();
	return $breadcrumbs->get_html( $args );
}

/**
 * Breadcrumbs shortcode
 *
 * Usage: [seovela_breadcrumbs]
 * With args: [seovela_breadcrumbs separator=">" home_text="Start"]
 *
 * @param array $atts Shortcode attributes
 * @return string Breadcrumbs HTML
 */
function seovela_breadcrumbs_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'separator'      => '',
			'home_text'      => '',
			'show_home'      => '',
			'bold_last'      => '',
			'prefix'         => '',
			'container_class' => '',
			'list_class'     => '',
			'item_class'     => '',
			'link_class'     => '',
		),
		$atts,
		'seovela_breadcrumbs'
	);

	// Remove empty values
	$atts = array_filter( $atts, function( $value ) {
		return $value !== '';
	} );

	// Convert string booleans
	if ( isset( $atts['show_home'] ) ) {
		$atts['show_home'] = filter_var( $atts['show_home'], FILTER_VALIDATE_BOOLEAN );
	}
	if ( isset( $atts['bold_last'] ) ) {
		$atts['bold_last'] = filter_var( $atts['bold_last'], FILTER_VALIDATE_BOOLEAN );
	}

	return seovela_get_breadcrumbs_html( $atts );
}
add_shortcode( 'seovela_breadcrumbs', 'seovela_breadcrumbs_shortcode' );

/**
 * Auto-insert breadcrumbs if enabled
 */
function seovela_auto_insert_breadcrumbs() {
	$settings = get_option( 'seovela_breadcrumbs_settings', array() );
	
	if ( ! empty( $settings['auto_insert'] ) && $settings['auto_insert'] ) {
		$hook = ! empty( $settings['auto_insert_hook'] ) ? $settings['auto_insert_hook'] : 'seovela_before_content';
		
		add_action( $hook, function() {
			seovela_breadcrumbs();
		} );
	}
}
add_action( 'template_redirect', 'seovela_auto_insert_breadcrumbs' );

/**
 * Output breadcrumb schema in head
 */
function seovela_output_breadcrumb_schema() {
	// Don't output on homepage
	if ( is_front_page() ) {
		return;
	}

	$settings = get_option( 'seovela_breadcrumbs_settings', array() );
	
	// Check if breadcrumbs are enabled
	if ( empty( $settings['enabled'] ) ) {
		return;
	}

	$breadcrumbs = seovela_get_breadcrumbs();
	$schema = $breadcrumbs->get_schema();

	if ( empty( $schema ) ) {
		return;
	}

	echo '<script type="application/ld+json">' . "\n";
	echo wp_json_encode( $schema );
	echo "\n" . '</script>' . "\n";
}
add_action( 'wp_head', 'seovela_output_breadcrumb_schema', 20 );

