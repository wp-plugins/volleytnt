<?php 
class VolleyTNT_Partite extends VolleyTNT_AdminPage {
	
	public function __construct() {	
		$this->set_title( __("Partite", 'volleytnt') );
		
		$this->add_help_tab( __("Creazione partite", 'volleytnt'), array( $this, 'help_creazione' ) );
		$this->add_help_tab( __("Assegnazione orari", 'volleytnt'), array( $this, 'help_assegnazione' ) );
		
		$this->load_js( 'admin_partite.js', 'jquery-ui-accordion', 'jquery-ui-draggable','jquery-ui-droppable' );
		
		$this->localize_js_string( 'rigenerapartitegironi', __( "Vuoi realmente cancellare TUTTE le partite di questo torneo e rigenerare gli accoppiamenti sulla base dei gironi?", 'volleytnt' ) );

		$this->add_case( 'firstpage' );
		
		$this->trigger( 'rigenerapartitegironi', array( $this, 'rigenera_partite_gironi' ), '?page=volleytnt_partite' );
		
		add_action( 'wp_ajax_volleytnt_partite_orari', 		array( $this, 'ajax_partite_orari' ) );
	}
	
	public function help_creazione() {
		echo '<p>';
		_e("Per poter impostare gli orari occorre prima generare gli accoppiamenti e le partite di finale. Questo viene fatto mediante il pulsante \"Cancella le partite e rigenerale dai gironi\". Quando viene premuto ogni precedente accoppiamento viene eliminato, e le partite vengono ricreate sulla base della composizione attuale dei gironi e dal turno di finali iniziali.", 'volleytnt');
		echo  '</p>';
	}
	
	public function help_assegnazione() {
		echo '<p>';
		_e("In questa pagina si possono inserire le partite negli slot temporali disponibili nel torneo. Questo permette la compilazione del calendario partite con relativi orari, sulla base delle lunghezze medie impostate nelle opzioni del torneo.", 'volleytnt');
		echo  '</p>';
		echo '<p>';
		_e("Cliccando su una partita vengono evidenziati in verde gli slot in cui entrambe le squadre sono disponibili. L'assegnazione viene effettuata trascinando una partita sullo slot prescelto. Nell'elenco delle partite sono presenti da subito anche quelle dei turni di finale, ancora senza partecipanti.", 'volleytnt');
		echo  '</p>';
	}
	
	public function ajax_partite_orari() {
		global $wpdb;
		header('Content-Type: application/json');
		if ( !isset( $_GET['partita'], $_GET['slot'] ) ) die( json_encode( false ) );
		$wpdb->query( $wpdb->prepare("UPDATE `{$this->prefix}partite` SET `slots_id`=%d WHERE `id`=%d", $_GET['slot'], $_GET['partita'] ) );
		die( json_encode( true ) );
		
	}
	
	public function rigenera_partite_gironi() {
		global $wpdb;
		$categorie = $this->torneo->categorie;
		$gironi = array();
		if ( $tmp = $wpdb->get_results("SELECT * FROM `{$this->prefix}gironi` WHERE `tornei_id`={$this->opts->corrente}") ) foreach ( $tmp as $row ) {
			$gironi[ $row->categoria ][ $row->girone ][] = $row->squadre_id;
		}

		$tokens = array();
		$wpdb->query("DELETE FROM `{$this->prefix}partite` WHERE `tornei_id`={$this->opts->corrente}");
		foreach ( $gironi as $categoria => $girs ) foreach ( $girs as $num_gir => $squadre ) {
			while ( $squadre ) {
				$cur = array_pop( $squadre );
				if ( $squadre ) foreach ( $squadre as $sq ) {
					$tokens[] = "('$categoria',$num_gir,$cur,$sq,{$this->opts->corrente})";
				}
			}				
		}
		if ( $tokens ) $wpdb->query("INSERT INTO `{$this->prefix}partite` (`categoria`,`girone`,`squadra_1`,`squadra_2`,`tornei_id`) VALUES " . implode(', ', $tokens ) );
		
		$tokens = array();
		if ( $categorie ) foreach ( $categorie as $categoria ) if ( in_array( $categoria, $this->torneo->categorie ) ) {
			$attr = 'finali_' . $categoria;
			if ( $this->torneo->$attr >= 32 ) for ( $i = 1; $i <= 32; $i++ ) $tokens[] = "('$categoria', '32_{$i}', {$this->opts->corrente})";
			if ( $this->torneo->$attr >= 16 ) for ( $i = 1; $i <= 16; $i++ ) $tokens[] = "('$categoria', '16_{$i}', {$this->opts->corrente})";
			if ( $this->torneo->$attr >= 8 ) for ( $i = 1; $i <= 8; $i++ ) $tokens[] = "('$categoria', '8_{$i}', {$this->opts->corrente})";
			if ( $this->torneo->$attr >= 4 ) for ( $i = 1; $i <= 4; $i++ ) $tokens[] = "('$categoria', '4_{$i}', {$this->opts->corrente})";
			if ( $this->torneo->$attr >= 2 ) for ( $i = 1; $i <= 2; $i++ ) $tokens[] = "('$categoria', '2_{$i}', {$this->opts->corrente})";
			if ( $this->torneo->$attr >= 2 ) $tokens[] = "('$categoria', '1_1', {$this->opts->corrente})";
			$tokens[] = "('$categoria', '0_1', {$this->opts->corrente})";
		}
		
		$wpdb->query("INSERT INTO `{$this->prefix}partite` (`categoria`,`finale`,`tornei_id`) VALUES " . implode( ', ', $tokens ) );
		return true;
	}
	
