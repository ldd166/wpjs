<?php
/* 规则：
** 1. 分成主的查询和子查询（$post_list_args['sub']=1）
** 2. 主查询支持 $_GET 参数 和 $_GET 参数 mapping
** 3. 子查询（sub）只支持 $post_list_args 参数
** 4. 主查询返回 next_cursor 和 total_pages，current_page，子查询（sub）没有
** 5. $_GET 参数只适用于 post.list 
** 6. term.list 只能用 $_GET 参数 mapping 来传递参数
*/

global $wp, $wp_query;

$post_list_args	= $args;
$is_main_query	= !($post_list_args['sub'] ?? false);

if(!$is_main_query){	// 子查询不支持 $_GET 参数
	$wp->query_vars	= [];
}

// 缓存处理
$wp->set_query_var('cache_results', true);
// $wp->set_query_var('update_post_meta_cache', true);
// $wp->set_query_var('update_post_term_cache', true);
// $wp->set_query_var('lazy_load_term_meta', false);	// 在 the_posts filter 的时候，已经处理了

// $post_list_args['ignore_sticky_posts']	= $post_list_args['ignore_sticky_posts'] ?? true;

if($post_list_args){
	foreach ($post_list_args as $query_key => $query_var) {
		$wp->set_query_var($query_key, $query_var);
	}
}

$post_type	= $wp->query_vars['post_type'] ?? '';

if(!empty($post_list_args['output'])){
	$output	= $post_list_args['output'];
}elseif($post_type && !is_array($post_type)){
	$output	= $post_type.'s';
}else{
	$output	= 'posts';
}

if($is_main_query){
	foreach (['meta_key','meta_value','meta_value_num', 'meta_compare'] as $key) {
		if($value = wpjam_get_parameter($key)){
			trigger_error('using meta_key get');
			$wp->set_query_var($key, $value);
		}
	}

	$posts_per_page	= wpjam_get_parameter('posts_per_page',	['sanitize_callback'=>'intval']);

	if($posts_per_page){
		if($posts_per_page	> 20){
			$posts_per_page	= 20;
		}

		$wp->set_query_var('posts_per_page', $posts_per_page);
	}

	$orderby	= $wp->query_vars['orderby'] ?? 'date';
	$paged		= $wp->query_vars['paged'] ?? null;
	
	if(empty($paged) && is_null(wpjam_get_parameter('s')) && !is_array($orderby) && in_array($orderby, ['date', 'post_date'])){
		$use_cursor	= true;
	}else{
		$use_cursor	= false;
	}

	if($use_cursor){
		if($cursor	= wpjam_get_parameter('cursor',	['default'=>0,	'sanitize_callback'=>'intval'])){
			$wp->set_query_var('cursor', $cursor);
		}

		if($since	= wpjam_get_parameter('since',	['default'=>0,	'sanitize_callback'=>'intval'])){
			$wp->set_query_var('since', $since);
		}
	}

	// taxonomy 参数处理，同时支持 $_GET 和 $post_list_args 参数
	if($post_type){
		$taxonomy_objs	= get_object_taxonomies($post_type, 'objects');
	}else{
		$taxonomy_objs	= get_taxonomies(['public'=>true], 'objects');
	}

	if($taxonomy_objs){
		foreach ($taxonomy_objs as $taxonomy=>$taxonomy_obj) {
			if($taxonomy == 'category'){
				foreach (['category_id', 'cat_id'] as $cat_key) {
					if($term_id	= wpjam_get_parameter($cat_key, ['sanitize_callback'=>'intval'])){
						$wp->set_query_var('cat', $term_id);
						break;
					}
				}
			}elseif($taxonomy == 'post_tag'){
				if($term_id	= wpjam_get_parameter('tag_id', ['sanitize_callback'=>'intval'])){
					$wp->set_query_var('tag_id', $term_id);
				}
			}else{
				if($term_id	= wpjam_get_parameter($taxonomy.'_id', ['sanitize_callback'=>'intval'])){
					$wp->set_query_var($taxonomy.'_id', $term_id);
				}
			}
		}

		if($term_id	= wpjam_get_parameter('term_id', ['sanitize_callback'=>'intval'])){
			$wp->set_query_var('term_id', $term_id);
		}
	}
}

wpjam_parse_query_vars($wp);

$wp->query_posts();

$posts_json = [];

if($wp_query->have_posts()){
	$posts_json	= apply_filters('wpjam_posts_json', $wp_query->posts, $post_list_args);
	$posts_json	= array_map(function($post_json) use ($post_list_args){ return wpjam_get_post($post_json->ID, $post_list_args); }, $posts_json);
}

if($is_main_query){
	if(is_category() || is_tag() || is_tax()){
		if($current_term	= get_queried_object()){
			$taxonomy		= $current_term->taxonomy;
			$current_term	= wpjam_get_term($current_term, $taxonomy);

			$response['current_taxonomy']	= $current_term['taxonomy'];
			$response['current_'.$taxonomy]	= $current_term;
			
			if(empty($response['page_title'])){
				$response['page_title']		= $current_term['page_title'];
			}

			if(empty($response['share_title'])){
				$response['share_title']	= $current_term['share_title'];
			}
		}
	}elseif(is_author()){
		$current_author	= get_queried_object();

		$response['current_author']	= [
			'nickname'		=> $current_author->display_name,
			'id'			=> $current_author->ID,
			'avatar'		=> get_avatar_url($current_author->ID, 200),
			'description'	=> $current_author->description
		];

		if(empty($response['page_title'])){
			$response['page_title']		= $current_author->display_name;
		}

		if(empty($response['share_title'])){
			$response['share_title']	= $current_author->display_name;
		}
	}elseif(is_post_type_archive()){
		$post_type_object	= get_queried_object();

		if(empty($response['page_title'])){
			$response['page_title']	= $post_type_object->label;
		}

		if(empty($response['share_title'])){
			$response['share_title']	= $post_type_object->label;
		}
	}

	$response['total']			= intval($wp_query->found_posts);
	$response['total_pages']	= intval($wp_query->max_num_pages);
	$response['current_page']	= intval($wp_query->get('paged') ?: 1);
	
	if($use_cursor){
		$response['next_cursor']	= ($posts_json && $wp_query->max_num_pages>1) ? end($posts_json)['timestamp'] : 0;
	}
}

$response[$output]	= $posts_json;
