<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Enqueue the plugin styles.
 *
 * @since 0.1.0
 *
 * @return void
 */
function pm_enqueue_styles() {
	wp_enqueue_style( 'promatchups', pm_get_file_url( 'promatchups', 'css' ), [], pm_get_file_version( 'promatchups', 'css' ) );
}

/**
 * Enqueue the plugin scripts.
 *
 * @since 0.8.0
 *
 * @param string $selected The selected team.
 *
 * @return void
 */
function pm_enqueue_scripts( $selected ) {
	// Enqueue JS.
	wp_enqueue_script( 'promatchups-vote', pm_get_file_url( 'promatchups-vote', 'js' ), [], pm_get_file_version( 'promatchups-vote', 'js' ), true );
	wp_localize_script( 'promatchups-vote', 'maiAskNewsVars', [
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'selected' => $selected,
	] );
}

/**
 * Gets the updated date.
 *
 * @since 0.1.0
 *
 * @return string
 */
function pm_get_updated_date() {
	// Set vars.
	$updated = '';
	$data    = pm_get_matchup_data( get_the_ID() );
	$date    = $data['date_modified'];

	// Bail if no date.
	if ( ! $date ) {
		return $updated;
	}

	// Get the date.
	$time_utc     = new DateTime( "@$date", new DateTimeZone( 'UTC' ) );
	$time_now     = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
	$interval_est = $time_now->setTimezone( new DateTimeZone( 'America/New_York' ) )->diff( $time_utc->setTimezone( new DateTimeZone( 'America/New_York' ) ) );
	$interval_pst = $time_now->setTimezone( new DateTimeZone( 'America/Los_Angeles' ) )->diff( $time_utc->setTimezone( new DateTimeZone( 'America/Los_Angeles' ) ) );

	// If within our range.
	if ( $interval_est->days < 2 || $interval_pst->days < 2 ) {
		if ( $interval_est->days > 0 ) {
			$time_ago_est = $interval_est->days . ' day' . ( $interval_est->days > 1 ? 's' : '' ) . ' ago';
		} elseif ( $interval_est->h > 0 ) {
			$time_ago_est = $interval_est->h . ' hour' . ( $interval_est->h > 1 ? 's' : '' ) . ' ago';
		} elseif ( $interval_est->i > 0 ) {
			$time_ago_est = $interval_est->i . ' minute' . ( $interval_est->i > 1 ? 's' : '' ) . ' ago';
		} else {
			$time_ago_est = __( 'Just now', 'promatchups' );
		}

		if ( $interval_pst->days > 0 ) {
			$time_ago_pst = $interval_pst->days . ' day' . ( $interval_pst->days > 1 ? 's' : '' ) . ' ago';
		} elseif ( $interval_pst->h > 0 ) {
			$time_ago_pst = $interval_pst->h . ' hour' . ( $interval_pst->h > 1 ? 's' : '' ) . ' ago';
		} elseif ( $interval_pst->i > 0 ) {
			$time_ago_pst = $interval_pst->i . ' minute' . ( $interval_pst->i > 1 ? 's' : '' ) . ' ago';
		} else {
			$time_ago_pst = __( 'Just now', 'promatchups' );
		}

		$updated = sprintf( '<span data-timezone="ET">%s</span><span data-timezonesep> | </span><span data-timezone="PT">%s</span>', $time_ago_est, $time_ago_pst );
	}
	// Older than our range.
	else {
		$date     = $time_utc->setTimezone( new DateTimeZone('America/New_York'))->format( 'M j, Y' );
		$time_est = $time_utc->setTimezone( new DateTimeZone( 'America/New_York' ) )->format( 'g:i a' ) . ' ET';
		$time_pst = $time_utc->setTimezone( new DateTimeZone( 'America/Los_Angeles' ) )->format( 'g:i a' ) . ' PT';
		$updated  = sprintf( '%s @ <span data-timezone="ET">%s</span><span data-timezonesep> | </span><span data-timezone="PT">%s</span>', $date, $time_est, $time_pst );
	}

	// Display the update.
	return sprintf( '<p class="pm-update">%s %s</p>', __( 'Updated', 'promatchups' ), $updated );
}


