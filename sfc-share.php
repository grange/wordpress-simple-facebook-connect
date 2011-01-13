<?php
/*
Plugin Name: SFC - Share Button
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Simple share button for use with SFC. Adds shortcodes and function calls.
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

/*
	There's two ways to do this share thing:
	1. Using XFBML
	 - Only works on FBConnect enabled sites
	 - Only shows count to users that are currently logged into Facebook (not into this site, just FB)
	 - Style as you like, it doesn't get special rules
	 - Way faster, as no extra code is needed to load (FB Connect is already loading XFBML)
	 - The "share" popup can be in a lightbox frame in the page (looks nice)
	2. Using special FB javascript
	 - Works anywhere
	 - Shows count to everybody
	 - Has special styling built in (the thing is floated by default, getting text wrap around it)
	 - Slower, requires a few extra JS files to load up
	 - Always opens a separate window for sharing (no pretty framing in the page)
	 
	 Use whichever you please by changing this define below.
	 True = XFBML (method 1)
	 False = JS (method 2)
*/
if (!defined('SFC_SHARE_USE_XFBML')) 
	define('SFC_SHARE_USE_XFBML',true);

// checks for sfc on activation
function sfc_share_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.1', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_share_activation_check');

if (!SFC_SHARE_USE_XFBML) {
	// add the script to do this the non-connect way
	add_action('wp_enqueue_scripts','sfc_share_enqueue');
	function sfc_share_enqueue() {
		wp_enqueue_script( 'fb-share', 'http://static.ak.fbcdn.net/connect.php/js/FB.Share', array(), '1', true);
	}
}

/**
 * Simple share button
 *
 * See http://wiki.developers.facebook.com/index.php/Fb:share-button for more info
 *
 * @param string $type box_count, button_count, button, icon, or icon_link
 * @param int $post_id An optional post ID.
 */
function get_sfc_share_button($type = '', $id = 0) {
	if (empty($type)) {
		$options = get_option('sfc_options');
		$type = $options['share_type'];
	}
		
	if (SFC_SHARE_USE_XFBML) {
		return '<fb:share-button href="'.get_permalink($id).'" type="'.$type.'"></fb:share-button>';
	} else {
		return "<a name='fb_share' type='{$type}' share_url='".get_permalink($id)."'></a>";
	}
}

function sfc_share_button($type = '', $id = 0) {
	echo get_sfc_share_button($type,$id);
}

/**
 * Simple share button as a shortcode
 *
 * See http://wiki.developers.facebook.com/index.php/Fb:share-button for more info
 *
 * Example use: [fb-share type="button"] or [fb-share id="123"]
 */
function sfcshare_shortcode($atts) {
	$options = get_option('sfc_options');
	extract(shortcode_atts(array(
		'type' => $options['share_type'],
		'id' => 0,
	), $atts));

	return get_sfc_share_button($type,$id);
}

add_shortcode('fb-share', 'sfcshare_shortcode');
add_shortcode('fbshare', 'sfcshare_shortcode'); // FB Foundations Share uses this shortcode. This is compatible with it.

function sfc_share_button_automatic($content) {
	$options = get_option('sfc_options');
	$button = get_sfc_share_button();
	switch ($options['share_position']) {
		case "before":
			$content = $button . $content;
			break;
		case "after":
			$content = $content . $button;
			break;
		case "both":
			$content = $button . $content . $button;
			break;
		case "manual":
		default:
			break;
	}
	return $content;
}
add_filter('the_content', 'sfc_share_button_automatic', 30);

// add the admin sections to the sfc page
add_action('admin_init', 'sfc_share_admin_init');
function sfc_share_admin_init() {
	add_settings_section('sfc_share', __('Share Button Settings', 'sfc'), 'sfc_share_section_callback', 'sfc');
	add_settings_field('sfc_share_position', __('Share Button Position', 'sfc'), 'sfc_share_position', 'sfc', 'sfc_share');
	add_settings_field('sfc_share_type', __('Share Button Type', 'sfc'), 'sfc_share_type', 'sfc', 'sfc_share');
}

function sfc_share_section_callback() {
	echo '<p>'.__('Choose where you want the share button to add the button in your content.', 'sfc').'</p>';
}

function sfc_share_position() {
	$options = get_option('sfc_options');
	if (!$options['share_position']) $options['share_position'] = 'manual';
	?>
	<p><label><input type="radio" name="sfc_options[share_position]" value="before" <?php checked('before', $options['share_position']); ?> /> <?php _e('Before the content of your post', 'sfc'); ?></label></p>
	<p><label><input type="radio" name="sfc_options[share_position]" value="after" <?php checked('after', $options['share_position']); ?> /> <?php _e('After the content of your post', 'sfc'); ?></label></p>
	<p><label><input type="radio" name="sfc_options[share_position]" value="both" <?php checked('both', $options['share_position']); ?> /> <?php _e('Before AND After the content of your post', 'sfc'); ?></label></p>
	<p><label><input type="radio" name="sfc_options[share_position]" value="manual" <?php checked('manual', $options['share_position']); ?> /> <?php _e('Manually add the button to your theme or posts (use the sfc_share_button function in your theme, or the [fb-share] shortcode in your posts)', 'sfc'); ?></label></p>
<?php 
}

