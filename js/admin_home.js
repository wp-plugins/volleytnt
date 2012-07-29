var volleytnt_aggiorna_contopartite;

jQuery( function ( $ ) {
	
	function aggiungiriga( cat, gir_da, num_gir ) {
		if ( ! num_gir ) return;
		var combin = volleytnt_fattoriale( gir_da ) / ( 2 * volleytnt_fattoriale( gir_da - 2 ) );
		var riga = '<tr>';
		riga += '<td>' + cat + '</td>';
		riga += '<td>' + gir_da + '</td>';
		riga += '<td>' + combin + '</td>';
		riga += '<td class="num_gir">' + num_gir  + '</td>';
		riga += '<td class="num_par">' + ( combin * num_gir ) + '</td>';
		riga += '</tr>';
		$( riga ).appendTo('#calcolopartite table.risultati tbody');
				
	}
	
	function volleytnt_fattoriale( n ) {
		return ( n > 1) ? n * volleytnt_fattoriale( n - 1 ) : 1;
	}
	
	volleytnt_aggiorna_contopartite = function () {
		$('#calcolopartite table.risultati tbody tr').remove();
		$('#calcolopartite tr.categoria').each( function () {
			var cat = $(this);
			var valore = parseInt( cat.find('.slider').slider('value') );
			var numsquadre = parseInt( cat.attr('squadre') );
			cat.find('.squadregironi').text( valore );
			cat.find('.numgironi').text( Math.floor( numsquadre / valore ) );
			
			var quantigironi = Math.floor( numsquadre / valore ) - ( numsquadre % valore );
			aggiungiriga( cat.attr('categoria'), valore, quantigironi );
			aggiungiriga( cat.attr('categoria'), valore + 1, numsquadre % valore );
		} );
		var tot = { num_gir : 0, num_par : 0 };
		$('#calcolopartite table.risultati tbody td.num_gir, #calcolopartite table.risultati tbody td.num_par').each( function () {
			tot[ $( this ).attr('class') ] += parseInt( $( this ).text() );
		} );
		$('#calcolopartite table.risultati tfoot .num_gir').text( tot['num_gir'] );
		$('#calcolopartite table.risultati tfoot .num_par').text( tot['num_par'] );
		
		$('#calcolopartite .selfinali').each( function () {
			$('#calcolopartite .' + $(this).attr('modifica') ).text( $(this).val() );
		} );
		
		var grantot = 0;
		$('#calcolopartite .subtot').each( function () {
			grantot += parseInt( $(this).text() );
		} );
		$('#iltotale').text( grantot );
	}
	
	$('#calcolopartite .slider').slider( {	range: 'min',
											min: 2,
											max: $('#volleytnt_calcolopartite .slider').attr('max'),
											change: volleytnt_aggiorna_contopartite } );
	$('#calcolopartite .selfinali').change( volleytnt_aggiorna_contopartite );		
	volleytnt_aggiorna_contopartite();
} );
