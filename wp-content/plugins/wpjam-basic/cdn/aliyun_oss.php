<?php
add_filter('wpjam_thumbnail','wpjam_get_aliyun_oss_thumbnail',10,2);

function wpjam_get_aliyun_oss_thumbnail($img_url, $args=[]){
	extract(wp_parse_args($args, array(
		'crop'		=> 1,
		'width'		=> 0,
		'height'	=> 0,
		'mode'		=> null,
		'format'	=> '',
		'interlace'	=> 0,
		'quality'	=> 0,
	)));

	if($height > 4096){
		$height = 0;
	}

	if($width > 4096){
		$height = 0;
	}

	if($mode === null){
		$crop	= $crop && ($width && $height);	// 只有都设置了宽度和高度才裁剪
		$mode	= $crop?',m_fill':'';
	}

	if($width || $height){
		$arg	= 'x-oss-process=image/resize'.$mode;

		if($width)		$arg .= ',w_'.$width;
		if($height) 	$arg .= ',h_'.$height;

		if(strpos($img_url, 'x-oss-process=image/resize')){
			$img_url	= preg_replace('/x-oss-process=image\/resize(.*?)#/', '', $img_url);
		}
		
		$img_url	= add_query_arg( array($arg => ''), $img_url );
		$img_url	= $img_url.'#';
	}

	return $img_url;
}