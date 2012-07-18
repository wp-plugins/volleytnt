jQuery( function ( $ ) {
	$('.tabbor').tabs();

	$('.modsalvpart').click( function () {
		var pulsante = $(this);
		var id_partita = pulsante.attr('id_partita');
		var tr = $('#partita' + id_partita);
		if ( tr.hasClass('modificando') ) {
			var dati = {};
			dati.action = 'volleytnt_salvapartita';
			dati.id_partita = id_partita;
			tr.find('.riga').each( function () {
				dati[ $(this).attr('campo') ] = $(this).children('.modpunti').val();
			} );
			$('body').addClass('loading');
			$.post(	ajaxurl,
					dati,
					function ( res ) {
						if ( res ) {
							for ( var i in res ) {
								tr.find('div[campo="' + i + '"]').text( res[ i ] );
							}
							$('body').removeClass('loading');
							tr.removeClass('modificando');
							pulsante.text( volleytnt.modifica );
						}
					},
					'json');
		} else {
			tr.addClass('modificando');
			$(this).text( volleytnt.salva );
			tr.find('.riga').each( function () {
				$(this).html('<input class="modpunti" value="' + $(this).text() + '"/>');
			} );
		}
	} );
	
} );