/**
 * Get the matchup date and time.
 *
 * @since 0.1.0
 *
 * @param int $matchup_id The matchup ID.
 *
 * @return void
 */
function pm_get_matchup_datetime( $matchup_id, $before = '' ) {
	$event_date = get_post_meta( $matchup_id, 'event_date', true );

	// Bail if no date.
	if ( ! $event_date ) {
		return '';
	}

	// Force timestamp.
	if ( ! is_numeric( $event_date ) ) {
		$event_date = strtotime( $event_date );
	}

	// Get the date and times.
	$time_utc = new DateTime( "@$event_date", new DateTimeZone( 'UTC' ) );
	$day_est  = $time_utc->setTimezone( new DateTimeZone( 'America/New_York' ) )->format( 'l, M j, Y' );
	$time_est = $time_utc->setTimezone( new DateTimeZone( 'America/New_York' ) )->format( 'g:i a' ) . ' ET';
	$time_pst = $time_utc->setTimezone( new DateTimeZone( 'America/Los_Angeles' ) )->format( 'g:i a' ) . ' PT';
	$before   = $before ? sprintf( '<strong>%s</strong> ', $before ) : '';

	return sprintf( '<p class="pm-datetime">%s%s @ <span data-timezone="ET">%s</span> <span data-timezonesep>/</span> <span data-timezone="PT">%s</span></p>', $before, $day_est, $time_est, $time_pst );
}

/**
 * Get the matchup teams list.
 *
 * @since 0.1.0
 *
 * @param array $atts The shortcode attributes.
 *
 * @return string
 */
function pm_get_matchup_teams_list( $atts = [] ) {
	// Atts.
	$atts = shortcode_atts(
		[
			'before' => '',
			'after'  => '',
		],
		$atts,
		'pm_matchup_teams'
	);

	// Sanitize.
	$atts = [
		'before' => esc_html( $atts['before'] ),
		'after'  => esc_html( $atts['after'] ),
	];

	$terms = get_the_terms( get_the_ID(), 'league' );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}

	// Remove top level terms.
	$terms = array_filter( $terms, function( $term ) {
		return 0 !== $term->parent;
	} );

	// Bail if no terms.
	if ( ! $terms ) {
		return '';
	}

	// Get teams.
	$teams = pm_get_teams( 'MLB' );

	// Build the output.
	$html = '<div class="pm-matchup-teams">';
		$html .= '<ul class="pm-matchup-teams__list">';
		foreach ( $terms as $term ) {
			$code  = isset( $teams[ $term->name ]['code'] ) ? $teams[ $term->name ]['code'] : '';
			$color = isset( $teams[ $term->name ]['color'] ) ? $teams[ $term->name ]['color'] : '';

			// These class names match the archive team list, minus the team name span.
			$html .= sprintf( '<li class="pm-team"><a class="pm-team__link" href="%s" style="--team-color:%s;" data-code="%s">%s</a></li>', get_term_link( $term ), $color, $code, $term->name );
		}
		$html .= '</ul>';
	$html .= '</div>';

	return $html;
}

/**
 * Get the prediction list.
 *
 * @since 0.1.0
 *
 * @param array $data   The insight data.
 * @param bool  $hidden Whether to hide the list.
 *
 * @return array
 */
