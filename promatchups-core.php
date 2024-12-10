<?php

/**
 * Plugin Name:     Pro Matchups Core
 * Plugin URI:      https://promatchups.com
 * Description:     Core functionality for promatchups.com.
 * Version:         1.0.0
 *
 * Author:          BizBudding
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Must be at the top of the file.
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Main ProMatchups_Plugin Class.
 *
 * @since 0.1.0
 */
final class ProMatchups_Plugin {

	/**
	 * @var   ProMatchups_Plugin The one true ProMatchups_Plugin
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main ProMatchups_Plugin Instance.
	 *
	 * Insures that only one instance of ProMatchups_Plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    ProMatchups_Plugin::setup_constants() Setup the constants needed.
	 * @uses    ProMatchups_Plugin::includes() Include the required files.
	 * @uses    ProMatchups_Plugin::hooks() Activate, deactivate, etc.
	 * @see     ProMatchups_Plugin()
	 * @return  object | ProMatchups_Plugin The one true ProMatchups_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new ProMatchups_Plugin;
			// Methods.
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
			self::$instance->classes();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access private
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'promatchups' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access private
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'promatchups' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function setup_constants() {
		// Plugin version.
		if ( ! defined( 'PROMATCHUPS_PLUGIN_VERSION' ) ) {
			define( 'PROMATCHUPS_PLUGIN_VERSION', '1.0.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'PROMATCHUPS_PLUGIN_DIR' ) ) {
			define( 'PROMATCHUPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// // Plugin Includes Path.
		// if ( ! defined( 'PROMATCHUPS_PLUGIN_INCLUDES_DIR' ) ) {
		// 	define( 'PROMATCHUPS_PLUGIN_INCLUDES_DIR', PROMATCHUPS_PLUGIN_DIR . 'includes/' );
		// }

		// Plugin Folder URL.
		if ( ! defined( 'PROMATCHUPS_PLUGIN_URL' ) ) {
			define( 'PROMATCHUPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function includes() {
		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';

		// Listeners.
		include_once PROMATCHUPS_PLUGIN_DIR . 'classes/listeners/class-listener.php'; // This has to be first.
		include_once PROMATCHUPS_PLUGIN_DIR . 'classes/listeners/class-listener-insight.php';
		include_once PROMATCHUPS_PLUGIN_DIR . 'classes/listeners/class-listener-insight-matchup.php';
		include_once PROMATCHUPS_PLUGIN_DIR . 'classes/listeners/class-listener-outcome.php';
		include_once PROMATCHUPS_PLUGIN_DIR . 'classes/listeners/class-listener-outcome-votes.php';
		include_once PROMATCHUPS_PLUGIN_DIR . 'classes/listeners/class-listener-user-vote.php';
		include_once PROMATCHUPS_PLUGIN_DIR . 'classes/listeners/class-listener-user-points.php';
		include_once PROMATCHUPS_PLUGIN_DIR . 'classes/listeners/class-ajax-post-commentary.php';
		include_once PROMATCHUPS_PLUGIN_DIR . 'classes/listeners/class-ajax-post-vote.php';

		// Includes.
		foreach ( glob( PROMATCHUPS_PLUGIN_DIR . 'classes/*.php' ) as $file ) { include $file; }
		foreach ( glob( PROMATCHUPS_PLUGIN_DIR . 'includes/*.php' ) as $file ) { include $file; }
	}

	/**
	 * Run the hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'plugins_loaded', [ $this, 'updater' ] );
		add_action( 'init',           [ $this, 'register_content_types' ] );

		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}

	/**
	 * Setup the updater.
	 *
	 * composer require yahnis-elsts/plugin-update-checker
	 *
	 * @since 0.1.0
	 *
	 * @uses https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return void
	 */
	public function updater() {
		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		// // Setup the updater.
		// $updater = PucFactory::buildUpdateChecker( 'https://github.com/maithemewp/plugin-slug/', __FILE__, 'mai-user-post' );

		// // Set the branch that contains the stable release.
		// $updater->setBranch( 'main' );

		// // Maybe set github api token.
		// if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
		// 	$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
		// }

		// // Add icons for Dashboard > Updates screen.
		// if ( function_exists( 'mai_get_updater_icons' ) && $icons = mai_get_updater_icons() ) {
		// 	$updater->addResultFilter(
		// 		function ( $info ) use ( $icons ) {
		// 			$info->icons = $icons;
		// 			return $info;
		// 		}
		// 	);
		// }
	}

