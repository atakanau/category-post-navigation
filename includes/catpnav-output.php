<?php
declare(strict_types=1);
/**
 * Usage paths: shortcode, template tags, automatic display.
 */
if(!defined('ABSPATH')){exit;}

/**
 * Renders the navigation via shortcode; unknown attributes are ignored.
 * @param array|string $raw Raw shortcode attributes.
 * @return string Navigation HTML or ''.
 */
function catpnav_shortcode($raw):string{
	$atts=shortcode_atts([
		'category'		=>'',
		'scope'			=>'',
		'around'		=>'',
		'orderby'		=>'',
		'order'			=>'',
		'mode'			=>'',
		'layout'		=>'',
		'numbering'		=>'',
		'show_title'	=>'',
		'ellipsis'		=>'',
		'multi_cat'		=>'',
		'prev'			=>'',
		'next'			=>'',
	],$raw,'category_post_navigation');
	$html=catpnav_get_navigation(array_filter($atts,static function($v):bool{
		return ''!==trim((string)$v);
	}));
	if(''!==$html){
		// Shortcode output wins over automatic display on the same request.
		$GLOBALS['catpnav_rendered']=true;
	}
	return $html;
}

/**
 * Returns the navigation; arguments override global settings.
 * @param array $args Same keys as the shortcode attributes plus category.
 * @return string Navigation HTML or ''.
 */
function catpnav_get_navigation(array $args=[]):string{
	// Friendly aliases: prev/next map to the prev_label/next_label settings.
	if(array_key_exists('prev',$args)){
		$args['prev_label']=$args['prev'];
		unset($args['prev']);
	}
	if(array_key_exists('next',$args)){
		$args['next_label']=$args['next'];
		unset($args['next']);
	}
	$cat=0;
	if(isset($args['category'])){
		$cat=catpnav_category_arg_id($args['category']);
		unset($args['category']);
	}
	return catpnav_render_navigation(catpnav_resolve_config($args),0,$cat);
}

/**
 * Prints the navigation; arguments override global settings.
 * @param array $args Same keys as the shortcode attributes plus category.
 * @return void
 */
function catpnav_the_navigation(array $args=[]):void{
	echo catpnav_get_navigation($args);// phpcs:ignore WordPress.Security.EscapeOutput -- markup is escaped at build time.
}

/**
 * Resolves the category argument (slug or term ID) to a term ID.
 * @param mixed $val Raw category argument.
 * @return int Term ID or 0.
 */
function catpnav_category_arg_id($val):int{
	$val=trim((string)$val);
	if(''===$val){
		return 0;
	}
	if(ctype_digit($val)){
		$term=get_term((int)$val,'category');
		return ($term instanceof WP_Term)?(int)$term->term_id:0;
	}
	$term=get_term_by('slug',$val,'category');
	return ($term instanceof WP_Term)?(int)$term->term_id:0;
}

/**
 * Prepends/appends the navigation to single post content.
 * @param string $content Post content.
 * @return string Content with optional navigation.
 */
function catpnav_auto_display(string $content):string{
	if(!catpnav_auto_display_enabled()){
		return $content;
	}
	$html=catpnav_get_navigation();
	if(''===$html){
		return $content;
	}
	$auto=catpnav_get_settings()['auto'];
	$before=('before'===$auto||'both'===$auto)?$html:'';
	$after=('after'===$auto||'both'===$auto)?$html:'';
	return $before.$content.$after;
}

/**
 * Checks the request conditions for automatic display.
 * @return bool True when the navigation may be attached.
 */
function catpnav_auto_display_enabled():bool{
	if(is_admin()||is_feed()||is_embed()||is_preview()||(defined('REST_REQUEST')&&REST_REQUEST)){
		return false;
	}
	if(!is_singular('post')||!in_the_loop()||!is_main_query()){
		return false;
	}
	return empty($GLOBALS['catpnav_rendered']);
}
