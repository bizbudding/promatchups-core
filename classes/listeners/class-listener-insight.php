<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * The insight listener class.
 * This was the original matchup listener, but is now broken into two separate listeners.
 *
 * @since 1.0.0
 */
class ProMatchups_AskNews_Insight_Listener extends ProMatchups_Listener {
	protected $body;
	protected $user;
	protected $return;

	/**
	 * Construct the class.
	 */
	function __construct( $body, $user = null ) {
		$this->body = is_string( $body ) ? json_decode( $body, true ) : $body;
		$this->user = $this->get_user( $user );
		$this->run();
	}

	/**
	 * Run the logic.
	 *
	 * @since 1.0.0
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

		// Prevent post_modified update.
		add_filter( 'wp_insert_post_data', 'pm_prevent_post_modified_update', 10, 4 );

		/***************************************************************
		 * Create or update the matchup insights.
		 *
		 * Builds the new insight post args.
		 * Check for existing insight to update.
		 * Creates or updates the insight.
		 * Sets the insight body as post meta.
		 * Sets forecast and event UUIDs as post meta.
		 ***************************************************************/

		// Set update flag.
		$update = false;

		// Required keys.
		$insight_keys = [
			'forecast_uuid',
			'event_uuid',
		];

		// Check for required keys.
		foreach ( $insight_keys as $key ) {
			if ( ! isset( $this->body[ $key ] ) ) {
				$this->return = $this->get_error( 'Missing required key: ' . $key );
				return;
			}
		}

		// Get values.
		$forecast_uuid = pm_get_key( 'forecast_uuid', $this->body );
		$event_uuid    = pm_get_key( 'event_uuid', $this->body );
		$summary       = pm_get_key( 'summary', $this->body );
		$date          = pm_get_key( 'date', $this->body );

		// Set default post args.
		$insight_args = [
			'post_type'    => 'insight',
			'post_status'  => 'publish',
			'post_author'  => $this->user->ID,
			'post_title'   => __( 'Insight', 'mai-asknews' ) . ' ' . $forecast_uuid, // Updated later.
			'post_name'    => $forecast_uuid,
			'post_excerpt' => $summary,
			'meta_input'   => [
				'asknews_body'  => $this->body,    // The full body for reference.
				'forecast_uuid' => $forecast_uuid, // The id of this specific forecast.
				'event_uuid'    => $event_uuid,    // The id of the event, if this is a post to update.
			],
		];

		// Set post date.
		if ( $date ) {
			$insight_args['post_date'] = $this->get_local_date( $this->get_timestamp( $date ) );
		}

		// Check for an existing insights.
		// This is mostly for reprocessing existing insights via CLI.
		$insight_ids = get_posts(
			[
				'post_type'    => 'insight',
				'post_status'  => 'any',
				'meta_key'     => 'forecast_uuid',
				'meta_value'   => $this->body['forecast_uuid'],
				'meta_compare' => '=',
				'fields'       => 'ids',
				'numberposts'  => -1,
			]
		);

		// If we have an existing post, update it.
		// This is only to fix/alter existing insights.
		if ( $insight_ids ) {
			$update                      = true;
			$insight_args['ID']          = $insight_ids[0];
			$insight_args['post_name']   = $this->body['forecast_uuid'];
			$insight_args['post_status'] = 'publish';

		}

		// Insert or update the post.
		$insight_id = wp_insert_post( $insight_args );

		// If no post ID, send error.
		if ( ! $insight_id ) {
			$this->return = $this->get_error( 'Failed during insight wp_insert_post()' );
			return;
		}

		// Bail if there was an error.
		if ( is_wp_error( $insight_id ) ) {
			$this->return = $insight_id;
			return;
		}

		/***************************************************************
		 * Run the matchup listener.
		 ***************************************************************/

		// Run the matchup listener.
		$matchup = new ProMatchups_AskNews_Insight_Matchup_Listener( $insight_id, $this->user );
		$response = $matchup->get_response();

		// If there was an error, return it.
		if ( is_wp_error( $response ) ) {
			$this->return = $response;
			return;
		}

		/***************************************************************
		 * Upload images from sources and attach to the insight.
		 ***************************************************************/

		// Set the threshold for image size.
		$threshold = function( $threshold ) {
			return 600;
		};

		// Limit image sizes.
		$sizes = function( $sizes ) {
			foreach ( $sizes as $name => $values ) {
				if ( 'medium' === $name ) {
					continue;
				}

				unset( $sizes[ $name ] );
			}

			return $sizes;
		};

