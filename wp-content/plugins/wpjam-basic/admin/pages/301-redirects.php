<?php
add_filter('wpjam_301_redirects_list_table', function(){
	return [
		'title'		=> '301跳转',
		'plural'	=> 'redirects',
		'singular' 	=> 'redirect',
		'fixed'		=> false,
		'ajax'		=> true,
		'per_page'	=> 300,
		'model'		=> 'WPJAM_Admin301Redirect',
		'summary'	=> '301跳转扩展让一些404页面正确跳转到正常页面，详细介绍请点击：<a href="https://blog.wpjam.com/m/301-redirects/" target="_blank">301 跳转扩展</a>。'
	];
});

$redirects	= get_option('301-redirects');
if($redirects && isset($redirects[0])){
	$redirects[]	= $redirects[0];

	unset($redirects[0]);

	update_option('301-redirects', $redirects);
}

class WPJAM_Admin301Redirect extends WPJAM_Model {
	private static $handler;

	public static function get_handler(){
		if(is_null(static::$handler)){
			static::$handler	= new WPJAM_Option('301-redirects', ['total'=>50, 'primary_key'=>'id']);
		}
		return static::$handler;
	}

	public static function get_actions(){
		return  [
			'add'	=> ['title'=>'新增',	'last'=>true],
			'edit'	=> ['title'=>'编辑'],
			'delete'=> ['title'=>'删除',	'direct'=>true,	'confirm'=>true,	'bulk'=>true],
		];
	}

	public static function get_fields($action_key='', $id=0){
		return [
			'request'		=> ['title'=>'原地址',	'type'=>'url',	'show_admin_column'=>true],
			'destination'	=> ['title'=>'目标地址',	'type'=>'url',	'show_admin_column'=>true]
		];
	}
}