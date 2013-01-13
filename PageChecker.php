<?php

require_once 'config.php';
require_once 'Mail.php';
require_once 'facebook-php-sdk/src/facebook.php';

class PageChecker
{
    protected $lastPostId;
    protected $pageFBId;
    protected $facebook;
    protected $message;
    
    function __construct($pageId){
      $this->pageFBId = $pageId;
      $this->facebook = new Facebook(array('appId'=> FB_APP_ID, 'secret' => FB_APP_SECRET));
      
      $data = $this->getFeedInfo();
      $this->lastPostId = $this->getPostId($data[0]);
    }
  
    function exec(){
      while(1){
        $this->checkLastPost();
        sleep(1);
      }
    }
    
    function checkLastPost(){
      if ($this->shouldNotify($this->getFeedInfo())){
        if (SEND_EMAIL)
        {
          echo "sending email \r\n";
          $this->sendEmail(TO_EMAIL_ADDRESS);
        }
        if (SEND_TEXT)
        {
          echo "sending text \r\n";
          $this->sendText();
        }
      }
    }
    
    function getFeedInfo(){
      $ret_obj = $this->facebook->api($this->pageFBId.'/feed');
      return $data = $ret_obj['data'];
    }
    
    function shouldNotify(&$data){
      
      if ($data){
        $id = $this->getPostId($data[0]);
        $message = $data[0]["message"];
        if ($message && $id > $this->lastPostId)
        {
          $this->lastPostId = $id;
          $this->message = $message;
          echo "updated last postId to $id\r\n";
          return true;        
        }
      }
      return false;
    }
    
    function getPostId(&$post)
    {
      $pieces = explode("_", $post['id']);
      $id = $pieces[1];
      return $id;
    }
    
    function sendEmail($address){
      $mail = Mail::factory("mail");

      $subject = "Page ".$this->pageFBId." changed!";

      $headers = array(
        "From"=>FROM_EMAIL_ADDRESS, 
        "Subject"=>$subject);
      
      $body = $this->message;
      
      $mail->send($address, $headers, $body);
    }
    
    function sendText(){
      $this->sendEmail(CARRIER_TEXT_EMAIL);
    }
}