function pm_get_prediction_list( $data, $hidden = false ) {
	$list             = [];
	$league           = $data['league'];
	$prediction       = $data['prediction'];
	$prediction_short = $data['prediction_short'] ?: $prediction;
	$probability      = $data['probability'];
	$probability      = $probability ? $probability . '%' : '';
	$likelihood       = $data['likelihood'];
	$spread_covered   = $data['spread_covered'];
	// $predicted_score  = $data['predicted_score'] ? implode( '-', (array) $data['predicted_score'] ) : '';

	// If prediction.
	if ( $prediction_short ) {
		$list['choice'] = [
			'hidden'  => __( 'Members Only', 'promatchups' ),
			'visible' => sprintf( "%s win", $prediction_short ),
		];
	}

	// If we have a spread covered value.
	if ( ! is_null( $spread_covered ) && isset( $data['spreads'][ $prediction ]['spread_used'] ) ) {
		$spread = $data['spreads'][ $prediction ]['spread_used'];

		// If spread.
		if ( ! is_null( $spread ) ) {
			if ( $spread_covered ) {
				$list['spread'] = [
					'hidden'  => __( 'Members Only', 'promatchups' ),
					'visible' => sprintf( '%s cover %s', $prediction_short, $spread ),
				];
			} else {
				$list['spread'] = [
					'hidden'  => __( 'Members Only', 'promatchups' ),
					'visible' => sprintf( '%s won\'t cover %s', $prediction_short, $spread ),
				];
			}
		}
	}

	// If probability and likelihood.
	if ( $probability && $likelihood ) {
		$list['probability'] = [
			'hidden'  => __( 'Members Only', 'promatchups' ),
			'visible' => sprintf( '%s, %s', $probability, ucfirst( strtolower( $likelihood ) ) ),
		];
	}

	// // If predicted score.
	// if ( $predicted_score ) {
	// 	if ( current_user_can( 'manage_options' ) ) {
	// 		$list['score'] = [
	// 			'hidden'  => sprintf( 'Score (admins): %s', $predicted_score ),
	// 			'visible' => sprintf( 'Score (admins): %s', $predicted_score ),
	// 		];
	// 	}
	// }

	// Bail if no data.
	if ( ! array_filter( $list ) ) {
		return;
	}

	$html  = '';
	$html .= '<ul class="pm-prediction__list">';
		if ( ! is_singular( 'matchup' ) ) {
			$html .= sprintf( '<li class="pm-prediction__item label">%s</li>', __( 'Our Prediction', 'promatchups' ) );
		}

		// Loop through list.
		foreach ( $list as $class => $values ) {
			$value = $hidden ? $values['hidden'] : $values['visible'];
			$html .= sprintf( '<li class="pm-prediction__item %s">%s</li>', $class, $value );
		}
	$html .= '</ul>';

	return $html;
}

/**
 * Get the bot results.
 *
 * @since TBD
 *
 * @param array $data The matchup data.
 *
 * @return string
 */
function pm_get_botresults( $data ) {
	// Bot results.
	$map = [
		-1 => ' loss',
		1  => ' win',
		2  => ' push',
	];

	// Get the results.
	$h2h = isset( $data['moneyline_result'] ) ? $map[ $data['moneyline_result'] ] : '';
	$ats = isset( $data['spread_result'] ) ? $map[ $data['spread_result'] ] : '';

	// Build HTML.
	$html  = '';
	$html .= '<div class="pm-botresults">';
		$html .= sprintf( '<p class="pm-botresults__heading">%s</p>', __( 'Bot Results', 'promatchups' ) );
		$html .= '<span class="pm-botresults__sep">&ndash;</span>';
		$html .= sprintf( '<p class="pm-botresults__result%s">%s</p>', $h2h, __( 'H2H', 'promatchups' ) );
		$html .= sprintf( '<p class="pm-botresults__result%s">%s</p>', $ats, __( 'Spread', 'promatchups' ) );
	$html .= '</div>';

	return $html;
}

/**
 * Get the odds table
 *
 * @since 0.1.0
 *
 * @param array $data   The matchup data.
 * @param bool  $hidden Whether to obfuscate the table.
 *
 * @return string
 */
