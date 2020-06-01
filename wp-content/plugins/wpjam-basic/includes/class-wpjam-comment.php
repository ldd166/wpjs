<?php
class WPJAM_Comment{
	public static function get($comment_id){
		$comment	= get_comment($comment_id, ARRAY_A);

		$post_id	= $comment['comment_post_ID'];
		$comment_id	= $comment['comment_ID'];

		$sticky_comments	= get_post_meta($post_id, 'sticky_comments', true) ?: [];

		$comment['comment_sticky']	= $comment['comment_approved'] && in_array($comment_id, $sticky_comments);
		$comment['digg_count']		= get_comment_meta($comment_id, 'digg_count', true) ?: '';

		return $comment;
	}

	public static function insert($comment_data){
		$comment_post_ID	= absint($comment_data['post_id']);
		$comment_type		= $comment_data['type'] ?? '';
		$comment_type		= $comment_type == 'comment' ? '' : $comment_type;

		if($comment_type == ''){
			if(empty($comment_post_ID)){
				return new WP_Error('empty_post_id', 'post_id不能为空');
			}

			$post	= get_post($comment_post_ID);
			if(empty($post)){
				return new WP_Error('invalid_post_id', '非法 post_id');
			}

			if($post->comment_status == 'closed'){
				return new WP_Error('comment_closed', '已关闭留言');
			}

			if('publish' != $post->post_status){
				return new WP_Error( 'invalid_post_status', '文章未发布，不能评论。' );
			}

			if(!post_type_supports($post->post_type, 'comments')){
				return new WP_Error('action_not_supported', '操作不支持');
			}
		}

		if(is_user_logged_in()){
			$user_id	= get_current_user_id();
			$user		= get_userdata($user_id);

			$comment_author_email	= $user->user_email;
			$comment_author			= $user->display_name ?: $user->user_login;
			$comment_author_url		= $user->user_url;
		}else{
			if(get_option('comment_registration')){
				return new WP_Error('not_logged_in', '只支持登录用户操作');	
			}

			$commenter	= wp_get_current_commenter();

			if(is_wp_error($commenter)){
				return $commenter;
			}

			$comment_author			= $commenter['comment_author'];
			$comment_author_email	= $commenter['comment_author_email'];
			$comment_author_url		= esc_url($commenter['comment_author_url']);
		}

		$comment_content	= $comment_data['comment'] ?? '';

		if($comment_type == ''){
			$comment_content	= trim(wp_strip_all_tags($comment_content));

			if(empty($comment_content)){
				return new WP_Error('require_valid_comment', '评论内容不能为空。');
			}
		}

		if(isset($comment_data['parent'])){
			$comment_parent = absint($comment_data['parent']);
		}else{
			$comment_parent		= 0;
		}

		$comment_author_IP	= $comment_data['ip'] ?? preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);

		$comment_agent		= $comment_data['agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
		$comment_agent		= substr($comment_agent, 0, 254);

		$comment_date		= $comment_data['date'] ?? current_time('mysql');
		$comment_date_gmt	= $comment_data['date_gmt'] ?? current_time('mysql', 1);

		$comment_meta		= $comment_data['meta'] ?? [];
		$comment_meta		= array_filter($comment_meta);
		
		$comment_data = compact(
			'comment_post_ID',
			'comment_author',
			'comment_author_email',
			'comment_author_url',
			'comment_content',
			'comment_type',
			'comment_parent',
			'comment_author_IP',
			'comment_agent',
			'comment_date',
			'comment_date_gmt',
			'user_id',
			'comment_meta'
		);

		$comment_data	= apply_filters('preprocess_comment', $comment_data);

		$comment_data	= wp_slash($comment_data);
		$comment_data	= wp_filter_comment($comment_data);

		$comment_approved	= 1;

		if($comment_type == ''){
			$comment_approved = wp_allow_comment($comment_data, $avoid_die=true);
			if(is_wp_error($comment_approved)) {
				if($comment_approved->get_error_code() == 'comment_duplicate'){
					return new WP_Error('comment_duplicate', '检测到重复评论，您似乎已经提交过这条评论了！');
				}elseif($comment_approved->get_error_code() == 'comment_flood'){
					return new WP_Error('comment_flood', '您提交评论的速度太快了，请稍后再发表评论。');
				}else{
					return $comment_approved;
				}
			}
		}

		$comment_data['comment_approved']	= $comment_approved;

		$comment_id	= wp_insert_comment($comment_data);
		
		if(!$comment_id) {
			global $wpdb;

			$fields = ['comment_author', 'comment_author_email', 'comment_author_url', 'comment_content'];

			foreach($fields as $field){
				$comment_data[$field]	= $wpdb->strip_invalid_text_for_column($wpdb->comments, $field, $comment_data[$field]);
			}

			$comment_data	= wp_filter_comment($comment_data);
			$comment_id		= wp_insert_comment($comment_data);
		}

		if(!$comment_id){
			return new WP_Error( 'comment_save_error', '评论保存失败，请稍后重试！', 500 );
		}

		do_action('comment_post', $comment_id, $comment_data['comment_approved'], $comment_data);

		return $comment_id;
	}

