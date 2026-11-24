/**
 * The field editor of the report form: add a row from the template, remove one, drag to reorder, and
 * detect the fields from the query (the columns one row of its result has, over admin-ajax).
 * The input names carry the row index; a new row takes the next free one.
 */
/* global jQuery, ajaxurl */
jQuery( function ( $ ) {
	const wrap = $( '.er-fields' );

	if ( ! wrap.length ) {
		return;
	}

	const list = wrap.find( '.er-fields__list' );
	const template = wrap.find( '.er-fields__template' ).html();
	const note = wrap.find( '.er-fields__note' );
	let next = parseInt( wrap.data( 'next-index' ), 10 ) || 0;

	// A row from the template, with the next index; the name and the type when given.
	function addRow( name, type ) {
		list.append( template.split( '__i__' ).join( String( next ) ) );
		next += 1;

		const row = list.children().last();

		if ( name ) {
			row.find( 'input[name^="field_name["]' ).val( name );
		}

		if ( type ) {
			row.find( 'select[name^="field_type["]' ).val( type );
			// A LIKE over a date or a number finds nothing worth the cost: a detected field is searchable when it is text.
			row.find( 'input[type="checkbox"][name^="field_search["]' ).prop(
				'checked',
				'text' === type
			);
		}

		return row;
	}

	// The query as typed: the code editor holds it when the user has syntax highlighting on.
	function queryText() {
		const field = document.getElementById( 'admin_ui_sql_query' );

		if ( ! field ) {
			return '';
		}

		if ( field.exportsReportsCodeMirror ) {
			return field.exportsReportsCodeMirror.getValue();
		}

		return field.value;
	}

	wrap.on( 'click', '.er-fields__add', function () {
		addRow( '', '' )
			.find( 'input[type="text"]' )
			.first()
			.trigger( 'focus' );
	} );

	wrap.on( 'click', '.er-fields__detect', function () {
		const sql = queryText().trim();

		if ( '' === sql ) {
			note.text( wrap.data( 'detect-empty' ) );
			return;
		}

		const button = $( this );

		button.prop( 'disabled', true );
		note.text( wrap.data( 'detecting' ) );

		$.post( ajaxurl, {
			action: 'exports_reports_detect_columns',
			_wpnonce: wrap.data( 'detect-nonce' ),
			sql,
		} )
			.done( function ( answer ) {
				if ( ! answer || ! answer.success ) {
					note.text(
						( answer && answer.data && answer.data.message ) ||
							wrap.data( 'detect-failed' )
					);
					return;
				}

				const listed = list
					.find( 'input[name^="field_name["]' )
					.map( function () {
						return $( this ).val().trim().toLowerCase();
					} )
					.get();
				let added = 0;
				let known = 0;

				( answer.data.columns || [] ).forEach( function ( column ) {
					if ( -1 !== listed.indexOf( column.name.toLowerCase() ) ) {
						known += 1;
						return;
					}

					addRow( column.name, column.type );
					listed.push( column.name.toLowerCase() );
					added += 1;
				} );

				note.text(
					wrap
						.data( 'detected' )
						.replace( '%1$s', String( added ) )
						.replace( '%2$s', String( known ) )
				);
			} )
			.fail( function () {
				note.text( wrap.data( 'detect-failed' ) );
			} )
			.always( function () {
				button.prop( 'disabled', false );
			} );
	} );

	wrap.on( 'click', '.er-field__remove', function () {
		// eslint-disable-next-line no-alert -- the editor asks before it removes a field.
		if ( window.confirm( wrap.data( 'confirm-remove' ) ) ) {
			$( this ).closest( '.er-field' ).remove();
		}
	} );

	list.sortable( {
		axis: 'y',
		handle: '.er-field__handle',
		placeholder: 'er-field er-field--placeholder',
		forcePlaceholderSize: true,
	} );
} );
