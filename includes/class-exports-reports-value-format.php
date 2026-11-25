<?php
/**
 * How a report's numbers and dates are written: for the screen, for a file as the plugin has always
 * written it, or for a language the person who exports chose.
 *
 * @package Exports_And_Reports
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes the values of number, decimal, date, time and datetime columns one of three ways.
 *
 * raw(): what the file exports have always held, whatever the site's language: 1,234.50 and 2026/03/07
 * 2:05:09 PM. A script that reads an export reads this, and it does not change.
 *
 * screen(): the report page, the view screen and the PDF. Numbers take the separators of the language
 * WordPress runs in; dates stay as raw() writes them unless the Settings page says to use the site's
 * date and time formats.
 *
 * locale(): an export formatted for a language, chosen from the Format menu beside the export buttons
 * or named by a REST request. PHP's intl extension writes the numbers and the dates of any language;
 * without it, the language pack WordPress has installed for the language does.
 *
 * Dates are written as they are stored: no time zone is applied, here or anywhere in the plugin.
 *
 * @since 1.3.0
 */
class Exports_Reports_Value_Format {

	/**
	 * raw, screen or locale.
	 *
	 * @var string
	 */
	protected $kind = 'raw';

	/**
	 * The WordPress locale of a locale() format, '' otherwise.
	 *
	 * @var string
	 */
	protected $locale = '';

	/**
	 * The ICU locale intl formats with, '' when WordPress's own data does.
	 *
	 * @var string
	 */
	protected $intl = '';

	/**
	 * Whether intl keeps the digits 0 to 9 in a language that has digits of its own.
	 *
	 * @var bool
	 */
	protected $western_digits = true;

	/**
	 * The number formatters of intl, by decimals and grouping.
	 *
	 * @var NumberFormatter[]
	 */
	protected $numbers = array();

	/**
	 * The date formatters of intl, by column type; null for a type intl could not set up.
	 *
	 * @var array<string, IntlDateFormatter|null>
	 */
	protected $dates = array();

	/**
	 * The thousands separator and the decimal point WordPress's data gives.
	 *
	 * @var array{thousands_sep: string, decimal_point: string}
	 */
	protected $separators = array(
		'thousands_sep' => ',',
		'decimal_point' => '.',
	);

	/**
	 * The PHP date format of each column type.
	 *
	 * @var array<string, string>
	 */
	protected $patterns = array(
		'date'     => 'Y/m/d',
		'time'     => 'g:i:s A',
		'datetime' => 'Y/m/d g:i:s A',
	);

	/**
	 * The month, weekday and meridiem names of a language pack, for a date format that writes any.
	 *
	 * @var WP_Locale|null
	 */
	protected $names = null;

	/**
	 * Use raw(), screen() or locale().
	 *
	 * @param string $kind raw, screen or locale.
	 */
	protected function __construct( $kind ) {
		$this->kind = $kind;
	}

	/**
	 * The values as the file exports have always held them.
	 *
	 * @return self
	 */
	public static function raw() {
		return new self( 'raw' );
	}

	/**
	 * The values as the screens and the PDF show them: numbers by the language WordPress runs in, dates
	 * as raw() unless the Settings page says to use the site's date and time formats.
	 *
	 * @return self
	 */
	public static function screen() {
		global $wp_locale;

		$format = new self( 'screen' );

		if ( $wp_locale instanceof WP_Locale ) {
			$format->separators = array(
				'thousands_sep' => (string) $wp_locale->number_format['thousands_sep'],
				'decimal_point' => (string) $wp_locale->number_format['decimal_point'],
			);
		}

		if ( self::uses_site_date_formats() ) {
			$date = (string) get_option( 'date_format', 'Y/m/d' );
			$time = (string) get_option( 'time_format', 'g:i:s A' );

			$format->patterns = array(
				'date'     => $date,
				'time'     => $time,
				'datetime' => $date . ' ' . $time,
			);
		}

		return $format;
	}

