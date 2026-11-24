<?php
/**
 * The heading of a screen: the title and, when the screen is not the list, the way back to it.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 *
 * @var Exports_Reports_Admin_UI $this  The screen.
 * @var string                   $screen_title The title.
 * @var bool                     $back  Whether to link back to the list.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2>
	<?php echo esc_html( $screen_title ); ?>
	<?php if ( $back ) { ?>
		<small>(<a href="
		<?php
		echo esc_url(
			$this->var_update(
				array(
					'action' => 'manage',
					'id'     => '',
				)
			)
		);
		?>
							">&laquo; <?php esc_html_e( 'Back to Manage', 'exports-and-reports' ); ?></a>)</small>
	<?php } ?>
</h2>
