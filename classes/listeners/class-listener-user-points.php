<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The listener class.
 *
 * @since 0.8.0
 */
class ProMatchups_User_Points extends ProMatchups_Listener {
	protected $keys;
	protected $user;
	protected $points;
	protected $win_percent;
	protected $confidence;
	protected $return;

	/**
	 * Construct the class.
	 */
	function __construct( $user = null ) {
		$this->keys        = $this->get_all_keys();
		$this->user        = $this->get_user( $user );
		$this->points      = array_flip( $this->keys );
		$this->win_percent = [];
		$this->confidence  = [];

		// Set all point values to 0.
		foreach ( $this->points as $key => $value ) {
			$this->points[ $key ] = 0;
		}

		// Run.
		$this->run();
	}

	/**
	 * Run the class.
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	function run() {
		// Bail if not a valid user.
		if ( ! $this->user ) {
			$this->return = $this->get_error( 'User not found.' );
			return;
		}

		// Loop through and delete them all.
		foreach ( $this->keys as $key ) {
			delete_user_meta( $this->user->ID, $key );
		}

		// Get all comments.
		$bot_id  = pm_get_bot_user_id();
		$user_id = $this->user->ID;
		$is_bot  = $user_id === $bot_id;
		$votes   = get_comments(
			[
				'type'    => 'pm_vote',
				'status'  => 'approve',
				'user_id' => $user_id,
				'orderby' => 'comment_date',
				'order'   => 'ASC',
			]
		);

		// Skip if no votes.
		if ( ! $votes ) {
			$this->return = 'No comments found for user ID: ' . get_the_author_meta( 'display_name', $user_id );
			return;
		}

		// Leagues.
		$leagues = pm_get_all_leagues();
		$leagues = array_map( 'strtolower', $leagues );

		// Get required minimum votes. ~80% of the total games per team, per season.
		$req_min = [
			'all' => 200, // Random.
			'mlb' => 130, // ~80% of the total games per team, per season (162 games).
			'nba' => 66,  // ~80% of the total games per team, per season (82 games).
			'nfl' => 26,  // 1.5x the total games per team, per season (17 games).
			'nhl' => 66,  // ~80% of the total games per team, per season (82 games).
		];

		// Loop through and add leagues,
		// so we don't have to check isset later.
		foreach ( $leagues as $league ) {
			// Skip if set already.
			if ( isset( $req_min[ $league ] ) ) {
				continue;
			}

			$req_min[ $league ] = 200; // Random.
		}

		// Loop through votes.
		foreach ( $votes as $comment ) {
			// Get the matchup ID.
			$matchup_id = $comment->comment_post_ID;

			// Get the matchup.
			$matchup = get_post( $matchup_id );

			// Skip if no matchup.
			if ( ! $matchup ) {
				continue;
			}

			// Get the matchup outcome.
			$outcome = get_post_meta( $matchup_id, 'asknews_outcome', true );

			// Skip if no outcome.
			if ( ! $outcome ) {
				continue;
			}

			// Get data and league.
			$data   = pm_get_matchup_data( $matchup_id );
			$league = strtolower( (string) $data['league'] );

			// Bail if missing data, or league.
			if ( ! ( $data && $league ) ) {
				continue;
			}

			// Get the user's vote.
			$user_vote = sanitize_text_field( $comment->comment_content );

			// Skip if no vote.
			if ( ! $user_vote ) {
				continue;
			}

			// Maybe add to leagues.
			if ( ! in_array( $league, $leagues ) ) {
				$leagues[] = $league;
			}

			// Get karma and spreads.
			$karma          = (int) $comment->comment_karma;
			$spread_covered = null;
			$spread_used    = null;
			$score_diff     = null;
			$has_spreads    = false;

			// If bot, get spread data.
			if ( $is_bot ) {
				$spread_covered = $data['spread_covered'];
				$spread_used    = isset( $data[ $user_vote ]['spread_used'] ) ? $data[ $user_vote ]['spread_used'] : null;
				$score_diff     = isset( $data['score_diff'] ) ? $data['score_diff'] : null;
				$has_spreads    = ! is_null( $spread_covered ) && ! is_null( $spread_used ) && ! is_null( $score_diff );
			}

			// Handle win/loss/tie.
			switch ( $karma ) {
				// Win.
				case 1:
					$this->points['total_votes']++;
					$this->points["total_votes_{$league}"]++;
					$this->points['total_wins']++;
					$this->points["total_wins_{$league}"]++;

					// Get points.
					$odds_average     = $user_vote && isset( $data['odds'][ $user_vote ]['average'] ) ? $data['odds'][ $user_vote ]['average'] : null;
					$user_vote_points = ! is_null( $odds_average ) ? pm_get_odds_points( $odds_average ) : null;

					// If we have points, update them.
					if ( ! is_null( $user_vote_points ) ) {
						$this->points['total_points']           += $user_vote_points;
						$this->points["total_points_{$league}"] += $user_vote_points;
					}
				break;
				// Loss.
				case -1:
					$this->points['total_votes']++;
					$this->points["total_votes_{$league}"]++;
					$this->points['total_losses']++;
					$this->points["total_losses_{$league}"]++;
				break;
				// Tie.
				case 2:
					$this->points['total_votes']++;
					$this->points["total_votes_{$league}"]++;
					$this->points['total_ties']++;
					$this->points["total_ties_{$league}"]++;
				break;
			}

			// If bot, and we have all spread data.
			if ( $is_bot && $has_spreads ) {
				// Increment total spreads votes.
				$this->points['total_votes_spread']++;
				$this->points["total_votes_spread_{$league}"]++;

				// If the spread was a tie.
				if ( $score_diff === $spread_used ) {
					$this->points['total_ties_spread']++;
					$this->points["total_ties_spread_{$league}"]++;
				}
				// If the spread was predicted to be covered.
				elseif ( $spread_covered ) {
					if ( $score_diff > $spread_used ) {
						$this->points['total_wins_spread']++;
						$this->points["total_wins_spread_{$league}"]++;
					} else {
						$this->points['total_losses_spread']++;
						$this->points["total_losses_spread_{$league}"]++;
					}
				}
				// Spread was not predicted to be covered.
				else {
					if ( $score_diff < $spread_used ) {
						$this->points['total_wins_spread']++;
						$this->points["total_wins_spread_{$league}"]++;
					} else {
						$this->points['total_losses_spread']++;
						$this->points["total_losses_spread_{$league}"]++;
					}
				}
			}
		}

		// Calculate win percent.
		if ( $this->points['total_wins'] && $this->points['total_votes'] ) {
			$win_percent = $this->points['total_wins'] / $this->points['total_votes'] * 100;
			$win_percent = round( $win_percent, 2 );                                          // Round to 2 decimal places.

			// Set win percents.
			$this->win_percent['win_percent'] = $win_percent;
		}

		// If bot, calculate win percent spread.
		if ( $is_bot && $this->points['total_wins_spread'] && $this->points['total_votes_spread'] ) {
			$win_percent_spread                      = $this->points['total_wins_spread'] / $this->points['total_votes_spread'] * 100;
			$win_percent_spread                      = round( $win_percent_spread, 2 );  // Round to 2 decimal places.
			$this->win_percent['win_percent_spread'] = $win_percent_spread;
		}

		// Loop through leagues and calculate win percent.
		foreach ( $leagues as $league ) {
			// If total votes is greater than 0.
			if ( $this->points["total_votes_{$league}"] > 0 ) {
				// Calculate win percent.
				$league_win_percent = $this->points["total_wins_{$league}"] / $this->points["total_votes_{$league}"] * 100;
				$league_win_percent = round( $league_win_percent, 2 ); // Round to 2 decimal places.

				// Set win percent.
				$this->win_percent["win_percent_{$league}"] = $league_win_percent;
			}

			// If bot and total votes spread is greater than 0.
			if ( $is_bot && $this->points["total_votes_spread_{$league}"] > 0 ) {
				// Calculate win percent.
				$league_win_percent_spread = $this->points["total_wins_spread_{$league}"] / $this->points["total_votes_spread_{$league}"] * 100;
				$league_win_percent_spread = round( $league_win_percent_spread, 2 ); // Round to 2 decimal places.

				// Set win percent.
				$this->win_percent["win_percent_spread_{$league}"] = $league_win_percent_spread;
			}
		}

		// Skipping all time confidence until we can figure out a formula.
		// Get confidence.
		// $this->confidence['confidence'] = $this->get_confidence( $total_votes['all'], $req_min['all'] );

		// Loop through leagues and get confidence.
		foreach ( $leagues as $league ) {
			// Set confidence.
			$this->confidence[ "confidence_{$league}" ] = $this->get_confidence( $this->points["total_votes_{$league}"], $req_min[ $league ] );
		}

		// Get XP. Total points x confidence.
		// Skipping all time xp until we can figure out a formula.
		// And cause we don't have confidence cause that is skipped too.
		// $xp = [
		// 	'xp_points' => $this->get_xp( $this->points['total_points'], $this->confidence['confidence'] ),
		// ];

		$xp = [];

		// Loop through leagues and get XP.
		foreach ( $leagues as $league ) {
			// Set XP.
			$xp[ "xp_points_{$league}" ] = $this->get_xp( $this->points[ "total_points_{$league}" ], $this->confidence[ "confidence_{$league}" ] );
		}

		// Loop through points and update them all.
		foreach ( $this->points as $key => $value ) {
			update_user_meta( $this->user->ID, $key, round( $value ) );
		}

		// Loop through win percent and update them all.
		foreach ( $this->win_percent as $key => $value ) {
			update_user_meta( $this->user->ID, $key, round( $value ) );
		}

		// Loop through confidence and update them all.
		foreach ( $this->confidence as $key => $value ) {
			update_user_meta( $this->user->ID, $key, $value );
		}

		// Loop through XP and update them all.
		foreach ( $xp as $key => $value ) {
			update_user_meta( $this->user->ID, $key, $value );
		}

		$this->return = 'User points updated for user ' . $this->user->ID . ': ' . get_the_author_meta( 'display_name', $this->user->ID );
	}

	/**
	 * Get the confidence score.
	 *
	 * @since 0.8.0
	 *
	 * @return float|int Between 0-1.
	 */
	function get_confidence( $votes, $req_min ) {
		$confidence = tanh( (2 * $votes) / $req_min );
		$confidence = round( $confidence, 4 ); // Round to 4. We used 3, but .9998 rounded up to 1.
		$confidence = substr( $confidence, 0, 5 ); // Trim the string to 5 characters.
		$confidence = pm_parse_float( $confidence );

		return $confidence;
	}

