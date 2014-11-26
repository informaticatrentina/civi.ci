<?php

/**
 * UserController
 * 
 * UserController class inherit pageController for including header .
 * Actions are defined in UserController.
 * User controller is used for user related functionality - registration,
 * Copyright (c) 2014 <ahref Foundation -- All rights reserved.
 * Author: Pradeep Kumar<pradeep@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
  <ahref Foundation.
 */
class UserController extends PageController {

  /**
   * init
   * function is used for set basic configuration and theme setting
   */
  public function init() {
    if (!defined('SITE_THEME')) {
      p('Site theme is not defined. Please define it in local config file');
    } else {
      $config = new Configuration;
      $data = $config->get();
      foreach ($data as $configration) {
        Yii::app()->globaldef->params[$configration['name_key']] = htmlspecialchars_decode($configration['value']);
      }
      Yii::app()->theme = SITE_THEME;
    }
  }

  public function beforeAction($action) {
    new JsTrans('js', SITE_LANGUAGE);
    return true;
  }

  /**
   * actionRegister
   * this function is used for register new user 
   * this function also set the user information in session and redirect to user
   * on same page from where he made a request for registration
   */
  public function actionRegister() {
    try {
      $saveUser = array('success' => false, 'msg' => '');
      $backUrl = BASE_URL;
      $user = array_map('trim', $_POST);
      if (!empty($user)) {
        if (empty($user['firstname'])) {
          throw new Exception(Yii::t('discussion', 'Please enter first name'));
        }
        if (empty($user['lastname'])) {
          throw new Exception(Yii::t('discussion', 'Please enter last name'));
        }
        if (empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
          throw new Exception(Yii::t('discussion', 'Please enter a valid email'));
        }
        if (empty($user['password'])) {
          throw new Exception(Yii::t('discussion', 'Please enter password'));
        }       
        if (empty($user['confirm_password'])) {
          throw new Exception(Yii::t('discussion', 'Please enter confirm password'));
        }
        if ($user['password'] !== $user['confirm_password']) {
          throw new Exception(Yii::t('discussion', 'Password does not match'));
        }
        if (!array_key_exists('terms_and_condition', $user)) {
          throw new Exception(Yii::t('discussion', 'Please check term and condition checkbox'));
        }
        if (!array_key_exists('privacy_policy', $user)) {
          throw new Exception(Yii::t('discussion', 'Please check privacy policy checkbox'));
        }
        $userDetail = array(
          'firstname' => $user['firstname'],
          'lastname' => $user['lastname'],
          'email' => $user['email'],
          'password' => $user['password'],
          'status' => 0
        );
        if (!empty($_GET['back'])) {
          $back = substr($_GET['back'], 1);
          if (!empty($back)) {
            $backUrl = BASE_URL . substr($_GET['back'], 1);
          }
        }
        $module = Yii::app()->getModule('backendconnector');
        if (empty($module)) {
          throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
        }
        $userIdentityMgr = new UserIdentityManager();
        $saveUser = $userIdentityMgr->createUser($userDetail);
        if (array_key_exists('success', $saveUser) && $saveUser['success'] == true) {
          $this->_sendActivationMail($user);
          $saveUser['msg'] = Yii::t('discussion', 'You have been successfully registered');
        } else {
          $saveUser['msg'] = Yii::t('discussion', $saveUser['msg']);
        }
      }
    } catch (Exception $e) {
      $saveUser['msg'] = $e->getMessage();
      Yii::log($e->getMessage(), ERROR, 'Error in actionRegister method');
    }
    $this->layout = 'userManager';
    $js = Yii::app()->getClientScript();
    $js->registerScriptFile(THEME_URL . 'js/userRegistration.js', CClientScript::POS_END);
    $this->render('registration', array('message' => $saveUser, 'back_url' => $backUrl, 'user' => $user));
  }

