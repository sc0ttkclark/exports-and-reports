<?php
/**
 * PDF export of a report on tc-lib-pdf: a title, the active filters, the column headings on every
 * page, one row per line, the sum row, and a footer with the date and the page count.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ExportsReports\Prefixed\Com\Tecnick\Pdf\Tcpdf;

/**
 * Class Exports_Reports_Admin_UI_Export_PDF
 */
class Exports_Reports_Admin_UI_Export_PDF {

	const MARGIN      = 10.0;  // mm, every side.
	const TITLE_SIZE  = 12.0;  // pt.
	const FILTER_SIZE = 8.0;
	const BODY_SIZE   = 8.0;
	const FOOTER_SIZE = 7.0;
	const LINE_H      = 4.5;   // mm, title and filter lines.
	const HEADER_H    = 6.5;   // mm, the column heading row.
	const ROW_H       = 5.5;   // mm, a data row with one line.
	const LINE_STEP   = 3.6;   // mm, each further line of a wrapped cell.
	const MAX_LINES   = 6;     // lines a cell may take; the last one ends in an ellipsis.
	const FOOTER_H    = 8.0;   // mm, reserved at the bottom.
	const PADDING     = 1.0;   // mm, inside a cell on each side.
	const LINE_WIDTH  = 0.2;
	const HEADER_FILL = 'rgb(230,230,230)';
	const ZEBRA_FILL  = 'rgb(246,246,246)';
	const FONT        = 'helvetica';
	const MIN_COL_W   = 12.0;  // mm, no column narrower than this while the page allows.
	const SAMPLE_ROWS = 500;   // rows measured to size the columns without a width.

	/**
	 * The body size and the heights derived from it for the document being rendered: 8 pt as a rule,
	 * smaller when the columns would be narrow (set at the top of render()).
	 *
	 * @var float
	 */
	protected static $body_size = self::BODY_SIZE;
	protected static $row_h     = self::ROW_H;
	protected static $line_step = self::LINE_STEP;
	protected static $header_h  = self::HEADER_H;

