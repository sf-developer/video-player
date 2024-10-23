<?php

namespace PanaVideoPlayer\Inc\Main;

defined( 'ABSPATH' ) || exit; // Prevent direct access

if( ! class_exists( 'PVP_Public_Resources' ) )
{
    /**
     * PVP_Public_Resources class handles enqueuing public-facing scripts and styles.
     *
     * It registers and enqueues the public CSS and JavaScript files.
     * It also adds attributes to the enqueued JS file, and sets up translations.
     *
     * Provides methods for enqueuing public assets, adding script attributes,
     * and setting up translations.
     */
    class PVP_Public_Resources
    {
        static $instance = null; // Singleton instance

        /**
         * Get singleton instance of the class.
         *
         * @return PVP_Public_Resources Returns singleton instance of the class.
         */
        public static function instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
        }

        /**
         * Constructor function to initialize the class.
         *
         * Registers the following actions:
         * - wp_enqueue_scripts: Enqueues the public CSS and JS.
         * - script_loader_tag: Adds attributes to the enqueued JS file.
         * - wp_enqueue_scripts: Sets up script translations.
         */
        public function __construct()
        {
            add_action('wp_enqueue_scripts', [$this, 'public']);
            add_action('script_loader_tag', [$this, 'add_attribute_to_script'], 10, 3);
            add_action('wp_enqueue_scripts', [$this, 'set_script_translations'], 99);
        }

        /**
         * Register and enqueue public-facing CSS and JavaScript.
         *
         * Registers the public CSS and JavaScript files.
         * Localizes the public JS file with REST API options.
         */
        public function public()
        {
            wp_register_style('public-pvp', PVP_URL . 'assets/public/style.css', array(), '1.0.0');
            wp_register_script('public-pvp', PVP_URL . 'assets/public/script.js', array( 'wp-i18n' ), '1.0.0', true);

            wp_localize_script('public-pvp', 'pvpApiOptions', [
                'restUrl' => esc_url_raw(rest_url()),
                'pluginUrl' => PVP_URL,
                'nonce' => wp_create_nonce('wp_rest')
            ]);

            if( file_exists( PVP_PATH . 'assets/public/custom.css' ) )
            {
                wp_enqueue_style( 'custom-public-pvp', PVP_URL . 'assets/public/custom.css', array(), '1.0.0' );
            }
        }

        /**
         * Sets up script translations.
         *
         * Registers script translations for the 'public-pvp' script.
         *
         * @since 1.0.0
         */
        public function set_script_translations()
        {
            wp_set_script_translations('public-pvp', 'pana-video-player', plugin_dir_path(__FILE__) . '../../languages');
        }

        /**
         * Adds a 'type="module"' attribute to the enqueued public-pvp script.
         *
         * This allows the public JS file to use ES module syntax.
         *
         * @since 1.0.0
         *
         * @param string $tag    The <script> tag for the enqueued script.
         * @param string $handle The script's registered handle.
         * @param string $source The script's source URL.
         * @return string $tag   The filtered script tag.
         */
        public function add_attribute_to_script($tag, $handle, $source)
        {
            if ($handle === 'public-pvp')
                $tag = '<script type="module" src="' . $source . '"></script>';

            return $tag;
        }
    }
}