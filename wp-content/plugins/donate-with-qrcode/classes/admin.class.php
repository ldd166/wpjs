<?php





class DWQR_Admin
{

    public static $name = 'dwqr_pack';
    public static $option_name = 'dwqr_option';
    public static $cnf_dwqr = array(
    	'theme_color'=>array(
            '#0066CC',
		    '#A02533',
		    '#CA891E',
		    '#6BB020',
		    '#8B572A',
		    '#000000',
		    '#666666'
	    ),
	    'poster_theme'=>array(
	    	0=>array(
	    		'name'=>'普通版',
			    'bgi'=>''
		    ),
	    	1=>array(
	    		'name'=>'风格1',
			    'bgi'=>''
		    ),
	    	2=>array(
	    		'name'=>'风格2',
			    'bgi'=>''
		    ),
	    	3=>array(
	    		'name'=>'风格3',
			    'bgi'=>''
		    ),
	    ),
	    'poster_cover_ratio'=>array('3:2','1:1','16:9')

    );


	public function __construct(){

        if(is_admin()){

            //注册相关动作
            add_action( 'admin_menu', array($this,'admin_menu') );

            add_action( 'admin_init', array($this,'admin_init') );
            //插件设置连接
            add_filter( 'plugin_action_links', array($this,'actionLinks'), 10, 2 );

            add_action('admin_enqueue_scripts',array($this,'admin_enqueue_scripts'),1);

            add_filter('plugin_row_meta', array(__CLASS__, 'plugin_row_meta'), 10, 2);

	        add_action('wp_ajax_wb_dwqr_setting', array(__CLASS__,'ajaxHandle'));
        }


	}

	public function admin_enqueue_scripts($hook){
        global $wb_settings_page_hook_dwqr;
        if($wb_settings_page_hook_dwqr != $hook) return;

		$opt = self::get_theme_setting();

		if(defined('WB_CORE_ASSETS_LOAD') && class_exists('WB_Core_Asset_Load')){
			WB_Core_Asset_Load::load('setting-04');
		}else{
			wp_enqueue_script('wbp-dwqr-js', plugin_dir_url(DWQR_BASE_FILE) . 'assets/wbp_setting.js', array(), DWQR_VERSION, true);

		}

		wp_add_inline_script('wbp-dwqr-js', 'var dwq_cnf ='. json_encode($opt).';','before');
		wp_enqueue_style('wbs-style-dwqr', plugin_dir_url(DWQR_BASE_FILE) . 'assets/wbp_setting.css', array(), DWQR_VERSION);
    }

    public static function plugin_row_meta($links,$file){

        $base = plugin_basename(DWQR_BASE_FILE);
        if($file == $base) {
            $links[] = '<a href="https://www.wbolt.com/plugins/dwq" target="_blank">插件主页</a>';
            $links[] = '<a href="https://www.wbolt.com/dwq-plugin-documentation.html" target="_blank">FAQ</a>';
            $links[] = '<a href="https://wordpress.org/support/plugin/donate-with-qrcode/" target="_blank">反馈</a>';
        }
        return $links;
    }
	
	function actionLinks( $links, $file ) {
		
		if ( $file != plugin_basename(DWQR_BASE_FILE) )
			return $links;
	
		$settings_link = '<a href="'.menu_page_url( self::$name, false ).'">设置</a>';
	
		array_unshift( $links, $settings_link );
	
		return $links;
	}
	
	function admin_menu(){
		global $wb_settings_page_hook_dwqr;
		$wb_settings_page_hook_dwqr = add_options_page(
			'博客社交分享组件设置',
			'博客社交分享组件',
			'manage_options',
			self::$name,
			array($this,'admin_settings')
		);
	}

	function admin_settings(){

		include_once( DWQR_PATH.'/settings.php' );
	}

	function admin_init(){
		register_setting(  self::$option_name, self::$option_name );
	}



	public static function def_opt(){

		$cnf = array(
			/*基本设置*/
			'dwqr_switch'=>1,
			'theme_color'=>'#0066CC',
			'dwqr_module'=>array(
				'donate'=>1,
				'like'=>1,
				'poster'=>1,
				'share'=>1
			),

			/*打赏*/
			'items' => array(
				'weixin' => array(
					'name' => '微信',
					'img'=>''
				),
				'alipay' => array(
					'name' => '支付宝',
					'img'=>''
				)
			),


			/*微海报*/
			'logo_url' => '',
			'cover_url'=>'',
			'poster_theme'=>0,
			'cover_ratio'=>'3:2',

		);

		return $cnf;
	}

	public static function extend_conf(&$cnf,$conf){
		if(is_array($conf))foreach($conf as  $k=>$v){
			if(!isset($cnf[$k])){
				$cnf[$k] = $v;
			}else if(is_array($v)){
				self::extend_conf($cnf[$k],$v);
			}
		}
	}

	//配置值
    public static function opt($name='', $default = false)
    {
	    static $options = null;

	    if (null == $options){
		    $options = get_option(self::$option_name,array());
		    $is_new = false;
		    if(!$options){
			    $is_new = true;
		    }

		    self::extend_conf($options,self::def_opt());
		    if($is_new){
			    $new_default = array(

			    );
			    foreach ($new_default as $k=>$v){
				    if(is_array($v)){
					    $options[$k] = array_merge($options[$k],$v);
				    }else{
					    $options[$k] = $v;
				    }
			    }
		    }
	    }

	    $return = null;
	    do{

		    if(!$name){
			    $return = $options;
			    break;
		    }

		    $ret = $options;
		    $ak = explode('.', $name);

		    foreach ($ak as $sk) {
			    if (isset($ret[$sk])) {
				    $ret = $ret[$sk];
			    } else {
				    $ret = $default;
				    break;
			    }
		    }

		    $return = $ret;


	    }while(0);


	    return apply_filters('wb_theme_get_conf',$return,$name,$default);
    }

	public static function set_theme_setting( $data ) {
		//$opt = self::opt();
		$opt = array();
		foreach($data as $key => $value){
			$opt[$key] = self::stripslashes_deep($value);
		}

		return update_option( self::$option_name, $opt );
	}

	//去掉vue提交时加的反斜杠
	public static function stripslashes_deep($value)
	{
		if(is_array($value)){
			foreach($value as $k => $v){
				$value[$k] = self::stripslashes_deep($v);
			}
		}else{
			$value = stripslashes($value);
		}
		return $value;
	}


	public static function get_theme_setting(){

		$ret = array();

		$ret['pd_code'] = self::$option_name;
		$ret['cnf'] = self::$cnf_dwqr;
		$ret['opt'] = self::opt();

		return $ret;
	}


	public static function ajaxHandle(){
		if( !is_user_logged_in()) {
			echo 'fail';
			exit();
		}
		switch ($_POST['do']){

			case 'set_setting':

				$opt_data = $_POST['opt'];

				self::set_theme_setting($opt_data);

				$ret = array('code'=>0,'desc'=>'success');

				header('content-type:text/json;charset=utf-8');
				echo json_encode($ret);
				break;
		}
		exit();
	}
}