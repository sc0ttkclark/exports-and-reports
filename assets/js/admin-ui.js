/**
 * The report screens: rows dragged into order when the screen reorders, the delete links' confirmation,
 * the export buttons and the button that removes an export, which run in the background and show their
 * message without leaving the page, and the pagination header's page field, which goes back to page 1
 * when the form is submitted for anything but a page change.
 */
/* global jQuery */
jQuery( function ( $ ) {
	// The page has loaded: the report it was loading, or the export it was writing, is done (or failed, and the screen says so).
	$( '.exports-reports-progress, .exports-reports-loading' ).remove();

	// The export buttons and the button that removes an export each name their form (templates/manage.php).
	const forms = $(
		'#exports-reports-export, #exports-reports-remove-export'
	);
	const formButtons =
		'button[form="exports-reports-export"], button[form="exports-reports-remove-export"]';

	if ( forms.length && window.fetch && window.FormData ) {
		const heading = $( '.wp-admin-ui-wrap > h2' ).first();
		const place = heading.length ? heading : forms.first();

		$( document ).on( 'click', formButtons, function ( event ) {
			const button = $( this );
			const form = $( '#' + button.attr( 'form' ) );

			if ( ! form.length ) {
				return;
			}

			event.preventDefault();

			const buttons = $( formButtons );
			const body = new FormData( form[ 0 ] );
			const notice = $(
				'<div class="notice notice-info exports-reports-progress"><p><span class="spinner is-active"></span></p></div>'
			);

			notice
				.find( 'p' )
				.append( document.createTextNode( form.data( 'preparing' ) ) );
			body.append( button.attr( 'name' ), button.val() );

			$(
				'.exports-reports-progress, .exports-reports-export-result'
			).remove();
			place.after( notice );
			buttons.prop( 'disabled', true );

			window
				.fetch( form.data( 'ajax' ), {
					method: 'POST',
					credentials: 'same-origin',
					body,
				} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( answer ) {
					const data = answer.data || {};

					notice.remove();
					buttons.prop( 'disabled', false );

					if ( data.html ) {
						place.after(
							$( data.html ).addClass(
								'exports-reports-export-result'
							)
						);
					}

					if ( answer.success && data.download ) {
						// The file is sent as an attachment: the page stays.
						window.location.assign( data.download );
					}
				} )
				.catch( function () {
					// The background request failed before an answer came: the form submits the usual way.
					notice.remove();
					buttons.prop( 'disabled', false );
					$( '<input type="hidden" />' )
						.attr( 'name', button.attr( 'name' ) )
						.val( button.val() )
						.appendTo( form );
					form[ 0 ].submit();
				} );
		} );
	}

	const sortable = $( '.admin_ui_reorder_form tbody' );

	if ( sortable.length && $.fn.sortable ) {
		sortable.sortable( { axis: 'y', handle: '.dragme' } );
	}

	$( document ).on(
		'click',
		'a[data-exports-reports-confirm]',
		function ( event ) {
			if (
				// eslint-disable-next-line no-alert -- the delete link asks before it deletes.
				! window.confirm(
					$( this ).attr( 'data-exports-reports-confirm' )
				)
			) {
				event.preventDefault();
			}
		}
	);

	const pageInput = $( 'input.current-page' );

	if ( pageInput.length ) {
		const currentPage = pageInput.val();

		pageInput.closest( 'form' ).on( 'submit', function () {
			const action = $( 'select[name="action"]' );
			const actionBulk = $( 'select[name="action_bulk"]' );
			const noAction =
				( 1 > action.length || -1 === parseInt( action.val(), 10 ) ) &&
				( 1 > actionBulk.length ||
					-1 === parseInt( actionBulk.val(), 10 ) );

			if ( noAction && pageInput.val() === currentPage ) {
				pageInput.val( '1' );
			}
		} );
	}
} );
