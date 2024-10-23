<?php

namespace PanaVideoPlayer\Views\Admin;

defined( 'ABSPATH' ) || exit; // Prevent direct access

if ( ! is_admin() ) return; //make sure we are on the backend

global $wp_version;

$body_classes = [
	'pvp-dashboard',
	'wp-version-' . str_replace( '.', '-', $wp_version ),
];

if ( is_rtl() ) {
	$body_classes[] = 'rtl';
}

$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo esc_html__( 'Pana Video Player Dashboard', 'pana-video-player' ) . ' | ' . esc_html( get_bloginfo( 'title' ) ); ?></title>
	<?php wp_head(); ?>
    <script>
		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>';
	</script>
    <?php
    if( in_array( 'wordpress-seo/wp-seo.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
        do_action( 'wpseo_head' );
    ?>
</head>
<body class="<?php echo esc_attr( implode( ' ', $body_classes ) ); ?>">
<div id="pvp-root"></div>
<?php
	wp_footer();
	/** This action is documented in wp-admin/admin-footer.php */
	do_action( 'admin_print_footer_scripts' );
?>
</body>
</html>