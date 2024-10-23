<?php

namespace PanaVideoPlayer\Inc\Core;

defined( 'ABSPATH' ) || exit; // Prevent direct access

if( ! class_exists( 'PVP_Shortcodes' ) )
{
    class PVP_Shortcodes
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
            add_shortcode( 'pana-video-player', [ $this, 'render' ] );
        }

        public function render( $atts )
        {
            $atts = shortcode_atts(
                [
                    'id' => 0
                ],
                $atts, 'pana-video-player'
            );

            $uuid = get_option( 'pvp_' . $atts['id'] . '_tagid', '' );

            if( empty( $uuid ) )
                return false;

            wp_enqueue_style( 'public-pvp' );
            wp_enqueue_script( 'public-pvp' );
            ob_start();
            ?>
            <div id="pvp-<?php echo $uuid; ?>" class="pana-pvp-player" data-vid="<?php echo $atts['id']; ?>"></div>
            <?php
            $output_string = ob_get_contents();
            ob_end_clean();
            return $output_string;
        }
    }
}