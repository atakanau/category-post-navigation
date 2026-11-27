<?php
declare(strict_types=1);
/**
 * Deletes plugin settings on uninstall; touches nothing else.
 */
if(!defined('WP_UNINSTALL_PLUGIN')){exit;}

delete_option('catpnav_settings');
