<?php
/*
Plugin Name: Elasticpress Archive
Description: Widget for elastic search query
Version: 1.0
Author: AJOTKA Agata Dobrowolska
Author URI: https://ajotka.com
Text Domain: ep-archive
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'EP_ARCHIVE_URL', plugin_dir_url( __FILE__ ) );
define( 'EP_ARCHIVE_PATH', plugin_dir_path( __FILE__ ) );
define( 'EP_ARCHIVE_VERSION', '1.0.0' );

/**
 * Setup hooks and filters for feature
 *
 * @since 1.0
 */
add_action( 'widgets_init', 'ep_archive_register_widgets' );
add_action( 'admin_enqueue_scripts', 'ep_archive_admin_scripts' );


/**
 * Register archive widget(s)
 *
 * @since 1.0
 */
function ep_archive_register_widgets() {
	require_once( dirname( __FILE__ ) . '/class-ep-archive-widget.php' );

	register_widget( 'EP_Archive_Widget' );
}

/**
 * Output scripts for widget admin
 *
 * @param  string $hook
 * @since  1.0
 */
function ep_archive_admin_scripts( $hook ) {
	if ( 'widgets.php' !== $hook ) {
        return;
    }

    $css_url = EP_ARCHIVE_URL . 'assets/css/admin.min.css';

	wp_enqueue_style(
		'elasticpress-archive-admin',
		$css_url,
		array(),
		EP_ARCHIVE_VERSION
	);
}

/**
 * Build query url
 *
 * @since  1.0
 * @return string
 */
function ep_archive_build_query_url( $filters ) {
	$query_string = '';

	$s = get_search_query();

	if ( ! empty( $s ) ) {
		$query_string .= 's=' . $s;
	}

	if ( ! empty( $filters['taxonomies'] ) ) {
		$tax_filters = $filters['taxonomies'];

		foreach ( $tax_filters as $taxonomy => $filter ) {
			if ( ! empty( $filter['terms'] ) ) {
				if ( ! empty( $query_string ) ) {
					$query_string .= '&';
				}

				$query_string .= 'filter_' . $taxonomy . '=' . implode( array_keys( $filter['terms'] ), ',' );
			}
			if ( ! empty( $filter['year'] ) ) {
				if ( ! empty( $query_string ) ) {
					$query_string .= '&';
				}

				$query_string .= 'filter_' . $taxonomy . '=' . implode( array_keys( $filter['year'] ), ',' );
			}
		}
	}

	return strtok( $_SERVER['REQUEST_URI'], '?' ) . ( ( ! empty( $query_string ) ) ? '?' . $query_string : '' );
}

?>