<?php
/*
Plugin Name: SFC - Connect Widget
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Shows a "Connect with Facebook" button in the sidebar which will log you into the site (should be used with SFC-Login plugin).
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
function sfc_connect_widget_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.4', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_connect_widget_activation_check');

// Shortcode for putting it into pages or posts directly
// profile id is required. Won't work without it.
function sfc_connect_shortcode() {
	$login ='<fb:login-button perms="email" v="2" size="medium" ';
	
	if (function_exists('sfc_login_activation_check')) {
		$login .= 'onlogin="window.location=\''. wp_login_url() . "?redirect_to='+document.URL;\"";
	}
	
	$login .= '><fb:intl>'.__('Connect with Facebook', 'sfc').'</fb:intl></fb:login-button>';
	return $login;
}
add_shortcode('fb-connect', 'sfc_connect_shortcode');

class SFC_Connect_Widget extends WP_Widget {
	function SFC_Connect_Widget() {
		$widget_ops = array('classname' => 'widget_sfc-connect', 'description' => __('Facebook Connect', 'sfc'));
		$this->WP_Widget('sfc-connect', __('Facebook Connect (SFC)', 'sfc'), $widget_ops);
	}

	function widget($args, $instance) {
		$options = get_option('sfc_options');
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<?php echo sfc_connect_shortcode(); ?>
		<?php echo $after_widget; ?>
		<?php
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '') );
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = strip_tags($instance['title']);
		?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</label></p>
		<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("SFC_Connect_Widget");'));
