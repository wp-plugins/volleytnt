<?php 
class VolleyTNT_Finali extends VolleyTNT_AdminPage {
	
	public function __construct() {	
		$this->set_title( __("Finali", 'volleytnt') );
		
//		$this->add_help_tab( __("Inserimento risultati", 'volleytnt'), array( $this, 'help_risultati' ) );
		
		$this->load_js( 'admin_finali.js', 'jquery-ui-tabs' );
		
//		$this->localize_js_string( 'modifica', __( "Modifica", 'volleytnt' ) );

		$this->add_case( 'firstpage' );
		
//		$this->trigger( 'rigenerapartitegironi', array( $this, 'rigenera_partite_gironi' ), '?page=VolleyTNT_Partite' );
		
//		add_action( 'wp_ajax_volleytnt_salvapartita', 		array( $this, 'ajax_salvapartita' ) );
	}
	
	public function firstpage() {
		$tree = new VolleyTNT_Tree();
		$tree->show( 'M' );
	}
}
?>