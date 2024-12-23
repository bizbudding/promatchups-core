<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// rcp_user_has_active_membership( $user_id = 0 );
// rcp_user_has_free_membership( $user_id = 0 )
// rcp_user_has_paid_membership( $user_id = 0 );
// rcp_user_has_expired_membership( $user_id = 0 );
// rcp_user_has_access( $user_id = 0, $access_level_needed = 0 )

/**
 * Get bot user ID.
 *
 * @since 0.10.0
 *
 * @return int
 */
function pm_get_bot_user_id() {
	return 2;
}

/**
 * Get away team bot user ID.
 *
 * @since TBD
 *
 * @return int
 */
function pm_get_awaybot_user_id() {
	return 64;
}

/**
 * Get home team bot user ID.
 *
 * @since TBD
 *
 * @return int
 */
function pm_get_homebot_user_id() {
	return 65;
}

/**
 * Get favored team bot user ID.
 *
 * @since TBD
 *
 * @return int
 */
function pm_get_favoredbot_user_id() {
	return 66;
}

/**
 * Get underdog team bot user ID.
 *
 * @since TBD
 *
 * @return int
 */
function pm_get_underdogbot_user_id() {
	return 67;
}

/**
 * TODO: Remove.
 * Get spreadbot user ID.
 *
 * @since 0.13.0
 *
 * @return int
 */
function pm_get_spreadbot_user_id() {
	return 55;
}

/**
 * TODO: Remove.
 * Get moneylinebot user ID.
 *
 * @since 0.13.0
 *
 * @return int
 */
function pm_get_moneylinebot_user_id() {
	return 57;
}

/**
 * Get the user object.
 *
 * @since 0.8.0
 *
 * @param WP_User|int $user The user object or ID.
 *
 * @return WP_User|false
 */
function pm_get_user( $user = null ) {
	$return = null;

	if ( $user instanceof WP_User ) {
		$return = $user;
	} elseif ( is_numeric( $user ) ) {
		$return = get_user_by( 'ID', $user );
	} else {
		$return = is_user_logged_in() ? wp_get_current_user() : false;
	}

	return $return;
}

/**
 * If user has role.
 *
 * @since 0.8.0
 *
 * @param string $role
 * @param int    $user_id
 *
 * @return bool
 */
function pm_has_role( $role, $user_id = 0 ) {
	// Get user ID.
	$user_id = $user_id ?: get_current_user_id();

	// Set cache.
	static $roles = [];

	// If we have cache, return it.
	if ( isset( $roles[ $user_id ] ) ) {
		return in_array( $role, $roles[ $user_id ] );
	}

	// If user ID.
	if ( $user_id ) {
		$user              = get_userdata( $user_id );
		$roles[ $user_id ] = $user ? $user->roles : [];
	} else {
		$roles[ $user_id ] = [];
	}

	return in_array( $role, $roles[ $user_id ] );
}

/**
 * If the user has any membership.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function pm_has_membership() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = pm_has_free_membership()
		|| pm_has_paid_membership()
		|| pm_has_pro_membership();

	return $cache;
}

/**
 * If the user has access to non-pro-level restricted content.
 *
 * @since 0.1.0
 *
 * @param string $league The league name.
 *
 * @return bool
 */
function pm_has_access( $league = '' ) {
	static $cache = [];

	if ( isset( $cache[ $league ] ) ) {
		return $cache[ $league ];
	}

	// Admins can always access.
	if ( current_user_can( 'manage_options' ) ) {
		$cache[ $league ] = true;
		return $cache[ $league ];
	}

	// If elite, they have access.
	if ( pm_has_elite_membership() ) {
		$cache[ $league ] = true;
		return $cache[ $league ];
	}

	// Get current page leage and user levels.
	$league = $league ?: pm_get_page_league();
	$levels = pm_get_membership_ids();

	// If no league or no levels, bail.
	if ( ! ( $league && $levels ) ) {
		$cache[ $league ] = false;
		return $cache[ $league ];
	}

	// Hardcoded paid membership IDs, including pro.
	$paid = [
		'MLB' => [
			1, // Monthly,
			2, // Season,
		],
		'NBA' => [
			10, // Monthly,
			11, // Season,
		],
		'NFL' => [
			4, // Monthly,
			5, // Season,
		],
		'NHL' => [
			7, // Monthly,
			8, // Season,
		],
	];

	// If they have any of these ids.
	$cache[ $league ] = (bool) isset( $paid[ $league ] ) ? array_intersect( $levels, $paid[ $league ] ) : false;

	return $cache[ $league ];
}

