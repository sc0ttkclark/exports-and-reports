<?php
/**
 * The admin UI of Exports and Reports: the manage, edit and reorder screens of the groups and the
 * reports, the report page with its filters and pagination, and the exports. Grown from the Admin UI
 * class for WordPress plugins (1.11.7) by Scott Kingsley Clark.
 *
 * @link    https://www.scottkclark.com/
 * @author  Scott Kingsley Clark
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-exports-reports-query.php';
require_once __DIR__ . '/exporters/class-exports-reports-exporter.php';
require_once __DIR__ . '/class-exports-reports-list-table.php';

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the plugin runs the administrator's report queries and keeps its own tables; there is nothing to cache.

if ( ! defined( 'EXPORTS_REPORTS_EXPORT_DIR' ) ) {
	define( 'EXPORTS_REPORTS_EXPORT_DIR', defined( 'EXPORTS_REPORTS_EXPORT_DIR' ) ? EXPORTS_REPORTS_EXPORT_DIR : wp_get_upload_dir()['basedir'] . '/exports-and-reports' );
}

/**
 * Class Exports_Reports_Admin_UI
 *
 * One instance per screen: the options name the table or the query, the columns and what the screen
 * allows; the instance reads the request, loads the rows and dispatches the action. Its collaborators
 * do the rest: Exports_Reports_Query builds and runs the statements, Exports_Reports_List_Table draws
 * the rows, the Exports_Reports_Exporter classes write the export files, and the templates under
 * templates/ hold the markup of the screens.
 */
class Exports_Reports_Admin_UI {

	// base
	public $table              = false;
	public $identifier         = 'id';
	public $sql                = false;
	public $sql_count          = false;
	public $id                 = false;
	public $action             = 'manage';
	public $do                 = false;
	public $search             = true;
	public $filters            = array();
	public $search_query       = false;
	public $pagination         = true;
	public $page               = 1;
	public $limit              = 25;
	public $order              = false;
	public $order_dir          = 'DESC';
	public $order_default      = false;
	public $order_dir_default  = 'DESC';
	public $order_from_request = false;
	public $reorder_order      = false;
	public $reorder_order_dir  = 'ASC';
	public $api                = false;
	/** @var int The report the instance shows: Exports and Reports sets it; 0 for an instance without one. */
	public $report_id = 0;
	public $num       = '';

	// ui
	public $item    = 'Item';
	public $items   = 'Items';
	public $heading = array(
		'manage'    => 'Manage',
		'add'       => 'Add New',
		'edit'      => 'Edit',
		'duplicate' => 'Duplicate',
		'view'      => 'View',
		'reorder'   => 'Reorder',
	);
	public $css     = false;

	// actions
	public $add       = true;
	public $view      = false;
	public $edit      = true;
	public $duplicate = false;
	public $delete    = true;
	public $save      = true;
	public $readonly  = false;
	public $export    = false;
	public $reorder   = false;

	// array of custom functions to run for actions
	public $custom = array();

	// data related
	public $total = 0;

	/**
	 * When the page's row count was counted, as a Unix time, when it comes from a kept count; 0 when the
	 * page counted it itself.
	 *
	 * @since 1.2.2
	 *
	 * @var int
	 */
	public $total_counted_at = 0;

	/** @var bool|array $totals */
	public $totals = false;

	public $columns   = array();
	public $data      = array();
	public $sum_data  = array();
	public $full_data = array();
	// Delimited exports read their rows from an unbuffered result; see export() and export_sv().
	public $stream_export   = false;
	public $stream_sql      = '';
	public $row             = array();
	public $temp_row        = array();
	public $default_none    = false;
	public $search_columns  = array();
	public $form_columns    = array();
	public $view_columns    = array();
	public $export_columns  = array();
	public $reorder_columns = array();
	public $insert_id       = 0;
	public $related         = array();

	// export related
	public $exported_file = false;
	public $export_url    = false;
	public $export_type   = false;
	/**
	 * The export formats the screen offers, by type (see exports_reports_export_formats()); a report's come
	 * from the Settings page and its own definition.
	 *
	 * @since 1.1.0
	 *
	 * @var string[]
	 */
	public $export_formats = array( 'csv', 'tsv', 'pipe', 'xlsx', 'xml', 'json', 'pdf' );
	/**
	 * Whether the request asked for an export through the page's address, the way the links before 1.1.0
	 * did (action=export&export_type=...). Such a request writes nothing: the address is what a browser
	 * keeps, and the file it pointed at was written when the link was made.
	 *
	 * @since 1.1.2
	 *
	 * @var bool
	 */
	public $export_from_address = false;
	/**
	 * The database's message when the report's query (or its count query) failed on the last get_data(), '' otherwise.
	 *
	 * @since 1.1.3
	 *
	 * @var string
	 */
	public $query_error      = '';
	public $page_orientation = 'P';

	public $base_dir = '';

	/**
	 * The screen's statements: the SQL helpers and the assembly of the report query.
	 *
	 * @since 1.0.0
	 *
	 * @var Exports_Reports_Query
	 */
	public $query;
	public $base_url   = '';
	public $assets_url = '';

	/**
	 * @param bool|array $options
	 */
	public function __construct( $options = false ) {
		$this->query = new Exports_Reports_Query( $this );

		/**
		 * Fires before the admin UI reads its options.
		 *
		 * @param bool|array $options The options as given.
		 */
		do_action( 'exports_reports_pre_init', $options );
		do_action_deprecated( 'wp_admin_ui_pre_init', array( $options ), '0.9.5', 'exports_reports_pre_init' );
		/**
		 * Filters the options of a report screen before they take effect.
		 *
		 * @since 1.0.0 The options and the instance, one by one; the array form stays with wp_admin_ui_options.
		 *
		 * @param array                   $options The options the screen was given.
		 * @param Exports_Reports_Admin_UI $admin   The screen.
		 */
		$options          = apply_filters( 'exports_reports_options', $options, $this );
		$options          = $this->legacy_hook( 'options', array( $options ) );
		$this->base_dir   = __DIR__;
		$this->base_url   = EXPORTS_REPORTS_URL;
		$this->export_url = admin_url( 'admin-ajax.php' ) . '?action=' . EXPORTS_REPORTS_EXPORT_ACTION . '&exports_and_reports_download=1&_wpnonce=' . wp_create_nonce( EXPORTS_REPORTS_EXPORT_NONCE ) . '&exports_and_reports_export=';
		$this->assets_url = EXPORTS_REPORTS_URL . 'assets';
		$this->item       = __( 'Item', 'exports-and-reports' );
		$this->items      = __( 'Items', 'exports-and-reports' );
		$this->heading    = array(
			'manage'    => __( 'Manage', 'exports-and-reports' ),
			'add'       => __( 'Add New', 'exports-and-reports' ),
			'edit'      => __( 'Edit', 'exports-and-reports' ),
			'duplicate' => __( 'Duplicate', 'exports-and-reports' ),
			'view'      => __( 'View', 'exports-and-reports' ),
			'reorder'   => __( 'Reorder', 'exports-and-reports' ),
		);
		// The options first: what the request names below (the action, the page, the sort column) stands above them.
		if ( false !== $options && ! empty( $options ) ) {
			if ( ! is_array( $options ) ) {
				parse_str( $options, $options );
			}
			foreach ( $options as $option => $value ) {
				$this->{$option} = $value;
			}
		}
		$id = $this->get_var( 'id' );
		if ( false !== $id ) {
			$this->id = $id;
		}
		$action = $this->get_var(
			'action',
			false,
			array(
				'add',
				'edit',
				'duplicate',
				'view',
				'delete',
				'manage',
				'reorder',
				'export',
			)
		);
		if ( false !== $action ) {
			$this->action = $action;
		}
		$do = $this->get_var( 'do', false, array( 'save', 'create' ) );
		if ( false !== $do ) {
			$this->do = $do;
		}
		$search_query = $this->get_var( 'search_query' );
		if ( false !== $search_query ) {
			$this->search_query = $search_query;
		}
		if ( false !== $this->get_var( 'pg' ) ) {
			$this->page = max( 1, absint( $this->get_var( 'pg' ) ) );
		}
		if ( false !== $this->get_var( 'limit' ) ) {
			$this->limit = absint( $this->get_var( 'limit' ) );
		}
		$this->order_default     = $this->order;
		$this->order_dir_default = $this->order_dir;
		$order                   = $this->get_var( 'order' );
		if ( is_string( $order ) && '' !== $order ) {
			$order = preg_replace( '/([- ])/', '_', trim( $order ) );
			$order = preg_replace( '/([^0-9a-z_])/', '', strtolower( $order ) );
			$order = preg_replace( '/(_){2,}/', '_', $order );

			$this->order              = $order;
			$this->order_from_request = true;
		}
		$order_dir = $this->get_var( 'order_dir', false, array( 'ASC', 'DESC' ) );
		if ( false !== $order_dir ) {
			$this->order_dir = $order_dir;
		}
		// The export buttons, and the button that removes an export, post their forms, so the request that
		// writes or removes a file is never an address a browser keeps or revisits. An export or a removal
		// asked for through the address, as the links before 1.1.0 (and the removal links before 1.2.0) did,
		// does nothing.
		$posted_export = '1' === $this->get_var( 'exports_reports_export', false, '1', 'post' );
		if ( $posted_export && false !== $this->export ) {
			$this->action = 'export';
		}
		if ( 'export' === $this->action ) {
			$source      = $posted_export ? 'post' : false;
			$export_type = $this->get_var( 'export_type', false, $this->allowed_export_types(), $source );
			if ( false !== $export_type && $posted_export ) {
				$this->export_type = $export_type;
			} elseif ( false !== $this->get_var( 'export_type' ) || false !== $this->get_var( 'remove_export' ) ) {
				$this->export_from_address = true;
			}
		}

		if ( false !== $this->readonly ) {
			$this->add     = false;
			$this->edit    = false;
			$this->delete  = false;
			$this->save    = false;
			$this->reorder = false;
		}
		if ( false !== $this->reorder && false === $this->reorder_order ) {
			$this->reorder_order = $this->reorder;
		}
		if ( ! empty( $this->columns ) ) {
			$this->columns = $this->setup_columns();
		}
		if ( ! empty( $this->filters ) && ! is_array( $this->filters ) ) {
			$this->filters = explode( ',', $this->filters );
		}
		/**
		 * Fires once a report screen is set up.
		 *
		 * @since 1.0.0 The options and the instance, one by one.
		 *
		 * @param array                   $options The options.
		 * @param Exports_Reports_Admin_UI $admin   The screen.
		 */
		do_action( 'exports_reports_post_init', $options, $this );
		$this->legacy_hook( 'post_init', array( $options ) );
	}