function pm_get_odds_table( $data, $hidden = false ) {
	// Get the odds data.
	$html      = '';
	$league    = $data['league'];
	$odds_data = $data['odds'];

	// If we have odds data.
	if ( ! $odds_data ) {
		return $html;
	}

	// Get home and away teams.
	list( $away_team, $home_team ) = array_keys( $odds_data );

	// Get short names.
	$away_short = $data['away_short'];
	$home_short = $data['home_short'];

	// Start the table data.
	$sites = [];

	// Loop through odds data.
	foreach ( $odds_data as $team => $data ) {
		// Merge the sites.
		$sites = array_merge( $sites, array_keys( $data['odds'] ) );
	}

	// Remove duplicates.
	$sites = array_unique( $sites );

	// Bail if no sites.
	if ( ! $sites ) {
		return $html;
	}

	// Start the odds.
	$html .= '<div class="pm-odds pm-datatable">';
		// Heading.
		$html .= sprintf( '<p id="odds" class="has-xs-margin-bottom"><strong>%s</strong></p>', __( 'Moneyline Odds', 'promatchups' ) );

		// Add a checkbox to expand/collapse the odds.
		$toggle = '<div class="pm-toggle">';
			$toggle .= '<label class="pm-toggle_label">';
				$toggle .= __( 'Show All', 'promatchups' );
				$toggle .= '<input class="pm-toggle__input" name="pm-toggle__input" type="checkbox" />';
				$toggle .= '<span class="pm-toggle__slider"></span>';
			$toggle .= '</label>';
		$toggle .= '</div>';

		// Build the table.
		$html .= '<table>';
			$html .= '<thead>';
				$html .= '<tr>';
					$html .= sprintf( '<th>%s</th>', $toggle );
					$html .= sprintf( '<th>%s</th>', $away_short );
					$html .= sprintf( '<th>%s</th>', $home_short );
				$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$html .= '<tr class="is-top">';
				$html .= sprintf( '<td class="pm-odds__average">%s</td>', __( 'Average odds', 'promatchups' ) );

				// Loop through the odds.
				foreach ( $odds_data as $team => $values ) {
					// If hidden, show N/A.
					if ( $hidden ) {
						$rounded = 'N/A';
						$html   .= sprintf( '<td class="pm-odds__average">%s</td>', $rounded );
					}
					// Otherwise, show the average.
					else {
						$rounded = round( $values['average'], 2 );
						$html   .= sprintf( '<td class="pm-odds__average">%s%s</td>', $rounded > 0 ? '+' : '', $rounded );
					}
				}
			$html .= '</tr>';

			// Start index.
			$i = 1;

			// Loop through the sites.
			foreach ( $sites as $maker ) {
				// Set class and odds.
				$class     = $i <= 3 ? 'is-top' : 'is-not-top';
				$away_odds = isset( $odds_data[ $away_team ]['odds'][ $maker ] ) ? (float) $odds_data[ $away_team ]['odds'][ $maker ] : '';
				$home_odds = isset( $odds_data[ $home_team ]['odds'][ $maker ] ) ? (float) $odds_data[ $home_team ]['odds'][ $maker ] : '';

				// If value, and it's positive, add a plus sign.
				$away_odds = $away_odds ? ( $away_odds > 0 ? '+' : '' ) . $away_odds : 'N/A';
				$home_odds = $home_odds ? ( $home_odds > 0 ? '+' : '' ) . $home_odds : 'N/A';

				// Build the row.
				$html .= sprintf( '<tr class="%s">', $class );
					$html .= sprintf( '<td>%s</td>', ucwords( $maker ) );

					// If hidden, show N/A.
					if ( $hidden ) {
						$html .= sprintf( '<td>%s</td>', __( 'N/A', 'promatchups' ) );
						$html .= sprintf( '<td>%s</td>', __( 'N/A', 'promatchups' ) );
					}
					// Otherwise, show the odds.
					else {
						$html .= sprintf( '<td>%s</td>', $away_odds );
						$html .= sprintf( '<td>%s</td>', $home_odds );
					}
				$html .= '</tr>';

				// Increment index.
				$i++;
			}

			$html .= '</tbody>';
		$html .= '</table>';
	$html .= '</div>';

	return $html;
}

/**
 * Get the spreads table
 *
 * @since 0.13.0
 *
 * @param array $data   The matchup data.
 * @param bool  $hidden Whether to obfuscate the table.
 *
 * @return string
 */
