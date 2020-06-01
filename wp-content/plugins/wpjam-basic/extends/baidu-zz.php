<?php
/*
Plugin Name: 百度站长
Plugin URI: http://blog.wpjam.com/project/wpjam-basic/
Description: 支持主动，被动，自动以及批量方式提交链接到百度站长。
Version: 1.0
*/

add_action('wp_enqueue_scripts', function(){
	if(wpjam_get_setting('baidu-zz', 'no_js')){
		return;
	}

	if(is_404() || is_preview()){
		return;
	}elseif(is_singular() && get_post_status() != 'publish'){
		return;
	}

	if(is_ssl()){
		wp_enqueue_script('baidu_zz_push', 'https://zz.bdstatic.com/linksubmit/push.js', '', '', true);
	}else{
		wp_enqueue_script('baidu_zz_push', 'http://push.zhanzhang.baidu.com/push.js', '', '', true);
	}
});

function wpjam_notify_baidu_zz($urls, $args=[]){
	$query_args	= [];

	$query_args['site']		= wpjam_get_setting('baidu-zz', 'site');
	$query_args['token']	= wpjam_get_setting('baidu-zz', 'token');

	if($query_args['site'] && $query_args['token']){
		$update	= $args['update'] ?? false;
		$type	= $args['type'] ?? '';
		
		if(empty($type) && wpjam_get_setting('baidu-zz', 'mip')){
			$type	= 'mip';
		}

		if($type){
			$query_args['type']	= $type;
		}

		if($update){
			$baidu_zz_api_url	= add_query_arg($query_args, 'http://data.zz.baidu.com/update');
		}else{
			$baidu_zz_api_url	= add_query_arg($query_args, 'http://data.zz.baidu.com/urls');
		}

		$response	= wp_remote_post($baidu_zz_api_url, array(
			'headers'	=> ['Accept-Encoding'=>'','Content-Type'=>'text/plain'],
			'sslverify'	=> false,
			'blocking'	=> false,
			'body'		=> $urls
		));
	}
}

add_action('publish_future_post', function($post_id){
	$urls	= apply_filters('baiduz_zz_post_link', get_permalink($post_id))."\n";	

	wp_cache_set($post_id, true, 'wpjam_baidu_zz_notified', HOUR_IN_SECONDS);
	wpjam_notify_baidu_zz($urls);
},11);

