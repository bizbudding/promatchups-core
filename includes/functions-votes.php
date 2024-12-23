<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Get the archive vote box for a matchup.
 *
 * @since 0.8.0
 *
 * @return string
 */
function pm_get_archive_vote_box() {
	$html = '';

	// Get the user and matchup data.
	$matchup_id = get_the_ID();
	$user       = pm_get_user();
	$data       = pm_get_matchup_data( $matchup_id );

	// Bail if no matchup data.
	if ( ! ( $data && array_values( $data ) ) ) {
		return $html;
	}

	// Get start timestamp.
	$timestamp = $data['date'];

	// Bail if no timestamp, we can't vote if we don't know when the game starts.
	if ( ! $timestamp ) {
		return $html;
	}

	// Set vars.
	$has_access   = $user && $user->ID;
	$started      = time() > $timestamp;
	$show_outcome = $started && $data['has_outcome'];
	$show_vote    = ! $started && $has_access;

	// Bail if conditions are not met.
	if ( ! ( $show_outcome || $show_vote ) ) {
		return $html;
	}

	// Add data.
	$data['redirect'] = home_url( add_query_arg( [] ) );

	// Start vote box.
	$html .= '<div class="pm-vote pm-vote-archive">';
		// If showing outcome.
		if ( $show_outcome ) {
			// Heading.
			$html .= sprintf( '<p class="pm-vote__heading">%s</p>', __( 'Game Results', 'promatchups' ) );

			// Outcome box.
			$html .= pm_get_outcome_box( $data );

			// If user has access, show bot results.
			if ( pm_has_access() ) {
				$html .= pm_get_botresults( $data );
			}
		}
		// If not started and they have access to vote.
		elseif ( $has_access ) {
			// Set first.
			static $first = true;

			// Maybe enqueue JS.
			if ( $first ) {
				// Enqueue.
				pm_enqueue_scripts( pm_get_vote_elements( 'selected' ) );

				// Set first to false.
				$first = false;
			}

			// Heading.
			$html .= sprintf( '<p class="pm-vote__heading">%s</p>', __( 'Make Your Pick', 'promatchups' ) );

			// Vote form.
			$html .= pm_get_vote_form( $data );
		}
		// Not started, and no access to vote.
		else {
			// Heading.
			$html .= sprintf( '<p class="pm-vote__heading">%s</p>', __( 'Make Your Pick', 'promatchups' ) );

			// Faux vote form.
			$html .= pm_get_faux_vote_form( $data );
		}

	$html .= '</div>';

	return $html;
}

/**
 * Get the singular vote box for a matchup.
 *
 * @since 0.8.0
 *
 * @return string
 */
