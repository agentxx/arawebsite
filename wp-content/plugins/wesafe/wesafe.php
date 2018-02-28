<?php
/*
  Plugin Name: WeSafe by uPress
  Plugin URI: https://www.upress.co.il
  Description: Limit and block unauthorized login attempts using uPress global IP block lists.
  Author: uPress
  Author URI: https://www.upress.co.il
  Text Domain: wesafe
  Domain Path: /languages
  Version: 1.7.1
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.html

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*
 * Constants
 */

/* Different ways to get remote address: direct & behind proxy */
define( 'WESAFE_DIRECT_ADDR', 'REMOTE_ADDR' );
define( 'WESAFE_PROXY_ADDR', 'HTTP_X_FORWARDED_FOR' );

/* Notify value checked against these in wesafe_sanitize_variables() */
define( 'WESAFE_LOCKOUT_NOTIFY_ALLOWED', 'log,email' );

/*
 * Variables
 *
 * Assignments are for default value -- change on admin page.
 */

$wesafe_options =
	array(
		/* Are we behind a proxy? */
		'client_type'            => WESAFE_DIRECT_ADDR,

		/* Lock out after this many tries */
		'allowed_retries'        => 6,

		'lockout_duration'      => 1200,

		'valid_duration'      => 4320,

		/* Also limit malformed/forged cookies? */
		'cookies'                => true,

		/* Notify on lockout. Values: '', 'log', 'email', 'log,email' */
		'lockout_notify'         => 'log',

		/* If notify by email, do so after this number of lockouts */
		'notify_email_after'     => 6,
	);

$wesafe_my_error_shown       = false; /* have we shown our stuff? */
$wesafe_just_lockedout       = false; /* started this pageload??? */
$wesafe_nonempty_credentials = false; /* user and pwd nonempty */


/*
 * Startup
 */

add_action( 'plugins_loaded', 'wesafe_setup', 99999 );

function wesafe_activate_plugin(){
	register_uninstall_hook( __FILE__, 'wesafe_deactivate_plugin' );
}
register_activation_hook( __FILE__, 'wesafe_activate_plugin' );

function wesafe_deactivate_plugin(){
	delete_option( 'wesafe_client_type' );
	delete_option( 'wesafe_allowed_retries' );
	delete_option( 'wesafe_lockout_duration' );
	delete_option( 'wesafe_valid_duration' );
	delete_option( 'wesafe_lockout_notify' );
	delete_option( 'wesafe_notify_email_after' );
	delete_option( 'wesafe_cookies' );
}

/*
 * Functions start here
 */

