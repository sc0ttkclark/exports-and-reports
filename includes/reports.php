<?php
/**
 * A report and its group as rows, a report's admin UI options, the kept row counts, and the report page.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the plugin runs the administrator's report queries and keeps its own tables; there is nothing to cache.

/**
 * An enabled report by id.
 *
 * @since 0.9.5
 *
 * @param int $report_id The report id.
 * @return object|null The row of the reports table, or null when there is no enabled report with that id.
 */
function exports_reports_get_report( $report_id ) {
	/** @var wpdb $wpdb */ global $wpdb;

	$report = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->exports_reports_reports} WHERE `id` = %d AND `disabled` = 0 LIMIT 1", absint( $report_id ) ) );

	return empty( $report ) ? null : $report;
}

/**
 * An enabled group by id.
 *
 * @since 0.9.5
 *
 * @param int $group_id The group id.
 * @return object|null The row of the groups table, or null when there is no enabled group with that id.
 */
function exports_reports_get_group( $group_id ) {
	/** @var wpdb $wpdb */ global $wpdb;

	$group = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->exports_reports_groups} WHERE `id` = %d AND `disabled` = 0 LIMIT 1", absint( $group_id ) ) );

	return empty( $group ) ? null : $group;
}

/**
 * How long the row count of a report page is kept, in seconds, from the Settings page: five minutes
 * unless changed there, 0 for none. The count runs with the first page over every row the query matches,
 * which costs a slow query as much again; while a count is kept, the page views that follow run the query
 * without it. Rows added or removed meanwhile show in the count once it is counted again.
 *
 * @since 1.2.0
 *
 * @return int
 */
function exports_reports_count_cache_ttl() {
	$minutes = get_option( 'exports_reports_count_cache', 5 );

	return max( 0, (int) $minutes ) * MINUTE_IN_SECONDS;
}

/**
 * The generation of the kept row counts, part of each one's key: raising it (exports_reports_forget_counts())
 * lets every kept count go at once.
 *
 * @since 1.2.0
 *
 * @return int
 */
function exports_reports_count_cache_generation() {
	return (int) get_option( 'exports_reports_count_cache_generation', 0 );
}

/**
 * Forgets every kept row count: the next page view of each report counts again.
 *
 * @since 1.2.0
 */
function exports_reports_forget_counts() {
	update_option( 'exports_reports_count_cache_generation', exports_reports_count_cache_generation() + 1 );
}

/**
 * The admin UI options for a report row: what the report page and the REST API hand to
 * Exports_Reports_Admin_UI.
 *
 * @since 0.9.5
 *
 * @param object $report A row of the reports table.
 * @return array
 */
