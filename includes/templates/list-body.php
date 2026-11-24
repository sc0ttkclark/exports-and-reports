<?php
/**
 * The rows of the list screen: the table, or the note that a search comes first on a report that shows
 * nothing until it is filtered. Included by manage.php where the rows belong.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 *
 * @var Exports_Reports_Admin_UI $this    The screen.
 * @var int                      $reorder 1 on the reorder screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $this->data ) && false !== $this->default_none && false === $this->search_query ) {
	?>
	<p>
		<?php
		if ( $this->export ) {
			esc_html_e( 'Please use the search filter(s) above to display data, or click on an Export to download a full copy of the data.', 'exports-and-reports' );
		} else {
			esc_html_e( 'Please use the search filter(s) above to display data.', 'exports-and-reports' );
		}
		?>
	</p>
	<?php
} else {
	$this->table( $reorder );
}
