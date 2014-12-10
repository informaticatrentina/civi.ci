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
          'status' => 0,
          'source' => CIVICO
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
        'salute_text' => Yii::t('discussion', 'Good Morning'),
        'firstname' => $user['firstname'],
        'lastname' => $user['lastname'],
        'activation_link' => BASE_URL . 'user/activate?' . http_build_query($param),
        'regards' => Yii::t('discussion', 'Regards'),
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
    $html = str_replace("{{salute_text}}", $userInfo['salute_text'], $html);
    $html = str_replace("{{user_name}}", $userInfo['firstname'] . ' ' . $userInfo['lastname'] , $html);
    $mailText = Yii::t('discussion', 'Thank you for registering. Please activate your account by clicking this {start_ahref_link} link {end_ahref_link}  or copy and paste the link below and follow the instructions.',
       array('{start_ahref_link}' => '<a href="' . $userInfo['activation_link'] . '" target="_blank">', '{end_ahref_link}' => '</a>' ));
    $html = str_replace("{{mail_text_description}}", $mailText, $html);
    $html = str_replace("{{activation_link}}", $userInfo['activation_link'], $html);
    $html = str_replace("{{regards}}", $userInfo['regards'], $html);
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
        if ($now > $time_out) {
          $message = Yii::t("discussion", "We are sorry, this link has expired. You will need to sign up again.");
        } else if ($secret_key != $encrypted_secret_key) {
          $message = Yii::t("discussion", "This is not a valid link.");
        } else {
          //update in identity manager
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
              'status' => '1',
              'id' => $userId
          );
          $updateUser = $userIdentityApi->curlPut(IDM_USER_ENTITY, $inputParam);
          if (array_key_exists('_status', $updateUser) && $updateUser['_status'] == 'OK') {
            $verified = TRUE;
            $message = Yii::t("discussion", "Your account has been activated. Please login");
          } else {
            throw new Exception('Failed to update status as active for email ' . $email);
          }
        }
      } else { 
        $message = Yii::t("discussion", "We are sorry, the page you are looking for seems to be missing.");
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in actionActivateUser');
      $message = Yii::t('discussion', 'Some technical problem occurred, contact administrator');
    }
    $this->render('activation', array('message' => $message, 'verified' => $verified));
  }

  /**
   * saveAdditionalInfo
   * function is used for additional information
   */
  public function actionSaveAdditionalInfo() {
    try {
      if (!isUserLogged()) {
        $this->redirect(BASE_URL);
      }
      //check flag whether this page is shown or not
      if (!array_key_exists('show_question_page', Yii::app()->session['user'])) {
        $this->redirect(BASE_URL);
      }
      $this->setHeader('2.0');
      $message = '';
      $postData = array();
      Yii::app()->clientScript->registerScriptFile(THEME_URL . 'js/' . 'loginQuestion.js', CClientScript::POS_END);
      $additionalInformation = array();
      $additionalInformationQuestion = json_decode(ADDITIONAL_INFORMATION, TRUE);
      $definedQuestion = Yii::app()->globaldef->params['user_additional_info_question'];
      if (!empty($definedQuestion)) {
        $definedQuestion = explode(',', $definedQuestion);;
        $definedQuestion = array_map('trim', $definedQuestion);
        foreach ($additionalInformationQuestion as $key => $question) {
          if (in_array($key, $definedQuestion)) {
            $additionalInformation[$key] = $question;
          }
        }
      }
      if (isModuleExist('backendconnector') == false) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
      }
      $postData = array_map('trim', $_POST);
      if (!empty($postData)) {
        if (array_key_exists('age_range', $postData)) {
          if (!empty($postData['age_range'])) {
            $userInfo['age-range'] = $postData['age_range'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please select age range'));
          }
        }
        if (array_key_exists('age', $postData)) {
          if (!empty($postData['age'])) {
            if (!preg_match('/^[0-9]*$/', $postData['age'])) {
              throw new Exception(Yii::t('discussion', 'Please provide valid age'));
            }
            $userInfo['age'] = $postData['age'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please provide your age'));
          }
        }
        if (array_key_exists('gender', $postData)) {
          if (!empty($postData['gender'])) {
            $userInfo['sex'] = array($postData['gender']);
          } else {
            throw new Exception(Yii::t('discussion', 'Please select gender'));
          }
        }
        if (array_key_exists('education_level', $postData)) {
          if (!empty($postData['education_level'])) {
            $userInfo['education-level'] = $postData['education_level'];
            if ($postData['education_level'] == 'other') {
              if (array_key_exists('education_level_description', $postData) && !empty($postData['education_level_description'])) {
                $userInfo['education-level'] = $postData['education_level_description'];
              } else {
                throw new Exception(Yii::t('discussion', 'Please select education level'));
              }
            }
          } else {
            throw new Exception(Yii::t('discussion', 'Please select education level'));
          }
        }
        if (array_key_exists('citizenship', $postData)) {
          if (!empty($postData['citizenship'])) {
            $userInfo['citizenship'] = $postData['citizenship'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please select citizenship'));
          }
        }
        if (array_key_exists('work', $postData)) {
          if (!empty($postData['work'])) {
            $userInfo['work'] = $postData['work'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please select work'));
          }
        }
        if (array_key_exists('public_authority', $postData)) {
          if (!empty($postData['public_authority'])) {
            $userInfo['public-authority']['name'] = $postData['public_authority'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please select authority'));
          }
        }
        if (array_key_exists('authority_description', $postData)) {
          if (!empty($postData['authority_description'])) {
            $userInfo['public-authority']['text'] = $postData['authority_description'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please add authority description'));
          }
        }
        if (array_key_exists('profession', $postData)) {
          if (!empty($postData['profession'])) {
            $profileInfo[0]['name'] = 'profession';
            $profileInfo[0]['text'] = $postData['profession'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please provide your profession'));
          }
        }
        if (array_key_exists('residence', $postData)) {
          if (!empty($postData['residence'])) {
            $profileInfo[1]['name'] = 'residence';
            $profileInfo[1]['text'] = $postData['residence'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please provide your residence'));
          }
        }
        if (array_key_exists('association', $postData)) {
          if (!empty($postData['association'])) {
            $profileInfo[2]['name'] = 'association';
            $profileInfo[2]['text'] = $postData['association'];
            if ($postData['association'] == 'other') {
              if (array_key_exists('association_description', $postData) && !empty($postData['association_description'])) {
                $profileInfo[2]['text'] = $postData['association_description'];
              } else {
                throw new Exception(Yii::t('discussion', 'Please select association'));
              }
            }
          } else {
            throw new Exception(Yii::t('discussion', 'Please select association'));
          }
        }
        $userInfo['profile-info'] = $profileInfo;
        $im = new UserIdentityAPI();
        $userInfo['id'] = Yii::app()->session['user']['id'];
        $userInfo['last-login'] = time();
        $updateUser = $im->curlPut(IDM_USER_ENTITY, $userInfo);
        if (array_key_exists('_status', $updateUser) && $updateUser['_status'] == 'OK') {
          unset($_SESSION['user']['show_question_page']);
          $redirectUrl = BASE_URL;
          if (isset(Yii::app()->session['user']['back_url']) && !empty(Yii::app()->session['user']['back_url'])) {
            $redirectUrl = Yii::app()->session['user']['back_url'];
          }
          $this->redirect($redirectUrl);
        } else {
          throw new Exception(Yii::t('discussion', 'Some technical problem occurred, contact administrator'));
        }
      } else {
        $userIdentityApi = new UserIdentityAPI();
        $userInfo = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => trim(Yii::app()->session['user']['email'])), false, false);
        if (array_key_exists('_items', $userInfo) && array_key_exists(0, $userInfo['_items'])) {
          $userInfo = $userInfo['_items'][0];
          if (array_key_exists('age-range', $userInfo)) {
            $postData['age_range'] = $userInfo['age-range'];
          }
          if (array_key_exists('age', $userInfo)) {
            $postData['age'] = $userInfo['age'];
          }
          if (array_key_exists('sex', $userInfo)) {
            $postData['gender'] = $userInfo['sex'][0];
          }
          if (array_key_exists('education-level', $userInfo)) {
            $postData['education_level'] = $userInfo['education-level'];
	          if (!array_key_exists($userInfo['education-level'], $additionalInformation['education_level']['value'])) {
              $postData['education_level'] =  'other';
              $postData['education_level_description'] = $userInfo['education-level'];
            }
          }
          if (array_key_exists('citizenship', $userInfo)) {
            $postData['citizenship'] = $userInfo['citizenship'];
          }
          if (array_key_exists('work', $userInfo)) {
            $postData['work'] = $userInfo['work'];
          }
          if (array_key_exists('public-authority', $userInfo)) {
            $postData['public_authority'] = $userInfo['public-authority']['name'];
            $postData['authority_description'] = $userInfo['public-authority']['text'];
          }
          if (array_key_exists('profile-info', $userInfo)) {
            if (array_key_exists('0', $userInfo['profile-info'])) {
              $postData['profession'] = $userInfo['profile-info'][0]['text'];
            }
          }
          if (array_key_exists('profile-info', $userInfo)) {
            if (array_key_exists('1', $userInfo['profile-info'])) {
              $postData['residence'] = $userInfo['profile-info'][0]['text'];
            }
          }
          if (array_key_exists('profile-info', $userInfo)) {
            if (array_key_exists('2', $userInfo['profile-info'])) {
              $postData['association'] = $userInfo['profile-info'][0]['text'];
            }
          }
        }
      }
    } catch (Exception $e) {
      $message = $e->getMessage();
      Yii::log($e->getMessage(), ERROR, 'Error in actionSaveAdditionalInfo');
    }
    $this->render('loginQuestion', array('additional_info' => $additionalInformation, 'message' => $message, 'post_data' => $postData));
  }

  /**
   * forgotPassword
   * funcion is used for getting forgot password
   */
  public function actionForgotPassword() {
    try {
      $response = array('status' => FALSE, 'msg' => '', 'data' => '');
      $this->layout = 'userManager';
      Yii::app()->clientScript->registerScriptFile(THEME_URL . 'js/' . 'forgotPassword.js', CClientScript::POS_END);
      if (!empty($_POST)) {
        $email = trim($_POST['email']);
        $response['data'] = $email;
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
          throw new Exception(Yii::t('discussion', 'Please enter a valid email'));
        }
        if (isModuleExist('backendconnector') == false) {
          throw new Exception(Yii::t('discussion', 'backendconnector module is missing'));
        }
        $module = Yii::app()->getModule('backendconnector');
        if (empty($module)) {
          throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
        }
        $userIdentityApi = new UserIdentityAPI();
        $userInfo = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => $email), false, false);
        if (array_key_exists('_items', $userInfo)) {
          if (!array_key_exists(0, $userInfo['_items'])) {
            throw new Exception(Yii::t('discussion', 'User does not exit in system'));
          }
          $userInfo = $userInfo['_items'][0];
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
              'salute_text' => Yii::t('discussion', 'Good Morning'),
              'firstname' => $userInfo['firstname'],
              'lastname' => $userInfo['lastname'],
              'activation_link' => BASE_URL . 'user/change-password?' . http_build_query($param),
              'regards' => Yii::t('discussion', 'Regards')
          );
          $body = $this->_prepareForgotPasswordMailBody($userInfo);
          $subject = Yii::t('discussion', 'Reset your password');
          $console = new BackgroundConsoleRunner('index-cli.php');
          $subject = str_replace("'", "$3#$", $subject);
          $body = str_replace("'", "$3#$", $body);
          $args = "sendmail '$subject'  '$body' 'forgot_password_mail' '$email'";
          $console->run($args);
          $response['status'] = TRUE;
          $response['msg'] = Yii::t('discussion', 'Please check your mailbox for reset password');
        }
      }
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
      Yii::log($e->getMessage(), 'ERROR', 'Error in actionForgotPassword');
    }
    $this->render('forgotPassword', array('response' => $response));
  }

  /**
   * _prepareForgotPasswordMailBody
   * This funtion is used to create email body for forgot password mail
   * @param  array $userInfo - data for mail
   * @return string $html
   */
  private function _prepareForgotPasswordMailBody($userInfo) {
    $html = '';
    $html = file_get_contents(Yii::app()->theme->basePath . '/views/user/forgotPasswordEmail.html');
    $html = str_replace("{{salute_text}}", $userInfo['salute_text'], $html);
    $html = str_replace("{{user_name}}", $userInfo['firstname'] . ' ' . $userInfo['lastname'] , $html);
    $mailText = Yii::t('discussion', 'Please reset your password by clicking this {start_ahref_link} link {end_ahref_link} or copy and paste the link below and follow the instructions.',
      array('{start_ahref_link}' => '<a href="' . $userInfo['activation_link'] . '" target="_blank">', '{end_ahref_link}' => '</a>' ));
    $html = str_replace("{{mail_text_description}}", $mailText, $html);
    $html = str_replace("{{activation_link}}", $userInfo['activation_link'], $html);
    $html = str_replace("{{regards}}", $userInfo['regards'], $html);
    return $html;
  }

  /**
   * actionChangePassword
   * function is used for change password
   */
  public function actionChangePassword() {
    try {
      $this->setHeader('2.0');
      Yii::app()->clientScript->registerScriptFile(THEME_URL . 'js/' . 'changePassword.js', CClientScript::POS_END);
      $userId = '';
      $response = array('status' => FALSE, 'msg' => '', 'data' => array('change_password_html' => TRUE));
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
      }
      $userIdentityApi = new UserIdentityAPI();
      if (!empty($_POST)) {
        if (empty($_POST['new_password'])) {
          throw new Exception(Yii::t('discussion', 'Please enter new password'));
        }
        if (empty($_POST['confirm_password'])) {
          throw new Exception(Yii::t('discussion', 'Please enter confirm password'));
        }
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
          throw new Exception(Yii::t('discussion', 'Password does not match'));
        }
        $userId =  $_POST['user_id'];
        $inputParam = array(
          'password' => $_POST['new_password'],
          'id' => $userId
        );
        $updateUser = $userIdentityApi->curlPut(IDM_USER_ENTITY, $inputParam);
        if (array_key_exists('_status', $updateUser) && $updateUser['_status'] == 'OK') {
          $response['status'] = TRUE;
          $response['msg']  = Yii::t("discussion", "Your password has been changed. Please login");
        } else {
          throw new Exception(Yii::t('discussion', 'Some technical problem occurred, contact administrator'));
        }
      } else if (array_key_exists('u1', $_GET) && array_key_exists('u2', $_GET) && array_key_exists('u3', $_GET)) {
        $response['data']['change_password_html'] = TRUE;
        $secret_key = $_GET['u1'];
        $encrypted_email = $_GET['u2'];
        $email = decryptDataString($encrypted_email);
        $time_out = $_GET['u3'];
        $encrypted_secret_key = getRegistrationtKey($email, $time_out);
        $now = time();
        if ($now > $time_out) {
          $response['data']['change_password_html'] = FALSE;
          throw new Exception(Yii::t("discussion", "We are sorry, this link has expired. You will need to sign up again."));
        }
        if ($secret_key != $encrypted_secret_key) {
          $response['data']['change_password_html'] = FALSE;
          throw new Exception(Yii::t("discussion", "This is not a valid link."));
        }
        //update in identity manager
        $userInfo = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => trim($email)), false, true);
        $userId = '';
        if (array_key_exists('_items', $userInfo) && array_key_exists(0, $userInfo['_items']) && array_key_exists('_id', $userInfo['_items'][0])) {
          $userId = $userInfo['_items'][0]['_id'];
        }
        if (empty($userId)) {
          $response['data']['change_password_html'] = FALSE;
          throw new Exception(Yii::t('discussion', 'User does not exit in system'));
        }
      } else {
        $response['data']['change_password_html'] = FALSE;
        throw new Exception(Yii::t("discussion", "We are sorry, the page you are looking for seems to be missing."));
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in actionChangePassword');
      $response['msg'] = $e->getMessage();
    }
    $this->render('changePassword', array('response' => $response, 'user_id' => $userId));
  }

  /**
   * actionSaveAdditinalInformationQuestion
   * function is ued for save additional information question in database
   */
  public function actionSaveAdditinalInformationQuestion() {
    if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
      $this->actionError();
      exit;
    }
    $response = array('status' => FALSE, 'msg' => '');
    if (array_key_exists('additinal_user_info_question', $_POST) && !empty($_POST['additinal_user_info_question'])) {
      $question = implode(', ', $_POST['additinal_user_info_question']);
      $config = new Configuration();
      $config->key = 'user_additional_info_question';
      $config->value = $question;
      $configurations = $config->save();
      if (is_int($configurations)) {
        $response['status'] = TRUE;
        $response['msg'] = Yii::t('discussion', 'Question has been saved successfully');
      }
    } else {
      $response['msg'] = Yii::t('discussion', 'Please select question');
    }
    echo json_encode($response);
    exit;
  }
}
?>
