<?php 
class VolleyTNT_Opzioni extends VolleyTNT_AdminPage {
	
	public function __construct() {	
		$this->set_title( __("Opzioni e tornei", 'volleytnt') );
		$this->set_title_action( $this->url('edit'), __("Nuovo torneo", 'volleytnt') );
		
		$this->add_help_tab( __("Opzioni generali", 'volleytnt'), array( $this, 'help_generali' ) );
		$this->add_help_tab( __("Opzioni torneo", 'volleytnt'), array( $this, 'help_torneo' ) );
		$this->add_help_tab( __("Ore di gioco", 'volleytnt'), array( $this, 'help_timespan' ) );
		
		$this->add_case( 'firstpage' );
		$this->add_case( 'edit' );
		$this->add_case( 'timespan' );
		
		$this->load_js( 'admin_opzioni.js', 'jquery-ui-tabs', 'jquery-ui-datepicker' );
		
		$this->localize_js_string( 'conferma_elimina_torneo', __("Si vuole realmente cancellare questo torneo?", 'volleytnt' ) );
		
		VolleyTNT_Form::register_cb_save( 'vtntfrm_torneo', array( $this, 'salva_torneo') );
		VolleyTNT_Form::register_cb_save( 'vtntfrm_opzioni', array( $this, 'salva_opzioni') );
		VolleyTNT_Form::register_cb_save( 'vtntslots', array( $this, 'salva_slots') );
		
		$this->trigger( 'delete', array( $this, 'cancella_torneo' ), $this->url('firstpage') );
	}
	
	public function help_timespan() {
		echo '<p>';
		_e("VolleyTNT gestisce tornei che si sviluppano su più campi, ognuno dei quali può avere differenti disponibilità nei vari giorni di svolgimento. Quando si definiscono gli orari di disponibilità occorre specificare tutti gli intervalli per tutti i giorni di gioco a ogni campo. In caso un campo sia disponibile in due distinte fasce orarie, per esempio al mattino e alla sera, va inserito due volte.", 'volleytnt');
		echo  '</p>';
		echo '<p>';
		_e("Quando si effettua il salvataggio degli orari di gioco vengono creati gli slot partita della lunghezza impostata nelle preferenze del torneo. Gli slot verranno utilizzati in fase di stesura del calendario per fissare l'orario indicativo di svolgimento della partita.", 'volleytnt');
		echo '</p>';
		echo '<p>';
		_e("Nel caso in cui siano già state assegnate delle partite a degli slot temporali, la modifica degli orari di gioco è disabilitata. Occorre andare nella pagina delle Partite e svincolarle prima tutte.", 'volleytnt');
		echo '</p>';
	}
	
	public function help_generali() {
		echo '<p>';
		_e("VolleyTNT può gestire infinite manifestazioni, ma di volta in volta soltanto una può essere <em>in corso</em>: tutte le edizioni precedenti sono accessibili solo in visualizzazione e non in modifica. In questa sezione vengono definite delle opzioni di configurazione valide per ogni manifestazione", 'volleytnt');
		echo  '</p>';
	}
	
	public function help_torneo() {
		echo '<p>';
		_e("Ogni torneo può avere differenti configurazioni, che ne modificano lo svolgimento e la visualizzazione:", 'volleytnt');
		echo '</p><dl>';
		echo '<dt>' . __("Nome torneo", 'volleytnt') . '<dt>';
		echo '<dd>' . __("Il nome di riferimento dell'edizione, usata in tutte le visualizzazioni", 'volleytnt') . '</dd>';
		echo '<dt>' . __("Categorie", 'volleytnt') . '<dt>';
		echo '<dd>' . __("Quali sono le categorie giocate: Maschile (<strong>M</strong>), Femminile (<strong>F</strong>) e Misto (<strong>X</strong>).", 'volleytnt') . '</dd>';
		echo '<dt>' . __("Set partita", 'volleytnt') . '<dt>';
		echo '<dd>' . __("Il numero massimo di set giocati in ogni partita.", 'volleytnt') . '</dd>';
		echo '<dt>' . __("Campi", 'volleytnt') . '<dt>';
		echo '<dd>' . __("Il numero di campi disponibili per il torneo.", 'volleytnt') . '</dd>';
		echo '<dt>' . __("Durata turno", 'volleytnt') . '<dt>';
		echo '<dd>' . __("La durata media di un incontro in minuti, valore di riferimento usato per la pianificazione del calendario partite.", 'volleytnt') . '</dd>';
		echo '<dt>' . __("Finali", 'volleytnt') . '<dt>';
		echo '<dd>' . __("La prima fase giocata delle finali a eliminazione diretta.", 'volleytnt') . '</dd>';
		echo '</dl>';
	}

