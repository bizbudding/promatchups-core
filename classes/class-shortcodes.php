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
		$datatable = new ProMatchups_Datatable( $atts );

		return $datatable->get();
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