	/**
	 * The values as a language writes them.
	 *
	 * @param string $locale A WordPress locale (it_IT, de_DE_formal).
	 *
	 * @return self raw() when the locale is none.
	 */
	public static function locale( $locale ) {
		$locale = self::sanitize_locale( $locale );

		if ( '' === $locale ) {
			return self::raw();
		}

		$format         = new self( 'locale' );
		$format->locale = $locale;

		/**
		 * Filters whether an export formatted for a language keeps the digits 0 to 9. intl writes Arabic,
		 * Persian, Bengali and other languages with digits of their own unless told otherwise; a
		 * spreadsheet reads the Western ones as numbers.
		 *
		 * @since 1.3.0
		 *
		 * @param bool   $western True by default.
		 * @param string $locale  The WordPress locale.
		 */
		$format->western_digits = (bool) apply_filters( 'exports_reports_export_western_digits', true, $locale );

		if ( self::has_intl() ) {
			$format->intl = self::intl_locale( $locale );
		}

		if ( '' === $format->intl ) {
			$format->read_language_pack();
		}

		return $format;
	}

	/**
	 * Whether the Settings page says the screens and the PDF write dates in the site's date and time
	 * formats (Settings > General).
	 *
	 * @return bool
	 */
	public static function uses_site_date_formats() {
		return (bool) get_option( 'exports_reports_site_date_formats', false );
	}

	/**
	 * Whether PHP's intl extension formats the exports of a language.
	 *
	 * @return bool
	 */
	public static function has_intl() {
		$loaded = class_exists( 'NumberFormatter' ) && class_exists( 'IntlDateFormatter' ) && class_exists( 'Locale' );

		/**
		 * Filters whether PHP's intl extension formats the exports of a language. Without it, the
		 * language pack WordPress has installed for the language does.
		 *
		 * @since 1.3.0
		 *
		 * @param bool $use Whether the extension is loaded.
		 */
		return $loaded && (bool) apply_filters( 'exports_reports_use_intl', $loaded );
	}

	/**
	 * A WordPress locale as it may be named: letters, digits and underscores, the language first
	 * (it_IT, de_DE_formal, ckb).
	 *
	 * @param mixed $locale The locale, from a request, a report definition or a filter.
	 *
	 * @return string '' when it is no locale.
	 */
	public static function sanitize_locale( $locale ) {
		$locale = is_string( $locale ) ? trim( $locale ) : '';

		return 1 === preg_match( '/^[a-z]{2,3}(?:_[A-Za-z0-9]{2,12}){0,3}$/', $locale ) ? $locale : '';
	}

	/**
	 * The languages the Format menu offers: the user's own, the site's when it differs, then every other
	 * language installed on the site, each with its name.
	 *
	 * @return array<string, string> The name of each language by its WordPress locale.
	 */
	public static function locales() {
		$own    = function_exists( 'get_user_locale' ) ? self::sanitize_locale( get_user_locale() ) : '';
		$site   = self::sanitize_locale( get_locale() );
		$others = array();

		foreach ( array_merge( array( 'en_US' ), get_available_languages() ) as $locale ) {
			$locale = self::sanitize_locale( $locale );

			if ( '' !== $locale ) {
				$others[ $locale ] = self::locale_name( $locale );
			}
		}

		asort( $others, SORT_FLAG_CASE | SORT_STRING );

		$locales = array();

		foreach ( array( $own, $site ) as $locale ) {
			if ( '' !== $locale ) {
				$locales[ $locale ] = self::locale_name( $locale );
			}
		}

		return $locales + $others;
	}

	/**
	 * The name of a language, in the language WordPress runs in when intl is loaded: "Italian (Italy)".
	 * Without intl, or for a language intl has no name for, the locale itself.
	 *
	 * @param string $locale A WordPress locale.
	 *
	 * @return string
	 */
	public static function locale_name( $locale ) {
		$locale = self::sanitize_locale( $locale );

		if ( '' === $locale || ! class_exists( 'Locale' ) ) {
			return $locale;
		}

		$id = self::intl_locale( $locale );

		if ( '' === $id ) {
			return $locale;
		}

		$name = (string) Locale::getDisplayName( $id, (string) preg_replace( '/^([a-z]{2,3}(?:_[A-Z]{2})?).*$/', '$1', determine_locale() ) );

		// The variant of a locale WordPress keeps apart (de_DE_formal) shows beside the name.
		return '' === $name || $name === $id ? $locale : $name . ( strlen( $locale ) > strlen( $id ) && 0 === strpos( $locale, $id ) ? ' [' . $locale . ']' : '' );
	}

