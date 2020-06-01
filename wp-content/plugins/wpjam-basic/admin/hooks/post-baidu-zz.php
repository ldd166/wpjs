<?php
// 直接新增
add_action('save_post', function($post_id, $post, $update){
	if((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)){
		return;
	}

	if(!$update && $post->post_status == 'publish'){
		$post_link	= apply_filters('baiduz_zz_post_link', get_permalink($post_id), $post_id);

		$args	= [];
		
		if(!empty($_POST['baidu_zz_daily'])){
			$args['type']	= 'daily';
		}
		
		wpjam_notify_baidu_zz($post_link, $args);
	}
}, 10, 3);

// 修改文章
add_action('post_updated', function($post_id, $post_after, $post_before){
	if((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)){
		return;
	}

	if($post_after->post_status == 'publish'){
		
		$baidu_zz_daily	= $_POST['baidu_zz_daily'] ?? false;

		if($baidu_zz_daily || wp_cache_get($post_id, 'wpjam_baidu_zz_notified') === false){
			wp_cache_set($post_id, true, 'wpjam_baidu_zz_notified', HOUR_IN_SECONDS);

			$post_link	= apply_filters('baiduz_zz_post_link', get_permalink($post_id), $post_id);

			$args	= [];
			
			if($baidu_zz_daily){
				$args['type']	= 'daily';
			}

			wpjam_notify_baidu_zz($post_link, $args);
		}
	}
}, 10, 3);

add_action('post_submitbox_misc_actions', function (){ ?>
	<div class="misc-pub-section" id="baidu_zz_section">
		<input type="checkbox" name="baidu_zz_daily" id="baidu_zz" value="1">
		<label for="baidu_zz_daily">提交给百度站长快速收录</label>
	</div>
<?php },11);

add_action('wpjam_'.$post_type.'_posts_actions', function($actions){
	return $actions + ['baidu-zz'=>['title'=>'提交到百度站长', 'bulk'=>true,	'direct'=>true]];
});

add_filter('wpjam_'.$post_type.'_posts_list_action', function($result, $list_action, $post_id, $data){
	if($list_action == 'baidu-zz'){

		$urls	= '';

		if(is_array($post_id)){		
			$post_ids	= $post_id;

			foreach ($post_ids as $post_id) {
				if(get_post($post_id)->post_status == 'publish'){
					if(wp_cache_get($post_id, 'wpjam_baidu_zz_notified') === false){
						wp_cache_set($post_id, true, 'wpjam_baidu_zz_notified', HOUR_IN_SECONDS);
						$urls	.= apply_filters('baiduz_zz_post_link', get_permalink($post_id))."\n";	
					}
				}
			}
		}else{
			if(get_post($post_id)->post_status == 'publish'){
				if(wp_cache_get($post_id, 'wpjam_baidu_zz_notified') === false){
					wp_cache_set($post_id, true, 'wpjam_baidu_zz_notified', HOUR_IN_SECONDS);
					$urls	.= apply_filters('baiduz_zz_post_link', get_permalink($post_id))."\n";	
				}else{
					return new WP_Error('has_submited', '一小时内已经提交过了');
				}
			}else{
				return new WP_Error('invalid_post_status', '未发布的文章不能同步到百度站长');
			}
		}

		if($urls){
			wpjam_notify_baidu_zz($urls);
		}else{
			return new WP_Error('empty_urls', '没有需要提交的链接');
		}

		return true;
	}

	return $result;
}, 10, 4);

add_action('admin_head', function(){
	?>
	<style type="text/css">
	#post-body #baidu_zz_section:before {
		content: "\f103";
		color:#82878c;
		font: normal 20px/1 dashicons;
		speak: none;
		display: inline-block;
		margin-left: -1px;
		padding-right: 3px;
		vertical-align: top;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
	}

	</style>
	<?php
});