	/**
	 * Writes the PDF of the admin UI's current report into the export directory.
	 *
	 * @param Exports_Reports_Admin_UI $admin The admin UI, get_data( true ) already run.
	 * @return string The file name inside the export directory.
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- the method name predates 0.9.5 and site code may call it.
	public static function CreateReport( $admin ) {
		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$export_file          = $admin->export_file( 'pdf' );
		$export_file_location = EXPORTS_REPORTS_EXPORT_DIR . '/' . $export_file;

		$wp_filesystem->put_contents( $export_file_location, self::render( $admin ), FS_CHMOD_FILE );

		return $export_file;
	}

	/**
	 * The PDF document of the admin UI's current report.
	 *
	 * @param Exports_Reports_Admin_UI $admin The admin UI, get_data( true ) already run.
	 * @return string
	 */
	public static function render( $admin ) {
		$orientation = 'L' === strtoupper( (string) $admin->page_orientation ) ? 'L' : 'P';
		$columns     = self::columns( $admin );
		$rows        = self::rows( $admin, $columns );
		$title       = (string) $admin->items;
		$filters     = self::filter_lines( $admin );
		$legacy      = self::legacy_content( $admin );
		$stamp       = date_i18n( 'm/d/Y H:i:s' );
		$author      = wp_get_current_user()->display_name;

		// The metric files live in the plugin, so the document is told to trust that directory.
		$pdf = new Tcpdf( 'mm', true, false, true, '', null, array( 'allowedPaths' => array( self::font_dir() ) ) );
		$pdf->setCreator( 'Exports and Reports' );
		$pdf->setAuthor( '' !== $author ? $author : 'Exports and Reports' );
		$pdf->setTitle( $title );
		$pdf->setPDFFilename( sanitize_title( $title ) . '.pdf' );
		$pdf->setDefaultCellPadding( self::PADDING, self::PADDING, self::PADDING, self::PADDING );

		$page   = $pdf->addPage(
			array(
				'format'      => 'LETTER',
				'orientation' => $orientation,
			)
		);
		$x0     = self::MARGIN;
		$width  = $page['width'] - 2 * self::MARGIN;
		$bottom = $page['height'] - self::MARGIN - self::FOOTER_H;
		$widths = self::column_widths( $columns, $width, $rows );
		self::size_for( $width / max( 1, count( $columns ) ) );

		// The title block (first page) and the lines a callback on the deprecated exports_and_reports_pdf_content filter supplied.
		$y = self::MARGIN;
		$pdf->page->addContent( self::select( $pdf, 'B', self::TITLE_SIZE ) );
		$pdf->page->addContent( self::cell( $pdf, $title, $x0, $y, $width, self::LINE_H + 1, 'L', array() ) );
		$y += self::LINE_H + 2;

		if ( array() !== $filters || array() !== $legacy ) {
			$pdf->page->addContent( self::select( $pdf, 'I', self::FILTER_SIZE ) );
			foreach ( array_merge( $filters, $legacy ) as $line ) {
				$pdf->page->addContent( self::cell( $pdf, $line, $x0, $y, $width, self::LINE_H, 'L', array() ) );
				$y += self::LINE_H;
			}
		}
		$y += 2;

		// The heading labels wrap onto a second line when a column is narrow; the row is as tall as it needs.
		self::select( $pdf, 'B', self::$body_size );
		$heading_lines = 1;
		foreach ( $columns as $key => $column ) {
			$columns[ $key ]['lines'] = self::wrap( $pdf, $column['label'], $widths[ $key ], 2 );
			$heading_lines            = max( $heading_lines, count( $columns[ $key ]['lines'] ) );
		}
		self::$header_h = self::HEADER_H * self::$body_size / self::BODY_SIZE + ( $heading_lines - 1 ) * self::$line_step;

		// Every cell is wrapped and measured in the body font first, so the page count is known before the
		// first footer.
		self::select( $pdf, '', self::$body_size );
		$heights    = array();
		$first_page = $bottom - $y - self::$header_h;
		$other_page = $bottom - self::MARGIN - self::$header_h;

		foreach ( $rows as $index => $row ) {
			$lines = 1;

			foreach ( $columns as $key => $column ) {
				$rows[ $index ]['lines'][ $key ] = self::wrap( $pdf, $row['cells'][ $key ], $widths[ $key ] );
				$lines                           = max( $lines, count( $rows[ $index ]['lines'][ $key ] ) );
			}

			$heights[ $index ] = self::$row_h + ( $lines - 1 ) * self::$line_step;
		}

		$pages = 1;
		$used  = 0.0;

		foreach ( $heights as $height ) {
			$room = 1 === $pages ? $first_page : $other_page;

			if ( $used + $height > $room && $used > 0 ) {
				++$pages;
				$used = 0.0;
			}

			$used += $height;
		}

		$page_number = 1;
		$y           = self::heading_row( $pdf, $columns, $widths, $x0, $y );
		$pdf->page->addContent( self::select( $pdf, '', self::$body_size ) );
		$fill = false;

		foreach ( $rows as $index => $row ) {
			$height = $heights[ $index ];

			if ( $y + $height > $bottom && $y > self::MARGIN + self::$header_h ) {
				self::footer( $pdf, $page, $stamp, $page_number, $pages );
				$page = $pdf->addPage(
					array(
						'format'      => 'LETTER',
						'orientation' => $orientation,
					)
				);
				++$page_number;
				$y = self::heading_row( $pdf, $columns, $widths, $x0, self::MARGIN );
				$pdf->page->addContent( self::select( $pdf, '', self::$body_size ) );
				$fill = false;
			}

			$style = self::row_style( $fill, $row['sum'] );
			$x     = $x0;

			foreach ( $columns as $key => $column ) {
				$pdf->page->addContent( self::wrapped_cell( $pdf, $row['lines'][ $key ], $x, $y, $widths[ $key ], $height, $column['align'], $style ) );
				$x += $widths[ $key ];
			}

			$y   += $height;
			$fill = ! $fill;
		}

		$pdf->page->addContent(
			$pdf->graph->getLine(
				$x0,
				$y,
				$x0 + $width,
				$y,
				array(
					'lineWidth' => self::LINE_WIDTH,
					'lineColor' => 'black',
				)
			)
		);
		self::footer( $pdf, $page, $stamp, $page_number, $pages );

		return $pdf->getOutPDFString();
	}

