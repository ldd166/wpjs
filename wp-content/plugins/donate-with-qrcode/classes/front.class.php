<?php




class DWQR_Front {

    static $is_set_script_var = false;
	public function __construct() {

	    //是否开启
	    $switch = DWQR_Admin::opt('dwqr_switch', 1);

	    if($switch && !is_admin()){
            add_filter( 'the_content', array( $this, 'the_content' ), 100 );
            add_action( 'wp_head', array( $this, 'wp_head' ), 50 );
        }

        add_action('wp_ajax_dwqr_ajax',array($this,'dwqr_ajax_handler'));
        add_action('wp_ajax_nopriv_dwqr_ajax',array($this,'dwqr_ajax_handler'));

		add_shortcode( 'wb_share_social', array($this,'wb_share_social_handler'));
	}

	public function dwqr_ajax_handler(){

		switch ($_POST['do']){


			case 'like':
				$post_id = intval($_REQUEST['post_id']);

				if(!$post_id){
					break;
				}
				$like = get_post_meta($post_id,'dwqr_like',true);
				if($like){
					$like = intval($like);
				}else{
					$like = 0;
				}
				$like++;

				update_post_meta($post_id,'dwqr_like',$like);
				echo $like;

				break;

			case 'wb_share_poster':
				self::wb_share_post_handler();
				break;

		}
		exit();
    }

    function wp_head(){
        if ( is_single() ) {
	        $ajax_url = admin_url('admin-ajax.php');
	        $curr_uid = wp_get_current_user()->ID;
	        $post_id = get_the_ID();

	        if (!function_exists('wbolt_header')) {
		        wp_enqueue_script('wbui-js', plugin_dir_url(DWQR_BASE_FILE) . 'assets/wbui/wbui.js', null, DWQR_VERSION,true);
		        wp_enqueue_style('wbui-css', plugin_dir_url(DWQR_BASE_FILE) . 'assets/wbui/assets/wbui.css', null, DWQR_VERSION);

	        }

	        if(!wp_script_is( 'jquery', 'enqueued' )){
		        wp_enqueue_script('jquery');
	        }

	        wp_enqueue_style('wbs-dwqr-css', plugin_dir_url(DWQR_BASE_FILE) . 'assets/wbp_donate.css', null, DWQR_VERSION);

            wp_enqueue_script('qrious-js', plugin_dir_url(DWQR_BASE_FILE) . 'assets/qrious.min.js', array('jquery'), DWQR_VERSION,true);
	        wp_enqueue_script('wbs-front-dwqr', plugin_dir_url(DWQR_BASE_FILE) . 'assets/wbp_front.js', array('jquery'), DWQR_VERSION,true);

	        $dwqr_opt = '';
	        $dwqr_opt .= 'var dwqr_opt="' . DWQR_VERSION. '|'. urlencode(plugin_dir_url(DWQR_BASE_FILE) ). '|'. $curr_uid .'|'. urlencode($ajax_url).'|'. $post_id .'";';

	        wp_add_inline_script('wbs-front-dwqr', $dwqr_opt ,'before');

        }
    }

	public function the_content( $content ) {

		if (is_single()) {
			$content .= $this->donateHtml();
		}
		return $content;
	}

