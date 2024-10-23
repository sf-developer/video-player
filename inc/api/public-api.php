<?php

namespace PanaVideoPlayer\Inc\Api;

defined( 'ABSPATH' ) || exit; // Prevent direct access

use PanaVideoPlayer\Inc\VideoStream;
use ipinfo\ipinfo\IPinfo;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

if( ! class_exists( 'PVP_Public_Api' ) )
{
    class PVP_Public_Api
    {
        static $instance = null; // Singleton instance of the class

        private $basename = PLUGIN_NAME . '/public/'; // Base name of the REST API routes

        private $version = 'v1'; // API version

        /**
         * Get singleton instance of the class.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @return PVP_Public_Api Returns the PVP_Public_Api instance.
         */
        public static function instance()
        {
            if( is_null( self::$instance ) )
            {
                self::$instance = new self();
            }
        }

        /**
         * Constructor function that initializes the class.
         *
         * Registers the routes() method to the rest_api_init action
         * to load the REST API routes when the WordPress REST API
         * is initialized.
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
         * Registers the routes() method to the rest_api_init action
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
             * Registers a REST API route to get a player by ID.
             *
             * @param string $this->basename The base path for the REST API namespace.
             * @param string $this->version The version number for the REST API routes.
             * @param string /player/(?P<id>\d+) The REST API route with a dynamic player ID parameter.
             * @param array Methods, callback and permissions config for the REST API route.
             */
            register_rest_route( $this->basename . $this->version, '/player/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_player' ]
            ) );

            /**
             * Registers a REST API route to add a statistic for a player.
             *
             * @param string $this->basename The base path for the REST API namespace.
             * @param string $this->version The version number for the REST API routes.
             * @param string /statistic/player/(?P<id>\d+)/(?P<type>[a-zA-Z]+) The REST API route with dynamic player ID and statistic type parameters.
             * @param array Methods, callback and permissions config for the REST API route.
             */
            register_rest_route( $this->basename . $this->version, '/statistic/player/(?P<id>\d+)/(?P<type>[a-zA-Z]+)', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'add_statistic' ],
                'permission_callback' => function( WP_REST_Request $request )
                {
                    return ! empty( $request['id'] ) && is_numeric( $request['id'] ) && $request['id'] > 0 && ! empty( $request['type'] ) && in_array( $request['type'], [ 'view', 'like', 'dislike' ] );
                }
            ) );

            /**
             * Registers a REST API route to delete a statistic by ID for a player.
             *
             * @param string $this->basename The base path for the REST API namespace.
             * @param string $this->version The version number for the REST API routes.
             * @param string /statistic/player/(?P<id>\d+)/(?P<statistic_id>\d+) The REST API route with dynamic player ID and statistic ID parameters.
             * @param array Methods, callback and permissions config for the REST API route.
             */
            register_rest_route( $this->basename . $this->version, '/statistic/player/(?P<id>\d+)/(?P<statistic_id>\d+)', array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'delete_statistic' ],
                'permission_callback' => function( WP_REST_Request $request )
                {
                    return ! empty( $request['id'] ) && is_numeric( $request['id'] ) && $request['id'] > 0 && ! empty( $request['statistic_id'] ) && is_numeric( $request['statistic_id'] ) && $request['statistic_id'] > 0;
                }
            ) );

            /**
             * Registers a REST API route to get statistics for a player.
             *
             * @param string $this->basename The base path for the REST API namespace.
             * @param string $this->version The version number for the REST API routes.
             * @param string /statistics/player/(?P<id>\d+) The REST API route with a dynamic player ID parameter.
             * @param array Methods, callback and permissions config for the REST API route.
             */
            register_rest_route( $this->basename . $this->version, '/statistics/player/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_statistics' ],
                'permission_callback' => function( WP_REST_Request $request )
                {
                    return ! empty( $request['id'] ) && is_numeric( $request['id'] ) && $request['id'] > 0;
                }
            ) );

            /**
             * Registers a REST API route to check if the current user is logged in.
             *
             * @param string $this->basename The base path for the REST API namespace.
             * @param string $this->version The version number for the REST API routes.
             * @param string /is-logged-in The REST API route to check if user is logged in.
             * @param array Methods, callback config for the REST API route.
             */
            register_rest_route( $this->basename . $this->version, '/is-logged-in', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'is_user_logged_in' ]
            ) );

            /**
             * Registers a REST API route to check if the current user is banned.
             *
             * @param string $this->basename The base path for the REST API namespace.
             * @param string $this->version The version number for the REST API routes.
             * @param string /is-banned The REST API route to check if user is banned.
             * @param array Methods, callback config for the REST API route.
             */
            register_rest_route( $this->basename . $this->version, '/is-banned', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'is_user_banned' ]
            ) );

            /**
             * Registers a REST API route to add a comment for a player.
             *
             * @param string $this->basename The base path for the REST API namespace.
             * @param string $this->version The version number for the REST API routes.
             * @param string /comment/player/(?P<id>\d+) The REST API route with a dynamic player ID parameter.
             * @param array Methods, callback and permission callback config for the REST API route.
             */
            register_rest_route( $this->basename . $this->version, '/comment/player/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'add_comment' ],
                'permission_callback' => function( WP_REST_Request $request )
                {
                    return ! empty( $request['id'] ) && is_numeric( $request['id'] ) && $request['id'] > 0 && ! empty( $request->get_body() );
                }
            ) );

            /**
             * Registers a REST API route to get comments for a player.
             *
             * @param string $this->basename The base path for the REST API namespace.
             * @param string $this->version The version number for the REST API routes.
             * @param string /comments/player/(?P<id>\d+) The REST API route with a dynamic player ID parameter.
             * @param array Methods, callback, args config for the REST API route.
             */
            register_rest_route( $this->basename . $this->version, '/comments/player/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_comments' ],
                'args' => [
                    'limit' => [
                        'validate_callback' => function( $param, $request, $key )
                        {
                            return is_numeric( $param );
                        }
                    ],
                    'offset' => [
                        'validate_callback' => function( $param, $request, $key )
                        {
                            return is_numeric( $param );
                        }
                    ]
                ]
            ) );

            /**
             * Registers a REST API route to handle email form submission for a player.
             *
             * @param string $this->basename The base path for the REST API namespace.
             * @param string $this->version The version number for the REST API routes.
             * @param string /email-form/player/(?P<id>\d+) The route with dynamic player ID parameter.
             * @param array Route config with methods, callbacks and permission callback.
             */
            register_rest_route($this->basename . $this->version, '/email-form/player/(?P<id>\d+)', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'add_email_form'],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return !empty($request['id']) && is_numeric($request['id']) && $request['id'] > 0 && !empty($request->get_body());
                }
            ));
        }

        /**
         * Get video player by ID.
         *
         * Verifies nonce for authentication. Gets player options.
         * Get player videos.
         * Get player statistics.
         * Check if user is logged-in
         * If user is logged in, get their activity for this player.
         * Returns API response with status, message and data.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return WP_REST_Response|WP_Error The response object or error.
         */
        public function get_player( WP_REST_Request $request )
        {
            /**
             * Verifies nonce for authentication.
             *
             * Checks if the x_wp_nonce header matches 'wp_rest'.
             * If not, returns a WP_Error with unauthorized status.
             */
            if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            {
                return new WP_Error(
                    'unauthorized',
                    __( 'Unauthorized!', 'pana-video-player' ),
                    array( 'status' => 401 )
                );
            }

            // Get player ID and player options.
            $player_id = $request['id'];
            $options = maybe_unserialize( get_option( 'pvp_' . $player_id . '_options' ) );

            // Check if player exists.
            if( empty( $options ) )
            {
                return new WP_Error(
                    'not-found',
                    __( 'No options found', 'pana-video-player' ),
                    array( 'status' => 404 )
                );
            }

            $is_user_liked_video = $is_user_disliked_video = false; // Default values for user activity.

            // Default values for statistics.
            $statistics_data = [
                'view' => 0,
                'like' => 0,
                'dislike' => 0,
                'comment' => 0
            ];

            /* Getting and setting player data */

            $videos = $this->get_videos( $player_id ); // Get player videos.

            // return video lists by type.
            switch( $videos['videos']->type )
            {
                case 'html5':
                    $videos_list = $videos['videos'];
                    break;
                case 'youtube':
                    if( ! empty( $videos['videos']->videoLists ) )
                    {
                        foreach( $videos['videos']->videoLists as $key => $video_list )
                        {
                            $videos['videos']->videoLists[$key]->youtubeUrl = 'https://www.youtube.com/watch?v=' . $video_list->youtubeId;
                        }
                    }

                    $videos_list = $videos['videos'];
                    break;
                case 'vimeo':
                    if( ! empty( $videos['videos']->videoLists ) )
                    {
                        foreach( $videos['videos']->videoLists as $key => $video_list )
                        {
                            $videos['videos']->videoLists[$key]->vimeoUrl = 'https://vimeo.com/' . $video_list->vimeoId;
                        }
                    }
                    $videos_list = $videos['videos'];
                    break;
                case 'youtubeChannel':
                    $videos_list = [
                        'type' => 'youtubeChannel'
                    ];

                    $json = file_get_contents( 'https://googleapis.com/youtube/v3/search?order=date&maxResults=' . $videos['videos']->videoCount . '&part=snippet&channelId=' . $videos['videos']->youtubeChannelId . '&key=' . $videos['videos']->youtubeApiKey . '&part=snippet' );

                    if( ! is_object( $json ) )
                    {
                        return new WP_Error(
                            'bad-request',
                            __( 'Bad request!', PLUGIN_NAME ),
                            array( 'status' => 400 )
                        );
                    }

                    $youtube_data = json_decode( $json );
                    if( ! empty( $youtube_data ) )
                    {
                        for( $i = 0; $i <= $youtube_data->pageInfo->totalResults-3; $i++ )
                        {
                            $videos_list['videoLists'][$i]['title'] = $youtube_data->items[$i]->snippet->title;
                            $videos_list['videoLists'][$i]['caption'] = $youtube_data->items[$i]->snippet->channelTitle;
                            $videos_list['videoLists'][$i]['id'] = $youtube_data->items[$i]->id->videoId;
                            $videos_list['videoLists'][$i]['info'] = $youtube_data->items[$i]->snippet->description;
                            $videos_list['videoLists'][$i]['thumbnail'] = $youtube_data->items[$i]->snippet->thumbnails->default->url;
                        }
                    }
                    break;
                case 'youtubePlaylist':
                    $videos_list = $this->get_videos( $player_id )['videos']->custom;
                default:
                    $videos_list = [];
                    break;
            }

            // Player data.
            $player = [
                'id' => $player_id,
                'creation_date' => gmdate( 'Y-m-d H:i:s', $player_id ),
                'options' => $options,
                'videos' => $videos_list
            ];

            /* End getting and setting player data */

            /* Getting and setting player statistics */

            // Get player statistics from database.
            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS `count`, `type` FROM `{$wpdb->prefix}pvp_statistics` WHERE player_id = %d GROUP BY `type`",
                    $player_id
                )
            );

            // If statistics found, populate statistics data.
            if( ! empty( $statistics ) )
            {
                foreach( $statistics as $statistic )
                {
                    $statistics_data[$statistic->type] = $statistic->count;
                }
            }

            /* End getting and setting player statistics */

            /* Getting and setting user activity */

            $is_user_logged_in = is_user_logged_in(); // Check if user is logged-in.
            $current_user_id = get_current_user_id(); // Get current user ID.

            // If user is logged in, get their activity for this player.
            if( $is_user_logged_in )
            {
                $user_activity = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `type` FROM `{$wpdb->prefix}pvp_user_activity` WHERE `player_id` = %d AND `user_id` = %d",
                        $player_id,
                        $current_user_id
                    )
                );

                // If activity found, populate activity data.
                if( ! empty( $user_activity ) )
                {
                    $is_user_liked_video = $user_activity[0]->type === 'like' ? true : false;
                    $is_user_disliked_video = $user_activity[0]->type === 'dislike' ? true : false;
                }
            }

            /* End getting and setting user activity */

            // Return API response with status, message and data.
            return new WP_REST_Response( [
                'status' => 200,
                'message' => __( 'Players retrieved successfully', 'pana-video-player' ),
                'data' => [
                    'player' => $player,
                    'statistics' => $statistics_data,
                    'user' => [
                        'is_logged_in' => $is_user_logged_in,
                        'id' => $current_user_id
                    ],
                    'activity' => [
                        'like' => $is_user_liked_video,
                        'dislike' => $is_user_disliked_video
                    ],
                    'login_url' => wp_login_url( get_permalink() )
                ]
            ], 200 );
        }

        /**
         * Add player statitics by ID.
         *
         * Verifies nonce for authentication. Gets player options.
         * Get player videos.
         * Get player statistics.
         * Check if user is logged-in
         * If user is logged in, get their activity for this player.
         * Returns API response with status, message and data.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return WP_REST_Response|WP_Error The response object or error.
         */
        public function add_statistic( WP_REST_Request $request )
        {
            /**
             * Verifies nonce for authentication.
             *
             * Checks if the x_wp_nonce header matches 'wp_rest'.
             * If not, returns a WP_Error with unauthorized status.
             */
            if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            {
                return new WP_Error(
                    'unauthorized',
                    __( 'Unauthorized!', 'pana-video-player' ),
                    array( 'status' => 401 )
                );
            }

            // Define table names.
            global $wpdb;
            $statistics_table = "{$wpdb->prefix}pvp_statistics";
            $notifications_table = "{$wpdb->prefix}pvp_notifications";
            $user_activity_table = "{$wpdb->prefix}pvp_user_activity";

            // Get player ID, statictic type, agent data and player options.
            $player_id = $request['id'];
            $statistic_type = $request['type'];
            $agent_data = $this->get_agent_data();
            $options = maybe_unserialize( get_option( 'pvp_' . $player_id . '_options' ) );

            // Default statistics data.
            $statistics_data = [
                'view' => 0,
                'like' => 0,
                'dislike' => 0,
                'comment' => 0
            ];

            // Check if player exists.
            if( empty( $options ) )
            {
                return new WP_Error(
                    'not-found',
                    __( 'No player found with this ID', 'pana-video-player' ),
                    array( 'status' => 404 )
                );
            }

            // Insert statistics data into database.
            $result = $wpdb->insert(
                $statistics_table,
                [
                    'type' => $statistic_type,
                    'player_id' => $player_id,
                    'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
                    'ip' => $agent_data['ip'],
                    'country' => $agent_data['country'],
                    'country_code' => $agent_data['country_code'],
                    'state' => $agent_data['state'],
                    'city' => $agent_data['city'],
                    'zip' => $agent_data['zip'],
                    'lat' => $agent_data['lat'],
                    'lon' => $agent_data['lon'],
                    'device' => $agent_data['device'],
                    'os' => $agent_data['os'],
                    'browser' => $agent_data['browser'],
                    'creation_date' => current_time( 'mysql' )
                ]
            );

            // If insert failed, return error.
            if( ! $result )
            {
                return new WP_Error(
                    'not-insert',
                    __( 'Problem occurred on inserting statistic', 'pana-video-player' ),
                    array( 'status' => 500 )
                );
            }

            // Get statistics ID.
            $statistics_id = $wpdb->insert_id;

            // Insert notification data into database.
            if( $statistic_type !== 'view' )
            {
                $result = $wpdb->insert(
                    $notifications_table,
                    [
                        'statistic_id' => $statistics_id,
                        'video_id' => $player_id,
                        'type' => $statistic_type,
                        'status' => 'unread',
                        'registrar' => is_user_logged_in() ? get_current_user_id() : 0,
                        'creation_date' => current_time( 'mysql' )
                    ]
                );

                // If insert failed, return error.
                if( ! $result )
                {
                    return new WP_Error(
                        'not-insert',
                        __( 'Problem occurred on inserting notification', 'pana-video-player' ),
                        array( 'status' => 500 )
                    );
                }

                // Check if user is logged in and insert user activity.
                if( is_user_logged_in() && in_array( $statistic_type, [ 'like', 'dislike' ] ) )
                {
                    $result = $wpdb->insert(
                        $user_activity_table,
                        [
                            'type' => $statistic_type,
                            'player_id' => $player_id,
                            'user_id' => get_current_user_id(),
                            'creation_date' => current_time( 'mysql' )
                        ]
                    );

                    if( ! $result )
                    {
                        return new WP_Error(
                            'not-insert',
                            __( 'Problem occurred on inserting user activity', 'pana-video-player' ),
                            array( 'status' => 500 )
                        );
                    }
                }
            }

            // Get updated statistics data.
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS `count`, `type` FROM {$wpdb->prefix}pvp_statistics WHERE `player_id` = %d GROUP BY `type`",
                    $player_id
                )
            );

            // If statistics found, populate statistics data.
            if( ! empty( $statistics ) )
            {
                foreach( $statistics as $statistic )
                {
                    $statistics_data[$statistic->type] = $statistic->count;
                }
            }

            // Return API response with status, message and data.
            return new WP_REST_Response( [
                'status' => 200,
                'message' => __( 'Player statistics inserted successfully', 'pana-video-player' ),
                'data' => [
                    'statistics' => $statistics_data,
                    'statistic_id' => $statistics_id
                ]
            ], 200 );
        }

        /**
         * Deletes a statistic for a video player by ID.
         *
         * Verifies nonce for authentication. Gets player options.
         * Deletes statistic from DB. Deletes related notifications.
         * If user is logged in, deletes their activity for this player.
         * Gets updated statistics counts for player.
         * Returns API response with status, message and updated counts.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return WP_REST_Response|WP_Error The response object or error.
         */
        public function delete_statistic( WP_REST_Request $request )
        {
            /**
             * Verifies nonce for authentication.
             *
             * Checks if the x_wp_nonce header matches 'wp_rest'.
             * If not, returns a WP_Error with unauthorized status.
             */
            if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            {
                return new WP_Error(
                    'unauthorized',
                    __( 'Unauthorized!', 'pana-video-player' ),
                    array( 'status' => 401 )
                );
            }

            // Define table names.
            global $wpdb;
            $statistics_table = "{$wpdb->prefix}pvp_statistics";
            $notifications_table = "{$wpdb->prefix}pvp_notifications";
            $user_activity_table = "{$wpdb->prefix}pvp_user_activity";

            // Get player ID, statistic ID and player options.
            $player_id = $request['id'];
            $statistic_id = $request['statistic_id'];
            $options = maybe_unserialize( get_option( 'pvp_' . $player_id . '_options' ) );

            // Default statistics data.
            $statistics_data = [
                'view' => 0,
                'like' => 0,
                'dislike' => 0,
                'comment' => 0
            ];

            // Check if player exists.
            if( empty( $options ) )
            {
                return new WP_Error(
                    'not-found',
                    __( 'No player found with this ID', 'pana-video-player' ),
                    array( 'status' => 404 )
                );
            }

            // Delete statistic from DB.
            $result = $wpdb->delete(
                $statistics_table,
                [
                    'ID' => $statistic_id
                ]
            );

            // If delete failed, return error.
            if( ! $result )
            {
                return new WP_Error(
                    'not-deleted',
                    __( 'Problem occurred on deleting statistic', 'pana-video-player' ),
                    array( 'status' => 500 )
                );
            }

            // Delete related notifications.
            $result = $wpdb->delete(
                $notifications_table,
                [
                    'statistic_id' => $statistic_id
                ]
            );

            // If delete failed, return error.
            if( ! $result )
            {
                return new WP_Error(
                    'not-deleted',
                    __( 'Problem occurred on deleting notification', 'pana-video-player' ),
                    array( 'status' => 500 )
                );
            }

            // If user is logged in, delete their activity for this player.
            if( is_user_logged_in() )
            {
                $result = $wpdb->delete(
                    $user_activity_table,
                    [
                        'player_id' => $player_id,
                        'user_id' => get_current_user_id()
                    ]
                );

                if( ! $result )
                {
                    return new WP_Error(
                        'not-deleted',
                        __( 'Problem occurred on deleting user activity', 'pana-video-player' ),
                        array( 'status' => 500 )
                    );
                }
            }

            // Get updated statistics counts for player.
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS `count`, `type` FROM `{$wpdb->prefix}pvp_statistics` WHERE `player_id` = %d GROUP BY `type`",
                    $player_id
                )
            );

            // If statistics found, populate statistics data.
            if( ! empty( $statistics ) )
            {
                foreach( $statistics as $statistic )
                {
                    $statistics_data[$statistic->type] = $statistic->count;
                }
            }

            // Return API response with status, message and updated counts.
            return new WP_REST_Response( [
                'status' => 200,
                'message' => __( 'Player statistics deleted successfully', 'pana-video-player' ),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200 );
        }

        /**
         * Retrieves statistics for a video player.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return WP_REST_Response|WP_Error The response object or error.
         */
        public function get_statistics( WP_REST_Request $request )
        {
            /**
             * Verifies nonce for authentication.
             *
             * Checks if the x_wp_nonce header matches 'wp_rest'.
             * If not, returns a WP_Error with unauthorized status.
             */
            if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            {
                return new WP_Error(
                    'unauthorized',
                    __( 'Unauthorized!', 'pana-video-player' ),
                    array( 'status' => 401 )
                );
            }

            // Get player ID and player options.
            $player_id = $request['id'];
            $options = maybe_unserialize( get_option( 'pvp_' . $player_id . '_options' ) );

            // Default statistics data.
            $statistics_data = [
                'view' => 0,
                'like' => 0,
                'dislike' => 0,
                'comment' => 0
            ];

            // Check if player exists.
            if( empty( $options ) )
            {
                return new WP_Error(
                    'not-found',
                    __( 'No player found with this ID', 'pana-video-player' ),
                    array( 'status' => 404 )
                );
            }

            // Get statistics for player.
            global $wpdb;
            $statistics = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(`ID`) AS `count`, `type` FROM `{$wpdb->prefix}pvp_statistics` WHERE player_id = %d GROUP BY `type`",
                    $player_id
                )
            );

            // If statistics found, populate statistics data.
            if( ! empty( $statistics ) )
            {
                foreach( $statistics as $statistic )
                {
                    $statistics_data[$statistic->type] = $statistic->count;
                }
            }

            // Return API response with status, message and updated counts.
            return new WP_REST_Response( [
                'code' => 'success',
                'message' => __('Player statistics retrieved successfully', 'pana-video-player'),
                'data' => [
                    'statistics' => $statistics_data
                ]
            ], 200 );
        }

        /**
         * Check if the current user is logged in and return user data.
         *
         * Verifies the nonce for security. Checks if the user is logged in.
         * Gets the current logged in user data and returns it in the response.
         * Checks if the user is banned.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return WP_REST_Response|WP_Error The response with user data or error.
         */
        public function is_user_logged_in( WP_REST_Request $request )
        {
            /**
             * Verifies nonce for authentication.
             *
             * Checks if the x_wp_nonce header matches 'wp_rest'.
             * If not, returns a WP_Error with unauthorized status.
             */
            if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            {
                return new WP_Error(
                    'unauthorized',
                    __( 'Unauthorized!', 'pana-video-player' ),
                    array( 'status' => 401 )
                );
            }

            // Check if user is logged in.
            if( ! is_user_logged_in() )
            {
                return new WP_REST_Response( [
                    'code' => 'not-logged-in',
                    'message' => __( 'User is logged in', 'pana-video-player' )
                ], 213 );
            }

            // Get current user data.
            $current_user = wp_get_current_user();

            // Return API response with status, message and data.
            return new WP_REST_Response( [
                'code' => 'success',
                'message' => __( 'User is logged in', 'pana-video-player' ),
                'data' => [
                    'user_id' => $current_user->ID,
                    'name' => $current_user->display_name,
                    'email' => $current_user->user_email,
                    'role' => $current_user->roles[0],
                    'avatar' => get_avatar_url( $current_user->ID, [ 'default' => 'gravatar_default' ] ),
                    'is_banned' => $this->check_banned_user( $current_user->ID )
                ]
            ], 200 );
        }

        /**
         * Check if a user is banned.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return WP_REST_Response|WP_Error The response if user is not banned, WP_Error if banned.
         */
        public function is_user_banned( WP_REST_Request $request )
        {
            /**
             * Verifies nonce for authentication.
             *
             * Checks if the x_wp_nonce header matches 'wp_rest'.
             * If not, returns a WP_Error with unauthorized status.
             */
            if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            {
                return new WP_Error(
                    'unauthorized',
                    __( 'Unauthorized!', 'pana-video-player' ),
                    array( 'status' => 401 )
                );
            }

            // Define table name.
            global $wpdb;

            // Get identifier and user IP.
            $identifier = $request->get_param('identifier');
            $ip = $_SERVER['REMOTE_ADDR'];

            // Check if identifier is email or user ID.
            if( is_email( $identifier ) )
            {
                $banned_user = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `ID`, `note`, `banned_for` FROM {$wpdb->prefix}pvp_banned_users WHERE `email` = %s OR `ip` = %s",
                        $identifier,
                        $ip
                    )
                );
            } else if( is_numeric( $identifier ) )
            {
                $banned_user = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `ID`, `note`, `banned_for` FROM {$wpdb->prefix}pvp_banned_users WHERE `user_id` = %d OR `ip` = %s",
                        $identifier,
                        $ip
                    )
                );
            } else {
                return new WP_Error(
                    'not-found',
                    __( 'No user found with this identifier', 'pana-video-player' ),
                    array( 'status' => 404 )
                );
            }

            // If banned user found, return API response with status, message and data.
            if( empty( $banned_user ) )
            {
                return new WP_REST_Response( [
                    'status' => 200,
                    'message' => __( 'Not banned', 'pana-video-player' )
                ], 200 );
            }

            // Return API response with status, message and data.
            return new WP_Error(
                'is-banned',
                sprintf(
                    __( 'User with this identifer is banned!%s%s', 'pana-video-player' ),
                    !empty( $banned_user[0]->note ) ? __( ' Reason: ', 'pana-video-player' ) . $banned_user[0]->note : '',
                    !empty( $banned_user[0]->banned_for ) ? ' | ' . __( 'Banned for ', 'pana-video-player' ) . $banned_user[0]->banned_for : ''
                ),
                array( 'status' => 500 )
            );
        }

        /**
         * Insert comment for a video player.
         *
         * Verifies nonce for authentication. Gets player ID from request.
         * Gets player options from database. Returns error if no player found.
         * Insert comment into database and return API response with status, message and data.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return WP_REST_Response|WP_Error The response if comment is added, WP_Error if not.
         */
        public function add_comment( WP_REST_Request $request )
        {
            /**
             * Verifies nonce for authentication.
             *
             * Checks if the x_wp_nonce header matches 'wp_rest'.
             * If not, returns a WP_Error with unauthorized status.
             */
            if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            {
                return new WP_Error(
                    'unauthorized',
                    __( 'Unauthorized!', 'pana-video-player' ),
                    array( 'status' => 401 )
                );
            }

            // Get player ID, body from request and player options from database.
            $player_id = $request['id'];
            $params = json_decode( $request->get_body() );
            $options = maybe_unserialize( get_option( 'pvp_' . $player_id . '_options' ) );

            // Check if player exists.
            if( empty( $options ) )
            {
                return new WP_Error(
                    'not-found',
                    __( 'No player found with this ID', 'pana-video-player' ),
                    array( 'status' => 404 )
                );
            }

            // Check if comment and author are set.
            if( ! isset( $params->comment ) ||
                ! isset( $params->author ) )
            {
                return new WP_Error(
                    'bad-request',
                    __( 'Bad request!', 'pana-video-player' ),
                    array( 'status' => 400 )
                );
            }

            // Get comment, comment author and agent data.
            $comment = $params->comment;
            $comment_author = $params->author;
            $agent_data = $this->get_agent_data();

            // Get comment author agent.
            $comment_author_agent = $_SERVER['HTTP_USER_AGENT'];

            // Check if comment options is set.
            if( isset( $options['comments'] ) )
            {
                // Check if comments are closed.
                if( $options['comments']->isClosed )
                {
                    return new WP_Error(
                        'comments-closed',
                        __( 'Comments are closed!', 'pana-video-player' ),
                        array( 'status' => 404 )
                    );
                }

                // Check who can submit comments. If only logged in user can submit comment and user not logged in, return error.
                if( $options['comments']->whoCanSubmit === 'loggedin' && ! is_user_logged_in() )
                {
                    return new WP_Error(
                        'not-logged-in',
                        __( 'User not logged in', 'pana-video-player' ),
                        array( 'status' => 213 )
                    );
                }

                // Check who can submit comments. If all users can submit comment and user not logged in and comment author is not object, return error.
                if( $options['comments']->whoCanSubmit == 'all' && ! is_user_logged_in() && ! is_object( $comment_author ) )
                {
                    return new WP_Error(
                        'bad-request',
                        __( 'Bad request!', 'pana-video-player' ),
                        array( 'status' => 400 )
                    );
                }

                // If all users can submit comment and user not logged in, get comment author data else get current user data.
                if( $options['comments']->whoCanSubmit === 'all' && ! is_user_logged_in() )
                {
                    $comment_author_name = $comment_author->name;
                    $comment_author_email = $comment_author->email;
                } else {
                    $current_user = wp_get_current_user();
                    $comment_author_name = $current_user->display_name;
                    $comment_author_email = $current_user->user_email;
                }

                // Insert comment.
                $comment_id = wp_insert_comment(
                    [
                        'comment_post_ID' => 0,
                        'comment_author' => $comment_author_name,
                        'comment_author_email' => $comment_author_email,
                        'comment_content' => $comment,
                        'comment_type' => 'pvp_comment',
                        'comment_parent' => 0,
                        'comment_date' => current_time('mysql' ),
                        'comment_date_gmt' => current_time('mysql', 1 ),
                        'comment_karma' => 0,
                        'comment_approved' => $options['comments']->immediatelyApprove ? 1 : 0,
                        'comment_agent' => $comment_author_agent,
                        'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
                        'comment_author_IP' => $agent_data['ip'],
                        'comment_author_url' => ''
                    ]
                );

                // Check if comment is inserted.
                if( ! $comment_id )
                {
                    return new WP_Error(
                        'not-insert',
                        __( 'Problem occurred on inserting comment', 'pana-video-player' ),
                        array( 'status' => 500 )
                    );
                }

                // Insert comment meta.
                update_comment_meta( $comment_id, 'pvp_player_id', $player_id );
                update_comment_meta( $comment_id, 'pvp_pending', $player_id );

                // Insert comment statistics.
                global $wpdb;
                $statistics_table = "{$wpdb->prefix}pvp_statistics";
                $notifications_table = "{$wpdb->prefix}pvp_notifications";
                $result = $wpdb->insert(
                    $statistics_table,
                    [
                        'type' => 'comment',
                        'player_id' => $player_id,
                        'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
                        'ip' => $agent_data['ip'],
                        'country' => $agent_data['country'],
                        'country_code' => $agent_data['country_code'],
                        'state' => $agent_data['state'],
                        'city' => $agent_data['city'],
                        'zip' => $agent_data['zip'],
                        'lat' => $agent_data['lat'],
                        'lon' => $agent_data['lon'],
                        'device' => $agent_data['device'],
                        'os' => $agent_data['os'],
                        'browser' => $agent_data['browser'],
                        'creation_date' => current_time( 'mysql' )
                    ]
                );

                // Check if comment statistic is inserted.
                if( ! $result )
                {
                    return new WP_Error(
                        'not-insert',
                        __( 'Problem occurred on inserting statistic', 'pana-video-player' ),
                        array( 'status' => 500 )
                    );
                }

                // Insert notification.
                $result = $wpdb->insert(
                    $notifications_table,
                    [
                        'video_id' => $player_id,
                        'type' => 'comment',
                        'status' => 'unread',
                        'registrar' => is_user_logged_in() ? get_current_user_id() : 0,
                        'creation_date' => current_time( 'mysql' )
                    ]
                );

                // Check if comment notification is inserted.
                if( ! $result )
                {
                    return new WP_Error(
                        'not-insert',
                        __( 'Problem occurred on inserting notification', 'pana-video-player' ),
                        array( 'status' => 500 )
                    );
                }

                // Return response.
                return new WP_REST_Response( [
                    'status' => 200,
                    'message' => __( 'Comment inserted successfully', 'pana-video-player' )
                ], 200 );
            }
        }

        /**
         * Retrieves comments for a video player.
         *
         * Verifies nonce for authentication. Gets player ID, limit, and offset from request.
         * Gets player options from database. Returns error if no player found.
         * Gets approved comments of type 'pvp_comment' with pagination.
         * Adds avatar URL to each comment.
         * Returns paginated comments in a REST response.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @param WP_REST_Request $request
         *
         * @return WP_REST_Response|WP_Error The response object on success, or WP_Error object on failure.
         */
        public function get_comments( WP_REST_Request $request )
        {
            /**
             * Verifies nonce for authentication.
             *
             * Checks if the x_wp_nonce header matches 'wp_rest'.
             * If not, returns a WP_Error with unauthorized status.
             */
            if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            {
                return new WP_Error(
                    'unauthorized',
                    __( 'Unauthorized!', 'pana-video-player' ),
                    array( 'status' => 401 )
                );
            }

            // Get player ID, limit, offset from request and player options from database.
            $player_id = $request['id'];
            $limit = isset( $_GET['limit'] ) ? $_GET['limit'] : 10;
            $offset = isset( $_GET['offset'] ) ? $_GET['offset'] : 0;
            $options = maybe_unserialize( get_option( 'pvp_' . $player_id . '_options' ) );

            // Check if player exists.
            if( empty( $options ) )
            {
                return new WP_Error(
                    'not-found',
                    __( 'No player found with this ID', 'pana-video-player' ),
                    array( 'status' => 404 )
                );
            }

            // Get approved comments of type 'pvp_comment' with pagination.
            $comments = get_comments( [
                'post_id' => 0,
                'number' => $limit,
                'offset' => $offset,
                'status'  => 'approve',
                'type'    => 'pvp_comment',
                'meta_key' => 'pvp_player_id',
                'meta_value' => $player_id
            ] );

            // Add avatar URL to each comment.
            if( ! empty( $comments ) )
            {
                foreach( $comments as $key => $comment )
                {
                    if( $comment->user_id == 0 )
                    {
                        $avatar = PVP_URL . 'assets/images/avatar.png';
                    } else {
                        $avatar = get_avatar_url( $comment->user_id, [ 'default' => 'gravatar_default' ] );
                    }

                    $comments[$key]->avatar = $avatar;
                }
            }

            // Return paginated comments in a REST response.
            return new WP_REST_Response( [
                'status' => 200,
                'message' => __( 'Comment retrieved successfully', 'pana-video-player' ),
                'data' => [
                    'comments' => $comments,
                    'count' => count( $comments )
                ]
            ], 200 );
        }

        /**
         * Handles adding email form submission for a video player.
         *
         * Verifies nonce, gets player ID, retrieves options, validates email,
         * checks if email form enabled, saves email or sends email based on formAction.
         * Returns WP_Error on error, WP_REST_Response on success.
         *
         * @since 1.0.0
         *
         * @access public
         *
         * @param WP_REST_Request $request
         *
         * @return WP_REST_Response|WP_Error The response object on success, or WP_Error object on failure.
         */
        public function add_email_form( WP_REST_Request $request )
        {
            /**
             * Verifies nonce for authentication.
             *
             * Checks if the x_wp_nonce header matches 'wp_rest'.
             * If not, returns a WP_Error with unauthorized status.
             */
            if( ! wp_verify_nonce( $request->get_header( 'x_wp_nonce' ), 'wp_rest' ) )
            {
                return new WP_Error(
                    'unauthorized',
                    __( 'Unauthorized!', 'pana-video-player' ),
                    array( 'status' => 401 )
                );
            }

            // Get player ID, body from request and player options from database.
            $player_id = $request['id'];
            $params = json_decode( $request->get_body() );
            $options = maybe_unserialize( get_option( 'pvp_' . $player_id . '_options' ) );

            // Check if player exists.
            if( empty( $options ) )
            {
                return new WP_Error(
                    'not-found',
                    __( 'No player found with this ID', 'pana-video-player' ),
                    array( 'status' => 404 )
                );
            }

            // Check if email is set in request body.
            if( ! isset( $params->email ) )
            {
                return new WP_Error(
                    'bad-request',
                    __( 'Bad request!', 'pana-video-player' ),
                    array( 'status' => 400 )
                );
            }

            $email = $params->email;

            // Check if email form is set in player options.
            if( isset( $options['emailForm'] ) )
            {
                // Check if email form is enabled.
                if( ! $options['emailForm']->show )
                {
                    return new WP_Error(
                        'email-form-closed',
                        __( 'Email form is closed!', 'pana-video-player' ),
                        array( 'status' => 404 )
                    );
                }

                // If email form is set to save email, save email to database.
                if( $options['emailForm']->formAction === 'saveEmail' )
                {
                    global $wpdb;
                    $table = $wpdb->prefix . "pvp_user_emails";
                    $result = $wpdb->insert(
                        $table,
                        [
                            'player_id' => $player_id,
                            'email' => $email,
                            'registrar' => get_current_user_id(),
                            'creation_date' => current_time( 'mysql' )
                        ]
                    );

                    // Check if email was inserted to database.
                    if( ! $result )
                    {
                        return new WP_Error(
                            'not-inserted',
                            __( 'Problem occurred on inserting email to db', 'pana-video-player' ),
                            array( 'status' => 500 )
                        );
                    }
                } else {
                    // If email form is set to send email, send email.
                    $result = wp_mail( $options['emailForm']->emailTo, sprintf( __('New email from video id %d', 'pana-video-player' ), $player_id ), $options['emailForm']->emailContent, ['Content-Type: text/html; charset=UTF-8'] );

                    // Check if email was sent.
                    if( ! $result )
                    {
                        return new WP_Error(
                            'email-not-send',
                            sprintf( __( 'Problem occurred on sending email to `%s`', 'pana-video-player' ), $options['emailForm']->emailTo ),
                            array( 'status' => 500 )
                        );
                    }
                }

                // Return success message.
                return new WP_REST_Response( [
                    'status' => 200,
                    'message' => __( 'Email form submitted successfully', 'pana-video-player' )
                ], 200 );
            }
        }

        /**
         * Retrieves the videos associated with the given player ID.
         *
         * @since 1.0.0
         *
         * @access private
         *
         * @param int $player_id The ID of the player to get videos for.
         *
         * @return array The array of video data associated with the player.
         */
        private function get_videos( $player_id )
        {
            return maybe_unserialize( get_option( 'pvp_' . $player_id . '_videos' ) );
        }

        /**
         * Get country, state, city, zip, lat, lon from IPinfo API.
         *
         * @since 1.0.0
         *
         * @access private
         *
         * @return array The array of agent data.
         */
        private function get_agent_data()
        {
            // Default values.
            $country = $country_code = $state = $city = $zip = $lat = $lon = null;

            // Get IP, user agent and access token from server.
            $ip = $_SERVER['REMOTE_ADDR'];
            $agent = $_SERVER['HTTP_USER_AGENT'];
            $plugin_settings = get_option( 'pvp_plugin_settings' );
            $access_token = ! empty( get_option( 'pvp_plugin_settings' ) ) && isset( $plugin_settings['api_key'] ) ? $plugin_settings['api_key'] : '';

            // Check if IPinfo API is enabled.
            if( ! empty( $access_token ) )
            {
                include_once( PVP_PATH . 'vendor/autoload.php' );

                $client = new IPinfo( $access_token );
                $details = $client->getDetails( $ip );

                if( is_object( $details ) )
                {
                    $country = $details->country_name;
                    $country_code = $details->country;
                    $state = $details->region;
                    $city = $details->city;
                    $zip = $details->postal;
                    $lat = $details->latitude;
                    $lon = $details->longitude;
                }
            }

            // Detect Device/Operating System
            $os = __( 'Unknown', 'pana-video-player' );
        	$os_array =   array(
        		'/windows nt 10/i'      =>  __( 'Windows 10', 'pana-video-player' ),
        		'/windows nt 6.3/i'     =>  __( 'Windows 8.1', 'pana-video-player' ),
        		'/windows nt 6.2/i'     =>  __( 'Windows 8', 'pana-video-player' ),
        		'/windows nt 6.1/i'     =>  __( 'Windows 7', 'pana-video-player' ),
        		'/windows nt 6.0/i'     =>  __( 'Windows Vista', 'pana-video-player' ),
        		'/windows nt 5.2/i'     =>  __( 'Windows Server 2003/XP x64', 'pana-video-player' ),
        		'/windows nt 5.1/i'     =>  __( 'Windows XP', 'pana-video-player' ),
        		'/windows xp/i'         =>  __( 'Windows XP', 'pana-video-player' ),
        		'/windows nt 5.0/i'     =>  __( 'Windows 2000', 'pana-video-player' ),
        		'/windows me/i'         =>  __( 'Windows ME', 'pana-video-player' ),
        		'/win98/i'              =>  __( 'Windows 98', 'pana-video-player' ),
        		'/win95/i'              =>  __( 'Windows 95', 'pana-video-player' ),
        		'/win16/i'              =>  __( 'Windows 3.11', 'pana-video-player' ),
        		'/macintosh|mac os x/i' =>  __( 'Mac OS X', 'pana-video-player' ),
        		'/mac_powerpc/i'        =>  __( 'Mac OS 9', 'pana-video-player' ),
        		'/linux/i'              =>  __( 'Linux', 'pana-video-player' ),
        		'/ubuntu/i'             =>  __( 'Ubuntu', 'pana-video-player' ),
        		'/iphone/i'             =>  __( 'iPhone', 'pana-video-player' ),
        		'/ipod/i'               =>  __( 'iPod', 'pana-video-player' ),
        		'/ipad/i'               =>  __( 'iPad', 'pana-video-player' ),
        		'/android/i'            =>  __( 'Android', 'pana-video-player' ),
        		'/blackberry/i'         =>  __( 'BlackBerry', 'pana-video-player' ),
        		'/webos/i'              =>  __( 'Mobile', 'pana-video-player' )
        	);

        	foreach( $os_array as $regex => $value )
            {
        		if( preg_match( $regex, $agent ) )
                {
        			$os = $value;
        		}
        	}

            // Detect Browser
            $browser = __( 'Unknown', 'pana-video-player' );
        	$browser_array  = array(
        		'/msie/i'       =>  __( 'Internet Explorer', 'pana-video-player' ),
        		'/firefox/i'    =>  __( 'Firefox', 'pana-video-player' ),
        		'/safari/i'     =>  __( 'Safari', 'pana-video-player' ),
        		'/chrome/i'     =>  __( 'Chrome', 'pana-video-player' ),
        		'/edge/i'       =>  __( 'Edge', 'pana-video-player' ),
        		'/opera/i'      =>  __( 'Opera', 'pana-video-player' ),
        		'/netscape/i'   =>  __( 'Netscape', 'pana-video-player' ),
        		'/maxthon/i'    =>  __( 'Maxthon', 'pana-video-player' ),
        		'/konqueror/i'  =>  __( 'Konqueror', 'pana-video-player' ),
        		'/mobile/i'     =>  __( 'Handheld Browser', 'pana-video-player' )
        	);

        	foreach( $browser_array as $regex => $value )
            {
        		if( preg_match( $regex, $agent ) )
                {
        			$browser = $value;
        		}
        	}

            // Detect Device
            switch( $agent )
            {
                case preg_match( '/Mobile/i',$agent ):
                    $device = __( 'Mobile', 'pana-video-player' );
                    break;
                case preg_match( '/Tablet/i',$agent ):
                    $device = __( 'Tablet', 'pana-video-player' );
                    break;
                default:
                    $device = __( 'PC', 'pana-video-player' );
            }

            // Return agent data.
            return [
                'ip' => $ip,
                'country' => $country,
                'country_code' => $country_code,
                'state' => $state,
                'city' => $city,
                'zip' => $zip,
                'lat' => $lat,
                'lon' => $lon,
                'os' => $os,
                'browser' => $browser,
                'device' => $device
            ];
        }

        /**
         * Checks if a user is banned.
         *
         * Queries the pvp_banned_users table to see if the provided user ID
         * exists in the banned users list.
         *
         * @since 1.0.0
         *
         * @access private
         *
         * @param int $user_id The user ID to check.
         *
         * @return bool True if the user is banned, false otherwise.
         */
        private function check_banned_user($user_id)
        {
            global $wpdb;

            // Get the banned user data.
            $banned_user = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `ID`, `note`, `banned_for` FROM `{$wpdb->prefix}pvp_banned_users` WHERE `user_id` = %d",
                    $user_id
                )
            );

            return empty( $banned_user ) ? false : true;
        }
    }
}