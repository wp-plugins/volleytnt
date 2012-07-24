jQuery( function ( $ ) {
	$('.tabbor').tabs();

	
	$('#formfinale').dialog( {	autoOpen: false,
		width: 700,
		modal: true } );

	$('#formfinale').submit( function () {
		var dati = {	action: 'volleytnt_finali',
						id_partita: $('#formfinale').attr('id_partita'),
						categoria: $('#formfinale').attr('categoria'),
						squadra_1: $('#formfinale select.squadra_1').val(),
						squadra_2: $('#formfinale select.squadra_2').val(),
						set1_sq1: $('#formfinale .set1_sq1').val(),
						set2_sq1: $('#formfinale .set2_sq1').val(),
						set3_sq1: $('#formfinale .set3_sq1').val(),
						set4_sq1: $('#formfinale .set4_sq1').val(),
						set5_sq1: $('#formfinale .set5_sq1').val(),
						set1_sq2: $('#formfinale .set1_sq2').val(),
						set2_sq2: $('#formfinale .set2_sq2').val(),
						set3_sq2: $('#formfinale .set3_sq2').val(),
						set4_sq2: $('#formfinale .set4_sq2').val(),
						set5_sq2: $('#formfinale .set5_sq2').val(),
						visibile: $('#partita_visibile').prop('checked') ? 1 : 0 };
		$('body').addClass('loading');
		$.post(	ajaxurl,
				dati, 
				function ( res ) {
					if ( res == false ) {
						alert( volleytnt.impossibile_salvare );
					} else {
						var partita = $('.partita[categoria="' + res.categoria + '"][id_partita="' + res.finale + '"]');
						partita.attr('squadra_1', res.squadra_1 );
						partita.attr('squadra_2', res.squadra_2 );
						partita.find('div.squadra1').text( res.label_1 );
						partita.find('div.squadra2').text( res.label_2 );
						partita.find('.set1_sq1').text( res.set1_sq1 );
						partita.find('.set2_sq1').text( res.set2_sq1 );
						partita.find('.set3_sq1').text( res.set3_sq1 );
						partita.find('.set4_sq1').text( res.set4_sq1 );
						partita.find('.set5_sq1').text( res.set5_sq1 );
						partita.find('.set1_sq2').text( res.set1_sq2 );
						partita.find('.set2_sq2').text( res.set2_sq2 );
						partita.find('.set3_sq2').text( res.set3_sq2 );
						partita.find('.set4_sq2').text( res.set4_sq2 );
						partita.find('.set5_sq2').text( res.set5_sq2 );
						partita.find('.risultato1').text( res.set1 );
						partita.find('.risultato2').text( res.set2 );
						if ( res.visibile == '1' ) {
							partita.removeClass('nongiocata');
						} else {
							partita.addClass('nongiocata');
						}
						$('#formfinale').dialog('close');
					}
					$('body').removeClass('loading');
				},
				'json' );
		return false;
	} );

	$('.tabellonefinale .partita').click( function () {
		var partita = $(this);
		var categoria = partita.attr('categoria');
		var html_select = '<option value="0">&nbsp;</option>';
		for ( i in squadre[ categoria ] ) {
			html_select += '<option value="' + i + '">' + squadre[ categoria ][ i ] + '</option>';
		}
		$('#formfinale select').html( html_select );
		$('#formfinale select.squadra_1').val( partita.attr('squadra_1') );
		$('#formfinale select.squadra_2').val( partita.attr('squadra_2') );
		$('#formfinale .set1_sq1').val( partita.find('.set1_sq1').text() );
		$('#formfinale .set2_sq1').val( partita.find('.set2_sq1').text() );
		$('#formfinale .set3_sq1').val( partita.find('.set3_sq1').text() );
		$('#formfinale .set4_sq1').val( partita.find('.set4_sq1').text() );
		$('#formfinale .set5_sq1').val( partita.find('.set5_sq1').text() );
		$('#formfinale .set1_sq2').val( partita.find('.set1_sq2').text() );
		$('#formfinale .set2_sq2').val( partita.find('.set2_sq2').text() );
		$('#formfinale .set3_sq2').val( partita.find('.set3_sq2').text() );
		$('#formfinale .set4_sq2').val( partita.find('.set4_sq2').text() );
		$('#formfinale .set5_sq2').val( partita.find('.set5_sq2').text() );
		$('#formfinale').attr('categoria', categoria );
		$('#formfinale').attr('id_partita', partita.attr('id_partita') );
		$('#partita_visibile').prop('checked', !partita.hasClass('nongiocata') );
		$('#formfinale').dialog('open');
	} );
} );