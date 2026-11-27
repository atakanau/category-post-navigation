/**
 * Category Post Navigation - settings screen live preview.
 * Renders the same markup as includes/catpnav-navigation.php for a synthetic
 * 50-post sample (current post #10) and re-renders on form changes.
 * Pure helpers are exported for the PHP/JS parity test under scripts/.
 */
(function(){
	'use strict';
	var cfgData=('undefined'!==typeof window&&window.catpnavPreview)?window.catpnavPreview:{};
	var i18n=cfgData.i18n||{};
	var SAMPLE_COUNT=50;
	var SAMPLE_CURRENT=10;
	/* Mirrors CATPNAV_ELLIPSIS_THRESHOLD from the main plugin file. */
	var ELLIPSIS_THRESHOLD=10;
	var FIELD='catpnav_settings[';
	/**
	 * Escapes a value for HTML output (mirrors esc_html/esc_attr).
	 * @param {string} value Raw value.
	 * @return {string} Escaped value.
	 */
	function catpnavPreviewEsc(value){
		return String(value).replace(/[&<>"']/g,function(ch){
			return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];
		});
	}
	/**
	 * Builds the window items; mirrors catpnav_build_items().
	 * @param {number} count Total post count.
	 * @param {number} pos Zero-based position of the current post.
	 * @param {string} scope 'all', 'around' or 'fixed'.
	 * @param {number} around Items per side ('around') or total ('fixed').
	 * @param {boolean} ellipsis Whether the all scope may collapse ranges.
	 * @return {Array} Items as {type:'post',pos} or {type:'ellipsis'}.
	 */
	function catpnavPreviewBuildItems(count,pos,scope,around,ellipsis){
		if(count<1||pos<0||pos>=count){
			return [];
		}
		var items=[],start,end,p;
		if('around'===scope){
			start=Math.max(0,pos-around);
			end=Math.min(count-1,pos+around);
			for(p=start;p<=end;p++){
				items.push({type:'post',pos:p});
			}
			return items;
		}
		if('fixed'===scope){
			var size=Math.max(1,around);
			if(size>=count){
				start=0;
				end=count-1;
			}else{
				start=Math.max(0,Math.min(pos-Math.floor((size-1)/2),count-size));
				end=start+size-1;
			}
			for(p=start;p<=end;p++){
				items.push({type:'post',pos:p});
			}
			return items;
		}
		if(!ellipsis||count<=ELLIPSIS_THRESHOLD){
			start=0;
			end=count-1;
			for(p=start;p<=end;p++){
				items.push({type:'post',pos:p});
			}
			return items;
		}
		var keep={};
		for(p=0;p<=Math.min(1,count-1);p++){
			keep[p]=true;
		}
		for(p=Math.max(0,pos-3);p<=Math.min(count-1,pos+3);p++){
			keep[p]=true;
		}
		for(p=Math.max(0,count-2);p<=count-1;p++){
			keep[p]=true;
		}
		var positions=Object.keys(keep).map(Number).sort(function(a,b){
			return a-b;
		});
		var prev=-1;
		positions.forEach(function(p){
			if(prev>=0&&p-prev>1){
				items.push({type:'ellipsis'});
			}
			items.push({type:'post',pos:p});
			prev=p;
		});
		return items;
	}
	/**
	 * Computes the displayed number; mirrors catpnav_display_number().
	 * @param {number} pos Zero-based position.
	 * @param {number} count Total post count.
	 * @param {string} numbering 'ascending' or 'descending'.
	 * @return {number} One-based display number.
	 */
	function catpnavPreviewDisplayNumber(pos,count,numbering){
		return 'descending'===numbering?count-pos:pos+1;
	}
	/**
	 * Resolves the previous/next label; mirrors catpnav_direction_label().
	 * @param {Object} cfg Effective config.
	 * @param {string} which 'prev' or 'next'.
	 * @return {string} Label text.
	 */
	function catpnavPreviewLabel(cfg,which){
		var custom=String('prev'===which?(cfg.prev_label||''):(cfg.next_label||'')).trim();
		if(''!==custom){
			return custom;
		}
		return 'prev'===which?(i18n.prevDefault||'Previous'):(i18n.nextDefault||'Next');
	}
	/**
	 * Builds the optional title span; mirrors catpnav_title_html().
	 * @param {string} title Post title.
	 * @param {boolean} showTitle Whether titles are enabled.
	 * @return {string} Title span or ''.
	 */
	function catpnavPreviewTitleHtml(title,showTitle){
		if(!showTitle){
			return '';
		}
		return ' <span class="catpnav-nav__title">'+catpnavPreviewEsc(title)+'</span>';
	}
	/**
	 * Builds the previous or next neighbour item; mirrors catpnav_neighbour_item_html().
	 * @param {Object} sample Sample data with ids and titles.
	 * @param {number} p Position of the neighbour.
	 * @param {number} count Total post count.
	 * @param {string} which 'prev' or 'next'.
	 * @param {Object} cfg Effective config.
	 * @return {string} List item HTML.
	 */
	function catpnavPreviewNeighbourItemHtml(sample,p,count,which,cfg){
		var label='<span class="catpnav-nav__label">'+catpnavPreviewEsc(catpnavPreviewLabel(cfg,which))+'</span>';
		var number='<span class="catpnav-nav__number">'+catpnavPreviewEsc(String(catpnavPreviewDisplayNumber(p,count,cfg.numbering)))+'</span>';
		var arrow='<span class="catpnav-nav__arrow">'+('prev'===which?'&larr;':'&rarr;')+'</span>';
		var title=catpnavPreviewTitleHtml(sample.titles[sample.ids[p]]||'',cfg.show_title);
		var inner='prev'===which
			?arrow+label+number+title
			:number+title+label+arrow;
		return '<li class="catpnav-nav__item catpnav-nav__item--'+which+'"><a class="catpnav-nav__link" href="#">'+inner+'</a></li>';
	}
	/**
	 * Renders the navigation markup; mirrors catpnav_items_html() over the sample.
	 * @param {Object} sample Sample data: ids (ordered), titles (id to title), currentId.
	 * @param {Object} cfg Effective config.
	 * @return {string} Navigation HTML.
	 */
	function catpnavPreviewItemsHtml(sample,cfg){
		cfg=cfg||{};
		var ids=sample.ids;
		var count=ids.length;
		var pos=ids.indexOf(sample.currentId);
		/* Same tier rule as the config reader and the PHP validator. */
		var n=Math.abs(parseInt(cfg.around,10)||0);
		var around=(n>=1&&n<=20)?n:5;
		var numbering=cfg.numbering||'ascending';
		var root='catpnav-nav catpnav-nav--'+(cfg.layout||'horizontal')+' catpnav-nav--mode-'+(cfg.mode||'numeric');
		var label=catpnavPreviewEsc(i18n.navLabel||'Navigate posts in this category');
		var out='<nav class="'+catpnavPreviewEsc(root)+'" aria-label="'+label+'"><ul class="catpnav-nav__list">';
		if('prevnext'===(cfg.mode||'numeric')){
			if(pos>0){
				out+=catpnavPreviewNeighbourItemHtml(sample,pos-1,count,'prev',cfg);
			}
			if(pos<count-1){
				out+=catpnavPreviewNeighbourItemHtml(sample,pos+1,count,'next',cfg);
			}
			return out+'</ul></nav>';
		}
		var items=catpnavPreviewBuildItems(count,pos,cfg.scope||'all',around,!!cfg.ellipsis);
		if('mixed'===(cfg.mode||'numeric')&&pos>0){
			out+=catpnavPreviewNeighbourItemHtml(sample,pos-1,count,'prev',cfg);
		}
		items.forEach(function(item){
			if('ellipsis'===item.type){
				out+='<li class="catpnav-nav__item catpnav-nav__ellipsis" aria-hidden="true"><span>…</span></li>';
				return;
			}
			var p=item.pos;
			if(p===pos){
				out+='<li class="catpnav-nav__item catpnav-nav__item--current" aria-current="page"><span class="catpnav-nav__number">'+catpnavPreviewEsc(String(catpnavPreviewDisplayNumber(p,count,numbering)))+'</span></li>';
				return;
			}
			var title=catpnavPreviewTitleHtml(sample.titles[ids[p]]||'',!!cfg.show_title);
			out+='<li class="catpnav-nav__item"><a class="catpnav-nav__link" href="#"><span class="catpnav-nav__number">'+catpnavPreviewEsc(String(catpnavPreviewDisplayNumber(p,count,numbering)))+'</span>'+title+'</a></li>';
		});
		if('mixed'===(cfg.mode||'numeric')&&pos<count-1){
			out+=catpnavPreviewNeighbourItemHtml(sample,pos+1,count,'next',cfg);
		}
		return out+'</ul></nav>';
	}
	/**
	 * Builds the synthetic sample for the live preview. Post 1 is the newest
	 * and the padded titles sort in ID order, so the effective direction
	 * depends on the sort key exactly like the real query: date sorts flip on
	 * ASC, title sorts flip on DESC.
	 * @param {Object} cfg Effective config (orderby and order are needed).
	 * @return {Object} Sample data with ids, titles and currentId.
	 */
	function catpnavPreviewSample(cfg){
		cfg=cfg||{};
		var ids=[],i;
		for(i=1;i<=SAMPLE_COUNT;i++){
			ids.push(i);
		}
		var flip='title'===cfg.orderby?'DESC'===cfg.order:'ASC'===cfg.order;
		if(flip){
			ids.reverse();
		}
		var format=i18n.sampleTitle||'Sample Post %d';
		var titles={};
		for(i=1;i<=SAMPLE_COUNT;i++){
			titles[i]=format.replace('%d',('0'+i).slice(-2));
		}
		return {ids:ids,titles:titles,currentId:SAMPLE_CURRENT};
	}
	/**
	 * Reads the effective config from the settings form.
	 * Reads run at document level (mirroring catpnav-admin.js) instead of
	 * form-scoped queries: form-scoped selectors proved unreliable inside
	 * the deferred admin script context.
	 * @return {Object} Config mirroring catpnav_get_settings().
	 */
	function catpnavPreviewConfig(){
		var radio=function(name,fallback){
			var inputs=document.querySelectorAll('input[name="'+FIELD+name+']"]');
			for(var i=0;i<inputs.length;i++){
				if(inputs[i].checked){
					return inputs[i].value;
				}
			}
			return fallback;
		};
		var flag=function(name){
			var inputs=document.querySelectorAll('input[name="'+FIELD+name+']"]');
			for(var i=0;i<inputs.length;i++){
				if('1'===inputs[i].value){
					return !!inputs[i].checked;
				}
			}
			return false;
		};
		var text=function(id){
			var input=document.getElementById(id);
			/* Light mirror of sanitize_text_field: strip tags, collapse whitespace runs, trim. */
			return input?input.value.replace(/<[^>]*>/g,'').replace(/[\r\n\t ]+/g,' ').trim():'';
		};
		var aroundField=document.getElementById('catpnav-around');
		/* Mirrors the sanitize tier: absint, accept 1..20, otherwise fall back to the default. */
		var n=parseInt(aroundField&&aroundField.value,10);
		n=isNaN(n)?0:Math.abs(n);
		var around=(n>=1&&n<=20)?n:5;
		return {
			mode:radio('mode','numeric'),
			scope:radio('scope','all'),
			around:around,
			orderby:radio('orderby','date'),
			order:radio('order','DESC'),
			numbering:radio('numbering','ascending'),
			layout:radio('layout','horizontal'),
			show_title:flag('show_title'),
			ellipsis:flag('ellipsis'),
			prev_label:text('catpnav-prev-label'),
			next_label:text('catpnav-next-label')
		};
	}
	/**
	 * Wires the preview to the settings form.
	 * @return void
	 */
	function catpnavPreviewMount(){
		var box=document.getElementById('catpnav-preview');
		var form=document.querySelector('.catpnav-settings-grid form');
		if(!box||!form){
			return;
		}
		var render=function(){
			var cfg=catpnavPreviewConfig();
			box.innerHTML=catpnavPreviewItemsHtml(catpnavPreviewSample(cfg),cfg);
		};
		render();
		form.addEventListener('input',render);
		form.addEventListener('change',render);
		box.addEventListener('click',function(event){
			var link=event.target&&event.target.closest?event.target.closest('a'):null;
			if(link){
				event.preventDefault();
			}
		});
	}
	/* Mount immediately when the DOM is ready (deferred scripts); otherwise
	 * wait for DOMContentLoaded so the settings form is fully parsed. */
	if('undefined'!==typeof document&&'loading'!==document.readyState&&document.getElementById('catpnav-preview')){
		catpnavPreviewMount();
	}else if('undefined'!==typeof document){
		document.addEventListener('DOMContentLoaded',function(){
			if(document.getElementById('catpnav-preview')){
				catpnavPreviewMount();
			}
		});
	}
	if('object'===typeof module&&module.exports){
		module.exports={
			buildItems:catpnavPreviewBuildItems,
			displayNumber:catpnavPreviewDisplayNumber,
			itemsHtml:catpnavPreviewItemsHtml,
			sample:catpnavPreviewSample
		};
	}
})();
