<?php

/** @deprecated **/
define('SESSION_MESSAGE_INFO', 'info');
define('SESSION_MESSAGE_WARNING', 'warning');
define('SESSION_MESSAGE_ERROR', 'error');
define('SESSION_MESSAGE_SUCCESS', 'success');

/**
 * Utility functions to handle sessions and users
 * @author levhita
 * @package ThaFrame
 */
class Session
{
  /**
   * Holds the logged in user, usually {@link UserModel} or some child class
   * @var  UserModel
   */
  protected static $_User;
  protected static $_error;
  protected static $prefix = "user_";
  
  const MESSAGE_INFO     = 'info';
  const MESSAGE_WARNING  = 'warning';
  const MESSAGE_ERROR    = 'error';
  const MESSAGE_SUCCESS  = 'success';
  
  public function __construct() {}
  
  public static function setAsLoggedIn($user_id)
  {
    $_SESSION[self::$prefix.'id'] = $user_id; 
    $_SESSION[self::$prefix.'ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION[self::$prefix.'agent'] = $_SERVER['HTTP_USER_AGENT'];
  }
  
  public static function assertLoggedIn($user_id='')
  {
    if ( !isset($_SESSION[self::$prefix.'id']) ) {
      self::$_error = 'User isn\'t Logged In' ;
      return false;
    }
    if ( !empty($user_id) ){
      if ( $_SESSION[self::$prefix.'id'] != $user_id) {
        self::$_error = 'User Id doesn\'t Match';
        return false;
      } 
    }
    if (!isset($_SESSION[self::$prefix.'ip']) && $_SESSION[self::$prefix.'ip'] != $_SERVER['REMOTE_ADDR']) {
      self::$_error = 'Ip Changed!';
      return false;
    }
    if ($_SESSION[self::$prefix.'agent'] != $_SERVER['HTTP_USER_AGENT'] ) {
      self::$_error = 'User Agent Changed!';
      return false;
    }
    return true;
  }
  
  public static function deleteSession() {
    $_SESSION = array();
    session_destroy();
    session_start();
  }

  public static function getUser($class='') {
  	$Config = Config::getInstance();
    if ( !isset(self::$_User) ) {
      if ( !empty($_SESSION[self::$prefix.'id']) ){
        if (empty($class)) {
          $class = (!isset($Config->user_class))?'UserModel':$Config->user_class;
        }
        if ( $class =='UserModel' ) { 
          $User = new UserModel( $Config->user_table, (int)$_SESSION[self::$prefix.'id'] );
        } else {
          $User = new $class( (int)$_SESSION[self::$prefix.'id'] );
        }
        
        if ( !$User->load() ) {
          self::$_error = "Couldn't Load User";
          
          return false;
        }
        if ( !$User ->loadPermissions() ){
          self::$_error="Couldn't Load Permissions";
          return false;
        }
        self::$_User = $User;
      } else {
        self::$_error = "Not Logged In";
        return false;
      }
  	}
  	return self::$_User;  
  }
  
  public static function getErrorString() {
    return self::$_error;
  }
  
  /**
   * saves a message in session
   * @param string $message
   * @param string $level might be: info, warning, error, success
   * @return false
   */
  public static function setMessage($message = '', $level=self::MESSAGE_INFO) {
    $_SESSION['__message_text'] = $message;
    $_SESSION['__message_level'] = $level;
  }
  
  public function __set($variable, $value) {
    $_SESSION[$variable] = $value;
  }
  
  public function __get($variable) {
    if (array_key_exists($variable, $_SESSION)) {
      return $_SESSION[$variable];
    }
    $trace = debug_backtrace();
    trigger_error(
    	'Undefined property via __get(): ' . $variable .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_NOTICE);
    return null;
  }
  
  public function __isset($variable) {
    return isset($_SESSION[$variable]);
  }
  
  public function __unset($variable){
    unset($_SESSION['variable']);
  }
}