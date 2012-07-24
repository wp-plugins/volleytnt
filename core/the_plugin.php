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
	
	public function call_page_method( $page_slug, $method ) {
		$args = func_get_args();
		$page_slug = array_shift( $args );
		$method = array_shift( $args );
		if ( isset( $this->pages[ $page_slug ] ) and is_callable( array( $this->pages[ $page_slug ], $method ) ) ) {
			call_user_func_array( array( $this->pages[ $page_slug ], $method ), $args );
		}
	}
	
	public function __construct() {
		global $wpdb;
		$this->path = VOLLEYTNT_PATH;
		$this->url = VOLLEYTNT_URL;
		$this->prefix = $wpdb->prefix . 'volleytnt_';
		
		require_once( $this->path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'functions.php' );
		require_once( $this->path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'table.php' );
		require_once( $this->path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'form.php' );
		require_once( $this->path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'adminpage.php' );

		add_action( 'init',						array( $this, 'init' ) );
		add_action( 'admin_menu',				array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts',	array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'template_redirect',		array( $this, 'template_redirect' ) );
		add_action( 'admin_head',				array( $this, 'admin_head' ) );
		
		add_filter( 'plugin_row_meta',			array( $this, 'plugin_row_meta' ), 10, 4 );

		$this->register_page('VolleyTNT_Home');
		$this->register_page('VolleyTNT_Squadre');
		$this->register_page('VolleyTNT_Gironi');
		$this->register_page('VolleyTNT_Partite');
		$this->register_page('VolleyTNT_Risultati');
		$this->register_page('VolleyTNT_Finali');
		$this->register_page('VolleyTNT_Opzioni');
		
				
		add_shortcode( 'volleytnt_classifiche_gironi', array( $this, 'sc_classifiche_gironi' ) );
		add_shortcode( 'volleytnt_risultati', array( $this, 'sc_risultati' ) );
		add_shortcode( 'volleytnt_calendario', array( $this, 'sc_calendario' ) );
		add_shortcode( 'volleytnt_squadre', array( $this, 'sc_squadre' ) );
		add_shortcode( 'volleytnt_finali', array( $this, 'sc_finali' ) );
		
	}

	public function get_lang() {
		return array_shift( explode( '_', get_locale() ) );
	}
	
	public function admin_head() {
		?>
		<script language="JavaScript" type="text/javascript">
			volleytnt_lang = '<?php echo $this->get_lang(); ?>';
		</script>
		<?php
	}
	public function template_redirect() {
		wp_enqueue_style('volleytnt_common');
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
		$this->l_finali = array(		'0' => __("Non giocato", 'volleytnt'),
										'1'	=> __("Finale", 'volleytnt'),
										'2'	=> __("Semifinali", 'volleytnt'),
										'4'	=> __("Quarti", 'volleytnt'),
										'8'	=> __("Ottavi", 'volleytnt'),
										'16'=> __("Sedicesimi", 'volleytnt'),
										'32'=> __("Trentaduesimi", 'volleytnt') );

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
		$this->torneo->finali_M = absint( $this->torneo->finali_M );
		$this->torneo->finali_F = absint( $this->torneo->finali_F );
		$this->torneo->finali_X = absint( $this->torneo->finali_X );
		
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
											`{$this->prefix}slots`.`giorno` ASC, 
											`campo` ASC, 
											ordo ASC");
		$_ = array();
		if ( $dati ) foreach ( $dati as $row ) $_[ $row->giorno ][ $row->campo ][ $row->id ] = $row;
		return $_;
	}
	
	
	private function get_risultati( $id_torneo ) {
		global $wpdb;
		$dati = $wpdb->get_results("SELECT
									  `sq1`.`label`              AS `nome1`,
									  `sq2`.`label`              AS `nome2`,
									  `{$this->prefix}partite`.*,
									  DATE_FORMAT(`{$this->prefix}slots`.`giorno`, '" . volleytnt_date_format('sql') . "') AS `giorno`,
									  DATE_FORMAT(`{$this->prefix}slots`.`inizio`, '%k:%i') AS `inizio`,
									  DATE_FORMAT(`{$this->prefix}slots`.`fine`, '%k:%i') AS `fine`,
									  `{$this->prefix}slots`.`campo`
									FROM `{$this->prefix}partite`
									  LEFT JOIN `{$this->prefix}slots`
									    ON `{$this->prefix}slots`.`id` = `{$this->prefix}partite`.`slots_id`
									  LEFT JOIN `{$this->prefix}squadre` AS `sq1`
									    ON `sq1`.`id` = `{$this->prefix}partite`.`squadra_1`
									  LEFT JOIN `{$this->prefix}squadre` AS `sq2`
									    ON `sq2`.`id` = `{$this->prefix}partite`.`squadra_2`
									WHERE `{$this->prefix}partite`.`tornei_id` = $id_torneo
										AND `{$this->prefix}partite`.`girone` <> 0
									ORDER BY `{$this->prefix}partite`.`categoria` ASC, `{$this->prefix}partite`.`girone` ASC");
		$_ = array();
		if ( $dati ) foreach ( $dati as $row ) $_[ $row->categoria ][ $row->girone ][ $row->id ] = $row;
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
	
	
	private function get_classifiche( $id_torneo ) {
		global $wpdb;
		$work = array();
		$dati = $wpdb->get_results("	SELECT 
											`{$this->prefix}partite`.*,
											`sq1`.`label` AS `label_1`,
											`sq2`.`label` AS `label_2`
										FROM 
											`{$this->prefix}partite` 
										LEFT JOIN `{$this->prefix}squadre` AS `sq1`
											ON `{$this->prefix}partite`.`squadra_1`=`sq1`.`id`
										LEFT JOIN `{$this->prefix}squadre` AS `sq2`
											ON `{$this->prefix}partite`.`squadra_2`=`sq2`.`id`
										WHERE 
											`{$this->prefix}partite`.`tornei_id`=$id_torneo AND `{$this->prefix}partite`.`girone`<>0");
		if ( $dati ) {
			foreach ( $dati as $row ) {
				if ( !isset( $work[ $row->categoria ][ $row->girone ] ) ) $work[ $row->categoria ][ $row->girone ] = array();

				if ( !isset( $work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ] ) )
					$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ] = array(	'giocate'		=> 0,
																							'vinti'			=> 0,
																							'persi'			=> 0,
																							'fatti'			=> 0,
																							'subiti'		=> 0,
																							'id'			=> $row->squadra_1,
																							'label'			=> $row->label_1 );
				if ( !isset( $work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ] ) ) 
					$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ] = array(	'giocate'		=> 0,
																							'vinti'			=> 0,
																							'persi'			=> 0,
																							'fatti'			=> 0,
																							'subiti'		=> 0,
																							'id'			=> $row->squadra_2,
																							'label'			=> $row->label_2  );

				
				$set1_sq1 = absint( $row->set1_sq1 );
				$set1_sq2 = absint( $row->set1_sq2 );
				$set2_sq1 = absint( $row->set2_sq1 );
				$set2_sq2 = absint( $row->set2_sq2 );
				$set3_sq1 = absint( $row->set3_sq1 );
				$set3_sq2 = absint( $row->set3_sq2 );
				if ( $this->torneo->set_partita == 5 ) {
					$set4_sq1 = absint( $row->set4_sq1 );
					$set4_sq2 = absint( $row->set4_sq2 );
					$set5_sq1 = absint( $row->set5_sq1 );
					$set5_sq2 = absint( $row->set5_sq2 );
				}
				$vinti1 = $vinti2 = 0;
				if ( ( $set1_sq1 or $set1_sq2 ) and ( $set2_sq1 or $set2_sq2 ) ) { // la partita è stata giocata
					$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['giocate']++;
					$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['giocate']++;
					$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['fatti'] += $set1_sq1 + $set2_sq1 + $set3_sq1;
					$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['fatti'] += $set1_sq2 + $set2_sq2 + $set3_sq2;
					$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['subiti'] += $set1_sq2 + $set2_sq2 + $set3_sq2;
					$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['subiti'] += $set1_sq1 + $set2_sq1 + $set3_sq1;
					
					if ( $this->torneo->set_partita == 5 ) {
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['fatti'] += $set4_sq1 + $set5_sq1;
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['fatti'] += $set4_sq2 + $set5_sq2;
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['subiti'] += $set4_sq2 + $set5_sq2;
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['subiti'] += $set4_sq1 + $set5_sq1;
					}
					
					if ( $set1_sq1 > $set1_sq2 ) {
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['vinti']++;
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['persi']++;
					} else {
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['vinti']++;
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['persi']++;
					}
					if ( $set2_sq1 > $set2_sq2 ) {
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['vinti']++;
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['persi']++;
					} else {
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['vinti']++;
						$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['persi']++;
					}
					if ( $this->torneo->set_partita == 5 ) {
						if ( $set3_sq1 > $set3_sq2 ) {
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['vinti']++;
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['persi']++;
						} else {
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['vinti']++;
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['persi']++;
						}
						if ( $set4_sq1 or $set4_sq2 ) if ( $set4_sq1 > $set4_sq2 ) {
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['vinti']++;
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['persi']++;
						} else {
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['vinti']++;
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['persi']++;
						}
						if ( $set5_sq1 or $set5_sq2 ) if ( $set5_sq1 > $set5_sq2 ) {
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['vinti']++;
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['persi']++;
						} else {
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['vinti']++;
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['persi']++;
						}
					} else {
						if ( $set3_sq1 or $set3_sq2 ) if ( $set3_sq1 > $set3_sq2 ) {
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['vinti']++;
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['persi']++;
						} else {
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_2 ]['vinti']++;
							$work[ $row->categoria ][ $row->girone ][ $row->squadra_1 ]['persi']++;
						}
					}
				}
			}
			
			foreach ( $work as $categoria => $i_gironi ) { 
				foreach ( $i_gironi as $girone => $squadre ) { 
					foreach ( $squadre as $id_squadra => $dati ) {
						$dati['q_vinti'] = $dati['giocate'] ? round( $dati['vinti'] / $dati['giocate'], 2 ) : 0;
						$dati['d_set'] = $dati['vinti'] - $dati['persi'];
						$dati['qd_set'] = $dati['giocate'] ? round( $dati['d_set'] / $dati['giocate'], 2 ) : 0;
						$dati['q_fatti'] = $dati['giocate'] ? round( $dati['fatti'] / $dati['giocate'], 2 ) : 0;
						$dati['d_punti'] = $dati['fatti'] - $dati['subiti'];
						$dati['qd_punti'] = $dati['giocate'] ? round( $dati['d_punti'] / $dati['giocate'], 2 ) : 0;
						$work[ $categoria ][ $girone ][ $id_squadra ] = $dati;
					}
					usort( $work[ $categoria ][ $girone ], array( $this, 'sort_classifica') );
				}
				ksort( $work[ $categoria ] );
			}
			ksort( $work );	
		}
		return $work;	
	}
	
	private function sort_classifica( $a, $b ) {
		if ( $a['vinti'] == $b['vinti'] ) {
			if ( $a['qd_set'] == $b['qd_set'] ) {
				if ( $a['qd_punti'] == $b['qd_punti'] ) {
					if ( $a['giocate'] == $b['giocate'] ) {
						return 0;
					} else return $a['giocate'] > $b['giocate'] ? 1 : -1;
				} else return $a['qd_punti'] < $b['qd_punti'] ? 1 : -1;
			} else return $a['qd_set'] < $b['qd_set'] ? 1 : -1;	
		} else return $a['vinti'] < $b['vinti'] ? 1 : -1;
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


	public function sc_finali( $atts, $content = '' ) {
		global $wpdb;
		extract( shortcode_atts( array(
			'torneo' => $this->opts->corrente,
			'categoria' => false
		), $atts ) );
		
		if ( !$torneo ) return '';
		
		$tree = new VolleyTNT_Tree( $torneo );
		if ( !$tree->finali ) return '';
		
		ob_start();
		echo '<div class="volleytnt_sc_finali">';
		$cats = $categoria ? array( $categoria ) : $tree->torneo->categorie;
		
		foreach ( $cats as $cat ) {
			echo '<div class="albero finali_' . $cat . '">';
			echo '<h3>' . $this->l_categorie[ $cat ] . '</h3>';
			$tree->show( $cat );
			echo '</div>';	
		}
		
		echo '</div>';
		return ob_get_clean();
	}
	
	public function sc_risultati( $atts, $content = '' ) {
		extract( shortcode_atts( array(
			'torneo' => $this->opts->corrente
		), $atts ) );
		if ( !$torneo ) return '';
		$_ = '<div class="tornei_risultati">';
		$dati = $this->get_risultati( $torneo );
		foreach ( $dati as $categoria => $gironi ) foreach ( $gironi as $girone => $partite ) {
			$_ .= '<h3>Girone ' . $categoria . $girone . '</h3>';
			$_ .= '<table>';
			$_ .= '<thead><tr>';
			$_ .= '<th class="partita">' . __('Partita', 'volleytnt') . '</th>';
			$_ .= '<th class="squadre">' . __('Squadre', 'volleytnt') . '</th>';
			$_ .= '<th class="risultato">' . __('Risultato', 'volleytnt') . '</th>';
			$_ .= '<th class="set set1">' . sprintf( __('%d&ordm; set', 'volleytnt'), 1 ) . '</th>';
			$_ .= '<th class="set set2">' . sprintf( __('%d&ordm; set', 'volleytnt'), 2 ) . '</th>';
			$_ .= '<th class="set set3">' . sprintf( __('%d&ordm; set', 'volleytnt'), 3 ) . '</th>';
			if ( $this->torneo->set_partita == 5 ) {
				$_ .= '<th class="set set4">' . sprintf( __('%d&ordm; set', 'volleytnt'), 4 ) . '</th>';
				$_ .= '<th class="set set5">' . sprintf( __('%d&ordm; set', 'volleytnt'), 5 ) . '</th>';
			}
			$_ .= '</tr></thead>';
			$_ .= '<tbody>';
			foreach ( $partite as $id_partita => $row ) {
				
				$set1 = $set2 = 0;
				if ( !$row->set1_sq1 and !$row->set1_sq2 ) {
					$row->set1_sq1 = $row->set1_sq2 = '';
				} else if ( $row->set1_sq1 > $row->set1_sq2 ) $set1++; else $set2++;
				if ( !$row->set2_sq1 and !$row->set2_sq2 ) {
					$row->set2_sq1 = $row->set2_sq2 = '';
				} else if ( $row->set2_sq1 > $row->set2_sq2 ) $set1++; else $set2++;
				if ( !$row->set3_sq1 and !$row->set3_sq2 ) {
					$row->set3_sq1 = $row->set3_sq2 = '';
				} else if ( $row->set3_sq1 > $row->set3_sq2 ) $set1++; else $set2++;
				if ( $this->torneo->set_partita == 5 ) {
					if ( !$row->set4_sq1 and !$row->set4_sq2 ) {
						$row->set4_sq1 = $row->set4_sq2 = '';
					} else if ( $row->set4_sq1 > $row->set4_sq2 ) $set1++; else $set2++;
					if ( !$row->set5_sq1 and !$row->set5_sq2 ) {
						$row->set5_sq1 = $row->set5_sq2 = '';
					} else if ( $row->set5_sq1 > $row->set5_sq2 ) $set1++; else $set2++;
				}
				if ( !$set1 and !$set2 ) $set1 = $set2 = '';
				$_ .= '<tr>';
				
				$_ .= '<th>';
				$_ .= sprintf( __('Girone %s', 'volleytnt'), $row->categoria . $row->girone );
				if ( $row->campo ) $_ .= '<br/>' . sprintf( __('%s, %s-%s, campo %s', 'volleytnt'), $row->giorno, $row->inizio, $row->fine, $row->campo );
				$_ .= '</th>';
				
				$_ .= '<td>';
				$_ .= '<div class="squadra sq1">' . $row->nome1 . '</div>';
				$_ .= '<div class="squadra sq2">' . $row->nome2 . '</div>';
				$_ .= '</td>';
				
				$_ .= '<td>';
				$_ .= '<div class="risultato sq1">' . $set1 . '</div>';
				$_ .= '<div class="risultato sq2">' . $set2 . '</div>';
				$_ .= '</td>';
				
				$_ .= '<td>';
				$_ .= '<div class="riga sq1">' . $row->set1_sq1 . '</div>';
				$_ .= '<div class="riga sq2">' . $row->set1_sq2 . '</div>';
				$_ .= '</td>';
				
				$_ .= '<td>';
				$_ .= '<div class="riga sq1">' . $row->set2_sq1 . '</div>';
				$_ .= '<div class="riga sq2">' . $row->set2_sq2 . '</div>';
				$_ .= '</td>';
				
				$_ .= '<td>';
				$_ .= '<div class="riga sq1">' . $row->set3_sq1 . '</div>';
				$_ .= '<div class="riga sq2">' . $row->set3_sq2 . '</div>';
				$_ .= '</td>';

				if ( $this->torneo->set_partita == 5 ) {
				
					$_ .= '<td>';
					$_ .= '<div class="riga sq1">' . $row->set4_sq1 . '</div>';
					$_ .= '<div class="riga sq2">' . $row->set4_sq2 . '</div>';
					$_ .= '</td>';
					
					$_ .= '<td>';
					$_ .= '<div class="riga sq1">' . $row->set5_sq1 . '</div>';
					$_ .= '<div class="riga sq2">' . $row->set5_sq2 . '</div>';
					$_ .= '</td>';

				}
								
				$_ .= '<tr>';
			}
			$_ .= '</tbody></table>';
		}
		return $_ . '</div>';
	}

	
	public function sc_classifiche_gironi( $atts, $content = '' ) {
		extract( shortcode_atts( array(
			'torneo' => $this->opts->corrente
		), $atts ) );
		if ( !$torneo ) return '';
		$_ = '<div class="tornei_classifiche_gironi">';
		$classifiche = $this->get_classifiche( $torneo );
		foreach ( $classifiche as $categoria => $gironi ) foreach ( $gironi as $girone => $squadre ) {
			$pos = 1;
			$_ .= '<h3>' . sprintf( __('Girone %s', 'volleytnt'), $categoria . $girone ) . '</h3>';
			$_ .= '<table><thead><tr>';
			$_ .= '<th class="posizione">' . __('Pos.', 'volleytnt') . '</th>';
			$_ .= '<th class="squadra">' . __('Squadra', 'volleytnt') . '</th>';
			$_ .= '<th class="partite">' . __('Partite', 'volleytnt') . '</th>';
			$_ .= '<th class="set">' . __('Set', 'volleytnt') . '</th>';
			$_ .= '<th class="punti">' . __('Punti', 'volleytnt') . '</th>';
			$_ .= '</tr></thead><tbody>';
			foreach ( $squadre as $s ) {
				$_ .= '<tr>';
				$_ .= '<td class="posizione">' . $pos . '&ordm;</td>';
				$_ .= '<td class="squadra">' . $s['label'] . '</td>';
				$_ .= '<td class="partite">' . $s['giocate'] . '</td>';
				$_ .= '<td class="set">' . $s['vinti'] . 'v, ' . $s['persi'] . 'p <em>(' . $s['qd_set'] . ')</em></td>';
				$_ .= '<td class="punti">' . $s['fatti'] . 'f, ' . $s['subiti'] . 's <em>(' . $s['qd_punti'] . ')</em></td>';
				$_ .= '</tr>';
				$pos++;
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
				if ( in_array( 'jquery-ui-datepicker', $file['dependencies'] ) ) {
					wp_register_script( 'jquery-ui-datepicker-localization', 'http://jquery-ui.googlecode.com/svn/trunk/ui/i18n/jquery.ui.datepicker-' . $this->get_lang() . '.js', array( 'jquery-ui-datepicker' ) );
					wp_register_script( 'jquery-ui-datepicker-localization-run', $this->url . '/js/datepicker_localization.js', array( 'jquery-ui-datepicker-localization' ) );
					wp_enqueue_script( 'jquery-ui-datepicker-localization-run' );
				}
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
			add_action( 'load-' . $page->wp_slug, array( $page, '_page_load' ) );
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
		$this->pages[ strtolower( $classname ) ] = new $classname();
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

class VolleyTNT_Tree {
	public $torneo = false;
	public $squadre = array();
	public $finali = array();
	
	public function __construct( $torneo = null ) {
		global $wpdb;
		$torneo = is_null( $torneo ) ? 	$this->opts->corrente : absint( $torneo );
		$this->torneo = $wpdb->get_row("SELECT * FROM `{$this->prefix}tornei` WHERE `id`={$torneo}");
		$this->torneo->categorie = explode( ',', $this->torneo->categorie );
		if ( $tmp = $wpdb->get_results("SELECT * FROM `{$this->prefix}partite` WHERE `finale`<>'' AND `tornei_id`={$torneo} AND `visibile`=1" ) ) {
			foreach ( $tmp as $row ) $this->finali[ $row->categoria ][ $row->finale ] = $row;
		}
		if ( $tmp = $wpdb->get_results("SELECT `id`, `label`, `categoria` FROM `{$this->prefix}squadre` WHERE `tornei_id`={$torneo}" ) ) {
			foreach ( $tmp as $row ) $this->squadre[ $row->categoria ][ $row->id ] = $row->label;
		}
		if ( isset( $this->squadre['M'] ) ) asort( $this->squadre['M'] );
		if ( isset( $this->squadre['F'] ) ) asort( $this->squadre['F'] );
		if ( isset( $this->squadre['X'] ) ) asort( $this->squadre['X'] );
	}

	final public function __get( $attr ) {
		global $VolleyTNT;
		if ( isset( $VolleyTNT->$attr ) ) {
			$this->$attr = &$VolleyTNT->$attr;
			return $this->$attr;
		} else {
			return false;
		}
	}
	
	private function partita( $categoria, $id ) {
		if ( isset( $this->finali[ $categoria ][ $id ] ) ) {
			$p = $this->finali[ $categoria ][ $id ];
			$class = 'partita';
		} else {
			$p = new stdClass();
			$p->categoria = $categoria;
			$p->squadra_1 = 0;
			$p->squadra_2 = 0;
			$p->set1_sq1 = '';
			$p->set1_sq2 = '';
			$p->set2_sq1 = '';
			$p->set2_sq2 = '';
			$p->set3_sq1 = '';
			$p->set3_sq2 = '';
			$p->set4_sq1 = '';
			$p->set4_sq2 = '';
			$p->set5_sq1 = '';
			$p->set5_sq2 = '';
			$class = 'partita nongiocata';
		}

		$p->set1 = $p->set2 = 0;
		if ( $p->set1_sq1 or $p->set1_sq2 ) if ( $p->set1_sq1 > $p->set1_sq2 ) $p->set1++; else $p->set2++;
		if ( $p->set2_sq1 or $p->set2_sq2 ) if ( $p->set2_sq1 > $p->set2_sq2 ) $p->set1++; else $p->set2++;
		if ( $p->set3_sq1 or $p->set3_sq2 ) if ( $p->set3_sq1 > $p->set3_sq2 ) $p->set1++; else $p->set2++;
		if ( $this->torneo->set_partita == 5 ) {
			if ( $p->set4_sq1 or $p->set4_sq2 ) if ( $p->set4_sq1 > $p->set4_sq2 ) $p->set1++; else $p->set2++;
			if ( $p->set5_sq1 or $p->set5_sq2 ) if ( $p->set5_sq1 > $p->set5_sq2 ) $p->set1++; else $p->set2++;
		}
		if ( !$p->set1 and !$p->set2 ) $p->set1 = $p->set2 = $p->set1_sq1 = $p->set1_sq2 = $p->set2_sq1 = $p->set2_sq2 = $p->set3_sq1 = $p->set3_sq2 = $p->set4_sq1 = $p->set4_sq2 = $p->set5_sq1 = $p->set5_sq2 = '';
		$class .= ' p' . $id;
		?>
		<div class="<?php echo $class; ?>" squadra_1="<?php echo $p->squadra_1; ?>" squadra_2="<?php echo $p->squadra_2; ?>" categoria="<?php echo $p->categoria; ?>" id_partita="<?php echo $id; ?>">
			<div class="squadra squadra1"><?php echo isset( $this->squadre[ $p->categoria ][ $p->squadra_1 ] ) ? $this->squadre[ $p->categoria ][ $p->squadra_1 ] : ''; ?></div>
			<div class="separatore">
				<div class="punti punti1">
					<div class="risultato risultato1"><?php echo $p->set1; ?></div>
					<span class="set set1 sq1 set1_sq1"><?php echo $p->set1_sq1; ?></span>
					<span class="set set2 sq1 set2_sq1"><?php echo $p->set2_sq1; ?></span>
					<span class="set set3 sq1 set3_sq1"><?php echo $p->set3_sq1; ?></span>
					<?php if ( $this->torneo->set_partita == 5 ) { ?>
						<span class="set set4 sq1 set4_sq1"><?php echo $p->set4_sq1; ?></span>
						<span class="set set5 sq1 set5_sq1"><?php echo $p->set5_sq1; ?></span>
					<?php } ?>
				</div>
				<div class="sottoseparatore"></div>
				<div class="punti punti2">
					<div class="risultato risultato2"><?php echo $p->set2; ?></div>
					<span class="set set1 sq2 set1_sq2"><?php echo $p->set1_sq2; ?></span>
					<span class="set set2 sq2 set2_sq2"><?php echo $p->set2_sq2; ?></span>
					<span class="set set3 sq2 set3_sq2"><?php echo $p->set3_sq2; ?></span>
					<?php if ( $this->torneo->set_partita == 5 ) { ?>
						<span class="set set4 sq2 set4_sq2"><?php echo $p->set4_sq2; ?></span>
						<span class="set set5 sq2 set5_sq2"><?php echo $p->set5_sq2; ?></span>
					<?php } ?>
				</div>
			</div>
			<div class="squadra squadra2"><?php echo isset( $this->squadre[ $p->categoria ][ $p->squadra_2 ] ) ? $this->squadre[ $p->categoria ][ $p->squadra_2 ] : ''; ?></div>
		</div>
		<?php
	}

	public function show( $categoria ) {
		$attr = 'finali_' . $categoria;
		echo '<div class="tabellonefinale base' . $this->torneo->$attr . '">';
		echo '<div class="fasi">';
		if ( $this->torneo->$attr >= 32 ) echo '<h4 class="trentaduesimi">' . __('Trentaduesimi', 'volleytnt') . '</h4>';
		if ( $this->torneo->$attr >= 16 ) echo '<h4 class="sedicesimi">' . __('Sedicesimi', 'volleytnt') . '</h4>';
		if ( $this->torneo->$attr >= 8 ) echo '<h4 class="ottavi">' . __('Ottavi', 'volleytnt') . '</h4>';
		if ( $this->torneo->$attr >= 4 ) echo '<h4 class="quarti">' . __('Quarti', 'volleytnt') . '</h4>';
		if ( $this->torneo->$attr >= 2 ) echo '<h4 class="semifinali">' . __('Semifinali', 'volleytnt') . '</h4><h4 class="finalina">' . __('Finalina', 'volleytnt') . '</h4>';
		echo '<h4 class="finale">' . __('Finale', 'volleytnt') . '</h4>';
		echo '<br style="clear:both" />';
		echo '</div>';
		
		if ( $this->torneo->$attr >= 32 ) {
			echo '<div class="trentaduesimi colonna">';
			for ( $i = 1; $i <= 32; $i++ ) $this->partita( $categoria, '32_' . $i );
			echo '</div>';
		}
		if ( $this->torneo->$attr >= 16 ) {
			echo '<div class="sedicesimi colonna">';
			for ( $i = 1; $i <= 16; $i++ ) $this->partita( $categoria, '16_' . $i );
			echo '</div>';
		}
		if ( $this->torneo->$attr >= 8 ) {
			echo '<div class="ottavi colonna">';
			for ( $i = 1; $i <= 8; $i++ ) $this->partita( $categoria, '8_' . $i );
			echo '</div>';
		}
		if ( $this->torneo->$attr >= 4 ) {
			echo '<div class="quarti colonna">';
			for ( $i = 1; $i <= 4; $i++ ) $this->partita( $categoria, '4_' . $i );
			echo '</div>';
		}
		if ( $this->torneo->$attr >= 2 ) {
			echo '<div class="semifinali colonna">';
			$this->partita( $categoria, '2_1' );
			$this->partita( $categoria, '2_2' );
			echo '</div>';

			echo '<div class="finalina colonna">';
			$this->partita( $categoria, '1_1' );
			echo '</div>';
		}
		
		echo '<div class="finale colonna">';
		$this->partita( $categoria, '0_1' );
		echo '</div>';

		echo '</div>';
		echo '<br style="clear:both" />';
	}
}

global $VolleyTNT;
if ( !isset( $VolleyTNT ) or !( $VolleyTNT instanceof VolleyTNT ) ) $VolleyTNT = new VolleyTNT();

?>