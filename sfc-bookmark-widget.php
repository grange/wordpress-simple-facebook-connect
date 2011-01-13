<?php
/*
Plugin Name: SFC - Bookmark Widget
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Shows a "Add Bookmark" button in the sidebar which allows users to add the site to their bookmark bar in Facebook.
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
function sfc_bookmark_widget_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.4', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_bookmark_widget_activation_check');

function get_sfc_bookmark($args='') {
	return '<fb:bookmark type="off-facebook"></fb:bookmark>';
}

function sfc_bookmark($args='') {
	echo get_sfc_bookmark();
}

function sfc_bookmark_shortcode($atts) {
	return get_sfc_bookmark();
}
add_shortcode('fb-bookmark', 'sfc_bookmark_shortcode');

class SFC_Bookmark_Widget extends WP_Widget {
	function SFC_Bookmark_Widget() {
		$widget_ops = array('classname' => 'widget_sfc-bookmark', 'description' => __('Facebook Bookmark', 'sfc'));
		$this->WP_Widget('sfc-bookmark', __('Facebook Bookmark (SFC)', 'sfc'), $widget_ops);
	}

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<?php sfc_bookmark(); ?>
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
add_action('widgets_init', create_function('', 'return register_widget("SFC_Bookmark_Widget");'));