	/**
	 * Instantiate the classes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function classes() {
		$endpoints  = new ProMatchups_Endpoints;
		$rewrites   = new ProMatchups_Rewrites;
		$display    = new ProMatchups_Display;
		$archives   = new ProMatchups_Archives;
		$singular   = new ProMatchups_Singular;
		$users      = new ProMatchups_Users;
		$dashboard  = new ProMatchups_Dashboard;
		$shortcodes = new ProMatchups_Shortcodes;
		$askthebot  = new ProMatchups_AskTheBot;
		$publisher  = new ProMatchups_Mai_Publisher;
		$rank_math  = new ProMatchups_Rank_Math;
		$pro_squad  = new ProMatchups_Pro_Squad;
		$comments   = new ProMatchups_Comments;
		$commentary = new ProMatchups_Ajax_Post_Commentary;
		$votes      = new ProMatchups_Ajax_Post_Vote;
	}

	/**
	 * Register content types.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_content_types() {
		/***********************
		 *  Post Types         *
		 ***********************/

		// Schedule/Matchups.
		register_post_type( 'matchup', [
			'exclude_from_search' => false,
			'has_archive'         => true, // So customizer works.
			'hierarchical'        => false,
			'labels'              => [
				'name'               => _x( 'Matchups', 'Matchup general name', 'promatchups' ),
				'singular_name'      => _x( 'Matchup', 'Matchup singular name', 'promatchups' ),
				'menu_name'          => _x( 'Matchups', 'Matchup admin menu', 'promatchups' ),
				'name_admin_bar'     => _x( 'Matchup', 'Matchup add new on admin bar', 'promatchups' ),
				'add_new'            => _x( 'Add New Matchup', 'Matchup', 'promatchups' ),
				'add_new_item'       => __( 'Add New Matchup',  'promatchups' ),
				'new_item'           => __( 'New Matchup', 'promatchups' ),
				'edit_item'          => __( 'Edit Matchup', 'promatchups' ),
				'view_item'          => __( 'View Matchup', 'promatchups' ),
				'all_items'          => __( 'All Matchups', 'promatchups' ),
				'search_items'       => __( 'Search Matchups', 'promatchups' ),
				'parent_item_colon'  => __( 'Parent Matchups:', 'promatchups' ),
				'not_found'          => __( 'No Matchups found.', 'promatchups' ),
				'not_found_in_trash' => __( 'No Matchups found in Trash.', 'promatchups' )
			],
			'menu_icon'          => 'dashicons-calendar',
			'menu_position'      => 5,
			'public'             => true,
			'publicly_queryable' => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'show_ui'            => true,
			'supports'           => [ 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'page-attributes', 'genesis-cpt-archives-settings', 'genesis-layouts', 'mai-archive-settings', 'mai-single-settings' ],
			'taxonomies'         => [ 'team', 'season' ],
			'rewrite'            => [
				'slug'       => 'matchups',
				'with_front' => false,
			],
		] );

		// Insights.
		register_post_type( 'insight', [
			'exclude_from_search' => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'labels'              => [
				'name'               => _x( 'Insights', 'Insight general name', 'promatchups' ),
				'singular_name'      => _x( 'Insight', 'Insight singular name', 'promatchups' ),
				'menu_name'          => _x( 'Insights', 'Insight admin menu', 'promatchups' ),
				'name_admin_bar'     => _x( 'Insight', 'Insight add new on admin bar', 'promatchups' ),
				'add_new'            => _x( 'Add New Insight', 'Insight', 'promatchups' ),
				'add_new_item'       => __( 'Add New Insight',  'promatchups' ),
				'new_item'           => __( 'New Insight', 'promatchups' ),
				'edit_item'          => __( 'Edit Insight', 'promatchups' ),
				'view_item'          => __( 'View Insight', 'promatchups' ),
				'all_items'          => __( 'All Insights', 'promatchups' ),
				'search_items'       => __( 'Search Insights', 'promatchups' ),
				'parent_item_colon'  => __( 'Parent Insights:', 'promatchups' ),
				'not_found'          => __( 'No Insights found.', 'promatchups' ),
				'not_found_in_trash' => __( 'No Insights found in Trash.', 'promatchups' )
			],
			'menu_icon'          => 'dashicons-analytics',
			'menu_position'      => 6,
			'public'             => false,
			'publicly_queryable' => false,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'show_ui'            => true,
			'supports'           => [ 'title', 'editor', 'author', 'thumbnail', 'page-attributes', 'genesis-cpt-archives-settings', 'genesis-layouts', 'mai-archive-settings', 'mai-single-settings' ],
			'taxonomies'         => [ 'team', 'league', 'season' ],
			'rewrite'            => false, // Handled in ProMatchups_Rewrites.
		] );

