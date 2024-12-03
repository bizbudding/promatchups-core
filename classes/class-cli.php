<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

use Ramsey\Uuid\Uuid;
use League\CommonMark\CommonMarkConverter;

/**
 * Temporarily disable doing_it_wrong errors when WP_CLI is running.
 * This is for `_load_textdomain_just_in_time` errors since WP 6.7.
 *
 * TODO: Remove?
 *
 * @since 1.0.0
 *
 * @return bool
 */
add_filter( 'doing_it_wrong_trigger_error', function( $return ) {
	return defined( 'WP_CLI' ) && WP_CLI ? false : $return;
}, 999 );

/**
 * Gets it started.
 *
 * @since 0.1.0
 *
 * @link https://docs.wpvip.com/how-tos/write-custom-wp-cli-commands/
 * @link https://webdevstudios.com/2019/10/08/making-wp-cli-commands/
 *
 * @return void
 */
add_action( 'cli_init', function() {
	WP_CLI::add_command( 'promatchups', 'ProMatchups_CLI' );
});

/**
 * Main ProMatchups_CLI Class.
 *
 * @since 0.1.0
 */
class ProMatchups_CLI {
	protected $user;

	/**
	 * Construct the class.
	 */
	function __construct() {
		$username   = defined( 'PROMATCHUPS_AUTH_UN' ) ? PROMATCHUPS_AUTH_UN : null;
		$this->user = $username ? get_user_by( 'login', $username ) : null;
	}

