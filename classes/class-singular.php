<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The singular class.
 *
 * @since 0.1.0
 */
class ProMatchups_Singular {
	/**
	 * The matchup ID.
	 *
	 * @var int
	 */
	protected $matchup_id;

	/**
	 * The current user.
	 *
	 * @var WP_User|false
	 */
	protected $user;

	/**
	 * The matchup data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The matchup content.
	 *
	 * @var array
	 */
	protected $content;

	/**
	 * The matchup insights.
	 *
	 * @var array
	 */
	protected $insights;

	/**
	 * The current vote team ID.
	 *
	 * @var int
	 */
	protected $vote_id;

	/**
	 * The current vote name.
	 *
	 * @var string
	 */
	protected $vote_name;

	/**
	 * If the user is pro squad.
	 *
	 * @var bool
	 */
	protected $is_pro_squad;

	/**
	 * Construct the class.
	 *
	 * @since 0.1.0
	 */
	function __construct() {
		add_action( 'template_redirect', [ $this, 'run' ] );
	}

	/**
	 * Maybe run the hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function run() {
		if ( ! is_singular( 'matchup' ) ) {
			return;
		}

		// Set initial data.
		$this->matchup_id = get_the_ID();
		$this->user       = wp_get_current_user();
		$this->data       = pm_get_matchup_data( $this->matchup_id );
		$this->content    = pm_get_matchup_content( $this->matchup_id );
		$event_uuid       = get_post_meta( $this->matchup_id, 'event_uuid', true );

		// If event uuid.
		if ( $event_uuid ) {
			$this->insights = get_posts(
				[
					'post_type'    => 'insight',
					'orderby'      => 'date',
					'order'        => 'DESC',
					'meta_key'     => 'event_uuid',
					'meta_value'   => $event_uuid,
					'meta_compare' => '=',
					'fields'       => 'ids',
					'numberposts'  => -1,
				]
			);
		}
		// No event uuid, no insights.
		else {
			$this->insights = [];
		}

		// Set body and pro squad. Body needs insights first.
		$this->is_pro_squad = $this->user->ID && pm_has_role( 'pro_squad', $this->user->ID );

		// Add hooks.
		add_action( 'wp_head',                            [ $this, 'do_head' ] );
		add_filter( 'genesis_markup_entry-title_content', [ $this, 'handle_title' ], 10, 2 );
		add_action( 'mai_after_entry_title',              [ $this, 'handle_descriptive_title' ], 6 );
		add_action( 'mai_after_entry_title',              [ $this, 'do_event_info' ], 8 );
		add_action( 'mai_after_entry_content_inner',      [ $this, 'do_content' ] );
		add_action( 'mai_after_entry_content_inner',      [ $this, 'do_updates' ] );
	}

	// Maybe later to make pro squad commentary textarea a wysiwyg.
	function do_head() {}

	/**
	 * Handle the title links and styles.
	 *
	 * @since 0.1.0
	 *
	 * @param string $content The default content.
	 * @param array  $args    The markup args.
	 *
	 * @return string
	 */
	function handle_title( $content, $args ) {
		if ( ! isset( $args['params']['args']['context'] ) ||  'single' !== $args['params']['args']['context'] ) {
			return $content;
		}

		// Bail if no league.
		if ( ! $this->data['league'] ) {
			return $content;
		}

		// Get teams terms.
		$terms = get_the_terms( get_the_ID(), 'league' );
		$terms = $terms && ! is_wp_error( $terms ) ? array_filter( $terms, function( $term ) { return $term->parent > 0; }) : [];
		$teams = $terms ? wp_list_pluck( $terms, 'term_id', 'name' ) : [];

		// Bail if no teams.
		if ( ! $teams ) {
			return $content;
		}

		// Get team data.
		$data = pm_get_teams( $this->data['league'] );

		// Build array from title.
		$array = explode( ' vs ', $content );

		// Loop through teams.
		foreach ( $array as $team ) {
			// Get code and color.
			$city  = isset( $data[ $team ]['city'] ) ? $data[ $team ]['city'] : '';
			$code  = isset( $data[ $team ]['code'] ) ? $data[ $team ]['code'] : '';
			$color = isset( $data[ $team ]['color'] ) ? $data[ $team ]['color'] : '';

			// Skip if no city, code, or color.
			if ( ! ( $city && $code && $color ) || ! isset( $teams[ "$city $team" ] ) ) {
				continue;
			}

			// Replace the team with the code and color.
			$replace = sprintf( '<a class="entry-title-team__link" href="%s" style="--team-color:%s;" data-code="%s"><span class="entry-title-team__name">%s</span></a>', get_term_link( $teams[ "$city $team" ] ), $color, $code, $team );
			$content = str_replace( $team, $replace, $content );

			// Add span to vs.
			$content = str_replace( ' vs ', ' <span class="entry-title__vs">vs</span> ', $content );
		}

		return $content;
	}

