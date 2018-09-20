jQuery( function ( $ ) {

	// Store the original query params to test for changes later
	var originalQueryParams = {};
	location.search.substr( 1 ).split( "&" ).forEach( function ( item ) {
		originalQueryParams[item.split( "=" )[0]] = item.split( "=" )[1]
	} );

	// Reset the pg param on form submit if any filter value change
	$( '#posts-filter' ).on( 'submit', function ( e ) {

		// Loop through the form values
		var form_params = $( this ).serializeArray();

		for ( var i = 0; i < form_params.length; i++ ) {

			var this_name = form_params[i].name;
			var this_value = encodeURIComponent( form_params[i].value );

			// Was there an original value for this key?
			if ( originalQueryParams.hasOwnProperty( this_name ) ) {

				// Original value has changed?
				if ( originalQueryParams[this_name] !== this_value ) {
					$( '#posts-filter [name=pg]' ).remove();
				}
			}
			// The param wasn't originally on the URL, so any non-empty value now means a change
			else {
				if ( this_value !== '' ) {
					$( '#posts-filter [name=pg]' ).remove();
				}
			}

		}

	} );

} );