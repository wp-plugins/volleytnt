<?php 
class VolleyTNT_Home extends VolleyTNT_AdminPage {
	
	public $menu_child_of = false;

	public function __construct() {
		$this->set_title( __("Volley TNT", 'volleytnt') ); 
		$this->add_case( 'firstpage' );
		
		$this->load_js( 'admin_squadre.js', 'jquery-ui-tabs', 'jquery-ui-autocomplete', 'jquery-ui-datepicker' );
		
		$this->localize_js_string( 'conferma_elimina_squadra', __("Si vuole realmente cancellare questa squadra dal torneo?", 'volleytnt') );
		
	}
	
	public function firstpage() {
		require_once(ABSPATH . 'wp-admin/includes/dashboard.php');
		$screen = get_current_screen();
		$priority = 'core';
		
//		wp_add_dashboard_widget('volleytnt_iscrizione_rapida', __('Iscrizione rapida', 'volleytnt'), array( $this, 'iscrizione_rapida' ) );	
//		wp_add_dashboard_widget('volleytnt_iscritti', __('Iscritti', 'volleytnt'), array( $this, 'iscritti' ) );	
		
		add_meta_box( 'volleytnt_iscrizione_rapida', __('Iscrizione rapida', 'volleytnt'), array( $this, 'iscrizione_rapida' ), $screen, 'normal', $priority );	
		add_meta_box( 'volleytnt_iscritti', __('Iscritti', 'volleytnt'), array( $this, 'iscritti' ), $screen, 'side', $priority );
		
		wp_dashboard();
	}

	function iscrizione_rapida() {
		global $VolleyTNT;
		echo '<p>';
		printf( __('Per impostare le impossibilità utilizzare la <a href="%s">registrazione completa</a>.', 'volleytnt'), add_query_arg( array(	'page'	=> 'volleytnt_squadre',	'method' => 'edit' ), admin_url('admin.php') ) );
		echo '</p>';
		$VolleyTNT->call_page_method( 'volleytnt_squadre', 'edit', 0, true );
	} 
	
	function iscritti() {
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
