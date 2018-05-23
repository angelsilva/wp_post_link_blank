<?php

/**
* Plugin Name: PostLinkBlank
* Plugin URI: http://www.example.com
* Description: Add Target Blank to Link Post, except internal links
* Version: 1.0.0
* Author: Example
* Author URI: http://www.example.com
* License: GPL2
*/

add_filter('the_content','post_link_blank_filter');

function post_link_blank_filter($content) {
	
	$content = preg_replace_callback("/<a(.*?)>/", "_post_link_blank_filter", $content);

	return $content;
}

function _post_link_blank_filter($links){
	$home_url = esc_url( home_url() );
	foreach ($links as $link) {
		if(strstr($link,'<a')===FALSE){
			continue;
		}
		if(strstr($link,$home_url)!==FALSE){
			return $link;
		}
		if(strstr($link,'target="_blank"')===FALSE){
			return str_replace(">", ' target="_blank">', $link);
		}
		return $link;
	}
}

add_action( 'init', 'activate_au' );
function activate_au()
{
	require_once ( 'wp_autoupdate.php' );
	$plugin_current_version = '1.0.0';
	$plugin_remote_path = 'https://wpdev.yoemprendo.online/update.php?plugin=post_link_blank';
	$plugin_slug = plugin_basename( __FILE__ );
	$license_user = '';
	$license_key = '';
	new WP_AutoUpdate ( $plugin_current_version, $plugin_remote_path, $plugin_slug, $license_user, $license_key );
}