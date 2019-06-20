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
 * <ahref Foundation.
 */
class UserController extends PageController
{

  /**
   * init
   * function is used for set basic configuration and theme setting
   */
  public function init()
  {
    if (!defined('SITE_THEME')) {
      p('Site theme is not defined. Please define it in local config file');
    } else {
      $config = new Configuration;
      $config->type = 'config';
      $data = $config->get();
      foreach ($data as $configration) {
        Yii::app()->globaldef->params[$configration['name_key']] = htmlspecialchars_decode($configration['value']);
        Yii::app()->globaldef->params['last_modified'][$configration['name_key']] = $configration['last_modified'];
      }
      Yii::app()->theme = SITE_THEME;
    }
  }

  /**
   * Method called before any specific ation call
   *
   * @property beforeAction
   *
   * @param $action
   *
   * @return bool always true
   */
  public function beforeAction($action)
  {
    new JsTrans('js', SITE_LANGUAGE);
    return true;
  }

  /**
   * actionRegister
   * this function is used for register new user
   * this function also set the user information in session and redirect to user
   * on same page from where he made a request for registration
   */
  public function actionRegister()
  {
    try {

      $nickname_enable = Yii::app()->globaldef->params['enable_nickname_use'];

      if ($nickname_enable  == "1") {
        $user['nickname_enable'] = "1";
      }



      $saveUser = array('success' => false, 'msg' => '');
      $backUrl = BASE_URL;
      $user = array_map('trim', $_POST);
      if (!empty($user)) {
        if (!empty($user['registration-type'])) {
          if (empty($user['firstname'])) {
            throw new Exception(Yii::t('discussion', 'Please enter first name'));
          }
          if ($user['registration-type'] == 'user') {
            if (empty($user['lastname'])) {
              throw new Exception(Yii::t('discussion', 'Please enter last name'));
            }
            if (empty($user['nickname']) && $user['nickname_enable'] == "1") {
              throw new Exception(Yii::t('discussion', 'Please enter nickname'));
            }
          } else if ($user['registration-type'] == 'org') {
            $user['lastname'] = ' ';
          }
          if (empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception(Yii::t('discussion', 'Please enter a valid email'));
          }
          if (empty($user['cemail'])) {
            throw new Exception(Yii::t('discussion', 'Please enter same email'));
          }
          if ($user['email'] != $user['cemail']) {
            throw new Exception(Yii::t('discussion', 'Please enter same email'));
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
          if (!array_key_exists('gdpr_policy', $user)) {
            throw new Exception(Yii::t('discussion', 'Please check GDPR privacy checkbox'));
          }

          $now = new \DateTime();
          $now->setTimezone(new \DateTimeZone('Europe/Rome'));

          $userDetail = array(
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'email' => $user['email'],
            'password' => $user['password'],
            'status' => 0,
            'gdpr' => 1,
            'gdpr_date' => $now->format('Y-m-d H:i:s'),
            'type' => $user['registration-type'],
            'source' => CIVICO
          );
          if ($user['registration-type'] == 'user') {
            $userDetail['nickname'] = $user['nickname'];
          }
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
          if (isset($saveUser['success']) && $saveUser['success'] == true) {
            $this->_sendActivationMail($user);
            $saveUser['msg'] = Yii::t('discussion', 'You have been successfully registered');
          } else {
            $saveUser['msg'] = Yii::t('discussion', $saveUser['msg']);
          }
        } else {
          throw new Exception(Yii::t('discussion', 'Please select a valid registration type'));
        }
      }
    } catch (Exception $e) {
      $saveUser['msg'] = $e->getMessage();
      Yii::log($e->getMessage(), ERROR, 'Error in actionRegister method');
    }

    $this->layout = 'userManager';
    if ($nickname_enable == "0") {
      $this->render('registration', array('message' => $saveUser, 'back_url' => $backUrl, 'user' => $user));
    } else {
      $this->render('registration_nickname', array('message' => $saveUser, 'back_url' => $backUrl, 'user' => $user));
    }
  }

  /**
   * actionExportUser
   * function is used for export all user  who have submitted proposal, opinions
   * and links
   * It creates an xls file containg user email id and content type submitted by user
   * @author Pradeep Kumar<pradeep@incaendo.com>
   */
  public function actionExportUser()
  {
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
      $header = array(
        Yii::t('discussion', 'Emails'),
        Yii::t('discussion', 'Content Submitted By Contributor')
      );
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
  private function _getAllContributorsId()
  {
    try {
      $contributorIds = array('Proposal' => array(), 'Opinion' => array(), 'Link' => array());
      $aggregatorManager = new AggregatorManager();
      $proposals = $aggregatorManager->getEntry(
        ALL_ENTRY,
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        array(),
        '',
        'title,tags,author',
        '',
        '',
        '',
        CIVICO
      );
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
              default:
                $content = '';
                break;
            }
            if (!empty($content)) {
              if (array_key_exists('author', $proposal) && array_key_exists(
                'slug',
                $proposal['author']
              ) && !empty($proposal['author']['slug'])) {
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
  private function _getContributorsEmail($contributorsId)
  {
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
          if (
            array_key_exists('_id', $authorEmail) && !empty($authorEmail['_id'])
            && array_key_exists('email', $authorEmail) && !empty($authorEmail['email'])
          ) {
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
  private function _sendActivationMail($user)
  {
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
  private function _prepareMailBody($userInfo)
  {
    $html = '';
    $html = file_get_contents(Yii::app()->theme->basePath . '/views/user/activationEmail.html');
    $html = str_replace("{{salute_text}}", $userInfo['salute_text'], $html);
    $html = str_replace("{{user_name}}", $userInfo['firstname'] . ' ' . $userInfo['lastname'], $html);
    $mailText = Yii::t(
      'discussion',
      'Thank you for registering. Please activate your account by clicking this {start_ahref_link} link {end_ahref_link}  or copy and paste the link below and follow the instructions.',
      array('{start_ahref_link}' => '<a href="' . $userInfo['activation_link'] . '" target="_blank">', '{end_ahref_link}' => '</a>')
    );
    $html = str_replace("{{mail_text_description}}", $mailText, $html);
    $html = str_replace("{{activation_link}}", $userInfo['activation_link'], $html);
    $html = str_replace("{{regards}}", $userInfo['regards'], $html);
    return $html;
  }

  /**
   * actionActivateUser
   * function is used for activate by using activation link
   */
  public function actionActivateUser()
  {
    //die('son giust');
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
          $userInfo = $userIdentityApi->getLastUserRegistered(IDM_USER_ENTITY, array('email' => trim($email)), false, true);

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

            // Scrivo file di log
            if (is_dir(Yii::app()->getRuntimePath())) {
              if (is_writable(Yii::app()->getRuntimePath())) {
                $now = new \DateTime();
                $now->setTimezone(new \DateTimeZone('Europe/Rome'));
                $filename = Yii::app()->getRuntimePath().'/activation.txt';
                file_put_contents($filename, $now->format('Y-m-d H:i:s') . ' ### ' . json_encode($inputParam) . PHP_EOL, FILE_APPEND);
              }
            }




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
  public function actionSaveAdditionalInfo()
  {
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
      $profileInfo = array();
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
        if (array_key_exists('sex', $postData)) {
          if (!empty($postData['sex'])) {
            $userInfo['sex'] = array($postData['sex']);
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
            $profileInfo['profession'] = $postData['profession'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please provide your profession'));
          }
        }
        if (array_key_exists('residence', $postData)) {
          if (!empty($postData['residence'])) {
            $profileInfo['residence'] = $postData['residence'];
          } else {
            throw new Exception(Yii::t('discussion', 'Please provide your residence'));
          }
        }
        if (array_key_exists('association', $postData)) {
          if (!empty($postData['association'])) {
            $profileInfo['association'] = $postData['association'];
            if ($postData['association'] == 'other') {
              if (array_key_exists('association_description', $postData) && !empty($postData['association_description'])) {
                $profileInfo['association'] = $postData['association_description'];
              } else {
                throw new Exception(Yii::t('discussion', 'Please select association'));
              }
            }
          } else {
            throw new Exception(Yii::t('discussion', 'Please select association'));
          }
        }
        if (!empty($profileInfo)) {
          $userInfo['profile-info'] = $profileInfo;
        }
        $im = new UserIdentityAPI();
        $userInfo['id'] = Yii::app()->session['user']['id'];
        $userInfo['last-login'] = time();
        $updateUser = $im->curlPut(IDM_USER_ENTITY, $userInfo);
        $userIdentityApi = new UserIdentityAPI();
        $userDetails = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => trim(Yii::app()->session['user']['email'])), false, false);
        $this->updateLastLoginTime($userDetails);
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
            $postData['sex'] = $userInfo['sex'][0];
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
            if (array_key_exists('profession', $userInfo['profile-info'])) {
              $postData['profession'] = $userInfo['profile-info']['profession'];
            }
          }
          if (array_key_exists('profile-info', $userInfo)) {
            if (array_key_exists('residence', $userInfo['profile-info'])) {
              $postData['residence'] = $userInfo['profile-info']['residence'];
            }
          }
          if (array_key_exists('profile-info', $userInfo)) {
            if (array_key_exists('association', $userInfo['profile-info']) && array_key_exists('association', $additionalInformation)) {
              $association = $userInfo['profile-info']['association'];
              $associationStoredValues = $additionalInformation['association']['value'];
              if (array_key_exists($association, $associationStoredValues)) {
                $postData['association'] = $association;
              } else {
                $postData['association'] = 'other';
                $postData['association_description'] = $association;
              }
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
  public function actionForgotPassword()
  {
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
  private function _prepareForgotPasswordMailBody($userInfo)
  {
    $html = '';
    $html = file_get_contents(Yii::app()->theme->basePath . '/views/user/forgotPasswordEmail.html');
    $html = str_replace("{{salute_text}}", $userInfo['salute_text'], $html);
    $html = str_replace("{{user_name}}", $userInfo['firstname'] . ' ' . $userInfo['lastname'], $html);
    $mailText = Yii::t(
      'discussion',
      'Please reset your password by clicking this {start_ahref_link} link {end_ahref_link} or copy and paste the link below and follow the instructions.',
      array('{start_ahref_link}' => '<a href="' . $userInfo['activation_link'] . '" target="_blank">', '{end_ahref_link}' => '</a>')
    );
    $html = str_replace("{{mail_text_description}}", $mailText, $html);
    $html = str_replace("{{activation_link}}", $userInfo['activation_link'], $html);
    $html = str_replace("{{regards}}", $userInfo['regards'], $html);
    return $html;
  }

  /**
   * actionChangePassword
   * function is used for change password
   */
  public function actionChangePassword()
  {
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
  public function actionSaveAdditinalInformationQuestion()
  {
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
      $config->type = 'config';
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

  /**
   * getUserAdditionalInfo
   * This function is used to get user additional user info.
   * @param array $authorIds
   * @return array
   * @throws Exception
   */
  public function getUserAdditionalInfo($authorIds)
  {
    try {
      $users = array();
      if (isModuleExist('backendconnector') == false) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
      }
      if (is_array($authorIds)) {
        $authorIds = array_unique($authorIds);
      }
      $userIdentityApi = new UserIdentityAPI();
      $emails = $this->_getContributorsEmail($authorIds);
      $question = json_decode(ADDITIONAL_INFORMATION, TRUE);
      if (!empty($emails)) {
        $userInfo = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => $emails));
        if (array_key_exists('_items', $userInfo)) {
          foreach ($userInfo['_items'] as $user) {
            if (array_key_exists('age', $user)) {
              $users[$user['_id']]['age'] = $user['age'];
            }
            if (array_key_exists('age-range', $user)) {
              $users[$user['_id']]['age_range'] = $user['age-range'];
            }
            if (
              array_key_exists('sex', $user) && array_key_exists(0, $user['sex'])
              && array_key_exists($user['sex'][0], $question['sex']['value'])
            ) {
              $users[$user['_id']]['sex'] = $question['sex']['value'][$user['sex'][0]];
            }
            if (
              array_key_exists('citizenship', $user)
              && array_key_exists($user['citizenship'], $question['citizenship']['value'])
            ) {
              $users[$user['_id']]['citizenship'] = $question['citizenship']['value'][$user['citizenship']];
            }
            if (
              array_key_exists('education-level', $user) &&
              array_key_exists($user['education-level'], $question['education_level']['value'])
            ) {
              $users[$user['_id']]['education_level'] = $question['education_level']['value'][$user['education-level']];
            }
            if (
              array_key_exists('work', $user)
              && array_key_exists($user['work'], $question['work']['value'])
            ) {
              $users[$user['_id']]['work'] = $question['work']['value'][$user['work']];
            }
            if (array_key_exists('public-authority', $user) && array_key_exists('name', $user['public-authority'])) {
              if (array_key_exists($user['public-authority']['name'], $question['public_authority']['value'])) {
                $users[$user['_id']]['public_authority'] = $question['public_authority']['value'][$user['public-authority']['name']];
              }
            }
            if (array_key_exists('profile-info', $user) && !empty($user['profile-info'])) {
              if (array_key_exists('profession', $user['profile-info'])) {
                $users[$user['_id']]['profession'] =  $user['profile-info']['profession'];
              }
              if (array_key_exists('residence', $user['profile-info'])) {
                $users[$user['_id']]['residence'] = $user['profile-info']['residence'];
              }
              if (
                array_key_exists('association', $user['profile-info']) &&
                array_key_exists('value', $question['association']) &&
                array_key_exists($user['profile-info']['association'], $question['association']['value'])
              ) {
                $users[$user['_id']]['association'] = $question['association']['value'][$user['profile-info']['association']];
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in getUserAdditionalInfo');
    }
    return $users;
  }

  /**
   * getAuthorEmail
   * function is used for getting author email on tthe basis of author id
   * @param array $authorId  - author slug
   * @param boolean $checkAdmin - if true then return admin user on admin key
   *   else it return all email on user key
   * @return array $userEmail
   */
  public function getAuthorEmail($authorIds, $checkAdmin = FALSE)
  {
    try {
      $userEmail = array('user' => array(), 'admin_user' => array());
      if (isModuleExist('rbacconnector') == false) {
        throw new Exception(Yii::t('discussion', 'rbacconnector module is missing'));
      }
      $module = Yii::app()->getModule('rbacconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'rbacconnector module is missing or not defined'));
      }
      if (is_array($authorIds)) {
        $authorIds = array_unique($authorIds);
      }
      $emails = $this->_getContributorsEmail($authorIds);
      if (!empty($emails) && $checkAdmin == TRUE) {
        foreach ($emails as $key => $email) {
          $isAdmin = User::checkPermission($email, 'is_admin');
          if ($isAdmin == TRUE) {
            $userEmail['admin_user'][$key] = $email;
          } else {
            $userEmail['user'][$key] = $email;
          }
        }
      } else {
        $userEmail['user'] = $emails;
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in getAuthorEmail ');
    }
    return $userEmail;
  }

  /**
   * showQuestionForm
   * function is used for check wether question form is shown or not
   * @param int $lastLoginTimestamp
   * @return boolean - TRUE if question form is to be shown
   */
  public function showQuestionForm($lastLoginTimestamp)
  {
    $showAdditionalQuestion = FALSE;
    if (
      isset(Yii::app()->globaldef->params['last_modified']['user_additional_info_question']) &&
      $lastLoginTimestamp < Yii::app()->globaldef->params['last_modified']['user_additional_info_question']
    ) {
      $showAdditionalQuestion = TRUE;
    }
    return $showAdditionalQuestion;
  }

  /**
   * updateLastLoginTime
   * function is used for updating last login time of user in identity manager
   * @param array $userInfo
   * @return void
   */
  public function updateLastLoginTime($userInfo)
  {
    $im = new UserIdentityAPI();
    $param = array(
      'id' => Yii::app()->session['user']['id'],
      'site-last-login' => array(CIVICO => time())
    );
    if (
      array_key_exists('_items', $userInfo) && array_key_exists(0, $userInfo['_items'])
      && array_key_exists('site-last-login', $userInfo['_items'][0])
    ) {
      $param['site-last-login'] = $userInfo['_items'][0]['site-last-login'];
      $param['site-last-login'][CIVICO] = time();
    }
    $updateUser = $im->curlPut(IDM_USER_ENTITY, $param);
    if (!array_key_exists('_status', $updateUser) || $updateUser['_status'] != 'OK') {
      Yii::log('Last login time is not updated.', ERROR, 'Error in action updateLastLoginTime');
    }
  }

  /**
   * actionCheckNickname
   * Method used to check whether the nick name is already present or not.
   *
   * @author Harsh <harsh@incaendo.com>
   * @return JSON Response is returned with a message and a success status
   * @throws Exception
   */
  public function actionCheckNickname()
  {
    $response = array(
      'msg' => Yii::t('discussion', 'Nickname already in use. Choose another!'),
      'success' => TRUE
    );
    try {
      if (array_key_exists('nickname', $_GET) && !empty($_GET['nickname'])) {
        $module = Yii::app()->getModule('backendconnector');
        if (empty($module)) {
          throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
        }
        $user = new UserIdentityAPI();
        $userDetail = array(
          'nickname' => $_GET['nickname']
        );
        $userStatus = $user->getUserDetail(IDM_USER_ENTITY, $userDetail, false, false, true);
        if (array_key_exists('_items', $userStatus) && empty($userStatus['_items'])) {
          $response['success'] = FALSE;
          $response['msg'] = Yii::t('discussion', 'Nickname available');
        }
      }
    } catch (Exception $exception) {
      $response['success'] = TRUE;
      $response['msg'] = Yii::t('discussion', 'Some error occured. Please try again.');
      Yii::log($exception->getMessage(), ERROR, 'Error in action Check Nickname');
    }
    echo CJSON::encode($response);
    exit;
  }

  /**
   * actionSaveNickname
   * Method used to save nickname
   *
   * @author Harsh <harsh@incaendo.com>
   * @return JSON Response message and success status are returned.
   * @throws Exception
   */
  public function actionSaveNickname()
  {
    $response = array(
      'msg' => '',
      'success' => FALSE
    );
    //var_dump('ok');
    try {
      if ((array_key_exists('nickname', $_GET) && array_key_exists('neverAddNickname', $_GET)) && ($_GET['nickname'] != '' || $_GET['neverAddNickname'] == ACTIVE)) {
        $module = Yii::app()->getModule('backendconnector');
        if (empty($module)) {
          throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
        }
        $nickname = $_GET['nickname'];

        $user = new UserIdentityAPI();
        $userDetail = array();
        $sessionArr = Yii::app()->session['user'];
        $sessionArr['firstname'] = $nickname;
        $sessionArr['lastname'] = '';
        if (
          !empty($sessionArr) && array_key_exists('id', $sessionArr) &&
          isset($sessionArr['id'])
        ) {
          $userDetail['id'] = $sessionArr['id'];
          $userStatus = $user->getUserDetail(IDM_USER_ENTITY, $userDetail, false, false, false);
          if (
            array_key_exists('_items', $userStatus) && !empty($userStatus['_items']) &&
            array_key_exists('0', $userStatus['_items']) && !empty($userStatus['_items'])
          ) {
            if (
              array_key_exists('site-user-info', $userStatus['_items']['0']) &&
              !empty($userStatus['_items']['0']['site-user-info']) &&
              is_array($userStatus['_items']['0']['site-user-info'])
            ) {
              $tempSiteUserInfo = $userStatus['_items']['0']['site-user-info'];
              if ($_GET['neverAddNickname'] == 1) {
                if (array_key_exists(CIVICO, $tempSiteUserInfo)) {
                  $tempSiteInfo = $tempSiteUserInfo[CIVICO];
                  $tempUserInfo = array('never-add-nickname' => 1);
                  $tempSiteUserInfo['site-user-info'][CIVICO] = array_merge($tempSiteInfo, $tempUserInfo);
                  $sessionArr['show-add-nickname-popup'] = 0;
                } else {
                  $tempSiteUserInfo[CIVICO] = array('never-add-nickname' => 1);
                  $tempSiteUserInfo['site-user-info'] = $tempSiteUserInfo;
                  $sessionArr['show-add-nickname-popup'] = 0;
                }
                Yii::app()->session['user'] = $sessionArr;
                $response = $this->saveUserNickname($userDetail['id'], $nickname, $tempSiteUserInfo);
              } else {
                $sessionArr['show-add-nickname-popup'] = 0;
                Yii::app()->session['user'] = $sessionArr;
                $response = $this->saveUserNickname($userDetail['id'], $nickname, $tempSiteUserInfo);
              }
            } else {


              $userSiteInfo['site-user-info'] = array();
              $response = $this->saveUserNickname($userDetail['id'], $nickname, $userSiteInfo);
              $sessionArr['show-add-nickname-popup'] = 0;

              Yii::app()->session['user'] = $sessionArr;
            }
          } else {
            throw new Exception('Some error occured in getting user detail');
          }
        }
      } else {
        $response['msg'] = Yii::t('discussion', 'Nickname cannot be empty');
      }
    } catch (Exception $exception) {
      $response['msg'] = Yii::t('discussion', 'Some error occured. Please try again.');
      Yii::log($exception->getMessage(), ERROR, 'Error in action Check Nickname!');
    }
    echo CJSON::encode($response);
    exit;
  }

  /**
   * saveUserNickname
   * Method used to add user nickname in its details.
   *
   * @param INT $userId
   * @param STRING $nickname
   * @param ARRAY $userSiteInfo
   * @return ARRAY Response with a message and a success status are returned
   */
  public function saveUserNickname($userId, $nickname = false, $userSiteInfo)
  {
    $response = array(
      'msg' => '',
      'success' => FALSE
    );
    $user = new UserIdentityAPI();
    $userInfo['id'] = $userId;
    if ($nickname != false) {
      $userInfo['nickname'] = $nickname;
    }
    if (
      array_key_exists('site-user-info', $userSiteInfo) &&
      !empty($userSiteInfo['site-user-info'])
    ) {
      $userInfo['site-user-info'] = $userSiteInfo['site-user-info'];
    }
    $updateUser = $user->curlPut(IDM_USER_ENTITY, $userInfo);
    if (array_key_exists('_status', $updateUser) && $updateUser['_status'] == 'OK') {
      $response['id'] = $updateUser['_id'];
      $response['msg'] = Yii::t('discussion', 'Nickname is successfully added to your account');
      $sessionArr = Yii::app()->session['user'];
      $sessionArr['show-add-nickname-popup'] = 0;
      if ($nickname == false) {
        $response['msg'] = Yii::t('discussion', 'You will never be asked from now');
      }
      $response['success'] = true;
    } else {
      $message = Yii::t('discussion', 'Some error occured please try again');
      if (array_key_exists('_status', $updateUser) && $updateUser['_status'] == 'ERR') {
        if (array_key_exists('nickname', $updateUser['_issues'])) {
          $message = $updateUser['_issues']['nickname'];
        }
        if (strpos($message, "is not unique") !== false) {
          if (array_key_exists('nickname', $updateUser['_issues'])) {
            $message = Yii::t('discussion', 'Nickname already in use. Choose another!');
          }
        } else {
          $message = Yii::t('discussion', 'Some technical problem occurred, contact administrator');
        }
      }
      $response['msg'] = $message;
    }
    return $response;
  }

  /**
   * Method used to save tags for :
   * using nickname in place of author names,
   * never displaying this pop up again.
   * @author Harsh <harsh@incaendo.com>
   * @throws Exception
   */
  public function actionDisplayNickname()
  {


    try {
      if (!empty($_POST)) {

        $postData = $_POST;



        $sessionArr = Yii::app()->session['user'];
        if (array_key_exists('id', $sessionArr) && !empty($sessionArr['id'])) {

          $tagToBeInserted = array();

          // caso usa il nickname senza flag su non mostrare piu
          if (array_key_exists('btn_use_nickname', $postData) && !array_key_exists('btn_never_display_nickname', $postData)) {
            $tagToBeInserted = array('use-nickname' => 1);
            $sessionArr['show-use-nickname'] = 0;
          }
          if (array_key_exists('btn_never_display_nickname', $postData) && array_key_exists('btn_use_nickname', $postData)) {
            if (
              array_key_exists('never_display_nickname', $postData) &&
              $postData['never_display_nickname'] = 'on'
            ) {

              $tagToBeInserted = array('never-display-nickname' => 1, 'use-nickname' => 1);

              $sessionArr['never_display_nickname'] = 1;
              $sessionArr['show-use-nickname'] = 0;
            }
          }



          $userInfo = array();
          $userInfo['id'] = $sessionArr['id'];
          $module = Yii::app()->getModule('backendconnector');
          if (empty($module)) {
            throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
          }
          $user = new UserIdentityAPI();
          $userStatus = $user->getUserDetail(IDM_USER_ENTITY, $userInfo, false, false, false);
          if (
            array_key_exists('_items', $userStatus) && !empty($userStatus['_items']) &&
            array_key_exists('0', $userStatus['_items']) && !empty($userStatus['_items'])
          ) {
            if (
              array_key_exists('site-user-info', $userStatus['_items']['0']) &&
              !empty($userStatus['_items']['0']['site-user-info']) &&
              is_array($userStatus['_items']['0']['site-user-info'])
            ) {
              $tempSiteUserInfo = $userStatus['_items']['0']['site-user-info'];
              if (array_key_exists(CIVICO, $tempSiteUserInfo)) {
                $tempSiteInfo = $tempSiteUserInfo[CIVICO];
                $tempUserInfo = $tagToBeInserted;
                $tempSiteUserInfo['site-user-info'][CIVICO] = array_merge($tempSiteInfo, $tempUserInfo);
              } else {
                $tempSiteUserInfo[CIVICO] = $tagToBeInserted;
                $tempSiteUserInfo['site-user-info'] = $tempSiteUserInfo;
              }
              $response = $this->saveUserNickname($userInfo['id'], false, $tempSiteUserInfo);
            } else {
              $userSiteInfo['site-user-info'][CIVICO] = $tagToBeInserted;
              $response = $this->saveUserNickname($userInfo['id'], false, $userSiteInfo);
            }
            if (!array_key_exists('success', $response) || !$response['success']) {
              throw new Exception('Not got success while saving use nickname tags');
            }

            if (
              array_key_exists('success', $response) && $response['success'] &&
              array_key_exists('use-nickname', $tagToBeInserted) &&
              array_key_exists('nickname', $sessionArr)
            ) {
              $sessionArr['firstname'] = $sessionArr['nickname'];
              $sessionArr['lastname'] = '';
              Yii::app()->session['user'] = $sessionArr;
            }
            if (array_key_exists('success', $response) && $sessionArr['never_display_nickname'] == '1') {
              // var_dump($sessionArr);
              //  die();
              Yii::app()->session['user'] = $sessionArr;
            }
          } else {
            throw new Exception('User detail is returned empty.');
          }
        }
      }
    } catch (Exception $exception) {
      Yii::log('Error in actionDisplayNickname method', ERROR, $exception->getMessage());
    }
    $this->redirect(Yii::app()->request->urlReferrer);
  }
}