function pm_get_spreads_table( $data, $hidden = false ) {
	// Get the odds data.
	$html         = '';
	$league       = $data['league'];
	$spreads_data = $data['spreads'];

	// If we have spread data.
	if ( ! $spreads_data ) {
		return $html;
	}

	// Get home and away teams.
	list( $away_team, $home_team ) = array_keys( $spreads_data );

	// Get short names.
	$away_short = $data['away_short'];
	$home_short = $data['home_short'];

	// Start the table data.
	$sites = [];

	// Loop through spreads data.
	foreach ( $spreads_data as $team => $data ) {
		// Merge the sites.
		$sites = array_merge( $sites, array_keys( $data['spreads'] ) );
	}

	// Remove duplicates.
	$sites = array_unique( $sites );

	// Bail if no sites.
	if ( ! $sites ) {
		return $html;
	}

	// Start the spreads.
	$html .= '<div class="pm-spreads pm-datatable">';
		// Heading.
		$html .= sprintf( '<p id="spreads" class="has-xs-margin-bottom"><strong>%s</strong></p>', __( 'Point Spread Odds', 'promatchups' ) );

		// Add a checkbox to expand/collapse the spreads.
		$toggle = '<div class="pm-toggle">';
			$toggle .= '<label class="pm-toggle_label">';
				$toggle .= __( 'Show All', 'promatchups' );
				$toggle .= '<input class="pm-toggle__input" name="pm-toggle__input" type="checkbox" />';
				$toggle .= '<span class="pm-toggle__slider"></span>';
			$toggle .= '</label>';
		$toggle .= '</div>';

		// Build the table.
		$html .= '<table>';
			$html .= '<thead>';
				$html .= '<tr>';
					$html .= sprintf( '<th>%s</th>', $toggle );
					$html .= sprintf( '<th colspan="2">%s<span class="pm-spreads__th"><span>%s</span><span>%s</span></span></th>', $away_short, __( 'Odds', 'promatchups' ), __( 'Spread', 'promatchups' ) );
					$html .= sprintf( '<th colspan="2">%s<span class="pm-spreads__th"><span>%s</span><span>%s</span></span></th>', $home_short, __( 'Odds', 'promatchups' ), __( 'Spread', 'promatchups' ) );
				$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			// Start index.
			$i = 1;

			// Loop through the sites.
			foreach ( $sites as $maker ) {
				// Set class and spreads.
				$class       = $i <= 4 ? 'is-top' : 'is-not-top';
				$away_odds   = isset( $spreads_data[ $away_team ]['spreads'][ $maker ][0] ) ? (float) $spreads_data[ $away_team ]['spreads'][ $maker ][0] : '';
				$away_spread = isset( $spreads_data[ $away_team ]['spreads'][ $maker ][1] ) ? (float) $spreads_data[ $away_team ]['spreads'][ $maker ][1] : '';
				$home_odds   = isset( $spreads_data[ $home_team ]['spreads'][ $maker ][0] ) ? (float) $spreads_data[ $home_team ]['spreads'][ $maker ][0] : '';
				$home_spread = isset( $spreads_data[ $home_team ]['spreads'][ $maker ][1] ) ? (float) $spreads_data[ $home_team ]['spreads'][ $maker ][1] : '';

				// If value, and it's positive, add a plus sign.
				$away_odds   = $away_odds ? ( $away_odds > 0 ? '+' : '' ) . $away_odds : 'N/A';
				$away_spread = $away_spread ? ( $away_spread > 0 ? '+' : '' ) . $away_spread : 'N/A';
				$home_odds   = $home_odds ? ( $home_odds > 0 ? '+' : '' ) . $home_odds : 'N/A';
				$home_spread = $home_spread ? ( $home_spread > 0 ? '+' : '' ) . $home_spread : 'N/A';

				// Build the row.
				$html .= sprintf( '<tr class="%s">', $class );
					$html .= sprintf( '<td>%s</td>', ucwords( $maker ) );

					// If hidden, show N/A.
					if ( $hidden ) {
						$html .= sprintf( '<td>%s</td>', __( 'N/A', 'promatchups' ) );
						$html .= sprintf( '<td>%s</td>', __( 'N/A', 'promatchups' ) );
					}
					// Otherwise, show the spreads.
					else {
						$html .= sprintf( '<td class="pm-spreads_odd">%s</td>', $away_odds );
						$html .= sprintf( '<td class="pm-spreads_spread">%s</td>', $away_spread );
						$html .= sprintf( '<td class="pm-spreads_odd">%s</td>', $home_odds );
						$html .= sprintf( '<td class="pm-spreads_spread">%s</td>', $home_spread );
					}
				$html .= '</tr>';

				// Increment index.
				$i++;
			}

			$html .= '</tbody>';
		$html .= '</table>';
	$html .= '</div>';

	return $html;
}

