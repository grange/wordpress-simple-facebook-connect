<?php
/*
Plugin Name: SFC - User Status Widget
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Display your FB User Status in the sidebar, simply.
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
function sfc_user_status_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.1', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_user_status_activation_check');

function get_sfc_userstatus($args='') {
	$args = wp_parse_args($args, array(
		'profileid' => '',
		'linked' => 'true'
		));
	extract($args);
	
	return "<fb:user-status uid='{$profileid}' linked='{$linked}'></fb:user-status>";
}

function sfc_userstatus($args='') {
	echo get_sfc_userstatus($args);
}

function sfc_userstatus_shortcode($atts) {
	$args = shortcode_atts(array(
		'profileid' => '',
		'linked' => 'true'
	), $atts);

	return get_sfc_userstatus($args);
}
add_shortcode('fb-userstatus', 'sfc_userstatus_shortcode');

class SFC_User_Status_Widget extends WP_Widget {
	function SFC_User_Status_Widget() {
		$widget_ops = array('classname' => 'widget_sfc-status', 'description' => __('Facebook User Status (needs user profile number)', 'sfc') );
		$this->WP_Widget('sfc-userstatus', __('Facebook Status (SFC)', 'sfc'), $widget_ops);
	}

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		$profileid = $instance['profileid'];
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<?php sfc_userstatus($instance); ?>
		<?php echo $after_widget; ?>
		<?php
	}

	function update($new_instance, $old_instance) {
		return $new_instance;
	}

	function form($instance) {
		$title = esc_attr($instance['title']);
		$profileid = esc_attr($instance['profileid']);
		?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('profileid'); ?>"><?php _e('Facebook User Profile Number:', 'sfc'); ?>
<input class="widefat" id="<?php echo $this->get_field_id('profileid'); ?>" name="<?php echo $this->get_field_name('profileid'); ?>" type="text" value="<?php echo $profileid; ?>" />
</label></p>
<?php 
		$fbuid = sfc_get_user();
		if ($fbuid) { ?>
<p><?php printf(__('Your User Profile Number is %s.', 'sfc'), $fbuid); ?></p>
		<?php
		}
	}
}
add_action('widgets_init', create_function('', 'return register_widget("SFC_User_Status_Widget");'));
