<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Shortcodes class.
 *
 * @since 0.8.0
 */
class ProMatchups_Shortcodes {

	/**
	 * Construct the class.
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	function hooks() {
		add_shortcode( 'pm_user_datatable', [ $this, 'get_datatable' ] );
		add_shortcode( 'pm_user_stats',     [ $this, 'get_stats' ] );
		add_shortcode( 'pm_leaderboard',    [ $this, 'get_leaderboard' ] );
	}

	function get_datatable( $atts ) {
		$html = '';

		// Parse atts.
		$atts = shortcode_atts([
			'user_id'  => get_current_user_id(),
		], $atts, 'pm_user_stats' );

		// Sanitize.
		$user_id = absint( $atts['user_id'] );
		$user    = get_user_by( 'ID', $user_id );

		// Bail if no user.
		if ( ! $user ) {
			return $html;
		}

		wp_enqueue_style( 'datatables', 'https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css', [], '2.1.8' );
		wp_enqueue_script( 'datatables', 'https://cdn.datatables.net/2.1.8/js/dataTables.min.js', [ 'jquery' ], '2.1.8', true );

		// wp_enqueue_style( 'datatables-responsive', 'https://cdn.datatables.net/responsive/3.0.3/css/responsive.dataTables.min.css', [], '2.4.1' );
		// wp_enqueue_script( 'datatables-responsive', 'https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js', [ 'datatables', 'jquery' ], '2.4.1', true );

		wp_enqueue_style( 'datatables-buttons', 'https://cdn.datatables.net/buttons/3.1.2/css/buttons.dataTables.min.css', [], '3.1.2' );
		wp_enqueue_script( 'datatables-buttons', 'https://cdn.datatables.net/buttons/3.1.2/js/dataTables.buttons.min.js', [ 'datatables', 'jquery' ], '3.1.2', true );

		// Start the data array for DataTables.
		$array = [];

		// Get user votes.
		$comments = get_comments(
			[
				'type'    => [ 'pm_vote', 'pm_spread' ],
				'status'  => 'approve',
				'user_id' => $user_id,
				'number'  => 1000, // This is hardcoded in the table filters too.
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
			$type = $comment->comment_type;
			// $type = str_replace( 'pm_', '', $type );
			// $type = ucwords( $type );

			// Map type.
			switch ( $type ) {
				case 'pm_vote':
					$probability = $comment->comment_parent;
					$type        = 'H2H';
				break;
				case 'pm_spread':
					$probability = '';
					$type        = 'ATS';
				break;
			}

			// Add to array.
			$array[] = [
				'date'        => date( 'Y-m-d', strtotime( $comment->comment_date ) ),
				'title'       => sprintf( '<a class="entry-title-link" href="%s">%s</a>',  get_permalink( $comment->comment_post_ID ), get_the_title( $comment->comment_post_ID ) ),
				'league'      => $comment->comment_agent,
				'probability' => $probability,
				'result'      => $result,
				'type'        => $type,
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

			// Filters.
			$html .= '<div style="display:flex;gap:1em;" class="has-lg-margin-bottom">';
				$html .= '<div style="flex:1;">';
					$html .= '<label for="global-search">Search:</label>';
					$html .= '<input type="text" id="global-search" placeholder="Search all columns">';
				$html .= '</div>';
				$html .= '<div>';
					$html .= '<label for="league-filter">League:</label>';
					$html .= '<select style="min-height:48px;" id="league-filter">';
						$html .= '<option value="">All</option>';
						$html .= '<option value="NFL">NFL</option>';
						$html .= '<option value="NBA">NBA</option>';
						$html .= '<option value="MLB">MLB</option>';
					$html .= '</select>';
				$html .= '</div>';
				$html .= '<div>';
					$html .= '<label for="result-filter">Result:</label>';
					$html .= '<select style="min-height:48px;" id="result-filter">';
						$html .= '<option value="">All</option>';
						$html .= '<option value="Win">Win</option>';
						$html .= '<option value="Loss">Loss</option>';
						$html .= '<option value="Tie">Tie</option>';
					$html .= '</select>';
				$html .= '</div>';
				$html .= '<div>';
					$html .= '<label for="type-filter">Type:</label>';
					$html .= '<select style="min-height:48px;" id="type-filter">';
						$html .= '<option value="">All</option>';
						$html .= '<option value="H2H">H2H</option>';
						$html .= '<option value="ATS">ATS</option>';
					$html .= '</select>';
				$html .= '</div>';
			$html .= '</div>';

			// Date filter.
			$html .= '<div style="display:flex;gap:1em;" class="has-lg-margin-bottom">';
				$html .= '<div style="flex:1;">';
					$html .= '<label for="date-filter">Date Range:</label>';
					$html .= '<div style="display:flex;gap:.5em;">';
						$html .= '<input type="date" id="min-date" data-column="0" placeholder="Start Date">';
						$html .= '<input type="date" id="max-date" data-column="0" placeholder="End Date">';
					$html .= '</div>';
				$html .= '</div>';
			$html .= '</div>';

			// Win percentage.
			$html .= '<p id="win-percentage-section" class="has-background has-alt-background-color" style="display:flex;justify-content:center;gap:.25em;">';
				$html .= '<label class="has-no-margin-bottom">Win Percentage:</label>';
				$html .= '<span id="win-percentage">Calculating...</span>';
			$html .= '</p>';

			// Build table.
			$html .= '<table id="vote-stats">';
				$html .= '<thead>';
					$html .= '<tr>';
						$html .= sprintf( '<th>%s</th>', __( 'Date', 'promatchups' ) );
						$html .= sprintf( '<th>%s</th>', __( 'Matchup', 'promatchups' ) );
						$html .= sprintf( '<th>%s</th>', __( 'League', 'promatchups' ) );
						$html .= sprintf( '<th>%s</th>', __( 'Probability', 'promatchups' ) );
						$html .= sprintf( '<th>%s</th>', __( 'Result', 'promatchups' ) );
						$html .= sprintf( '<th>%s</th>', __( 'Type', 'promatchups' ) );
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
				var data = <?php echo $json_data; ?>;

				// Initialize DataTable.
				var table = $('#vote-stats').DataTable({
					data: data,
					columns: [
						{ data: 'date', title: 'Date' },
						{ data: 'title', title: 'Matchup' },
						{ data: 'league', title: 'League' },
						{ data: 'probability', title: 'Probability' },
						{ data: 'result', title: 'Result' },
						{ data: 'type', title: 'Type' }
					],
					order: [[0, 'desc']],
					paging: true,
					ordering: true,
					searching: true, // Needed for filters too.
					info: true,
					pageLength: 1000,
					// responsive: true,
					dom: '<"top"i>rt<"bottom pages-row"lp><"clear">', // Add entries-per-page (l) to the bottom with pagination (p)
					language: {
					lengthMenu: '<select>' + // Only the dropdown without text.
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

				// Function to calculate and display win percentage.
				function updateWinPercentage() {
					// Get filtered rows
					var filteredData = table.rows({ filter: 'applied' }).data();

					// Count total rows and wins
					var totalRows = filteredData.length;
					var winCount = 0;

					filteredData.each(function(row) {
						if (row.result === 'Win') {
							winCount++;
						}
					});

					// Calculate win percentage
					var winPercentage = totalRows > 0 ? (winCount / totalRows) * 100 : 0;

					// Update the display
					$('#win-percentage').text(`${winPercentage.toFixed(2)}%`);
				}

				// Update win percentage on every table draw (e.g., search, filter, paginate).
				table.on('draw', updateWinPercentage);

				// Initial calculation
				updateWinPercentage();

				// Date range filtering.
				$('#min-date, #max-date').on('change', function() {
					var min = $('#min-date').val();
					var max = $('#max-date').val();
					$.fn.dataTable.ext.search.push(function(settings, data) {
						var date = data[0]; // Date column value
						if ((min === '' || date >= min) && (max === '' || date <= max)) {
							return true;
						}
						return false;
					});
					table.draw();
				});

				// Global search bar.
				$('#global-search').on('keyup', function() {
					table.search(this.value).draw();
				});

				// Column-specific filters.
				$('#league-filter').on('change', function() {
					var value = $(this).val();
					table.column(2).search(value).draw(); // League is column index 2
				});

				$('#result-filter').on('change', function() {
					var value = $(this).val();
					table.column(4).search(value).draw(); // Result is column index 4
				});

				$('#type-filter').on('change', function() {
					var value = $(this).val();
					table.column(5).search(value).draw(); // Type is column index 5
				});
			});
			</script>
			<?php
		});

		return $html;
	}

	function get_datatable_og( $atts ) {
		$html = '';

		// Parse atts.
		$atts = shortcode_atts([
			'user_id'  => get_current_user_id(),
		], $atts, 'pm_user_stats' );

		// Sanitize.
		$user_id = absint( $atts['user_id'] );
		$user    = get_user_by( 'ID', $user_id );

		// Bail if no user.
		if ( ! $user ) {
			return $html;
		}

		// wp_enqueue_style( 'datatables', 'https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css', [], '2.1.8' );
		// wp_enqueue_script( 'datatables', 'https://cdn.datatables.net/2.1.8/js/dataTables.min.js', [ 'jquery' ], '2.1.8', true );
		wp_enqueue_style( 'datatables', 'https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css', [], '2.1.8' );
		wp_enqueue_script( 'datatables', 'https://cdn.datatables.net/2.1.8/js/dataTables.js', [ 'jquery' ], '2.1.8', true );

		wp_enqueue_style( 'datatables-buttons', 'https://cdn.datatables.net/buttons/3.1.2/css/buttons.dataTables.min.css', [], '3.1.2' );
		wp_enqueue_script( 'datatables-buttons', 'https://cdn.datatables.net/buttons/3.1.2/js/dataTables.buttons.min.js', [ 'datatables', 'jquery' ], '3.1.2', true );

		// Start the data array for DataTables.
		$array = [];

		// Get user votes.
		$comments = get_comments(
			[
				'type'    => [ 'pm_vote', 'pm_spread' ],
				'status'  => 'approve',
				'user_id' => $user_id,
				// 'orderby' => 'comment_date',
				// 'order'   => 'ASC',
			]
		);

		// Loop through comments and build an array of date, result (karma), league, and probability.
		foreach ( $comments as $comment ) {
			// Skip if karma is 0.
			if ( 0 === (int) $comment->comment_karma ) {
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

			// Add to array.
			$array[] = [
				'date'        => date( 'Y-m-d', strtotime( $comment->comment_date ) ),
				'result'      => $result,
				'league'      => $comment->comment_agent,
				'probability' => $comment->comment_parent,
			];
		}

		// Convert to JSON.
		$json_data = wp_json_encode( $array );

		// Build table.
		$html .= '<div>';
			$html .= '<table id="vote-stats">';
				$html .= '<thead>';
					$html .= '<tr>';
						$html .= sprintf( '<th>%s</th>', __( 'League', 'promatchups' ) );
						$html .= sprintf( '<th>%s</th>', __( 'Total Wins', 'promatchups' ) );
						$html .= sprintf( '<th>%s</th>', __( 'Win Percentage', 'promatchups' ) );
					$html .= '</tr>';
				$html .= '</thead>';
				$html .= '<tfoot>';
					$html .= '<tr>';
						$html .= '<th>NBA</th>';
						$html .= '<th id="nbaTotalWins"></th>';
						$html .= '<th id="nbaWinPercentage"></th>';
					$html .= '</tr>';
					$html .= '<tr>';
						$html .= '<th>MLB</th>';
						$html .= '<th id="mlbTotalWins"></th>';
						$html .= '<th id="mlbWinPercentage"></th>';
					$html .= '</tr>';
					$html .= '<tr>';
						$html .= '<th>NFL</th>';
						$html .= '<th id="nflTotalWins"></th>';
						$html .= '<th id="nflWinPercentage"></th>';
					$html .= '</tr>';
				$html .= '</tfoot>';
			$html .= '</table>';
		$html .= '</div>';

		add_action( 'wp_footer', function() use ( $json_data ) {
			?>
			<script>
			jQuery(document).ready(function($) {
				const data = <?php echo $json_data; ?>;

				$('#vote-stats').DataTable({
					data: data,
					columns: [
						{ data: 'league' },
						{ data: 'result' },
						{ data: 'probability' }
					],
					paging: false,      // Disable pagination to work with all rows
					ordering: false,     // Disable ordering since rows are hidden
					searching: false,    // Disable searching since rows are hidden
					info: false,         // Hide DataTables info
					footerCallback: function (row, data, start, end, display) {
						let nbaWins = 0, nbaTotal = 0;
						let mlbWins = 0, mlbTotal = 0;
						let nflWins = 0, nflTotal = 0;

						data.forEach(entry => {
							if (entry.league === 'NBA') {
								nbaTotal++;
								if (entry.result === 'Win') nbaWins++;
							} else if (entry.league === 'MLB') {
								mlbTotal++;
								if (entry.result === 'Win') mlbWins++;
							} else if (entry.league === 'NFL') {
								nflTotal++;
								if (entry.result === 'Win') nflWins++;
							}
						});

						// Calculate win percentages
						const nbaWinPercentage = nbaTotal > 0 ? ((nbaWins / nbaTotal) * 100).toFixed(2) + '%' : '0%';
						const mlbWinPercentage = mlbTotal > 0 ? ((mlbWins / mlbTotal) * 100).toFixed(2) + '%' : '0%';
						const nflWinPercentage = nflTotal > 0 ? ((nflWins / nflTotal) * 100).toFixed(2) + '%' : '0%';

						// Update footer totals
						$('#nbaTotalWins').html(nbaWins);
						$('#nbaWinPercentage').html(nbaWinPercentage);
						$('#mlbTotalWins').html(mlbWins);
						$('#mlbWinPercentage').html(mlbWinPercentage);
						$('#nflTotalWins').html(nflWins);
						$('#nflWinPercentage').html(nflWinPercentage);
					}
				});

				// Hide all rows in the table body with CSS
				// $('#vote-stats tbody').hide();
			});
			</script>
			<?php
		});

		return $html;
		// $html .= '</div>';
	}

	/**
	 * Display the current user stats.
	 *
	 * @since 0.8.0
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	function get_stats( $atts ) {
		$html = '';

		// Parse atts.
		$atts = shortcode_atts([
			'user_id'  => get_current_user_id(),
			'all_time' => true,
			'leagues'  => 'mlb,nba,nfl,nhl',
		], $atts, 'pm_user_stats' );

		// Sanitize.
		$bot_id   = pm_get_bot_user_id();
		$user_id  = absint( $atts['user_id'] );
		$is_bot   = $bot_id == $user_id;
		$user     = get_user_by( 'ID', $user_id );
		$all_time = rest_sanitize_boolean( $atts['all_time'] );
		$leagues  = explode( ',', $atts['leagues'] );
		$leagues  = array_map( 'sanitize_text_field', $leagues );
		$leagues  = array_map( 'strtolower', $leagues );
		$leagues  = array_filter( $leagues );

		// Bail if no user.
		if ( ! $user ) {
			return $html;
		}

		// Bail if no leagues.
		if ( ! $leagues ) {
			return $html;
		}

		// Get base keys.
		$keys = [
			'total_votes'         => __( 'Total Picks', 'promatchups' ),
			'total_wins'          => __( 'Wins', 'promatchups' ),
			'total_losses'        => __( 'Losses', 'promatchups' ),
			'total_ties'          => __( 'Ties', 'promatchups' ),
			'win_percent'         => __( 'Win %', 'promatchups' ),
		];

		// If bot.
		if ( $is_bot ) {
			$keys = array_merge(
				$keys,
				[
					'total_votes_spread'  => __( 'Spread Picks', 'promatchups' ),
					'total_wins_spread'   => __( 'Spread Wins', 'promatchups' ),
					'total_losses_spread' => __( 'Spread Losses', 'promatchups' ),
					'total_ties_spread'   => __( 'Spread Ties', 'promatchups' ),
					'win_percent_spread'  => __( 'Spread Win %', 'promatchups' ),
				]
			);
		}

		// Add more keys.
		$keys = array_merge(
			$keys,
			[
				'total_points'        => __( 'Points Awarded', 'promatchups' ),
				'confidence'          => __( 'Confidence', 'promatchups' ),
				'xp_points'           => __( 'XPoints', 'promatchups' ),
			]
		);

		// Build HTML.
		$html .= '<div class="pm-userstats">';
			// If all time.
			if ( $all_time ) {
				// Build main section.
				$html .= '<ul class="pm-userstats__section">';
					// Build heading.
					$html .= sprintf( '<li class="pm-userstats__heading">%s</li>', __( 'All Time', 'promatchups' ) );

					// Get user meta.
					foreach ( $keys as $key => $label ) {
						switch ( $key ) {
							case 'xp_points':
							case 'confidence':
								// Converting to uppercase because i do a search-replace for the caps version when updating versions ;P
								$value = strtoupper( 'tbd' );
							break;
							case 'win_percent':
							case 'win_percent_spread':
								$value  = pm_parse_float( get_user_meta( $user_id, $key, true ) );
								$value .= '%';
							break;
							default:
								$value = pm_parse_float( get_user_meta( $user_id, $key, true ) );
								$value = 'win_percent' === $key ? $value . '%' : $value;
						}

						$html  .= sprintf( '<li class="pm-userstats__item %s"><span class="pm-userstats__label">%s</span><span class="pm-userstats__value">%s</span></li>', sanitize_html_class( $key ), $label, $value );
					}

				$html .= '</ul>';
			}

			// Loop through leagues.
			foreach ( $leagues as $league ) {
				// Get league keys and labels.
				$keys = [
					"total_votes_{$league}"  => __( 'Total Picks', 'promatchups' ),
					"total_wins_{$league}"   => __( 'Wins', 'promatchups' ),
					"total_losses_{$league}" => __( 'Losses', 'promatchups' ),
					"total_ties_{$league}"   => __( 'Ties', 'promatchups' ),
					"win_percent_{$league}"  => __( 'Win %', 'promatchups' ),
				];

				// If bot.
				if ( $is_bot ) {
					$keys = array_merge(
						$keys,
						[
							"total_votes_spread_{$league}"  => __( 'Spread Picks', 'promatchups' ),
							"total_wins_spread_{$league}"   => __( 'Spread Wins', 'promatchups' ),
							"total_losses_spread_{$league}" => __( 'Spread Losses', 'promatchups' ),
							"total_ties_spread_{$league}"   => __( 'Spread Push', 'promatchups' ),
							"win_percent_spread_{$league}"  => __( 'Spread Win %', 'promatchups' ),
						]
					);
				}

				// Add more keys.
				$keys = array_merge(
					$keys,
					[
						"total_points_{$league}" => __( 'Points Awarded', 'promatchups' ),
						"confidence_{$league}"   => __( 'Confidence', 'promatchups' ),
						"xp_points_{$league}"    => __( 'XPoints', 'promatchups' ),
					]
				);

				// Build main section.
				$html .= '<ul class="pm-userstats__section">';
					// Build heading.
					$html .= sprintf( '<li class="pm-userstats__heading">%s</li>', strtoupper( $league ) );

					// Get user meta.
					foreach ( $keys as $key => $label ) {
						$value = pm_parse_float( get_user_meta( $user_id, $key, true ) );
						$value = in_array( $key, [ "win_percent_{$league}", "win_percent_spread_{$league}" ] ) ? $value . '%' : $value;
						$html .= sprintf( '<li class="pm-userstats__item %s"><span class="pm-userstats__label">%s</span><span class="pm-userstats__value">%s</span></li>', sanitize_html_class( $key ), $label, $value );
					}

				$html .= '</ul>';
			}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Display the leaderboard.
	 *
	 * @since 0.8.0
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	function get_leaderboard( $atts ) {
		// Parse atts.
		$atts = shortcode_atts([
			'type'   => 'xp', // 'xp' or 'total'.
			'league' => '',
		], $atts, 'pm_leaderboard' );

		// Sanitize.
		$type   = strtolower( sanitize_text_field( $atts['type'] ) );
		$league = strtolower( sanitize_text_field( $atts['league'] ) );

		// Bail if not a valid type.
		if ( ! in_array( $type, [ 'xp', 'total' ] ) ) {
			return '';
		}

		// Build key.
		$key = $league ? "{$type}_points_{$league}" : "{$type}_points";

		// Query users with the most total comment_karma.
		$users = get_users(
			[
				'meta_key' => $key,
				'orderby'  => 'meta_value_num',
				'order'    => 'DESC',
				'number'   => 50,
			]
		);

		// Get current user ID.
		$bot_user_id     = 2;
		$current_user_id = get_current_user_id();

		// Output the leaderboard.
		$html  = '';
		$html .= '<div class="pm-leaderboard">';
			$html .= '<ol class="pm-leaderboard__list">';
				foreach ( $users as $user ) {
					$points     = get_user_meta( $user->ID, $key, true );
					$classes    = [ 'pm-leaderboard__item' ];
					$classes[]  = $current_user_id && $current_user_id == $user->ID ? 'current-user' : '';
					$classes[]  = $bot_user_id && $bot_user_id == $user->ID ? 'bot-user' : '';
					$classes    = array_filter( $classes );

					// Add list item.
					$html .= sprintf( '<li class="%s"><span class="pm-leaderboard__name">%s</span><span class="pm-leaderboard__score">%s</span></li>', implode( ' ', $classes ), esc_html( $user->display_name ), esc_html( $points ) );
				}
			$html .= '</ol>';
		$html .= '</div>';

		return $html;
	}
}