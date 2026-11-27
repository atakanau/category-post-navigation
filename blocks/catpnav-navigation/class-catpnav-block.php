<?php
declare(strict_types=1);
/**
 * Gutenberg block: catpnav/navigation (dynamic; reuses the shortcode renderer).
 */
if(!defined('ABSPATH')){exit;}

final class Catpnav_Block{

	const HANDLE_EDITOR='catpnav-block-editor';

	/**
	 * Registers the editor script and the block type; hooked to init at priority 20.
	 * @return void
	 */
	public static function register():void{
		wp_register_script(self::HANDLE_EDITOR,plugins_url('assets/js/catpnav-block-editor.js',CATPNAV_FILE),['wp-blocks','wp-element','wp-block-editor','wp-components','wp-api-fetch','wp-i18n','wp-server-side-render'],CATPNAV_VERSION,['in_footer'=>true]);
		wp_set_script_translations(self::HANDLE_EDITOR,'category-post-navigation',plugin_dir_path(CATPNAV_FILE).'languages');
		register_block_type(__DIR__,['render_callback'=>[self::class,'render']]);
	}

	/**
	 * Renders the navigation; the block mirrors catpnav_get_navigation() exactly.
	 * Empty attribute values keep the global plugin settings; only non-empty
	 * overrides reach the renderer (same contract as the shortcode arguments).
	 * @param array $attributes Block attributes.
	 * @param string $content Saved content; ignored (dynamic block).
	 * @param WP_Block|null $block Block instance; ignored.
	 * @return string Navigation HTML, editor placeholder, or ''.
	 */
	public static function render(array $attributes,string $content='',?WP_Block $block=null):string{
		unset($content,$block);
		$cat=(int)($attributes['categoryId']??0);
		$mode=(string)($attributes['mode']??'');
		$scope=(string)($attributes['scope']??'');
		$around=(int)($attributes['around']??0);
		$args=[];
		if($cat>0){$args['category']=$cat;}
		if(''!==$mode){$args['mode']=$mode;}
		if(''!==$scope){$args['scope']=$scope;}
		if($around>0){$args['around']=$around;}
		$html=catpnav_get_navigation($args);
		if(''!==$html){
			// Block output wins over automatic display on the same request, as with the shortcode.
			$GLOBALS['catpnav_rendered']=true;
			return $html;
		}
		// Editor preview without a single-post context (e.g. the Site Editor): the
		// renderer needs a published post, so show a friendly placeholder instead
		// of the empty output; the front end stays silent like the shortcode.
		if(defined('REST_REQUEST')&&REST_REQUEST){
			$pid=(int)get_the_ID();
			if($pid<1||'post'!==get_post_type($pid)||'auto-draft'===get_post_status($pid)){
				return self::placeholder();
			}
		}
		return '';
	}

	/**
	 * Editor-only placeholder shown when no live preview is possible.
	 * @return string Placeholder HTML.
	 */
	private static function placeholder():string{
		$label=esc_html__('Category Post Navigation','category-post-navigation');
		$hint=esc_html__('Live preview needs a published single post whose category has at least two posts; the navigation renders on the front end.','category-post-navigation');
		return '<div class="components-placeholder"><div class="components-placeholder__label">'.$label.'</div><div class="components-placeholder__instructions">'.$hint.'</div></div>';
	}
}
