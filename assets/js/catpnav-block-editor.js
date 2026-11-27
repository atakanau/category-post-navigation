/* Category Post Navigation: editor side of the catpnav/navigation block (no build step). */
(function(wp){
	'use strict';
	if(!wp||!wp.blocks||!wp.element||!wp.blockEditor||!wp.components||!wp.apiFetch||!wp.serverSideRender||!wp.i18n){
		return;
	}
	var el=wp.element.createElement;
	var __=wp.i18n.__;
	var useState=wp.element.useState;
	var useEffect=wp.element.useEffect;
	var InspectorControls=wp.blockEditor.InspectorControls;
	var PanelBody=wp.components.PanelBody;
	var SelectControl=wp.components.SelectControl;
	var NumberControl=wp.components.NumberControl||wp.components.__experimentalNumberControl;
	var useBlockProps=wp.blockEditor.useBlockProps;
	// A missing global must bail out here: an undefined component passed to
	// createElement crashes the editor with React error #130, while skipping
	// registration only hides the block. InspectorControls and useBlockProps
	// live in wp.blockEditor; NumberControl keeps its experimental alias on 6.8.
	if(!el||!__||!useState||!useEffect||!useBlockProps||!InspectorControls||!PanelBody||!SelectControl||!NumberControl){
		return;
	}

	var USE_SETTING=__('Use settings default','category-post-navigation');
	var MODE_OPTIONS=[
		{value:'',label:USE_SETTING},
		{value:'numeric',label:__('Numeric list','category-post-navigation')},
		{value:'prevnext',label:__('Prev / Next links','category-post-navigation')},
		{value:'mixed',label:__('Mixed','category-post-navigation')}
	];
	var SCOPE_OPTIONS=[
		{value:'',label:USE_SETTING},
		{value:'all',label:__('Classic list (all posts)','category-post-navigation')},
		{value:'around',label:__('Nearby window','category-post-navigation')},
		{value:'fixed',label:__('Fixed window','category-post-navigation')}
	];

	/**
	 * Loads published categories once for the override dropdown; empty lists
	 * or REST errors simply keep the settings-default option.
	 * @return Array Fetched category terms (empty before the request resolves).
	 */
	function useCategories(){
		var state=useState([]);
		var setCategories=state[1];
		useEffect(function(){
			var current=true;
			wp.apiFetch({path:'/wp/v2/categories?per_page=100&orderby=name&order=asc&_fields=id,name,count'}).then(function(list){
				if(current&&Array.isArray(list)){
					setCategories(list);
				}
			}).catch(function(){});
			return function(){
				current=false;
			};
		},[]);
		return state[0];
	}

	/**
	 * Editor view: inspector overrides plus the live server-side preview.
	 * Empty attribute values mean "follow the global settings".
	 * @param Object props Block props.
	 * @return Object Element tree.
	 */
	function edit(props){
		var attributes=props.attributes;
		var setAttributes=props.setAttributes;
		var categories=useCategories();
		var catOptions=[{value:'0',label:USE_SETTING}].concat(categories.filter(function(term){
			return (term.count|0)>0;
		}).map(function(term){
			return {value:String(term.id),label:String(term.name||('Category '+term.id))};
		}));
		var scope=attributes.scope||'';
		var showAround='around'===scope||'fixed'===scope;
		return el(
			'div',
			useBlockProps({className:'catpnav-block-editor'}),
			el(
				InspectorControls,
				{},
				el(PanelBody,{title:__('Category','category-post-navigation'),initialOpen:true},
					el(SelectControl,{
						label:__('Category','category-post-navigation'),
						value:String(attributes.categoryId||0),
						options:catOptions,
						onChange:function(value){
							setAttributes({categoryId:parseInt(value,10)||0});
						}
					})
				),
				el(PanelBody,{title:__('Display','category-post-navigation')},
					el(SelectControl,{
						label:__('Mode','category-post-navigation'),
						value:attributes.mode||'',
						options:MODE_OPTIONS,
						onChange:function(value){
							setAttributes({mode:value});
						}
					})
				),
				el(PanelBody,{title:__('Window','category-post-navigation')},
					el(SelectControl,{
						label:__('Scope','category-post-navigation'),
						value:scope,
						options:SCOPE_OPTIONS,
						onChange:function(value){
							setAttributes({scope:value});
						}
					}),
					showAround?el(NumberControl,{
						label:'around'===scope?__('Posts on each side','category-post-navigation'):__('Window items','category-post-navigation'),
						value:attributes.around>0?attributes.around:undefined,
						min:1,
						max:20,
						onChange:function(value){
							var n=parseInt(value,10);
							setAttributes({around:isNaN(n)?0:n});
						}
					}):null
				)
			),
			el(wp.serverSideRender,{
				block:'catpnav/navigation',
				attributes:attributes
			}),
			el(
				'p',
				{className:'catpnav-block-editor__hint'},
				__('Live preview. Global settings apply unless overridden in the block sidebar.','category-post-navigation')
			)
		);
	}

	wp.blocks.registerBlockType('catpnav/navigation',{
		edit:edit,
		save:function(){return null;}
	});
})(window.wp);
