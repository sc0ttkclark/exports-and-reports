<?php
/**
 * The statements of a report screen: the SQL helpers and the assembly of the report query and its count
 * query from the screen's settings and the request.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the class runs the administrator's report queries and the screens' own statements; there is nothing to cache.

/**
 * Builds and runs the statements of an Exports_Reports_Admin_UI screen.
 *
 * The screen keeps the settings (the query as the administrator wrote it, the columns, the filters) and
 * the request (the page, the sort, the search); this class turns them into the statement that runs and
 * runs statements on wpdb. The screen's own quote(), rows(), execute() and the other helpers forward here.
 *
 * @since 1.0.0
 */
class Exports_Reports_Query {

	/**
	 * The screen.
	 *
	 * @var Exports_Reports_Admin_UI
	 */
	public $admin;

	/**
	 * @param Exports_Reports_Admin_UI $admin The screen whose statements these are.
	 */
	public function __construct( Exports_Reports_Admin_UI $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Escapes a value, or each value of an array or object, for a statement that is not prepared: esc_sql() on
	 * the trimmed text, numbers as they are.
	 *
	 * @param mixed $input The value.
	 *
	 * @return mixed
	 */
	public function sanitize( $input ) {
		$output = array();

		if ( is_numeric( $input ) ) {
			$output = $input;
		} elseif ( is_object( $input ) ) {
			$input = (array) $input;
			foreach ( $input as $key => $val ) {
				$output[ $key ] = $this->sanitize( $val );
			}
			$output = (object) $output;
		} elseif ( is_array( $input ) ) {
			foreach ( $input as $key => $val ) {
				$output[ $key ] = $this->sanitize( $val );
			}
		} elseif ( ! empty( $input ) ) {
			$output = esc_sql( trim( $input ) );
		}

		return $output;
	}

	/**
	 * A column or table name as a quoted identifier: backticks around it, a backtick inside it doubled.
	 *
	 * @param string $name The name, from the report definition.
	 *
	 * @return string
	 */
	public function backtick( $name ) {
		return '`' . str_replace( '`', '``', (string) $name ) . '`';
	}

	/**
	 * The names of the report's columns, whether they are set up already (keyed arrays) or still a list of names.
	 *
	 * @return string[]
	 */
	public function column_names() {
		$names = array();

		foreach ( (array) $this->admin->columns as $key => $column ) {
			$names[] = (string) ( is_array( $column ) ? $key : $column );
		}

		return $names;
	}

	/**
	 * A value as a SQL string literal, quoted and escaped by $wpdb->prepare(). A literal % in it stands behind
	 * wpdb's placeholder escape until the statement runs through wpdb, as rows() and execute() do; a statement
	 * run anywhere else takes $wpdb->remove_placeholder_escape() first.
	 *
	 * @since 0.9.5
	 *
	 * @param string $value The value.
	 *
	 * @return string
	 */
	public function quote( $value ) {
		global $wpdb;

		return (string) $wpdb->prepare( '%s', $value );
	}

	/**
	 * Splits SQL into its statements, at the semicolons outside quoted strings, quoted identifiers and
	 * comments. A report may set session variables, or prepare a dynamic statement, before the query that
	 * returns its rows (SET @day = CURRENT_DATE(); EXECUTE IMMEDIATE CONCAT( ... )). The database takes one
	 * statement per call, so the plugin runs them one at a time, in order. A statement that is empty or only a
	 * comment (a doubled or a trailing semicolon) is left out.
	 *
	 * @since 1.0.2
	 *
	 * @param string $sql The SQL.
	 *
	 * @return string[] The statements, trimmed, in order.
	 */
	public static function statements( $sql ) {
		$sql        = (string) $sql;
		$length     = strlen( $sql );
		$statements = array();
		$statement  = '';
		$quote      = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $sql[ $i ];

			if ( '' !== $quote ) {
				// Inside a quoted string or identifier; in a string, a backslash escapes the character after it.
				if ( '\\' === $char && '`' !== $quote && $i + 1 < $length ) {
					$statement .= $char . $sql[ $i + 1 ];
					++$i;
					continue;
				}

				if ( $char === $quote ) {
					$quote = '';
				}

				$statement .= $char;
				continue;
			}

			if ( "'" === $char || '"' === $char || '`' === $char ) {
				$quote      = $char;
				$statement .= $char;
				continue;
			}

			$end = false;

			if ( '#' === $char || ( '-' === $char && '--' === substr( $sql, $i, 2 ) && ( $i + 2 === $length || '' === trim( $sql[ $i + 2 ] ) ) ) ) {
				// A comment to the end of its line.
				$end = strpos( $sql, "\n", $i );
				$end = false === $end ? $length : $end;
			} elseif ( '/' === $char && '/*' === substr( $sql, $i, 2 ) ) {
				// A block comment.
				$end = strpos( $sql, '*/', $i + 2 );
				$end = false === $end ? $length : $end + 2;
			}

			if ( false !== $end ) {
				$statement .= substr( $sql, $i, $end - $i );
				$i          = $end - 1;
				continue;
			}

			if ( ';' !== $char ) {
				$statement .= $char;
				continue;
			}

			if ( self::has_sql( $statement ) ) {
				$statements[] = trim( $statement );
			}

			$statement = '';
		}

		if ( self::has_sql( $statement ) ) {
			$statements[] = trim( $statement );
		}

		return $statements;
	}

