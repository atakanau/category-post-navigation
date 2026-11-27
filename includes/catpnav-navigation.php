<?php
declare(strict_types=1);
/**
 * Core engine: context category, post query, windowing, HTML rendering.
 */
if(!defined('ABSPATH')){exit;}

/**
 * Returns the context category ID using the configured multi-category priority.
 * @param int $post_id Post ID.
 * @param array $selected Allowed category IDs; empty means all.
 * @param string $priority 'first' (lowest term ID), 'deepest' or 'most_posts'.
 * @return int Context category term ID or 0 when none qualifies.
 */
function catpnav_context_category_id(int $post_id,array $selected,string $priority='first'):int{
	$terms=get_the_category($post_id);
	if(!is_array($terms)||[]===$terms){
		return 0;
	}
	// Baseline: stable ascending term_id order, so ties resolve to the lowest ID.
	$terms=wp_list_sort($terms,'term_id','ASC');
	$candidates=[];
	foreach($terms as $term){
		if([]===$selected||in_array((int)$term->term_id,$selected,true)){
			$candidates[]=$term;
		}
	}
	if([]===$candidates){
		return 0;
	}
	if('deepest'===$priority){
		usort($candidates,static function(WP_Term $a,WP_Term $b):int{
			return catpnav_term_depth((int)$b->term_id)<=>catpnav_term_depth((int)$a->term_id)
				?:(int)$a->term_id<=>(int)$b->term_id;
		});
	}elseif('most_posts'===$priority){
		usort($candidates,static function(WP_Term $a,WP_Term $b):int{
			return (int)$b->count<=>(int)$a->count
				?:(int)$a->term_id<=>(int)$b->term_id;
		});
	}
	return (int)$candidates[0]->term_id;
}

/**
 * Counts the ancestors of a category term.
 * @param int $term_id Category term ID.
 * @return int Ancestor count; 0 for top-level terms.
 */
function catpnav_term_depth(int $term_id):int{
	return count(get_ancestors($term_id,'category'));
}

/**
 * Queries published post IDs of a category in the requested order.
 * @param int $cat_id Category term ID.
 * @param string $orderby 'date' or 'title'.
 * @param string $order 'ASC' or 'DESC'.
 * @return array Ordered post ID list.
 */
function catpnav_query_post_ids(int $cat_id,string $orderby,string $order):array{
	$q=new WP_Query([
		'post_type'					=>'post',
		'post_status'				=>'publish',
		'posts_per_page'			=>-1,
		'fields'					=>'ids',
		'no_found_rows'				=>true,
		'update_post_meta_cache'	=>false,
		'update_post_term_cache'	=>false,
		'orderby'					=>$orderby,
		'order'						=>$order,
		'tax_query'					=>[
			[
				'taxonomy'	=>'category',
				'field'		=>'term_id',
				'terms'		=>$cat_id,
			],
		],
	]);
	return array_map('intval',(array)$q->posts);
}

/**
 * Primes the post cache for the rendered items with one batched query.
 * @param array $visible_ids Post IDs to prime.
 * @return void
 */
function catpnav_prime_posts(array $visible_ids):void{
	if([]===$visible_ids){
		return;
	}
	new WP_Query([
		'post_type'					=>'post',
		'post_status'				=>'publish',
		'posts_per_page'			=>count($visible_ids),
		'post__in'					=>$visible_ids,
		'orderby'					=>'post__in',
		'no_found_rows'				=>true,
		'ignore_sticky_posts'		=>true,
		'update_post_meta_cache'	=>false,
		'update_post_term_cache'	=>false,
	]);
}

/**
 * Builds the rendered items for a scope; pure function with no WordPress calls.
 * @param array $ids Ordered post ID list.
 * @param int $pos Zero-based position of the current post.
	 * @param string $scope 'all', 'around' or 'fixed'.
	 * @param int $around Items kept on each side for the 'around' scope; total items for 'fixed'.
 * @param bool $ellipsis Whether the all-scope may collapse ranges.
 * @return array Items as ['type'=>'post','pos'=>int] or ['type'=>'ellipsis'].
 */
