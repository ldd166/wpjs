<?php
// 获取设置
function wpjam_get_setting($option, $setting_name, $blog_id=0){
	return WPJAM_API::get_setting($option, $setting_name, $blog_id);
}

// 更新设置
function wpjam_update_setting($option_name, $setting_name, $setting_value, $blog_id=0){
	return WPJAM_API::update_setting($option_name, $setting_name, $setting_value, $blog_id);
}

function wpjam_delete_setting($option_name, $setting_name, $blog_id=0){
	return WPJAM_API::delete_setting($option_name, $setting_name, $blog_id);
}

// 获取选项
function wpjam_get_option($option_name, $blog_id=0){
	return WPJAM_API::get_option($option_name, $blog_id);
}

// 1. $img_url 
// 2. $img_url, array('width'=>100, 'height'=>100)	// 这个为最标准版本
// 3. $img_url, 100x100
// 4. $img_url, 100
// 5. $img_url, array(100,100)
// 6. $img_url, array(100,100), $crop=1, $retina=1
// 7. $img_url, 100, 100, $crop=1, $retina=1
function wpjam_get_thumbnail(...$args){
	return WPJAM_API::get_thumbnail(...$args);
}

function wpjam_parse_size($size, $retina=1){
	return WPJAM_API::parse_size($size, $retina);
}


function wpjam_add_admin_notice($notice, $blog_id=0){
	$switched	= $blog_id ? switch_to_blog($blog_id) : false;

	$result		= WPJAM_Notice::add($notice);
	
	restore_current_blog();

	return $result;
}

function wpjam_add_user_notice($user_id, $notice){
	$user_id	= $user_id ?: get_current_user_id();

	return WPJAM_Notice::add($notice, $user_id);
}

function wpjam_send_user_message($message){
	return WPJAM_Message::insert($message);
}

function wpjam_generate_random_string($length){
	return WPJAM_OPENSSL_Crypt::generate_random_string($length);
}

// 显示字段
function wpjam_fields($fieds, $args=[]){
	return WPJAM_Field::fields_callback($fieds, $args);
}

function wpjam_get_field_value($field, $args=[]){
	return WPJAM_Field::get_field_value($field, $args);
}

// 验证一组字段的值
function wpjam_validate_fields_value($fields, $values=[]){
	return WPJAM_Field::validate_fields_value($fields, $values);
}

// 获取表单 HTML
function wpjam_get_field_html($field){
	return WPJAM_Field::get_field_html($field);
}

function wpjam_form_field_tmpls($echo=true){
}





function wpjam_parse_shortcode_attr($str,  $tagnames=null){
	return 	WPJAM_API::parse_shortcode_attr($str,  $tagnames);
}

// 去掉非 utf8mb4 字符
function wpjam_strip_invalid_text($str){
	return WPJAM_API::strip_invalid_text($str);
}

// 去掉 4字节 字符
function wpjam_strip_4_byte_chars($chars){
	return WPJAM_API::strip_4_byte_chars($chars);
}

// 去掉控制字符
function wpjam_strip_control_characters($text){
	return WPJAM_API::strip_control_characters($text);
}

//获取纯文本
function wpjam_get_plain_text($text){
	return WPJAM_API::get_plain_text($text);
}

//获取第一段
function wpjam_get_first_p($text){
	return WPJAM_API::get_first_p($text);
}

//中文截取方式
function wpjam_mb_strimwidth($text, $start=0, $width=40, $trimmarker='...', $encoding='utf-8'){
	return WPJAM_API::mb_strimwidth($text, $start, $width, $trimmarker, $encoding);
}

// 检查非法字符
function wpjam_blacklist_check($text, $name='内容'){
	if(empty($text)){
		return false;
	}

	$pre	= apply_filters('wpjam_pre_blacklist_check', null, $text, $name);
	
	if(is_null($pre)){
		return  WPJAM_API::blacklist_check($text);	
	}else{
		return $pre;
	}
	
}

// 获取当前页面 url
function wpjam_get_current_page_url(){
	return WPJAM_API::get_current_page_url();
}