/**
 * Get the teams list.
 *
 * @since 0.8.0
 *
 * @param array $args The shortcode attributes.
 *
 * @return string
 */
function pm_get_teams_list( $args = [] ) {
	// Atts.
	$args = shortcode_atts(
		[
			'league' => '',
			'before' => '',
			'after'  => '',
		],
		$args,
		'pm_teams'
	);

	// Sanitize.
	$args = [
		'league' => sanitize_text_field( $args['league'] ),
		'before' => esc_html( $args['before'] ),
		'after'  => esc_html( $args['after'] ),
	];

	// If no league, get current.
	if ( ! $args['league'] ) {
		$args['league'] = pm_get_page_league();
	}

	// Bail if no league.
	if ( ! $args['league'] ) {
		return '';
	}

	// Get the league object.
	$object = get_term_by( 'slug', strtolower( $args['league'] ), 'league' );

	// Bail if no league object.
	if ( ! $object ) {
		return '';
	}

	// Get child terms.
	$terms = get_terms(
		[
			'taxonomy'   => 'league',
			'hide_empty' => false,
			'parent'     => $object->term_id,
		]
	);

	// Bail if no terms.
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}

	// Get the teams.
	$list  = [];
	$new   = [];
	$teams = pm_get_teams( $object->name );

	// Format teams array.
	foreach( $teams as $name => $values ) {
		$new[ $values['city'] . ' ' . $name ] = [
			'name'  => $name,
			'color' => $values['color'],
			'code'  => $values['code'],
		];
	}

	// Format the list.
	foreach ( $terms as $term ) {
		// Bail if no team.
		if ( ! ( $new && isset( $new[ $term->name ] ) ) ) {
			continue;
		}

		// Add to the list.
		$list[ $new[ $term->name ]['name'] ] = [
			'color' => $new[ $term->name ]['color'],
			'code'  => $new[ $term->name ]['code'],
			'link'  => get_term_link( $term ),
		];
	}

	// Order alphabetically by key.
	ksort( $list );

	// Get the HTML.
	$html  = '';
	$html .= '<ul class="pm-teams">';
		foreach ( $list as $name => $item ) {
			// These class names match the pm_matchup_teams shortcode, minus the team name span.
			$html .= sprintf( '<li class="pm-team" style="--team-color:%s;">', $item['color'] );
				$html .= sprintf( '<a class="pm-team__link" href="%s" data-code="%s"><span class="pm-team__name">%s</span></a>', $item['link'], $item['code'], $name );
			$html .= '</li>';
		}
	$html .= '</ul>';

	return $html;
}

/**
 * Get the team name from the league/team archive.
 *
 * @since 0.8.0
 *
 * @param array $atts The shortcode attributes.
 *
 * @return string
 */
function pm_get_team_name( $atts ) {
	if ( ! is_tax( 'league' ) ) {
		return '';
	}

	// Atts.
	$atts = shortcode_atts(
		[
			'full_name' => false,
			'fallback'  => '',      // Accepts 'league'.
		],
		$atts,
		'pm_team'
	);

	// Sanitize.
	$atts = [
		'full_name' => rest_sanitize_boolean( $atts['full_name'] ),
		'fallback'  => sanitize_text_field( $atts['fallback'] ),
	];

	// Hash the args.
	$hash = md5( serialize( $atts ) );

	// Cache the results.
	static $cache = [];

	if ( isset( $cache[ $hash ] ) ) {
		return $cache[ $hash ];
	}

	// Set vars.
	$league = pm_get_page_league();
	$term   = get_queried_object();
	$name   = $term ? $term->name : '';

	// If not showing full name.
	if ( ! $atts['full_name'] ) {
		$short = pm_get_team_short_name( $name, $league );
		$name  = $short ?: $name;
	}

	// If no name and we have a fallback.
	if ( ! $name && $atts['fallback'] ) {
		// If falling back to league.
		if ( 'league' === $atts['fallback'] ) {
			$name = $league;
		}
		// Not league, use string.
		else {
			$name = $atts['fallback'];
		}
	}

	// Cache the results.
	$cache[ $hash ] = $name;

	return $cache[ $hash ];
}