	/**
	 * The displayed columns: key, label, alignment and the width weight the definition gives.
	 *
	 * @param Exports_Reports_Admin_UI $admin The admin UI.
	 * @return array<string, array{label: string, align: string, type: string, weight: float, attributes: array}>
	 */
	protected static function columns( $admin ) {
		$columns = array();

		foreach ( (array) $admin->columns as $key => $attributes ) {
			if ( ! is_array( $attributes ) ) {
				$key        = $attributes;
				$attributes = $admin->setup_column( $key );
			}

			if ( false === $attributes['display'] ) {
				continue;
			}

			$type = isset( $attributes['type'] ) ? (string) $attributes['type'] : 'text';

			$columns[ $key ] = array(
				'label'      => (string) $attributes['label'],
				'align'      => in_array( $type, array( 'number', 'decimal' ), true ) ? 'R' : 'L',
				'type'       => $type,
				'weight'     => isset( $attributes['width'] ) ? (float) preg_replace( '/[^0-9.]/', '', (string) $attributes['width'] ) : 0.0,
				'attributes' => $attributes,
			);
		}

		return $columns;
	}

	/**
	 * Column widths in mm. A column's width setting is a relative weight; a column without one is sized by
	 * its content: the longer of its label and the 90th percentile of its values (in characters, over the
	 * first rows), put on the scale of the columns that do have a weight, or used as the weight itself when
	 * none has. The weights fill the usable width, and no column is narrower than MIN_COL_W when the page
	 * allows. The TCPDF engine applies the same rule, so both engines lay a report out the same way.
	 *
	 * @param array $columns The columns.
	 * @param float $width   The usable page width.
	 * @param array $rows    The rows, for the content measure.
	 * @return array<string, float>
	 */
	protected static function column_widths( $columns, $width, $rows ) {
		$need = self::content_needs( $columns, $rows );

		$given = array_filter( array_column( $columns, 'weight' ), static fn( $weight ) => $weight > 0 );
		$scale = 1.0;

		if ( array() !== $given ) {
			$given_need = array();
			foreach ( $columns as $key => $column ) {
				if ( $column['weight'] > 0 ) {
					$given_need[] = $need[ $key ];
				}
			}
			$scale = ( array_sum( $given ) / count( $given ) ) / max( 1.0, array_sum( $given_need ) / count( $given_need ) );
		}

		$weights = array();
		foreach ( $columns as $key => $column ) {
			$weights[ $key ] = $column['weight'] > 0 ? $column['weight'] : $need[ $key ] * $scale;
		}

		$total  = max( 0.001, array_sum( $weights ) );
		$widths = array();
		foreach ( $weights as $key => $weight ) {
			$widths[ $key ] = $width * $weight / $total;
		}

		// Nothing narrower than the minimum while the page allows: the shortfall comes out of the wider columns.
		if ( count( $widths ) * self::MIN_COL_W <= $width ) {
			for ( $pass = 0; $pass < 3; $pass++ ) {
				$short = 0.0;
				$wide  = 0.0;
				foreach ( $widths as $w ) {
					if ( $w < self::MIN_COL_W ) {
						$short += self::MIN_COL_W - $w;
					} else {
						$wide += $w - self::MIN_COL_W;
					}
				}
				if ( $short <= 0.001 || $wide <= 0.001 ) {
					break;
				}
				foreach ( $widths as $key => $w ) {
					$widths[ $key ] = $w < self::MIN_COL_W ? self::MIN_COL_W : $w - $short * ( $w - self::MIN_COL_W ) / $wide;
				}
			}
		}

		return $widths;
	}