	public static function update($comment_id, $data){
		$comment_data	= self::get_comment($comment_id, ARRAY_A);

		if(is_wp_error($comment_data)){
			return $comment_data;
		}

		if(isset($data['comment'])){
			$comment_data['comment_content']	= $data['comment'];
		}

		if(isset($data['approved'])){
			$comment_data['comment_approved']	= $data['approved'];
		}

		$result	= wp_update_comment($comment_data);

		if(!$result){
			return new WP_Error('comment_update_failed', '评论更新失败！');
		}

		return $result;
	}
	
	public static function approve($comment_id){
		return self::update($comment_id, ['approved'=>1]);
	}

	public static function unapprove($comment_id){
		self::unstick($comment_id);

		return self::update($comment_id, ['approved'=>0]);
	}

	public static function stick($comment_id){
		$comment	= self::get($comment_id);

		if(!$comment['comment_sticky']){
			$post_id	= $comment['comment_post_ID'];
			$comment_id	= $comment['comment_ID'];

			$sticky_comments	= get_post_meta($post_id, 'sticky_comments', true) ?: [];

			if(count($sticky_comments) >= 5){
				return new WP_Error('sticky_comments_over_quato', '最多5个置顶评论。');
			}
			
			array_unshift($sticky_comments, $comment_id);

			update_post_meta($post_id, 'sticky_comments', array_values($sticky_comments));
		}

		return true;
	}

	public static function unstick($comment_id){
		$comment	= self::get($comment_id);

		if($comment['comment_sticky']){
			$post_id	= $comment['comment_post_ID'];
			$comment_id	= $comment['comment_ID'];

			$sticky_comments	= get_post_meta($post_id, 'sticky_comments', true);
			$sticky_comments	= array_diff($sticky_comments, [$comment_id]);

			if($sticky_comments){
				update_post_meta($post_id, 'sticky_comments', array_values($sticky_comments));
			}else{
				delete_post_meta($post_id, 'sticky_comments');
			}
		}

		return true;
	}

	public static function delete($comment_id, $force_delete=false){
		$comment	= self::get_comment($comment_id);

		if(is_wp_error($comment)){
			return $comment;
		}

		if(is_user_logged_in()){
			if($comment->user_id != get_current_user_id() && !current_user_can('manage_options')){
				return new WP_Error('bad_authentication', '你不能删除别人的评论');
			}
		}else{
			if($comment->user_id){
				return new WP_Error('bad_authentication', '你不能删除别人的评论');
			}

			$commenter	= wp_get_current_commenter();

			if(is_wp_error($commenter)){
				return $commenter;
			}

			$author_email	= $commenter['comment_author_email'];
			
			if($comment->comment_author_email != $author_email){
				return new WP_Error('bad_authentication', '你不能删除别人的评论');
			}
		}

		return wp_delete_comment($comment_id, $force_delete);
	}

