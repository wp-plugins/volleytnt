<?php

class VolleyTNT {
	public $path = '';
	public $url = '';
	public $prefix = '';
	public $opts = false;
	public $torneo = false;
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
		
		add_filter( 'plugin_row_meta',						array( $this, 'plugin_row_meta' ), 10, 4 );

		$this->register_page('VolleyTNT_Home');
		$this->register_page('VolleyTNT_Squadre');
		$this->register_page('VolleyTNT_Gironi');
		$this->register_page('VolleyTNT_Partite');
		$this->register_page('VolleyTNT_Risultati');
		$this->register_page('VolleyTNT_Opzioni');
		
				
		add_shortcode( 'volleytnt_classifiche_gironi', array( $this, 'sc_classifiche_gironi' ) );
		add_shortcode( 'volleytnt_risultati', array( $this, 'sc_risultati' ) );
		add_shortcode( 'volleytnt_calendario', array( $this, 'sc_calendario' ) );
		add_shortcode( 'volleytnt_squadre', array( $this, 'sc_squadre' ) );
		add_shortcode( 'volleytnt_finali', array( $this, 'sc_finali' ) );
		
	}
	
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( $plugin_file === plugin_basename( VOLLEYTNT_PATH . '/volleytnt.php' ) ) {
			$plugin_meta[] = '<a href="' . add_query_arg( 'page', 'VolleyTNT_Opzioni', admin_url('admin.php') ) .  '">' . __("Impostazioni", 'volleytnt') . '</a>';
		}
		return $plugin_meta;
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
		
		$this->opts = (object) get_option( 'volleytnt_opts', array(	'nome'			=> 'Torneo',
																	'corrente'		=> 0 ) );
		$this->torneo = $wpdb->get_row("SELECT * FROM `{$this->prefix}tornei` WHERE `id`={$this->opts->corrente}");
		$this->torneo->categorie = explode( ',', $this->torneo->categorie );
		$this->torneo->set_partita = absint( $this->torneo->set_partita );
		$this->torneo->campi = absint( $this->torneo->campi );
		$this->torneo->durata_turno = absint( $this->torneo->durata_turno );
		$this->torneo->finali = absint( $this->torneo->finali );
		
		VolleyTNT_Form::manage_cbs_save();
		foreach ( $this->pages as $page ) $page->do_triggers();
	}
	
	
	private function get_partite( $id_torneo ) {
		global $wpdb;
		
		$dati = $wpdb->get_results("	SELECT
										  `par`.`id`,
										  `par`.`girone`,
										  `par`.`categoria`,
										  `par`.`slots_id`,
										  `par`.`squadra_1`,
										  `par`.`squadra_2`,
										  `sq1`.`label`     AS `label_1`,
										  `sq2`.`label`     AS `label_2`,
										  GROUP_CONCAT(`imp`.`id`) AS `imps`
										FROM `{$this->prefix}partite` AS `par`
										  LEFT JOIN `{$this->prefix}squadre` AS `sq1`
										    ON `sq1`.`id` = `par`.`squadra_1`
										  LEFT JOIN `{$this->prefix}squadre` AS `sq2`
										    ON `sq2`.`id` = `par`.`squadra_2`
										  LEFT JOIN `{$this->prefix}impossibilita` AS `imp`
										    ON `imp`.`squadre_id` = `sq1`.`id`
										       OR `imp`.`squadre_id` = `sq2`.`id`
										WHERE `par`.`tornei_id` = $id_torneo
										  AND `par`.`girone` <> 0
										GROUP BY `par`.`id`");
		$_ = array();
		if ( $dati ) foreach ( $dati as $row ) $_[ $row->categoria ][ $row->girone ][ $row->id ] = $row;
		return $_;
	}
	
	private function get_finali( $id_torneo ) {
		global $wpdb;
		
		$dati = $wpdb->get_results("	SELECT
										  `par`.*,
										  `sq1`.`label`     AS `label_1`,
										  `sq2`.`label`     AS `label_2`,
										  GROUP_CONCAT(`imp`.`id`) AS `imps`
										FROM `{$this->prefix}partite` AS `par`
										  LEFT JOIN `{$this->prefix}squadre` AS `sq1`
										    ON `sq1`.`id` = `par`.`squadra_1`
										  LEFT JOIN `{$this->prefix}squadre` AS `sq2`
										    ON `sq2`.`id` = `par`.`squadra_2`
										  LEFT JOIN `{$this->prefix}impossibilita` AS `imp`
										    ON `imp`.`squadre_id` = `sq1`.`id`
										       OR `imp`.`squadre_id` = `sq2`.`id`
										WHERE `par`.`tornei_id` = $id_torneo
										  AND `par`.`girone` = 0
										  AND `par`.`visibile` = 1
										GROUP BY `par`.`id`");
		$_ = array();
		if ( $dati ) foreach ( $dati as $row ) $_[ $row->categoria ][ $row->finale ][ $row->id ] = $row;
		return $_;
	}
	
	private function get_slots( $id_torneo ) {
		global $wpdb;
		
		$dati = $wpdb->get_results("	SELECT
											`id`,
											DATE_FORMAT(`giorno`, '" . volleytnt_date_format('sql') . "') AS giorno,
											DATE_FORMAT(`inizio`, '%k:%i') AS inizio,
											DATE_FORMAT(`fine`, '%k:%i') AS fine,
											UNIX_TIMESTAMP( CONCAT( `giorno`, ' ', `inizio` ) ) + IF( DATE_FORMAT(`inizio`,'%k')<6, 24*60*60, 0) AS ts_i,
											UNIX_TIMESTAMP( CONCAT( `giorno`, ' ', `fine` ) ) + IF( DATE_FORMAT(`fine`,'%k')<6, 24*60*60, 0) AS ts_f,
											`campo`,
											60*(IF( DATE_FORMAT(`inizio`,'%k')<6, DATE_FORMAT(`inizio`,'%k')+24, DATE_FORMAT(`inizio`,'%k')))+DATE_FORMAT(`inizio`,'%i') AS ordo
										FROM 
											`{$this->prefix}slots` 
										WHERE 
											`tornei_id`=$id_torneo 
										ORDER BY 
											`giorno` ASC, 
											`campo` ASC, 
											ordo ASC");
		$_ = array();
		if ( $dati ) foreach ( $dati as $row ) $_[ $row->giorno ][ $row->campo ][ $row->id ] = $row;
		return $_;
	}
	
	private function get_squadre( $id_torneo ) {
		global $wpdb;
		$dati = $wpdb->get_results("
				SELECT 
					GROUP_CONCAT( CONCAT(`{$this->prefix}atleti`.`nome`, ' ',`{$this->prefix}atleti`.`cognome`)  SEPARATOR ', ') AS `atleti`,
					`{$this->prefix}squadre`.`id`,
					`{$this->prefix}squadre`.`label`,
					`{$this->prefix}squadre`.`categoria`,
					`{$this->prefix}gironi`.`girone`
				FROM 
					`{$this->prefix}squadre`
				LEFT JOIN `{$this->prefix}squadre_atleti`
					ON `{$this->prefix}squadre_atleti`.`squadre_id`=`{$this->prefix}squadre`.`id`
				LEFT JOIN `{$this->prefix}atleti`
					ON `{$this->prefix}squadre_atleti`.`atleti_id`=`{$this->prefix}atleti`.`id`
				LEFT JOIN `{$this->prefix}gironi`
					ON `{$this->prefix}gironi`.`squadre_id`=`{$this->prefix}squadre`.`id`
				WHERE 
					`{$this->prefix}squadre`.`tornei_id`={$id_torneo} 
				GROUP BY
					`{$this->prefix}squadre`.`id`
				ORDER BY 
					`{$this->prefix}squadre`.`label` ASC,
					`{$this->prefix}atleti`.`cognome` ASC,
					`{$this->prefix}atleti`.`nome` ASC" );
		$_ = array();
		if ( $dati ) foreach ( $dati as $row ) $_[ $row->categoria ][ $row->girone ][ $row->label ] = $row;
		foreach ( $_ as $categoria => $gironi ) foreach ( $gironi as $girone => $squadre ) {
			ksort( $squadre );
			$_[ $categoria ][ $girone ] = $squadre;
		}
		return $_;
	}
	
	public function sc_squadre( $atts, $content = '' ) {
		extract( shortcode_atts( array(
			'torneo' => $this->opts->corrente
		), $atts ) );
		
		if ( !$torneo ) return '';
		
		$_ = '<div class="volleytnt_sc_squadre">';
		$squadre = $this->get_squadre( $torneo );
		
		foreach ( $squadre as $categoria => $gironi ) foreach ( $gironi as $girone => $squadre ) {
			$_ .= '<h3>' . sprintf( __('Girone %s', 'volleytnt'), $categoria . $girone ) . '</h3>';
			$_ .= '<table><thead><tr><th class="squadra">' . __('Squadra', 'volleytnt') . '</th><th class="atleti">' . __('Componenti', 'volleytnt') . '</th></tr></thead><tbody>';
			foreach ( $squadre as $row ) {
				$_ .= '<tr><th class="squadra">' . $row->label . '</th><td class="atleti">' . $row->atleti . '</td></tr>';
			}
			$_ .= '</tbody></table>';
		}
		return $_ . '</div>';
	}
	
	
	public function sc_calendario( $atts, $content = '' ) {
		extract( shortcode_atts( array(
			'torneo' => $this->opts->corrente
		), $atts ) );
		if ( !$torneo ) return '';
		$_ = '<div class="volleytnt_sc_calendario">';
		$slots = $this->get_slots( $torneo );
		$partite = $this->get_partite( $torneo );
		$finali = $this->get_finali( $torneo );
		$partite = array_merge_recursive( $partite, $finali );

		foreach ( $slots as $giorno => $campi ) {
			$mappa = array();
			$i_campi = array();
			$le_ore = array();
			foreach ( $campi as $num_campo => $slot_campo ) foreach ( $slot_campo as $slot ) {
				$mappa[ $slot->ts_i ][ $num_campo ] = $slot;
				$i_campi[] = $num_campo;
				$le_ore[ $slot->ts_i ] =  $slot->inizio . ' - ' . $slot->fine;
			}
			$i_campi = array_unique( $i_campi );
			sort( $i_campi );
			$_ .= '<h3>' . $giorno . '</h3>';
			$_ .= '<table><thead><tr><th class="orario">' . __('Orario', 'volleytnt') . '</th>';
			foreach ( $i_campi as $id_campo ) $_ .= '<th class="campo">' . sprintf( __('Campo %d', 'volleytnt'), $id_campo ) . '</th>';
			$_ .= '</tr></thead><tbody>';
			foreach ( $mappa as $ts_i => $slotti ) {
				$_ .= '<tr>';
				$_ .= '<th class="orario">' . $le_ore[ $ts_i ] . '</th>';
				foreach ( $i_campi as $id_campo ) {
					if ( isset( $slotti[ $id_campo ] ) ) {
						$is_partita = false;
						foreach ( $partite as $categ ) foreach ( $categ as $giron ) foreach ( $giron as $partita ) if ( $partita->slots_id == $slotti[ $id_campo ]->id ) {
							$is_partita = true;	
							break( 3 );
						}
						$_ .= '<td class="campo ' . ( $is_partita ? 'partita' : 'no_partita' ) . '">';
						if ( $is_partita ) {
							if ( isset( $partita->girone ) and $partita->girone ) $_ .= '<strong>' . $partita->categoria . $partita->girone . ':</strong>&nbsp;';
							if ( isset( $partita->finale ) and $partita->finale ) $_ .= '<em>' . $this->idfinale2human( $partita->finale ) . ' ' . $partita->categoria . '</em><br/>';
							if ( $partita->label_1 and $partita->label_2 ) $_ .= $partita->label_1 . ' - ' . $partita->label_2;
						} else {
							$_ .= '&nbsp;';
						}
						$_ .= '</td>';
					} else {
						$_ .= '<td class="campo no_campo">&nbsp;</td>';
					}
				}
				$_ .= '</tr>';
			}
			
			$_ .= '</tbody></table>';
		}
		return $_ . '</div>';
	}

	
	private function idfinale2human( $id ) {
		switch ( array_shift( explode( '_', $id ) ) ) {
			case '32':	return __('Trentaduesimi', 'volleytnt');	break;
			case '16':	return __('Sedicesimi', 'volleytnt');		break;
			case '8': 	return __('Ottavi', 'volleytnt'); 			break;
			case '4': 	return __('Quarti', 'volleytnt'); 			break;
			case '2': 	return __('Semifinali', 'volleytnt'); 		break;
			case '1': 	return __('Finalina', 'volleytnt');	 		break;
			case '0': 	return __('Finale', 'volleytnt'); 			break;
			default: 	return $id; 								break;
		}
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
		if ( !is_callable( array( $wp_admin_bar, 'add_menu' ) ) ) return;
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
		echo '<div class="wrap ' . get_class( $page ) . '">';
		screen_icon('volleytnt');
		echo '<h2>' . $page->get_title() . $page->get_title_action() . '</h2>';
		$page->do_page( $this );
		echo '</div>';
	}
	
}

global $VolleyTNT;
if ( !isset( $VolleyTNT ) or !( $VolleyTNT instanceof VolleyTNT ) ) $VolleyTNT = new VolleyTNT();

?>