/* Get options and setup filters & actions */
function wesafe_setup() {
	load_plugin_textdomain( 'wesafe', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	wesafe_setup_options();

	/* Filters and actions */
	add_action( 'wp_login_failed', 'wesafe_failed' );
	if ( wesafe_option( 'cookies' ) ) {
		wesafe_handle_cookies();
		add_action( 'auth_cookie_bad_username', 'wesafe_failed_cookie' );

		global $wp_version;

		if ( version_compare( $wp_version, '3.0', '>=' ) ) {
			add_action( 'auth_cookie_bad_hash', 'wesafe_failed_cookie_hash' );
			add_action( 'auth_cookie_valid', 'wesafe_valid_cookie', 10, 2 );
		} else {
			add_action( 'auth_cookie_bad_hash', 'wesafe_failed_cookie' );
		}
	}
	add_filter( 'wp_authenticate_user', 'wesafe_wp_authenticate_user', 99999, 2 );
	add_filter( 'shake_error_codes', 'wesafe_failure_shake' );
	add_action( 'login_head', 'wesafe_add_error_message' );
	add_action( 'login_errors', 'wesafe_fixup_error_messages' );
	add_action( 'admin_menu', 'wesafe_admin_menu' );

	/*
	 * This action should really be changed to the 'authenticate' filter as
	 * it will probably be deprecated. That is however only available in
	 * later versions of WP.
	 */
	add_action( 'wp_authenticate', 'wesafe_track_credentials', 10, 2 );
}


/* Get current option value */
function wesafe_option( $option_name ) {
	global $wesafe_options;

	if ( isset( $wesafe_options[ $option_name ] ) ) {
		return $wesafe_options[ $option_name ];
	} else {
		return null;
	}
}


/* Get correct remote address */
function wesafe_get_address( $type_name = '' ) {
	$type = $type_name;
	if ( empty( $type ) ) {
		$type = wesafe_option( 'client_type' );
	}

	if ( isset( $_SERVER[ $type ] ) ) {
		return $_SERVER[ $type ];
	}

	/*
	 * Not found. Did we get proxy type from option?
	 * If so, try to fall back to direct address.
	 */
	if ( empty( $type_name ) && $type == WESAFE_PROXY_ADDR
	     && isset( $_SERVER[ WESAFE_DIRECT_ADDR ] )
	) {

		/*
		 * NOTE: Even though we fall back to direct address -- meaning you
		 * can get a mostly working plugin when set to PROXY mode while in
		 * fact directly connected to Internet it is not safe!
		 *
		 * Client can itself send HTTP_X_FORWARDED_FOR header fooling us
		 * regarding which IP should be banned.
		 */

		return $_SERVER[ WESAFE_DIRECT_ADDR ];
	}

	return '';
}


/*
 * Check if IP is whitelisted.
 *
 * This function allow external ip whitelisting using a filter. Note that it can
 * be called multiple times during the login process.
 *
 * Note that retries and statistics are still counted and notifications
 * done as usual for whitelisted ips , but no lockout is done.
 *
 * Example:
 * function my_ip_whitelist($allow, $ip) {
 * 	return ($ip == 'my-ip') ? true : $allow;
 * }
 * add_filter('wesafe_whitelist_ip', 'my_ip_whitelist', 10, 2);
 */
function is_wesafe_ip_whitelisted( $ip = null ) {
	if ( is_null( $ip ) ) {
		$ip = wesafe_get_address();
	}
	$whitelisted = apply_filters( 'wesafe_whitelist_ip', false, $ip );

	return ( $whitelisted === true );
}


/* Check if it is ok to login */
function is_wesafe_ok() {
	$ip = wesafe_get_address();

	/* Check external whitelist filter */
	if ( is_wesafe_ip_whitelisted( $ip ) ) {
		return true;
	}

	/* lockout active? */
	$lockouts = get_option( 'wesafe_lockouts' );

	return ( ! is_array( $lockouts ) || ! isset( $lockouts[ $ip ] ) || time() >= $lockouts[ $ip ] );
}


/* Filter: allow login attempt? (called from wp_authenticate()) */
function wesafe_wp_authenticate_user( $user, $password ) {
	if ( is_wesafe_ok() || is_wp_error( $user ) ) {
		return $user;
	}

	global $wesafe_my_error_shown;
	$wesafe_my_error_shown = true;

	$error = new WP_Error();
	// This error should be the same as in "shake it" filter below
	$error->add( 'too_many_retries', wesafe_error_msg() );

	return $error;
}


/* Filter: add this failure to login page "Shake it!" */
function wesafe_failure_shake( $error_codes ) {
	$error_codes[] = 'too_many_retries';

	return $error_codes;
}


/*
 * Must be called in plugin_loaded (really early) to make sure we do not allow
 * auth cookies while locked out.
 */
function wesafe_handle_cookies() {
	if ( is_wesafe_ok() ) {
		return;
	}

	wesafe_clear_auth_cookie();
}


/*
 * Action: failed cookie login hash
 *
 * Make sure same invalid cookie doesn't get counted more than once.
 *
 * Requires WordPress version 3.0.0, previous versions use wesafe_failed_cookie()
 */
function wesafe_failed_cookie_hash( $cookie_elements ) {
	wesafe_clear_auth_cookie();

	/*
	 * Under some conditions an invalid auth cookie will be used multiple
	 * times, which results in multiple failed attempts from that one
	 * cookie.
	 *
	 * Unfortunately I've not been able to replicate this consistently and
	 * thus have not been able to make sure what the exact cause is.
	 *
	 * Probably it is because a reload of for example the admin dashboard
	 * might result in multiple requests from the browser before the invalid
	 * cookie can be cleard.
	 *
	 * Handle this by only counting the first attempt when the exact same
	 * cookie is attempted for a user.
	 */

	extract( $cookie_elements, EXTR_OVERWRITE );

	if ( ! isset( $username ) ) {
		return;
	}

	// Check if cookie is for a valid user
	$user = get_user_by( 'login', $username );
	if ( ! $user ) {
		// "shouldn't happen" for this action
		wesafe_failed( $username );

		return;
	}

	$previous_cookie = get_user_meta( $user->ID, 'wesafe_previous_cookie', true );
	if ( $previous_cookie && $previous_cookie == $cookie_elements ) {
		// Identical cookies, ignore this attempt
		return;
	}

	// Store cookie
	if ( $previous_cookie ) {
		update_user_meta( $user->ID, 'wesafe_previous_cookie', $cookie_elements );
	} else {
		add_user_meta( $user->ID, 'wesafe_previous_cookie', $cookie_elements, true );
	}

	wesafe_failed( $username );
}


/*
 * Action: successful cookie login
 *
 * Clear any stored user_meta.
 *
 * Requires WordPress version 3.0.0, not used in previous versions
 */
function wesafe_valid_cookie( $cookie_elements, $user ) {
	/*
	 * As all meta values get cached on user load this should not require
	 * any extra work for the common case of no stored value.
	 */

	if ( get_user_meta( $user->ID, 'wesafe_previous_cookie' ) ) {
		delete_user_meta( $user->ID, 'wesafe_previous_cookie' );
	}
}


/* Action: failed cookie login (calls wesafe_failed()) */
function wesafe_failed_cookie( $cookie_elements ) {
	wesafe_clear_auth_cookie();

	/*
	 * Invalid username gets counted every time.
	 */

	wesafe_failed( $cookie_elements['username'] );
}


/* Make sure auth cookie really get cleared (for this session too) */
function wesafe_clear_auth_cookie() {
	wp_clear_auth_cookie();

	if ( ! empty( $_COOKIE[ AUTH_COOKIE ] ) ) {
		$_COOKIE[ AUTH_COOKIE ] = '';
	}
	if ( ! empty( $_COOKIE[ SECURE_AUTH_COOKIE ] ) ) {
		$_COOKIE[ SECURE_AUTH_COOKIE ] = '';
	}
	if ( ! empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
		$_COOKIE[ LOGGED_IN_COOKIE ] = '';
	}
}

/*
 * Action when login attempt failed
 *
 * Increase nr of retries (if necessary). Reset valid value. Setup
 * lockout if nr of retries are above threshold. And more!
 *
 * A note on external whitelist: retries and statistics are still counted and
 * notifications done as usual, but no lockout is done.
 */
function wesafe_failed( $username ) {
	$ip = wesafe_get_address();

	/* if currently locked-out, do not add to retries */
	$lockouts = get_option( 'wesafe_lockouts' );
	if ( ! is_array( $lockouts ) ) {
		$lockouts = array();
	}
	if ( isset( $lockouts[ $ip ] ) && time() < $lockouts[ $ip ] ) {
		return;
	}

	/* Get the arrays with retries and retries-valid information */
	$retries = get_option( 'wesafe_retries' );
	$valid   = get_option( 'wesafe_retries_valid' );
	if ( ! is_array( $retries ) ) {
		$retries = array();
		add_option( 'wesafe_retries', $retries, '', 'no' );
	}
	if ( ! is_array( $valid ) ) {
		$valid = array();
		add_option( 'wesafe_retries_valid', $valid, '', 'no' );
	}

	/* Check validity and add one to retries */
	if ( isset( $retries[ $ip ] ) && isset( $valid[ $ip ] ) && time() < $valid[ $ip ] ) {
		$retries[ $ip ] ++;
	} else {
		$retries[ $ip ] = 1;
	}
	$valid[ $ip ] = time() + wesafe_option( 'valid_duration' );

	/* lockout? */
	if ( $retries[ $ip ] % wesafe_option( 'allowed_retries' ) != 0 ) {
		/*
		 * Not lockout (yet!)
		 * Do housecleaning (which also saves retry/valid values).
		 */
		wesafe_cleanup( $retries, null, $valid );

		return;
	}

	/* lockout! */

	$whitelisted = is_wesafe_ip_whitelisted( $ip );

	$retries            = wesafe_option( 'allowed_retries' );

	/*
	 * Note that retries and statistics are still counted and notifications
	 * done as usual for whitelisted ips , but no lockout is done.
	 */
	if ( $whitelisted ) {
		unset( $retries[ $ip ] );
		unset( $valid[ $ip ] );
	} else {
		global $wesafe_just_lockedout;
		$wesafe_just_lockedout = true;

		/* normal lockout */
		$lockouts[ $ip ] = time() + wesafe_option( 'lockout_duration' );

		/* trigger network lockout */
		wesafe_network_lockout( $ip, $lockouts[ $ip ] );

		// wesafe_ip_locked_out($ip, $tries)
		do_action( 'wesafe_ip_locked_out', $ip, $retries[ $ip ]);
	}

	/* do housecleaning and save values */
	wesafe_cleanup( $retries, $lockouts, $valid );

	/* do any notification */
	wesafe_notify( $username );

	/* increase statistics */
	$total = get_option( 'wesafe_lockouts_total' );
	if ( $total === false || ! is_numeric( $total ) ) {
		add_option( 'wesafe_lockouts_total', 1, '', 'no' );
	} else {
		update_option( 'wesafe_lockouts_total', $total + 1 );
	}
}


/**
 * Send network lockout request
 *
 * @param string $ip locked ip address
 * @param string $timeout when lockout resets
 */
function wesafe_network_lockout( $ip, $timeout ) {
	$time = time();
	$data = array(
		'ip' => $ip,
		'time' => $time,
		'timeout' => $timeout,
		'origin' => get_bloginfo( 'url' ),
		'host' => gethostname(),
	);
	$hash = crypt( base64_encode( json_encode( $data ) ), $time . json_encode( $data ) );
	$url = 'https://my.upress.co.il/api/wesafe/block/' . $time . '/' . urlencode( $hash );
	wp_safe_remote_post( $url, array(
		'user-agent' => 'wesafe',
		'body' => $data,
	) );
}


/* Clean up old lockouts and retries, and save supplied arrays */
function wesafe_cleanup( $retries = null, $lockouts = null, $valid = null ) {
	$now      = time();
	$lockouts = ! is_null( $lockouts ) ? $lockouts : get_option( 'wesafe_lockouts' );

	/* remove old lockouts */
	if ( is_array( $lockouts ) ) {
		foreach ( $lockouts as $ip => $lockout ) {
			if ( $lockout < $now ) {
				unset( $lockouts[ $ip ] );
			}
		}
		update_option( 'wesafe_lockouts', $lockouts );
	}

	/* remove retries that are no longer valid */
	$valid   = ! is_null( $valid ) ? $valid : get_option( 'wesafe_retries_valid' );
	$retries = ! is_null( $retries ) ? $retries : get_option( 'wesafe_retries' );
	if ( ! is_array( $valid ) || ! is_array( $retries ) ) {
		return;
	}

	foreach ( $valid as $ip => $lockout ) {
		if ( $lockout < $now ) {
			unset( $valid[ $ip ] );
			unset( $retries[ $ip ] );
		}
	}

	/* go through retries directly, if for some reason they've gone out of sync */
	foreach ( $retries as $ip => $retry ) {
		if ( ! isset( $valid[ $ip ] ) ) {
			unset( $retries[ $ip ] );
		}
	}

	update_option( 'wesafe_retries', $retries );
	update_option( 'wesafe_retries_valid', $valid );
}


/* Is this WP Multisite? */
function is_wesafe_multisite() {
	return function_exists( 'get_site_option' ) && function_exists( 'is_multisite' ) && is_multisite();
}


/* Email notification of lockout to admin (if configured) */
function wesafe_notify_email( $user ) {
	$ip          = wesafe_get_address();
	$whitelisted = is_wesafe_ip_whitelisted( $ip );

	$retries = get_option( 'wesafe_retries' );
	if ( ! is_array( $retries ) ) {
		$retries = array();
	}

	/* check if we are at the right nr to do notification */
	if ( isset( $retries[ $ip ] )
	     && ( ( $retries[ $ip ] / wesafe_option( 'allowed_retries' ) )
	          % wesafe_option( 'notify_email_after' ) ) != 0
	) {
		return;
	}

	/* Format message. First current lockout duration */
	/* normal lockout */
	$count    = $retries[ $ip ];
	$lockouts = floor( $count / wesafe_option( 'allowed_retries' ) );
	$when = "";

	$blogname = is_wesafe_multisite() ? get_site_option( 'site_name' ) : get_option( 'blogname' );

	if ( $whitelisted ) {
		$subject = sprintf( __( "[%s] Failed login attempts from whitelisted IP"
				, 'wesafe' )
			, $blogname );
	} else {
		$subject = sprintf( __( "[%s] Too many failed login attempts"
				, 'wesafe' )
			, $blogname );
	}

	$message = sprintf( __( "%d failed login attempts from IP: %s"
			, 'wesafe' ) . "\r\n\r\n"
		, $count, $ip );
	if ( $user != '' ) {
		$message .= sprintf( __( "Last user attempted: %s", 'wesafe' )
		                     . "\r\n\r\n", $user );
	}
	if ( $whitelisted ) {
		$message .= __( "IP was NOT blocked because of external whitelist.", 'wesafe' );
	} else {
		// $message .= sprintf( __( "IP was blocked for %s", 'wesafe' ), $when );
	}

	$admin_email = is_wesafe_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );


	/* mail template */
	$direction = is_rtl() ? 'rtl' : 'ltr';
	$logourl = trailingslashit( plugin_dir_url( __FILE__ ) ) . 'images/wesafe-logo.png';
	ob_start();
	include_once __DIR__ . '/mail-template.php';
	$template = ob_get_clean();


	@wp_mail( $admin_email, $subject, $template, array( 'Content-Type: text/html; charset=UTF-8' ) );
}