/**
 * If the user has access to pro-level restricted content.
 *
 * @since 0.1.0
 *
 * @param string $league The league name.
 *
 * @return bool
 */
function pm_has_pro_access( $league = '' ) {
	static $cache = [];

	if ( isset( $cache[ $league ] ) ) {
		return $cache[ $league ];
	}

	// Admins can always access.
	if ( current_user_can( 'manage_options' ) ) {
		$cache[ $league ] = true;
		return $cache[ $league ];
	}

	// Get current page leage and user levels.
	$league = $league ?: pm_get_page_league();
	$levels = pm_get_membership_ids();

	// If no league or no levels, bail.
	if ( ! ( $league && $levels ) ) {
		return false;
	}

	// Hardcoded pro membership IDs.
	$pro = [
		'MLB' => [ 3 ],
		'NBA' => [ 12 ],
		'NFL' => [ 6 ],
		'NHL' => [ 9 ],
	];

	// If they have any of these ids.
	$cache[ $league ] = (bool) isset( $pro[ $league ] ) ? array_intersect( $levels, $pro[ $league ] ) : false;

	return $cache[ $league ];
}

/**
 * If the current user has a free membership.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function pm_has_free_membership() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = function_exists( 'rcp_user_has_free_membership' ) && rcp_user_has_free_membership();

	return $cache;
}

/**
 * If the current user has a paid membership.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function pm_has_paid_membership() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = current_user_can( 'manage_options' ) || ( function_exists( 'rcp_user_has_paid_membership' ) && rcp_user_has_paid_membership() );

	return $cache;
}

/**
 * If the current user has a pro membership.
 *
 * // TODO: I think this is wrong. Access number is RCP level? Not ID? What's going on here.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function pm_has_pro_membership() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = current_user_can( 'manage_options' ) || ( pm_has_paid_membership() && pm_has_access( 8 ) );

	return $cache;
}

/**
 * If the user has elite membership.
 *
 * @since 0.11.0
 *
 * @return bool
 */
function pm_has_elite_membership() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = current_user_can( 'manage_options' ) || in_array( 3, rcp_get_customer_membership_level_ids() );

	return $cache;
}

/**
 * If the current user has a specific access level.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function pm_has_access_level( $level ) {
	static $cache = [];

	if ( isset( $cache[ $level ] ) ) {
		return $cache[ $level ];
	}

	$cache[ $level ] = current_user_can( 'manage_options' ) || ( function_exists( 'rcp_user_has_access' ) && rcp_user_has_access( get_current_user_id(), $level ) );

	return $cache[ $level ];
}

/**
 * If the current user has an active membership.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function pm_has_active_membership() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = current_user_can( 'manage_options' ) || ( function_exists( 'rcp_user_has_active_membership' ) && rcp_user_has_active_membership() );

	return $cache;
}

/**
 * If the current user has an expired membership.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function pm_has_expired_membership() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = function_exists( 'rcp_user_has_expired_membership' ) && rcp_user_has_expired_membership();

	return $cache;
}

/**
 * Get the user current and active membership level IDs.
 *
 * @since 0.1.0
 *
 * @return string
 */
function pm_get_membership_ids() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = function_exists( 'rcp_get_customer_membership_level_ids' ) ? rcp_get_customer_membership_level_ids() : [];

	return $cache;
}