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
		add_shortcode( 'pm_user_datatable',  [ $this, 'get_datatable' ] );
		add_shortcode( 'pm_user_stats',      [ $this, 'get_stats' ] );
		add_shortcode( 'pm_leaderboard',     [ $this, 'get_leaderboard' ] );
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
				'type'    => [ 'pm_vote' ],
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

		// / Convert to JSON.
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
				$('#vote-stats tbody').hide();
			});
			</script>
			<?php
		});

		return $html;

		// $array['total_votes']  = get_user_meta( $user_id, 'total_votes', true );
		// $array['total_wins']   = get_user_meta( $user_id, 'total_wins', true );
		// $array['total_losses'] = get_user_meta( $user_id, 'total_losses', true );
		// $array['total_ties']   = get_user_meta( $user_id, 'total_ties', true );
		// $array['win_percent']  = get_user_meta( $user_id, 'win_percent', true );

		// // Leagues.
		// $leagues = [ 'mlb', 'nba', 'nfl', 'nhl' ];

		// // Loop through leagues.
		// foreach ( $leagues as $league ) {
		// 	$array["total_votes_{$league}"]  = get_user_meta( $user_id, "total_votes_{$league}", true );
		// 	$array["total_wins_{$league}"]   = get_user_meta( $user_id, "total_wins_{$league}", true );
		// 	$array["total_losses_{$league}"] = get_user_meta( $user_id, "total_losses_{$league}", true );
		// 	$array["total_ties_{$league}"]   = get_user_meta( $user_id, "total_ties_{$league}", true );
		// 	$array["win_percent_{$league}"]  = get_user_meta( $user_id, "win_percent_{$league}", true );
		// }

		// $html .= '<div>';
		// 	$html .= '<table id="vote-stats" class="display" style="width:100%">';
		// 		$html .= '<thead>';
		// 			$html .= '<tr>';
		// 				$html .= sprintf( '<th>%s</th>', __( 'Total Votes', 'promatchups' ) );
		// 				$html .= sprintf( '<th>%s</th>', __( 'Wins', 'promatchups' ) );
		// 				$html .= sprintf( '<th>%s</th>', __( 'Losses', 'promatchups' ) );
		// 				$html .= sprintf( '<th>%s</th>', __( 'Ties', 'promatchups' ) );
		// 				$html .= sprintf( '<th>%s</th>', __( 'Win %', 'promatchups' ) );
		// 				$html .= sprintf( '<th>%s</th>', __( 'League', 'promatchups' ) );
		// 				$html .= sprintf( '<th>%s</th>', __( 'Day', 'promatchups' ) );
		// 				$html .= sprintf( '<th>%s</th>', __( 'Week', 'promatchups' ) );
		// 			$html .= '</tr>';
		// 		$html .= '</thead>';
		// 	$html .= '</table>';
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