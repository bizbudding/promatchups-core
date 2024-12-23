<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The admin post voting class.
 *
 * TODO: Make a AjaxPost class and extend it here.
 *
 * @since 0.8.0
 */
class ProMatchups_Ajax_Post_Vote {
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
		add_action( 'admin_post_pm_vote_submission',        [ $this, 'handle_submission' ] );
		add_action( 'admin_post_nopriv_pm_vote_submission', [ $this, 'handle_submission' ] );
		add_action( 'wp_ajax_pm_vote_submission',           [ $this, 'handle_submission' ] );
		add_action( 'wp_ajax_nopriv_pm_vote_submission',    [ $this, 'handle_submission' ] );
	}

	/**
	 * Handles the vote submission.
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	function handle_submission() {
		// Verify nonce for security.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pm_vote_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Vote submission security check failed.', 'promatchups' ) ] );
			exit;
		}

		// Get the post data.
		$args = wp_parse_args(
			$_POST,
			[
				'team'       => null,
				'user_id'    => null,
				'matchup_id' => null,
				'redirect'   => null,
				'fetch'      => null,
			]
		);

		// TODO: Switch 'fetch' to 'ajax' and handle errors like post commentary.

		// Sanitize.
		$team       = sanitize_text_field( $args['team'] );
		$user_id    = absint( $args['user_id'] );
		$matchup_id = absint( $args['matchup_id'] );
		$redirect   = esc_url( $args['redirect'] );
		$fetch      = rest_sanitize_boolean( $args['fetch'] );

		// Bail if no team.
		if ( ! $team ) {
			wp_send_json_error( [ 'message' => __( 'No team selected.', 'promatchups' ) ] );
			exit;
		}

		// Bail if no user ID.
		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => __( 'No user ID found.', 'promatchups' ) ] );
			exit;
		}

		// Bail if no matchup ID.
		if ( ! $matchup_id ) {
			wp_send_json_error( [ 'message' => __( 'No matchup ID found.', 'promatchups' ) ] );
			exit;
		}

		// Run listener and get response.
		$listener = new ProMatchups_User_Vote_Listener( $matchup_id, $team, $user_id );
		$response = $listener->get_response();

		// Handle response.
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => $response->get_error_message() ] );
			exit;
		}

		// If redirecting, do it.
		if ( ! $fetch && $args['redirect'] ) {
			wp_safe_redirect( $redirect );
			exit;
		}

		// Send success response.
		wp_send_json_success( $response->get_data() );
		exit;
	}
}