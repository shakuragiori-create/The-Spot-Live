<?php

//////////////////////////////////////////////////////////////
//===========================================================
// tampalte-builder.php
//===========================================================
// PAGELAYER
// Inspired by the DESIRE to be the BEST OF ALL
// ----------------------------------------------------------
// Started by: Pulkit Gupta
// Date:	   23rd Jan 2017
// Time:	   23:00 hrs
// Site:	   http://pagelayer.com/wordpress (PAGELAYER)
// ----------------------------------------------------------
// Please Read the Terms of use at http://pagelayer.com/tos
// ----------------------------------------------------------
//===========================================================
// (c)Pagelayer Team
//===========================================================
//////////////////////////////////////////////////////////////

// Are we being accessed directly ?
if(!defined('PAGELAYER_PRO_VERSION')) {
	exit('Hacking Attempt !');
}

// The function shows the page to add template
function pagelayer_builder_template_wizard(){
	
	global $pagelayer;
	
	include_once(PAGELAYER_DIR.'/main/settings.php');
	pagelayer_page_header('Add New Template', 0);
	
	if(!empty($_REQUEST['post'])){
		$posts = get_posts([
			'post_type' => $pagelayer->builder['name'],
			'post_status' => 'any',
			'include' => (int) $_REQUEST['post'],
		]);		
		//print_r($posts);
		
		// Did we find it ?
		if(!empty($posts[0])){
			$post = $posts[0];
			$_post['id'] = $post->ID;
			$_post['type'] = get_post_meta($post->ID, 'pagelayer_template_type', true);
			$_post['conditions'] = get_post_meta( $post->ID, 'pagelayer_template_conditions', true );
			$_post['post_title'] = $post->post_title;
		}
	}
	
	// Fill in defaults if nothing found
	if(empty($_post)){
		$_post['id'] = 0;
		$_post['type'] = '';
		$_post['post_title'] = '';
		$_post['conditions'] = [];
	}
	
?>
<style>

.pagelayer-temp-search-sel-open{
float: right;
font-size: 10px;
padding: 0px;
line-height: 20px;
}

.pagelayer-temp-search-sel-remove{
position: absolute;
right: 20px;
font-size: 11px;
top: 10px;
z-index: 10;
}

.pagelayer-temp-search-sel-span{
display: block;
margin: 2px;
cursor: pointer;
box-sizing: border-box;
border: 1px solid #dbdbdb;
transition: all 0.3s;
font-size: 13px;
padding: 3px;
}

.pagelayer-temp-search-sel-span:hover{
border-color: #4f46e5;
background-color: #eef2ff;
color: #4f46e5;
}

.pagelayer-temp-search-sel-span i{
font-size: 15px;
line-height: 1em;
padding: 3px;
color: #555;
transition: all 0.3s;
vertical-align: middle;
}

.pagelayer-temp-search-sel-span:hover i{
color: #4f46e5;
}

.pagelayer-temp-search-sel{
vertical-align: top
}

.pagelayer-temp-toggle{
cursor:pointer;	
}

.pagelayer-temp-label{
display:block;
margin-bottom:6px;
font-size: 13px;
font-weight: 600;
color: #374151;
}

.pagelayer-temp-fields{
display:inline-block;
position:relative;
margin:0 8px;
vertical-align: bottom;
}

.pagelayer-temp-condition{
border: 1px solid #e2e4e9;
border-radius: 8px;
margin-bottom: 12px;
background: #fff;
overflow: hidden;
box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.pagelayer-temp-tab{
padding: 10px 14px;
background: #f8fafc;
border-bottom: 1px solid #e2e4e9;
display: flex;
align-items: center;
justify-content: space-between;
}

.pagelayer-temp-condition-div{
padding: 18px;
display: flex;
gap: 16px;
flex-wrap: wrap;
align-items: flex-end;
background: #fff;
}

.pagelayer-temp-hide{
display:none;
}

.pagelayer-temp-search-holder{
position:relative;
display:inline-block;
}

.pagelayer-temp-search-div{
cursor: pointer;
padding: 8px 12px;
border: 1.5px solid #d0d5dd;
border-radius: 6px;
background: #fff;
color: #1a1a2e;
font-size: 13px;
vertical-align: middle;
width: 180px;
display: flex;
justify-content: space-between;
align-items: center;
box-sizing: border-box;
transition: border-color 0.15s;
}

.pagelayer-temp-search-div:hover {
border-color: #4f46e5;
}

.pagelayer-temp-search-preview{
position: relative;
margin-right: 5px;
width: 100%;
border: none;
overflow: hidden;
white-space: nowrap;
text-overflow: ellipsis;
}

.pagelayer-temp-search-preview i{
padding-right: 5px;
font-size: 19px;
color: #666;
vertical-align: middle;
}

.pagelayer-temp-search-name{
font-size: 13px;
color: #1a1a2e;
}

.pagelayer-temp-search-selector{
position: absolute;
z-index: 1000;
border: 1px solid #e2e4e9 !important;
background: #fff !important;
box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
padding: 8px;
height: auto;
box-sizing: border-box;
display: none;
width: 100%;
border-radius: 6px;
top: 42px;
}

.pagelayer-temp-search-val{
margin-bottom: 8px !important;
padding: 6px 10px;
border-radius: 6px !important;
border: 1.5px solid #d0d5dd !important;
width: 100%;
box-sizing: border-box;
}

.pagelayer-temp-search-val:focus {
border-color: #4f46e5 !important;
outline: none;
}

.pagelayer-temp-search-list{
width: 100%;
box-sizing: border-box;
padding: 0;
max-height: 180px;
overflow-y: auto;
}

.pagelayer-temp-close{
float:right;
font-size:15px;
color: #9ca3af;
cursor: pointer;
transition: color 0.15s;
}

.pagelayer-temp-close:hover {
color: #ef4444;
}

.pagelayer-temp-pad5{
padding-left:5px;
padding-right:5px;
}

.pagelayer-close-condition{
font-size: 18px;
font-weight: 600;
color: #9ca3af;
cursor: pointer;
transition: color 0.15s;
line-height: 1;
}

.pagelayer-close-condition:hover {
color: #ef4444;
}

.pagelayer-temp-add-new {
background: #eef2ff !important;
color: #4f46e5 !important;
border: 1.5px solid #4f46e5 !important;
border-radius: 6px !important;
padding: 8px 16px !important;
font-size: 13px !important;
font-weight: 600 !important;
display: inline-block;
cursor: pointer;
transition: all 0.2s;
text-shadow: none !important;
box-shadow: none !important;
}

.pagelayer-temp-add-new:hover {
background: #4f46e5 !important;
color: #fff !important;
}

.pagelayer-temp-submit-btn {
background: #4f46e5 !important;
color: #fff !important;
border: none !important;
border-radius: 6px !important;
padding: 10px 24px !important;
font-size: 14px !important;
font-weight: 600 !important;
cursor: pointer;
transition: background 0.2s;
box-shadow: 0 1px 3px rgba(79,70,229,0.3) !important;
text-shadow: none !important;
}

.pagelayer-temp-submit-btn:hover {
background: #4338ca !important;
color: #fff !important;
}

.pagelayer-temp-container{
background: #fff;
border: 1px solid #e2e4e9;
border-radius: 10px;
margin-bottom: 20px;
overflow: hidden;
margin: 0 auto 30px auto;
box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.pagelayer-temp-head{
padding: 16px 22px;
border-bottom: 1px solid #f0f0f1;
font-size: 14.5px;
font-weight: 600;
color: #1a1a2e;
display: flex;
align-items: center;
gap: 10px;
}

.pagelayer-temp-row{
display: block;
padding: 16px 22px;
border-bottom: 1px solid #f5f5f8;
}

.pagelayer-temp-condition-holder{
padding: 16px 22px;
display: none;
}

.pagelayer-temp-submit{
padding: 20px;
background: #fafafa;
border-top: 1px solid #f0f0f1;
}

.pagelayer-temp-container select,
.pagelayer-temp-container input[type="text"] {
border: 1.5px solid #d0d5dd;
border-radius: 6px;
padding: 8px 12px;
font-size: 13px;
color: #1a1a2e;
background: #fff;
transition: border-color 0.15s;
width: 100%;
max-width: 100%;
box-sizing: border-box;
}

.pagelayer-temp-container select:focus,
.pagelayer-temp-container input[type="text"]:focus {
border-color: #4f46e5;
outline: none;
box-shadow: 0 0 0 3px rgba(79,70,229,0.12);
}

.postbox{
margin-bottom:5px;
}

</style>
<?php

	$sel_type = $_post['type'];
	$dis_conditions = $_post['conditions'];
	
	echo '
<div class="pagelayer-temp-container">
	<div class="pagelayer-temp-head">
		<img src="'.PAGELAYER_URL.'/images/pagelayer-logo-19.png'.'" style="vertical-align: top;" /> ';
		
		if(empty($_post['type'])){
			echo __pl('add_temp');
		}else{
			echo __pl('edit_temp').'<span style="float:right" class="pagelayer-temp-edit"><a href="'.esc_url( pagelayer_shortlink($_post['id']).'&pagelayer-live=1' ).'">'.__pl('edit_using').'</a></span>';
		}
	echo '
	</div>
	
	<div class="pagelayer-temp-row">
		<label class="pagelayer-temp-label">'.__('Select Template Type :').'</label>
		<select name="pagelayer_template_type" class="postbox">
			'.pagelayer_create_sel_options( $pagelayer->builder["type"], $sel_type ).'
		</select>
	</div>
	
	<div class="pagelayer-temp-row">
		<label for="pagelayer_lib_title" class="pagelayer-temp-label">'.__('Name :').'</label>
		<input type="text" name="pagelayer_lib_title" size="30" value="'.$_post['post_title'].'" />
	</div>
	
	<div class="pagelayer-temp-condition-holder '.((!empty($pagelayer->builder["type"][$sel_type]['no_condition'])) ? ' pagelayer-temp-hide' : '').'">
		<label class="pagelayer-temp-label">'.__('Template Display Conditions :').'</label>
		<div class="pagelayer-temp-condition-container">';
		
	if( !empty($dis_conditions) ){
		
		foreach($dis_conditions as  $condi){
			
			echo '
			<div class="pagelayer-temp-condition">
				<div class="pagelayer-temp-tab">
					<span class="pagelayer-temp-toggle pagelayer-temp-pad5">&#9776;</span>
					<span>'. __('Display Conditions').'</span>
					<span class="pagelayer-close-condition">&times;</span>
				</div>
				<div class="pagelayer-temp-condition-div">
					<div class="pagelayer-temp-fields">
						<label for="pagelayer_condition_type" class="pagelayer-temp-label">'. __('Action Type :').'</label>
						<select name="pagelayer_condition_type[]" class="postbox">
						'.pagelayer_create_sel_options( $pagelayer->builder["action"], $condi['type'] ).'
						</select>
					</div>
					<div class="pagelayer-temp-fields">					
						<label for="pagelayer_condition_name" class="pagelayer-temp-label"> '. __('Display On : ').'</label>
						<select name="pagelayer_condition_name[]" class="postbox">
						'.pagelayer_create_sel_options( $pagelayer->builder['dispay_on'], $condi['template'] ).'
						</select>
					</div>
					<div class="pagelayer-temp-fields '.(empty($condi['template']) ? 'pagelayer-temp-hide' : '').'">
						<label for="pagelayer_condition_sub_template" class="pagelayer-temp-label"> '. __('Template : ').'</label>
						<select name="pagelayer_condition_sub_template[]" class="postbox">';
					
			if($condi['template'] == 'archives'){
				echo pagelayer_create_sel_options( $pagelayer->builder['archives_templates'], $condi['sub_template'] );
			}else{
				echo pagelayer_create_sel_options( $pagelayer->builder['singular_templates'], $condi['sub_template'] );
			}
			echo '
						</select>
					</div>
					<div class="pagelayer-temp-fields pagelayer-temp-search-sel '. ((empty($condi['sub_template'])) ? ' pagelayer-temp-hide' : '').'">
						<label for="pagelayer_condition_id" class="pagelayer-temp-label">'.__('Specific Items').'</label>';
			
			$req_arr['id'] = $condi['id'];
			$req_arr['filter_type'] =  'post';

			if( is_numeric(strpos($condi['sub_template'] , 'author'))){
				$req_arr['filter_type'] = 'author';
			}
			
			if( is_numeric(strpos($condi['sub_template'] , 'category'))  || is_numeric(strpos($condi['sub_template'] , 'tag')) ){
				$req_arr['filter_type'] = 'taxonomy';
			}
			
			$title_array = pagelayer_builder_get_title($req_arr['filter_type'], $req_arr['id']);
			$id = '';
			$title = '';
			foreach ( $title_array as $tmp_id => $tmp_title ) {
				$id = $tmp_id;
				$title = $tmp_title;
			}
			
			echo '
						<select name="pagelayer_condition_id[]" class="postbox pagelayer-temp-hide">
							<option selected value="'. ((empty($id)) ? '' : $id).'"> '. ((empty($title)) ? __('All') : $title).'</option>
						</select>
						<div class="pagelayer-temp-search-holder">
							<div class="pagelayer-temp-search-div">
								<div class="pagelayer-temp-search-preview">
									<span class="pagelayer-temp-search-name">
										'. ((empty($title)) ? __('All') : $title).'
									</span>
									<span class="pagelayer-temp-close '. ((empty($title)) ? ' pagelayer-temp-hide' : '').'">&times;</span>					
								</div>
								<span class="">&#11206;</span>
							</div>
							<div class="pagelayer-temp-search-selector">
								<input autocomplete="off" type="text" class="pagelayer-temp-search-val" name="search-icon" placeholder="Search">
								<div class="pagelayer-temp-search-list">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';
			
		}
	}
	echo '
		</div>
		<div style="margin:15px 10px;">
			<span class="pagelayer-temp-add-new" >'. __('Add Conditions').'</span>
		</div>
	</div>
	
	<div class="pagelayer-temp-submit">
		<center><button type="submit" class="pagelayer-temp-submit-btn button button-primary">'.__('Save Template').'</button></center>
	</div>
</div>
						
<script type="text/javascript">
pagelayer_cpt = '.json_encode($pagelayer->builder).';
pagelayer_ajax_url = "'.admin_url( 'admin-ajax.php' ).'?";
pagelayer_builder_nonce = "'.wp_create_nonce('pagelayer_builder').'";
</script>';

?>
<script type="text/javascript">
jQuery(document).ready(function(){
	var $ = jQuery;
	
	if(typeof pagelayer !== 'undefined'){
		var $ = pagelayer.$$ || $;
	} 
	
	// The container
	var pl_temp = $(".pagelayer-temp-container");
	var pl_condi = pl_temp.find('.pagelayer-temp-condition-holder');
	
	// Get values from multi-dimensional array by key 
	var pagelayer_multi_array_search = function(array = [], key){
		var array_key = [];
		
		if (key in array) {
			array_key = array[key];
		}
		
		for( var k in array) {
			if (typeof array[k] === 'object' && key in array[k]) {
				array_key = array[k][key];
			}
		}
		
		return array_key;
	}
	
	// Creates the select options
	var create_options = function(opt_array){
		var options = '';
		var tmp_numeric = ''; // Required to handle key 404 in singular templates as json decode puts number first and that spoils our order
		
		for (x in opt_array){
			
			// Single item
			if(typeof opt_array[x] == 'string'){
				options += option(x, opt_array[x]);
				
				if(tmp_numeric.length > 0){
					options += tmp_numeric;
					tmp_numeric = '';
				}
			
			// If is array then we get the label string
			}else if(typeof opt_array[x] == 'object'){
				if('label' in opt_array[x]){
			
					if(x == 404){
						tmp_numeric = option(x, opt_array[x]['label']);
						continue;
					}
			
					options += option(x, opt_array[x]['label']);

				// Groups
				}else{
					options += '<optgroup label="'+x+'">';
					options += create_options(opt_array[x]);					
					options += '</optgroup>';
				}
			}
		}
		
		return options;
		
	}
	
	var option = function(val, lang){
		return '<option value="'+val+'">'+lang+'</option>';
	}
	
	var html_condi = '<div class="pagelayer-temp-condition">'+
		'<div class="pagelayer-temp-tab">'+
			'<span class="pagelayer-temp-toggle pagelayer-temp-pad5">&#9776;</span>'+
			'<span>'+<?php echo "'".__('Display Conditions')."'";?>+'</span>'+
			'<span class="pagelayer-close-condition">&times;</span>'+
		'</div>'+
		'<div class="pagelayer-temp-condition-div">'+
			'<div class="pagelayer-temp-fields">'+
				'<label for="pagelayer_condition_type" class="pagelayer-temp-label">'+<?php echo "'".__('Action Type')."'";?>+'</label>'+
				'<select name="pagelayer_condition_type[]" class="postbox">'+
					create_options(pagelayer_cpt["action"])+
				'</select>'+
			'</div>'+
			' <div class="pagelayer-temp-fields">'+
				'<label for="pagelayer_condition_name" class="pagelayer-temp-label">'+<?php echo "'".__('Display On')."'";?>+'</label>'+
				'<select name="pagelayer_condition_name[]" class="postbox">'+
					create_options(pagelayer_cpt["dispay_on"])+
				'</select>'+
			'</div>'+
			' <div class="pagelayer-temp-fields pagelayer-temp-hide">'+
				'<label for="pagelayer_condition_sub_template" class="pagelayer-temp-label">'+<?php echo "'".__('Template')."'";?>+'</label>'+
				'<select name="pagelayer_condition_sub_template[]" class="postbox">'+
				'</select>'+
			'</div>'+
			' <div class="pagelayer-temp-fields pagelayer-temp-hide pagelayer-temp-search-sel">'+
				'<label for="pagelayer_condition_id" class="pagelayer-temp-label">'+<?php echo "'".__('Specific Items')."'";?>+'</label>'+
				'<select name="pagelayer_condition_id[]" class="postbox pagelayer-temp-hide">'+
					'<option selected value="">All</option>'+
				'</select>'+
				'<div class="pagelayer-temp-search-holder">'+
					'<div class="pagelayer-temp-search-div">'+
						'<div class="pagelayer-temp-search-preview">'+
							'<span class="pagelayer-temp-search-name">All</span>'+
							'<span class="pagelayer-temp-close pagelayer-temp-hide">&times;</span>'+
						'</div>'+
						'<span class="">&#11206;</span>'+
					'</div>'+
					'<div class="pagelayer-temp-search-selector">'+
						'<input autocomplete="off" type="text" class="pagelayer-temp-search-val" name="search-icon" placeholder="Search">'+
						'<div class="pagelayer-temp-search-list">'+
						'</div>'+
					'</div>'+
				'</div>'+
			'</div>'+
		'</div>'+
	'</div>';
	
	// Removes a condition
	var close_tab = function(){
		var condi = pl_temp.find(".pagelayer-temp-condition");
		condi.find(".pagelayer-close-condition").click(function(){
			jQuery(this).closest(".pagelayer-temp-condition").remove();
		});
	};
	
	// Toggle a condition content
	var toggle_tab = function(){
		var condi = pl_temp.find(".pagelayer-temp-condition");
		condi.find(".pagelayer-temp-tab").unbind("click");
		condi.find(".pagelayer-temp-tab").click(function(){
			jQuery(this).closest(".pagelayer-temp-condition").find(".pagelayer-temp-condition-div").toggle();
		});
	};
	
	// Opens the search selector
	var open_search = function(){
		var el = pl_temp.find('.pagelayer-temp-search-div')
		el.unbind('click');
		el.on('click', function(){
			jQuery(this).parent().find('.pagelayer-temp-search-selector').slideToggle();
		});	
	};
	
	var pagelayer_set_inter; // A timer
	
	// Get closest select box by name in closest "pagelayer-temp-condition" class
	var pagelayer_get_closest_sel = function(jEle, name){
		return jEle.closest(".pagelayer-temp-condition").find("[name='"+name+"']");
	}
	
	// Handle search of values
	var search_list = function(){
		
		pl_temp.find('.pagelayer-temp-search-val').on('keyup', function(){
			var v = this.value;
			var iEle = jQuery(this);
			var template_name, ele_name, template_name_array;
			//v = v.replace(/\s+/g, '-');
			
			template_name = pagelayer_get_closest_sel(iEle, 'pagelayer_condition_name[]').val();
			ele_name =  pagelayer_get_closest_sel(iEle, 'pagelayer_condition_sub_template[]').val();
			template_name_array = pagelayer_multi_array_search(pagelayer_cpt[template_name+'_templates'], ele_name);
			
			data_array = {
				pagelayer_nonce: pagelayer_builder_nonce,
				search : v,
				filter_type : template_name_array['filter_type'] || 'post',
				object_type : template_name_array['object_type'] || 'post',
			};
			
			clearTimeout(pagelayer_set_inter);
			pagelayer_set_inter = setTimeout(function () {
				v = v.toLowerCase();
				
				jQuery.ajax({
					url: pagelayer_ajax_url+'&action=pagelayer_search_ids',
					type: 'post',
					data : data_array,
					success:function(response){
						iEle.next().html(response);
						//console.log(response);
					}

				});
				
				//row.find('.pagelayer-temp-search-list').empty().html(span);
			}, 200);
			
		});
		
		// Handle click within the ID selector
		pl_temp.find('.pagelayer-temp-search-selector').unbind('click');
		pl_temp.find('.pagelayer-temp-search-selector').on('click', function(e){
			
			var jEle = jQuery(e.target);
			var val = jEle.attr('value');
			var text = jEle.text();
			
			if(val.length < 1 || text.length < 1){
				return false;
			}
			
			// Set the ID in this list
			var row = jEle.closest('.pagelayer-temp-search-sel');
			row.find('[name="pagelayer_condition_id[]"]').html('<option selected value="'+ val +'">'+ text +'</option>');
			row.find('.pagelayer-temp-search-preview .pagelayer-temp-search-name').text(text).next().show();
			row.find('.pagelayer-temp-search-selector').slideUp();
			
			return false; 
			
		});
		
		pl_temp.find('.pagelayer-temp-close').click(function(e){
			e.stopPropagation();
			var sHold = jQuery(this).closest('.pagelayer-temp-search-sel');
			sHold.find('[name="pagelayer_condition_id[]"]').html('<option selected value="">All</option>');
			sHold.find('.pagelayer-temp-search-preview .pagelayer-temp-search-name').text('All').next().hide();
		});
	};
	
	// Whenever a condition is to be initialized
	var reinit_conditions = function(){
		
		close_tab();
		toggle_tab();
		open_search();
		search_list();
		
		pl_temp.find('select').unbind('change');
		pl_temp.find('select').on('change', function(event, triggerEle){
			var sEle = jQuery(this);
			var sVal = sEle.val();
			var condiEle = triggerEle || pl_condi;
			//console.log(sVal);
			
			// Hide search box if any select is changed
			var temp_search = jQuery('.pagelayer-temp-search-selector');
			if(temp_search.is(':visible') || jQuery('.pagelayer-temp-search-preview > .pagelayer-temp-close').is(':visible')){
				temp_search.hide();
				temp_search.find('.pagelayer-temp-search-list').empty();
				temp_search.find('.pagelayer-temp-search-val').val('');
			}
			
			switch(sEle.attr('name')){
				case 'pagelayer_template_type':
					var no_condition = pagelayer_cpt["type"][sVal]['no_condition'] || false;
					
					if( no_condition ){
						pl_condi.hide();
					}else if(!pl_condi.is(':visible')){
						pl_condi.show();
					}
					
					condiEle.find("[name='pagelayer_condition_name[]']").each(function(){
						var condi_name = jQuery(this);
						var need_selection = pagelayer_multi_array_search(pagelayer_cpt["type"], sVal);
						if(typeof need_selection === 'object' && 'need_selection' in need_selection){
							if(need_selection['need_selection'] != condi_name.val() ){
								condi_name.val(need_selection['need_selection']).trigger('change');
							}
							condi_name.css('pointer-events', 'none');
						}else if(condi_name.css('pointer-events') == 'none'){
							condi_name.css('pointer-events', 'all');
						}
						
					});
					
					break;
					
				case 'pagelayer_condition_type[]':
					//console.log(sEle.val());
					break;
					
				case 'pagelayer_condition_name[]':
					var ele_name = pagelayer_get_closest_sel(sEle, 'pagelayer_condition_sub_template[]');
					//console.log(sEle.val());
					
					if(sVal.length == 0){
						ele_name.closest(".pagelayer-temp-fields").addClass('pagelayer-temp-hide');
					}else{
						if(pagelayer_cpt[sVal+"_templates"]){
							ele_name.html(create_options(pagelayer_cpt[sVal+"_templates"]));
							ele_name.closest(".pagelayer-temp-fields").removeClass('pagelayer-temp-hide');
						}	
					}
					
					ele_name.trigger('change');
					break;
					
				case 'pagelayer_condition_sub_template[]':
					// Get selected template name
					var template_name = pagelayer_get_closest_sel(sEle, 'pagelayer_condition_name[]').val();
					var ele_name = pagelayer_get_closest_sel(sEle, 'pagelayer_condition_id[]');
					var template_name_array = pagelayer_multi_array_search(pagelayer_cpt[template_name+'_templates'], sVal);
					
					if(sVal.length == 0 || template_name_array['no_id_section'] || !sEle.is(':visible')){
						ele_name.closest(".pagelayer-temp-fields").addClass('pagelayer-temp-hide');
					}else{
						ele_name.closest(".pagelayer-temp-fields").removeClass('pagelayer-temp-hide');	
					}
					ele_name.closest(".pagelayer-temp-fields").find('.pagelayer-temp-close').click();
					break;
					
				case 'pagelayer_condition_id[]':
					//console.log(sEle.val());						
				break;	
			}
		});
		
	}
	
	reinit_conditions();
	
	<?php
	
	if(!empty($_post['id'])){
		$sub_template_sel = "'pagelayer_condition_sub_template[]'";
		echo '
	//reinit_conditions();
	pl_condi.show();
	pl_temp.find("select[name=pagelayer_template_type]").trigger("change");
	pl_temp.find("select[name='.$sub_template_sel.']").trigger("change");
	pl_temp.find("select[name=pagelayer_template_type]").prop("disabled", true);
	//pl_condi.find("select").trigger("change")';
	
	}
	
	?>

	// On click add more condition
	pl_temp.find(".pagelayer-temp-add-new").click(function(){
		pl_condi.show();
		var hEle = jQuery(html_condi);
		pl_temp.find('.pagelayer-temp-condition-container').append(hEle);
		reinit_conditions();
		pl_temp.find('select[name="pagelayer_template_type"]').trigger('change', [hEle]);
	});
	
	// Save the template
	pl_temp.find(".pagelayer-temp-submit-btn").click(function(e){
		e.preventDefault();
		var data = pl_temp.find('input, select, textarea').serialize();
		//alert(data);
		
		jQuery.ajax({
			url: pagelayer_ajax_url+'&action=pagelayer_save_template&postID='+ <?php echo "'".$_post['id']."'";?>,
			type: 'post',
			data : 'pagelayer_nonce='+ pagelayer_builder_nonce +'&'+ data,
			success: function(response, status, xhr){
				var obj = jQuery.parseJSON(response);
				//alert(obj);
				if(obj['error']){
					alert(obj['error']);
				}else{
					alert(obj['success']);
					var hasPost = new window.URLSearchParams(window.location.search).has('post');
					if(!hasPost){
						window.location.replace(window.location+'&post='+obj['id']);
					}
				}
			},			
			error: function(errorThrown){
				alert(errorThrown);
				console.log(errorThrown);
			}
		});
	});		

});
</script>
	<?php
	pagelayer_page_footer();
}

// Get Title from ID
function pagelayer_builder_get_title($type, $ids = array()){
	$ids = (array) $ids;
	$sel_title = [];

	switch ( $type ) {
		case 'taxonomy':
			$terms = get_terms([
				'include' => $ids,
				'hide_empty' => false,
			]);

			global $wp_taxonomies;
			foreach ( $terms as $term ) {
				$sel_title[ $term->term_taxonomy_id ] = $term->name ;
			}
			break;

		case 'post':
			$query = new \WP_Query([
				'post_type' => 'any',
				'post__in' => $ids,
				'posts_per_page' => -1,
				'post_status' => 'any',
			]);
		
			foreach ( $query->posts as $post ) {
				$sel_title[ $post->ID ] = $post->post_title;
			}
			break;

		case 'author':
			$query_params = [
				'capability' => array( 'edit_posts' ),
				'fields' => ['ID', 'display_name'],
				'include' => $ids,
			];
			
			// Capability queries were only introduced in WP 5.9.
			if( version_compare( $GLOBALS['wp_version'], '5.9-alpha', '<' ) ){
				$args['who'] = 'authors';
				unset( $args['capability'] );
			}

			$user_query = new \WP_User_Query( $query_params );

			foreach ( $user_query->get_results() as $author ) {
				$sel_title[ $author->ID ] = $author->display_name;
			}
			break;
	}

	return $sel_title;
}

// Append the Popup templates
function pagelayer_builder_popup_append(){
	global $pagelayer;
	
	if(!empty($GLOBALS['pagelayer_builder_popup_append_called'])){
		return;
	}
	
	$GLOBALS['pagelayer_builder_popup_append_called'] = 1;
	
	// Render the multiple Popups
	foreach($pagelayer->template_popup_ids as $id => $priority){
	
		$bLoadString = '';
		
		$content = pagelayer_get_post_content($id);
		
		// For popup before loading option
		// First check that att is placed or not. Then scan and extract the value of id and insert it into bLoadString variable
		if(strpos($content, 'data-trig_before_load') !== false){			
			preg_match('#pagelayer-id=([^\s]+)#', $content, $matches);
			$bLoadString = 'pagelayer-popup-Shown="1" style="display: flex;" pagelayer-popup-id="'.$matches[1].'"';
		}
	
		echo '<div class="pagelayer-popup-modal" '.$bLoadString.'>
			<div class="pagelayer-popup-modal-content">
				<div class="pagelayer-popup-content">
					'.$content.'
				</div>
			</div>
		</div>';
	}
}

function pagelayer_builder_export($type = ''){
	
	global $pagelayer;
	
	// Load the templates
	pagelayer_builder_load_templates();
	
	$data['page'] = [];
	
	// Load the other posts
	foreach($pagelayer->settings['post_types'] as $type){
	
		// Make the query
		$type_query = new WP_Query(['post_type' => $type, 'status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'name', 'order' => 'ASC']);
		$data[$type] = $type_query->posts;
		
	}

	include_once(PAGELAYER_DIR.'/main/settings.php');
	pagelayer_page_header('Export Theme', 0);
	
	$nonce = wp_create_nonce('pagelayer_builder');
	
	echo '
	<div class="pagelayer-premium-alert">
		<span class="pagelayer-alert-icon dashicons dashicons-info"></span>
		<div class="pagelayer-alert-content">
			' . __('With this wizard you can export Pagelayer Template(s) (and pages) into a theme folder. This theme folder can be distributed as a theme and can be used by any Pagelayer user.', 'pagelayer-pro') . '
		</div>
	</div>';
	
	// Make two tables
	echo '
<script>
pagelayer_ajax_url = "'.esc_url(admin_url( 'admin-ajax.php' )).'?";

function pagelayer_checkbox(ele, cl){
	var jEle = jQuery(ele);
	jQuery(cl).prop("checked", jEle.prop("checked"));
}

function pagelayer_export_template(){
	var data = jQuery("#pagelayer_export_template_form").serialize();
	
	jQuery.ajax({
		dataType: "json",
		url: pagelayer_ajax_url+"&action=pagelayer_export_template",
		type: "post",
		data: data,
		success:function(response){
			if("success" in response){
				alert(response["success"]);
			}
			
			if("error" in response){
				alert(response["error"]);
			}
		}
	});
	
	return false;
}

jQuery(function($){
	var btn = "<button onclick=\'return pagelayer_export_template()\' class=\'pl-save-btn\'>" + "' . esc_attr__('Export Template Files', 'pagelayer-pro') . '" + "</button>";
	$(".pagelayer-header-actions").append(btn);
});
</script>	

<form id="pagelayer_export_template_form">
<input type="hidden" name="pagelayer_nonce" value="'.esc_attr($nonce).'">
<div class="pagelayer-export-grid">
	<div class="pagelayer-export-col pagelayer-export-col-templates">
		<div class="pagelayer-settings-card">
			<div class="pagelayer-settings-card-header">
				<h3>Pagelayer Templates</h3>
			</div>
			<div class="pagelayer-export-table-wrap">
				<table cellspacing="0" cellpadding="0" border="0" width="100%" class="pagelayer-export-table">
				<thead>
					<tr>
						<th width="30"><input type="checkbox" onclick="pagelayer_checkbox(this, \'.pagelayer_temp_cb\');"></th>
						<th>Title</th>
						<th>Type</th>
						<th>Display On</th>
					</tr>
				</thead>
				<tbody>';

		foreach($pagelayer->templates as $k => $v){
			
			$type = get_post_meta($v->ID, 'pagelayer_template_type', true);
			
			$dis_conditions = get_post_meta( $v->ID, 'pagelayer_template_conditions', true );
			$dis_html = 'None';
			
			if( !empty($dis_conditions) ){
				$dis_html = '';
				foreach($dis_conditions as $condi){
					$dis_html .= '<span>';
					
					if(isset($condi['template'])){
						$template = pagelayer_multi_array_search($pagelayer->builder['dispay_on'], $condi['template']);
						
						if(is_array($template) && array_key_exists('label', $template)){
							$template = $template['label'];
						}
						
						$dis_html .= esc_html($template);
					}
					
					if(isset($condi['sub_template'])){
						$sub_template = pagelayer_multi_array_search($pagelayer->builder[$condi['template'].'_templates'], $condi['sub_template']);
						
						if(is_array($sub_template) && array_key_exists('label', $sub_template)){
							$sub_template = $sub_template['label'];
						}
						
						if(!empty($sub_template)){
							$dis_html .= ' &gt; '. esc_html($sub_template);
						}
					}
					
					if(!empty($condi['id'])){
						$dis_html .= ' &gt; #'. intval($condi['id']);
					}
					
					$dis_html .= '</span><br/>';
				}
			}
			
			$type_label = !empty($pagelayer->builder['type'][$type]['label']) ? $pagelayer->builder['type'][$type]['label'] : $pagelayer->builder['type'][$type];
			
			echo '
					<tr>
						<td><input type="checkbox" class="pagelayer_temp_cb" name="templates['.intval($v->ID).']"></td>
						<td>'.esc_html($v->post_title).'</td>
						<td>'.esc_html($type_label).'</td>
						<td>'.$dis_html.'</td>
					</tr>';
			
		}

	echo '
				</tbody>
				</table>
			</div>
		</div>
	</div>';
	
	$pt_objects = get_post_types(['public' => true], 'objects');
	
	foreach($data as $type => $d){
		
		if(empty($pt_objects[$type])){
			continue;
		}
		
		$pt_name = esc_html($pt_objects[$type]->labels->name);
		
		echo '
	<div class="pagelayer-export-col pagelayer-export-col-posts">
		<div class="pagelayer-settings-card">
			<div class="pagelayer-settings-card-header">
				<h3>'.$pt_name.'</h3>
			</div>
			<div class="pagelayer-export-table-wrap">
				<table cellspacing="0" cellpadding="0" border="0" width="100%" class="pagelayer-export-table">
				<thead>
					<tr>
						<th width="30"><input type="checkbox" onclick="pagelayer_checkbox(this, \'.pagelayer_'.esc_attr($type).'_cb\');"></th>
						<th>Title</th>
						<th width="80">Type</th>
					</tr>
				</thead>
				<tbody>';

		foreach($data[$type] as $k => $v){
			
			echo '
					<tr>
						<td><input type="checkbox" class="pagelayer_'.esc_attr($type).'_cb" name="'.esc_attr($type).'['.intval($v->ID).']"></td>
						<td>'.esc_html($v->post_title).'</td>
						<td>'.$pt_name.'</td>
					</tr>';
			
		}

		echo '
				</tbody>
				</table>
			</div>
		</div>
	</div>';
	
	}
	
	echo '
</div>
</form>';
	
	pagelayer_page_footer();
}