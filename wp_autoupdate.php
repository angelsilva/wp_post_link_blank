<?php

class WP_AutoUpdate 
{
	/**
	 * The plugin current version
	 * @var string
	 */
	private $current_version;

	/**
	 * The plugin remote update path
	 * @var string
	 */
	private $update_path;

	/**
	 * Plugin Slug (plugin_directory/plugin_file.php)
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin name (plugin_file)
	 * @var string
	 */
	private $slug;

	/**
	 * License User
	 * @var string
	 */
	private $license_user;

	/**
	 * License Key 
	 * @var string
	 */
	private $license_key;

	/**
	 * Initialize a new instance of the WordPress Auto-Update class
	 * @param string $current_version
	 * @param string $update_path
	 * @param string $plugin_slug
	 */
	public function __construct( $current_version, $update_path, $plugin_slug, $license_user = '', $license_key = '' )
	{
		// Set the class public variables
		$this->current_version = $current_version;
		$this->update_path = $update_path;

		// Set the License
		$this->license_user = $license_user;
		$this->license_key = $license_key;

		// Set the Plugin Slug	
		$this->plugin_slug = $plugin_slug;
		list ($t1, $t2) = explode( '/', $plugin_slug );
		$this->slug = str_replace( '.php', '', $t2 );		

		// define the alternative API for updating checking
		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ) );

		// Define the alternative response for information checking
		add_filter( 'plugins_api', array( &$this, 'check_info' ), 10, 3 );
	}

	/**
	 * Add our self-hosted autoupdate plugin to the filter transient
	 *
	 * @param $transient
	 * @return object $ transient
	 */
	public function check_update( $transient )
	{
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get the remote version
		$remote_version = $this->getRemote('version');

		// If a newer version is available, add the update
		if ( version_compare( $this->current_version, $remote_version->new_version, '<' ) ) {
			$obj = new stdClass();
			$obj->slug = $this->slug;
			$obj->new_version = $remote_version->new_version;
			$obj->url = $remote_version->url;
			$obj->plugin = $this->plugin_slug;
			$obj->package = $remote_version->package;
			$obj->tested = $remote_version->tested;
			$transient->response[$this->plugin_slug] = $obj;
		}
		return $transient;
	}

	/**
	 * Add our self-hosted description to the filter
	 *
	 * @param boolean $false
	 * @param array $action
	 * @param object $arg
	 * @return bool|object
	 */
	public function check_info($obj, $action, $arg)
	{
		if (($action=='query_plugins' || $action=='plugin_information') && 
		    isset($arg->slug) && $arg->slug === $this->slug) {
			return $this->getRemote('info');
		}
		
		return $obj;
	}

	/**
	 * Return the remote version
	 * 
	 * @return string $remote_version
	 */
	public function getRemote($action = '')
	{
		/*$params = array(
			'body' => array(
				'action'       => $action,
				'license_user' => $this->license_user,
				'license_key'  => $this->license_key,
			),
		);
		
		// Make the POST request
		$request = wp_remote_post($this->update_path, $params );
		
		// Check if response is valid
		if ( !is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			return @unserialize( $request['body'] );
		}*/

		$params = array(
			
			'action'       => $action,
			'license_user' => $this->license_user,
			'license_key'  => $this->license_key,
		
		);

		$request = $this->web_request($this->update_path, $params );

		if($request) return @unserialize( $request );
		
		return false;
	}

	public function web_request($url,$fields_string=array(),&$header=array()){
		
        $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        curl_setopt($ch, CURLOPT_COOKIE, '');

        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        $rough_content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );

        $header_content = substr($rough_content, 0, $header['header_size']);
        $body_content = trim(str_replace($header_content, '', $rough_content));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
        preg_match_all($pattern, $header_content, $matches); 
        $cookiesOut = implode("; ", $matches['cookie']);

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['headers']  = $header_content;
        $header['content'] = $body_content;
        $header['cookies'] = $cookiesOut;

        curl_close( $ch );
            
        return $body_content;
    }
}