/* Logging of lockout (if configured) */
function wesafe_notify_log( $user ) {
	$log = $option = get_option( 'wesafe_logged' );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	$ip = wesafe_get_address();

	/* can be written much simpler, if you do not mind php warnings */
	if ( isset( $log[ $ip ] ) ) {
		if ( isset( $log[ $ip ][ $user ] ) ) {
			$log[ $ip ][ $user ] ++;
		} else {
			$log[ $ip ][ $user ] = 1;
		}
	} else {
		$log[ $ip ] = array( $user => 1 );
	}

	if ( $option === false ) {
		add_option( 'wesafe_logged', $log, '', 'no' ); /* no autoload */
	} else {
		update_option( 'wesafe_logged', $log );
	}
}


/* Handle notification in event of lockout */
function wesafe_notify( $user ) {
	$args = explode( ',', wesafe_option( 'lockout_notify' ) );

	if ( empty( $args ) ) {
		return;
	}

	foreach ( $args as $mode ) {
		switch ( trim( $mode ) ) {
			case 'email':
				wesafe_notify_email( $user );
				break;
			case 'log':
				wesafe_notify_log( $user );
				break;
		}
	}
}


/* Construct informative error message */
function wesafe_error_msg() {
	$ip       = wesafe_get_address();
	$lockouts = get_option( 'wesafe_lockouts' );

	$msg = __( '<strong>ERROR</strong>: Too many failed login attempts.', 'wesafe' ) . ' ';

	if (!is_array($lockouts) || !isset($lockouts[$ip]) || time() >= $lockouts[$ip]) {
		/* Huh? No timeout active? */
		$msg .=  __('Please try again later.', 'wesafe');
		return $msg;
	}

	$when = ceil(($lockouts[$ip] - time()) / 60);
	if ($when > 60) {
		$when = ceil($when / 60);
		$msg .= sprintf(_n('Please try again in %d hour.', 'Please try again in %d hours.', $when, 'wesafe'), $when);
	} else {
		$msg .= sprintf(_n('Please try again in %d minute.', 'Please try again in %d minutes.', $when, 'wesafe'), $when);
	}

	return $msg;
}


