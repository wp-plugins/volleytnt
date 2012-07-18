jQuery( function ( $ ) {
	$('#vtnt_tabgironi').tabs();

	
	function gestisci_gironi( evento, ui ) {
		$('body').addClass('loading');
		$.getJSON( 	ajaxurl, 
					{	action: 'volleytnt_gironi',
						squadra: ui.item.attr('squadra'),
						categoria: $(this).attr('categoria'),
						girone: $(this).attr('girone') },
					function ( res ) {
						$('body').removeClass('loading');
						if ( !res ) {
							alert("Impossibile spostare la squadra in questa posizione!");
						}
					} );
	}
	
	var configura_gironedit = {	connectWith: ".girone",
								placeholder: "ui-state-highlight",
								receive: gestisci_gironi };
	
	$( ".gironedit .girone" ).sortable( configura_gironedit ).disableSelection();
	
	$('.gironedit .aggiungigirone').click( function () {
		var categoria = $(this).attr('categoria');
		var max = 0;
		$('#categoria' + categoria + ' ul.girone').each( function () {
			var gir = parseInt( $(this).attr('girone') );
			if ( gir > max ) {
				max = gir;
			}
		} );
		max++;
		$('<h4>' + volleytnt.girone + ' ' + max + '</h4><ul class="girone ui-corner-all ui-widget-content" girone="' + max + '" categoria="' + categoria + '"></ul>').appendTo('#categoria' + categoria);
		$('#categoria' + categoria + ' .girone:last-child').sortable( configura_gironedit ).disableSelection();
	} );
} );
