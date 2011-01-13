<?php
/*
Plugin Name: Simple Facebook Connect - Base
Plugin URI: http://ottopress.com/wordpress-plugins/simple-facebook-connect/
Description: Makes it easy for your site to use Facebook Connect, in a wholly modular way.
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
function sfc_version() {
	return '0.22';
}

// fast check for xd_receiver request on plugin load.
if (array_key_exists('xd_receiver', $_GET) && $_GET['xd_receiver'] == 1) { ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>xd</title></head>
<body>
<?php 
if ($_SERVER['HTTPS'] == 'on')
	echo '<script src="https://ssl.connect.facebook.com/js/api_lib/v0.4/XdCommReceiver.js" type="text/javascript"></script>';
else
	echo '<script src="http://static.ak.facebook.com/js/api_lib/v0.4/XdCommReceiver.js" type="text/javascript"></script>';
?>
</body>
</html>
<?php
exit; // stop normal WordPress execution
}

// require PHP 5
function sfc_activation_check(){
	if (version_compare(PHP_VERSION, '5', '<')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate ourself
		wp_die(printf(__('Sorry, Simple Facebook Connect requires PHP 5 or higher. Your PHP version is "%s". Ask your web hosting service how to enable PHP 5 as the default on your servers.', 'sfc'), PHP_VERSION));
	}
}
register_activation_hook(__FILE__, 'sfc_activation_check');

// this will prevent the PHP 5 code from causing parsing errors on PHP 4 systems
if (!version_compare(PHP_VERSION, '5', '<')) {
	include 'sfc-base.php';
}
