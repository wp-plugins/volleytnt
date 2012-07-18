jQuery( function ( $ ) {
	$('#vtnt_tabgiorni').tabs();
	
	
	$('#aggiungiintervallo').click( function () {
		$('#blueprint_intervallo>li').clone().appendTo( $('#elenco_intervalli') ).find('.ggmmaa').datepicker( { dateFormat: volleytnt.date_format } );		
	} );
} );