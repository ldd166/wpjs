<?php
class WPJAM_Field{
	public static $field_tmpls = [];

	public static function get_field_value($field, $args=[]){
		$args	= wp_parse_args($args, [
			'data_type'		=> 'form',
			'option_name'	=> '',
			'data'			=> [],
			'id'			=> 0
		]);

		$type	= $field['type'];

		if(is_admin()){
			$default	= $field['value'] ?? null;
		}else{
			$default	= $field['default'] ?? null;
		}

		if($type == 'view' && !is_null($default)){
			return $default;
		}

		$name	= $field['name'] ?? $field['key'];

		if(preg_match('/\[([^\]]*)\]/', $name)){
			$name_arr	= wp_parse_args($name);
			$name		= current(array_keys($name_arr));
		}else{
			$name_arr	= [];
		}

		$data_type	= $args['data_type'];
		$id			= $args['id'];
		$value		= null;

		if($data_type == 'form'){
			$data	= $args['data'];

			if($data && isset($data[$name])){
				$value	= $data[$name];
			}
		}elseif($data_type == 'option'){
			$option_name	= $args['option_name'];

			if($option_name){
				$value	= wpjam_get_setting($option_name, $name);
			}else{
				$value	= get_option($name, null);
			}
		}elseif($data_type == 'post_meta'){
			if($id && metadata_exists('post', $id, $name)){
				$value	= get_post_meta($id, $name, true);
			}
		}elseif($data_type == 'term_meta'){
			if($id && metadata_exists('term', $id, $name)){
				$value	= get_term_meta($id, $name, true);
			}
		}

		if($name_arr){
			$name_arr	= current(array_values($name_arr));
			
			do{
				$sub_name	= current(array_keys($name_arr));
				$name_arr	= current(array_values($name_arr));
				$value		= $value[$sub_name] ?? null;
			}while ($name_arr && $value);
		}

		if(is_null($value)){
			return $default;
		}

		return $value;
	}

	public static function fields_callback($fields, $args=[]){
		extract(wp_parse_args($args, array(
			'fields_type'	=> 'table',
			'data_type'		=> 'form',
			'option_name'	=> '',
			'data'			=> [],
			'id'			=> 0,
			'is_add'		=> false,
			'item_class'	=> '',
			'echo'			=> true,
		)));

		$item_class	= $item_class ? ' class="'.$item_class.'"' : ''; 

		$output	= '';

		if($fields_type == 'list'){
			$output	.= '<ul>';
		}elseif($fields_type == 'table'){
			$output	.= '<table class="form-table" cellspacing="0">';
			$output	.= '<tbody>';
		}
		
		foreach($fields as $key => $field){ 

			if(isset($field['show_admin_column']) && ($field['show_admin_column'] === 'only')){
				continue;
			}

			$field['key']	= $key;
			$field['name']	= $field['name'] ?? $key;

			if($field['type'] == 'fieldset'){
				$fieldset_type	= $field['fieldset_type'] ?? 'single';

				if(!empty($field['fields'])){
					foreach ($field['fields'] as $sub_key => &$sub_field){
						if($sub_field['type'] == 'fieldset'){
							wp_die('fieldset 不允许内嵌 fieldset');
						}

						$sub_field['name']	= $sub_field['name'] ?? $sub_key;

						if($fieldset_type == 'array'){
							$sub_field['key']	= $key.'_'.$sub_key;
							$sub_field['name']	= $field['name'].self::generate_sub_field_name($sub_field['name']);	
						}else{
							$sub_field['key']	= $sub_key;
						}

						if(!$is_add){
							$sub_field['value']	= self::get_field_value($sub_field, $args);
						}
						
						if($data_type == 'option' && $option_name){
							$sub_field['name']	= $option_name.self::generate_sub_field_name($sub_field['name']);;
						}	
					}
				}
			}else{
				if(!$is_add){
					$field['value']	= self::get_field_value($field, $args);
				}

				if($data_type == 'option' && $option_name){
					$field['name']	= $option_name.self::generate_sub_field_name($field['name']);
				}
			}

			$field['html']	= self::get_field_html($field);
			$field['title']	= $field['title']??'';

			if($field['title'] && $field['type']!='fieldset'){
				$field['title']	= '<label for="'.$key.'">'.$field['title'].'</label>';
			}

			if($field['type'] == 'hidden'){
				$output	.= $field['html'];
			}else{
				if($fields_type == 'list'){
					$output	.= '<li'.$item_class.' id="li_'.$key.'">'.$field['title'].$field['html'].'</li>';	
				}elseif($fields_type == 'tr' || $fields_type == 'table'){
					$output	.= '<tr'.$item_class.' valign="top" id="tr_'.$key.'">';
					if($field['title']) {
						$output	.= '<th scope="row">'.$field['title'].'</th>';
						$output	.= '<td>'.$field['html'].'</td>';
					} else {
						$output	.= '<td colspan="2" style="padding:12px 10px 12px 0;">'.$field['html'].'</td>';
					}
					$output	.= '</tr>';
				}elseif($fields_type == 'div'){
					$output	.= '<div'.$item_class.' id="div_'.$key.'">';
					$output	.= $field['title'];
					$output	.= $field['html'];
					$output	.= '</div>';
				}else{
					$output	.= $field['title'].$field['html'];
				}
			}
		}

		if($fields_type == 'list'){
			$output	.= '</ul>';
		}elseif($fields_type == 'table'){
			$output	.= '</tbody>';
			$output	.= '</table>';
		}

		if(wp_doing_ajax()){ 
			$output	.= self::get_field_tmpls();
		}

		if($echo){
			echo $output;
		}else{
			return $output;
		}
	}

