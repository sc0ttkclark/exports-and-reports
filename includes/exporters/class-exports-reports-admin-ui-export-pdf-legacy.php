<?php
/**
 * The PDF export on TCPDF: the engine below PHP 8.2, where tc-lib-pdf (the other engine) cannot load.
 *
 * TCPDF renders the report as an HTML table; the output matches what the plugin produced before 0.9.5.
 *
 * @package Exports_And_Reports
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- TCPDF's own property names.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Exports_Reports_Admin_UI_Export_PDF_Legacy
 */
class Exports_Reports_Admin_UI_Export_PDF_Legacy extends ExportsReports__Prefixed__TCPDF {

	/**
	 * The admin UI instance the report comes from.
	 *
	 * @var Exports_Reports_Admin_UI|null
	 */
	public $admin = null;

	/**
	 * A document for the admin UI instance.
	 *
	 * @param Exports_Reports_Admin_UI $admin       The report.
	 * @param string                   $orientation P or L.
	 *
	 * @return self
	 */
	public static function create( Exports_Reports_Admin_UI $admin, $orientation = 'P' ) {
		// TCPDF 6.11 raises a deprecation notice per process otherwise; the plugin chose this engine for PHP below 8.2.
		if ( ! defined( 'TCPDF_SILENCE_DEPRECATION' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- TCPDF's own switch for its deprecation notice.
			define( 'TCPDF_SILENCE_DEPRECATION', true );
		}

		$obj = new self( $orientation, 'mm', 'LETTER', true, 'UTF-8', false, false );

		$obj->admin = $admin;

		return $obj;
	}

	/**
	 * The page header: the report name and the active filters, with a rule under them (TCPDF's own header without the logo).
	 */
	public function Header() {
		if ( false === $this->header_xobjid ) {
			$this->header_xobjid = $this->startTemplate( $this->w, $this->tMargin );
			$headerfont          = $this->getHeaderFont();
			$headerdata          = $this->getHeaderData();
			$this->y             = $this->header_margin;
			$this->x             = $this->rtl ? $this->w - $this->original_rMargin : $this->original_lMargin;

			$cell_height = $this->getCellHeight( $headerfont[2] / $this->k );
			$header_x    = $this->getRTL() ? $this->original_rMargin : $this->original_lMargin;
			$cw          = $this->w - $this->original_lMargin - $this->original_rMargin;

			$this->SetTextColorArray( $this->header_text_color );

			$this->SetFont( $headerfont[0], 'B', $headerfont[2] + 1 );
			$this->SetX( $header_x );
			$this->Cell( $cw, $cell_height, $headerdata['title'], 0, 1, '', false, '', 0 );

			$this->SetFont( $headerfont[0], 'i', $headerfont[2] );
			$this->SetX( $header_x );
			$this->MultiCell( $cw, $cell_height, $headerdata['string'], 0, '', false, 1, null, null, true, 0, false, true, 0, 'T', false );

			$this->SetLineStyle(
				array(
					'width' => 0.85 / $this->k,
					'cap'   => 'butt',
					'join'  => 'miter',
					'dash'  => 0,
					'color' => $headerdata['line_color'],
				)
			);
			$this->SetY( ( 2.835 / $this->k ) + $this->y );
			$this->SetX( $this->rtl ? $this->original_rMargin : $this->original_lMargin );
			$this->Cell( ( $this->w - $this->original_lMargin - $this->original_rMargin ), 0, '', 'T', 0, 'C' );
			$this->endTemplate();
		}

		$dx = 0;
		if ( ! $this->header_xobj_autoreset && $this->booklet && ( 0 === ( $this->page % 2 ) ) ) {
			$dx = ( $this->original_lMargin - $this->original_rMargin );
		}
		$x = $this->rtl ? $this->w + $dx : $dx;

		$this->printTemplate( $this->header_xobjid, $x, 0, 0, 0, '', '', false );

		if ( $this->header_xobj_autoreset ) {
			$this->resetHeaderTemplate();
		}
	}

	/**
	 * The page footer: the date on the right, "Page N / M" on the left, over a rule.
	 */
	public function Footer() {
		$cur_y = $this->y;
		$this->SetTextColorArray( $this->footer_text_color );
		$this->SetLineStyle(
			array(
				'width' => 0.85 / $this->k,
				'cap'   => 'butt',
				'join'  => 'miter',
				'dash'  => 0,
				'color' => $this->footer_line_color,
			)
		);

		$w_page = isset( $this->l['w_page'] ) ? $this->l['w_page'] . ' ' : '';
		if ( empty( $this->pagegroups ) ) {
			$pagenumtxt = $w_page . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
		} else {
			$pagenumtxt = $w_page . $this->getPageNumGroupAlias() . ' / ' . $this->getPageGroupAlias();
		}
		$this->SetY( $cur_y );

		$headerfont = $this->getHeaderFont();
		$this->SetFont( $headerfont[0], $headerfont[1], 8 );
		$this->Cell( 0, 0, date_i18n( 'm/d/Y H:i:s' ), 0, 0, 'R' );
		$this->SetX( $this->original_lMargin );
		$this->Cell( 0, 0, 'Page ' . $pagenumtxt, 'T', 1, 'L' );
	}

	/**
	 * Renders the report into the export directory.
	 *
	 * @param Exports_Reports_Admin_UI $admin The report, with full_data loaded.
	 *
	 * @return string The file name.
	 */
	public static function CreateReport( $admin ) {
		$page_orientation = ! empty( $admin->page_orientation ) ? $admin->page_orientation : 'P';
		$pdf              = self::create( $admin, $page_orientation );

		$filter_output = array();
		foreach ( $admin->filters as $filter ) {
			if ( ! isset( $admin->search_columns[ $filter ] ) ) {
				continue;
			}

			$filter_column = $admin->search_columns[ $filter ];
			$selected_id   = $admin->get_var( 'filter_' . $filter, $filter_column['filter_default'] );

			if ( 'related' === $filter_column['type'] ) {
				$filter_label    = empty( $selected_id ) || ! isset( $filter_column['related_lookup'][ $selected_id ] ) ? 'All' : $filter_column['related_lookup'][ $selected_id ];
				$filter_output[] = $filter_column['label'] . ': ' . $filter_label;
			} elseif ( in_array( $filter_column['type'], array( 'date', 'datetime' ), true ) ) {
				$start = $admin->get_var( 'filter_' . $filter . '_start', $filter_column['filter_default'] );
				$end   = $admin->get_var( 'filter_' . $filter . '_end', $filter_column['filter_ongoing_default'] );

				if ( ! empty( $start ) && ! empty( $end ) ) {
					$filter_output[] = $filter_column['label'] . ' from ' . $start . ' to ' . $end;
				}
			} elseif ( 'bool' === $filter_column['type'] ) {
				if ( '' === $selected_id ) {
					$filter_display_value = 'All';
				} else {
					$filter_display_value = $selected_id ? 'Yes' : 'No';
				}

				$filter_output[] = $filter_column['label'] . ': ' . $filter_display_value;
			}
		}

		$pdf->SetTitle( $admin->items );
		$pdf->SetAuthor( wp_get_current_user()->display_name );
		$pdf->setHeaderData( '', 0, $admin->items, implode( "\n", $filter_output ) );
		$pdf->setHeaderFont( array( 'helvetica', '', 10 ) );
		$pdf->setFooterFont( array( 'helvetica', '', 8 ) );
		$pdf->SetDefaultMonospacedFont( 'courier' );
		$pdf->SetMargins( 8, 27, 8 );
		$pdf->setHeaderMargin( 5 );
		$pdf->setFooterMargin( 10 );
		$pdf->SetAutoPageBreak( true, 25 );
		$pdf->setImageScale( 1.25 );
		$pdf->SetFont( 'helvetica', '', count( self::shown_columns( $admin ) ) > 8 ? 7 : 8 );
		$pdf->AddPage();

		$output = apply_filters_deprecated( 'exports_and_reports_pdf_content', array( '', $admin, $pdf ), '0.9.5', 'exports_reports_export_pdf_report_file', 'Supply the finished file instead; this filter is only applied by the TCPDF engine.' );
		if ( empty( $output ) ) {
			$output = self::content( $admin );
		}

		$pdf->writeHTML( $output );

		$export_file          = $admin->export_file( 'pdf' );
		$export_file_location = EXPORTS_REPORTS_EXPORT_DIR . '/' . $export_file;

		$pdf->Output( $export_file_location, 'F' );

		return $export_file;
	}

	/**
	 * The report as an HTML table for TCPDF.
	 *
	 * @param Exports_Reports_Admin_UI $admin The report, with full_data loaded.
	 *
	 * @return string
	 */
	protected static function content( $admin ) {
		$columns = self::shown_columns( $admin );

		if ( array() === $columns ) {
			return '<p>' . esc_html__( 'This report has no columns to show.', 'exports-and-reports' ) . '</p>';
		}

		$cells   = self::cells( $admin, $columns );
		$widths  = self::column_widths( $admin, $columns, $cells );
		$numeric = array( 'number', 'decimal' );
		ob_start();
		?>
		<table border="1" cellpadding="3" cellspacing="0" style="table-layout: fixed; border-color: #BBBBBB;">
			<thead>
			<tr>
				<?php foreach ( $columns as $column => $attributes ) { ?>
					<th width="<?php echo esc_attr( $widths[ $column ] ); ?>%" align="center" bgcolor="#E6E6E6"><strong><?php echo wp_kses_post( $attributes['label'] ); ?></strong></th>
				<?php } ?>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $cells as $row_index => $row_cells ) { ?>
				<tr<?php echo 1 === $row_index % 2 ? ' bgcolor="#F6F6F6"' : ''; ?>>
					<?php foreach ( $columns as $column => $attributes ) { ?>
						<td width="<?php echo esc_attr( $widths[ $column ] ); ?>%" align="<?php echo in_array( $attributes['type'], $numeric, true ) ? 'right' : 'left'; ?>"><?php echo wp_kses_post( $row_cells[ $column ] ); ?></td>
					<?php } ?>
				</tr>
			<?php } ?>
			<?php if ( ! empty( $admin->sum_data ) ) { ?>
				<tr>
					<?php foreach ( $columns as $column => $attributes ) { ?>
						<td width="<?php echo esc_attr( $widths[ $column ] ); ?>%" align="<?php echo in_array( $attributes['type'], $numeric, true ) ? 'right' : 'left'; ?>"><strong><?php echo isset( $admin->sum_data[0][ $column ] ) ? esc_html( $admin->sum_data[0][ $column ] ) : ''; ?></strong></td>
					<?php } ?>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The columns the report shows, with their attributes.
	 *
	 * @param Exports_Reports_Admin_UI $admin The report.
	 *
	 * @return array<string, array>
	 */
	protected static function shown_columns( $admin ) {
		$columns = array();

		foreach ( (array) $admin->columns as $column => $attributes ) {
			if ( is_array( $attributes ) && false !== $attributes['display'] ) {
				$columns[ $column ] = $attributes;
			}
		}

		return $columns;
	}

	/**
	 * The text of every cell: the display callback's output, formatted for the column type, as the
	 * tc-lib-pdf engine shows it; the widths are measured on the same text.
	 *
	 * @param Exports_Reports_Admin_UI $admin   The report, with full_data loaded.
	 * @param array<string, array>     $columns The shown columns.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected static function cells( $admin, $columns ) {
		$cells = array();

		foreach ( (array) $admin->full_data as $row_data ) {
			$row_data = (array) $row_data;
			$row      = array();

			foreach ( $columns as $column => $attributes ) {
				$value = isset( $row_data[ $column ] ) ? $row_data[ $column ] : '';

				if ( false !== $attributes['custom_display'] && function_exists( "{$attributes['custom_display']}" ) ) {
					$value               = $attributes['custom_display']( $value, $row_data, $column, $attributes, $admin );
					$row_data[ $column ] = $value;
				}

				$row[ $column ] = (string) self::format_field( $attributes['type'], $value );
			}

			$cells[] = $row;
		}

		return $cells;
	}

	/**
	 * The displayed columns' widths as percentages of the table, by the rule the tc-lib-pdf engine uses: a
	 * column's width setting is a relative weight; a column without one is sized by its content, the longer
	 * of its label and the 90th percentile of its values (in characters, over the first 500 rows, kept
	 * between 4 and 48), put on the scale of the columns that have a weight, or used as the weight when none
	 * has; the weights are scaled to add up to 100.
	 *
	 * @param Exports_Reports_Admin_UI $admin   The report, with full_data loaded.
	 * @param array<string, array>     $columns The shown columns.
	 * @param array<int, array>        $cells   The cell text, by row and column.
	 *
	 * @return array<string, string> Column key => percentage with two decimals.
	 */
	protected static function column_widths( $admin, $columns, $cells ) {
		$given = array();
		$need  = array();
		$rows  = array_slice( $cells, 0, 500 );

		foreach ( $columns as $column => $attributes ) {
			$weight = ! empty( $attributes['width'] ) ? (float) preg_replace( '/[^0-9.]/', '', (string) $attributes['width'] ) : 0.0;
			if ( $weight > 0 ) {
				$given[ $column ] = $weight;
			}
			$lengths = array();
			foreach ( $rows as $row ) {
				$lengths[] = mb_strlen( wp_strip_all_tags( isset( $row[ $column ] ) ? $row[ $column ] : '' ) );
			}
			sort( $lengths );
			$p90             = array() === $lengths ? 0 : $lengths[ (int) floor( 0.9 * ( count( $lengths ) - 1 ) ) ];
			$need[ $column ] = (float) min( 48, max( 4, mb_strlen( (string) $attributes['label'] ) + 2, $p90 ) );
		}

		$scale = 1.0;
		if ( array() !== $given ) {
			$given_need = array_intersect_key( $need, $given );
			$scale      = ( array_sum( $given ) / count( $given ) ) / max( 1.0, array_sum( $given_need ) / count( $given_need ) );
		}

		$weights = array();
		foreach ( $need as $column => $chars ) {
			$weights[ $column ] = isset( $given[ $column ] ) ? $given[ $column ] : $chars * $scale;
		}

		$total  = array_sum( $weights );
		$widths = array();
		foreach ( $weights as $column => $weight ) {
			$widths[ $column ] = number_format( 0 < $total ? 100 * $weight / $total : 0, 2, '.', '' );
		}

		return $widths;
	}

	/**
	 * A value formatted for its column type.
	 *
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

		return is_scalar( $raw_data ) ? (string) $raw_data : '';
	}
}
