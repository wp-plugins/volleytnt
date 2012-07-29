<?php
/*
Plugin Name: VolleyTNT
Plugin URI: http://volleytnt.belinde.net
Description: Gestione di tornei di Beach Volley e Pallavolo
Author: Franco Traversaro
Version: 0.2
Author URI: mailto:f.traversaro@gmail.com
*/

define( 'VOLLEYTNT_PATH', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'volleytnt' );
define( 'VOLLEYTNT_URL', WP_PLUGIN_URL . '/volleytnt' );

global $wp_version;

if ( version_compare( $wp_version, '3.3', '>=' ) and version_compare( PHP_VERSION, '5.0', '>=' ) ) {
	require_once( VOLLEYTNT_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'the_plugin.php' );
} else {
	add_action( 'pre_current_active_plugins', create_function( '', 'echo \'<div class="error fade"><p><strong>Whoa! There is an error!</strong></p><p>VolleyTNT requires at least Wordpress 3.3 and PHP 5.0. Please disable VolleyTNT plugin and upgrade your installation.</p></div>\';') );
}

function volleytnt_activation() {
	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$prefix = $wpdb->prefix . 'volleytnt_';
	$sql = array(
	
"CREATE TABLE `{$prefix}atleti` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `cognome` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(100) NOT NULL,
  `mail` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8",

"CREATE TABLE `{$prefix}gironi` (
  `girone` TINYINT(3) UNSIGNED NOT NULL,
  `categoria` ENUM('M','F','X') NOT NULL DEFAULT 'M',
  `squadre_id` BIGINT(20) UNSIGNED NOT NULL,
  `tornei_id` BIGINT(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`categoria`,`squadre_id`,`tornei_id`)
) DEFAULT CHARSET=utf8",

"CREATE TABLE `{$prefix}impossibilita` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `squadre_id` BIGINT(20) UNSIGNED NOT NULL,
  `giorno` DATE NOT NULL,
  `primadopo` ENUM('prima','dopo') NOT NULL DEFAULT 'prima',
  `ora` TIME NOT NULL DEFAULT '20:00:00',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8",

"CREATE TABLE `{$prefix}partite` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `slots_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
  `categoria` ENUM('M','F','X') NOT NULL DEFAULT 'M',
  `girone` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `finale` CHAR(5) NOT NULL DEFAULT '',
  `squadra_1` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
  `squadra_2` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
  `set1_sq1` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `set1_sq2` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `set2_sq1` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `set2_sq2` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `set3_sq1` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `set3_sq2` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `set4_sq1` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `set4_sq2` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `set5_sq1` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `set5_sq2` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `tornei_id` BIGINT(20) UNSIGNED NOT NULL,
  `visibile` TINYINT(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8",

"CREATE TABLE `{$prefix}slots` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `giorno` DATE NOT NULL,
  `inizio` TIME NOT NULL,
  `fine` TIME NOT NULL,
  `tornei_id` BIGINT(20) UNSIGNED NOT NULL,
  `campo` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8",

"CREATE TABLE `{$prefix}squadre` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` VARCHAR(100) NOT NULL,
  `categoria` ENUM('M','F','X') NOT NULL DEFAULT 'M',
  `tornei_id` BIGINT(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8",

"CREATE TABLE `{$prefix}squadre_atleti` (
  `squadre_id` BIGINT(20) UNSIGNED NOT NULL,
  `atleti_id` BIGINT(20) UNSIGNED NOT NULL,
  `pagato` TINYINT(1) NOT NULL DEFAULT '0',
  `manleva` TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`squadre_id`,`atleti_id`)
) DEFAULT CHARSET=utf8",

"CREATE TABLE `{$prefix}tornei` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` VARCHAR(100) NOT NULL,
  `categorie` SET('M','F','X') NOT NULL DEFAULT 'M,F',
  `set_partita` ENUM('3','5') NOT NULL DEFAULT '3',
  `campi` TINYINT(3) UNSIGNED NOT NULL DEFAULT '2',
  `durata_turno` TINYINT(3) UNSIGNED NOT NULL DEFAULT '30',
  `finali_M` ENUM('0','1','2','4','8','16','32') NOT NULL DEFAULT '0',
  `finali_F` ENUM('0','1','2','4','8','16','32') NOT NULL DEFAULT '0',
  `finali_X` ENUM('0','1','2','4','8','16','32') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8"

	);
	
	foreach ( $sql as $s ) dbDelta( $s );
}
register_activation_hook( __FILE__, 'volleytnt_activation' );
?>
