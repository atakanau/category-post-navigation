<?php
declare(strict_types=1);
/**
 * Settings screen: single page with Basic and Advanced sections, plus shortcode examples.
 */
if(!defined('ABSPATH')){exit;}

/**
 * Registers the settings page under Settings.
 * @return void
 */
function catpnav_register_settings_screen():void{
	add_options_page(__('Category Navigation','category-post-navigation'),__('Post Navigation','category-post-navigation'),'manage_options','catpnav-settings','catpnav_render_settings_screen');
}

/**
 * Registers the option with its sanitize callback.
 * @return void
 */
function catpnav_register_setting():void{
	register_setting('catpnav','catpnav_settings',['type'=>'array','sanitize_callback'=>'catpnav_sanitize_settings','default'=>catpnav_default_settings()]);
}

/**
 * Enqueues the settings screen assets (form behaviour, live preview) with localized data.
 * @param string $hook Current admin page hook suffix.
 * @return void
 */
function catpnav_enqueue_admin_assets(string $hook):void{
	if('settings_page_catpnav-settings'!==$hook){
		return;
	}
	wp_enqueue_script('catpnav-admin',plugins_url('assets/js/catpnav-admin.js',CATPNAV_FILE),[],CATPNAV_VERSION,['strategy'=>'defer','in_footer'=>true]);
	wp_localize_script('catpnav-admin','catpnavAdmin',[
		'categoryToggle'	=>'catpnav-categories-enabled',
		'categorySelect'	=>'catpnav-categories',
		'categoryDescription'=>'catpnav-categories-desc',
		'scopeName'			=>'catpnav_settings[scope]',
		'aroundLabel'		=>'catpnav-around-label',
		'aroundDesc'		=>'catpnav-around-desc',
		'aroundField'		=>'catpnav-around',
		'i18n'				=>[
			'descOn'	=>__('Empty selection means all categories.','category-post-navigation'),
			'descOff'	=>__('The category filter is off: navigation uses all categories.','category-post-navigation'),
			'labelPerSide'=>__('Items per side','category-post-navigation'),
			'labelTotal'	=>__('Total items','category-post-navigation'),
			'descPerSide'	=>__('Posts shown on each side of the current post.','category-post-navigation'),
			'descTotal'		=>__('Total items shown; the window shifts near the edges to keep the count constant.','category-post-navigation'),
		],
	]);
	wp_enqueue_style('catpnav-public',plugins_url('assets/css/catpnav-public.css',CATPNAV_FILE),[],CATPNAV_VERSION);
	wp_enqueue_style('catpnav-admin',plugins_url('assets/css/catpnav-admin.css',CATPNAV_FILE),['catpnav-public'],CATPNAV_VERSION);
	wp_enqueue_script('catpnav-preview',plugins_url('assets/js/catpnav-preview.js',CATPNAV_FILE),['catpnav-admin'],CATPNAV_VERSION,['strategy'=>'defer','in_footer'=>true]);
	wp_localize_script('catpnav-preview','catpnavPreview',[
		'i18n'=>[
			'navLabel'	=>__('Navigate posts in this category','category-post-navigation'),
			'sampleTitle'=>__('Sample Post %d','category-post-navigation'),
			'prevDefault'=>__('Previous','category-post-navigation'),
			'nextDefault'=>__('Next','category-post-navigation'),
		],
	]);
}

/**
 * Renders the settings screen.
 * @return void
 */
