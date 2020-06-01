<?php
/*
Plugin Name: 相关文章
Plugin URI: http://blog.wpjam.com/project/wpjam-basic/
Description: 根据文章的标签和分类，自动生成相关文章，并在文章末尾显示。
Version: 1.0
*/
add_filter('wpjam_related_posts_args', function($args){
	$args['number']		= $args['number']	?? wpjam_basic_get_setting('related_posts_number');
	$args['excerpt']	= $args['excerpt']	?? wpjam_basic_get_setting('related_posts_excerpt');
	$args['post_type']	= $args['post_type']?? wpjam_basic_get_setting('related_posts_post_types');
	$args['class']		= $args['class']	?? wpjam_basic_get_setting('related_posts_class');
	$args['div_id']		= $args['div_id']	?? wpjam_basic_get_setting('related_posts_div_id');
	$args['title']		= $args['title']	?? wpjam_basic_get_setting('related_posts_title');
	$args['thumb']		= $args['thumb']	?? wpjam_basic_get_setting('related_posts_thumbnail');
	
	if($args['thumb']){
		$args['size']	= [
			'width'		=> wpjam_basic_get_setting('related_posts_width') * 2,
			'height'	=> wpjam_basic_get_setting('related_posts_height') * 2,
		];
	}

	return $args;
});

if(wpjam_basic_get_setting('related_posts_auto')){
	add_filter('the_content', function($content){
		$post_id	= get_the_ID();

		if(doing_filter('get_the_excerpt') || !is_singular() || wpjam_get_json() || $post_id != get_queried_object_id()){
			return $content;
		}

		$post_types	= wpjam_basic_get_setting('related_posts_post_types');

		if($post_types && !in_array(get_post_type(), $post_types)){
			return $content;
		}
		
		return $content.wpjam_get_related_posts();
	},11);
}

add_filter('wpjam_post_json', function($post_json){
	if(is_singular() && get_the_ID() == get_queried_object_id()){
		$post_types	= wpjam_basic_get_setting('related_posts_post_types');

		if(!$post_types || ($post_types && in_array(get_post_type(), $post_types))){
			$post_json['related']	= WPJAM_Post::get_related();
		}
	}

	return $post_json;
}, 10, 2);

add_shortcode('related', function($atts, $content=''){
	extract(shortcode_atts(['tag'=>''], $atts));

	$tags	= $tag ? explode(",", $tag) : '';

	if(empty($tags)){
		return '';
	}
	
	$related_query	= wpjam_query(array( 
		'post_type'		=>'any', 
		'no_found_rows'	=>true,
		'post_status'	=>'publish', 
		'post__not_in'	=>[get_the_ID()],
		'tax_query'		=>[
			[
				'taxonomy'	=> 'post_tag',
				'terms'		=> $tags,
				'operator'	=> 'AND',
				'field'		=> 'name'
			]
		]
	));

	return  wpjam_get_post_list($related_query, ['thumb'=>false,'class'=>'related-posts']);
});