	private function donateHtml( $attr = array() ){

			$items = DWQR_Admin::opt('items');
			$selected_module = !empty($attr) && isset($attr['selected_module']) ? $attr['selected_module'] : DWQR_Admin::opt('dwqr_module');
			$post_id = get_the_ID();
			$wp_classname = !empty($attr) && isset($attr['wpclass']) ? $attr['wpclass'] : 'wbp-cbm';

			$theme_color = '';
			$inline_style = '';
			if( DWQR_Admin::opt('theme_color') != '#0066CC' ){
				$custom_theme_color = DWQR_Admin::opt('theme_color');
				$theme_color .= ' style="--dwqrColor: '. $custom_theme_color . ';" ';

				global $is_IE;
				if($is_IE){
					$inline_style .='.wbp-cbm .wb-btn-dwqr{border: 1px solid ' . $custom_theme_color . ';} ';
					$inline_style .='.wbui-dwqr-donate .tab-cont .hl, .wbp-cbm .wb-btn-dwqr span{color: ' . $custom_theme_color . ';} ';
					$inline_style .='.wbp-cbm .wb-btn-dwqr svg, .widget-social-dwqr .wb-btn-dwqr:hover svg{fill: ' . $custom_theme_color . ';} ';
					$inline_style .='.wbp-cbm .wb-btn-dwqr.active,.wbp-cbm .wb-btn-dwqr:active,.wbp-cbm .wb-btn-dwqr:hover{background-color: ' . $custom_theme_color . ';} ';
				}
			}

			$share_items = array(
				'weixin'=>array('name'=>'微信'),
				'weibo'=>array('name'=>'微博'),
				'qzone'=>array('name'=>'QQ空间'),
				'qq'=>array('name'=>'QQ')
			);

			$tpl ='';
			$inline_script ='';
			$tab_html ='';
			$cont_html ='';

			$like = get_post_meta($post_id,'dwqr_like',true);
			$like = $like ? intval($like) : 0;
			$like = $like > 999 ? intval($like / 1000) . 'k+' : $like;

			if(isset($selected_module['poster']) && $selected_module['poster']) {
				$inline_script .= 'var poster_theme='.DWQR_Admin::opt('poster_theme').', poster_ratio="'.DWQR_Admin::opt('cover_ratio').'";';
			}

			if(isset($selected_module['donate']) && $selected_module['donate']) {

				$index = 0;
				foreach ($items as $k => $v){
					if(empty($v['img']))continue;

					$tab_html .= '<div class="tab-nav-item item-'.$k. ($index==0 ? ' current':'') .'"><span>'.$v['name'].'</span></div>';
					$cont_html .= '<div class="tab-cont'.($index==0 ? ' current':'') .'"><div class="pic"><img src="'.$v['img'].'" alt="'.$v['name'].'二维码图片"></div><p>用<span class="hl">'.$v['name'].'</span>扫描二维码打赏</p></div>';
					$index ++;
				}
				if($index < 2) $tab_html ='';
				if($index == 0) $selected_module['donate'] = '';

				$inline_script .= 'var wb_dwqr_donate_html=\'<div class="tab-navs">'.$tab_html.'</div><div class="tab-conts">'.$cont_html.'</div>\';';
			}

			if(isset($selected_module['share']) && $selected_module['share']) {
				$img_url     = $this->wbolt_catch_first_image();
				$opt_def_img = DWQR_Admin::opt( 'cover_url' );
				$def_cover   = plugin_dir_url( DWQR_BASE_FILE ) . 'assets/img/def_cover.png';
				$share_cover = $img_url ? $img_url['src'] : ( $opt_def_img ? $opt_def_img : $def_cover );
				$share_url =  wp_get_shortlink( $post_id );
				$share_url = !empty($attr) && isset($attr['url']) ? $attr['url'] : $share_url;

				$wb_dwqr_share_html = '<div class="wb-share-list" data-cover="' . $share_cover . '">';
				foreach ( $share_items as $k => $v ) {
					$wb_dwqr_share_html .= '<a class="share-logo icon-' . $k . '" data-cmd="' . $k . '" title="分享到' . $v['name'] . '" rel="nofollow"><svg class="wb-icon wbsico-dwqr-' . $k . '"><use xlink:href="#wbsico-dwqr-' . $k . '"></use></svg></a>';
				}

				$inline_script .= 'var wb_dwqr_share_html=\''.$wb_dwqr_share_html.'\';';
			}


			if ( !self::$is_set_script_var) {
				self::$is_set_script_var = wp_add_inline_script('wbs-front-dwqr', $inline_script,'before');

				//按后台加载自定义样式
				if($inline_style){
					wp_add_inline_style('wbs-dwqr-css', $inline_style);
				}
			}

			$tpl .= '
			<div class="'.$wp_classname.'" '
				. $theme_color
		        . (!empty($share_url) ? 'wb-share-url="'.$share_url.'"': '') . '>';

			if( isset($selected_module['donate']) && $selected_module['donate'] ) {
				$tpl .= '<a class="wb-btn-dwqr wb-btn-donate j-dwqr-donate-btn"><svg class="wb-icon wbsico-donate"><use xlink:href="#wbsico-dwqr-donate"></use></svg><span>打赏</span></a>';
			}

			if( isset($selected_module['like']) && $selected_module['like']) {
				$tpl .= '<a class="wb-btn-dwqr wb-btn-like j-dwqr-like-btn" data-count="'.$like.'"><svg class="wb-icon wbsico-like"><use xlink:href="#wbsico-dwqr-like"></use></svg><span class="like-count">赞' . ( $like ? '('.$like .')' : '' ) . '</span></a>';
			}

			if( isset($selected_module['poster']) && $selected_module['poster']) {
				$tpl .= '<a class="wb-btn-dwqr wb-share-poster j-dwqr-poster-btn"><svg class="wb-icon wbsico-poster"><use xlink:href="#wbsico-dwqr-poster"></use></svg><span>微海报</span></a>';
			}

			if( isset($selected_module['share']) && $selected_module['share']){
				$tpl .= '<a class="wb-btn-dwqr wb-btn-share j-dwqr-social-btn"><svg class="wb-icon wbsico-share"><use xlink:href="#wbsico-dwqr-share"></use></svg><span>分享</span></a>';
			}

			$tpl .= '</div>'; //ctrl-area-cbm

			return $tpl;
	}