/* Construct retries remaining message */
function wesafe_retries_remaining_msg() {
	$ip      = wesafe_get_address();
	$retries = get_option( 'wesafe_retries' );
	$valid   = get_option( 'wesafe_retries_valid' );

	/* Should we show retries remaining? */

	if ( ! is_array( $retries ) || ! is_array( $valid ) ) {
		/* no retries at all */
		return '';
	}
	if ( ! isset( $retries[ $ip ] ) || ! isset( $valid[ $ip ] ) || time() > $valid[ $ip ] ) {
		/* no: no valid retries */
		return '';
	}
	if ( ( $retries[ $ip ] % wesafe_option( 'allowed_retries' ) ) == 0 ) {
		/* no: already been locked out for these retries */
		return '';
	}

	$remaining = max( ( wesafe_option( 'allowed_retries' ) - ( $retries[ $ip ] % wesafe_option( 'allowed_retries' ) ) ), 0 );

	return sprintf( _n( "<strong>%d</strong> attempt remaining.", "<strong>%d</strong> attempts remaining.", $remaining, 'wesafe' ), $remaining );
}


/* Return current (error) message to show, if any */
function wesafe_get_message() {
	/* Check external whitelist */
	if ( is_wesafe_ip_whitelisted() ) {
		return '';
	}

	/* Is lockout in effect? */
	if ( ! is_wesafe_ok() ) {
		return wesafe_error_msg();
	}

	return wesafe_retries_remaining_msg();
}


