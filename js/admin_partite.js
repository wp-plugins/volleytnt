jQuery( function ( $ ) {

	$('div.accordion').accordion( { collapsible: true } );
	
	$('#rigenerapartitegironi').click( function () {
		return confirm( volleytnt.rigenerapartitegironi );
	} );

	
	$('.partita').click( function () {
		var annulla = $(this).hasClass('ui-state-error');
		$('.partita').removeClass('ui-state-error');
		$('.disp_si, .disp_no').removeClass('disp_si').removeClass('disp_no');
		if ( annulla ) {
			return;
		}
		$(this).addClass('ui-state-error');
		var imps = $(this).attr('impossibilita').split(',');
		var classi = '';
		for ( var i in imps ) {
			if ( imps[ i ] != '' ) {
				classi += ', .no' + imps[ i ];
			}
		}
		
		$('.slot').addClass('disp_si');
		$(classi).removeClass('disp_si').addClass('disp_no');
	} );

	
	$('.partita').draggable({revert: "invalid",
							 appendTo: 'body',
							 helper:'clone'});
	
	$('.slot, #partite').droppable({drop: function ( event, ui ) {
										ui.draggable.appendTo( $(this) );
										$('body').addClass('loading');
										$.getJSON( 	ajaxurl, 
													{	action: 'volleytnt_partite_orari',
														partita: ui.draggable.attr('partita_id'),
														slot: $(this).attr('slot_id') },
													function ( res ) {
														$('body').removeClass('loading');
														if ( !res ) {
															alert("Impossibile spostare la partita in questo orario!");
														}
													} );
									} });

} );