	public static function get_field_tmpls(){
		$output = '';
		if(self::$field_tmpls){ 
			foreach (self::$field_tmpls as $tmpl_id => $field_tmpl) {
				$output .= "\n".'<script type="text/html" id="tmpl-wpjam-'.$tmpl_id.'">'."\n";
				$output .=  $field_tmpl."\n";
				$output .=  '</script>'."\n";
			}

			self::$field_tmpls	= [];
		}

		return $output;
	}

	public static function parse_field($field){
		$field['key']	= $field['key'] ?? '';
		$field['name']	= $field['name'] ?? $field['key'];
		$field['value']	= isset($field['value']) ?stripslashes_deep($field['value']) : '';

		if(empty($field['type'])){
			$field['type']	= 'text';
		}elseif($field['type'] == 'br'){
			$field['type']	= 'view';
		}else{
			$field['type']	= str_replace(['mulit','multi','_'], ['mu','mu','-'], $field['type']);	// 各种 multi 写错，全部转换成 mu-
		}

		if(!empty($field['data-type'])){
			// $field['data_type']	= $field['data-type'];	// data-type 转换成 data-type，所有自定义属性，都要写成 下划线
			unset($field['data-type']);
			trigger_error('data-type '.var_export($field, true));
		}

		if(isset($field['options']) && !is_array($field['options'])){
			if(strpos($field['options'], '&')){			// url query string 模式
				$field['options']	= wp_parse_args($field['options']);
			}elseif(strpos($field['options'], '=>')){	// 自创的，不够标准，不推荐使用
				trigger_error('using => in options');
				$options	= explode(",", $field['options']);
				$options	= array_map(function($option){
					$option	= explode("=>", $option);
					return array('k'=>trim($option[0]), 'v'=>trim($option[1]));
				}, $options);

				$field['options']	= wp_list_pluck($options, 'v', 'k');
			}
		}

		$default_classes = array(
			'textarea'	=> 'large-text',
			'checkbox'	=> '',
			'radio'		=> '',
			'select'	=> '',
			'color'		=> '',
			'date'		=> ''
		);

		$field['class']	= $field['class'] ?? ($default_classes[$field['type']] ?? 'regular-text');

		if($field['description'] = $field['description']??''){
			if($field['type'] == 'view' || $field['type'] == 'hr'){
				$field['description']	= '';
			}elseif($field['type'] == 'checkbox' || $field['type']=='mu-text'){
				$field['description']	= ' <span class="description">'.$field['description'].'</span>';
			}elseif($field['class'] != 'large-text' && $field['class'] != 'regular-text'){
				$field['description']	= ' <span class="description">'.$field['description'].'</span>';
			}else{
				$field['description']	= '<br /><span class="description">'.$field['description'].'</span>';
			}
		}
		
		$extra	= $field['extra'] ?? '';
		foreach ($field as $attr_key => $attr_value) {
			if(is_numeric($attr_key)){
				$attr_key	= $attr_value = strtolower(trim($attr_value));
				$field[$attr_key]	= $attr_value;
			}else{
				$attr_key	= strtolower(trim($attr_key));
			}
			
			if(!in_array($attr_key, ['type','name','title','key','description','class','value','default','options','fields','size','show_admin_column','sortable_column','taxonomies','taxonomy','settings','data_type','post_type','item_type','total','max_items','field_callback','field_validate','sanitize','validate','column_callback','sep','extra'])){

				if(is_object($attr_value) || is_array($attr_value)){
					trigger_error($attr_key.' '.var_export($attr_value, true));
				}else{
					$extra .= ' '.$attr_key.'="'.esc_attr($attr_value).'"';
				}
			}
		}

		$field['extra'] = $extra;

		return $field;
	}

