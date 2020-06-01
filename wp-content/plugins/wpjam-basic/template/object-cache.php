<?php
if(
	// (isset($_POST['action']) && $_POST['action'] == 'query-attachments') ||
	(isset($_GET['debug']) && $_GET['debug'] == 'sql')
){
	// require_once ( ABSPATH . WPINC . '/cache.php' );
	return;
}

if ( !defined( 'WP_CACHE_KEY_SALT' ) )
	define( 'WP_CACHE_KEY_SALT', '' );

if ( class_exists( 'Memcached' ) ) {

	function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
		global $wp_object_cache;
		return $wp_object_cache->add( $key, $data, $group, (int) $expire );
	}

	function wp_cache_cas( $cas_token, $key, $data, $group = '', $expire = 0  ) {
		global $wp_object_cache;
		return $wp_object_cache->cas( $cas_token, $key, $data, $group, (int) $expire );
	}

	function wp_cache_close() {
		global $wp_object_cache;
		return $wp_object_cache->close();
	}

	function wp_cache_decr( $key, $offset = 1, $group = '' ) {
		global $wp_object_cache;
		return $wp_object_cache->decr( $key, $offset, $group );
	}

	function wp_cache_delete( $key, $group = '' ) {
		global $wp_object_cache;
		return $wp_object_cache->delete( $key, $group );
	}

	function wp_cache_delete_multi( $keys, $group = '' ) {
		global $wp_object_cache;
		return $wp_object_cache->delete_multi( $keys, $group );
	}

	function wp_cache_flush() {
		global $wp_object_cache;
		return $wp_object_cache->flush();
	}

	function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
		global $wp_object_cache;
		return $wp_object_cache->get( $key, $group, $force, $found );
	}
	
	function wp_cache_get_multi( $keys, $group = '' ) {
		global $wp_object_cache;
		return $wp_object_cache->get_multi( $keys, $group );
	}

	function wp_cache_get_with_cas( $key, $group = '', &$cas_token = null ) {
		global $wp_object_cache;
		return $wp_object_cache->get_with_cas( $key, $group, $cas_token );
	}

	function wp_cache_incr( $key, $offset = 1, $group = '' ) {
		global $wp_object_cache;
		return $wp_object_cache->incr( $key, $offset, $group );
	}

	if(!isset($_GET['debug']) || $_GET['debug'] != 'sql'){
		function wp_cache_init() {
			$GLOBALS['wp_object_cache'] = new WP_Object_Cache();
		}
	}

	function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
		global $wp_object_cache;
		return $wp_object_cache->replace( $key, $data, $group, (int) $expire );
	}

	// function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	// 	global $wp_object_cache;

	// 	if ( defined( 'WP_INSTALLING' ) == false ) {
	// 		return $wp_object_cache->set( $key, $data, $group, $expire );
	// 	} else {
	// 		return $wp_object_cache->delete( $key, $group );
	// 	}
	// }

	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		global $wp_object_cache;
		return $wp_object_cache->set( $key, $data, $group, (int) $expire );
	}

	function wp_cache_set_multi( $keys, $datas, $group= '', $expire = 0 ) {
		global $wp_object_cache;
		return $wp_object_cache->set_multi( $keys, $datas,  $group, (int) $expire);
	}

	function wp_cache_switch_to_blog( $blog_id ) {
		global $wp_object_cache;
		return $wp_object_cache->switch_to_blog( $blog_id );
	}

	function wp_cache_add_global_groups( $groups ) {
		global $wp_object_cache;
		$wp_object_cache->add_global_groups( $groups );
	}

	function wp_cache_add_non_persistent_groups( $groups ) {
		global $wp_object_cache;
		$wp_object_cache->add_non_persistent_groups( $groups );
	}

	function wp_cache_get_stats() {
		global $wp_object_cache;
		return $wp_object_cache->get_stats();
	}

	class WP_Object_Cache {
		private $cache 			= array();
		private $mc				= null;

		public $cache_hits		= 0;
		public $cache_misses	= 0;

		private $blog_prefix;
		private $global_prefix;

		protected $global_groups	= array();
		protected $no_mc_groups		= array();

		public function add( $id, $data, $group = 'default', $expire = 0 ) {
			if ( wp_suspend_cache_addition() ) {
				return false;
			}

			$key = $this->build_key( $id, $group );

			if ( is_object( $data ) ) {
				$data	= clone $data;
			}

			if ( isset( $this->no_mc_groups[ $group ] ) ) {
				if ( isset( $this->cache[ $key ] ) && $this->cache[ $key ] !== false ) {
					return false;
				}

				$this->cache[ $key ]	= $data;
				return true;
			}

			$result	= $this->mc->add( $key, $data, $expire );

			if( $this->mc->getResultCode() === Memcached::RES_SUCCESS ) {
				$this->cache[ $key ]	= $data;
			} elseif ( isset( $this->cache[ $key ] ) ){
				unset( $this->cache[ $key ] );
			}

			return $result;
		}

		public function add_global_groups( $groups ) {
			$groups = (array) $groups;

			$groups					= array_fill_keys( $groups, true );
			$this->global_groups	= array_merge( $this->global_groups, $groups );
		}

		public function add_non_persistent_groups( $groups ) {
			$groups = (array) $groups;

			$groups					= array_fill_keys( $groups, true );
			$this->no_mc_groups		= array_merge( $this->no_mc_groups, $groups );
		}

		public function incr( $id, $offset = 1, $group = 'default' ) {
			$key	= $this->build_key( $id, $group );

			$result	= $this->mc->increment( $key, $offset );

			if( $this->mc->getResultCode() === Memcached::RES_SUCCESS ){
				$this->cache[ $key ]	= $result;
			}

			return $result;
		}

		public function decr( $id, $offset = 1, $group = 'default' ) {
			$key	= $this->build_key( $id, $group );

			$result	= $this->mc->decrement( $key, $offset );

			if( $this->mc->getResultCode() === Memcached::RES_SUCCESS ){
				$this->cache[ $key ]	= $result;
			}

			return $result;
		}

		public function close() {
			$this->mc->quit();
		}

		public function delete( $id, $group = 'default' ) {
			$key = $this->build_key( $id, $group );

			unset( $this->cache[ $key ] );

			if ( isset( $this->no_mc_groups[ $group ] ) ) {
				return true;
			}

			return $this->mc->delete( $key );
		}

		public function delete_multi($ids, $group = 'default'){
			$keys =	array_map(function($id) use ($group){ return $this->build_key( $id, $group ); }, $ids);

			foreach ($keys as $key) {
				unset( $this->cache[ $key ] );
			}

			if ( isset( $this->no_mc_groups[ $group ] ) ) {
				return true;
			}

			$result	= $this->mc->deleteMulti( $keys );

			return $result;
		}

		public function flush() {
			return $this->mc->flush();
		}

		public function get( $id, $group = 'default', $force = false, &$found = null ) {
			$key	= $this->build_key( $id, $group );

			if( isset( $this->no_mc_groups[ $group ] ) ){
				$force	= false;
			}

			if ( isset( $this->cache[ $key ] ) && ! $force  ) {
				$found	= true;

				if ( is_object( $this->cache[ $key ] ) ) {
					$value	= clone $this->cache[ $key ];
				} else {
					$value	= $this->cache[ $key ];
				}
			} elseif ( isset( $this->no_mc_groups[ $group ] ) ) {
				$found	= false;
				$value	= false;
			} else {
				$value	= $this->mc->get( $key );
				
				if ($this->mc->getResultCode() == Memcached::RES_NOTFOUND) {
					$value	= false;
					$found	= false;
				} else {
					$found	= true;

					$this->cache[ $key ]	= $value;
				}
			}

			return $value;
		}

		public function get_with_cas( $id, $group = 'default', &$cas_token=null){
			$key	= $this->build_key( $id, $group );
			
			if(defined('Memcached::GET_EXTENDED')) {
				$result	= $this->mc->get($key, null, Memcached::GET_EXTENDED);

				if ($this->mc->getResultCode() == Memcached::RES_NOTFOUND) {
					$value	= false;
				}else{
					$value		= $result['value'];
					$cas_token 	= $result['cas'];

					$this->cache[ $key ]	= $value;
				}
			}else{
				$value	= $this->mc->get($key, null, $cas_token);

				if ($this->mc->getResultCode() == Memcached::RES_NOTFOUND) {
					$value	= false;
				} else {
					$this->cache[ $key ]	= $value;
				}
			}

			return $value;
		}

		public function cas( $cas_token, $id, $data, $group = 'default', $expire = 0 ) {
			$key = $this->build_key( $id, $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			unset( $this->cache[ $key ] );

			return $this->mc->cas( $cas_token, $key, $data, $expire );
		}

		public function get_multi( $ids, $group = 'default' ) {
			if ( isset( $this->no_mc_groups[ $group ] ) ) {
				return false;
			}

			$keys =	array_map(function($id) use ($group){ return $this->build_key( $id, $group ); }, $ids);

			$key_ids = array_combine($keys, $ids);

			// $version = intval($this->mc->getVersion());
			// if ($version < 3) {
			// 	$results = $this->mc->getMulti( $keys, $cas_tokens, Memcached::GET_PRESERVE_ORDER );
			// } else {
			// 	$results = $this->mc->getMulti( $keys, Memcached::GET_PRESERVE_ORDER);
			// }

			$results	= $this->mc->getMulti($keys);

			if($results){
				$returns	= array();
				foreach ($key_ids as $key=>$id) {
					if(isset($results[$key])){
						$this->cache[ $key ] = $results[$key];
						$returns[$id]	= $results[$key];
					}
				}

				return $returns;
			}

			return $results;
		}

		private function build_key( $id, $group ) {
			if ( empty( $group ) ) {
				$group = 'default';
			}

			if ( isset( $this->global_groups[ $group ] ) ) {
				$prefix = $this->global_prefix;
			} else {
				$prefix = $this->blog_prefix;
			}

			return preg_replace( '/\s+/', '', WP_CACHE_KEY_SALT . $prefix . $group . ':' . $id );
		}

		public function replace( $id, $data, $group = 'default', $expire = 0 ) {
			$key	= $this->build_key( $id, $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			if ( isset( $this->no_mc_groups[ $group ] ) ) {
				if ( !isset( $this->cache[ $key ] ) || $this->cache[ $key ] === false ) {
					return false;
				}

				$this->cache[ $key ]	= $data;
				return true;
			}

			$result = $this->mc->replace( $key, $data, $expire );

			if( $this->mc->getResultCode() === Memcached::RES_SUCCESS ) {
				$this->cache[ $key ]	= $data;
			} elseif ( isset( $this->cache[ $key ] ) ){
				unset( $this->cache[ $key ] );
			}

			return $result;
		}

		public function set( $id, $data, $group = 'default', $expire = 0 ) {
			$key = $this->build_key( $id, $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			if ( isset( $this->no_mc_groups[ $group ] ) ) {
				$this->cache[ $key ]	= $data;
				return true;
			}

			$result	= $this->mc->set( $key, $data, $expire );

			if( $this->mc->getResultCode() === Memcached::RES_SUCCESS ) {
				$this->cache[ $key ]	= $data;
			} elseif ( isset( $this->cache[ $key ] ) ){
				unset( $this->cache[ $key ] );
			}

			return $result;
		}

		public function set_multi( $ids, $datas, $group = 'default', $expire = 0 ) {
			if ( isset( $this->no_mc_groups[ $group ] ) ) {
				return true;
			}

			$keys =	array_map(function($id) use ($group){	return $this->build_key( $id, $group );}, $ids);

			foreach ($keys as $i=>$key) {
				$this->cache[ $key ] = $datas[$i];
			}

			$items	= array_combine($keys, $datas);

			$result = $this->mc->setMulti( $items, $expire );

			return $result;
		}

		public function switch_to_blog($blog_id) {
			if(is_multisite()){
				$blog_id	= (int) $blog_id;

				$this->blog_prefix		= $blog_id . ':';
			}else{
				global $table_prefix;

				$this->blog_prefix		= $table_prefix . ':';	
			}
		}

		public function get_stats() {
			return $this->mc->getStats();
			
		}

		public function get_mc(){
			return $this->mc;
		}

		public function failure_callback( $host, $port ) {
		}

		public function __construct() {

			$this->mc = new Memcached();
			$this->mc ->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

			if(!$this->mc->getServerList()){
				$this->mc->addServer('127.0.0.1', 11211, 100);
			}

			// global $memcached_servers;

			// if ( isset( $memcached_servers ) ) {
			// 	$buckets = $memcached_servers;
			// } else {
			// 	$buckets = array( '127.0.0.1' );
			// }

			// reset( $buckets );
			// if ( is_int( key( $buckets ) ) ) {
			// 	$buckets = array( 'default' => $buckets );
			// }

			// foreach ( $buckets as $bucket => $servers ) {
			// 	$this->mc[ $bucket ] = new Memcached($bucket);
			// 	$this->mc[ $bucket ] ->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

			// 	if(count($this->mc[ $bucket ]->getServerList())){
			// 		continue;
			// 	}

			// 	$instances = array();
			// 	foreach ( $servers as $server ) {
			// 		@list( $node, $port ) = explode( ':', $server.":" );
			// 		if ( empty( $port ) ) {
			// 			$port = ini_get( 'memcache.default_port' );
			// 		}
			// 		$port = intval( $port );
			// 		if ( ! $port ) {
			// 			$port = 11211;
			// 		}

			// 		$instances[] = array( $node, $port, 1 );
			// 	}
			// 	$this->mc[ $bucket ]->addServers( $instances );
			// }

			if(is_multisite()){
				$this->blog_prefix		= get_current_blog_id() . ':';
				$this->global_prefix	= '';
			}else{
				global $table_prefix;

				$this->blog_prefix		= $table_prefix . ':';

				if(defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' )){
					$this->global_prefix	= '';
				}else{
					$this->global_prefix	= $table_prefix . ':';	
				}	
			}

			// $this->cache_hits   =& $this->stats['get'];
			// $this->cache_misses =& $this->stats['add'];
		}


	}
} else {

	// No Memcached

	// if ( function_exists( 'wp_using_ext_object_cache' ) ) {
	// 	// In 3.7+, we can handle this smoothly
	// 	wp_using_ext_object_cache( false );
	// } else {
	// 	// In earlier versions, there isn't a clean bail-out method.
	// 	wp_die( 'Memcached class not available.' );
	// }
}