// 获取参数，
function wpjam_get_parameter($parameter, $args=[]){
	return WPJAM_API::get_parameter($parameter, $args);
}

function wpjam_get_data_parameter($parameter, $args=[]){
	return WPJAM_API::get_data_parameter($parameter, $args);
}

function wpjam_human_time_diff($from, $to=0) {
	return WPJAM_API::human_time_diff($from, $to);
}

function wpjam_get_video_mp4($id_or_url){
	return WPJAM_API::get_video_mp4($id_or_url);
}

function wpjam_get_qqv_mp4($vid){
	return WPJAM_API::get_qqv_mp4($vid);
}

function wpjam_send_json($response=[], $status_code=null){
	WPJAM_API::send_json($response, $status_code);
}

function wpjam_json_encode( $data, $options = JSON_UNESCAPED_UNICODE, $depth = 512){
	return WPJAM_API::json_encode($data, $options, $depth);
}

function wpjam_json_decode($json, $assoc=true, $depth=512, $options=0){
	return WPJAM_API::json_decode($json, $assoc, $depth, $options);
}

function wpjam_remote_request($url, $args=[], $err_args=[]){
	return WPJAM_API::http_request($url, $args, $err_args);
}

function wpjam_get_ua(){
	return WPJAM_API::get_user_agent();
}

function wpjam_get_user_agent(){
	return WPJAM_API::get_user_agent();
}

function wpjam_get_ua_data($ua=''){
	return WPJAM_API::parse_user_agent($ua);
}

function wpjam_parse_user_agent($ua=''){
	return WPJAM_API::parse_user_agent($ua);
}

function wpjam_get_ipdata($ip=''){
	return WPJAM_API::parse_ip($ip);
}

function wpjam_parse_ip($ip=''){
	return WPJAM_API::parse_ip($ip);
}

function wpjam_get_ip(){
	return WPJAM_API::get_ip();
}

function is_ipad(){
	return WPJAM_API::is_ipad();
}

function is_iphone(){
	return WPJAM_API::is_iphone();
}

function is_ios(){
	return WPJAM_API::is_ios();
}

function is_mac(){
	return is_macintosh();
}

function is_macintosh(){
	return WPJAM_API::is_macintosh();
}

function is_android(){
	return WPJAM_API::is_android();
}

// 判断当前用户操作是否在微信内置浏览器中
function is_weixin(){ 
	return WPJAM_API::is_weixin();
}

// 判断当前用户操作是否在微信小程序中
function is_weapp(){ 
	return WPJAM_API::is_weapp();
}

// 判断当前用户操作是否在头条小程序中
function is_bytedance(){ 
	return WPJAM_API::is_bytedance();
}


function wpjam_is_platform($platform){
	return WPJAM_Platform::is_platform($platform);
}

function wpjam_get_current_platform($platforms=[], $type='key'){
	return WPJAM_Platform::get_current_platform($platforms, $type);
}

if(!function_exists('is_login')){
	function is_login(){
		global $pagenow;
		return $pagenow == 'wp-login.php';
	}	
}

if(!function_exists('str_replace_deep')){
	function str_replace_deep($search, $replace, $value){
		return map_deep($value, function($value) use($search, $replace){
			return str_replace($search, $replace, $value);
		});
	}
}

function wpjam_doing_debug(){
	if(isset($_GET['debug'])){
		if($_GET['debug']){
			return sanitize_key($_GET['debug']);
		}else{
			return true;
		}
	}else{
		return false;
	}
}

// 打印
function wpjam_print_r($value){
	$capability	= (is_multisite())?'manage_site':'manage_options';
	if(current_user_can($capability)){
		echo '<pre>';
		print_r($value);
		echo '</pre>'."\n";
	}
}

function wpjam_var_dump($value){
	$capability	= (is_multisite())?'manage_site':'manage_options';
	if(current_user_can($capability)){
		echo '<pre>';
		var_dump($value);
		echo '</pre>'."\n";
	}
}

function wpjam_pagenavi($total=0){
	$args = [
		'prev_text'	=> '&laquo;',
		'next_text'	=> '&raquo;'
	];

	if(!empty($total)){
		$args['total']	= $total;
	}

	echo '<div class="pagenavi">'.paginate_links($args).'</div>'; 
}

