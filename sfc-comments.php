<?php
/* 
Plugin Name: SFC - Comments
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Allow users to leave comments using their Facebook info (without their logging into WordPress itself).
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

Usage Note: You have to modify your theme to use this plugin.

In your comments.php file (or wherever your comments form is), you need to do the following.

1. Find the three inputs for the name, email, and url.

2. Just before the first input, add this code:
<div id="comment-user-details">
<?php do_action('alt_comment_login'); ?>

3. Just below the last input (not the comment text area, just the name/email/url inputs, add this:
</div>

That will add the necessary pieces to allow the script to work.

Hopefully, a future version of WordPress will make this simpler.

*/

add_action('admin_init','sfc_comm_error_check'); 
function sfc_comm_error_check() { 
      if ( get_option( 'comment_registration' ) ) { 
            add_action('admin_notices', create_function( '', "_e('<div class=\"error\"><p>SFC-Comments doesn\'t work with sites that require registration to comment. Use SFC-Login and SFC-Register to allow users to register on your site.</p></div>','sfc');" ) );
      } 
} 

// if you don't want the plugin to ask for email permission, ever, then define this to true in your wp-config
if ( !defined('SFC_DISABLE_EMAIL_PERMISSION') )
	define( 'SFC_DISABLE_EMAIL_PERMISSION', false ); 


// checks for sfc on activation
function sfc_comm_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.1', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_comm_activation_check');

// force load jQuery (we need it later anyway)
add_action('wp_enqueue_scripts','sfc_comm_jquery');
function sfc_comm_jquery() {
	wp_enqueue_script('jquery');
}

// set a variable to know when we are showing comments (no point in adding js to other pages)
add_action('comment_form','sfc_comm_comments_enable');
function sfc_comm_comments_enable() {
	global $sfc_comm_comments_form;
	$sfc_comm_comments_form = true;
}

// add placeholder for sending comment to Facebook checkbox
add_action('comment_form','sfc_comm_send_place');
function sfc_comm_send_place() {
?><p id="sfc_comm_send"></p><?php
}

