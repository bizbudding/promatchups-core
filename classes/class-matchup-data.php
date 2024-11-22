<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The matchup data class.
 *
 * @since 1.0.0
 */
class ProMatchups_Matchup_Data {
	protected $matchup_id;
	protected $insight_id;
	protected $outcome;
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
	 * @param int $matchup_id
	 *
	 * @since 1.0.0
	 */
	public function __construct( $matchup_id ) {
		// Set properties.
		$this->matchup_id = $matchup_id;
		$this->insight_id = pm_get_insight_id( $this->matchup_id );
		$this->outcome    = (array) get_post_meta( $this->matchup_id, 'asknews_outcome', true );

		// If we have an insight ID.
		if ( $this->insight_id ) {
			// Get the insight data.
			$insight_data = ProMatchups_Insight_Data::get( $this->insight_id );

			// Start with the insight data.
			$this->data = $insight_data;
		}
		// No insight ID.
		else {
			$this->data = [];
		}

		// Set the matchup data.
		$this->set_data();
	}

	/**
	 * Static method to get the data for a specific ID.
	 * Utilizes caching to prevent redundant processing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $matchup_id
	 *
	 * @return array
	 */
	public static function get( $matchup_id ) {
		if ( ! isset( self::$cache[ $matchup_id ] ) ) {
			self::$cache[ $matchup_id ] = new self( $matchup_id );
		}

		return self::$cache[ $matchup_id ]->data;
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
		$this->set_id();
		$this->set_date();
		$this->set_league();
		$this->set_outcome();

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
	}

	/**
	 * Set the matchup ID.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_id() {
		$this->data['matchup_id'] = $this->matchup_id;
	}

	/**
	 * Set the matchup dates.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function set_date() {
		$this->data['date'] = get_post_meta( $this->matchup_id, 'event_date', true ) ?: null;
	}

	/**
	 * Set the matchup league.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_league() {
		// Bail if we already have a league.
		if ( $this->data['league'] ) {
			return;
		}

		// Check for league terms as fallback.
		$terms = get_the_terms( $this->matchup_id, 'league' );
		$terms = $terms && ! is_wp_error( $terms ) ? $terms : [];
		$top   = $terms ? array_filter( $terms, function( $term ) { return 0 === $term->parent; }) : [];
		$term  = $top ? reset( $top ) : reset( $terms );

		// If a WP_Term object.
		if ( $term && is_a( $term, 'WP_Term' ) ) {
			$term = $term->parent ? get_term( $term->parent, 'league' ) : $term;
		}

		$this->data['league'] = $term && is_a( $term, 'WP_Term' ) ? $term->name : '';
	}

	/**
	 * Set the matchup outcome.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_outcome() {
		// Winner.
		$this->data['winner_team']  = isset( $this->outcome['winner']['team'] ) ? $this->outcome['winner']['team'] : '';
		$this->data['winner_short'] = $this->data['winner_team'] ? pm_get_team_short_name( $this->data['winner_team'], $this->data['league'] ) : '';
		$this->data['winner_score'] = isset( $this->outcome['winner']['score'] ) ? (int) $this->outcome['winner']['score'] : null;

		// Loser.
		$this->data['loser_team']   = isset( $this->outcome['loser']['team'] ) ? $this->outcome['loser']['team'] : '';
		$this->data['loser_short']  = $this->data['loser_team'] ? pm_get_team_short_name( $this->data['loser_team'], $this->data['league'] ) : '';
		$this->data['loser_score']  = isset( $this->outcome['loser']['score'] ) ? (int) $this->outcome['loser']['score'] : null;

		// Other.
		$this->data['winner_home']  = $this->data['winner_team'] && $this->data['loser_team'] ? $this->data['winner_team'] === $this->data['home_team'] : null;
		$this->data['away_score']   = $this->data['winner_team'] && $this->data['loser_team'] ? $this->data['winner_team'] === $this->data['away_team'] ? $this->data['winner_score'] : $this->data['loser_score'] : null;
		$this->data['home_score']   = $this->data['winner_team'] && $this->data['loser_team'] ? $this->data['winner_team'] === $this->data['home_team'] ? $this->data['winner_score'] : $this->data['loser_score'] : null;
		$this->data['score_diff']   = ! is_null( $this->data['winner_score'] ) && ! is_null( $this->data['loser_score'] ) ? $this->data['winner_score'] - $this->data['loser_score'] : null;

		// Conditionals.
		$this->data['has_winner']   = $this->data['winner_team'] ? true : false;
		$this->data['has_loser']    = $this->data['loser_team'] ? true : false;
		$this->data['has_score']    = $this->data['winner_score'] && $this->data['loser_score'] ? true : false;
		$this->data['has_outcome']  = $this->data['has_winner'] && $this->data['has_loser'] && $this->data['has_score'] ? true : false;

		// Bot result.
		$this->data['spread_result']    = pm_get_spread_result( $this->data['prediction'], $this->data['spread_covered'], $this->data );
		$this->data['moneyline_result'] = pm_get_moneyline_result( $this->data['prediction'], $this->data );
	}
}