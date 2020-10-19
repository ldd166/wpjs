<?php
class Mine_Video{
	protected $action;
	public function __construct(){
		add_action('admin_menu',			array($this, 'minevideo_admin_menu'));//admin menu
		add_action('admin_init',			array($this, 'minevideo_admin_style'));// admin style
		add_action('wp_enqueue_scripts',	array($this, 'minevideo_scripts'));
		add_shortcode('mine_video',			array($this, 'minevideo_shortcode'));//register shortcode
		add_filter("mce_external_plugins",	array($this, "add_minevideo_tinymce_plugin"), 9999);
		add_filter('mce_buttons',			array($this, 'register_minevideo_button'), 9999);
		add_filter('plugin_action_links',	array($this, 'add_minevideo_settings_link'), 10, 2);
		$this->action = isset($_GET['action'])?sanitize_text_field($_GET['action']):'';
		if($this->action  == 'win'){
			$this->minevideo_admin_win();
		}
	}

	function mine_wp_head() {
		echo '<meta name="referrer" content="never">';
	}
	
	public function minevideo_admin_menu() {
		add_menu_page('Mine视频播放', 'Mine视频播放', 'manage_options', 'mine_video', array($this, 'minevideo_options'), MINEVIDEO_URL.'/images/minevideo.png');
	}

	public function minevideo_admin_style(){
		wp_enqueue_style('mine_setting_layui',  MINEVIDEO_URL.'/js/layui/css/layui.css');
		wp_enqueue_script('mine_setting_layuijs', MINEVIDEO_URL.'/js/layui/layui.js');
		wp_add_inline_script('mine_setting_layuijs','layui.use([\'form\', \'element\'], function(){var $ = layui.jquery,element = layui.element,form = layui.form;});');
	}

	public function add_minevideo_tinymce_plugin($plugins) {
		$plugins['minevideo'] = MINEVIDEO_URL.'/js/editor_plugin.js';
		return $plugins;
	}

	public function register_minevideo_button($buttons) {
		array_push($buttons, "separator", "minevideo");
		return $buttons;
	}

	public function add_minevideo_settings_link($links, $file) {
		if (strpos($file, 'mine-video') !== false && is_plugin_active($file)){
			$settings_link = '<a href="'.wp_nonce_url("admin.php?page=mine_video").'">Settings</a>';
			array_unshift($links, $settings_link);
		}
		return $links;
	}
	
