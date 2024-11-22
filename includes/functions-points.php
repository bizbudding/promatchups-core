<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Calculate the points for a matchup.
 *
 * @since 0.8.0
 *
 * @param float $odds The odds for the team.
 *
 * @return float
 */
function pm_get_odds_points( $odds ) {
	// If favorite (negative odds).
	if ( $odds < 0 ) {
		$points = (100 / abs($odds)) * 10;
	}
	// Underdog (positive odds).
	else {
		$points = ($odds / 100) * 10;
	}

	// Return the calculated points, rounded to 2 decimal places.
	return pm_parse_float( round( $points, 2 ) );
}

/**
 * Get the average odds for a matchup.
 * Returns an array with the average odds for the away and home teams.
 * The array is ordered by the away team first.
 *
 * @since 0.8.0
 *
 * @param array $body The insight body.
 *
 * @return array
 */
function pm_get_odds_data( $body ) {
	static $odds = [];

	// Get the forecast UUID.
	$forecast_uuid = pm_get_key( 'forecast_uuid', $body );

	// Maybe return cache.
	if ( ! $forecast_uuid || isset( $odds[ $forecast_uuid ] ) ) {
		return $odds[ $forecast_uuid ];
	}

	// Get the body and odds data.
	$odds_data = pm_get_key( 'odds_info', $body );
	$odds_data = $odds_data && is_array( $odds_data ) ? $odds_data : [];
	$away_long = pm_get_key( 'away_team', $body );
	$home_long = pm_get_key( 'home_team', $body );

	// Bail if no odds data or team names.
	if ( ! ( $odds_data && $away_long && $home_long ) ) {
		$odds[ $forecast_uuid ] = [];
		return $odds[ $forecast_uuid ];
	}

	// Start the new odds and averages.
	$odds[ $forecast_uuid ] = [];

	// Loop through the odds data for each team.
	foreach ( $odds_data as $team_long => $sites ) {
		// Start the decimal sum.
		$decimal_sum = 0;

		// Convert each odd to decimal and sum them
		foreach ( $sites as $site_name => $odd ) {
			$decimal_sum += pm_american_to_decimal( (float) $odd );
		}

		// Find the average of the decimal odds
		$decimal_avg = $decimal_sum / count( $sites );

		// Convert the average decimal odds back to American odds.
		$average = round( pm_decimal_to_american( $decimal_avg ) );

		// Add to $odds array.
		$odds[ $forecast_uuid ][ $team_long ] = [
			'average' => $average,
			'odds'    => $sites,
		];
	}

	// Order by away team first.
	$odds[ $forecast_uuid ] = array_merge(
		[ $away_long => $odds[ $forecast_uuid ][ $away_long ] ],
		[ $home_long => $odds[ $forecast_uuid ][ $home_long ] ]
	);

	return $odds[ $forecast_uuid ];
}

/**
 * Get the average spread for a matchup.
 * Returns an array with the average spread for the away and home teams.
 * The array is ordered by the away team first.
 *
 * @since 0.11.0
 *
 * @param array $body The insight body.
 *
 * @return array
 */
function pm_get_spreads_data_og( $body ) {
	static $spreads = [];

	// Get the forecast UUID.
	$forecast_uuid = pm_get_key( 'forecast_uuid', $body );

	// Bail if no forecast UUID.
	if ( ! $forecast_uuid ) {
		return $spreads;
	}

	// Maybe return cache.
	if ( isset( $spreads[ $forecast_uuid ] ) ) {
		return $spreads[ $forecast_uuid ];
	}

	// Get the body and spreads data.
	$spreads_data = pm_get_key( 'spreads_info', $body );
	$spreads_data = $spreads_data && is_array( $spreads_data ) ? $spreads_data : [];
	$away_long    = pm_get_key( 'away_team', $body );
	$home_long    = pm_get_key( 'home_team', $body );

	// Bail if no spreads data or team names.
	if ( ! ( $spreads_data && $away_long && $home_long ) ) {
		$spreads[ $forecast_uuid ] = [];
		return $spreads[ $forecast_uuid ];
	}

	// Start the new spreads and averages.
	$spreads[ $forecast_uuid ] = [];

	// Loop through the spreads data for each team.
	foreach ( $spreads_data as $team_long => $sites ) {
		// Start the decimal sums.
		$odd_count          = 0;
		$odd_decimal_sum    = 0;
		$spread_count       = 0;
		$spread_decimal_sum = 0;

		// Convert each odd to decimal and sum them
		foreach ( $sites as $site_name => $data ) {
			$odd    = isset( $data[0] ) ? $data[0] : null;
			$spread = isset( $data[1] ) ? $data[1] : null;

			// If there is an odd, use it.
			if ( ! is_null( $odd ) ) {
				$odd_count++;
				$odd_decimal_sum += pm_american_to_decimal( (float) $odd );
			}

			// If there is a spread, use it.
			if ( ! is_null( $spread ) ) {
				$spread_count++;
				$spread_decimal_sum += (float) $spread;
			}
		}

		// Set values, without keys.
		$spread_values = array_values( $sites );
		$spread_values = array_column( $spread_values, 1 );

		// Find the average of the decimal spreads
		$odd_decimal_avg    = $odd_decimal_sum / $odd_count;
		$spread_decimal_avg = $spread_decimal_sum / $spread_count;

		// Convert the average decimal spreads back to American spreads.
		$odd_average    = round( pm_decimal_to_american( $odd_decimal_avg ) );
		$spread_average = round( $spread_decimal_avg, 1 );

		// Add to $spreads array.
		$spreads[ $forecast_uuid ][ $team_long ] = [
			'odds_average'    => $odd_average,
			'spread_used'    => pm_get_spreads_mode_og( $spread_values ),
			// 'spread_minmax'  => $spread_average < 0 ? min( $spread_values ) : max( $spread_values ),
			'spread_average' => $spread_average,
			'spreads'        => $sites,
		];
	}

	// Order by away team first.
	$spreads[ $forecast_uuid ] = array_merge(
		[ $away_long => $spreads[ $forecast_uuid ][ $away_long ] ],
		[ $home_long => $spreads[ $forecast_uuid ][ $home_long ] ]
	);

	return $spreads[ $forecast_uuid ];
}

/**
 * Get the spreads mode from values.
 *
 * Counts the occurrences of each value.
 * Find the highest occurrence count.
 * Get all values with the highest occurrence count (to handle multiple modes).
 * Get lowest mode if there are multiple modes.
 *
 * @since 0.13.0
 *
 * @param int[] $spread_values The spread values.
 *
 * @return int
 */
function pm_get_spreads_mode_og( $spread_values ) {
	$spread_values    = array_map( 'pm_parse_float', $spread_values );
	$spread_counts    = pm_array_count_floats( $spread_values );
	$spread_max_count = max( $spread_counts );
	$spread_modes     = array_keys( $spread_counts, $spread_max_count );
	$spread_mode      = min( $spread_modes );

	return $spread_mode;
}

/**
 * Count the number of floats in an array.
 *
 * @since 0.13.0
 *
 * @param float[] $array The array of floats.
 *
 * @return int[]
 */
function pm_array_count_floats( $array ) {
	$counts = [];

	foreach( $array as $value ) {
		// Round to 1 decimal place and trim trailing zero.
		$key = number_format( (float) $value, 1, '.', '' );
		$key = rtrim( $key, '.0' );

		// Initialize the count if it doesn't exist.
		if ( ! isset( $counts[ $key ] ) ) {
			$counts[ $key ] = 0;
		}

		// Increment the count.
		$counts[$key]++;
	}

	return $counts;
}