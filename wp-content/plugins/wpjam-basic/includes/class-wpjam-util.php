<?php
abstract class WPJAM_API{
	public static function method_allow($method, $send=true){
		if ($_SERVER['REQUEST_METHOD'] != $method) {
			$wp_error = new WP_Error('method_not_allow', '接口不支持 '.$_SERVER['REQUEST_METHOD'].' 方法，请使用 '.$method.' 方法！');
			if($send){
				self::send_json($wp_error);
			}else{
				return $wp_error;
			}
		}else{
			return true;		
		}
	}

	private static function get_post_input(){
		static $post_input;
		if(!isset($post_input)) {
			$post_input	= file_get_contents('php://input');
			// trigger_error(var_export($post_input,true));
			if(is_string($post_input)){
				$post_input	= @self::json_decode($post_input);
			}
		}

		return $post_input;
	}

	public static function get_parameter($parameter, $args=[]){
		$value		= null;
		$method		= !empty($args['method']) ? strtoupper($args['method']) : 'GET';

		if ($method == 'GET') {
			if(isset($_GET[$parameter])){
				$value = $_GET[$parameter];
			}
		} elseif ($method == 'POST') {
			if(empty($_POST)){
				$post_input	= self::get_post_input();

				if(is_array($post_input) && isset($post_input[$parameter])){
					$value = $post_input[$parameter];
				}
			}else{
				if(isset($_POST[$parameter])){
					$value = $_POST[$parameter];
				}
			}
		} else {
			if(!isset($_GET[$parameter]) && empty($_POST)){
				$post_input	= self::get_post_input();
				
				if(is_array($post_input) && isset($post_input[$parameter])){
					$value = $post_input[$parameter];
				}
			}else{
				if(isset($_REQUEST[$parameter])){
					$value = $_REQUEST[$parameter];
				}
			}
		}

		if(is_null($value) && isset($args['default'])){
			return $args['default'];
		}

		$validate_callback	= $args['validate_callback'] ?? '';

		$send	= $args['send'] ?? true;

		if($validate_callback && is_callable($validate_callback)){
			$result	= call_user_func($validate_callback, $value);

			if($result === false){
				$wp_error = new WP_Error('invalid_parameter', '非法参数：'.$parameter);

				if($send){
					self::send_json($wp_error);
				}else{
					return $wp_error;
				}
			}elseif(is_wp_error($result)){
				if($send){
					self::send_json($result);
				}else{
					return $result;
				}
			}
		}else{
			if(!empty($args['required']) && is_null($value)) {
				$wp_error = new WP_Error('missing_parameter', '缺少参数：'.$parameter);

				if($send){
					self::send_json($wp_error);
				}else{
					return $wp_error;
				}
			}

			$length	= $args['length'] ?? 0;
			$length	= intval($length);

			if($length && (mb_strlen($value) < $length)){
				$wp_error = new WP_Error('short_parameter', $parameter.' 参数长度不能少于 '.$length);

				if($send){
					self::send_json($wp_error);
				}else{
					return $wp_error;
				}
			}
		}

		$sanitize_callback	= $args['sanitize_callback'] ?? '';

		if($sanitize_callback && is_callable($sanitize_callback)){
			$value	= call_user_func($sanitize_callback, $value);
		}else{
			if(!empty($args['type']) && $args['type'] == 'int' && $value) {
				$value	= intval($value);
			}
		}
		
		return $value;
	}

	public static function get_data_parameter($parameter, $args=[]){
		$value		= null;

		if(isset($_GET[$parameter])){
			$value	= $_GET[$parameter];
		}elseif(isset($_REQUEST['data'])){
			$data		= wp_parse_args($_REQUEST['data']);
			$defaults	= !empty($_REQUEST['defaults']) ? wp_parse_args($_REQUEST['defaults']) : [];
			$data		= wpjam_array_merge($defaults, $data);

			if(isset($data[$parameter])){
				$value	= $data[$parameter];
			}
		}

		if(is_null($value) && isset($args['default'])){
			return $args['default'];
		}

		$sanitize_callback	= $args['sanitize_callback'] ?? '';

		if(is_callable($sanitize_callback)){
			$value	= call_user_func($sanitize_callback, $value);
		}

		return $value;
	}

	public static function get_option($option_name, $blog_id=0){
		if(is_multisite()){
			if(is_network_admin()){
				return get_site_option($option_name);
			}else{
				
				if($blog_id){
					$option	= get_blog_option($blog_id, $option_name) ?: [];
				}else{
					$option	= get_option($option_name) ?: [];	
				}

				if(apply_filters('wpjam_option_use_site_default', false, $option_name)){
					$site_option	= get_site_option($option_name) ?: [];
					$option			= $option + $site_option;
				}

				return $option;
			}
		}else{
			return get_option($option_name) ?: [];
		}
	}

	public static function get_setting($option, $setting_name, $blog_id=0){
		if(is_string($option)) {
			$option = self::get_option($option, $blog_id);
		}

		if($option && isset($option[$setting_name])){
			$value	= $option[$setting_name];
		}else{
			return null;
		}

		if($value && is_string($value)){
			return  str_replace("\r\n", "\n", trim($value));
		}else{
			return $value;
		}
	}

	// 更新设置
	public static function update_setting($option_name, $setting_name, $setting_value, $blog_id=0){
		$option	= self::get_option($option_name, $blog_id);
		$option[$setting_name]	= $setting_value;

		if($blog_id && is_multisite()){
			return update_blog_option($blog_id, $option_name, $option);
		}else{
			return update_option($option_name, $option);
		}
	}

	public static function delete_setting($option_name, $setting_name, $blog_id=0){
		$option	= self::get_option($option_name, $blog_id);

		if(isset($option[$setting_name])){
			unset($option[$setting_name]);
		}

		if($blog_id && is_multisite()){
			return update_blog_option($blog_id, $option_name, $option);
		}else{
			return update_option($option_name, $option);
		}
	}

