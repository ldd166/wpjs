<?php
add_filter('wpjam_basic_sub_pages',function($subs){
	$subs['wpjam-seo']	= [
		'menu_title'	=>'SEO设置',
		'page_title'	=>'简单SEO',
		'function'		=>'option', 
		'option_name'	=>'wpjam-basic', 
		'page_file'		=>WPJAM_BASIC_PLUGIN_DIR.'admin/pages/wpjam-seo.php'
	];

	return $subs;
});

if(wpjam_basic_get_setting('seo_individual')){
	add_action('wpjam_builtin_page_load', function ($screen_base, $current_screen){
		if($screen_base == 'edit'){
			$seo_post_types	= wpjam_basic_get_setting('seo_post_types') ?? ['post'];

			if($seo_post_types  && in_array($current_screen->post_type, $seo_post_types)){
				$post_type	= $current_screen->post_type;
				include WPJAM_BASIC_PLUGIN_DIR.'admin/hooks/post-seo.php';
			}
		}elseif($screen_base == 'edit-tags'){
			$seo_taxonomies	= wpjam_basic_get_setting('seo_taxonomies') ?? ['category'];

			if($seo_taxonomies && in_array($current_screen->taxonomy, $seo_taxonomies)){
				$taxonomy	= $current_screen->taxonomy;
				include WPJAM_BASIC_PLUGIN_DIR.'admin/hooks/term-seo.php';
			}
		}elseif($screen_base == 'post'){
			$seo_post_types	= wpjam_basic_get_setting('seo_post_types') ?? ['post'];

			if($seo_post_types  && in_array($current_screen->post_type, $seo_post_types)){
				add_filter('wpjam_post_options',function($post_options){
					$post_options['wpjam-seo'] = [
						'title'		=> 'SEO设置',
						'fields'	=> [
							'seo_title'			=> ['title'=>'标题', 	'type'=>'text',		'placeholder'=>'不填则使用文章标题'],
							'seo_description'	=> ['title'=>'描述', 	'type'=>'textarea'],
							'seo_keywords'		=> ['title'=>'关键字',	'type'=>'text']
						]
					];
					return $post_options;
				});
			}
				
		}elseif($screen_base == 'term'){
			$seo_taxonomies	= wpjam_basic_get_setting('seo_taxonomies') ?? ['category'];

			if($seo_taxonomies && in_array($current_screen->taxonomy, $seo_taxonomies)){
				add_filter('wpjam_term_options', function($term_options){
					$term_options['seo_title'] 			= ['title'=>'SEO 标题',		'type'=>'text'];
					$term_options['seo_description']	= ['title'=>'SEO 描述',		'type'=>'textarea'];
					$term_options['seo_keywords']		= ['title'=>'SEO 关键字',	'type'=>'text'];
					return $term_options;
				});
			}
		}
	}, 10, 2);
}

add_action('blog_privacy_selector', function(){
	?>
	<style type="text/css">tr.option-site-visibility{display: none;}</style>
	<?php
});

