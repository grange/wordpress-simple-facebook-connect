<?php
/*
Plugin Name: SFC - Upcoming Events Widget
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-upcoming/
Description: Shows a list of upcoming events (for a user, group, fan page, or application) in the sidebar.
Author: Otto
Version: 0.25
Author URI: http://ottodestruct.com
License: GPL2

    Copyright 2010  Samuel Wood  (email : otto@ottodestruct.com)

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
function sfc_upcoming_widget_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.4', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_upcoming_widget_activation_check');

// produce a list of upcoming events for a given facebook user
function sfc_upcoming_events($uid) {
	if (!$uid) return;

	$options = get_option('sfc_options');
	
	// load facebook platform
	include_once 'facebook-platform/facebook.php';
	$fb=new Facebook($options['api_key'], $options['app_secret']);

	try {
		$events = $fb->api_client->events_get($uid, null, time());
	} catch (Exception $e) { }

	if (!$events) return;
	
	$events_sorted = sfc_upcoming_sort($events, 'start_time');
	
	foreach ($events_sorted as $event) {
		do_action('sfc_upcoming_event',$event);
	}
}

function sfc_upcoming_event_output($event) {
	echo date_i18n('F jS', $event["start_time"]);
?>
 - <fb:eventlink eid="<?php echo $event["eid"]; ?>"></fb:eventlink>
<br />
<?php 
}
add_action('sfc_upcoming_event','sfc_upcoming_event_output');

function sfc_upcoming_sort($array, $column) {
	$s = array();
	foreach($array as $row)	$s[] = $row[$column];
	array_multisort($s, SORT_ASC, SORT_NUMERIC, $array);
	return $array;
}

class SFC_Upcoming_Widget extends WP_Widget {
	function SFC_Upcoming_Widget() {
		$widget_ops = array('classname' => 'widget_sfc-upcoming', 'description' => __('Facebook Upcoming Events', 'sfc'));
		$this->WP_Widget('sfc-upcoming', __('Facebook Upcoming Events (SFC)', 'sfc'), $widget_ops);
	}

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		$appid = $options['appid'];
		$id = $instance['id'];
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<?php sfc_upcoming_events($id); ?>
		<?php echo $after_widget; ?>
		<?php
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '') );
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['id'] = strip_tags($new_instance['id']);
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = strip_tags($instance['title']);
		$id = strip_tags($instance['id']);
		?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('ID'); ?>"><?php _e('User ID:'); ?>  
<input class="widefat" id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" type="text" value="<?php echo $id; ?>" />
</label></p>
<p>(<?php _e('The User ID can also be a Group ID, a Fan Page ID, or an Application ID.', 'sfc'); ?>)</p>
		<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("SFC_Upcoming_Widget");'));
