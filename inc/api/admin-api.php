<?php

namespace PanaVideoPlayer\Inc\Api;

defined('ABSPATH') || exit; // Prevent direct access

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

if (!class_exists('PVP_Admin_Api')) {
    class PVP_Admin_Api
    {
        static $instance = null; // Singleton instance of the class

        private $basename = PLUGIN_NAME . '/admin/'; // Base name of the REST API routes

        private $version = 'v1'; // API version

        /**
         * Get singleton instance of the class.
         *
         * Checks if instance already exists and returns it,
         * otherwise creates new instance.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @return PVP_Admin_Api Returns the PVP_Admin_Api instance.
         */
        public static function instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
        }

        /**
         * Constructor function that initializes the REST API routes when the class is instantiated.
         *
         * Registers the rest_api_init action to call the routes() method
         * to register the REST API routes.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @return void
         */
        public function __construct()
        {
            add_action('rest_api_init', [$this, 'routes']);
        }

        /**
         * Registers the REST API routes.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @return void
         */
        public function routes()
        {
            /**
             * Registers a REST API route to create a new video player.
             *
             * @since 1.0.0
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args {
             *     Route arguments.
             *
             *     @type string $methods HTTP methods this route responds to.
             *     @type callable $callback Callback function to run when route is accessed.
             *     @type callable $permission_callback Function that checks user permissions.
             * }
             */
            register_rest_route($this->basename . $this->version, '/player', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'add_player'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request->get_json_params()) && is_array($request->get_json_params());
                }
            ));

            /**
             * Registers a REST API route to get all video players.
             *
             * @since 1.0.0
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args {
             *     Route arguments.
             *
             *     @type string $methods HTTP methods this route responds to.
             *     @type callable $callback Callback function to run when route is accessed.
             * }
             */
            register_rest_route($this->basename . $this->version, '/players', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_players']
            ));

            /**
             * Registers a REST API route to get the options for a video player.
             *
             * @since 1.0.0
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param int $id The ID of the video player to get options for.
             * @param array $args {
             *     Route arguments.
             *
             *     @type string $methods HTTP methods this route responds to.
             *     @type callable $callback Callback function to run when route is accessed.
             *     @type callable $permission_callback Function that checks user permissions.
             * }
             * @return WP_REST_Response The player options if the request is valid, WP_Error otherwise.
             */
            register_rest_route($this->basename . $this->version, '/options/player/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_options'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to get a specific option for a video player.
             *
             * @since 1.0.0
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param int $id The ID of the video player.
             * @param string $option The option name to get. Valid options are
             * 'general', 'appearance', 'playerButtons', 'ads', 'comments',
             * 'sensitiveContent', 'emailForm', 'callToActionBtn', 'actionBar'.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/option/player/(?P<id>\d+)/(?P<option>[a-zA-Z0-9-]+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_option'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && !empty($request['option']) && is_string($request['option']) && strlen($request['option']) > 0 && in_array($request['option'], ['general', 'appearance', 'playerButtons', 'ads', 'comments', 'sensitiveContent', 'emailForm', 'callToActionBtn', 'actionBar']);
                }
            ));

            /**
             * Registers a REST API route to update options for a video player.
             *
             * @since 1.0.0
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param int $id The ID of the video player to update options for.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/options/player/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_option'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && !empty($request->get_json_params()) && is_array($request->get_json_params());
                }
            ));

            /**
             * Registers a REST API route to delete a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param int $id The ID of the video player to delete.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/player/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_player'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to get the current logged in user.
             *
             * @since 1.0.0
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/current-user', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_current_user']
            ));

            /**
             * Registers a REST API route to get the WordPress admin URL.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/admin', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_wp_admin_url']
            ));

            /**
             * Registers a REST API route to get statistics for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param int $id The ID of the video player to get statistics for.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_statistics'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to get statistics for users.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/statistics/users', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_users_statistics']
            ));

            /**
             * Registers a REST API route to get statistics for a video player filtered by user ID.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param int $id The ID of the video player to get statistics for.
             * @param int $uid The user ID to filter statistics by.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/user/(?P<uid>\d+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_statistics_by_user'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && is_numeric($request['uid']) && $request['uid'] >= 0;
                }
            ));

            /**
             * Registers a REST API route to get statistics for comments.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/statistics/comments', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_comments_statistics']
            ));

            /**
             * Registers a REST API route to get monthly comment statistics.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/statistics/monthly-comments', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_this_month_comments_statistics']
            ));

            /**
             * Registers a REST API route to get comment statistics for a specific video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/comments', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_comments_statistics_by_player'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to get monthly comment statistics for a specific video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/monthly-comments', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_this_month_comments_statistics_by_player'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to get chart statistics for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/chart', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'chart_statistics'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to get country statistics for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/countries', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_countries_statistics'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to get country statistics for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/country/(?P<country>[a-zA-Z]+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_statistics_by_country'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && !empty($request['country']) && is_string($request['country']) && strlen($request['country']) > 0;
                }
            ));

            /**
             * Registers a REST API route to get state statistics for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/state/(?P<state>[a-zA-Z]+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_statistics_by_state'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && !empty($request['state']) && is_string($request['state']) && strlen($request['state']) > 0;
                }
            ));

            /**
             * Registers a REST API route to get city statistics for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/city/(?P<city>[a-zA-Z]+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_statistics_by_city'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && !empty($request['city']) && is_string($request['city']) && strlen($request['city']) > 0;
                }
            ));

            /**
             * Registers a REST API route to get year statistics for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/year/(?P<year>[0-9]+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_statistics_by_year'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && !empty($request['year']) && is_numeric($request['year']) && strlen($request['year']) == 4;
                }
            ));

            /**
             * Registers a REST API route to get date statistics for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/date/(?P<date>[0-9-]+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_statistics_by_date'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && !empty($request['date']) && is_string($request['date']) && strlen($request['date']) > 0;
                }
            ));

            /**
             * Registers a REST API route to get date range statistics for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/statistics/player/(?P<id>\d+)/range/(?P<start>[0-9-]+)/(?P<end>[0-9-]+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_statistics_by_range_date'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && !empty($request['start']) && is_string($request['start']) && strlen($request['start']) > 0 && !empty($request['end']) && is_string($request['end']) && strlen($request['end']) > 0;
                }
            ));

            /**
             * Registers a REST API route to get all comments.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/comments', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_comments']
            ));

            /**
             * Registers a REST API route to get all comments for a video player.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/player/(?P<id>\d+)/comments', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_player_comments'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to approve a comment.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/comment/(?P<id>\d+)/approve', array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'approve_comment'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to reject a comment.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/comment/(?P<id>\d+)/reject', array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'reject_comment'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to reply a comment.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/comment/(?P<id>\d+)/reply', array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'reply_comment'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to delete a comment.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/comment/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_comment'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to get notifications.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param string $args['args'] The parameters for the route.
             */
            register_rest_route($this->basename . $this->version, '/notifications', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_notifications'],
                'args' => [
                    'limit' => [
                        'validate_callback' => function ($param, $request, $key) {
                            return is_numeric($param);
                        }
                    ],
                    'status' => [
                        'validate_callback' => function ($param, $request, $key) {
                            return in_array($param, array('all', 'new'));
                        }
                    ]
                ]
            ));

            /**
             * Registers a REST API route to update the status of a notification.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/notification/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_notification_status'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to send a support ticket.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/ticket', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'send_ticket'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request->get_params());
                }
            ));

            /**
             * Registers a REST API route to save settings.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Function to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/settings', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'save_settings'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request->get_json_params()) && is_array($request->get_json_params());
                }
            ));

            /**
             * Registers a REST API route to get settings.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/settings', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_settings']
            ));

            /**
             * Registers a REST API route to get the "What's New" data.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/whats-new', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_whats_new']
            ));

            /**
             * Registers a REST API route to get users who submitted a comment.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/users-submitted-comment', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_users_who_submitted_comment']
            ));

            /**
             * Registers a REST API route to get banned users.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             */
            register_rest_route($this->basename . $this->version, '/banned-users', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_banned_users']
            ));

            /**
             * Registers a REST API route to unban a user by ID.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Callback to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/unban-user/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'unban_user'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to ban a user.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Callback to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/ban-user', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'ban_user'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request->get_json_params()) && is_array($request->get_json_params());
                }
            ));

            /**
             * Registers a REST API route to get a user's emails by ID.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Callback to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/user-emails/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_emails'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));

            /**
             * Registers a REST API route to delete a user email by ID.
             *
             * @param string $this->basename The base URL for the REST API routes.
             * @param string $this->version The version number for the REST API routes.
             * @param array $args Route arguments.
             * @param string $args['methods'] The HTTP methods this route responds to.
             * @param callable $args['callback'] Callback function when route is accessed.
             * @param callable $args['permission_callback'] Callback to check permissions.
             */
            register_rest_route($this->basename . $this->version, '/delete-email/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_email'],
                'permission_callback' => function (WP_REST_Request $request) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0;
                }
            ));
        }

        public function add_player(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = time();

            $players = maybe_unserialize(get_option('pana_video_player_players', []));

            $options_data = $videos_data = [];

            $params = json_decode($request->get_body());

            if (
                !isset($params->general) ||
                !isset($params->appearance) ||
                !isset($params->playerButtons) ||
                !isset($params->ads) ||
                !isset($params->comments) ||
                !isset($params->sensitiveContent) ||
                !isset($params->emailForm) ||
                !isset($params->callToActionBtn) ||
                !isset($params->actionBar) ||
                !isset($params->videos)
            ) {
                return new WP_Error(
                    'bad-request',
                    __('Bad request!', 'pana-video-player'),
                    array('status' => 400)
                );
            }

            $options_data['general'] = $params->general;
            $options_data['appearance'] = $params->appearance;
            $options_data['playerButtons'] = $params->playerButtons;
            $options_data['ads'] = $params->ads;
            $options_data['comments'] = $params->comments;
            $options_data['sensitiveContent'] = $params->sensitiveContent;
            $options_data['emailForm'] = $params->emailForm;
            $options_data['callToActionBtn'] = $params->callToActionBtn;
            $options_data['actionBar'] = $params->actionBar;
            $videos_data['videos'] = $params->videos;

            $options_result = add_option('pvp_' . $player_id . '_options', maybe_serialize($options_data));
            $videos_result = add_option('pvp_' . $player_id . '_videos', maybe_serialize($videos_data));
            $tagid_result = add_option('pvp_' . $player_id . '_tagid', wp_generate_uuid4());

            if ($options_result && $videos_result && $tagid_result) {
                $players[] = [
                    'id' => $player_id,
                    'title' => count($videos_data['videos']->videoLists) === 1 ? $videos_data['videos']->videoLists[0]->title : $options_data['general']->playerName,
                    'thumbnail' => in_array($videos_data['videos']->type, ['html5', 'youtube', 'vimeo']) && count($videos_data['videos']->videoLists) === 1 ? $videos_data['videos']->videoLists[0]->thumbnail[0]->link : PVP_URL . 'assets/images/video-playlist.svg',
                    'shortcode' => '[pana-video-player id=\'' . $player_id . '\']',
                ];

                update_option('pana_video_player_players', maybe_serialize($players));

                $videos = $this->get_videos($player_id);

                return new WP_REST_Response([
                    'code' => 'success',
                    'message' => __('Options added successfully', 'pana-video-player'),
                    'data' => [
                        'player_id' => $player_id,
                        'shortcode' => '[pana-video-player id=\'' . $player_id . '\']',
                        'option' => maybe_unserialize(get_option('pvp_' . $player_id . '_options', [])),
                        'videos' => isset($videos['videos']) ? $videos['videos'] : [],
                        'players' => maybe_unserialize($players)
                    ]
                ], 200);
            }

            return new WP_Error(
                'not-updated',
                __('Problem occurred on updating options', 'pana-video-player'),
                array('status' => 500)
            );
        }

        public function get_players(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $players = maybe_unserialize(get_option('pana_video_player_players', []));

            $data = [];
            if (!empty($players)) {
                foreach ($players as $key => $player) {
                    $data[] = [
                        'row' => ++$key,
                        'id' => $player['id'],
                        'title' => $player['title'],
                        'thumbnail' => $player['thumbnail'],
                        'shortcode' => $player['shortcode']
                    ];
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Players retrieved successfully', 'pana-video-player'),
                'data' => $data
            ], 200);
        }

        public function get_options(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));
            $videos = $this->get_videos($player_id);

            $data = array_merge_recursive($options, $videos);

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player options retrieved successfully', 'pana-video-player'),
                'data' => $data
            ], 200);
        }

        public function get_option(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];
            $option_name = $request['option'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            $options_data = [];

            if (!empty($options)) {
                foreach ($options as $key => $value) {
                    if ($key == $option_name) {
                        $options_data[$key] = $value;
                    }
                }
            }

            $videos = $this->get_videos($player_id);
            $data = array_merge_recursive($options_data, $videos);

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player option retrieved successfully', 'pana-video-player'),
                'data' => $data
            ], 200);
        }

        public function update_option(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $params = json_decode($request->get_body());

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_REST_Response([
                    'code' => 'not-found',
                    'message' => __('No options found', 'pana-video-player')
                ], 404);
            }

            $videos = maybe_unserialize(get_option('pvp_' . $player_id . '_videos', []));

            $options_data = $videos_data = [];

            if (isset($params->general)) {
                $options_data['general'] = $params->general;
            }

            if (isset($params->appearance)) {
                $options_data['appearance'] = $params->appearance;
            }

            if (isset($params->playerButtons)) {
                $options_data['playerButtons'] = $params->playerButtons;
            }

            if (isset($params->ads)) {
                $options_data['ads'] = $params->ads;
            }

            if (isset($params->comments)) {
                $options_data['comments'] = $params->comments;
            }

            if (isset($params->sensitiveContent)) {
                $options_data['sensitiveContent'] = $params->sensitiveContent;
            }

            if (isset($params->emailForm)) {
                $options_data['emailForm'] = $params->emailForm;
            }

            if (isset($params->callToActionBtn)) {
                $options_data['callToActionBtn'] = $params->callToActionBtn;
            }

            if (isset($params->actionBar)) {
                $options_data['actionBar'] = $params->actionBar;
            }

            if (isset($params->videos)) {
                $videos_data['videos'] = $params->videos;
            }

            $options = array_merge($options, $options_data);
            $options_result = update_option('pvp_' . $player_id . '_options', maybe_serialize($options));


            if (empty($videos)) {
                $videos_result = add_option('pvp_' . $player_id . '_videos', $videos_data);
            } else {
                $videos = array_merge($videos, $videos_data);
                $videos_result = update_option('pvp_' . $player_id . '_videos', maybe_serialize($videos));
            }

            if ($options_result || $videos_result) {
                $players = maybe_unserialize(get_option('pana_video_player_players', []));

                if (!empty($players)) {
                    foreach ($players as $index => $player) {
                        if ($player['id'] == $player_id) {
                            $players[$index]['title'] = count($videos['videos']->videoLists) === 1 ? $videos['videos']->videoLists[0]->title : $options['general']->playerName;
                            update_option('pana_video_player_players', maybe_serialize($players));
                            break;
                        }
                    }
                }

                $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));
                $videos = $this->get_videos($player_id);
                $data = array_merge_recursive($options, $videos);


                return new WP_REST_Response([
                    'code' => 'success',
                    'message' => __('Player options updated successfully', 'pana-video-player'),
                    'data' => $data
                ], 200);
            }

            return new WP_REST_Response([
                'status' => 215,
                'code' => 'cant-update',
                'message' => __('Problem occurred on updating options', 'pana-video-player')
            ], 215);
        }

        public function delete_player(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];
            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));
            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this id', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $result = delete_option('pvp_' . $player_id . '_options');
            if ($result) {
                $players = maybe_unserialize(get_option('pana_video_player_players', []));
                if (!empty($players)) {
                    foreach ($players as $index => $player) {
                        if ($player['id'] == $player_id) {
                            unset($players[$index]);
                            $players = array_values($players);
                            update_option('pana_video_player_players', maybe_serialize($players));
                            break;
                        }
                    }
                }

                $videos = maybe_unserialize(get_option('pvp_' . $player_id . '_videos', []));

                if (!empty($videos)) {
                    $result = delete_option('pvp_' . $player_id . '_videos');

                    if ($result) {
                        $tagid = get_option('pvp_' . $player_id . '_tagid', '');

                        if (!empty($tagid)) {
                            $result = delete_option('pvp_' . $player_id . '_tagid');

                            if ($result) {
                                return new WP_REST_Response([
                                    'code' => 'success',
                                    'message' => __('The player has been successfully deleted', 'pana-video-player')
                                ], 200);
                            }

                            return new WP_Error(
                                'not-deleted',
                                __('The player was successfully deleted but there was a problem deleting the tag id', 'pana-video-player'),
                                array('status' => 500)
                            );
                        }

                        return new WP_REST_Response([
                            'code' => 'success',
                            'message' => __('The player has been successfully deleted', 'pana-video-player')
                        ], 200);
                    }

                    return new WP_Error(
                        'not-deleted',
                        __('The player was successfully deleted but there was a problem deleting the videos', 'pana-video-player'),
                        array('status' => 500)
                    );
                }
            }

            return new WP_Error(
                'not-deleted',
                __('Problem occurred on deleting options', 'pana-video-player'),
                array('status' => 500)
            );
        }

        public function get_current_user(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $current_user = wp_get_current_user();
            $user_data = [
                'id' => $current_user->ID,
                'name' => $current_user->display_name,
                'email' => $current_user->user_email,
                'role' => $current_user->roles[0],
                'avatar' => get_avatar_url($current_user->ID, ['default' => 'gravatar_default']),
                'editUserUrl' => get_edit_user_link($current_user->ID),
                'newUserUrl' => admin_url('user-new.php'),
                'logoutUrl' => wp_logout_url(),
                'privacyPolicyUrl' => get_privacy_policy_url()
            ];

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('User data has been successfully retrieved', 'pana-video-player'),
                'data' => [
                    'user_data' => $user_data
                ]
            ], 200);
        }

        public function get_wp_admin_url(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('WP admin url has been successfully retrieved', 'pana-video-player'),
                'data' => [
                    'url' => admin_url()
                ]
            ], 200);
        }

        public function get_statistics(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `ID`, `type`, `creation_date` AS `date` FROM `{$wpdb->prefix}pvp_statistics` WHERE `player_id` = %d",
                    $player_id
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $statistics_data = [
                'like' => [
                    'count' => 0,
                    'rate' => 0,
                    'type' => 'equal'
                ],
                'dislike' => [
                    'count' => 0,
                    'rate' => 0,
                    'type' => 'equal'
                ],
                'comment' => [
                    'count' => 0,
                    'rate' => 0,
                    'type' => 'equal'
                ],
                'view' => [
                    'count' => 0,
                    'rate' => 0,
                    'type' => 'equal'
                ]
            ];

            if (!empty($statistics)) {
                $compare_statistics = [];
                $today = gmdate('Y-m-d');
                $yesterday = gmdate('Y-m-d', strtotime('-1 day'));


                $compare_statistics['like']['total'] =
                    $compare_statistics['like']['this'] =
                    $compare_statistics['like']['last'] =
                    $compare_statistics['dislike']['total'] =
                    $compare_statistics['dislike']['this'] =
                    $compare_statistics['dislike']['last'] =
                    $compare_statistics['comment']['total'] =
                    $compare_statistics['comment']['this'] =
                    $compare_statistics['comment']['last'] =
                    $compare_statistics['view']['total'] =
                    $compare_statistics['view']['this'] =
                    $compare_statistics['view']['last'] = 0;

                foreach ($statistics as $statistic) {
                    $compare_statistics[$statistic->type]['total'] += 1;
                    if (gmdate('Y-m-d', strtotime($statistic->date)) === $today) {
                        $compare_statistics[$statistic->type]['this'] += 1;
                    } elseif (gmdate('Y-m-d', strtotime($statistic->date)) === $yesterday) {
                        $compare_statistics[$statistic->type]['last'] += 1;
                    }
                }

                if (!empty($compare_statistics)) {
                    $total_count = $this_count = $last_count = 0;
                    foreach ($compare_statistics as $type => $data) {
                        $total_count = $data['total'];
                        $this_count = $data['this'];
                        $last_count = $data['last'];
                        $statistics_data[$type] = [
                            'count' => number_format($total_count),
                            'rate' => isset($this_count, $last_count) && (!empty($this_count) || !empty($last_count)) ? round(abs((($this_count - $last_count) / ($this_count + $last_count))) * 100, 2) : 0,
                            'type' => isset($this_count, $last_count) ? ((($this_count - $last_count) < 0) ? 'decrease' : ((($this_count - $last_count) === 0) ? 'equal' : 'increase')) : ((isset($this_count) && !isset($last_count)) ? 'increase' : 'decrease')
                        ];
                    }
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player statistics retrieved successfully', 'pana-video-player'),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200);
        }

        public function get_users_statistics(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            global $wpdb;
            $users_statistics = $wpdb->get_results(
                "SELECT COUNT(`ID`) AS `count` FROM `$wpdb->users`
                UNION ALL
                SELECT COUNT(`ID`) AS `count` FROM `$wpdb->users` WHERE DATE(`user_registered`) = CURDATE()
                UNION ALL
                SELECT COUNT(`ID`) AS `count` FROM `{$wpdb->prefix}pvp_banned_users`"
            );

            if (is_null($users_statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Users statistics retrieved successfully', 'pana-video-player'),
                'data' => [
                    'total' => number_format($users_statistics[0]->count),
                    'new' => number_format($users_statistics[1]->count),
                    'banned' => number_format($users_statistics[2]->count),
                    'manage_users_link' => admin_url('users.php')
                ]
            ], 200);
        }

        public function get_comments_statistics(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $total = $approved = $rejected = $pending = 0;

            global $wpdb;
            $results = $wpdb->get_results(
                "SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_type` = 'pvp_comment'
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_approved` = '1' AND `comment_type` = 'pvp_comment'
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_approved` = '-1' AND `comment_type` = 'pvp_comment'
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_approved` = '0' AND `comment_type` = 'pvp_comment'"
            );

            if (!empty($results)) {
                $total = intval($results[0]->count);
                $approved = intval($results[1]->count);
                $rejected = intval($results[2]->count);
                $pending = intval($results[3]->count);
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Users statistics retrieved successfully', 'pana-video-player'),
                'data' => [
                    'total' => $total,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'pending' => $pending
                ]
            ], 200);
        }

        public function get_this_month_comments_statistics(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $this_month_total_comments =
                $last_month_total_comments =
                $this_month_approved_comments =
                $last_month_approved_comments =
                $this_month_rejected_comments =
                $last_month_rejected_comments =
                $this_month_pending_comments =
                $last_month_pending_comments = 0;

            global $wpdb;

            $results = $wpdb->get_results(
                "SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_type` = 'pvp_comment' AND MONTH(`comment_date`) = MONTH(CURRENT_DATE()) AND YEAR(`comment_date`) = YEAR(CURRENT_DATE())
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_type` = 'pvp_comment' AND DATE(`comment_date`) BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%%Y-%%m-01 00:00:00') AND DATE_FORMAT(LAST_DAY(NOW() - INTERVAL 1 MONTH), '%%Y-%%m-%%d 23:59:59')
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_approved` = '1' AND `comment_type` = 'pvp_comment' AND MONTH(`comment_date`) = MONTH(CURRENT_DATE()) AND YEAR(`comment_date`) = YEAR(CURRENT_DATE())
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_approved` = '1' AND `comment_type` = 'pvp_comment' AND DATE(`comment_date`) BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%%Y-%%m-01 00:00:00') AND DATE_FORMAT(LAST_DAY(NOW() - INTERVAL 1 MONTH), '%%Y-%%m-%%d 23:59:59')
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_approved` = '-1' AND `comment_type` = 'pvp_comment' AND MONTH(`comment_date`) = MONTH(CURRENT_DATE()) AND YEAR(`comment_date`) = YEAR(CURRENT_DATE())
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_approved` = '-1' AND `comment_type` = 'pvp_comment' AND DATE(`comment_date`) BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%%Y-%%m-01 00:00:00') AND DATE_FORMAT(LAST_DAY(NOW() - INTERVAL 1 MONTH), '%%Y-%%m-%%d 23:59:59')
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_approved` = '0' AND `comment_type` = 'pvp_comment' AND MONTH(`comment_date`) = MONTH(CURRENT_DATE()) AND YEAR(`comment_date`) = YEAR(CURRENT_DATE())
                UNION ALL
                SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_approved` = '0' AND `comment_type` = 'pvp_comment' AND DATE(`comment_date`) BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%%Y-%%m-01 00:00:00') AND DATE_FORMAT(LAST_DAY(NOW() - INTERVAL 1 MONTH), '%%Y-%%m-%%d 23:59:59')"
            );

            if (!empty($results)) {
                $this_month_total_comments = intval($results[0]->count);
                $last_month_total_comments = intval($results[1]->count);
                $this_month_approved_comments = intval($results[2]->count);
                $last_month_approved_comments = intval($results[3]->count);
                $this_month_rejected_comments = intval($results[4]->count);
                $last_month_rejected_comments = intval($results[5]->count);
                $this_month_pending_comments = intval($results[6]->count);
                $last_month_pending_comments = intval($results[7]->count);
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Users statistics retrieved successfully', 'pana-video-player'),
                'data' => [
                    'total' => [
                        'count' => $this_month_total_comments,
                        'rate' => !empty($this_month_total_comments) || !empty($last_month_total_comments) ? round(abs((($this_month_total_comments - $last_month_total_comments) / ($this_month_total_comments + $last_month_total_comments))) * 100, 2) : 0,
                        'type' => ((($this_month_total_comments - $last_month_total_comments) < 0) ? 'decrease' : ((($this_month_total_comments - $last_month_total_comments) === 0) ? 'equal' : 'increase'))
                    ],
                    'approved' => [
                        'count' => $this_month_approved_comments,
                        'rate' => !empty($this_month_approved_comments) || !empty($last_month_approved_comments) ? round(abs((($this_month_approved_comments - $last_month_approved_comments) / ($this_month_approved_comments + $last_month_approved_comments))) * 100, 2) : 0,
                        'type' => ((($this_month_approved_comments - $last_month_approved_comments) < 0) ? 'decrease' : ((($this_month_approved_comments - $last_month_approved_comments) === 0) ? 'equal' : 'increase'))
                    ],
                    'rejected' => [
                        'count' => $this_month_rejected_comments,
                        'rate' => !empty($this_month_rejected_comments) || !empty($last_month_rejected_comments) ? round(abs((($this_month_rejected_comments - $last_month_rejected_comments) / ($this_month_rejected_comments + $last_month_rejected_comments))) * 100, 2) : 0,
                        'type' => ((($this_month_rejected_comments - $last_month_rejected_comments) < 0) ? 'decrease' : ((($this_month_rejected_comments - $last_month_rejected_comments) === 0) ? 'equal' : 'increase'))
                    ],
                    'pending' => [
                        'count' => $this_month_pending_comments,
                        'rate' => !empty($this_month_pending_comments) || !empty($last_month_pending_comments) ? round(abs((($this_month_pending_comments - $last_month_pending_comments) / ($this_month_pending_comments + $last_month_pending_comments))) * 100, 2) : 0,
                        'type' => ((($this_month_pending_comments - $last_month_pending_comments) < 0) ? 'decrease' : ((($this_month_pending_comments - $last_month_pending_comments) === 0) ? 'equal' : 'increase'))
                    ]
                ]
            ], 200);
        }

        public function get_comments_statistics_by_player(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $total = $approved = $rejected = $pending = 0;

            global $wpdb;
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d)
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d ) AND `comment_approved` = '1'
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d ) AND `comment_approved` = '-1'
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d ) AND `comment_approved` = '0'",
                    $player_id,
                    $player_id,
                    $player_id,
                    $player_id
                )
            );

            if (!empty($results)) {
                $total = intval($results[0]->count);
                $approved = intval($results[1]->count);
                $rejected = intval($results[2]->count);
                $pending = intval($results[3]->count);
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Users statistics retrieved successfully', 'pana-video-player'),
                'data' => [
                    'total' => $total,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'pending' => $pending
                ]
            ], 200);
        }

        public function get_this_month_comments_statistics_by_player(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $this_month_total_comments =
                $last_month_total_comments =
                $this_month_approved_comments =
                $last_month_approved_comments =
                $this_month_rejected_comments =
                $last_month_rejected_comments =
                $this_month_pending_comments =
                $last_month_pending_comments = 0;

            global $wpdb;

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d) AND `comment_type` = 'pvp_comment' AND MONTH(`comment_date`) = MONTH(CURRENT_DATE()) AND YEAR(`comment_date`) = YEAR(CURRENT_DATE())
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d) AND `comment_type` = 'pvp_comment' AND DATE(`comment_date`) BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%%Y-%%m-01 00:00:00') AND DATE_FORMAT(LAST_DAY(NOW() - INTERVAL 1 MONTH), '%%Y-%%m-%%e 23:59:59')
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d) AND `comment_approved` = '1' AND `comment_type` = 'pvp_comment' AND MONTH(`comment_date`) = MONTH(CURRENT_DATE()) AND YEAR(`comment_date`) = YEAR(CURRENT_DATE())
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d) AND `comment_approved` = '1' AND `comment_type` = 'pvp_comment' AND DATE(`comment_date`) BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%%Y-%%m-01 00:00:00') AND DATE_FORMAT(LAST_DAY(NOW() - INTERVAL 1 MONTH), '%%Y-%%m-%%e 23:59:59')
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d) AND `comment_approved` = '-1' AND `comment_type` = 'pvp_comment' AND MONTH(`comment_date`) = MONTH(CURRENT_DATE()) AND YEAR(`comment_date`) = YEAR(CURRENT_DATE())
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d) AND `comment_approved` = '-1' AND `comment_type` = 'pvp_comment' AND DATE(`comment_date`) BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%%Y-%%m-01 00:00:00') AND DATE_FORMAT(LAST_DAY(NOW() - INTERVAL 1 MONTH), '%%Y-%%m-%%e 23:59:59')
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN (SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d) AND `comment_approved` = '0' AND `comment_type` = 'pvp_comment' AND MONTH(`comment_date`) = MONTH(CURRENT_DATE()) AND YEAR(`comment_date`) = YEAR(CURRENT_DATE())
                    UNION ALL
                    SELECT COUNT(`comment_ID`) AS `count` FROM `$wpdb->comments` WHERE `comment_ID` IN ( SELECT `comment_id` FROM `$wpdb->commentmeta` WHERE WHERE `meta_key` = 'pvp_player_id' AND `meta_value` = %d) AND `comment_approved` = '0' AND `comment_type` = 'pvp_comment' AND DATE(`comment_date`) BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%%Y-%%m-01 00:00:00') AND DATE_FORMAT(LAST_DAY(NOW() - INTERVAL 1 MONTH), '%%Y-%%m-%%e 23:59:59')",
                    $player_id,
                    $player_id,
                    $player_id,
                    $player_id,
                    $player_id,
                    $player_id,
                    $player_id,
                    $player_id
                )
            );

            if (!empty($results)) {
                $this_month_total_comments = intval($results[0]->count);
                $last_month_total_comments = intval($results[1]->count);
                $this_month_approved_comments = intval($results[2]->count);
                $last_month_approved_comments = intval($results[3]->count);
                $this_month_rejected_comments = intval($results[4]->count);
                $last_month_rejected_comments = intval($results[5]->count);
                $this_month_pending_comments = intval($results[6]->count);
                $last_month_pending_comments = intval($results[7]->count);
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Users statistics retrieved successfully', 'pana-video-player'),
                'data' => [
                    'total' => [
                        'count' => $this_month_total_comments,
                        'rate' => !empty($this_month_total_comments) || !empty($last_month_total_comments) ? round(abs((($this_month_total_comments - $last_month_total_comments) / ($this_month_total_comments + $last_month_total_comments))) * 100, 2) : 0,
                        'type' => ((($this_month_total_comments - $last_month_total_comments) < 0) ? 'decrease' : ((($this_month_total_comments - $last_month_total_comments) === 0) ? 'equal' : 'increase'))
                    ],
                    'approved' => [
                        'count' => $this_month_approved_comments,
                        'rate' => !empty($this_month_approved_comments) || !empty($last_month_approved_comments) ? round(abs((($this_month_approved_comments - $last_month_approved_comments) / ($this_month_approved_comments + $last_month_approved_comments))) * 100, 2) : 0,
                        'type' => ((($this_month_approved_comments - $last_month_approved_comments) < 0) ? 'decrease' : ((($this_month_approved_comments - $last_month_approved_comments) === 0) ? 'equal' : 'increase'))
                    ],
                    'rejected' => [
                        'count' => $this_month_rejected_comments,
                        'rate' => !empty($this_month_rejected_comments) || !empty($last_month_rejected_comments) ? round(abs((($this_month_rejected_comments - $last_month_rejected_comments) / ($this_month_rejected_comments + $last_month_rejected_comments))) * 100, 2) : 0,
                        'type' => ((($this_month_rejected_comments - $last_month_rejected_comments) < 0) ? 'decrease' : ((($this_month_rejected_comments - $last_month_rejected_comments) === 0) ? 'equal' : 'increase'))
                    ],
                    'pending' => [
                        'count' => $this_month_pending_comments,
                        'rate' => !empty($this_month_pending_comments) || !empty($last_month_pending_comments) ? round(abs((($this_month_pending_comments - $last_month_pending_comments) / ($this_month_pending_comments + $last_month_pending_comments))) * 100, 2) : 0,
                        'type' => ((($this_month_pending_comments - $last_month_pending_comments) < 0) ? 'decrease' : ((($this_month_pending_comments - $last_month_pending_comments) === 0) ? 'equal' : 'increase'))
                    ]
                ]
            ], 200);
        }

        public function chart_statistics(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            global $wpdb;

            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS `count`, `type`, DATE_FORMAT(`creation_date`,'%%b') AS `month` FROM `{$wpdb->prefix}pvp_statistics` WHERE `player_id` = %d AND DATE_FORMAT(`creation_date`,'%%Y') = YEAR(curdate()) GROUP BY `type`, `month` ORDER BY `month` DESC",
                    $player_id
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $output = $data = [];

            $output = [
                [
                    'id' => 'like',
                    'data' => [
                        ['x' => 'Jan', 'y' => 0], ['x' => 'Feb', 'y' => 0], ['x' => 'Mar', 'y' => 0], ['x' => 'Apr', 'y' => 0], ['x' => 'May', 'y' => 0], ['x' => 'Jun', 'y' => 0],
                        ['x' => 'Jul', 'y' => 0], ['x' => 'Aug', 'y' => 0], ['x' => 'Sep', 'y' => 0], ['x' => 'Oct', 'y' => 0], ['x' => 'Nov', 'y' => 0], ['x' => 'Dec', 'y' => 0]
                    ]
                ],
                [
                    'id' => 'dislike',
                    'data' => [
                        ['x' => 'Jan', 'y' => 0], ['x' => 'Feb', 'y' => 0], ['x' => 'Mar', 'y' => 0], ['x' => 'Apr', 'y' => 0], ['x' => 'May', 'y' => 0], ['x' => 'Jun', 'y' => 0],
                        ['x' => 'Jul', 'y' => 0], ['x' => 'Aug', 'y' => 0], ['x' => 'Sep', 'y' => 0], ['x' => 'Oct', 'y' => 0], ['x' => 'Nov', 'y' => 0], ['x' => 'Dec', 'y' => 0]
                    ]
                ],
                [
                    'id' => 'comment',
                    'data' => [
                        ['x' => 'Jan', 'y' => 0], ['x' => 'Feb', 'y' => 0], ['x' => 'Mar', 'y' => 0], ['x' => 'Apr', 'y' => 0], ['x' => 'May', 'y' => 0], ['x' => 'Jun', 'y' => 0],
                        ['x' => 'Jul', 'y' => 0], ['x' => 'Aug', 'y' => 0], ['x' => 'Sep', 'y' => 0], ['x' => 'Oct', 'y' => 0], ['x' => 'Nov', 'y' => 0], ['x' => 'Dec', 'y' => 0]
                    ]
                ],
                [
                    'id' => 'view',
                    'data' => [
                        ['x' => 'Jan', 'y' => 0], ['x' => 'Feb', 'y' => 0], ['x' => 'Mar', 'y' => 0], ['x' => 'Apr', 'y' => 0], ['x' => 'May', 'y' => 0], ['x' => 'Jun', 'y' => 0],
                        ['x' => 'Jul', 'y' => 0], ['x' => 'Aug', 'y' => 0], ['x' => 'Sep', 'y' => 0], ['x' => 'Oct', 'y' => 0], ['x' => 'Nov', 'y' => 0], ['x' => 'Dec', 'y' => 0]
                    ]
                ]
            ];

            if (!empty($statistics)) {
                foreach ($statistics as $key => $statistic) {
                    $data[$statistic->type][] = [
                        'x' => $statistic->month,
                        'y' => $statistic->count
                    ];
                }

                foreach ($data as $key => $value) {
                    foreach ($output as $index => $o) {
                        if ($o['id'] === $key) {
                            foreach ($value as $v) {
                                for ($i = 0; $i < 12; $i++) {
                                    if ($o['data'][$i]['x'] === $v['x']) {
                                        $output[$index]['data'][$i]['y'] = $v['y'];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Chart object build successfully', 'pana-video-player'),
                'data' => $output
            ], 200);
        }

        public function get_statistics_by_user(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $uid = $request['uid'];

            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS count, `type` FROM `{$wpdb->prefix}pvp_statistics` WHERE player_id = %d AND `user_id` = %d GROUP BY `type`",
                    $player_id,
                    $uid
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $statistics_data = [
                'like' => 0,
                'dislike' => 0,
                'comment' => 0,
                'view' => 0
            ];

            if (!empty($statistics)) {
                foreach ($statistics as $statistic) {
                    $statistics_data[$statistic->type] = number_format($statistic->count);
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player statistic retrieved successfully', 'pana-video-player'),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200);
        }

        public function get_countries_statistics(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $plugin_settings = maybe_unserialize(get_option('pvp_plugin_settings', []));

            if (empty($plugin_settings) || !isset($plugin_settings->api_key)  || empty($plugin_settings->api_key)) {
                return new WP_Error(
                    'api-key-not-found',
                    __('No API key found to get countries statistics', 'pana-video-player'),
                    array('status' => 211)
                );
            }

            global $wpdb;
            $compare = $request->get_param('compare');

            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS `count`, `type`, `country`, `country_code`, DATE_FORMAT(`creation_date`,'%%Y-%%m-%%e') AS `date` FROM `{$wpdb->prefix}pvp_statistics` WHERE `player_id` = %d AND `country_code` != '' GROUP BY `country_code`, `type`, `date` ORDER BY `type` DESC",
                    $player_id
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $statistics_data = $compare_statistics = $_data = $countries = $output = [];

            if (!empty($statistics)) {
                foreach ($statistics as $statistic) {
                    if (isset($compare)) {
                        if (!isset($compare_statistics[$statistic->country]['like']['this']))
                            $compare_statistics[$statistic->country]['like']['this'] = 0;

                        if (!isset($compare_statistics[$statistic->country]['like']['last']))
                            $compare_statistics[$statistic->country]['like']['last'] = 0;

                        if (!isset($compare_statistics[$statistic->country]['dislike']['this']))
                            $compare_statistics[$statistic->country]['dislike']['this'] = 0;

                        if (!isset($compare_statistics[$statistic->country]['dislike']['last']))
                            $compare_statistics[$statistic->country]['dislike']['last'] = 0;

                        if (!isset($compare_statistics[$statistic->country]['comment']['this']))
                            $compare_statistics[$statistic->country]['comment']['this'] = 0;

                        if (!isset($compare_statistics[$statistic->country]['comment']['last']))
                            $compare_statistics[$statistic->country]['comment']['last'] = 0;

                        if (!isset($compare_statistics[$statistic->country]['view']['this']))
                            $compare_statistics[$statistic->country]['view']['this'] = 0;

                        if (!isset($compare_statistics[$statistic->country]['view']['last']))
                            $compare_statistics[$statistic->country]['view']['last'] = 0;

                        $compare_statistics[$statistic->country]['country_code'] = $statistic->country_code;

                        switch ($compare) {
                            case 'day':
                                $today = strtotime(gmdate('Y-m-d'));
                                $yesterday = strtotime(gmdate('Y-m-d', strtotime('-1 day')));
                                if (strtotime($statistic->date) === $today) {
                                    $compare_statistics[$statistic->country][$statistic->type]['this'] += $statistic->count;
                                } elseif (strtotime($statistic->date) === $yesterday) {
                                    $compare_statistics[$statistic->country][$statistic->type]['last'] += $statistic->count;
                                }
                                break;
                            case 'week':
                                $this_week = strtotime(gmdate('Y-m-d', strtotime('-7 day')));
                                $last_week = strtotime(gmdate('Y-m-d', strtotime('-14 day')));
                                if (strtotime($statistic->date) >= $this_week) {
                                    $compare_statistics[$statistic->country][$statistic->type]['this'] += $statistic->count;
                                } elseif (strtotime($statistic->date) >= $last_week && strtotime($statistic->date) < $this_week) {
                                    $compare_statistics[$statistic->country][$statistic->type]['last'] += $statistic->count;
                                }
                                break;
                            case 'month':
                                $this_month = strtotime(gmdate('Y-m-d', strtotime('-1 month')));
                                $last_month = strtotime(gmdate('Y-m-d', strtotime('-2 month')));
                                if (strtotime($statistic->date) >= $this_month) {
                                    $compare_statistics[$statistic->country][$statistic->type]['this'] += $statistic->count;
                                } elseif (strtotime($statistic->date) >= $last_month && strtotime($statistic->date) < $this_month) {
                                    $compare_statistics[$statistic->country][$statistic->type]['last'] += $statistic->count;
                                }
                                break;
                            case 'year':
                                $this_year = strtotime(gmdate('Y-m-d', strtotime('-1 year')));
                                $last_year = strtotime(gmdate('Y-m-d', strtotime('-2 year')));
                                if (strtotime($statistic->date) >= $this_year) {
                                    $compare_statistics[$statistic->country][$statistic->type]['this'] += $statistic->count;
                                } elseif (strtotime($statistic->date) >= $last_year && strtotime($statistic->date) < $this_year) {
                                    $compare_statistics[$statistic->country][$statistic->type]['last'] += $statistic->count;
                                }
                                break;
                            default:
                                return new WP_Error(
                                    'invalid-compare',
                                    __('Invalid compare parameter', 'pana-video-player'),
                                    array('status' => 400)
                                );
                        }
                    } else {
                        if (!isset($statistics_data[$statistic->country]['like']))
                            $statistics_data[$statistic->country]['like'] = 0;

                        if (!isset($statistics_data[$statistic->country]['dislike']))
                            $statistics_data[$statistic->country]['dislike'] = 0;

                        if (!isset($statistics_data[$statistic->country]['comment']))
                            $statistics_data[$statistic->country]['comment'] = 0;

                        if (!isset($statistics_data[$statistic->country]['view']))
                            $statistics_data[$statistic->country]['view'] = 0;

                        $statistics_data[$statistic->country][$statistic->type] += $statistic->count;
                        $statistics_data[$statistic->country]['country_code'] = $statistic->country_code;
                    }
                }

                if (!empty($compare_statistics)) {
                    foreach ($compare_statistics as $country => $types) {
                        $this_count = $last_count = 0;
                        $country_code = $types['country_code'];
                        foreach ($types as $type => $data) {
                            if ($type !== 'country_code') {
                                $this_count = $data['this'];
                                $last_count = $data['last'];
                                $countries[] = [
                                    'name' => $country,
                                    'flag' => 'https://cdn.ipinfo.io/static/images/countries-flags/' . $country_code . '.svg'
                                ];
                                $_data[$country][] = [
                                    $type => [
                                        'count' => $this_count,
                                        'rate' => isset($this_count, $last_count) && (!empty($this_count) || !empty($last_count)) ? round(abs((($this_count - $last_count) / ($this_count + $last_count))) * 100, 2) : 0,
                                        'type' => isset($this_count, $last_count) ? ((($this_count - $last_count) < 0) ? 'decrease' : ((($this_count - $last_count) === 0) ? 'equal' : 'increase')) : ((isset($this_count) && !isset($last_count)) ? 'increase' : 'decrease')
                                    ]
                                ];
                            }
                        }
                    }
                } else {
                    if (!empty($statistics_data)) {
                        foreach ($statistics_data as $country => $types) {
                            $count = 0;
                            foreach ($types as $type => $data) {
                                $count = isset($data['count']) ? array_sum($data['count'])  : 0;
                                $country_code = $data['country_code'];
                                $countries[] = [
                                    'name' => $country,
                                    'flag' => 'https://cdn.ipinfo.io/static/images/countries-flags/' . $country_code . '.svg'
                                ];
                                $_data[$country][] = [
                                    $type => [
                                        'count' => $count
                                    ]
                                ];
                            }
                        }
                    }
                }

                $countries = array_values(array_unique($countries, SORT_REGULAR));
                foreach ($countries as $country) {
                    $output[] = [
                        'country' => $country['name'],
                        'flag' =>  $country['flag'],
                        'types' => $_data[$country['name']]
                    ];
                }
            } else {
                $types = [
                    [
                        'like' => [
                            'count' => 0,
                            'rate' => 0,
                            'type' => 'equal'
                        ]
                    ],
                    [
                        'dislike' => [
                            'count' => 0,
                            'rate' => 0,
                            'type' => 'equal'
                        ]
                    ],
                    [
                        'comment' => [
                            'count' => 0,
                            'rate' => 0,
                            'type' => 'equal'
                        ]
                    ],
                    [
                        'view' => [
                            'count' => 0,
                            'rate' => 0,
                            'type' => 'equal'
                        ]
                    ]
                ];

                $output[] = [
                    'country' => null,
                    'flag' =>  null,
                    'types' => $types
                ];
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player statistic retrieved successfully', 'pana-video-player'),
                'data' => $output
            ], 200);
        }

        public function get_statistics_by_country(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $country = $request['country'];

            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS count, `type` FROM `{$wpdb->prefix}pvp_statistics` WHERE player_id = %d AND `country_code` = %s GROUP BY `type`",
                    $player_id,
                    $country
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $statistics_data = [
                'like' => 0,
                'dislike' => 0,
                'comment' => 0,
                'view' => 0
            ];

            if (!empty($statistics)) {
                foreach ($statistics as $statistic) {
                    $statistics_data[$statistic->type] = number_format($statistic->count);
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player statistic retrieved successfully', 'pana-video-player'),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200);
        }

        public function get_statistics_by_state(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $state = $request['state'];

            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS count, `type` FROM `{$wpdb->prefix}pvp_statistics` WHERE player_id = %d AND `state_code` = %s GROUP BY `type`",
                    $player_id,
                    $state
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $statistics_data = [
                'like' => 0,
                'dislike' => 0,
                'comment' => 0,
                'view' => 0
            ];

            if (!empty($statistics)) {
                foreach ($statistics as $statistic) {
                    $statistics_data[$statistic->type] = number_format($statistic->count);
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player statistic retrieved successfully', 'pana-video-player'),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200);
        }

        public function get_statistics_by_city(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $city = $request['city'];

            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS count, `type` FROM `{$wpdb->prefix}pvp_statistics` WHERE player_id = %d AND `city` = %s GROUP BY `type`",
                    $player_id,
                    $city
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $statistics_data = [
                'like' => 0,
                'dislike' => 0,
                'comment' => 0,
                'view' => 0
            ];

            if (!empty($statistics)) {
                foreach ($statistics as $statistic) {
                    $statistics_data[$statistic->type] = number_format($statistic->count);
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player statistic retrieved successfully', 'pana-video-player'),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200);
        }

        public function get_statistics_by_date(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $date = $request['date'];

            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS count, `type` FROM `{$wpdb->prefix}pvp_statistics` WHERE player_id = %d AND DATE(`creation_date`) = %s GROUP BY `type`",
                    $player_id,
                    $date
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $statistics_data = [
                'like' => 0,
                'dislike' => 0,
                'comment' => 0,
                'view' => 0
            ];

            if (!empty($statistics)) {
                foreach ($statistics as $statistic) {
                    $statistics_data[$statistic->type] = number_format($statistic->count);
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player statistic retrieved successfully', 'pana-video-player'),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200);
        }

        public function get_statistics_by_year(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $year = $request['year'];

            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS `count`, `type`, `creation_date` FROM `{$wpdb->prefix}pvp_statistics` WHERE `player_id` = %d AND YEAR(`creation_date`) = %s GROUP BY `type`",
                    $player_id,
                    $year
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $statistics_data = [
                'like' => 0,
                'dislike' => 0,
                'comment' => 0,
                'view' => 0
            ];

            if (!empty($statistics)) {
                foreach ($statistics as $statistic) {
                    $statistics_data[$statistic->type] = [
                        number_format($statistic->count),
                        gmdate('Y-m-d', strtotime($statistic->creation_date))
                    ];
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player statistic retrieved successfully', 'pana-video-player'),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200);
        }

        public function get_statistics_by_range_date(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $start = $request['start'];
            $end = $request['end'];

            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS `count`, `type`, `creation_date` FROM `{$wpdb->prefix}pvp_statistics` WHERE `player_id` = %d AND DATE(`creation_date`) BETWEEN %s AND %s GROUP BY `creation_date`",
                    $player_id,
                    $start,
                    $end
                )
            );

            if (is_null($statistics)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $statistics_data = [
                'like' => 0,
                'dislike' => 0,
                'comment' => 0,
                'view' => 0
            ];

            if (!empty($statistics)) {
                foreach ($statistics as $statistic) {
                    $count = isset($statistics_data[$statistic->type][gmdate('Y-m-d', strtotime($statistic->creation_date))]) ? $statistics_data[$statistic->type][gmdate('Y-m-d', strtotime($statistic->creation_date))] + 1 : 1;
                    $statistics_data[$statistic->type][gmdate('Y-m-d', strtotime($statistic->creation_date))] = number_format($count);
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Player statistic retrieved successfully', 'pana-video-player'),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200);
        }

        public function get_comments(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }

            global $wpdb;
            $comments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        `t1`.`comment_ID`,
                        `t1`.`comment_author_email` AS `user_email`,
                        `t1`.`comment_author` AS `user_name`,
                        `t1`.`comment_author_IP` AS `user_ip`,
                        `t1`.`comment_date` AS `submit_date`,
                        `t1`.`comment_content` AS `comment`,
                        `t2`.`meta_value` AS `video_id`,
                        `t3`.`option_value` AS `player_videos`,
                        `t1`.`comment_approved` AS `status`,
                        IF(`user_id` = '0', %s, `user_id`) AS `user_id`
                    FROM
                        `$wpdb->comments` AS `t1`
                    LEFT JOIN `$wpdb->commentmeta` AS `t2` ON `t2`.`meta_key` = 'pvp_player_id' AND `t1`.`comment_ID` = `t2`.`comment_id`
                    LEFT JOIN `$wpdb->options` AS `t3` ON `t3`.`option_name` = CONCAT('pvp_', `t2`.`meta_value`, '_videos') AND `t1`.`comment_ID` = `t2`.`comment_id`
                    WHERE
                        `comment_type` = %s
                    AND `comment_author_email` NOT IN (SELECT `email` FROM `{$wpdb->prefix}pvp_banned_users`)",
                    __('Guest', 'pana-video-player'),
                    'pvp_comment'
                )
            );

            if (!empty($comments)) {
                foreach ($comments as $key => $comment) {
                    $player_videos = maybe_unserialize(maybe_unserialize($comment->player_videos));
                    // @TODO: check if video exists
                    $player_name = !empty($player_videos) && isset($player_videos['videos']) ? (count($player_videos['videos']->videoLists) === 1 ? $player_videos['videos']->videoLists[0]->title : 'Multiple Videos') : 'No Videos';
                    unset($comments[$key]->player_videos);
                    $comments[$key]->video_name = $player_name;
                }
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Comments retrieved successfully', 'pana-video-player'),
                'data' => $comments
            ], 200);
        }

        public function get_player_comments(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $player_id = $request['id'];

            $options = maybe_unserialize(get_option('pvp_' . $player_id . '_options', []));

            if (empty($options)) {
                return new WP_Error(
                    'not-found',
                    __('No player found with this ID', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            $comments = get_comments([
                'post_id' => 0,
                'status'  => ['1', '-1', '0'],
                'type'    => 'pvp_comment',
                'meta_key' => 'pvp_player_id',
                'meta_value' => $player_id,
            ]);

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Comments retrieved successfully', 'pana-video-player'),
                'data' => [
                    'comments' => $comments
                ]
            ], 200);
        }

        public function approve_comment(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $commentarr = array();
            $commentarr['comment_ID'] = $request['id'];
            $commentarr['comment_approved'] = 1;
            $result = wp_update_comment($commentarr);

            if (is_wp_error($result) || !$result || $result === 0) {
                return new WP_Error(
                    'not-updated',
                    __('Problem occurred on updating comment', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            delete_comment_meta($request['id'], 'pvp_pending');

            $player_id = get_comment_meta($request['id'], 'pvp_player_id', true);

            $comments = get_comments([
                'post_id' => 0,
                'status'  => 'all',
                'type'    => 'pvp_comment',
                'meta_key' => 'pvp_player_id',
                'meta_value' => $player_id,
            ]);

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Comment approved successfully', 'pana-video-player'),
                'data' => [
                    'comments' => $comments
                ]
            ], 200);
        }

        public function reject_comment(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $commentarr = array();
            $commentarr['comment_ID'] = $request['id'];
            $commentarr['comment_approved'] = '-1';
            $result = wp_update_comment($commentarr);

            if (is_wp_error($result) || !$result || $result === 0) {
                return new WP_Error(
                    'not-updated',
                    __('Problem occurred on updating comment', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            delete_comment_meta($request['id'], 'pvp_pending');

            $player_id = get_comment_meta($request['id'], 'pvp_player_id', true);

            $comments = get_comments([
                'post_id' => 0,
                'status'  => 'all',
                'type'    => 'pvp_comment',
                'meta_key' => 'pvp_player_id',
                'meta_value' => $player_id,
            ]);

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Comment rejected successfully', 'pana-video-player'),
                'data' => [
                    'comments' => $comments
                ]
            ], 200);
        }

        public function reply_comment(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $comment_id = $request['id'];
            $params = json_decode($request->get_body());

            if (!isset($params->reply) || empty($params->reply)) {
                return new WP_Error(
                    'bad-request',
                    __('Bad request!', 'pana-video-player'),
                    array('status' => 400)
                );
            }

            $comment_exists = get_comment($comment_id);

            if (is_null($comment_exists)) {
                return new WP_Error(
                    'bad-request',
                    __('Bad request!', 'pana-video-player'),
                    array('status' => 400)
                );
            }

            $current_user = wp_get_current_user();

            // Insert comment.
            $comment_id = wp_insert_comment(
                [
                    'comment_post_ID' => 0,
                    'comment_author' => $current_user->display_name,
                    'comment_author_email' => $current_user->user_email,
                    'comment_content' => $params->reply,
                    'comment_type' => 'pvp_comment',
                    'comment_parent' => $comment_id,
                    'comment_date' => current_time('mysql'),
                    'comment_date_gmt' => current_time('mysql', 1),
                    'comment_karma' => 0,
                    'comment_approved' => 1,
                    'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'user_id' => $current_user->ID,
                    'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
                    'comment_author_url' => ''
                ]
            );

            // Check if comment is inserted.
            if (!$comment_id) {
                return new WP_Error(
                    'not-insert',
                    __('Problem occurred on inserting comment', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            // Return response.
            return new WP_REST_Response([
                'status' => 200,
                'message' => __('Comment inserted successfully', 'pana-video-player')
            ], 200);
        }

        public function delete_comment(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }

            $player_id = get_comment_meta($request['id'], 'pvp_player_id', true);

            $result = wp_delete_comment($request['id'], true);

            if (!$result) {
                return new WP_Error(
                    'not-deleted',
                    __('Problem occurred on deleting comment', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $comments = get_comments([
                'post_id' => 0,
                'status'  => 'all',
                'type'    => 'pvp_comment',
                'meta_key' => 'pvp_player_id',
                'meta_value' => $player_id,
            ]);

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Comment deleted successfully', 'pana-video-player'),
                'data' => [
                    'comment' => $comments
                ]
            ], 200);
        }

        public function get_notifications(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }

            global $wpdb;
            $limit = null !== $request->get_param('limit') ? $request->get_param('limit') : '';
            $status = $request->get_param('status');

            if (empty($status) || $status === 'all') {
                if (!empty($limit)) {
                    $notifications = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT `t1`.`ID`, `t1`.`video_id`, `t1`.`type`, `t1`.`status`, `t1`.`creation_date`, `t2`.`option_value` FROM `{$wpdb->prefix}pvp_notifications` AS `t1`
                            LEFT JOIN `{$wpdb->prefix}options` AS `t2` ON CONCAT('pvp_', `t1`.`video_id`, '_options') = `t2`.`option_name`
                            ORDER BY `t1`.`creation_date` DESC LIMIT %d",
                            $limit
                        )
                    );
                } else {
                    $notifications = $wpdb->get_results(
                        "SELECT `t1`.`ID`, `t1`.`video_id`, `t1`.`type`, `t1`.`status`, `t1`.`creation_date`, `t2`.`option_value` FROM `{$wpdb->prefix}pvp_notifications` AS `t1`
                        LEFT JOIN `{$wpdb->prefix}options` AS `t2` ON CONCAT('pvp_', `t1`.`video_id`, '_options') = `t2`.`option_name`
                        ORDER BY `t1`.`creation_date` DESC"
                    );
                }
            } elseif ($status === 'new') {
                if (!empty($limit)) {
                    $notifications = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT `t1`.`ID`, `t1`.`video_id`, `t1`.`type`, `t1`.`status`, `t1`.`creation_date`, `t2`.`option_value` FROM `{$wpdb->prefix}pvp_notifications` AS `t1`
                            LEFT JOIN `{$wpdb->prefix}options` AS `t2` ON CONCAT('pvp_', `t1`.`video_id`, '_options') = `t2`.`option_name`
                            WHERE `t1`.`status` = 'unread' ORDER BY `t1`.`creation_date` DESC LIMIT %d",
                            $limit
                        )
                    );
                } else {
                    $notifications = $wpdb->get_results(
                        "SELECT `t1`.`ID`, `t1`.`video_id`, `t1`.`type`, `t1`.`status`, `t1`.`creation_date`, `t2`.`option_value` FROM `{$wpdb->prefix}pvp_notifications` AS `t1`
                        LEFT JOIN `{$wpdb->prefix}options` AS `t2` ON CONCAT('pvp_', `t1`.`video_id`, '_options') = `t2`.`option_name`
                        WHERE `t1`.`status` = 'unread' ORDER BY `t1`.`creation_date` DESC"
                    );
                }
            } else {
                return new WP_Error(
                    'not-found',
                    __('No notification found', 'pana-video-player'),
                    array('status' => 404)
                );
            }

            if (is_null($notifications)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $output = [];
            foreach ($notifications as $notification) {
                $current_date_time = current_datetime();
                $seconds_ago = (strtotime($current_date_time->format('Y-m-d H:i:s')) - strtotime($notification->creation_date));

                switch ($seconds_ago) {
                    case $seconds_ago >= 31536000:
                        $creation_date = sprintf(__('%s year(s) ago', 'pana-video-player'), intval($seconds_ago / 31536000));
                        break;
                    case $seconds_ago >= 2419200:
                        $creation_date = sprintf(__('%s month(s) ago', 'pana-video-player'), intval($seconds_ago / 2419200));
                        break;
                    case $seconds_ago >= 86400:
                        $creation_date = sprintf(__('%s day(s) ago', 'pana-video-player'), intval($seconds_ago / 86400));
                        break;
                    case $seconds_ago >= 3600:
                        $creation_date = sprintf(__('%s hour(s) ago', 'pana-video-player'), intval($seconds_ago / 3600));
                        break;
                    case $seconds_ago >= 60:
                        $creation_date = sprintf(__('%s minute(s) ago', 'pana-video-player'), intval($seconds_ago / 60));
                        break;
                    default:
                        $creation_date = __('Just now', 'pana-video-player');
                        break;
                }

                switch ($notification->type) {
                    case 'comment':
                        $type = __('comment', 'pana-video-player');
                        break;
                    case 'like':
                        $type = __('like', 'pana-video-player');
                        break;
                    case 'dislike':
                        $type = __('dislike', 'pana-video-player');
                        break;
                    default:
                        $type = __('unknown', 'pana-video-player');
                        break;
                }
                $player_options = maybe_unserialize(maybe_unserialize($notification->option_value));
                $player_videos = maybe_unserialize(get_option('pvp_' . $notification->video_id . '_videos'));
                $player_name = !empty($player_videos) && isset($player_videos['videos']) ? (count($player_videos['videos']->videoLists) === 1 ? $player_videos['videos']->videoLists[0]->title : $player_options['general']->playerName) : '';
                $video_poster = !empty($player_videos) && isset($player_videos['videos']) ? $player_videos['videos']->videoLists[0]->thumbnail[0]->link : '';

                $output[] = [
                    'id' => $notification->ID,
                    'type' => $notification->type,
                    'message' => sprintf(
                        __('New %s for %s', 'pana-video-player'),
                        $type,
                        $player_name
                    ),
                    'status' => $notification->status,
                    'player_id' => $notification->video_id,
                    'player_name' => $player_name,
                    'player_poster' => $video_poster,
                    'creation_date' => $creation_date
                ];
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Notifications retrieved successfully', 'pana-video-player'),
                'data' => [
                    'notifications' => $output
                ]
            ], 200);
        }

        public function update_notification_status(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            global $wpdb;
            $notifications_table = "{$wpdb->prefix}pvp_notifications";
            $result = $wpdb->update(
                $notifications_table,
                [
                    'status' => 'read'
                ],
                [
                    'ID' => $request['id']
                ]
            );

            if (!$result) {
                return new WP_Error(
                    'not-update',
                    __('Problem occurred on updating notification', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Notification updated successfully', 'pana-video-player')
            ], 200);
        }

        public function send_ticket(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $params = json_decode($request->get_body());
            $attachment = [];

            if (!isset($params->name, $params->email, $params->subject, $params->message)) {
                return new WP_Error(
                    'bad-request',
                    __('Bad request!', 'pana-video-player'),
                    array('status' => 400)
                );
            }

            $response = wp_remote_get(
                'https://panawebsites.com/support/wp-json/pana-websites-support/public/v1/check-banned-url',
                array(
                    'headers' => array('Content-Type' => 'application/json', 'url' => site_url())
                )
            );

            $response_code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (is_wp_error($response)) {
                return new WP_Error(
                    $body['code'],
                    $body['message'],
                    array('status' => $response_code)
                );
            }

            if ($response_code !== 200) {
                return new WP_Error(
                    $body['code'],
                    $body['message'],
                    array('status' => $response_code)
                );
            }

            if (isset($params->attachment_id) && !empty($params->attachment_id)) {
                $file_path = get_attached_file($params->attachment_id);
                $file_url = wp_get_attachment_url($params->attachment_id);
                $filetype = wp_check_filetype($file_url);
                $valid_extensions = ['zip', 'jpg', 'jpeg', 'png', 'gif', 'tiff'];

                if ($file_path) {
                    if (!in_array(strtolower($filetype['ext']), $valid_extensions)) {
                        return new WP_Error(
                            'invalid-file-type',
                            __('Invalid file type. You can only upload `ZIP`, `JPG`, `JPEG`, `PNG`, `GIF`, `TIFF` files.', 'pana-video-player'),
                            array('status' => 403)
                        );
                    }

                    if (strlen(basename($file_path)) > 100) {
                        return new WP_Error(
                            'exceeded-file-name',
                            __('The file name must be less than 100 character', 'pana-video-player'),
                            array('status' => 403)
                        );
                    }

                    if (filesize($file_path) > 10485760) {
                        return new WP_Error(
                            'exceeded-file-size',
                            __('The file size must be less than 10 MB', 'pana-video-player'),
                            array('status' => 403)
                        );
                    }

                    $attachment = [$file_path];
                }
            }

            add_filter('wp_mail_content_type', [$this, 'set_html_mail_content_type']);

            $message = $params->message . '<br/> Plugin name: Pana Video Player';

            $result = wp_mail(
                'support@panawebsites.com',
                $params->subject,
                $message,
                ['Content-Type: text/html; charset=UTF-8'],
                $attachment
            );

            remove_filter('wp_mail_content_type', [$this, 'set_html_mail_content_type']);

            if (!$result) {
                add_action('wp_mail_failed', [$this, 'log_mailer_errors'], 10, 1);
                return new WP_Error(
                    'not-send',
                    sprintf(__('Problem occurred on sendin email. You can check email log in the % path.', 'pana-video-player'), PVP_PATH . 'logs/mail.log'),
                    array('status' => 502)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Thank you for contacting us. We have received your message and will get back to you as soon as possible.', 'pana-video-player')
            ], 200);
        }

        public function save_settings(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $params = json_decode($request->get_body());

            $ipinfo_api_key = isset($params->api_key) ? $params->api_key : '';
            $custom_css = isset($params->custom_css) ? $params->custom_css : '';

            if (!empty($ipinfo_api_key)) {
                $response = wp_remote_get(
                    add_query_arg('token', $ipinfo_api_key, 'https://ipinfo.io'),
                    array(
                        'headers' => array('Content-Type' => 'application/json')
                    )
                );

                if (is_wp_error($response)) {
                    return new WP_Error(
                        $response->get_error_code(),
                        $response->get_error_message(),
                        array('status' => 500)
                    );
                }
                $response_code = wp_remote_retrieve_response_code($response);
                $response_body = json_decode(wp_remote_retrieve_body($response));

                if ($response_code !== 200) {
                    return new WP_Error(
                        $response_code,
                        $response_body->error->message,
                        array('status' => 216)
                    );
                    return new WP_REST_Response([
                        'code' => 'test-failed',
                        'message' => $response_body->error->message,
                        'data' => [
                            'token' => $ipinfo_api_key
                        ]
                    ], 216);
                }
            }

            if (!empty($custom_css)) {
                global $wp_filesystem;
                require_once(ABSPATH . '/wp-admin/includes/file.php');
                WP_Filesystem();
                $custom_css_file = PVP_PATH . 'assets/public/custom.css';
                $success = $wp_filesystem->put_contents($custom_css_file, $custom_css, FS_CHMOD_FILE);

                if (!$success) {
                    die(__('Error creating file.', 'pana-video-player'));
                }
            }

            update_option('pvp_plugin_settings', maybe_serialize($params));

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Settings updated successfully', 'pana-video-player'),
                'data' => [
                    'settings' => maybe_unserialize(get_option('pvp_plugin_settings', []))
                ]
            ], 200);
        }

        public function get_settings(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $settings = maybe_unserialize(get_option('pvp_plugin_settings', []));

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Settings retrieved successfully', 'pana-video-player'),
                'data' => [
                    'settings' => $settings
                ]
            ], 200);
        }

        public function get_whats_new(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $response = wp_remote_get(
                add_query_arg('item', 'pana-video-player', 'https://panawebsites.com/support/wp-json/pana-websites-support/public/v1/item-features'),
                array(
                    'headers' => array('Content-Type' => 'application/json')
                )
            );

            if (is_wp_error($response)) {
                return new WP_Error(
                    $response->get_error_code(),
                    $response->get_error_message(),
                    array('status' => 500)
                );
            }
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response));

            if ($response_code !== 200) {
                return new WP_Error(
                    str_replace(' ', '-', strtolower($response_body->code)),
                    $response_body->message,
                    array('status' => $response_code)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Item features retrieved successfully', 'pana-video-player'),
                'data' => [
                    'features' => $response_body->data->features
                ]
            ], 200);
        }

        public function get_users_who_submitted_comment(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            global $wpdb;
            $users = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                    DISTINCT `comment_author_email` AS `user_email`,
                        `comment_author` AS `user_name`,
                        `comment_author_IP` AS `user_ip`,
                        `comment_date`,
                        `comment_content`,
                        IF(`user_id` = '0', %s, `user_id`) AS `user_id`
                    FROM
                        `$wpdb->comments`
                    WHERE
                        `comment_type` = %s
                    AND `comment_author_email` NOT IN (SELECT `email` FROM `{$wpdb->prefix}pvp_banned_users`)
                    GROUP BY
                        `comment_author_email`",
                    __('Guest', 'pana-video-player'),
                    'pvp_comment'
                )
            );

            if (is_null($users)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Banned users retrieved successfully', 'pana-video-player'),
                'data' => [
                    'users' => $users
                ]
            ], 200);
        }

        public function get_banned_users(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            global $wpdb;
            $banned_users = $wpdb->get_results(
                "SELECT
                    `ID`,
                    `email`,
                    `ip`,
                    `note`,
                    `banned_for`,
                    `registrar`,
                    `creation_date`
                FROM
                    `{$wpdb->prefix}pvp_banned_users`"
            );

            if (is_null($banned_users)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Banned users retrieved successfully', 'pana-video-player'),
                'data' => [
                    'banned_users' => $banned_users
                ]
            ], 200);
        }

        public function unban_user(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            global $wpdb;
            $table = "{$wpdb->prefix}pvp_banned_users";
            $result = $wpdb->delete(
                $table,
                ['ID' => $request['id']]
            );

            if (!$result) {
                return new WP_Error(
                    'not-deleted',
                    __('Problem occurred on deleting user from banned user table', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $banned_users = $wpdb->get_results(
                "SELECT
                    `ID`,
                    `email`,
                    `ip`,
                    `note`,
                    `banned_for`,
                    `registrar`,
                    `creation_date`
                FROM
                    `{$wpdb->prefix}pvp_banned_users`"
            );

            if (is_null($banned_users)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('User successfully deleted from banned users table', 'pana-video-player'),
                'data' => [
                    'banned_users' => $banned_users
                ]
            ], 200);
        }

        public function ban_user(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            $params = json_decode($request->get_body());

            if (
                !isset($params->user_id) ||
                !isset($params->email) ||
                !isset($params->ip) ||
                !isset($params->note) ||
                !isset($params->banned_for)
            ) {
                return new WP_Error(
                    'bad-request',
                    __('Bad request!', 'pana-video-player'),
                    array('status' => 400)
                );
            }

            global $wpdb;
            $banned_users_table = "{$wpdb->prefix}pvp_banned_users";
            $result = $wpdb->insert(
                $banned_users_table,
                [
                    'user_id' => $params->user_id,
                    'email' => $params->email,
                    'ip' => $params->ip,
                    'note' => $params->note,
                    'banned_for' => $params->banned_for,
                    'registrar' => get_current_user_id(),
                    'creation_date' => current_time('mysql')
                ]
            );

            if (!$result) {
                return new WP_Error(
                    'not-inserted',
                    __('Problem occurred on inserting user to banned user table', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $users = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                    DISTINCT `comment_author_email` AS `user_email`,
                        `comment_author` AS `user_name`,
                        `comment_author_IP` AS `user_ip`,
                        `comment_date`,
                        `comment_content`,
                        `user_id`
                    FROM
                        `$wpdb->comments`
                    WHERE
                        `comment_type` = %s
                    AND `comment_author_email` NOT IN (SELECT `email` FROM `{$wpdb->prefix}pvp_banned_users`)
                    GROUP BY
                        `comment_author_email`",
                    'pvp_comment'
                )
            );

            if (is_null($users)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('User successfully inserted to banned user table', 'pana-video-player'),
                'data' => [
                    'banned_users' => $users
                ]
            ], 200);
        }

        public function get_emails(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            global $wpdb;
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `ID`, `email`, `registrar`, `creation_date` FROM `{$wpdb->prefix}pvp_user_emails` WHERE `player_id` = %d",
                    $request['id']
                )
            );

            if (is_null($results)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Email retrieved successfully', 'pana-video-player'),
                'data' => [
                    'emails' => $results
                ]
            ], 200);
        }

        public function delete_email(WP_REST_Request $request)
        {
            // if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            // {
            //     return new WP_Error(
            //         'unauthorized',
            //         __( 'Unauthorized!', 'pana-video-player' ),
            //         array( 'status' => 401 )
            //     );
            // }

            // if( ! current_user_can( 'edit_posts' ) )
            // {
            //     return new WP_Error(
            //         'not-access',
            //         __( 'Forbidden!', 'pana-video-player' ),
            //         array( 'status' => 403 )
            //     );
            // }
            global $wpdb;
            $table = "{$wpdb->prefix}pvp_user_emails";

            $player_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT `player_id` FROM `{$wpdb->prefix}pvp_user_emails` WHERE `ID` = %d",
                    $request['id']
                )
            );

            if (is_null($player_id)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $result = $wpdb->delete(
                $table,
                [
                    'ID' => $request['id']
                ]
            );

            if (!$result) {
                return new WP_Error(
                    'not-deleted',
                    __('Problem occurred on deleting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `ID`, `email`, `registrar`, `creation_date` FROM `{$wpdb->prefix}pvp_user_emails` WHERE `player_id` = %d",
                    $player_id
                )
            );

            if (is_null($results)) {
                return new WP_Error(
                    'getting-error',
                    __('Problem occurred on getting data from DB', 'pana-video-player'),
                    array('status' => 500)
                );
            }

            return new WP_REST_Response([
                'code' => 'success',
                'message' => __('Email deleted successfully', 'pana-video-player'),
                'data' => [
                    'emails' => $results
                ]
            ], 200);
        }

        public function set_html_mail_content_type()
        {
            return 'text/html';
        }

        public function log_mailer_errors($wp_error)
        {
            $fn = PVP_PATH . 'logs/mail.log';
            $fp = fopen($fn, 'a');
            fputs($fp, 'Mailer Error: ' . $wp_error->get_error_message() . '\n');
            fclose($fp);
        }

        private function get_videos($player_id)
        {
            return maybe_unserialize(get_option('pvp_' . $player_id . '_videos', []));
        }
    }
}
