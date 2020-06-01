<?php
add_filter('wpjam_cdn_setting', function(){

	$taxonomies			= get_taxonomies(['show_ui'=>true, 'public'=>true],'objects');
	$taxonomy_options	= wp_list_pluck($taxonomies, 'label', 'name');

	$term_thumbnail_taxonomies	= wpjam_cdn_get_setting('term_thumbnail_taxonomies') ?: [];
	$term_taxonomy_options		= wp_array_slice_assoc($taxonomy_options, $term_thumbnail_taxonomies);

	$post_thumbnail_orders_options	= [''=>'请选择来源', 'first'=>'第一张图','post_meta'=>'自定义字段'];

	if(wpjam_cdn_get_setting('term_thumbnail_type')){
		$post_thumbnail_orders_options += ['term'=>'分类缩略图'];
	}

	$sections	= [
		'thumb'	=> [
			'title'		=> '缩略图',			
			'fields'	=> [
				'default'	=> ['title'=>'默认缩略图',	'type'=>'image',	'description'=>'各种情况都找不到缩略图之后默认的缩略图，可以填本地或者云存储的地址！'],
				'width'		=> ['title'=>'图片最大宽度',	'type'=>'number',	'class'=>'all-options',	'description'=>'<br />设置博客文章内容中图片的最大宽度，插件会使用将图片缩放到对应宽度，节约流量和加快网站速度加载。']
			],
		],
		'term_thumbnail'	=> [
			'title'		=> '分类缩略图',
			'fields'	=> [
				'term_thumbnail_type'		=> ['title'=>'分类缩略图',	'type'=>'select',	'options'=>[''=>'关闭分类缩略图', 'img'=>'本地媒体模式','image'=>'输入图片链接模式']],
				'term_thumbnail_taxonomies'	=> ['title'=>'支持分类模式',	'type'=>'checkbox',	'options'=>$taxonomy_options],
				'term_thumbnail_size'		=> ['title'=>'缩略图尺寸',	'type'=>'fieldset',	'fields'=>[
					'term_thumbnail_width'		=> ['title'=>'',	'type'=>'number',	'class'=>'small-text'],
					'term_thumbnail_height'		=> ['title'=>'x',	'type'=>'number',	'class'=>'small-text']
				]]
			]
		],
		'post_thumbnail'	=> [
			'title'		=> '文章缩略图',
			'summary'	=> '首先使用文章特色图片，如果没有设置文章特色图片，将按照下面的顺序获取：',
			'fields'	=> [
				'post_thumbnail_orders'	=> ['title'=>'获取顺序',	'type'=>'mu-fields',	'max_items'=>5,	'fields'=>[
					'type'		=> ['title'=>'',	'type'=>'select',	'class'=>'post_thumbnail_order_class',		'options'=>$post_thumbnail_orders_options],
					'taxonomy'	=> ['title'=>'',	'type'=>'select',	'class'=>'post_thumbnail_order_taxonomy',	'options'=>[''=>'请选择分类模式']+$term_taxonomy_options],
					'post_meta'	=> ['title'=>'',	'type'=>'text',		'class'=>'post_thumbnail_order_post_meta all-options',	'placeholder'=>'请输入自定义字段的 meta_key'],
				]]
			]
		]
	];
	
	return compact('sections');
});


add_action('admin_head', function(){
	$taxonomies			= get_taxonomies(['show_ui'=>true, 'public'=>true],'objects');
	$taxonomy_options	= wp_list_pluck($taxonomies, 'label', 'name');

	?>
	<style type="text/css">
		#tr_post_thumbnail_orders .sub-field,
		#div_term_thumbnail_width, 
		#div_term_thumbnail_height{
			display: inline-block;
			margin: 0;
		}

		#tr_post_thumbnail_orders label.sub-field-label,
		#div_term_thumbnail_taxonomies label.sub-field-label, 
		#div_term_thumbnail_width label.sub-field-label, 
		#div_term_thumbnail_height label.sub-field-label{
			min-width: inherit; margin: 0 3px; font-weight: normal;
		}

		#TB_window #tr_post_thumbnail_orders div.sub-field-detail,
		#TB_window #div_term_thumbnail_width div.sub-field-detail, 
		#TB_window #div_term_thumbnail_height div.sub-field-detail{
			width:inherit;
		}

		#tr_post_thumbnail_view th,  #tr_post_thumbnail_view td{
			padding-bottom: 0;
		}

		#tr_post_thumbnail_orders div.mu-fields > div.mu-item > a{
			margin: 0 0 10px 10px
		}
	</style>
	<script type="text/javascript">
	jQuery(function ($){
		$('body').on('change', '#term_thumbnail_type', function (){
			$('#tr_term_thumbnail_taxonomies').hide();
			$('#tr_term_thumbnail_size').hide();

			if($(this).val()){
				$('#tr_term_thumbnail_taxonomies').show();
				$('#tr_term_thumbnail_size').show();

				if($('body .post_thumbnail_order_class option[value="term"]').length == 0){
					var opt = $("<option></option>").text('分类缩略图').val('term');
					$('body .post_thumbnail_order_class').append(opt);
				}
			}else{
				$('body .post_thumbnail_order_class option[value="term"]').remove();
				$('body .post_thumbnail_order_class').change();
			}
		});

		$('body').on('change', '.post_thumbnail_order_class', function(){
			var i	= $(this).data('i');

			$('#sub_field_taxonomy_'+i).hide();
			$('#sub_field_post_meta_'+i).hide();

			if($(this).val() == 'term'){
				$('#sub_field_taxonomy_'+i).show();
			}else if($(this).val() == 'post_meta'){
				$('#sub_field_post_meta_'+i).show();
			}
		});

		$('body').on('mu_fields_added', '#tr_post_thumbnail_orders .mu-fields', function(event, i){
			$('#sub_field_taxonomy_'+i).hide();
			$('#sub_field_post_meta_'+i).hide();
		});
		
		var taxonomy_options 	= <?php echo wpjam_json_encode($taxonomy_options); ?>;

		$('body').on('change', '#div_term_thumbnail_taxonomies input', function(){
			var taxonomy = $(this).val();

			if($(this).is(":checked")){
				var opt = $("<option></option>").text(taxonomy_options[taxonomy]).val(taxonomy);
				$('body .post_thumbnail_order_taxonomy').append(opt);
			}else{
				$('body .post_thumbnail_order_taxonomy option[value="'+taxonomy+'"]').remove();
			}
		});

		$('body #term_thumbnail_type').change();
		$('body .post_thumbnail_order_class').change();
	});
	</script>
	<?php
});