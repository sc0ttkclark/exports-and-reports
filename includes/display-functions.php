<?php
/**
 * The functions a report definition may name: the list, the names recorded from the stored definitions
 * when the list was first asked for, and the notice that names them.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- one read of the plugin's own reports table, whose result is kept in an option.

/**
 * The functions a report definition may name, as a field's Display function or as the helper of a
 * {@field,helper} tag: the ones the site's code registers through the exports_reports_display_callbacks
 * filter, and the ones the site's definitions named when the list was first asked for
 * (exports_reports_record_display_callbacks()). Exports_Reports_Admin_UI::is_allowed_callback() calls
 * no other.
 *
 * @since 1.3.0
 *
 * @return string[] Function names.
 */
function exports_reports_display_callbacks() {
	$recorded = get_option( 'exports_reports_display_callbacks', null );

	if ( ! is_array( $recorded ) ) {
		$recorded = exports_reports_record_display_callbacks();
	}

	/**
	 * Filters the functions a report definition may name as a Display function or a template helper.
	 * A plugin or a theme adds its own:
	 *
	 *     add_filter( 'exports_reports_display_callbacks', function ( $callbacks ) {
	 *         $callbacks[] = 'my_plugin_format_money';
	 *         return $callbacks;
	 *     } );
	 *
	 * A Display function receives the value, the row, the column, the column's attributes and the screen,
	 * and returns what the cell shows.
	 *
	 * @since 1.3.0
	 *
	 * @param string[] $callbacks The functions the site's definitions named before 1.3.0.
	 */
	$callbacks = apply_filters( 'exports_reports_display_callbacks', $recorded );

	return array_values( array_unique( array_filter( array_map( 'strval', (array) $callbacks ) ) ) );
}

/**
 * Records the functions the stored report definitions name as Display functions, so that the reports of a
 * site that comes from a version before 1.3.0 keep calling them, and keeps the names for the notice that
 * tells its managers (exports_reports_display_callbacks_notice()). Runs once, when the list is first
 * asked for: on the first admin request after the update, or on the first report a script reads before
 * that. A name a field takes afterwards is not recorded: the site's code lists it.
 *
 * @since 1.3.0
 *
 * @return string[] The names, sorted; empty when the reports table cannot be read yet (nothing is recorded then).
 */
function exports_reports_record_display_callbacks() {
	/** @var wpdb $wpdb */ global $wpdb;

	$suppressed  = $wpdb->suppress_errors();
	$definitions = $wpdb->get_col( "SELECT `field_data` FROM {$wpdb->exports_reports_reports} WHERE `field_data` LIKE '%custom_display%'" );
	$failed      = '' !== (string) $wpdb->last_error;

	$wpdb->suppress_errors( $suppressed );

	if ( $failed ) {
		return array();
	}

	$names = array();

	foreach ( $definitions as $definition ) {
		$fields = json_decode( (string) $definition, true );

		foreach ( is_array( $fields ) ? $fields : array() as $field ) {
			if ( is_array( $field ) && isset( $field['custom_display'] ) && is_string( $field['custom_display'] ) && '' !== trim( $field['custom_display'] ) ) {
				$names[] = trim( $field['custom_display'] );
			}
		}
	}

	$names = array_values( array_unique( $names ) );

	sort( $names );

	update_option( 'exports_reports_display_callbacks', $names );

	if ( array() !== $names ) {
		update_option( 'exports_reports_display_callbacks_notice', $names, false );
	}

	return $names;
}

/**
 * Tells the users who manage reports, once, which Display functions the update listed for them, and how a
 * function is listed from now on: on every admin page until one of them dismisses it.
 *
 * @since 1.3.0
 */
function exports_reports_display_callbacks_notice() {
	$names = get_option( 'exports_reports_display_callbacks_notice' );

	if ( ! is_array( $names ) || array() === $names || ! exports_reports_current_user_can_manage() ) {
		return;
	}

	$listed = array();

	foreach ( $names as $name ) {
		$listed[] = '<code>' . esc_html( (string) $name ) . '</code>';
	}

	$dismiss = wp_nonce_url( add_query_arg( 'exports_reports_dismiss', 'display-functions' ), 'exports-reports-dismiss' );

	echo '<div class="notice notice-warning"><p>';
	echo wp_kses(
		sprintf(
			/* translators: 1: a list of function names, 2: the name of a filter. */
			__( '<strong>Exports and Reports:</strong> a report now calls a Display function only when the site lists it. The functions your reports name today were listed for you: %1$s. Check that each is one you want a report to call. A function you put into a field from now on is called once your code lists it through the %2$s filter (see the plugin\'s readme).', 'exports-and-reports' ),
			implode( ', ', $listed ),
			'<code>exports_reports_display_callbacks</code>'
		),
		array(
			'strong' => array(),
			'code'   => array(),
		)
	);
	echo ' <a href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Dismiss this notice', 'exports-and-reports' ) . '</a></p></div>';
}

/**
 * Dismisses the notice a manager of reports clicked away (admin_init).
 *
 * @since 1.3.0
 */
function exports_reports_dismiss_notice() {
	if ( ! isset( $_GET['exports_reports_dismiss'], $_GET['_wpnonce'] ) || 'display-functions' !== sanitize_key( wp_unslash( $_GET['exports_reports_dismiss'] ) ) ) {
		return;
	}

	if ( ! exports_reports_current_user_can_manage() || false === wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'exports-reports-dismiss' ) ) {
		return;
	}

	delete_option( 'exports_reports_display_callbacks_notice' );
}