  /**
   * actionExportUser
   * function is used for export all user  who have submitted proposal, opinions
   * and links
   * It creates an xls file containg user email id and content type submitted by user
   * @author Pradeep Kumar<pradeep@incaendo.com>
   */
  public function actionExportUser() {
    try {
      $isAdmin = checkPermission('admin');
      if ($isAdmin == false) {
        $this->redirect(BASE_URL);
      }
      $userIds = $this->_getAllContributorsId();
      if (empty($userIds)) {
        throw new Exception('Content is not submitted by user');
      }
      $uniqueUserId = array_unique(array_merge($userIds['Proposal'], $userIds['Opinion'], $userIds['Link']));
      $userEmail = $this->_getContributorsEmail($uniqueUserId);
      if (empty($userEmail)) {
        throw new Exception('User email id is a blank array');
      }
      $header = array( Yii::t('discussion', 'Emails'),
                       Yii::t('discussion','Content Submitted By Contributor'));
      foreach ($userEmail as $id => $email) {
        $content = array();
        if (in_array($id, $userIds['Proposal'])) {
          $content[] = 'Proposal';
        }
        if (in_array($id, $userIds['Opinion'])) {
          $content[] = 'Opinion';
        }
        if (in_array($id, $userIds['Link'])) {
          $content[] = 'Link';
        }
        $contents = '';
        if (!empty($content)) {
          $contents = implode(', ', $content);
        }
        $rows[] = array($email, $contents);
      }
      createXlsFile('contributors.xls', $header, $rows);
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in actionExportUser');
    }
    exit;
  }

