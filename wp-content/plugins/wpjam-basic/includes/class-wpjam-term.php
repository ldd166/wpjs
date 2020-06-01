<?php
class WPJAM_Term{
	public static function get_thumbnail_url($term=null, $size='full', $crop=1){
		$term	= $term ?: get_queried_object();
		$term	= get_term($term);

		if(!$term) {
			return '';
		}

		$thumbnail_url	= apply_filters('wpjam_term_thumbnail_url', '', $term);

		return $thumbnail_url ? wpjam_get_thumbnail($thumbnail_url, $size, $crop) : '';
	}

	public static function get_term($term, $taxonomy){
		return self::parse_for_json($term, $taxonomy);
	}

	public static function get_children($term, $children_terms=[], $max_depth=-1, $depth=0){
		$term	= self::parse_for_json($term);
		if(is_wp_error($term)){
			return $term;
		}

		$term['children'] = [];

		if($children_terms){
			$term_id	= $term['id'];

			if(($max_depth == 0 || $max_depth > $depth+1) && isset($children_terms[$term_id])){
				foreach($children_terms[$term_id] as $child){
					$term['children'][]	= self::get_children($child, $children_terms, $max_depth, $depth + 1);
				}
			} 
		}

		return $term;
	}

	/**
	 * $max_depth = -1 means flatly display every element.
	 * $max_depth = 0 means display all levels.
	 * $max_depth > 0 specifies the number of display levels.
	 *
	 */
	public static function get_terms($args, $max_depth=-1){
		$taxonomy	= $args['taxonomy'];
		$parent		= 0;

		$raw_args	= $args;

		if(isset($args['parent']) && ($max_depth != -1 && $max_depth != 1)){
			$parent		= $args['parent'];
			unset($args['parent']);
		}

		$terms = get_terms($args) ?: [];

		if(is_wp_error($terms) || empty($terms)){
			return $terms;
		}

		if($max_depth == -1){
			foreach ($terms as &$term) {
				$term = self::parse_for_json($term, $taxonomy); 

				if(is_wp_error($term)){
					return $term;
				}
			}
		}else{
			$top_level_terms	= [];
			$children_terms		= [];

			foreach($terms as $term){
				if(empty($term->parent)){
					if($parent){
						if($term->term_id == $parent){
							$top_level_terms[] = $term;
						}
					}else{
						$top_level_terms[] = $term;
					}
				}else{
					$children_terms[$term->parent][] = $term;
				}
			}

			if($terms = $top_level_terms){
				foreach ($terms as &$term) {
					if($max_depth == 1){
						$term = self::parse_for_json($term, $taxonomy);
					}else{
						$term = self::get_children($term, $children_terms, $max_depth, 0);	
					}

					if(is_wp_error($term)){
						return $term;
					}
				}
			}
		}
	
		return apply_filters('wpjam_terms', $terms, $raw_args, $max_depth);
	}

	public static function flatten($terms, $depth=0){
		$terms_flat	= [];

		if($terms){
			foreach ($terms as $term){
				$term['name']	= str_repeat('&nbsp;', $depth*3).$term['name'];
				$terms_flat[]	= $term;

				if(!empty($term['children'])){
					$depth++;

					$terms_flat	= array_merge($terms_flat, self::flatten($term['children'], $depth));

					$depth--;
				}
			}
		}

		return $terms_flat;
	}

	public static function get($term_id){
		$term	= get_term($term_id);

		if(is_wp_error($term) || empty($term)){
			return [];
		}else{
			return self::parse_for_json($term, $term->taxonomy);
		}
	}

	public static function insert($data){
		$taxonomy		= $data['taxonomy']		?? '';

		if(empty($taxonomy)){
			return new WP_Error('empty_taxonomy', '分类模式不能为空');
		}

		$name			= $data['name']			?? '';
		$parent			= $data['parent']		?? 0;
		$slug			= $data['slug']			?? '';
		$description	= $data['description']	?? '';

		if(term_exists($name, $taxonomy)){
			return new WP_Error('term_exists', '相同名称的'.get_taxonomy($taxonomy)->label.'已存在。');
		}

		$term	= wp_insert_term($name, $taxonomy, compact('parent','slug','description'));

		if(is_wp_error($term)){
			return $term;
		}

		$term_id	= $term['term_id'];

		$meta_input	= $data['meta_input']	?? [];

		if($meta_input){
			foreach($meta_input as $meta_key => $meta_value) {
				update_term_meta($term_id, $meta_key, $meta_value);
			}
		}

		return $term_id;
	}

