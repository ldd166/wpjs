jQuery(function($){
	$.fn.extend({
		wpjam_add_attachment: function(attachment){
			var render	= wp.template('wpjam-img');
			
			$(this).prev('input').val($(this).data('item_type') == 'url' ? attachment.url : attachment.id);
			$(this).html(render({
				img_url		: attachment.url,
				img_style	: $(this).data('img_style'),
				thumb_args	: $(this).data('thumb_args')
			})).removeClass('button add_media');
		},

		wpjam_add_mu_attachment: function(attachment){
			var render		= wp.template('wpjam-mu-img');
			var max_items	= parseInt($(this).data('max_items'));

			if(max_items && $(this).parent().parent().find('div.mu-item').length >= max_items){
				return ;
			}
			
			$(this).before(render({
				img_url		: attachment.url, 
				img_value	: ($(this).data('item_type') == 'url') ? attachment.url : attachment.id,
				input_name	: $(this).data('input_name'),
				thumb_args	: $(this).data('thumb_args'),
				key			: $(this).data('key'),
				i			: $(this).data('i')
			}));
		},

		wpjam_remove_attachemnt: function(){
			$(this).prev('input').val('');
			$(this).find('img').fadeOut(300, function(){
				$(this).remove();
			});

			if($(this).parent().hasClass('wp-media-buttons')){
				$(this).addClass('button add_media').html('<span class="wp-media-buttons-icon"></span> 添加图片</button>');
			}

			$(this).find('.del-img').remove();

			return false;
		}
	});

	$.extend({
		wpjam_autocomplete_query: function(){
			$('input.wpjam-query-id').autocomplete({
				minLength:	0,
				source: 	function(request, response){
					var args = {
						action:		'wpjam-query',
						data_type:	this.element.data('data_type')
					};

					search_term	= request.term;

					if(args.data_type == 'post_type'){
						args.post_type	= this.element.data('post_type');
						if(search_term){
							args.s		= search_term;
						}
					}else if(args.data_type == 'taxonomy'){
						args.taxonomy	= this.element.data('taxonomy');
						if(search_term){
							args.search	= search_term;
						}
					}
					
					$.post(ajaxurl, args, function(data, status){
						if(args.data_type == 'post_type'){
							response($.map(data.posts, function(post){
								return {label:post.title, value:post.id};
							}));
						}else if(args.data_type == 'taxonomy'){
							response($.map(data.terms, function(term){
								return {label:term.name, value:term.id};
							}));
						}
					});
				},
				select: function(event, ui){
					$(this).next('span').fadeIn(300).html('<span class="dashicons dashicons-dismiss"></span>'+ui.item.label).css('display','inline-block');
					$(this).hide();
				}
			}).focus(function(){
				if(this.value == ''){
					$(this).autocomplete('search');
				}
			});
		},

		wpjam_form_init: function(){
			// 拖动排序
			$('.mu-fields').sortable({
				handle: '.dashicons-menu',
				cursor: 'move'
			});

			$('.mu-images').sortable({
				handle: '.dashicons-menu',
				cursor: 'move'
			});

			$('.mu-files').sortable({
				handle: '.dashicons-menu',
				cursor: 'move'
			});

			$('.mu-texts').sortable({
				handle: '.dashicons-menu',
				cursor: 'move'
			});

			$('.mu-imgs').sortable({
				cursor: 'move'
			});

			$('.tabs').tabs({
				activate: function(event, ui){
					$('.ui-corner-top a').removeClass('nav-tab-active');
					$('.ui-tabs-active a').addClass('nav-tab-active');

					var tab_href = window.location.origin + window.location.pathname + window.location.search +ui.newTab.find('a').attr('href');
					window.history.replaceState(null, null, tab_href);
					$('input[name="_wp_http_referer"]').val(tab_href);
				},
				create: function(event, ui){
					if(ui.tab.find('a').length){
						ui.tab.find('a').addClass('nav-tab-active');
						$('input[name="_wp_http_referer"]').val($('input[name="_wp_http_referer"]').val()+ui.tab.find('a').attr('href'));
					}
				}
			});
			
			$.wpjam_autocomplete_query();

			// $('.sortable').disableSelection();

			$('input.color').wpColorPicker();
			// $('.type-date').datepicker();

			$('select#page_key').change();
		}
	});

	$('body').on('change', 'select#page_key', function(){
		var page_key	= $(this).val();
		var hide_backup	= false;

		if(typeof page_key_fields == 'undefined'){
			page_key_fields	= {};
		}

		if($('select#page_key_backup').length){
			if($(this).find(':selected').data('shared')){
				$('select#page_key_backup').val($('select#page_key_backup option:first').val());
				$('#tr_page_key_backup_fieldset').hide();
			}else{
				$('#tr_page_key_backup_fieldset').show();
			}

			$('select#page_key_backup').change();
		}
		
		$('#tr_page_key_fieldset :input').each(function(){
			if($(this).data('page_key')){
				var item_id	= $(this).attr('id');
				var item	= $('#div_'+item_id);
				
				$.each($(this).data('page_key').split(','), function(index, key){
					if(!page_key_fields[key]){
						page_key_fields[key] = {}
					}

					page_key_fields[key][item_id]	= item;
				});

				$('#div_'+$(this).attr('id')).remove();
			}
		});

		if(!$.isEmptyObject(page_key_fields) && page_key_fields[page_key]){
			$.each(Object.keys(page_key_fields[page_key]).reverse(), function(index, key){
				$('#div_page_key').after(page_key_fields[page_key][key]);
			});

			$.wpjam_autocomplete_query();

			tb_position();
		}
	});

	$('body').on('change', 'select#page_key_backup', function(){
		var page_key_backup	= $(this).val();

		if(typeof module_page_item == 'undefined'){
			module_page_item	= $('#div_module_page_id_backup');
		}
		
		$('#tr_page_key_backup_fieldset :input').each(function(){
			if($(this).data('page_key_backup')){
				$('#div_'+$(this).attr('id')).remove();
			}
		});

		if($(this).val() == 'module_page'){
			$('#div_page_key_backup').after(module_page_item);
		}
	});

	$.wpjam_form_init();

	$('body').on('list_table_action_success', function(event, response){
		$.wpjam_form_init();
	});

	$('body').on('page_action_success', function(event, response){
		$.wpjam_form_init();
	});

	$('body').on('option_action_success', function(event, response){
		$.wpjam_form_init();
	});

	//  重新设置
	$('body').on('click', 'span.wpjam-query-title span.dashicons', function(){
		$(this).parent().prev('input').fadeIn(300).val('');
		$(this).parent().hide();
		return false;
	});

	var del_item = '<a href="javascript:;" class="button del-item">删除</a> <span class="dashicons dashicons-menu"></span>';

	var custom_uploader;
	if (custom_uploader) {
		custom_uploader.open();
		return;
	}

	$('body').on('click', '.wpjam-file', function(e) {	
		e.preventDefault();	// 阻止事件默认行为。

		var prev_input	= $(this).prev('input');
		var item_type	= $(this).data('item_type');
		var title		= (item_type == 'image')?'选择图片':'选择文件';

		custom_uploader = wp.media({
			title:		title,
			library:	{ type: item_type },
			button:		{ text: title },
			multiple:	false 
		}).on('select', function() {
			var attachment = custom_uploader.state().get('selection').first().toJSON();
			prev_input.val(attachment.url);
			$('.media-modal-close').trigger('click');
		}).open();

		return false;
	});

	//上传单个图片
	$('body').on('click', '.wpjam-img', function(e) {	
		e.preventDefault();	// 阻止事件默认行为。

		var img_wrap	= $(this);

		if(wp.media.view.settings.post.id){
			custom_uploader = wp.media({
				title:		'选择图片',
				library:	{ type: 'image' },
				button:		{ text: '选择图片' },
				frame:		'post',
				multiple:	false 
			// }).on('select', function() {
			}).on('open',function(){
				$('.media-frame').addClass('hide-menu');
			}).on('insert', function() {
				img_wrap.wpjam_add_attachment(custom_uploader.state().get('selection').first().toJSON());
				
				$('.media-modal-close').trigger('click');
			}).open();
		}else{
			custom_uploader = wp.media({
				title:		'选择图片',
				library:	{ type: 'image' },
				button:		{ text: '选择图片' },
				multiple:	false 
			}).on('select', function() {
				img_wrap.wpjam_add_attachment(custom_uploader.state().get('selection').first().toJSON());
				
				$('.media-modal-close').trigger('click');
			}).open();
		}

		return false;
	});

	//上传多个图片或者文件
	$('body').on('click', '.wpjam-mu-file', function(e) {
		e.preventDefault();	// 阻止事件默认行为。

		var render		= wp.template('wpjam-mu-file');
		var prev_input	= $(this).prev('input');
		var item_type	= $(this).data('item_type');
		var title		= (item_type == 'image')?'选择图片':'选择文件';
		
		custom_uploader = wp.media({
			title:		title,
			library:	{ type: item_type },
			button:		{ text: title },
			multiple:	true
		}).on('select', function() {
			custom_uploader.state().get('selection').map( function( attachment ) {
				attachment	= attachment.toJSON();
				
				prev_input.parent().before(render({
					img_url	: attachment.url,
					input_name	: prev_input.attr('name'),
					input_id	: prev_input.attr('id'),
				}));
			});
			$('.media-modal-close').trigger('click');
		}).open();

		prev_input.focus();

		return false;
	});

	//上传多个图片
	$('body').on('click', '.wpjam-mu-img', function(e) {
		e.preventDefault();	// 阻止事件默认行为。

		var max_items	= parseInt($(this).data('max_items'));

		if(max_items){
			if($(this).parent().parent().find('div.mu-item').length >= max_items){
				alert('最多'+max_items+'个');
				return false;
			}
		}

		var mu_img_wrap	= $(this);

		if(wp.media.view.settings.post.id){
			custom_uploader = wp.media({
				title:		'选择图片',
				library:	{ type: 'image' },
				button:		{ text: '选择图片' },
				frame:		'post',
				multiple:	true
			// }).on('select', function() {
			}).on('open',function(){
				$('.media-frame').addClass('hide-menu');
			}).on('insert', function() {
				custom_uploader.state().get('selection').map( function( attachment ) {
					mu_img_wrap.wpjam_add_mu_attachment(attachment.toJSON());
				});

				$('.media-modal-close').trigger('click');
			}).open();
		}else{
			custom_uploader = wp.media({
				title:		'选择图片',
				library:	{ type: 'image' },
				button:		{ text: '选择图片' },
				multiple:	true
			}).on('select', function() {
				custom_uploader.state().get('selection').map( function( attachment ) {
					mu_img_wrap.wpjam_add_mu_attachment(attachment.toJSON());
				});

				$('.media-modal-close').trigger('click');
			}).open();
		}

		return false;
	});

	//  删除选项
	$('body').on('click', '.del-img', function(){
		return $(this).parent().wpjam_remove_attachemnt();
	});

	// 添加多个选项
	$('body').on('click', 'a.wpjam-mu-text', function(){
		var max_items	= parseInt($(this).data('max_items'));

		if(max_items){
			if($(this).parent().parent().find('div.mu-item').length >= max_items){
				alert('最多'+max_items+'个');
				return false;
			}
		}

		var i		= $(this).data('i');
		var item	= $(this).parent().clone();

		i	= i+1;

		item.insertAfter($(this).parent());
		item.find('input').attr('id', $(this).data('key')+'_'+i).val('').show();
		item.find('span.wpjam-query-title').hide();
		item.find('a.wpjam-mu-text').data('i', i);

		$(this).parent().append(del_item);
		$(this).remove();

		$.wpjam_autocomplete_query();

		return false;
	});

	$('body').on('click', 'a.wpjam-mu-fields', function(){
		var max_items	= parseInt($(this).data('max_items'));

		if(max_items){
			if($(this).parent().parent().find(' > div.mu-item').length >= max_items){
				alert('最多'+max_items+'个');
				return false;
			}
		}

		var i		= $(this).data('i');
		var render	= wp.template($(this).data('tmpl-id'));

		i	= i+1;

		$(this).parent().after(render({i:i}));
		$(this).parent().append(del_item);
		$(this).parent().parent().trigger('mu_fields_added', i);
		$(this).remove();

		$.wpjam_autocomplete_query();

		return false;
	});

	//  删除选项
	$('body').on('click', '.del-item', function(){
		var next_input	= $(this).parent().next('input');
		if(next_input.length > 0){
			next_input.val('');
		}

		$(this).parent().fadeOut(300, function(){
			$(this).remove();
		});

		return false;
	});
});

if (self != top) {
	document.getElementsByTagName('html')[0].className += ' TB_iframe';
}

function isset(obj){
	if(typeof(obj) != 'undefined' && obj !== null) {
		return true;
	}else{
		return false;
	}
}