	public static function action($post_id, $action='fav'){
		if(is_array($post_id)){
			$comment_data	= $post_id;
			$post_id		= absint($comment_data['post_id']);
		}else{
			$comment_data	= compact('post_id');
			$post_id		= absint($post_id);
		}

		if(empty($post_id)){
			return new WP_Error('empty_post_id', 'post_id不能为空');
		}

		$post	= get_post($post_id);

		if(empty($post)){
			return new WP_Error('invalid_post_id', '非法 post_id');
		}

		if(in_array($action, ['unlike', 'unfav'])){
			$comment_type	= str_replace('un', '', $action);
			$status			= 0;
		}else{
			$comment_type	= $action;
			$status			= 1;
		}

		if(!post_type_supports($post->post_type, $comment_type) && !post_type_supports($post->post_type, $comment_type.'s')){
			return new WP_Error('action_not_supported', '操作不支持');
		}

		$did	= self::did_action($post_id, $comment_type);

		if(is_wp_error($did)){
			return $did;
		}

		if($did){
			if($status == 1){
				return true;
			}

			$result	= wp_delete_comment($did, $force_delete=true);
		}else{
			if($status != 1){
				return true;
			}

			$comment_data['type']	= $comment_type;
			$result	= self::insert($comment_data);
		}

		if(!is_wp_error($result)){
			self::update_count($post_id, $comment_type);
		}

		return $result;
	}

	public static function did_action($post_id, $action='fav'){
		$comments	= get_comments(['post_id'=>$post_id, 'type'=>$action, 'order'=>'ASC']);

		if(empty($comments)){
			return 0;
		}

		if(is_user_logged_in()){
			$comments	= wp_list_pluck($comments, 'comment_ID', 'user_id');
			$user_id	= get_current_user_id();
			if(isset($comments[$user_id])){
				return $comments[$user_id];
			}
		}else{
			if(get_option('comment_registration')){
				return new WP_Error('not_logged_in', '只支持登录用户操作');	
			}

			$commenter	= wp_get_current_commenter();

			if(is_wp_error($commenter)){
				return $commenter;
			}

			$author_email	= $commenter['comment_author_email'];
			
			$comments		= wp_list_pluck($comments, 'comment_ID', 'comment_author_email');

			if(isset($comments[$author_email])){
				return $comments[$author_email];
			}
		}
		
		return 0;
	}

	public static function digg($comment_id, $action='digg'){
		$comment	= self::get_comment($comment_id);

		if(is_wp_error($comment)){
			return $comment;
		}

		$post	= get_post($comment->comment_post_ID);

		if(!post_type_supports($post->post_type, 'comment_digg')){
			return new WP_Error('comment_digg_not_supported', '不支持 comment digg');
		}

		// CAS 处理
		$retry_times	= 10;

		do{
			$is_digged	= self::is_digged($comment_id);

			if(($action == 'digg' && $is_digged) || ($action == 'undigg' && !$is_digged)){
				return true;
			}

			$new_digg_data	= $prev_digg_data = get_comment_meta($comment_id, 'digg_data', true) ?: [];

			if(is_user_logged_in()){
				$user_ids	= $prev_digg_data['user_ids'] ?? [];

				if($action == 'digg'){
					$user_ids[]	= get_current_user_id(); 
				}else{
					$user_ids	= array_values(array_diff($user_ids, [get_current_user_id()])); 
				}

				$new_digg_data['user_ids']	= $user_ids;
			}else{
				$commenter	= wp_get_current_commenter();

				if(is_wp_error($commenter)){
					return $commenter;
				}

				$author_email	= $commenter['comment_author_email'];

				$user_emails	= $prev_digg_data['user_emails'] ?? [];

				if($action == 'digg'){
					$user_emails[]	= $author_email;
				}else{
					$user_emails	= array_values(array_diff($user_emails, [$author_email])); 
				}
				
				$new_digg_data['user_emails']	= $user_emails;
			}

			$updated	= update_comment_meta($comment_id, 'digg_data', $new_digg_data, $prev_digg_data);

			if($updated){
				$user_ids		= $new_digg_data['user_ids'] ?? [];
				$user_emails	= $new_digg_data['user_emails'] ?? [];

				update_comment_meta($comment_id, 'digg_count', count($user_ids) + count($user_emails));
			}
		} while(!$updated && $retry_times > 0);

		return $updated;
	}

	public static function is_digged($comment_id){
		$digg_data	= get_comment_meta($comment_id, 'digg_data', true) ?: [];

		if(empty($digg_data)){
			return false;
		}

		if(is_user_logged_in()){
			$user_ids	= $digg_data['user_ids'] ?? [];

			$is_digged	= $user_ids && in_array(get_current_user_id(), $user_ids);
		}else{
			$commenter	= wp_get_current_commenter();

			if(is_wp_error($commenter)){
				$is_digged		= false;
			}else{
				$author_email	= $commenter['comment_author_email'];
				$user_emails	= $digg_data['user_emails'] ?? [];

				$is_digged		= $user_emails && in_array($author_email, $user_emails);
			}
		}

		return $is_digged;
	}

