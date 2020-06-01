<?php
add_filter('wpjam_post_options',function ($post_options){
	$post_options['wpjam-toc'] = [
		'title'		=> '文章目录',
		'context'	=> 'side',
		'fields'	=> [
			'toc_hidden'	=> ['title'=>'',		'type'=>'checkbox',	'description'=>'隐藏文章目录'],
			'toc_depth'		=> ['title'=>'显示到：',	'type'=>'select',	'options'=>['1'=>'h1','2'=>'h2','3'=>'h3','4'=>'h4','5'=>'h5','6'=>'h6']]
		]
	];

	return $post_options;
});

add_action('save_post', function($post_id){
	wp_cache_delete($post_id,'wpjam-toc');
});