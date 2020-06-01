<?php
global $post;
$post = get_post(get_query_var('p'));

if($post){
	// do nothing
}else{
	wp_die('该日志不存在','该日志不存在',array( 'response' => 404 ));
}

$post_thumbnail_src = wpjam_get_post_thumbnail_uri($post);

if(wpjam_doing_debug()){
	echo $post_thumbnail_src;
}else{
	if($post_thumbnail_src){
		$post_thumbnail = wp_remote_get(trim($post_thumbnail_src));

		if(is_wp_error($post_thumbnail)){
			wp_die('原图不存在','原图不存在',array( 'response' => 404 ));
		}else{
			header("HTTP/1.1 200 OK");
			header("Content-Type: image/png");
			imagepng(imagecreatefromstring($post_thumbnail['body']));
		}

	}else{
		wp_die('该文章没有图片','该文章没有图片',array( 'response' => 404 ));
	}
}