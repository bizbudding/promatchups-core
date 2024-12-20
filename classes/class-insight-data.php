<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The insight data class.
 *
 * @since 1.0.0
 */
class ProMatchups_Insight_Data {
	protected $insight_id;
	protected $body;
	protected $odds;
	protected $spreads;
	protected $data;

	/**
	 * Data cache for instances.
	 *
	 * @var array
	 */
	private static $cache = [];

	/**
	 * Construct the class.
	 *
	 * @param int $insight_id
	 *
	 * @since 1.0.0
	 */
	public function __construct( $insight_id ) {
		// Set properties.
		$this->insight_id = $insight_id;
		$this->body       = (array) get_post_meta( $insight_id, 'asknews_body', true );
		$this->odds       = (array) pm_get_key( 'odds_info', $this->body );
		$this->spreads    = (array) pm_get_key( 'spreads_info', $this->body );
		$this->data       = [];

		// Set the data.
		$this->set_data();
	}

	/**
	 * Static method to get the data for a specific ID.
	 * Utilizes caching to prevent redundant processing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $insight_id
	 *
	 * @return array
	 */
	public static function get( $insight_id ) {
		if ( ! isset( self::$cache[ $insight_id ] ) ) {
			self::$cache[ $insight_id ] = new self( $insight_id );
		}

		return self::$cache[ $insight_id ]->data;
	}