	/**
	 * A request value, unslashed and sanitized (see clean_input()), or $fallback when it is absent or not among $allowed.
	 *
	 * @param string            $index    The request key.
	 * @param mixed             $fallback What to return when the key is absent or not allowed.
	 * @param bool|string|array $allowed  The accepted values, or false for any.
	 * @param bool|string|array $source   The array to read: 'post' for $_POST, an array of your own, or $_GET.
	 *
	 * @return mixed The value, as filtered by exports_reports_get_var.
	 */
	public function get_var( $index, $fallback = false, $allowed = false, $source = false ) {
		if ( ! is_array( $source ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- a read accessor; save(), reorder() and the delete action verify their nonces before anything is written.
			$source = ( 'post' === $source ) ? $_POST : $_GET;
		}
		if ( false !== $allowed && ! is_array( $allowed ) ) {
			$allowed = array( $allowed );
		}
		$value = $fallback;
		if ( isset( $source[ $index ] ) ) {
			$candidate = $this->clean_input( $source[ $index ] );
			if ( false === $allowed || in_array( $candidate, $allowed, true ) ) {
				$value = $candidate;
			}
		}

		/**
		 * Filters a request value as the screen reads it.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed                   $value    The value, or the fallback.
		 * @param string                  $index    The request key.
		 * @param mixed                   $fallback The fallback.
		 * @param array|false             $allowed  The values allowed, or false for any.
		 * @param array|false             $source   The array read instead of the request, or false.
		 * @param Exports_Reports_Admin_UI $admin    The screen.
		 */
		$value = apply_filters( 'exports_reports_get_var', $value, $index, $fallback, $allowed, $source, $this );

		return $this->legacy_hook( 'get_var', array( $value, $index, $fallback, $allowed, $source ) );
	}

	/**
	 * Unslashes and sanitises a request value: text through sanitize_text_field(), arrays recursively, keys included.
	 *
	 * @param mixed $value A request value.
	 *
	 * @return string|array
	 */
	public function clean_input( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean[ sanitize_text_field( (string) $key ) ] = $this->clean_input( $item );
			}

			return $clean;
		}

		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * The query string, unslashed and sanitized: the state that links and hidden fields carry forward.
	 *
	 * @return array
	 */
	public function query_vars() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation state; nothing is written from it.
		return $this->clean_input( $_GET );
	}

	/**
	 * The request URI, unslashed and escaped for use in a URL; empty on the command line.
	 *
	 * @return string
	 */
	public function request_uri() {
		return isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	}

	/**
	 * @param bool|array $exclude
	 */
	public function hidden_vars( $exclude = false ) {
		/**
		 * Filters the request keys the search form does not carry as hidden fields.
		 *
		 * @since 1.0.0
		 *
		 * @param array|false             $exclude The keys, or false for the default list.
		 * @param Exports_Reports_Admin_UI $admin   The screen.
		 */
		$exclude = apply_filters( 'exports_reports_hidden_vars', $exclude, $this );
		$exclude = $this->legacy_hook( 'hidden_vars', array( $exclude ) );
		if ( false === $exclude ) {
			$exclude = array();
		}
		if ( ! is_array( $exclude ) ) {
			$exclude = explode( ',', $exclude );
		}
		foreach ( $this->query_vars() as $k => $v ) {
			if ( ! is_scalar( $v ) || in_array( $k, $exclude, true ) ) {
				continue;
			}
			?>
			<input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>" />
			<?php
		}
	}

	/**
	 * Runs the wp_admin_ui_<name> hook of the versions before 0.9.5, deprecated, after the
	 * exports_reports_<name> hook has run. Its callbacks receive the arguments the way they always did:
	 * as one array indexed from 1 and, second, an array of this instance and the hook name. The first
	 * argument comes back as they left it.
	 *
	 * @since 1.0.0 The exports_reports_<name> hooks pass their arguments one by one; only the old names keep the array.
	 *
	 * @param string $name The hook name without its prefix.
	 * @param array  $args The arguments of the exports_reports_<name> hook, without this instance.
	 *
	 * @return mixed The first argument, or null when the hook has none.
	 */
	public function legacy_hook( $name, array $args = array() ) {
		$first = isset( $args[0] ) ? $args[0] : null;

		if ( ! has_filter( 'wp_admin_ui_' . $name ) ) {
			return $first;
		}

		$legacy = array();

		foreach ( array_values( $args ) as $position => $arg ) {
			$legacy[ $position + 1 ] = $arg;
		}

		$legacy = apply_filters_deprecated( 'wp_admin_ui_' . $name, array( $legacy, array( &$this, 'wp_admin_ui_' . $name ) ), '0.9.5', 'exports_reports_' . $name );

		return isset( $legacy[1] ) ? $legacy[1] : $first;
	}

	/**
	 * Whether a function named in a report definition may be called back with a value: a function
	 * WordPress, a plugin or a theme defines. PHP's own functions are not called: none of them takes the
	 * arguments the callbacks receive, and the variadic ones among them could reach anything. The
	 * exports_reports_allowed_callback filter decides for a name this rule does not settle the way a site needs.
	 *
	 * @param mixed $callback The function name.
	 *
	 * @return bool
	 */
	public function is_allowed_callback( $callback ) {
		$allowed = false;

		if ( is_string( $callback ) && '' !== $callback && function_exists( $callback ) ) {
			$allowed = ! ( new ReflectionFunction( $callback ) )->isInternal();
		}

		/**
		 * Filters whether a function named in a report definition is called back.
		 *
		 * @since 1.0.0 The arguments one by one; the array form stays with wp_admin_ui_allowed_callback.
		 *
		 * @param bool                    $allowed  Whether it is called.
		 * @param string                  $callback The function name.
		 * @param Exports_Reports_Admin_UI $admin    The screen.
		 */
		$allowed = apply_filters( 'exports_reports_allowed_callback', $allowed, $callback, $this );

		return false !== $this->legacy_hook( 'allowed_callback', array( $allowed, $callback ) );
	}

	/**
	 * A request value as a list of non-empty strings: a multiple select sends an array, the other
	 * inputs a string, and the SQL and the form read both the same way.
	 *
	 * @param string      $name    The query variable.
	 * @param bool|string $fallback The value when the request has none.
	 *
	 * @return string[]
	 */
	public function filter_values( $name, $fallback = false ) {
		$value  = $this->get_var( $name, $fallback );
		$values = array();

		if ( false === $value || null === $value ) {
			return $values;
		}

		foreach ( (array) $value as $item ) {
			if ( is_scalar( $item ) && '' !== trim( (string) $item ) ) {
				$values[] = (string) $item;
			}
		}

		return $values;
	}

	/**
	 * The name of a new export file: the report's name as a slug of ASCII letters, digits and
	 * underscores ('export' when nothing of it survives), the time, a random suffix and the extension.
	 * A name the download link can carry as it is: export.php and the REST route read it back through
	 * sanitize_file_name().
	 *
	 * @param string $extension The extension, without the dot.
	 *
	 * @return string
	 */
	public function export_file_name( $extension ) {
		$slug = preg_replace( '/%[0-9a-f]{2}/i', '', sanitize_title( (string) $this->items ) );
		$slug = trim( (string) preg_replace( '/[^a-z0-9]+/', '_', strtolower( (string) $slug ) ), '_' );

		if ( '' === $slug ) {
			$slug = 'export';
		}

		return $slug . '_' . date_i18n( 'm-d-Y_H-i-s' ) . '_' . wp_generate_password( 8, false ) . '.' . $extension;
	}

	/**
	 * The file an export writes: export_file_name() after the exports_reports_export_file filter.
	 *
	 * @since 0.9.5
	 *
	 * @param string $extension The extension, without the dot.
	 *
	 * @return string The file name, in the export directory.
	 */
	public function export_file( $extension ) {
		/**
		 * Filters the name of the file an export writes.
		 *
		 * @param string                   $export_file The name export_file_name() made.
		 * @param string                   $export_type The export type (csv, tsv, pipe, custom, xml, xlsx, json, pdf).
		 * @param string                   $extension   The file extension.
		 * @param string                   $items       The report's plural label.
		 * @param Exports_Reports_Admin_UI $admin       The report.
		 */
		$export_file = apply_filters( 'exports_reports_export_file', $this->export_file_name( $extension ), $this->export_type, $extension, $this->items, $this );

		return apply_filters_deprecated( 'wp_admin_ui_export_file', array( $export_file, $this->export_type, $this->export_type, $this->items, $this ), '0.9.5', 'exports_reports_export_file' );
	}

	/**
	 * The columns an export writes, each with its attributes, in order.
	 *
	 * @since 0.9.5
	 *
	 * @return array<string, array> Keyed by column.
	 */
	public function exported_columns() {
		$columns = array();

		foreach ( (array) $this->export_columns as $key => $attributes ) {
			if ( ! is_array( $attributes ) ) {
				$key        = $attributes;
				$attributes = $this->setup_column( $key );
			}

			if ( false !== $attributes['export'] ) {
				$columns[ $key ] = $attributes;
			}
		}

		return $columns;
	}

	/**
	 * One row of an export: the exported columns' values, formatted by field_value() and the column's
	 * display function.
	 *
	 * @since 0.9.5
	 *
	 * @param array $item    The row as the query returned it.
	 * @param array $columns exported_columns().
	 *
	 * @return array<string, mixed> Keyed by column.
	 */
	public function export_row( array $item, array $columns ) {
		$row = array();

		foreach ( $columns as $key => $attributes ) {
			$item[ $key ] = $this->field_value( $item[ $key ], $key, $attributes );

			if ( false !== $attributes['custom_display'] && function_exists( "{$attributes['custom_display']}" ) ) {
				$item[ $key ] = $attributes['custom_display']( $item[ $key ], $item, $key, $attributes, $this );
			}

			$row[ $key ] = $item[ $key ];
		}

		return $row;
	}

	/**
	 * Tells the user an export is written: the message with the download and removal links, and the
	 * script that opens the download.
	 *
	 * @since 0.9.5
	 *
	 * @param string $export_file The file name, in the export directory.
	 * @param string $format      The format, for the message (csv, pdf, ...).
	 */
	public function export_ready( $export_file, $format ) {
		$download = $this->export_url . rawurlencode( $export_file );

		$this->message(
			sprintf(
				/* translators: 1: a link to the export file, 2: a link that removes it. */
				__( '<strong>Success:</strong> Your export is ready, the download should begin in a few moments. If it does not, %1$s.<br /><br />When you are done with your export, %2$s; otherwise the export will be deleted within 24 hours of generation.', 'exports-and-reports' ),
				/* translators: %s: the export format (CSV, PDF, ...). */
				'<a href="' . esc_url( $download ) . '" target="_blank">' . esc_html( sprintf( __( 'click here to download your %s export file', 'exports-and-reports' ), strtoupper( $format ) ) ) . '</a>',
				// A button of the removal form on the list screen (see templates/manage.php): a POST, like the export itself.
				'<button type="submit" class="button-link exports-reports-remove-export" form="exports-reports-remove-export" name="remove_export" value="' . esc_attr( $export_file ) . '">' . esc_html__( 'click here to remove it', 'exports-and-reports' ) . '</button>'
			)
		);
		wp_print_inline_script_tag( 'window.open( ' . wp_json_encode( $download ) . ' );' );
	}

	/**
	 * The export file the request asks to remove, posted by the button export_ready() prints: the file name
	 * alone, or '' when the request removes nothing. An address that names a file (the links before 1.2.0)
	 * removes nothing; see export_from_address.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function removing_export() {
		return str_replace( '/', '', sanitize_file_name( (string) $this->get_var( 'remove_export', '', false, 'post' ) ) );
	}

	/**
	 * The extensions export files have.
	 *
	 * @return string[]
	 */
	public static function export_file_extensions() {
		return array( 'csv', 'tsv', 'txt', 'xlsx', 'xml', 'json', 'pdf' );
	}

	/**
	 * Whether a file name is one an export writes: an export extension, and not one of the files that
	 * keep the directory from being served.
	 *
	 * @param mixed $file The file name.
	 *
	 * @return bool
	 */
	public static function is_export_file_name( $file ) {
		$file = (string) $file;

		return '' !== $file && ! in_array( $file, array( 'index.php', '.htaccess' ), true ) && in_array( strtolower( (string) pathinfo( $file, PATHINFO_EXTENSION ) ), self::export_file_extensions(), true );
	}

	/**
	 * An address that names an export: var_update() with the action and the nonce export() checks. The
	 * screen's buttons post their forms instead, and an address like this writes and removes nothing since
	 * 1.1.2 and 1.2.0 (see export_from_address); kept for code that built such links.
	 *
	 * @param array $args The query arguments (export_type, or remove_export).
	 *
	 * @return string
	 */
	public function export_link( $args ) {
		return $this->var_update( array_merge( array( 'action' => 'export' ), $args, array( '_wpnonce' => wp_create_nonce( 'exports-reports-export-request' ) ) ) );
	}

	/**
	 * Whether the request carries the nonce of the export form or of export_link(), posted or in the address.
	 * The REST route does not come through here: its token or user is the credential.
	 *
	 * @since 1.1.0 The nonce may be posted.
	 *
	 * @return bool
	 */
	public function verify_export_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- this is the verification.
		$source = isset( $_POST['_wpnonce'] ) ? $_POST : $_GET;
		$nonce  = isset( $source['_wpnonce'] ) ? sanitize_key( wp_unslash( $source['_wpnonce'] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'exports-reports-export-request' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Every export format, the label of each by its type.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, string>
	 */
	protected static function format_labels() {
		if ( function_exists( 'exports_reports_export_formats' ) ) {
			return exports_reports_export_formats();
		}

		return array(
			'csv'  => 'CSV',
			'tsv'  => 'TSV',
			'pipe' => 'TXT',
			'xlsx' => 'XLSX',
			'xml'  => 'XML',
			'json' => 'JSON',
			'pdf'  => 'PDF',
		);
	}

	/**
	 * The export types the screen accepts: its formats.
	 *
	 * @since 1.1.0
	 *
	 * @return string[]
	 */
	public function allowed_export_types() {
		return array_values( array_intersect( array_keys( self::format_labels() ), array_map( 'strval', (array) $this->export_formats ) ) );
	}

	/**
	 * The export buttons the screen shows: the label of each by its type, in the plugin's order.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, string>
	 */
	public function export_formats_offered() {
		return array_intersect_key( self::format_labels(), array_flip( $this->allowed_export_types() ) );
	}

	/**
	 * The address the export form posts to: the screen's current address with its search, sort and filters,
	 * the report named outright on a report page (the page's default report changes with the order), and
	 * nothing of an earlier export or a page number.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function export_form_action() {
		$vars = 0 < (int) $this->report_id ? array( 'report' => (string) (int) $this->report_id ) : false;

		return $this->var_update( $vars, array( 'search_query', 'order', 'order_dir' ) );
	}

	/**
	 * The admin-ajax address the screen's script posts the export form to: the same query as
	 * export_form_action(), with the plugin's action.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function export_ajax_url() {
		parse_str( (string) wp_parse_url( $this->export_form_action(), PHP_URL_QUERY ), $vars );

		$vars['action'] = 'exports_reports_export_report';

		return add_query_arg( $vars, admin_url( 'admin-ajax.php' ) );
	}

	/**
	 * @param array|bool  $vars      The query variables to set, or false for the current ones.
	 * @param array|bool  $allowed   Excluded variables to keep anyway.
	 * @param bool|string $url       The URL to build on, or false for the current request.
	 * @param bool        $exclusive Whether to drop every current variable first.
	 *
	 * @return string
	 */
	public function var_update( $vars = false, $allowed = false, $url = false, $exclusive = false ) {
		$excluded = array(
			'do',
			'id',
			'pg',
			'search_query',
			'order',
			'order_dir',
			'limit',
			'action',
			'exports_and_reports_download',
			'exports_and_reports_export',
			'export_type',
			'remove_export',
			'_wpnonce',
			'updated',
			'duplicate',
		);
		if ( false === $allowed ) {
			$allowed = array();
		}
		$get = $this->query_vars();
		if ( is_array( $vars ) ) {
			if ( false === $exclusive ) {
				foreach ( $excluded as $exclusion ) {
					if ( isset( $get[ $exclusion ] ) && ! isset( $vars[ $exclusion ] ) && ! in_array( $exclusion, $allowed, true ) ) {
						unset( $get[ $exclusion ] );
					}
				}
			} else {
				$get = array();
			}
			foreach ( $vars as $key => $val ) {
				if ( 0 < strlen( $val ) ) {
					$get[ $key ] = $val;
				} elseif ( isset( $get[ $key ] ) ) {
					unset( $get[ $key ] );
				}
			}
		}
		if ( false === $url ) {
			$url = '';
		} else {
			$url = explode( '?', $this->request_uri() );
			$url = explode( '#', $url[0] );
			$url = $url[0];
		}

		/**
		 * Filters a link of the screen after its query has been built.
		 *
		 * @since 1.0.0
		 *
		 * @param string                  $link    The link.
		 * @param array                   $vars    The query variables set or removed.
		 * @param array|false             $allowed The variables carried over from the request, or false for all.
		 * @param string                  $url     The link without its query.
		 * @param Exports_Reports_Admin_UI $admin   The screen.
		 */
		$link = apply_filters( 'exports_reports_var_update', $url . '?' . http_build_query( $get ), $vars, $allowed, $url, $this );

		return $this->legacy_hook( 'var_update', array( $link, $vars, $allowed, $url ) );
	}

	/**
	 * Escapes a value for a statement that is not prepared; see Exports_Reports_Query::sanitize().
	 *
	 * @since 1.0.0 Forwards to the screen's Exports_Reports_Query.
	 */
	public function sanitize( $input ) {
		return $this->query->sanitize( $input );
	}

	/**
	 * A column or table name as a quoted identifier; see Exports_Reports_Query::backtick().
	 *
	 * @since 1.0.0 Forwards to the screen's Exports_Reports_Query.
	 */
	public function backtick( $name ) {
		return $this->query->backtick( $name );
	}

	/**
	 * The names of the report's columns; see Exports_Reports_Query::column_names().
	 *
	 * @since 1.0.0 Forwards to the screen's Exports_Reports_Query.
	 */
	public function column_names() {
		return $this->query->column_names();
	}

	/**
	 * A value as a SQL string literal; see Exports_Reports_Query::quote().
	 *
	 * @since 1.0.0 Forwards to the screen's Exports_Reports_Query.
	 */
	public function quote( $value ) {
		return $this->query->quote( $value );
	}

	/**
	 * Runs a statement and returns its rows; see Exports_Reports_Query::rows().
	 *
	 * @since 1.0.0 Forwards to the screen's Exports_Reports_Query.
	 */
	public function rows( $sql, $output = OBJECT ) {
		return $this->query->rows( $sql, $output );
	}

	/**
	 * Runs a statement that writes to the screen's own table; see Exports_Reports_Query::execute().
	 *
	 * @since 1.0.0 Forwards to the screen's Exports_Reports_Query.
	 */
	public function execute( $sql ) {
		return $this->query->execute( $sql );
	}

	/**
	 * Removes the slashes a request adds; see Exports_Reports_Query::unsanitize().
	 *
	 * @since 1.0.0 Forwards to the screen's Exports_Reports_Query.
	 */
	public function unsanitize( $input ) {
		return $this->query->unsanitize( $input );
	}

	/**
	 * The WHERE or HAVING text the filters add to a statement; see Exports_Reports_Query::clause_sql().
	 *
	 * @since 1.0.0 Forwards to the screen's Exports_Reports_Query.
	 */
	public function clause_sql( $sql, $keyword, array $conditions ) {
		return $this->query->clause_sql( $sql, $keyword, $conditions );
	}

	/**
	 * Places the clauses into a statement; see Exports_Reports_Query::place_clauses().
	 *
	 * @since 1.0.0 Forwards to the screen's Exports_Reports_Query.
	 */
	public function place_clauses( $sql, array $clauses, array $variables = array() ) {
		return $this->query->place_clauses( $sql, $clauses, $variables );
	}

	public function catch_columns( $full = false ) {
		$data = $this->data;
		if ( $full ) {
			$data = $this->full_data;
		}
		if ( ! empty( $data ) && is_array( $data ) && isset( $data[0] ) ) {
			$data = current( $data );
		}
		$columns = array();
		foreach ( (array) $data as $column => $value ) {
			$column = trim( str_replace( '`', '', $column ) );
			if ( 0 < strlen( $column ) ) {
				$columns[] = $column;
			}
		}
		$this->columns = $this->setup_columns( $columns, 'columns', true );
	}

	/**
	 * @param null|array $columns
	 * @param string     $which
	 * @param bool       $init
	 *
	 * @return bool
	 */
	public function setup_columns( $columns = null, $which = 'columns', $init = false ) {
		if ( null === $columns ) {
			$columns = $this->{$which};
			if ( 'columns' === $which ) {
				$init = true;
			}
		}
		if ( ! empty( $columns ) ) {
			$new_columns = array();
			$filterable  = false;
			if ( empty( $this->filters ) && ( empty( $this->search_columns ) || 'search_columns' === $which ) && false !== $this->search ) {
				$filterable    = true;
				$this->filters = array();
			}
			foreach ( $columns as $column => $attributes ) {
				if ( ! is_array( $attributes ) ) {
					$column = $attributes;
				}
				$attributes = $this->setup_column( $column, $attributes, $filterable );
				if ( 'search_columns' === $which && false === $attributes['search'] ) {
					continue;
				}
				$new_columns[ $column ] = $attributes;
			}
			$columns = $new_columns;
		}
		if ( false !== $init ) {
			if ( ! empty( $this->form_columns ) && ( $this->edit || $this->add || $this->duplicate ) ) {
				$this->form_columns = $this->setup_columns( $this->form_columns, 'form_columns' );
			} else {
				$this->form_columns = $columns;
			}
			if ( ! empty( $this->view_columns ) && $this->view ) {
				$this->view_columns = $this->setup_columns( $this->view_columns, 'view_columns' );
			} else {
				$this->view_columns = $this->form_columns;
			}
			if ( ! empty( $this->search_columns ) && $this->search ) {
				$this->search_columns = $this->setup_columns( $this->search_columns, 'search_columns' );
			} else {
				$this->search_columns = $columns;
			}
			if ( ! empty( $this->export_columns ) && $this->export ) {
				$this->export_columns = $this->setup_columns( $this->export_columns, 'export_columns' );
			} else {
				$this->export_columns = $columns;
			}
			if ( ! empty( $this->reorder_columns ) && $this->reorder ) {
				$this->reorder_columns = $this->setup_columns( $this->reorder_columns, 'reorder_columns' );
			} else {
				$this->reorder_columns = $columns;
			}
		}

		/**
		 * Filters the column definitions once they are complete.
		 *
		 * @since 1.0.0
		 *
		 * @param array                   $columns The columns, by name.
		 * @param string                  $which   Which set: columns, reorder_columns, form_columns, ...
		 * @param bool                    $init    Whether the screen is being set up.
		 * @param Exports_Reports_Admin_UI $admin   The screen.
		 */
		$columns = apply_filters( 'exports_reports_setup_columns', $columns, $which, $init, $this );

		return $this->legacy_hook( 'setup_columns', array( $columns, $which, $init ) );
	}

	public function setup_column( $column = null, $attributes = null, $filterable = false ) {
		// Available Attributes
		// type = field type
		// type = date (data validation as date)
		// type = time (data validation as time)
		// type = datetime (data validation as datetime)
		// date_touch = use current timestamp when saving (even if readonly, if type is date-related)
		// date_touch_on_create = use current timestamp when saving ONLY on create (even if readonly, if type is date-related)
		// date_ongoing = use this additional column to search between as if the first is the "start" and the date_ongoing is the "end" for filter
		// type = text / other (single line text box)
		// type = desc (textarea)
		// type = number (data validation as int float)
		// type = decimal (data validation as decimal)
		// type = password (single line password box)
		// type = bool (checkbox)
		// type = related (select box)
		// related = table to relate to (if type=related) OR custom array of (key=>label or comma separated values) items
		// related_field = field name on table to show (if type=related) - default "name"
		// related_multiple = true (ability to select multiple values if type=related)
		// related_sql = custom where / order by SQL (if type=related)
		// readonly = true (shows as text)
		// display = false (doesn't show on form, but can be saved)
		// search = this field is searchable
		// filter = this field will be independently searchable (by default, searchable fields are searched by the primary search box)
		// comments = comments to show for field
		// comments_top = true (shows comments above field instead of below)
		// real_name = the real name of the field (if using an alias for 'name')
		// group_related = true (uses HAVING instead of WHERE for filtering column)
		if ( ! is_array( $attributes ) ) {
			if ( null !== $attributes ) {
				$column = $attributes;
			}
			$attributes = array();
		}
		if ( ! isset( $attributes['real_name'] ) ) {
			$attributes['real_name'] = false;
		}
		if ( ! isset( $attributes['label'] ) ) {
			$attributes['label'] = ucwords( str_replace( '_', ' ', $column ) );
		}
		if ( ! isset( $attributes['type'] ) ) {
			$attributes['type'] = 'text';
		}
		if ( 'related' !== $attributes['type'] || ! isset( $attributes['custom_relate'] ) ) {
			$attributes['custom_relate'] = false;
		}
		if ( 'related' !== $attributes['type'] || ! isset( $attributes['related'] ) ) {
			$attributes['related'] = false;
		}
		if ( 'related' !== $attributes['type'] || ! isset( $attributes['related_id'] ) ) {
			$attributes['related_id'] = 'id';
		}
		if ( 'related' !== $attributes['type'] || ! isset( $attributes['related_field'] ) ) {
			$attributes['related_field'] = 'name';
		}
		if ( 'related' !== $attributes['type'] || ! isset( $attributes['related_multiple'] ) ) {
			$attributes['related_multiple'] = false;
		}
		if ( 'related' !== $attributes['type'] || ! isset( $attributes['related_sql'] ) ) {
			$attributes['related_sql'] = false;
		}
		if ( 'related' === $attributes['type'] && ( is_array( $attributes['related'] ) || strpos( $attributes['related'], ',' ) ) ) {
			if ( ! is_array( $attributes['related'] ) ) {
				$related_items = array();
				foreach ( explode( ',', $attributes['related'] ) as $label ) {
					$related_items[ $label ] = ucwords( str_replace( '_', ' ', $label ) );
				}
				$attributes['related'] = $related_items;
			}
			if ( empty( $attributes['related'] ) ) {
				$attributes['related'] = false;
			}
		}
		if ( ! isset( $attributes['readonly'] ) ) {
			$attributes['readonly'] = false;
		}
		if ( ! isset( $attributes['date_touch'] ) || ! in_array(
			$attributes['type'],
			array(
				'date',
				'time',
				'datetime',
			),
			true
		) ) {
			$attributes['date_touch'] = false;
		}
		if ( ! isset( $attributes['date_touch_on_create'] ) || ! in_array(
			$attributes['type'],
			array(
				'date',
				'time',
				'datetime',
			),
			true
		) ) {
			$attributes['date_touch_on_create'] = false;
		}
		if ( ! isset( $attributes['display'] ) ) {
			$attributes['display'] = true;
		}
		if ( ! isset( $attributes['search'] ) || false === $this->search ) {
			$attributes['search'] = ( false !== $this->search ? true : false );
		}
		if ( ! isset( $attributes['filter'] ) || false === $this->search ) {
			$attributes['filter'] = false;
		}
		if ( false !== $attributes['filter'] && false !== $filterable ) {
			$this->filters[] = $column;
		}
		if ( false === $attributes['filter'] || ! isset( $attributes['filter_label'] ) || ! in_array( $column, $this->filters, true ) ) {
			$attributes['filter_label'] = $attributes['label'];
		}
		if ( false === $attributes['filter'] || ! isset( $attributes['filter_default'] ) || ! in_array( $column, $this->filters, true ) ) {
			$attributes['filter_default'] = false;
		}
		if ( false === $attributes['filter'] || ! isset( $attributes['date_ongoing'] ) || ! in_array(
			$attributes['type'],
			array(
				'date',
				'time',
				'datetime',
			),
			true
		) || ! in_array( $column, $this->filters, true ) ) {
			$attributes['date_ongoing'] = false;
		}
		if ( false === $attributes['filter'] || ! in_array(
			$attributes['type'],
			array(
				'date',
				'time',
				'datetime',
			),
			true
		) || ! isset( $attributes['filter_ongoing_default'] ) || ! in_array( $column, $this->filters, true ) ) {
			$attributes['filter_ongoing_default'] = false;
		}
		if ( ! isset( $attributes['export'] ) ) {
			$attributes['export'] = true;
		}
		if ( ! isset( $attributes['total_field'] ) ) {
			$attributes['total_field'] = false;
		}
		if ( ! isset( $attributes['group_related'] ) ) {
			$attributes['group_related'] = false;
		}
		if ( ! isset( $attributes['comments'] ) ) {
			$attributes['comments'] = '';
		}
		if ( ! isset( $attributes['comments_top'] ) ) {
			$attributes['comments_top'] = false;
		}
		if ( ! isset( $attributes['custom_input'] ) ) {
			$attributes['custom_input'] = false;
		}
		if ( ! isset( $attributes['custom_display'] ) ) {
			$attributes['custom_display'] = false;
		}
		if ( false !== $attributes['custom_display'] && ! $this->is_allowed_callback( $attributes['custom_display'] ) ) {
			$attributes['custom_display'] = false;
		}
		if ( ! isset( $attributes['custom_form_display'] ) ) {
			$attributes['custom_form_display'] = false;
		}
		if ( ! isset( $attributes['custom_view'] ) ) {
			$attributes['custom_view'] = $attributes['custom_display'];
		}
		if ( ! isset( $attributes['width'] ) ) {
			$attributes['width'] = '';
		}

		/**
		 * Filters one column definition once it is complete.
		 *
		 * @since 1.0.0
		 *
		 * @param array                   $attributes The column.
		 * @param string                  $column     Its name.
		 * @param bool                    $filterable Whether the column may filter the list.
		 * @param Exports_Reports_Admin_UI $admin      The screen.
		 */
		$attributes = apply_filters( 'exports_reports_setup_column', $attributes, $column, $filterable, $this );

		return $this->legacy_hook( 'setup_column', array( $attributes, $column, $filterable ) );
	}

	/**
	 * The HTML a display function, a view function or the header callback may return: the tags of post
	 * content, which the exports_reports_allowed_html filter may extend.
	 *
	 * @since 0.9.5
	 *
	 * @return array<string, array<string, bool>> As wp_kses() takes it.
	 */
	public function allowed_html() {
		/**
		 * Filters the HTML the report screens let a callback's output keep.
		 *
		 * @param array                    $allowed wp_kses_allowed_html( 'post' ).
		 * @param Exports_Reports_Admin_UI $admin   The report.
		 */
		return apply_filters( 'exports_reports_allowed_html', wp_kses_allowed_html( 'post' ), $this );
	}

	public function message( $msg ) {
		/**
		 * Filters a message before the screen shows it.
		 *
		 * @since 1.0.0
		 *
		 * @param string                  $msg   The message, HTML.
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		$msg = apply_filters( 'exports_reports_message', $msg, $this );
		$msg = $this->legacy_hook( 'message', array( $msg ) );
		?>
		<div id="message" class="updated fade"><p><?php echo wp_kses( $msg, self::message_html() ); ?></p></div>
		<?php
	}

	/**
	 * The HTML a message or an error may carry: what a post may, and a button that submits one of the
	 * screen's forms (the removal of an export, in export_ready()).
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	protected static function message_html() {
		$allowed = wp_kses_allowed_html( 'post' );

		$allowed['button'] = array(
			'class' => true,
			'form'  => true,
			'name'  => true,
			'type'  => true,
			'value' => true,
		);

		return $allowed;
	}

	public function error( $msg ) {
		/**
		 * Filters an error message before the screen shows it.
		 *
		 * @since 1.0.0
		 *
		 * @param string                  $msg   The message, HTML.
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		$msg = apply_filters( 'exports_reports_error', $msg, $this );
		$msg = $this->legacy_hook( 'error', array( $msg ) );
		?>
		<div id="message" class="error fade"><p><?php echo wp_kses( $msg, self::message_html() ); ?></p></div>
		<?php
		return false;
	}

	public function go() {
		/**
		 * Fires before the screen dispatches the request.
		 *
		 * @since 1.0.0
		 *
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_go', $this );
		$this->legacy_hook( 'go' );

		// The plugin's own stylesheet is in the head already (exports_reports_assets()); a stylesheet of a custom screen goes here.
		if ( false !== $this->css && ! ( EXPORTS_REPORTS_URL . 'assets/css/admin.css' === $this->css && wp_style_is( 'exports-reports-admin', 'enqueued' ) ) ) {
			wp_enqueue_style( 'exports-reports-admin-ui-css', $this->css, array(), EXPORTS_REPORTS_VERSION );
		}

		wp_enqueue_script( 'exports-reports-admin-ui', $this->assets_url . '/js/admin-ui.js', array( 'jquery' ), EXPORTS_REPORTS_VERSION, true );

		if ( isset( $this->custom[ $this->action ] ) && function_exists( "{$this->custom[$this->action]}" ) ) {
			call_user_func( $this->custom[ $this->action ], $this );
		} elseif ( 'add' === $this->action && $this->add ) {
			if ( 'create' === $this->do && $this->save && ! empty( $_POST ) && ! empty( $_POST['_wpnonce'] ) && false !== wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'exports-reports-form-' . $this->do ) ) {
				$this->save( 1 );
				if ( false === $this->api ) {
					$this->manage();
				}
			} else {
				$this->add();
			}
		} elseif ( ( 'edit' === $this->action && $this->edit ) || ( 'duplicate' === $this->action && $this->duplicate ) ) {
			if ( 'save' === $this->do && $this->save && ! empty( $_POST ) && ! empty( $_POST['_wpnonce'] ) && false !== wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'exports-reports-form-' . $this->do ) ) {
				$this->save();
			}
			$this->edit( ( 'duplicate' === $this->action && $this->duplicate ? 1 : 0 ) );
		} elseif ( 'delete' === $this->action && $this->delete && ! empty( $_GET['_wpnonce'] ) && false !== wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'exports-reports-' . $this->action ) ) {
			$this->delete();
			if ( false === $this->api ) {
				$this->manage();
			}
		} elseif ( 'reorder' === $this->action && $this->reorder ) {
			if ( false === $this->table ) {
				return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "table" definition.', 'exports-and-reports' ) );
			}
			if ( false === $this->identifier ) {
				return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "identifier" definition.', 'exports-and-reports' ) );
			}
			if ( 'save' === $this->do ) {
				$this->reorder();
			}
			if ( false === $this->api ) {
				$this->manage( 1 );
			}
		} elseif ( 'save' === $this->do && $this->save && ! empty( $_POST ) && ! empty( $_POST['_wpnonce'] ) && false !== wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'exports-reports-form-' . $this->do ) ) {
			$this->save();
			if ( false === $this->api ) {
				$this->manage();
			}
		} elseif ( 'create' === $this->do && $this->save && ! empty( $_POST ) && ! empty( $_POST['_wpnonce'] ) && false !== wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'exports-reports-form-' . $this->do ) ) {
			$this->save( 1 );
			if ( false === $this->api ) {
				$this->manage();
			}
		} elseif ( 'view' === $this->action && $this->view ) {
			$this->view();
		} elseif ( false === $this->api ) {
			$this->manage();
		}
	}

	public function add() {
		/**
		 * Fires before the add form.
		 *
		 * @since 1.0.0
		 *
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_add', $this );
		$this->legacy_hook( 'add' );

		$screen_title = $this->heading['add'] . ' ' . $this->item;
		$create       = 1;
		$duplicate    = 0;

		require __DIR__ . '/templates/edit.php';
	}

	public function edit( $duplicate = 0 ) {
		if ( ! $this->duplicate ) {
			$duplicate = 0;
		}
		/**
		 * Fires before the edit form.
		 *
		 * @since 1.0.0
		 *
		 * @param int                     $duplicate 1 when the form duplicates the row.
		 * @param Exports_Reports_Admin_UI $admin     The screen.
		 */
		do_action( 'exports_reports_edit', $duplicate, $this );
		$this->legacy_hook( 'edit', array( $duplicate ) );
		if ( isset( $this->custom['edit'] ) && function_exists( "{$this->custom['edit']}" ) ) {
			call_user_func( $this->custom['edit'], $this, $duplicate );
		}

		$screen_title = ( $duplicate ? $this->heading['duplicate'] : $this->heading['edit'] ) . ' ' . $this->item;
		$create       = 0;

		require __DIR__ . '/templates/edit.php';
	}

	public function form( $create = 0, $duplicate = 0 ) {
		/**
		 * Fires before the form fields.
		 *
		 * @since 1.0.0
		 *
		 * @param int                     $create    1 when the form adds a row.
		 * @param int                     $duplicate 1 when the form duplicates the row.
		 * @param Exports_Reports_Admin_UI $admin     The screen.
		 */
		do_action( 'exports_reports_form', $create, $duplicate, $this );
		$this->legacy_hook( 'form', array( $create, $duplicate ) );
		if ( isset( $this->custom['form'] ) && function_exists( "{$this->custom['form']}" ) ) {
			return call_user_func( $this->custom['form'], $this, $create );
		}
		if ( false === $this->table && false === $this->sql ) {
			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "table" definition.', 'exports-and-reports' ) );
		}
		if ( empty( $this->form_columns ) ) {
			$this->form_columns = $this->columns;
		}
		/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
		$submit = sprintf( __( 'Add %s', 'exports-and-reports' ), $this->item );
		$id     = '';
		$vars   = array(
			'action' => 'manage',
			'do'     => 'create',
			'id'     => '',
		);
		if ( 0 === $create ) {
			if ( empty( $this->row ) ) {
				$this->get_row();
			}
			if ( empty( $this->row ) ) {
				/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
				return $this->error( sprintf( __( '<strong>Error:</strong> %s not found.', 'exports-and-reports' ), $this->item ) );
			}
			if ( 0 === $duplicate ) {
				$submit = __( 'Save Changes', 'exports-and-reports' );
				$id     = $this->row[ $this->identifier ];
				$vars   = array(
					'action' => 'edit',
					'do'     => 'save',
					'id'     => $id,
				);
			}
		}
		require __DIR__ . '/templates/form.php';
	}

	public function view() {
		/**
		 * Fires before the view screen.
		 *
		 * @since 1.0.0
		 *
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_view', $this );
		$this->legacy_hook( 'view' );
		if ( isset( $this->custom['view'] ) && function_exists( "{$this->custom['view']}" ) ) {
			return call_user_func( $this->custom['view'], $this );
		}
		if ( false === $this->table && false === $this->sql ) {
			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "table" definition.', 'exports-and-reports' ) );
		}
		if ( empty( $this->row ) ) {
			$this->get_row();
		}
		if ( empty( $this->row ) ) {
			/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
			return $this->error( sprintf( __( '<strong>Error:</strong> %s not found.', 'exports-and-reports' ), $this->item ) );
		}
		require __DIR__ . '/templates/view.php';
	}

	public function delete( $id = false ) {
		/**
		 * Fires before a row is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed                   $id    The identifier.
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_pre_delete', $id, $this );
		$this->legacy_hook( 'pre_delete', array( $id ) );
		if ( isset( $this->custom['delete'] ) && function_exists( "{$this->custom['delete']}" ) ) {
			return call_user_func( $this->custom['delete'], $this );
		}
		if ( false === $this->table && false === $this->sql ) {
			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "table" definition.', 'exports-and-reports' ) );
		}
		if ( false === $this->id && false === $id ) {
			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "id" definition.', 'exports-and-reports' ) );
		}
		if ( false === $id ) {
			$id = $this->id;
		}
		global $wpdb;
		$check = $this->execute( "DELETE FROM $this->table WHERE `id` = " . (int) $id );
		if ( $check ) {
			/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
			$this->message( sprintf( __( '<strong>Deleted:</strong> %s has been deleted.', 'exports-and-reports' ), $this->item ) );
		} else {
			/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
			$this->error( sprintf( __( '<strong>Error:</strong> %s has not been deleted.', 'exports-and-reports' ), $this->item ) );
		}
		/**
		 * Fires once a row has been deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed                   $id    The identifier.
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_post_delete', $id, $this );
		$this->legacy_hook( 'post_delete', array( $id ) );
	}

	public function save( $create = 0 ) {
		if ( ! isset( $_POST['_wpnonce'] ) || false === wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'exports-reports-form-' . $this->do ) ) {
			return $this->error( __( '<strong>Error:</strong> The form has expired; reload the page and try again.', 'exports-and-reports' ) );
		}
		/**
		 * Fires before the posted form is saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int                     $create 1 when a row is added.
		 * @param Exports_Reports_Admin_UI $admin  The screen.
		 */
		do_action( 'exports_reports_pre_save', $create, $this );
		$this->legacy_hook( 'pre_save', array( $create ) );
		if ( isset( $this->custom['save'] ) && function_exists( "{$this->custom['save']}" ) ) {
			return call_user_func( $this->custom['save'], $this, $create );
		}
		global $wpdb;
		$action = 'saved';
		if ( 1 === $create ) {
			$action = 'created';
		}
		// Each column is sanitized below by its type.
		$posted     = wp_unslash( $_POST );
		$column_sql = array();
		$data       = array();
		foreach ( $this->form_columns as $column => $attributes ) {
			if ( ! is_array( $attributes ) ) {
				$column     = $attributes;
				$attributes = $this->setup_column( $column );
			}
			$vartype = '%s';
			if ( false === $attributes['display'] || false !== $attributes['readonly'] ) {
				if ( ! in_array( $attributes['type'], array( 'date', 'time', 'datetime' ), true ) ) {
					continue;
				}
				if ( false === $attributes['date_touch'] && ( false === $attributes['date_touch_on_create'] || 1 !== $create || $this->id > 0 ) ) {
					continue;
				}
			}
			if ( in_array( $attributes['type'], array( 'date', 'time', 'datetime' ), true ) ) {
				$format = 'Y-m-d H:i:s';
				if ( 'date' === $attributes['type'] ) {
					$format = 'Y-m-d';
				}
				if ( 'time' === $attributes['type'] ) {
					$format = 'H:i:s';
				}
				if ( false !== $attributes['date_touch'] || ( false !== $attributes['date_touch_on_create'] && 1 === $create && $this->id < 1 ) ) {
					$value = date_i18n( $format );
				} else {
					$posted_date = isset( $posted[ $column ] ) ? sanitize_text_field( (string) $posted[ $column ] ) : '';
					$value       = date_i18n( $format, strtotime( ( 'time' === $attributes['type'] ? date_i18n( 'Y-m-d ' ) : '' ) . $posted_date ) );
				}
			} else {
				if ( ! isset( $posted[ $column ] ) ) {
					$posted[ $column ] = '';
				}
				if ( 'bool' === $attributes['type'] ) {
					$vartype = '%d';
					$value   = ( 1 === (int) $posted[ $column ] ) ? 1 : 0;
				} elseif ( 'number' === $attributes['type'] ) {
					$vartype = '%d';
					$value   = number_format( (float) $posted[ $column ], 0, '', '' );
				} elseif ( 'decimal' === $attributes['type'] ) {
					$vartype = '%f';
					$value   = number_format( (float) $posted[ $column ], 2, '.', '' );
				} elseif ( 'related' === $attributes['type'] ) {
					if ( is_array( $posted[ $column ] ) ) {
						$value = implode( ',', array_map( 'sanitize_text_field', $posted[ $column ] ) );
					} else {
						$value = sanitize_text_field( (string) $posted[ $column ] );
					}
				} elseif ( in_array( $attributes['type'], array( 'desc', 'code', 'password' ), true ) ) {
					// Queries, descriptions and passwords are stored as typed.
					$value = is_array( $posted[ $column ] ) ? '' : (string) $posted[ $column ];
				} else {
					$value = sanitize_text_field( is_array( $posted[ $column ] ) ? '' : (string) $posted[ $column ] );
				}
			}
			if ( isset( $attributes['custom_save'] ) && false !== $attributes['custom_save'] && function_exists( "{$attributes['custom_save']}" ) ) {
				$value = $attributes['custom_save']( $value, $column, $attributes, $this );
			}
			if ( '%d' === $vartype ) {
				$literal = $wpdb->prepare( '%d', $value );
			} elseif ( '%f' === $vartype ) {
				$literal = $wpdb->prepare( '%f', $value );
			} else {
				$literal = $this->quote( (string) $value );
			}
			$column_sql[]    = $this->backtick( $column ) . ' = ' . $literal;
			$data[ $column ] = $value;
		}
		$column_sql = implode( ',', $column_sql );
		if ( 0 === $create && $this->id > 0 ) {
			$this->insert_id = $this->id;
			$check           = $this->execute( "UPDATE $this->table SET $column_sql WHERE `id` = " . (int) $this->id );
		} else {
			$check = $this->execute( "INSERT INTO $this->table SET $column_sql" );
		}
		if ( $check ) {
			if ( 0 === $this->insert_id ) {
				$this->insert_id = $wpdb->insert_id;
			}
			if ( 'created' === $action ) {
				/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
				$this->message( sprintf( __( '<strong>Success!</strong> %s created successfully.', 'exports-and-reports' ), $this->item ) );
			} else {
				/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
				$this->message( sprintf( __( '<strong>Success!</strong> %s saved successfully.', 'exports-and-reports' ), $this->item ) );
			}
		} elseif ( 'created' === $action ) {
			/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
			$this->error( sprintf( __( '<strong>Error:</strong> %s has not been created.', 'exports-and-reports' ), $this->item ) );
		} else {
			/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
			$this->error( sprintf( __( '<strong>Error:</strong> %s has not been saved.', 'exports-and-reports' ), $this->item ) );
		}
		/**
		 * Fires once the posted form has been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int                     $id     The identifier of the row.
		 * @param array                   $data   The values saved.
		 * @param int                     $create 1 when the row was added.
		 * @param Exports_Reports_Admin_UI $admin  The screen.
		 */
		do_action( 'exports_reports_post_save', $this->insert_id, $data, $create, $this );
		$this->legacy_hook( 'post_save', array( $this->insert_id, $data, $create ) );
	}

	public function reorder() {
		if ( ! isset( $_POST['_wpnonce'] ) || false === wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'exports-reports-reorder' ) ) {
			return $this->error( __( '<strong>Error:</strong> The order was not saved: the page has expired, reload it and try again.', 'exports-and-reports' ) );
		}
		/**
		 * Fires before the posted order is saved.
		 *
		 * @since 1.0.0
		 *
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_pre_reorder', $this );
		$this->legacy_hook( 'pre_reorder' );
		if ( isset( $this->custom['reorder'] ) && function_exists( "{$this->custom['reorder']}" ) ) {
			return call_user_func( $this->custom['reorder'], $this );
		}
		global $wpdb;
		if ( false === $this->table ) {
			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "table" definition.', 'exports-and-reports' ) );
		}
		if ( false === $this->identifier ) {
			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "identifier" definition.', 'exports-and-reports' ) );
		}
		$order_ids = ( isset( $_POST['order'] ) && is_array( $_POST['order'] ) ) ? array_map( 'absint', wp_unslash( $_POST['order'] ) ) : array();
		if ( ! empty( $order_ids ) ) {
			foreach ( $order_ids as $order => $id ) {
				$updated = $wpdb->update(
					$this->table,
					array( $this->reorder => (int) $order ),
					array( $this->identifier => $id ),
					array(
						'%s',
						'%d',
					),
					array( '%d' )
				);
			}
			$this->message( __( '<strong>Success!</strong> Order updated successfully.', 'exports-and-reports' ) );
		} else {
			$this->error( __( '<strong>Error:</strong> Order has not been updated.', 'exports-and-reports' ) );
		}
		/**
		 * Fires once the posted order has been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int                     $id    The identifier of the last row saved.
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_post_reorder', $this->insert_id, $this );
		$this->legacy_hook( 'post_reorder', array( $this->insert_id ) );
	}

	public function field_value( $value, $field_name, $attributes ) {
		global $wpdb;
		if ( 'date' === $attributes['type'] ) {
			if ( 'N/A' === $value || '0000-00-00' === $value || '0000-00-00 00:00:00' === $value ) {
				$value = 'N/A';
			} else {
				$value = date_i18n( 'Y/m/d', strtotime( $value ) );
			}
		} elseif ( 'time' === $attributes['type'] ) {
			if ( 'N/A' === $value || '00:00:00' === $value || '0000-00-00 00:00:00' === $value ) {
				$value = 'N/A';
			} else {
				$value = date_i18n( 'g:i:s A', strtotime( $value ) );
			}
		} elseif ( 'datetime' === $attributes['type'] ) {
			if ( 'N/A' === $value || '0000-00-00' === $value || '0000-00-00 00:00:00' === $value ) {
				$value = 'N/A';
			} else {
				$value = date_i18n( 'Y/m/d g:i:s A', strtotime( $value ) );
			}
		} elseif ( 'related' === $attributes['type'] && false !== $attributes['related'] ) {
			$column_data = array();
			if ( ! is_array( $attributes['related'] ) ) {
				$selected_options = explode( ',', trim( $value ) );
				if ( ! empty( $this->related ) && isset( $this->related[ $attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id'] ] ) && ! empty( $this->related[ $attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id'] ] ) ) {
					foreach ( $selected_options as $option ) {
						if ( $attributes['related_id'] === $attributes['related_field'] ) {
							if ( isset( $this->related[ $attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id'] ][ $option ] ) ) {
								$column_data[ $option ] = $option;
							}
						} elseif ( in_array( $option, array_map( 'strval', $this->related[ $attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id'] ] ), true ) ) {
							$column_data[ $option ] = $this->related[ $attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id'] ][ $option ];
						}
					}
				}
				if ( empty( $column_data ) ) {
					$limited = ' WHERE ' . $this->backtick( $attributes['related_id'] ) . ' IN (' . implode( ', ', array_map( array( $this, 'quote' ), $selected_options ) ) . ')';

					$related = $this->rows( 'SELECT ' . $this->backtick( $attributes['related_id'] ) . ',' . $this->backtick( $attributes['related_field'] ) . ' FROM ' . $attributes['related'] . ( ! empty( $attributes['related_sql'] ) ? ' ' . $attributes['related_sql'] : $limited ) );

					foreach ( $related as $option ) {
						if ( in_array( (string) $option->{$attributes['related_id']}, $selected_options, true ) ) {
							$column_data[ $option->{$attributes['related_id']} ] = $option->{$attributes['related_field']};
							if ( ! isset( $this->related[ $attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id'] ] ) ) {
								$this->related[ $attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id'] ] = array();
							}
							$this->related[ $attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id'] ][ $option->{$attributes['related_id']} ] = $option->{$attributes['related_field']};
						}
					}
				}
			} else {
				$related          = $attributes['related'];
				$selected_options = explode( ',', $value );
				foreach ( $related as $option_id => $option ) {
					if ( in_array( (string) $option_id, $selected_options, true ) ) {
						$column_data[ $option_id ] = $option;
					}
				}
			}
			$value = implode( ', ', $column_data );
		} elseif ( 'bool' === $attributes['type'] ) {
			$value = ( 1 === (int) $value ? __( 'Yes', 'exports-and-reports' ) : __( 'No', 'exports-and-reports' ) );
		} elseif ( 'number' === $attributes['type'] ) {
			$value = number_format( $value, 0 );
		} elseif ( 'decimal' === $attributes['type'] ) {
			$value = number_format( $value, 2 );
		}
		if ( false !== $attributes['custom_relate'] ) {
			$table = $attributes['custom_relate'];
			$on    = $field_name;
			$is    = $value;
			$what  = array( 'name' );
			if ( is_array( $table ) ) {
				if ( isset( $table['on'] ) ) {
					$on = $table['on'];
				}
				if ( isset( $table['is'] ) && isset( $this->row[ $table['is'] ] ) ) {
					$is = $this->row[ $table['is'] ];
				}
				if ( isset( $table['what'] ) ) {
					$what = array();
					if ( is_array( $table['what'] ) ) {
						foreach ( $table['what'] as $wha ) {
							$what[] = $wha;
						}
					} else {
						$what[] = $table['what'];
					}
				}
				if ( isset( $table['table'] ) ) {
					$table = $table['table'];
				}
			}
			$sql = 'SELECT ' . implode( ',', array_map( array( $this, 'backtick' ), $what ) ) . ' FROM ' . $this->backtick( $table ) . ' WHERE ' . $this->backtick( $on ) . ' = ' . $this->quote( (string) $is );

			$results = current( $this->rows( $sql, ARRAY_A ) );

			if ( ! empty( $results ) ) {
				$val = array();
				foreach ( $what as $wha ) {
					if ( isset( $results[ $wha ] ) ) {
						$val[] = $results[ $wha ];
					}
				}
				if ( ! empty( $val ) ) {
					$value = implode( ' ', $val );
				}
			}
		}

		return $value;
	}

	/**
	 * The PDF engine; see Exports_Reports_Exporter_PDF::engine().
	 *
	 * @since 1.0.0 Forwards to the PDF exporter.
	 *
	 * @return string 'tc-lib-pdf' or 'tcpdf'.
	 */
	public static function pdf_engine() {
		return Exports_Reports_Exporter_PDF::engine();
	}

	/**
	 * Whether a delimited export can read its rows from an unbuffered result; see
	 * Exports_Reports_Exporter_Delimited::can_stream().
	 *
	 * @since 1.0.0 Forwards to the delimited exporter.
	 *
	 * @return bool
	 */
	public function can_stream_export() {
		return ( new Exports_Reports_Exporter_Delimited( $this ) )->can_stream();
	}

	/**
	 * Writes a delimited export (CSV, TSV, pipe or custom) and shows the download; see
	 * Exports_Reports_Exporter_Delimited. export() does this for every format.
	 *
	 * @since 1.0.0 Forwards to the delimited exporter.
	 *
	 * @return string|false The file name, or false when the statement failed.
	 */
	public function export_sv() {
		$exporter    = new Exports_Reports_Exporter_Delimited( $this );
		$export_file = $exporter->write();

		if ( false !== $export_file ) {
			$this->export_ready( $export_file, $exporter->extension() );
		}

		return $export_file;
	}

	public function export() {
		/**
		 * Fires before an export is written.
		 *
		 * @since 1.0.0
		 *
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_pre_export', $this );
		$this->legacy_hook( 'pre_export' );
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$url         = explode( '/', $this->request_uri() );
		$url         = array_reverse( $url );
		$url         = $url[0];
		$credentials = request_filesystem_credentials( $url, '', false, ABSPATH );
		if ( false === $credentials ) {
			$this->error( __( '<strong>Error:</strong> Your hosting configuration does not allow access to add files to your site.', 'exports-and-reports' ) );

			return false;
		}
		if ( ! WP_Filesystem( $credentials, ABSPATH ) ) {
			request_filesystem_credentials( $url, '', true, ABSPATH ); // The connection failed: ask for the credentials again.
			$this->error( __( '<strong>Error:</strong> Your hosting configuration does not allow access to add files to your site.', 'exports-and-reports' ) );

			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		/**
		 * @var WP_Filesystem_Base $wp_filesystem
		 */
		global $wp_filesystem;

		WP_Filesystem();

		if ( ! $this->api && ! $this->verify_export_request() ) {
			$this->error( __( '<strong>Error:</strong> The export link has expired. Go back to the report and use its export buttons again.', 'exports-and-reports' ) );

			return false;
		}
		if ( isset( $this->custom['export'] ) && function_exists( "{$this->custom['export']}" ) ) {
			return call_user_func( $this->custom['export'], $this );
		}
		$dir = dirname( EXPORTS_REPORTS_EXPORT_DIR );
		if ( ! file_exists( EXPORTS_REPORTS_EXPORT_DIR ) ) {
			if ( ! $wp_filesystem->is_writable( $dir ) || ! $wp_filesystem->mkdir( EXPORTS_REPORTS_EXPORT_DIR ) ) {
				/* translators: %s: the export directory. */
				$this->error( sprintf( __( '<strong>Error:</strong> Your export directory (<strong>%s</strong>) did not exist and could not be created by the web server. Check the directory permissions and try again.', 'exports-and-reports' ), EXPORTS_REPORTS_EXPORT_DIR ) );

				return false;
			}
		}
		if ( ! $wp_filesystem->is_writable( EXPORTS_REPORTS_EXPORT_DIR ) ) {
			/* translators: %s: the export directory. */
			$this->error( sprintf( __( '<strong>Error:</strong> Your export directory (<strong>%s</strong>) needs to be writable for this plugin to work. Double-check it and try again.', 'exports-and-reports' ), EXPORTS_REPORTS_EXPORT_DIR ) );

			return false;
		}
		self::protect_export_dir();
		$remove_export = $this->removing_export();
		if ( ! $this->api && '' !== $remove_export ) {
			$remove_export_path = EXPORTS_REPORTS_EXPORT_DIR . '/' . $remove_export;

			if ( ! self::is_export_file_name( $remove_export ) ) {
				$this->error( __( '<strong>Error:</strong> That is not an export file.', 'exports-and-reports' ) );

				return false;
			}

			/**
			 * Fires before an export file is removed.
			 *
			 * @since 1.0.0
			 *
			 * @param string                  $file  The file name.
			 * @param string                  $path  Its path.
			 * @param Exports_Reports_Admin_UI $admin The screen.
			 */
			do_action( 'exports_reports_pre_remove_export', $remove_export, $remove_export_path, $this );
			$this->legacy_hook( 'pre_remove_export', array( $remove_export, $remove_export_path ) );

			if ( $wp_filesystem->exists( $remove_export_path ) ) {
				$remove = $wp_filesystem->delete( $remove_export_path );
				if ( $remove ) {
					/**
					 * Fires once an export file has been removed, or could not be.
					 *
					 * @since 1.0.0
					 *
					 * @param string                  $file    The file name.
					 * @param bool                    $removed Whether the file is gone.
					 * @param string                  $path    Its path.
					 * @param Exports_Reports_Admin_UI $admin   The screen.
					 */
					do_action( 'exports_reports_post_remove_export', $remove_export, true, $remove_export_path, $this );
					$this->legacy_hook( 'post_remove_export', array( $remove_export, true, $remove_export_path ) );
					$this->message( __( '<strong>Success:</strong> Export removed successfully.', 'exports-and-reports' ) );

					return;
				} else {
					/**
					 * This action is documented above.
					 */
					do_action( 'exports_reports_post_remove_export', $remove_export, false, $remove_export_path, $this );
					$this->legacy_hook( 'post_remove_export', array( $remove_export, false, $remove_export_path ) );
					/* translators: %s: the export directory. */
					$this->error( sprintf( __( '<strong>Error:</strong> Your export directory (<strong>%s</strong>) needs to be writable for this plugin to work. Double-check it and try again.', 'exports-and-reports' ), EXPORTS_REPORTS_EXPORT_DIR ) );

					return false;
				}
			} else {
				$this->error( __( '<strong>Error:</strong> That file does not exist in the export directory.', 'exports-and-reports' ) );

				return false;
			}
		} else {
			if ( ! in_array( (string) $this->export_type, $this->allowed_export_types(), true ) ) {
				$this->error( __( '<strong>Error:</strong> That export format is not offered for this report.', 'exports-and-reports' ) );

				return false;
			}

			if ( empty( $this->full_data ) ) {
				// Delimited exports read their rows from an unbuffered result; the other formats load the whole result
				// here. (A removal gets this far without running the report's query.)
				$this->stream_sql    = '';
				$this->stream_export = Exports_Reports_Exporter::streams( $this->export_type ) && $this->can_stream_export();
				$data                = $this->get_data( true );
				$this->stream_export = false;

				if ( false === $data ) {
					return false;
				}
			}

			$exporter = Exports_Reports_Exporter::create( $this->export_type, $this );

			if ( null === $exporter ) {
				$this->error( __( '<strong>Error:</strong> Invalid export type.', 'exports-and-reports' ) );

				return false;
			}

			$export_file = $exporter->write();

			if ( false === $export_file ) {
				return false;
			}

			$this->export_ready( $export_file, $exporter->extension() );
		}

		$this->exported_file = $export_file;

		/**
		 * Fires once an export file has been written.
		 *
		 * @since 1.0.0
		 *
		 * @param string                  $file  The file name.
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_post_export', $export_file, $this );
		$this->legacy_hook( 'post_export', array( $export_file ) );
	}

	/**
	 * Keeps the export directory from being served directly: an .htaccess that denies every request
	 * (Apache 2.4 and 2.2 syntax) and an index.php, written when missing and rewritten when the plugin's
	 * rules have changed since (a file of the site's own is left alone). Downloads go through
	 * exports_reports_send_export_file() after a nonce or token check; nginx needs the equivalent
	 * location rule in the server configuration (see the readme).
	 *
	 * @since 0.9.5
	 */
	public static function protect_export_dir() {
		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$rules = array(
			'.htaccess' => "# Exports and Reports: export files are served by the plugin after an access check.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n",
			'index.php' => "<?php\n// Silence is golden.\n",
		);

		$markers = array(
			'.htaccess' => 'Exports and Reports',
			'index.php' => 'Silence is golden',
		);

		foreach ( $rules as $name => $contents ) {
			$path = EXPORTS_REPORTS_EXPORT_DIR . '/' . $name;

			if ( $wp_filesystem->exists( $path ) ) {
				$current = (string) $wp_filesystem->get_contents( $path );

				// The plugin's own file is brought up to date when its rules changed; a file the site wrote stays.
				if ( $current === $contents || false === strpos( $current, $markers[ $name ] ) ) {
					continue;
				}
			}

			$wp_filesystem->put_contents( $path, $contents, FS_CHMOD_FILE );
		}
	}

	public function get_row( $id = false ) {
		if ( isset( $this->custom['row'] ) && function_exists( "{$this->custom['row']}" ) ) {
			return call_user_func( $this->custom['row'], $this );
		}
		if ( false === $this->table && false === $this->sql ) {
			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "table" definition.', 'exports-and-reports' ) );
		}
		if ( false === $this->id && false === $id ) {
			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "id" definition.', 'exports-and-reports' ) );
		}
		if ( false === $id ) {
			$id = $this->id;
		}
		global $wpdb;

		$sql = "SELECT * FROM `{$this->table}` WHERE `id` = " . (int) $id;

		$row = current( $this->rows( $sql, ARRAY_A ) );

		/**
		 * Filters the row the edit and view screens load.
		 *
		 * @since 1.0.0
		 *
		 * @param array|false             $row   The row, or false when there is none.
		 * @param mixed                   $id    Its identifier.
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		$row = apply_filters( 'exports_reports_get_row', $row, $id, $this );
		$row = $this->legacy_hook( 'get_row', array( $row, $id ) );
		if ( ! empty( $row ) ) {
			$this->row = $row;
		}

		return $row;
	}

	public function get_data( $full = false ) {
		if ( isset( $this->custom['data'] ) && function_exists( "{$this->custom['data']}" ) ) {
			return call_user_func( $this->custom['data'], $this );
		}
		if ( false === $this->table && false === $this->sql ) {
			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "table" definition.', 'exports-and-reports' ) );
		}

		/** @global wpdb $wpdb */ global $wpdb;

		$statement = $this->query->statement( $full );
		// The page's row count, when a recent page view of the same rows kept it (count_cache_key()): the query
		// then runs without the count, which costs a slow query as much again as the page's rows.
		$count_key = $full || $this->stream_export ? '' : $this->count_cache_key( $statement['count_key'] );
		$cached    = '' !== $count_key ? get_transient( $count_key ) : false;
		if ( false !== $cached ) {
			$statement = $this->query->statement( $full, false );
		}
		$sql       = $statement['sql'];
		$sql_count = $statement['sql_count'];
		$wheresql  = $statement['where'];
		$havingsql = $statement['having'];

		/**
		 * Filters the report query right before it runs, placeholders substituted.
		 *
		 * The callback may rewrite the statement, run preparation statements of its own on wpdb's
		 * connection first (temporary tables, session variables the statement reads), or return
		 * false to run nothing once it has reported why. A literal % in a quoted value stands behind
		 * wpdb's placeholder escape, which wpdb removes when it runs the statement.
		 *
		 * @since 1.0.0 The arguments one by one; the array form stays with wp_admin_ui_get_data_sql.
		 *
		 * @param string|false            $sql   The statement.
		 * @param bool                    $full  True when every row is read, as for an export.
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		$sql = apply_filters( 'exports_reports_get_data_sql', $sql, $full, $this );
		$sql = $this->legacy_hook( 'get_data_sql', array( $sql, $full ) );

		if ( false !== $this->sql_count && ! empty( $sql_count ) ) {
			/** Filters the count query right before it runs; same arguments as exports_reports_get_data_sql. */
			$sql_count = apply_filters( 'exports_reports_get_data_sql_count', $sql_count, $full, $this );
			$sql_count = $this->legacy_hook( 'get_data_sql_count', array( $sql_count, $full ) );
		}

		if ( false === $sql ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) && 1 === (int) $this->get_var( 'debug', 0 ) ) {
			echo "<textarea cols='130' rows='30'>" . esc_textarea( $wpdb->remove_placeholder_escape( $sql ) ) . '</textarea>';
		}
		if ( false !== $this->default_none && false === $this->search_query && false === $full && empty( $wheresql ) && empty( $havingsql ) ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) && 1 === (int) $this->get_var( 'debug', 0 ) ) {
			$wpdb->show_errors( true );
		}

		$results           = array();
		$total             = 0;
		$this->query_error = '';

		if ( $this->stream_export ) {
			// The delimited exporter reads the result row by row.
			$this->stream_sql = $wpdb->remove_placeholder_escape( $sql ); // mysqli runs it without wpdb, which would remove the escape.
		} else {
			$results = $this->rows( $sql, ARRAY_A );

			// The database's complaint, before the count query clears it.
			$this->query_error = (string) $wpdb->last_error;

			if ( is_array( $cached ) && isset( $cached['total'] ) ) {
				$total                  = (int) $cached['total'];
				$this->total_counted_at = isset( $cached['at'] ) ? (int) $cached['at'] : 0;
			} elseif ( false !== $cached ) {
				$total = (int) $cached; // A count kept by 1.2.0 or 1.2.1, without its time.
			} else {
				$total = current( $wpdb->get_col( 'SELECT FOUND_ROWS()' ) );

				if ( '' !== $count_key && '' === $this->query_error && is_numeric( $total ) ) {
					set_transient(
						$count_key,
						array(
							'total' => (int) $total,
							'at'    => time(),
						),
						$this->count_cache_ttl()
					);
				}
			}
		}

		/**
		 * Filters the row count of the report, for the paging; a statement that does not begin with
		 * SELECT has no count of its own and supplies it here.
		 *
		 * @since 1.0.0
		 *
		 * @param int                     $total The count.
		 * @param bool                    $full  Whether every row was asked for (an export).
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		$total = apply_filters( 'exports_reports_get_data_total', $total, $full, $this );
		$total = $this->legacy_hook( 'get_data_total', array( $total, $full ) );

		// The page's count. A full read keeps the count the page made: on the list screen the export runs
		// after the page has loaded, and a streamed export counts nothing.
		if ( ! $full && is_numeric( $total ) ) {
			$this->total = $total;
		}

		$this->set_filter_lookups();

		if ( $full ) {
			$this->full_data = $results;
		} else {
			$this->data = $results;
		}
		$totals = false;
		if ( ! empty( $results ) ) {
			foreach ( $this->columns as $key => $column ) {
				if ( false === $totals ) {
					$totals = current( $results );
				}
				if ( empty( $totals ) || ! is_array( $totals ) ) {
					continue;
				}
				$attributes = $column;
				if ( ! is_array( $attributes ) ) {
					$attributes = $this->setup_column( $column );
				}
				if ( true !== $attributes['total_field'] ) {
					continue;
				}
				if ( is_array( $column ) ) {
					$column = $key;
				}
				if ( false === $this->totals ) {
					$this->totals = array();
				}
				$this->totals[ $column ] = $totals[ 'exports_reports_total_' . $column ];
			}
		}
		if ( empty( $this->columns ) ) {
			$this->catch_columns( $full );
		}
		if ( $full ) {
			return $results;
		}

		if ( false !== $this->sql_count && ! empty( $sql_count ) ) {
			$this->sum_data = $this->rows( $sql_count, ARRAY_A );

			if ( '' === $this->query_error ) {
				$this->query_error = (string) $wpdb->last_error;
			}
		}

		return $results;
	}

	/**
	 * The transient that keeps the row count of a page view: the rows the count describes (the statement with
	 * its conditions and variables in, without the sort and the page), the user and the counts' generation,
	 * hashed. '' when counts are not kept (count_cache_ttl()), the statement gives no key, or the screen is
	 * not a report view (the management screens).
	 *
	 * @since 1.2.0
	 *
	 * @param string $rows The text of the rows the count describes, Exports_Reports_Query::statement()'s count_key.
	 *
	 * @return string
	 */
	public function count_cache_key( $rows ) {
		// The report views only: the management screens list the plugin's own small tables, which cost nothing
		// to count and change with every save.
		if ( false === $this->readonly || ! is_string( $this->sql ) || '' === trim( (string) $rows ) || 0 >= $this->count_cache_ttl() ) {
			return '';
		}

		$generation = function_exists( 'exports_reports_count_cache_generation' ) ? exports_reports_count_cache_generation() : 0;

		return 'exports_reports_count_' . md5( $generation . '|' . get_current_user_id() . '|' . $rows );
	}

	/**
	 * How long the row count of a page view is kept, in seconds: the Settings page's choice (five minutes
	 * unless changed), 0 for none. A kept count spares the query its count, which runs over every row the
	 * conditions match, on the page views that follow; rows added or removed meanwhile show in the count
	 * once it is counted again.
	 *
	 * @since 1.2.0
	 *
	 * @return int
	 */
	public function count_cache_ttl() {
		$ttl = function_exists( 'exports_reports_count_cache_ttl' ) ? exports_reports_count_cache_ttl() : 0;

		/**
		 * Filters how long the row count of this screen's page views is kept, in seconds; 0 keeps none.
		 *
		 * @since 1.2.0
		 *
		 * @param int                     $ttl   The seconds, from the Settings page.
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		return (int) apply_filters( 'exports_reports_count_cache_ttl', $ttl, $this );
	}

	/**
	 * What the screen says when the report's query failed: the database's message to a user who may edit
	 * reports (or to the API's token), a plain note to anyone else.
	 *
	 * @since 1.1.3
	 *
	 * @param bool|null $in_full Whether to give the database's message; null decides by the user's capabilities.
	 *
	 * @return string HTML.
	 */
	public function query_error_message( $in_full = null ) {
		if ( null === $in_full ) {
			$in_full = current_user_can( 'manage_options' ) || ( function_exists( 'exports_reports_current_user_can_any' ) && exports_reports_current_user_can_any( array( 'exports_reports_full_access', 'exports_reports_settings' ) ) );
		}

		if ( $in_full ) {
			/* translators: %s: the database's error message. */
			return sprintf( __( '<strong>Error:</strong> The report\'s query failed: %s', 'exports-and-reports' ), esc_html( $this->query_error ) );
		}

		return __( '<strong>Error:</strong> The report\'s query failed. Ask an administrator to check its definition.', 'exports-and-reports' );
	}

	/**
	 * Whether the search box has anything to search: a filter, or a column the search reads (one not marked
	 * unsearchable, and not a date or time, which the search skips). A screen with neither shows no search
	 * box, which would only push the table down.
	 *
	 * @since 1.2.3
	 *
	 * @return bool
	 */
	public function has_search_fields() {
		if ( false === $this->search ) {
			return false;
		}

		if ( ! empty( $this->filters ) ) {
			return true;
		}

		foreach ( $this->search_columns as $key => $column ) {
			$attributes = is_array( $column ) ? $column : $this->setup_column( $column );

			if ( false === $attributes['search'] || in_array( $attributes['type'], array( 'date', 'time', 'datetime' ), true ) ) {
				continue;
			}

			return true;
		}

		return false;
	}

	public function set_filter_lookups() {
		/** @global wpdb $wpdb */ global $wpdb;

		foreach ( $this->filters as $filter ) {
			if ( ! isset( $this->search_columns[ $filter ] ) ) {
				continue;
			}

			$filter_column = $this->search_columns[ $filter ];

			if ( 'related' === $filter_column['type'] && false !== $filter_column['related'] ) {
				if ( is_array( $filter_column['related'] ) ) {
					// A related array is already key => label.
					$related_lookup = $filter_column['related'];
				} else {
					$lookup_results = $this->rows( 'SELECT `' . $filter_column['related_id'] . '`,`' . $filter_column['related_field'] . '` FROM ' . $filter_column['related'] . ( ! empty( $filter_column['related_sql'] ) ? ' ' . $filter_column['related_sql'] : '' ), ARRAY_A );

					$id_field       = $filter_column['related_id'];
					$label_field    = $filter_column['related_field'];
					$related_lookup = array();

					foreach ( $lookup_results as $this_pair ) {
						$related_lookup[ $this_pair[ $id_field ] ] = $this_pair[ $label_field ];
					}
				}

				$this->search_columns[ $filter ]['related_lookup'] = $related_lookup;
			}
		}
	}

	/**
	 * The list screen, or the reorder screen: loads the rows and renders includes/templates/manage.php.
	 *
	 * @param int $reorder 1 for the reorder screen.
	 */
	public function manage( $reorder = 0 ) {
		/**
		 * Fires before the list screen.
		 *
		 * @since 1.0.0
		 *
		 * @param int                     $reorder 1 on the reorder screen.
		 * @param Exports_Reports_Admin_UI $admin   The screen.
		 */
		do_action( 'exports_reports_manage', $reorder, $this );
		$this->legacy_hook( 'manage', array( $reorder ) );
		if ( isset( $this->custom['manage'] ) && function_exists( "{$this->custom['manage']}" ) ) {
			return call_user_func( $this->custom['manage'], $this, $reorder );
		}

		if ( empty( $this->data ) ) {
			$this->get_data();
		}

		if ( empty( $this->columns ) ) {
			$this->catch_columns();
		}

		require __DIR__ . '/templates/manage.php';
	}

	/**
	 * The table of rows (Exports_Reports_List_Table), inside the reorder form on the reorder screen.
	 *
	 * @since 1.0.0 On WP_List_Table.
	 *
	 * @param int $reorder 1 for the reorder screen.
	 *
	 * @return bool False when the screen has no columns.
	 */
	public function table( $reorder = 0 ) {
		if ( 1 === $reorder && false !== $this->reorder ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
		/**
		 * Fires before the table.
		 *
		 * @since 1.0.0
		 *
		 * @param int                     $reorder 1 on the reorder screen.
		 * @param Exports_Reports_Admin_UI $admin   The screen.
		 */
		do_action( 'exports_reports_table', $reorder, $this );
		$this->legacy_hook( 'table', array( $reorder ) );
		if ( isset( $this->custom['table'] ) && function_exists( "{$this->custom['table']}" ) ) {
			return call_user_func( $this->custom['table'], $this, $reorder );
		}
		$column_index = 1 === $reorder ? 'reorder_columns' : 'columns';

		if ( '' !== $this->query_error ) {
			$this->error( $this->query_error_message() );

			return false;
		}

		if ( empty( $this->{$column_index} ) ) {
			if ( 1 !== $reorder && empty( $this->data ) && false !== $this->sql ) {
				// No field definitions and no rows: the columns would have come from the rows.
				?>
				<p><?php esc_html_e( 'The report returned no rows.', 'exports-and-reports' ); ?></p>
				<?php
				return false;
			}

			return $this->error( __( '<strong>Error:</strong> Invalid Configuration - Missing "columns" definition.', 'exports-and-reports' ) );
		}

		$list = $this->list_table( $reorder );
		$list->prepare_items();

		if ( 1 === $reorder && $this->reorder ) {
			?>
			<form action="
			<?php
			echo esc_url(
				$this->var_update(
					array(
						'action' => 'reorder',
						'do'     => 'save',
					)
				)
			);
			?>
							" method="post" id="exports-reports-reorder" class="admin_ui_reorder_form">
				<?php wp_nonce_field( 'exports-reports-reorder' ); ?>
				<?php $list->display(); ?>
			</form>
			<?php
		} else {
			?>
			<div class="exports-reports-table-scroll">
				<?php $list->display(); ?>
			</div>
			<?php
		}

		return true;
	}

	/**
	 * The list table that renders the rows.
	 *
	 * @since 1.0.0
	 *
	 * @param int $reorder 1 for the reorder screen.
	 *
	 * @return Exports_Reports_List_Table
	 */
	public function list_table( $reorder = 0 ) {
		return new Exports_Reports_List_Table( $this, $reorder );
	}

	/**
	 * @param bool $header
	 *
	 * @return mixed
	 */
	public function pagination( $header = false ) {
		/**
		 * Fires before the page links.
		 *
		 * @since 1.0.0
		 *
		 * @param Exports_Reports_Admin_UI $admin The screen.
		 */
		do_action( 'exports_reports_pagination', $this );
		$this->legacy_hook( 'pagination' );
		if ( isset( $this->custom['pagination'] ) && function_exists( "{$this->custom['pagination']}" ) ) {
			return call_user_func( $this->custom['pagination'], $this );
		}

		$page          = $this->page;
		$rows_per_page = $this->limit;
		$total_rows    = $this->total;
		$total_pages   = (int) ceil( $total_rows / $rows_per_page );

		$request_uri = $this->var_update(
			array( 'pg' => '' ),
			array(
				'limit',
				'order',
				'order_dir',
				'search_query',
			)
		);

		$append = false;

		if ( false !== strpos( $request_uri, '?' ) ) {
			$append = true;
		}

		if ( $header || 1 !== $total_rows ) {
			$singular_label = strtolower( $this->item );
			$plural_label   = strtolower( $this->items );

			$label_n = 1 === (int) $total_rows ? $singular_label : $plural_label;
			?>
			<span class="displaying-num"><?php echo esc_html( number_format_i18n( $total_rows ) . ' ' . $label_n ); ?></span>
			<?php
			if ( 0 < $this->total_counted_at ) {
				// The count comes from an earlier page view (count_cache_key()); the time says how much earlier.
				?>
				<span class="exports-reports-counted" title="<?php esc_attr_e( 'The row count is kept for a while (Settings: Row Count Cache); rows added or removed since show once it is counted again.', 'exports-and-reports' ); ?>">
					<?php
					/* translators: %s: a length of time, e.g. "3 mins". */
					echo esc_html( sprintf( __( '(counted %s ago)', 'exports-and-reports' ), human_time_diff( $this->total_counted_at ) ) );
					?>
				</span>
				<?php
			}
		}

		if ( 1 < $total_pages ) {
			?>
			<a class="first-page<?php echo ( 1 < $this->page ) ? '' : ' disabled'; ?>" title="<?php esc_attr_e( 'Go to the first page', 'exports-and-reports' ); ?>" href="<?php echo esc_url( $request_uri . ( $append ? '&' : '?' ) . 'pg' . $this->num . '=1' ); ?>">&laquo;</a>
			<a class="prev-page<?php echo ( 1 < $this->page ) ? '' : ' disabled'; ?>" title="<?php esc_attr_e( 'Go to the previous page', 'exports-and-reports' ); ?>" href="<?php echo esc_html( $request_uri . ( $append ? '&' : '?' ) . 'pg' . $this->num . '=' . max( $this->page - 1, 1 ) ); ?>">&lsaquo;</a>
			<?php
			if ( true === $header ) {
				?>
				<span class="paging-input"><input class="current-page" title="<?php esc_attr_e( 'Current page', 'exports-and-reports' ); ?>" type="text" name="pg<?php echo esc_attr( $this->num ); ?>" value="<?php echo esc_attr( $this->page ); ?>" size="<?php echo esc_attr( (string) strlen( (string) $total_pages ) ); ?>"> <?php esc_html_e( 'of', 'exports-and-reports' ); ?>
					<span class="total-pages"><?php echo esc_html( number_format_i18n( $total_pages ) ); ?></span></span>
				<?php
			} else {
				?>
				<span class="paging-input"><?php echo esc_html( $this->page ); ?> <?php esc_html_e( 'of', 'exports-and-reports' ); ?>
					<span class="total-pages"><?php echo esc_html( number_format_i18n( $total_pages ) ); ?></span></span>
				<?php
			}
			?>
			<a class="next-page<?php echo ( $this->page < $total_pages ) ? '' : ' disabled'; ?>" title="<?php esc_attr_e( 'Go to the next page', 'exports-and-reports' ); ?>" href="<?php echo esc_url( $request_uri . ( $append ? '&' : '?' ) . 'pg' . $this->num . '=' . min( $this->page + 1, $total_pages ) ); ?>">&rsaquo;</a>
			<a class="last-page<?php echo ( $this->page < $total_pages ) ? '' : ' disabled'; ?>" title="<?php esc_attr_e( 'Go to the last page', 'exports-and-reports' ); ?>'" href="<?php echo esc_url( $request_uri . ( $append ? '&' : '?' ) . 'pg' . $this->num . '=' . $total_pages ); ?>">&raquo;</a>
			<?php
		}
	}

	public function limit( $options = false ) {
		/**
		 * Filters the page sizes offered.
		 *
		 * @since 1.0.0 A filter; the value the callbacks return is used.
		 *
		 * @param int[]|false             $options The sizes, or false for the default list.
		 * @param Exports_Reports_Admin_UI $admin   The screen.
		 */
		$options = apply_filters( 'exports_reports_limit', $options, $this );
		$options = $this->legacy_hook( 'limit', array( $options ) );
		if ( isset( $this->custom['limit'] ) && function_exists( "{$this->custom['limit']}" ) ) {
			return call_user_func( $this->custom['limit'], $this );
		}
		if ( false === $options || ! is_array( $options ) || empty( $options ) ) {
			$options = array( 10, 25, 50, 100, 200 );
		}
		if ( ! in_array( $this->limit, array_map( 'intval', $options ), true ) ) {
			$this->limit = $options[1];
		}
		foreach ( $options as $option ) {
			if ( $this->limit === (int) $option ) {
				echo ' <span class="page-numbers current">' . esc_html( $option ) . '</span>';
			} else {
				echo ' <a href="' . esc_url(
					$this->var_update(
						array( 'limit' => $option ),
						array(
							'order',
							'order_dir',
							'search_query',
						)
					)
				) . '">' . esc_html( $option ) . '</a>';
			}
		}
	}

	public function parse_template_string( $in, $row = false ) {
		if ( false !== $row ) {
			$this->temp_row = $this->row;
			$this->row      = $row;
		}
		$out = preg_replace_callback( '/({@(.*?)})/m', array( $this, 'parse_magic_tags' ), $in );
		if ( false !== $row ) {
			$this->row = $this->temp_row;
		}

		return $out;
	}

	public function parse_magic_tags( $in ) {
		$name   = $in[2];
		$helper = '';
		if ( false !== strpos( $name, ',' ) ) {
			[ $name, $helper ] = explode( ',', $name );
			$name              = trim( $name );
			$helper            = trim( $helper );
		}
		$value = $this->row[ $name ];
		// A helper named after the comma ({@field,helper}) formats the value.
		if ( '' !== $helper && $this->is_allowed_callback( $helper ) ) {
			$value = $helper( $value, $name, $this->row );
		}

		return $value;
	}
}