/* Should we show errors and messages on this page? */
function should_wesafe_show_msg() {
	if ( isset( $_GET['key'] ) ) {
		/* reset password */
		return false;
	}

	$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

	return ( $action != 'lostpassword' && $action != 'retrievepassword'
	         && $action != 'resetpass' && $action != 'rp'
	         && $action != 'register' );
}


/* Fix up the error message before showing it */
function wesafe_fixup_error_messages( $content ) {
	global $wesafe_just_lockedout, $wesafe_nonempty_credentials, $wesafe_my_error_shown;

	if ( ! should_wesafe_show_msg() ) {
		return $content;
	}

	/*
	 * During lockout we do not want to show any other error messages (like
	 * unknown user or empty password).
	 */
	if ( ! is_wesafe_ok() && ! $wesafe_just_lockedout ) {
		return wesafe_error_msg();
	}

	/*
	 * We want to filter the messages 'Invalid username' and
	 * 'Invalid password' as that is an information leak regarding user
	 * account names (prior to WP 2.9?).
	 *
	 * Also, if more than one error message, put an extra <br /> tag between
	 * them.
	 */
	$msgs = explode( "<br />\n", $content );

	if ( strlen( end( $msgs ) ) == 0 ) {
		/* remove last entry empty string */
		array_pop( $msgs );
	}

	$count         = count( $msgs );
	$my_warn_count = $wesafe_my_error_shown ? 1 : 0;

	if ( $wesafe_nonempty_credentials && $count > $my_warn_count ) {
		/* Replace error message, including ours if necessary */
		$content = __( '<strong>ERROR</strong>: Incorrect username or password.', 'wesafe' ) . "<br />\n";
		if ( $wesafe_my_error_shown ) {
			$content .= "<br />\n" . wesafe_get_message() . "<br />\n";
		}

		return $content;
	} elseif ( $count <= 1 ) {
		return $content;
	}

	$new = '';
	while ( $count -- > 0 ) {
		$new .= array_shift( $msgs ) . "<br />\n";
		if ( $count > 0 ) {
			$new .= "<br />\n";
		}
	}

	return $new;
}


/* Add a message to login page when necessary */
function wesafe_add_error_message() {
	global $error, $wesafe_my_error_shown;

	if ( ! should_wesafe_show_msg() || $wesafe_my_error_shown ) {
		return;
	}

	$msg = wesafe_get_message();

	if ( $msg != '' ) {
		$wesafe_my_error_shown = true;
		$error .= $msg;
	}

	return;
}


/* Keep track of if user or password are empty, to filter errors correctly */
function wesafe_track_credentials( $user, $password ) {
	global $wesafe_nonempty_credentials;

	$wesafe_nonempty_credentials = ( ! empty( $user ) && ! empty( $password ) );
}