	/**
	 * Characters a column needs: the longer of its label (plus two) and the 90th percentile of its values'
	 * lengths over the first SAMPLE_ROWS rows, kept between 4 and 48.
	 *
	 * @param array $columns The columns.
	 * @param array $rows    The rows.
	 * @return array<string, float>
	 */
	protected static function content_needs( $columns, $rows ) {
		$need = array();

		foreach ( $columns as $key => $column ) {
			$lengths = array();
			foreach ( array_slice( $rows, 0, self::SAMPLE_ROWS ) as $row ) {
				$lengths[] = mb_strlen( (string) $row['cells'][ $key ] );
			}
			sort( $lengths );
			$p90          = array() === $lengths ? 0 : $lengths[ (int) floor( 0.9 * ( count( $lengths ) - 1 ) ) ];
			$need[ $key ] = (float) min( 48, max( 4, mb_strlen( $column['label'] ) + 2, $p90 ) );
		}

		return $need;
	}

	/**
	 * The body size for the average column width: 8 pt as a rule, 7 pt under 22 mm, 6.5 pt under 16 mm; the
	 * row and line heights follow.
	 *
	 * @param float $average_width mm.
	 */
	protected static function size_for( $average_width ) {
		$size = $average_width < 16 ? 6.5 : ( $average_width < 22 ? 7.0 : self::BODY_SIZE );

		self::$body_size = $size;
		self::$row_h     = self::ROW_H * $size / self::BODY_SIZE;
		self::$line_step = self::LINE_STEP * $size / self::BODY_SIZE;
		self::$header_h  = self::HEADER_H * $size / self::BODY_SIZE;
	}

	/**
	 * The rows as display text per column, the sum row last when the report has one.
	 *
	 * @param Exports_Reports_Admin_UI $admin   The admin UI.
	 * @param array                    $columns The columns.
	 * @return array<int, array{cells: array<string, string>, sum: bool}>
	 */
	protected static function rows( $admin, $columns ) {
		$rows = array();

		foreach ( (array) $admin->full_data as $item ) {
			$cells = array();

			foreach ( $columns as $key => $column ) {
				$value = isset( $item[ $key ] ) ? $item[ $key ] : '';

				if ( false !== $column['attributes']['custom_display'] && function_exists( "{$column['attributes']['custom_display']}" ) ) {
					$value = $column['attributes']['custom_display']( $value, $item, $key, $column['attributes'], $admin );
				}

				$cells[ $key ] = html_entity_decode( wp_strip_all_tags( (string) self::format_field( $column['type'], $value ) ), ENT_QUOTES, 'UTF-8' );
			}

			$rows[] = array(
				'cells' => $cells,
				'sum'   => false,
			);
		}

		if ( is_array( $admin->sum_data ) && isset( $admin->sum_data[0] ) ) {
			$cells = array();

			foreach ( $columns as $key => $column ) {
				$cells[ $key ] = isset( $admin->sum_data[0][ $key ] ) ? (string) $admin->sum_data[0][ $key ] : '';
			}

			$rows[] = array(
				'cells' => $cells,
				'sum'   => true,
			);
		}

		return $rows;
	}

