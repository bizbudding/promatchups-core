<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The outcome votes listener class.
 *
 * @since 0.2.0
 */
class ProMatchups_Outcome_Votes_Listener extends ProMatchups_Listener {
	protected $matchup_id;
	protected $data;
	protected $update_points;
	protected $return;

	/**
	 * Construct the class.
	 */
	function __construct( $matchup_id, $update_points = true ) {
		$this->matchup_id    = (int) $matchup_id;
		$this->data          = pm_get_matchup_data( $this->matchup_id );
		$this->update_points = (bool) $update_points;

		$this->run();
	}

	/**
	 * Run the logic.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	function run() {
		// If no data, return error.
		if ( ! array_values( $this->data ) ) {
			$this->return = $this->get_error( 'No AskNews data found for post ID: ' . $this->matchup_id . ' ' . get_permalink( $this->matchup_id ) );
			return;
		}

		// Required keys.
		$keys = [
			'date',
			'winner_team',
			'score_diff',
		];

		// Loop through and bail if any key is missing.
		foreach ( $keys as $key ) {
			if ( ! isset( $this->data[ $key ] ) || is_null( $this->data[ $key ] ) ) {
				$this->return = $this->get_error( 'Missing key: ' . $key . ' for post ID: ' . $this->matchup_id . ' ' . get_permalink( $this->matchup_id ) );
				return;
			}
		}

		// Update all votes for this matchup.
		$counts = $this->update_comments();

		// Return success.
		$this->return = $this->get_success( $counts['votes'] . ' votes updated with ' . $counts['users'] . ' users for matchup ' . $this->matchup_id . ' ' . get_permalink( $this->matchup_id ) );
	}

	/**
	 * Update all votes for this matchup.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	function update_comments() {
		$users_to_update = [];
		$comments        = 0;
		$users           = 0;

		// Get all votes for this matchup.
		$votes = get_comments(
			[
				'type'    => [ 'pm_vote', 'pm_spread' ],
				'status'  => 'approve',
				'post_id' => $this->matchup_id,
			]
		);

		// Loop through all votes.
		foreach ( $votes as $comment ) {
			// Set vars.
			$karma = 0;
			$user  = get_user_by( 'ID', $comment->user_id );
			$vote  = trim( $comment->comment_content );

			// Skip if no user.
			if ( ! $user ) {
				WP_CLI::line( 'No user found for comment ID: ' . $comment->comment_ID );
				continue;
			}

			// If not a valid team.
			if ( ! ( $vote && in_array( $vote, [ $this->data['away_team'], $this->data['home_team'] ] ) ) ) {
				WP_CLI::line( 'Invalid vote for comment ID: ' . $comment->comment_ID. ' Team: ' . $vote );
				continue;
			}

			// If a spread vote.
			// Set karma. 1 for covered spread, -1 for not covering spread, 2 for a push.
			// 0 is default for karma, when a vote is initially placed.
			if ( 'pm_spread' === $comment->comment_type ) {
				// Get spread result.
				$karma = pm_get_spread_result( $vote, $this->data['spread_covered'], $this->data );

				// Skip if no karma.
				if ( is_null( $karma ) ) {
					continue;
				}
			}

			// Standard vote.
			// Set karma. 1 for win, -1 for loss, 2 for a tie.
			// 0 is default for karma, when a vote is initially placed.
			else {
				$karma = pm_get_moneyline_result( $vote, $this->data );

				// Skip if no karma.
				if ( is_null( $karma ) ) {
					continue;
				}
			}

			// Get the date.
			$event_date = isset( $this->data['date'] ) && $this->data['date'] ? wp_date( 'Y-m-d H:i:s', $this->data['date'] ) : wp_date( 'Y-m-d H:i:s' );

			// Update comment,
			$update = wp_update_comment(
				[
					'comment_ID'    => $comment->comment_ID,
					'comment_karma' => $karma,
					'comment_agent' => $this->data['league'],   // TODO: Remove later if this is added during the voting process anyway.
					'comment_date'  => $event_date,             // Set only local time, WP will also save gmt.
				]
			);

			// Maybe update comment count.
			if ( $update && ! is_wp_error( $update ) ) {
				$comments++;
			}

			// Collect user IDs for updating points.
			if ( $this->update_points ) {
				$users_to_update[ $user->ID ] = $user;
			}

			// Increment users.
			$users++;
		}

		// DISABLED FOR NOW TO SEE IF IT FIXES TIMEOUTS.
		// // If updating the user's points, update user points.
		// foreach ( $users_to_update as $user ) {
		// 	$listener = new ProMatchups_User_Points( $user );
		// 	$response = $listener->get_response();
		// }

		return [
			'votes' => $comments,
			'users' => $users,
		];
	}
}