	/**
	 * Set data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_data() {
		// Keep in this order. Some keys depend on others.
		$this->set_ids();
		$this->set_date();
		$this->set_league();
		$this->set_season();
		$this->set_teams();
		$this->set_favored();
		$this->set_odds();
		$this->set_spreads();
		$this->set_prediction();
		$this->set_spread_covered();
		$this->set_model();

		// TODO: Sanitize in each method?
		// Loop through and sanitize strings.
		foreach ( $this->data as $key => $value ) {
			// Skip if not a string.
			if ( is_null( $value ) || ! is_string( $value ) ) {
				continue;
			}

			// Sanitize.
			$this->data[ $key ] = pm_sanitize_string( $value );
		}

		// // If the spread is not covered, flip the probability.
		// if ( ! is_null( $this->data['spread_covered'] ) && ! $this->data['spread_covered'] ) {
		// 	// Maybe handle probability conversion.
		// 	$this->data['probability'] = max( $this->data['probability'], 100 - (int) $this->data['probability'] );
		// }
	}

	/**
	 * Set the matchup ID.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_ids() {
		$this->data['insight_id']    = $this->insight_id;
		$this->data['event_uuid']    = pm_get_key( 'event_uuid', $this->body );
		$this->data['forecast_uuid'] = pm_get_key( 'forecast_uuid', $this->body );
	}

	/**
	 * Set the matchup dates.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function set_date() {
		$date_modified               = pm_get_key( 'date', $this->body );
		$date_modified               = is_numeric( $date_modified ) ? $date_modified : strtotime( $date_modified );
		$this->data['date_modified'] = $date_modified;
	}

	/**
	 * Set the matchup league.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_league() {
		$this->data['league'] = pm_get_key( 'sport', $this->body );
	}

	/**
	 * Set the matchup season.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_season() {
		$this->data['season'] = pm_get_key( 'season', $this->body );
	}

	/**
	 * Set the matchup teams.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_teams() {
		// Get the team names.
		$home_team  = pm_get_key( 'home_team', $this->body );
		$away_team  = pm_get_key( 'away_team', $this->body );
		$home_short = pm_get_key( 'home_team_name', $this->body );
		$home_short = $home_short ?: pm_get_team_short_name( $home_team, $this->data['league'] );
		$away_short = pm_get_key( 'away_team_name', $this->body );
		$away_short = $away_short ?: pm_get_team_short_name( $away_team, $this->data['league'] );

		// Set the team names.
		$this->data['home_team']  = (string) $home_team;
		$this->data['away_team']  = (string) $away_team;
		$this->data['home_short'] = (string) $home_short;
		$this->data['away_short'] = (string) $away_short;
	}

	/**
	 * Set the favorite team by full name.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_favored() {
		// If the favored team is set in the body. This was added mid Nov 2024.
		if ( isset( $this->body['favored_team'] ) ) {
			$this->data['favored']       = $this->body['favored_team'];
			$this->data['favored_short'] = pm_get_team_short_name( $this->data['favored'], $this->data['league'] );
			return;
		}

		// Bail if no odds data.
		if ( ! $this->odds ) {
			$this->data['favored']       = null;
			$this->data['favored_short'] = null;
			return;
		}

		// Set vars.
		$favorite = null;
		$highest  = 0;

		// Loop through each team in the odds information.
		foreach ( $this->odds as $team_full => $bookmakers ) {
			$sum   = 0;
			$count = 0;

			// Calculate implied probability for each bookmaker's odds.
			foreach ( (array) $bookmakers as $odds ) {
				$odds = (float) $odds;

				// If the odds are negative.
				if ( $odds < 0 ) {
					$sum += (-$odds) / (-$odds + 100);
				}
				// The odds are positive.
				else {
					$sum += 100 / ($odds + 100);
				}

				// Increment the count.
				$count++;
			}

			// Skip if no sum or count.
			if ( ! $sum || ! $count ) {
				continue;
			}

			// Calculate the average implied probability for the team.
			$average = $sum  / $count;

			// If this team has the highest average probability so far.
			if ( $average > $highest ) {
				$highest  = $average;
				$favorite = $team_full;
			}
		}

		$this->data['favored']       = $favorite;
		$this->data['favored_short'] = pm_get_team_short_name( $favorite, $this->data['league'] );
	}

	/**
	 * Set the odds data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_odds() {
		// Bail if no odds data or team names.
		if ( ! ( $this->odds && $this->data['away_team'] && $this->data['home_team'] ) ) {
			$this->data['odds'] = null;
			return;
		}

		// Set var.
		$values = [];

		// Loop through the odds data for each team.
		foreach ( $this->odds as $team_full => $bookmakers ) {
			// Validate team.
			if ( ! in_array( $team_full, [ $this->data['away_team'], $this->data['home_team'] ] ) ) {
				continue;
			}

			// Start the decimal sum.
			$sum = 0;

			// Convert each odd to decimal and sum them.
			foreach ( $bookmakers as $bookmaker => $odds ) {
				$sum += $this->get_american_to_decimal( $odds );
			}

			// Find the average of the decimal odds.
			$average = $sum / count( $bookmakers );

			// Set values, and convert the average decimal odds back to American odds.
			$values[ $team_full ]            = [];
			$values[ $team_full ]['average'] = round( $this->get_decimal_to_american( $average ) );
			$values[ $team_full ]['odds']    = $bookmakers;
		}

		// Final values.
		$odds  = [];
		$teams = [ $this->data['away_team'], $this->data['home_team'] ];
		$keys  = [
			'average',
			'odds',
		];

		// Loop through and set odds.
		foreach ( $teams as $team ) {
			$odds[ $team ] = [];

			foreach ( $keys as $key ) {
				$odds[ $team ][ $key ] = isset( $values[ $team ][ $key ] ) ? $values[ $team ][ $key ] : null;
			}
		}

		// Set odds.
		$this->data['odds'] = $odds;
	}

	/**
	 * Set the spreads.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_spreads() {
		// Bail if no spreads data or team names.
		if ( ! ( $this->spreads && $this->data['away_team'] && $this->data['home_team'] ) ) {
			$this->data['spreads'] = null;
			return;
		}

		// Set var.
		$values = [];

		// TODO: Add spread_juice to the data array.

		// Loop through the spreads data for each team.
		foreach ( $this->spreads as $team_full => $bookmakers ) {
			// Validate team.
			if ( ! in_array( $team_full, [ $this->data['away_team'], $this->data['home_team'] ] ) ) {
				continue;
			}

			$odd_count    = 0;
			$odd_sum      = 0;
			$spread_count = 0;
			$spread_sum   = 0;

			// Convert each odd to decimal and sum them
			foreach ( $bookmakers as $bookmaker => $book_values ) {
				$odds   = isset( $book_values[0] ) ? $book_values[0] : null;
				$spread = isset( $book_values[1] ) ? $book_values[1] : null;

				// If there are odds, use it.
				if ( ! is_null( $odds ) ) {
					$odd_count++;
					$odd_sum += $this->get_american_to_decimal( (float) $odds );
				}

				// If there is a spread, use it.
				if ( ! is_null( $spread ) ) {
					$spread_count++;
					$spread_sum += (float) $spread;
				}
			}

			// Set values without keys.
			$spread_values = array_values( $bookmakers );

			// Get juice value.
			$juice_values = array_column( $spread_values, 0 );
			$juice_used   = $this->get_mode( $juice_values );

			// Get spread used.
			if ( isset( $this->data['spread_used'] ) ) {
				$spread_used = $this->data['spread_used'];
			}
			// No spread_used value, fall back to spread mode.
			else {
				$spread_values = array_column( $spread_values, 1 );
				$spread_used   = $this->get_mode( $spread_values );
			}

			// Find the averages.
			$odds_avg    = $odd_sum / $odd_count;
			$spreads_avg = $spread_sum / $spread_count;

			// Convert the average decimal spreads back to American spreads.
			$odds_average   = round( $this->get_decimal_to_american( $odds_avg ) );
			$spread_average = round( $spreads_avg, 1 );

			// Add to $spreads array.
			$values[ $team_full ] = [
				'odds_average'   => ! is_null( $odds_average ) ? (float) $odds_average : null,
				'spread_juice'   => ! is_null( $juice_used ) ? (float) $juice_used : null,
				'spread_used'    => ! is_null( $spread_used ) ? (float) $spread_used : null,
				'spread_average' => ! is_null( $spread_average ) ? (float) $spread_average : null,
				'spreads'        => $bookmakers,
			];
		}

		// Teams and keys.
		$spreads = [];
		$teams   = [ $this->data['away_team'], $this->data['home_team'] ];
		$keys    = [
			'odds_average',
			'spread_juice',
			'spread_used',
			'spread_average',
			'spreads',
		];

		// Loop through and set spreads.
		foreach ( $teams as $team ) {
			$spreads[ $team ] = [];

			foreach ( $keys as $key ) {
				$spreads[ $team ][ $key ] = isset( $values[ $team ][ $key ] ) ? $values[ $team ][ $key ] : null;
			}
		}

		// Set spreads.
		$this->data['spreads'] = $spreads;
	}

	/**
	 * Set the prediction.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_prediction() {
		$this->data['prediction']       = pm_get_key( 'choice', $this->body );
		$this->data['prediction_short'] = pm_get_team_short_name( $this->data['prediction'], $this->data['league'] );
		$this->data['predicted_score']  = $this->get_predicted_score();
		$this->data['likelihood']       = pm_get_key( 'likelihood', $this->body );
		$this->data['probability']      = pm_get_key( 'probability', $this->body );
	}

	/**
	 * Set the spread covered.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_spread_covered() {
		// If the spread_used is set in the body. This was added mid Nov 2024 when forecasts switched from moneyline to spread.
		// At some point prior to this, the spread_covered was calculated with average and not mode, so we'll use our own calculation.
		if ( isset( $this->body['spread_used'] ) && isset( $this->body['spread_covered'] ) ) {
			$this->data['spread_covered'] = $this->body['spread_covered'];
			return;
		}

		// Bail if no prediction or predicted score.
		if ( is_null( $this->data['prediction'] || is_null( $this->data['predicted_score'] ) ) ) {
			if ( isset( $this->body['spread_covered'] ) ) {
				$this->data['spread_covered'] = $this->body['spread_covered'];
			} else {
				$this->data['spread_covered'] = null;
			}
			return;
		}

		// Bail if no spread used.
		if ( ! isset( $this->data['spreads'][ $this->data['prediction'] ]['spread_used'] ) || is_null( $this->data['spreads'][ $this->data['prediction'] ]['spread_used'] ) ) {
			$this->data['spread_covered'] = null;
			return;
		}

		// Set vars.
		$predicted_winner_spread = (float) $this->data['spreads'][ $this->data['prediction'] ]['spread_used'];
		$predicted_winner_score  = (float) $this->data['predicted_score'][0];
		$predicted_loser_score   = (float) $this->data['predicted_score'][1];

		// Calculate the spread covered.
		$spread_covered = (($predicted_winner_score + $predicted_winner_spread) - $predicted_loser_score);

		// Set the spread covered.
		$this->data['spread_covered'] = (bool) $spread_covered > 0;
	}

	/**
	 * Set the model used.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_model() {
		$this->data['model_used'] = pm_get_key( 'model_used', $this->body );
	}

	/**
	 * Convert American odds to decimal.
	 *
	 * @access private
	 *
	 * @since 0.1.0
	 * @since 1.0.0 Moved to data class.
	 *
	 * @param float $value
	 *
	 * @return float
	 */
	function get_american_to_decimal( float $value ) {
		// If the odds are positive.
		if ( $value > 0 ) {
			return 1 + ( $value / 100 );
		}

		// The odds are negative.
		return 1 + ( 100 / abs( $value ) );
	}