	/**
	 * One line per active filter, as the report page shows them.
	 *
	 * @param Exports_Reports_Admin_UI $admin The admin UI.
	 * @return string[]
	 */
	protected static function filter_lines( $admin ) {
		$lines = array();

		foreach ( (array) $admin->filters as $filter ) {
			if ( ! isset( $admin->search_columns[ $filter ] ) ) {
				continue;
			}

			$column   = $admin->search_columns[ $filter ];
			$selected = $admin->get_var( 'filter_' . $filter, $column['filter_default'] );

			if ( 'related' === $column['type'] ) {
				$lines[] = $column['label'] . ': ' . ( empty( $selected ) || ! isset( $column['related_lookup'][ $selected ] ) ? 'All' : $column['related_lookup'][ $selected ] );
			} elseif ( in_array( $column['type'], array( 'date', 'datetime' ), true ) ) {
				$start = $admin->get_var( 'filter_' . $filter . '_start', $column['filter_default'] );
				$end   = $admin->get_var( 'filter_' . $filter . '_end', $column['filter_ongoing_default'] );

				if ( ! empty( $start ) && ! empty( $end ) ) {
					$lines[] = $column['label'] . ' from ' . $start . ' to ' . $end;
				}
			} elseif ( 'bool' === $column['type'] ) {
				$choice = 'All';

				if ( '' !== (string) $selected ) {
					$choice = $selected ? 'Yes' : 'No';
				}

				$lines[] = $column['label'] . ': ' . $choice;
			}
		}

		return $lines;
	}

	/**
	 * What a callback on the deprecated exports_and_reports_pdf_content filter supplies, as text
	 * lines; HTML in it is not rendered.
	 *
	 * @param Exports_Reports_Admin_UI $admin The admin UI.
	 * @return string[]
	 */
	protected static function legacy_content( $admin ) {
		if ( ! has_filter( 'exports_and_reports_pdf_content' ) ) {
			return array();
		}

		// A callback written for the TCPDF engine may call methods on the third argument, which this engine
		// has no object for: its failure costs the preamble, not the export.
		try {
			$output = apply_filters_deprecated( 'exports_and_reports_pdf_content', array( '', $admin, null ), '0.9.5', 'exports_reports_export_pdf_report_file', 'The PDF export no longer renders HTML; supply the finished file instead.' );
		} catch ( Throwable $e ) {
			return array();
		}

		if ( ! is_string( $output ) || '' === trim( $output ) ) {
			return array();
		}

		$text = html_entity_decode( wp_strip_all_tags( $output, true ), ENT_QUOTES, 'UTF-8' );

		return array_values( array_filter( array_map( 'trim', explode( "\n", $text ) ), static fn( $line ) => '' !== $line ) );
	}

	/**
	 * The column heading row.
	 *
	 * @param Tcpdf $pdf     The document.
	 * @param array $columns The columns.
	 * @param array $widths  Column widths.
	 * @param float $x0      Left edge.
	 * @param float $y       Top edge.
	 * @return float The y below the row.
	 */
	protected static function heading_row( $pdf, $columns, $widths, $x0, $y ) {
		$style = array(
			'all' => array(
				'lineWidth' => self::LINE_WIDTH,
				'lineColor' => 'black',
				'fillColor' => self::HEADER_FILL,
			),
		);

		$pdf->page->addContent( self::select( $pdf, 'B', self::$body_size ) );
		$x = $x0;

		foreach ( $columns as $key => $column ) {
			$lines = isset( $column['lines'] ) ? $column['lines'] : self::wrap( $pdf, $column['label'], $widths[ $key ], 2 );
			$pdf->page->addContent( self::wrapped_cell( $pdf, $lines, $x, $y, $widths[ $key ], self::$header_h, 'C', $style ) );
			$x += $widths[ $key ];
		}

		return $y + self::$header_h;
	}

	/**
	 * The footer: the date on the right, the page count on the left, a rule above.
	 *
	 * @param Tcpdf  $pdf    The document.
	 * @param array  $page   The page (addPage() result).
	 * @param string $stamp  The date.
	 * @param int    $number This page's number.
	 * @param int    $pages  The page count.
	 */
	protected static function footer( $pdf, $page, $stamp, $number, $pages ) {
		$x0    = self::MARGIN;
		$width = $page['width'] - 2 * self::MARGIN;
		$y     = $page['height'] - self::MARGIN - self::FOOTER_H + 2;

		$pdf->page->addContent(
			$pdf->graph->getLine(
				$x0,
				$y,
				$x0 + $width,
				$y,
				array(
					'lineWidth' => self::LINE_WIDTH,
					'lineColor' => 'black',
				)
			)
		);
		$pdf->page->addContent( self::select( $pdf, '', self::FOOTER_SIZE ) );
		$pdf->page->addContent( self::cell( $pdf, sprintf( 'Page %d of %d', $number, $pages ), $x0, $y + 1, $width / 2, self::LINE_H, 'L', array() ) );
		$pdf->page->addContent( self::cell( $pdf, $stamp, $x0 + $width / 2, $y + 1, $width / 2, self::LINE_H, 'R', array() ) );
	}