	public static function get_field_html($field){
		$field	= self::parse_field($field);
		extract($field);

		$del_item_button	= ' <a href="javascript:;" class="button del-item">删除</a> ';
		$sortable_dashicons	= ' <span class="dashicons dashicons-menu"></span>';

		$field_html	= '';

		if($type == 'view'){
			if(!empty($field['options'])){
				$value		= $value ?: 0;
				$field_html	= $field['options'][$value] ?? '';
			}else{
				$field_html	= $value;
			}
		}elseif($type == 'hr'){
			$field_html	= '<hr />';
		}elseif($type == 'color'){
			$field_html	= self::get_input_field_html('text', $name, $key, $class.' color', $value, '', $description);
		}elseif($type == 'range'){
			$extra		.=	' onchange="jQuery(\'#'.$key.'_span\').html(jQuery(\'#'.$key.'\').val());"';
			$field_html	= self::get_input_field_html($type, $name, $key, $class, $value, $extra).' <span id="'.$key.'_span">'.$value.'</span>';
		}elseif($type == 'checkbox'){
			if(!empty($field['options'])){
				$sep	= $field['sep'] ?? '&emsp;';
				foreach ($field['options'] as $option_value => $option_title){ 
					if($value && is_array($value) && in_array($option_value, $value)){
						$checked	= " checked='checked'";
					}else{
						$checked	= '';
					}

					$field_html .= self::get_input_field_html($type, $name.'[]', $key.'_'.$option_value, $class, $option_value, $extra.$checked, $option_title).$sep;
				}

				$field_html = '<div id="'.$key.'_options">'.$field_html.'</div>'.$description;
			}else{
				$extra		.= checked('1', $value, false);
				$field_html	= self::get_input_field_html($type, $name, $key, $class, '1', $extra, $description);
			}
		}elseif($type == 'radio'){
			if(!empty($field['options'])){
				$sep	= $field['sep'] ?? '&emsp;';
				$value	= $value ?: current(array_keys($field['options']));

				foreach ($field['options'] as $option_value => $option_title) {
					$data_attr	= '';

					if(is_array($option_title)){
						foreach ($option_title as $k => $v) {
							if($k != 'title' && !is_array($v)){
								$data_attr .= ' data-'.$k.'='.$v;
							}
						}

						$option_title	= $option_title['title'];
					}

					$field_html	.= '<label '.$data_attr.' id="label_'.$key.'_'.$option_value.'" for="'.$key.'_'.$option_value.'"><input type="radio" name="'.$name.'" id="'.$key.'_'.$option_value.'" class="'.$class.'" value="'.$option_value.'" '.$extra.checked($option_value, $value, false).' />'.$option_title.'</label>'.$sep;
				}

				$field_html = '<div id="'.$key.'_options">'.$field_html.'</div>';

				if($description){
					$field_html .= '<br />'.$description;
				}
			}
		}elseif($type == 'select'){
			if(!empty($field['options'])){
				foreach ($field['options'] as $option_value => $option_title){ 
					$data_attr	= '';

					if(is_array($option_title)){
						foreach ($option_title as $k => $v) {
							if($k != 'title' && !is_array($v)){
								$data_attr .= ' data-'.$k.'='.$v;
							}
						}

						$option_title	= $option_title['title'];
					}

					$field_html .= '<option value="'.esc_attr($option_value).'" '.selected($option_value, $value, false).$data_attr.'>'.$option_title.'</option>';
				}
			}

			$field_html	= '<select name="'.esc_attr($name).'" id="'. esc_attr($key).'" class="'.esc_attr($class).'" '.$extra.' >'.$field_html.'</select>' .$description;
		}elseif($type == 'file'){
			if(current_user_can('upload_files')){
				$field_html	= self::get_input_field_html('url', $name, $key, $class, $value, $extra, $description).' <input type="button" item_type="" class="wpjam-file button" value="选择文件">';
			}
		}elseif($type == 'image'){
			if(current_user_can('upload_files')){
				$field_html	= self::get_input_field_html('url', $name, $key, $class, $value, $extra, '').' <input type="button" item_type="image" class="wpjam-file button" value="选择图片">'.$description;
			}
		}elseif($type == 'img'){
			if(current_user_can('upload_files')){
				$item_type	= $field['item_type']??'';
				$size		= $field['size']??'400x0';

				$img_style	= '';
				$thumb_args	= '';
				if(isset($field['size'])){
					$size	= wpjam_parse_size($field['size']);

					if($size['width'] > 600 || $size['height'] > 600){
						if($size['width'] > $size['height']){
							$size['height']	= intval(($size['height'] / $size['width']) * 600);
							$size['width']	= 600;
						}else{
							$size['width']	= intval(($size['width'] / $size['height']) * 600);
							$size['height']	= 600;
						}
					}

					if($size['width']){
						$img_style	.= ' width:'.intval($size['width']/2).'px;';
					}

					if($size['height']){
						$img_style	.= ' height:'.intval($size['height']/2).'px;';
					}

					$thumb_args	= wpjam_get_thumbnail('',$size);
				}else{
					$thumb_args	= wpjam_get_thumbnail('',400);
				}

				$img_style	= $img_style ?: 'max-width:200px;';
					
				$div_class	= 'wpjam-img button add_media';
				$field_html	= '<span class="wp-media-buttons-icon"></span> 添加图片</button>';

				if(isset($field['disabled']) || isset($field['readonly'])){
					$div_class	= '';
					$field_html	= '';
				}

				if(!empty($value)){
					$img_url	= ($item_type == 'url')?$value:wp_get_attachment_url($value);

					if($img_url){
						$img_url	= wpjam_get_thumbnail($img_url, $size);
						$field_html	= '<img style="'.$img_style.'" src="'.$img_url.'" alt="" />';

						if(!isset($field['disabled']) && !isset($field['readonly'])){
							$div_class	= 'wpjam-img';
							$field_html	.= '<a href="javascript:;" class="del-img dashicons dashicons-no-alt"></a>';
						}
					}
				}

				$field_html = '<div class="wp-media-buttons" style="display: inline-block; float:none;">'.self::get_input_field_html('hidden', $name, $key, $class, $value).'<div data-item_type="'.$item_type.'" data-img_style="'.$img_style.'" data-thumb_args="'.$thumb_args.'"  class="'.$div_class.'">'.$field_html.'</div></div>'.$description;
			}
		}elseif($type == 'textarea'){
			$rows = $field['rows'] ?? 6;
			$field_html = '<textarea name="'.$name.'" id="'.$key.'" class="'.$class.' code" rows="'.$rows.'" cols="50" '.$extra.' >'.esc_textarea($value).'</textarea>'.$description;
		}elseif($type == 'editor'){
			wp_enqueue_editor();
				
			ob_start();
			$settings		= $field['settings'] ?? [];
			wp_editor($value, $key, $settings);
			$field_style	= isset($field['style'])?' style="'.$field['style'].'"':'';
			$field_html 	= '<div'.$field_style.'>'.ob_get_contents().'</div>';
			ob_end_clean();

			$field_html		.= $description;
		}elseif($type == 'mu-file'){
			if(current_user_can('upload_files')){
				if(is_array($value)){
					foreach($value as $file){
						if(empty($file)){
							continue;
						}
						$field_html .= '<div class="mu-item">'.self::get_input_field_html('url', $name.'[]', $key, $class, esc_attr($file)).$del_item_button.$sortable_dashicons.'</div>';
					}
				}

				$field_html  .= '<div class="mu-item">'.self::get_input_field_html('url', $name.'[]', $key, $class).' <input type="button" item_type="" class="wpjam-mu-file button" value="选择文件[多选]" title="按住Ctrl点击鼠标左键可以选择多个文件"></div>';

				$field_html = '<div class="mu-files">'.$field_html.'</div>'.$description;
			}
		}elseif($type == 'mu-image'){
			if(current_user_can('upload_files')){

				if(is_array($value)){
					foreach($value as $image){
						if(empty($image)){
							continue;
						}
						$field_html .= '<div class="mu-item">'.self::get_input_field_html('url', $name.'[]', $key, $class, esc_attr($image)).$del_item_button.$sortable_dashicons.'</div>';
					}
				}

				$field_html  .= '<div class="mu-item">'.self::get_input_field_html('url', $name.'[]', $key, $class).' <input type="button" item_type="image" class="wpjam-mu-file button" value="选择图片[多选]" title="按住Ctrl点击鼠标左键可以选择多张图片"></div>';

				$field_html	= '<div class="mu-images" style="display:inline-grid;">'.$field_html.'</div>'.$description;
			}
		}elseif($type == 'mu-img'){
			if(current_user_can('upload_files')){

				$item_type	= $field['item_type'] ?? '';
				$max_items	= $field['max_items'] ?? 0;

				$i	= 0;
				if(is_array($value)){
					foreach($value as $img){
						if(empty($img)){
							continue;
						}

						$img_key	= $key.'_'.$i;

						$i++;

						if($max_items && $i > $max_items){
							break;
						}

						$img_url	= ($item_type == 'url') ? $img : wp_get_attachment_url($img);
						$img_url	= wpjam_get_thumbnail($img_url, 200, 200);

						
						if(!isset($field['disabled']) && !isset($field['readonly'])){
							$field_html .= '<div class="mu-img mu-item"><img width="100" src="'.$img_url.'" alt="">'.self::get_input_field_html('hidden', $name.'[]', $img_key, $class, $img).'<a href="javascript:;" class="del-item dashicons dashicons-no-alt"></a></div>';
						}else{
							$field_html .= '<div class="mu-img mu-item"><img width="100" src="'.$img_url.'" alt=""></div>';
						}
						
					}
				}

				if(!isset($field['disabled']) && !isset($field['readonly'])){
					$thumb_args	= wpjam_get_thumbnail('',[200,200]);

					$datas	= ' data-i='.$i.' data-key="'.$key.'" data-item_type="'.$item_type.'" data-thumb_args="'.$thumb_args.'" data-input_name="'.$name.'[]"';

					if($max_items){
						$datas	.= ' data-max_items='.$max_items;
					}

					$field_html  .= '<div title="按住Ctrl点击鼠标左键可以选择多张图片" class="wpjam-mu-img dashicons dashicons-plus-alt2" '.$datas.'></div>';
				}

				$field_html = '<div class="mu-imgs">'.$field_html.'</div>'.$description;
			}
		}elseif($type == 'mu-text'){
			$item_type	= $field['item_type'] ?? 'text';
			$item_field	= $field;
			unset($item_field['description']);
			$item_field['type']	= $item_type;
			$item_field['name']	= $name.'[]';

			$max_items	= $field['max_items'] ?? ($field['total'] ?? 0);

			$i	= 0;
			if(is_array($value)){
				foreach($value as $item){
					if(empty($item)){
						continue;
					}
					
					$item_field['value']	= $item;
					$item_field['key']		= $key.'_'.$i;

					$i++;

					if($max_items && $i >= $max_items){
						$max_reached	= true;
						break;
					}

					$field_html .= '<div class="mu-item">'.self::get_field_html($item_field).$del_item_button.$sortable_dashicons.'</div>';
				}
			}

			$datas	= ' data-i='.$i.' data-key="'.$key.'"';

			if(!$max_items || empty($max_reached)){
				$item_field['value']	= '';
				$item_field['key']		= $key.'_'.$i;
			}

			if($max_items){
				$datas	.= ' data-max_items='.$max_items;
			}

			$field_html .= '<div class="mu-item">'.self::get_field_html($item_field).' <a class="wpjam-mu-text button" '.$datas.'">添加选项</a></div>';
			$field_html = '<div class="mu-texts">'.$field_html.'</div>';

			$field_html .= $description;
		}elseif($type == 'mu-fields'){
			if(!empty($field['fields'])){
				if(!empty($field['data-type'])){
					// $field['data_type']	= $field['data-type'];
					trigger_error('data-type '.var_export($field, true));
				}
				
				$max_items	= $field['max_items'] ?? ($field['total'] ?? 0);

				$i	= 0;
				if(is_array($value)){
					foreach($value as $item){
						if(empty($item)){
							continue;
						}

						$item_html	= self::get_mu_fields_html($name, $field['fields'], $i, $item);

						$i++;

						if($max_items && $i >= $max_items){
							$max_reached	= true;
							break;
						}

						$field_html .= '<div class="mu-item">'.$item_html.$del_item_button.$sortable_dashicons.'</div>';
					}
				}

				$tmpl_id	= md5($name);
				$datas		= ' data-tmpl-id="wpjam-'.$tmpl_id.'"';

				if(!$max_items || empty($max_reached)){
					$item_html	= self::get_mu_fields_html($name, $field['fields'], $i);
				}

				if($max_items){
					$datas	.= ' data-max_items='.$max_items;
				}

				$field_html	.= '<div class="mu-item">'.$item_html.' <a class="wpjam-mu-fields button" data-i='.$i.$datas.'">添加选项</a></div>'; 

				$field_html	= '<div class="mu-fields" id="mu_fields_'.$name.'">'.$field_html.'</div>';

				self::$field_tmpls[$tmpl_id]	= '<div class="mu-item">'.self::get_mu_fields_html($name,  $field['fields'], '{{ data.i }}').' <a class="wpjam-mu-fields button" data-i="{{ data.i }}" '.$datas.'>添加选项</a>'.'</div>';
			}
		}elseif($type == 'fieldset'){
			if(!empty($field['fields'])){
				$field_html  = '<legend class="screen-reader-text"><span>'.$title.'</span></legend>';

				$fieldset_type	= $field['fieldset_type'] ?? 'single';

				foreach ($field['fields'] as $sub_key=>$sub_field) {
					$sub_field['name']	= $sub_field['name'] ?? $sub_key;
					
					if($sub_field['type'] == 'hidden'){
						$field_html		.= self::get_field_html($sub_field);
					}else{
						$sub_field['key']	= $sub_field['key'] ?? $sub_key;

						$div_id			= $fieldset_type == 'array' ? $key.'_'.$sub_key : $sub_key;

						$field_title	= !empty($sub_field['title']) ? '<label class="sub-field-label" for="'.$sub_key.'">'.$sub_field['title'].'</label>' : '';
						$field_html		.= '<div class="sub-field" id="div_'.$div_id.'">'.$field_title.'<div class="sub-field-detail">'.self::get_field_html($sub_field).'</div>'.'</div>';	
					}
				}
			}
		}else{
			$query_title	= '';
			if(!empty($field['data_type'])){
				$extra		.= ' data-data_type="'.esc_attr($field['data_type']).'"';
				
				$class		= 'wpjam-query-id '.$class;
				$span_class	= 'wpjam-query-title';

				if($field['data_type'] == 'post_type'){
					$extra .= ' data-post_type="'.esc_attr($field['post_type']).'"';

					if($value && is_numeric($value) && ($field_post = get_post($value))){
						$class		.= ' hidden';
						$post_title	= $field_post->post_title ?: $field_post->ID;
					}else{
						$span_class	.= ' hidden';
						$post_title	= '';
					}

					$query_title	= '<span class="'.$span_class.'"><span class="dashicons dashicons-dismiss"></span>'.$post_title.'</span>';
				}elseif($field['data_type'] == 'taxonomy'){
					$extra .= ' data-taxonomy="'.esc_attr($field['taxonomy']).'"';

					if($value && is_numeric($value) && ($field_term = get_term($value))){
						$class		.= ' hidden';
						$term_name	= $field_term->name ?: $field_term->term_id;
					}else{
						$span_class	.= ' hidden';
						$term_name	= '';
					}

					$query_title	= '<span class="'.$span_class.'"><span class="dashicons dashicons-dismiss"></span>'.$term_name.'</span>';
				}

				$description	= '';
			}

			$field_html = self::get_input_field_html($type, $name, $key, $class, $value, $extra, $description).$query_title;
		}

		$datalist = '';
		if(!empty($field['list'])){
			static $datalist_ids;
			$datalist_ids	= $datalist_ids ?? [];

			if(!in_array($field['list'], $datalist_ids)){
				$datalist_ids[]	= $field['list'];

				$datalist	.= '<datalist id="'.$field['list'].'">';

				if(!empty($field['options'])){
					foreach ($field['options'] as $option_key => $option) {
						$datalist	.= '<option label="'.esc_attr($option).'" value="'.esc_attr($option_key).'" />';
					}
				}
				
				$datalist	.= '</datalist>';
			}
		}

		return apply_filters('wpjam_field_html', $field_html.$datalist, $field);
	}

