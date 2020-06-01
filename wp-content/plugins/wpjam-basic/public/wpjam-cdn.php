<?php
function wpjam_cdn_get_setting($setting_name){
	return wpjam_get_setting('wpjam-cdn', $setting_name);
}

function wpjam_get_cdns(){
	global $wpjam_cdns;

	if(!is_array($wpjam_cdns)){
		return [];
	}

	return $wpjam_cdns;
}

function wpjam_register_cdn($key, $args){
	global $wpjam_cdns;

	if(!is_array($wpjam_cdns)){
		$wpjam_cdns	= [];
	}

	$wpjam_cdns[$key]	= $args;
}

add_action('plugins_loaded', function(){
	foreach (['aliyun_oss'=>'阿里云OSS', 'qcloud_cos'=>'腾讯云COS', 'ucloud'=>'UCloud UFile', 'qiniu'=>'七牛云存储'] as $cdn_key => $cdn_title) {
		wpjam_register_cdn($cdn_key, [
			'title'	=> $cdn_title, 
			'file'	=> WPJAM_BASIC_PLUGIN_DIR.'cdn/'.$cdn_key.'.php',
		]);
	}

	$current_cdn	= wpjam_cdn_get_setting('cdn_name');

	if(empty($current_cdn)){
		return;
	}

	$cdns	= wpjam_get_cdns();

	if(!isset($cdns[$current_cdn])){
		return;
	}

	define('CDN_NAME',		$current_cdn);	// CDN 名称

	define('LOCAL_HOST',	untrailingslashit(wpjam_cdn_get_setting('local') ? set_url_scheme(wpjam_cdn_get_setting('local')): site_url()));
	define('CDN_HOST',		untrailingslashit(wpjam_cdn_get_setting('host') ?: site_url()));
		
	$cdn_file	= $cdns[$current_cdn]['file'] ?? '';

	if($cdn_file && file_exists($cdn_file)){
		include($cdn_file);
	}

	// 不用生成 -150x150.png 这类的图片
	add_filter('intermediate_image_sizes_advanced', function($sizes){
		if(isset($sizes['full'])){
			return ['full'=>$sizes['full']];
		}else{
			return [];
		}
	});

	add_filter('image_size_names_choose', function($sizes){
		$_sizes	= $sizes;

		$sizes	= [];
		$sizes['full']	= $_sizes['full'];
		unset($_sizes['full']);

		foreach(['large', 'medium', 'thumbnail'] as $key){
			if(get_option($key.'_size_w') || get_option($key.'_size_h')){
				$sizes[$key]	= $_sizes[$key];
			}else{
				unset($_sizes[$key]);
			}
		}

		if($_sizes){
			foreach ($_sizes as $key => $value) {
				$sizes[$key]	= $value;
			}
		}

		return $sizes;
	});

	add_filter('wpjam_thumbnail', 'wpjam_cdn_replace_local_hosts', 1);
	// add_filter('wp_get_attachment_url', 'wpjam_cdn_replace_local_hosts');

	add_filter('upload_dir', function($uploads){
		$uploads['url']		= wpjam_cdn_replace_local_hosts($uploads['url']);
		$uploads['baseurl']	= wpjam_cdn_replace_local_hosts($uploads['baseurl']);
		return $uploads;
	});

	// add_filter('wp_calculate_image_srcset_meta', '__return_empty_array');
	// add_filter('image_downsize', '__return_true');

	if(wpjam_get_content_width()){
		remove_filter('the_content', 'wp_make_content_images_responsive');
	}

	add_filter('the_content', function($content){
		if(doing_filter('get_the_excerpt')){
			return $content;
		}
		
		return wpjam_content_images($content);
	}, 5);

	add_filter('image_downsize', function($out, $id, $size){
		if(!wp_attachment_is_image($id)){	
			return false;
		}

		$meta		= wp_get_attachment_metadata($id);
		$img_url	= wp_get_attachment_url($id);	

		$size		= wpjam_parse_size($size);

		if($size['crop']){
			$size['width']	= min($size['width'],  $meta['width']);
			$size['height']	= min($size['height'],  $meta['height']);
		}else{
			list($width, $height)	= wp_constrain_dimensions($meta['width'], $meta['height'], $size['width'], $size['height']);

			$size['width']	= $width;
			$size['height']	= $height;
		}

		if($size['width'] < $meta['width'] || $size['height'] <  $meta['height']){
			$img_url	= wpjam_get_thumbnail($img_url, $size);
		}else{
			$img_url	= wpjam_get_thumbnail($img_url);
		}

		return [$img_url, $size['width'], $size['height'], 1];
	},10 ,3);

	add_filter('wp_resource_hints', function($urls, $relation_type){
		if($relation_type == 'dns-prefetch'){
			$urls[]	= CDN_HOST;
		}
		return $urls;
	}, 10, 2);

	if(wpjam_image_remote_method() == 'rewrite'){
		add_action('init',function(){
			add_rewrite_rule(CDN_NAME.'/([0-9]+)/image/([^/]+)?$', 'index.php?p=$matches[1]&'.CDN_NAME.'=$matches[2]', 'top');
		});

		// 远程图片的 Query Var
		add_filter('query_vars', function($query_vars) {
			$query_vars[] = CDN_NAME;
			return $query_vars;
		});

		// 远程图片加载模板
		add_action('template_redirect',	function(){
			if(get_query_var(CDN_NAME)){
				include(WPJAM_BASIC_PLUGIN_DIR.'template/image.php');
				exit;
			}
		}, 5);
	}

	add_filter('wpjam_html_replace', function($html){
		if(is_admin()){
			return $html;
		}

		$html	= wpjam_cdn_replace_local_hosts($html, false);
		$dirs	= wpjam_cdn_get_setting('dirs');
		$exts	= wpjam_cdn_get_setting('exts');
		$exts	= explode('|', $exts);

		if(is_login()){
			$exts	= array_diff($exts, ['js','css']);
		}

		$exts	= implode('|', $exts);

		if($exts){
			if($dirs){
				$dirs	= str_replace(['-','/'],['\-','\/'], $dirs);
				$regex	= '/'.str_replace('/','\/',LOCAL_HOST).'\/(('.$dirs.')\/[^\s\?\\\'\"\;\>\<]{1,}.('.$exts.'))([\"\\\'\)\s\]\?]{1})/';
				$html	= preg_replace($regex, CDN_HOST.'/$1$4', $html);
			}else{
				$regex	= '/'.str_replace('/','\/',LOCAL_HOST).'\/([^\s\?\\\'\"\;\>\<]{1,}.('.$exts.'))([\"\\\'\)\s\]\?]{1})/';
				$html	= preg_replace($regex, CDN_HOST.'/$1$3', $html);
			}
		}	

		return $html;
	},9);
}, 99);