function wpjam_sha1(...$args){
	sort($args, SORT_STRING);
		
	return sha1(implode($args));
}

// 判断一个数组是关联数组，还是顺序数组
function wpjam_is_assoc_array(array $arr){
	if ([] === $arr) return false;
	return array_keys($arr) !== range(0, count($arr) - 1);
}

// 向关联数组指定的 Key 之前插入数据
function wpjam_array_push(&$array, $data=null, $key=false){
	$data	= (array)$data;

	$offset	= ($key===false)?false:array_search($key, array_keys($array));
	$offset	= ($offset)?$offset:false;

	if($offset){
		$array = array_merge(
			array_slice($array, 0, $offset), 
			$data, 
			array_slice($array, $offset)
		);
	}else{	// 没指定 $key 或者找不到，就直接加到末尾
		$array = array_merge($array, $data);
	}
}

function wpjam_array_merge($arr1, $arr2){
	foreach($arr2 as $key => &$value){
		if(is_array($value) && isset($arr1[$key]) && is_array($arr1[$key])){
			$arr1[$key]	= wpjam_array_merge($arr1[$key], $value);
		}else{
			$arr1[$key]	= $value;
		}
	}

	return $arr1;
}

function wpjam_localize_script($handle, $object_name, $l10n ){
	wp_localize_script( $handle, $object_name, array('l10n_print_after' => $object_name.' = ' . wpjam_json_encode( $l10n )) );
}


function wpjam_is_mobile_number($number){
	return preg_match('/^0{0,1}(1[3,5,8][0-9]|14[5,7]|166|17[0,1,3,6,7,8]|19[8,9])[0-9]{8}$/', $number);
}


function wpjam_create_meta_table($meta_type, $table=''){
	if(empty($meta_type)){
		return;
	}

	global $wpdb;

	$table	= $table ?: $wpdb->prefix . $meta_type .'meta';
	$column	= sanitize_key($meta_type . '_id');

	if($wpdb->get_var("show tables like '{$table}'") != $table) {
		$sql	= "CREATE TABLE {$table} (
			meta_id bigint(20) unsigned NOT NULL auto_increment,
			{$column} bigint(20) unsigned NOT NULL default '0',
			meta_key varchar(255) default NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY {$column} ({$column}),
			KEY meta_key (meta_key(191))
		)";


		$wpdb->query($sql);
	}
}

// function wpjam_is_400_number($number){
// 	return preg_match('/^400(\d{7})$/', $number);
// }

// function wpjam_is_800_number($number){
// 	return preg_match('/^800(\d{7})$/', $number);
// }

function wpjam_is_scheduled_event( $hook ) {	// 不用判断参数
	$crons = _get_cron_array();
	if (empty($crons)) return false;
	
	foreach ($crons as $timestamp => $cron) {
		if (isset($cron[$hook])) return true;
	}

	return false;
}

function wpjam_is_holiday($date=''){
	$date	= ($date)?$date:date('Y-m-d', current_time('timestamp'));
	$w		= date('w', strtotime($date));

	$is_holiday = ($w == 0 || $w == 6)?1:0;

	return apply_filters('wpjam_holiday', $is_holiday, $date);
}

function wpjam_set_cookie($key, $value, $expire){
	$expire	= ($expire < time())?$expire+time():$expire;

	$secure = ('https' === parse_url(get_option('home'), PHP_URL_SCHEME));

	setcookie($key, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure);

    if ( COOKIEPATH != SITECOOKIEPATH ){
        setcookie($key, $value, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, $secure);
    }
    $_COOKIE[$key] = $value;
}


function wpjam_get_additional_capabilities($user){
	global $wp_roles;

	$capabilities	= [];

	foreach ($user->caps as $cap => $value) {
		if($value && !$wp_roles->is_role($cap)){
			$capabilities[]	= $cap;
		}
	}

	return $capabilities;
}