	public function minevideo_options() {
		if (!current_user_can('manage_options'))  {
			wp_die(__('您没有操作权限！'));
		}
		if(isset($_POST['mine_video_player_height'])) {
			$mine_video_player_jxapi =		sanitize_text_field($_POST['mine_video_player_jxapi']);
			$mine_video_player_from =		sanitize_textarea_field($_POST['mine_video_player_from']);
			$mine_video_player_height =		sanitize_text_field($_POST['mine_video_player_height']);
			$mine_video_player_height_m =	sanitize_text_field($_POST['mine_video_player_height_m']);
			$mine_video_playertop =			sanitize_text_field($_POST['mine_video_playertop']);
			$mine_video_dplayer_config =	sanitize_textarea_field(stripslashes($_POST['mine_video_dplayer_config']));

			update_option('mine_video_player_jxapi', $mine_video_player_jxapi);
			update_option('mine_video_player_from', $mine_video_player_from);
			update_option('mine_video_player_height', $mine_video_player_height);
			update_option('mine_video_player_height_m', $mine_video_player_height_m);
			update_option('mine_video_playertop', $mine_video_playertop);
			update_option('mine_video_dplayer_config', $mine_video_dplayer_config);
	?>
	<div class="updated"><p><strong>保存成功！</strong></p></div>
	<?php
		}
		echo '<div class="wrap">';
		echo "<h2>Mine视频播放</h2>";
	?>
	<form name="form1" method="post" class="layui-form" action="">
	<div class="layui-tab layui-tab-brief" lay-filter="docDemoTabBrief">
	  <ul class="layui-tab-title">
		<li class="layui-this">插件设置</li>
	  </ul>
	  <div class="layui-tab-content">
		<div class="layui-tab-item layui-show">
			<div class="layui-form-item">
				<label class="layui-form-label">通用接口</label>
				<div class="layui-input-block">
					<input type="text" name="mine_video_player_jxapi" value="<?php echo get_option('mine_video_player_jxapi');?>"  class="layui-input">
				</div>
			</div>
			<div class="layui-form-item layui-form-text">
				<label class="layui-form-label">播放来源</label>
				<div class="layui-input-block">
					<textarea placeholder="请输入内容" class="layui-textarea" name="mine_video_player_from" style="min-height:200px;"><?php echo get_option('mine_video_player_from');?></textarea>
				</div>
			</div>
			<div class="layui-form-item">
				<div class="layui-inline">
					<label class="layui-form-label">PC高度</label>
					<div class="layui-input-inline">
						<input type="tel" name="mine_video_player_height" autocomplete="off" class="layui-input" value="<?php echo get_option('mine_video_player_height');?>">
					</div>
				</div>
				<div class="layui-inline">
					<label class="layui-form-label">手机高度</label>
					<div class="layui-input-inline">
						<input type="text" name="mine_video_player_height_m" autocomplete="off" class="layui-input" value="<?php echo get_option('mine_video_player_height_m');?>">
					</div>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">头部信息</label>
				<div class="layui-input-block">
					<input type="radio" name="mine_video_playertop" value="show" title="显示" <?php if(get_option('mine_video_playertop')=='show')echo 'checked=""';?>>
					<input type="radio" name="mine_video_playertop" value="hide" title="隐藏" <?php if(get_option('mine_video_playertop')=='hide')echo 'checked=""';?>>
				</div>
			</div>
			<div class="layui-form-item layui-form-text">
				<label class="layui-form-label">DPlayer配置</label>
				<div class="layui-input-block">
					<textarea placeholder="请输入内容" class="layui-textarea" name="mine_video_dplayer_config" style="min-height:200px;"><?php echo get_option('mine_video_dplayer_config');?></textarea>
				</div>
			</div>
		</div>
	  </div>
	</div> 

	<hr class="layui-bg-green">

	<div class="layui-form-item">
		<div class="layui-input-block">
		<button type="submit" class="layui-btn"><?php esc_attr_e('Save Changes') ?></button>
	</div>

	</form>
	</div>
	<?php
	}

