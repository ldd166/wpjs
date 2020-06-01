<?php
class WPJAM_Verify{
	public static function verify(){
		if(self::verify_domain()){
			return 'verified';
		}

		$weixin_user	= self::get_weixin_user();

		if(empty($weixin_user) || empty($weixin_user['subscribe'])){
			return false;
		}

		if(time() - $weixin_user['last_update'] < DAY_IN_SECONDS) {
			return true;
		}

		$openid		= $weixin_user['openid'];
		$user_id	= get_current_user_id();

		$response	= wp_cache_get('wpjam_weixin_user_'.$openid, 'counts');

		if($response === false){
			$response	= wpjam_remote_request('http://jam.wpweixin.com/api/topic/user/get.json?openid='.$openid);

			wp_cache_set('wpjam_weixin_user_'.$openid, $response, 'counts');
		}

		if(is_wp_error($response)){
			$failed_times	= get_user_meta($user_id, 'wpjam_weixin_user_failed_times') ?: 0;
			$failed_times ++;

			if($failed_times >= 3){	// 重复三次
				delete_user_meta($user_id, 'wpjam_weixin_user_failed_times');
				delete_user_meta($user_id, 'wpjam_weixin_user');
			}else{
				update_user_meta($user_id, 'wpjam_weixin_user_failed_times', $failed_times);
			}

			return false;
		}

		$weixin_user	= $response['user'];

		if(empty($weixin_user) || !$weixin_user['subscribe']){
			delete_user_meta($user_id, 'wpjam_weixin_user');
			delete_user_meta($user_id, 'wpjam_weixin_user_failed_times');
			return false;
		}

		$weixin_user['last_update']	= time();

		update_user_meta($user_id, 'wpjam_weixin_user', $weixin_user);
		delete_user_meta($user_id, 'wpjam_weixin_user_failed_times');

		return true;
	}

	public static function verify_domain($id=0){
		return get_transient('wpjam_basic_verify');
	}

	public static function get_weixin_user(){
		return get_user_meta(get_current_user_id(), 'wpjam_weixin_user', true);
	}

	public static function get_openid(){
		$weixin_user	= self::get_weixin_user();

		if($weixin_user && isset($weixin_user['openid'])){
			return $weixin_user['openid'];
		}else{
			return '';
		}
	}

	public static function get_qrcode($key=''){
		$key	= $key?:md5(home_url().'_'.get_current_user_id());

		return wpjam_remote_request('http://jam.wpweixin.com/api/weixin/qrcode/create.json?key='.$key);
	}

	public static function bind_user($data){
		$response	= wpjam_remote_request('http://jam.wpweixin.com/api/weixin/qrcode/verify.json', [
			'method'	=>'POST',
			'body'		=> $data
		]);

		if(is_wp_error($response)){
			return $response;
		}

		$weixin_user =	$response['user'];

		$weixin_user['last_update']	= time();

		update_user_meta(get_current_user_id(), 'wpjam_weixin_user', $weixin_user);

		return $weixin_user;
	}

	public static function get_messages(){
		$messages	= [];

		if(self::get_openid()){
			$user_id	= get_current_user_id();
			$messages	= get_transient('wpjam_topic_messages_'.get_current_user_id());

			if($messages === false){
				$messages = wpjam_remote_request('http://jam.wpweixin.com/api/topic/messages.json',[
					'method'	=> 'POST',
					'headers'	=> ['openid'=>self::get_openid()]
				]);

				if(is_wp_error($messages)){
					$messages = array('unread_count'=>0, 'messages'=>array());
				}
				
				set_transient('wpjam_topic_messages_'.get_current_user_id(), $messages, 900);
			}
		}

		return $messages;
	}

	public static function read_messages(){
		$result	= $messages	= self::get_messages();

		if($messages['unread_count']){

			wpjam_remote_request('http://jam.wpweixin.com/api/topic/messages/read.json',[
				'headers'	=> ['openid'=>self::get_openid()]
			]);

			$messages['unread_count'] = 0;
			
			foreach ($messages['messages'] as $key => &$message) {
				$message['status'] = 1;
			}

			set_transient('wpjam_topic_messages_'.get_current_user_id(), $messages, 900);
		}

		return $result;
	}
}