<?php
/*
Plugin Name: SFC - Publish
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Allows you to share your posts to your Facebook Application page. Activate this plugin, then look on the Edit Post pages for Facebook publishing buttons.
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

// checks for sfc on activation
function sfc_publish_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.4', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_publish_activation_check');

// add the meta boxes
add_action('admin_menu', 'sfc_publish_meta_box_add');
function sfc_publish_meta_box_add() {
	$post_types = get_post_types( array('public' => true), 'objects' );
	foreach ( $post_types as $post_type ) {
		add_meta_box('sfc-publish-div', __('Facebook Publisher', 'sfc'), 'sfc_publish_meta_box', $post_type->name, 'side');
	}
}

// add the admin sections to the sfc page
add_action('admin_init', 'sfc_publish_admin_init');
function sfc_publish_admin_init() {
	add_settings_section('sfc_publish', __('Publish Settings', 'sfc'), 'sfc_publish_section_callback', 'sfc');
	add_settings_field('sfc_publish_flags', __('Automatic Publishing', 'sfc'), 'sfc_publish_auto_callback', 'sfc', 'sfc_publish');
	add_settings_field('sfc_publish_extended_permissions', __('Extended Permissions', 'sfc'), 'sfc_publish_extended_callback', 'sfc', 'sfc_publish');
	wp_enqueue_script('jquery');
}

function sfc_publish_section_callback() {
	echo "<p>".__('Settings for the SFC-Publish plugin. The manual Facebook Publishing buttons can be found on the Edit Post or Edit Page screen, after you publish a post. If you can\'t find them, try scrolling down or seeing if you have the box disabled in the Options dropdown.', 'sfc')."</p>";
}

function sfc_publish_auto_callback() {
	$options = get_option('sfc_options');
	if (!$options['autopublish_app']) $options['autopublish_app'] = false;
	if (!$options['autopublish_profile']) $options['autopublish_profile'] = false;
	?>
	<p><label>Automatically Publish to Facebook <?php
	if ($options['fanpage']) echo 'Fan Page';
	else echo 'Application';
	?>: <input type="checkbox" name="sfc_options[autopublish_app]" value="1" <?php checked('1', $options['autopublish_app']); ?> /></label>
	<?php if (!$options['fanpage']) _e('(Note: This does not work due to a <a href="http://bugs.developers.facebook.com/show_bug.cgi?id=8184">Facebook bug</a>.)', 'sfc'); ?>
	</p>
	<p><label><?php _e('Automatically Publish to Facebook Profile:', 'sfc'); ?> <input type="checkbox" name="sfc_options[autopublish_profile]" value="1" <?php checked('1', $options['autopublish_profile']); ?> /></label></p>
<?php
}

function sfc_publish_extended_callback() {

	$options = get_option('sfc_options');

	// load facebook platform
	include_once 'facebook-platform/facebook.php';
	$fb=new Facebook($options['api_key'], $options['app_secret']);

?><p><?php _e('In order for the SFC-Publish plugin to be able to publish your posts automatically, you must grant some "Extended Permissions"
to the plugin.', 'sfc'); ?></p>
<ul>
<li><?php _e('Offline Permission is needed to access your Page as if you were publishing manually.', 'sfc'); ?><br /><span id="sfc-offline-perm-check"></span></li>
<li><?php _e('Publish Permission is needed to publish stories to the stream automatically.', 'sfc'); ?><br /><span id="sfc-publish-perm-check"></span></li>
<?php if ($options['fanpage']) { ?>
<li><?php _e('Fan Page Publish Permission is needed to publish stories to the Fan Page automatically.', 'sfc'); ?><br /><span id="sfc-fanpage-perm-check"></span></li>
<?php } ?>
</ul>
<?php if ($options['user'] && $options['session_key']) {
	?><p><?php _e('User ID and Session Key found! Automatic publishing is ready to go!', 'sfc'); ?></p><?php
} else {
	?><p><?php _e('Be sure to click the "Save Settings" button on this page after granting these permissions! This will allow SFC to save your user id and session key, for usage by the plugin when publishing posts to your profile and/or page.', 'sfc'); ?></p><?php
} ?>
<script type="text/javascript">
FB.ensureInit(function () {
<?php
	if ($options['fanpage']) {
		try {
			$result = $fb->api_client->users_hasAppPermission('publish_stream', $options['fanpage']);
			if (!$result) $add_auths=true;
		} catch (Exception $e) {
			$add_auths=true;
		}
		if ($add_auths) {
		?>
		jQuery('#sfc-fanpage-perm-check').html('<input type="button" class="button-primary" onclick="sfc_publish_get_perm(\'publish_stream\',\'#sfc-fanpage-perm-check\', <?php echo $options['fanpage']; ?>);" value="<?php echo addslashes(__('Grant Publish Permission', 'sfc')); ?>" />');
		<?php } else { ?>
		jQuery('#sfc-fanpage-perm-check').html('<input type="button" class="button-primary" disabled="disabled" value="<?php echo addslashes(__('Fan Page Publish Permission Granted', 'sfc')); ?>" />');
		<?php }
	}
	?>

	FB.Facebook.apiClient.users_hasAppPermission('offline_access', function(res,ex) {
		if (res == 0) {
			jQuery('#sfc-offline-perm-check').html('<input type="button" class="button-primary" onclick="sfc_publish_get_perm(\'offline_access\',\'#sfc-offline-perm-check\');" value="<?php echo addslashes(__('Grant Offline Permission', 'sfc')); ?>" />');
		} else {
			jQuery('#sfc-offline-perm-check').html('<input type="button" class="button-primary" disabled="disabled" value="<?php echo addslashes(__('Offline Permission Granted', 'sfc')); ?>" />');
		}
	});

	FB.Facebook.apiClient.users_hasAppPermission('publish_stream', function(res,ex) {
		if (res == 0) {
			jQuery('#sfc-publish-perm-check').html('<input type="button" class="button-primary" onclick="sfc_publish_get_perm(\'publish_stream\',\'#sfc-publish-perm-check\');" value="<?php echo addslashes(__('Grant Publish Permission', 'sfc')); ?>" />');
		} else {
			jQuery('#sfc-publish-perm-check').html('<input type="button" class="button-primary" disabled="disabled" value="<?php echo addslashes(__('Publish Permission Granted', 'sfc')); ?>" />');
		}
	});
});

function sfc_publish_get_perm($perm, $id, $page) {

	if ($page) {
		FB.Connect.showPermissionDialog($perm, function(res,ex) {
			if (res.match($perm)) {
				jQuery($id).html('<input type="button" class="button-primary" disabled="disabled" value="<?php echo addslashes(__('Permission Granted', 'sfc')); ?>" />');
			}
		}, true, $page);
	} else {
		FB.Connect.showPermissionDialog($perm, function(res,ex) {
			if (res.match($perm)) {
				jQuery($id).html('<input type="button" class="button-primary" disabled="disabled" value="<?php echo addslashes(__('Permission Granted', 'sfc')); ?>" />');
			}
		});
	}
}
</script>
<?php
}

// I wish wp_trim_excerpt was easier to use separately...
function sfc_publish_make_excerpt($text) {
	$text = strip_shortcodes( $text );
	remove_filter( 'the_content', 'wptexturize' );
	$text = apply_filters('the_content', $text);
	add_filter( 'the_content', 'wptexturize' );
	$text = str_replace(']]>', ']]&gt;', $text);
	$text = wp_strip_all_tags($text);
	$text = str_replace(array("\r\n","\r","\n"),' ',$text);
	$excerpt_more = apply_filters('excerpt_more', '[...]');
	$excerpt_more = html_entity_decode($excerpt_more, ENT_QUOTES, 'UTF-8');
	$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        $max = min(1000,apply_filters('sfc_excerpt_length',1000));
        $max -= strlen ($excerpt_more) + 1;
        $max -= strlen ('</fb:intl>') * 2 - 1;
        
	$text = substr($text, 0, $max);
	$words = explode(' ', $text);
	array_pop ($words);
	array_push ($words, $excerpt_more);
	$text = implode(' ', $words);
        
	return $text;
}

function sfc_publish_meta_box( $post ) {
	$options = get_option('sfc_options');

	if ($post->post_status == 'private') {
		echo '<p>'.__('Why would you put private posts on Facebook, for all to see?', 'sfc').'</p>';
		return;
	}

	if ($post->post_status !== 'publish') {
		echo '<p>'.__('After publishing the post, you can send it to Facebook from here.', 'sfc').'</p>';
		return;
	}

	// apply the content filters, in case some plugin is doing weird image stuff
	$content = apply_filters('the_content', $post->post_content);

	// look for the images to add with image_src
	$images = array();

	// get the post thumbnail, put it first in the image list
	if (current_theme_supports('post-thumbnails')) {
		if ( has_post_thumbnail($post->ID) ) {
			$thumbid = get_post_thumbnail_id($post->ID);
			$att = wp_get_attachment_image_src($thumbid, 'full');
			if (!empty($att[0])) $images[] = $att[0];
		}
	}

	// look for any images in the content
	if ( preg_match_all('/<img (.+?)>/i', $content, $matches) ) {
		foreach ($matches[1] as $match) {
			foreach ( wp_kses_hair($match, array('http')) as $attr) {
				$img[strtolower($attr['name'])] = $attr['value'];
			}
			if ( isset($img['src']) ) {
				if (!isset($img['class']) ||
					(isset($img['class']) && false === straipos($img['class'], apply_filters('sfc_img_exclude',array('wp-smiley'))))
					) { // ignore smilies
					$images[] = $img['src'];
				}
			}
		}
	}

	// build the attachment
	$permalink = apply_filters('sfc_publish_permalink',get_permalink($post->ID),$post->ID);
	$attachment['name'] = $post->post_title;
	$attachment['href'] = $permalink;
	$attachment['description'] = sfc_publish_make_excerpt($post->post_content);
	//$attachment['comments_xid'] = urlencode($permalink);

	// image attachments (up to 5, as that's all FB allows)
	$count=0;
	foreach ($images as $image) {
		$attachment['media'][$count]['type'] = 'image';
		$attachment['media'][$count]['src'] = $image;
		$attachment['media'][$count]['href'] = $permalink;
		$count++; if ($count==5) break;
	}

	// Share link
	$action_links[0]['text'] = 'Share';
	$action_links[0]['href'] = 'http://www.facebook.com/share.php?u='.urlencode($permalink);
	?>
	<script type="text/javascript">
	function sfcPublish() {
	  FB.ensureInit(function () {
	    FB.Connect.streamPublish(null,
			<?php echo json_encode($attachment); ?>,
			<?php echo json_encode($action_links); ?>,
			null, null, null, false,
			'<?php
			if ($options['fanpage']) echo $options['fanpage'];
			else echo $options['appid'];
			?>'
		);
	  });
	}

	function sfcPersonalPublish() {
		  FB.ensureInit(function () {
		    FB.Connect.streamPublish(null,
				<?php echo json_encode($attachment); ?>,
				<?php echo json_encode($action_links); ?>
			);
		  });
	}

	function sfcShowPubButtons() {
		jQuery('#sfc-publish-buttons').html('<input type="button" class="button-primary" onclick="sfcPublish(); return false;" value="<?php echo addslashes(__('Publish to Facebook', 'sfc')); ?> <?php if ($options["fanpage"]) echo addslashes (__('Fan Page', 'sfc')); else echo addslashes(__('Application', 'sfc')); ?>" /><input type="button" class="button-primary" onclick="sfcPersonalPublish(); return false;" value="<?php echo addslashes(__('Publish to your Facebook Profile', 'sfc')); ?>" />');
	}

	FB.ensureInit(function(){
		FB.Connect.ifUserConnected(sfcShowPubButtons, function() {
			jQuery('#sfc-publish-buttons').html('<fb:login-button v="2" perms="email" onlogin="sfcShowPubButtons();"><fb:intl><?php echo addslashes(__('Connect with Facebook', 'sfc')); ?></fb:intl></fb:login-button>');
			FB.XFBML.Host.parseDomTree();
		});
	});

	</script>
	<div id="sfc-publish-buttons"><p><?php _e('If you can see this, then there is some form of problem showing you the Facebook publishing buttons. This may be caused by a plugin conflict or some form of bad javascript on this page. Try reloading or disabling other plugins to find the source of the problem.', 'sfc'); ?></p></div>
	<?php
}

//add_action('publish_post','sfc_publish_automatic',10,2);
//add_action('publish_page','sfc_publish_automatic',10,2);

// this new function prevents edits to existing posts from auto-posting
add_action('transition_post_status','sfc_publish_auto_check',10,3);
function sfc_publish_auto_check($new, $old, $post) {
	if ($new == 'publish' && $old != 'publish') {
		$post_types = get_post_types( array('public' => true), 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( $post->post_type == $post_type->name ) {
				sfc_publish_automatic($post->ID, $post);
				break;
			}
		}
	}
}

function sfc_publish_automatic($id, $post) {

	// check to make sure post is published
	if ($post->post_status !== 'publish') return;

	// check options to see if we need to send to FB at all
	$options = get_option('sfc_options');
	if (!$options['autopublish_app'] && !$options['autopublish_profile'])
		return;

	// load facebook platform
	include_once 'facebook-platform/facebook.php';
	$fb=new Facebook($options['api_key'], $options['app_secret']);

	// to do this autopublish, we might need to switch users
	if ($options['user'] && $options['session_key']) {
		$tempuser = $fb->user;
	    $tempkey = $fb->api_client->session_key = $session_key;
		$fb->set_user($options['user'], $options['session_key']);
	} else {
		return; // safety net: if we don't have a user and session key, we can't publish properly.
	}

	// build the post to send to FB

	// apply the content filters, in case some plugin is doing weird image stuff
	$content = apply_filters('the_content', $post->post_content);

	// look for the images to add with image_src
	$images = array();

	// get the post thumbnail, put it first in the image list
	if (current_theme_supports('post-thumbnails')) {
		if ( has_post_thumbnail($post->ID) ) {
			$thumbid = get_post_thumbnail_id($post->ID);
			$att = wp_get_attachment_image_src($thumbid, 'full');
			if (!empty($att[0])) $images[] = $att[0];
		}
	}

	// look for any images in the content
	if ( preg_match_all('/<img (.+?)>/i', $content, $matches) ) {
		foreach ($matches[1] as $match) {
			foreach ( wp_kses_hair($match, array('http')) as $attr) {
				$img[strtolower($attr['name'])] = $attr['value'];
			}
			if ( isset($img['src']) ) {
				if (!isset($img['class']) ||
					(isset($img['class']) && false === straipos($img['class'], apply_filters('sfc_img_exclude',array('wp-smiley'))))
					) { // ignore smilies
					$images[] = $img['src'];
				}
			}
		}
	}

	// build the attachment
	$permalink = apply_filters('sfc_publish_permalink',get_permalink($post->ID), $post->ID);
	$attachment['name'] = '<fb:intl>'.$post->post_title.'</fb:intl>';
	$attachment['href'] = $permalink;
	$attachment['description'] = sfc_publish_make_excerpt($post->post_content);
	//$attachment['comments_xid'] = urlencode($permalink);

	// image attachments (up to 5, as that's all FB allows)
	$count=0;
	foreach ($images as $image) {
		$attachment['media'][$count]['type'] = 'image';
		$attachment['media'][$count]['src'] = $image;
		$attachment['media'][$count]['href'] = $permalink;
		$count++; if ($count==5) break;
	}

	// Share link
	$action_links[0]['text'] = 'Share';
	$action_links[0]['href'] = 'http://www.facebook.com/share.php?u='.urlencode($permalink);

	// publish to page
	if ($options['autopublish_app'] && !get_post_meta($id,'_fb_post_id_app',true)
		&& $options['fanpage'] // TODO eliminate this when Facebook fixes the bug
		) {

		if ($options['fanpage']) $who = $options['fanpage'];
		else $who = $options['appid'];

		// check to see if we can send to FB at all
		$result = $fb->api_client->users_hasAppPermission('publish_stream', $who);
		if (!$result) break;

		$fb_post_id = $fb->api_client->stream_publish(null, json_encode($attachment), json_encode($action_links), null, $who);

		if ($fb_post_id) {
			// update the post id so as to prevent automatically posting it twice
			update_post_meta($id,'_fb_post_id_app',$fb_post_id);
		}
	}

	// publish to profile
	if ($options['autopublish_profile'] && !get_post_meta($id,'_fb_post_id_profile',true)) {

		// check to see if we can send to FB at all
		$result = $fb->api_client->users_hasAppPermission('publish_stream');
		if (!$result) break;

		$fb_post_prof_id = $fb->api_client->stream_publish(null, json_encode($attachment), json_encode($action_links));

		if ($fb_post_prof_id) {
			// update the post id so as to prevent automatically posting it twice
			update_post_meta($id,'_fb_post_id_profile',$fb_post_prof_id);
		}
	}

	// switch users back, just in case
	if ($tempuser) {
		$fb->set_user($tempuser, $tempkey);
	}
}

add_filter('sfc_validate_options','sfc_publish_validate_options');
function sfc_publish_validate_options($input) {
	$options = get_option('sfc_options');

	if ($input['autopublish_app'] != 1) $input['autopublish_app'] = 0;
	if ($input['autopublish_profile'] != 1) $input['autopublish_profile'] = 0;

	// find the infinite session key and save it if it's there
	if ($_COOKIE[$options['api_key'].'_expires'] == 0) {
		// save the user and session key
		$input['user'] = $_COOKIE[$options['api_key'].'_user'];
		$input['session_key'] = $_COOKIE[$options['api_key'].'_session_key'];
	}

	return $input;
}

// finds a item from an array in a string
if (!function_exists('straipos')) :
function straipos($haystack,$array,$offset=0)
{
   $occ = array();
   for ($i = 0;$i<sizeof($array);$i++)
   {
       $pos = strpos($haystack,$array[$i],$offset);
       if (is_bool($pos)) continue;
       $occ[$pos] = $i;
   }
   if (sizeof($occ)<1) return false;
   ksort($occ);
   reset($occ);
   list($key,$value) = each($occ);
   return array($key,$value);
}
endif;