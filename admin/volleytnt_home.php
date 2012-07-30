<?php 
class VolleyTNT_Home extends VolleyTNT_AdminPage {
	
	public $menu_child_of = false;

	public function __construct() {
		$this->set_title( __("Volley TNT", 'volleytnt') ); 
		$this->add_case( 'firstpage' );
		
		$this->load_js( 'admin_squadre.js', 'jquery-ui-tabs', 'jquery-ui-autocomplete', 'jquery-ui-datepicker' );
		$this->load_js( 'admin_home.js', 'jquery-ui-slider' );
		
		$this->localize_js_string( 'conferma_elimina_squadra', __("Si vuole realmente cancellare questa squadra dal torneo?", 'volleytnt') );
		
	}
	
	public function firstpage() {
		require_once(ABSPATH . 'wp-admin/includes/dashboard.php');
		$screen = get_current_screen();
		$priority = 'core';
		
//		wp_add_dashboard_widget('volleytnt_iscrizione_rapida', __('Iscrizione rapida', 'volleytnt'), array( $this, 'iscrizione_rapida' ) );	
//		wp_add_dashboard_widget('volleytnt_iscritti', __('Iscritti', 'volleytnt'), array( $this, 'iscritti' ) );	
		
		add_meta_box( 'volleytnt_iscrizione_rapida', __('Iscrizione rapida', 'volleytnt'), array( $this, 'iscrizione_rapida' ), $screen, 'normal', $priority );	
		add_meta_box( 'volleytnt_iscritti', __('Iscritti', 'volleytnt'), array( $this, 'iscritti' ), $screen, 'normal', $priority );
		add_meta_box( 'volleytnt_calcolopartite', __('Calcolo partite gironi', 'volleytnt'), array( $this, 'calcolo' ), $screen, 'side', $priority );
		
		wp_dashboard();
	}

	public function calcolo() {
		global $wpdb;
		$squadre = array();
		if ( $tmp = $wpdb->get_results("SELECT
										  `categoria`,
										  COUNT(`id`) AS `numero`
										FROM `{$this->prefix}squadre`
										WHERE `tornei_id` = {$this->opts->corrente}
										GROUP BY `categoria`" ) ) foreach ( $tmp as $row ) {
			$squadre[ $row->categoria ] = absint( $row->numero );
		}
		echo '<div id="calcolopartite">';
		echo '<table class="widefat">';
		echo '<thead><tr>';
		echo '<th>' . __('Categoria', 'volleytnt') . '</th>';
		echo '<th>' . __('Gironi', 'volleytnt') . '</th>';
		echo '<th>' . __('Finali', 'volleytnt') . '</th>';
		echo '</tr></thead><tbody>';
		$vfin = array( 	2 => $this->l_finali['1'],
						4 => $this->l_finali['2'],
						8 => $this->l_finali['4'],
						16 => $this->l_finali['8'],
						32 => $this->l_finali['16'],
						64 => $this->l_finali['32'] );
		
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<tr class="categoria categoria' . $categoria . '" categoria="' . $categoria . '" squadre="' . $squadre[ $categoria ] . '">';
			if ( !isset( $squadre[ $categoria ] ) ) $squadre[ $categoria ] = 0;
			echo '<th>' . $this->l_categorie[ $categoria ] . '</th>';
			echo '<td>';
			echo '<p>' . __( "Squadre per girone:", 'volleytnt' ) . '</p>';
			echo '<div class="slider" categoria="' . $categoria . '" max="' . floor( $squadre[ $categoria ] / 2 ) . '"></div>';
			echo '<p>' . sprintf( __( "%s gironi da almeno %s squadre.", 'volleytnt' ), '<span class="numgironi"></span>', '<span class="squadregironi"></span>' ) . '</p>';
			echo '</td>';
			echo '<td>';
			echo '<p>' . __("Prima fase delle finali:", 'volleytnt') . '</p>';
			echo '<select class="selfinali" modifica="num_par_' . $categoria . '"">';
			foreach ( $vfin as $k => $l ) echo '<option value="' . $k . '">' . $l . '</option>';
			echo '</select>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<br>';
		echo '<table class="risultati widefat">';
		echo '<thead>';
		echo '<th>' . __("Categoria", 'volleytnt') . '</th>';
		echo '<th>' . __("Gironi da", 'volleytnt') . '</th>';
		echo '<th>' . __("Part./gir.", 'volleytnt') . '</th>';
		echo '<th>' . __("Num. gironi", 'volleytnt') . '</th>';
		echo '<th>' . __("Partite", 'volleytnt') . '</th>';
		echo '</thead>';
		echo '<tbody></tbody><tfoot>';
		echo '<tr><td colspan="3">' . __('Totali gironi:', 'volleytnt') . '<td class="num_gir"></td><td class="num_par subtot"></td></tr>';
		
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<tr><td colspan="4">' . sprintf( __('Totali finali %s:', 'volleytnt'), $this->l_categorie[ $categoria ] ) . '<td class="num_par_' . $categoria . ' subtot"></td></tr>';
		
		}
		echo '<tr><th colspan="4">' . __('Partite giocate:', 'volleytnt') . '<th><span id="iltotale"></span> / ' . $wpdb->get_var("SELECT COUNT(`id`) AS `t` FROM `{$this->prefix}slots` WHERE `tornei_id`=" . $this->opts->corrente ) . '</th></tr>';
		
		echo '</tfoot>';
		echo '</table>';
		echo '</div>';
	}

