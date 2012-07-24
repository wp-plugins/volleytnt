<?php 
class VolleyTNT_Squadre extends VolleyTNT_AdminPage {
	
	public function __construct() {	
		$this->set_title( __("Squadre", 'volleytnt') );
		$this->set_title_action( $this->url('edit'), __("Nuova squadra", 'volleytnt') );
		$this->add_menubar_newlink( $this->url('edit'), __("Iscrizione squadra", 'volleytnt') );
		
		$this->add_help_tab( __("Elenco squadre", 'volleytnt'), array( $this, 'help_elenco' ) );
		$this->add_help_tab( __("Inserimento squadra", 'volleytnt'), array( $this, 'help_form' ) );
		
		$this->load_js( 'admin_squadre.js', 'jquery-ui-tabs', 'jquery-ui-autocomplete', 'jquery-ui-datepicker' );
		
		$this->localize_js_string( 'conferma_elimina_squadra', __("Si vuole realmente cancellare questa squadra dal torneo?", 'volleytnt') );

		$this->add_case( 'firstpage' );
		$this->add_case( 'edit' );
		
		VolleyTNT_Form::register_cb_save( 'vtntfrm_squadra', array( $this, 'salva_squadra') );
		
		add_action( 'wp_ajax_volleytnt_acatleti', 		array( $this, 'ajax_ac_atleti' ) );
	}
	
	public function help_elenco() {
		echo '<p>';
		_e("L'elenco delle squadre iscritte presenta la composizione di tutte le compagini iscritte al torneo in corso. Per ogni atleta sono mostrati i dati di contatto, la sua situazione di pagamento e se ha firmato la manleva di responsabilità.", 'volleytnt');
		echo  '</p>';
	}
	
	public function help_form() {
		echo '<p>';
		_e("Ogni squadra è caratterizzata da un nome di riferimento e può venire iscritta in una sola categoria. Nel caso i componenti abbiano problemi di presenza è possibile specificare degli orari prima o dopo dei quali la squadra non può partecipare a partite.", 'volleytnt');
		echo  '</p>';
		echo '<p>';
		_e("La squadra può essere composta da un qualsiasi numero di atleti. L'anagrafica degli atleti è condivisa tra tutti i tornei, e in fase di inserimento è possibile cercare rapidamente atleti già registrati in passato mediante il cognome. La situazione di pagamento e di manleva è riferita alla partecipazione nella squadra corrente (un atleta può risultare pagante e manlevato in una squadra e non in un'altra).", 'volleytnt');
		echo  '</p>';
	}
	
	public function ajax_ac_atleti() {
		global $wpdb;
		if ( isset( $_GET['term'] ) and $_GET['term'] ) {
			echo json_encode( $wpdb->get_results( $wpdb->prepare("SELECT *, `cognome` AS label FROM `{$this->prefix}atleti` WHERE `cognome` LIKE %s", "%{$_GET['term']}%" ) ) );
		} else {
			echo json_encode( array() );
		}
		die();
	}
	
