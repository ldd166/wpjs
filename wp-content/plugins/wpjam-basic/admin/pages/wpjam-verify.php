<?php
add_action('wpjam_page_action', function($action){
	if($action == 'verify_wpjam'){
		WPJAM_Verify::bind_user(wp_parse_args($_POST['data']));
		wpjam_send_json();
	}
});

function wpjam_verify_page(){
	$response	= WPJAM_Verify::get_qrcode();

	if(is_wp_error($response)){

		echo '<div class="notice notice-error"><p>'.$response->get_error_message().'</p></div>';

		return;
	}

	echo '
	<p><strong>通过验证才能使用 WPJAM Basic 的扩展功能。 </strong></p>
	<p>1. 使用微信扫描下面的二维码获取验证码。<br />
	2. 将获取验证码输入提交即可！<br />
	3. 如果验证不通过，请使用 Chrome 浏览器验证，并在验证之前清理浏览器缓存。</p>
	';

	$qrcode = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$response['ticket'];
	$fields	= [
		'qrcode'	=> ['title'=>'二维码',	'type'=>'view',		'value'=>'<img src="'.$qrcode.'" style="max-width:250px;" />'],
		'code'		=> ['title'=>'验证码',	'type'=>'number',	'class'=>'all-options',	'description'=>'验证码10分钟内有效！'],
		'scene'		=> ['title'=>'scene',	'type'=>'hidden',	'value'=>$response['scene']]
	];

	wpjam_ajax_form([
		'fields'		=> $fields, 
		'action'		=> 'verify_wpjam',
		'submit_text'	=> '验证'
	]);
}

add_action('admin_head', function(){
	$page	= current_user_can('manage_options') ? 'wpjam-extends' : 'wpjam-basic-topics';
	
	?>

	<style type="text/css">
	.form-table th{width: 100px;}
	</style>

	<script type="text/javascript">
	jQuery(function($){
		$('body').on('page_action_success', function(e, response){
			if(response.page_action == 'verify_wpjam'){
				window.location.replace('<?php echo admin_url('admin.php?page='.$page); ?>');
			}
		});
	});
	</script>
	<?php
});