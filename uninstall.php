<?php

// if uninstall.php is not called by WordPress, die
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) die;

/* <------------------------------- Drop tables -------------------------------> */
global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}pvp_statistics`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}pvp_user_activity`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}pvp_banned_users`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}pvp_notifications`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}pvp_user_emails`" );

/* <------------------------------- Delete plugin options -------------------------------> */

$videos = maybe_unserialize( get_option('pana_video_player_players', []) );
if( ! empty( $videos ) )
{
    foreach( $videos as $video )
    {
        delete_option( 'pvp_' . $video['id'] . '_options' );
        delete_option( 'pvp_' . $video['id'] . '_videos' );
        delete_option( 'pvp_' . $video['id'] . '_tagid' );
    }
}
delete_option('pana_video_player_players');
delete_option('pvp_plugin_settings');