	private static function get_input_field_html($type, $name, $key, $class, $value='', $extra='', $description=''){
		$class	= $class ? ' class="'.esc_attr($class).'"' : '';
		$html	= '<input type="'.esc_attr($type).'" name="'.esc_attr($name).'" id="'.esc_attr($key).'" value="'.esc_attr($value).'"'.$class.$extra.' />';

		if($description && $type != 'hidden'){
			$html	= '<label for="'.esc_attr($key).'">'.$html.$description.'</label>';
		}

		return $html;
	}

	private static function get_mu_fields_html($name, $fields, $i, $value=[]){
		$field_html		= '';
		$field_count	= count($fields);
		$count			= 0;

		$return = false;
		foreach ($fields as $sub_key=>$sub_field) {
			$count ++;

			$sub_name	= $sub_field['name'] ?? $sub_key;

			if(preg_match('/\[([^\]]*)\]/', $sub_name)){
				wp_die('mu-fields 类型里面子字段不允许[]模式');
			}
			
			$sub_field['name']	= $name.'['.$i.']'.'['.$sub_name.']';

			if($value){
				if(!empty($value[$sub_name])){
					$sub_field['value']	= $value[$sub_name];
				}
			}

			$class				= 'sub-field sub-field_'.$sub_key;		
			$sub_key			.= '_'.$i; 
			$sub_field['key']	= $sub_key;
			$sub_field['data-i']= $i;

			if($sub_field['type'] == 'hidden'){
				$field_html		.= self::get_field_html($sub_field);
			}else{
                // $field_style = $sub_field['style'] ?? '';
				$field_title 	= (!empty($sub_field['title']))?'<label class="sub-field-label" for="'.$sub_key.'">'.$sub_field['title'].'</label>':'';	
				$field_html		.= '<div class="'.$class.'" id="sub_field_'.$sub_key.'">'.$field_title.'<div class="sub-field-detail">'.self::get_field_html($sub_field).'</div>'.'</div>';
			}
		}

		return $field_html;
	}