	public function firstpage() {
		global $wpdb;
		$partite = $wpdb->get_results("	SELECT
										  `par`.`id`,
										  `par`.`girone`,
										  `par`.`finale`,
										  `par`.`categoria`,
										  `par`.`slots_id`,
										  `par`.`squadra_1`,
										  `par`.`squadra_2`,
										  `sq1`.`label`     AS `label_1`,
										  `sq2`.`label`     AS `label_2`,
										  GROUP_CONCAT(`imp`.`id`) AS `imps`
										FROM `{$this->prefix}partite` AS `par`
										  LEFT JOIN `{$this->prefix}squadre` AS `sq1`
										    ON `sq1`.`id` = `par`.`squadra_1`
										  LEFT JOIN `{$this->prefix}squadre` AS `sq2`
										    ON `sq2`.`id` = `par`.`squadra_2`
										  LEFT JOIN `{$this->prefix}impossibilita` AS `imp`
										    ON `imp`.`squadre_id` = `sq1`.`id`
										       OR `imp`.`squadre_id` = `sq2`.`id`
										WHERE `par`.`tornei_id` = {$this->opts->corrente}
										    AND `par`.`visibile` = 1
										GROUP BY `par`.`id`");
		if ( !$partite ) $partite = array();
		$impossibilita = $wpdb->get_results("	SELECT
												  `{$this->prefix}impossibilita`.`id`,
												  `{$this->prefix}impossibilita`.`primadopo`,
												  DATE_FORMAT(`{$this->prefix}impossibilita`.`giorno`, '%e/%c%/%Y') AS giorno,
												  UNIX_TIMESTAMP( CONCAT(`{$this->prefix}impossibilita`.`giorno`, ' ', `{$this->prefix}impossibilita`.`ora`) ) + IF( DATE_FORMAT(CONCAT(`{$this->prefix}impossibilita`.`giorno`, ' ', `{$this->prefix}impossibilita`.`ora`),'%k')<6, 24*60*60, 0) AS `ts`
												FROM `{$this->prefix}squadre`
												  INNER JOIN `{$this->prefix}impossibilita`
												    ON `{$this->prefix}impossibilita`.`squadre_id` = `{$this->prefix}squadre`.`id`
												WHERE `{$this->prefix}squadre`.`tornei_id` = {$this->opts->corrente}");


		echo '<div id="elencopartite"><h4>' . __("Partite non assegnate",'volleytnt') . '</h4>';

		if ( $partite ) {
			echo '<p>' . sprintf( __('Prima di assegnare gli orari consiglia di disattivare le partite non giocate nella <a href="%s">pagina delle finali</a>.', 'volleytnt'), add_query_arg( 'page', 'volleytnt_finali', admin_url('admin.php') ) ) . '</p>';
		} else {
			echo '<p>' . __("&Egrave; necessario generare le partite mediante il tasto sottostante", 'volleytnt') . '</p>';
		}
		echo '<p><a id="rigenerapartitegironi" class="button-primary" href="' . add_query_arg( array( 'method' => 'rigenerapartitegironi' ) ) . '">' . __('Cancella le partite e rigenerale dai gironi','volleytnt') . '</a></p>';
		echo '<div id="partite" slot_id="0" class="ui-widget-content ui-corner-all">';
		foreach ( $partite as $par ) if ( !$par->slots_id ) $this->riga_partita( $par );
		echo '</div>';
		echo '</div>';
	

		$slots = $wpdb->get_results("	SELECT
											`id`,
											`giorno` AS `sql`,
											DATE_FORMAT(`giorno`, '" . volleytnt_date_format('sql') . "') AS giorno,
											DATE_FORMAT(`inizio`, '%k:%i') AS inizio,
											DATE_FORMAT(`fine`, '%k:%i') AS fine,
											UNIX_TIMESTAMP( CONCAT( `giorno`, ' ', `inizio` ) ) + IF( DATE_FORMAT(`inizio`,'%k')<6, 24*60*60, 0) AS ts_i,
											UNIX_TIMESTAMP( CONCAT( `giorno`, ' ', `fine` ) ) + IF( DATE_FORMAT(`fine`,'%k')<6, 24*60*60, 0) AS ts_f,
											`campo`,
											60*(IF( DATE_FORMAT(`inizio`,'%k')<6, DATE_FORMAT(`inizio`,'%k')+24, DATE_FORMAT(`inizio`,'%k')))+DATE_FORMAT(`inizio`,'%i') AS ordo
										FROM 
											`{$this->prefix}slots` 
										WHERE 
											`tornei_id`={$this->opts->corrente} 
										ORDER BY 
											`{$this->prefix}slots`.`giorno` ASC, 
											`campo` ASC, 
											ordo ASC");
		
		if ( $slots ) {
			$settimana = array( 1 => __('Lunedì', 'volleytnt' ),
								2 => __('Martedì', 'volleytnt' ),
								3 => __('Mercoledì', 'volleytnt' ),
								4 => __('Giovedì', 'volleytnt' ),
								5 => __('Venerdì', 'volleytnt' ),
								6 => __('Sabato', 'volleytnt' ),
								7 => __('Domenica', 'volleytnt' ) );
			echo '<div class="accordion" id="accopartite">';
			$inizi = $campi = $giorni = array();
			$labelgiorni = array();
			foreach ( $slots as $slot ) {
				$inizi[ $slot->ordo ] = $slot->inizio;
				$campi[] = $slot->campo;
				$giorni[] = $slot->giorno;
				if ( !isset( $labelgiorni[ $slot->giorno ] ) ) {
					$d = new DateTime( $slot->sql );
					$labelgiorni[ $slot->giorno ] = $settimana[ $d->format('N') ];
					unset( $d );
				}
			}
			ksort( $inizi );
			$campi = array_unique( $campi );
			$giorni = array_unique( $giorni );
			$width = number_format( 100 / count( $campi ), 2 );
			
			foreach ( $giorni as $giorno ) {
				echo '<h3><a href="#">' . $giorno . '</a></h3>';
				echo '<div><table class="widefat gestione_calendario">';
				echo '<thead><tr>';
				foreach ( $campi as $campo ) echo '<th width="' . $width . '%">' . sprintf( __('Campo %s', 'volleytnt'), $campo ) . '</th>';
				echo '</tr></thead>';
				echo '<tbody>';
				foreach ( $inizi as $inizio ) {
					ob_start();
					$almeno_uno = false;
					echo '<tr>';
					foreach ( $campi as $campo ) {
						$stampato = false;
						foreach ( $slots as $slot ) {
							if ( $slot->campo == $campo and $slot->inizio == $inizio and $slot->giorno == $giorno ) {
								$classi = array('slot');
								foreach ( $impossibilita as $imp ) if ( $imp->giorno == $giorno ) {
									$ts_imp = intval( $imp->ts );
									switch ( $imp->primadopo ) {
										case 'prima':
											if ( intval( $slot->ts_f ) <= $ts_imp ) $classi[] = 'no' . $imp->id;
											break;
										case 'dopo':
											if ( intval( $slot->ts_i ) >= $ts_imp ) $classi[] = 'no' . $imp->id;
											break;
									}
								}
								echo '<td class="' . implode( ' ', $classi ) . '" slot_id="' . $slot->id . '">';
								echo '<span class="orario">' . $slot->inizio . ' - ' . $slot->fine . '</span>';
								foreach ( $partite as $par ) if ( $slot->id == $par->slots_id ) $this->riga_partita( $par );
								echo '</td>';
								$stampato = $almeno_uno = true;
							}
						}
						if ( !$stampato ) echo '<td class="vuoto">&nbsp;</td>';
					}
					echo '</tr>';
					if ( $almeno_uno ) ob_end_flush(); else ob_end_clean();
				}
				echo '</tbody>';
				echo '</table></div>';
			}
		
			echo '</div>';
		} else {
			echo '<p>' . sprintf( __('Nessuno slot partita definito! Occorre impostarli nella <a href="%s">pagina degli orari del torneo</a>.', 'volleytnt' ),
								   add_query_arg( array('page'=>'VolleyTNT_Opzioni',
								   						'method'=>'timespan',
								   						'param'=>$this->opts->corrente ), admin_url('admin.php') )	) . '</p>';
		}

	}

	private function riga_partita( $par ) {
		$class = $par->imps ? ' ui-state-focus' : '';
		echo '<div class="partita ui-corner-all ui-widget-content' . $class . '" partita_id="' . $par->id . '" impossibilita="' . $par->imps . '">';
		echo '<span class="girone">' . $par->categoria;
		if ( $par->girone ) echo $par->girone;
		if ( $par->finale ) echo $par->finale;
		echo '</span>';
		echo $par->label_1 . ' - ' . $par->label_2;
		echo '</div>';
	}
	
}
?>