	public function translate_finali( $num ) {
		return $this->l_finali[ $num ];
	}

	public function translate_set_partita( $num ) {
		return $this->l_set_partita[ $num ];
	}

	public function translate_categorie( $cats ) {
		$return = array();
		foreach ( explode( ',', $cats ) as $cat ) $return[] = $this->l_categorie[ $cat ];
		return implode( ', ', $return );
	}
	
	public function salva_slots( $slots ) {
		global $wpdb;
		$torneo = $wpdb->get_row("SELECT * FROM `{$this->prefix}tornei` WHERE `id`={$slots['id']}");
		if ( $torneo ) {
			$wpdb->query("DELETE FROM `{$this->prefix}slots` WHERE `tornei_id`={$slots['id']}");
			$torneo->durata_turno = intval( $torneo->durata_turno );
			$tokens = array();
			if ( $slots['intervallo']['giorno'] ) foreach ( $slots['intervallo']['giorno'] as $i => $giorno ) {
				$giorno = DateTime::createFromFormat( volleytnt_date_format(), $giorno )->format('Y-m-d');
				$hh_i = intval( $slots['intervallo']['hh_inizio'][ $i ] );
				$mm_i = intval( $slots['intervallo']['mm_inizio'][ $i ] );
				$hh_f = intval( $slots['intervallo']['hh_fine'][ $i ] );
				$mm_f = intval( $slots['intervallo']['mm_fine'][ $i ] );
				$campo = intval( $slots['intervallo']['campo'][ $i ] );
				
				if ( $hh_f < 6 ) $hh_f += 24;
				
				$first = $hh_i * 60 + $mm_i;
				$last = $hh_f * 60 + $mm_f - $torneo->durata_turno;
				for ( $now = $first; $now <= $last; $now += $torneo->durata_turno ) {
					$h_i = floor( $now / 60 );
					$m_i = $now - ( $h_i * 60 );
					
					$h_f = floor( ( $now + $torneo->durata_turno ) / 60 );
					$m_f = $now + $torneo->durata_turno - ( $h_f * 60 );
					
					if ( $h_i >= 24 ) $h_i -= 24;
					if ( $h_f >= 24 ) $h_f -= 24;
					$tokens[] = "({$slots['id']},$campo,'$giorno','$h_i:$m_i:00','$h_f:$m_f:00')";
				}
				
			}
			
			if ( $tokens ) $wpdb->query("INSERT INTO `{$this->prefix}slots` (`tornei_id`,`campo`,`giorno`,`inizio`,`fine`) VALUES " . implode( ', ', $tokens ) );
			return 0;
		} else {
			return 1;
		}
	}
	