		// AskTheBot.
		register_post_type( 'askthebot', [
			'exclude_from_search' => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'labels'              => [
				'name'               => _x( 'AskTheBot', 'AskTheBot general name', 'promatchups' ),
				'singular_name'      => _x( 'AskTheBot', 'AskTheBot singular name', 'promatchups' ),
				'menu_name'          => _x( 'AskTheBot', 'AskTheBot admin menu', 'promatchups' ),
				'name_admin_bar'     => _x( 'AskTheBot', 'AskTheBot add new on admin bar', 'promatchups' ),
				'add_new'            => _x( 'Add New AskTheBot', 'AskTheBot', 'promatchups' ),
				'add_new_item'       => __( 'Add New AskTheBot',  'promatchups' ),
				'new_item'           => __( 'New AskTheBot', 'promatchups' ),
				'edit_item'          => __( 'Edit AskTheBot', 'promatchups' ),
				'view_item'          => __( 'View AskTheBot', 'promatchups' ),
				'all_items'          => __( 'All AskTheBot', 'promatchups' ),
				'search_items'       => __( 'Search AskTheBot', 'promatchups' ),
				'parent_item_colon'  => __( 'Parent AskTheBot:', 'promatchups' ),
				'not_found'          => __( 'No AskTheBot found.', 'promatchups' ),
				'not_found_in_trash' => __( 'No AskTheBot found in Trash.', 'promatchups' )
			],
			'menu_icon'          => 'dashicons-format-chat',
			'menu_position'      => 6,
			'public'             => true,
			'publicly_queryable' => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'show_ui'            => true,
			'supports'           => [ 'title', 'editor', 'author', 'page-attributes', 'genesis-cpt-archives-settings', 'genesis-layouts', 'mai-archive-settings', 'mai-single-settings' ],
			'taxonomies'         => [ 'team', 'league', 'season' ],
			'rewrite'            => [
				'slug'       => 'askthebot',
				'with_front' => false,
			],
		] );

		/***********************
		 *  Custom Taxonomies  *
		 ***********************/

		// Leagues/Matchups.
		register_taxonomy( 'league', [ 'matchup', 'insight' ], [
			'hierarchical' => true,
			'labels'       => [
				'name'                       => _x( 'Leagues', 'League General Name', 'promatchups' ),
				'singular_name'              => _x( 'League', 'League Singular Name', 'promatchups' ),
				'menu_name'                  => __( 'Leagues', 'promatchups' ),
				'all_items'                  => __( 'All Leagues', 'promatchups' ),
				'parent_item'                => __( 'Parent League', 'promatchups' ),
				'parent_item_colon'          => __( 'Parent League:', 'promatchups' ),
				'new_item_name'              => __( 'New League Name', 'promatchups' ),
				'add_new_item'               => __( 'Add New League', 'promatchups' ),
				'edit_item'                  => __( 'Edit League', 'promatchups' ),
				'update_item'                => __( 'Update League', 'promatchups' ),
				'view_item'                  => __( 'View League', 'promatchups' ),
				'separate_items_with_commas' => __( 'Separate items with commas', 'promatchups' ),
				'add_or_remove_items'        => __( 'Add or remove items', 'promatchups' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'promatchups' ),
				'popular_items'              => __( 'Popular Leagues', 'promatchups' ),
				'search_items'               => __( 'Search Leagues', 'promatchups' ),
				'not_found'                  => __( 'Not Found', 'promatchups' ),
			],
			'meta_box_cb'       => false,   // Hides metabox.
			'public'            => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'show_tagcloud'     => false,
			'show_ui'           => true,
			'rewrite'           => false, // Handled in ProMatchups_Rewrites.
		] );

