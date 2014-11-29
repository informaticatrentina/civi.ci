<?php

/**
 * SendMail
 * 
 * This class is used for sending mail to moderator.
 * This class will used discussion model for getting moderator list.
 * Copyright (c) 2014 <ahref Foundation -- All rights reserved.
 * Author: Pradeep Kumar<pradeep@incaendo.com>
 * This file is part of <civico>.
 * This file can not be copied and/or distributed without the express permission of
  <ahref Foundation.
 */
class SendMailCommand extends CConsoleCommand {

  public function run($args) {
    try {
      if (!array_key_exists(0, $args) || empty($args[0])) {
        throw new Exception('Subject can not be empty');
      }
      if (!array_key_exists(1, $args) || empty($args[1])) {
        throw new Exception('Body can not be empty');
      }
      $subject = $args[0];
      $subject = str_replace("$3#$", "'", $subject);
      $subject = html_entity_decode($subject);
      $body = $args[1];
      $body = str_replace("$3#$", "'", $body);
      $email = new EMail();
      $email->subject = $subject;
      $email->from = 'noreply@civiclinks.it';
      if (defined('DEFAULT_EMAIL')) {
        $email->from = DEFAULT_EMAIL;
      }
      $email->body = htmlspecialchars_decode($body);
      $moderatorEmails = '';
      if (array_key_exists(2, $args) && $args[2] == 'registeration_activation_mail') {
        if (!array_key_exists(3, $args) || empty($args[3])) {
          throw new Exception('Email can not be empty');
        }
        $moderatorEmails = $args[3];
      }
      if (empty($moderatorEmails)) {
        $config = new Configuration();
        $configurations = $config->get();
        foreach ($configurations as $confiuration) {
          if ($confiuration['name_key'] == 'moderators_email') {
            $moderatorEmails = $confiuration['value'];
          }
        }
      }
      if (!empty($moderatorEmails)) {
        $moderatorsEmail = explode(',', $moderatorEmails);
        foreach ($moderatorsEmail as $emailId) {
          $email->to = array($emailId);
          $emailResponse = $email->sendMail();
          if (array_key_exists('success', $emailResponse) && $emailResponse['success'] == false) {
            Yii::log('Failed to send mail :' . $emailId, 'error', 'Error in sending mail command');
          }
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), 'error', 'Error in SendMailCommand');
    }
  }
}
