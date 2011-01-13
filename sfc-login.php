<?php
/*
Plugin Name: SFC - Login
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Integrates Facebook Login and Authentication to WordPress. Log into your WordPress account with your Facebook credentials.
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

// if you want people to be unable to disconnect their WP and FB accounts, set this to false in wp-config
if (!defined('SFC_ALLOW_DISCONNECT')) 
	define('SFC_ALLOW_DISCONNECT',true);

// checks for sfc on activation
function sfc_login_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.7', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_login_activation_check');

// add the section on the user profile page
add_action('profile_personal_options','sfc_login_profile_page');

function sfc_login_profile_page($profile) {
	$options = get_option('sfc_options');
?>
	<table class="form-table">
		<tr>
			<th><label><?php _e('Facebook Connect', 'sfc'); ?></label></th>
<?php
	$fbuid = get_usermeta($profile->ID, 'fbuid');	
	if (empty($fbuid)) { 
		?>
			<td><p><fb:login-button perms="email,offline_access" v="2" size="large" onlogin="sfc_login_update_fbuid(0);"><fb:intl><?php _e('Connect this WordPress account to Facebook', 'sfc'); ?></fb:intl></fb:login-button></p></td>
		</tr>
	</table>
	<?php	
	} else { ?>
		<td><p><?php _e('Connected as', 'sfc'); ?>
		<fb:profile-pic size="square" width="32" height="32" uid="<?php echo $fbuid; ?>" linked="true"></fb:profile-pic>
		<fb:name useyou="false" uid="<?php echo $fbuid; ?>"></fb:name>.
<?php if (SFC_ALLOW_DISCONNECT) { ?>
		<input type="button" class="button-primary" value="<?php _e('Disconnect this account from WordPress', 'sfc'); ?>" onclick="sfc_login_update_fbuid(1); return false;" />
<?php } ?>
		</p></td>
	<?php } ?>
	</tr>
	</table>
	<?php
}

add_action('admin_footer','sfc_login_update_js',30); 
function sfc_login_update_js() {
	if (IS_PROFILE_PAGE) {
		?>
		<script type="text/javascript">
		function sfc_login_update_fbuid(disconnect) {
			FB.ensureInit ( function () { 
				var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
				if (disconnect == 1) {
					// FB.Connect.logout(); // logs the user out of facebook.. supposed to log out of this site too, but doesn't.
					var fbuid = 0;
				} else {
					var fbuid = FB.Connect.get_loggedInUser();
				}
				var data = {
					action: 'update_fbuid',
					fbuid: fbuid
				}
				jQuery.post(ajax_url, data, function(response) {
					if (response == '1') {
						location.reload(true);
					}
				});
			});
		}
		</script>
		<?php
	}
}

add_action('wp_ajax_update_fbuid', 'sfc_login_ajax_update_fbuid');
function sfc_login_ajax_update_fbuid() {
	$options = get_option('sfc_options');
	$user = wp_get_current_user();
	$fbuid = trim($_POST['fbuid']);
	update_usermeta($user->ID, 'fbuid', $fbuid);
	echo 1;
	exit();
}

// computes facebook's email hash thingy. See http://wiki.developers.facebook.com/index.php/Connect.registerUsers
function sfc_login_fb_hash_email($email) {
	$email = strtolower(trim($email));
	$c = crc32($email);
	$m = md5($email);
	$fbhash = sprintf('%u_%s',$c,$m);
	return $fbhash;
}
	
add_action('login_form','sfc_login_add_login_button');
function sfc_login_add_login_button() {
	global $action;
	?>
	<script type="text/javascript">
	function sfc_login_check() {
		FB.Facebook.apiClient.users_hasAppPermission('email',function(res,ex){
			if( !res ) {
				FB.Connect.showPermissionDialog("email", function(perms) {
					window.location.reload();
				});
			} else {
				window.location.reload();
			}
		});
	}
	</script>
	<?php
	if ($action == 'login') echo '<p><fb:login-button v="2" perms="email" onlogin="sfc_login_check();"><fb:intl>';
	_e('Connect with Facebook', 'sfc');
	echo '</fb:intl></fb:login-button></p><br />';
}

add_filter('authenticate','sfc_login_check',90);
function sfc_login_check($user) {
	if ( is_a($user, 'WP_User') ) { return $user; } // check if user is already logged in, skip FB stuff

	$options = get_option('sfc_options');	

	// load facebook platform
	include_once 'facebook-platform/facebook.php';

	$fb=new Facebook($options['api_key'], $options['app_secret']);
	$fbuid=$fb->get_loggedin_user();
	
	if($fbuid):
	    try {
	        $test = $fb->api_client->fql_query('SELECT uid, pic_square, first_name FROM user WHERE uid = ' . $fbuid);
	        if ($test) {
				global $wpdb;
				$user_id = $wpdb->get_var( $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'fbuid' AND meta_value = %s", $fbuid) );
				
				if ($user_id) {
					$user = new WP_User($user_id);
				} else {
					do_action('sfc_login_new_fb_user',$fb); // hook for creating new users if desired
					global $error;
					$error = '<strong>'.__('ERROR', 'sfc').'</strong>: '.__('Facebook user not recognized.', 'sfc');
				}
			}

	    } catch (Exception $ex) {
	        $fb->clear_cookie_state();
	    }
	    
	endif;
		
	return $user;	
}

add_action('wp_logout','sfc_login_logout');
function sfc_login_logout() {
	$options = get_option('sfc_options');	
	
	$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : wp_login_url().'?loggedout=true';
	
	// load facebook platform
	include_once 'facebook-platform/facebook.php';
	
	$fb=new Facebook($options['api_key'], $options['app_secret']);
	$fbuid=$fb->get_loggedin_user();
	if ($fbuid) {
		$fb->logout($redirect_to);
	}
}

add_action('login_head','sfc_login_featureloader');
function sfc_login_featureloader() {
	if ($_SERVER['HTTPS'] == 'on')
		echo "<script src='https://ssl.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php'></script>";
	else
		echo "<script src='http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php'></script>";
}

add_action('login_form','sfc_add_base_js');

// add the fb icon to the admin bar
add_filter('admin_user_info_links','sfc_login_admin_header');
function sfc_login_admin_header($links) {
	$user = wp_get_current_user();
	$fbuid = get_user_meta($user->ID, 'fbuid', true);
	$icon = plugins_url('/images/fb-icon.png', __FILE__);
	if ($fbuid) $links[6]="<a href='http://www.facebook.com/profile.php?id=$fbuid'><img src='$icon' /></a>";
	return $links;
}

/*
// generate facebook avatar code for users who login with Facebook
// NOTE: This overrides Gravatar if you use it.
//
add_filter('get_avatar','sfc_login_avatar', 10, 5);
function sfc_login_avatar($avatar, $id_or_email, $size = '96', $default = '', $alt = false) {
	// check to be sure this is for a user id
	if ( !is_numeric($id_or_email) ) return $avatar;
	$fbuid = get_usermeta( $id_or_email, 'fbuid');
	if ($fbuid) {
		// return the avatar code
		return "<img width='{$size}' height='{$size}' class='avatar avatar-{$size} fbavatar' src='http://graph.facebook.com/{$fbuid}/picture?type=square' />";
	}
	return $avatar;
}
*/
