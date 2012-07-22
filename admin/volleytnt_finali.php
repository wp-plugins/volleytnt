<?php 
class VolleyTNT_Finali extends VolleyTNT_AdminPage {
	
	public function __construct() {	
		$this->set_title( __("Finali", 'volleytnt') );
		
		$this->add_help_tab( __("Inserimento risultati", 'volleytnt'), array( $this, 'help_risultati' ) );
		
		$this->load_js( 'admin_finali.js', 'jquery-ui-tabs', 'jquery-ui-dialog' );
		
		$this->localize_js_string( 'impossibile_salvare', __( "Impossibile effettuare il salvataggio!", 'volleytnt' ) );

		$this->add_case( 'firstpage' );
	
		add_action( 'wp_ajax_volleytnt_finali', 		array( $this, 'ajax_finali' ) );
	}
	
	public function help_risultati() {
		echo '<p>';
		_e("In questa pagina si possono impostare le partite giocate, le squadre e i risultati. Le partite non giocate permettono di creare alberi di eliminatorie da forme differenti (per esempio con i secondi e i terzi classificati che giocano gli ottavi di finale e i primi che entrano ai quarti). I risultati si inseriscono anche qua mettendo soltanto i punti per ogni set, ma occorre segnare a mano chi passa al turno successivo.", 'volleytnt');
		echo '</p>';
	}
	
	public function ajax_finali() {
		global $wpdb;
		header('Content-Type: application/json');
		if ( !isset( $_POST['id_partita'], $_POST['categoria'] ) ) die( json_encode( false ) );
		if ( !in_array( $_POST['categoria'], array( 'M', 'F', 'X' ) ) ) die( json_encode( false ) );
		if ( !preg_match( '|\d+_\d+|', $_POST['id_partita'] ) ) die( json_encode( false ) );
		$_POST = stripslashes_deep( $_POST );
		$wpdb->query( $wpdb->prepare("	UPDATE `{$this->prefix}partite`
										SET	`set1_sq1` = %d,
											`set2_sq1` = %d,
											`set3_sq1` = %d,
											`set4_sq1` = %d,
											`set5_sq1` = %d,
											`set1_sq2` = %d,
											`set2_sq2` = %d,
											`set3_sq2` = %d,
											`set4_sq2` = %d,
											`set5_sq2` = %d,
											`squadra_1` = %d,
											`squadra_2` = %d,
											`visibile` = %d
										WHERE `categoria` = %s
											AND `finale` = %s
											AND `tornei_id` = %d",
										$_POST['set1_sq1'],
										$_POST['set2_sq1'],
										$_POST['set3_sq1'],
										$_POST['set4_sq1'],
										$_POST['set5_sq1'],
										$_POST['set1_sq2'],
										$_POST['set2_sq2'],
										$_POST['set3_sq2'],
										$_POST['set4_sq2'],
										$_POST['set5_sq2'],
										$_POST['squadra_1'],
										$_POST['squadra_2'],
										$_POST['visibile'],
										$_POST['categoria'],
										$_POST['id_partita'],
										$this->opts->corrente	) );

		$row = $wpdb->get_row( $wpdb->prepare(" SELECT `{$this->prefix}partite`.*,
														`sq1`.`label` AS `label_1`,
														`sq2`.`label` AS `label_2`
												FROM `{$this->prefix}partite`
												LEFT JOIN `{$this->prefix}squadre` AS `sq1`
													ON `{$this->prefix}partite`.`squadra_1`=`sq1`.`id`
												LEFT JOIN `{$this->prefix}squadre` AS `sq2`
													ON `{$this->prefix}partite`.`squadra_2`=`sq2`.`id`
												WHERE `{$this->prefix}partite`.`categoria` = %s
													AND `{$this->prefix}partite`.`finale` = %s
													AND `{$this->prefix}partite`.`tornei_id` = %d",
												$_POST['categoria'],
												$_POST['id_partita'],
												$this->opts->corrente ) );
		
		$row->set1 = $row->set2 = 0;
		if ( !$row->set1_sq1 and !$row->set1_sq2 ) {
			$row->set1_sq1 = $row->set1_sq2 = '';
		} else if ( $row->set1_sq1 > $row->set1_sq2 ) $row->set1++; else $row->set2++;
		if ( !$row->set2_sq1 and !$row->set2_sq2 ) {
			$row->set2_sq1 = $row->set2_sq2 = '';
		} else if ( $row->set2_sq1 > $row->set2_sq2 ) $row->set1++; else $row->set2++;
		if ( !$row->set3_sq1 and !$row->set3_sq2 ) {
			$row->set3_sq1 = $row->set3_sq2 = '';
		} else if ( $row->set3_sq1 > $row->set3_sq2 ) $row->set1++; else $row->set2++;
		if ( $this->torneo->set_partita == 5 ) {
			if ( !$row->set4_sq1 and !$row->set4_sq2 ) {
				$row->set4_sq1 = $row->set4_sq2 = '';
			} else if ( $row->set4_sq1 > $row->set4_sq2 ) $row->set1++; else $row->set2++;
			if ( !$row->set5_sq1 and !$row->set5_sq2 ) {
				$row->set5_sq1 = $row->set5_sq2 = '';
			} else if ( $row->set5_sq1 > $row->set5_sq2 ) $row->set1++; else $row->set2++;
		}
				
		if ( !$row->set1 and !$row->set2 ) $row->set1 = $row->set2 = '';
		die( json_encode( $row ) );
	}
	
