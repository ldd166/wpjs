<?php
function wpjam_admin_add_error($message='', $type='success'){
	WPJAM_Notice::$errors[]	= compact('message','type');
}

add_action('admin_notices', function(){
	if(WPJAM_Notice::$errors){
		foreach (WPJAM_Notice::$errors as $error){
			$error	= wp_parse_args($error, ['type'=>'error',	'message'=>'']);

			if($error['message']){
				echo '<div class="notice notice-'.$error['type'].' is-dismissible"><p>'.$error['message'].'</p></div>';
			}
		}
	}

	if($notice_key	= wpjam_get_parameter('notice_key')){
		WPJAM_Notice::delete($notice_key);
	}

	$notices	= WPJAM_Notice::get_notices(get_current_user_id());

	if(current_user_can('manage_options')){
		$notices	= array_merge($notices, WPJAM_Notice::get_notices());
	}

	if(empty($notices)){
		return;
	}

	uasort($notices, function($n, $m){ return $m['time'] <=> $n['time']; });

	$modal_notice	= '';

	foreach ($notices as $notice_key => $notice){
		$notice = wp_parse_args($notice, [
			'type'		=> 'info',
			'class'		=> 'is-dismissible',
			'admin_url'	=> '',
			'notice'	=> '',
			'title'		=> '',
			'modal'		=> 0,
		]);

		$admin_notice	= $notice['notice'];

		if($notice['admin_url']){
			$admin_notice	.= $notice['modal'] ? "\n\n" : ' ';
			$admin_notice	.= '<a style="text-decoration:none;" href="'.add_query_arg(compact('notice_key'), home_url($notice['admin_url'])).'">点击查看<span class="dashicons dashicons-arrow-right-alt"></span></a>';
		}

		$admin_notice	= wpautop($admin_notice).wpjam_get_ajax_button([
			'tag'			=>'span',
			'action'		=>'delete_notice', 
			'class'			=>'hidden',
			'button_text'	=>'删除',
			'data'			=>compact('notice_key'),
			'direct'		=>true
		]);

		if($notice['modal']){
			if($modal_notice){
				continue;	// 弹窗每次只显示一条
			}

			$modal_notice	= wpjam_json_encode($admin_notice);
			$modal_title	= $notice['title'] ?: '消息';
		}else{
			echo '<div class="notice notice-'.$notice['type'].' '.$notice['class'].'">'.$admin_notice.'</div>';
		}
	}

	if($modal_notice){
		?>
		<script type="text/javascript">
		jQuery(function($){
			$('#tb_modal').html('<?php echo $modal_notice; ?>');
			tb_show('<?php echo esc_js($modal_title); ?>', "#TB_inline?inlineId=tb_modal&height=200");
			tb_position();
		});
		</script>
		<?php
	}
});

add_action('wpjam_page_action', function($action){
	if($action == 'delete_notice'){
		if($notice_key = wpjam_get_data_parameter('notice_key')){
			WPJAM_Notice::delete($notice_key);
			wpjam_send_json();
		}else{
			wpjam_send_json([
				'errcode'	=> 'invalid_notice_key',
				'errmsg'	=> '非法消息'
			]);
		}
	}
});