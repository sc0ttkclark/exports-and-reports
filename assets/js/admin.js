/* global jQuery */
jQuery( function ( $ ) {
	// The Copy button of the Settings page's token notice: the clipboard where the page may use it, the text
	// selected for a Ctrl+C otherwise.
	$( document ).on( 'click', '.exports-reports-copy', function () {
		const button = $( this );
		const text = String( button.data( 'copy' ) );
		const label = button.text();
		const done = function () {
			button.text( button.data( 'copied' ) );
			window.setTimeout( function () {
				button.text( label );
			}, 2000 );
		};
		const select = function () {
			const code = button.prev( 'code' )[ 0 ];
			const view = code && code.ownerDocument.defaultView;

			if ( view && view.getSelection ) {
				const range = code.ownerDocument.createRange();
				const selection = view.getSelection();

				range.selectNodeContents( code );
				selection.removeAllRanges();
				selection.addRange( range );
			}
		};

		if ( window.navigator.clipboard && window.isSecureContext ) {
			window.navigator.clipboard
				.writeText( text )
				.then( done )
				.catch( select );
		} else {
			select();
		}
	} );

	// The report selector goes to the chosen report.
	$( '#report' ).on( 'change', function () {
		window.location = this.value;
	} );

	// The query string the page was opened with, to compare the filter form against when it is submitted.
	const originalQueryParams = new URLSearchParams( window.location.search );

	// A search with changed values starts on page 1: the page field goes when any value of the form
	// differs from the one the page was opened with, or is new and not empty.
	$( '#posts-filter' ).on( 'submit', function () {
		const current = {};

		$( this )
			.serializeArray()
			.forEach( function ( field ) {
				if ( ! current[ field.name ] ) {
					current[ field.name ] = [];
				}

				current[ field.name ].push( field.value );
			} );

		const changed = Object.keys( current ).some( function ( name ) {
			const original = originalQueryParams.getAll( name );

			if ( 0 === original.length ) {
				return current[ name ].some( function ( value ) {
					return '' !== value;
				} );
			}

			return (
				original.length !== current[ name ].length ||
				original.some( function ( value, index ) {
					return value !== current[ name ][ index ];
				} )
			);
		} );

		if ( changed ) {
			$( this ).find( 'input.current-page' ).remove();
		}
	} );
} );
