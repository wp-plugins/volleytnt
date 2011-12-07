jQuery( function ( $ ) {
	$('#vtnt_tabiscrizioni').tabs();
	$('#elenco_impossibilita .ggmmaa').datepicker( { dateFormat: volleytnt.date_format } );
	
	$('#aggiungi_impossibilita').click( function () {
		$('#blueprint_impossibilita>div').clone().appendTo( $('#elenco_impossibilita') ).find('.ggmmaa').datepicker( { dateFormat: volleytnt.date_format } );
	} );
	
	
	$('.remove').live( 'click', function() {
		$(this).parent().remove();
	} )
	
	function ajax_ac_atleti( target ) {
		if ( target.length > 0 ) {
			target.autocomplete({
				source: ajaxurl + '?action=volleytnt_acatleti',
				minLength: 3,
				select: function( event, ui ) {
					var tr = $(this).parent().parent();
					tr.find('input[field="id"]').val( ui.item.id );
					tr.find('input[field="cognome"]').val( ui.item.cognome );
					tr.find('input[field="nome"]').val( ui.item.nome );
					tr.find('input[field="telefono"]').val( ui.item.telefono );
					tr.find('input[field="mail"]').val( ui.item.mail );
				}
			}).data( "autocomplete" )._renderItem = function( ul, item ) {
				return $( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( "<a><strong>" + item.label + "</strong> " + item.nome + "</a>" )
				.appendTo( ul );
			};
		}
	}
	ajax_ac_atleti( $('#squadra input[field="cognome"]') );
	
	$('#aggiungi_atleta').click( function () {
		$('#blueprint tr').clone().appendTo( $('#squadra') );
		var aggiunto = $('#squadra tr:last-child');
		ajax_ac_atleti( aggiunto.find('input[field="cognome"]') );
	} );
} );