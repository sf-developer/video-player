<?php

/**
 * @package Pana Video Player
 * @version 1.0.0
 * @author Pana Websites
 * @author URI: https://panawebsites.com/
 * @license GPL2
 * @copyright 2023 Pana Websites
 * @link https://panawebsites.com/
 * @since 1.0.0
 *
 * Plugin Name: Pana Video Player
 * Plugin URI: https://panawebsites.com/
 * Description: Pana Video Player is a pro video player plugin for WordPress.
 * Version: 1.0.0
 * Author: Pana Websites
 * Author URI: https://panawebsites.com/
 * License: GPL2
 * Text Domain: pana-video-player
 * Domain Path: /languages
 */

namespace PanaVideoPlayer;

defined( 'ABSPATH' ) || exit; // Prevent direct access

use PanaVideoPlayer\Inc\Main\PVP_DB;
use PanaVideoPlayer\Inc\Main\PVP_Menu;
use PanaVideoPlayer\Inc\Main\PVP_Public_Resources;
use PanaVideoPlayer\Inc\Core\PVP_Dashboard;
use PanaVideoPlayer\Inc\Api\PVP_Admin_Api;
use PanaVideoPlayer\Inc\Api\PVP_Public_Api;
use PanaVideoPlayer\Inc\Core\PVP_Shortcodes;

if( ! class_exists( 'PVP' ) )
{
    /**
     * PVP main plugin class.
     *
     * Initializes the plugin by registering activation and init hooks,
     * loading textdomain, including required files and initializing
     * plugin classes.
     */
    final class PVP
    {
        public function __construct()
        {
            register_activation_hook(__FILE__, [$this, 'activate']);
            add_action('plugins_loaded', [$this, 'i18n']);
            add_action('init', [$this, 'init']);
        }

        /**
         * Activate plugin
         *
         * On plugin activation:
         * - Include DB class
         * - Add plugin tables
         * - Initialize plugin settings
         * - Flush rewrite rules
         */
        public function activate()
        {
            include_once plugin_dir_path(__FILE__) . 'inc/main/db.php';
            PVP_DB::add_tables();
            PVP_DB::init_settings();
            flush_rewrite_rules();
        }

        /**
         * Load plugin textdomain.
         *
         * This loads the translation files for the plugin so it can be translated.
         * It looks in the /languages/ folder and loads the textdomain
         * 'pana-video-player' from there.
         */
        public function i18n()
        {
            load_plugin_textdomain('pana-video-player', false, plugin_basename(__DIR__) . '/languages');
        }

        /**
         * Initialize plugin.
         *
         * Defines plugin constants, includes required files,
         * initializes plugin classes.
         */
        public function init()
        {
            defined('PLUGIN_NAME') or define('PLUGIN_NAME', 'pana-video-player'); // Plugin name
            defined('PLUGIN_VERSION') or define('PLUGIN_VERSION', '1.0.0'); // Plugin version
            defined('PLUGIN_RELEASE_DATE') or define('PLUGIN_RELEASE_DATE', '2024-01-27'); // Plugin release date
            defined('PVP_PATH') or define('PVP_PATH', plugin_dir_path(__FILE__)); // Plugin path
            defined('PVP_URL') or define('PVP_URL', plugin_dir_url(__FILE__)); // Plugin url

            include_once(PVP_PATH . 'inc/main/menu.php');
            include_once(PVP_PATH . 'inc/main/public-resources.php');
            include_once(PVP_PATH . 'inc/core/shortcodes.php');
            include_once(PVP_PATH . 'inc/core/dashboard.php');
            include_once(PVP_PATH . 'inc/api/admin-api.php');
            include_once(PVP_PATH . 'inc/api/public-api.php');

            PVP_Menu::instance();
            PVP_Public_Resources::instance();
            PVP_Dashboard::instance();
            PVP_Shortcodes::instance();
            PVP_Admin_Api::instance();
            PVP_Public_Api::instance();
        }
    }
}
new PVP;