	// 1. $img_url 
	// 2. $img_url, array('width'=>100, 'height'=>100)	// 这个为最标准版本
	// 3. $img_url, 100x100
	// 4. $img_url, 100
	// 5. $img_url, array(100,100)
	// 6. $img_url, array(100,100), $crop=1, $retina=1
	// 7. $img_url, 100, 100, $crop=1, $retina=1
	public static function get_thumbnail(){
		$args_num	= func_num_args();
		$args		= func_get_args();

		$img_url	= $args[0];

		if(strpos($img_url, '?') === false){
			$img_url	= str_replace(['%3A','%2F'], [':','/'], urlencode(urldecode($img_url)));	// 中文名
		}

		if($args_num == 1){	
			// 1. $img_url 简单替换一下 CDN 域名

			$thumb_args = [];
		}elseif($args_num == 2){		
			// 2. $img_url, ['width'=>100, 'height'=>100]	// 这个为最标准版本
			// 3. $img_url, [100,100]
			// 4. $img_url, 100x100
			// 5. $img_url, 100		

			$thumb_args = self::parse_size($args[1]);
		}else{
			if(is_numeric($args[1])){
				// 6. $img_url, 100, 100, $crop=1, $retina=1

				$width	= $args[1] ?? 0;
				$height	= $args[2] ?? 0;
				$crop	= $args[3] ?? 1;
				// $retina	= $args[4] ?? 1;
			}else{
				// 7. $img_url, array(100,100), $crop=1, $retina=1

				$size	= self::parse_size($args[1]);
				$width	= $size['width'];
				$height	= $size['height'];
				$crop	= $args[2]??1;
				// $retina	= $args[3]??1;
			}

			// $width		= intval($width)*$retina;
			// $height		= intval($height)*$retina;

			$thumb_args = compact('width','height','crop');
		}

		return apply_filters('wpjam_thumbnail', $img_url, $thumb_args);
	}

	public static function parse_size($size, $retina=1){
		global $content_width;	

		$_wp_additional_image_sizes = wp_get_additional_image_sizes();

		if(is_array($size)){
			if(wpjam_is_assoc_array($size)){
				$size['width']	= $size['width'] ?? 0;
				$size['height']	= $size['height'] ?? 0;
				$size['width']	*= $retina;
				$size['height']	*= $retina;
				$size['crop']	= !empty($size['width']) && !empty($size['height']);
				return $size;
			}else{
				$width	= intval($size[0]??0);
				$height	= intval($size[1]??0);
				$crop	= $width && $height;
			}
		}else{
			if(strpos($size, 'x')){
				$size	= explode('x', $size);
				$width	= intval($size[0]);
				$height	= intval($size[1]);
				$crop	= $width && $height;
			}elseif(is_numeric($size)){
				$width	= $size;
				$height	= 0;
				$crop	= false;
			}elseif($size == 'thumb' || $size == 'thumbnail'){
				$width	= intval(get_option('thumbnail_size_w'));
				$height = intval(get_option('thumbnail_size_h'));
				$crop	= get_option('thumbnail_crop');

				if(!$width && !$height){
					$width	= 128;
					$height	= 96;
				}

			}elseif($size == 'medium'){

				$width	= intval(get_option('medium_size_w')) ?: 300;
				$height = intval(get_option('medium_size_h')) ?: 300;
				$crop	= get_option('medium_crop');

			}elseif( $size == 'medium_large' ) {

				$width	= intval(get_option('medium_large_size_w'));
				$height	= intval(get_option('medium_large_size_h'));
				$crop	= get_option('medium_large_crop');

				if(intval($content_width) > 0){
					$width	= min(intval($content_width), $width);
				}

			}elseif($size == 'large'){

				$width	= intval(get_option('large_size_w')) ?: 1024;
				$height	= intval(get_option('large_size_h')) ?: 1024;
				$crop	= get_option('large_crop');

				if (intval($content_width) > 0) {
					$width	= min(intval($content_width), $width);
				}
			}elseif(isset($_wp_additional_image_sizes) && isset($_wp_additional_image_sizes[$size])){
				$width	= intval($_wp_additional_image_sizes[$size]['width']);
				$height	= intval($_wp_additional_image_sizes[$size]['height']);
				$crop	= $_wp_additional_image_sizes[$size]['crop'];

				if(intval($content_width) > 0){
					$width	= min(intval($content_width), $width);
				}
			}else{
				$width	= 0;
				$height	= 0;
				$crop	= 0;
			}
		}

		$width	= $width * $retina;
		$height	= $height * $retina;

		return compact('width','height', 'crop');
	}

	public static function parse_shortcode_attr($str,  $tagnames=null){
		$pattern = get_shortcode_regex([$tagnames]);

		if(preg_match("/$pattern/", $str, $m)){
			return shortcode_parse_atts( $m[3] );
		}else{
			return [];
		}		
	}

	public static function human_time_diff($from,  $to=0) {
		$to		= ($to)?:time();
		$day	= date('Y-m-d',$from);
		$today	= date('Y-m-d');
		
		$secs	= $to - $from;	//距离的秒数
		$days	= $secs / DAY_IN_SECONDS;

		$from += get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ;

		if($secs > 0){
			if((date('Y')-date('Y',$from))>0 && $days>3){//跨年且超过3天
				return date('Y-m-d',$from);
			}else{

				if($days<1){//今天
					if($secs<60){
						return $secs.'秒前';
					}elseif($secs<3600){
						return floor($secs/60)."分钟前";
					}else {
						return floor($secs/3600)."小时前";
					}
				}else if($days<2){	//昨天
					$hour=date('g',$from);
					return "昨天".$hour.'点';
				}elseif($days<3){	//前天
					$hour=date('g',$from);
					return "前天".$hour.'点';
				}else{	//三天前
					return date('n月j号',$from);
				}
			}
		}else{
			if((date('Y')-date('Y',$from))<0 && $days<-3){//跨年且超过3天
				return date('Y-m-d',$from);
			}else{

				if($days>-1){//今天
					if($secs>-60){
						return absint($secs).'秒后';
					}elseif($secs>-3600){
						return floor(absint($secs)/60)."分钟前";
					}else {
						return floor(absint($secs)/3600)."小时前";
					}
				}else if($days>-2){	//昨天
					$hour=date('g',$from);
					return "明天".$hour.'点';
				}elseif($days>-3){	//前天
					$hour=date('g',$from);
					return "后天".$hour.'点';
				}else{	//三天前
					return date('n月j号',$from);
				}
			}
		}
	}
	