add_filter('wpjam_post_thumbnail_url',	function ($thumbnail_url, $post){
	$thumbnail_orders	= wpjam_cdn_get_setting('post_thumbnail_orders') ?: [];

	if(empty($thumbnail_orders)){
		return $thumbnail_url;
	}

	foreach ($thumbnail_orders as $thumbnail_order) {
		if($thumbnail_order['type'] == 'first'){
			if($post_first_image = wpjam_get_post_first_image_url($post)){
				return $post_first_image;
			}
		}elseif($thumbnail_order['type'] == 'post_meta'){
			if($post_meta 	= $thumbnail_order['post_meta']){
				if($post_meta_url = get_post_meta($post->ID, $post_meta, true)){
					return $post_meta_url;
				}
			}
		}elseif($thumbnail_order['type'] == 'term'){
			if(!wpjam_cdn_get_setting('term_thumbnail_type')){
				continue;
			}

			$taxonomy	= $thumbnail_order['taxonomy'];

			if(empty($taxonomy)){
				continue;
			}

			$thumbnail_taxonomies	= $thumbnail_taxonomies ?? wpjam_cdn_get_setting('term_thumbnail_taxonomies');

			if(empty($thumbnail_taxonomies) || !in_array($taxonomy, $thumbnail_taxonomies)){
				continue;
			}

			$post_taxonomies	= $post_taxonomies ?? get_post_taxonomies($post);

			if(empty($post_taxonomies) || !in_array($taxonomy, $post_taxonomies)){
				continue;
			}
			
			if($terms = get_the_terms($post, $taxonomy)){
				foreach ($terms as $term) {
					if($term_thumbnail = wpjam_get_term_thumbnail_url($term)){
						return $term_thumbnail;
					}
				}
			}
		}
	}

	return $thumbnail_url;
}, 1, 2);

add_filter('wpjam_term_thumbnail_url', function($thumbnail_url, $term){
	if(!wpjam_cdn_get_setting('term_thumbnail_type')){
		return $thumbnail_url;
	}

	$thumbnail_taxonomies	= wpjam_cdn_get_setting('term_thumbnail_taxonomies');

	if(empty($thumbnail_taxonomies) || !in_array($term->taxonomy, $thumbnail_taxonomies)){
		return $thumbnail_url;
	}

	return get_term_meta($term->term_id, 'thumbnail', true);
}, 1, 2);

add_filter('wpjam_default_thumbnail_url', function($thumbnail_url){
	return wpjam_cdn_get_setting('default') ?? $thumbnail_url;
}, 1);

function wpjam_cdn_replace_local_hosts($html, $to_cdn=true){
	$local_hosts	= wpjam_cdn_get_setting('locals') ?: [];

	if($to_cdn){
		$local_hosts[]	= str_replace('https://', 'http://', LOCAL_HOST);
		$local_hosts[]	= str_replace('http://', 'https://', LOCAL_HOST);

		if(strpos(CDN_HOST, 'http://') === 0){
			$local_hosts[]	= str_replace('http://', 'https://', CDN_HOST);
		}
	}else{
		if(strpos(LOCAL_HOST, 'https://') !== false){
			$local_hosts[]	= str_replace('https://', 'http://', LOCAL_HOST);
		}else{
			$local_hosts[]	= str_replace('http://', 'https://', LOCAL_HOST);
		}
	}

	$local_hosts	= apply_filters('wpjam_cdn_local_hosts', $local_hosts);
	$local_hosts	= array_unique($local_hosts);
	$local_hosts	= array_map('untrailingslashit', $local_hosts);

	if($to_cdn){
		return str_replace($local_hosts, CDN_HOST, $html);
	}else{
		return str_replace($local_hosts, LOCAL_HOST, $html);
	}
}

