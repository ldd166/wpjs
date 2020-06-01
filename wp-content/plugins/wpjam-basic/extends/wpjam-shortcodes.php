<?php
/*
Plugin Name: 常用短代码
Plugin URI: https://blog.wpjam.com/project/wpjam-basic/
Description: 添加 list table 等常用短代码，并在后台罗列所有系统所有短代码。 
Version: 1.0
*/

add_shortcode('hide', '__return_empty_string');

add_shortcode('list',  function($atts, $content='') {
	extract( shortcode_atts( array(
		'type' 	=> '',
		'class' => ''
	), $atts ) );

	$output = '';

	$content	= str_replace("\r\n", "\n", $content);
	$content	= str_replace("<br />\n", "\n", $content);
	$content	= str_replace("</p>\n", "\n", $content);
	$content	= str_replace("\n<p>", "\n", $content);

	$lists		= explode("\n", $content);

	foreach($lists as $li){
		$li = trim($li);
		if(empty($li)){
			continue;
		}

		$output .= "<li>".do_shortcode($li)."</li>\n";
	}

	$class	= $class?' class="'.$class.'"':'';

	if($type=="order" || $type=="ol"){
		return "<ol".$class.">\n".$output."</ol>\n";
	}else{
		return "<ul".$class.">\n".$output."</ul>\n";
	}
});

add_shortcode('table', function($atts, $content='') {
	extract( shortcode_atts( array(
		'border'		=> '0',
		'cellpading'	=> '0',
		'cellspacing'   => '0',
		'width'			=> '',
		'class'			=> '',
		'caption'		=> '',
		'th'			=> '0',  // 0-无，1-横向，2-纵向，4-横向并且有 footer 
	), $atts ) );

	$output		= $thead = $tbody = '';
	$content	= str_replace("\r\n", "\n", $content);
	$content	= str_replace("\r\n", "\n", $content);
	$content	= str_replace("<br />\n", "\n", $content);
	$content	= str_replace("</p>\n", "\n\n", $content);
	$content	= str_replace("\n<p>", "\n", $content);

	$trs		= explode("\n\n", $content);

	if($caption){
		$output	.= '<caption>'.$caption.'</caption>';
	}

	$tr_counter = 0;
	foreach($trs as $tr){
		$tr = trim($tr);
		if(empty($tr)) continue;
		
		$tds = explode("\n", $tr);
		if(($th == 1 || $th == 4) && $tr_counter == 0){
			foreach($tds as $td){
				$td = trim($td);
				if($td){
					$thead .= "\t\t\t".'<th>'.$td.'</th>'."\n";
				}
			}
			$thead = "\t\t".'<tr>'."\n".$thead."\t\t".'</tr>'."\n";
		}else{
			$tbody .= "\t\t".'<tr>'."\n";
			$td_counter = 0;
			foreach($tds as $td){
				$td = trim($td);
				if($td){
					if($th == 2 && $td_counter ==0){
						$tbody .= "\t\t\t".'<th>'.$td.'</th>'."\n";
					}else{
						$tbody .= "\t\t\t".'<td>'.$td.'</td>'."\n";
					}
					$td_counter++;
				}
			}
			$tbody .= "\t\t".'</tr>'."\n";
		}
		$tr_counter++;
	}

	if($th == 1 || $th == 4){ $output .=  "\t".'<thead>'."\n".$thead."\t".'</thead>'."\n"; }
	if($th == 4){ $output .=  "\t".'<tfoot>'."\n".$thead."\t".'</tfoot>'."\n"; }
	
	$output	.= "\t".'<tbody>'."\n".$tbody."\t".'</tbody>'."\n";
	
	$class	= $class?' class="'.$class.'"':'';
	$width	= $width?' width="'.$width.'"':'';

	return "\n".'<table border="'.$border.'" cellpading="'.$cellpading.'" cellspacing="'.$cellspacing.'" '.$width.' '.$class.' >'."\n".$output.'</table>'."\n";
});