function catpnav_render_settings_screen():void{
	if(!current_user_can('manage_options')){
		return;
	}
	$cfg=catpnav_get_settings();
	$cats=get_terms(['taxonomy'=>'category','hide_empty'=>false]);
	$cats=is_wp_error($cats)?[]:(array)$cats;
	?>
	<div class="wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	<div class="catpnav-settings-grid">
	<form method="post" action="options.php">
		<?php settings_fields('catpnav'); ?>
		<h2 class="title"><?php esc_html_e('Basic Settings','category-post-navigation'); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e('Quick start','category-post-navigation'); ?></th>
				<td>
					<button type="button" class="button" data-catpnav-preset="classic" data-mode="numeric" data-scope="all" data-ellipsis="1" title="<?php esc_attr_e('Numbered list with ellipsis for long series.','category-post-navigation'); ?>"><?php esc_html_e('Classic list','category-post-navigation'); ?></button>
					<button type="button" class="button" data-catpnav-preset="around" data-mode="numeric" data-scope="around" data-around="5" title="<?php esc_attr_e('Posts around the current post','category-post-navigation'); ?>"><?php esc_html_e('Nearby window','category-post-navigation'); ?></button>
					<button type="button" class="button" data-catpnav-preset="fixed" data-mode="numeric" data-scope="fixed" data-around="7" title="<?php esc_attr_e('Fixed-size window; the count stays constant near the edges.','category-post-navigation'); ?>"><?php esc_html_e('Fixed window','category-post-navigation'); ?></button>
					<button type="button" class="button" data-catpnav-preset="prevnext" data-mode="prevnext" title="<?php esc_attr_e('Simple step-by-step links.','category-post-navigation'); ?>"><?php esc_html_e('Prev / Next','category-post-navigation'); ?></button>
					<button type="button" class="button" data-catpnav-preset="mixed" data-mode="mixed" title="<?php esc_attr_e('Mixed combines prev/next links with the numeric list.','category-post-navigation'); ?>"><?php esc_html_e('Mixed','category-post-navigation'); ?></button>
					<p class="description"><?php esc_html_e('Presets fill the settings below as a quick start; adjust anything and save.','category-post-navigation'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="catpnav-categories"><?php esc_html_e('Categories','category-post-navigation'); ?></label></th>
				<td>
					<label><input type="hidden" name="catpnav_settings[categories_enabled]" value="0"><input type="checkbox" id="catpnav-categories-enabled" name="catpnav_settings[categories_enabled]" value="1"<?php checked(!empty($cfg['categories'])); ?>> <?php esc_html_e('Limit navigation to selected categories','category-post-navigation'); ?></label><br>
					<select id="catpnav-categories" name="catpnav_settings[categories][]" multiple size="6"<?php disabled(empty($cfg['categories'])); ?>>
						<?php foreach($cats as $cat){ ?>
							<option value="<?php echo esc_attr($cat->term_id); ?>"<?php selected(in_array((int)$cat->term_id,$cfg['categories'],true)); ?>><?php echo esc_html($cat->name); ?></option>
						<?php } ?>
					</select>
					<p class="description" id="catpnav-categories-desc"><?php echo esc_html(empty($cfg['categories'])?__('The category filter is off: navigation uses all categories.','category-post-navigation'):__('Empty selection means all categories.','category-post-navigation')); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Mode','category-post-navigation'); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e('Mode','category-post-navigation'); ?></legend>
						<label><input type="radio" name="catpnav_settings[mode]" value="numeric"<?php checked('numeric',$cfg['mode']); ?>> <?php esc_html_e('Numeric','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[mode]" value="prevnext"<?php checked('prevnext',$cfg['mode']); ?>> <?php esc_html_e('Prev / Next','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[mode]" value="mixed"<?php checked('mixed',$cfg['mode']); ?>> <?php esc_html_e('Mixed','category-post-navigation'); ?></label>
						<p class="description"><?php esc_html_e('Mixed combines prev/next links with the numeric list.','category-post-navigation'); ?></p>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="catpnav-prev-label"><?php esc_html_e('Previous label','category-post-navigation'); ?></label></th>
				<td>
					<input type="text" id="catpnav-prev-label" name="catpnav_settings[prev_label]" value="<?php echo esc_attr($cfg['prev_label']); ?>" class="regular-text">
					<p class="description"><?php esc_html_e('Custom text for the previous link; empty uses the translated default.','category-post-navigation'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="catpnav-next-label"><?php esc_html_e('Next label','category-post-navigation'); ?></label></th>
				<td>
					<input type="text" id="catpnav-next-label" name="catpnav_settings[next_label]" value="<?php echo esc_attr($cfg['next_label']); ?>" class="regular-text">
					<p class="description"><?php esc_html_e('Custom text for the next link; empty uses the translated default.','category-post-navigation'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Automatic display','category-post-navigation'); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e('Automatic display','category-post-navigation'); ?></legend>
						<label><input type="hidden" name="catpnav_settings[auto_before]" value="0"><input type="checkbox" name="catpnav_settings[auto_before]" value="1"<?php checked(in_array($cfg['auto'],['before','both'],true)); ?>> <?php esc_html_e('Before content','category-post-navigation'); ?></label><br>
						<label><input type="hidden" name="catpnav_settings[auto_after]" value="0"><input type="checkbox" name="catpnav_settings[auto_after]" value="1"<?php checked(in_array($cfg['auto'],['after','both'],true)); ?>> <?php esc_html_e('After content','category-post-navigation'); ?></label>
					</fieldset>
				</td>
			</tr>
		</table>
		<h2 class="title"><?php esc_html_e('Advanced Settings','category-post-navigation'); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e('Multi-category priority','category-post-navigation'); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e('Multi-category priority','category-post-navigation'); ?></legend>
						<label><input type="radio" name="catpnav_settings[multi_cat]" value="first"<?php checked('first',$cfg['multi_cat']); ?>> <?php esc_html_e('First category (lowest ID)','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[multi_cat]" value="deepest"<?php checked('deepest',$cfg['multi_cat']); ?>> <?php esc_html_e('Deepest (child) category','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[multi_cat]" value="most_posts"<?php checked('most_posts',$cfg['multi_cat']); ?>> <?php esc_html_e('Category with the most posts','category-post-navigation'); ?></label>
						<p class="description"><?php esc_html_e('How to pick the context category when a post belongs to several categories.','category-post-navigation'); ?></p>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Scope','category-post-navigation'); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e('Scope','category-post-navigation'); ?></legend>
						<label><input type="radio" name="catpnav_settings[scope]" value="all"<?php checked('all',$cfg['scope']); ?>> <?php esc_html_e('All posts','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[scope]" value="around"<?php checked('around',$cfg['scope']); ?>> <?php esc_html_e('Posts around the current post','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[scope]" value="fixed"<?php checked('fixed',$cfg['scope']); ?>> <?php esc_html_e('Fixed total','category-post-navigation'); ?></label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="catpnav-around"><span id="catpnav-around-label"><?php esc_html_e('Items per side','category-post-navigation'); ?></span></label></th>
				<td>
					<input type="number" id="catpnav-around" name="catpnav_settings[around]" value="<?php echo esc_attr($cfg['around']); ?>" min="1" max="20" class="small-text">
					<p class="description" id="catpnav-around-desc"<?php echo 'all'===$cfg['scope']?' style="display:none"':''; ?>><?php echo esc_html('fixed'===$cfg['scope']?__('Total items shown; the window shifts near the edges to keep the count constant.','category-post-navigation'):__('Posts shown on each side of the current post.','category-post-navigation')); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Order by','category-post-navigation'); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e('Order by','category-post-navigation'); ?></legend>
						<label><input type="radio" name="catpnav_settings[orderby]" value="date"<?php checked('date',$cfg['orderby']); ?>> <?php esc_html_e('Date','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[orderby]" value="title"<?php checked('title',$cfg['orderby']); ?>> <?php esc_html_e('Title','category-post-navigation'); ?></label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Direction','category-post-navigation'); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e('Direction','category-post-navigation'); ?></legend>
						<label><input type="radio" name="catpnav_settings[order]" value="ASC"<?php checked('ASC',$cfg['order']); ?>> <?php esc_html_e('Ascending','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[order]" value="DESC"<?php checked('DESC',$cfg['order']); ?>> <?php esc_html_e('Descending','category-post-navigation'); ?></label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Layout','category-post-navigation'); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e('Layout','category-post-navigation'); ?></legend>
						<label><input type="radio" name="catpnav_settings[layout]" value="horizontal"<?php checked('horizontal',$cfg['layout']); ?>> <?php esc_html_e('Horizontal','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[layout]" value="vertical"<?php checked('vertical',$cfg['layout']); ?>> <?php esc_html_e('Vertical','category-post-navigation'); ?></label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Numbering','category-post-navigation'); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e('Numbering','category-post-navigation'); ?></legend>
						<label><input type="radio" name="catpnav_settings[numbering]" value="ascending"<?php checked('ascending',$cfg['numbering']); ?>> <?php esc_html_e('Ascending','category-post-navigation'); ?></label><br>
						<label><input type="radio" name="catpnav_settings[numbering]" value="descending"<?php checked('descending',$cfg['numbering']); ?>> <?php esc_html_e('Descending','category-post-navigation'); ?></label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Show titles','category-post-navigation'); ?></th>
				<td><label><input type="hidden" name="catpnav_settings[show_title]" value="0"><input type="checkbox" name="catpnav_settings[show_title]" value="1"<?php checked($cfg['show_title']); ?>> <?php esc_html_e('Show titles next to numbers','category-post-navigation'); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Use ellipsis','category-post-navigation'); ?></th>
				<td><label><input type="hidden" name="catpnav_settings[ellipsis]" value="0"><input type="checkbox" name="catpnav_settings[ellipsis]" value="1"<?php checked($cfg['ellipsis']); ?>> <?php esc_html_e('Collapse long lists to numbers around the current post','category-post-navigation'); ?></label></td>
			</tr>
		</table>
		<?php submit_button(); ?>
		</form>
		<div class="catpnav-side-pane">
		<aside class="catpnav-preview-pane" aria-label="<?php esc_attr_e('Preview','category-post-navigation'); ?>">
			<h2 class="title"><?php esc_html_e('Preview','category-post-navigation'); ?></h2>
			<p class="description"><?php esc_html_e('Preview of the navigation with sample posts; it updates as you change the settings.','category-post-navigation'); ?></p>
			<div class="catpnav-preview-box" id="catpnav-preview"></div>
		</aside>
		<aside class="catpnav-support-pane" aria-label="<?php esc_attr_e('Support and donation','category-post-navigation'); ?>">
			<p class="description"><?php esc_html_e('Get help, leave a rating or support the development.','category-post-navigation'); ?></p>
			<ul class="catpnav-support-links">
				<?php foreach(['support','rate','donate'] as $key){ ?>
					<li><?php echo catpnav_link_html($key); ?></li>
				<?php } ?>
			</ul>
		</aside>
		</div>
		</div>
		<h2 class="title"><?php esc_html_e('Shortcode','category-post-navigation'); ?></h2>
		<p><?php esc_html_e('Default usage, which follows the settings above:','category-post-navigation'); ?></p>
		<p><code>[category_post_navigation]</code></p>
		<p><?php esc_html_e('Override any setting per usage; unknown or invalid attributes fall back to the settings:','category-post-navigation'); ?></p>
		<p><code>[category_post_navigation category="news-and-updates" scope="around" around="5" show_title="false"]</code></p>
		<p><code>[category_post_navigation category="tutorials" scope="all" orderby="date" order="ASC" numbering="descending"]</code></p>
		<p><code>[category_post_navigation mode="mixed" scope="fixed" around="9" prev="Back" next="Forward"]</code></p>
	</div>
	<?php
}

/**
 * Adds a Settings link to the plugin row on the Plugins screen.
 * @param array $links Existing action links.
 * @return array Action links with Settings prepended.
 */
function catpnav_action_links(array $links):array{
	$url=admin_url('options-general.php?page=catpnav-settings');
	array_unshift($links,'<a href="'.esc_url($url).'">'.esc_html__('Settings','category-post-navigation').'</a>');
	return $links;
}

/**
 * Central registry of plugin links and labels.
 * Single source of truth for the Plugins screen row meta and the settings
 * screen support box. Labels are translated here and escaped where printed.
 * @return array Map of link key to ['label'=>string,'url'=>string].
 */
function catpnav_links():array{
	return [
		'support'	=>['label'=>__('Support','category-post-navigation'),'url'=>'https://en.programs.com.tr/2026/11/wordpress-category-post-navigation-tool.html#comment'],
		'rate'		=>['label'=>__('Rate ★★★★★','category-post-navigation'),'url'=>'https://github.com/atakanau/category-post-navigation'],
		'donate'	=>['label'=>__('❤︎ Donate','category-post-navigation'),'url'=>'https://buymeacoffee.com/atakanau'],
	];
}

/**
 * Renders one escaped anchor from the central link registry.
 * @param string $key Link key in catpnav_links().
 * @return string Escaped anchor HTML, or an empty string for unknown keys.
 */
function catpnav_link_html(string $key):string{
	$links=catpnav_links();
	if(empty($links[$key]['url'])){
		return '';
	}
	return '<a href="'.esc_url($links[$key]['url']).'">'.esc_html($links[$key]['label']).'</a>';
}

/**
 * Adds the central plugin links to the row meta on the Plugins screen.
 * WordPress already prints the version, the author and the visit-plugin-site
 * link from the plugin header; only the central links are appended,
 * without duplicates.
 * @param array $meta Existing row meta items.
 * @param string $file Plugin base name of the current row.
 * @return array Row meta with the central links appended for this plugin.
 */
function catpnav_row_meta(array $meta,string $file):array{
	if(plugin_basename(CATPNAV_FILE)!==$file){
		return $meta;
	}
	foreach(['support','rate','donate'] as $key){
		$meta[]=catpnav_link_html($key);
	}
	return $meta;
}