function exports_reports_report_options( $report ) {
	$options = array();

	$options['css']      = EXPORTS_REPORTS_URL . 'assets/css/admin.css';
	$options['readonly'] = true;

	$options['export']         = ( 0 === (int) $report->disable_export );
	$options['export_formats'] = exports_reports_report_export_formats( $report );
	$options['search']         = ( strlen( (string) $report->field_data ) > 0 ? true : false );
	$options['default_none']   = ( 1 === (int) $report->default_none ? true : false );
	$options['sql']            = trim( (string) $report->sql_query );
	$options['sql_count']      = trim( (string) $report->sql_query_count );

	if ( empty( $options['sql_count'] ) ) {
		unset( $options['sql_count'] );
	}

	$options['page_orientation'] = $report->page_orientation;
	$options['item']             = __( 'item', 'exports-and-reports' );
	$options['items']            = $report->name;
	$options['heading']          = array(
		'manage' => __( 'View Report:', 'exports-and-reports' ),
	);

	$field_data = json_decode( (string) $report->field_data, true );

	if ( is_array( $field_data ) && ! empty( $field_data ) ) {
		$options['columns'] = array();

		foreach ( $field_data as $field ) {
			$field = exports_reports_field_defaults( $field );

			$options['columns'][ $field['name'] ] = array();

			if ( 0 < strlen( $field['real_name'] ) ) {
				$options['columns'][ $field['name'] ]['real_name'] = $field['real_name'];
			}

			if ( 0 < strlen( $field['label'] ) ) {
				$options['columns'][ $field['name'] ]['label'] = $field['label'];
			}

			if ( 0 < strlen( $field['filter_label'] ) ) {
				$options['columns'][ $field['name'] ]['filter_label'] = $field['filter_label'];
			}

			if ( 0 < strlen( $field['custom_display'] ) ) {
				$options['columns'][ $field['name'] ]['custom_display'] = $field['custom_display'];
			}

			if ( 0 < strlen( $field['type'] ) ) {
				$options['columns'][ $field['name'] ]['type'] = $field['type'];
			}

			if ( 0 < strlen( $field['width'] ) ) {
				$options['columns'][ $field['name'] ]['width'] = $field['width'];
			}

			if ( 1 === (int) $field['hide_report'] ) {
				$options['columns'][ $field['name'] ]['display'] = false;
			}

			if ( 1 === (int) $field['hide_export'] ) {
				$options['columns'][ $field['name'] ]['export'] = false;
			}

			if ( 1 === (int) $field['search'] || 1 === (int) $field['hide_report'] ) {
				$options['columns'][ $field['name'] ]['search'] = false;
			} else {
				$options['columns'][ $field['name'] ]['search'] = true;
			}

			if ( 1 === (int) $field['filter'] ) {
				$options['columns'][ $field['name'] ]['filter'] = true;

				if ( 0 < strlen( $field['filter_default'] ) ) {
					$options['columns'][ $field['name'] ]['filter_default'] = $field['filter_default'];
				}

				if ( 0 < strlen( $field['filter_ongoing'] ) ) {
					$options['columns'][ $field['name'] ]['date_ongoing'] = $field['filter_ongoing'];

					if ( 0 < strlen( $field['filter_ongoing_default'] ) ) {
						$options['columns'][ $field['name'] ]['filter_ongoing_default'] = $field['filter_ongoing_default'];
					}
				}
			}

			if ( 1 === (int) $field['total_field'] ) {
				$options['columns'][ $field['name'] ]['total_field'] = true;
			}

			if ( 1 === (int) $field['group_related'] ) {
				$options['columns'][ $field['name'] ]['group_related'] = true;
			}

			if ( 'related' === $field['type'] ) {
				if ( 0 < strlen( $field['related'] ) ) {
					$options['columns'][ $field['name'] ]['related'] = $field['related'];
				}

				if ( 0 < strlen( $field['related_field'] ) ) {
					$options['columns'][ $field['name'] ]['related_field'] = $field['related_field'];
				}

				if ( 0 < strlen( $field['related_id'] ) ) {
					$options['columns'][ $field['name'] ]['related_id'] = $field['related_id'];
				}

				if ( 0 < strlen( $field['related_sql'] ) ) {
					$options['columns'][ $field['name'] ]['related_sql'] = $field['related_sql'];
				}
			}
		}
	}

	$options['report_id'] = (int) $report->id;

	$options = apply_filters( 'exports_reports_report_options', $options, (int) $report->id );

	return $options;
}

/**
 * The report page of a group: the reports the user may view, and the chosen one through
 * Exports_Reports_Admin_UI.
 *
 * @param int|false $group_id        The group, or false to read it from the page slug.
 * @param bool|null $has_full_access Whether the user has full access; null to work it out.
 *
 * @return bool False when there is nothing to show.
 */
