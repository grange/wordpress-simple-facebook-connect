<?php
/*
Plugin Name: SFC - Live Stream Widget
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Create a Live Stream in your site's sidebar, allowing users to chat in real-time.
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
function sfc_live_stream_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.1', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_live_stream_activation_check');

function get_sfc_livestream($args='') {
	$args = wp_parse_args($args, array(
		'width' => '200',
		'height' => '400',
		));
	extract($args);

	return '<fb:live-stream width="'.$width.'" height="'.$height.'"></fb:live-stream>';
}

function sfc_livestream($args='') {
	echo get_sfc_livestream($args);
}

function sfc_live_stream_shortcode($atts) {
	$args = shortcode_atts(array(
		'width' => '200',
		'height' => '400',
	), $atts);

	return get_sfc_livestream($args);
}
add_shortcode('fb-livestream', 'sfc_live_stream_shortcode');

class SFC_Live_Stream_Widget extends WP_Widget {
	function SFC_Live_Stream_Widget() {
		$widget_ops = array('classname' => 'widget_sfc-livestream', 'description' => __('Facebook Live Stream', 'sfc'));
		$this->WP_Widget('sfc-livestream', __('Facebook Live Stream (SFC)', 'sfc'), $widget_ops);
	}

	function widget($args, $instance) {
		$options = get_option('sfc_options');
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		$width = intval($instance['width']);
		$height = intval($instance['height']);
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<?php sfc_livestream($instance); ?>
		<?php echo $after_widget; ?>
		<?php
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '', 'width'=>200, 'height'=>400 ) );
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['width'] = intval($new_instance['width']);
		if ($instance['width'] < 200) $instance['width'] = 200;
		$instance['height'] = intval($new_instance['height']);
		if ($instance['height'] < 400) $instance['height'] = 400;
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'width'=>200, 'height'=>400 ) );
		$title = strip_tags($instance['title']);
		$connections = intval($instance['connections']);
		$width = intval($instance['width']);
		$height = intval($instance['height']);
		$stream = $instance['stream'] ? true : false;
		?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width of the widget in pixels (minimum 200):', 'sfc'); ?>
<input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $width; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height of the widget in pixels (minimum 400):', 'sfc'); ?>
<input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo $height; ?>" />
</label></p>

		<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("SFC_Live_Stream_Widget");'));