		// Seasons.
		register_taxonomy( 'season', [ 'matchup', 'insight' ], [
			'hierarchical' => false,
			'labels'       => [
				'name'                       => _x( 'Seasons', 'Season General Name', 'promatchups' ),
				'singular_name'              => _x( 'Season', 'Season Singular Name', 'promatchups' ),
				'menu_name'                  => __( 'Seasons', 'promatchups' ),
				'all_items'                  => __( 'All Items', 'promatchups' ),
				'parent_item'                => __( 'Parent Item', 'promatchups' ),
				'parent_item_colon'          => __( 'Parent Item:', 'promatchups' ),
				'new_item_name'              => __( 'New Item Name', 'promatchups' ),
				'add_new_item'               => __( 'Add New Item', 'promatchups' ),
				'edit_item'                  => __( 'Edit Item', 'promatchups' ),
				'update_item'                => __( 'Update Item', 'promatchups' ),
				'view_item'                  => __( 'View Item', 'promatchups' ),
				'separate_items_with_commas' => __( 'Separate items with commas', 'promatchups' ),
				'add_or_remove_items'        => __( 'Add or remove items', 'promatchups' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'promatchups' ),
				'popular_items'              => __( 'Popular Items', 'promatchups' ),
				'search_items'               => __( 'Search Items', 'promatchups' ),
				'not_found'                  => __( 'Not Found', 'promatchups' ),
			],
			'meta_box_cb'       => false,   // Hides metabox.
			'public'            => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'show_tagcloud'     => false,
			'show_ui'           => true,
			'rewrite'           => false,   // Handled in ProMatchups_Rewrites.
		] );

		// Matchup Tags.
		register_taxonomy( 'matchup_tag', 'matchup', [
			'hierarchical' => false,
			'labels'       => [
				'name'                       => _x( 'Matchup Tags', 'Matchup Tag General Name', 'promatchups' ),
				'singular_name'              => _x( 'Matchup Tag', 'Matchup Tag Singular Name', 'promatchups' ),
				'menu_name'                  => __( 'Tags', 'promatchups' ),
				'all_items'                  => __( 'All Matchup Tags', 'promatchups' ),
				'parent_item'                => __( 'Parent Matchup Tag', 'promatchups' ),
				'parent_item_colon'          => __( 'Parent Matchup Tag:', 'promatchups' ),
				'new_item_name'              => __( 'New Matchup Tag Name', 'promatchups' ),
				'add_new_item'               => __( 'Add New Matchup Tag', 'promatchups' ),
				'edit_item'                  => __( 'Edit Matchup Tag', 'promatchups' ),
				'update_item'                => __( 'Update Matchup Tag', 'promatchups' ),
				'view_item'                  => __( 'View Matchup Tag', 'promatchups' ),
				'separate_items_with_commas' => __( 'Separate items with commas', 'promatchups' ),
				'add_or_remove_items'        => __( 'Add or remove items', 'promatchups' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'promatchups' ),
				'popular_items'              => __( 'Popular Matchup Tags', 'promatchups' ),
				'search_items'               => __( 'Search Matchup Tags', 'promatchups' ),
				'not_found'                  => __( 'Not Found', 'promatchups' ),
			],
			'meta_box_cb'       => false,   // Hides metabox.
			'public'            => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'rewrite'           => [
				'slug'       => 'tags',
				'with_front' => false,
			],
		] );
	}

	/**
	 * Plugin activation.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function activate() {
		$this->register_content_types();
		flush_rewrite_rules();
	}
}

/**
 * The main function for that returns ProMatchups_Plugin
 *
 * @since 0.1.0
 *
 * @return object|ProMatchups_Plugin The one true ProMatchups_Plugin Instance.
 */
function mai_asknews_plugin() {
	return ProMatchups_Plugin::instance();
}

// Get ProMatchups_Plugin Running.
mai_asknews_plugin();