function pm_get_singular_vote_box() {
	// Get the user and matchup data.
	$html       = '';
	$matchup_id = get_the_ID();
	$user       = pm_get_user();
	$data       = pm_get_matchup_data( $matchup_id );

	// Bail if no matchup data.
	if ( ! ( $data && array_values( $data ) ) ) {
		return $html;
	}

	// Get start timestamp.
	$timestamp = $data['date'];

	// Bail if no timestamp, we can't vote if we don't know when the game starts.
	if ( ! $timestamp ) {
		return $html;
	}

	// Set vars.
	$has_access   = $user && $user->ID;
	$started      = time() > $timestamp;
	// $show_outcome = $started && $data['winner_team'] && $data['loser_team'];
	$show_outcome = $started;
	$show_vote    = ! $started && $has_access;
	$vote         = $user && $user->ID ? pm_get_user_vote( $matchup_id, $user->ID ) : [];
	$vote         = isset( $vote['name'] ) ? $vote['name'] : '';

	// Add data.
	$data['redirect'] = get_permalink( $matchup_id ) . '#vote';

	// If the game has started.
	if ( $started ) {
		// If we have an outcome.
		if ( $data['winner_team'] && $data['loser_team'] ) {
			$heading = sprintf( '<h2 class="pm-vote__heading">%s</h2>', __( 'Game Results', 'promatchups' ) );
			$desc    = [];
			// $desc[]  = __( 'The game has ended.', 'promatchups' );

			// If we have both scores.
			if ( $data['winner_score'] && $data['loser_score'] ) {
				$scores = sprintf( " %s - %s", $data['winner_score'], $data['loser_score'] );
				// $desc[] = sprintf( __( 'The %s defeated the %s%s.', 'promatchups' ), $data['winner_short'], $data['loser_short'], $scores );
			} else {
				// $desc[] = __( 'Sorry, we don\'t have scores at this time.', 'promatchups' );
			}

			// To string.
			$desc = $desc ? sprintf( '<p>%s</p>', implode( ' ', $desc ) ) : '';
		}
		// No outcome.
		else {
			$heading = sprintf( '<h2 class="pm-vote__heading">%s</h2>', __( 'Game Info', 'promatchups' ) );
			$desc    = sprintf( '<p>%s</p>', __( 'Voting is closed after the game starts. Once we analyze the results and calculate your points we\'ll update here.', 'promatchups' ) );
		}
	}
	// If they have already voted.
	elseif ( $vote ) {
		$heading = sprintf( '<h2 class="pm-vote__heading">%s</h2>', __( 'Make Your Pick', 'promatchups' ) );
		$desc    = sprintf( '<p>%s</p>', sprintf( __( 'You have already voted for the %s; leave it as is or change your vote before game time.', 'promatchups' ), $vote ) );
	}
	// Fallback for voting.
	else {
		$heading = sprintf( '<h2 class="pm-vote__heading">%s</h2>', __( 'Make Your Pick', 'promatchups' ) );
		$desc    = sprintf( '<p>%s</p>', __( 'Compete with others to beat the SportsDesk Bot.<br>Who do you think will win?', 'promatchups' ) );
	}

	// Start vote box.
	$html .= '<div id="vote" class="pm-vote pm-vote-single">';
		// Get user avatar.
		$avatar = get_avatar( get_current_user_id(), 128 );

		// Display the avatar.
		$html .= sprintf( '<div class="pm-vote__avatar">%s</div>', $avatar );

		// If showing outcome.
		if ( $show_outcome ) {
			// Heading.
			$html .= $heading;

			// Outcome box.
			$html .= pm_get_outcome_box( $data );

			// If user has access, show bot results.
			if ( pm_has_access() && $data['has_outcome'] ) {
				$html .= pm_get_botresults( $data );
			}
		}
		// If not started and they have access to vote.
		elseif ( ! $started && $has_access ) {
			// Enqueue JS.
			pm_enqueue_scripts( pm_get_vote_elements( 'selected' ) );

			// Heading.
			$html .= $heading;

			// Vote form.
			$html .= pm_get_vote_form( $data );
		}
		// Not started, and no access to vote.
		elseif ( ! $has_access ) {
			// Heading.
			$html .= $heading;

			// Faux vote form.
			$html .= pm_get_faux_vote_form( $data );
		}

		// Description.
		$html .= $desc;

	$html .= '</div>';

	// Add tracking attributes.
	$html = pm_add_matomo_attributes( $html, 'single-matchup-vote-box' );

	return $html;
}

/**
 * Get the outcome box.
 *
 * @since 0.8.0
 *
 * @param array $data The matchup data.
 *
 * @return string
 */
