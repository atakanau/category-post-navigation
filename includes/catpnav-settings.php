<?php
declare(strict_types=1);
/**
 * Settings: defaults, option persistence, validation, config resolution.
 */
if(!defined('ABSPATH')){exit;}

/**
 * Returns the default settings.
 * @return array Default value for every catpnav_settings key.
 */
function catpnav_default_settings():array{
	return [
		'categories'	=>[],
		'multi_cat'		=>'first',
		'scope'			=>'all',
		'around'		=>5,
		'orderby'		=>'date',
		'order'			=>'DESC',
		'mode'			=>'numeric',
		'layout'		=>'horizontal',
		'numbering'		=>'ascending',
		'show_title'	=>false,
		'ellipsis'		=>true,
		'auto'			=>'none',
		'prev_label'	=>'',
		'next_label'	=>'',
	];
}

/**
 * Returns the allowed values per choice setting.
 * @return array Map of setting key to allowed values.
 */
function catpnav_allowed_values():array{
	return [
		'scope'		=>['all','around','fixed'],
		'orderby'	=>['date','title'],
		'order'		=>['ASC','DESC'],
		'mode'		=>['numeric','prevnext','mixed'],
		'layout'	=>['horizontal','vertical'],
		'numbering'	=>['ascending','descending'],
		'auto'		=>['none','before','after','both'],
		'multi_cat'	=>['first','deepest','most_posts'],
	];
}

/**
 * Seeds the option with defaults on activation; keeps existing values.
 * @return void
 */
function catpnav_activate():void{
	if(false===get_option('catpnav_settings',false)){
		add_option('catpnav_settings',catpnav_default_settings());
	}
}

/**
 * Returns merged, validated settings from the option store.
 * @return array Validated settings with every key present.
 */
function catpnav_get_settings():array{
	return array_merge(catpnav_default_settings(),catpnav_validated_partial((array)get_option('catpnav_settings',[])));
}

/**
 * Sanitize callback for the settings form; translates UI-only fields.
 * The category filter checkbox and the automatic display checkboxes are
 * form controls without a stored counterpart and are mapped before validation.
 * @param mixed $raw Raw option values from the settings form.
 * @return array Validated settings with every key present.
 */
function catpnav_sanitize_settings($raw):array{
	$raw=(array)$raw;
	if(empty($raw['categories_enabled'])){
		$raw['categories']=[];// Filter off: all categories.
	}
	unset($raw['categories_enabled']);
	if(isset($raw['auto_before'],$raw['auto_after'])){
		$raw['auto']=catpnav_auto_from_checkboxes(!empty($raw['auto_before']),!empty($raw['auto_after']));
	}
	unset($raw['auto_before'],$raw['auto_after']);
	return array_merge(catpnav_default_settings(),catpnav_validated_partial($raw));
}

/**
 * Maps the automatic display checkboxes to the stored auto value.
 * @param bool $before Whether "Before content" is checked.
 * @param bool $after Whether "After content" is checked.
 * @return string One of 'none','before','after','both'.
 */
function catpnav_auto_from_checkboxes(bool $before,bool $after):string{
	if($before&&$after){
		return 'both';
	}
	if($before){
		return 'before';
	}
	if($after){
		return 'after';
	}
	return 'none';
}

/**
 * Validates raw values and returns only the provided valid keys.
 * Unknown keys are dropped; invalid values fall back to the next config tier.
 * @param array $raw Raw values, e.g. option data or call arguments.
 * @return array Partial map of valid keys.
 */
function catpnav_validated_partial(array $raw):array{
	$out=[];
	$allowed=catpnav_allowed_values();
	$defaults=catpnav_default_settings();
	foreach($raw as $key=>$val){
		if(!array_key_exists($key,$defaults)){
			continue;
		}
		if(isset($allowed[$key])){
			if(is_string($val)&&in_array($val,$allowed[$key],true)){
				$out[$key]=$val;
			}
			continue;
		}
		if('categories'===$key){
			$out[$key]=catpnav_sanitize_category_ids((array)$val);
			continue;
		}
		if('around'===$key){
			$n=absint($val);
			if($n>=1&&$n<=20){
				$out[$key]=$n;
			}
			continue;
		}
		if('prev_label'===$key||'next_label'===$key){
			$text=sanitize_text_field((string)$val);
			if(''!==$text){
				$out[$key]=$text;
			}
			continue;
		}
		$out[$key]=catpnav_parse_bool($val);
	}
	return $out;
}

/**
 * Keeps only IDs of existing categories.
 * @param array $raw Raw category ID list.
 * @return array Unique non-zero term IDs that exist.
 */
function catpnav_sanitize_category_ids(array $raw):array{
	$ids=array_values(array_unique(array_filter(array_map('absint',$raw))));
	if([]===$ids){
		return [];
	}
	$existing=get_terms(['taxonomy'=>'category','fields'=>'ids','hide_empty'=>false]);
	if(is_wp_error($existing)){
		return [];
	}
	return array_values(array_intersect($ids,array_map('intval',(array)$existing)));
}

/**
 * Parses a boolean setting from mixed input.
 * @param mixed $val Raw value, e.g. '1','0','true','false' or bool.
 * @return bool Parsed value.
 */
function catpnav_parse_bool($val):bool{
	if(is_bool($val)){
		return $val;
	}
	if(is_string($val)&&in_array(strtolower($val),['false','0','off','no',''],true)){
		return false;
	}
	return (bool)$val;
}

/**
 * Resolves the effective config: call arguments, saved settings, then defaults.
 * @param array $raw Call arguments, e.g. shortcode attributes.
 * @return array Validated effective config.
 */
function catpnav_resolve_config(array $raw):array{
	return array_merge(catpnav_get_settings(),catpnav_validated_partial($raw));
}
