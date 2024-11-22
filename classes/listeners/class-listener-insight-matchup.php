<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The insight matchup listener class.
 * This creates or updates a matchup from a new insight.
 *
 * @since 0.1.0
 */
class ProMatchups_AskNews_Insight_Matchup_Listener extends ProMatchups_Listener {
	protected $insight_id;
	protected $matchup_id;
	protected $body;
	protected $content;
	protected $data;
	protected $user;
	protected $return;

	/**
	 * Construct the class.
	 */
	function __construct( $insight_id, $user = null ) {
		$this->insight_id = absint( $insight_id );
		$this->body       = (array) get_post_meta( $this->insight_id, 'asknews_body', true );
		$this->content    = ProMatchups_Insight_Content::get( $this->insight_id );
		$this->data       = ProMatchups_Insight_Data::get( $this->insight_id );
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
		// No user.
		if ( ! $this->user ) {
			$this->return = $this->get_error( 'User not found.' );
			return;
		}

		// If no capabilities.
		if ( ! user_can( $this->user, 'edit_posts' ) ) {
			$this->return = $this->get_error( 'User cannot edit posts.' );
			return;
		}

		// If no body.
		if ( ! $this->body ) {
			$this->return = $this->get_error( 'No body found.' );
			return;
		}

		// If no data.
		if ( ! array_values( $this->data ) ) {
			$this->return = $this->get_error( 'No data found.' );
			return;
		}

		// Prevent post_modified update.
		add_filter( 'wp_insert_post_data', 'pm_prevent_post_modified_update', 10, 4 );

		/***************************************************************
		 * Get the matchup post ID.
		 *
		 * Set team vars.
		 * Set matchup title and data.
		 * Check for an existing matchup.
		 * If no matchup, create one.
		 * Set matchup ID.
		 ***************************************************************/

		// Get matchup title from body.
		$matchup_body_title = pm_get_key( 'matchup', $this->body );

		// Set title and date.
		if ( $matchup_body_title ) {
			list( $matchup_title, $matchup_datetime ) = explode( ',', $matchup_body_title, 2 );
			$matchup_title     = trim( $matchup_title );
			$matchup_datetime  = trim( $matchup_datetime );
			$matchup_timestamp = $this->get_timestamp( $matchup_datetime );
		} else {
			$matchup_title     = $this->data['away_short'] . ' vs ' . $this->data['home_short'];
			$matchup_timestamp = null;
		}

		// Set the update flag.
		$update = false;

		// Check for an existing matchup.
		$matchup_ids = get_posts(
			[
				'post_type'    => 'matchup',
				'post_status'  => 'any',
				'meta_key'     => 'event_uuid',
				'meta_value'   => $this->data['event_uuid'],
				'meta_compare' => '=',
				'fields'       => 'ids',
				'numberposts'  => 1,
			]
		);

		// Existing matchup, get post ID.
		if ( $matchup_ids && isset( $matchup_ids[0] ) ) {
			// Rebuild matchup title and check if it needs updating.
			$needs_title   = $matchup_title !== get_the_title( $matchup_ids[0] );
			$needs_summary = ! get_the_excerpt( $matchup_ids[0] ) && isset( $this->data['summary'] ) && $this->data['summary'];

			// If title or summary needs updating.
			if ( $needs_title || $needs_summary ) {
				$update_args = [
					'ID'             => $matchup_ids[0],
					'comment_status' => 'open',
				];

				// If title needs updating.
				if ( $needs_title ) {
					$update_args['post_title'] = $matchup_title;
				}

				// If summary needs updating.
				if ( $needs_summary ) {
					$update_args['post_excerpt'] = $this->body['summary'];
				}

				// Update the matchup.
				$this->matchup_id = wp_update_post( $update_args );

				// If no post ID, send error.
				if ( ! $this->matchup_id ) {
					$this->return = $this->get_error( 'Failed during wp_update_post()' );
					return;
				}

				// Bail if there was an error.
				if ( is_wp_error( $this->matchup_id ) ) {
					$this->return = $this->matchup_id;
					return;
				}
			}
			// Set the ID.
			else {
				$this->matchup_id = $matchup_ids[0];
			}
		}
		// If no matchup, create one.
		else {
			$matchup_args = [
				'post_type'      => 'matchup',
				'post_status'    => 'publish',
				'post_author'    => $this->user->ID,
				'post_title'     => $matchup_title,
				'post_name'      => sanitize_title( $matchup_title ) . ' ' . wp_date( 'Y-m-d', $matchup_timestamp ),
				'post_excerpt'   => $this->body['summary'],
				'comment_status' => 'open',
				'meta_input'     => [
					'event_uuid' => $this->body['event_uuid'], // The id of this specific event.
					'event_date' => $matchup_timestamp,        // The event date timestamp.
				],
			];

			// Insert the matchup post.
			$this->matchup_id = wp_insert_post( $matchup_args );

			// If no post ID, send error.
			if ( ! $this->matchup_id ) {
				$this->return = $this->get_error( 'Failed during wp_insert_post()' );
				return;
			}

			// Bail if there was an error.
			if ( is_wp_error( $this->matchup_id ) ) {
				$this->return = $this->matchup_id;
				return;
			}
		}

		// Meta title for Rank Math.
		if ( $this->data['home_team'] && $this->data['away_team'] ) {
			$meta_title     = sprintf( '%s vs %s Prediction and Insights %s', $this->data['home_team'], $this->data['home_team'], wp_date( 'F j, Y', $matchup_timestamp ) );
			$focus_keywords = strtolower( $this->data['home_team'] . ' vs ' . $this->data['home_team'] . ' matchup' );

			update_post_meta( $this->matchup_id, 'rank_math_title', $meta_title );
			update_post_meta( $this->matchup_id, 'rank_math_focus_keyword', strtolower( $focus_keywords ));
		}

		/***************************************************************
		 * Update the author/bot vote.
		 ***************************************************************/

		// If we have a prediction, add the vote.
		if ( $this->data['prediction'] ) {
			// Get bot ID.
			$bot_id = pm_get_bot_user_id();

			// If we have a valid user.
			if ( get_user_by( 'id', $bot_id ) ) {
				// Add author/bot vote.
				pm_update_user_vote( $this->matchup_id, $bot_id, $this->data['prediction'] );

				// Get spread covered.
				$spread_covered = $this->data['spread_covered'];

				// If we have a spread covered prediction.
				if ( ! is_null( $spread_covered ) ) {
					// Add author/bot vote for spread.
					pm_update_user_vote( $this->matchup_id, $bot_id, $this->data['prediction'], $spread_covered );
				}
			}
		}

		/***************************************************************
		 * Update Matchup Tags.
		 ***************************************************************/

		// Get people.
		$name_ids = [];
		$people   = $this->content['key_people'];

		// If we have people.
		if ( $people ) {
			// Loop through people.
			foreach ( $people as $person ) {
				// Early versions were a string of the person's name.
				if ( is_string( $person ) ) {
					$name = $person;
				}
				// We should be getting dict/array now.
				else {
					$name = isset( $person['name'] ) ? $person['name'] : '';
				}

				// Skip if no name.
				if ( ! $name ) {
					continue;
				}

				// Get or create the tag.
				$name_ids[] = $this->get_term( $name, 'matchup_tag' );
			}
		}

		// Remove empties.
		$name_ids = array_filter( $name_ids );

		// If names.
		if ( $name_ids ) {
			// Set the tags.
			wp_set_object_terms( $this->matchup_id, $name_ids, 'matchup_tag', $append = true );
		}

		/***************************************************************
		 * Set the league and season taxonomy terms.
		 ***************************************************************/

		// Get teams. This will create them if they don't exist.
		$league_id  = $this->get_term( $this->data['league'], 'league' );
		$league_ids = [
			$league_id,
			$this->data['away_team'] ? $this->get_term( $this->data['away_team'], 'league', $league_id ) : '',
			$this->data['home_team'] ? $this->get_term( $this->data['home_team'], 'league', $league_id ) : '',
		];

		// Remove empties.
		$league_ids = array_filter( $league_ids );

		// If we have categories.
		if ( $league_ids ) {
			// Set the league and teams.
			wp_set_object_terms( $this->matchup_id, $league_ids, 'league', $append = false );
			wp_set_object_terms( $this->insight_id, $league_ids, 'league', $append = false );
		}

		// Start season va.
		$season_id = null;

		// Check season.
		if ( $this->data['season'] ) {
			// Split season. Sometimes we saw '2024-2025-preseason'.
			$season = explode( '-', $this->data['season'] );
			$season = reset( $season );

			// Get or create the season term.
			$season_id = $this->get_term( $season, 'season' );
		}
		// No seasons, use timestamp.
		elseif ( $matchup_timestamp ) {
			// Get year from event date.
			$year = wp_date( 'Y', $matchup_timestamp );

			// If we have a year.
			if ( $year ) {
				// Get or create the year term.
				$season_id = $this->get_term( $year, 'season' );
			}
		}

		// If we have a season term.
		if ( $season_id ) {
			// Set the post season.
			wp_set_object_terms( $this->matchup_id, $season_id, 'season', $append = false );
			wp_set_object_terms( $this->insight_id, $season_id, 'season', $append = false );
		}

		/***************************************************************
		 * Update the matchup insights.
		 * Replace the insight titles.
		 ***************************************************************/

		// If event uuid.
		if ( ! is_null( $this->data['event_uuid'] ) ) {

			// Gets all insights, sorted by date.
			$insights = get_posts(
				[
					'post_type'    => 'insight',
					'post_status'  => 'any',
					'meta_key'     => 'event_uuid',
					'meta_value'   => $this->data['event_uuid'],
					'meta_compare' => '=',
					'fields'       => 'ids',
					'numberposts'  => -1,
					'orderby'      => 'date',
					'order'        => 'ASC',
				]
			);

			// Update all insights titles with the update number.
			if ( $insights ) {
				foreach ( $insights as $index => $id ) {
					// Build title with index.
					$updated_title = sprintf( '%s (%s #%s)', $matchup_title, __( 'Update', 'mai-asknews' ), $index + 1 );

					// Update post title.
					wp_update_post(
						[
							'ID'         => $id,
							'post_title' => $updated_title,
						]
					);
				}

				// Update the insights, sorted in reverse order, in the matchup post meta.
				update_post_meta( $this->matchup_id, 'insight_ids', array_reverse( $insights ) );
			}
		}


		/***************************************************************
		 * Next Step 0.3.0
		 ***************************************************************/

		// // Set post content. This runs after so we can attach images to the post ID.
		// $updated_id = wp_update_post(
		// 	[
		// 		'ID'           => $insight_id,
		// 		'post_content' => $this->handle_content( $content ),
		// 	]
		// );

		/***************************************************************
		 * End.
		 ***************************************************************/

		// Remove post_modified update filter.
		remove_filter( 'wp_insert_post_data', 'pm_prevent_post_modified_update', 10, 4 );

		$text         = $update ? ' updated successfully' : ' imported successfully';
		$this->return = $this->get_success( get_permalink( $this->insight_id ) . $text );
		return;
	}

	/**
	 * Gets a term ID by name. If it doesn't exist, it creates it.
	 *
	 * @since 0.1.0
	 *
	 * @param string $term_name The term name.
	 * @param string $taxonomy  The taxonomy.
	 * @param int    $parent_id The parent term ID.
	 *
	 * @return int|null The term ID.
	 */
	function get_term( $term_name, $taxonomy, $parent_id = 0 ) {
		// Check if the term already exists.
		$term    = get_term_by( 'name', $term_name, $taxonomy );
		$term_id = $term ? $term->term_id : null;

		// If the term doesn't exist, create it.
		if ( ! $term_id ) {
			$args    = is_taxonomy_hierarchical( $taxonomy ) ? [ 'parent' => $parent_id ] : [];
			$term    = wp_insert_term( $term_name, $taxonomy, $args );
			$term_id = $term && ! is_wp_error( $term ) ? $term['term_id'] : null;
		}

		return $term_id;
	}
}
