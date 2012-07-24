<?php 
class VolleyTNT_Risultati extends VolleyTNT_AdminPage {
	
	public function __construct() {	
		$this->set_title( __("Risultati", 'volleytnt') );
		
		$this->add_help_tab( __("Inserimento risultati", 'volleytnt'), array( $this, 'help_risultati' ) );
		
		$this->load_js( 'admin_risultati.js', 'jquery-ui-tabs' );
		
		$this->localize_js_string( 'modifica', __( "Modifica", 'volleytnt' ) );
		$this->localize_js_string( 'salva', __( "Salva", 'volleytnt' ) );

		$this->add_case( 'firstpage' );
		
//		$this->trigger( 'rigenerapartitegironi', array( $this, 'rigenera_partite_gironi' ), '?page=VolleyTNT_Partite' );
		
		add_action( 'wp_ajax_volleytnt_salvapartita', 		array( $this, 'ajax_salvapartita' ) );
	}
	
	public function help_risultati() {
		echo '<p>';
		_e("In questa pagina si possono inserire i risultati degli incontri dei gironi eliminatori. Basta premere il pulsante \"Modifica\" e inserire i punteggi per ogni set; il sistema si occuperà di ogni altro calcolo.", 'volleytnt');
		echo  '</p>';
	}
	
	public function ajax_salvapartita() {
		global $wpdb;
		header('Content-Type: application/json');
		$_POST = stripslashes_deep( $_POST );
		$dati = array( 	'set1_sq1' => $_POST['set1_sq1'],
						'set1_sq2' => $_POST['set1_sq2'],
						'set2_sq1' => $_POST['set2_sq1'],
						'set2_sq2' => $_POST['set2_sq2'],
						'set3_sq1' => $_POST['set3_sq1'],
						'set3_sq2' => $_POST['set3_sq2'] );
						
		if ( $this->torneo->set_partita == 5 ) {
			$dati2 = array( 'set4_sq1' => $_POST['set4_sq1'],
							'set4_sq2' => $_POST['set4_sq2'],
							'set5_sq1' => $_POST['set5_sq1'],
							'set5_sq2' => $_POST['set5_sq2'] );
		} else {
			$dati2 = array( 'set4_sq1' => 0,
							'set4_sq2' => 0,
							'set5_sq1' => 0,
							'set5_sq2' => 0 );
		}
		$dati = array_map( 'absint', array_merge( $dati, $dati2 ) );
		$wpdb->update( "{$this->prefix}partite", $dati, array( 'id' => absint( $_POST['id_partita'] ) ) );
		$row = $wpdb->get_row( $wpdb->prepare("SELECT `set1_sq1`, `set1_sq2`, `set2_sq1`, `set2_sq2`, `set3_sq1`, `set3_sq2`, `set4_sq1`, `set4_sq2`, `set5_sq1`, `set5_sq2` FROM `{$this->prefix}partite` WHERE `id`=%d", $_POST['id_partita'] ), ARRAY_A );
		$row = (object) array_map( 'absint', $row );
		
		$set1 = $set2 = 0;
		if ( $row->set1_sq1 or $row->set1_sq2 ) if ( $row->set1_sq1 > $row->set1_sq2 ) $set1++; else $set2++;
		if ( $row->set2_sq1 or $row->set2_sq2 ) if ( $row->set2_sq1 > $row->set2_sq2 ) $set1++; else $set2++;
		if ( $row->set3_sq1 or $row->set3_sq2 ) if ( $row->set3_sq1 > $row->set3_sq2 ) $set1++; else $set2++;
		if ( $this->torneo->set_partita == 5 ) {
			if ( $row->set4_sq1 or $row->set4_sq2 ) if ( $row->set4_sq1 > $row->set4_sq2 ) $set1++; else $set2++;
			if ( $row->set5_sq1 or $row->set5_sq2 ) if ( $row->set5_sq1 > $row->set5_sq2 ) $set1++; else $set2++;
		}
		
		if ( !$row->set1_sq1 and !$row->set1_sq2 ) $row->set1_sq1 = $row->set1_sq2 = '';
		if ( !$row->set2_sq1 and !$row->set2_sq2 ) $row->set2_sq1 = $row->set2_sq2 = '';
		if ( !$row->set3_sq1 and !$row->set3_sq2 ) $row->set3_sq1 = $row->set3_sq2 = '';
		if ( !$row->set4_sq1 and !$row->set4_sq2 ) $row->set4_sq1 = $row->set4_sq2 = '';
		if ( !$row->set5_sq1 and !$row->set5_sq2 ) $row->set5_sq1 = $row->set5_sq2 = '';
		if ( !$set1 and !$set2 ) $set1 = $set2 = '';
		$row->squadra1 = $set1;
		$row->squadra2 = $set2;
		echo json_encode( $row );
		die();
	}
	
