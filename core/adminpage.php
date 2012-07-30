<?php 
abstract class VolleyTNT_AdminPage {
	public $menu_child_of = 'volleytnt_home';
	public $help_tabs = array();
	public $menubar_newlinks = array();
	public $js_files = array();
	public $js_strings = array();
	protected $title = '';
	protected $title_action = false;
	protected $brain_data = array();
	protected $init_triggers = array();
	
	public function _load() {}
	
	final protected function trigger( $method, $callback, $return_to = false ) {
		$this->init_triggers[] = array(	'method' 	=> $method, 
										'callback'	=> $callback, 
										'return_to' => $return_to );
	}
	
	final protected function localize_js_string( $slug, $string ) {
		$this->js_strings[ $slug ] = $string;
	}
	
	final protected function add_menubar_newlink( $url, $label ) {
		$this->menubar_newlinks[] = array( 'url' => $url, 'label' => $label );
	}
	
	final protected function load_js() {
		$dependencies = func_get_args();
		$filename = array_shift( $dependencies );
		if ( file_exists( VOLLEYTNT_PATH . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . $filename ) ) {
			$this->js_files[] = array( 'file' => $filename, 'dependencies' => $dependencies );
		}
	}
	
	final public function _page_load() {
		global $current_screen;
		$this->_load();
		if ( $this->help_tabs ) foreach ( $this->help_tabs as $help ) $current_screen->add_help_tab( $help );
	}
	
	final protected function add_help_tab( $title, $callback ) {
		static $counter = 0;
		$id = get_class( $this ) . '_' . ++$counter;
		if ( is_callable( $callback ) ) {
			$this->help_tabs[] = array( 'id' 		=> $id, 
										'title' 	=> $title, 
										'callback' 	=> $callback );
		}
	}
	
	final public function do_triggers() {
		if ( isset( $_GET['page'] ) and $_GET['page'] == strtolower( get_class( $this ) ) ) {
			foreach ( $this->init_triggers as $trigger ) {
				if ( is_callable( $trigger['callback'] ) and isset( $_GET['method'] ) and $_GET['method'] == $trigger['method'] ) {
					if ( call_user_func( $trigger['callback'] ) ) {
						if ( $trigger['return_to'] ) {
							wp_safe_redirect( $trigger['return_to'] );
							die();
						}
					}
				}
			}
		}
	}
	
	final protected function add_case( $method, $action_name = false, $param_filter_cb = 'absint' ) {
		if ( !is_callable( array( $this, $method ) ) ) return false;
		if ( !is_callable( $param_filter_cb ) ) return false;
		if ( !$action_name ) $action_name = $method;
		$this->brain_data[] = array(	'method'	=> $method,
										'action'	=> $action_name,
										'filter'	=> $param_filter_cb );
	}
	
	public function do_page() {
		$chosen = false;
		if ( isset( $_GET['method'] ) ) {
			foreach ( $this->brain_data as $perhaps ) {
				if ( $perhaps['action'] == $_GET['method'] ) {
					$chosen = $perhaps;
					break;
				}
			}
		}
		if ( !$chosen ) $chosen = $this->brain_data[ 0 ];
		$param = call_user_func( $chosen['filter'], isset( $_GET['param'] ) ? $_GET['param'] : false );
		return call_user_func( array( $this, $chosen['method'] ), $param );
	}
	
	final protected function url( $method, $param = false ) {
		return add_query_arg( array(	'page'		=> strtolower( get_class( $this ) ),
										'method'	=> $method,
										'param'		=> $param ), admin_url('admin.php') );
	}
	
	final protected function set_title_action( $url, $label ) {
		$this->title_action = array( 'url' => $url, 'label' => $label );
	}
	
	final public function get_title_action() {
		return $this->title_action ? '<a class="add-new-h2" href="' . $this->title_action['url'] . '">' . $this->title_action['label'] . '</a>' : '';
	}
	
	final protected function set_title( $title ) {
		$this->title = $title;
	}
	
	final public function get_title() {
		return $this->title;
	}
	
	final protected function enqueue_script( $filename, $target = 'admin' ) {
		global $VolleyTNT;
		if ( !in_array( $target, array( 'admin', 'site', 'both' ) ) ) $target = 'admin';
		$slug = preg_replace( '|[^a-z]|', '', $filename );
		$VolleyTNT->scripts[ $target ][ $slug ] = $filename;
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

	final protected function get_squadre( $id_torneo = false ) {
		global $wpdb;
		if ( !( $id_torneo = intval( $id_torneo ) ) ) $id_torneo = $this->opts->corrente;
		return $wpdb->get_results("SELECT `id`, `label`, `sesso`
											FROM `{$this->prefix}squadre`
											WHERE `tornei_id`=$id_torneo 
											ORDER BY `label` ASC" );
	}
}
?>