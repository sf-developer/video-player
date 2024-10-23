<?php

namespace PanaVideoPlayer\Inc\Main;

defined( 'ABSPATH' ) || exit; // Prevent direct access

/**
 * PVP_Menu class registers the plugin's admin menu.
 *
 * This is a singleton class that registers the top-level admin menu page
 * and dashboard page using WordPress admin menu API.
 *
 * It hooks into 'admin_menu' to register the menu on admin initialization.
 */

if( ! class_exists( 'PVP_Menu' ) )
{
    class PVP_Menu
    {
        static $instance = null;

        public static function instance()
        {
            if (is_null(self::$instance))
            {
                self::$instance = new self();
            }
        }

        public function __construct()
        {
            add_action( 'admin_menu', [ $this, 'add' ] );
        }

        public function add()
        {
            if ( ! is_admin() ) return; //make sure we are on the backend

            $players = get_option( 'pana_video_player_players' );
            // Dashboard menu
            add_menu_page(
                __( 'Dashboard', 'pana-video-player' ),
                __( 'Pana Video Player', 'pana-video-player' ),
                'publish_posts',
                ! $players ? add_query_arg( [ 'action' => 'pvp-dashboard', 'route' => 'add-player', '_pvp_nonce' => wp_create_nonce( 'pvp-dashboard' ) ], admin_url( 'admin.php' ) ) : add_query_arg( [ 'action' => 'pvp-dashboard', 'route' => 'statistics', '_pvp_nonce' => wp_create_nonce( 'pvp-dashboard' ) ], admin_url( 'admin.php' ) ),
                null,
                'dashicons-playlist-video',
                20
            );
        }
    }
}