<?php

namespace PanaVideoPlayer\Inc\Main;

defined( 'ABSPATH' ) || exit; // Prevent direct access

if( ! class_exists( 'PVP_Admin_Resources' ) )
{
    /**
     * PVP_Admin_Resources class handles enqueuing admin-facing scripts and styles.
     *
     * It registers and enqueues the admin CSS and JavaScript files.
     * It also adds attributes to the enqueued JS file, and sets up translations.
     *
     * Provides methods for enqueuing admin assets, adding script attributes,
     * and setting up translations.
     */
    class PVP_Admin_Resources
    {
        static $instance = null; // Singleton instance

        /**
         * Get singleton instance of the class.
         *
         * @return PVP_Admin_Resources Returns singleton instance of the class.
         */
        public static function instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
        }

        /**
         * Class constructor.
         *
         * - wp_enqueue_scripts: Enqueues the admin CSS and JS.
         * - script_loader_tag: Adds attributes to the enqueued JS file.
         * - wp_enqueue_scripts: Sets up script translations.
         */
        public function __construct()
        {
            add_action('wp_enqueue_scripts', [$this, 'admin']);
            add_action('script_loader_tag', [$this, 'add_attribute_to_script'], 10, 3);
            add_action('wp_enqueue_scripts', [$this, 'set_script_translations'], 99);
        }

        /**
         * Register and enqueue admin CSS and JavaScript.
         *
         * Checks if we are on the admin side, then conditionally registers and enqueues
         * scripts and styles for the admin dashboard page. Also localizes script with
         * REST API options.
         */
        public function admin()
        {
            if (!is_admin()) return; //make sure we are on the backend

            // if( ! isset( $_GET['_pvp_nonce'] ) || ! wp_verify_nonce( $_GET['_pvp_nonce'], 'pvp-dashboard' ) ) return; //check nonce security

            if (isset($_GET['action']) && $_GET['action'] === 'pvp-dashboard') {
                wp_register_style('admin-pvp', PVP_URL . 'assets/admin/style.css', array(), '1.0.0');
                wp_register_script('admin-pvp', PVP_URL . 'assets/admin/script.js', array( 'wp-i18n' ), '1.0.0', true);

                $admin_url = is_network_admin() ? str_replace(network_site_url(), '', network_admin_url('admin.php')) : str_replace(get_site_url(), '', admin_url('admin.php'));
                $subfolder = site_url('', 'relative');
                $basename = $subfolder . $admin_url;

                wp_localize_script('admin-pvp', 'pvpApiOptions', [
                    'restUrl' => esc_url_raw(rest_url()),
                    'restPath' => PLUGIN_NAME . '/admin/v1',
                    'basename' => $basename,
                    'nonce' => wp_create_nonce('wp_rest'),
                    'pluginUrl' => PVP_URL,
                    'pluginVersion' => PLUGIN_VERSION,
                    'releaseDate' => gmdate('M d, Y', strtotime(PLUGIN_RELEASE_DATE))
                ]);
            }
        }

        /**
         * Set script translations.
         *
         * If we are on the admin side, this registers script translations
         * for the admin script.
         */
        public function set_script_translations()
        {
            if (!is_admin()) return; //make sure we are on the backend

            wp_set_script_translations('admin-pvp', 'pana-video-player', plugin_dir_path(__FILE__) . '../../languages');
        }

        /**
         * Add attribute to script tag.
         *
         * Checks if the script handle matches 'admin-pvp',
         * and if so, adds the 'type="module"' attribute
         * to the script tag.
         *
         * @param string $tag The full script tag.
         * @param string $handle The script handle.
         * @param string $source The script source URL.
         * @return string The updated script tag.
         */
        public function add_attribute_to_script($tag, $handle, $source)
        {
            if ($handle === 'admin-pvp')
                $tag = '<script type="module" src="' . $source . '"></script>';

            return $tag;
        }
    }
}