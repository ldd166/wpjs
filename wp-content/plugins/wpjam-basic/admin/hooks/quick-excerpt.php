<?php
add_filter('wpjam_html_replace', function($html){
	$excerpt_inline_edit	= '
	<label>
		<span class="title">摘要</span>
		<span class="input-text-wrap"><textarea cols="22" rows="2" name="the_excerpt"></textarea></span>
	</label>
	';

	return str_replace('<fieldset class="inline-edit-date">', $excerpt_inline_edit.'<fieldset class="inline-edit-date">', $html);
});

add_action('add_inline_data', function($post){
	echo '<div class="post_excerpt">' . esc_textarea(trim($post->post_excerpt)) . '</div>';
});

add_filter('wp_insert_post_data', function($data, $postarr){
	if(isset($_POST['the_excerpt'])){
		$data['post_excerpt']   = $_POST['the_excerpt'];
	}
		
	return $data;
}, 10, 2);

add_action('admin_head', function(){
	?>
	<script type="text/javascript">
	jQuery(function($){
 
		var wp_inline_edit_function = inlineEditPost.edit;
		
		inlineEditPost.edit = function(id){
	
			wp_inline_edit_function.apply(this, arguments);

			if(typeof(id) === 'object'){
				id = this.getId(id);
			}

			if(id > 0){
				var excerpt		= $('#inline_'+id+' .post_excerpt').text();
				var edit_row	= $('#edit-' + id);
				$(':input[name="the_excerpt"]', edit_row).val(excerpt);
			}
		}
	});
	</script>
	<?php
});