function wpjam_get_content_width(){
	return intval(apply_filters('wpjam_content_image_width', wpjam_cdn_get_setting('width')));
}

function wpjam_content_images($content, $max_width=0){
	$content	= wpjam_cdn_replace_local_hosts($content, false);
	$max_width	= $max_width ?: wpjam_get_content_width();
	$search		= $replace = [];
	
	if(preg_match_all('/<img.*?src=[\'"](.*?)[\'"].*?>/i', $content, $matches)){
		$search		= $replace	= $matches[0];
		$img_urls	= $matches[1];

		foreach ($search as $i => $img_tag){
		 	$img_url	= $img_urls[$i];

		 	if(empty($img_url)){
		 		continue;
		 	}
		 	
		 	if(wpjam_is_remote_image($img_url)){
				if(wpjam_image_remote_method($img_url) == 'rewrite'){
					$img_url	= wpjam_get_content_remote_image_url($img_url);
				}else{
					continue;
				}
			}

			$size	= [
				'width'		=> 0,
				'height'	=> 0,
				'content'	=> true
			];

			preg_match_all('/(width|height)=[\'"]([0-9]+)[\'"]/i', $img_tag, $hw_matches);
			if($hw_matches[0]){
				$hw_arr	= array_flip($hw_matches[1]);
				$size	= array_merge($size, array_combine($hw_matches[1], $hw_matches[2]));
			}

			if($max_width) {
				if($size['width'] >= $max_width){
					if($size['height']){
						$size['height']	= intval(($max_width / $size['width']) * $size['height']);

						$index		= $hw_arr['height'];
						$img_tag	= str_replace($hw_matches[0][$index], 'height="'.$size['height'].'"', $img_tag);
					}
					
					$size['width']	= $max_width;
					$index			= $hw_arr['width'];
					$img_tag		= str_replace($hw_matches[0][$index], 'width="'.$size['width'].'"', $img_tag);

				}elseif($size['width'] == 0){
					if($size['height'] == 0){
						$size['width']	= $max_width;
					}
				}
			}

			$size['width']	= $size['width']*2;
			$size['height']	= $size['height']*2;
			
			$thumbnail		= wpjam_get_thumbnail($img_url, $size);
			$replace[$i]	= str_replace($img_urls[$i], $thumbnail, $img_tag);
		}
	}

	if($search){
		$content	= str_replace($search, $replace, $content);
	}

	return $content;
}

function wpjam_is_remote_image($img_url){
	$status = strpos($img_url, CDN_HOST) === false && strpos($img_url, LOCAL_HOST) === false;

	return apply_filters('wpjam_is_remote_image', $status, $img_url);
}

function wpjam_image_remote_method($img_url=''){
	$remote	= wpjam_cdn_get_setting('remote');

	if(!$remote){
		return '';	//	没开启选项
	}

	if($remote != 'download'){
		$remote	= 'rewrite';
	}

	if($remote == 'rewrite'){
		if(is_multisite()){
			return '';
		}

		if(!apache_mod_loaded('mod_rewrite', true) && empty($GLOBALS['is_nginx']) && !iis7_supports_permalinks()){
			return '';
		}
		
		if(!extension_loaded('gd') || get_option('permalink_structure') == false){
			return '';
		}
	}

	if($img_url){
		$exceptions	= wpjam_cdn_get_setting('exceptions');	// 后台设置不加载的远程图片

		if($exceptions){
			$exceptions	= explode("\n", $exceptions);
			foreach ($exceptions as $exception) {
				if(trim($exception) && strpos($img_url, trim($exception)) !== false ){
					return '';
				}
			}
		}
	}

	return $remote;
}

// 获取远程图片
function wpjam_get_content_remote_image_url($img_url, $post_id=null){
	$img_type = strtolower(pathinfo($img_url, PATHINFO_EXTENSION));
	if($img_type != 'gif'){
		$img_type	= ($img_type == 'png')?'png':'jpg';
		$post_id	= $post_id ?: get_the_ID();
		$img_url	= CDN_HOST.'/'.CDN_NAME.'/'.$post_id.'/image/'.md5($img_url).'.'.$img_type;
	}

	return $img_url;
}

function wpjam_attachment_url_to_postid($url){
	$post_id = wp_cache_get($url, 'attachment_url_to_postid');

	if($post_id === false){
		global $wpdb;

		$upload_dir	= wp_get_upload_dir();
		$path		= str_replace(parse_url($upload_dir['baseurl'], PHP_URL_PATH).'/', '', parse_url($url, PHP_URL_PATH));

		$post_id	= $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s", $path));

		wp_cache_set($url, $post_id, 'attachment_url_to_postid', DAY_IN_SECONDS);
	}

	return (int) apply_filters( 'attachment_url_to_postid', $post_id, $url );
}