	public function register_minevideo_init() {
		if(!get_option('mine_video_player_jxapi'))update_option('mine_video_player_jxapi','https://vip.52jiexi.top/?url={vid}');
		if(!get_option('mine_video_player_from'))update_option('mine_video_player_from','youku==优酷==https://vip.52jiexi.top/?url={vid}
iqiyi==爱奇异
qq==腾讯
sohu==搜狐
mgtv==芒果
weibo==微博==http://minevideo.sxl.me/api.php?url={vid}
m3u8==M3U8/Mp4==dplayer
iframe==IFrame==self
live==直播==dplayer_live');
		if(!get_option('mine_video_player_height'))update_option('mine_video_player_height','500');
		if(!get_option('mine_video_player_height_m'))update_option('mine_video_player_height_m','300');
		if(!get_option('mine_video_playertop'))update_option('mine_video_playertop','hide');
		if(!get_option('mine_video_dplayer_config') || get_option('mine_video_dplayer_config')=='')update_option('mine_video_dplayer_config','{
	"autoplay":"false"
	,"theme":"#b7daff"
	,"logo":"'.MINEVIDEO_URL.'/images/logo.png"
	,"loop":"true"
	,"lang":"zh-cn"
	,"hotkey":"true"
	,"preload":"auto"
	,"volume":"0.7"
	,"contextmenu":"[{text: \'custom link\',link: \'https://www.zwtt8.com/\'},{text: \'mine video player\',link: \'https://www.zwtt8.com/\'}]"
	,"mutex":"true"
}');
	}

	public function minevideo_shortcode($atts, $content=null){
		extract(shortcode_atts(array("type"=>'common'),$atts));

		$url = $content ? $content : ($atts['vid'] ? $atts['vid'] : '');
		if(!$url) return '视频ID/URL不能为空';
		if(wp_is_mobile()){
			$h = $atts['height_wap'] ? $atts['height_wap'] : (get_option('mine_video_player_height_m') ? get_option('mine_video_player_height_m') : '300');
		}
		else{
			$h = $atts['height'] ? $atts['height'] : (get_option('mine_video_player_height') ? get_option('mine_video_player_height') : '500');
		}
		$mine_video_player_jxapi = get_option('mine_video_player_jxapi') ? get_option('mine_video_player_jxapi') : '';
		
		$mine_video_playlist_position = get_option('mine_video_playlist_position') ? get_option('mine_video_playlist_position') : 'bottom';
		$mine_video_playertop = get_option('mine_video_playertop') ? get_option('mine_video_playertop') : 'show';
		$parr = $this->minevideo_get_players();
		$typearr = explode('^', $type);
		$type = $typearr[0];
		$typestr = '';
		$urlarr = explode('^', $url);
		$vlistarr = array();
		$vliststr = '';
		$jxapistr = '';
		$r = rand(1000,99999);
		$typelen = count($typearr);
		$vgshoworhide = '';
		$mine_dplayerconfig = '';
		for($ti=0;$ti<$typelen;$ti++){
			if($ti == 0){
				$typestr .= '<li class="layui-this">'.$parr[$typearr[$ti]].'</li>';
				$vliststr .= '<div class="layui-tab-item layui-show"><div id="MineBottomList_'.$typearr[$ti].'_'.$r.'" class="MineBottomList"><ul class="result_album" id="result_album_'.$typearr[$ti].'_'.$r.'">';
			}else{
				$typestr .= '<li>'.$parr[$typearr[$ti]].'</li>';
				$vliststr .= '<div class="layui-tab-item"><div id="MineBottomList_'.$typearr[$ti].'_'.$r.'" class="MineBottomList"><ul class="result_album" id="result_album_'.$typearr[$ti].'_'.$r.'">';
			}
			$vidgroup = explode(',', $urlarr[$ti]);
			$vidlen = count($vidgroup);
			if($typelen == 1 && $vidlen == 1) $vgshoworhide = 'display:none;';
			$jxapi_cur = trim($parr[$typearr[$ti].'_api']?$parr[$typearr[$ti].'_api']:$mine_video_player_jxapi);
			if($jxapi_cur == 'self'){
					$jxapi_cur = '{vid}';
			}
			
			for($vi=0;$vi<$vidlen;$vi++){
				$vidtemp = explode('$', $vidgroup[$vi]);
				if(!$vidtemp[1]){
					$vidtemp[1]=$vidtemp[0];
					$vidtemp[0]='第'.(intval($vi+0)<9?'0':'') . ($vi+1).'集';
				}
				$vlid = $vi;
				if(isset($vlistarr[$typearr[$ti]]) && count($vlistarr[$typearr[$ti]])>$vi){
					$vlid = count($vlistarr[$typearr[$ti]]);
				}
				$vlistarr[$typearr[$ti]][] = array('id'=>$vlid, 'pre'=>$vidtemp[0],'video'=>$vidtemp[1]);
				$vliststr .= '<li><a href="javascript:void(0)" onclick="MP_'.$r.'.Go('.$vlid.', \''.$typearr[$ti].'\');return false;">'.$vidtemp[0].'</a></li>';
			}
			$vliststr .= '</ul></div></div>';
			switch($jxapi_cur){
				case 'dplayer':
				case 'dplayer_live':
					$this->minevideo_dplayer_scripts();
					$jxapistr .= '<input type="hidden" id="mine_ifr_'.$typearr[$ti].'_'.$r.'" value=\''.$jxapi_cur.'\'/>';
					if(!get_option('mine_video_dplayer_config') || get_option('mine_video_dplayer_config')=='')$this->register_minevideo_init();
					$mine_video_dplayer_config = json_decode(get_option('mine_video_dplayer_config'), true);
					foreach($mine_video_dplayer_config as $k=>$v){
						if(in_array($k, array('autoplay','loop','screenshot','hotkey','contextmenu','mutex')))
							$mine_dplayerconfig .= $k.':'.$v.',';
						else
							$mine_dplayerconfig .= $k.':\''.$v.'\',';
					}
					break;
				default:
					$jxapistr .= '<input type="hidden" id="mine_ifr_'.$typearr[$ti].'_'.$r.'" value=\'<i'.'fr'.'ame border="0" src="'.$jxapi_cur.'" width="100%" height="'.$h.'" marginwidth="0" framespacing="0" marginheight="0" frameborder="0" scrolling="no" vspale="0" noresize="" allowfullscreen="true" id="minewindow_'.$typearr[$ti].'_'.$r.'"></'.'if'.'rame>\'/>';
			}
		}
		
		
		wp_enqueue_script('mine_video_player', MINEVIDEO_URL.'/js/mineplayer.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
		wp_add_inline_script('mine_video_player', 'var mine_dplayerconfig = {'.$mine_dplayerconfig.'};var mine_di_'.$r.'="第",mine_ji_'.$r.'="集",mine_playing_'.$r.'="正在播放 ";var minevideo_type_'.$r.'="'.$type.'";var minevideo_vids_'.$r.'='.json_encode($vlistarr).';var MP_'.$r.' = new MinePlayer('.$r.');MP_'.$r.'.Go(0);');
		$player = '<div id="MinePlayer_'.$r.'" class="MinePlayer"><table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody><tr'.($mine_video_playertop=='show'?'':' style="display:none;"').'><td height="26"><table border="0" cellpadding="0" cellspacing="0" id="playtop_'.$r.'" class="playtop"><tbody><tr><td id="topleft"><a target="_self" href="javascript:void(0)" onclick="MP_'.$r.'.GoPreUrl();return false;">上一集</a> <a target="_self" href="javascript:void(0)" onclick="MP_'.$r.'.GoNextUrl();return false;">下一集</a></td><td id="topcc"><div id="topdes_'.$r.'" class="topdes">正在播放</div></td><td id="topright_'.$r.'" class="topright"></td></tr></tbody></table></td></tr><tr><td><table border="0" cellpadding="0" cellspacing="0"><tbody><tr><td id="playleft_'.$r.'" class="playleft" valign="top" style="height:'.$h.'px;"></td><td id="playright_'.$r.'" valign="top"></td></tr></tbody></table></td></tr></tbody></table></div>'.$jxapistr.'<div class="layui-tab layui-tab-brief" lay-filter="videoGroup" style="margin:10px auto;'.$vgshoworhide.'"><ul class="layui-tab-title">'.$typestr.'</ul><div class="layui-tab-content" style="height: auto;padding-left:0;">'.$vliststr.'</div></div>';
		
		return $player;
	}
	public function minevideo_scripts(){
		global $posts;
		foreach($posts as $post){
			if(has_shortcode($post->post_content, 'mine_video')){
				add_action('wp_head',				array($this, 'mine_wp_head'));
				wp_enqueue_style( 'mine_video_layuicss', MINEVIDEO_URL.'/js/layui/css/layui.css',  array(), MINEVIDEO_VERSION);
				wp_enqueue_style( 'mine_video_css', MINEVIDEO_URL.'/css/minevideo.css',  array(), MINEVIDEO_VERSION);
				wp_enqueue_style('mine_dplayer_css', MINEVIDEO_URL.'/dplayer/CBPlayer.min.css', MINEVIDEO_URL, false);
				wp_enqueue_script('mine_video_layuijs', MINEVIDEO_URL.'/js/layui/layui.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
				wp_add_inline_script('mine_video_layuijs', 'layui.use(\'element\', function(){var $ = layui.jquery,element = layui.element;$(".layui-tab-content a").click(function(){$(".layui-tab-content a").removeClass("list_on");$(this).addClass("list_on");});});');
				break;
			}
		}
	}
	public function minevideo_dplayer_scripts(){
		wp_enqueue_script('mine_dplayer_p2p-engine', MINEVIDEO_URL.'/dplayer/hlsjs-p2p-engine.min.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
		wp_enqueue_script('mine_dplayer_hls', MINEVIDEO_URL.'/dplayer/hls.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
		wp_enqueue_script('mine_dplayer_2', MINEVIDEO_URL.'/dplayer/cbplayer2@latest.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
	}
	public function minevideo_get_players(){
		$players = get_option('mine_video_player_from');
		$players = explode("\n", $players);
		$arr = array();
		foreach($players as $p){
			if($p){
				$tmp = explode('==', $p);
				if(count($tmp)>=2){
					$tmp[0] = trim($tmp[0]);
					$tmp[1] = trim($tmp[1]);
					$arr[$tmp[0]] = $tmp[1];
					$arr[$tmp[0].'_api'] = isset($tmp[2])?trim($tmp[2]):'';
				}
			}
		}
		return $arr;
	}

	public function minevideo_admin_win(){
		$mine_video_player_from = $this->minevideo_get_players();
		$players_str = '';
		foreach($mine_video_player_from as $k=>$p){
			if(strlen($k)==strlen(rtrim($k, '_api')))
				$players_str .= '<option value="'.$k.'">'.$p.'</option>';
		}
	?><html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>添加视频</title>
	<link rel='stylesheet' href='<?php echo MINEVIDEO_URL.'/js/layui/css/layui.css';?>' media='all' />
	<script type='text/javascript' src='<?php echo MINEVIDEO_URL.'/js/tinymce.js?ver='.$GLOBALS['wp_version'];?>'></script>
	<script type='text/javascript' src='<?php echo get_option('siteurl').'/wp-includes/js/tinymce/tiny_mce_popup.js?ver='.$GLOBALS['wp_version'];?>'></script>
	<script type='text/javascript' src='<?php echo MINEVIDEO_URL.'/js/layui/layui.js?ver='.$GLOBALS['wp_version'];?>'></script>
	<base target="_self" />
</head>
<body id="link" onload="tinyMCEPopup.executeOnLoad('init();');" >
<div class="layui-tab layui-tab-brief" lay-filter="videoGroup" style="margin:0 auto;" lay-allowclose="true">
  <button class="layui-btn" id="addPlayer" style="margin-top: 50px;position: absolute;right: 12px;">新增一组</button>
  <ul class="layui-tab-title minevideo-video-from">
    <li lay-id="1" class="layui-this" lay-allowclose="false">来源1</li>
  </ul>
  <div class="layui-tab-content layui-form">
    <div class="layui-tab-item layui-show">
		<div class="layui-form-item">
			<label class="layui-form-label">播放来源</label>
			<div class="layui-inline">
				<select id="mvtype1" name="mvtype" fwin="winbox">
				<?php echo $players_str;?>
				</select>
			</div>
		</div>
		<div class="layui-form-item">
			<label class="layui-form-label">视频ID/URL<button class="layui-btn layui-btn-xs" onclick="checkMineVideo(1)" >校正</button></label>
			<div class="layui-input-block">
				<textarea type="text" name="mvurl" id="mvurl1" placeholder="请填写视频ID/URL 一行一条数据" class="layui-textarea" style="min-height:160px;"></textarea>
			</div>
		</div>
	</div>
  </div>
</div>
<div class="layui-form">
		<div class="layui-form-item">
			<label class="layui-form-label">默认参数</label>
			<div class="layui-input-block">
				 <input type="radio" name="defaultpara" id="defaultpara1" onclick="isDefaultPara(1);" value="1" title="是" lay-filter="defaultpara" checked="checked" >	
				 <input type="radio" onclick="isDefaultPara(0);" name="defaultpara" id="defaultpara0" value="0" title="否" lay-filter="defaultpara">
			</div>
		</div>
		<div class="layui-form-item minedisplay" style="display:none;">
			<label class="layui-form-label">PC高度</label>
			<div class="layui-input-block">
				<input type="text" name="mvheight" id="mvheight" value="500" placeholder="默认为500" size="20" class="layui-input"  value="<?php $mvh = get_option('mine_video_player_height'); echo empty($mvh)?'300':$mvh;?>">
			</div>
		</div>
		<div class="layui-form-item minedisplay" style="display:none;">
			<label class="layui-form-label">手机高度</label>
			<div class="layui-input-block">
				<input type="text" name="mvmheight" id="mvmheight" value="320" placeholder="默认为300" size="20" class="layui-input"  value="<?php $mvmh = get_option('mine_video_player_height_m'); echo empty($mvmh)?'300':$mvmh;?>">
			</div>
		</div>
		<div class="layui-form-item">
			<div class="layui-input-block">
				<button class="layui-btn" lay-submit="" lay-filter="formDemo" id="e_minevideopro_btn_charu">添加视频</button>
			</div>
		</div>
		<hr class="layui-bg-green">
</div>
<?php 
echo '<script>
layui.use([\'form\',\'element\'], function(){
	var $ = layui.jquery
	,element = layui.element
	,form = layui.form;

	var tabid = $(\'.minevideo-video-from li\').length+1;
	$(\'#addPlayer\').click(function(){
		element.tabAdd(\'videoGroup\', {
			title: \'来源\'+ tabid
			,content: \'<div class="layui-form-item"><label class="layui-form-label">播放来源\'+tabid+\'<\/label><div class="layui-inline"><select id="mvtype\'+tabid+\'" name="mvtype" fwin="winbox">'.$players_str.'<\/select><\/div><\/div><div class="layui-form-item"><label class="layui-form-label">视频ID|URL<button class="layui-btn layui-btn-xs" onclick="checkMineVideo(\'+tabid+\')" >校正<\/button><\/label><div class="layui-input-block"><textarea type="text" name="mvurl" id="mvurl\'+tabid+\'" placeholder="请填写视频ID|URL 一行一条数据" class="layui-textarea" style="min-height:160px;"><\/textarea><\/div><\/div>\'
			,id: tabid
		});
		element.tabChange(\'videoGroup\', tabid);
		tabid++;
		form.render();
		$(\'.minevideo-video-from li[lay-id=1]\').children().remove();
	});
	$(\'#e_minevideopro_btn_charu\').click(function(){
		var mvurl = \' vid="\';
		var mvheight = " height=\"" + $("#mvheight").val()+"\"";
		var mvmheight = " height_wap=\"" + $("#mvmheight").val()+"\"";
		var mvtype = \' type="\';
		for(var tid=1;tid<tabid;tid++){
			if($("#mvurl"+tid).val().replace(/\r|\n/g,\',\').length>0){
				mvurl += $("#mvurl"+tid).val().replace(/\r|\n/g,\',\')+\'^\';
				mvtype += $("#mvtype"+tid).val()+\'^\';
			}
		}
		mvurl=mvurl.substring(0,mvurl.length-1)+\'"\';
		mvtype=mvtype.substring(0,mvtype.length-1)+\'"\';
		var para = \'\';
		if(document.getElementById("defaultpara0").checked){
			 para =  mvheight + mvmheight;
		}
		var shortcode = "" ;
		shortcode = shortcode+"[mine_video "+ mvtype + mvurl + para + "][/mine_video]";
		tinyMCE.activeEditor.insertContent(shortcode);
		//tinyMCEPopup.editor.execCommand(\'mceRepaint\');
		tinyMCEPopup.close();
		return;
	});
	form.on(\'radio(defaultpara)\', function (data) {
		if(data.value==\'1\'){
			$(\'.minedisplay\').hide();
		}
		else{
			$(\'.minedisplay\').show();
		}
	});
	$(\'.minevideo-video-from li[lay-id=1]\').children().remove();
	$(\'.minevideo-video-from li[lay-id=1]\').on(\'DOMNodeInserted\',function(){
        $(\'.minevideo-video-from li[lay-id=1]\').children().remove();
    });
});</script>';
	
?>
</body>
</html>
	<?php
	exit;
	}
}