function pm_get_outcome_box( $data ) {
	$html       = '';
	$user_id    = get_current_user_id();
	$prediction = $user_id ? pm_get_vote_elements( 'prediction' ) : '';
	$vote       = pm_get_user_vote( $data['matchup_id'], $user_id );
	$selected   = pm_get_vote_elements( 'selected' );
	$status     = pm_get_vote_elements( 'winner_team' );
	$has_winner = $data['winner_team'] && $data['loser_team'];

	// Set home/away classes.
	if ( is_null( $data['winner_home'] ) ) {
		if ( $has_winner ) {
			$data['winner_home'] = $data['winner_team'] === $data['home_team'];
		} else {
			$home_class = '';
			$away_class = '';
		}
	} else {
		$home_class = $data['winner_home'] ? 'winner_team' : 'loser_team';
		$away_class = ! $data['winner_home'] ? 'winner_team' : 'loser_team';
	}

	// Set empty scores.
	if ( ! $has_winner ) {
		$data['winner_score'] = '--';
		$data['loser_score']  = '--';
	}

	// Build the markup.
	$html .= '<div class="pm-outcome pm-actions">';
		// Away team first.
		$html .= '<div class="pm-outcome__col pm-action__col away">';
			// Status.
			if ( $has_winner && ! $data['winner_home'] ) {
				$html .= $status;
			}

			// Content.
			$html .= '<div class="pm-outcome__content">';
				$html .= sprintf( '<p class="pm-outcome__team %s">%s</p>', $away_class, $data['away_short'] );
				$html .= sprintf( '<p class="pm-outcome__score %s">%s</p>', $away_class, ! $data['winner_home'] ? $data['winner_score'] : $data['loser_score'] );
			$html .= '</div>';

			// Bot prediction.
			if ( $data['away_team'] && $data['prediction'] && $data['away_team'] === $data['prediction'] ) {
				$html .= $prediction;
			}

			// User vote.
			if ( $data['away_team'] && $vote['name'] && $data['away_team'] === $vote['name'] ) {
				$html .= $selected;
			}
		$html .= '</div>';

		// Home team second.
		$html .= '<div class="pm-outcome__col pm-action__col home">';
			// Status.
			if ( $has_winner && $data['winner_home'] ) {
				$html .= $status;
			}

			// Content.
			$html .= '<div class="pm-outcome__content">';
				$html .= sprintf( '<p class="pm-outcome__team %s">%s</p>', $home_class, $data['home_short'] );
				$html .= sprintf( '<p class="pm-outcome__score %s">%s</p>', $home_class, $data['winner_home'] ? $data['winner_score'] : $data['loser_score'] );
			$html .= '</div>';

			// Bot prediction.
			if ( $data['home_team'] && $data['prediction'] && $data['home_team'] === $data['prediction'] ) {
				$html .= $prediction;
			}

			// User vote.
			if ( $data['home_team'] && $vote['name'] && $data['home_team'] === $vote['name'] ) {
				$html .= $selected;
			}
		$html .= '</div>';
	$html .= '</div>';

	return $html;
}

/**
 * Get the vote form.
 *
 * @since 0.8.0
 *
 * @param array $data The matchup data.
 *
 * @return string
 */
function pm_get_vote_form( $data ) {
	$html       = '';
	$user_id    = get_current_user_id();
	$prediction = $data['league'] && pm_has_access( $data['league'] ) ? pm_get_vote_elements( 'prediction' ) : '';
	$vote       = pm_get_user_vote( $data['matchup_id'], $user_id );
	$selected   = pm_get_vote_elements( 'selected' );

	// Get the vote form markup.
	$html .= sprintf( '<form class="pm-vote__form" action="%s" method="post">', esc_url( admin_url('admin-post.php') ) );
		// Team buttons.
		$html .= '<div class="pm-vote__buttons pm-actions">';
			$html .= '<div class="pm-vote__button pm-action__col away">';

				// Away team button.
				$away_vote  = $data['away_team'] && $vote['name'] && $data['away_team'] === $vote['name'];
				$html      .= sprintf( '<button class="button button-small button-ajax" type="submit" name="team" value="%s"%s><span class="button-text">%s</span></button>', $data['away_team'], $away_vote ? ' disabled' : '', $data['away_short'] );

				// Bot prediction.
				if ( $data['away_team'] && $data['prediction'] && $data['away_team'] === $data['prediction'] ) {
					$html .= $prediction;
				}

				// User vote.
				if ( $away_vote ) {
					$html .= $selected;
				}
			$html .= '</div>';
			$html .= '<div class="pm-vote__button pm-action__col home">';

				// Home team button.
				$home_vote  = $data['home_team'] && $vote['name'] && $data['home_team'] === $vote['name'];
				$html      .= sprintf( '<button class="button button-small button-ajax" type="submit" name="team" value="%s"%s><span class="button-text">%s</span></button>', $data['home_team'], $home_vote ? ' disabled' : '', $data['home_short'] );

				// Bot prediction.
				if ( $data['home_team'] && $data['prediction'] && $data['home_team'] === $data['prediction'] ) {
					$html .= $prediction;
				}

				// User vote.
				if ( $home_vote ) {
					$html .= $selected;
				}
			$html .= '</div>';
		$html .= '</div>';

		// Hidden inputs.
		$html .= '<input type="hidden" name="action" value="pm_vote_submission">';
		$html .= sprintf( '<input type="hidden" name="user_id" value="%s">', $user_id );
		$html .= sprintf( '<input type="hidden" name="matchup_id" value="%s">', $data['matchup_id'] );
		$html .= sprintf( '<input type="hidden" name="redirect" value="%s">', esc_url( $data['redirect'] ) );
		$html .= wp_nonce_field( 'pm_vote_nonce', '_wpnonce', true, false );
	$html .= '</form>';

	return $html;
}