	private static function update_count($post_id, $action='like', $meta_key=''){
		$comments	= get_comments(['post_id'=>$post_id, 'type'=>$action, 'order'=>'ASC']);
		$meta_key	= $meta_key ?: $action.'s';

		update_post_meta($post_id, $meta_key, count($comments));
	}

	public static function get_comment($comment_id, $output=OBJECT){
		$comment	= get_comment($comment_id, $output);

		if(empty($comment)){
			return new WP_Error('invalid_comment_id', '无效的 comment_id');
		}

		if($output == OBJECT){
			$post	= get_post($comment->comment_post_ID);	
		}else{
			$post	= get_post($comment['comment_post_ID']);
		}

		if(empty($post)){
			return new WP_Error('invalid_comment_id', '无效的 comment_id');
		}

		return $comment;
	}

	public static function get_comments($args=[], &$next_cursor=0){
		$args	= wp_parse_args($args, [
			'post_id'	=> 0,
			'order'		=> 'ASC',
			'type'		=> 'comment',

			'sticky_comments'	=> [],

			'update_comment_meta_cache'	=> true,
			'update_comment_post_cache'	=> true,
		]);

		$comment_type	= $args['type'];
		$comment_type	= $comment_type ?: 'comment';
		$post_id		= intval($args['post_id']);

		if(empty($post_id)){
			$args['no_found_rows']	= false;
		}else{
			$args['status']			= 'approve';
		}

		$comment_args	= $args;

		if($comment_type == 'comment'){
			if($post_id){
				$post_type	= get_post($post_id)->post_type;

				if(post_type_supports($post_type, 'comment_digg')){
					$comment_args['orderby']	= 'digg_count';
				}
			}elseif(isset($args['post_type'])){
				$post_type	= $args['post_type'];
			}else{
				$post_type	= null;
			}

			$comment_args['hierarchical']	= false;

			if($post_type && in_array(get_post_type_support_value($post_type, 'reply_type'), ['admin_reply', 'all'])){
				$comment_args['hierarchical']	= 'threaded';
			}
		}

		if(is_user_logged_in()){
			if(empty($post_id)){
				$comment_args['user_id']	= get_current_user_id();
			}else{
				$comment_args['include_unapproved']	= get_current_user_id();
			}
		}else{
			$commenter	= wp_get_current_commenter();

			if(empty($post_id)){
				if(get_option('comment_registration')){
					return new WP_Error('not_logged_in', '只支持登录用户操作');	
				}

				if(is_wp_error($commenter)){
					return $commenter;
				}

				$comment_args['author_email']	= $commenter['comment_author_email'];

			}else{
				if(!is_wp_error($commenter)){
					$comment_args['include_unapproved']	= [$commenter['comment_author_email']];
				}
			}
		}

		$comment_query	= new WP_Comment_Query($comment_args);

		if(empty($comment_query->comments)){
			return [];
		}

		if(empty($post_id)){
			$posts_json	= [];

			foreach ($comment_query->comments as $comment){
				$post_json	= wpjam_get_post($comment->comment_post_ID, $args);

				if($post_json){
					$post_json[$comment_type]	= self::parse_for_json($comment, $args);
					$posts_json[]				= $post_json;
				}
			}

			if($comment_query->max_num_pages > 1 && $posts_json){
				$next_cursor	= end($posts_json)[$comment_type]['timestamp'];
			}

			return $posts_json;
		}else{

			$comments_json	= [];
		
			foreach($comment_query->comments as $comment){
				$comments_json[]	= self::parse_for_json($comment, $args);
			}
			
			return $comments_json;
		}
	}

