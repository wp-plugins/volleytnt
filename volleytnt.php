<?php
/*
Plugin Name: Volley TNT
Plugin URI: http://volleytnt.belinde.net
Description: Management system for volley tournament
Author: Franco Traversaro
Version: 0.1
Author URI: mailto:f.traversaro@gmail.com
*/

define( 'VOLLEYTNT_PATH', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'volleytnt' );
define( 'VOLLEYTNT_URL', WP_PLUGIN_URL . '/volleytnt' );

class VolleyTNT {
	public $path = '';
	public $url = '';
	public $prefix = '';
	public $opts = array();
	private $pages = array();
	private $wp_tnt_pages = array();
	private $scripts = array();
	
	public $l_categorie = array();
	public $l_set_partita = array();
	public $l_finali = array();
	private $l_js = array();
	
	public function __construct() {
		global $wpdb;
		$this->path = VOLLEYTNT_PATH;
		$this->url = VOLLEYTNT_URL;
		$this->prefix = $wpdb->prefix . 'volleytnt_';
		
		require_once( $this->path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'functions.php' );
		require_once( $this->path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'table.php' );
		require_once( $this->path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'form.php' );
		require_once( $this->path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'adminpage.php' );

		add_action( 'init',									array( $this, 'init' ) );
		add_action( 'admin_menu',							array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts',				array( $this, 'admin_enqueue_scripts' ) );

		$this->register_page('VolleyTNT_Home');
		$this->register_page('VolleyTNT_Squadre');
		$this->register_page('VolleyTNT_Options');
		
	}
	
	public function init() {
		global $wpdb;
		load_plugin_textdomain('volleytnt', false , basename( $this->path ) . '/langs');
		
		$this->l_js['date_format'] = volleytnt_date_format( 'js' );
		
		$this->l_categorie = array( 	'M' => __("Maschile", 'volleytnt'),
										'F'	=> __("Femminile", 'volleytnt'),
										'X'	=> __("Misto", 'volleytnt') );
		$this->l_set_partita = array(	'3' => __("Alla meglio dei 3", 'volleytnt'),
										'5'	=> __("Alla meglio dei 5", 'volleytnt') );
		$this->l_finali = array(		'32'=> __("Trentaduesimi di finale", 'volleytnt'),
										'16'=> __("Sedicesimi di finale", 'volleytnt'),
										'8'	=> __("Ottavi di finale", 'volleytnt'),
										'4'	=> __("Quarti di finale", 'volleytnt'),
										'2'	=> __("Semifinali", 'volleytnt'),
										'1'	=> __("Finale", 'volleytnt') );

		wp_register_style( 'volleytnt_common', $this->url . '/style/common.css' );
		wp_register_style( 'volleytnt_jqueryui', $this->url . '/style/jqueryui_gray/jquery-ui-1.8.15.custom.css' );
		wp_register_style( 'volleytnt_admin', $this->url . '/style/admin.css', array( 'volleytnt_common', 'volleytnt_jqueryui' ) );
		
		$this->opts = (object) get_option( 'volleytnt_opts', array(	'nome'	=> 'Torneo',
																	'corrente'		=> 0 ) );
		$this->torneo = $wpdb->get_row("SELECT * FROM `{$this->prefix}tornei` WHERE `id`={$this->opts->corrente}");
		$this->torneo->categorie = explode( ',', $this->torneo->categorie );
		
		VolleyTNT_Form::manage_cbs_save();
		foreach ( $this->pages as $page ) $page->do_triggers();
	}

	public function admin_enqueue_scripts( $hook ) {
		if ( isset( $this->wp_tnt_pages[ $hook ] ) ) {
			wp_register_script( 'volleytnt', $this->url . '/js/common.js' );
			wp_enqueue_script( 'volleytnt' );
			wp_enqueue_style( 'volleytnt_admin' );
			$page = $this->pages[ $this->wp_tnt_pages[ $hook ] ];
			if ( $page->js_files ) foreach ( $page->js_files as $file ) {
				$file['dependencies'][] = 'volleytnt';
				wp_register_script( $file['file'], $this->url . '/js/' . $file['file'], $file['dependencies'] );
				wp_enqueue_script( $file['file'] );
			}
			if ( $page->js_strings ) wp_localize_script( 'volleytnt', 'volleytnt', array_merge( $this->l_js, $page->js_strings ) );
		}		
	}
	
	public function admin_menu() {
		global $wp_admin_bar;
		static $counter = 0;
		foreach ( $this->pages as $slug => $page ) {
			if ( $page->menu_child_of ) {
				$page->wp_slug = add_submenu_page( $page->menu_child_of, __('VolleyTNT', 'volleytnt') . ' | ' . $page->get_title(), $page->get_title(), 'edit_pages', $slug, array( $this, 'admin_page' ) );
			} else {
				$page->wp_slug = add_menu_page( $page->get_title(), $page->get_title(), 'edit_pages', $slug, array( $this, 'admin_page' ), $this->url . '/style/Volleyball_16x16.png', 4 );
			}
			$this->wp_tnt_pages[ $page->wp_slug ] = $slug;
			if ( $page->help_tabs ) {
				add_action( 'load-' . $page->wp_slug, array( $page, 'do_help_tabs' ) );
			}
			if ( $page->menubar_newlinks ) foreach ( $page->menubar_newlinks as $link ) {
				$wp_admin_bar->add_menu( array(
					'parent'    => 'new-content',
					'id' 		=> get_class( $this ) . '_' . ++$counter,
					'title' 	=> $link['label'],
					'href' 		=> $link['url']
				) );
			}
		}
	}
	
	private function register_page( $classname ) {
		require_once( $this->path . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . strtolower( $classname ) . '.php' );
		$this->pages[ $classname ] = new $classname();
	}
	
	public function admin_page() {
		global $current_screen;
		$page = $this->pages[ $this->wp_tnt_pages[ $current_screen->id ] ];
		echo '<div class="wrap">';
		screen_icon('volleytnt');
		echo '<h2>' . $page->get_title() . $page->get_title_action() . '</h2>';
		$page->do_page( $this );
		echo '</div>';
	}
	
}

global $VolleyTNT;
if ( !isset( $VolleyTNT ) or !( $VolleyTNT instanceof VolleyTNT ) ) $VolleyTNT = new VolleyTNT();
?>