		// Resize images.
		$resize = function( $metadata, $attachment_id ) {
			$upload_dir    = wp_get_upload_dir();
			$file_path     = $upload_dir['basedir'] . '/' . $metadata['file'];
			$original_path = str_replace( '-scaled', '', $file_path );

			// Check if the original full-size image exists.
			if ( file_exists( $original_path )) {
				// Delete the original full-size image.
				unlink( $original_path );
			}

			return $metadata;
		};

		// Limit max image size.
		add_filter( 'big_image_size_threshold', $threshold, 9999 );

		// Limit image sizes.
		add_filter( 'intermediate_image_sizes_advanced', $sizes, 9999 );

		// Resize images.
		add_filter( 'wp_generate_attachment_metadata', $resize, 9999, 2 );

		// Get sources.
		$has_images = false;
		$sources    = $this->body['sources'];

		// Loop through sources.
		foreach ( $sources as $index => $source ) {
			// Skip if we have an image ID and it's 0 (can't upload image) or there is a file url.
			if ( isset( $source['image_id'] ) && is_numeric( $source['image_id'] ) ) {
				if ( 0 === (int) $source['image_id'] || wp_get_attachment_image_url( $source['image_id'] ) ) {
					continue;
				}
			}

			// Skip if no image.
			if ( ! ( isset( $source['image_url'] ) && $source['image_url'] ) ) {
				continue;
			}

			// Get file name.
			$file_name = isset( $source['eng_title'] ) && $source['eng_title'] ? sanitize_title_with_dashes( $source['eng_title'] ) : '';

			// Upload the image.
			$image_id = $this->upload_image( $source['image_url'], $insight_id, $file_name );

			// If not an error, and not already set.
			// We're storing `0` as the ID too, so it doesn't continually try to
			// upload the image every time we reprocess the insight.
			if ( ! is_wp_error( $image_id ) && ! ( isset( $this->body['sources'][ $index ]['image_id'] ) && $image_id === $this->body['sources'][ $index ]['image_id'] ) ) {
				// Set the image ID in the sources array.
				$this->body['sources'][ $index ]['image_id'] = (int) $image_id;

				// Sort the sources array by key.
				ksort( $this->body['sources'][ $index ] );

				// Update the insight body.
				$has_images = true;
			}
		}

		// If we have images, update the insight body.
		if ( $has_images ) {
			// Update the insight body.
			update_post_meta( $insight_id, 'asknews_body', $this->body );
		}

		// Remove the filters.
		remove_filter( 'wp_generate_attachment_metadata', $resize, 9999, 2 );
		remove_filter( 'intermediate_image_sizes_advanced', $sizes, 9999 );
		remove_filter( 'big_image_size_threshold', $threshold, 9999 );

		/***************************************************************
		 * Check and update all source urls for redirects.
		 ***************************************************************/

		// // Needs update.
		// $has_redirects = false;

		// // Loop through sources.
		// foreach ( $sources as $index => $source ) {
		// 	// Skip if we already have a final url.
		// 	if ( isset( $source['article_url_final'] ) && $source['article_url_final'] ) {
		// 		continue;
		// 	}

		// 	// Skip if no url.
		// 	if ( ! ( isset( $source['article_url'] ) && $source['article_url'] ) ) {
		// 		continue;
		// 	}

		// 	// Get the final url via curl.
		// 	$url = $source['article_url'];
		// 	$ch  = curl_init( $url );
		// 	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		// 	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		// 	curl_setopt( $ch, CURLOPT_NOBODY, true );
		// 	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
		// 	curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		// 	curl_setopt( $ch, CURLOPT_MAXREDIRS, 5 );
		// 	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'HEAD' );
		// 	curl_exec( $ch );
		// 	$final = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );
		// 	curl_close( $ch );

		// 	// Update source. This may be the same url, but we want to store the final url key either way.
		// 	$this->body['sources'][ $index ]['article_url_final'] = $final;

		// 	// Set flag.
		// 	$has_redirects = true;
		// }

		// // If needs update.
		// if ( $has_redirects ) {
		// 	// Update post meta.
		// 	update_post_meta( get_the_ID(), 'asknews_body', $this->body );
		// }

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
		$this->return = $this->get_success( get_permalink( $insight_id ) . $text );
		return;
	}
}
