<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The comments class.
 *
 * @since TBD
 */
class ProMatchups_Datatable {
	protected $args;

	/**
	 * Construct the class.
	 */
	function __construct( $args = [] ) {
		// // Parse atts.
		// $this->args = shortcode_atts([
		// 	'user_id'  => get_current_user_id(),
		// ], $args, 'pm_user_stats' );

		// // Sanitize.
		// $this->args['user_id'] = absint( $this->args['user_id'] );
	}

	function hooks() {
		add_action( 'wp_ajax_pm_datatable',        [ $this, 'handle_datatable' ] );
		add_action( 'wp_ajax_nopriv_pm_datatable', [ $this, 'handle_datatable' ] );
	}

	function handle_datatable() {
		// Verify nonce for security.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pm_datatable_nonce' ) ) {
			$message = __( 'Datatable security check failed.', 'promatchups' );

			wp_send_json_error( [ 'message' => $message ] );
			exit;
		}

		// Get data.
		$user_id  = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		// TODO:
	}

	/**
	 * Enqueue the scripts.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function get() {
		$html = '';

		wp_enqueue_style( 'datatables', 'https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css', [], '2.1.8' );
		wp_enqueue_script( 'datatables', 'https://cdn.datatables.net/2.1.8/js/dataTables.min.js', [ 'jquery' ], '2.1.8', true );

		// wp_enqueue_style( 'datatables-responsive', 'https://cdn.datatables.net/responsive/3.0.3/css/responsive.dataTables.min.css', [], '2.4.1' );
		// wp_enqueue_script( 'datatables-responsive', 'https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js', [ 'datatables', 'jquery' ], '2.4.1', true );

		wp_enqueue_style( 'datatables-buttons', 'https://cdn.datatables.net/buttons/3.1.2/css/buttons.dataTables.min.css', [], '3.1.2' );
		wp_enqueue_script( 'datatables-buttons', 'https://cdn.datatables.net/buttons/3.1.2/js/dataTables.buttons.min.js', [ 'datatables', 'jquery' ], '3.1.2', true );

		// Get user data.
		$bots    = [];
		$bot_id  = pm_get_bot_user_id();
		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : $bot_id;
		$bot_ids = array_filter([
			get_current_user_id(),
			$bot_id,
			pm_get_awaybot_user_id(),
			pm_get_homebot_user_id(),
			pm_get_favoredbot_user_id(),
			pm_get_underdogbot_user_id(),
		]);

		// Loop through bot ID's and get user display name.
		foreach ( $bot_ids as $bot_id ) {
			$bots[ $bot_id ] = get_user_by( 'ID', $bot_id )->display_name;
		}

		// Start the data array for DataTables.
		$array = [];

		// Get user votes.
		$comments = get_comments(
			[
				'type'       => [ 'pm_vote', 'pm_spread' ],
				'status'     => 'approve',
				'user_id'    => $user_id,
				'number'     => 1000, // This is hardcoded in the table filters too.
				// 'date_query' => [
				// 	[
				// 		'year' => '2024',
				// 	],
				// ],
				// 'orderby' => 'comment_date',
				// 'order'   => 'ASC',
			]
		);

		// Loop through comments and build an array of date, result (karma), league, and probability.
		foreach ( $comments as $comment ) {
			// Skip if karma is 0.
			if ( is_null( $comment->comment_karma ) || 0 === (int) $comment->comment_karma ) {
				continue;
			}

			// Result.
			switch ( (int) $comment->comment_karma ) {
				case 1:
					$result = __( 'Win', 'promatchups' );
				break;
				case -1:
					$result = __( 'Loss', 'promatchups' );
				break;
				case 2:
					$result = __( 'Tie', 'promatchups' );
				break;
				default:
					$result = '';
			}

			// Format type.
			$type    = $comment->comment_type;
			$covered = '';
			// $type = str_replace( 'pm_', '', $type );
			// $type = ucwords( $type );

			// Map type.
			switch ( $type ) {
				case 'pm_vote':
					$type = 'H2H';
				break;
				case 'pm_spread':
					$type = 'ATS';

					switch ( $comment->comment_parent ) {
						case 1:
						case '1':
							$covered = __( 'Yes', 'promatchups' );
						break;
						case 0:
						case '0':
							$covered = __( 'No', 'promatchups' );
						break;
					}

				break;
			}

			// Add to array.
			$array[] = [
				'date'       => date( 'Y-m-d', strtotime( $comment->comment_date ) ),
				'title'      => sprintf( '<a class="entry-title-link" href="%s">%s</a>',  get_permalink( $comment->comment_post_ID ), get_the_title( $comment->comment_post_ID ) ),
				'league'     => $comment->comment_agent,
				'confidence' => $comment->comment_author_IP,
				'covered'    => $covered,
				'result'     => $result,
				'type'       => $type,
			];
		}

		// Start table wrap.
		$html .= '<div class="has-xl-margin-bottom has-sm-font-size">';
			// Custom CSS.
			ob_start();
			?>
			<style>
			.dt-search {
				display: none;
			}
			.pages-row {
				position: sticky;
				bottom: 0;
				display: flex;
				justify-content: space-between;
				gap: 1em;
				background: var(--color-alt);
				padding: 24px;
				border-top: var(--border);
			}
			#vote-stats_info {
				margin-bottom: 12px;
				font-size: var(--font-size-sm);
				text-align: center;
			}
			</style>
			<?php
			$html .= ob_get_clean();

			// TODO: Add filter for different User ID's.
			// TODO: Update url with query parameters so current filters are saved and can be shared.



			// Win percentage.
			$html .= '<div id="win-percentage-section" class="has-md-padding has-lg-margin-bottom has-background has-alt-background-color" style="display:flex;justify-content:space-between;align-items:center;gap:.5em;">';
				// User/Bot filter.
				$html .= '<div style="display:flex;gap:.5em;align-items:center;">';
					$html .= '<label class="has-no-margin-bottom" for="user-filter">Bot:</label>';
					$html .= '<select style="min-height:48px;" id="user-filter">';
						foreach ( $bots as $bot_id => $bot_name ) {
							$selected  = $user_id === $bot_id ? ' selected' : '';
							$html     .= sprintf( '<option value="%d"%s>%s</option>', $bot_id, $selected, $bot_name );
						}
					$html .= '</select>';
				$html .= '</div>';
				$html .= '<div style="display:flex;gap:.5em;align-items:center;">';
					$html .= '<label class="has-no-margin-bottom">Win Percentage:</label>';
					$html .= '<span id="win-percentage">Calculating...</span>';
				$html .= '</div>';
			$html .= '</div>';

			// Filters.
			$html .= '<div style="display:flex;gap:1em;" class="has-lg-margin-bottom">';
				// Search.
				$html .= '<div style="flex:1;">';
					$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
					$html  .= '<label for="global-search">Search:</label>';
					$html  .= sprintf( '<input type="text" id="global-search" placeholder="Search all columns" value="%s">', $search );
				$html .= '</div>';

				// League filter.
				$html .= '<div>';
					$league = isset( $_GET['league'] ) ? sanitize_text_field( $_GET['league'] ) : '';
					$html  .= '<label for="league-filter">League:</label>';
					$html  .= '<select style="min-height:48px;" id="league-filter">';
						$html .= '<option value="">All</option>';
						$html .= sprintf( '<option value="NFL"%s>NFL</option>', 'NFL' === $league ? ' selected' : '' );
						$html .= sprintf( '<option value="NBA"%s>NBA</option>', 'NBA' === $league ? ' selected' : '' );
						$html .= sprintf( '<option value="MLB"%s>MLB</option>', 'MLB' === $league ? ' selected' : '' );
					$html .= '</select>';
				$html .= '</div>';

				// Result filter.
				$html .= '<div>';
					$result = isset( $_GET['result'] ) ? sanitize_text_field( $_GET['result'] ) : '';
					$html  .= '<label for="result-filter">Result:</label>';
					$html  .= '<select style="min-height:48px;" id="result-filter">';
						$html .= '<option value="">All</option>';
						$html .= sprintf( '<option value="Win"%s>Win</option>', 'Win' === $result ? ' selected' : '' );
						$html .= sprintf( '<option value="Loss"%s>Loss</option>', 'Loss' === $result ? ' selected' : '' );
						$html .= sprintf( '<option value="Tie"%s>Tie</option>', 'Tie' === $result ? ' selected' : '' );
					$html .= '</select>';
				$html .= '</div>';

				// Type filter.
				$html .= '<div>';
					$type  = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
					$html .= '<label for="type-filter">Type:</label>';
					$html .= '<select style="min-height:48px;" id="type-filter">';
						$html .= '<option value="">All</option>';
						$html .= sprintf( '<option value="H2H"%s>H2H</option>', 'H2H' === $type ? ' selected' : '' );
						$html .= sprintf( '<option value="ATS"%s>ATS</option>', 'ATS' === $type ? ' selected' : '' );
					$html .= '</select>';
				$html .= '</div>';

				// Confidence filter.
				$html .= '<div style="width:10ch;">';
					$html .= '<label for="confidence-filter">Confidence:</label>';
					$html .= sprintf( '<input type="number" id="confidence-filter" placeholder="60" min="0" style="min-height: 48px;" value="%s">', isset( $_GET['conf'] ) ? absint( $_GET['conf'] ) : '' );
				$html .= '</div>';
			$html .= '</div>';

			// Date filter.
			$html .= '<div style="display:flex;gap:1em;" class="has-lg-margin-bottom">';
				$html .= '<div style="flex:1;">';
					$min_date = isset( $_GET['min_date'] ) ? sanitize_text_field( $_GET['min_date'] ) : '';
					$max_date = isset( $_GET['max_date'] ) ? sanitize_text_field( $_GET['max_date'] ) : '';
					$html    .= '<label for="date-filter">Date Range:</label>';
					$html    .= '<div style="display:flex;gap:.5em;">';
						$html .= sprintf( '<input type="date" id="min-date" placeholder="Start Date" value="%s">', $min_date );
						$html .= sprintf( '<input type="date" id="max-date" placeholder="End Date" value="%s">', $max_date );
					$html .= '</div>';
				$html .= '</div>';
			$html .= '</div>';

			// Build table.
			$html .= '<table id="vote-stats">';
				$html .= '<thead>';
					$html .= '<tr>';
						$html .= sprintf( '<th data-column="date">%s</th>', __( 'Date', 'promatchups' ) );
						$html .= sprintf( '<th data-column="matchup">%s</th>', __( 'Matchup', 'promatchups' ) );
						$html .= sprintf( '<th data-column="league">%s</th>', __( 'League', 'promatchups' ) );
						$html .= sprintf( '<th data-column="confidence">%s</th>', __( 'Confidence', 'promatchups' ) );
						$html .= sprintf( '<th data-column="covered">%s</th>', __( 'Covered', 'promatchups' ) );
						$html .= sprintf( '<th data-column="result">%s</th>', __( 'Result', 'promatchups' ) );
						$html .= sprintf( '<th data-column="type">%s</th>', __( 'Type', 'promatchups' ) );
					$html .= '</tr>';
				$html .= '</thead>';
				$html .= '<tfoot>';
					$html .= '<tr>';
						$html .= '<th></th>';
						$html .= '<th></th>';
						$html .= '<th></th>';
						$html .= '<th></th>';
						$html .= '<th></th>';
						$html .= '<th></th>';
						$html .= '<th></th>';
					$html .= '</tr>';
				$html .= '</tfoot>';
				$html .= '<tbody></tbody>';
			$html .= '</table>';
		$html .= '</div>';

		// Convert to JSON.
		$json_data = wp_json_encode( $array );

		// Add the DataTables initialization script.
		add_action( 'wp_footer', function() use ( $json_data ) {
			?>
			<script>
			jQuery(document).ready(function($) {
				var data    = <?php echo $json_data; ?>;
				var tableId = '#vote-stats';
				var table   = $(tableId).DataTable({
					data: data,
					columns: [
						{ data: 'date', title: 'Date' },
						{ data: 'title', title: 'Matchup' },
						{ data: 'league', title: 'League' },
						{ data: 'confidence', title: 'Confidence' },
						{ data: 'covered', title: 'Covered' },
						{ data: 'result', title: 'Result' },
						{ data: 'type', title: 'Type' }
					],
					order: [[0, 'desc']],
					paging: true,
					ordering: true,
					searching: true,
					info: true,
					pageLength: 1000,
					dom: '<"top"i>rt<"bottom pages-row"lp><"clear">',
					language: {
						lengthMenu: '<select>' +
							'<option value="10">10 rows</option>' +
							'<option value="25">25 rows</option>' +
							'<option value="50">50 rows</option>' +
							'<option value="100">100 rows</option>' +
							'<option value="200">200 rows</option>' +
							'<option value="500">500 rows</option>' +
							'<option value="750">750 rows</option>' +
							'<option value="1000">1000 (max query)</option>' +
							'</select>'
					},
				});

				// Dynamically resolve column indices once and cache them
				var columnIndices = {
					date: $(`${tableId} thead th[data-column="date"]`).index(),
					league: $(`${tableId} thead th[data-column="league"]`).index(),
					confidence: $(`${tableId} thead th[data-column="confidence"]`).index(),
					result: $(`${tableId} thead th[data-column="result"]`).index(),
					type: $(`${tableId} thead th[data-column="type"]`).index(),
				};

				// Custom filtering logic
				$.fn.dataTable.ext.search = [];

				// Date range filter
				$.fn.dataTable.ext.search.push(function(settings, data) {
					var min = $('#min-date').val();
					var max = $('#max-date').val();
					var date = data[columnIndices.date];

					if ((min === '' || date >= min) && (max === '' || date <= max)) {
						return true;
					}
					return false;
				});

				// Confidence filter
				$.fn.dataTable.ext.search.push(function(settings, data) {
					var minConfidence = parseFloat($('#confidence-filter').val()) || 0;   // Default to 0 if empty
					var confidence    = parseFloat(data[columnIndices.confidence]) || 0;

					return confidence >= minConfidence;
				});

				$('#user-filter').on('change', function() {
					var value = this.value;
					var url   = updateURLParameter('user_id', value);
					// Refresh the page with the new user ID
					window.location.href = url;
				});

				$('#global-search').on('keyup', function() {
					updateURLParameter('search', this.value);
					table.search(this.value).draw();
				});

				$('#league-filter').on('change', function() {
					var value = this.value;
					updateURLParameter('league', value);
					table.column(columnIndices.league).search(value).draw();
				});

				$('#result-filter').on('change', function() {
					var value = this.value;
					updateURLParameter('result', value);
					table.column(columnIndices.result).search(value).draw();
				});

				$('#type-filter').on('change', function() {
					var value = this.value;
					updateURLParameter('type', value);
					table.column(columnIndices.type).search(value).draw();
				});

				$('#confidence-filter').on('keyup change', function() {
					updateURLParameter('conf', this.value);
					table.draw();
				});

				// Event listeners for filters
				$('#min-date, #max-date').on('change', function() {
					updateURLParameter('min_date', $('#min-date').val());
					updateURLParameter('max_date', $('#max-date').val());
					table.draw();
				});

				// Update the URL with the new parameter value.
				function updateURLParameter(param, value) {
					const url = new URL(window.location.href);

					if (value) {
						url.searchParams.set(param, value);
					} else {
						url.searchParams.delete(param); // Remove the param if the value is empty.
					}

					window.history.replaceState({}, '', url); // Update the URL without reloading.

					// Return the url.
					return url;
				}

				// Win percentage calculation.
				function updateWinPercentage() {
					var filteredData = table.rows({ filter: 'applied' }).data();
					var totalRows = filteredData.length;
					var winCount = 0;

					filteredData.each(function(row) {
						if (row.result === 'Win') {
							winCount++;
						}
					});

					var winPercentage = totalRows > 0 ? (winCount / totalRows) * 100 : 0;
					$('#win-percentage').text(`${winPercentage.toFixed(2)}%`);
				}

				table.on('draw', updateWinPercentage);

				// Initial calculation for win percentage
				updateWinPercentage();
			});
			</script>
			<?php
		});

		return $html;
	}
}