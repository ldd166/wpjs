<?php
add_filter('wpjam_basic_setting', function(){
	$fields	= [
		'baidu_tongji'		=>['title'=>'百度统计',		'type'=>'fieldset',	'fields'=>[
			'baidu_tongji_id'		=>['title'=>'跟踪 ID：',	'type'=>'text']
		]],
		'google_analytics'	=>['title'=>'Google 分析',	'type'=>'fieldset',	'fields'=>[
			'google_analytics_id'	=>['title'=>'跟踪 ID：',	'type'=>'text'],
			'google_universal'		=>['title'=>'',			'type'=>'checkbox',	'description'=>'使用 Universal Analytics 跟踪代码。'],
		]]	
	];

	$summary	= '统计代码扩展让你最简化插入 Google 分析和百度统计的代码，详细介绍请点击：<a href="https://blog.wpjam.com/m/wpjam-stats/" target="_blank">统计代码扩展</a>。';

	return compact('fields', 'summary');
});