add_shortcode('email', function($atts, $content='') {
	extract( shortcode_atts( array( 
		'mailto' => false
	), $atts ) );

	return antispambot( $content, $mailto );
});

add_shortcode('code',  function ( $atts, $content='' ) {
	$atts = shortcode_atts( array( 'type' => 'php' ), $atts );
	extract($atts);

	if($type == 'html') $type = 'markup';

	$content	= str_replace("<br />\n", "\n", $content);
	$content	= str_replace("</p>\n", "\n\n", $content);
	$content	= str_replace("\n<p>", "\n", $content);
	$content	= str_replace('&amp;', '&', esc_textarea($content)); // wptexturize 会再次转化 & => &#038;

	$content	= trim($content);
	
	if($type){
		return '<pre><code class="language-'.$type.'">'.$content.'</code></pre>';		
	}else{
		return '<pre>'.$content.'</pre>';
	}
});

add_shortcode('youku', function( $atts, $content='') {
	extract( shortcode_atts( array( 
		'width'		=> '510', 
		'height'	=> '498'
	), $atts ) );

	if(preg_match('#http://v.youku.com/v_show/id_(.*?).html#i',$content,$matches)){
		return '<iframe class="wpjam_video" height='.esc_attr($height).' width='.esc_attr($width).' src="http://player.youku.com/embed/'.esc_attr($matches[1]).'" frameborder=0 allowfullscreen></iframe>';
	}
});

add_shortcode('qqv',  function($atts, $content='') {
	extract( shortcode_atts( array( 
		'width'		=> '510', 
		'height'	=> '498'
	), $atts ) );

	if(preg_match('#//v.qq.com/iframe/player.html\?vid=(.+)#i',$content,$matches)){
		//var_dump($matches);exit();
		return '<iframe class="wpjam_video" height='.esc_attr($height).' width='.esc_attr($width).' src="http://v.qq.com/iframe/player.html?vid='.esc_attr($matches[1]).'" frameborder=0 allowfullscreen></iframe>';
	}elseif(preg_match('#//v.qq.com/iframe/preview.html\?vid=(.+)#i',$content,$matches)){
		//var_dump($matches);exit();
		return '<iframe class="wpjam_video" height='.esc_attr($height).' width='.esc_attr($width).' src="http://v.qq.com/iframe/player.html?vid='.esc_attr($matches[1]).'" frameborder=0 allowfullscreen></iframe>';
	}
});

add_shortcode('bilibili',  function($atts, $content='') {
	extract( shortcode_atts( array( 
		'width'		=> '510', 
		'height'	=> '498'
	), $atts ) );

	if(preg_match('#//www.bilibili.com/video/(.+)#i',$content, $matches)){
		return '<iframe class="wpjam_video" height='.esc_attr($height).' width='.esc_attr($width).' src="//player.bilibili.com/player.html?bvid='.esc_attr($matches[1]).'" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true"> </iframe>';
	}
});

add_shortcode('tudou', function($atts, $content=''){
	extract( shortcode_atts( array(
		'width'		=> '480', 
		'height'	=> '400'
	), $atts ) );

	if(preg_match('#http://www.tudou.com/programs/view/(.*?)#i',$content, $matches)){
		return '<iframe class="wpjam_video" width='. esc_attr($width) .' height='. esc_attr($height) .' src="http://www.tudou.com/programs/view/html5embed.action?code='. esc_attr($matches[1]) .'" frameborder=0 allowfullscreen></iframe>';
	}
});

add_shortcode('sohutv', function($atts, $content=''){
	extract( shortcode_atts( array( 
		'width'		=> '510', 
		'height'	=> '498'
	), $atts ) );

	if(preg_match('#http://tv.sohu.com/upload/static/share/share_play.html\#(.+)#i',$content,$matches)){
		//var_dump($matches);exit();
		return '<iframe class="wpjam_video" height='.esc_attr($height).' width='.esc_attr($width).' src="http://tv.sohu.com/upload/static/share/share_play.html#'.esc_attr($matches[1]).'" frameborder=0 allowfullscreen></iframe>';
	}
});