	/**
	 * Get the XP.
	 *
	 * @since 0.8.0
	 *
	 * @return float|int
	 */
	function get_xp( $points, $confidence ) {
		$xp = $points * $confidence;
		$xp = round( $xp );

		return $xp;
	}

	/**
	 * Get all point/win/loss related keys.
	 *
	 * @since 0.8.0
	 *
	 * @return array
	 */
	function get_all_keys() {
		// Get leagues.
		$leagues = pm_get_all_leagues();
		$leagues = array_map( 'strtolower', $leagues );

		// Get base keys.
		$keys = [
			'total_votes',
			'total_ties',
			'total_wins',
			'total_losses',
			'total_points',
			'total_votes_spread',
			'total_ties_spread',
			'total_wins_spread',
			'total_losses_spread',
		];

		// Loop through leagues and add keys.
		foreach ( $leagues as $league ) {
			$keys[] = "total_votes_{$league}";
			$keys[] = "total_ties_{$league}";
			$keys[] = "total_wins_{$league}";
			$keys[] = "total_losses_{$league}";
			$keys[] = "total_points_{$league}";
			$keys[] = "total_votes_spread_{$league}";
			$keys[] = "total_ties_spread_{$league}";
			$keys[] = "total_wins_spread_{$league}";
			$keys[] = "total_losses_spread_{$league}";
		}

		return $keys;
	}
}
