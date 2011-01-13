<?php
/*
Plugin Name: SFC - Chicklet
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Creates a chicklet for showing fan count of your app/page on FB.
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

define('SFC_FANCOUNT_CACHE',60*60); // 1 hour caching

// checks for sfc on activation
function sfc_chicklet_activation_check(){
	if (function_exists('sfc_version')) {
		if (version_compare(sfc_version(), '0.8', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die(__('The base SFC plugin must be activated before this plugin will run.', 'sfc'));
}
register_activation_hook(__FILE__, 'sfc_chicklet_activation_check');

function sfc_chicklet($id = 0) {
	$options = get_option('sfc_options');

	if ($id == 0) {	
		if ($options['fanpage']) $id = $options['fanpage'];
		else $id = $options['appid'];
	}
	
	$sfc_chicklet_fancount = get_transient('sfc_chicklet_fancount');
	
	if (!isset($sfc_chicklet_fancount[$id])) {
		include_once 'facebook-platform/facebook.php';
		$fb=new Facebook($options['api_key'], $options['app_secret']);
		$result = $fb->api_client->fql_query("SELECT fan_count, page_url FROM page WHERE page_id={$id}");
		if ($result) {
			$sfc_chicklet_fancount[$id] = $result[0];
			set_transient('sfc_chicklet_fancount',$sfc_chicklet_fancount,SFC_FANCOUNT_CACHE); 
		}
	}
	
	if ($sfc_chicklet_fancount) {
		$fancount = $sfc_chicklet_fancount[$id]['fan_count'];
		$pageurl = $sfc_chicklet_fancount[$id]['page_url'];	
	}
	
	global $sfc_chicklet_no_style;
	if (!$sfc_chicklet_no_style) {
?>
<style>
.fanBoxChicklet {
	width:88px;
	height:17px;
	overflow:auto;
	background-color:#94bfbf;
	border-top: 1px solid #cefdfd;
	border-left: 1px solid #cefdfd;
	border-right: 1px solid #5f8586;
	border-bottom: 1px solid #5f8586; 
	font: 11px/normal monospace, courier new, sans-serif;
	color:#59564f;
	margin: 0;
	padding: 0;
	text-align:right;
}

.fanBoxChicklet .quantity {
	width:auto;
	height:13px;
	min-width:40px;
	background-color:#cefdfd;
	border-top: 1px solid #8a8a8a;
	border-left: 1px solid #8a8a8a;
	border-right: 1px solid #fefffe;
	border-bottom: 1px solid #fefffe;
	padding: 2px;
	float: left;
	text-align: center;
	overflow: hidden;
	margin:1px 5px 0 0;
	padding:0;
}

.fanBoxChicklet .readerCaption {
	width:auto;
	float: left;
	text-align: center;
	vertical-align: middle;
	margin: 2px 0 0 0;
	padding: 0;
}

.fanBoxChicklet .feedCountLink {
	color:#59564f;
	text-decoration:none;
	margin:0;
	padding:0;
}

.fanBoxBy {
	width:88px;
	height:9px;
	font: 9px/normal monospace, courier new, sans-serif;
	color:#59564f;
}
</style>
<?php } ?>
<div class="fanBoxChicklet fanBoxChicklet-<?php echo $id; ?>">
<p class="quantity"><?php echo $fancount; ?></p>
<p class="readerCaption"><a href="<?php echo $pageurl; ?>" class="feedCountLink" target="_blank"><?php _e('Fans', 'sfc'); ?></a></p>
</div>
<div class="fanBoxBy"><?php _e('ON FACEBOOK', 'sfc'); ?></div>  
<?php
}


class SFC_Chicklet_Widget extends WP_Widget {
	function SFC_Chicklet_Widget() {
		$widget_ops = array('classname' => 'widget_sfc-chicklet', 'description' => __('Facebook Chicklet', 'sfc'));
		$this->WP_Widget('sfc-chicklet', __('Facebook Chicklet', 'sfc'), $widget_ops);
	}

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<?php sfc_chicklet(); ?>
		<?php echo $after_widget; ?>
		<?php
	}

	function update($new_instance, $old_instance) {
		return $new_instance;
	}

	function form($instance) {
		$title = esc_attr($instance['title']);
		?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</label></p>
		<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("SFC_Chicklet_Widget");'));

