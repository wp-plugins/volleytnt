<?php 
class VolleyTNT_Gironi extends VolleyTNT_AdminPage {
	
	public function __construct() {	
		$this->set_title( __("Gironi", 'volleytnt') );
		
		$this->add_help_tab( __("Composizione gironi", 'volleytnt'), array( $this, 'help_gironi' ) );
		
		$this->load_js( 'admin_gironi.js', 'jquery-ui-tabs', 'jquery-ui-sortable' );
		
		$this->localize_js_string( 'girone', __("Girone", 'volleytnt') );

		$this->add_case( 'firstpage' );
		
		add_action( 'wp_ajax_volleytnt_gironi', 		array( $this, 'ajax_gironi' ) );
	}
	
	public function ajax_gironi() {
		global $wpdb;
		header('Content-Type: application/json');
		if ( !isset( $_GET['squadra'], $_GET['categoria'], $_GET['girone'] ) ) die( json_encode( false ) );
		$squadra = absint( $_GET['squadra'] );
		$girone = absint( $_GET['girone'] );
		$categoria = strtoupper( $_GET['categoria'] );
		if ( !$squadra or !in_array( $categoria, array( 'M', 'F', 'X' ) ) ) die( json_encode( false ) );
		if ( $girone ) {
			$wpdb->query("INSERT INTO `{$this->prefix}gironi` SET `girone`=$girone, `categoria`='$categoria', `squadre_id`=$squadra, `tornei_id`={$this->opts->corrente} ON DUPLICATE KEY UPDATE `girone`=$girone");
		} else {
			$wpdb->query("DELETE FROM `{$this->prefix}gironi` WHERE `categoria`='$categoria' AND `squadre_id`=$squadra AND `tornei_id`={$this->opts->corrente}");
		}
		die( json_encode( true ) );
	}
	
	public function help_gironi() {
		echo '<p>';
		_e("Un torneo può avere infiniti gironi per ogni categoria: premere il pulsante \"aggiungi girone\" per crearne uno nuovo (i gironi senza squadre non verranno salvati). Per inserire una squadra in un girone basta trascinarcela sopra.", 'volleytnt');
		echo  '</p>';
	}
	public function firstpage() {
		global $wpdb;
		
		$gironi = array();
		$tmp = $wpdb->get_results("	SELECT 
										`{$this->prefix}gironi`.`girone`,
										`{$this->prefix}gironi`.`squadre_id`,
										`{$this->prefix}squadre`.`label`,
										`{$this->prefix}gironi`.`categoria`
									FROM 
										`{$this->prefix}gironi`
									LEFT JOIN `{$this->prefix}squadre`
										ON `{$this->prefix}squadre`.`id`=`{$this->prefix}gironi`.`squadre_id`
									WHERE
										`{$this->prefix}gironi`.`tornei_id`={$this->opts->corrente}
									ORDER BY
										`{$this->prefix}gironi`.`girone` ASC, `{$this->prefix}squadre`.`label` ASC");	
		if ( $tmp ) foreach ( $tmp as $row ) $gironi[ $row->categoria ][ $row->girone ][ $row->squadre_id ] = $row->label;

		echo '<div id="vtnt_tabgironi"><ul>';
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<li><a href="#tab' . $categoria . '">' . $this->l_categorie[ $categoria ] . '</a></li>';
		}
		echo '</ul>';
		
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<div id="tab' . $categoria . '" class="gironedit">';
			$disp = $wpdb->get_results("SELECT `{$this->prefix}squadre`.`id`, `{$this->prefix}squadre`.`label`
										FROM `{$this->prefix}squadre`
										LEFT JOIN `{$this->prefix}gironi` ON `{$this->prefix}gironi`.`squadre_id`=`{$this->prefix}squadre`.`id`
										WHERE `{$this->prefix}squadre`.`tornei_id`={$this->opts->corrente} AND `{$this->prefix}squadre`.`categoria`='$categoria' AND `{$this->prefix}gironi`.`girone` IS NULL
										ORDER BY `{$this->prefix}squadre`.`label` ASC");
			echo '<div class="disponibili"><h4>Squadre disponibili:</h4>';
			echo '<ul class="girone ui-corner-all ui-widget-content" id="disponibili' . $categoria . '" categoria="' . $categoria . '" girone="0">';
			if ( $disp ) foreach ( $disp as $row ) {
				echo '<li squadra="' . $row->id . '" class="ui-corner-all ui-widget-content">' . $row->label . '</li>';
			}
			echo '</ul></div>';
			echo '<div id="categoria' . $categoria . '">';
			if ( isset( $gironi[ $categoria ] ) ) foreach ( $gironi[ $categoria ] as $id_girone => $squadre ) {
				echo '<h4>Girone ' . $id_girone . '</h4>';
				echo '<ul class="girone ui-corner-all ui-widget-content" categoria="' . $categoria . '" girone="' . $id_girone . '">';
				foreach ( $squadre as $id_sq => $lab_sq ) {
					echo '<li squadra="' . $id_sq . '" class="ui-corner-all ui-widget-content">' . $lab_sq . '</li>';
				}
				echo '</ul>';
			}
			echo '</div>';
			echo '<p><a class="button aggiungigirone" categoria="' . $categoria . '">Aggiungi girone</a></p>';
			echo '<br class="clearer" />';
			echo '</div>';
		}
		echo '</div>';
	}
}
?>
