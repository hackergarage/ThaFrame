<?php
/**
 * @package Garson
 * @todo Use configs instead of constants
 */

require_once THAFRAME . "/vendors/swift/swift_required.php";

/**
 * Class that handle email sending
 */
class Mailer {
  /**
   * Vendor's {@link Swift_Mailer}
   * @var Swift_Mailer
   */
  private $__Mailer= Null;
  
  private $__last_error='';
  
  public function __construct() {
  	$Config=Config::getInstance();
    if ($Config->email_ssl) {
      $Transport = Swift_SmtpTransport::newInstance($Config->email_server, $Config->email_port,'ssl');
    }else{
      $Transport = Swift_SmtpTransport::newInstance($Config->email_server, $Config->email_port);
    }
    $Transport->setUsername($Config->email_login);
    $Transport->setPassword($Config->email_password);
    
    $this->__Mailer = Swift_Mailer::newInstance($Transport);
  }
  /**
   * Sends an email using default settings.
   * @param string $email
   * @param string $full_name
   * @param string $subject
   * @param body $body
   * @return True on success false otherwise
   */

  public function send($email, $full_name, $subject, $body, $html=false) {
  	$Config=Config::getInstance();
    $Message = Swift_Message::newInstance();
    
    $success=true;
    try {
      $Message->setSubject($subject);
      $Message->setFrom(array($Config->email_from => $Config->email_name ));
      $Message->setTo( array($email => $full_name) );
      $Message->setBody($body);
      
      if($html) {
        $Message->setContentType("text/html");
      }
      $this->__Mailer->send($Message);
    } catch (Exception $e) {
      $this->__last_error = $e->getMessage();
      $success=false;
    }
    return $success;
  }
  
  public function getLastError() {
    return $this->__last_error;
  }
}