function wpjam_set_additional_capabilities($user, $capabilities){
	$old_capabilities 	= wpjam_get_additional_capabilities($user);

	$remove_capabilities	= array_diff($old_capabilities, $capabilities);
	$add_capabilities		= array_diff($capabilities, $old_capabilities);

	if($remove_capabilities){
		foreach ($remove_capabilities as $cap) {
			$user->remove_cap($cap);
		}
	}

	if($add_capabilities){
		foreach ($add_capabilities as $cap) {
			$user->add_cap($cap);
		}
	}

	return $capabilities;
}

// WP_Query 缓存
function wpjam_query($args=[], $cache_time='600'){
	$args['no_found_rows']	= $args['no_found_rows'] ?? true;
	$args['cache_results']	= $args['cache_results'] ?? true;

	$args['cache_it']	= true;

	return new WP_Query($args);
}

function wpjam_validate_post($post_id, $post_type='', $action=''){
	return WPJAM_Post::validate($post_id, $post_type, $action);
}

function wpjam_get_post($post_id, $args=[]){
	return WPJAM_Post::parse_for_json($post_id, $args);
}

function wpjam_get_posts($post_ids, $args=[]){
	$posts = WPJAM_Post::get_by_ids($post_ids, $args);
	return $posts ? array_values($posts) : [];
}

function wpjam_get_post_views($post_id, $addon=true){
	return WPJAM_Post::get_views($post_id, $addon);
}

function wpjam_update_post_views($post_id, $type='views'){
	return WPJAM_Post::update_views($post_id, $type);
}

function wpjam_get_post_excerpt($post=null, $excerpt_length=240){
	return WPJAM_Post::get_excerpt($post, $excerpt_length);
}

function wpjam_has_post_thumbnail(){
	return wpjam_get_post_thumbnail_url() ? true : false;
}

function wpjam_post_thumbnail($size='thumbnail', $crop=1, $class='wp-post-image', $retina=2){
	echo wpjam_get_post_thumbnail(null, $size, $crop, $class, $retina);
}

function wpjam_get_post_thumbnail($post=null, $size='thumbnail', $crop=1, $class='wp-post-image', $retina=2){
	$size	= wpjam_parse_size($size, $retina);
	if($post_thumbnail_url = wpjam_get_post_thumbnail_url($post, $size, $crop)){
		$image_hwstring	= image_hwstring($size['width']/$retina, $size['height']/$retina);
		return '<img src="'.$post_thumbnail_url.'" alt="'.the_title_attribute(['echo'=>false]).'" class="'.$class.'"'.$image_hwstring.' />';
	}else{
		return '';
	}
}

function wpjam_get_post_thumbnail_url($post=null, $size='full', $crop=1){
	return WPJAM_Post::get_thumbnail_url($post, $size, $crop);
}

function wpjam_get_default_thumbnail_url($size='full', $crop=1){
	return WPJAM_Post::get_default_thumbnail_url($size, $crop);
}

function wpjam_get_post_first_image_url($post=null, $size='full'){
	return WPJAM_Post::get_first_image_url($post, $size);
}

function wpjam_get_related_posts_query($number=5, $post_type=null){
	return WPJAM_Post::get_related_query(null, $number, $post_type);
}

function wpjam_related_posts($args=[]){
	echo wpjam_get_related_posts($args);
}

function wpjam_get_related_posts($args=[]){
	$args	= apply_filters('wpjam_related_posts_args', $args);

	$post_type	= $args['post_type'] ?? null;
	$number		= $args['number'] ?? null;

	$related_query	= wpjam_get_related_posts_query($number, $post_type);
	$related_posts	= wpjam_get_post_list($related_query, $args);

	return $related_posts;
}

function wpjam_get_new_posts($args=[]){
	$wpjam_query	= wpjam_query(array(
		'posts_per_page'=> $args['number'] ?? 5, 
		'post_type'		=> $args['post_type'] ?? 'post', 
		'orderby'		=> $args['orderby'] ?? 'date', 
	));

	return wpjam_get_post_list($wpjam_query, $args);
}

function wpjam_new_posts($args=[]){
	echo wpjam_get_new_posts($args);
}