	/**
	 * Convert decimal odds to American odds.
	 *
	 * @access private
	 *
	 * @since 0.1.0
	 * @since 1.0.0 Moved to data class.
	 *
	 * @param float $value
	 *
	 * @return float
	 */
	function get_decimal_to_american( float $value ) {
		// If the decimal odds are 2.00 or greater.
		if ( $value >= 2 ) {
			return ( $value - 1 ) * 100;
		}

		// The decimal odds are less than 2.00.
		return -100 / ( $value - 1 );
	}

	/**
	 * Get the mode from values.
	 *
	 * Counts the occurrences of each value.
	 * Find the highest occurrence count.
	 * Get all values with the highest occurrence count (to handle multiple modes).
	 * Get lowest mode if there are multiple modes.
	 *
	 * @since 0.13.0
	 * @since 1.0.0 Moved to data class.
	 *
	 * @param float[] $spread_values The spread values.
	 *
	 * @return float
	 */
	function get_mode( array $spread_values ) {
		$spread_values    = array_map( 'pm_parse_float', $spread_values );
		$spread_counts    = pm_array_count_floats( $spread_values );
		$spread_max_count = max( $spread_counts );
		$spread_modes     = array_keys( $spread_counts, $spread_max_count );
		$spread_mode      = min( $spread_modes );

		return $spread_mode;
	}

	/**
	 * Get the predicted score, making sure the winning team is first.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function get_predicted_score() {
		$predicted_score  = (array) pm_get_key( 'final_score', $this->body );
		$predicted_score  = array_column( $predicted_score, 'score' );
		rsort( $predicted_score );

		return 2 == count( $predicted_score ) ? $predicted_score : null;
	}
}