<?php
add_filter('wpjam_basic_setting', function($sections){
	$fields = [
		'toc_depth'		=> ['title'=>'显示到第几级',	'type'=>'select',	'value'=>6,	'options'=>['1'=>'h1','2'=>'h2','3'=>'h3','4'=>'h4','5'=>'h5','6'=>'h6']],
    	'toc_individual'=> ['title'=>'目录单独设置',	'type'=>'checkbox',	'value'=>1,	'description'=>'在每篇文章编辑页面单独设置是否显示文章目录以及显示到第几级。'],
    	'toc_position'	=> ['title'=>'目录显示位置',	'type'=>'select',	'value'=>'content',	'options'=>['content'=>'显示在文章内容前面','function'=>'调用函数wpjam_get_toc()显示']],
		'toc_auto'		=> ['title'=>'脚本自动插入',	'type'=>'checkbox', 'value'=>1,	'description'=>'自动插入文章目录的 JavaScript 和 CSS 代码，请点击这里获取<a href="https://blog.wpjam.com/m/toc-js-css-code/" target="_blank">文章目录的默认 JS 和 CSS</a>。'],
		'toc_script'	=> ['title'=>'JS代码',		'type'=>'textarea',	'description'=>'如果你没有选择自动插入脚本，可以将下面的 JavaScript 代码复制你主题的 JavaScript 文件中。'],
		'toc_css'		=> ['title'=>'CSS代码',		'type'=>'textarea',	'description'=>'根据你的主题对下面的 CSS 代码做适当的修改。<br />如果你没有选择自动插入脚本，可以将下面的 CSS 代码复制你主题的 CSS 文件中。'],
    	'toc_copyright'	=> ['title'=>'版权信息',		'type'=>'checkbox', 'value'=>1,	'description'=>'在文章目录下面显示版权信息。']
	];

	$summary	= '文章目录扩展自动根据文章内容里的子标题提取出文章目录，并显示在内容前，详细介绍请点击：<a href="https://blog.wpjam.com/m/wpjam-toc/" target="_blank">文章目录扩展</a>。';

	return compact('fields', 'summary');
});

add_action('admin_head',function(){
	?>
	<script type="text/javascript">
	jQuery(function($){
		$('input#toc_auto').change(function(){
			$('tr#tr_toc_script').hide();
			$('tr#tr_toc_css').hide();

			if($(this).is(':checked')){
				$('tr#tr_toc_script').show();
				$('tr#tr_toc_css').show();
			}
		});

		$('input#toc_auto').change();
	});
	</script>
	<?php
});