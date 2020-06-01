<?php
add_action('wpjam_'.$taxonomy.'_terms_actions', function($actions, $taxonomy){
	return $actions + ['seo'=>['title'=>'SEO设置', 'page_title'=>'SEO设置',	'submit_text'=>'设置']];
}, 10, 2);

add_filter('wpjam_'.$taxonomy.'_terms_fields', function($fields, $action_key, $term_id){
	if($action_key == 'seo'){
		return [
			'seo_title'			=> ['title'=>'SEO 标题',		'type'=>'text',		'value'=>get_term_meta($term_id, 'seo_title', true),	'placeholder'=>'不填则使用文章标题'],
			'seo_description'	=> ['title'=>'SEO 描述', 	'type'=>'textarea',	'value'=>get_term_meta($term_id, 'seo_description', true)],
			'seo_keywords'		=> ['title'=>'SEO 关键字',	'type'=>'text',		'value'=>get_term_meta($term_id, 'seo_keywords', true)]
		];
	}

	return $fields;
}, 10, 3);

add_filter('wpjam_'.$taxonomy.'_terms_list_action', function($result, $list_action, $term_id, $data){
	if($list_action == 'seo'){
		foreach(['seo_title', 'seo_description', 'seo_keywords'] as $meta_key){
			$meta_value	= $data[$meta_key] ?? '';

			if($meta_value){
				update_term_meta($term_id, $meta_key, $meta_value);
			}else{
				delete_term_meta($term_id, $meta_key);
			}
		}
		return true;
	}

	return $result;
}, 10, 4);