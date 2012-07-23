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
		
		wp_add_dashboard_widget('volleytnt_iscrizione_rapida', __('Iscrizione rapida', 'volleytnt'), array( $this, 'iscrizione_rapida' ) );	
		
		
		wp_dashboard();
	}

	function iscrizione_rapida() {
		global $VolleyTNT;
		$VolleyTNT->call_page_method( 'volleytnt_squadre', 'edit', 0, true );
	} 

	
}
?>