/**
 * Get the faux vote form.
 *
 * @since 0.8.0
 *
 * @param array $data The matchup data.
 *
 * @return string
 */
function pm_get_faux_vote_form( $data ) {
	$html = '';

	// Display the faux vote form.
	$html .= '<div class="pm-vote__form">';
		// Build url.
		$url = add_query_arg(
			[
				'rcp_redirect' => home_url( add_query_arg( [] ) ),
			],
			get_permalink( 7049 )
		);

		// Build the markup.
		$html .= '<div class="pm-vote__buttons pm-actions">';
			$html .= '<div class="pm-vote__button pm-action__col away">';
				$html .= sprintf( '<a class="button button-small" href="%s">%s</a>', esc_url( $url ), $data['away_short'] );
			$html .= '</div>';
			$html .= '<div class="pm-vote__button pm-action__col home">';
				$html .= sprintf( '<a class="button button-small" href="%s">%s</a>', esc_url( $url ), $data['home_short'] );
			$html .= '</div>';
		$html .= '</div>';
	$html .= '</div>';

	return $html;
}

/**
 * Enqueue the vote scripts.
 *
 * @since 0.8.0
 *
 * @param string $selected The selected markup.
 *
 * @return string
 */
function pm_get_vote_elements( $element ) {
	static $cache = [];

	// If we have cache.
	if ( $cache ) {
		if ( $element ) {
			return isset( $cache[ $element ] ) ? $cache[ $element ] : '';
		}

		return $cache;
	}

	// Set cache.
	$cache = [
		'prediction' => sprintf( '<span class="pm-outcome__prediction">%s</span>', __( 'Bot pick', 'promatchups' ) ),
		'selected'   => sprintf( '<span class="pm-outcome__selected">%s</span>', __( 'Your pick', 'promatchups' ) ),
		'winner_team'     => sprintf( '<span class="pm-outcome__status">%s</span>', __( 'Winner', 'promatchups' ) ),
	];

	if ( $element ) {
		return isset( $cache[ $element ] ) ? $cache[ $element ] : '';
	}

	return $cache;
}

/**
 * Creates or updates a user's vote for a matchup.
 *
 * @since 0.2.0
 *
 * @param int         $matchup_id     The matchup ID or object.
 * @param int|WP_User $user           The user ID or object.
 * @param string      $team           The team name.
 * @param bool|null   $spread_covered If the spread was covered.
 *
 * @return WP_Error|int The comment ID.
 */
