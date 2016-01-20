( function($) {
	$( document ).ready( function() {
		/* checkboxes */
		$( '.pdtr-check-theme-all' ).on( 'click', function() {
			var c = $( this ).is( ':checked' );
			$( "input:checkbox[class!='hide-if-no-js pdtr-check-plugin-all'][class!='pdtr-check-all'][name!='checked_plugin[]'][name!='checked_core'][name!='pdtr_check_all_plugins']" ).prop( 'checked', c );
		});
		$( '.pdtr-check-plugin-all' ).on( 'click', function() {
			var c = $( this ).is( ':checked' );
			$( "input:checkbox[class!='hide-if-no-js pdtr-check-theme-all'][class!='pdtr-check-all'][name!='checked_theme[]'][name!='checked_core'][name!='pdtr_check_all_plugins']" ).prop( 'checked', c );
		});
		$( '.pdtr-check-all' ).on( 'click', function() {
			var c = $( this ).is( ':checked' );
			$( "input:checkbox" ).prop( 'checked', c );
		});
	});
})(jQuery);