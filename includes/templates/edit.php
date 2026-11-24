<?php
/**
 * The add, edit and duplicate screens: the heading and the form.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 *
 * @var Exports_Reports_Admin_UI $this      The screen.
 * @var string                   $screen_title     The heading.
 * @var int                      $create    1 when the form adds a row.
 * @var int                      $duplicate 1 when the form duplicates the row.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a method of Exports_Reports_Admin_UI: the variables are local to that call, not globals.

$back = true;
?>
<div class="wrap wp-admin-ui-wrap">
	<?php
	require __DIR__ . '/heading.php';
	$this->form( $create, $duplicate );
	?>
</div>