function wpjam_get_top_viewd_posts($args=[]){
	$date_query	= array();

	if(isset($args['days'])){
		$date_query	= array(array(
			'column'	=> $args['column']??'post_date_gmt',
			'after'		=> $args['days'].' days ago',
		));
	}

	$wpjam_query	= wpjam_query(array(
		'posts_per_page'=> $args['number'] ?? 5, 
		'post_type'		=> $args['post_type']??['post'], 
		'orderby'		=> 'meta_value_num', 
		'meta_key'		=> 'views', 
		'date_query'	=> $date_query 
	));

	return wpjam_get_post_list($wpjam_query, $args);
}

function wpjam_top_viewd_posts($args=[]){
	echo wpjam_get_top_viewd_posts($args);
}

function wpjam_get_post_list($wpjam_query, $args=[]){
	return WPJAM_API::get_post_list($wpjam_query, $args);
}

function wpjam_get_terms(){
	$args_num	= func_num_args();
	$func_args	= func_get_args();

	if($func_args[0] && wp_is_numeric_array($func_args[0])){
		$term_ids	= $func_args[0];
		$args		= [];

		if($args_num == 2){
			$args	= $func_args[1];
		}

		$terms	= WPJAM_Term::update_caches($term_ids, $args);

		return $terms ? array_values($terms) : [];
	}else{
		$args		= $func_args[0];
		$max_depth	= -1;

		if($args_num == 2){
			$max_depth	= $func_args[1];
		}

		return WPJAM_Term::get_terms($args, $max_depth);
	}
}

function wpjam_flatten_terms($terms){
	return WPJAM_Term::flatten($terms);
}

function wpjam_get_term($term, $taxonomy){
	return WPJAM_Term::get_term($term, $taxonomy);
}

function wpjam_get_parent_terms($term){
	return WPJAM_Term::get_parents($term);
}

function wpjam_has_term_thumbnail(){
	return wpjam_get_term_thumbnail_url()? true : false;
}

function wpjam_term_thumbnail($size='thumbnail', $crop=1, $class="wp-term-image", $retina=2){
	echo  wpjam_get_term_thumbnail(null, $size, $crop, $class);
}

function wpjam_get_term_thumbnail($term=null, $size='thumbnail', $crop=1, $class="wp-term-image", $retina=2){
	$size	= wpjam_parse_size($size, $retina);
	
	if($term_thumbnail_url = wpjam_get_term_thumbnail_url($term, $size, $crop)){
		$image_hwstring	= image_hwstring($size['width']/$retina, $size['height']/$retina);
		
		return  '<img src="'.$term_thumbnail_url.'" class="'.$class.'"'.$image_hwstring.' />';
	}else{
		return '';
	}
}

function wpjam_get_term_thumbnail_url($term=null, $size='full', $crop=1){
	return WPJAM_Term::get_thumbnail_url($term, $size, $crop);
}



function wpjam_basic_get_setting($setting_name){
	$setting_value	= wpjam_get_setting('wpjam-basic', $setting_name);

	if($setting_value){
		if($setting_name == 'disable_rest_api'){
			return wpjam_basic_get_setting('disable_post_embed') && wpjam_basic_get_setting('diable_block_editor');
		}elseif($setting_name == 'disable_xml_rpc'){
			return wpjam_basic_get_setting('diable_block_editor');
		}
	}

	return $setting_value;
}

function wpjam_basic_update_setting($setting, $value){
	return wpjam_update_setting('wpjam-basic', $setting, $value);
}

function wpjam_basic_get_default_settings(){
	return [
		'diable_revision'			=> 1,
		'disable_trackbacks'		=> 1,
		'disable_emoji'				=> 1,
		'disable_texturize'			=> 1,
		'disable_privacy'			=> 1,
		
		'remove_head_links'			=> 1,
		'remove_capital_P_dangit'	=> 1,

		'admin_footer'				=> '<span id="footer-thankyou">感谢使用<a href="https://cn.wordpress.org/" target="_blank">WordPress</a>进行创作。</span> | <a href="http://wpjam.com/" title="WordPress JAM" target="_blank">WordPress JAM</a>'
	];
}