<?php
/*
Plugin Name: SFC - Register
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Allows new users to register using Facebook credentials.
Author: Otto
Version: 0.25
Author URI: http://ottodestruct.com
License: GPL2

    Copyright 2009-2010  Samuel Wood  (email : otto@ottodestruct.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2, 
    as published by the Free Software Foundation. 
    
    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    The license for this software can likely be found here: 
    http://www.gnu.org/licenses/gpl-2.0.html
    
*/

// quick exit when users cant register to begin with
if ( !get_option('users_can_register') ) {
	return;
}

// if you want registration to be totally transparent, set this to true in wp-config
if (!defined('SFC_REGISTER_TRANSPARENT')) 
	define ('SFC_REGISTER_TRANSPARENT', false);

// checks for sfc on activation
function sfc_register_activation_check(){
	if (!function_exists('sfc_login_activation_check')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate ourself
		wp_die(__('The SFC-Register plugin requires that the SFC-Login plugin be activated first.', 'sfc'));
	}
	
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.10', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_register_activation_check');

// force a registration redirect if user is unknown
add_action('sfc_login_new_fb_user','sfc_register_redirect');
function sfc_register_redirect($fb) {
	if (SFC_REGISTER_TRANSPARENT) {
		$fbuid=$fb->get_loggedin_user();

		// this is a facebook user, get the info
		if ($fbuid) {
			$user_details = $fb->api_client->users_getInfo($fbuid, 'name, proxied_email');
			if (is_array($user_details)) {
				$fbname = $user_details[0]['name'];
				$fbemail = $fbemail[0]['proxied_email'];
			}

			$query = "SELECT email FROM user WHERE uid=\"{$fbuid}\"";
			$fbemail = $fb->api_client->fql_query($query);
			if (is_array($fbemail)) {
				$fbemail = $fbemail[0]['email'];
			}
		}

		// force create the user instantly
		require_once( ABSPATH . WPINC . '/registration.php');
		$errors = register_new_user($fbname, $fbemail);
		
		if ( !is_wp_error($errors) ) {
			wp_redirect('wp-login.php?checkemail=registered');
			exit();
		}
	}
	wp_redirect('wp-login.php?action=register');
	exit();
}

// we need jquery on the register form
add_action('login_head','sfc_register_jquery');
function sfc_register_jquery() {
	echo "<script src='".site_url( '/wp-includes/js/jquery/jquery.js' )."'></script>";
}

// add init code
add_action('register_form','sfc_add_base_js');

// add javascript to fill in the reg form automagically for fb users
add_action('register_form','sfc_register_form');
function sfc_register_form() {
	$options = get_option('sfc_options');
	include_once 'facebook-platform/facebook.php';
	$fb=new Facebook($options['api_key'], $options['app_secret']);
	$fbuid=$fb->get_loggedin_user();
	
	// this is a facebook user, get the info
	if ($fbuid) {
		$user_details = $fb->api_client->users_getInfo($fbuid, 'name, proxied_email');
		if (is_array($user_details)) {
			$fbname = $user_details[0]['name']; 
		}
		
		$query = "SELECT email FROM user WHERE uid=\"{$fbuid}\"";
		$fbemail = $fb->api_client->fql_query($query);
		if (is_array($fbemail)) {
			$fbemail = $fbemail[0]['email'];
		}
	}
?>
<script type="text/javascript">
FB.ensureInit ( function () { 
	FB.Connect.ifUserConnected( function () { 
		jQuery('#user_login').val(<?php echo json_encode($fbname); ?>);
		FB.Facebook.apiClient.users_hasAppPermission('email',function(res,ex){
			if( !res ) {
				FB.Connect.showPermissionDialog("email", function(perms) {
					if (perms) {
						window.location.reload();
					}
				});
			} else {
				jQuery('#user_email').val(<?php echo json_encode($fbemail); ?>);
			}
		});	
	});
});

FB.ensureInit(function(){
	FB.Connect.ifUserConnected( function() {
		jQuery('#sfc-fb-button').html('<input class="button-primary" type="button" onclick="FB.Connect.logoutAndRedirect(\'wp-login.php\');" value="<?php echo addslashes(__('Logout of Facebook', 'sfc')); ?>" />');
	}, function() {
		jQuery('#sfc-fb-button').html('<fb:login-button v="2" perms="email" onlogin="location.reload(true);"><fb:intl><?php echo addslashes(__('Connect with Facebook', 'sfc')); ?></fb:intl></fb:login-button>');
		FB.XFBML.Host.parseDomTree();
	});
});
</script>
<div id="sfc-fb-button"></div>

<?php
}

add_action('user_register','sfc_register_metadata');
function sfc_register_metadata($user_id) {
	$options = get_option('sfc_options');
	include_once 'facebook-platform/facebook.php';
	$fb=new Facebook($options['api_key'], $options['app_secret']);
	$fbuid=$fb->get_loggedin_user();
	
	// this is a facebook user, get the info
	if ($fbuid) {
		$user_details = $fb->api_client->users_getInfo($fbuid, 'first_name, last_name, profile_url, about_me'); 
		if ($user_details) {
			$user['ID'] = $user_id;
			$user['user_url'] = $user_details[0]['profile_url'];
			wp_update_user($user);

			update_usermeta( $user_id, 'fbuid', $fbuid);	
			update_usermeta( $user_id, 'first_name', $user_details[0]['first_name'] );
			update_usermeta( $user_id, 'last_name', $user_details[0]['last_name']);
			update_usermeta( $user_id, 'description', $user_details[0]['about_me'] );
		}
	}
}
