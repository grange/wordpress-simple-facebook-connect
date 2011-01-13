<?php
/*
Plugin Name: SFC - Activity Feed Widget
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Create an Activity Feed for your sites sidebar.
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
function sfc_activity_feed_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.18', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_activity_feed_activation_check');

function get_sfc_activity_feed($args) {
	$args = wp_parse_args($args, array(
		'header'=>'true',
		'site'=>'',
		'bordercolor'=>'000000',
		'width'=>'260',
		'height'=>'400',
		'font'=>'lucida+grande',
		'colorscheme'=>'light'));
	extract($args);
	
	if (empty($site)) $site = get_bloginfo('url');
	
	return "<p class='fb-activity-feed'><iframe src='http://www.facebook.com/plugins/activity.php?site={$site}&amp;width={$width}&amp;height={$height}&amp;header={$header}&amp;colorscheme={$colorscheme}&amp;font={$font}&amp;border_color={$bordercolor}' scrolling='no' frameborder='0' allowTransparency='true' style='border:none; overflow:hidden; width:{$width}px; height:{$height}px'></iframe></p>";
}
	
function sfc_activity_feed($args='') {
	echo get_sfc_activity_feed($args);
}

function sfc_activity_feed_shortcode($atts) {
	$args = shortcode_atts(array(
		'header'=>'true',
		'site'=>'',
		'bordercolor'=>'000000',
		'width'=>'260',
		'height'=>'400',
		'font'=>'lucida+grande',
		'colorscheme'=>'light'), $atts);

	return get_sfc_activity_feed($args);
}
add_shortcode('fb-activity', 'sfc_activity_feed_shortcode');

class SFC_Activity_Feed_Widget extends WP_Widget {
	function SFC_Activity_Feed_Widget() {
		$widget_ops = array('classname' => 'widget_sfc-activity-feed', 'description' => __('Facebook Activity Feed', 'sfc'));
		$this->WP_Widget('sfc-activity', __('Facebook Activity Feed (SFC)', 'sfc'), $widget_ops);
	}

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title;
		sfc_activity_feed($instance);
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '', 'width'=>260, 'height'=>400, 'bordercolor'=>'000000', 'font'=>'lucida+grande', 'colorscheme'=>'light') );
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['width'] = intval($new_instance['width']);
		$instance['height'] = intval($new_instance['height']);
		$instance['bordercolor'] = strip_tags($new_instance['bordercolor']);
		$instance['colorscheme'] = strip_tags($new_instance['colorscheme']);
		$instance['font'] = strip_tags($new_instance['font']);
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'width'=>260, 'height'=>400, 'bordercolor'=>'000000', 'font'=>'lucida+grande', 'colorscheme'=>'light' ) );
		$title = strip_tags($instance['title']);
		$width = intval($instance['width']);
		$height = intval($instance['height']);
		$bordercolor = strip_tags($instance['bordercolor']);
		if (empty($bordercolor)) $bordercolor = '000000';
		$colorscheme = strip_tags($instance['colorscheme']);
		$font = strip_tags($instance['font']);
		?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width of the widget in pixels:', 'sfc'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $width; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height of the widget in pixels:', 'sfc'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo $height; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('bordercolor'); ?>"><?php _e('Border color:', 'sfc'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('bordercolor'); ?>" name="<?php echo $this->get_field_name('bordercolor'); ?>" type="text" value="<?php echo $bordercolor; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('colorscheme'); ?>"><?php _e('Color scheme:', 'sfc'); ?> 
<select name="<?php echo $this->get_field_name('colorscheme'); ?>" id="<?php echo $this->get_field_id('colorscheme'); ?>">
<option value="light" <?php selected('light', $colorscheme); ?>><?php _e('light', 'sfc'); ?></option>
<option value="dark" <?php selected('dark', $colorscheme); ?>><?php _e('dark', 'sfc'); ?></option>
</select>
</label></p>
<p><label for="<?php echo $this->get_field_id('font'); ?>"><?php _e('Font:', 'sfc'); ?>
<select name="<?php echo $this->get_field_name('font'); ?>" id="<?php echo $this->get_field_id('font'); ?>">
<option value="arial" <?php selected('arial', $font); ?>>arial</option>
<option value="lucide+grande" <?php selected('lucide+grande', $font); ?>>lucide grande</option>
<option value="segoe+ui" <?php selected('segoe+ui', $font); ?>>segoe ui</option>
<option value="tahoma" <?php selected('tahoma', $font); ?>>tahoma</option>
<option value="trebuchet+ms" <?php selected('trebuchet+ms', $font); ?>>trebuchet ms</option>
<option value="verdana" <?php selected('verdana', $font); ?>>verdana</option>
</select>
</label></p>
		<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("SFC_Activity_Feed_Widget");'));

