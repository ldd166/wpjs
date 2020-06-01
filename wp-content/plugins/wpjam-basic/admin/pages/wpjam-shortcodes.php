<?php

add_filter('wpjam_shortcodes_list_table', function(){
	return [
		'title'		=> '短代码',
		'plural'	=> 'shortcodes',
		'singular' 	=> 'shortcode',
		'fixed'		=> false,
		'ajax'		=> true,
		'per_page'	=> 300,
		'model'		=> 'WPJAM_AdminShortcode'
	];
});

class WPJAM_AdminShortcode{
	public static function get_shortcodes(){
		global $shortcode_tags;
		return $shortcode_tags;
	}

	public static function get_primary_key(){
		return 'tag';
	}

	public static function query_items($limit, $offset){
		$shortcodes	= self::get_shortcodes();
		$items		= [];

		foreach ($shortcodes as $tag => $function) {
			if(is_array($function)){
				if(is_object($function[0])){
					$function	= '<p>'.get_class($function[0]).'->'.(string)$function[1].'</p>';	
				}else{
					$function	= '<p>'.$function[0].'->'.(string)$function[1].'</p>';
				}
			}elseif(is_object($function)){
				$function	= '<pre>'.print_r($function, true).'</pre>';
			}else{
				$function	= wpautop($function);
			}

			$tag		= wpautop($tag);
			$items[]	= compact('tag', 'function');
		}

		$total	= count($items);

		return compact('items', 'total');
	}

	public static function get_actions(){
		return [];
	}

	public static function get_fields($action_key='', $id=0){
		return [
			'tag'		=> ['title'=>'短代码',	'type'=>'view',	'show_admin_column'=>true],
			'function'	=> ['title'=>'函数',		'type'=>'view',	'show_admin_column'=>true]
		];
	}
}