	public function wbolt_catch_first_image() {
		global $post;
		$first_img = array();

		if(has_post_thumbnail() && get_the_post_thumbnail( get_the_ID() )!='') {
			$first_img['src'] = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'post-thumbnail' )[0];

			return $first_img;
		}

		if(preg_match_all('#<img[^>]+>#is',$post->post_content,$match)){

			$match_frist = $match[0][0];


			if($match_frist) :
				preg_match('#src=[\'"]([^\'"]+)[\'"]#',$match_frist,$src);
				preg_match('#width=[\'"]([^\'"]+)[\'"]#',$match_frist,$width);
				preg_match('#height=[\'"]([^\'"]+)[\'"]#',$match_frist,$height);

				$first_img['src'] = $src ? $src[1] : '';
				$first_img['width'] = $width ? $width[1] : '';
				$first_img['height'] = $height ? $height[1] : '';

			endif;
		}else{
			$first_img = 0;
		}
		return $first_img;
	}

	public function wb_share_post_handler(){
		global $post;

		if(isset($_POST['id']) && $_POST['id'] && $post = get_post($_POST['id'])){
			setup_postdata( $post );
			$img_url = $this->wbolt_catch_first_image();
			$opt_def_img = DWQR_Admin::opt('cover_url');
			$def_cover = plugin_dir_url(DWQR_BASE_FILE) . 'assets/img/def_cover.png';
			$share_head = $img_url ? $img_url['src'] : ( !empty($opt_def_img) ? $opt_def_img : $def_cover);

			$site_logo =  DWQR_Admin::opt('logo_url');
			$share_logo = isset($site_logo) && $site_logo ? $site_logo : plugin_dir_url(DWQR_BASE_FILE) . 'assets/img/def_logo.png';
			$excerpt = rtrim( trim( strip_tags( apply_filters( 'the_excerpt', get_the_excerpt() ) ) ), '[原文链接]');
			$excerpt = preg_replace('/\\s+/', ' ', $excerpt );

			$res = array(
				'head' => $this->wb_image_to_base64($share_head),
				'logo' => $this->wb_image_to_base64($share_logo),
				'title' => $post->post_title,
				'excerpt' => $excerpt,
				'timestamp' => get_post_time('U', true)
			);

			wp_reset_postdata();

			echo wp_json_encode($res);

			exit;
		}
	}

	public function wb_image_to_base64( $image ){
		$site_domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);
		$img_domain = parse_url($image, PHP_URL_HOST);
		if ( $img_domain != $site_domain ) {
			$http_options = array(
				'httpversion' => '1.0',
				'timeout' => 20,
				'redirection' => 20,
				'sslverify' => FALSE,
				'user-agent' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; MALC)'
			);
			if(preg_match('/^\/\//i', $image)) $image = 'http:' . $image;
			$get = wp_remote_get($image, $http_options);
			if (!is_wp_error($get) && 200 === $get ['response'] ['code']) {
				$img_base64 = 'data:' . $get['headers']['content-type'] . ';base64,' . base64_encode($get ['body']);
				return $img_base64;
			}
		}
		$image = preg_replace('/^(http:|https:)/i', '', $image);
		return $image;
	}

	/**
	 * e.g. [wb_share_social items="donate,like,poster,share" wpclass="widget-social"]
	 * items 添加显示组件名称
	 * wpclass 外层DOM的类名（免于与文章详情底下的冲突）
	*/
	public function wb_share_social_handler($attr=array()){
		$set_attr = array();

		if(!empty($attr)){
			if(isset( $attr['items'] )){
				$get_items = explode(',', $attr['items']);

				foreach ($get_items as $item){
					$set_attr['selected_module'][$item] = 1;
				}
			}

			$set_attr['wpclass'] = isset( $attr['wpclass'] ) ? $attr['wpclass'] : 'widget-social-dwqr';
		}
		echo $this->donateHtml($set_attr);
	}
}