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
  
  public function __construct() {
    if (EMAIL_SSL) {
      $Transport = Swift_SmtpTransport::newInstance(EMAIL_SERVER, EMAIL_PORT,'ssl');
    }else{
      $Transport = Swift_SmtpTransport::newInstance(EMAIL_SERVER, EMAIL_PORT);
    }
    $Transport->setUsername(EMAIL_LOGIN);
    $Transport->setPassword(EMAIL_PASSWORD);
    
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
    $Message = Swift_Message::newInstance();
    $Message->setSubject($subject);
    $Message->setFrom(array(EMAIL_FROM => EMAIL_NAME));
    $Message->setTo( array($email => $full_name) );
    $Message->setBody($body);
    if($html) {
      $Message->setContentType("text/html");
    }
    return $this->__Mailer->send($Message);
  }
}