/*
 * Admin stuff
 */

/* Make a guess if we are behind a proxy or not */
function wesafe_guess_proxy() {
	return isset( $_SERVER[ WESAFE_PROXY_ADDR ] )
		? WESAFE_PROXY_ADDR : WESAFE_DIRECT_ADDR;
}


/* Only change var if option exists */
function wesafe_get_option( $option, $var_name ) {
	$a = get_option( $option );

	if ( $a !== false ) {
		global $wesafe_options;

		$wesafe_options[ $var_name ] = $a;
	}
}


/* Setup global variables from options */
function wesafe_setup_options() {
	wesafe_get_option( 'wesafe_client_type', 'client_type' );
	wesafe_get_option( 'wesafe_allowed_retries', 'allowed_retries' );
	wesafe_get_option( 'wesafe_lockout_duration', 'lockout_duration' );
	wesafe_get_option( 'wesafe_valid_duration', 'valid_duration' );
	wesafe_get_option( 'wesafe_cookies', 'cookies' );
	wesafe_get_option( 'wesafe_lockout_notify', 'lockout_notify' );
	wesafe_get_option( 'wesafe_notify_email_after', 'notify_email_after' );

	wesafe_sanitize_variables();
}


/* Update options in db from global variables */
function wesafe_update_options() {
	update_option( 'wesafe_client_type', wesafe_option( 'client_type' ) );
	update_option( 'wesafe_allowed_retries', wesafe_option( 'allowed_retries' ) );
	update_option( 'wesafe_lockout_duration', wesafe_option( 'lockout_duration' ) );
	update_option( 'wesafe_valid_duration', wesafe_option( 'valid_duration' ) );
	update_option( 'wesafe_lockout_notify', wesafe_option( 'lockout_notify' ) );
	update_option( 'wesafe_notify_email_after', wesafe_option( 'notify_email_after' ) );
	update_option( 'wesafe_cookies', wesafe_option( 'cookies' ) ? '1' : '0' );
}


/* Make sure the variables make sense -- simple integer */
function wesafe_sanitize_simple_int( $var_name ) {
	global $wesafe_options;

	$wesafe_options[ $var_name ] = max( 1, intval( wesafe_option( $var_name ) ) );
}


/* Make sure the variables make sense */
function wesafe_sanitize_variables() {
	global $wesafe_options;

	wesafe_sanitize_simple_int( 'allowed_retries' );

	$wesafe_options['cookies'] = ! ! wesafe_option( 'cookies' );

	$notify_email_after                   = max( 1, intval( wesafe_option( 'notify_email_after' ) ) );
	$wesafe_options['notify_email_after'] = min( wesafe_option( 'allowed_retries' ), $notify_email_after );

	$args         = explode( ',', wesafe_option( 'lockout_notify' ) );
	$args_allowed = explode( ',', WESAFE_LOCKOUT_NOTIFY_ALLOWED );
	$new_args     = array();
	foreach ( $args as $a ) {
		if ( in_array( $a, $args_allowed ) ) {
			$new_args[] = $a;
		}
	}
	$wesafe_options['lockout_notify'] = implode( ',', $new_args );

	if ( wesafe_option( 'client_type' ) != WESAFE_DIRECT_ADDR
	     && wesafe_option( 'client_type' ) != WESAFE_PROXY_ADDR
	) {
		$wesafe_options['client_type'] = WESAFE_DIRECT_ADDR;
	}
}


/* Add admin options page */
function wesafe_admin_menu() {
	global $wp_version;

	// Modern WP?
	if ( version_compare( $wp_version, '3.0', '>=' ) ) {
		add_options_page( 'WeSafe', 'WeSafe', 'manage_options', 'wesafe', 'wesafe_option_page' );

		return;
	}

	// Older WPMU?
	if ( function_exists( "get_current_site" ) ) {
		add_submenu_page( 'wpmu-admin.php', 'WeSafe', 'WeSafe', 9, 'wesafe', 'wesafe_option_page' );

		return;
	}

	// Older WP
	add_options_page( 'WeSafe', 'WeSafe', 9, 'wesafe', 'wesafe_option_page' );
}


/* Show log on admin page */
function wesafe_show_log( $log ) {
	echo '<thead><tr><th class="row-title" scope="col">' . _x( "IP", "Internet address", 'wesafe' ) . '</th><th class="row-title" scope="col">' . __( 'Tried to log in as', 'wesafe' ) . '</th></tr></thead>';

	echo '<tbody>';
	if ( ! is_array( $log ) || count( $log ) == 0 ) {
		echo '<tr><td class="" colspan="42">' . __( 'No lockouts yet', 'wesafe' ) . '</td></tr>';
		echo '</tbody>';

		return;
	}

	$alternate = false;
	foreach ( $log as $ip => $arr ) {
		echo '<tr class="' . ( $alternate ? 'alternate' : '' ) . '"><td class="wesafe-ip">' . $ip . '</td><td class="wesafe-max">';
		$first = true;
		foreach ( $arr as $user => $count ) {
			$count_desc = sprintf( _n( '%d lockout', '%d lockouts', $count, 'wesafe' ), $count );
			if ( ! $first ) {
				echo ', ' . $user . ' (' . $count_desc . ')';
			} else {
				echo $user . ' (' . $count_desc . ')';
			}
			$first = false;
		}
		echo '</td></tr>';

		$alternate = ! $alternate;
	}

	echo '</tbody>';
}