  /**
   * _getAllContributorsId
   * function is used for getting author slug who have submitted proposal, opinions
   * and links
   * @param void
   * @return array $contributorIds - id (slug) of contributor (user)
   */
  private function _getAllContributorsId() {
    try {
      $contributorIds = array('Proposal' => array(), 'Opinion' => array(), 'Link' => array());
      $aggregatorManager = new AggregatorManager();
      $proposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', '', '', '', '',
      '', '', '', '', '', array(), '', 'title,tags,author', '', '', '', CIVICO);
      foreach ($proposals as $proposal) {
        if (array_key_exists('tags', $proposal) && !empty($proposal['tags'])) {
          foreach ($proposal['tags'] as $tag) {
            $content = '';
            switch ($tag['scheme']) {
              case PROPOSAL_TAG_SCEME:
                $content = 'Proposal';
                break;
              case OPINION_TAG_SCEME:
                $content = 'Opinion';
                break;
              case LINK_TAG_SCEME:
                $content = 'Link';
                break;
              default :
                $content = '';
                break;
            }
            if (!empty($content)) {
              if (array_key_exists('author', $proposal) && array_key_exists('slug',
                $proposal['author']) && !empty($proposal['author']['slug'])) {
                if (!in_array($proposal['author']['slug'], $contributorIds[$content])) {
                  $contributorIds[$content][] = $proposal['author']['slug'];
                }
              }
              break;
            }
          }
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in _getAllContributors');
    }
    return $contributorIds;
  }

  /**
   * _getContributorsEmail
   * function is used for getting author email who have submitted proposal, opinions
   * and links on the basis of contributors id (id)
   * @param array $contributorsId
   * @return array $contributorEmail - email id of contributor (user)
   */
  private function _getContributorsEmail($contributorsId) {
    try {
      $userEmail = array();
      if (empty($contributorsId)) {
        return $userEmail;
      }
      $identityManager = new UserIdentityAPI();
      $authorsEmails = $identityManager->getUserDetail(IDM_USER_ENTITY, array('id' =>
      $contributorsId), true, false);
      if (array_key_exists('_items', $authorsEmails) && !empty($authorsEmails['_items'])) {
        foreach ($authorsEmails['_items'] as $authorEmail) {
          if (array_key_exists('_id', $authorEmail) && !empty($authorEmail['_id'])
            && array_key_exists('email', $authorEmail) && !empty($authorEmail['email'])) {
            $userEmail[$authorEmail['_id']] = $authorEmail['email'];
          }
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in _getContributorsEmail');
    }
    return $userEmail;
  }
  
  /**
   * _sendActivationMail
   * function is used for sending activation email in background
   * @param array $user - user information
   * @return void
   */
  private function _sendActivationMail($user) {
    try {
      $email = $user['email'];
      $encrypted_email = encryptDataString($email);
      $now = time();
      $time_out = $now + ACTIVATION_LINK_TIME_OUT;
      $key = getRegistrationtKey($email, $time_out);
      $param = array(
        'u1' => $key,
        'u2' => $encrypted_email,
        'u3' => $time_out,
      );
      $userInfo = array(
        'firstname' => $user['firstname'],
        'lastname' => $user['lastname'],
        'activation_link' => BASE_URL . 'user/activate?' . http_build_query($param),
      );
      $body = $this->_prepareMailBody($userInfo);
      $subject = Yii::t('discussion', 'Verify your account');
      $console = new BackgroundConsoleRunner('index-cli.php');
      $subject = str_replace("'", "$3#$", $subject);
      $body = str_replace("'", "$3#$", $body);
      $args = "sendmail '$subject'  '$body' 'registeration_activation_mail' '$email'";
      $console->run($args);
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in _sendActivationMail');
    }
  }

  /**
   * _prepareMailBody
   * This funtion is used to create email body for activation mail
   * @param  array $userInfo - data for mail
   * @return string $html
   */
  private function _prepareMailBody($userInfo) {
    $html = '';
    $html = file_get_contents(Yii::app()->theme->basePath . '/views/user/activationEmail.html');
    $html = str_replace("{{user_name}}", $userInfo['firstname'] . ' ' . $userInfo['lastname'] , $html);
    $mailText = Yii::t('discussion', 'Thank you for registering. Please activate your account by clicking
      this {start_ahref_link} link {end_ahref_link}  or copy and paste the link below and follow the instructions.',
       array('{start_ahref_link}' => '<a href=' . $userInfo['activation_link'] . 'target="_blank">', '{end_ahref_link}' => '</a>' ));
    $html = str_replace("{{mail_text_description}}", $mailText, $html);
    $html = str_replace("{{activation_link}}", $userInfo['activation_link'], $html);
    return $html;
  }
  
  /**
   * actionActivateUser
   * function is used for activate by using activation link
   */
  public function actionActivateUser() {
    try {
      $this->setHeader('2.0');
      $message = '';
      $verified = FALSE;
      if (array_key_exists('u1', $_GET) && array_key_exists('u2', $_GET) && array_key_exists('u3', $_GET)) {
        $secret_key = $_GET['u1'];
        $encrypted_email = $_GET['u2'];
        $email = decryptDataString($encrypted_email);
        $time_out = $_GET['u3'];
        $encrypted_secret_key = getRegistrationtKey($email, $time_out);
        $now = time();
        if ($now <= $time_out) {
          Yii::t("frontend", "We are sorry, this link has expired. You will need to sign up again.");
        }
        if ($secret_key != $encrypted_secret_key) {
          Yii::t("frontend", "This is not a valid link.");
        }
        //update in identity manager
        $userIdentityApi = new UserIdentityAPI();
        $module = Yii::app()->getModule('backendconnector');
        if (empty($module)) {
          throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
        }
        $userIdentityApi = new UserIdentityAPI();
        $userInfo = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => trim($email)), false, true);
        $userId = '';
        if (array_key_exists('_items', $userInfo) && array_key_exists(0, $userInfo['_items']) && array_key_exists('_id', $userInfo['_items'][0])) {
          $userId = $userInfo['_items'][0]['_id'];
        }
        if (empty($userId)) {
          throw new Exception('User id is empty for email ' . $email);
        }
        $inputParam = array(
          'status' => 1,
          'id' => $userId
        );
        $updateUser = $userIdentityApi->curlPut(IDM_USER_ENTITY, $inputParam);
        if (array_key_exists('_status', $updateUser) && $updateUser['_status'] == 'OK') {
          $verified = TRUE;
          $message = Yii::t("frontend", "Your account has been activated. Please login");
        } else {
          throw new Exception('Failed to update status as active for email ' . $email);
        }
      } else { 
        $message = Yii::t("frontend", "We are sorry, the page you are looking for seems to be missing.");
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in actionActivateUser');
      $message = Yii::t('discussion', 'Some technical problem occurred, contact administrator');
    }
    $this->render('activation', array('message' => $message, 'verified' => $verified));
  }

}
?>
