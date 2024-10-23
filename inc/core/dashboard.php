<?php

namespace PanaVideoPlayer\Inc\Core;

defined( 'ABSPATH' ) || exit; // Prevent direct access

use PanaVideoPlayer\Inc\Main\PVP_Admin_Resources;

if( ! class_exists( 'PVP_Dashboard' ) )
{
    class PVP_Dashboard
    {
        static $instance = null;

        public static function instance()
        {
            if( is_null( self::$instance ) )
            {
                self::$instance = new self();
            }
        }

        public function __construct()
        {
            add_action( 'admin_action_pvp-dashboard', [ $this, 'dashboard' ] );
        }

        public function dashboard()
        {
            if ( ! is_admin() ) return; //make sure we are on the backend

            self::prepare();
            include_once( sprintf( '%sviews/admin/wrapper.php', PVP_PATH ) ); // Include views
            die;
        }

        private function prepare()
        {
            // Send MIME Type header like WP admin-header.
            @header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
            add_filter( 'show_admin_bar', '__return_false' );

            // Remove all WordPress actions
            remove_all_actions( 'wp_head' );
            remove_all_actions( 'wp_print_styles' );
            remove_all_actions( 'wp_print_head_scripts' );
            remove_all_actions( 'wp_footer' );

            // Handle `admin_head`
            add_action( 'wp_head', 'wp_enqueue_scripts', 1 );
            add_action( 'wp_head', 'wp_print_styles', 8 );
            add_action( 'wp_head', 'wp_print_head_scripts', 9 );
            add_action( 'wp_head', 'wp_site_icon' );
            add_action( 'wp_head', [ $this, 'styles' ] );

            add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
            add_action( 'wp_footer', 'wp_auth_check_html', 30 );
            add_action( 'wp_footer', [ $this, 'scripts' ] );

            // Handle `wp_enqueue_scripts`
            remove_all_actions( 'wp_enqueue_scripts' );

            include_once( PVP_PATH . 'inc/main/admin-resources.php' );
            PVP_Admin_Resources::instance();

            // Setup default heartbeat options
            add_filter( 'heartbeat_settings', function( $settings ) {
                $settings['interval'] = 15;
                return $settings;
            } );
        }

        public function styles()
        {
            wp_enqueue_style( 'wp-auth-check' );
            wp_enqueue_style( 'admin-pvp' );
        }

        public function scripts()
        {
            wp_enqueue_script( 'wp-auth-check' );
            wp_enqueue_script( 'admin-pvp' );
        }
    }
}