	/**
	 * The ICU locale of a WordPress locale, when intl knows the language: the language and the region,
	 * without the variants WordPress adds (de_DE_formal is de_DE to intl).
	 *
	 * @param string $locale A WordPress locale.
	 *
	 * @return string '' when intl is not loaded or has no data for the language.
	 */
	protected static function intl_locale( $locale ) {
		if ( ! class_exists( 'NumberFormatter' ) || 1 !== preg_match( '/^([a-z]{2,3})(?:_([A-Z]{2}))?(?:_|$)/', $locale, $parts ) ) {
			return '';
		}

		$id = $parts[1] . ( empty( $parts[2] ) ? '' : '_' . $parts[2] );

		// A locale intl cannot open leaves no formatter, or one that stops when it is used.
		try {
			$known = (string) NumberFormatter::create( $id, NumberFormatter::DECIMAL )->getLocale( Locale::VALID_LOCALE );
		} catch ( Throwable $error ) {
			return '';
		}

		// A language ICU has no data for comes back as its root, or as the POSIX locale.
		return in_array( $known, array( '', 'root', 'en_US_POSIX' ), true ) ? '' : $id;
	}

	/**
	 * Reads how a language writes numbers and dates from the language pack WordPress has installed for
	 * it: the separators of its numbers, and core's own numeric date and time of day as the language's
	 * translators wrote them for the admin's list tables. A language with no pack installed is written
	 * as raw() writes.
	 */
	protected function read_language_pack() {
		global $wp_locale;

		$switched = false;

		if ( determine_locale() !== $this->locale ) {
			if ( ! function_exists( 'switch_to_locale' ) || ! switch_to_locale( $this->locale ) ) {
				return;
			}

			$switched = true;
		}

		if ( $wp_locale instanceof WP_Locale ) {
			$this->separators = array(
				'thousands_sep' => (string) $wp_locale->number_format['thousands_sep'],
				'decimal_point' => (string) $wp_locale->number_format['decimal_point'],
			);
			$this->names      = $wp_locale;
		}

		// Core keeps the two formats with its admin strings, which a REST request has not loaded.
		if ( ! is_admin() && defined( 'WP_LANG_DIR' ) && is_readable( WP_LANG_DIR . '/admin-' . $this->locale . '.mo' ) ) {
			load_textdomain( 'default', WP_LANG_DIR . '/admin-' . $this->locale . '.mo' );
		}

		// phpcs:disable WordPress.WP.I18n -- core's own translations of its date and time formats, in core's text domain: the strings are core's, and nothing here belongs in the plugin's template.
		$date = (string) translate( 'Y/m/d' );
		$time = (string) translate( 'g:i a' );
		// phpcs:enable WordPress.WP.I18n

		// The plugin's times carry their seconds; core's format has none.
		if ( false === strpos( $time, 's' ) ) {
			$time = (string) preg_replace( '/(?<!\\\\)i/', 'i:s', $time, 1 );
		}

		$this->patterns = array(
			'date'     => $date,
			'time'     => $time,
			'datetime' => $date . ' ' . $time,
		);

		if ( $switched ) {
			restore_previous_locale();
		}
	}

	/**
	 * The WordPress locale of an export formatted for a language.
	 *
	 * @return string '' for raw() and screen().
	 */
	public function language() {
		return $this->locale;
	}