	public static function parse_for_json($comment, $args=[]){
		$comment	= self::get_comment($comment);

		if(is_wp_error($comment)){
			return [];
		}

		$timestamp		= strtotime($comment->comment_date_gmt);
		$comment_id		= $comment->comment_ID;
		$comment_type	= $comment->comment_type ?: 'comment';
		$post_id		= $comment->comment_post_ID;
		$post_type		= get_post($post_id)->post_type;

		$author			= self::get_author($comment);

		$comment_json	= [
			'id'		=> intval($comment_id),
			'post_id'	=> intval($post_id),
			'timestamp'	=> $timestamp,
			'type'		=> $comment_type
		];

		if($comment_type == 'like' || $comment_type == 'fav'){
			$comment_json	= array_merge($comment_json, $author);
		}else{
			$sticky_comments			= $args['sticky_comments'] ?? [];

			$comment_json['time']		= wpjam_human_time_diff($timestamp);
			$comment_json['content']	= wp_strip_all_tags($comment->comment_content);

			$images	= get_comment_meta($comment_id, 'images', true) ?: [];

			if($images){
				if(count($images) > 1){
					array_walk($images, function(&$image){
						$image = [
							'thumb'		=> wpjam_get_thumbnail($image, '200x200'),
							'original'	=> wpjam_get_thumbnail($image, ['width'=>1080])
						];
					});
				}else{
					array_walk($images, function(&$image){
						$image = [
							'thumb'		=> wpjam_get_thumbnail($image, ['width'=>300]),
							'original'	=> wpjam_get_thumbnail($image, ['width'=>1080])
						];
					});
				}
			}

			$comment_json['images']		= $images;

			if(post_type_supports($post_type, 'rating')){
				$comment_json['rating']		= intval(get_comment_meta($comment_id, 'rating', true));
			}

			$comment_json['author']		= $author;
			$comment_json['user_id']	= $author['user_id'];
			$comment_json['parent']		= intval($comment->comment_parent);

			$comment_json['approved']	= intval($comment->comment_approved);

			if($comment->comment_parent == 0 && get_post_type_support_value($post_type, 'reply_type') != 'disabled'){
				$comment_json['is_sticky']	= intval($comment->comment_approved && $sticky_comments && in_array($comment_id, $sticky_comments));	
			}
			

			if(post_type_supports($post_type, 'comment_digg')){
				$comment_json['digg_count']	= intval(get_comment_meta($comment_id, 'digg_count', true));

				if($comment_json['digg_count']){
					$comment_json['is_digged']	= self::is_digged($comment_id);
					// $comment_json['digg_data']	= get_comment_meta($comment_id, 'digg_data', true);
				}else{
					$comment_json['is_digged']	= false; 
				}
			}

			if($comment_type == 'comment'){
				
				$comment_json['reply_to']	= '';

				if(in_array(get_post_type_support_value($post_type, 'reply_type'), ['admin_reply', 'all'])){
					if($comment_json['parent']){
						if(get_post_type_support_value($post_type, 'reply_type') == 'all'){
							if(empty($args['top_comment_id']) || $args['top_comment_id'] != $comment_json['parent']){
								$parent_comment	= get_comment($comment_json['parent']);
								$comment_json['reply_to']	= $parent_comment ? $parent_comment->comment_author : '';
							}
						}else{
							$comment_json['author']	= [];
						}
					}else{
						$comment_children = $comment->get_children([
							'format'	=> 'flat',
							'status'	=> $args['status'] ?? 'all'
						]);

						$comment_json['children']	= [];

						if($comment_children){
							$args['top_comment_id']	= $comment_json['id'];

							foreach($comment_children as $comment_child){
								if(get_post_type_support_value($post_type, 'reply_type') == 'admin_reply'){
									if($comment_child->user_id && user_can($comment_child->user_id, 'manage_options')){
										$comment_json['children'][]	= self::parse_for_json($comment_child, $args);
									}
								}else{
									$comment_json['children'][]	= self::parse_for_json($comment_child, $args);
								}
							}
						}
					}
				}
			}
		}

		return apply_filters('wpjam_comment_json', $comment_json, $comment_id, $comment);
	}

	public static function get_author($comment){
		$email		= $comment->comment_author_email;
		$author		= $comment->comment_author;
		$user_id	= intval($comment->user_id);
		$avatar		= get_avatar_url($comment, 200);

		$userdata	= $user_id ? get_userdata($user_id) : null;
		$nickname	= $userdata ? $userdata->display_name : $author;

		return compact('email', 'author', 'nickname', 'user_id',  'avatar');
	}
}