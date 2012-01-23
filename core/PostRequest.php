<?php
/**
 * Class that handle Request related things, with a little effort can be used to
 * create from zero and re-create from current request.
 *
 * @package Garson
 * @author Argel Arias <levhita@gmail.com>
 */
class PostRequest {
  /**
   * Singleton instance
   * @var Request
   */
  protected static $__instance = null;
  
  /**
   * The last section of the uri
   * @var string
   */
  protected $__parameters  = '';
  
  /** The full request_uri, after striping the BASE_URL
   * @var string
   */
  protected $__request_uri = '';
  
  protected function __construct() {
    $Config = Config::getInstance();
    $rewrite_base    = $Config->system_rewrite_base;
    $request_uri = $_SERVER[ 'REQUEST_URI'];
    
    /** Removes everything from the request uri, up to the base url **/
    $rewrite_base_len = strlen($rewrite_base);
    $request_uri_len = strlen($request_uri);
    $x = 0;
    while ( $x<$rewrite_base_len && $x<$request_uri_len && $rewrite_base{$x}==$request_uri{$x} ) {
      $x++;
    }
    $this->__request_uri = trim(substr($request_uri,$x), '/');
  }
  
  /**
   * Get a single instance of the class (Singleton)
   * @return PostRequest
   */
  public static function getInstance() {
    if (!self::$__instance instanceof self) {
      self::$__instance = new self;
    }
    return self::$__instance;
  }
  
  public function getParams() {
    return $_POST;
  }
  
  public function __get($field)
  {
    return $_POST[$field];
  }
  
  public function __isset($field) {
    return isset($_POST[$field]);
  }
  
  public function getRequestUri(){
    return $this->_request_uri;
  }
  
  public static function zeroParameter($field, $format='string'){
    if( isset($_POST[$field]) && !empty($_POST[$field]) ) {
    	switch ($format)
    	{
    		case 'int':
    				return (intval($_POST[$field]));
    		default :
    			 	return $_POST[$field];
    	}
    }
    return 0;
  }
  
}