	public function firstpage() {
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
									WHERE `{$this->prefix}partite`.`tornei_id` = {$this->opts->corrente}
										AND `{$this->prefix}partite`.`girone` <> 0
										AND `{$this->prefix}partite`.`visibile` = 1
									ORDER BY `{$this->prefix}partite`.`categoria` ASC, `{$this->prefix}partite`.`girone` ASC");
		
		echo '<div class="tabbor"><ul>';
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<li><a href="#tab' . $categoria . '">' . $this->l_categorie[ $categoria ] . '</a></li>';
		}
		echo '</ul>';
		
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<div id="tab' . $categoria . '" class="gironedit">';
			echo '<table class="widefat">';
			echo '<thead><tr>';
			echo '<th>' . __('Partita', 'volleytnt') . '</th>';
			echo '<th>' . __('Squadre', 'volleytnt') . '</th>';
			echo '<th>' . __('Risultato', 'volleytnt') . '</th>';
			echo '<th>' . sprintf( __('%d&ordm; set', 'volleytnt'), 1 ) . '</th>';
			echo '<th>' . sprintf( __('%d&ordm; set', 'volleytnt'), 2 ) . '</th>';
			echo '<th>' . sprintf( __('%d&ordm; set', 'volleytnt'), 3 ) . '</th>';
			if ( $this->torneo->set_partita == 5 ) {
				echo '<th>' . sprintf( __('%d&ordm; set', 'volleytnt'), 4 ) . '</th>';
				echo '<th>' . sprintf( __('%d&ordm; set', 'volleytnt'), 5 ) . '</th>';
			}
			echo '<th>&nbsp;</th>';
			echo '</tr></thead>';
			echo '<tbody>';
			if ( $dati ) foreach ( $dati as $row ) if ( $row->categoria == $categoria ) {
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
				echo '<tr id="partita' . $row->id . '">';
				
				echo '<th>';
				printf( __('Girone %s', 'volleytnt'), $row->categoria . $row->girone );
				if ( $row->campo ) echo '<br/>' . sprintf( __('%s, %s-%s, campo %s', 'volleytnt'), $row->giorno, $row->inizio, $row->fine, $row->campo );
				echo '</th>';
				
				echo '<td>';
				echo '<div class="squadra">' . $row->nome1 . '</div>';
				echo '<div class="squadra">' . $row->nome2 . '</div>';
				echo '</td>';
				
				echo '<td>';
				echo '<div class="risultato" campo="squadra1">' . $set1 . '</div>';
				echo '<div class="risultato" campo="squadra2">' . $set2 . '</div>';
				echo '</td>';
				
				echo '<td>';
				echo '<div class="riga" campo="set1_sq1">' . $row->set1_sq1 . '</div>';
				echo '<div class="riga" campo="set1_sq2">' . $row->set1_sq2 . '</div>';
				echo '</td>';
				
				echo '<td>';
				echo '<div class="riga" campo="set2_sq1">' . $row->set2_sq1 . '</div>';
				echo '<div class="riga" campo="set2_sq2">' . $row->set2_sq2 . '</div>';
				echo '</td>';
				
				echo '<td>';
				echo '<div class="riga" campo="set3_sq1">' . $row->set3_sq1 . '</div>';
				echo '<div class="riga" campo="set3_sq2">' . $row->set3_sq2 . '</div>';
				echo '</td>';
				
				if ( $this->torneo->set_partita == 5 ) {
					echo '<td>';
					echo '<div class="riga" campo="set4_sq1">' . $row->set4_sq1 . '</div>';
					echo '<div class="riga" campo="set4_sq2">' . $row->set4_sq2 . '</div>';
					echo '</td>';
				
					echo '<td>';
					echo '<div class="riga" campo="set5_sq1">' . $row->set5_sq1 . '</div>';
					echo '<div class="riga" campo="set5_sq2">' . $row->set5_sq2 . '</div>';
					echo '</td>';
				}
				
				echo '<td><p><a class="button modsalvpart" id_partita="' . $row->id . '">' . __('Modifica', 'volleytnt') . '</a></p></td>';
				
				echo '<tr>';
			}
			echo '</tbody>';
			echo '</table>';
			
			echo '</div>';
		}

	}
	
}
?>