	/**
	 * Zebra row style: a fill on alternate rows, rules left and right, a heavier top rule on the sum row.
	 *
	 * @param bool $fill Whether this row is shaded.
	 * @param bool $sum  Whether this is the sum row.
	 * @return array
	 */
	protected static function row_style( $fill, $sum ) {
		$rule  = array(
			'lineWidth' => self::LINE_WIDTH,
			'lineColor' => 'black',
		);
		$style = array(
			'all' => array( 'lineWidth' => 0 ),
			'L'   => $rule,
			'R'   => $rule,
		);

		if ( $sum ) {
			$style['T'] = array_merge( $rule, array( 'lineWidth' => self::LINE_WIDTH * 2 ) );
		} elseif ( $fill ) {
			$style['all']['fillColor'] = self::ZEBRA_FILL;
		}

		return $style;
	}

	/**
	 * One text cell on a single line; text that does not fit is cut to the width with an ellipsis.
	 *
	 * @param Tcpdf  $pdf    The document.
	 * @param string $text   Cell text.
	 * @param float  $x      Left, mm.
	 * @param float  $y      Top, mm.
	 * @param float  $w      Width, mm.
	 * @param float  $h      Height, mm.
	 * @param string $halign L, C or R.
	 * @param array  $styles Border and fill styles.
	 * @return string PDF content.
	 */
	protected static function cell( $pdf, $text, $x, $y, $w, $h, $halign, array $styles ) {
		$lines = self::wrap( $pdf, $text, $w, 1 );

		return $pdf->getTextCell( $lines[0], $x, $y, $w, $h, 0, 0, 'C', $halign, null, $styles );
	}

	/**
	 * A cell of several lines: the box with its borders and fill, then one line of text per row.
	 *
	 * @param Tcpdf    $pdf    The document.
	 * @param string[] $lines  The wrapped lines.
	 * @param float    $x      Left, mm.
	 * @param float    $y      Top, mm.
	 * @param float    $w      Width, mm.
	 * @param float    $h      Height, mm.
	 * @param string   $halign L, C or R.
	 * @param array    $styles Border and fill styles.
	 * @return string PDF content.
	 */
	protected static function wrapped_cell( $pdf, $lines, $x, $y, $w, $h, $halign, array $styles ) {
		$out = $pdf->getTextCell( ' ', $x, $y, $w, $h, 0, 0, 'C', 'L', null, $styles );

		if ( 1 === count( $lines ) ) {
			return $out . $pdf->getTextCell( $lines[0], $x, $y, $w, $h, 0, 0, 'C', $halign, null, array(), drawcell: false );
		}

		// The lines sit in the middle of the cell: a two-line heading in a taller heading row, wrapped rows from
		// the first line down.
		$block = count( $lines ) * self::$line_step;
		$top   = $y + max( ( self::$row_h - self::$line_step ) / 2, ( $h - $block ) / 2 );

		foreach ( $lines as $index => $line ) {
			$out .= $pdf->getTextCell( $line, $x, $top + $index * self::$line_step, $w, self::$line_step, 0, 0, 'C', $halign, null, array(), drawcell: false );
		}

		return $out;
	}

