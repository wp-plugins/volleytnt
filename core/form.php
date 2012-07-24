<?php 
class VolleyTNT_Form {
	private $id = '';
	private $method = '';
	private $action = false;
	private $elements = array();
	private $redirect = '';
	private $data = array();
	private $msgs = array();
	private static $cbs_save = array();
	
	public function __construct( $id, $action = false, $method = 'POST' ) {
		$this->id = $id;
		$this->action = $action;
		$this->method = $method;
		$this->redirect = add_query_arg( 'error_' . $id, null, $_SERVER['REQUEST_URI'] );
		$this->register_msg_code( 'generic', __("Si è verificato un errore imprevisto.", 'volleytnt' ) );
		$this->add_element( 'hidden', 'id' );
	}
	
	public static function register_cb_save( $form_id, $callback ) {
		if ( is_callable( $callback ) ) {
			self::$cbs_save[ $form_id ] = $callback;
			return true;
		} else return false;
	}
	
	public function register_msg_code( $errcode, $message, $type = 'error', $long_message = '' ) {
		$this->msgs[ $errcode ] = array( 'type' => $type, 'short' => $message, 'long' => $long_message );
	} 
	
	public static function manage_cbs_save() {
		foreach ( self::$cbs_save as $form_id => $callback ) {
			$req = false;
			if ( isset( $_GET[ $form_id ]['_nonce'] ) ) $req = $_GET[ $form_id ];
			if ( isset( $_POST[ $form_id ]['_nonce'] ) ) $req = $_POST[ $form_id ];
			if ( $req and wp_verify_nonce( $req['_nonce'], $form_id ) ) {
				$return = $req['_return'];
				$redirect = $req['_redirect'];
				unset( $req['_nonce'], $req['_return'], $req['_redirect'] );
				$exit_status = call_user_func( $callback, stripslashes_deep( $req ), &$req['id'] );
				if ( $exit_status === 0 ) {
					wp_safe_redirect( add_query_arg( 'param', $req['id'], $redirect ) );
				} else {
					wp_safe_redirect( add_query_arg( 'error_' . $form_id, $exit_status, $return ) );
				}
				die();
			}
		}
	}
	
	public function load( $data ) {
		$this->data = (array) $data;
	}
	
	public function set_redirect( $url ) {
		$this->redirect = $url;
	}
	
	public static function field( $type, $name, $id = false, $value = false, $params = false ) {
		if ( $id === false ) $id = preg_replace( '|[^\w]|i', '$', $name );
		switch ( $type ) {
			case 'select':
				$_ = '<select name="' . $name . '" id="' . $id . '">';
				if ( $params ) foreach ( $params as $k => $v ) {
					$_ .= '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $value, false ) . '>' . $v . '</option>';
				}
				return $_ . '</select>';
			case 'multiselect':
				$_ = array();
				$value = array_map( 'trim', explode( ',', $value ) );
				if ( $params ) foreach ( $params as $k => $v ) {
					$_[] = '<li><label><input type="checkbox"' . checked( in_array( $k, $value ), true, false ) . ' name="'. $name . '[]" value="' . esc_attr( $k ) . '">&nbsp;' . $v . '</label></li>';
				}
				return '<ul class="multiselect" id="' . $id . '">' . implode( '', $_ ) . '</ul>';
				break;
			case 'custom':
				return call_user_func( $params, $name, $id, $value );
				break;
			case 'hidden':
				return '<input type="hidden" name="' . $name . '" id="' . $id . '" value="' . esc_attr( $value ) . '">';
			case 'string':
			default:
				return '<input type="text" class="regular-text" name="' . $name . '" id="' . $id . '" value="' . esc_attr( $value ) . '">';
		}	
	}
	
	public function show( $short = false ) {
		if ( isset( $_GET['error_' . $this->id ] ) ) {
			$errcode = strval( $_GET['error_' . $this->id ] );
			if ( !isset( $this->msgs[ $errcode ] ) ) $errcode = 'generic';
			echo '<div class="' . $this->msgs[ $errcode ]['type'] . '"><p><strong>' . $this->msgs[ $errcode ]['short'] . '</strong>';
			if ( $this->msgs[ $errcode ]['long'] ) echo '<br />' . $this->msgs[ $errcode ]['long'];
			echo '</p></div>';
		}
		echo '<form class="volleytnt_form" method="' . $this->method . '"' . ( $this->action ? ' action="' . $this->action . '"' : '' ) . '>';
		echo VolleyTNT_Form::field( 'hidden', "{$this->id}[_nonce]", "{$this->id}_nonce", wp_create_nonce( $this->id ) );
		echo VolleyTNT_Form::field( 'hidden', "{$this->id}[_return]", "{$this->id}_return", $_SERVER['REQUEST_URI'] );
		echo VolleyTNT_Form::field( 'hidden', "{$this->id}[_redirect]", "{$this->id}_redirect", $this->redirect );
		if ( !$short ) echo '<table class="form-table"><tbody>';
		foreach ( $this->elements as $field ) {
			$value = isset( $this->data[ $field['name'] ] ) ? $this->data[ $field['name'] ] : '';
			if ( $short ) echo '<div class="shortform">'; 
			if ( !$short ) echo '<tr valign="top"><th scope="row">';
			echo '<label for="' . $this->id . '_' . $field['name'] . '">' . $field['label'] . '</label>';
			if ( !$short ) echo '</th><td>';
			echo VolleyTNT_Form::field( $field['type'], $this->id . '[' . $field['name'] . ']', $this->id . '_' . $field['name'], $value, $field['params'] );
			if ( !$short and $field['desc'] ) echo '<span class="description">' . $field['desc'] . '</span>';
			if ( !$short ) echo '</td></tr>';
			if ( $short ) echo '</div>';
		}		
		if ( !$short ) echo '</tbody></table>';
		echo '<p class="submit"><input class="button-primary" type="submit" value="' . esc_attr__( "Salva", 'volleytnt' ) . '"></p>';
		echo '</form>';
	}
	
	public function add_element( $type, $name, $label = '', $description = '', $params = false ) {
		$this->elements[] = array(	'type'		=> $type,
									'name'		=> $name,
									'label'		=> $label,
									'desc'		=> $description,
									'params'	=> $params );
	}
	
}
?>	