	/**
	 * Whether a statement has anything in it besides whitespace and comments.
	 *
	 * @since 1.0.2
	 *
	 * @param string $statement The statement.
	 *
	 * @return bool
	 */
	protected static function has_sql( $statement ) {
		return '' !== trim( (string) preg_replace( '~/\\*.*?\\*/|(?:--[ \\t]|#)[^\\n]*|--$~ms', '', $statement ) );
	}

	/**
	 * Runs every statement of the SQL but the last on wpdb's connection, where the last one finds the session
	 * variables and temporary tables they set up, and returns the last for the caller to run the way it
	 * needs: buffered, or unbuffered for a streamed export.
	 *
	 * @since 1.0.2
	 *
	 * @param string $sql The SQL, one statement or several.
	 *
	 * @return string|false The last statement; false when one before it failed ($wpdb->last_error says how) or when there is none.
	 */
	public function run_leading_statements( $sql ) {
		global $wpdb;

		$statements = self::statements( $sql );
		$last       = array_pop( $statements );

		if ( null === $last ) {
			return false;
		}

		foreach ( $statements as $statement ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- the administrator's own statement; see rows().
			if ( false === $wpdb->query( $statement ) ) {
				return false;
			}
		}

		return $last;
	}

	/**
	 * Runs a statement and returns its rows. The one place the plugin runs SQL it did not prepare in full: the
	 * report's query and count query as the administrator wrote them, with the plugin's clauses around prepared
	 * values; a lookup on a related table the definition names; the screen's own table. Only a user with the
	 * plugin's settings capability writes a definition.
	 *
	 * @since 0.9.5
	 * @since 1.0.2 Several statements run one at a time; the rows are the last one's.
	 *
	 * @param string $sql    The statement, or several (see statements()).
	 * @param string $output OBJECT or ARRAY_A, as $wpdb->get_results() takes it.
	 *
	 * @return array<int, mixed> Empty when a statement before the last failed.
	 */
	public function rows( $sql, $output = OBJECT ) {
		global $wpdb;

		$sql = $this->run_leading_statements( $sql );

		if ( false === $sql ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- the administrator's own statement, or one of the plugin's with its values prepared; see the method's description.
		return $wpdb->get_results( $sql, $output );
	}

	/**
	 * Runs a statement that writes to the screen's own table (the form's INSERT, UPDATE and DELETE), its values
	 * quoted by quote() or cast.
	 *
	 * @since 0.9.5
	 * @since 1.0.2 Several statements run one at a time; the result is the last one's.
	 *
	 * @param string $sql The statement, or several (see statements()).
	 *
	 * @return int|bool Rows affected, or false on error.
	 */
	public function execute( $sql ) {
		global $wpdb;

		$sql = $this->run_leading_statements( $sql );

		if ( false === $sql ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- the screen's own table; the values are quoted or cast by the caller.
		return $wpdb->query( $sql );
	}

	/**
	 * Removes the slashes a request adds, from a value or from each value of an array or object.
	 *
	 * @param mixed $input The value.
	 *
	 * @return mixed
	 */
	public function unsanitize( $input ) {
		$output = array();
		if ( is_object( $input ) ) {
			$input = (array) $input;
			foreach ( $input as $key => $val ) {
				$output[ $key ] = $this->unsanitize( $val );
			}
			$output = (object) $output;
		} elseif ( is_array( $input ) ) {
			foreach ( $input as $key => $val ) {
				$output[ $key ] = $this->unsanitize( $val );
			}
		} elseif ( empty( $input ) ) {
			$output = $input;
		} else {
			$output = stripslashes( $input );
		}

		return $output;
	}

	/**
	 * The keywords and placeholders of a statement, each with the parenthesis depth and group it sits in:
	 * SELECT, FROM, WHERE, GROUP BY, HAVING, ORDER BY, LIMIT and the plugin's %%NAME%% placeholders, outside
	 * quoted strings, quoted identifiers and comments. The SQL of an EXECUTE IMMEDIATE statement sits in a
	 * string: that string's quote is read through, so the words inside count as the statement's own.
	 *
	 * @since 1.1.3
	 *
	 * @param string $sql One statement.
	 *
	 * @return array{tokens: array<int, array{kind: string, pos: int, end: int, depth: int, group: int}>, end: int}
	 *               The tokens in order (kind: the keyword in lower case, 'group' and 'order' standing for GROUP BY
	 *               and ORDER BY, or the placeholder as written), and the offset where the statement's own text
	 *               ends: before the closing quote of a dynamic statement, else after its last character.
	 */
	public static function tokens( $sql ) {
		$sql     = (string) $sql;
		$length  = strlen( $sql );
		$through = '';
		$end     = strlen( rtrim( $sql, " \t\n\r;" ) );

		if ( preg_match( '/^\s*EXECUTE\s+IMMEDIATE\b/i', $sql, $head ) ) {
			$first = strlen( $head[0] ) + strcspn( $sql, "'\"", strlen( $head[0] ) );

			if ( $first < $length ) {
				$through = $sql[ $first ];
				$close   = strrpos( $sql, $through );

				if ( false !== $close && $close > $first ) {
					$end = $close;
				}
			}
		}

		$keywords = array( 'select', 'from', 'where', 'group', 'having', 'order', 'limit' );
		$tokens   = array();
		$depth    = 0;
		$groups   = array( 0 => -1 );
		$quote    = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $sql[ $i ];

			if ( '' !== $quote ) {
				if ( '\\' === $char && '`' !== $quote && $i + 1 < $length ) {
					++$i;
				} elseif ( $char === $quote ) {
					$quote = '';
				}

				continue;
			}

			if ( '' !== $through && $char === $through ) {
				continue;
			}

			if ( "'" === $char || '"' === $char || '`' === $char ) {
				$quote = $char;
				continue;
			}

			if ( '#' === $char || ( '-' === $char && '--' === substr( $sql, $i, 2 ) && ( $i + 2 === $length || '' === trim( $sql[ $i + 2 ] ) ) ) ) {
				$newline = strpos( $sql, "\n", $i );
				$i       = false === $newline ? $length : $newline;
				continue;
			}

			if ( '/' === $char && '/*' === substr( $sql, $i, 2 ) ) {
				$close = strpos( $sql, '*/', $i + 2 );
				$i     = false === $close ? $length : $close + 1;
				continue;
			}

			if ( '(' === $char ) {
				++$depth;
				$groups[ $depth ] = $i;
				continue;
			}

			if ( ')' === $char ) {
				$depth = max( 0, $depth - 1 );
				continue;
			}

			if ( '%' === $char && preg_match( '/^%%([A-Za-z0-9_]+)%%/', substr( $sql, $i, 80 ), $placeholder ) ) {
				$tokens[] = array(
					'kind'  => $placeholder[0],
					'pos'   => $i,
					'end'   => $i + strlen( $placeholder[0] ),
					'depth' => $depth,
					'group' => $groups[ $depth ],
				);
				$i       += strlen( $placeholder[0] ) - 1;
				continue;
			}

			if ( ! ctype_alpha( $char ) || ( 0 < $i && preg_match( '/[A-Za-z0-9_$@.]/', $sql[ $i - 1 ] ) ) ) {
				continue;
			}

			$word_length = strspn( $sql, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_$', $i );
			$word        = strtolower( substr( $sql, $i, $word_length ) );
			$after       = $i + $word_length;

			if ( 'group' === $word || 'order' === $word ) {
				if ( preg_match( '/^\s+BY\b/i', substr( $sql, $after, 16 ), $by ) ) {
					$after += strlen( $by[0] );
				} else {
					$word = '';
				}
			}

			if ( in_array( $word, $keywords, true ) ) {
				$tokens[] = array(
					'kind'  => $word,
					'pos'   => $i,
					'end'   => $after,
					'depth' => $depth,
					'group' => $groups[ $depth ],
				);
			}

			$i = $after - 1;
		}

		return array(
			'tokens' => $tokens,
			'end'    => $end,
		);
	}

	/**
	 * The shape of a statement around its main SELECT, the one whose rows the screen shows: the offsets of
	 * that keyword and of the clauses that belong to it. A SELECT inside parentheses (a subquery, the query of
	 * a WITH clause, a window function's argument) is deeper than the main one: the main SELECT is the first
	 * at the shallowest depth, and only the keywords at its depth, in its group and after it are its own.
	 *
	 * @since 1.1.3
	 *
	 * @param string $sql One statement.
	 *
	 * @return array{select: int|false, select_end: int, from: int|false, from_end: int, where: int|false, where_end: int, group: int|false, group_end: int, having: int|false, having_end: int, order: int|false, order_end: int, limit: int|false, limit_end: int, end: int, depth: int}
	 *               For each keyword its offset (false when the main SELECT has none) and the offset after it;
	 *               end, where a clause goes when the statement has nothing for it to go before (before the
	 *               closing quote of a dynamic statement); depth, the main SELECT's.
	 */
	public static function outline( $sql ) {
		$scan    = self::tokens( $sql );
		$outline = array(
			'select'     => false,
			'select_end' => 0,
			'from'       => false,
			'from_end'   => 0,
			'where'      => false,
			'where_end'  => 0,
			'group'      => false,
			'group_end'  => 0,
			'having'     => false,
			'having_end' => 0,
			'order'      => false,
			'order_end'  => 0,
			'limit'      => false,
			'limit_end'  => 0,
			'end'        => $scan['end'],
			'depth'      => 0,
		);
		$main    = false;

		foreach ( $scan['tokens'] as $token ) {
			if ( 'select' === $token['kind'] && ( false === $main || $token['depth'] < $main['depth'] ) ) {
				$main = $token;
			}
		}

		if ( false === $main ) {
			return $outline;
		}

		$outline['select']     = $main['pos'];
		$outline['select_end'] = $main['end'];
		$outline['depth']      = $main['depth'];

		foreach ( $scan['tokens'] as $token ) {
			$kind = $token['kind'];

			if ( ! isset( $outline[ $kind ] ) || 'select' === $kind || $token['pos'] <= $main['pos'] || $token['depth'] !== $main['depth'] || $token['group'] !== $main['group'] ) {
				continue;
			}

			if ( false === $outline[ $kind ] ) {
				$outline[ $kind ]          = $token['pos'];
				$outline[ $kind . '_end' ] = $token['end'];
			}
		}

		return $outline;
	}

	/**
	 * The WHERE or HAVING text the filters add to a statement. Where the author wrote the %%WHERE%% or
	 * %%HAVING%% placeholder, the text fits that spot: the conditions and AND right after the clause's own
	 * keyword, AND and the conditions further along a clause, the conditions alone after an AND or OR the
	 * author left for them, the clause with its keyword where the query around the placeholder has none.
	 * Without a placeholder: the conditions and AND when the main SELECT has the clause (place_clauses()
	 * puts them right after its keyword), otherwise the whole clause.
	 *
	 * @since 0.9.5
	 * @since 1.1.3 Judged by the main SELECT's own clauses, not the first keyword in the text.
	 *
	 * @param string   $sql        The statement.
	 * @param string   $keyword    'WHERE' or 'HAVING'.
	 * @param string[] $conditions The conditions, values prepared.
	 *
	 * @return string '' when there is no condition.
	 */
	public function clause_sql( $sql, $keyword, array $conditions ) {
		if ( empty( $conditions ) ) {
			return '';
		}

		$conditions  = ' (' . implode( ' AND ', $conditions ) . ') ';
		$placeholder = '%%' . $keyword . '%%';
		$kind        = strtolower( $keyword );
		$scan        = self::tokens( $sql );

		foreach ( $scan['tokens'] as $token ) {
			if ( $token['kind'] !== $placeholder ) {
				continue;
			}

			$own = false;

			foreach ( $scan['tokens'] as $other ) {
				if ( $other['kind'] === $kind && $other['group'] === $token['group'] && $other['pos'] < $token['pos'] ) {
					$own = $other;
				}
			}

			if ( false === $own ) {
				return ' ' . $keyword . $conditions;
			}

			$between = trim( substr( $sql, $own['end'], $token['pos'] - $own['end'] ) );

			if ( '' === $between ) {
				return $conditions . 'AND ';
			}

			if ( preg_match( '/\b(AND|OR)$/i', $between ) ) {
				return $conditions;
			}

			return ' AND' . $conditions;
		}

		$outline = self::outline( $sql );

		return false === $outline[ $kind ] ? ' ' . $keyword . $conditions : $conditions . 'AND ';
	}

	/**
	 * Puts the plugin's clauses into a statement, at its main SELECT (see outline()). A %%WHERE%%, %%HAVING%%,
	 * %%ORDERBY%% or %%LIMIT%% placeholder the author wrote is where a clause goes, wherever it is. Otherwise a
	 * clause with text goes right after the main SELECT's own keyword, or before the first of its later clauses,
	 * or at the end of its text (inside the string of a dynamic statement). A main SELECT with a LIMIT of its
	 * own and no placeholder keeps it: the screen shows what the statement returns, as one page. The filters'
	 * own %%name%% variables are substituted, then each placeholder is replaced by its clause.
	 *
	 * @since 0.9.5
	 * @since 1.1.3 At the main SELECT, not the first keyword in the text.
	 *
	 * @param string                $sql       The statement, a space at each end.
	 * @param array<string, string> $clauses   The clauses by placeholder name (WHERE, HAVING, ORDERBY, LIMIT), '' for none: the
	 *                                         first three with their keyword when the statement has none, the LIMIT as the page alone.
	 * @param array<string, string> $variables The filters' conditions by the %%name%% the definition wrote for them.
	 *
	 * @return string
	 */
	public function place_clauses( $sql, array $clauses, array $variables = array() ) {
		$kinds     = array(
			'WHERE'   => 'where',
			'HAVING'  => 'having',
			'ORDERBY' => 'order',
			'LIMIT'   => 'limit',
		);
		$followers = array(
			'WHERE'   => array( 'group', 'having', 'order', 'limit' ),
			'HAVING'  => array( 'order', 'limit' ),
			'ORDERBY' => array( 'limit' ),
			'LIMIT'   => array(),
		);
		// The WHERE, HAVING and ORDER BY clauses bring their keyword when the statement has none; the LIMIT clause is the page alone.
		$appended = array(
			'WHERE'   => ' %%WHERE%%',
			'HAVING'  => ' %%HAVING%%',
			'ORDERBY' => ' %%ORDERBY%%',
			'LIMIT'   => ' LIMIT %%LIMIT%%',
		);

		foreach ( $clauses as $name => $clause ) {
			$placeholder = '%%' . $name . '%%';

			if ( '' === $clause || false !== stripos( $sql, $placeholder ) ) {
				continue;
			}

			$outline = self::outline( $sql );
			$kind    = $kinds[ $name ];

			if ( false !== $outline[ $kind ] ) {
				if ( 'LIMIT' === $name ) {
					continue;
				}

				$sql = substr_replace( $sql, ' ' . $placeholder, $outline[ $kind . '_end' ], 0 );
				continue;
			}

			$at = $outline['end'];

			foreach ( $followers[ $name ] as $follower ) {
				if ( false !== $outline[ $follower ] ) {
					$at = $outline[ $follower ];
					break;
				}
			}

			$sql = substr_replace( $sql, $appended[ $name ] . ' ', $at, 0 );
		}

		foreach ( $variables as $name => $value ) {
			$sql = str_ireplace( '%%' . $name . '%%', $value, $sql );
		}

		foreach ( $clauses as $name => $clause ) {
			$sql = str_ireplace( '%%' . $name . '%%', $clause, $sql );
		}

		return $sql;
	}

	/**
	 * The report query, and its count query when the report has one, for a page or for every row:
	 * SQL_CALC_FOUND_ROWS and the SUM() of each total field in the select list, the search and the filters
	 * as prepared conditions in the WHERE or HAVING clause (or at the %%name%% placeholder the definition
	 * wrote for them), the sort column checked against the report's columns, the page's LIMIT. The screen's
	 * sql_count, order, order_dir and order_from_request settings are normalized on the way.
	 *
	 * @since 1.0.0 Out of Exports_Reports_Admin_UI::get_data().
	 * @since 1.0.2 A definition of several statements keeps the ones before the last as they are.
	 * @since 1.2.0 The $count argument, and count_key in the result.
	 *
	 * @param bool $full  Whether every row is wanted, as for an export: no count and no LIMIT.
	 * @param bool $count Whether the page's rows are counted (SQL_CALC_FOUND_ROWS); false when a kept count serves.
	 *
	 * @return array{sql: string, sql_count: string, where: string, having: string, count_key: string} count_key is the
	 *               text of the rows the count describes: the statement with its conditions and variables in, without the
	 *               count, the sort and the page, as the key of a kept count (Exports_Reports_Admin_UI::count_cache_key()).
	 */
	public function statement( $full = false, $count = true ) {
		/** @global wpdb $wpdb */ global $wpdb;

		$admin = $this->admin;

		$admin->sql_count = trim( $admin->sql_count );
		if ( empty( $admin->sql_count ) ) {
			$admin->sql_count = false;
		}
		if ( false === $admin->sql ) {
			$admin->sql = "SELECT * FROM {$admin->table}";
		}
		// The statements before the last, if any (session variables, a prepared dynamic statement), run as they
		// are; the plugin's additions go into the last, the one that returns the rows.
		$leading = self::statements( $admin->sql );
		$sql     = ' ' . str_replace( array( "\n", "\r", "\t", '  ' ), ' ', ' ' . (string) array_pop( $leading ) ) . ' ';
		// The main SELECT, the one whose rows the screen shows (see outline()): the author's own SQL_CALC_FOUND_ROWS
		// after it comes off, and its select list is read before anything is added to it.
		$outline = self::outline( $sql );
		if ( false !== $outline['select'] && preg_match( '/^\s+SQL_CALC_FOUND_ROWS\b/i', substr( $sql, $outline['select_end'] ), $own_count ) ) {
			$sql     = substr_replace( $sql, '', $outline['select_end'], strlen( $own_count[0] ) );
			$outline = self::outline( $sql );
		}
		$selectmatches = array();
		if ( false !== $outline['select'] && false !== $outline['from'] ) {
			$selectmatches[1] = substr( $sql, $outline['select_end'], $outline['from'] - $outline['select_end'] );
		}
		// A statement with a LIMIT of its own and no placeholder pages itself: the screen shows what it returns.
		$own_limit = false !== $outline['limit'] && false === stripos( $sql, '%%LIMIT%%' );
		// The statement as the author wrote it, for the key of a kept count.
		$bare_sql = $sql;
		// What goes after the main SELECT: the row count for the page view (an export reads every row and needs
		// none, a kept count spares it, and a totals query feeds the sum row, not the count), then a SUM() per
		// total field.
		$select_extras = $full || $own_limit || ! $count ? '' : 'SQL_CALC_FOUND_ROWS';
		$totals        = array();
		foreach ( $admin->columns as $key => $column ) {
			$attributes = $column;
			if ( ! is_array( $attributes ) ) {
				$attributes = $admin->setup_column( $column );
			}
			if ( true !== $attributes['total_field'] ) {
				continue;
			}
			if ( is_array( $column ) ) {
				$column = $key;
			}
			$columnfield = '`' . $column . '`';
			if ( false !== $attributes['real_name'] ) {
				$columnfield = $attributes['real_name'];
			}
			$totals[] = 'SUM(' . $columnfield . ') AS ' . $this->backtick( 'exports_reports_total_' . $column );
		}
		if ( ! empty( $totals ) ) {
			$select_extras .= ' ' . implode( ',', $totals ) . ', ';
		}
		if ( '' !== trim( $select_extras ) && false !== $outline['select'] ) {
			$sql = substr_replace( $sql, ' ' . trim( $select_extras ), $outline['select_end'], 0 );
		}
		$wheresql          = '';
		$havingsql         = '';
		$ordersql          = '';
		$limitsql          = '';
		$other_sql         = array();
		$having_sql        = array();
		$replace_variables = array();
		$selects           = array();
		// The columns the SELECT list names, for the sort check: its aliases and its bare column names. A list
		// with a * or an unaliased expression names fewer columns than the result has, so it settles nothing.
		$select_names = array();
		$select_known = isset( $selectmatches[1] ) && '' !== trim( $selectmatches[1] );
		if ( $select_known ) {
			$theselects = explode( ', ', $selectmatches[1] );
			if ( 1 === count( $theselects ) ) {
				$theselects = explode( ',', $selectmatches[1] );
			}
			foreach ( $theselects as $selected ) {
				$selectfield = preg_split( '/ AS /i', $selected );
				if ( count( $selectfield ) === 2 ) {
					$field             = trim( trim( $selectfield[1] ), '`' );
					$real_field        = trim( trim( $selectfield[0] ), '`' );
					$selects[ $field ] = $real_field;
					$select_names[]    = $field;
				} elseif ( preg_match( '/^`?(?:[a-z0-9_]+`?\.`?)?([a-z0-9_]+)`?$/i', trim( $selected ), $bare ) ) {
					$select_names[] = $bare[1];
				} else {
					$select_known = false;
				}
			}
		}
		if ( false !== $admin->search && ! empty( $admin->search_columns ) ) {
			if ( false !== $admin->search_query && 0 < strlen( $admin->search_query ) ) {
				foreach ( $admin->search_columns as $key => $column ) {
					$attributes = $column;
					if ( ! is_array( $attributes ) ) {
						$attributes = $admin->setup_column( $column );
					}
					if ( false === $attributes['search'] ) {
						continue;
					}
					if ( in_array( $attributes['type'], array( 'date', 'time', 'datetime' ), true ) ) {
						continue;
					}
					if ( is_array( $column ) ) {
						$column = $key;
					}
					if ( isset( $admin->filters[ $column ] ) ) {
						continue;
					}
					$columnfield = '`' . $column . '`';
					if ( isset( $selects[ $column ] ) ) {
						$columnfield = '`' . $selects[ $column ] . '`';
					}
					if ( false !== $attributes['real_name'] ) {
						$columnfield = $attributes['real_name'];
					}

					$filter_field_key = str_replace( '`', '', $columnfield );
					$like             = $columnfield . ' LIKE ' . $this->quote( '%' . $wpdb->esc_like( $admin->search_query ) . '%' );

					if ( false !== $attributes['group_related'] ) {
						$having_sql[ $filter_field_key ] = $like;
					} else {
						$other_sql[ $filter_field_key ] = $like;
					}
				}
				if ( ! empty( $other_sql ) ) {
					foreach ( $other_sql as $key => $value ) {
						if ( false !== stripos( $sql, '%%' . $key . '%%' ) ) {
							$replace_variables[ $key ] = $value;

							unset( $other_sql[ $key ] );
						}
					}
				}
				if ( ! empty( $other_sql ) ) {
					$other_sql = array( '(' . implode( ' OR ', $other_sql ) . ')' );
				}
				if ( ! empty( $having_sql ) ) {
					foreach ( $having_sql as $key => $value ) {
						if ( false !== stripos( $sql, '%%' . $key . '%%' ) ) {
							$replace_variables[ $key ] = $value;

							unset( $having_sql[ $key ] );
						}
					}
				}
				if ( ! empty( $having_sql ) ) {
					$having_sql = array( '(' . implode( ' OR ', $having_sql ) . ')' );
				}
			}
			foreach ( $admin->filters as $filter ) {
				if ( ! isset( $admin->search_columns[ $filter ] ) ) {
					continue;
				}

				$filter_column = $admin->search_columns[ $filter ];
				$filter_values = $admin->filter_values( 'filter_' . $filter, $filter_column['filter_default'] );

				$filterfield         = '`' . $filter . '`';
				$filter_sql          = false;
				$search_value        = false;
				$related_filterfield = false;

				if ( isset( $selects[ $filter ] ) ) {
					$filterfield = '`' . $selects[ $filter ] . '`';
				}
				if ( false !== $filter_column['real_name'] ) {
					$filterfield = $filter_column['real_name'];
				}
				if ( in_array( $filter_column['type'], array( 'date', 'datetime' ), true ) ) {
					$start        = date_i18n( 'Y-m-d' ) . ( 'datetime' === $filter_column['type'] ? ' 00:00:00' : '' );
					$end          = date_i18n( 'Y-m-d' ) . ( 'datetime' === $filter_column['type'] ? ' 23:59:59' : '' );
					$start_values = $admin->filter_values( 'filter_' . $filter . '_start', $filter_column['filter_default'] );
					$end_values   = $admin->filter_values( 'filter_' . $filter . '_end', $filter_column['filter_ongoing_default'] );
					if ( array() === $start_values && array() === $end_values ) {
						continue;
					}
					if ( array() !== $start_values ) {
						$start = date_i18n( 'Y-m-d', strtotime( $start_values[0] ) ) . ( 'datetime' === $filter_column['type'] ? ' 00:00:00' : '' );
					}
					if ( array() !== $end_values ) {
						$end = date_i18n( 'Y-m-d', strtotime( $end_values[0] ) ) . ( 'datetime' === $filter_column['type'] ? ' 23:59:59' : '' );
					}
					$from = $this->quote( $start );
					$to   = $this->quote( $end );

					if ( false !== $filter_column['date_ongoing'] ) {
						$date_ongoing = $filter_column['date_ongoing'];
						if ( isset( $selects[ $date_ongoing ] ) ) {
							$date_ongoing = $selects[ $date_ongoing ];
						}

						$filter_sql = "(($filterfield <= $from OR ($filterfield >= $from AND $filterfield <= $to)) AND ($date_ongoing >= $from OR ($date_ongoing >= $from AND $date_ongoing <= $to)))";
					} else {
						$filter_sql = "($filterfield BETWEEN $from AND $to)";
					}
				} elseif ( array() !== $filter_values && 'related' === $filter_column['type'] && false !== $filter_column['related'] ) {
					if ( ! is_array( $filter_column['related'] ) ) {
						$search_values = array();
						foreach ( $filter_values as $filter_value ) {
							$search_values[] = preg_match( '/[^0-9]/', $filter_value ) ? $wpdb->prepare( '%s', $filter_value ) : (string) (int) $filter_value;
						}
						$search_value        = implode( ', ', $search_values );
						$related_filterfield = '`' . $filter . '`.`' . $filter_column['related_id'] . '`';
						if ( isset( $selects[ $filter ] ) ) {
							$related_filterfield = '`' . $selects[ $filter ] . '`.`' . $filter_column['related_id'] . '`';
						}
						if ( false !== $filter_column['real_name'] ) {
							$related_filterfield = $filter_column['real_name'];
						}

						$filter_sql = 1 === count( $search_values ) ? "{$related_filterfield} = {$search_value}" : "{$related_filterfield} IN ({$search_value})";
					} else {
						$likes = array();
						foreach ( $filter_values as $filter_value ) {
							$likes[] = $filterfield . ' LIKE ' . $this->quote( '%' . $wpdb->esc_like( $filter_value ) . '%' );
						}
						$filter_sql = 1 === count( $likes ) ? $likes[0] : '(' . implode( ' OR ', $likes ) . ')';
					}
				} elseif ( 'bool' === $filter_column['type'] && array() !== $filter_values ) {
					$filter_sql = "$filterfield = " . (int) $filter_values[0];
				} elseif ( array() !== $filter_values ) {
					$filter_sql = $filterfield . ' LIKE ' . $this->quote( '%' . $wpdb->esc_like( $filter_values[0] ) . '%' );
				}

				if ( $filter_sql ) {
					/**
					 * Filters the condition a filter value adds to the query.
					 *
					 * @param string                   $filter_sql          The condition, values prepared: a literal % in a value
					 *                                                      stands behind wpdb's placeholder escape until the query runs.
					 * @param string                   $filter              The filter's column.
					 * @param array                    $filter_column       The column's attributes.
					 * @param string                   $filterfield         The column as an identifier.
					 * @param string|bool              $related_filterfield The related column, or false.
					 * @param string|array             $search_value        The value from the request.
					 * @param Exports_Reports_Admin_UI $admin               The report.
					 */
					$filter_sql = apply_filters( 'exports_reports_data_filter_sql', $filter_sql, $filter, $filter_column, $filterfield, $related_filterfield, $search_value, $admin );
					$filter_sql = apply_filters_deprecated( 'wp_admin_ui_data_filter_sql', array( $filter_sql, $filter, $filter_column, $filterfield, $related_filterfield, $search_value, $admin ), '0.9.5', 'exports_reports_data_filter_sql' );

					$filter_field_key = str_replace( '`', '', $filterfield );

					if ( false !== $filter_column['group_related'] ) {
						$having_sql[ $filter ] = $filter_sql;
					} else {
						$other_sql[ $filter ] = $filter_sql;
					}
				}
			}
			// A filter the definition wrote a %%name%% placeholder for goes there; the others join the WHERE or HAVING clause.
			foreach ( $other_sql + $having_sql as $key => $value ) {
				if ( false !== stripos( $sql, '%%' . $key . '%%' ) ) {
					$replace_variables[ $key ] = $value;

					unset( $other_sql[ $key ], $having_sql[ $key ] );
				}
			}
			$wheresql  = $this->clause_sql( $sql, 'WHERE', $other_sql );
			$havingsql = $this->clause_sql( $sql, 'HAVING', $having_sql );
		}
		if ( $admin->order_from_request && ( ! empty( $admin->columns ) || $select_known ) ) {
			// The request's column, lower-cased on the way in, against the report's columns as the report writes
			// them. A report whose columns are not known before it runs (no field definitions, a SELECT list that
			// names fewer columns than its result) takes the name as the identifier it was sanitized to; the
			// database checks it.
			$sort_column = false;

			foreach ( array_merge( $this->column_names(), $select_names ) as $name ) {
				if ( strtolower( $name ) === $admin->order ) {
					$sort_column = $name;
					break;
				}
			}

			if ( false === $sort_column ) {
				// The request named a column the report does not have: the report's own order applies.
				$admin->order              = $admin->order_default;
				$admin->order_dir          = $admin->order_dir_default;
				$admin->order_from_request = false;
			} else {
				$admin->order = $sort_column;
			}
		}
		if ( false !== $admin->order && ( false === $admin->reorder || 'reorder' !== $admin->action ) ) {
			$ordersql = trim( ( $admin->order_from_request ? $this->backtick( $admin->order ) : $admin->order ) . ' ' . $admin->order_dir );
		} elseif ( false !== $admin->reorder && 'reorder' === $admin->action ) {
			$ordersql = trim( $admin->reorder_order . ' ' . $admin->reorder_order_dir );
		}
		if ( ! empty( $ordersql ) ) {
			$outline = self::outline( $sql );

			if ( false === $outline['order'] ) {
				$ordersql = ' ORDER BY ' . $ordersql;
			} elseif ( false !== stripos( $sql, ' ORDER BY %%ORDERBY%% ' ) ) {
				$ordersql = $ordersql . ', ';
			} elseif ( false !== stripos( $sql, ' %%ORDERBY%% ' ) ) {
				$ordersql = ',' . $ordersql;
			} else {
				$ordersql = $ordersql . ', ';
			}
		}
		if ( false !== $admin->pagination && ! $full ) {
			$start = ( $admin->page - 1 ) * $admin->limit;

			$limitsql .= (int) $start . ',' . (int) $admin->limit;
		} else {
			$sql = preg_replace( '/\s+LIMIT\s+%%LIMIT%%\s*/i', ' ', $sql );
		}
		$sql = $this->place_clauses(
			$sql,
			array(
				'WHERE'   => $wheresql,
				'HAVING'  => $havingsql,
				'ORDERBY' => $ordersql,
				'LIMIT'   => $limitsql,
			),
			$replace_variables
		);
		// The rows the count describes, as text: the same conditions and variables, no sort and no page.
		$count_key = $this->place_clauses(
			$bare_sql,
			array(
				'WHERE'   => $wheresql,
				'HAVING'  => $havingsql,
				'ORDERBY' => '',
				'LIMIT'   => '',
			),
			$replace_variables
		);
		if ( false !== $admin->sql_count ) {
			// The count query takes the same conditions, placed by its own shape, and one row.
			$leading_count = self::statements( $admin->sql_count );
			$sql_count     = ' ' . preg_replace( '/\s+/', ' ', ' ' . (string) array_pop( $leading_count ) ) . ' ';
			$sql_count     = str_ireplace( ' SELECT SQL_CALC_FOUND_ROWS ', ' SELECT ', $sql_count );
			$sql_count     = $this->place_clauses(
				$sql_count,
				array(
					'WHERE'  => $this->clause_sql( $sql_count, 'WHERE', $other_sql ),
					'HAVING' => $this->clause_sql( $sql_count, 'HAVING', $having_sql ),
					'LIMIT'  => '1',
				),
				$replace_variables
			);

			if ( ! empty( $leading_count ) ) {
				$sql_count = implode( ";\n", $leading_count ) . ";\n" . $sql_count;
			}
		}

		if ( ! empty( $leading ) ) {
			$sql       = implode( ";\n", $leading ) . ";\n" . $sql;
			$count_key = implode( ";\n", $leading ) . ";\n" . $count_key;
		}

		return array(
			'sql'       => $sql,
			'sql_count' => isset( $sql_count ) ? $sql_count : '',
			'where'     => $wheresql,
			'having'    => $havingsql,
			'count_key' => $count_key,
		);
	}
}