// hook to the footer to add our scripting
add_action('wp_footer','sfc_comm_footer_script',30); // 30 to ensure we happen after sfc base
function sfc_comm_footer_script() {
	global $sfc_comm_comments_form;
	if ($sfc_comm_comments_form != true) return; // nothing to do, not showing comments

	if ( is_user_logged_in() ) return; // don't bother with this stuff for logged in users
	
	$options = get_option('sfc_options');
?>
<style type="text/css">
#fb-user { border: 1px dotted #C0C0C0; padding: 5px; display: block; height: 96px; }
#fb-user .fb_profile_pic_rendered { margin-right: 5px; }
#fb-user a.FB_Link img { float: left; }
</style>

<script type="text/javascript">
var fb_connect_user = false;

function sfc_update_user_details() {
	fb_connect_user = true;

	// Show their FB details TODO this should be configurable, or at least prettier...
	if (!jQuery('#fb-user').length) {
		jQuery('#comment-user-details').hide().after("<span id='fb-user'>" +
		"<fb:profile-pic uid='loggedinuser' facebook-logo='true' size='normal' height='96'></fb:profile-pic>" +
		"<span id='fb-msg'><strong><fb:intl><?php echo addslashes(__('Hi', 'sfc')); ?></fb:intl> <fb:name uid='loggedinuser' useyou='false'></fb:name>!</strong><br /><fb:intl><?php echo addslashes(__('You are connected with your Facebook account.', 'sfc')); ?></fb:intl>" +
		"<a href='#' onclick='FB.Connect.logoutAndRedirect(\"<?php the_permalink() ?>\"); return false;'> <?php echo addslashes(__('Logout', 'sfc')); ?></a>" +
		"</span></span>");
		jQuery('#sfc_comm_send').html('<input style="width: auto;" type="checkbox" id="sfc_comm_share" /><label for="sfc_comm_send"><fb:intl><?php echo addslashes(__('Share Comment on Facebook', 'sfc')); ?></fb:intl></label>');
	}

	// Refresh the DOM
	FB.XFBML.Host.parseDomTree();
}

jQuery("#commentform").bind('submit',sfc_handle_submit_share);
function sfc_handle_submit_share() {
	if (jQuery('#sfc_comm_share:checked').val() == 'on') {
		sfc_setCookie('sfc_share', 'yes');
	}
	return true;
}

<?php if (get_option('require_name_email') && !SFC_DISABLE_EMAIL_PERMISSION) { ?>
// first, check if we already have email permission
var sfc_comm_email_perm = false;
FB.ensureInit ( function () { 
	FB.Facebook.apiClient.users_hasAppPermission('email',function(res,ex){
		if (res == 0) {
			// no permission, ask for it on submit
			jQuery("#commentform").bind('submit',sfc_get_email_perms);
		} else {
			// we have permission, no special handler needed
			sfc_comm_email_perm = true;
		}
	});
});

// ask for email permission
function sfc_get_email_perms() {
	if (sfc_comm_email_perm) return true;
	if (fb_connect_user) {
		FB.Facebook.apiClient.users_hasAppPermission('email',function(res,ex){
			if (res == 0) {
				FB.Connect.showPermissionDialog("email", function(perms) {
					if (perms.match("email")) {
						sfc_commentform_submit();
					} else {
						var dialog = FB.UI.FBMLPopupDialog('<?php echo addslashes(__('Email required', 'sfc')); ?>', '');
						var fbml='\
<div id="fb_dialog_content" class="fb_dialog_content">\
	<div class="fb_confirmation_stripes"></div>\
	<div class="fb_confirmation_content"><p><?php echo addslashes(__('This site requires permission to get your email address for you to leave a comment. You can not leave a comment without granting that permission.', 'sfc')); ?></p></div>\
</div>';
						dialog.setFBMLContent(fbml);
						dialog.setContentWidth(540); 
						dialog.setContentHeight(65);
						dialog.set_placement(FB.UI.PopupPlacement.topCenter);
						dialog.show();
						setTimeout ( function() { dialog.close(); }, 5000 );					
					}
				});
			} else {
				sfc_commentform_submit();
			}
		});
		return false;
	} else {
		return true;
	}	
}

// submit the form
function sfc_commentform_submit() {
	jQuery("#commentform").unbind('submit',sfc_get_email_perms);
	jQuery("#commentform :submit").click();
}
<?php } ?>

function sfc_setCookie(c_name,value,expiredays) {
	var exdate=new Date();
	exdate.setDate(exdate.getDate()+expiredays);
	document.cookie=c_name+ "=" +escape(value)+((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
}

function sfc_getCookie(c_name) {
	if (document.cookie.length>0) {
		c_start=document.cookie.indexOf(c_name + "=");
		if (c_start!=-1) {
			c_start=c_start + c_name.length+1;
			c_end=document.cookie.indexOf(";",c_start);
			if (c_end==-1) c_end=document.cookie.length;
			return unescape(document.cookie.substring(c_start,c_end));
		}
	}
	return "";
}

FB.ensureInit ( function () { 
	FB.Connect.ifUserConnected(sfc_update_user_details);
	if (sfc_getCookie('sfc_share') == 'yes') {
		sfc_setCookie('sfc_share', null);
		<?php
			global $post;
			// build the attachment
			$permalink = get_permalink($post->ID);
			$attachment['name'] = get_the_title();
			$attachment['href'] = get_permalink();
			$attachment['description'] = sfc_comm_make_excerpt($post->post_content);
			$attachment['caption'] = '{*actor*} left a comment on '.get_the_title();
			$attachment['comments_xid'] = urlencode(get_permalink());
						
			$action_links[0]['text'] = 'Read Post';
			$action_links[0]['href'] = get_permalink();
		?>
		
		FB.Connect.streamPublish(null, 
			<?php echo json_encode($attachment); ?>,
			<?php echo json_encode($action_links); ?>
			);
	}
}); 
</script>
<?php
}

// I wish wp_trim_excerpt was easier to use separately...
function sfc_comm_make_excerpt($text) {
	$text = strip_shortcodes( $text );
	remove_filter( 'the_content', 'wptexturize' );
	$text = apply_filters('the_content', $text);
	add_filter( 'the_content', 'wptexturize' );
	$text = str_replace(']]>', ']]&gt;', $text);
	$text = wp_strip_all_tags($text);
	$text = str_replace(array("\r\n","\r","\n"),' ',$text);
	$excerpt_length = apply_filters('excerpt_length', 55);
	$excerpt_more = apply_filters('excerpt_more', '[...]');
	$words = explode(' ', $text, $excerpt_length + 1);
	if (count($words) > $excerpt_length) {
		array_pop($words);
		array_push($words, $excerpt_more);
		$text = implode(' ', $words);
	}
	return $text;
}

// this bit is to allow the user to add the relevant comments login button to the comments form easily
// user need only stick a do_action('alt_comment_login'); wherever he wants the button to display
add_action('alt_comment_login','sfc_comm_login_button');
add_action('comment_form_before_fields', 'sfc_comm_login_button',10,0); // WP 3.0 support

function sfc_comm_login_button() {
	echo '<p><fb:login-button v="2" perms="email" onlogin="sfc_update_user_details();"><fb:intl>'.__('Connect with Facebook', 'sfc').'</fb:intl></fb:login-button></p>';
}

// this exists so that other plugins can hook into the same place to add their login buttons
if (!function_exists('alt_login_method_div')) {

add_action('alt_comment_login','alt_login_method_div',5,0);
add_action('comment_form_before_fields', 'alt_login_method_div',5,0); // WP 3.0 support

function alt_login_method_div() { echo '<div id="alt-login-methods">'; }

add_action('alt_comment_login','alt_login_method_div_close',20,0);
add_action('comment_form_before_fields', 'alt_login_method_div_close',20,0); // WP 3.0 support

function alt_login_method_div_close() { echo '</div>'; }

}

// WP 3.0 support
if (!function_exists('comment_user_details_begin')) {

add_action('comment_form_before_fields', 'comment_user_details_begin',1,0);
function comment_user_details_begin() { echo '<div id="comment-user-details">'; }

add_action('comment_form_after_fields', 'comment_user_details_end',20,0);
function comment_user_details_end() { echo '</div>'; }

}

// generate facebook avatar code for FB user comments
add_filter('get_avatar','sfc_comm_avatar', 10, 5);
function sfc_comm_avatar($avatar, $id_or_email, $size = '96', $default = '', $alt = false) {
	// check to be sure this is for a comment
	if ( !is_object($id_or_email) || !isset($id_or_email->comment_ID) || $id_or_email->user_id) 
		 return $avatar;
		 
	// check for fbuid comment meta
	$fbuid = get_comment_meta($id_or_email->comment_ID, 'fbuid', true);
	if ($fbuid) {
		// return the avatar code
		return "<img width='{$size}' height='{$size}' class='avatar avatar-{$size} fbavatar' src='http://graph.facebook.com/{$fbuid}/picture?type=square' />";
	}
	
	// check for number@facebook.com email address (deprecated, auto-converts to new meta data)
	if (preg_match('|(\d+)\@facebook\.com|', $id_or_email->comment_author_email, $m)) {
		// save the fbuid as meta data
		update_comment_meta($id_or_email->comment_ID, 'fbuid', $m[1]);
		
		// return the avatar code
		return "<img width='{$size}' height='{$size}' class='avatar avatar-{$size} fbavatar' src='http://graph.facebook.com/{$m[1]}/picture?type=square' />";
	}
	
	return $avatar;
}

// store the FB user ID as comment meta data ('fbuid')
add_action('comment_post','sfc_comm_add_meta', 10, 1);
function sfc_comm_add_meta($comment_id) {
	$options = get_option('sfc_options');
	include_once 'facebook-platform/facebook.php';
	$fb=new Facebook($options['api_key'], $options['app_secret']);
	$fbuid=$fb->get_loggedin_user();
	if ($fbuid) {
		update_comment_meta($comment_id, 'fbuid', $fbuid);
	}
}

// Add user fields for FB commenters
add_filter('pre_comment_on_post','sfc_comm_fill_in_fields');
function sfc_comm_fill_in_fields($comment_post_ID) {
	if (is_user_logged_in()) return; // do nothing to WP users
	
	$options = get_option('sfc_options');
	include_once 'facebook-platform/facebook.php';
	$fb=new Facebook($options['api_key'], $options['app_secret']);
	$fbuid=$fb->get_loggedin_user();
	
	// this is a facebook user, override the sent values with FB info
	if ($fbuid) {
		$user_details = $fb->api_client->users_getInfo($fbuid, 'name, profile_url');
		if (is_array($user_details)) {
			$_POST['author'] = $user_details[0]['name']; 
			$_POST['url'] = $user_details[0]['profile_url'];
		}
		
		$query = "SELECT email FROM user WHERE uid=\"{$fbuid}\""; 
		$email = $fb->api_client->fql_query($query);
		if (is_array($email)) {
			$email = $email[0]['email'];
			$_POST['email'] = $email; 
		}
	}
}