	public function firstpage() {
		$tree = new VolleyTNT_Tree();
		
		echo '<div class="tabbor"><ul>';
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<li><a href="#tab' . $categoria . '">' . $this->l_categorie[ $categoria ] . '</a></li>';
		}
		echo '</ul>';
		foreach ( $this->torneo->categorie as $categoria ) {
			echo '<div id="tab' . $categoria . '">';
			$tree->show( $categoria );
			echo '</div>';	
		}
		echo '</div>';
		
		?>
<script type="text/javascript">var squadre = <?php echo json_encode( $tree->squadre ); ?>;</script>
<form id="formfinale" title="<?php echo esc_attr( __('Modifica partita', 'volleytnt' ) ); ?>" categoria="" id_partita="">
<table class="widefat">
<thead>
<tr>
<th><?php echo __('Squadra', 'volleytnt'); ?></th>
<th class="punti"><?php printf( __('%d&ordm; set', 'volleytnt'), 1 ); ?></th>
<th class="punti"><?php printf( __('%d&ordm; set', 'volleytnt'), 2 ); ?></th>
<th class="punti"><?php printf( __('%d&ordm; set', 'volleytnt'), 3 ); ?></th>
<?php if ( $this->torneo->set_partita == 5 ) { ?>
	<th class="punti"><?php printf( __('%d&ordm; set', 'volleytnt'), 4 ); ?></th>
	<th class="punti"><?php printf( __('%d&ordm; set', 'volleytnt'), 5 ); ?></th>
<?php } ?>
</tr>
</thead>
<tbody>
<tr>
<td><select class="squadra_1" tabindex="1"></select></td>
<td><input type="text" class="punti set1_sq1" tabindex="3"></td>
<td><input type="text" class="punti set2_sq1" tabindex="5"></td>
<td><input type="text" class="punti set3_sq1" tabindex="7"></td>
<?php if ( $this->torneo->set_partita == 5 ) { ?>
	<td><input type="text" class="punti set4_sq1" tabindex="9"></td>
	<td><input type="text" class="punti set5_sq1" tabindex="11"></td>
<?php } ?>
</tr>
<tr>
<td><select class="squadra_2" tabindex="2"></select></td>
<td><input type="text" class="punti set1_sq2" tabindex="4"></td>
<td><input type="text" class="punti set2_sq2" tabindex="6"></td>
<td><input type="text" class="punti set3_sq2" tabindex="8"></td>
<?php if ( $this->torneo->set_partita == 5 ) { ?>
	<td><input type="text" class="punti set4_sq2" tabindex="10"></td>
	<td><input type="text" class="punti set5_sq2" tabindex="12"></td>
<?php } ?>
</tr>
</tbody>
</table>
<p><input type="checkbox" id="partita_visibile" tabindex="13"><label for="partita_visibile"><?php _e('Partita giocata', 'volleytnt'); ?></label></p>
<p><input type="submit" class="button-primary" value="<?php echo esc_attr( __('Salva partita', 'volleytnt' ) ); ?>" tabindex="14"></p>
</form>
		<?php
	}
}
?>