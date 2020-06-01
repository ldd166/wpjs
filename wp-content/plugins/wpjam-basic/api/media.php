<?php
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$media_id	= $args['media'] ?? 'media';

if (!isset($_FILES[$media_id])) {
	wpjam_send_json(array(
		'errcode'	=> 'empty_media',
		'errmsg'	=> '媒体流不能为空！'
	));	
}

if($module_type == 'post_type'){
	$post_id		= wpjam_get_parameter('post_id',	['method'=>'POST', 'default'=>0, 'sanitize_callback'=>'intval']);
	$attachment_id	= media_handle_upload($media_id,	$post_id);

	if(is_wp_error($attachment_id)){
		wpjam_send_json($attachment_id);
	}

	$media_url		= wp_get_attachment_url($attachment_id);
}else{
	$upload_file	= wp_handle_upload($_FILES[$media_id], ['test_form' => false]);

	if(isset($upload_file['error'])){
		wpjam_send_json(array(
			'errcode'	=> 'upload_error',
			'errmsg'	=> $upload_file['error']
		));	
	}

	$media_url		= $upload_file['url'];
}

wpjam_send_json(array(
	'errcode'	=> 0,
	'url'		=> wpjam_get_thumbnail($media_url)
));