	private function form_timespan( $id ) {
		global $wpdb;
		if ( $wpdb->get_var("SELECT COUNT(`id`) FROM `{$this->prefix}partite` WHERE `tornei_id`={$id} AND `slots_id`<>0" ) ) {
			echo '<p>' . __("Ci sono partite già assegnate agli slot di questo torneo: per modificare gli slot occorre prima svincolare tutte le partite.", 'volleytnt' ) . '</p>';
		} else {		
			echo '<p><strong>' . __( "Attenzione!", 'volleytnt') . '</strong> ' . __("Impostare nuovi slot cancellerà gli eventuali slot precedentemente inseriti per il torneo corrente!", 'volleytnt' ) . '</p>';

			$form = new VolleyTNT_Form( 'vtntslots' );
			$form->register_msg_code( 1, __("Impossibile effettuare il salvataggio: torneo inesistente.", 'volleytnt') );
			$form->load( array( 'id' => $id, 'intervallo' => false ) );
			$form->add_element( 'custom', 'intervallo', __("Orari di gioco:", 'volleytnt'), __("Le disponibilità orarie dei campi da gioco. Le ore da mezzanotte alle 6 sono considerate appartenenti al giorno precedente.", 'volleytnt'), create_function( '', 'return \'<ol id="elenco_intervalli"></ol><p><a class="button" id="aggiungiintervallo">' . __("Aggiungi ore di gioco", 'volleytnt' ) . '</a></p>\';' ) );
			$form->show();

			$fr_g = '<input autocomplete="off" type="text" class="ggmmaa" name="vtntslots[intervallo][giorno][]"/>';
			$fr_da = '<input autocomplete="off" type="text" class="hh" name="vtntslots[intervallo][hh_inizio][]"/>:<input autocomplete="off" type="text" class="mm" name="vtntslots[intervallo][mm_inizio][]"/>';
			$fr_a = '<input autocomplete="off" type="text" class="hh" name="vtntslots[intervallo][hh_fine][]"/>:<input autocomplete="off" type="text" class="mm" name="vtntslots[intervallo][mm_fine][]"/>';
			$fr_cmp = '<select autocomplete="off" class="campo" name="vtntslots[intervallo][campo][]">';
			for ( $i = 1; $i <= $this->torneo->campi; $i++ ) $fr_cmp .= '<option value="' . $i . '">' . sprintf( __('Campo %d', 'volleytnt' ), $i ) . '</option>';
			$fr_cmp .= '</select>';
			
			echo '<ol id="blueprint_intervallo" style="display:none;"><li>';
			printf( __("Giorno %s, dalle %s alle %s, %s", 'volleytnt'), $fr_g, $fr_da, $fr_a, $fr_cmp );
			echo '</li></ol>';
		
		}
	}
	
