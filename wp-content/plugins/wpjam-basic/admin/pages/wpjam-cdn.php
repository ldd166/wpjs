<?php
add_filter('wpjam_cdn_setting', function(){
	$detail = '
	<p>阿里云 OSS 用户：请点击这里注册和申请<a href="http://wpjam.com/go/aliyun/" target="_blank">阿里云</a>可获得代金券，阿里云OSS<strong><a href="https://blog.wpjam.com/m/aliyun-oss-cdn/" target="_blank">详细使用指南</a></strong>。</p>
	<p>腾讯云 COS 用户：请点击这里注册和申请<a href="http://wpjam.com/go/qcloud/" target="_blank">腾讯云</a>可获得优惠券。</p>';

	$cdns			= wpjam_get_cdns();
	$cdn_options	= array_map(function($cdn){return $cdn['title'];}, $cdns);
	$cdn_options	= array_merge([''=>' '], $cdn_options);

	$cdn_fields		= [
		'cdn_name'	=> ['title'=>'云存储',	'type'=>'select',	'options'=>$cdn_options],
		'host'		=> ['title'=>'CDN域名',	'type'=>'url',		'description'=>'设置为在CDN云存储绑定的域名。<strong>请在域名前面要加上 http://或https://</strong>。'],
		'detail'	=> ['title'=>'其他说明',	'type'=>'view',		'value'=>$detail],
	];

	$local_fields = [		
		'exts'		=> ['title'=>'扩展名',	'type'=>'text',		'value'=>'png|jpg|jpeg|gif|ico',	'description'=>'设置要缓存静态文件的扩展名，请使用 | 分隔开，|前后都不要留空格。'],
		'dirs'		=> ['title'=>'目录',		'type'=>'text',		'value'=>'wp-content|wp-includes',	'description'=>'设置要缓存静态文件所在的目录，请使用 | 分隔开，|前后都不要留空格。'],
		'local'		=> ['title'=>'本地域名',	'type'=>'url',		'value'=>home_url(),				'description'=>'将该域名也填入CDN的镜像源中。'],
		'locals'	=> ['title'=>'其他域名',	'type'=>'mu-text',	'item_type'=>'url'],
	];

	$remote_options	= [
		0			=>'关闭远程图片镜像到云存储。',
		1			=>'自动将远程图片镜像到云存储。',
		'download'	=>'将远程图片下载服务器再镜像到云存储。'
	];

	global $wp_rewrite;

	if(is_multisite() || !$wp_rewrite->using_mod_rewrite_permalinks() || !extension_loaded('gd')){
		unset($remote_options[1]);
	}

	$remote_fields	= [
		'remote'		=> ['title'=>'远程图片',	'type'=>'select',	'options'=>$remote_options],
		'exceptions'	=> ['title'=>'例外',		'type'=>'textarea',	'class'=>'regular-text','description'=>'如果远程图片的链接中包含以上字符串或者域名，就不会被保存并镜像到云存储。']
	];

	$image_fields	= [
		'interlace'	=> ['title'=>'渐进显示',	'type'=>'checkbox',	'description'=>'是否JPEG格式图片渐进显示。'],
		'quality'	=> ['title'=>'图片质量',	'type'=>'number',	'class'=>'all-options',	'description'=>'<br />1-100之间图片质量，七牛默认为75。','mim'=>0,'max'=>100]
	];

	$watermark_options = [
		'SouthEast'	=> '右下角',
		'SouthWest'	=> '左下角',
		'NorthEast'	=> '右上角',
		'NorthWest'	=> '左上角',
		'Center'	=> '正中间',
		'West'		=> '左中间',
		'East'		=> '右中间',
		'North'		=> '上中间',
		'South'		=> '下中间',
	];

	$watermark_fields = [
		'watermark'	=> ['title'=>'水印图片',	'type'=>'image',	'description'=>''],
		'disslove'	=> ['title'=>'透明度',	'type'=>'number',	'class'=>'all-options',	'description'=>'<br />透明度，取值范围1-100，缺省值为100（完全不透明）','min'=>0,	'max'=>100],
		'gravity'	=> ['title'=>'水印位置',	'type'=>'select',	'options'=>$watermark_options],
		'dx'		=> ['title'=>'横轴边距',	'type'=>'number',	'class'=>'all-options',	'description'=>'<br />横轴边距，单位:像素(px)，缺省值为10'],
		'dy'		=> ['title'=>'纵轴边距',	'type'=>'number',	'class'=>'all-options',	'description'=>'<br />纵轴边距，单位:像素(px)，缺省值为10'],
	];

	if(is_network_admin()){
		unset($local_fields['local']);
		unset($watermark_fields['watermark']);
	}

	$remote_summary	= '
	*自动将远程图片镜像到云存储需要你的博客支持固定链接和服务器支持GD库（不支持gif图片）。
	*将远程图片下载服务器再镜像到云存储，会在你保存文章的时候自动执行。
	';

	$sections	= [
		'cdn'		=> ['title'=>'CDN设置',		'fields'=>$cdn_fields],
		'local'		=> ['title'=>'本地设置',		'fields'=>$local_fields],
		'remote'	=> ['title'=>'远程图片设置',	'fields'=>$remote_fields,	'summary'=>$remote_summary],
		'image'		=> ['title'=>'图片设置',		'fields'=>$image_fields],
		'watermark'	=> ['title'=>'水印设置',		'fields'=>$watermark_fields]
	];
	
	return compact('sections');
});

if(isset($_GET['reset'])){
	delete_option('wpjam-cdn');
}elseif(!empty($_GET['cdn'])){
	$cdns	= wpjam_get_cdns();

	if(isset($cdns[$_GET['cdn']])){
		wpjam_update_setting('wpjam-cdn', 'cdn_name', $_GET['cdn']);
	}
}

add_action('updated_option', function($option){
	if($option == 'wpjam-cdn'){
		flush_rewrite_rules();
	}
});

add_action('added_option', function($option){
	if($option == 'wpjam-cdn'){
		flush_rewrite_rules();
	}
});

add_action('admin_head',function(){
	?>
	<script type="text/javascript">
	jQuery(function($){
		function wpjam_cdn_switched(){
			var sections 	= ['image','watermark','remote'];
			var cdn_name	= $('select#cdn_name').val();
			
			$.each(sections, function(index,section){
				$('#tab_title_'+section).hide();
			});

			if(cdn_name){
				$('#tab_title_remote').show();

				if(cdn_name == 'qiniu'){
					$('#tab_title_image').show();
					$('#tab_title_watermark').show();
				}
			}
		}

		wpjam_cdn_switched();

		$('select#cdn_name').change(function(){
			wpjam_cdn_switched();
		});
	
		// $('body').on('option_action_success', function(e, response){
		// 	wpjam_cdn_switched();
		// });
	});
	</script>
	<?php
});