	/**
	 * Handle the descriptive title.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function handle_descriptive_title() {
		// Bail if no title.
		if ( ! $this->content['title'] ) {
			return;
		}

		printf( '<h2 class="pm-title">%s</h2>', $this->content['title'] );
	}

	/**
	 * Do the event info.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function do_event_info() {
		echo pm_get_updated_date();
	}

	/**
	 * Do the insight content.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $first Whether this is the first insight.
	 *
	 * @return void
	 */
	function do_content( $first = true ) {
		// If first.
		if ( $first ) {
			$this->do_jumps();
			$this->do_votes();
			$this->do_pro_squad();
			$this->do_commentary();
		}

		$this->do_prediction( ! pm_has_access(), $first );
		$this->do_people( $first );

		// Do CCA hook for Matchup Promo 1 (Mai Content Areas)
		do_action( 'pm_promo_cca1', $this->data, $first );

		$this->do_injuries( $first );
		$this->do_timeline( $first );

		// Do CCA hook for Matchup Promo 2 (Mai Content Areas)
		do_action( 'pm_promo_cca2', $this->data, $first );

		$this->do_sources( $first ); // displayed on the site as Latest News
		$this->do_search_results( $first );
	}

	/**
	 * Display the general content.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function do_jumps() {
		// If odds.
		$odds = pm_has_access() ? $this->data['odds'] : null;

		// Display the nav.
		echo '<ul class="pm-jumps">';
			if ( $odds ) {
				printf( '<li class="pm-jump"><a class="pm-jump__link" href="#odds">%s</a></li>', __( 'Odds', 'promatchups' ) );
			}
			printf( '<li class="pm-jump"><a class="pm-jump__link" href="#people">%s</a></li>', __( 'People', 'promatchups' ) );
			printf( '<li class="pm-jump"><a class="pm-jump__link" href="#timeline">%s</a></li>', __( 'Timeline', 'promatchups' ) );
			printf( '<li class="pm-jump"><a class="pm-jump__link" href="#sources">%s</a></li>', __( 'Latest News', 'promatchups' ) );

			// TODO: Better name for external sources and sites talking about this.
			printf( '<li class="pm-jump"><a class="pm-jump__link" href="#web">%s</a></li>', __( 'Mentions', 'promatchups' ) );

			// If comments open.
			if ( comments_open() ) {
				printf( '<li class="pm-jump"><a class="pm-jump__link" href="#respond">%s</a></li>', __( 'Comments', 'promatchups' ) );
			}

			// if ( $this->insights ) {
			// 	printf( '<li class="pm-jump"><a class="pm-jump__link" href="#updates">%s</a></li>', __( 'Updates', 'promatchups' ) );
			// }
		echo '</ul>';
	}

	/**
	 * Display the vote box.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function do_votes() {
		// Display the vote box.
		echo pm_get_singular_vote_box();
	}

	/**
	 * Display the pro squad commentary submission.
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	function do_pro_squad() {
		// Bail if not pro squad.
		if ( ! $this->is_pro_squad ) {
			return;
		}

		// Check for existing commentary.
		$commented = get_comments(
			[
				'type'    => 'pm_commentary',
				'status'  => 'approve',
				'post_id' => $this->matchup_id,
				'user_id' => $this->user->ID,
			]
		);

		// Bail if already commented.
		if ( $commented ) {
			return;
		}

		// Get user avatar and name.
		$avatar = get_avatar( $this->user->ID, 64 );
		$name  = $this->user->display_name;

		echo '<div class="pm-commentarysub">';
			// Header.
			echo '<div class="pm-commentarysub__header">';
				printf( '<div class="pm-commentarysub__avatar">%s</div>', $avatar );
				printf( '<div class="pm-commentarysub__heading">%s</div>', sprintf( __( 'Hey %s, add your commentary below!', 'promatchups' ), $name ) );
				printf( '<div class="pm-commentarysub__desc">%s</div>', __( 'Pro members have access to exclusive commentary from the Pro Squad members like you.', 'promatchups' ) );
			echo '</div>';

			// Textarea for commentary.
			printf( '<form class="pm-commentarysub__form" action="%s" data-ajax="%s" method="post">', esc_url( admin_url('admin-post.php') ), esc_url( admin_url('admin-ajax.php') ) );
				// Add a basic textarea.
				echo '<textarea id="pm-commentarysub-textarea" class="pm-commentarysub__textarea" rows="5" name="commentary_text" placeholder="Add your commentary here..."></textarea>';

				// Add div for JS editor.
				// echo '<div id="pm-commentarysub" class="pm-commentarysub__textarea"></div>';
				echo '<div id="pm-commentarysub" class="pm-commentarysub__textarea"></div>';

				// Add submit button and hidden fields.
				printf( '<button class="pm-commentarysub__submit button button-secondary button-small" type="submit">%s</button>', __( 'Add Commentary', 'promatchups' ) );
				printf( '<input type="hidden" name="action" value="pm_commentary_submission">' );
				printf( '<input type="hidden" name="commentary_nonce" value="%s" />' , wp_create_nonce( 'pm_commentary_submission' ) );
				printf( '<input type="hidden" name="commentary_user_id" value="%s"/>', $this->user->ID );
				printf( '<input type="hidden" name="commentary_matchup_id" value="%s"/>', $this->matchup_id );
			echo '</form>';
			?>
			<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
			<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
			<script>
			// Get the text area.
			const textArea = document.getElementById('pm-commentarysub-textarea');

			// Set up the text area.
			textArea.style.display    = 'none';    // Hide from the user.
			textArea.style.visibility = 'hidden';  // Hide from the user.
			textArea.readOnly         = true;      // Prevent user from modifying it directly.

			// Get quill.
			const quill = new Quill('#pm-commentarysub', {
				placeholder: 'Add your commentary here...',
				theme: 'snow',
				modules: {
					toolbar: [['bold', 'italic', 'underline', 'strike'], [{ 'list': 'ordered'}, { 'list': 'bullet' }]],
				}
			});

			// Update hidden field.
			quill.on('text-change', function(delta, oldDelta, source) {
				textArea.value = quill.root.innerHTML;
			});
			</script>
			<script>
			(function() {
				// Get the form and button.
				const form   = document.querySelector('.pm-commentarysub__form');
				const button = form.querySelector('.pm-commentarysub__submit');

				// On form submit.
				form.addEventListener('submit', (e) => {
					// Prevent the default form submission.
					e.preventDefault();

					// Disable the button.
					e.submitter.disabled = true;

					// Collect form data.
					const formData = new FormData(form);

					// Add ajax true.
					formData.append('ajax', 'true');

					// Add a loading icon or text while waiting for the response.
					e.submitter.innerHTML += '<span class="pm-loading-wrap"><svg class="pm-loading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Pro 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2024 Fonticons, Inc.--><path class="fa-secondary" opacity=".4" d="M478.7 364.6zm-22 6.1l-27.8-15.9a15.9 15.9 0 0 1 -6.9-19.2A184 184 0 1 1 256 72c5.9 0 11.7 .3 17.5 .8-.7-.1-1.5-.2-2.2-.2-8.5-.7-15.2-7.3-15.2-15.8v-32a16 16 0 0 1 15.3-16C266.2 8.5 261.2 8 256 8 119 8 8 119 8 256s111 248 248 248c98 0 182.4-57 222.7-139.4-4.1 7.9-14.2 10.6-22 6.1z"/><path class="fa-primary" d="M271.2 72.6c-8.5-.7-15.2-7.3-15.2-15.8V24.7c0-9.1 7.7-16.8 16.8-16.2C401.9 17.2 504 124.7 504 256a246 246 0 0 1 -25 108.2c-4 8.2-14.4 11-22.3 6.5l-27.8-15.9c-7.4-4.2-9.8-13.4-6.2-21.1A182.5 182.5 0 0 0 440 256c0-96.5-74.3-175.6-168.8-183.4z"/></svg></span>';

					// Send the form data to the server using Fetch API.
					fetch( form.dataset.ajax, {
						method: 'POST',
						body: formData,
						headers: {
							'X-Requested-With': 'XMLHttpRequest'
						}
					})
					.then( response => response.json() ) // Assuming the server returns JSON
					.then( data => {
						revertForm();

						// If successful, refresh.
						if ( data.success ) {
							window.location.reload();
						} else {
							alert( data.data.message );
						}
					})
					.catch( error => {
						console.error( 'promatchups error:', error );

						revertForm();
					});

					/**
					 * Revert the form back to its original state.
					 *
					 * @return {void}
					 */
					function revertForm() {
						// Remove the loading icon.
						e.submitter.querySelector('.pm-loading-wrap').remove();
					}
				});
			})();
			</script>
			<?php
		echo '</div>';
	}

	/**
	 * Display the commentary.
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	function do_commentary() {
		// Get the commentary.
		$commentary = get_comments(
			[
				'type'    => 'pm_commentary',
				'status'  => 'approve',
				'post_id' => $this->matchup_id,
			]
		);

		// Bail if no commentary.
		if ( ! $commentary ) {
			return;
		}

		// Display the commentary box.
		echo '<div id="commentary" class="pm-commentary">';
			// Loop through commentary.
			foreach( $commentary as $comment ) {
				// Get user.
				$user = get_user_by( 'ID', $comment->user_id );

				// Bail if no user.
				if ( ! $user ) {
					continue;
				}

				// Get user avatar and name.
				$avatar = get_avatar( $user->ID, 64 );
				$name  = $user->display_name;

				// Display the commentary.
				echo '<div class="pm-commentary__comment">';
					printf( '<div class="pm-commentary__avatar">%s</div>', $avatar );
					printf( '<div class="pm-commentary__heading">%s</div>', $name );
					// printf( '<div class="pm-commentary__date">%s</div>', human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) );
					printf( '<div class="pm-commentary__content">%s</div>', $comment->comment_content );
				echo '</div>';
			}

		echo '</div>';
	}

	/**
	 * Display the prediction info.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $hidden Whether the prediction is hidden.
	 * @param bool $first  Whether this is the first prediction.
	 *
	 * @return void
	 */
	function do_prediction( $hidden = false, $first = true ) {
		// Display the prediction.
		printf( '<div %sclass="pm-prediction">', $first ? 'id="prediction" ' : '' );
			// Display the header.
			echo '<div class="pm-prediction__header">';
				// Get image.
				$image = (string) wp_get_attachment_image( 9592, 'medium', false, [ 'class' => 'pm-prediction__img' ] );

				// Display the image. No conditions, always show, even if empty because of CSS grid.
				printf( '<div class="pm-prediction__image">%s</div>', $image );

				// Display the heading and prediction list.
				echo '<div class="pm-prediction__bubble">';
					printf( '<h2>%s</h2>', __( 'My Prediction', 'promatchups' ) );
					echo pm_get_prediction_list( $this->data, $hidden );
				echo '</div>';
			echo '</div>';



			// If first.
			if ( $first ) {

				// Prediction note.
				printf( '<p class="has-sm-font-size"><em>%s</em></p>', __( 'SportsDesk Bot predictions are posted as early as possible to help you take advantage of early odds or spreads. Our final forecast is published on the site one hour before the match begins.', 'promatchups' ) );

				// Build teams array.
				$teams = array_filter( [
					$this->data['away_team'],
					$this->data['home_team'],
				] );

				// If teams.
				if ( $teams && 2 === count( $teams ) ) {
					// Results map.
					$map = [
						-1 => ' loss',
						1  => ' win',
						2  => ' push',
						null => ' null+',
					];

					// Loop through teams.
					foreach ( $teams as $team ) {
						// Start table.
						$table = [];

						// Get team term.
						$term = get_term_by( 'name', $team, 'league' );

						// Skip if no term.
						if ( ! $term ) {
							continue;
						}

						// Get latest matchups by team.
						$query = new WP_Query(
							[
								'post_type'              => 'matchup',
								'posts_per_page'         => 5,
								'no_found_rows'          => true,
								'update_post_meta_cache' => false,
								'update_post_term_cache' => false,
								'meta_key'               => 'event_date',
								'orderby'                => 'meta_value_num',
								'order'                  => 'DESC',
								'post__not_in'           => [ get_the_ID() ],
								'tax_query'              => [
									[
										'taxonomy' => 'league',
										'field'    => 'term_id',
										'terms'    => $term->term_id,
									],
								],
								'meta_query'             => [
									'relation' => 'AND',
									[
										'key'     => 'asknews_outcome',
										'compare' => '!=',
										'value'   => '',
									],
									[
										'key'     => 'event_date',
										'value'   => strtotime( '-2 hours' ),
										'compare' => '<',
										'type'    => 'NUMERIC',
									],
								],
							]
						);

						// Loop through posts.
						if ( $query->have_posts() ) {
							while ( $query->have_posts() ) : $query->the_post();
								$matchup_data = pm_get_matchup_data( get_the_ID() );

								// Skip if no outcome.
								if ( ! $matchup_data['has_outcome'] ) {
									continue;
								}

								// Get date and results.
								$matchup_date     = date( 'M j', $matchup_data['date'] );
								$moneyline_result = pm_get_moneyline_result( $matchup_data['prediction'], $matchup_data );
								$spread_result    = pm_get_spread_result( $matchup_data['prediction'], $matchup_data['spread_covered'], $matchup_data );

								// Skip if both null.
								if ( is_null( $moneyline_result ) && is_null( $spread_result ) ) {
									continue;
								}

								// Add to table.
								$table[] = [
									'date'             => $matchup_date,
									'title'            => get_the_title(),
									'permalink'        => get_permalink(),
									'moneyline_result' => $moneyline_result,
									'spread_result'    => $spread_result,
								];

							endwhile;
						}
						wp_reset_postdata();

						// If we have a table.
						if ( $table ) {
							$html  = '';
							$html .= '<div class="pm-previousresults">';
								// Heading.
								$html .= sprintf( '<p class="pm-previousresults__heading is-style-heading">%s %s %s</p>', __( 'Previous', 'promatchups' ), pm_get_team_short_name( $team, $this->data['league'] ), __( 'Predictions', 'promatchups' ) );

								// Start table.
								$html .= '<table class="pm-previousresults__table">';
									// Head.
									$html .= '<thead>';
										$html .= '<tr>';
											$html .= sprintf( '<th>%s</th>', __( 'Date', 'promatchups' ) );
											$html .= sprintf( '<th>%s</th>', __( 'Matchup', 'promatchups' ) );
											$html .= sprintf( '<th class="has-text-align-center">%s</th>', __( 'H2H', 'promatchups' ) );
											$html .= sprintf( '<th class="has-text-align-center">%s</th>', __( 'Spread', 'promatchups' ) );
										$html .= '</tr>';
									$html .= '</thead>';

									// Body.
									$html .= '<tbody>';
										// Build table data from array.
										foreach ( $table as $row ) {
											// Start row.
											$html .= '<tr>';
												// Date.
												$html .= sprintf( '<td class="pm-previous__date"><a href="%s" title="%s">%s</a></td>', $row['permalink'], $row['title'], $row['date'] );

												// Matchup.
												$html .= sprintf( '<td class="pm-previous__matchup"><a href="%s" title="%s">%s</a></td>', $row['permalink'], $row['title'], $row['title'] );

												// Moneyline result.
												$html .= sprintf( '<td class="pm-previous__h2h pm-previous__icon %s"><span class="screen-reader-text">%s</span></td>', $map[ $row['moneyline_result'] ], ucwords( $map[ $row['moneyline_result'] ] ) );

												// Spread result.
												$html .= sprintf( '<td class="pm-previous__ats pm-previous__icon %s"><span class="screen-reader-text">%s</span></td>', $map[ $row['spread_result'] ], ucwords( $map[ $row['spread_result'] ] ) );
											$html .= '</tr>';
										}
									$html .= '</tbody>';
								$html .= '</table>';
							$html .= '</div>';

							// Display the table.
							echo $html;
						}
					}
				}
			}

			// Get reasoning and build keys.
			$reasoning = sprintf( __( 'Either the %s or the %s are predicted to win this game. You do not have access to our predictions.', 'promatchups' ), $this->data['home_team'], $this->data['away_team'] );
			$keys      = [
				'forecast'               => [
					'label'  => __( 'Forecast', 'promatchups' ),
					'hidden' => sprintf( '%s %s %s', $this->data['home_team'], __( 'or', 'promatchups' ), $this->data['away_team'] ),
				],
				'reasoning'              => [
					'label'  => __( 'Reasoning', 'promatchups' ),
					'hidden' => $reasoning,
				],
				'score_rationale' => [
					'label'  => __( 'Score Rationale', 'promatchups' ),
					'hidden' => $reasoning,
				],
				// Disabled per Rob's request. "reconciled info is not really meant for anything besides internal thinking of the LLM".
				// 'reconciled_information' => [
				// 	'label'  => __( 'Reconciled Info', 'promatchups' ),
				// 	'hidden' => $reasoning,
				// ],
				'unique_prediction'      => [
					'label'  => __( 'Unique Prediction', 'promatchups' ),
					'hidden' => $reasoning,
				],
				'unique_information'     => [
					'label'  => __( 'Unique Info', 'promatchups' ),
					'hidden' => $reasoning,
				],
			];

			// Display the inner content.
			printf( '<div class="pm-prediction__inner%s">', $hidden ? ' pm-prediction__inner--obfuscated' : '' );
				// Loop through and display the key body.
				foreach ( $keys as $key => $value ) {
					$content = $hidden ? $value['hidden'] : $this->content[ $key ];

					if ( ! $content ) {
						continue;
					}

					$heading = $value['label'] ? sprintf( '<strong>%s:</strong> ', $value['label'] ) : '';

					printf( '<p>%s%s</p>', $heading, $content );
				}

				// If hidden, show CTA.
				if ( $hidden ) {
					echo '<div class="pm-prediction__cta">';
						echo '<div class="pm-prediction__cta-inner">';
							printf( '<h3>%s</h3>', __( 'Advanced Insights', 'promatchups' ) );
							printf( '<p>%s</p>', __( 'Advanced insights and predictions available to members.', 'promatchups' ) );
							printf( '<a class="button" href="%s">%s</a>', get_permalink( 41 ), __( 'Get Access', 'promatchups' ) );
						echo '</div>';
					echo '</div>';

				}
				// Show stat, fantasy tip, and odds.
				else {
					// Get the interesting stat.
					$stat = $hidden ? $reasoning : $this->content['interesting_stat'];

					// Display the interesting stat.
					if ( $stat ) {
						$heading = sprintf( '<strong>%s:</strong> ', __( 'Interesting Statistic', 'promatchups' ) );

						printf( '<p>%s%s</p>', $heading, $stat );
					}

					// Get the spread insights.
					$spread_insights = $this->content['spread_insights'];

					// Display the spread insights.
					if ( $spread_insights ) {
						printf( '<h3>%s</h3>', __( 'Spread Insights', 'promatchups' ) );
						echo implode( '', $spread_insights );
					}

					// Get the takeaways.
					$takeaways = $this->content['takeaways'];

					// Display the takeaways.
					if ( $takeaways ) {
						printf( '<h3 class="has-lg-margin-top">%s</h3>', __( 'Key Takeaways', 'promatchups' ) );
						echo '<ul>';
							foreach ( (array) $takeaways as $takeaway ) {
								printf( '<li>%s</li>', $takeaway );
							}
						echo '</ul>';
					}

					// Get the fantasy tip.
					$fantasy = $hidden ? $reasoning : $this->content['fantasy_tip'];

					// Display the fantasy tip.
					if ( $fantasy ) {
						$heading = sprintf( '<h3 class="has-xxxs-margin-bottom">%s!</h3> ', __( 'Fantasy Tip', 'promatchups' ) );
						printf( '<div class="pm-prediction__fantasy">%s%s</div>', $heading, wpautop( $fantasy, false ) );
					}

					// Get odds/spread tables.
					$odds    = pm_get_odds_table( $this->data, $hidden );
					$spreads = pm_get_spreads_table( $this->data, $hidden );

					// Display the odds.
					if ( $odds ) {
						echo $odds;
					}

					// Display the spreads.
					if ( $spreads ) {
						echo $spreads;
					}
				}

			echo '</div>';
		echo '</div>';
	}

	/**
	 * Display the people.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $first Whether this is the first insight.
	 *
	 * @return void
	 */
	function do_people( $first ) {
		$people = $this->content['key_people'];

		if ( ! $people ) {
			return;
		}

		// Start markup.
		printf( '<h2 %sclass="is-style-heading">%s</h2>', $first ? 'id="people" ' : '', __( 'Key People', 'promatchups' ) );
		printf( '<p>%s</p>', __( 'Key people highlighted in this matchup. Click to follow.', 'promatchups' ) );

		echo '<ul class="pm-people">';

		// Loop through people.
		foreach ( $people as $person ) {
			// Early versions were a string of the person's name.
			if ( is_string( $person ) ) {
				printf( '<li class="pm-person">%s</li>', $person );
			}
			// We should be getting dict/array now.
			else {
				// Get the term/name.
				$name = isset( $person['name'] ) ? $person['name'] : '';
				$term = $name ? get_term_by( 'name', $name, 'matchup_tag' ) : '';
				$name = $term ? sprintf( '<strong><a class="pm-person__link" href="%s">%s</a></strong>', get_term_link( $term ), $term->name ) : $name;

				// Build the info.
				$info = [
					$name,
					isset( $person['role'] ) ? $person['role'] : '',
				];

				// Remove empty items.
				$info = array_filter( $info );

				echo '<li>';
					echo implode( ': ', $info );
				echo '</li>';
			}
		}

		echo '</ul>';
	}

	/**
	 * Display the injuries.
	 *
	 * @since 0.8.0
	 *
	 * @param bool $first Whether this is the first insight.
	 *
	 * @return void
	 */
	function do_injuries( $first ) {
		$injuries = $this->content['injuries'];

		// Bail if no injuries.
		if ( ! $injuries ) {
			return;
		}

		// Start lis.
		$lis = [];

		// Loop through injuries.
		foreach ( $injuries as $index => $injury ) {
			$name   = pm_get_key( 'name', $injury );
			$team   = pm_get_key( 'team', $injury );
			$status = pm_get_key( 'status', $injury );

			// Skip if no data.
			if ( ! ( $name && $team && $status ) ) {
				continue;
			}

			$lis[] = sprintf('<li><strong>%s</strong> (%s): %s</li>', $name, $team, $status );
		}

		// Bail if no lis.
		if ( ! $lis ) {
			return;
		}

		// Heading and desc.
		printf( '<h2 %sclass="is-style-heading">%s</h2>', $first ? 'id="injuries" ' : '', __( 'Injuries', 'promatchups' ) );
		printf( '<p>%s</p>', __( 'Injuries that may affect the outcome of this matchup.', 'promatchups' ) );

		echo '<ul class="pm-injuries">';
			foreach ( $lis as $li ) {
				echo $li;
			}
		echo '</ul>';
	}

	/**
	 * Display the timeline.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $first Whether this is the first insight.
	 *
	 * @return void
	 */
	function do_timeline( $first ) {
		$timeline = $this->content['timeline'];

		if ( ! $timeline ) {
			return;
		}

		// Heading and desc.
		printf( '<h2 %sclass="is-style-heading">%s</h2>', $first ? 'id="timeline" ' : '', __( 'Timeline', 'promatchups' ) );
		printf( '<p>%s</p>', __( 'Timeline of relevant events and news articles.', 'promatchups' ) );

		echo '<ul>';
		foreach ( $timeline as $event ) {
			printf( '<li>%s</li>', $event );
		}
		echo '</ul>';
	}

	/**
	 * Display the sources.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $first Whether this is the first insight.
	 *
	 * @return void
	 */
	function do_sources( $first ) {
		$sources = $this->content['sources'];

		if ( ! $sources ) {
			return;
		}

		// Heading.
		printf( '<h2 %sclass="is-style-heading">%s</h2>', $first ? 'id="sources" ' : '', __( 'Latest News', 'promatchups' ) );

		// Start the list.
		echo '<ul class="pm-sources">';
			// Loop through sources.
			foreach ( $sources as $source ) {
				// Build list item.
				echo '<li class="pm-source">';
					// Image.
					echo '<figure class="pm-source__image">';
						if ( $source['image_url'] ) {
							printf( '<img class="pm-source__image-bg" src="%s" alt="%s" />', $source['image_url'], $source['title'] );
							printf( '<img class="pm-source__image-img" src="%s" alt="%s" />', $source['image_url'], $source['title'] );
						}
					echo '</figure>';

					// Title.
					echo '<h3 class="pm-source__title entry-title">';
						echo $source['host'];
					echo '</h3>';

					// Meta.
					echo '<p class="pm-source__meta">';
						echo $source['meta'];
					echo '</p>';

					// Summary.
					echo '<p class="pm-source__summary">';
						echo $source['summary'];
					echo '</p>';

					// People/Entities.
					if ( $source['people'] ) {
						echo '<ul class="pm-entities">';
							foreach ( $source['people'] as $person ) {
								printf( '<li class="pm-entity">%s</li>', $person );
							}
						echo '</ul>';
					}
				echo '</li>';

				// Add hook.
				do_action( 'pm_after_source', $source );
			}
		echo '</ul>';
	}

	/**
	 * Display the web search results.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $first Whether this is the first insight.
	 *
	 * @return void
	 */
	function do_search_results( $first ) {
		$results = $this->content['search_results'];

		if ( ! $results ) {
			return;
		}

		// Possible TODOs:
		// Order by date. Only show the last 3 days before the most recent insight date.
		// For subscription, show the last 2 weeks?

		// Heading.
		printf( '<h2 %sclass="is-style-heading">%s</h2>', $first ? 'id="web" ' : '', __( 'Around the Web', 'promatchups' ) );

		// Start the list.
		echo '<ul class="pm-results">';
			// Loop through search results.
			foreach ( $results as $result ) {
				// Skip if no points.
				if ( ! $result['points'] ) {
					continue;
				}

				// Build list item.
				echo '<li class="pm-result">';
					// Title.
					echo '<h3 class="entry-title pm-result__title">';
						echo $result['title'];
					echo '</h3>';

					// Meta.
					echo '<div class="pm-result__meta">';
						echo $result['meta'];
					echo '</div>';

					// Points.
					echo '<ul>';
						foreach ( $result['points'] as $point ) {
							printf( '<li>%s</li>', $point );
						}
					echo '</ul>';
				echo '</li>';

				// Add hook.
				do_action( 'pm_after_web_result', $result );
			}
		echo '</ul>';
	}

	/**
	 * Do the insights.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function do_updates() {
		// Get all but the first insight.
		$insight_ids = array_slice( $this->insights, 1 );

		// Bail if no insight.
		if ( ! $insight_ids ) {
			return;
		}

		// Heading.
		printf( '<h2 id="updates">%s</h2>', __( 'Previous Updates', 'promatchups' ) );

		// Loop through insights.
		foreach ( $insight_ids as $index => $insight_id ) {
			// Get body, and the post date with the time.
			$data = get_post_meta( $insight_id, 'asknews_body', true );
			$date = get_the_date( 'F j, Y @g:m a', $insight_id );

			printf( '<details id="pm-insight-%s" class="pm-insight">', $index );
				printf( '<summary class="pm-insight__summary">%s %s</summary>', get_the_title( $insight_id ), $date );
				echo '<div class="pm-insight__content entry-content">';
					$this->do_content( $data, false );
				echo '</div>';
			echo '</details>';
		}
	}

	/**
	 * Get the body.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_body() {
		static $cache = null;

		if ( ! is_null( $cache ) ) {
			return $cache;
		}

		// Get the first insight.
		$cache      = [];
		$insight_id = reset( $this->insights );

		// Bail if no insight.
		if ( ! $insight_id ) {
			return $cache;
		}

		// Get the body.
		$cache = (array) get_post_meta( $insight_id, 'asknews_body', true );

		return $cache;
	}
}
