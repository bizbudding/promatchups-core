<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The insight content class.
 *
 * @since 1.0.0
 */
class ProMatchups_Insight_Content {
	protected $insight_id;
	protected $body;
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
	 * @param int $insight_id The matchup ID.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $insight_id ) {
		// Set properties.
		$this->insight_id = $insight_id;
		$this->body       = (array) get_post_meta( $this->insight_id, 'asknews_body', true );
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
	public static function get( $insight_id ) {
		if ( ! isset( self::$cache[ $insight_id ] ) ) {
			self::$cache[ $insight_id ] = new self( $insight_id );
		}

		return self::$cache[ $insight_id ]->content;
	}

	/**
	 * Set the content.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_content() {
		$this->set_title();
		$this->set_forecast();
		$this->set_reasoning();
		$this->set_score_rationale();
		$this->set_reconciled_information();
		$this->set_unique_prediction();
		$this->set_unique_information();
		$this->set_interesting_stat();
		$this->set_spread_insights();
		$this->set_takeaways();
		$this->set_fantasy_tip();
		$this->set_key_people();
		$this->set_injuries();
		$this->set_timeline();
		$this->set_sources();
		$this->set_search_results();
	}

	/**
	 * Set descriptive title.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_title() {
		$this->content['title'] = (string) pm_get_key( 'descriptive_title', $this->body );
	}

	/**
	 * Set the forecast.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_forecast() {
		$this->content['forecast'] = (string) pm_get_key( 'forecast', $this->body );
	}

	/**
	 * Set the reasoning.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_reasoning() {
		$this->content['reasoning'] = (string) pm_get_key( 'reasoning', $this->body );
	}

	/**
	 * Set the high or low score.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_score_rationale() {
		$this->content['score_rationale'] = (string) pm_get_key( 'high_or_low_score', $this->body );
	}

	/**
	 * Set the reconciled information.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_reconciled_information() {
		$this->content['reconciled_information'] = (string) pm_get_key( 'reconciled_information', $this->body );
	}

	/**
	 * Set the unique prediction.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_unique_prediction() {
		$this->content['unique_prediction'] = (string) pm_get_key( 'unique_prediction', $this->body );
	}

	/**
	 * Set the unique information.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_unique_information() {
		$this->content['unique_information'] = (string) pm_get_key( 'unique_information', $this->body );
	}

	/**
	 * Set the interesting statistic.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_interesting_stat() {
		$this->content['interesting_stat'] = (string) pm_get_key( 'interesting_statistic', $this->body );
	}

	/**
	 * Set the spread insights.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_spread_insights() {
		$this->content['spread_insights'] = array_filter([
			(string) pm_get_key( 'spread_evaluation', $this->body ),
			(string) pm_get_key( 'spread_covered_discussion', $this->body ),
			(string) pm_get_key( 'spread_not_covered_discussion', $this->body ),
		]);
	}

	/**
	 * Set the key takeaways.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_takeaways() {
		$this->content['takeaways'] = (array) pm_get_key( 'key_facets', $this->body );
	}

	/**
	 * Set the fantasy tip.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_fantasy_tip() {
		$this->content['fantasy_tip'] = (string) pm_get_key( 'fantasy_tip', $this->body );
	}

	/**
	 * Set the key people.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_key_people() {
		$this->content['key_people'] = (array) pm_get_key( 'key_people', $this->body );
	}

	/**
	 * Set injuries.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_injuries() {
		$this->content['injuries'] = (array) pm_get_key( 'relevant_injuries', $this->body );
	}

	/**
	 * Set timeline.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_timeline() {
		$this->content['timeline'] = (array) pm_get_key( 'timeline', $this->body );
	}

	/**
	 * Set sources.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_sources() {
		$return  = [];
		$sources = (array) pm_get_key( 'sources', $this->body );

		foreach ( $sources as $source ) {
			$return[] = $this->get_source( $source );
		}

		$this->content['sources'] = $return;
	}

	/**
	 * Set search results.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function set_search_results() {
		$return  = [];
		$results = (array) pm_get_key( 'web_search_results', $this->body );
		$results = $results ?: (array) pm_get_key( 'web_seach_results', $this->body ); // Temp fix for mispelled.

		// Remove bad results.
		foreach ( $results as $result ) {
			$title = pm_get_key( 'title', $result );

			// Skip if not valid.
			if ( in_array( $title, [ '404', 'reCAPTCHA', 'Unusual Traffic Detection' ] ) ) {
				continue;
			}

			$return[] = $this->get_search_result( $result );
		}

		$this->content['search_results'] = $return;
	}

	/**
	 * Get the source data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $source The source data.
	 *
	 * @return array
	 */
	function get_source( $source ) {
		$url        = pm_get_key( 'article_url_final', $source );
		$url        = $url ?: pm_get_key( 'article_url', $source );
		$host       = pm_get_key( 'domain_url', $source );
		$name       = pm_get_key( 'source_id', $source );
		$parsed_url = wp_parse_url( $url );
		$base_url   = $parsed_url['scheme'] . '://' . $parsed_url['host'];
		$host       = $name ?: $parsed_url['host'];
		$host       = str_replace( 'www.', '', $host );
		$host       = $host ? 'mlb.com' === strtolower( $host ) ? 'MLB.com' : $host : '';
		$host       = $host ? sprintf( '<a class="entry-title-link" href="%s" target="_blank" rel="nofollow">%s</a>', $url, $host ) : '';
		$date       = pm_get_key( 'pub_date', $source );
		$date       = $date ? wp_date( get_option( 'date_format' ), strtotime( $date ) ) : '';
		$title      = pm_get_key( 'eng_title', $source );
		$image_id   = pm_get_key( 'image_id', $source );
		$image_id   = $image_id ?: 4078;
		$image_url  = $image_id && ! is_wp_error( $image_id ) ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		$summary    = pm_get_key( 'summary', $source );
		$meta       = [ trim( $date ), trim( $title ) ];
		$meta       = implode( ' &ndash; ', array_filter( $meta ) );
		$entities   = pm_get_key( 'entities', $source );
		$people     = pm_get_key( 'Person', (array) $entities );

		// Set data.
		$data = [
			'image_url' => $image_url,
			'title'     => $title,
			'host'      => $host,
			'meta'      => $meta,
			'summary'   => $summary,
			'people'    => $people,
		];

		return $data;
	}

	/**
	 * Get the search result data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $result The search result data.
	 *
	 * @return array
	 */
	function get_search_result( $result ) {
		$url        = pm_get_key( 'url', $result );
		$name       = pm_get_key( 'source', $result );
		$name       = 'unknown' === strtolower( $name ) ? '' : $name;
		$parsed_url = wp_parse_url( $url );
		$host       = $name ?: (isset( $parsed_url['host'] ) ? $parsed_url['host'] : '');
		$host       = str_replace( 'www.', '', $host );
		$host       = $host ? 'mlb.com' === strtolower( $host ) ? 'MLB.com' : $host : '';
		$host       = $host ? sprintf( '<a class="entry-title-link" href="%s" target="_blank" rel="nofollow">%s</a>', $url, $host ) : '';
		$title      = pm_get_key( 'title', $result );
		$date       = pm_get_key( 'published', $result );
		$date       = $date ? wp_date( get_option( 'date_format' ), strtotime( $date ) ) : '';
		$meta       = [ trim( $date ), trim( $title ) ];
		$meta       = implode( ' &ndash; ', array_filter( $meta ) );
		$points     = (array) pm_get_key( 'key_points', $result );

		// Set data.
		$data = [
			'title'  => $title,
			'meta'   => $meta,
			'points' => $points,
		];

		return $data;
	}
}
