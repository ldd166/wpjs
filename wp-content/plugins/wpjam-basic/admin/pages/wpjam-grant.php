<?php
add_action('wpjam_page_action', function($action){
	if($action == 'reset_secret'){
		$data	= wp_parse_args($_POST['data']);
		$appid	= $data['appid'];
		$secret	= WPJAM_Grant::reset_secret($appid);

		if(is_wp_error($secret)){
			wpjam_send_json($secret);
		}else{
			wpjam_send_json(compact('appid', 'secret'));
		}
	}elseif($action == 'delete_grant'){
		$data	= wp_parse_args($_POST['data']);
		$appid	= $data['appid'];

		$result	= WPJAM_Grant::delete_grant($appid);

		if(is_wp_error($result)){
			wpjam_send_json($result);
		}else{
			wpjam_send_json(compact('appid'));
		}
	}elseif($action == 'create_grant'){
		$appid	= WPJAM_Grant::generate_appid();

		$result	= WPJAM_Grant::add_appid($appid);

		if(is_wp_error($result)){
			wpjam_send_json($result);
		}

		$secret	= WPJAM_Grant::reset_secret($appid);
		$table 	= wpjam_get_grant_table($appid, $secret);
		$grants	= WPJAM_Grant::get_grants();

		$rest	= 3 - count($grants);

		wpjam_send_json(compact('table', 'rest'));
	}elseif($action == 'access_token'){
		$access_token	= '
			<p>access_token 是开放接口的全局<strong>接口调用凭据</strong>，第三方调用各接口时都需使用 access_token，开发者需要进行妥善保存。</p>
			<p>access_token 的有效期目前为2个小时，需定时刷新，重复获取将导致上次获取的 access_token 失效。</p>

			<h4>请求地址</h4>

			<p><code>'.home_url('/api/').'token/grant.json?appid=APPID&secret=APPSECRET</code></p>

			<h4>参数说明<h4>

			'.do_shortcode('[table th=1 class="form-table striped"]
			参数	
			是否必须
			说明

			appid
			是
			第三方用户凭证

			secret
			是
			第三方用户证密钥。
			[/table]').'
			
			<h4>返回说明</h4>

			<p><code>
				{"errcode":0,"access_token":"ACCESS_TOKEN","expires_in":7200}
			</code></p>';

		$data	= wpjam_get_ajax_form([
			'fields'		=>['access_token'=>['title'=>'', 'type'=>'view', 'value'=>$access_token]], 
			'action'		=>'access_token', 
			'submit_text'	=>''
		]);

		wpjam_send_json(compact('data'));
	}
});

function wpjam_get_grant_table($appid, $secret=''){
	$secret	= $secret ? '<p class="secret" id="secret_'.$appid.'" style="display:block;">'.$secret.'</p>' : '<p class="secret" id="secret_'.$appid.'"></p>';

	return '<table class="form-table widefat striped" id="table_'.$appid.'">
		<tbody>
			<tr>
				<th>AppID</th>
				<td class="appid">'.$appid.'</td>
				<td>'. wpjam_get_ajax_button([
					'action'		=>'delete_grant', 
					'button_text'	=>'删除',
					'class'			=>'button',
					'data'			=>compact('appid'),
					'direct'		=>true,
					'confirm'		=>true
				]).'</td>
			</tr>
			<tr>
				<th>Secret</th>
				<td>出于安全考虑，Secret不再被明文保存，忘记密钥请点击重置：'.$secret.'</td>
				<td>'.wpjam_get_ajax_button([
					'action'		=>'reset_secret', 
					'button_text'	=>'重置',
					'class'			=>'button',
					'data'			=>compact('appid'),
					'direct'		=>true,
					'confirm'		=>true
				]).'</td>
			</tr>
		</tbody>
	</table>';
}

function wpjam_get_grant_create_table($count){
	return'<table class="form-table widefat striped" id="create_grant" style="'. ($count >=3 ? 'display: none;' : '').'">
		<tbody>
			<tr>
				<th>创建</th>
				<td>点击右侧按钮创建 AppID/Secret，最多可创建三个：</td>
				<td>'.wpjam_get_ajax_button([
					'action'		=>'create_grant', 
					'button_text'	=>'创建',
					'class'			=>'button',
					'direct'		=>true,
					'confirm'		=>true
				]).'</td>
			</tr>
		</tbody>
	</table>';
}

function wpjam_grant_page(){
	?>
	<div class="card">

	<h3>开发者 ID<?php wpjam_ajax_button([
		'action'		=>'access_token', 
		'button_text'	=>'接口文档',
		'page_title'	=>'获取access_token',
		'class'			=>'page-title-action button'
	]);?></h3>

	<?php 

	$grants = WPJAM_Grant::get_grants();
	$count	= count($grants);

	if($grants){
		foreach($grants as $grant){
			echo wpjam_get_grant_table($grant['appid']);
		} 
	}

	echo wpjam_get_grant_create_table($count);

	?>
	
	</div>

	<?php
}

add_action('admin_head', function(){
	?>
	
	<style type="text/css">
	div.card {max-width:640px; width:640px;}
	
	div.card .form-table{margin: 20px 0; border: none;}
	div.card .form-table th{width: 60px; padding-left: 10px;}

	table.form-table code{display: block; padding: 5px 10px; font-size: smaller; }

	td.appid{font-weight: bold;}
	p.secret{display: none; background: #ffc; padding:4px 8px; font-weight: bold;}
	a.wpjam-button.button{float: right;}
	</style>

	<script type="text/javascript">
	jQuery(function($){
		$('body').on('page_action_success', function(e, response){
			if(response.page_action == 'reset_secret'){
				$('p#secret_'+response.appid).show().html(response.secret);
			}else if(response.page_action == 'create_grant'){
				$('table#create_grant').before(response.table);
				if(response.rest == 0){
					$('table#create_grant').hide();
				}
			}else if(response.page_action == 'delete_grant'){
				$('table#table_'+response.appid).remove();
				
				$('table#create_grant').show();
			}
		});
	});
	</script>
	
	<?php
});


