<?php
/*
Plugin Name: VolleyTNT
Plugin URI: http://volleytnt.belinde.net
Description: Gestione di tornei di Beach Volley e Pallavolo
Author: Franco Traversaro
Version: 0.1
Author URI: mailto:f.traversaro@gmail.com
*/

define( 'VOLLEYTNT_PATH', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'volleytnt' );
define( 'VOLLEYTNT_URL', WP_PLUGIN_URL . '/volleytnt' );

global $wp_version;

if ( version_compare( $wp_version, '3.3-RC1', '>=' ) ) {
	require_once( VOLLEYTNT_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'the_plugin.php' );
} else {
	add_action( 'pre_current_active_plugins', create_function( '', 'echo \'<div class="error fade"><p><strong>Whoa! There is an error!</strong></p><p>VolleyTNT requires at least Wordpress 3.3 RC1. Please disable the plugin and upgrade your installation.</p></div>\';') );
}
?>