	public static function get_current_page_url(){
		// $sp			= strtolower($_SERVER['SERVER_PROTOCOL']);
		// $protocol	= substr($sp, 0, strpos($sp, '/')) . (is_ssl() ? 's' : '');
		// $port		= $_SERVER['SERVER_PORT'];
		// $port		= ((!is_ssl() && $port=='80') || (is_ssl() && $port=='443')) ? '' : ':'.$port;
		// $host		= $_SERVER['HTTP_X_FORWARDED_HOST'] ??  ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']);
		// return $protocol . '://' . $host . $port . $_SERVER['REQUEST_URI'];

		return set_url_scheme('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	}

	public static function json_encode( $data, $options=JSON_UNESCAPED_UNICODE, $depth = 512){
		return wp_json_encode($data, $options, $depth);
	}

	public static function send_json($response=[], $status_code=null){
		if(is_wp_error($response)){
			$response	= ['errcode'=>$response->get_error_code(), 'errmsg'=>$response->get_error_message()];
		}else{
			$response	= array_merge(['errcode'=>0], $response);
		}

		$result	= self::json_encode($response);

		if(!headers_sent() && !wpjam_doing_debug()){
			if (!is_null($status_code)) {
				status_header($status_code);
			}

			if(wp_is_jsonp_request()){
				@header('Content-Type: application/javascript; charset=' . get_option('blog_charset'));	
				
				$jsonp_callback	= $_GET['_jsonp'];
				
				$result	= '/**/' . $jsonp_callback . '(' . $result . ')';

			}else{	
				@header('Content-Type: application/json; charset=' . get_option('blog_charset'));
			}
		}

		echo $result;

		exit;
	}

	public static function json_decode($json, $assoc=true, $depth=512, $options=0){
		$json	= self::strip_control_characters($json);

		if(empty($json)){
			return new WP_Error('empty_json', 'JSON 内容不能为空！');
		}

		$result	= json_decode($json, $assoc, $depth, $options);

		if(is_null($result)){
			$result	= json_decode(stripslashes($json), $assoc, $depth, $options);
			
			if(is_null($result)){
				if(wpjam_doing_debug()){
					print_r(json_last_error());
					print_r(json_last_error_msg());
				}
				trigger_error('json_decode_error '. json_last_error_msg()."\n".var_export($json,true));
				return new WP_Error('json_decode_error', json_last_error_msg());
			}
		}

		return $result;

		// wp 5.3 不建议使用 Services_JSON
		if(is_null($result)){
			require_once( ABSPATH . WPINC . '/class-json.php' );

			$wp_json	= new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
			$result		= $wp_json->decode($json); 

			if(is_null($result)){
				return new WP_Error('json_decode_error', json_last_error_msg());
			}else{
				if($assoc){
					return (array)$result;
				}else{
					return (object)$result;
				}
			}
		}else{
			return $result;
		}
	}

	public static function get_video_mp4($id_or_url){
		if(filter_var($id_or_url, FILTER_VALIDATE_URL)){ 
			if(preg_match('#http://www.miaopai.com/show/(.*?).htm#i',$id_or_url, $matches)){
				return 'http://gslb.miaopai.com/stream/'.esc_attr($matches[1]).'.mp4';
			}elseif(preg_match('#https://v.qq.com/x/page/(.*?).html#i',$id_or_url, $matches)){
				return self::get_qqv_mp4($matches[1]);
			}elseif(preg_match('#https://v.qq.com/x/cover/.*/(.*?).html#i',$id_or_url, $matches)){
				return self::get_qqv_mp4($matches[1]);
			}else{
				return str_replace(['%3A','%2F'], [':','/'], urlencode($id_or_url));
			}
		}else{
			return self::get_qqv_mp4($id_or_url);
		}
	}

	public static function get_qqv_mp4($vid){
		if(strlen($vid) > 20){
			return new WP_Error('invalid_qqv_vid', '非法的腾讯视频 ID');
		}

		$mp4 = wp_cache_get($vid, 'qqv_mp4');
		if($mp4 === false){
			$response	= wpjam_remote_request('http://vv.video.qq.com/getinfo?otype=json&platform=11001&vid='.$vid, ['timeout'=>4,	'need_json_decode'	=>false]);

			if(is_wp_error($response)){
				return $response;
			}

			$response	= trim(substr($response, strpos($response, '{')),';');
			$response	= wpjam_json_decode($response);

			if(is_wp_error($response)){
				return $response;
			}

			if(empty($response['vl'])){
				return new WP_Error('illegal_qqv', '该腾讯视频不存在或者为收费视频！');
			}

			$u		= $response['vl']['vi'][0];
			$p0		= $u['ul']['ui'][0]['url'];
			$p1		= $u['fn'];
			$p2		= $u['fvkey'];

			$mp4	= $p0.$p1.'?vkey='.$p2;

			wp_cache_set($vid, $mp4, 'qqv_mp4', HOUR_IN_SECONDS*6);
		}

		return $mp4;
	}

	public static function get_qq_vid($id_or_url){
		if(filter_var($id_or_url, FILTER_VALIDATE_URL)){ 
			if(preg_match('#https://v.qq.com/x/page/(.*?).html#i',$id_or_url, $matches)){
				return $matches[1];
			}elseif(preg_match('#https://v.qq.com/x/cover/.*/(.*?).html#i',$id_or_url, $matches)){
				return $matches[1];
			}else{
				return '';
			}
		}else{
			return $id_or_url;
		}
	}

	// 移除除了 line feeds 和 carriage returns 所有控制字符
	public static function strip_control_characters($text){
		return preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F]/u', '', $text);	
		// return preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $str);
	}

	// 去掉非 utf8mb4 字符
	public static function strip_invalid_text($str){
		$regex = '/
		(
			(?: [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
			|   [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
			|   \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
			|   [\xE1-\xEC][\x80-\xBF]{2}
			|   \xED[\x80-\x9F][\x80-\xBF]
			|   [\xEE-\xEF][\x80-\xBF]{2}
			|    \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
			|    [\xF1-\xF3][\x80-\xBF]{3}
			|    \xF4[\x80-\x8F][\x80-\xBF]{2}
			){1,50}                          # ...one or more times
		)
		| .                                  # anything else
		/x';

		return preg_replace($regex, '$1', $str);
	}

	public static function strip_4_byte_chars($chars){
		return preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $chars);
		// return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $chars);
	}

	//获取纯文本
	public static function get_plain_text($text){

		$text = wp_strip_all_tags($text);
		
		$text = str_replace('"', '', $text); 
		$text = str_replace('\'', '', $text);	
		// replace newlines on mac / windows?
		$text = str_replace("\r\n", ' ', $text);
		// maybe linux uses this alone
		$text = str_replace("\n", ' ', $text);
		$text = str_replace("  ", ' ', $text);

		return trim($text);
	}

	// 获取第一段
	public static function get_first_p($text){
		if($text){
			$text = explode("\n", trim(strip_tags($text))); 
			$text = trim($text['0']); 
		}
		return $text;
	}

	public static function mb_strimwidth($text, $start=0, $length=40, $trimmarker='...', $encoding='utf-8'){
		return mb_strimwidth(self::get_plain_text($text), $start, $length, $trimmarker, $encoding);
	}

	public static function blacklist_check($str){
		$moderation_keys	= trim(get_option('moderation_keys'));
		$blacklist_keys		= trim(get_option('blacklist_keys'));

		$words = explode("\n", $moderation_keys ."\n".$blacklist_keys);

		foreach ((array)$words as $word){
			$word = trim($word);

			// Skip empty lines
			if ( empty($word) ) {
				continue;
			}

			// Do some escaping magic so that '#' chars in the
			// spam words don't break things:
			$word	= preg_quote($word, '#');
			if ( preg_match("#$word#i", $str) ) {
				return true;
			}
		}

		return false;
	}

	public static function http_request($url, $args=[], $err_args=[]){
		$args = wp_parse_args($args, [
			'timeout'			=> 5,
			'method'			=> '',
			'body'				=> [],
			'sslverify'			=> false,
			'blocking'			=> true,	// 如果不需要立刻知道结果，可以设置为 false
			'stream'			=> false,	// 如果是保存远程的文件，这里需要设置为 true
			'filename'			=> null,	// 设置保存下来文件的路径和名字
			'need_json_decode'	=> true,
			'need_json_encode'	=> false,
			// 'headers'		=> ['Accept-Encoding'=>'gzip;'],	//使用压缩传输数据
			// 'headers'		=> ['Accept-Encoding'=>''],
			// 'compress'		=> false,
			'decompress'		=> true,
		]);

		if(wpjam_doing_debug()){
			print_r($url);
			print_r($args);
		}

		$need_json_decode	= $args['need_json_decode'];
		$need_json_encode	= $args['need_json_encode'];

		if(!empty($args['method'])){
			$method			= strtoupper($args['method']);
		}else{
			$method			= $args['body'] ? 'POST' : 'GET';
		}

		unset($args['need_json_decode']);
		unset($args['need_json_encode']);
		unset($args['method']);

		if($method == 'GET'){
			$response = wp_remote_get($url, $args);
		}elseif($method == 'POST'){
			if($need_json_encode && is_array($args['body'])){
				$args['body']	= self::json_encode($args['body']);
			}
			$response	= wp_remote_post($url, $args);
		}elseif($method == 'FILE'){	// 上传文件
			$args['method']				= $args['body'] ? 'POST' : 'GET';
			$args['sslcertificates']	= $args['sslcertificates'] ?? ABSPATH.WPINC.'/certificates/ca-bundle.crt';
			$args['user-agent']			= $args['user-agent'] ?? 'WordPress';

			$wp_http_curl	= new WP_Http_Curl();
			$response		= $wp_http_curl->request($url, $args);
		}elseif($method == 'HEAD'){
			if($need_json_encode && is_array($args['body'])){
				$args['body']	= self::json_encode($args['body']);
			}

			$response = wp_remote_head($url, $args);
		}else{
			if($need_json_encode && is_array($args['body'])){
				$args['body']	= self::json_encode($args['body']);
			}

			$response = wp_remote_request($url, $args);
		}

		if(is_wp_error($response)){
			trigger_error($url."\n".$response->get_error_code().' : '.$response->get_error_message()."\n".var_export($args['body'],true));
			return $response;
		}

		$headers	= $response['headers'];
		$response	= $response['body'];

		if($need_json_decode || isset($headers['content-type']) && strpos($headers['content-type'], '/json')){
			if($args['stream']){
				$response	= file_get_contents($args['filename']);
			}

			$response	= self::json_decode($response);

			if(is_wp_error($response)){
				return $response;
			}
		}
		
		$err_args	= wp_parse_args($err_args,  [
			'errcode'	=>'errcode',
			'errmsg'	=>'errmsg',
			'detail'	=>'detail',
			'success'	=>'0',
		]);

		if(isset($response[$err_args['errcode']]) && $response[$err_args['errcode']] != $err_args['success']){
			$errcode	= $response[$err_args['errcode']];
			$errmsg		= $response[$err_args['errmsg']] ?? '';

			if(isset($response[$err_args['detail']])){
				$detail	= $response[$err_args['detail']];

				trigger_error($url."\n".$errcode.' : '.$errmsg."\n".var_export($detail,true)."\n".var_export($args['body'],true));
				return new WP_Error($errcode, $errmsg, $detail);
			}else{

				trigger_error($url."\n".$errcode.' : '.$errmsg."\n".var_export($args['body'],true));
				return new WP_Error($errcode, $errmsg);
			}	
		}

		if(wpjam_doing_debug()){
			echo $url;
			print_r($response);
		}

		return $response;
	}

	public static function get_post_list($wpjam_query, $args=[]){
		if(!$wpjam_query){
			return false;
		}
		
		$args	= wp_parse_args($args, [
			'title'			=> '',
			'div_id'		=> '',
			'class'			=> '', 
			'thumb'			=> true,	
			'excerpt'		=> false, 
			'size'			=> 'thumbnail', 
			'crop'			=> true, 
			'thumb_class'	=> 'wp-post-image'
		]);

		$output = '';

		if($wpjam_query->have_posts()){
			while($wpjam_query->have_posts()){
				$wpjam_query->the_post();

				$li = get_the_title();

				if($args['thumb'] || $args['excerpt']){
					$li = '<h4>'.$li.'</h4>';

					if($args['thumb']){
						$li = wpjam_get_post_thumbnail(null, $args['size'], $args['crop'], $args['thumb_class'])."\n".$li;
					}

					if($args['excerpt']){
						$li .= "\n".'<p>'.get_the_excerpt().'</p>';
					}
				}

				if(!is_singular() || (is_singular() && get_queried_object_id() != get_the_ID())) {
					$li = '<a href="'.get_permalink().'" title="'.the_title_attribute(['echo'=>false]).'">'.$li.'</a>';
				}

				$output .=	'<li>'.$li.'</li>'."\n";
			}

			if($args['thumb']){
				$args['class']	= $args['class'].' has-thumb';
			}
			
			$class	= $args['class'] ? ' class="'.$args['class'].'"' : '';
			$output = '<ul'.$class.'>'."\n".$output.'</ul>'."\n";

			if($args['title']){
				$output	= '<h3>'.$args['title'].'</h3>'."\n".$output;
			}

			if($args['div_id']){
				$output	= '<div id="'.$args['div_id'].'">'."\n".$output.'</div>'."\n";
			}
		}

		wp_reset_postdata();
		return $output;	
	}

	static $user_agent;
	static $referer;
	static $is_macintosh;
	static $is_iphone;
	static $is_ipod;
	static $is_ipad;
	static $is_android;
	static $is_weapp;
	static $is_weixin;
	static $is_bytedance;

	public static function get_ip(){
		// if (!empty($_SERVER['HTTP_CLIENT_IP'])) { //check ip from share internet
		// 	return $_SERVER['HTTP_CLIENT_IP'];
		// } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { //to check ip is pass from proxy
		// 	return $_SERVER['HTTP_X_FORWARDED_FOR'];
		// } else {
			return $_SERVER['REMOTE_ADDR'] ??'';
		// }
		// return '';
	}

	public static function parse_ip($ip=''){
		$ip	= ($ip)?:self::get_ip();

		if($ip == 'unknown'){
			return false;
		}

		$ipdata		= IP::find($ip);

		return [
			'ip'		=> $ip,
			'country'	=> isset($ipdata['0'])?$ipdata['0']:'',
			'region'	=> isset($ipdata['1'])?$ipdata['1']:'',
			'city'		=> isset($ipdata['2'])?$ipdata['2']:'',
			'isp'		=> '',
		];
	}

	public static function get_user_agent(){
		if (!isset(self::$user_agent)){
			self::$user_agent = ($_SERVER['HTTP_USER_AGENT'])??'';
		}

		return self::$user_agent;
	}

	public static function parse_user_agent($user_agent=''){
		$user_agent	= ($user_agent)?:self::get_user_agent();
		$user_agent	= $user_agent.' ';	// 为了特殊情况好匹配

		$os	= $os_ver = $device	= $build = $weixin_ver = $net_type = '';

		if(self::is_weixin() || self::is_weapp()){
			if(preg_match('/MicroMessenger\/(.*?)\s/', $user_agent, $matches)){
				$weixin_ver = $matches[1];
			}

			if(preg_match('/NetType\/(.*?)\s/', $user_agent, $matches)){
				$net_type = $matches[1];
			}
		}

		if(self::is_ios()){
			$os 	= 'iOS';
			$os_ver	= self::get_ios_version($user_agent);
			if(self::is_ipod()){
				$device	= 'iPod';
			}elseif(self::is_iphone()){
				$device	= 'iPhone';
			}elseif(self::is_ipad()){
				$device	= 'iPad';
			}
		}elseif(self::is_android()){
			$os		= 'Android';

			if(preg_match('/Android ([0-9\.]{1,}?); (.*?) Build\/(.*?)[\)\s;]{1}/i', $user_agent, $matches)){
				if(!empty($matches[1]) && !empty($matches[2])){
					$os_ver	= trim($matches[1]);
					$device	= $matches[2];
					if(strpos($device,';')!==false){
						$device	= substr($device, strpos($device,';')+1, strlen($device)-strpos($device,';'));
					}
					$device	= trim($device);
					$build	= trim($matches[3]);
				}
			}
			
		}elseif(stripos($user_agent, 'Windows NT')){
			$os		= 'Windows';
		}elseif(stripos($user_agent, 'Macintosh')){
			$os		= 'Macintosh';
		}elseif(stripos($user_agent, 'Windows Phone')){
			$os		= 'Windows Phone';
		}elseif(stripos($user_agent, 'BlackBerry') || stripos($user_agent, 'BB10')){
			$os		= 'BlackBerry';
		}elseif(stripos($user_agent, 'Symbian')){
			$os		= 'Symbian';
		}else{
			$os		= 'unknown';
		}

		return compact("os", "os_ver", "device", "build", "weixin_ver", "net_type");
	}

	public static function get_ios_version($user_agent){
		if(preg_match('/OS (.*?) like Mac OS X[\)]{1}/i', $user_agent, $matches)){
			return trim($matches[1]);
		}else{
			return '';
		}
	}

	public static function get_ios_build($user_agent){
		if(preg_match('/Mobile\/(.*?)\s/i', $user_agent, $matches)){
			return trim($matches[1]);
		}else{
			return '';
		}
	}

	public static function get_referer(){
		if (!isset(self::$referer)){
			self::$referer = $_SERVER['HTTP_REFERER'] ?? '';
		}

		return self::$referer;
	}

	public static function is_iphone(){
		if (!isset(self::$is_iphone)){
			if(strpos(self::get_user_agent(), 'iPhone') !== false){
				self::$is_iphone = true;
			}else{
				self::$is_iphone = false;
			}
		}

		return self::$is_iphone;
	}

	public static function is_mac(){
		return self::is_macintosh();
	}

	public static function is_macintosh(){
		if (!isset(self::$is_macintosh)){
			if(strpos(self::get_user_agent(), 'Macintosh') !== false){
				self::$is_macintosh = true;
			}else{
				self::$is_macintosh = false;
			}
		}

		return self::$is_macintosh;
	}

	public static function is_ipod(){
		if (!isset(self::$is_ipod)){
			if(strpos(self::get_user_agent(), 'iPod') !== false){
				self::$is_ipod = true;
			}else{
				self::$is_ipod = false;
			}
		}

		return self::$is_ipod;
	}

	public static function is_ipad(){	
		if (!isset(self::$is_ipad)){
			if(strpos(self::get_user_agent(), 'iPad') !== false){
				self::$is_ipad = true;
			}else{
				self::$is_ipad = false;
			}
		}

		return self::$is_ipad;
	}

	public static function is_ios(){
		return self::is_iphone() || self::is_ipod() || self::is_ipad();
	}

	public static function is_android(){
		if (!isset(self::$is_android)){
			if(strpos(self::get_user_agent(), 'Android') !== false){
				self::$is_android = true;
			}else{
				self::$is_android = false;
			}			
		}

		return self::$is_android;
	}

	public static function is_weixin(){ 
		if (!isset(self::$is_weixin)){
			if(strpos(self::get_user_agent(), 'MicroMessenger') !== false){
				if(strpos(self::get_referer(), 'https://servicewechat.com') !== false){
					self::$is_weixin	= false;
					self::$is_weapp		= true;
				}else{
					self::$is_weixin	= true;
					self::$is_weapp		= false;
				}
			}else{
				self::$is_weixin	= false;
			}			
		}

		return self::$is_weixin;
	}

	public static function is_weapp(){
		if (!isset(self::$is_weapp)){
			if(strpos(self::get_user_agent(), 'MicroMessenger') !== false){
				if(strpos(self::get_referer(), 'https://servicewechat.com') !== false){
					self::$is_weapp		= true;
					self::$is_weixin	= false;
				}else{
					self::$is_weapp		= false;
					self::$is_weixin	= true;
				}
			}else{
				self::$is_weapp = false;
			}			
		}

		return self::$is_weapp;
	}

	public static function is_bytedance(){
		if (!isset(self::$is_bytedance)){
			if(strpos(self::get_user_agent(), 'ToutiaoMicroApp') !== false){
				self::$is_bytedance		= true;
			}else{
				self::$is_bytedance = false;
			}			
		}

		return self::$is_bytedance;
	}
}

class WPJAM_Notice extends WPJAM_Model{
	public static $errors = [];

	public static function add($notice, $user_id=0){
		$notice['user_id']	= $user_id;
		$notice['time']		= $notice['time'] ?? time();
		$notice['key']		= $notice['key'] ?? md5(maybe_serialize($notice));

		$notices	= self::get_notices($user_id);
		$key		= $notice['key'];

		$notices[$key]	= $notice;

		return self::update_notices($notices, $user_id);
	}

	public static function delete($key, $user_id=0){
		$result		= false;
		$notices	= self::get_notices($user_id);

		if(isset($notices[$key])){
			unset($notices[$key]);
			$result	= self::update_notices($notices, $user_id);
		}

		if(!$user_id){
			return self::delete($key, get_current_user_id());
		}else{
			return $result;
		}
	}

	public static function get_notices($user_id=0){
		if($user_id){
			$notices	= get_user_meta($user_id, 'wpjam_notices', true) ?: [];
		}else{
			$notices	= get_option('wpjam_notices') ?: [];
		}

		if($notices){
			return array_filter($notices, function($notice){
				return $notice['time'] > time() - MONTH_IN_SECONDS * 3;
			});
		}else{
			return [];
		}
	}

	public static function update_notices($notices, $user_id=0){
		if($user_id){
			if(empty($notices)){
				return delete_user_meta($user_id, 'wpjam_notices');
			}else{
				return update_user_meta($user_id, 'wpjam_notices', $notices);
			}
		}else{
			if(empty($notices)){
				return delete_option('wpjam_notices');
			}else{
				return update_option('wpjam_notices', $notices);
			}
		}
	}
}

wp_cache_add_global_groups(['wpjam_messages']);

class WPJAM_Message extends WPJAM_Model {
	public static function insert($data){
		$data = wp_parse_args($data, [
			'sender'	=> get_current_user_id(),
			'receiver'	=> '',
			'type'		=> '',
			'content'	=> '',
			'status'	=> 0,
			'time'		=> time()
		]);

		$data['content'] = wp_strip_all_tags($data['content']);

		return parent::insert($data);
	}

	public static function get_unread_count(){
		return self::Query()->where('receiver', get_current_user_id())->where('status', 0)->get_var('count(*)');
	}

	public static function set_all_read(){
		return self::Query()->where('receiver', get_current_user_id())->where('status', 0)->update(['status'=>1]);
	}

	private static 	$handler;

	public static function get_table(){
		global $wpdb;
		return $wpdb->base_prefix.'messages';
	}

	public static function get_handler(){
		global $wpdb;


		if(is_null(self::$handler)){
			self::$handler = new WPJAM_DB(self::get_table(), [
				'primary_key'		=> 'id',
				'cache_group'		=> 'wpjam_messages',
				'field_types'		=> ['id'=>'%d','time'=>'%d'],
				'searchable_fields'	=> ['content'],
				'filterable_fields'	=> ['type'],
			]);
		}
		return self::$handler;
	}

	public static function create_table($appid=''){
		global $wpdb;

		$table	= self::get_table($appid);

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		if($wpdb->get_var("show tables like '{$table}'") != $table){
			$sql = "
			CREATE TABLE IF NOT EXISTS `{$table}` (
				`id` bigint(20) NOT NULL auto_increment,
				`sender` bigint(20) NOT NULL,
				`receiver` bigint(20) NOT NULL,
				`type` varchar(15) NOT NULL,
				`blog_id` bigint(20) NOT NULL,
				`post_id` bigint(20) NOT NULL,
				`comment_id` bigint(20) NOT NULL,
				`content` text NOT NULL,
				`status` int(1) NOT NULL,
				`time` int(10) NOT NULL,
				PRIMARY KEY	(`id`),
				KEY `type_idx` (`type`),
				KEY `blog_id_idx` (`blog_id`),
				KEY `sender_idx` (`sender`),
				KEY `receiver_idx` (`receiver`)
			) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
			";
	 
			dbDelta($sql);
		}
	}
}

class WPJAM_Platform{
	const PLATFORM_WEAPP	= 1;
	const PLATFORM_WEB		= 2;
	const PLATFORM_WEIXIN	= 4;
	const PLATFORM_MOBILE	= 8;

	public static function get_options($type='bit'){
		$options	= [
			[
				'bit'	=> self::PLATFORM_WEAPP,
				'key'	=> 'weapp',
				'title'	=> '小程序'
			],
			[
				'bit'	=> self::PLATFORM_WEIXIN,
				'key'	=> 'weixin',
				'title'	=> '微信网页'
			],
			[
				'bit'	=> self::PLATFORM_MOBILE,
				'key'	=> 'mobile',
				'title'	=> '移动网页'
			],
			[
				'bit'	=> self::PLATFORM_WEB,
				'key'	=> 'web',
				'title'	=> '网页'
			]
		];

		if($type == 'key'){
			return wp_list_pluck($options, 'title', 'key');
		}elseif($type == 'bit'){
			return wp_list_pluck($options, 'title', 'bit');
		}else{
			return wp_list_pluck($options, 'bit', 'key');
		}
	}

	public static function get_bit($key){
		if(is_numeric($key)){
			return intval($key);
		}else{
			$options	= self::get_options('');

			$options['template']	= self::PLATFORM_WEB;

			return $options[$key] ?? 0;
		}
	}

	public static function is_platform($platform){
		$platform	= self::get_bit($platform);

		if($platform == self::PLATFORM_WEAPP){
			return is_weapp() || isset($_GET['appid']);
		}elseif($platform == self::PLATFORM_WEIXIN ){
			return is_weixin() || isset($_GET['weixin_appid']);
		}elseif($platform == self::PLATFORM_MOBILE ){
			return wp_is_mobile();
		}elseif($platform == self::PLATFORM_WEB){
			return true;
		}else{
			return false;
		}
	}

	public static function get_current_platform($platforms=[], $type='bit'){
		$options	= self::get_options($type);

		if($type == 'key'){
			$options['template']	= self::PLATFORM_WEB;
		}

		foreach($options as $platform=>$title){
			if($platforms){
				if(in_array($platform, $platforms) && self::is_platform($platform)){
					return $platform;
				}
			}else{
				if(self::is_platform($platform)){
					return $platform;
				}
			}
		}

		return '';
	}

	protected $platform;

	public function __construct($platform){
		$this->set_platform($platform);
	}

	public function set_platform($platform){
		$platform	= self::get_bit($platform);
		$this->platform	= $platform;
	}

	public function get_platform(){
		return $this->platform;
	}

	public function has($platform){
		$platform	= self::get_bit($platform);
		return ($this->platform & $platform) == $platform;
	}

	public function add($platform){
		$platform	= self::get_bit($platform);
		$this->platform = $this->platform | $platform;

		return $this->platform;
	}

	public function remove($platform){
		$platform	= self::get_bit($platform);
		$this->platform = $this->platform & (~$platform);

		return $this->platform;
	}
}

class WPJAM_VerifyTXT{
	private static $verify_txts	= [];

	public static function register($key, $args){
		if(isset(self::$verify_txts[$key])){
			return new WP_Error('verify_txt_registered', '该验证txt文件已被注册');
		}

		self::$verify_txts[$key]	= $args;

		return self::$verify_txts;
	}

	public static function get_name($key){
		if(isset(self::$verify_txts[$key])){
			$value	= wpjam_get_setting('wpjam_verify_txts', $key);

			if($value){
				return $value['name'] ?? '';
			}
		}

		return '';
	}

	public static function get_value($key){
		if(isset(self::$verify_txts[$key])){
			$value	= wpjam_get_setting('wpjam_verify_txts', $key);

			if($value){
				return $value['value'] ?? '';
			}
		}

		return '';
	}

	public static function set($key, $name, $value){
		if(isset(self::$verify_txts[$key])){
			wpjam_update_setting('wpjam_verify_txts', $key, compact('name', 'value'));
			return true;
		}else{
			return false;
		}
	}

	public static function get_value_by_name($name){
		if($values = wpjam_get_option('wpjam_verify_txts')){
			$name	= str_replace('.txt', '', $name).'.txt';
			foreach ($values as $key => $value) {
				if($value['name'] == $name){
					return $value['value'];
				}
			}
		}

		return '';
	}
}

class WPJAM_Grant{
	public static function validate_access_token($token){
		$grants	= self::get_grants();

		if(empty($grants)){
			return false;
		}

		$grants	= array_filter($grants, function($item) use($token){
			return isset($item['token']) && $item['token'] == $token;
		});

		if(empty($grants)){
			return false;
		}

		$grant	= current($grants);

		if($grant['token'] != $token || (time()-$grant['time'] > 7200)){
			return false;
		}

		return true;
	}

	public static function generate_appid(){
		return 'jam'.strtolower(wp_generate_password(15, false, false));
	}

	public static function reset_secret($appid){
		$grant	= self::get_grant($appid);

		if(is_wp_error($grant)){
			return $grant;
		}

		$secret	= strtolower(wp_generate_password(32, false, false));

		$grant['secret']	= md5($secret);

		self::set_grant($appid, $grant);

		return $secret;
	}

	public static function generate_access_token($appid, $secret){
		$grant	= self::get_grant($appid);

		if(is_wp_error($grant)){
			return $grant;
		}

		if(empty($grant['secret']) || $grant['secret'] != md5($secret)){
			return new WP_Error('invalid_secret', '非法密钥');
		}

		$token	= wp_generate_password(64, false, false);
		$time	= time();

		$grant['token']	= $token;
		$grant['time']	= $time;

		self::set_grant($appid, $grant);

		return $token;
	}

	public static function add_appid($appid){
		$items	= self::get_grants();

		if($items){
			if(count($items) >= 3){
				return new WP_Error('too_much_appid', '最多可以设置三个APPID');
			}

			$grant	= self::get_grant($appid);

			if($grant && !is_wp_error($grant)){
				return new WP_Error('appid_exists', 'AppId已存在');
			}
		}

		$items[]	= compact('appid');

		return update_option('wpjam_grant', $items);
	}

	public static function delete_grant($appid){
		$grant	= self::get_grant($appid);

		if(is_wp_error($grant)){
			return $grant;
		}

		$items	= self::get_grants();

		$items	= array_filter($items, function($item) use($appid){
			return $item['appid'] != $appid;
		});

		return update_option('wpjam_grant', array_values($items));
	}

	public static function set_grant($appid, $grant){
		$items	= self::get_grants();
		$update	= false;

		foreach($items as $i => &$item){
			if($item['appid'] == $appid){
				$item	= array_merge($item, $grant);
				$update	= true;
				break;
			}
		}

		if($update){
			return update_option('wpjam_grant', $items);
		}else{
			return true;
		}
	}

	public static function get_grant($appid){
		if(empty($appid)){
			return new WP_Error('invalid_appid', '无效的AppId');
		}

		$items	= self::get_grants();

		if($items){
			$items	= array_filter($items, function($item) use($appid){
				return $item['appid'] == $appid;
			});

			if($items){
				return current($items);
			}
		}

		return new WP_Error('invalid_appid', '无效的AppId');
	}

	public static function get_grants(){
		$items	= get_option('wpjam_grant') ?: [];

		if($items && !wp_is_numeric_array($items)){
			$items	= [$items];

			update_option('wpjam_grant', $items);
		}

		return $items;
	}
}

class WPJAM_OPENSSL_Crypt{
	private $key;
	private $method = 'aes-128-cbc';
	private $iv = '';
	private $options = OPENSSL_RAW_DATA;

	public function __construct($key, $args=[])
	{
		$this->key		= $key;
		$this->method	= $args['method'] ?? $this->method;
		$this->options	= $args['options'] ?? $this->options;
		$this->iv		= $args['iv'] ?? '';
	}

	public function encrypt($text)
	{
		$encrypted_text = openssl_encrypt($text, $this->method, $this->key, $this->options, $this->iv);

		return trim($encrypted_text);
	}

	public function decrypt($encrypted_text)
	{
		$decrypted_text = openssl_decrypt($encrypted_text, $this->method, $this->key, $this->options, $this->iv);

		return trim($decrypted_text);
	}

	public static function generate_random_string($length)
	{
		
		$alphabet	= "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
		$max		= strlen($alphabet);

		$token		= '';
		for ($i = 0; $i < $length; $i++) {
			$token .= $alphabet[self::crypto_rand_secure(0, $max - 1)];
		}

		return $token;
	}

	private static function crypto_rand_secure($min, $max)
	{
		$range	= $max - $min;
		if($range < 1){
			return $min;
		} // not so random...

		$log	= ceil(log($range, 2));
		$bytes	= (int)($log / 8) + 1;		// length in bytes
		$bits	= (int)$log + 1;			// length in bits
		$filter	= (int)(1 << $bits) - 1;	// set all lower bits to 1
		
		do {
			$rnd	= hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd	= $rnd & $filter;	// discard irrelevant bits
		}while($rnd > $range);

		return $min + $rnd;
	}
}

wp_cache_add_global_groups(['wpjam_list_cache']);
class WPJAM_ListCache{
	private $key;

	public function __construct($key){
		$this->key	= $key;
	}

	private function get_items(&$cas_token){
		$items	= wp_cache_get_with_cas($this->key, 'wpjam_list_cache', $cas_token);

		if($items === false){
			$items	= [];
			wp_cache_add($this->key, [], 'wpjam_list_cache', DAY_IN_SECONDS);
			$items	= wp_cache_get_with_cas($this->key, 'wpjam_list_cache', $cas_token);
		}

		return $items;
	}

	private function set_items($cas_token, $items){
		return wp_cache_cas($cas_token, $this->key, $items, 'wpjam_list_cache', DAY_IN_SECONDS);
	}

	public function get_all(){
		$items	= wp_cache_get($this->key, 'wpjam_list_cache');
		return $items ?: [];
	}

	public function get($k){
		$items = $this->get_all();
		return $items[$k]??false;  
	}

	public function add($item, $k=null){
		$cas_token	= '';
		$retry		= 10;

		do{
			$items	= $this->get_items($cas_token);

			if($k!==null){
				if(isset($items[$k])){
					return false;
				}

				$items[$k]	= $item;
			}else{
				$items[]	= $item;
			}
			
			$result	= $this->set_items($cas_token, $items);

			$retry	 -= 1;
		}while (!$result && $retry > 0);

		return $result;
	}

	public function increment($k, $offset=1){
		$cas_token	= '';
		$retry		= 10;

		do{
			$items		= $this->get_items($cas_token);
			$items[$k]	= $items[$k]??0; 
			$items[$k]	= $items[$k]+$offset;
			
			$result	= $this->set_items($cas_token, $items);

			$retry	 -= 1;
		}while (!$result && $retry > 0);

		return $result;
	}

	public function decrement($k, $offset=1){
		return $this->increment($k, 0-$offset);
	}

	public function set($item, $k){
		$cas_token	= '';
		$retry		= 10;

		do{
			$items		= $this->get_items($cas_token);
			$items[$k]	= $item;
			$result		= $this->set_items($cas_token, $items);
			$retry 		-= 1;
		}while(!$result && $retry > 0);

		return $result;
	}

	public function remove($k){
		$cas_token	= '';
		$retry		= 10;

		do{
			$items	= $this->get_items($cas_token);
			if(!isset($items[$k])){
				return false;
			}
			unset($items[$k]);
			$result	= $this->set_items($cas_token, $items);
			$retry 	-= 1;
		}while(!$result && $retry > 0);

		return $result;
	}

	public function empty(){
		$cas_token		= '';
		$retry	= 10;

		do{
			$items	= $this->get_items($cas_token);
			if($items == []){
				return [];
			}
			$result	= $this->set_items($cas_token, []);
			$retry 	-= 1;
		}while(!$result && $retry > 0);

		if($result){
			return $items;
		}

		return $result;
	}
}

class WPJAM_Cache{
	/* HTML 片段缓存
	Usage:

	if (!WPJAM_Cache::output('unique-key')) {
		functions_that_do_stuff_live();
		these_should_echo();
		WPJAM_Cache::store(3600);
	}
	*/
	public static function output($key) {
		$output	= get_transient($key);
		if(!empty($output)) {
			echo $output;
			return true;
		} else {
			ob_start();
			return false;
		}
	}

	public static function store($key, $cache_time='600') {
		$output = ob_get_flush();
		set_transient($key, $output, $cache_time);
		echo $output;
	}
}

class IP{
	private static $ip = null;
	private static $fp = null;
	private static $offset = null;
	private static $index = null;
	private static $cached = [];

	public static function find($ip){
		if (empty( $ip ) === true) {
			return 'N/A';
		}

		$nip	= gethostbyname($ip);
		$ipdot	= explode('.', $nip);

		if ($ipdot[0] < 0 || $ipdot[0] > 255 || count($ipdot) !== 4) {
			return 'N/A';
		}

		if (isset( self::$cached[$nip] ) === true) {
			return self::$cached[$nip];
		}

		if (self::$fp === null) {
			self::init();
		}

		$nip2 = pack('N', ip2long($nip));

		$tmp_offset	= (int) $ipdot[0] * 4;
		$start		= unpack('Vlen',
			self::$index[$tmp_offset].self::$index[$tmp_offset + 1].self::$index[$tmp_offset + 2].self::$index[$tmp_offset + 3]);

		$index_offset = $index_length = null;
		$max_comp_len = self::$offset['len'] - 1024 - 4;
		for ($start = $start['len'] * 8 + 1024; $start < $max_comp_len; $start += 8) {
			if (self::$index[$start].self::$index[$start+1].self::$index[$start+2].self::$index[$start+3] >= $nip2) {
				$index_offset = unpack('Vlen',
					self::$index[$start+4].self::$index[$start+5].self::$index[$start+6]."\x0");
				$index_length = unpack('Clen', self::$index{$start + 7});

				break;
			}
		}

		if ($index_offset === null) {
			return 'N/A';
		}

		fseek(self::$fp, self::$offset['len'] + $index_offset['len'] - 1024);

		self::$cached[$nip] = explode("\t", fread(self::$fp, $index_length['len']));

		return self::$cached[$nip];
	}

	private static function init(){
		if (self::$fp === null) {
			self::$ip = new self();

			self::$fp = fopen(WP_CONTENT_DIR.'/uploads/17monipdb.dat', 'rb');
			if (self::$fp === false) {
				throw new Exception('Invalid 17monipdb.dat file!');
			}

			self::$offset = unpack('Nlen', fread(self::$fp, 4));
			if (self::$offset['len'] < 4) {
				throw new Exception('Invalid 17monipdb.dat file!');
			}

			self::$index = fread(self::$fp, self::$offset['len'] - 4);
		}
	}

	public function __destruct(){
		if (self::$fp !== null) {
			fclose(self::$fp);
		}
	}
}