	private static function generate_sub_field_name($name){
		if(preg_match('/\[([^\]]*)\]/', $name)){
			$name_arr	= wp_parse_args($name);
			$name		= '';

			do{
				$name		.='['.current(array_keys($name_arr)).']';
				$name_arr	= current(array_values($name_arr));
			}while ($name_arr);

			return $name;
		}else{
			return '['.$name.']';
		}
	}

	public static function validate_fields_value($fields, $values=[]){
		$data = [];

		foreach ($fields as $key => $field) {
			if($field['type'] == 'fieldset'){
				if(empty($field['fields'])){
					continue;
				}
				
				$fieldset_type	= $field['fieldset_type'] ?? 'single';

				if($fieldset_type == 'array'){
					
					$name	= $field['name'] ?? $key;

					array_walk($field['fields'], function(&$sub_field, $sub_key) use($name){
						$sub_field['name']	= $sub_field['name'] ?? $sub_key;
						$sub_field['name']	= $name.self::generate_sub_field_name($sub_field['name']);
					});
				}

				$data	= wpjam_array_merge($data, self::validate_fields_value($field['fields'], $values));
			}else{
				$name	= $field['name'] ?? $key;
				$values	= $values ?: $_POST;

				if(preg_match('/\[([^\]]*)\]/', $name)){
					$name_arr	= wp_parse_args($name);
					$name		= current(array_keys($name_arr));

					if(isset($values) && isset($values[$name])){
						$value		= $values[$name];
					}else{
						$value		= null;
					}

					$name_arr		= current(array_values($name_arr));
					$sub_name_arr	= [];

					do{
						$sub_name	= current(array_keys($name_arr));
						$name_arr	= current(array_values($name_arr));

						if(isset($value) && isset($value[$sub_name])){
							$value	= $value[$sub_name];
						}else{
							$value	= null;
						}

						array_unshift($sub_name_arr, $sub_name);
					}while($name_arr && $value);

					$value	= self::sanitize_by_field($value, $field);

					if($value !== false){	
						foreach($sub_name_arr as $sub_name) {
							$value	= [$sub_name => $value];
						}

						$data	= wpjam_array_merge($data, [$name=>$value]);
					}
					
				}else{
					$value	= $values[$name] ?? null;
					$value	= self::sanitize_by_field($value, $field);

					if($value !== false){
						$data[$name]	= $value;
					}
				}
			}
		}

		return $data;
	}
	