	public function iscrizione_rapida() {
		global $VolleyTNT;
		echo '<p>';
		printf( __('Per impostare le impossibilità utilizzare la <a href="%s">registrazione completa</a>.', 'volleytnt'), add_query_arg( array(	'page'	=> 'volleytnt_squadre',	'method' => 'edit' ), admin_url('admin.php') ) );
		echo '</p>';
		$VolleyTNT->call_page_method( 'volleytnt_squadre', 'edit', 0, true );
	} 
	
	public function iscritti() {
		global $wpdb;
		$dati = $wpdb->get_results("SELECT
									  `s`.`categoria`,
									  COUNT( DISTINCT `sa`.`squadre_id` ) AS `num`,
									  COUNT( `a`.`id` ) AS `atl`,
									  SUM( `sa`.`pagato` ) AS `pag`,
									  SUM( `sa`.`manleva` ) AS `man`
									FROM `{$this->prefix}squadre_atleti` AS `sa`
									  JOIN `{$this->prefix}atleti` AS `a`
									    ON `a`.`id` = `sa`.`atleti_id`
									  JOIN `{$this->prefix}squadre` AS `s`
									    ON `s`.`id` = `sa`.`squadre_id`
									      AND `s`.`tornei_id` = {$this->opts->corrente}
									GROUP BY `s`.`categoria`
									ORDER BY `s`.`categoria`");
		echo '<p>';
		printf( __('Per informazioni dettagliate andare alla <a href="%s">pagina delle squadre</a>.', 'volleytnt'), add_query_arg( array(	'page'	=> 'volleytnt_squadre' ), admin_url('admin.php') ) );
		echo '</p>';
		echo '<table class="widefat"><thead><tr><th>&nbsp;</th>';
		echo '<th>' . __('Squadre', 'volleytnt') . '</th>';
		echo '<th>' . __('Atleti', 'volleytnt') . '</th>';
		echo '<th>' . __('Pagato', 'volleytnt') . '</th>';
		echo '<th>' . __('Manleva', 'volleytnt') . '</th>';
		echo '</tr></thead><tbody>';
		if ( $dati ) {
			$num = $atl = $pag = $man = 0;
			foreach ( $dati as $row ) {
				echo '<tr>';
				echo '<th>' . $this->l_categorie[ $row->categoria ] . '</th>';
				echo '<td>' . number_format_i18n( $row->num ) . '</td>';
				echo '<td>' . number_format_i18n( $row->atl ) . '</td>';
				echo '<td>' . number_format_i18n( $row->pag ) . '</td>';
				echo '<td>' . number_format_i18n( $row->man ) . '</td>';
				echo '</tr>';
				$num += intval( $row->num );
				$atl += intval( $row->atl );
				$pag += intval( $row->pag );
				$man += intval( $row->man );
			}
			echo '</tbody>';
			echo '<tfoot><tr><th>' . __("Totali:", 'volleytnt') . '</th>';
			echo '<th>' . number_format_i18n( $num ) . '</th>';
			echo '<th>' . number_format_i18n( $atl ) . '</th>';
			echo '<th>' . number_format_i18n( $pag ) . '</th>';
			echo '<th>' . number_format_i18n( $man ) . '</th>';
			echo '</tr></tfoot>';
		} else {
			echo '<tr><td colspan="5">' . __("Ancora nessuna iscrizione per il torneo corrente.", 'volleytnt') . '</td></tr>';
			echo '</tbody>';
		}
		echo '</table>';
	}
	
}
?>
