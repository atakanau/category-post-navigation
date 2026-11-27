/**
 * Category Post Navigation - settings screen behaviour.
 * Element IDs and translatable strings arrive via wp_localize_script (catpnavAdmin).
 */
(function(){
	'use strict';
	var cfg=window.catpnavAdmin||{};
	var i18n=cfg.i18n||{};
	var toggle=document.getElementById(cfg.categoryToggle||'');
	var select=document.getElementById(cfg.categorySelect||'');
	var desc=document.getElementById(cfg.categoryDescription||'');
	/**
	 * Syncs the category select and its description with the filter checkbox.
	 * @return void
	 */
	function catpnavSyncCategories(){
		var on=!!toggle.checked;
		select.disabled=!on;
		select.style.display=on?'':'none';
		if(desc&&i18n.descOn&&i18n.descOff){
			desc.textContent=on?i18n.descOn:i18n.descOff;
			desc.style.display=on?'':'none';
		}
	}
	if(toggle&&select){
		toggle.addEventListener('change',catpnavSyncCategories);
		catpnavSyncCategories();
	}
	var scopes=document.querySelectorAll('input[name="'+(cfg.scopeName||'')+'"]');
	var aroundLabel=document.getElementById(cfg.aroundLabel||'');
	var aroundDesc=document.getElementById(cfg.aroundDesc||'');
	/**
	 * Syncs the items-per-side label and description with the scope radio.
	 * @return void
	 */
	function catpnavSyncAround(){
		var value='';
		scopes.forEach(function(input){
			if(input.checked){
				value=input.value;
			}
		});
		var fixed='fixed'===value;
		if(aroundLabel&&i18n.labelPerSide&&i18n.labelTotal){
			aroundLabel.textContent=fixed?i18n.labelTotal:i18n.labelPerSide;
		}
		if(aroundDesc){
			var text=fixed?i18n.descTotal:('around'===value?i18n.descPerSide:'');
			aroundDesc.textContent=text;
			aroundDesc.style.display=text?'':'none';
		}
	}
	if(scopes.length){
		scopes.forEach(function(input){
			input.addEventListener('change',catpnavSyncAround);
		});
		catpnavSyncAround();
	}
	var presetButtons=document.querySelectorAll('button[data-catpnav-preset]');
	/**
	 * Checks the form radio/checkbox matching a name and value.
	 * @param {string} name Form field name.
	 * @param {string} value Field value to select.
	 * @return void
	 */
	function catpnavCheckField(name,value){
		var input=document.querySelector('input[name="'+name+'"][value="'+value+'"]');
		if(input){
			input.checked=true;
		}
	}
	/**
	 * Applies a quick-start preset from a button's data attributes.
	 * @param {HTMLElement} button Clicked preset button.
	 * @return void
	 */
	function catpnavApplyPreset(button){
		var mode=button.getAttribute('data-mode');
		var scope=button.getAttribute('data-scope');
		var around=button.getAttribute('data-around');
		if(mode){
			catpnavCheckField('catpnav_settings[mode]',mode);
		}
		if(scope){
			catpnavCheckField('catpnav_settings[scope]',scope);
		}
		if(around){
			var field=document.getElementById(cfg.aroundField||'');
			if(field){
				field.value=around;
			}
		}
		if(button.getAttribute('data-ellipsis')){
			catpnavCheckField('catpnav_settings[ellipsis]','1');
		}
		catpnavSyncAround();
		var gridForm=document.querySelector('.catpnav-settings-grid form');
		if(gridForm){
			gridForm.dispatchEvent(new Event('input',{bubbles:true}));
		}
	}
	if(presetButtons.length){
		presetButtons.forEach(function(button){
			button.addEventListener('click',function(){
				catpnavApplyPreset(button);
			});
		});
	}
})();
