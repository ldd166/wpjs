<?php
add_filter('wpjam_basic_sub_pages',function($subs){
	$subs['wpjam-smtp']	= [
		'menu_title'	=> '发信设置',
		'page_title'	=> 'SMTP邮件服务',
		'function'		=> 'tab',
		'summary'		=> 'SMTP 邮件服务扩展让你可以使用第三方邮箱的 SMTP 服务来发邮件，详细介绍请点击：<a href="https://blog.wpjam.com/m/wpjam-smtp/" target="_blank">SMTP 邮件服务扩展</a>，点击这里查看：<a target="_blank" href="http://blog.wpjam.com/m/gmail-qmail-163mail-imap-smtp-pop3/" target="_blank">常用邮箱的 SMTP 设置</a>。',
		'page_file'		=> WPJAM_BASIC_PLUGIN_DIR.'admin/pages/wpjam-smtp.php'
	];
	return $subs;
});