function pm_update_user_vote( $matchup_id, $user, $team, $spread_covered = null ) {
	$comment_id = 0;

	// Get user.
	$user = pm_get_user( $user );

	// Bail if no user.
	if ( ! ( $user && $user->ID ) ) {
		return $comment_id;
	}

	// Get matchup data.
	$data = pm_get_matchup_data( $matchup_id );

	// Bail if no matchup data.
	if ( ! ( $data && array_values( $data ) ) ) {
		return $comment_id;
	}

	// Bail if no team or team is not away or home team.
	if ( ! ( $team && in_array( $team, [ $data['away_team'], $data['home_team'] ] ) ) ) {
		return $comment_id;
	}

	// Get event date timestamp.
	$event_date = isset( $data['date'] ) && $data['date'] ? wp_date( 'Y-m-d H:i:s', $data['date'] ) : wp_date( 'Y-m-d H:i:s' );

	// Build comment data.
	$args = [
		'comment_approved'     => 1,
		'comment_post_ID'      => $matchup_id,
		'comment_content'      => $team,
		'comment_agent'        => $data['league'],
		'user_id'              => $user->ID,
		'comment_author'       => $user->user_login,
		'comment_author_email' => $data['model_used'],
		'comment_author_url'   => $user->user_url,
		'comment_author_IP'    => $user->ID === pm_get_bot_user_id() ? $data['probability'] : null,
		'comment_date'         => $event_date, // Set only local time, WP will also save gmt.
	];

	// If a regular vote.
	if ( is_null( $spread_covered ) ) {
		$args['comment_type'] = 'pm_vote';
	}
	// Spread vote.
	else {
		$args['comment_type']   = 'pm_spread';
		$args['comment_parent'] = $spread_covered ? 1 : -1;
	}

	// Get existing vote.
	$existing = pm_get_user_vote( $matchup_id, $user, $args['comment_type'] );

	// If user has voted, update.
	if ( $existing['id'] ) {
		// Set comment ID.
		$args['comment_ID'] = (int) $existing['id'];

		// Update the comment.
		$comment_id = wp_update_comment( $args );
	}
	// New vote.
	else {
		// Insert the comment.
		$comment_id = wp_insert_comment( $args );
	}

	return $comment_id;
}

/**
 * Get the user's vote for a matchup.
 *
 * @since 0.2.0
 *
 * @param int         $matchup_id The matchup ID or object.
 * @param int|WP_User $user       The user ID or object. Null for current user.
 *
 * @return array
 */
function pm_get_user_vote( $matchup_id, $user, $type = 'pm_vote' ) {
	$vote = [
		'id'   => null,
		'name' => null,
	];

	// Get user, with fallback to current user.
	$user = pm_get_user( $user );

	// Bail if no user.
	if ( ! $user ) {
		return $vote;
	}

	// Get user votes.
	$comments = get_comments(
		[
			'type'    => $type,
			'post_id' => $matchup_id,
			'user_id' => $user->ID,
			'number'  => 1,
		]
	);

	// If user has voted.
	if ( $comments ) {
		$existing = reset( $comments );
		$vote     = [
			'id'   => $existing->comment_ID,
			'name' => $existing->comment_content,
		];
	}

	return $vote;
}

/**
 * Get the result of a moneyline vote.
 *
 * @since 1.0.0
 *
 * @param string $vote  The team full name.
 * @param array  $data  The matchup data.
 *
 * @return int|null
 */
function pm_get_moneyline_result( $vote, $data ) {
	if ( ! ( $data['has_winner'] && ! is_null( $data['score_diff'] ) ) ) {
		return null;
	}

	// If a tie.
	if ( 0 === (int) $data['score_diff'] ) {
		return 2;
	}

	return $data['winner_team'] === $vote ? 1 : -1;
}

/**
 * Get the result of a spread vote.
 *
 * @since 0.13.0
 *
 * @param string $vote  The team full name.
 * @param bool   $cover If the team will cover the spread.
 * @param array  $data  The matchup data.
 *
 * @return int|null
 */
