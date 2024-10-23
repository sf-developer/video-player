<?php

namespace PanaVideoPlayer\Inc\Main;

defined( 'ABSPATH' ) || exit; // Prevent direct access

if(! class_exists('PVP_DB')) {

    class PVP_DB
    {
        /**
         * Create database tables on plugin activation
         *
         * @return void
         */
        public static function add_tables(): void
        {

            if( ! is_admin() )
                return;

            global $wpdb;
            $table_prefix = $wpdb->prefix; // Get tables prefix
            $charset_collate = $wpdb->get_charset_collate(); // Get table charset collate
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); // For calling dbDelta function

            /********** Create pvp_statistics table **********/
            $pvp_statistics_table_name = $table_prefix . 'pvp_statistics';

            $pvp_statistics_table = "CREATE TABLE IF NOT EXISTS $pvp_statistics_table_name (

                `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `type` enum('view', 'comment', 'like', 'dislike') NOT NULL DEFAULT 'view',
                `player_id` bigint(20) UNSIGNED NOT NULL,
                `user_id` bigint(20) UNSIGNED NOT NULL,
                `ip` varchar(100) NOT NULL DEFAULT '0.0.0.0',
                `country` varchar(255) DEFAULT NULL,
                `country_code` varchar(4) DEFAULT NULL,
                `state` varchar(255) DEFAULT NULL,
                `city` varchar(255) DEFAULT NULL,
                `zip` varchar(255) DEFAULT NULL,
                `lat` varchar(255) DEFAULT NULL,
                `lon` varchar(255) DEFAULT NULL,
                `device` varchar(255) DEFAULT NULL,
                `os` varchar(255) DEFAULT NULL,
                `browser` varchar(255) DEFAULT NULL,
                `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (`ID`)

            ) $charset_collate;";

            dbDelta($pvp_statistics_table); // Create pvp_statistics table

            /********** Create pvp_user_activity table **********/
            $pvp_user_activity_table_name = $table_prefix . 'pvp_user_activity';

            $pvp_user_activity_table = "CREATE TABLE IF NOT EXISTS $pvp_user_activity_table_name (

                `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `type` enum('like', 'dislike') NOT NULL DEFAULT 'like',
                `player_id` bigint(20) UNSIGNED NOT NULL,
                `user_id` bigint(20) UNSIGNED NOT NULL,
                `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (`ID`)

            ) $charset_collate;";

            dbDelta($pvp_user_activity_table); // Create pvp_user_activity table

            /********** Create pvp_banned_users table **********/
            $pvp_banned_users_table_name = $table_prefix . 'pvp_banned_users';

            $pvp_banned_users_table = "CREATE TABLE IF NOT EXISTS $pvp_banned_users_table_name (

                `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
                `email` varchar(255) NOT NULL DEFAULT '',
                `ip` varchar(100) NOT NULL DEFAULT '0.0.0.0',
                `note` text NOT NULL DEFAULT '',
                `banned_for` varchar(255) DEFAULT NULL,
                `registrar` bigint(20) UNSIGNED NOT NULL,
                `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (`ID`)

            ) $charset_collate;";

            dbDelta($pvp_banned_users_table); // Create pvp_banned_users table

            /********** Create pvp_notifications table **********/
            $pvp_notifications_table_name = $table_prefix . 'pvp_notifications';

            $pvp_notifications_table = "CREATE TABLE IF NOT EXISTS $pvp_notifications_table_name (

                `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `statistic_id` bigint(20) UNSIGNED NOT NULL,
                `video_id` bigint(20) UNSIGNED NOT NULL,
                `type` enum('comment', 'like', 'dislike') NOT NULL DEFAULT 'like',
                `status` enum('read', 'unread') NOT NULL DEFAULT 'unread',
                `registrar` bigint(20) UNSIGNED NOT NULL,
                `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (`ID`)

            ) $charset_collate;";

            dbDelta($pvp_notifications_table); // Create pvp_notifications table

            /********** Create pvp_user_emails table **********/
            $pvp_user_emails_table_name = $table_prefix . 'pvp_user_emails';

            $pvp_user_emails_table = "CREATE TABLE IF NOT EXISTS $pvp_user_emails_table_name (

                `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `player_id` bigint(20) UNSIGNED NOT NULL,
                `email` varchar(255) NOT NULL,
                `registrar` bigint(20) UNSIGNED NOT NULL,
                `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (`ID`)

            ) $charset_collate;";

            dbDelta($pvp_user_emails_table); // Create pvp_user_emails table
        }

        /**
         * Create database tables on plugin activation
         *
         * @return void
         */
        public static function init_settings(): void
        {

            if( ! is_admin() )
                return;

            $plugin_settings = maybe_unserialize( get_option( 'pvp_plugin_settings', [] ) );

            if( empty( $plugin_settings ) )
            {
                add_option( 'pvp_plugin_settings',
                    maybe_serialize( [
                        'api_key' => '',
                        'custom_css' => ''
                    ] )
                );
            }
        }
    }
}