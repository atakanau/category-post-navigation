<?php
declare(strict_types=1);
/**
 * Plugin Name: Category Post Navigation
 * Plugin URI:  https://en.programs.com.tr/2026/11/wordpress-category-post-navigation-tool.html
 * Description: Numeric, prev/next and mixed navigation between published posts of the current post's category on single post views.
 * Author:      Atakan Au
 * Author URI:  https://en.programs.com.tr
 * Version:     0.1.0
 * Requires at least: 6.8
 * Requires PHP: 8.1
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Donate link: https://buymeacoffee.com/atakanau
 * Text Domain: category-post-navigation
 * Domain Path: /languages
 */
if(!defined('ABSPATH')){exit;}

define('CATPNAV_VERSION','0.1.0');
define('CATPNAV_FILE',__FILE__);
// Windowing threshold for the "all" scope; fixed by design, not a setting.
define('CATPNAV_ELLIPSIS_THRESHOLD',10);

require_once __DIR__.'/includes/catpnav-settings.php';
require_once __DIR__.'/includes/catpnav-navigation.php';
require_once __DIR__.'/includes/catpnav-output.php';
require_once __DIR__.'/blocks/catpnav-navigation/class-catpnav-block.php';

if(is_admin()){
	require_once __DIR__.'/admin/catpnav-admin.php';
	add_action('admin_menu','catpnav_register_settings_screen');
	add_action('admin_init','catpnav_register_setting');
	add_action('admin_enqueue_scripts','catpnav_enqueue_admin_assets');
	add_filter('plugin_action_links_'.plugin_basename(__FILE__),'catpnav_action_links');
	add_filter('plugin_row_meta','catpnav_row_meta',10,2);
}

register_activation_hook(__FILE__,'catpnav_activate');
add_shortcode('category_post_navigation','catpnav_shortcode');
add_filter('the_content','catpnav_auto_display',20);
// Gutenberg block; also makes the block usable in block theme templates (FSE).
add_action('init',['Catpnav_Block','register'],20);