	/**
	 * Wraps text at the words that fit the inner width of a cell, in the current font; a text that
	 * needs more than the line limit ends in an ellipsis on the last line. Never returns an empty
	 * list: getTextCell() draws nothing for an empty string, the box included.
	 *
	 * @param Tcpdf  $pdf   The document.
	 * @param string $text  The text.
	 * @param float  $w     Cell width, mm.
	 * @param int    $limit Lines at most.
	 * @return string[]
	 */
	protected static function wrap( $pdf, $text, $w, $limit = self::MAX_LINES ) {
		$text  = trim( str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', (string) $text ) );
		$inner = $pdf->toPoints( max( 0.1, $w - 2 * self::PADDING ) );

		if ( '' === $text ) {
			return array( ' ' );
		}

		$width = static fn( $text ) => $pdf->font->getOrdArrWidth( $pdf->uniconv->strToOrdArr( $text ) );
		$lines = array();
		$line  = '';

		foreach ( preg_split( '/\s+/', $text ) as $word ) {
			$candidate = '' === $line ? $word : $line . ' ' . $word;

			if ( $width( $candidate ) <= $inner || '' === $line ) {
				$line = $candidate;
				continue;
			}

			$lines[] = $line;
			$line    = $word;
		}

		$lines[] = $line;

		if ( count( $lines ) > $limit ) {
			$lines = array_slice( $lines, 0, $limit );
			$last  = $lines[ $limit - 1 ] . "\xE2\x80\xA6";

			while ( $width( $last ) > $inner && mb_strlen( $last ) > 2 ) {
				$last = mb_substr( $last, 0, -2 ) . "\xE2\x80\xA6";
			}

			$lines[ $limit - 1 ] = $last;
		}

		// A single word wider than the cell is cut with an ellipsis too.
		foreach ( $lines as $index => $value ) {
			while ( $width( $value ) > $inner && mb_strlen( $value ) > 2 ) {
				$value = mb_substr( (string) preg_replace( '/\x{2026}$/u', '', $value ), 0, -1 ) . "\xE2\x80\xA6";
			}

			$lines[ $index ] = $value;
		}

		return $lines;
	}

	/**
	 * Makes a style and size of the font the current one, for measuring and for the content stream.
	 *
	 * @param Tcpdf  $pdf   The document.
	 * @param string $style '', 'B', 'I' or 'BI'.
	 * @param float  $size  Points.
	 * @return string The content that switches the page to the font.
	 */
	protected static function select( $pdf, $style, $size ) {
		$font = $pdf->font->insert( $pdf->pon, self::FONT, $style, $size, null, null, self::font_file( $style ) );

		return $font['out'];
	}

	/**
	 * The metric file of a style of the font, shipped in assets/fonts/core.
	 *
	 * @param string $style '', 'B', 'I' or 'BI'.
	 * @return string
	 */
	protected static function font_file( $style ) {
		return self::font_dir() . '/' . self::FONT . strtolower( $style ) . '.json';
	}

	/**
	 * The directory of the shipped metric files.
	 *
	 * @return string
	 */
	protected static function font_dir() {
		return dirname( __DIR__, 2 ) . '/assets/fonts/core';
	}

	/**
	 * @param string $type     The column type.
	 * @param mixed  $raw_data The value.
	 *
	 * @return int|string
	 */
	public static function format_field( $type, $raw_data ) {
		if ( 'date' === $type ) {
			return date_i18n( 'Y/m/d', strtotime( (string) $raw_data ) );
		} elseif ( 'time' === $type ) {
			return date_i18n( 'g:i:s A', strtotime( (string) $raw_data ) );
		} elseif ( 'datetime' === $type ) {
			return date_i18n( 'Y/m/d g:i:s A', strtotime( (string) $raw_data ) );
		} elseif ( 'bool' === $type ) {
			return ( 1 === (int) $raw_data ? 'Yes' : 'No' );
		} elseif ( 'number' === $type ) {
			return (int) $raw_data;
		} elseif ( 'decimal' === $type ) {
			return number_format( (float) $raw_data, 2 );
		}

		return $raw_data;
	}
}