	public static function update($term_id, $data){
		$taxonomy		= $data['taxonomy']	?? '';

		if(empty($taxonomy)){
			return new WP_Error('empty_taxonomy', '分类模式不能为空');
		}

		$term	= self::get_term($term_id, $taxonomy);

		if(is_wp_error($term)){
			return $term;
		}

		if(isset($data['name'])){
			$exist	= term_exists($data['name'], $taxonomy);

			if($exist){
				$exist_term_id	= $exist['term_id'];

				if($exist_term_id != $term_id){
					return new WP_Error('term_name_duplicate', '相同名称的'.get_taxonomy($taxonomy)->label.'已存在。');
				}
			}
		}

		$term_args = [];

		$term_keys = ['name', 'parent', 'slug', 'description'];

		foreach($term_keys as $key) {
			$value = $data[$key] ?? null;
			if (is_null($value)) {
				continue;
			}

			$term_args[$key] = $value;
		}

		if(!empty($term_args)){
			$term =	wp_update_term($term_id, $taxonomy, $term_args);
			if(is_wp_error($term)){
				return $term;	
			}
		}

		$meta_input		= $data['meta_input']	?? [];

		if($meta_input){
			foreach($meta_input as $meta_key => $meta_value) {
				update_term_meta($term['term_id'], $meta_key, $meta_value);
			}
		}

		return true;
	}

	public static function delete($term_id){
		$term	= get_term($term_id);

		if(is_wp_error($term) || empty($term)){
			return $term;
		}

		return wp_delete_term($term_id, $term->taxonomy);
	}

	public static function merge($term_id, $merge_to, $delete=true){
		$term	= get_term($term_id);

		if(is_wp_error($term) || empty($term)){
			return $term;
		}

		$merge_to_term	= get_term($merge_to);

		if(is_wp_error($merge_to_term) || empty($merge_to_term)){
			return $merge_to_term;
		}

		$taxonomy_obj	= get_taxonomy($term->taxonomy);
		$post_types		= $taxonomy_obj->object_type;

		$query	= new WP_Query([
			'post_type'		=> $post_types,
			'post_status'	=> 'all',
			'fields'		=> 'ids',
			'posts_per_page'=> -1,
			'tax_query'		=> [
				['taxonomy'=>$term->taxonomy, 'terms'=>[$term_id], 'field'=>'id']
			]
		]);

		if($query->posts){
			foreach($query->posts as $post_id) {
				wp_set_post_terms($post_id, $merge_to, $merge_to_term->taxonomy, true);
			}
		}

		if($delete){
			return self::delete($term_id);
		}else{
			return true;
		}
	}
	
	public static function parse_for_json($term, $taxonomy=null){
		$term		= get_term($term);
		$taxonomy	= $taxonomy ?: $term->taxonomy;

		if(is_wp_error($term) || empty($term) || $taxonomy != $term->taxonomy){
			return new WP_Error('illegal_'.$taxonomy.'_id', '非法 '.$taxonomy.'_id');
		}

		$term_json	= [];
		$term_id	= $term->term_id;

		$term_json['id']			= $term_id;
		$term_json['taxonomy']		= $taxonomy;
		$term_json['name']			= $term->name;

		if(get_queried_object_id() == $term_id){
			$term_json['page_title']	= $term->name;
			$term_json['share_title']	= $term->name;
		}

		$taxonomy_obj	= get_taxonomy($taxonomy);

		if($taxonomy_obj->public || $taxonomy_obj->publicly_queryable || $taxonomy_obj->query_var){
			$term_json['slug']		= $term->slug;
		}
		
		$term_json['count']			= intval($term->count);
		$term_json['description']	= $term->description;
		$term_json['parent']		= $term->parent;
		
		return apply_filters('wpjam_term_json', $term_json, $term_id);
	}

	public static function get_by_ids($post_ids){
		return self::update_caches($post_ids);
	}

	public static function update_caches($term_ids, $args=[]){
		if($term_ids){
			$term_ids 	= array_filter($term_ids);
			$term_ids 	= array_unique($term_ids);
		}

		if(empty($term_ids)) {
			return [];
		}

		$update_meta_cache	= $args['update_meta_cache'] ?? true;

		_prime_term_caches($term_ids, $update_meta_cache);

		$caches	= [];

		foreach ($term_ids as $term_id) {
			$cache	= wp_cache_get($term_id, 'terms');
			if($cache !== false){
				$caches[$term_id]	= $cache;
			}
		}

		return $caches;
	}
}

class WPJAM_Taxonomy extends WPJAM_Term{
	// 兼容，以后去掉
}

