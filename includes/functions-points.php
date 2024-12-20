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
 * Count the number of floats in an array.
 *
 * @since 0.13.0
 *
 * @param float[] $array The array of floats.
 *
 * @return float[]
 */
function pm_array_count_floats( $array ) {
	$counts = [];

	foreach( $array as $value ) {
		// Initialize the count if it doesn't exist.
		if ( ! isset( $counts[ $value ] ) ) {
			$counts[ $value ] = 0;
		}

		// Increment the count.
		$counts[$value]++;
	}

	return $counts;
}