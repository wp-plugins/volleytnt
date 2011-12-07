<?php 
class VolleyTNT_Home extends VolleyTNT_AdminPage {
	
	public $menu_child_of = false;

	public function __construct() {
		$this->set_title( __("Volley TNT", 'volleytnt') ); 
		$this->add_case( 'firstpage' );
	}
	
	public function firstpage() {
		echo __METHOD__;
	}
	
}
?>