/* Actual admin page */
function wesafe_option_page() {
	wesafe_cleanup();

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sorry, but you do not have permissions to change settings.' );
	}

	/* Make sure post was from this page */
	if ( count( $_POST ) > 0 ) {
		check_admin_referer( 'wesafe-options' );
	}

	/* Should we clear log? */
	if ( isset( $_POST['clear_log'] ) ) {
		delete_option( 'wesafe_logged' );
		echo '<div id="message" class="updated fade"><p>'
		     . __( 'Cleared IP log', 'wesafe' )
		     . '</p></div>';
	}

	/* Should we reset counter? */
	if ( isset( $_POST['reset_total'] ) ) {
		update_option( 'wesafe_lockouts_total', 0 );
		echo '<div id="message" class="updated fade"><p>'
		     . __( 'Reset lockout count', 'wesafe' )
		     . '</p></div>';
	}

	/* Should we restore current lockouts? */
	if ( isset( $_POST['reset_current'] ) ) {
		update_option( 'wesafe_lockouts', array() );
		echo '<div id="message" class="updated fade"><p>'
		     . __( 'Cleared current lockouts', 'wesafe' )
		     . '</p></div>';
	}

	/* Should we update options? */
	if ( isset( $_POST['update_options'] ) ) {
		global $wesafe_options;

		$wesafe_options['client_type']            = $_POST['client_type'];
		$wesafe_options['allowed_retries']        = $_POST['allowed_retries'];
		$wesafe_options['notify_email_after']     = $_POST['email_after'];
		$wesafe_options['cookies']                = ( isset( $_POST['cookies'] ) && $_POST['cookies'] == '1' );

		$v = array();
		if ( isset( $_POST['lockout_notify_log'] ) ) {
			$v[] = 'log';
		}
		if ( isset( $_POST['lockout_notify_email'] ) ) {
			$v[] = 'email';
		}
		$wesafe_options['lockout_notify'] = implode( ',', $v );

		wesafe_sanitize_variables();
		wesafe_update_options();
		echo '<div id="message" class="updated fade"><p>'
		     . __( 'Options changed', 'wesafe' )
		     . '</p></div>';
	}

	$lockouts_total = get_option( 'wesafe_lockouts_total', 0 );
	$lockouts       = get_option( 'wesafe_lockouts' );
	$lockouts_now   = is_array( $lockouts ) ? count( $lockouts ) : 0;

	$cookies_yes = wesafe_option( 'cookies' ) ? ' checked ' : '';
	$cookies_no  = wesafe_option( 'cookies' ) ? '' : ' checked ';

	$client_type        = wesafe_option( 'client_type' );
	$client_type_direct = $client_type == WESAFE_DIRECT_ADDR ? ' checked ' : '';
	$client_type_proxy  = $client_type == WESAFE_PROXY_ADDR ? ' checked ' : '';

	$client_type_guess = wesafe_guess_proxy();

	if ( $client_type_guess == WESAFE_DIRECT_ADDR ) {
		$client_type_message = sprintf( __( 'It appears the site is reached directly (from your IP: %s)', 'wesafe' ), wesafe_get_address( WESAFE_DIRECT_ADDR ) );
	} else {
		$client_type_message = sprintf( __( 'It appears the site is reached through a proxy server (proxy IP: %s, your IP: %s)', 'wesafe' ), wesafe_get_address( WESAFE_DIRECT_ADDR ), wesafe_get_address( WESAFE_PROXY_ADDR ) );
	}
	$client_type_message .= '<br />';

	$client_type_warning = '';
	if ( $client_type != $client_type_guess ) {
		$faq = 'http://wordpress.org/extend/plugins/wesafe/faq/';

		$client_type_warning = '<br /><br />' . sprintf( __( '<strong>Current setting appears to be invalid</strong>. Please make sure it is correct. Further information can be found <a href="%s" title="FAQ">here</a>', 'wesafe' ), $faq );
	}

	$v             = explode( ',', wesafe_option( 'lockout_notify' ) );
	$log_checked   = in_array( 'log', $v ) ? ' checked ' : '';
	$email_checked = in_array( 'email', $v ) ? ' checked ' : '';
	?>
	<div class="wrap">
		<h2><img src="<?php echo plugin_dir_url( __FILE__ ); ?>images/wesafe-logo.png" style="height: 200px; width: auto"
		         alt="<?php echo __( 'WeSafe Settings', 'wesafe' ); ?>"></h2>
		<h3><?php echo __( 'Statistics', 'wesafe' ); ?></h3>
		<form action="options-general.php?page=wesafe" method="post">
			<?php wp_nonce_field( 'wesafe-options' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row" valign="top"><?php echo __( 'Total lockouts', 'wesafe' ); ?></th>
					<td>
						<?php if ( $lockouts_total > 0 ) : ?>
							<?php submit_button( __( 'Reset Counter', 'wesafe' ), 'secondary', 'reset_total' ); ?>
							<?php echo sprintf( _n( '%d lockout since last reset', '%d lockouts since last reset', $lockouts_total, 'wesafe' ), $lockouts_total ); ?>
						<?php else : echo __( 'No lockouts yet', 'wesafe' ); endif; ?>
					</td>
				</tr>
				<?php if ( $lockouts_now > 0 ) { ?>
					<tr>
						<th scope="row" valign="top"><?php echo __( 'Active lockouts', 'wesafe' ); ?></th>
						<td>
							<?php submit_button( __( 'Restore Lockouts', 'wesafe' ), 'secondary', 'reset_current' ); ?>
							<?php echo sprintf( __( '%d IP is currently blocked from trying to log in', 'wesafe' ), $lockouts_now ); ?>
						</td>
					</tr>
				<?php } ?>
			</table>
		</form>
		<h3><?php echo __( 'Options', 'wesafe' ); ?></h3>
		<form action="options-general.php?page=wesafe" method="post">
			<?php wp_nonce_field( 'wesafe-options' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row" valign="top"><?php echo __( 'Lockout', 'wesafe' ); ?></th>
					<td>
						<input type="text" size="3" maxlength="4"
						       value="<?php echo( wesafe_option( 'allowed_retries' ) ); ?>"
						       name="allowed_retries"/>
						<?php echo __( 'allowed retries', 'wesafe' ); ?> <br/>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><?php echo __( 'Site connection', 'wesafe' ); ?></th>
					<td>
						<?php echo $client_type_message; ?>
						<label>
							<input type="radio" name="client_type"
								<?php echo $client_type_direct; ?> value="<?php echo WESAFE_DIRECT_ADDR; ?>"/>
							<?php echo __( 'Direct connection', 'wesafe' ); ?>
						</label>
						<label>
							<input type="radio" name="client_type"
								<?php echo $client_type_proxy; ?> value="<?php echo WESAFE_PROXY_ADDR; ?>"/>
							<?php echo __( 'From behind a reverse proxy', 'wesafe' ); ?>
						</label>
						<?php echo $client_type_warning; ?>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><?php echo __( 'Handle cookie login', 'wesafe' ); ?></th>
					<td>
						<label><input type="radio" name="cookies" <?php echo $cookies_yes; ?>
						              value="1"/> <?php echo __( 'Yes', 'wesafe' ); ?></label> <label><input
								type="radio" name="cookies" <?php echo $cookies_no; ?>
								value="0"/> <?php echo __( 'No', 'wesafe' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><?php echo __( 'Notify on lockout', 'wesafe' ); ?></th>
					<td>
						<input type="checkbox" name="lockout_notify_log" <?php echo $log_checked; ?>
						       value="log"/>
						<?php echo __( 'Log IP', 'wesafe' ); ?><br/>
						<input type="checkbox" name="lockout_notify_email" <?php echo $email_checked; ?>
						       value="email"/>
						<?php echo __( 'Email to admin after', 'wesafe' ); ?>
						<input type="text"
						       size="3"
						       maxlength="4"
						       value="<?php echo( wesafe_option( 'notify_email_after' ) ); ?>"
						       name="email_after"/> <?php echo __( 'lockouts', 'wesafe' ); ?>
					</td>
				</tr>
			</table>
			<p class="submit">
				<?php submit_button( __( 'Save' ), 'primary', 'update_options' ); ?>
			</p>
		</form>
		<?php
		$log = get_option( 'wesafe_logged' );

		if ( is_array( $log ) && count( $log ) > 0 ) : ?>
			<h3><?php echo __( 'Lockout log', 'wesafe' ); ?></h3>
			<form action="options-general.php?page=wesafe" method="post">
				<?php wp_nonce_field( 'wesafe-options' ); ?>
				<input type="hidden" value="true" name="clear_log"/>
				<p class="submit">
					<?php submit_button( __( 'Clear Log', 'wesafe' ), 'secondary' ); ?>
				</p>
			</form>
			<style type="text/css">
				.wesafe-log th {
					font-weight: bold;
				}

				td.wesafe-ip {
					/*font-family: "Courier New", Courier, monospace;*/
					vertical-align: top;
				}

				td.wesafe-max {
					width: 100%;
				}
			</style>
			<div class="wesafe-log">
				<table class="widefat">
					<?php wesafe_show_log( $log ); ?>
				</table>
			</div>
		<?php endif; /* if showing $log */ ?>

	</div>
	<?php
}

?>
