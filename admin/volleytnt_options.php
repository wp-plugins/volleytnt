<?php 
class VolleyTNT_Options extends VolleyTNT_AdminPage {
	
	public function __construct() {	
		$this->set_title( __("Opzioni", 'volleytnt') );
		$this->set_title_action( $this->url('edit'), __("Nuovo torneo", 'volleytnt') );
		
		$this->add_help_tab( __("Opzioni generali", 'volleytnt'), array( $this, 'help_generali' ) );
		$this->add_help_tab( __("Opzioni torneo", 'volleytnt'), array( $this, 'help_torneo' ) );
		$this->add_help_tab( __("Ore di gioco", 'volleytnt'), array( $this, 'help_timespan' ) );
		
		$this->add_case( 'firstpage' );
		$this->add_case( 'edit' );
		$this->add_case( 'timespan' );
		
		$this->localize_js_string( 'conferma_elimina_torneo', __("Si vuole realmente cancellare questo torneo?", 'volleytnt' ) );
		
		VolleyTNT_Form::register_cb_save( 'vtntfrm_torneo', array( $this, 'salva_torneo') );
		VolleyTNT_Form::register_cb_save( 'vtntfrm_opzioni', array( $this, 'salva_opzioni') );
		
		$this->trigger( 'delete', array( $this, 'cancella_torneo' ), $this->url('firstpage') );
	}
	
	public function help_timespan() {
		echo '<p>';
		_e("", 'volleytnt');
		echo  '</p>';
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
	
	public function timespan( $id ) {
		global $wpdb;
		$torneo = $wpdb->get_row("SELECT * FROM `{$this->prefix}tornei` WHERE `id`=$id");
		if ( $torneo ) {
			echo '<h3>';
			printf( __('Orari di gioco per <u>%s</u>', 'volleytnt'), $torneo->label );
			echo '</h3>';
			
			
			
		
		$slots = $wpdb->get_results("	SELECT
											`id`,
											DATE_FORMAT(`giorno`, '%e/%c%/%Y') AS giorno,
											DATE_FORMAT(`inizio`, '%k:%i') AS inizio,
											DATE_FORMAT(`fine`, '%k:%i') AS fine,
											`campo`,
											60*(IF( DATE_FORMAT(`inizio`,'%k')<6, DATE_FORMAT(`inizio`,'%k')+24, DATE_FORMAT(`inizio`,'%k')))+DATE_FORMAT(`inizio`,'%i') AS ordo
										FROM 
											`{$this->prefix}slots` 
										WHERE 
											`tornei_id`={$this->opts->corrente} 
										ORDER BY 
											`giorno` ASC, 
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
			foreach ( $giorni as $giorno ) {
				echo '<h3>' . $giorno . '</h3>';
				echo '<table class="widefat">';
				echo '<thead><tr>';
				foreach ( $campi as $campo ) echo '<th width="' . $width . '%">Campo ' . $campo . '</th>';
				echo '</tr></thead>';
				echo '<tbody>';
				foreach ( $inizi as $inizio ) {
					echo '<tr>';
					foreach ( $campi as $campo ) {
						$stampato = false;
						foreach ( $slots as $slot ) {
							if ( $slot->campo == $campo and $slot->inizio == $inizio and $slot->giorno == $giorno ) {
								echo '<td class="slot">';
								echo '<div class="numpartita">partita n° ' . ++$conto . '</div>';
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
