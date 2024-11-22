<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The matchup content class.
 *
 * @since 1.0.0
 */
class ProMatchups_Matchup_Content {
	protected $matchup_id;
	protected $insight_id;
	protected $content;

	/**
	 * Data cache for instances.
	 *
	 * @var array
	 */
	private static $cache = [];

	/**
	 * Construct the class.
	 *
	 * @param int $matchup_id The matchup ID.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $matchup_id ) {
		// Set properties.
		$this->matchup_id = $matchup_id;
		$this->insight_id = pm_get_insight_id( $this->matchup_id );
		$this->content    = [];

		// Set the content.
		$this->set_content();
	}

	/**
	 * Static method to get the content for a specific ID.
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

		return self::$cache[ $matchup_id ]->content;
	}

	/**
	 * Set the content.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_content() {
		$this->content = ProMatchups_Insight_Content::get( $this->insight_id );
	}
}
