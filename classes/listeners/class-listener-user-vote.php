<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The vote listener class.
 * This class saves a user's vote for a matchup.
 *
 * @since 0.1.0
 */
class ProMatchups_User_Vote_Listener extends ProMatchups_Listener {
	protected $matchup_id;
	protected $team;
	protected $user;
	protected $return;

	/**
	 * Construct the class.
	 */
	function __construct( $matchup_id, $team, $user = null ) {
		$this->matchup_id = absint( $matchup_id );
		$this->team       = sanitize_text_field( $team );
		$this->user       = $this->get_user( $user );
		$this->run();
	}

	/**
	 * Run the logic.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function run() {
		// Bail if not a valid user.
		if ( ! $this->user ) {
			$this->return = $this->get_error( 'User not found.' );
			return;
		}

		// If no capabilities.
		if ( ! is_user_logged_in( $this->user ) ) {
			$this->return = $this->get_error( 'User is not logged in.' );
			return;
		}

		// Get the user's vote data.
		$existing = pm_get_user_vote( $this->matchup_id, $this->user );

		// If already voted.
		if ( $existing['id'] && $existing['name'] ) {
			// If the same team.
			if ( $this->team === $existing['name'] ) {
				$this->return = $this->get_success( sprintf( __( 'Vote has not been saved. User has already voted for %s.', 'promatchups' ), $this->team ) );
				return;
			}
		}

		// Add the vote.
		$comment_id = pm_update_user_vote( $this->matchup_id, $this->user, $this->team );

		// If error.
		if ( is_wp_error( $comment_id ) ) {
			$this->return = $this->get_error( $comment_id->get_error_message() );
			return;
		}

		// If no vote.
		if ( ! $comment_id ) {
			$this->return = $this->get_error( sprintf( 'Vote skipped for %s. Comment ID: %s', $this->team, $comment_id ) );
			return;
		}

		// Set the return message.
		$this->return = $this->get_success( sprintf( __( 'Vote saved for %s. Comment ID: %s', 'promatchups' ), $this->team, $comment_id ) );
		return;
	}
}