	/**
	 * The decimal point the numbers are written with: a comma makes the CSV export separate its values
	 * with semicolons.
	 *
	 * @return string
	 */
	public function decimal_point() {
		if ( 'raw' === $this->kind ) {
			return '.';
		}

		if ( '' !== $this->intl ) {
			$formatter = $this->number_formatter( 2, true );

			if ( null !== $formatter ) {
				return (string) $formatter->getSymbol( NumberFormatter::DECIMAL_SEPARATOR_SYMBOL );
			}
		}

		return $this->separators['decimal_point'];
	}

	/**
	 * A value of a number or decimal column. NULL counts as 0, as it always has; a value that is no
	 * number (text, an empty string) comes back as it is.
	 *
	 * @param mixed $value    The value from the row.
	 * @param int   $decimals The decimals to round to.
	 * @param bool  $grouped  Whether the thousands are separated.
	 *
	 * @return mixed The number as a string, or the value as it came when it is no number.
	 */
	public function number( $value, $decimals = 0, $grouped = true ) {
		if ( null === $value ) {
			$value = 0;
		}

		if ( ! is_numeric( $value ) ) {
			return $value;
		}

		if ( '' !== $this->intl ) {
			$formatter = $this->number_formatter( $decimals, $grouped );

			if ( null !== $formatter ) {
				// A whole number is formatted digit for digit; a float keeps about fifteen.
				$whole = is_int( $value ) || ( is_string( $value ) && (string) (int) $value === $value );
				$text  = $whole ? $formatter->format( (int) $value, NumberFormatter::TYPE_INT64 ) : $formatter->format( (float) $value );

				if ( is_string( $text ) ) {
					return $text;
				}
			}
		}

		if ( 'screen' === $this->kind && $grouped ) {
			return number_format_i18n( (float) $value, $decimals );
		}

		return number_format( (float) $value, $decimals, $this->separators['decimal_point'], $grouped ? $this->separators['thousands_sep'] : '' );
	}

	/**
	 * A value of a date, time or datetime column, as it is stored: no time zone is applied.
	 *
	 * @param mixed  $value The value from the row: a date and time MySQL or strtotime() can read.
	 * @param string $type  date, time or datetime.
	 *
	 * @return string
	 */
	public function date( $value, $type ) {
		$type      = isset( $this->patterns[ $type ] ) ? $type : 'datetime';
		$timestamp = strtotime( (string) $value );

		if ( 'locale' !== $this->kind ) {
			return (string) date_i18n( $this->patterns[ $type ], $timestamp );
		}

		// A value that is no date is left as it is: a language has no way to write it.
		if ( false === $timestamp ) {
			return (string) $value;
		}

		if ( '' !== $this->intl ) {
			$formatter = $this->date_formatter( $type );

			if ( null !== $formatter ) {
				$text = $formatter->format( $timestamp );

				if ( is_string( $text ) ) {
					// ICU sets a narrow no-break space before AM and PM; a spreadsheet reads the time with a plain one.
					return str_replace( array( "\u{202F}", "\u{00A0}" ), ' ', $text );
				}
			}
		}

		return $this->pack_date( $this->patterns[ $type ], $timestamp );
	}

	/**
	 * The moment a document was written, for the foot of a PDF page: month first with a 24-hour clock as
	 * the plugin has always stamped it, unless the format writes dates another way.
	 *
	 * @return string
	 */
	public function stamp() {
		if ( 'locale' === $this->kind || ( 'screen' === $this->kind && self::uses_site_date_formats() ) ) {
			return $this->date( current_time( 'mysql' ), 'datetime' );
		}

		return (string) date_i18n( 'm/d/Y H:i:s' );
	}

