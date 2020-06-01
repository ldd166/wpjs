<?php
add_filter('wpjam_smtp_tabs', function($tabs){
	return [
		'smtp'	=> ['title'=>'发信设置',	'function'=>'option', 'option_name'=>'wpjam-basic'],
		'send'	=> ['title'=>'发送测试',	'function'=>'wpjam_smtp_send_page'],
	];
});

add_filter('wpjam_basic_setting', function(){
	$fields = [
		'smtp_setting'			=> ['title'=>'SMTP 设置',	'type'=>'fieldset','fields'=>[
			'smtp_host'	=> ['title'=>'地址',		'type'=>'text',		'class'=>'all-options',	'value'=>'smtp.qq.com'],
			'smtp_ssl'	=> ['title'=>'发送协议',	'type'=>'text',		'class'=>'',			'value'=>'ssl'],
			'smtp_port'	=> ['title'=>'SSL端口',	'type'=>'number',	'class'=>'',			'value'=>'465'],
			'smtp_user'	=> ['title'=>'邮箱账号',	'type'=>'email',	'class'=>'all-options'],
			'smtp_pass'	=> ['title'=>'邮箱密码',	'type'=>'password',	'class'=>'all-options'],
		]],
		'smtp_mail_from_name'	=> ['title'=>'发送者姓名',	'type'=>'text',	'class'=>''],
		'smtp_reply_to_mail'	=> ['title'=>'回复地址',		'type'=>'email','class'=>'all-options',	'description'=>'不填则用户回复使用SMTP设置中的邮箱账号']
	];

	return compact('fields');
});

add_action('wpjam_page_action', function($action){
	if($action == 'submit'){
		$to			= wpjam_get_data_parameter('to');
		$subject	= wpjam_get_data_parameter('subject');
		$message	= wpjam_get_data_parameter('message');

		if(wp_mail($to, $subject, $message)){
			wpjam_send_json();
		}
	}
});

add_action('wp_mail_failed', function ($mail_failed){
	wpjam_send_json($mail_failed);
});

function wpjam_smtp_send_page(){
	$fields = array(
		'to'		=> array('title'=>'收件人',	'type'=>'email',	'required'),
		'subject'	=> array('title'=>'主题',	'type'=>'text',		'required'),
		'message'	=> array('title'=>'内容',	'type'=>'textarea',	'style'=>'max-width:640px;',	'rows'=>8,	'required'),
	);

	wpjam_ajax_form([
		'fields'		=> $fields, 
		'action'		=> 'submit', 
		'submit_text'	=> '发送'
	]);
}

