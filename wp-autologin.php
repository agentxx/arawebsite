<?php
/**
 * uPress auto login script
 *
 * @package    uPress Auto Login
 * @author     Ilan Firsov <support@upress.co.il>
 * @link       https://www.upress.co.il
 */
require_once( __DIR__ . '/wp-load.php' );
global $wpdb;

// No authorization parameter? get out...
if ( empty( $_GET['auth'] ) ) {
    wp_die( 'Authorization failed: Missing parameters.' );
    exit;
}

if ( "http" . ( is_ssl() ? 's' : '' ) . "://{$_SERVER['HTTP_HOST']}" != get_option( 'siteurl')  ) {
    wp_redirect( get_option( 'siteurl' ) . "/" . basename( __FILE__ ). "?auth={$_GET['auth']}" );
    exit;
}

$users = array();
$sites = array();
$network_admins = array();

$auth_key = trim( $_GET['auth'] );
$verification_hash = '';
$server_ip = $_SERVER['SERVER_ADDR'];
$client_ip = $_SERVER['REMOTE_ADDR'];
if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
    $client_ip = $_SERVER['HTTP_CLIENT_IP'];
}
if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
}
if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
    $client_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
}

// Load list of users available to login to
if( is_multisite() ) {
    // Get regular users from all blogs
    $sites = get_sites();
    foreach( $sites as $site ) {
        $site_users = get_users( array(
            'blog_id' => $site->blog_id,
            'role__in' => array(
                'Author',
                'Editor',
                'Administrator'
            )
        ) );
        $users = array_merge( $users, $site_users );
    }

    // Get multisite super admins
    $wp_network_admins = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->users );
    $network_admins_usernames = unserialize( $wpdb->get_var( 'SELECT * FROM ' . $wpdb->sitemeta . ' WHERE meta_key = \'site_admins\'', 3 ) );
    $wp_network_admins = array_filter( $wp_network_admins, function( $user ) use ( $network_admins_usernames ) {
        return in_array( $user->user_login, $network_admins_usernames );
    } );
    $wp_network_admins = array_map(function($user) {
        return get_user_by( 'ID', $user->ID );
    }, $wp_network_admins);
    $users = array_merge( $users, $wp_network_admins );
} else {
    // This is a normal wordpress install, get all regular users
    $users = get_users( array(
        'role__in' => array(
            'Author',
            'Editor',
            'Administrator'
        )
    ) );
}

// Filter out duplicate users
$mapped_users = array();
$users = array_filter( $users, function( $user ) use ( &$mapped_users ) {
    if( in_array( $user->ID, $mapped_users ) ) return false;
    $mapped_users[] = $user->ID;
    return true;
} );
sort( $users );


if( count( $_POST ) ) {
    if( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wp-auto-login' ) ) {
        wp_die( 'Authorization failed.' );
    }

    // Check the verification hash
    $auth_key = !empty( $_POST['key'] ) ? trim( $_POST['key'] ) : null;
    $upress_auth = !empty( $_POST['auth'] ) ? trim( $_POST['auth'] ) : null;
    $calculated_hash = hash_hmac( 'sha256', $client_ip . $server_ip . $auth_key, 'EoE8mNAT7Ym975yJdNzEob8qS3ijfrONAT7x' );

    if ( empty( $upress_auth ) || ! hash_equals( $calculated_hash, $upress_auth ) ) {
        wp_die( 'Authorization failed: You are not allowed to login at this time.' );
    }

    if( count( $users ) > 1 ) {
        $user_id = (int)$_POST['userId'];
        $user = get_user_by( 'id', $user_id );
        $user_login = $user->user_login;
    } else {
        $user_id = $users[0]->ID;
        $user_login = $users[0]->user_login;
    }

    $user = wp_set_current_user( $user_id, $user_login );
    wp_set_auth_cookie( $user_id, true );
    do_action( 'wp_login', $user_login, $user );

    wp_redirect( get_admin_url() );
    exit;
} else {
    // Get auth data for current website
    $verify = wp_remote_post( 'https://my.upress.co.il/api/autologin/authorize/v2', array(
        'user-agent' => 'uPressAutologin/' . $host,
        'sslverify'  => true,
        'blocking'   => true,
        'body'       => array(
            'v'    => $auth_key,
            'ip'   => $client_ip,
            'server_ip'   => $server_ip,
            'dev'  => defined( 'AUTOLOGIN_DEV' ) ? AUTOLOGIN_DEV : ''
        ),
    ) );
    $verify = json_decode( wp_remote_retrieve_body( $verify ), true );
    if(is_wp_error( $verify ) || ! isset( $verify[ 'hash' ] )) {
        wp_die( 'Authorization failed: Request expired.' );
    }
    $verification_hash = $verify[ 'hash' ];
}
?>
<!doctype html>
<html>
<head>
    <title>uPress Auto Login</title>
    <meta charset="utf-8">
    <link href="https://my.upress.co.il/themes/upress/assets/css/autologin.css" rel="stylesheet">
</head>
<body class="login login-action-login wp-core-ui  locale-en-us">
<div id="login">
    <h1>
        <a href="https://my.upress.co.il/"
           title="Powered by uPress"
           tabindex="-1"
           style="background-image: none,url('https://www.upress.co.il/themes/upress/assets/img/newhomepage/logo600.png'); width: 160px; background-size: 140px">
            uPress Auto Login
        </a>
    </h1>

    <form method="post">
        <p>
            <label for="userId">Login as</label><br />
            <select id="userId" name="userId" class="input" <?php echo count( $users ) <= 1 ? 'disabled' : ''; ?>>
                <?php foreach( $users as $user ): ?>
                    <option value="<?php echo esc_attr( $user->ID ); ?>">
                        <?php echo $user->user_login; ?>
                        <?php echo $user->user_login !== $user->display_name ? ' (' . $user->display_name . ')' : ''; ?>
                        <?php echo $user->has_cap( 'manage_options' ) ? ' - ' . (is_multisite() && in_array( $user->user_login, $network_admins_usernames ) ? ' Super' : '') . ' Admin' : ''; ?>

                        <?php if ( is_multisite() ) :
                            $user_wp_blogs = get_blogs_of_user( $user->ID );
                            $blogs = array();
                            foreach( $user_wp_blogs as $blog ) {
                                $blogs[] = $blog->blogname;
                            }
                            echo count( $blogs ) ? ' (' . implode(', ', $blogs) . ')' : '';
                        endif; ?>

                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p class="submit">
            <?php wp_nonce_field( 'wp-auto-login' ); ?>
            <input type="hidden" name="key" value="<?php echo esc_attr( $auth_key ); ?>">
            <input type="hidden" name="auth" value="<?php echo esc_attr( $verification_hash ); ?>">
            <button name="wp-submit" id="wp-submit" class="button button-primary button-large">Login</button>
        </p>
    </form>

    <p id="backtoblog"><a href="https://my.upress.co.il/">&larr; Back to uPress Dashboard</a></p>
</div>

<div class="clear"></div>
</body>
</html>