	public static function sanitize_by_field($value, $field){
		$field	= self::parse_field($field);
		$type	= $field['type'];

		if($type == 'view' || $type == 'hr'){
			return false;
		}elseif($type == 'checkbox'){
			if(is_null($value)){
				return 0;
			}
		}

		if(!empty($field['readonly']) || !empty($field['disabled'])){
			return false;
		}

		if(isset($field['show_admin_column']) && ($field['show_admin_column'] === 'only')){
			return false;
		}

		if(is_null($value)){
			return $value;
		}

		if(in_array($type, ['mu-image','mu-file','mu-text','mu-img'])){
			if(!is_array($value)){
				$value	= null;
			}else{
				$value	= array_filter($value);
			}
		}elseif($type == 'mu-fields'){
			if(!is_array($value)){
				$value	= null;
			}else{
				$value	= array_filter($value, function($v){
					foreach($v as $sub_key => $sub_value) {
						if(is_array($sub_value)){
							$v[$sub_key]	= array_filter($sub_value);
						}
					}
					return !empty(array_filter($v));
				});
			}
		}

		if($value && !is_array($value)){
			$value	= stripslashes(trim($value));	
		}

		if($type == 'textarea'){
			$value	= $value ? str_replace("\r\n", "\n",$value) : $value;
		}elseif($type == 'number'){
			$value	= intval($value);
		}

		if(!empty($field['sanitize'])){
			$value	= call_user_func($field['sanitize'], $value);
		}

		return $value;
	}
}

class WPJAM_Form extends WPJAM_Field{
}