/**
 * Get the short name of a team.
 *
 * @since 0.8.0
 *
 * @param string $team  The team name.
 * @param string $sport The sport.
 *
 * @return string
 */
function pm_get_team_short_name( $team, $sport ) {
	static $cache = [];

	if ( $cache && isset( $cache[ $sport ][ $team ] ) ) {
		return $cache[ $sport ][ $team ];
	}

	$teams = pm_get_teams( $sport );

	foreach( $teams as $name => $values ) {
		if ( ! isset( $values['city'] ) ) {
			continue;
		}

		$cache[ $sport ][ $values['city'] . ' ' . $name ] = $name;
	}

	return isset( $cache[ $sport ][ $team ] ) ? $cache[ $sport ][ $team ] : $team;
}

/**
 * Displays breadcrumbs if not hidden.
 *
 * @since 0.1.0
 *
 * @return void
 */
function pm_do_breadcrumbs() {
	if ( mai_is_element_hidden( 'breadcrumbs' ) ) {
		return;
	}

	$is_league   = is_tax( 'league' );
	$is_season   = is_tax( 'season' );
	$is_singular = is_singular( 'matchup' );

	if ( ! ( $is_league || $is_season || $is_singular ) ) {
		return;
	}

	// Archive.
	// <div class="breadcrumb" itemscope="" itemtype="https://schema.org/BreadcrumbList">
	// 	<span class="breadcrumb-link-wrap" itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem">
	// 		<a class="breadcrumb-link" href="https://promatchups.local/" itemprop="item">
	// 			<span class="breadcrumb-link-text-wrap" itemprop="name">Home</span>
	// 		</a>
	// 		<meta itemprop="position" content="1">
	// 	</span>
	// 	<span aria-label="breadcrumb separator">/</span>
	// 	Archives for MLB
	// </div>

	// Singular.
	// <div class="breadcrumb" itemprop="breadcrumb" itemscope="" itemtype="https://schema.org/BreadcrumbList">
	// 	<span class="breadcrumb-link-wrap" itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem">
	// 		<a class="breadcrumb-link" href="https://promatchups.local/" itemprop="item">
	// 			<span class="breadcrumb-link-text-wrap" itemprop="name">Home</span>
	// 		</a>
	// 		<meta itemprop="position" content="1">
	// 	</span>
	// 	<span aria-label="breadcrumb separator">/</span>
	// 	Matchups
	// 	<span aria-label="breadcrumb separator">/</span>
	// 	Orioles vs Nationals
	// </div>

	// Get the global query.
	global $wp_query;

	// Set vars.
	$separator  = '/';
	$breadcumbs = [
		[
			'url'  => home_url(),
			'text' => __( 'Home', 'promatchups' ),
		],
	];

	// If league/team.
	if ( $is_league ) {
		// Get taxonomy.
		$taxonomy = isset( $wp_query->query_vars['taxonomy'] ) ? $wp_query->query_vars['taxonomy'] : '';

		// If not league or season, bail.
		if ( ! in_array( $taxonomy, [ 'league', 'season' ] ) ) {
			return;
		}

		// Get term.
		$slug = isset( $wp_query->query_vars['term'] ) ? $wp_query->query_vars['term'] : '';
		$term = $slug ? get_term_by( 'slug', $slug, $taxonomy ) : '';

		// Get parent term.
		$parent      = $term && $term->parent ? get_term( $term->parent, $taxonomy ) : '';
		$grandparent = $parent && $parent->parent ? get_term( $parent->parent, $taxonomy ) : '';

		// Maybe add grandparent.
		if ( $grandparent ) {
			$breadcumbs[] = [
				'url'  => get_term_link( $grandparent ),
				'text' => $grandparent->name,
			];
		}

		// Maybe add parent.
		if ( $parent ) {
			$breadcumbs[] = [
				'url'  => get_term_link( $parent ),
				'text' => $parent->name,
			];
		}

		// Add term.
		$breadcumbs[] = [
			'url'  => get_term_link( $term ),
			'text' => $term->name,
		];
	}
	// If season.
	elseif ( $is_season ) {
		// Get the terms.
		$team   = isset( $wp_query->query_vars['league'] ) ? $wp_query->query_vars['league'] : '';
		$team   = $team ? get_term_by( 'slug', $team, 'league' ) : '';
		$league = $team && $team->parent ? get_term( $team->parent, 'league' ) : '';
		$season = isset( $wp_query->query_vars['term'] ) ? $wp_query->query_vars['term'] : '';
		$season = $season ? get_term_by( 'slug', $season, 'season' ) : '';

		// Maybe add league.
		if ( $league ) {
			$breadcumbs[] = [
				'url'  => get_term_link( $league ),
				'text' => $league->name,
			];
		}

		// Maybe add team.
		if ( $team ) {
			$breadcumbs[] = [
				'url'  => get_term_link( $team ),
				'text' => $team->name,
			];
		}

		// Maybe add season.
		if ( $season ) {
			$breadcumbs[] = [
				'url'  => get_term_link( $season ),
				'text' => $season->name,
			];
		}
	}
	// If singular.
	elseif ( $is_singular ) {
		// Get the terms.
		$league      = isset( $wp_query->query_vars['league'] ) ? $wp_query->query_vars['league'] : '';
		$league      = $league ? get_term_by( 'slug', $league, 'league' ) : '';
		$parent      = $league && $league->parent ? get_term( $league->parent, 'league' ) : '';
		$grandparent = $parent && $parent->parent ? get_term( $parent->parent, 'league' ) : '';
		$season      = isset( $wp_query->query_vars['season'] ) ? $wp_query->query_vars['season'] : '';
		$season      = $season ? get_term_by( 'slug', $season, 'season' ) : '';

		// Maybe add grandparent.
		if ( $grandparent ) {
			$breadcumbs[] = [
				'url'  => get_term_link( $grandparent ),
				'text' => $grandparent->name,
			];
		}

		// Maybe add parent.
		if ( $parent ) {
			$breadcumbs[] = [
				'url'  => get_term_link( $parent ),
				'text' => $parent->name,
			];
		}

		// Maybe add league.
		if ( $league ) {
			$breadcumbs[] = [
				'url'  => get_term_link( $league ),
				'text' => $league->name,
			];
		}

		// Maybe add season.
		if ( $season && $league ) {
			$breadcumbs[] = [
				'url'  => trailingslashit( get_term_link( $league ) ) . $season->slug . '/',
				'text' => $season->name,
			];
		}

		// Add matchup.
		$breadcumbs[] = [
			'url'  => get_permalink(),
			'text' => get_the_title(),
		];
	}

	// Bail if no breadcrumbs.
	if ( ! $breadcumbs ) {
		return;
	}

	// Output breadcrumbs.
	echo '<div class="breadcrumb">';
		foreach ( $breadcumbs as $i => $crumb ) {
			$last = $i === count( $breadcumbs ) - 1;

			echo '<span class="breadcrumb__item">';
				if ( $crumb['url'] && ! $last ) {
					printf( '<a class="breadcrumb__link" href="%s">', esc_url( $crumb['url'] ) );
				}

				echo esc_html( $crumb['text'] );

				if ( $crumb['url'] && ! $last ) {
					echo '</a>';
				}
			echo '</span>';

			if ( ! $last ) {
				echo '<span class="breadcrumb__separator"> ' . esc_html( $separator ) . ' </span>';
			}
		}
	echo '</div>';
}
