<?php
add_filter('wpjam_rewrites_tabs', function(){
	return [
		'rules'		=> ['title'=>'Rewrites 规则',	'function'=>'list'],
		'optimize'	=> ['title'=>'Rewrites 优化',	'function'=>'option',	'option_name'=>'wpjam-basic'],
	];
});

add_filter('wpjam_rewrites_list_table', function(){
	return [
		'title'		=> 'Rewrites 规则',
		'plural'	=> 'rewrites',
		'singular' 	=> 'rewrite',
		'fixed'		=> false,
		'ajax'		=> true,
		'per_page'	=> 300,
		'model'		=> 'WPJAM_AdminRewrite'
	];
});

class WPJAM_AdminRewrite{
	public static function get_all(){
		return get_option('rewrite_rules');
	}

	public static function get_rewrites(){
		return wpjam_basic_get_setting('rewrites') ?: [];
	}

	public static function update_rewrites($rewrites){
		wpjam_basic_update_setting('rewrites', $rewrites);
		flush_rewrite_rules();
		return true;
	}

	public static function get($id){
		$rewrites	= self::get_all();
		$regex_arr	= array_keys($rewrites);

		$regex		= $regex_arr[($id-1)] ?? '';

		if($regex){
			$query	= $rewrites[$regex];
			return compact('id', 'regex', 'query');
		}else{
			return [];
		}
	}

	public static function prepare($data, $id=''){
		$regex	= $data['regex'] ?? '';
		$query	= $data['query'] ?? '';

		if(empty($regex) || empty($query)){
			return new WP_error('empty_regex', 'Rewrite 规则不能为空');
		}

		$rewrites	= self::get_all();

		if($id){
			$current	= self::get($id);

			if(empty($current)){
				return new WP_error('invalid_regex', '该 Rewrite 规则不存在');
			}elseif($current['regex'] != $regex && isset($rewrites[$regex])){
				return new WP_error('invalid_regex', '该 Rewrite 规则已使用');
			}
		}else{
			if(isset($rewrites[$regex])){
				return new WP_error('duplicate_regex', '该 Rewrite 规则已存在');
			}
		}

		return $data;
	}

	public static function insert($data){
		$data	= self::prepare($data);
		if(is_wp_error($data)){
			return $data;
		}

		$rewrites	= self::get_rewrites();
		$rewrites	= array_merge([$data], $rewrites);

		return self::update_rewrites($rewrites);
	}

	public static function update($id, $data){
		$data	= self::prepare($data, $id);
		if(is_wp_error($data)){
			return $data;
		}

		$current	= self::get($id);
		$rewrites	= self::get_rewrites();
		foreach ($rewrites as $i => $rewrite){
			if($rewrite['regex'] == $current['regex']){
				$rewrites[$i]	= $data;
				break;
			}
		}

		return self::update_rewrites($rewrites);
	}

	public static function delete($id){
		$current	= self::get($id);
		if(empty($current)){
			return new WP_error('invalid_regex', '该 Rewrite 规则不存在');
		}

		$rewrites	= self::get_rewrites();
		foreach ($rewrites as $i => $rewrite){
			if($rewrite['regex'] == $current['regex']){
				unset($rewrites[$i]);
				break;
			}
		}

		return self::update_rewrites($rewrites);
	}

	public static function query_items($limit, $offset){
		$rewrites	= self::get_all();
		$items		= [];

		$id	= 0;
		foreach ($rewrites as $regex => $query) {
			$id++;
			$items[]	= compact('id', 'regex', 'query');
		}

		$total	= count($items);

		// wpjam_print_r(get_defined_constants(true));
		// wpjam_print_r(get_defined_functions(true));
		// wpjam_print_r(get_declared_classes());
		// wpjam_print_r(get_declared_interfaces());
		// wpjam_print_r(get_defined_vars());

		return compact('items', 'total');
	}

	public static function item_callback($item){
		$rewrites	= self::get_rewrites();

		$rewrites	= $rewrites ? wp_list_pluck($rewrites, 'query', 'regex') : [];
		if(!$rewrites || !isset($rewrites[$item['regex']])){
			unset($item['row_actions']);
			$item['regex']	= wpautop($item['regex']);
		}

		$item['query']	= wpautop($item['query']);
		
		return $item;
	}

	public static function get_actions(){
		return [
			'add'		=> ['title'=>'新建',	'response'=>'list'],
			'edit'		=> ['title'=>'编辑'],
			'delete'	=> ['title'=>'删除',	'direct'=>true,	'response'=>'list']
		];
	}

	public static function get_fields($action_key='', $id=0){
		return [
			'regex'		=> ['title'=>'正则',		'type'=>'text',	'show_admin_column'=>true],
			'query'		=> ['title'=>'查询',		'type'=>'text',	'show_admin_column'=>true]
		];
	}
}

add_filter('wpjam_basic_setting', function(){
	$rewrite_fields		= [
		'remove_comment_rewrite'		=> '留言 Rewrite 规则',
		'remove_comment-page_rewrite'	=> '留言分页 Rewrite 规则',
		'remove_feed=_rewrite'			=> '分类 Feed Rewrite 规则',
		'remove_attachment_rewrite'		=> '附件 Rewrite 规则',
		// 'remove_type/_rewrite'		=> '文章格式Rewrite规则',
		// 'remove_author_rewrite'		=> '作者Rewrite规则',
	];

	return [
		'summary'	=>'<p>如果你的网站没有使用以下页面，可以移除相关功能的的 Rewrites 规则以提高网站效率！</p>',
		'fields'	=> array_map(function($title){return ['title'=>'','type'=>'checkbox','description'=>'移除'.$title]; }, $rewrite_fields)
	];
});

flush_rewrite_rules();