function exports_reports_view( $group_id = false, $has_full_access = null ) {
	/** @var string|null $plugin_page The admin page slug, as the admin sets it. */ global $plugin_page;

	$page = isset( $plugin_page ) ? sanitize_key( (string) $plugin_page ) : '';

	if ( '' === $page ) {
		return false;
	}

	/** @var wpdb $wpdb */ global $wpdb;

	wp_enqueue_script( 'exports-reports-admin' );
	wp_enqueue_style( 'exports-reports-admin' );

	if ( null === $has_full_access ) {
		$has_full_access = is_super_admin() || exports_reports_current_user_can_any( 'exports_reports_full_access' );
	}

	if ( 'exports-reports' !== $page && false !== strpos( $page, 'exports-reports-group-' ) ) {
		if ( empty( $group_id ) ) {
			$group_id = (int) str_replace( 'exports-reports-group-', '', $page );
		}

		$group = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `role_access` FROM {$wpdb->exports_reports_groups} WHERE `disabled` = 0 AND `id` = %d ORDER BY `weight`, `name` LIMIT 1", $group_id ) );

		if ( empty( $group ) ) {
			return false;
		}
		$group_roles = explode( ',', $group->role_access );
	} else {
		$group_id    = 0;
		$group_roles = array();

		$groups = $wpdb->get_results( "SELECT `id`, `role_access` FROM {$wpdb->exports_reports_groups} WHERE `disabled` = 0 ORDER BY `weight`, `name`" );

		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				$reports = $wpdb->get_results( $wpdb->prepare( "SELECT `id`, `role_access` FROM {$wpdb->exports_reports_reports} WHERE `disabled` = 0 AND `group` = %d ORDER BY `weight`, `name`", $group->id ) );

				if ( ! empty( $reports ) ) {
					foreach ( $reports as $report ) {
						if ( $has_full_access || exports_reports_current_user_can_any( 'exports_reports_view' ) || exports_reports_current_user_can_any( 'exports_reports_view_group_' . $group->id ) || exports_reports_current_user_can_any( 'exports_reports_view_report_' . $report->id ) ) {
							$group_id    = $group->id;
							$group_roles = explode( ',', $group->role_access );

							break;
						}

						$roles = array_filter( array_unique( array_merge( explode( ',', $group->role_access ), explode( ',', $report->role_access ) ) ) );

						if ( empty( $roles ) ) {
							continue;
						}

						foreach ( $roles as $role ) {
							if ( exports_reports_has_role( $role ) ) {
								$group_id    = $group->id;
								$group_roles = explode( ',', $group->role_access );

								break 2;
							}
						}
					}
				}
			}
		}
	}

	if ( empty( $group_id ) ) {
		return false;
	}

	$reports = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->exports_reports_reports} WHERE `disabled` = 0 AND `group` = %d ORDER BY `weight`, `name`", $group_id ) );

	if ( empty( $reports ) ) {
		return false;
	}

	$selectable_reports = array();
	$current_report     = false;

	foreach ( $reports as $report ) {
		if ( $has_full_access || exports_reports_current_user_can_any( 'exports_reports_view' ) || exports_reports_current_user_can_any( 'exports_reports_view_group_' . $group_id ) || exports_reports_current_user_can_any( 'exports_reports_view_report_' . $report->id ) ) {
			if ( false === $current_report ) {
				$current_report = absint( $report->id );
			}

			$selectable_reports[ $report->id ] = $report;

			continue;
		}

		$roles = array_filter( array_unique( array_merge( $group_roles, explode( ',', $report->role_access ) ) ) );

		if ( empty( $roles ) ) {
			continue;
		}

		foreach ( $roles as $role ) {
			if ( exports_reports_has_role( $role ) ) {
				if ( false === $current_report ) {
					$current_report = absint( $report->id );
				}

				$selectable_reports[ $report->id ] = $report;
			}
		}
	}

	if ( empty( $selectable_reports ) ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the report chosen from the dropdown.
	$requested_report = isset( $_GET['report'] ) ? absint( wp_unslash( $_GET['report'] ) ) : 0;

	if ( 0 < $requested_report && isset( $selectable_reports[ $requested_report ] ) ) {
		$current_report = $requested_report;
	}

	require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';

	$options = exports_reports_report_options( $selectable_reports[ $current_report ] );

	$admin = new Exports_Reports_Admin_UI( $options );

	if ( 1 < count( $selectable_reports ) ) {
		?>
		<div class="exports-reports-selector">
			<strong><?php esc_html_e( 'Exports and Reports:', 'exports-and-reports' ); ?></strong>
			<label for="report"><?php esc_html_e( 'Choose Report', 'exports-and-reports' ); ?></label>
			<select id="report">
				<?php
				foreach ( $selectable_reports as $report_id => $report ) {
					?>
					<option value="
					<?php
					echo esc_attr(
						$admin->var_update(
							array(
								'page'   => $page,
								'report' => absint( $report_id ),
							),
							false,
							false,
							true
						)
					);
					?>
					"<?php selected( $current_report, $report_id ); ?>><?php echo esc_html( $report->name ); ?></option>
					<?php
				}
				?>
			</select>
		</div>
		<?php
	}

	// The report's query runs while this request renders. A note for the wait, sent ahead when the server
	// lets it through; the screen's script removes it once the page has loaded.
	?>
	<p class="exports-reports-loading"><span class="spinner is-active"></span><?php esc_html_e( 'Loading the report…', 'exports-and-reports' ); ?></p>
	<?php
	if ( 0 < ob_get_level() ) {
		ob_flush();
	}
	flush();

	$admin->go();

	return true;
}