function catpnav_build_items(array $ids,int $pos,string $scope,int $around,bool $ellipsis):array{
	$count=count($ids);
	if($count<1||$pos<0||$pos>=$count){
		return [];
	}
	if('around'===$scope){
		return catpnav_range_items(max(0,$pos-$around),min($count-1,$pos+$around));
	}
	if('fixed'===$scope){
		$size=max(1,$around);
		if($size>=$count){
			return catpnav_range_items(0,$count-1);
		}
		// Shift the window at both edges so the item total stays constant.
		$start=max(0,min($pos-intdiv($size-1,2),$count-$size));
		return catpnav_range_items($start,$start+$size-1);
	}
	if(!$ellipsis||$count<=CATPNAV_ELLIPSIS_THRESHOLD){
		return catpnav_range_items(0,$count-1);
	}
	// Keep head 2, tail 2 and three neighbours on each side of the current post.
	$keep=array_merge(
		range(0,min(1,$count-1)),
		range(max(0,$pos-3),min($count-1,$pos+3)),
		range(max(0,$count-2),$count-1)
	);
	$keep=array_values(array_unique($keep));
	sort($keep);
	$items=[];
	$prev=-1;
	foreach($keep as $p){
		if($prev>=0&&$p-$prev>1){
			$items[]=['type'=>'ellipsis'];
		}
		$items[]=['type'=>'post','pos'=>$p];
		$prev=$p;
	}
	return $items;
}

/**
 * Returns sequential post items for a position range.
 * @param int $start First position.
 * @param int $end Last position, inclusive.
 * @return array Post items in order.
 */
function catpnav_range_items(int $start,int $end):array{
	$items=[];
	for($p=$start;$p<=$end;$p++){
		$items[]=['type'=>'post','pos'=>$p];
	}
	return $items;
}

/**
 * Computes the displayed number for a position.
 * @param int $pos Zero-based position.
 * @param int $count Total post count.
 * @param array $cfg Effective config.
 * @return int One-based display number.
 */
function catpnav_display_number(int $pos,int $count,array $cfg):int{
	return 'descending'===$cfg['numbering']?$count-$pos:$pos+1;
}

/**
 * Collects the post IDs that get rendered as links or titles.
 * @param array $items Window items.
 * @param array $ids Ordered post ID list.
 * @return array Rendered post IDs.
 */
function catpnav_visible_ids(array $items,array $ids):array{
	$out=[];
	foreach($items as $item){
		if('post'===$item['type']){
			$out[]=(int)$ids[$item['pos']];
		}
	}
	return $out;
}

/**
 * Renders the navigation HTML for a post; returns '' when hidden.
 * @param array $cfg Effective config.
 * @param int $post_id Post to navigate from; 0 uses the current post.
 * @param int $cat_override Fixed category term ID; 0 uses the context rule.
 * @return string Navigation HTML or ''.
 */
function catpnav_render_navigation(array $cfg,int $post_id=0,int $cat_override=0):string{
	$post_id=$post_id>0?$post_id:(int)get_the_ID();
	if($post_id<1||'post'!==get_post_type($post_id)){
		return '';
	}
	$cat_id=$cat_override>0?$cat_override:catpnav_context_category_id($post_id,$cfg['categories'],(string)($cfg['multi_cat']??'first'));
	if($cat_id<1){
		return '';
	}
	$ids=catpnav_query_post_ids($cat_id,$cfg['orderby'],$cfg['order']);
	if(count($ids)<2){
		return '';
	}
	$found=array_search($post_id,$ids,true);
	if(false===$found){
		return '';
	}
	$pos=(int)$found;
	$items=catpnav_build_items($ids,$pos,$cfg['scope'],(int)$cfg['around'],(bool)$cfg['ellipsis']);
	if([]===$items){
		return '';
	}
	$items=(array)apply_filters('catpnav_window_items',$items,$cfg);
	if([]===$items){
		return '';
	}
	catpnav_prime_posts(catpnav_visible_ids($items,$ids));
	$html=(string)apply_filters('catpnav_navigation_html',catpnav_items_html($items,$ids,$pos,$cfg),$items,$cfg);
	if(''!==$html){
		wp_enqueue_style('catpnav-public',plugins_url('assets/css/catpnav-public.css',CATPNAV_FILE),[],CATPNAV_VERSION);
	}
	return $html;
}

/**
 * Builds the navigation markup for the active mode (numeric or mixed).
 * @param array $items Window items from catpnav_build_items().
 * @param array $ids Ordered post ID list.
 * @param int $pos Position of the current post.
 * @param array $cfg Effective config.
 * @return string Navigation HTML.
 */
