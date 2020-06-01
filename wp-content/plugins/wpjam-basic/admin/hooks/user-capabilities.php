<?php
add_filter('additional_capabilities_display', '__return_false' );

function wpjam_edit_user_capabilities_profile($profileuser){

	if(current_user_can('edit_users')){
		$capabilities	= wpjam_get_additional_capabilities($profileuser);

		echo '<h3>额外权限</h3>';

		$form_fields = array(
			'capabilities'	=> array('title'=>'权限',	'type'=>'mu-text',	'value'=>$capabilities),
		);

		wpjam_fields($form_fields); 
	}
}
add_action('show_user_profile','wpjam_edit_user_capabilities_profile');
add_action('edit_user_profile','wpjam_edit_user_capabilities_profile');


function wpjam_edit_user_capabilities_profile_update($user_id){

	if(current_user_can('edit_users')){
		$user			= get_userdata($user_id);
		$capabilities	= $_POST['capabilities'] ?: [];

		wpjam_set_additional_capabilities($user, $capabilities);
	}
}
add_action('personal_options_update','wpjam_edit_user_capabilities_profile_update');
add_action('edit_user_profile_update','wpjam_edit_user_capabilities_profile_update');
