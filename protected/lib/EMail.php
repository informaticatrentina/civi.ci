<?php

/**
 * Email
 * 
 * Email class is used for send mail.
 * Copyright (c) 2014 <ahref Foundati on -- All rights reserved.
 * Author: Kuldeep Singh <kuldeep@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of <ahref Foundation.
 */
include('PhpMailer/class.phpmailer.php');

class EMail {

  public $to = array();
  public $cc = array();
  public $bcc = array();
  public $from = DEFAULT_EMAIL;
  public $subject;
  public $body;
  public $attachment = array();
  public $fromName = '';
   
  /**
   * sendMail
   * This function is used to send mail.
   * It can send mail through 'sendmail' and 'smtp'.
   * To send through sendmail set constant ENABLE_SENDMAIL value = 1 else for smtp set 0.
   * If sending through smtp set smtp constants also in local_config file.
   * @return array
   * @throws Exception
   */
  public function sendMail() {
    $sendMail = array('success' => false, 'msg'=>'');
    try {
      $mail = new PHPMailer;
      $mail->CharSet = 'UTF-8';
      if (empty($this->subject)) {
        throw new Exception('subject can not be empty');
      }
      if (count($this->to) == 0) {
        throw new Exception('please specify reciepient');
      }
      $mail->Sender = $this->from;
      $mail->From = $this->from;
      $mail->FromName = $this->fromName;
      $toString = implode(',', $this->to);
      $ccString = implode(',', $this->cc);
      $bccString = implode(',', $this->bcc);
      $mail->AddAddress($toString);
      if (!empty($ccString)) {
        $mail->AddCC($ccString);
      }
      if (!empty($bccString)) {
        $mail->AddBCC($bccString);
      }
      $mail->isHTML(true);
      $mail->Subject = $this->subject;
      $mail->Body = $this->body;
      
      $fileName = '';
      if (!empty($this->attachment)) {
        foreach ($file as $files) {
          $fileName = end(explode('/', $files));
          $mail->AddAttachment($files, $fileName, 1);
        }
      }
      if (defined('ENABLE_SENDMAIL') && ENABLE_SENDMAIL == 1) {
        $mail->IsSendmail();
      } else if(ENABLE_SENDMAIL == 0) {
        $mail->IsSMTP();
        if (defined('SMTP_HOST')) {
          $mail->Host = SMTP_HOST;
        }
        if (defined('SMTP_PORT')) {
          $mail->Port = SMTP_PORT;
        }
        if (defined('SMTP_AUTH')) {
          $mail->SMTPAuth = SMTP_AUTH;
        }
        if (defined('SMTP_USER')) {
          $mail->Username = SMTP_USER;
        }
        if (defined('SMTP_PASSWORD')) {
          $mail->Password = SMTP_PASSWORD;
        }
        if (defined('SMTP_SECURE') && SMTP_SECURE != '') {
          $mail->SMTPSecure = SMTP_SECURE;
        }
      } else {
        Yii::log('Error in sendMail', ERROR, 'ENABLE_SENDMAIL constant is not defined.');
        throw new Exception('Please set ENABLE SENDMAIL constant as 0 or 1 only.');
      }
      if ($mail->Send()) {
        $sendMail['success'] = true;
      }
    } catch (Exception $e) {
      Yii::log('Error in sendMail', ERROR, $e->getMessage());
      $sendMail['msg'] = $e->getMessage();
    }
    return $sendMail;
  }
}