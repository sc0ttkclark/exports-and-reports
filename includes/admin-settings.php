<?php
/**
 * The Settings page.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reset settings for plugin: every export format back on, the row count cache back to its default, the
 * dates back to the plugin's own format, the token taken in the address again as well as in the header,
 * and a new API token.
 *
 * @since 0.9.4
 * @since 1.2.0 Returns the new token.
 *
 * @return string The new API token, for the page to show once.
 */
function exports_reports_reset() {
	update_option( 'exports_reports_version', EXPORTS_REPORTS_VERSION );
	delete_option( 'exports_reports_export_formats_off' );
	delete_option( 'exports_reports_export_formats' );
	delete_option( 'exports_reports_count_cache' );
	delete_option( 'exports_reports_token_header_only' );
	delete_option( 'exports_reports_site_date_formats' );
	exports_reports_forget_counts();

	return exports_reports_generate_token();
}

/**
 * Output the plugin settings page.
 */
function exports_reports_settings() {
	if ( ! exports_reports_current_user_can_manage() ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to manage the Exports and Reports settings.', 'exports-and-reports' ), 403 );
	}

	$cleared   = false;
	$reset     = false;
	$forgotten = false;
	$saved     = false;
	$new_token = '';
	$bad_token = '';

	if ( isset( $_POST['cronjob_token'] ) || ! empty( $_POST['generate_token'] ) || ! empty( $_POST['disable_token'] ) || isset( $_POST['token_header_set'] ) || isset( $_POST['export_formats_set'] ) || isset( $_POST['date_formats_set'] ) || isset( $_POST['count_cache'] ) || ! empty( $_POST['count_cache_forget'] ) || ! empty( $_POST['clear'] ) || ! empty( $_POST['reset'] ) ) {
		check_admin_referer( 'exports-reports-settings' );

		$saved = true;

		if ( isset( $_POST['count_cache'] ) ) {
			update_option( 'exports_reports_count_cache', absint( wp_unslash( $_POST['count_cache'] ) ) );
		}

		if ( ! empty( $_POST['count_cache_forget'] ) ) {
			exports_reports_forget_counts();
			$forgotten = true;
		}

		// The token is kept as a hash, so the page shows it once, now, and the field for one of the
		// administrator's own is otherwise empty: saved empty, it changes nothing.
		if ( ! empty( $_POST['generate_token'] ) ) {
			$new_token = exports_reports_generate_token();
		} elseif ( ! empty( $_POST['disable_token'] ) ) {
			exports_reports_set_token( '' );
		} elseif ( isset( $_POST['cronjob_token'] ) && is_string( $_POST['cronjob_token'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- a secret, stored as typed or refused: exports_reports_token_problem() compares it with its sanitized form, and nothing rewrites it into another token.
			$own_token = trim( wp_unslash( $_POST['cronjob_token'] ) );

			if ( '' !== $own_token ) {
				$bad_token = exports_reports_token_problem( $own_token );

				if ( '' === $bad_token ) {
					exports_reports_set_token( $own_token );
					$new_token = $own_token;
				}
			}
		}

		if ( isset( $_POST['token_header_set'] ) ) {
			update_option( 'exports_reports_token_header_only', empty( $_POST['token_header_only'] ) ? 0 : 1 );
		}

		if ( isset( $_POST['date_formats_set'] ) ) {
			update_option( 'exports_reports_site_date_formats', empty( $_POST['site_date_formats'] ) ? 0 : 1 );
		}

		if ( isset( $_POST['export_formats_set'] ) ) {
			// The boxes left unticked are the formats turned off; none ticked turns every export button off.
			$chosen = isset( $_POST['export_formats'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['export_formats'] ) ) : array();

			update_option( 'exports_reports_export_formats_off', array_values( array_diff( array_keys( exports_reports_export_formats() ), $chosen ) ) );
		}

		if ( ! empty( $_POST['clear'] ) || ! empty( $_POST['reset'] ) ) {
			exports_reports_cleanup( true );
			$cleared = true;
		}

		if ( ! empty( $_POST['reset'] ) ) {
			$new_token = exports_reports_reset();
			$reset     = true;
		}
	}

	// Whether the web server serves the export directory: as found within the hour, or now when the form was saved.
	$dir_status   = exports_reports_export_dir_status( $saved );
	$dir_statuses = array(
		'protected' => __( 'Checked: a request for a file in the directory by its address was refused.', 'exports-and-reports' ),
		'served'    => __( 'Checked: a file in the directory was served to a request for its address.', 'exports-and-reports' ),
		'outside'   => __( 'The directory is outside the web root, where the web server cannot serve it.', 'exports-and-reports' ),
		'missing'   => __( 'The directory is created with the first export and checked then.', 'exports-and-reports' ),
		'unknown'   => __( 'The plugin could not check whether the directory is served: the file for the check could not be written, or the site could not reach itself.', 'exports-and-reports' ),
	);
	$rest_base    = rest_url( 'exports-reports/v1/reports/YOUR_REPORT_ID' );
	$token_on     = '' !== exports_reports_token_hash();
	$token        = '' !== $new_token ? $new_token : 'YOUR_TOKEN'; // In the example command.
	$header_only  = (bool) get_option( 'exports_reports_token_header_only', false );
	$site_formats = exports_reports_site_export_formats();
	$site_dates   = (bool) get_option( 'exports_reports_site_date_formats', false );
	$date_example = gmmktime( 14, 5, 9, 3, 7, (int) gmdate( 'Y' ) ); // The afternoon of the 7th of March: a day and a month that show their order, and a time that shows the clock.
	$count_cache  = (int) get_option( 'exports_reports_count_cache', 5 );
	$count_caches = array(
		0  => __( 'Off', 'exports-and-reports' ),
		1  => __( '1 minute', 'exports-and-reports' ),
		5  => __( '5 minutes (the default)', 'exports-and-reports' ),
		15 => __( '15 minutes', 'exports-and-reports' ),
		60 => __( '1 hour', 'exports-and-reports' ),
	);
	if ( ! isset( $count_caches[ $count_cache ] ) ) {
		/* translators: %s: a number of minutes. */
		$count_caches[ $count_cache ] = sprintf( _n( '%s minute', '%s minutes', $count_cache, 'exports-and-reports' ), number_format_i18n( $count_cache ) );
		ksort( $count_caches );
	}

	wp_enqueue_style( 'exports-reports-admin' );
	wp_enqueue_script( 'exports-reports-admin' ); // The Copy button of the token notice.
	?>
	<div class="wrap exports-reports-settings">
		<h2><?php esc_html_e( 'Exports and Reports - Settings', 'exports-and-reports' ); ?></h2>

		<?php if ( $cleared ) : ?>
			<div id="message" class="updated fade">
				<p><?php esc_html_e( 'Your Exports directory has been cleaned up and all export files have been removed.', 'exports-and-reports' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $reset ) : ?>
			<div id="message" class="updated fade">
				<p><?php esc_html_e( 'Your Settings have been reset.', 'exports-and-reports' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $forgotten ) : ?>
			<div id="message" class="updated fade">
				<p><?php esc_html_e( 'The kept row counts are forgotten; the next page view of each report counts again.', 'exports-and-reports' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( 'served' === $dir_status ) : ?>
			<div class="notice notice-error inline exports-reports-dir-served">
				<p>
					<strong><?php esc_html_e( 'The web server serves the export directory.', 'exports-and-reports' ); ?></strong>
					<?php esc_html_e( 'A file in it was served to a request for its address, without the plugin\'s access check: until an export is removed, anyone who has or guesses its address can download it.', 'exports-and-reports' ); ?>
				</p>
				<p><?php echo wp_kses( exports_reports_export_dir_advice(), array( 'code' => array() ) ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $bad_token ) : ?>
			<div class="notice notice-error inline exports-reports-token-refused">
				<p><?php echo esc_html( $bad_token ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $new_token ) : ?>
			<div class="notice notice-success exports-reports-token-notice">
				<p>
					<strong><?php esc_html_e( 'Your API token:', 'exports-and-reports' ); ?></strong>
					<code><?php echo esc_html( $new_token ); ?></code>
					<button type="button" class="button button-small exports-reports-copy" data-copy="<?php echo esc_attr( $new_token ); ?>" data-copied="<?php esc_attr_e( 'Copied', 'exports-and-reports' ); ?>"><?php esc_html_e( 'Copy', 'exports-and-reports' ); ?></button>
				</p>
				<p><?php esc_html_e( 'Copy it now and put it in your scripts and cronjobs: the plugin keeps only its hash, like a password, and cannot show it again.', 'exports-and-reports' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'exports-reports-settings' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="clear"><?php esc_html_e( 'Clear Exports Directory', 'exports-and-reports' ); ?></label></th>
					<td>
						<input name="clear" type="submit" id="clear" class="button" value="<?php esc_attr_e( 'Clear Now', 'exports-and-reports' ); ?>" />
						<p class="description">
							<?php
							/* translators: %s: the export directory, relative to the WordPress directory. */
							echo esc_html( sprintf( __( 'This will remove all files from your Exports directory - %s', 'exports-and-reports' ), str_replace( ABSPATH, '', EXPORTS_REPORTS_EXPORT_DIR ) ) );
							?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Export Formats', 'exports-and-reports' ); ?></th>
					<td>
						<fieldset class="er-choices exports-reports-formats">
							<input type="hidden" name="export_formats_set" value="1" />
							<?php foreach ( exports_reports_export_formats() as $type => $label ) : ?>
								<label><input type="checkbox" name="export_formats[]" value="<?php echo esc_attr( $type ); ?>"<?php checked( in_array( $type, $site_formats, true ) ); ?> /> <?php echo esc_html( $label ); ?></label>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'The formats the export buttons offer on every report; a report can narrow them further in its own settings. CSV, TSV and TXT are written row by row and suit very large reports; the other formats hold the whole report in memory while they are written.', 'exports-and-reports' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Dates', 'exports-and-reports' ); ?></th>
					<td>
						<input type="hidden" name="date_formats_set" value="1" />
						<label><input type="checkbox" name="site_date_formats" value="1"<?php checked( $site_dates ); ?> /> <?php esc_html_e( 'Use the site\'s date and time formats', 'exports-and-reports' ); ?></label>
						<p class="description">
							<?php
							echo wp_kses(
								sprintf(
									/* translators: 1: a date as the plugin writes it, 2: the same date in the site's formats. */
									esc_html__( 'On the report pages and in PDF exports, date, time and date-and-time columns are written like %1$s. Ticked, they follow the formats of Settings > General, which now give %2$s. Dates are shown as they are stored; no time zone is applied. The other export formats keep the first form unless the person who exports picks a language from the Format menu beside the export buttons.', 'exports-and-reports' ),
									'<code>' . esc_html( gmdate( 'Y/m/d g:i:s A', $date_example ) ) . '</code>',
									'<code>' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date_example, new DateTimeZone( 'UTC' ) ) ) . '</code>'
								),
								array( 'code' => array() )
							);
							?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="count_cache"><?php esc_html_e( 'Row Count Cache', 'exports-and-reports' ); ?></label></th>
					<td>
						<select name="count_cache" id="count_cache">
							<?php foreach ( $count_caches as $minutes => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $minutes ); ?>"<?php selected( $count_cache, $minutes ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<input name="count_cache_forget" type="submit" class="button" value="<?php esc_attr_e( 'Forget the Counts Now', 'exports-and-reports' ); ?>" />
						<p class="description"><?php esc_html_e( 'How long the row count of a report page ("1,234 items") is kept for the same query, search and filters. The count runs with the first page over every row the query matches, which costs a slow query as much again; while a count is kept, the page views that follow run the query without it. Rows added or removed meanwhile show in the count once it is counted again; the button makes every report count again now.', 'exports-and-reports' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Export Directory', 'exports-and-reports' ); ?></th>
					<td>
						<code><?php echo esc_html( EXPORTS_REPORTS_EXPORT_DIR ); ?></code><br />
						<span class="description">
							<?php if ( isset( $dir_statuses[ $dir_status ] ) ) : ?>
								<strong class="exports-reports-dir-status"><?php echo esc_html( $dir_statuses[ $dir_status ] ); ?></strong>
							<?php endif; ?>
							<?php esc_html_e( 'Downloads are served by the plugin after an access check; the directory itself should not be reachable by URL.', 'exports-and-reports' ); ?>
							<?php if ( file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/.htaccess' ) ) : ?>
								<?php esc_html_e( 'Apache: the plugin\'s .htaccess denies direct requests.', 'exports-and-reports' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Apache: the plugin writes an .htaccess that denies direct requests with the first export.', 'exports-and-reports' ); ?>
							<?php endif; ?>
							<?php
							echo wp_kses(
								sprintf(
									/* translators: 1: the nginx location rule, 2: the EXPORTS_REPORTS_EXPORT_DIR constant. */
									esc_html__( 'nginx ignores .htaccess; add %1$s to the server block, or define %2$s in wp-config.php with a directory outside the web root, which works on any server.', 'exports-and-reports' ),
									'<code>location ~* ' . esc_html( trailingslashit( (string) wp_parse_url( EXPORTS_REPORTS_EXPORT_URL, PHP_URL_PATH ) ) ) . ' { deny all; }</code>',
									'<code>EXPORTS_REPORTS_EXPORT_DIR</code>'
								),
								array( 'code' => array() )
							);
							?>
						</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Cronjob/JSON API Token', 'exports-and-reports' ); ?></th>
					<td>
						<p class="exports-reports-token-state">
							<?php if ( $token_on ) : ?>
								<?php esc_html_e( 'Token access is on. The token is stored as a hash, like a password, so it cannot be shown here; generate a new one if it is lost.', 'exports-and-reports' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Token access is off: the REST routes answer logged-in users only. Generate a token to let a script or a cronjob in.', 'exports-and-reports' ); ?>
							<?php endif; ?>
						</p>
						<p>
							<input name="generate_token" type="submit" class="button" value="<?php echo esc_attr( $token_on ? __( 'Generate a New Token', 'exports-and-reports' ) : __( 'Generate a Token', 'exports-and-reports' ) ); ?>" />
							<?php if ( $token_on ) : ?>
								<input name="disable_token" type="submit" class="button" value="<?php esc_attr_e( 'Turn Token Access Off', 'exports-and-reports' ); ?>" />
							<?php endif; ?>
						</p>
						<label for="cronjob_token"><?php esc_html_e( 'Or set a token of your own, then save the settings:', 'exports-and-reports' ); ?></label><br />
						<input name="cronjob_token" type="text" id="cronjob_token" size="50" value="" autocomplete="off" minlength="<?php echo esc_attr( (string) EXPORTS_REPORTS_TOKEN_MIN_LENGTH ); ?>" /><br />
						<span class="description">
							<?php
							/* translators: %d: a number of characters. */
							echo esc_html( sprintf( __( 'At least %d characters: letters, digits and punctuation marks, without spaces. It is stored as you type it.', 'exports-and-reports' ), EXPORTS_REPORTS_TOKEN_MIN_LENGTH ) );
							?>
							<br /><br />
							<?php
							echo wp_kses(
								sprintf(
									/* translators: %s: the Authorization header, as code. */
									esc_html__( 'Keep the token secret: it lets a script or a cronjob run and download exports and read report data through the REST API, without a logged-in user. Send it with each request as the header %s:', 'exports-and-reports' ),
									'<code>Authorization: Bearer TOKEN</code>'
								),
								array( 'code' => array() )
							);
							?>
							<br />
							<input type="text" id="export_api_command" class="exports-reports-url" readonly aria-label="<?php esc_attr_e( 'An export downloaded with the token in the header', 'exports-and-reports' ); ?>" value="<?php echo esc_attr( 'curl -H "Authorization: Bearer ' . $token . '" "' . $rest_base . '/export?type=csv&download=1" -o report.csv' ); ?>" /><br />
							<br />

							<input type="hidden" name="token_header_set" value="1" />
							<label><input type="checkbox" name="token_header_only" value="1"<?php checked( $header_only ); ?> /> <?php esc_html_e( 'Take the token in the Authorization header only', 'exports-and-reports' ); ?></label><br />
							<?php
							echo wp_kses(
								sprintf(
									/* translators: %s: the token query parameter, as code. */
									esc_html__( 'A token in the address ends up in server logs, proxies and histories. Until this box is ticked the routes still take it there, as %s at the end of an address, for the scripts that send it that way; tick the box once yours send the header. Ten wrong tokens from one address within a quarter of an hour make the routes refuse that address\'s tokens for as long.', 'exports-and-reports' ),
									'<code>&amp;token=TOKEN</code>'
								),
								array( 'code' => array() )
							);
							?>
							<br /><br />

							<label for="export_api_url1" class="exports-reports-url-label"><strong><?php esc_html_e( 'URL to Export (useful for Cronjobs):', 'exports-and-reports' ); ?></strong></label><br />
							<input type="text" id="export_api_url1" class="exports-reports-url" readonly value="<?php echo esc_url( $rest_base . '/export?type=YOUR_EXPORT_TYPE' ); ?>" /><br />

							<label for="export_api_url4" class="exports-reports-url-label"><strong><?php esc_html_e( 'URL to Export and then Download File:', 'exports-and-reports' ); ?></strong></label><br />
							<input type="text" id="export_api_url4" class="exports-reports-url" readonly value="<?php echo esc_url( $rest_base . '/export?type=YOUR_EXPORT_TYPE&download=1' ); ?>" /><br />

							<label for="export_api_url2" class="exports-reports-url-label"><strong><?php esc_html_e( 'URL to JSON API (paginated):', 'exports-and-reports' ); ?></strong></label><br />
							<input type="text" id="export_api_url2" class="exports-reports-url" readonly value="<?php echo esc_url( $rest_base . '/data?pg=PAGE_NUMBER' ); ?>" /><br />

							<label for="export_api_url3" class="exports-reports-url-label"><strong><?php esc_html_e( 'URL to JSON API (full data):', 'exports-and-reports' ); ?></strong></label><br />
							<input type="text" id="export_api_url3" class="exports-reports-url" readonly value="<?php echo esc_url( $rest_base . '/data?full=1' ); ?>" />
						</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="reset"><?php esc_html_e( 'Reset All Settings', 'exports-and-reports' ); ?></label></th>
					<td>
						<input name="reset" type="submit" id="reset" class="button" value="<?php esc_attr_e( 'Reset Now', 'exports-and-reports' ); ?>" />
						<p class="description">
							<?php
							/* translators: %s: the export directory, relative to the WordPress directory. */
							echo esc_html( sprintf( __( 'This generates a new token, takes the token in the address again as well as in the header, puts every export format back on, the dates back to the plugin\'s own format and the row count cache back to five minutes, clears the export log and removes the export files from your Exports directory - %s. Groups and reports are kept.', 'exports-and-reports' ), str_replace( ABSPATH, '', EXPORTS_REPORTS_EXPORT_DIR ) ) );
							?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Settings', 'exports-and-reports' ) ); ?>
		</form>
	</div>
	<?php
}