function catpnav_items_html(array $items,array $ids,int $pos,array $cfg):string{
	$root='catpnav-nav catpnav-nav--'.$cfg['layout'].' catpnav-nav--mode-'.$cfg['mode'];
	$count=count($ids);
	if('prevnext'===$cfg['mode']){
		return catpnav_prevnext_html($ids,$pos,$cfg,$root);
	}
	$label=__('Navigate posts in this category','category-post-navigation');
	$out='<nav class="'.esc_attr($root).'" aria-label="'.esc_attr($label).'"><ul class="catpnav-nav__list">';
	if('mixed'===$cfg['mode']&&$pos>0){
		$out.=catpnav_neighbour_item_html((int)$ids[$pos-1],$pos-1,$count,'prev',$cfg);
	}
	foreach($items as $item){
		if('ellipsis'===$item['type']){
			$out.='<li class="catpnav-nav__item catpnav-nav__ellipsis" aria-hidden="true"><span>…</span></li>';
			continue;
		}
		$p=$item['pos'];
		$id=(int)$ids[$p];
		if($p===$pos){
			$out.='<li class="catpnav-nav__item catpnav-nav__item--current" aria-current="page"><span class="catpnav-nav__number">'.esc_html(catpnav_display_number($p,$count,$cfg)).'</span></li>';
			continue;
		}
		$out.='<li class="catpnav-nav__item"><a class="catpnav-nav__link" href="'.esc_url(get_permalink($id)).'"><span class="catpnav-nav__number">'.esc_html(catpnav_display_number($p,$count,$cfg)).'</span>'.catpnav_title_html($id,$cfg).'</a></li>';
	}
	if('mixed'===$cfg['mode']&&$pos<$count-1){
		$out.=catpnav_neighbour_item_html((int)$ids[$pos+1],$pos+1,$count,'next',$cfg);
	}
	return $out.'</ul></nav>';
}

/**
 * Builds the optional title span for a linked item.
 * @param int $id Post ID.
 * @param array $cfg Effective config.
 * @return string Title span or ''.
 */
function catpnav_title_html(int $id,array $cfg):string{
	if(!$cfg['show_title']){
		return '';
	}
	return ' <span class="catpnav-nav__title">'.esc_html(get_the_title($id)).'</span>';
}

/**
 * Resolves the previous/next label with the translatable default as fallback.
 * @param array $cfg Effective config.
 * @param string $which 'prev' or 'next'.
 * @return string Label text; the caller escapes it.
 */
function catpnav_direction_label(array $cfg,string $which):string{
	$key='prev'===$which?'prev_label':'next_label';
	$custom=trim((string)($cfg[$key]??''));
	if(''!==$custom){
		return $custom;
	}
	return 'prev'===$which?__('Previous','category-post-navigation'):__('Next','category-post-navigation');
}

/**
 * Builds the previous or next neighbour list item.
 * @param int $id Post ID of the neighbour.
 * @param int $p Position of the neighbour.
 * @param int $count Total post count.
 * @param string $which 'prev' or 'next'.
 * @param array $cfg Effective config.
 * @return string List item HTML.
 */
function catpnav_neighbour_item_html(int $id,int $p,int $count,string $which,array $cfg):string{
	$label='<span class="catpnav-nav__label">'.esc_html(catpnav_direction_label($cfg,$which)).'</span>';
	$number='<span class="catpnav-nav__number">'.esc_html(catpnav_display_number($p,$count,$cfg)).'</span>';
	$arrow='<span class="catpnav-nav__arrow">'.('prev'===$which?'&larr;':'&rarr;').'</span>';
	$inner='prev'===$which
		?$arrow.$label.$number.catpnav_title_html($id,$cfg)
		:$number.catpnav_title_html($id,$cfg).$label.$arrow;
	return '<li class="catpnav-nav__item catpnav-nav__item--'.$which.'"><a class="catpnav-nav__link" href="'.esc_url(get_permalink($id)).'">'.$inner.'</a></li>';
}

/**
 * Builds the prev/next mode markup; always has one neighbour by design.
 * @param array $ids Ordered post ID list.
 * @param int $pos Position of the current post.
 * @param array $cfg Effective config.
 * @param string $root Root class list, unescaped.
 * @return string Navigation HTML.
 */
function catpnav_prevnext_html(array $ids,int $pos,array $cfg,string $root):string{
	$count=count($ids);
	$label=__('Navigate posts in this category','category-post-navigation');
	$out='<nav class="'.esc_attr($root).'" aria-label="'.esc_attr($label).'"><ul class="catpnav-nav__list">';
	if($pos>0){
		$out.=catpnav_neighbour_item_html((int)$ids[$pos-1],$pos-1,$count,'prev',$cfg);
	}
	if($pos<$count-1){
		$out.=catpnav_neighbour_item_html((int)$ids[$pos+1],$pos+1,$count,'next',$cfg);
	}
	return $out.'</ul></nav>';
}