function sfc_share_type() {
	$options = get_option('sfc_options');
	if (!$options['share_type']) $options['share_type'] = 'box_count';
	?>
	<table><tr><td style="width:140px;">
	<div class="sfc_share_type_selector">
	<select name="sfc_options[share_type]" id="sfc_select_share_type">
	<option value="icon" <?php selected('icon', $options['share_type']); ?>><?php _e('Icon', 'sfc'); ?></option>
	<option value="icon_link" <?php selected('icon_link', $options['share_type']); ?>><?php _e('Icon and Link', 'sfc'); ?></option>
	<option value="button" <?php selected('button', $options['share_type']); ?>><?php _e('Button', 'sfc'); ?></option>
	<option value="button_count" <?php selected('button_count', $options['share_type']); ?>><?php _e('Button Count', 'sfc'); ?></option>
	<option value="box_count" <?php selected('box_count', $options['share_type']); ?>><?php _e('Box Count', 'sfc'); ?></option>
	</select>
	</td><td>
	<div id="sfc_share_type_preview" style="float:right;"><?php _e('Preview:', 'sfc'); ?>
	<img id="sfc_share_type_preview_image" src="<?php echo plugins_url('/images/'.$options['share_type'].'.png', __FILE__); ?>" />
	</div>
	</td></tr>
	<tr><td colspan="2"><p><?php _e('Note: Counts will not show up until they reach at least three. This is a Facebook limitation on the Share button.', 'sfc'); ?></p></td></tr></table>
	</div>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#sfc_select_share_type").change(function() {
			var selected = jQuery("#sfc_select_share_type").val();
			jQuery("#sfc_share_type_preview_image").attr('src',"<?php echo plugins_url('/images/', __FILE__); ?>"+selected+".png");
		});
	});
	</script>
<?php 
}

add_filter('sfc_validate_options','sfc_share_validate_options');
function sfc_share_validate_options($input) {
	if (!in_array($input['share_position'], array('before', 'after', 'both', 'manual'))) {
			$input['share_position'] = 'manual';
	}
	if (!in_array($input['share_type'], array('icon', 'icon_link', 'button', 'button_count', 'box_count'))) {
			$input['share_type'] = 'box_count';
	}
	return $input;
}

add_action('wp_head','sfc_share_meta');

function sfc_share_meta() {
	$excerpt = '';
	if (is_singular()) {
		the_post();
		rewind_posts(); 
		$excerpt = strip_tags(get_the_excerpt());
		$content = get_the_content();
		$content = apply_filters('the_content', $content);
?>
<meta name="title" content="<?php if ( is_singular() ) { single_post_title('', true); } else { bloginfo('name'); echo " - "; bloginfo('description'); } ?>" />
<meta name="description" content="<?php if ( is_singular() ) { echo str_replace(array("\r\n","\r","\n"),' ',$excerpt); } else { bloginfo('name'); echo " - "; bloginfo('description'); } ?>" />
<meta name="medium" content="blog" />
<?php
		// look for image to add with image_src (simple, just add first image)
		
		// get the post thumbnail, put it first in the image list
		if ( current_theme_supports('post-thumbnails') && has_post_thumbnail(get_the_ID()) ) {
			$thumbid = get_post_thumbnail_id(get_the_ID());
			$att = wp_get_attachment_image_src($thumbid, 'full');
			if (!empty($att[0])) {
				?><link rel="image_src" href="<?php echo $att[0]; ?>" /><?php
			}
		} else if ( preg_match('/<img (.+?)>/', $content, $matches) ) {
			foreach ( wp_kses_hair($matches[1], array('http')) as $attr) 
				$img[$attr['name']] = $attr['value'];
			if ( isset($img['src']) ) {
				if (!isset($img['class']) || 
					(isset($img['class']) && false === straipos($img['class'], apply_filters('sfc_img_exclude',array('wp-smiley'))))
					) { // ignore smilies
?><link rel="image_src" href="<?php echo $img['src'] ?>" />
<?php
				}
			}
		}		
		
		// look for an embed to add with video_src (simple, just add first embed)
		if ( preg_match('/<embed (.+?)>/', $content, $matches) ) {
			foreach ( wp_kses_hair($matches[1], array('http')) as $attr) 
				$embed[$attr['name']] = $attr['value'];
			if ( isset($embed['src']) ) {
?><link rel="video_src" href="<?php echo $embed['src'] ?>" />
<?php
			}
			if ( isset($embed['height']) ) {
?><link rel="video_height" href="<?php echo $embed['height'] ?>" />
<?php
			}
			if ( isset($embed['width']) ) {
?><link rel="video_width" href="<?php echo $embed['width'] ?>" />
<?php
			}
			if ( isset($embed['type']) ) {
?><link rel="video_type" href="<?php echo $embed['type'] ?>" />
<?php
			}
		}
	}
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