	public function firstpage() {
		global $wpdb;
		echo '<h3>' . __("Situazione iscrizioni", 'volleytnt') . '</h3>';
		$squadre = $wpdb->get_results("SELECT `id`, `label`, `categoria` FROM `{$this->prefix}squadre` WHERE `tornei_id`={$this->opts->corrente} ORDER BY `label` ASC" );

		$tmp = $wpdb->get_results("
			SELECT 
				`{$this->prefix}atleti`.*,
				`{$this->prefix}squadre_atleti`.`squadre_id`,
				`{$this->prefix}squadre_atleti`.`pagato`,
				`{$this->prefix}squadre_atleti`.`manleva` 
			FROM 
				`{$this->prefix}squadre`
			LEFT JOIN `{$this->prefix}squadre_atleti`
				ON `{$this->prefix}squadre_atleti`.`squadre_id`=`{$this->prefix}squadre`.`id`
			LEFT JOIN `{$this->prefix}atleti`
				ON `{$this->prefix}squadre_atleti`.`atleti_id`=`{$this->prefix}atleti`.`id`
			WHERE 
				`{$this->prefix}squadre`.`tornei_id`={$this->opts->corrente} 
			ORDER BY 
				`{$this->prefix}atleti`.`cognome` ASC,
				`{$this->prefix}atleti`.`nome` ASC" );
		$partecipanti = array();
		if( $tmp ) foreach ( $tmp as $row ) $partecipanti[ $row->squadre_id ][] = $row;

		echo '<div id="vtnt_tabiscrizioni">';
		echo '<ul>';
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<li><a href="#tab' . $categoria . '">' . $this->l_categorie[ $categoria ] . '</a></li>';
		}
		echo '</ul>';
		
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<div id="tab' . $categoria . '">';
			echo '<table class="widefat">';
			echo '<thead><tr>';
			$headers = '<th>' . __("Squadra", 'volleytnt' ) . '</th><th>' . __("Giocatore", 'volleytnt' ) . '</th><th>' . __("Telefono", 'volleytnt' ) . '</th><th>' . __("E-mail", 'volleytnt' ) . '</th><th>' . __("Pagato", 'volleytnt' ) . '</th><th>' . __("Manleva", 'volleytnt' ) . '</th>';
			echo $headers;
			echo '</tr><thead><tbody>';
			$conta_squadre = $conta_persone = 0;
			foreach ( $squadre as $sq ) if ( $sq->categoria == $categoria and isset( $partecipanti[ $sq->id ] ) ) {
				$stampato = false;
				$conta_squadre++;
				foreach ( $partecipanti[ $sq->id ] as $atl ) {
					$conta_persone++;
					echo ( $conta_squadre % 2 ) ? '<tr class="alternate">' : '<tr>';
					if ( !$stampato ) {
						echo '<td rowspan="' . count( $partecipanti[ $sq->id ] ) . '">';
						echo '<strong>' . $sq->label . '</strong>';


						echo '<div class="row-actions">';
						
						echo '<span class="standard">';
						echo '<a href="' . $this->url( 'edit',  $sq->id ) . '">' . __("Modifica", 'volleytnt') . '</a>';
						echo '</span>';
						echo ' | ';
						echo '<span class="delete">';
						echo '<a href="' . $this->url( 'delete',  $sq->id ) . '" onClick="return confirm(volleytnt.conferma_elimina_squadra);">' . __("Elimina", 'volleytnt') . '</a>';
						echo '</span>';
						echo '</div>';
					
						echo '</td>';
						$stampato = true;
					}
					echo '<td><strong>' . $atl->cognome . '</strong> ' . $atl->nome . '</td>';
					echo '<td>' . $atl->telefono . '</td>';
					echo '<td>' . $atl->mail. '</td>';
					echo '<td>' . ( ( $atl->pagato ) ? __( 'Sì', 'volleytnt' ) : '&nbsp;' ) . '</td>';
					echo '<td>' . ( ( $atl->manleva ) ? __( 'Sì', 'volleytnt' ) : '&nbsp;' ) . '</td>';
					echo '</tr>';	
				}
			}
			echo '</tbody><tfoot>' . $headers . '</tfoot></table>';
			echo '<p><strong>' . __("Squadre:", 'volleytnt' ) . '</strong> ' . number_format_i18n( $conta_squadre ) . '<br/>';
			echo '<strong>' . __("Atleti:", 'volleytnt' ) . '</strong> ' . number_format_i18n( $conta_persone ) . '</p>';
			
			echo '</div>';
		}		
	}

	public function salva_squadra( $data ) {
		global $wpdb;
		if ( $id_squadra = intval( $data['id'] ) ) {
			$wpdb->update( "{$this->prefix}squadre", array( 'label' => $data['label'], 'categoria' => $data['categoria'], 'tornei_id' => $this->opts->corrente ), array( 'id' => $id_squadra ) );
		} else {
			$wpdb->insert( "{$this->prefix}squadre", array( 'label' => $data['label'], 'categoria' => $data['categoria'], 'tornei_id' => $this->opts->corrente ) );
			$id_squadra = $wpdb->insert_id;
		}
		$wpdb->query("DELETE FROM `{$this->prefix}squadre_atleti` WHERE `squadre_id`=$id_squadra");
		$wpdb->query("DELETE FROM `{$this->prefix}impossibilita` WHERE `squadre_id`=$id_squadra");
		if ( isset( $data['atleti']['cognome'] ) and $data['atleti']['cognome'] ) foreach ( $data['atleti']['cognome'] as $i => $cognome ) {
			$atleta = array('cognome' 	=> $cognome,
							'nome'		=> $data['atleti']['nome'][ $i ],
							'telefono' 	=> $data['atleti']['telefono'][ $i ],
							'mail' 		=> $data['atleti']['mail'][ $i ] );
			
			$id_atleta = intval( $data['atleti']['id'][ $i ] );
			
			if ( $id_atleta ) {
				$wpdb->update( "{$this->prefix}atleti", $atleta, array( 'id' => $id_atleta ) );
			} else {
				$wpdb->insert( "{$this->prefix}atleti", $atleta );
				$id_atleta = $wpdb->insert_id;
			}
			
			$wpdb->insert( "{$this->prefix}squadre_atleti", array(	'pagato'	=> $data['atleti']['pagato'][ $i ],
																	'manleva' 	=> $data['atleti']['manleva'][ $i ],
																	'squadre_id'=> $id_squadra,
																	'atleti_id'	=> $id_atleta ) );
		}
		
		if ( isset( $data['impossibilita']['giorno'] ) and $data['impossibilita']['giorno'] ) foreach ( $data['impossibilita']['giorno'] as $i => $gi ) {
			$riga = array(	'squadre_id'	=> $id_squadra,
							'giorno'		=> DateTime::createFromFormat( volleytnt_date_format(), $data['impossibilita']['giorno'][ $i ] )->format('Y-m-d'),
							'primadopo'		=> $data['impossibilita']['primadopo'][ $i ],
							'ora'			=> $data['impossibilita']['hh'][ $i ] . ':' . $data['impossibilita']['mm'][ $i ] . ':00' );
			$wpdb->insert( "{$this->prefix}impossibilita", $riga );
		}
		return 0;
		die();
	}

	public function form_indisponibilita( $name, $id, $impossibilita ) {
		$this->workaround_indisponibilita = $name;
		ob_start();
		echo '<div id="elenco_impossibilita">';
		if ( $impossibilita ) foreach ( $impossibilita as $row ) call_user_func_array( array( $this, 'riga_impossibilita' ), $row );
		echo '</div><p><a id="aggiungi_impossibilita" class="button">' . __("Aggiungi", 'volleytnt') . '</a></p>';
		return ob_get_clean();
	}
	
	public function form_atleti( $name, $id, $atleti ) {
		$this->workaround_atleti = $name;
		echo '<table class="widefat" id="squadra">';
		echo '<thead><tr><th>' . __("Cognome", 'volleytnt' ) . '</th><th>' . __("Nome", 'volleytnt' ) . '</th><th>' . __("Telefono", 'volleytnt' ) . '</th><th>' . __("E-mail", 'volleytnt' ) . '</th><th>' . __("Pagato", 'volleytnt' ) . '</th><th>' . __("Manleva", 'volleytnt' ) . '</th><th>&nbsp;</th></tr></thead>';
		echo '<tbody id="squadra">';
		foreach ( $atleti as $row ) call_user_func_array( array( $this, 'riga_persona' ), $row );
		echo '</tbody>';
		echo '<tfoot><tr><th colspan="7"><a class="button" id="aggiungi_atleta">' . __("Aggiungi un atleta", 'volleytnt') . '</a></th></tr></tfoot>';
		echo '</table>';
	}
	
	public function form_atleti_short( $name, $id, $atleti ) {
		$this->workaround_atleti = $name;
		echo '<table class="widefat" id="squadra">';
		echo '<thead><tr><th>' . __("Cognome", 'volleytnt' ) . '</th><th>' . __("Nome", 'volleytnt' ) . '</th><th>' . __("Telefono", 'volleytnt' ) . '</th><th>' . __("E-mail", 'volleytnt' ) . '</th><th>&nbsp;</th></tr></thead>';
		echo '<tbody id="squadra">';
		foreach ( $atleti as $row ) call_user_func_array( array( $this, 'riga_persona' ), $row );
		echo '</tbody>';
		echo '<tfoot><tr><th colspan="7"><a class="button" id="aggiungi_atleta">' . __("Aggiungi un atleta", 'volleytnt') . '</a></th></tr></tfoot>';
		echo '</table>';
	}

	public function edit( $id_squadra, $short_form = false ) {
		global $wpdb;
		if ( $id_squadra ) {
			if ( !$short_form ) echo '<h3>' . __("Modifica iscrizione", 'volleytnt') . '</h3>';			
			$atleti = $wpdb->get_results("
				SELECT 
					`{$this->prefix}atleti`.`id`,
					`{$this->prefix}atleti`.`cognome`,
					`{$this->prefix}atleti`.`nome`,
					`{$this->prefix}atleti`.`telefono`,
					`{$this->prefix}atleti`.`mail`,
					`{$this->prefix}squadre_atleti`.`pagato`,
					`{$this->prefix}squadre_atleti`.`manleva` 
				FROM 
					`{$this->prefix}squadre_atleti`
				LEFT JOIN `{$this->prefix}atleti`
					ON `{$this->prefix}squadre_atleti`.`atleti_id`=`{$this->prefix}atleti`.`id`
				WHERE 
					`{$this->prefix}squadre_atleti`.`squadre_id`=$id_squadra", ARRAY_A );
			$impossibilita = $wpdb->get_results("
				SELECT 
					`id`, 
					DATE_FORMAT(`giorno`, '" . volleytnt_date_format( 'sql' ) . "') AS giorno, 
					`primadopo`, 
					DATE_FORMAT(`ora`, '%k') AS ho, 
					DATE_FORMAT(`ora`, '%i') AS mi 
				FROM 
					`{$this->prefix}impossibilita` 
				WHERE 
					`squadre_id`=$id_squadra
				ORDER BY
					`giorno` ASC", ARRAY_A);
			$squadra = $wpdb->get_row("SELECT * FROM `{$this->prefix}squadre` WHERE `id`=$id_squadra", ARRAY_A );
			$squadra['impossibilita'] = $impossibilita;
			$squadra['atleti'] = $atleti;
		} else {
			if ( !$short_form ) echo '<h3>' . __("Iscrivi nuova squadra", 'volleytnt') . '</h3>';
			$atleti = array();
			$impossibilita = array();
			$squadra = array( 'id' => 0, 'label' => '', 'categoria' => 'M', 'tornei_id' => $this->opts->corrente, 'impossibilita' => $impossibilita, 'atleti' => $atleti );
		}

		$form = new VolleyTNT_Form( 'vtntfrm_squadra' );
		$form->load( $squadra );
		$form->set_redirect( $this->url('firstpage') );
		$form->add_element( 'select', 'categoria', __("Categoria", 'volleytnt'), __("Categoria in cui iscrivere la squadra.", 'volleytnt'), $this->l_categorie );
		$form->add_element( 'string', 'label', __("Nome squadra", 'volleytnt'), __("Un nome di riferimento usato in tutte le stampe e gli elenchi.", 'volleytnt') );
		if ( !$short_form ) $form->add_element( 'custom', 'impossibilita', __("Orari non disponibili", 'volleytnt'), __("Gli orari in cui la squadra non può giocare, riferiti alla giornata di gioco. Orari dopo la mezzanotte ma prima delle 6 si considerano del giorno precedente.", 'volleytnt' ), array( $this, 'form_indisponibilita' ) );
		$form->add_element( 'custom', 'atleti', __("Atleti", 'volleytnt'), __("Dati anagrafici e informazioni di contatto dei componenti della squadra.", 'volleytnt' ), array( $this, $short_form ? 'form_atleti_short' : 'form_atleti' ) );
		
		$form->show( $short_form );		
				
		echo '<table id="blueprint" style="display:none;">';
		if ( $short_form ) $this->riga_persona_short(); else $this->riga_persona();		
		echo '</table>';
		
		echo '<div id="blueprint_impossibilita" style="display:none;">';
		$this->riga_impossibilita();
		echo '</div>';
	}

	
	private function riga_impossibilita( $id = '0', $giorno = '', $primadopo = 'prima', $ora = '20', $minuti = 0 ) {
		echo '<div class="impossibilita">';
		echo '<div class="remove ui-state-default ui-corner-all"><span class="ui-icon ui-icon-circle-close"></span></div>';
		echo '<input autocomplete="off" type="hidden" class="id" name="' . $this->workaround_indisponibilita . '[id][]" value="' . esc_attr( $id ) . '" />';
		echo '<input autocomplete="off" type="text" class="ggmmaa" name="' . $this->workaround_indisponibilita . '[giorno][]" value="' . esc_attr( $giorno ) . '" />';
		echo '<select autocomplete="off" class="primadopo" name="' . $this->workaround_indisponibilita . '[primadopo][]">';
		echo '<option value="prima"' . selected( $primadopo, 'prima', false ) . '>' . __("prima delle", 'volleytnt' ) . '</option>';
		echo '<option value="dopo"' . selected( $primadopo, 'dopo', false ) . '>' . __("dopo le", 'volleytnt' ) . '</option>';
		echo '</select>';
		echo '<input autocomplete="off" type="text" class="hh" name="' . $this->workaround_indisponibilita . '[hh][]" value="' . esc_attr( $ora ) . '" />';
		echo ':<input autocomplete="off" type="text" class="mm" name="' . $this->workaround_indisponibilita . '[mm][]" value="' . esc_attr( sprintf( '%02d', $minuti ) ) . '" />';
		echo '</div>';
	}
	
	private function riga_persona( $id = '0', $cognome = '', $nome = '', $tel = '', $mail = '', $pag = '0', $man = '0' ) {
		echo '<tr>';
		echo '<td><input autocomplete="off" type="text" field="cognome" name="' . $this->workaround_atleti . '[cognome][]" value="' . esc_attr( $cognome ) . '" /><input autocomplete="off" type="hidden" field="id" name="' . $this->workaround_atleti . '[id][]" value="' . esc_attr( $id ) . '" /></td>';
		echo '<td><input autocomplete="off" type="text" field="nome" name="' . $this->workaround_atleti . '[nome][]" value="' . esc_attr( $nome ) . '" ></td>';
		echo '<td><input autocomplete="off" type="text" field="telefono" name="' . $this->workaround_atleti . '[telefono][]" value="' . esc_attr( $tel ) . '" ></td>';
		echo '<td><input autocomplete="off" type="text" field="mail" name="' . $this->workaround_atleti . '[mail][]" value="' . esc_attr( $mail ) . '" ></td>';
		echo '<td><select autocomplete="off" field="pagato" name="' . $this->workaround_atleti . '[pagato][]"><option value="0"' . selected( $pag, '0', false ) . '>No</option><option value="1"' . selected( $pag, '1', false ) . '>Sì</option></select></td>';
		echo '<td><select autocomplete="off" field="manleva" name="' . $this->workaround_atleti . '[manleva][]"><option value="0"' . selected( $man, '0', false ) . '>No</option><option value="1"' . selected( $man, '1', false ) . '>Sì</option></select></td>';
		echo '<td class="remove ui-state-default ui-corner-all"><span class="ui-icon ui-icon-circle-close"></span></td>';
		echo '</tr>';
	}
	
	private function riga_persona_short( $id = '0', $cognome = '', $nome = '', $tel = '', $mail = '' ) {
		echo '<tr>';
		echo '<td><input autocomplete="off" type="text" field="cognome" name="' . $this->workaround_atleti . '[cognome][]" value="' . esc_attr( $cognome ) . '" /><input autocomplete="off" type="hidden" field="id" name="' . $this->workaround_atleti . '[id][]" value="' . esc_attr( $id ) . '" /></td>';
		echo '<td><input autocomplete="off" type="text" field="nome" name="' . $this->workaround_atleti . '[nome][]" value="' . esc_attr( $nome ) . '" ></td>';
		echo '<td><input autocomplete="off" type="text" field="telefono" name="' . $this->workaround_atleti . '[telefono][]" value="' . esc_attr( $tel ) . '" ></td>';
		echo '<td><input autocomplete="off" type="text" field="mail" name="' . $this->workaround_atleti . '[mail][]" value="' . esc_attr( $mail ) . '" ></td>';
		echo '<td class="remove ui-state-default ui-corner-all"><span class="ui-icon ui-icon-circle-close"></span></td>';
		echo '</tr>';
	}
	
}
?>