	/**
	 * intl's formatter for numbers of so many decimals: half rounds away from zero, as number_format()
	 * rounds, and the minus is the one of the keyboard, which a spreadsheet reads (ICU writes U+2212 for
	 * some languages and a direction mark before it for others).
	 *
	 * @param int  $decimals The decimals.
	 * @param bool $grouped  Whether the thousands are separated.
	 *
	 * @return NumberFormatter|null Null when intl could not set one up.
	 */
	protected function number_formatter( $decimals, $grouped ) {
		$key = (int) $decimals . ( $grouped ? 'g' : '' );

		if ( isset( $this->numbers[ $key ] ) ) {
			return $this->numbers[ $key ];
		}

		try {
			$formatter = NumberFormatter::create( $this->intl . ( $this->western_digits ? '@numbers=latn' : '' ), NumberFormatter::DECIMAL );

			$formatter->setAttribute( NumberFormatter::MIN_FRACTION_DIGITS, (int) $decimals );
			$formatter->setAttribute( NumberFormatter::MAX_FRACTION_DIGITS, (int) $decimals );
			$formatter->setAttribute( NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP );
			$formatter->setAttribute( NumberFormatter::GROUPING_USED, $grouped ? 1 : 0 );
			$formatter->setSymbol( NumberFormatter::MINUS_SIGN_SYMBOL, '-' );
		} catch ( Throwable $error ) {
			return null;
		}

		$this->numbers[ $key ] = $formatter;

		return $formatter;
	}

	/**
	 * intl's formatter for a column type: the language's short date with the year in full (many
	 * languages' short date has two digits of it), its time of day with seconds, in the Gregorian
	 * calendar.
	 *
	 * @param string $type date, time or datetime.
	 *
	 * @return IntlDateFormatter|null Null when intl could not set one up.
	 */
	protected function date_formatter( $type ) {
		if ( array_key_exists( $type, $this->dates ) ) {
			return $this->dates[ $type ];
		}

		$this->dates[ $type ] = null;

		try {
			$formatter = IntlDateFormatter::create(
				$this->intl . '@calendar=gregorian' . ( $this->western_digits ? ';numbers=latn' : '' ),
				'time' === $type ? IntlDateFormatter::NONE : IntlDateFormatter::SHORT,
				'date' === $type ? IntlDateFormatter::NONE : IntlDateFormatter::MEDIUM,
				'UTC',
				IntlDateFormatter::GREGORIAN
			);

			if ( $formatter instanceof IntlDateFormatter ) {
				$pattern = (string) $formatter->getPattern();

				$formatter->setPattern( (string) preg_replace( '/(?<!y)yy(?!y)/', 'y', $pattern ) );

				$this->dates[ $type ] = $formatter;
			}
		} catch ( Throwable $error ) {
			$this->dates[ $type ] = null;
		}

		return $this->dates[ $type ];
	}

	/**
	 * A date in a PHP date format, with the names of the language pack where the format writes a month,
	 * a weekday or AM and PM: what wp_date() does with the language WordPress runs in, done here with
	 * the language of the export.
	 *
	 * @param string $format    A PHP date format.
	 * @param int    $timestamp The moment, as strtotime() read the stored value.
	 *
	 * @return string
	 */
	protected function pack_date( $format, $timestamp ) {
		$moment = ( new DateTimeImmutable( '@' . (int) $timestamp ) )->setTimezone( new DateTimeZone( 'UTC' ) );
		$names  = $this->names;

		if ( ! $names instanceof WP_Locale ) {
			return $moment->format( $format );
		}

		$length  = strlen( $format );
		$written = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $format[ $i ];

			switch ( $char ) {
				case 'D':
					$written .= addcslashes( $names->get_weekday_abbrev( $names->get_weekday( (int) $moment->format( 'w' ) ) ), '\\A..Za..z' );
					break;
				case 'l':
					$written .= addcslashes( $names->get_weekday( (int) $moment->format( 'w' ) ), '\\A..Za..z' );
					break;
				case 'F':
					$written .= addcslashes( $names->get_month( $moment->format( 'm' ) ), '\\A..Za..z' );
					break;
				case 'M':
					$written .= addcslashes( $names->get_month_abbrev( $names->get_month( $moment->format( 'm' ) ) ), '\\A..Za..z' );
					break;
				case 'a':
				case 'A':
					$written .= addcslashes( $names->get_meridiem( $moment->format( $char ) ), '\\A..Za..z' );
					break;
				case '\\':
					$written .= $char . ( $i + 1 < $length ? $format[ ++$i ] : '' );
					break;
				default:
					$written .= $char;
			}
		}

		return $moment->format( $written );
	}
}