	public function timespan( $id ) {
		global $wpdb;
		$torneo = $wpdb->get_row("SELECT * FROM `{$this->prefix}tornei` WHERE `id`=$id");
		if ( $torneo ) {
			echo '<h3>';
			printf( __('Orari di gioco per <u>%s</u>', 'volleytnt'), $torneo->label );
			echo '</h3>';

			$this->form_timespan( $id );
			
			$slots = $wpdb->get_results("	SELECT
												`id`,
												DATE_FORMAT(`giorno`, '" . volleytnt_date_format('sql') . "') AS giorno,
												DATE_FORMAT(`inizio`, '%k:%i') AS inizio,
												DATE_FORMAT(`fine`, '%k:%i') AS fine,
												`campo`,
												60*(IF( DATE_FORMAT(`inizio`,'%k')<6, DATE_FORMAT(`inizio`,'%k')+24, DATE_FORMAT(`inizio`,'%k')))+DATE_FORMAT(`inizio`,'%i') AS ordo
											FROM 
												`{$this->prefix}slots` 
											WHERE 
												`tornei_id`={$id} 
											ORDER BY 
												`{$this->prefix}slots`.`giorno` ASC, 
												`campo` ASC, 
												ordo ASC");
	
			if ( $slots ) {
				$inizi = $campi = $giorni = array();
				foreach ( $slots as $slot ) {
					$inizi[ $slot->ordo ] = $slot->inizio;
					$campi[] = $slot->campo;
					$giorni[] = $slot->giorno;
				}
				ksort( $inizi );
				$campi = array_unique( $campi );
				$giorni = array_unique( $giorni );
				$width = number_format( 100 / count( $campi ), 2 );
				$conto = 0;
				echo '<div id="vtnt_tabgiorni">';
				echo '<ul>';
				foreach ( $giorni as $giorno ) {
					echo '<li><a href="#tab' . trim( base64_encode( $giorno ), '=' ) . '">' . $giorno . '</a></li>';
				}
				echo '</ul>';
				foreach ( $giorni as $giorno ) {
					echo '<div id="tab' . trim( base64_encode( $giorno ), '=' ) . '">';
					echo '<table class="widefat">';
					echo '<thead><tr>';
					foreach ( $campi as $campo ) echo '<th width="' . $width . '%">' . sprintf( __( "Campo %d", 'volleytnt' ), $campo ) . '</th>';
					echo '</tr></thead>';
					echo '<tbody>';
					foreach ( $inizi as $inizio ) {
						echo '<tr>';
						foreach ( $campi as $campo ) {
							$stampato = false;
							foreach ( $slots as $slot ) {
								if ( $slot->campo == $campo and $slot->inizio == $inizio and $slot->giorno == $giorno ) {
									echo '<td class="slot">';
									echo '<div class="numpartita">' . sprintf( __( "Partita n° %d", 'volleytnt' ), ++$conto ) . '</div>';
									echo $slot->inizio . ' - ' . $slot->fine;
									echo '</td>';
									$stampato = true;
								}
							}
							if ( !$stampato ) echo '<td class="vuoto">&nbsp;</td>';
						}
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
					echo '</div>';
				}
			} else {
				echo '<p>Nessuno slot definito!</p>';
			}
		} else {
			echo '<p>';
			_e("Torneo inesistente.", 'volleytnt');
			echo '</p>';
		}
	}
	
	public function firstpage() {
		
		echo '<h3>' . __("Tornei gestiti", 'volleytnt') . '</h3>';
		$cols = array(	'label'			=> __("Nome torneo", 'volleytnt'),
						'categorie'		=> __("Categorie", 'volleytnt' ),
						'set_partita'	=> __("Set partita", 'volleytntn' ),
						'campi'			=> __("Campi", 'volleytnt' ),
						'durata_turno'	=> __("Durata turno", 'volleytnt'),
						'finali'		=> __("Finali", 'volleytnt' ) );
		$table = new VolleyTNT_Table( 'vtnt_opts', $cols, "SELECT * FROM `{$this->prefix}tornei` ORDER BY `id` ASC" );
		$table->add_action( $this->url( 'edit', '%id%' ), __("Modifica", 'volleytnt') );
		$table->add_action( $this->url( 'timespan', '%id%' ), __("Giorni e ore", 'volleytnt') );
		$table->add_action( $this->url( 'delete', '%id%' ), __("Elimina", 'volleytnt'), 'delete', 'conferma_elimina_torneo' );
		$table->add_filter( 'finali', array( $this, 'translate_finali' ) );
		$table->add_filter( 'set_partita', array( $this, 'translate_set_partita' ) );
		$table->add_filter( 'categorie', array( $this, 'translate_categorie' ) );
		$table->add_filter( 'durata_turno', create_function( '$a', 'return "$a\'";' ) );
		$table->show();
		
		echo '<h3>' . __("Opzioni generali", 'volleytnt') . '</h3>';
		$form = new VolleyTNT_Form( 'vtntfrm_opzioni' );
		$form->load( $this->opts );
		$form->set_redirect( $this->url('firstpage') );
		$form->add_element( 'string', 'nome', __("Nome manifestazione", 'volleytnt'), __("Il nome generico della manifestazione, che comprenderà tutte le edizioni gestite.", 'volleytnt') );
		$form->add_element( 'select', 'corrente', __("Torneo corrente", 'volleytnt'), __("Il torneo in corso, le cui partite sono le sole modificabili.", 'volleytnt'), volleytnt_sql2keyval("SELECT `id`, `label` FROM  `{$this->prefix}tornei` ORDER BY `label` ASC") );
		$form->show();
	}
	
	public function edit( $id ) {
		global $wpdb;
		if ( $id ) {
			echo '<h3>' . __("Modifica dati torneo", 'volleytnt') . '</h3>';
		} else {
			echo '<h3>' . __("Inserisci nuovo torneo", 'volleytnt') . '</h3>';
		}
		$form = new VolleyTNT_Form( 'vtntfrm_torneo' );
		$form->set_redirect( $this->url('firstpage') );
		$form->register_msg_code( 1, __("Impossibile modificare il torneo", 'volleytnt'), 'error', __("Si è verificato un errore nel salvataggio dei dati; le modifiche effettuate non sono state registrate.", 'volleytnt' ) );
		$form->register_msg_code( 2, __("Impossibile creare il torneo", 'volleytnt'), 'error', __("Si è verificato un errore nel salvataggio dei dati; le modifiche effettuate non sono state registrate.", 'volleytnt' ) );
		if ( $id ) $form->load( $wpdb->get_row("SELECT * FROM `{$this->prefix}tornei` WHERE `id`=$id") );
		$form->add_element( 'string', 'label', __("Nome edizione", 'volleytnt'), __("Un nome di riferimento per distinguere le varie edizioni del torneo gestito.", 'volleytnt') );
		$form->add_element( 'multiselect', 'categorie', __("Categorie", 'volleytnt'), __("Le categorie in cui ci si può iscrivere", 'volleytnt'), $this->l_categorie );
		$form->add_element( 'select', 'set_partita', __("Set per partita", 'volleytnt'), __("Numero massimo di set giocati in ogni partita.", 'volleytnt'), $this->l_set_partita );
		$form->add_element( 'string', 'campi', __("Numero di campi", 'volleytnt'), __("Su quanti campi si svolge il torneo.", 'volleytnt') );
		$form->add_element( 'string', 'durata_turno', __("Durata del turno in minuti", 'volleytnt'), __("Durata indicativa della partita media, usata per il calcolo degli orari.", 'volleytnt') );
		$form->add_element( 'select', 'finali', __("Prima fase delle finali", 'volleytnt'), __("Determina quante squadre passano alle finali a eliminazione diretta. ", 'volleytnt'), $this->l_finali );
		$form->show();
	}
	
	public function cancella_torneo() {
		global $wpdb;
		if ( isset( $_GET['param'] ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$this->prefix}tornei` WHERE `id`=%d", $_GET['param'] ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$this->prefix}gironi` WHERE `tornei_id`=%d", $_GET['param'] ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$this->prefix}partite` WHERE `tornei_id`=%d", $_GET['param'] ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$this->prefix}slots` WHERE `tornei_id`=%d", $_GET['param'] ) );
			
			$squadre = $wpdb->get_col( $wpdb->prepare( "SELECT `id` FROM `{$this->prefix}squadre` WHERE `tornei_id`=%d", $_GET['param'] ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$this->prefix}squadre` WHERE `tornei_id`=%d", $_GET['param'] ) );
			$wpdb->query( "DELETE FROM `{$this->prefix}impossibilita` WHERE `squadre_id` IN (" . implode( ',', $squadre ) . ")" );
			$wpdb->query( "DELETE FROM `{$this->prefix}squadre_atleti` WHERE `squadre_id` IN (" . implode( ',', $squadre ) . ")" );
			return true;
		} else return false;
	}
	
	public function salva_opzioni( $data, $id ) {
		$this->opts->nome = strip_tags( $data['nome'] );
		$this->opts->corrente = absint( $data['corrente'] );
		update_option( 'volleytnt_opts', $this->opts );
		return 0;
	}
	
	public function salva_torneo( $data, $id ) {
		global $wpdb;
		$data['categorie'] = implode( ',', $data['categorie'] );
		$id = intval( $data['id'] );
		unset( $data['id'] );
		if ( $id ) {
			$wpdb->update( "{$this->prefix}tornei", $data, array( 'id' => $id ) );
			$return = $wpdb->last_error ? 1 : 0;
		} else {
			$wpdb->insert( "{$this->prefix}tornei", $data );
			$return = $wpdb->last_error ? 2 : 0;
			$id = $wpdb->insert_id;
		}
		return $return;
	}
}
?>