function pm_get_spread_result( $vote, $cover, $data ) {
	$winner_name    = $data['winner_team'];
	$score_diff     = $data['score_diff'];
	$spread_used    = $winner_name && isset( $data['spreads'][ $winner_name ]['spread_used'] ) ? $data['spreads'][ $winner_name ]['spread_used'] : null;
	$spread_covered = filter_var( $cover, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

	// Bail if missing data.
	if ( ! $data['has_winner'] || is_null( $winner_name ) || is_null( $score_diff ) || is_null( $spread_used ) || is_null( $spread_covered ) ) {
		return null;
	}

	// If push. Note `==` for truthy comparison.
	if ( 0 == (float) $score_diff ) {
		return 2;
	}

	// If the vote is not the winner.
	if ( $vote !== $winner_name ) {
		// If we predicted to cover spread, we lose.
		if ( $spread_covered ) {
			return -1;
		}
		// We predicted the spread was not covered, we win.
		else {
			return 1;
		}
	}

	// If we predicted the winning team would cover.
	if ( $spread_covered ) {
		// If we covered, we win.
		if ( $score_diff > abs( $spread_used ) ) {
			return 1;
		}
		// We didn't cover, we lose.
		else {
			return -1;
		}
	}
	// We predicted the winning team would not cover.
	else {
		// If we did cover, we lose.
		if ( $score_diff > abs( $spread_used ) ) {
			return -1;
		}
		// We covered, we win.
		else {
			return 1;
		}
	}
}

/**
 * Update all the bot votes for a matchup.
 *
 * @since 0.8.0
 *
 * @param int $matchup_id
 *
 * @return array
 */
function pm_update_bot_votes( $matchup_id ) {
	$bot_id         = pm_get_bot_user_id();
	$awaybot_id     = pm_get_awaybot_user_id();
	$homebot_id     = pm_get_homebot_user_id();
	$favoredbot_id  = pm_get_favoredbot_user_id();
	$underdogbot_id = pm_get_underdogbot_user_id();
	$matchup_id     = get_the_ID();
	$data           = pm_get_matchup_data( $matchup_id );
	$team           = $data['prediction'];
	$favored        = $data['favored'];
	$probability    = $data['probability'];

	// Start counts.
	$votes = 0;
	$skipped  = 0;

	// If team, update main bot votes.
	if ( $team && $bot_id && $bot_user = get_user_by( 'ID', $bot_id ) ) {
		$h2h_id = 0;
		$ats_id = 0;

		// Update vote.
		$h2h_id = pm_update_user_vote( $matchup_id, $bot_id, $team );

		// Get spread covered prediction.
		$spread_covered = $data['spread_covered'];

		// If we have a spread covered prediction.
		if ( ! is_null( $spread_covered ) ) {
			// Spread vote is on the favored team.
			$team = $favored ?: $team;

			// Update spread vote.
			$ats_id = pm_update_user_vote( $matchup_id, $bot_id, $team, $spread_covered );
		}

		// If comment ID, add it.
		if ( $h2h_id ) {
			$votes++;
		} else {
			$skipped++;
		}

		// If comment ID, add it.
		if ( $ats_id ) {
			$votes++;
		} else {
			$skipped++;
		}
	}

	// If away team, update away bot votes.
	if ( $data['away_team'] && $awaybot_id && $awaybot_user = get_user_by( 'ID', $awaybot_id ) ) {
		$away_id = pm_update_user_vote( $matchup_id, $awaybot_id, $data['away_team'] );

		// If comment ID, add it.
		if ( $away_id ) {
			$votes++;
		} else {
			$skipped++;
		}
	}

	// If home team, update home bot votes.
	if ( $data['home_team'] && $homebot_id && $homebot_user = get_user_by( 'ID', $homebot_id ) ) {
		$home_id = pm_update_user_vote( $matchup_id, $homebot_id, $data['home_team'] );

		// If comment ID, add it.
		if ( $home_id ) {
			$votes++;
		} else {
			$skipped++;
		}
	}

	// If favored team, update favored and underdog bot votes.
	if ( $favored ) {
		// If favored team, update favored bot votes.
		if ( $favoredbot_id && $favoredbot_user = get_user_by( 'ID', $favoredbot_id ) ) {
			$favored_id = pm_update_user_vote( $matchup_id, $favoredbot_id, $favored );

			// If comment ID, add it.
			if ( $favored_id ) {
				$votes++;
			} else {
				$skipped++;
			}
		}

		// If underdog team, update underdog bot votes.
		if ( $underdogbot_id && $underdogbot_user = get_user_by( 'ID', $underdogbot_id ) ) {
			// If we have teams to compare.
			if ( $data['has_teams'] ) {
				// Get team name from away_team or home_team based which one is not favored.
				$underdog = $favored === $data['away_team'] ? $data['home_team'] : $data['away_team'];

				// Update vote.
				$underdog_id = pm_update_user_vote( $matchup_id, $underdogbot_id, $underdog );

				// If comment ID, add it.
				if ( $underdog_id ) {
					$votes++;
				} else {
					$skipped++;
				}
			}
		}
	}

	return [
		'votes'   => $votes,
		'skipped' => $skipped,
	];
}