	/**
	 * Gets environment.
	 *
	 * Usage: wp promatchups get_environment
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function get_environment() {
		WP_CLI::log( sprintf( 'Environment: %s', wp_get_environment_type() ) );
	}

	/**
	 * Creates a CSV file of votes.
	 *
	 * Usage:
	 * wp promatchups create_votes_csv --posts_per_page=10000000
	 * wp promatchups create_votes_csv --posts_per_page=1000 --date_after="Nov 27, 2024 12am EST" --date_before="Dec 6, 2024" --league=NFL
	 * wp promatchups create_votes_csv --posts_per_page=1000 --date_after="Nov 13, 2024 12am EST" --league=NFL
	 * wp promatchups create_votes_csv --posts_per_page=100 --league=NBA --vote_type=pm_spread --vote_result=1 --user_id=1
	 *
	 * @link https://docs.google.com/spreadsheets/d/1Bp5D2WeohFO8bMGCN5z9ePHapHjTRrM30-Cc6eDTkNs/edit?gid=1631607262#gid=1631607262
	 *
	 * @param array $args       Standard command args.
	 * @param array $assoc_args Keyed args like --posts_per_page and --offset.
	 *
	 * @return void
	 */
	function create_votes_csv( $args, $assoc_args ) {
		try {
			$start = microtime( true );
			WP_CLI::log( 'Starting...' );

			// Parse args.
			$assoc_args = wp_parse_args(
				$assoc_args,
				[
					'user_id'                => null,
					'league'                 => null,
					'vote_type'              => null,
					'vote_result'            => null,
					'post_type'              => 'matchup',
					'post_status'            => 'any',
					'posts_per_page'         => 100,
					'offset'                 => 0,
					'date_before'            => null,
					'date_after'             => null,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			// If no user ID, use bot.
			if ( is_null( $assoc_args['user_id'] ) ) {
				$assoc_args['user_id'] = pm_get_bot_user_id();
			}

			// If we have a league, add it.
			if ( ! is_null( $assoc_args['league'] ) ) {
				$assoc_args['tax_query'] = [
					[
						'taxonomy' => 'league',
						'field'    => 'slug',
						'terms'    => strtolower( $assoc_args['league'] ),
					],
				];
			}

			// If we have date restrictions.
			if ( $assoc_args['date_before'] || $assoc_args['date_after'] ) {
				// Meta query for 'event_date', in timestamp format.
				// Check if we have before and after or just one.
				if ( $assoc_args['date_after'] && $assoc_args['date_before'] ) {
					$assoc_args['meta_query'] = [
						[
							'key'     => 'event_date',
							'value'   => [ strtotime( $assoc_args['date_after'] ), strtotime( $assoc_args['date_before'] ) ],
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						],
					];
				} elseif ( $assoc_args['date_before'] ) {
					$assoc_args['meta_query'] = [
						[
							'key'     => 'event_date',
							'value'   => strtotime( $assoc_args['date_before'] ),
							'compare' => '<=',
							'type'    => 'NUMERIC',
						],
					];
				} elseif ( $assoc_args['date_after'] ) {
					$assoc_args['meta_query'] = [
						[
							'key'     => 'event_date',
							'value'   => strtotime( $assoc_args['date_after'] ),
							'compare' => '>=',
							'type'    => 'NUMERIC',
						],
					];
				}

				// Unset so they don't mess with WP_Query.
				unset( $assoc_args['date_before'], $assoc_args['date_after'] );
			}

			// Start rows.
			$rows = [];

			// Headers.
			$headers = [
				'post_id',          // Int.
				'user_id',          // Int.
				'league',           // MLB, NFL, etc.
				'matchup',          // 'Boston Red Sox vs New York Yankees',
				'event_date',       // 'Y-m-d H:i:s'.
				'away_name',        // Boston Red Sox.
				'away_score',       // Int.
				'home_name',        // New York Yankees.
				'home_score',       // Int.
				'vote_type',        // pm_vote, pm_spread.
				'probability',      // 85.
				'moneyline_vote',   // Team name.
				'moneyline_result', // 1 for win, -1 for loss, 2 for push.
				'spread_vote',      // Team name.
				'spread_covered',   // 1 for covered, -1 for not covered, 2 for push (if that's even a thing?). This matches the comment karma.
				'spread_used',      // -5.5, 2.5, etc.
				'spread_result',    // 1 for win, -1 for loss, 2 for push.
				'model_used',       // chatgpt, etc.
			];

			// Get posts.
			$query = new WP_Query( $assoc_args );

			// If we have posts.
			if ( $query->have_posts() ) {
				// Log how many total posts found.
				WP_CLI::log( 'Posts found: ' . $query->post_count );

				// Loop through posts.
				while ( $query->have_posts() ) : $query->the_post();
					$matchup_id   = get_the_ID();
					$data         = pm_get_matchup_data( $matchup_id );
					$date         = isset( $data['date'] ) ? wp_date( 'Y-m-d H:i:s', $data['date'] ) : wp_date( 'Y-m-d H:i:s' );
					$comment_args = [
						'type'    => isset( $assoc_args['vote_type'] ) && ! is_null( $assoc_args['vote_type'] ) ? $assoc_args['vote_type'] : [ 'pm_vote', 'pm_spread' ],
						'post_id' => $matchup_id,
						'status'  => 'approve',
					];

					// If user ID, add it.
					if ( ! is_null( $assoc_args['user_id'] ) ) {
						$comment_args['author__in'] = explode( ',', $assoc_args['user_id'] );
					}

					// If vote result, add it.
					if ( ! is_null( $assoc_args['vote_result'] ) ) {
						$comment_args['karma'] = $assoc_args['vote_result'];
					}

					// Get comments.
					$comments = get_comments( $comment_args );

					// Skip if no comments.
					if ( ! $comments ) {
						continue;
					}

					// Loop through comments.
					foreach ( $comments as $comment ) {
						// Skip if no karma. We only want votes with results.
						if ( 0 === (int) $comment->comment_karma ) {
							continue;
						}

						// Set row.
						$row = [
							'post_id'          => $matchup_id,
							'user_id'          => $comment->user_id,
							'league'           => $data['league'],
							'matchup'          => $data['away_team'] . ' vs ' . $data['home_team'],
							'event_date'       => $date,
							'away_name'        => $data['away_team'],
							'away_score'       => $data['away_score'],
							'home_name'        => $data['home_team'],
							'home_score'       => $data['home_score'],
							'vote_type'        => $comment->comment_type,
							'probability'      => $data['probability'],
							'moneyline_vote'   => null,
							'moneyline_result' => null,
							'spread_vote'      => null,
							'spread_covered'   => null,
							'spread_used'      => null,
							'spread_result'    => null,
							'model_used'       => $data['model_used'],
						];

						// If moneyline vote.
						if ( 'pm_vote' === $comment->comment_type ) {
							$row['moneyline_vote']   = $comment->comment_content;
							$row['moneyline_result'] = $comment->comment_karma;
						}
						// If spread vote.
						else {
							// Set spread covered.
							if ( ! is_null( $data['spread_covered'] ) ) {
								$spread_covered = $data['spread_covered'] ? 1 : -1;
							} else {
								$spread_covered = 0;
							}

							// Set spread data.
							$spread_used = isset( $data['spreads'][ $comment->comment_content ]['spread_used'] ) ? $data['spreads'][ $comment->comment_content ]['spread_used'] : null;

							// Add row.
							$row['spread_vote']      = $comment->comment_content;
							$row['spread_covered']   = $spread_covered;
							$row['spread_used']      = $spread_used;
							$row['spread_result']    = $comment->comment_karma;
						}

						// Add row.
						$rows[] = array_values( $row );

						// // If no row, create it.
						// if ( ! isset( $rows[ $matchup_id ] ) ) {
						// 	$rows[ $matchup_id ] = [
						// 		'post_id'          => $matchup_id,
						// 		'user_id'          => $comment->user_id,
						// 		'league'           => $data['league'],
						// 		'matchup'          => $data['away_team'] . ' vs ' . $data['home_team'],
						// 		'event_date'       => $date,
						// 		'away_name'        => $data['away_team'],
						// 		'away_score'       => $data['away_score'],
						// 		'home_name'        => $data['home_team'],
						// 		'home_score'       => $data['home_score'],
						// 		'vote_type'        => $comment->comment_type,
						// 		'probability'      => $data['probability'],
						// 		'moneyline_vote'   => null,
						// 		'moneyline_result' => null,
						// 		'spread_vote'      => null,
						// 		'spread_covered'   => null,
						// 		'spread_used'      => null,
						// 		'spread_result'    => null,
						// 		'model_used'       => $data['model_used'],
						// 	];
						// }

						// // Add type specific values.
						// switch ( $comment->comment_type ) {
						// 	case 'pm_vote':
						// 		$rows[ $matchup_id ]['moneyline_vote']   = $comment->comment_content;
						// 		$rows[ $matchup_id ]['moneyline_result'] = $comment->comment_karma;
						// 	break;
						// 	case 'pm_spread':
						// 		$rows[ $matchup_id ]['spread_vote']      = $comment->comment_content;
						// 		$rows[ $matchup_id ]['spread_covered']   = $spread_covered;
						// 		$rows[ $matchup_id ]['spread_used']      = $spread_mode;
						// 		$rows[ $matchup_id ]['spread_result']    = $spread_result;
						// 	break;
						// }
					}

				endwhile;
			}
			wp_reset_postdata();

			// Bail if no rows.
			if ( ! $rows ) {
				WP_CLI::error( 'No rows found.' );
				return;
			}

			// Get filename.
			// Saves to current directory
			$filename = getcwd() . '/votes_' . date( 'Y-m-d-H-i-s' ) . '.csv';

			// Open the file for writing.
			$output = fopen( $filename, 'w' );

			// Add headers.
			fputcsv( $output, $headers );

			// Add rows.
			foreach ( $rows as $row ) {
				fputcsv( $output, $row );
			}

			// Close the file.
			fclose( $output );

			// Log time.
			WP_CLI::log( 'Process time: ' . microtime( true ) - $start . ' seconds' );

			// Count rows.
			$count = count( $rows );

			// Success.
			WP_CLI::success( "CSV file with {$count} rows created at {$filename}" );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf(
				'Error: %s in %s on line %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			) );
		}
	}

	/**
	 * Update bot votes from matchups.
	 *
	 * Usage: wp promatchups update_bot_votes --posts_per_page=10 --offset=0
	 *
	 * @param array $args       Standard command args.
	 * @param array $assoc_args Keyed args like --posts_per_page and --offset.
	 *
	 * @return void
	 */
	function update_bot_votes( $args, $assoc_args ) {
		try {
			// Parse args.
			$assoc_args = wp_parse_args(
				$assoc_args,
				[
					'post_type'              => 'matchup',
					'post_status'            => 'any',
					'posts_per_page'         => 10,
					'offset'                 => 0,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			// Get posts.
			$query = new WP_Query( $assoc_args );

			// If we have posts.
			if ( $query->have_posts() ) {
				// Log how many total posts found.
				WP_CLI::log( 'Posts found: ' . $query->post_count );

				// Loop through posts.
				while ( $query->have_posts() ) : $query->the_post();
					$matchup_id  = get_the_ID();
					$bot_id      = pm_get_bot_user_id();
					$bot_id_s    = pm_get_spreadbot_user_id();
					// $bot_id_ml   = pm_get_moneylinebot_user_id();
					$data        = pm_get_matchup_data( $matchup_id );
					$team        = $data['prediction'];
					$favored     = $data['favored'];
					$probability = $data['probability'];

					// Start counts.
					$comments = 0;
					$skipped  = 0;

					// If team, update main bot votes.
					if ( $team ) {
						$moneyline_id = 0;
						$spread_id    = 0;

						// Update vote.
						$moneyline_id = pm_update_user_vote( $matchup_id, $bot_id, $team );

						// Get spread covered prediction.
						$spread_covered = $data['spread_covered'];

						// If we have a spread covered prediction.
						if ( ! is_null( $spread_covered ) ) {
							// Update spread vote.
							$spread_id = pm_update_user_vote( $matchup_id, $bot_id, $team, $spread_covered );
						}

						// If comment ID, add it.
						if ( $moneyline_id ) {
							$comments++;
						} else {
							$skipped++;
						}

						// If comment ID, add it.
						if ( $spread_id ) {
							$comments++;
						} else {
							$skipped++;
						}
					}

					// If favored team, update moneyline bot votes.
					if ( $favored ) {
						$moneyline_id = 0;
						$spread_id    = 0;

						// Update vote.
						$moneyline_id = pm_update_user_vote( $matchup_id, $bot_id_s, $favored );

						// // If we have a probability.
						// if ( $probability ) {
						// 	// If the probability is >= N%, the spread is covered.
						// 	$favored_spread_covered = (int) $probability >= 65;

						// 	// Add spread vote.
						// 	$spread_id = pm_update_user_vote( $matchup_id, $bot_id_s, $favored, $favored_spread_covered );
						// }

						// If comment ID, add it.
						if ( $moneyline_id ) {
							$comments++;
						} else {
							$skipped++;
						}

						// // If comment ID, add it.
						// if ( $spread_id ) {
						// 	$comments++;
						// } else {
						// 	$skipped++;
						// }
					}

					// If comments.
					if ( $comments ) {
						WP_CLI::log( $comments . ' bot vote(s) updated for post ID: ' . $matchup_id . ' ' . get_permalink() );
					}

					// If skipped.
					if ( $skipped ) {
						WP_CLI::log( $skipped . ' bot vote(s) skipped for post ID: ' . $matchup_id . ' ' . get_permalink() );
					}

				endwhile;

			} else {
				WP_CLI::log( 'No posts found.' );
			}

			wp_reset_postdata();

			WP_CLI::success( 'Done.' );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf(
				'Error: %s in %s on line %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			) );
		}
	}

	/**
	 * Updates user points.
	 *
	 * Usage: wp promatchups update_user_points --number=10 --offset=0
	 * Usage: wp promatchups update_user_points --number=1 --include=2
	 *
	 * @param array $args       Standard command args.
	 * @param array $assoc_args Keyed args like --number, --offset and --include.
	 *
	 * @return void
	 */
	function update_user_points( $args, $assoc_args ) {
		try {
			// Parse args.
			$assoc_args = wp_parse_args(
				$assoc_args,
				[
					'number' => 10,
					'offset' => 0,
					'fields' => 'ID',
				]
			);

			// Get users.
			$users = get_users( $assoc_args );

			// If users.
			if ( $users ) {
				// Log how many total users found.
				WP_CLI::log( 'Users found: ' . count( $users ) );

				// Loop through users.
				foreach ( $users as $user_id ) {
					// Log user display name.
					WP_CLI::log( 'Calculating totals for: ' . get_the_author_meta( 'display_name', $user_id ) );

					// Get listener response.
					$listener = new ProMatchups_User_Points( $user_id );
					$response = $listener->get_response();

					// Log response.
					if ( is_wp_error( $response ) ) {
						WP_CLI::log( 'Error: ' . $response->get_error_message() );
					} else {
						WP_CLI::log( 'Success: ' . $response->get_data() );
					}
				}
			}
			// No users.
			else {
				WP_CLI::log( 'No users found.' );
			}

			WP_CLI::success( 'Done.' );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf(
				'Error: %s in %s on line %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			) );
		}
	}

	/**
	 * Update votes from matchups.
	 *
	 * Usage: wp promatchups update_matchup_votes --posts_per_page=10 --offset=0
	 *
	 * @param array $args       Standard command args.
	 * @param array $assoc_args Keyed args like --posts_per_page and --offset.
	 *
	 * @return void
	 */
	function update_matchup_votes( $args, $assoc_args ) {
		try {
			// Parse args.
			$assoc_args = wp_parse_args(
				$assoc_args,
				[
					'post_type'              => 'matchup',
					'post_status'            => 'any',
					'posts_per_page'         => 10,
					'offset'                 => 0,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'comment_count'          => [
						'value'   => 1,
						'compare' => '>=',
					],
				]
			);

			// Get posts.
			$query = new WP_Query( $assoc_args );

			// If we have posts.
			if ( $query->have_posts() ) {
				// Log how many total posts found.
				WP_CLI::log( 'Posts found: ' . $query->post_count );

				// Loop through posts.
				while ( $query->have_posts() ) : $query->the_post();
					$matchup_id = get_the_ID();
					$data       = pm_get_matchup_data( $matchup_id );
					$comments   = get_comments(
						[
							'type'    => [ 'pm_vote', 'pm_spread' ],
							'post_id' => $matchup_id,
							'status'  => 'approve',
						]
					);

					if ( ! $comments ) {
						WP_CLI::log( 'No votes found for post ID: ' . $matchup_id . ' ' . get_permalink() );
						continue;
					}

					// Get event date.
					$event_date = isset( $data['date'] ) && $data['date'] ? wp_date( 'Y-m-d H:i:s', $data['date'] ) : wp_date( 'Y-m-d H:i:s' );

					// Loop through comments.
					foreach ( $comments as $comment ) {
						wp_update_comment(
							[
								'comment_ID'    => $comment->comment_ID,
								'comment_date'  => $event_date,            // Set only local time, WP will also save gmt.
								'comment_agent' => $data['league'],
							]
						);
					}

					// Matchup updated.
					WP_CLI::log( 'Matchup votes updated for post ID: ' . $matchup_id . ' ' . get_permalink() );

				endwhile;
			} else {
				WP_CLI::log( 'No posts found.' );
			}

			wp_reset_postdata();

			WP_CLI::success( 'Done.' );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf(
				'Error: %s in %s on line %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			) );
		}
	}

	/**
	 * Processes outcomes from stored AskNews data.
	 *
	 * Usage: wp promatchups update_matchup_outcomes --posts_per_page=10 --offset=0
	 *        wp promatchups update_matchup_outcomes --posts_per_page=10000 --update_points=false
	 *
	 * @since 0.1.0
	 *
	 * @param array $args       Standard command args.
	 * @param array $assoc_args Keyed args like --posts_per_page and --offset.
	 *
	 * @return void
	 */
	function update_matchup_outcomes( $args, $assoc_args ) {
		try {
			// Parse args.
			$assoc_args = wp_parse_args(
				$assoc_args,
				[
					'post_type'              => 'matchup',
					'post_status'            => 'any',
					'posts_per_page'         => 10,
					'offset'                 => 0,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'comment_count'          => [
						'value'   => 1,
						'compare' => '>=',
					],
					// Do we need to check for past games?
					// The outcome should not even be here if the game has not been played.

					// Make sure the outcome is not empty.
					'meta_query'             => [
						[
							'key'     => 'asknews_outcome',
							'value'   => '',
							'compare' => '!=',
						],
					],

					// Update points.
					'update_points'          => true,
				]
			);

			// Update points.
			$update_points = rest_sanitize_boolean( $assoc_args['update_points'] );
			unset( $assoc_args['update_points'] );

			// Get posts.
			$query = new WP_Query( $assoc_args );

			// If we have posts.
			if ( $query->have_posts() ) {
				// Log how many total posts found.
				WP_CLI::log( 'Posts found: ' . $query->post_count );

				// Loop through posts.
				while ( $query->have_posts() ) : $query->the_post();
					// Get matchup listener response.
					$matchup_id = get_the_ID();
					$listener   = new ProMatchups_Outcome_Votes_Listener( $matchup_id, $update_points );
					$response   = $listener->get_response();

					if ( is_wp_error( $response ) ) {
						WP_CLI::log( 'Error: ' . $response->get_error_message() );
					} else {
						WP_CLI::log( 'Success: ' . $response->get_data() );
					}

				endwhile;
			} else {
				WP_CLI::log( 'No posts found.' );
			}

			wp_reset_postdata();

			WP_CLI::success( 'Done.' );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf(
				'Error: %s in %s on line %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			) );
		}
	}

	/**
	 * Updates posts from stored AskNews data.
	 *
	 * Usage: wp promatchups update_insights --posts_per_page=10 --offset=0
	 *
	 * @since 0.1.0
	 *
	 * @param array $args       Standard command args.
	 * @param array $assoc_args Keyed args like --posts_per_page and --offset.
	 *
	 * @return void
	 */
	function update_insights( $args, $assoc_args ) {
		try {
			// Parse args.
			$assoc_args = wp_parse_args(
				$assoc_args,
				[
					'post_type'              => 'insight',
					'post_status'            => 'any',
					'posts_per_page'         => 10,
					'offset'                 => 0,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			// Get posts.
			$query = new WP_Query( $assoc_args );

			// If we have posts.
			if ( $query->have_posts() ) {
				// Log how many total posts found.
				WP_CLI::log( 'Posts found: ' . $query->post_count );

				// Loop through posts.
				while ( $query->have_posts() ) : $query->the_post();
					$asknews_body = get_post_meta( get_the_ID(), 'asknews_body', true );

					if ( ! $asknews_body ) {
						WP_CLI::log( 'No AskNews data found for post ID: ' . get_the_ID() . ' ' . get_permalink() );
						continue;
					}

					$listener = new ProMatchups_Matchup_Listener( $asknews_body, $this->user );
					$response = $listener->get_response();

					if ( is_wp_error( $response ) ) {
						WP_CLI::log( 'Error: ' . $response->get_error_message() );
					} else {
						WP_CLI::log( 'Success: ' . $response->get_data() );
					}

				endwhile;
			} else {
				WP_CLI::log( 'No posts found.' );
			}

			wp_reset_postdata();

			WP_CLI::success( 'Done.' );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf(
				'Error: %s in %s on line %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			) );
		}
	}

	/**
	 * Gets example json files from /examples/insights/*.json and hits our endpoint.
	 *
	 * 1. Create an application un/pw via your WP user account.
	 * 2. Set un/pw in wp-config.php via `PROMATCHUPS_AUTH_UN` (user login name) and `PROMATCHUPS_AUTH_PW` (application password) constants.
	 * 3. Copy the path to this file.
	 * 4. Execute via command line:
	 *    wp promatchups test_insights --max=2
	 *
	 * @since 0.1.0
	 *
	 * @param array $args       Standard command args.
	 * @param array $assoc_args Keyed args like --max.
	 *
	 * @return void
	 */
	function test_insights( $args, $assoc_args ) {
		try {
			WP_CLI::log( 'Starting...' );

			// Parse args.
			$assoc_args = wp_parse_args(
				$assoc_args,
				[
					'max' => 10,
				]
			);

			if ( ! defined( 'PROMATCHUPS_PLUGIN_DIR' ) || ! is_dir( PROMATCHUPS_PLUGIN_DIR . 'examples' ) ) {
				WP_CLI::error( 'PROMATCHUPS_PLUGIN_DIR is not defined or the examples directory is missing.' );
				return;
			}

			// Set data.
			$url      = home_url( '/wp-json/asknews/v1/insight/' );
			$name     = defined( 'PROMATCHUPS_AUTH_UN' ) ? PROMATCHUPS_AUTH_UN : '';
			$password = defined( 'PROMATCHUPS_AUTH_PW' ) ? PROMATCHUPS_AUTH_PW : '';

			if ( ! $name ) {
				WP_CLI::error( 'No name found via PROMATCHUPS_AUTH_UN constant.' );
				return;
			}

			if ( ! $password ) {
				WP_CLI::error( 'No password found via PROMATCHUPS_AUTH_PW constant.' );
				return;
			}

			// Get all json files in examples directory.
			$files = glob( PROMATCHUPS_PLUGIN_DIR . 'examples/insights/*.json' );

			// Start count.
			$i = 1;

			// Loop through files.
			foreach ( $files as $file ) {
				// Break if max reached.
				if ( $i > (int) $assoc_args['max'] ) {
					break;
				}

				// Increment count.
				$i++;

				// Check if file exists.
				if ( ! file_exists( $file ) ) {
					WP_CLI::log( 'File does not exists via ' . $file );
					continue;
				}

				// Data to be sent in the JSON packet.
				// Get content from json file.
				$data = file_get_contents( $file );

				// Bail if no test data.
				if ( ! $data ) {
					WP_CLI::log( 'No data found via ' . $file );
					continue;
				}

				// Prepare the request arguments.
				$args = [
					'method'  => 'POST',
					'headers' => [
						'Authorization' => 'Basic ' . base64_encode( $name . ':' . $password ),
					],
					'body'      => $data,
					'sslverify' => 'local' !== wp_get_environment_type(),
					'timeout'   => 20,
				];

				// Make the POST request.
				$response = wp_remote_post( $url, $args );

				// If error.
				if ( is_wp_error( $response ) ) {
					WP_CLI::log( 'Error: ' . $response->get_error_message() );
				}
				// Success.
				else {
					// Get code and decode the response body.
					$code = wp_remote_retrieve_response_code( $response );
					$body = wp_remote_retrieve_body( $response );
					$body = json_decode( $body, true );

					// If error.
					if ( 200 !== $code ) {
						$message = $body && isset( $body['message'] ) ? $body['message'] : '';

						WP_CLI::log( 'Error: ' . $message );
						continue;
					}

					// If success.
					WP_CLI::log( $code . ' : ' . $body );
				}
			}

			WP_CLI::success( 'Done.' );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf(
				'Error: %s in %s on line %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			) );
		}
	}

	/**
	 * Gets example json files from /examples/outcomes/*.json and hits our endpoint.
	 *
	 * 1. Create an application un/pw via your WP user account.
	 * 2. Set un/pw in wp-config.php via `PROMATCHUPS_AUTH_UN` (user login name) and `PROMATCHUPS_AUTH_PW` (application password) constants.
	 * 3. Copy the path to this file.
	 * 4. Execute via command line:
	 *    wp promatchups test_outcomes --max=2
	 *
	 * @since 0.1.0
	 *
	 * @param array $args       Standard command args.
	 * @param array $assoc_args Keyed args like --max.
	 *
	 * @return void
	 */
	function test_outcomes( $args, $assoc_args ) {
		try {
			WP_CLI::log( 'Starting...' );

			// Parse args.
			$assoc_args = wp_parse_args(
				$assoc_args,
				[
					'max' => 10,
				]
			);

			if ( ! defined( 'PROMATCHUPS_PLUGIN_DIR' ) || ! is_dir( PROMATCHUPS_PLUGIN_DIR . 'examples' ) ) {
				WP_CLI::error( 'PROMATCHUPS_PLUGIN_DIR is not defined or the examples directory is missing.' );
				return;
			}

			// Set data.
			$url      = home_url( '/wp-json/asknews/v1/outcome/' );
			$name     = defined( 'PROMATCHUPS_AUTH_UN' ) ? PROMATCHUPS_AUTH_UN : '';
			$password = defined( 'PROMATCHUPS_AUTH_PW' ) ? PROMATCHUPS_AUTH_PW : '';

			if ( ! $name ) {
				WP_CLI::error( 'No name found via PROMATCHUPS_AUTH_UN constant.' );
				return;
			}

			if ( ! $password ) {
				WP_CLI::error( 'No password found via PROMATCHUPS_AUTH_PW constant.' );
				return;
			}

			// Get all json files in examples directory.
			$files = glob( PROMATCHUPS_PLUGIN_DIR . 'examples/outcomes/*.json' );

			// Start count.
			$i = 1;

			// Loop through files.
			foreach ( $files as $file ) {
				// Break if max reached.
				if ( $i > (int) $assoc_args['max'] ) {
					break;
				}

				// Increment count.
				$i++;

				// Check if file exists.
				if ( ! file_exists( $file ) ) {
					WP_CLI::log( 'File does not exists via ' . $file );
					continue;
				}

				// Data to be sent in the JSON packet.
				// Get content from json file.
				$data = file_get_contents( $file );

				// Bail if no test data.
				if ( ! $data ) {
					WP_CLI::log( 'No data found via ' . $file );
					continue;
				}


				// Prepare the request arguments.
				$args = [
					'method'  => 'POST',
					'headers' => [
						'Authorization' => 'Basic ' . base64_encode( $name . ':' . $password ),
					],
					'body'      => $data,
					'sslverify' => 'local' !== wp_get_environment_type(),
					'timeout'   => 20,
				];

				// Make the POST request.
				$response = wp_remote_post( $url, $args );

				// If error.
				if ( is_wp_error( $response ) ) {
					WP_CLI::log( 'Error: ' . $response->get_error_message() );
				}
				// Success.
				else {
					// Get code and decode the response body.
					$code = wp_remote_retrieve_response_code( $response );
					$body = wp_remote_retrieve_body( $response );
					$body = json_decode( $body, true );

					// If error.
					if ( 200 !== $code ) {
						$message = $body && isset( $body['message'] ) ? $body['message'] : '';

						WP_CLI::log( 'Error: ' . $message );
						continue;
					}

					// If success.
					WP_CLI::log( $code . ' : ' . $body );
				}
			}

			WP_CLI::success( 'Done.' );
		} catch ( Exception $e ) {
			WP_CLI::error( sprintf(
				'Error: %s in %s on line %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			) );
		}
	}
}