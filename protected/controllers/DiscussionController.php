<?php

/**
 * DiscussionController
 *
 * DiscussionController class inherit controller (base) class .
 * Actions are defined in DiscussionController.
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra <sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
 * <ahref Foundation.
 */
class DiscussionController  extends PageController {

  public $discussionId;

  public function init() {
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

  public function beforeAction($action) {
    new JsTrans('js', SITE_LANGUAGE);
    checkAdditionalFormFilled();
    return true;
  }

  /**
   * actionInDdex
   *
   * This is the default 'index' action that is invoked
   * when an action is not explicitly requested by users.
   */
  public function actionIndex() {
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    $discussion = new Discussion();
    $discussions = array();
    $stripedContent = array();
    $discussions = $discussion->getDiscussionDetail();
    foreach ($discussions as $discussion) {
      $discussion['summary'] = strip_tags($discussion['summary'], '<p><i><strong><br>');
      $stripedContent[] = $discussion;
    }
    $homeConfig = new Configuration();
    $homeConfig->type = 'homeconfig';
    $homeConfigurations = $homeConfig->get();
    $configuration = array();

    foreach ($homeConfigurations as $config) {
      if ($config['name_key'] == 'introduction_text') {
        $configuration[$config['name_key']] = html_entity_decode(stripslashes($config['value']));
      } else {
        $configuration[$config['name_key']] = $config['value'];
      }
    }
    $chunkSize = 1;
    if ($configuration['layout'] != 0) {
      $chunkSize = $configuration['layout'];
    }
    $discussions = array_chunk($stripedContent, $chunkSize);
  
    $showUseNickname = INACTIVE;

    $showNicknamePopUp = FALSE;

    if (!empty(Yii::app()->session['user'])) {
      $sessionArr = Yii::app()->session['user'];



      if (is_array($sessionArr) && array_key_exists('show-add-nickname-popup', $sessionArr)) {
        if ($sessionArr['show-add-nickname-popup'] == TRUE) {
          $showNicknamePopUp = TRUE;
        }
      }
      if (array_key_exists('enable_nickname_use', Yii::app()->globaldef->params) &&
        Yii::app()->globaldef->params['enable_nickname_use'] == 1) {
        if (is_array($sessionArr)) {
          if (array_key_exists('never-display-nickname', $sessionArr) &&
            isset($sessionArr['never-display-nickname']) &&
            $sessionArr['never-display-nickname'] == ACTIVE) {
            $showUseNickname = INACTIVE;
          } else if (array_key_exists('show-use-nickname', $sessionArr) &&
            isset($sessionArr['show-use-nickname']) && $sessionArr['show-use-nickname'] == 0) {
              $showUseNickname = INACTIVE;
          } else {
            if (array_key_exists('nickname', $sessionArr) && isset($sessionArr['nickname']) ) {
              if (array_key_exists('use-nickname', $sessionArr) && isset($sessionArr['nickname'])) {
                if ($sessionArr['use-nickname'] == ACTIVE && $sessionArr['show-use-nickname'] == 0 ) {
                  $showUseNickname = INACTIVE;
                } else {
                  $showUseNickname = ACTIVE;
                }
              } else {
                $showUseNickname = ACTIVE;
              }
            }
          }
        }
        if ($showUseNickname == ACTIVE) {
          $sessionArr['show-use-nickname'] = ACTIVE;
        }
      }

       Yii::app()->session['user'] = $sessionArr;
    }


if($showNicknamePopUp == 1) $showUseNickname = 0;

    if (defined('DIV_COLORS')) {
      $color = unserialize(DIV_COLORS);
      $this->render('index', array('color' => $color, 'submission' => Yii::app()->globaldef->params['submission'], 'discussions' => $discussions, 'text' => Yii::app()->globaldef->params['homepage_text'], 'homeDetails' => $configuration, 'shownNicknamePopUp' => $showNicknamePopUp, 'showUseNickname' => $showUseNickname));
    } else {
      $this->render('index', array('submission' => Yii::app()->globaldef->params['submission'], 'discussions' => $discussions, 'text' => Yii::app()->globaldef->params['homepage_text'], 'homeDetails' => $configuration, 'shownNicknamePopUp' => $showNicknamePopUp, 'showUseNickname' => $showUseNickname));
    }
  }

  /**
   * actionLogin
   * this function is used for login user
   */
  public function actionLogin() {    
$admin = array();
    if (userIsLogged()) {
      $this->redirect(BASE_URL);
    }
    $response = array();
    $backUrl = BASE_URL;
    $additionalInfoUrl = '';
    try {
      if (isModuleExist('backendconnector') == false) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
      }
      $showQuestionModal = FALSE;
      $user = new UserIdentityManager();
if (!empty($_POST)) {
        $userDetail = $_POST;
        if (empty($userDetail['email']) || !filter_var($userDetail['email'], FILTER_VALIDATE_EMAIL)) {
          throw new Exception(Yii::t('discussion', 'Please enter a valid email'));
        }
        if (empty($userDetail['password'])) {
          throw new Exception(Yii::t('discussion', 'Please enter password'));
        }
        $response = $user->validateUser($userDetail);

//return;
if (array_key_exists('success', $response) && $response['success']) {
          $discussion = new Discussion();
          $userProposals = $discussion->userSubmittedProposal('', true);
          $temp = Yii::app()->session['user'];
          $temp['canSubmitProposal'] = false;
          if (empty($userProposals)) {
            $temp['canSubmitProposal'] = true;
          } else {
            foreach ($userProposals as $proposal) {
              foreach ($proposal['tags'] as $tag) {
                if (array_key_exists('scheme', $tag)) {
                  if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
                    if (array_key_exists('weight', $tag)) {
                      if ($tag['weight'] >= intval(Yii::app()->globaldef->params['threshold_opinion_count'])) {
                        $temp['canSubmitProposal'] = true;
                        break;
                      }
                    }
                  }
                }
              }
            }
          }
          $userIdentityApi = new UserIdentityAPI();
          $userController = new UserController('user');
          $userInfo = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => trim($userDetail['email'])), false, false);
          if (array_key_exists('_items', $userInfo) && array_key_exists(0, $userInfo['_items'])) {
            if (array_key_exists('additional_information_status', Yii::app()->globaldef->params)
              && Yii::app()->globaldef->params['additional_information_status'] == 1) {
              if (array_key_exists('site-last-login', $userInfo['_items'][0]) &&
                array_key_exists(CIVICO, $userInfo['_items'][0]['site-last-login'])) {
                $showQuestionModal = $userController->showQuestionForm($userInfo['_items'][0]['site-last-login'][CIVICO]);
              } else {
                $showQuestionModal = TRUE;
              }
            }
          }
          //update user last login time
          if(!$showQuestionModal) {
            $userController->updateLastLoginTime($userInfo);
          }
          if (array_key_exists('_items', $response) && !empty($response['_items'])
            && array_key_exists('0', $response['_items']) && !empty($response['_items']['0'])
            && array_key_exists('site-user-info', $response['_items']['0'])
            && !empty($response['_items']['0']['site-user-info']) &&
            array_key_exists(CIVICO, $response['_items']['0']['site-user-info'])
            && !empty($response['_items']['0']['site-user-info'][CIVICO])) {
            $siteUserInfo = $response['_items']['0']['site-user-info'][CIVICO];

            if (array_key_exists('nickname', $response['_items']['0']) && trim($response['_items']['0']['nickname']) != "") {
              $temp['has-nickname'] = ACTIVE;
           
              $temp['show-add-nickname-popup'] = INACTIVE;
           
             
            } else {
          
   
              $temp['has-nickname'] = INACTIVE;
              $temp['show-add-nickname-popup'] = ACTIVE;
                
            }



            if (array_key_exists('never-add-nickname', $siteUserInfo)) {
              $temp['never-add-nickname'] = $siteUserInfo['never-add-nickname'];
            }
            if (array_key_exists('use-nickname', $siteUserInfo)) {
              $temp['use-nickname'] = $siteUserInfo['use-nickname'];
              $temp['firstname'] = $response['_items']['0']['nickname'];
              $temp['lastname'] = '';
            }
           
            if (array_key_exists('never-display-nickname', $siteUserInfo)) {
              $temp['never-display-nickname'] = $siteUserInfo['never-display-nickname'];
            }
            if (array_key_exists('never-add-nickname', $siteUserInfo)
              || array_key_exists('nickname', $response['_items']['0'])) {
          
               if(array_key_exists('nickname', $response['_items']['0']) && trim($response['_items']['0']['nickname']) != "")
              {
                $temp['show-add-nickname-popup'] = INACTIVE;
               
              }
              else $temp['show-add-nickname-popup'] = ACTIVE;
            
            } else {
              $temp['show-add-nickname-popup'] = ACTIVE;
            }
          } else {

            if (array_key_exists('nickname', $response['_items']['0']) && trim($response['_items']['0']['nickname']) != "") {
              $temp['has-nickname'] = ACTIVE;
             
              $temp['show-add-nickname-popup'] = INACTIVE;
  
             
            } else {
              $temp['has-nickname'] = INACTIVE;
              $temp['show-add-nickname-popup'] = ACTIVE;
            
            }
          }
          if (array_key_exists('type', $response['_items']['0'])) {
            $temp['user-type'] = $response['_items']['0']['type'];
            if (isset($temp['user-type']) && $temp['user-type'] == 'org') {
              $showQuestionModal = FALSE;
              if (array_key_exists('show-add-nickname-popup', $temp)) {
                $temp['show-add-nickname-popup'] = INACTIVE;
              }
              $temp['show-use-nickname'] = ACTIVE;
            }
          }

 

         if (isset($siteUserInfo) && !array_key_exists('never_display_nickname', $siteUserInfo) && array_key_exists('nickname', $response['_items']['0'])) {


                 $temp['show-use-nickname'] = ACTIVE;
           
            }



          $nickname_enabled = Yii::app()->globaldef->params['enable_nickname_use'];

          if(isset($nickname_enabled) && $nickname_enabled == "0" )
          {
            $temp['show-add-nickname-popup'] = INACTIVE;
          }

          Yii::app()->session['user'] = $temp;
          $isAdmin = checkPermission('is_admin');
          $_SESSION['user']['admin'] = $isAdmin;
          $backUrl = '';
          if (!empty($_GET['back'])) {
            $back = substr($_GET['back'], 1);
            if (!empty($back)) {
              $backUrl = BASE_URL . substr($_GET['back'], 1);
            }
          }
          if ($isAdmin && empty($backUrl)) {
            $admin['admin'] = true;
            $admin['url'] = BASE_URL . 'admin/discussion/list';
          }
          if ($showQuestionModal) {
            $additionalInfoUrl = BASE_URL . 'user/question';
            $_SESSION['user']['back_url'] = $backUrl;
            $_SESSION['user']['show_question_page'] = TRUE;
          }
        }
      }
    } catch (Exception $e) {
      $response['success'] = false;
      $response['msg'] = $e->getMessage();
      Yii::log('', ERROR, Yii::t('discussion', 'Error in actionRegisterUser method :') . $e->getMessage());
    }
    $this->layout = 'userManager';
    $this->render('login', array('message' => $response, 'back_url' => $backUrl, 'user' => $admin, 'additional_info_url' => $additionalInfoUrl));
  }

  /**
   * actionCreateDiscussion
   *
   * This function is used for create discussion
   */
  public function actionCreateDiscussion() {
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    //check if user belong to admin users or not
    $isAdmin = checkPermission('create_new_discussion');
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    $response = array();
    $discussion = new Discussion();
    try {
      if (!empty($_POST)) {
        $response = $discussion->createDiscussion();
        if ($response['success']) {
          $this->redirect(BASE_URL . 'admin/discussion/list');
        } else {
          $response['msg'] = $response['msg'];
        }
      }
    } catch (Exception $e) {
      $response['success'] = false;
      $response['msg'] = $e->getMessage();
    }
    $this->render('discussionCreation', array('message' => $response, 'discussion' => $_POST));
  }

  /**
   * actionGetDiscussion
   *
   * This function is used for getting existing discussions' information
   */
  public function actionGetDiscussion() {
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    $isHighlighter = checkPermission('can_mark_highlighted');
    $can_show_hide_opinion = checkPermission('can_show_hide_opinion');
    $configureHomePage = checkPermission('configure_home_page');
    $createNewDiscussion = checkPermission('create_new_discussion');
    $accessReport = checkPermission('access_report');
    if ($isHighlighter == FALSE && $can_show_hide_opinion == FALSE && $configureHomePage == FALSE
      && $createNewDiscussion == FALSE && $accessReport == FALSE) {
      $this->redirect(BASE_URL);
    }
    $discussion = $this->_getDiscussionProposalOpinionLinks();
    $showNicknamePopUp = FALSE;
    $sessionArr = Yii::app()->session['user'];
    if (is_array($sessionArr) && array_key_exists('show-add-nickname-popup', $sessionArr)) {
      if ($sessionArr['show-add-nickname-popup'] == TRUE) {
        $showNicknamePopUp = TRUE;
      }
    }
    $showUseNickname = INACTIVE;
    if (array_key_exists('enable_nickname_use', Yii::app()->globaldef->params) &&
        Yii::app()->globaldef->params['enable_nickname_use'] == 1) {
      if (is_array($sessionArr)) {
        if (array_key_exists('never-display-nickname', $sessionArr) &&
          isset($sessionArr['never-display-nickname']) &&
          $sessionArr['never-display-nickname'] == ACTIVE) {
          $showUseNickname = INACTIVE;
        } else if (array_key_exists('show-use-nickname', $sessionArr) &&
          isset($sessionArr['show-use-nickname']) && $sessionArr['show-use-nickname'] == 1) {
            $showUseNickname = ACTIVE;
        } else {
          if (array_key_exists('nickname', $sessionArr) && isset($sessionArr['nickname'])) {
            if (array_key_exists('use-nickname', $sessionArr) && isset($sessionArr['nickname'])) {
              if ($sessionArr['use-nickname'] == ACTIVE) {
                $showUseNickname = INACTIVE;
              } else {
                $showUseNickname = ACTIVE;
              }
            } else {
              $showUseNickname = ACTIVE;
            }
          }
        }
      }

      if ($showUseNickname == ACTIVE) {
        $sessionArr['show-use-nickname'] = ACTIVE;
      }
    }
   
   


    Yii::app()->session['user'] = $sessionArr;
    $this->render('discussionList', array(
        'discussionInfo' => $discussion['discussion'],
        'emails' => $discussion['emails'],
        'authorNames' => $authorNames,
        'shownNicknamePopUp' => $showNicknamePopUp,
        'showUseNickname' => $showUseNickname
    ));
  }

  /**
   * actionUpdateDiscussion
   *
   * This function is used for existing contest
   * Only admin user can update a contest
   */
  public function actionUpdateDiscussion() {
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    $isAdmin = checkPermission('admin');
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    $discussion = new Discussion();
    $contestInfo = array();
    $discussionDetail = array();
    $message = array();
    try {
      if (!empty($_POST)) {
        $response = $discussion->updateDiscussion();
        if ($response['success']) {
          $this->redirect(BASE_URL . 'admin/discussion/list');
        } else {
          $response['msg'] = $response['msg'];
        }
      }
    } catch (Exception $e) {
      $response['success'] = false;
      $response['msg'] = $e->getMessage();
    }
    if (array_key_exists('slug', $_GET) && !empty($_GET['slug'])) {
      $discussion = new DiscussionAPI();
      if (empty($discussion->discussionSlug)) {
        $discussion->discussionSlug = $_GET['slug'];
      }
      try {
        $discussionInfo = $discussion->getDiscussionDetailBySlug();
        if (empty($discussionInfo)) {
          Yii::log('', ERROR, Yii::t('discussion', 'Error in getContestDetailByContestSlug'));
          throw new Exception(Yii::t('discussion', 'Some technical problem occurred, For more detail check log file'));
        }
        $discussionDetail['title'] = $discussionInfo['title'];
        $discussionDetail['summary'] = $discussionInfo['summary'];
        $discussionDetail['slug'] = $discussionInfo['slug'];
        $discussionDetail['author'] = $discussionInfo['author'];
        $discussionDetail['status'] = $discussionInfo['status'];
        $discussionDetail['proposal_status'] = $discussionInfo['proposal_status'];
        $discussionDetail['topics'] = $discussionInfo['topic'];
        $discussionDetail['additionalDescription'] = str_replace('foundation_logo_text',
          htmlspecialchars('<ahref'),  $discussionInfo['additional_description']);
      } catch (Exception $e) {
        $message['success'] = false;
        $message['msg'] = $e->getMessage();
      }
    }
    $this->render('editDiscussion', array('discussion' => $discussionDetail, 'message' => $message));
  }

  /**
   * actionDeleteContest
   *
   * This function is used for delete an existing contest
   */
  public function actionDeleteContest() {
    $contest = new ContestAPI();
    if (array_key_exists('slug', $_GET) && (!empty($_GET['slug']))) {
      $contest->contestSlug = $_GET['slug'];
      $contest->deleteContest();
    }
    $this->redirect(BASE_URL . 'admin/contest/list');
  }

  /**
   * actionLogout
   *
   * This function is used for logout user and destroy user session
   */
  public function actionLogout() {
    Yii::app()->session->destroy();
    $this->redirect(BASE_URL);
  }

  public function actionError() {
    $this->render('error404');
  }

  /**
   * actionList
   *
   * This function is used to list all the discussions.
   */
  public function actionList() {
    $isLogged = isUserLogged();
    if (!$isLogged) {
      $this->redirect(BASE_URL);
    }
    $discussionInfo = array();
    $discussionDetail = array();
    $discussion = new Discussion();
    $entry = array();
    $discussionInfo = $discussion->getDiscussionDetail();
    if (!empty($discussionInfo)) {
      $i = 0;
      foreach ($discussionInfo as $info) {
        $entries = array();
        $discussionDetail[$i]['discussionTitle'] = $info['title'];
        $discussionDetail[$i]['discussionSummary'] = mb_substr($info['summary'], 0, 20, "UTF-8");;
        $discussionDetail[$i]['discussionSlug'] = $info['slug'];
        $discussionDetail[$i]['discussionAuthor'] = $info['author'];
        $discussion->discussionSlug = $info['slug'];
        $discussion->count = 2;
        $i++;
      }
    }
    $this->render('discussionLIst', array('discussionInfo' => $discussionDetail));
  }

  /**
   * actionSubmitProposal
   *
   * This function is used to submit a proposal.
   */
  public function actionSubmitProposal() {
    Yii::log('', DEBUG, prepareLogMessage('SubmitProposal', 'Post Data for proposal submission', $_REQUEST));
    $isLogged = isUserLogged();
    if (!$isLogged) {
      $this->redirect(BASE_URL);
    }
    Yii::log('', DEBUG, prepareLogMessage('SubmitProposal', 'Logged in user Data for proposal submission', Yii::app()->session['user']));
    try {
      $discussion = new Discussion();
      $discussionInfo = array();
      $entries = array();
      $allowedImageExtentions = array();
      if (array_key_exists('slug', $_GET) && !empty($_GET['slug'])) {
        $discussion->slug = $_GET['slug'];
      }
      $entrySubmissionResponse = array();
      $discussionInfo = $discussion->getDiscussionDetail();
      $postData = array();
      if (!empty($_POST)) {
        $postData = $_POST;
        if (array_key_exists('title', $postData) && empty($postData['title'])) {
          throw new Exception(Yii::t('discussion', 'Title can not be empty'));
        } else {
          $_POST['title'] = mb_substr(trim($_POST['title']), 0, intval(Yii::app()->globaldef->params['max_char_title']), "UTF-8");
        }
        if (array_key_exists('summary', $postData) && empty($postData['summary'])) {
          throw new Exception(Yii::t('discussion', 'Introduction can not be empty'));
        } else {
          //check for the allowed character limit before purification.
          $_POST['summary'] = nl2br(trim($_POST['summary']));
          $_POST['summary'] = mb_substr($_POST['summary'], 0, intval(Yii::app()->globaldef->params['max_char_intro']), "UTF-8");
        }
        if (array_key_exists('body', $postData) && empty($postData['body'])) {
          throw new Exception(Yii::t('discussion', 'Body can not be empty'));
        }
        if (array_key_exists('video_url', $postData)) {
          $postData['video_url'] = trim($postData['video_url']);
          if(!empty($postData['video_url']) &&
            preg_match('/^.*(player.|www.)?(vimeo\.com|youtu(be\.com|\.be))\/(video\/|embed\/|watch\?v=)?([A-Za-z0-9._%-]*)(\&\S+)?/', $postData['video_url']) != 1) {
            throw new Exception(Yii::t('discussion', 'Please enter valid youtube or vimeo video url'));
          }
        }
        $errorMessage = '';
        if (array_key_exists('proposal_image', $_FILES) && $_FILES['proposal_image']['error'] != 4) {
          if ($_FILES['proposal_image']['error'] != 0 ) {
            $errorMessage = setFileUploadError($_FILES['proposal_image']['error']);
            throw new Exception(Yii::t('discussion', $errorMessage));
          }
          if (empty($_FILES['proposal_image']['name'])) {
            throw new Exception(Yii::t('discussion', 'Missing File Name'));
          }
          if ($_FILES['proposal_image']['size'] > UPLOAD_IMAGE_SIZE_LIMIT) {
            throw new Exception(Yii::t('discussion', 'File size exceeded'));
          }
          $allowedImageExtentions = json_decode(ALLOWED_IMAGE_EXTENSION);
          $imageExtention = explode('/', $_FILES['proposal_image']['type']);
          $imageExtention = end($imageExtention);
          if (!in_array($imageExtention, $allowedImageExtentions)) {
            throw new Exception(Yii::t('discussion', 'Only png and jpg/jpeg image allowed'));
          }
        }
        $_POST = array_map('userInputPurifier', $_POST);
        $entrySubmissionResponse = $discussion->submitProposal($discussionInfo['id'], $_GET['slug']);
        if (array_key_exists('success', $entrySubmissionResponse) &&
          $entrySubmissionResponse['success'] == false) {
          throw new Exception($entrySubmissionResponse['msg']);
        }
        $this->redirect(BASE_URL . 'discussion/proposals/' . $_GET['slug']);
      }
    } catch (Exception $e) {
      $entrySubmissionResponse['success'] = false;
      $entrySubmissionResponse['msg'] = $e->getMessage();
      $this->actionProposals($_POST, $entrySubmissionResponse['msg']);
      Yii::log('', DEBUG, prepareLogMessage('SubmitProposal', 'Submit proposal Error ocurred', $e->getMessage()));
      exit;
    }
    $this->render('discussionProposals', array('slug' => $discussion->slug, 'message' => $entrySubmissionResponse));
  }

  /**
   * actionDiscussionProposals
   *
   * This function is used to display all proposals of a discussion.
   */
  public function actionProposals($proposalData = array(), $errorMessage = '') {
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap_3.css');
    Yii::app()->clientScript->registerScriptFile(ADMIN_THEME_URL . 'js/admin.js', CClientScript::POS_END);
    $this->setHeader('3.0');
    $homeConfig = new Configuration();
    $homeConfig->type = 'homeconfig';
    $homeConfigurations = $homeConfig->get();
    $configuration = array();
    foreach ($homeConfigurations as $config) {
      if ($config['name_key'] == 'introduction_text') {
        $configuration[$config['name_key']] = html_entity_decode(stripslashes($config['value']));
      } else {
        $configuration[$config['name_key']] = $config['value'];
      }
    }
    $discussion = new Discussion();
    // $discussion->sort = '-creation_date';
    $discussionInfo = array();
    $proposalCount = 0;
    $proposals = array();
    $countFromEntries = array();
    $discussionSlug = '';
    $hasSubmitted = false;
    $proposalSubmissionStatus = intval(Yii::app()->globaldef->params['submission']);
    $opinionSubmissionStatus = intval(Yii::app()->globaldef->params['opinion_submission']);
    $linkSubmissionStatus = intval(Yii::app()->globaldef->params['link_submission']);
    if (array_key_exists('slug', $_GET) && !empty($_GET['slug'])) {
      $discussionSlug = $_GET['slug'];
      $discussion->slug = $_GET['slug'];
      $discussionTitle = '';
      $discussionSummary = '';
      $discussionTopics = '';
      $discussionInfo = $discussion->getDiscussionDetail();
      if (empty($discussionInfo)) {
        $this->redirect(BASE_URL);
      }
      $discussion->id = $discussionInfo['id'];
      $discussionTitle = ucfirst($discussionInfo['title']);
      $discussionSummary = $discussionInfo['summary'];
      $discussionTopics = array_filter(explode(',', $discussionInfo['topic']));
      $additionalDescription = $discussionInfo['additional_description'];
      $discussionInfo['additional_description'] = nl2br($discussionInfo['additional_description']);
      $additionalDescription = htmlspecialchars_decode($discussionInfo['additional_description']);
      $additionalDescription =   str_replace('foundation_logo_text', htmlspecialchars('<ahref'),  $additionalDescription);
      $proposalSubmissionStatus = $discussionInfo['proposal_status'];
    } else {
      $this->redirect(BASE_URL);
    }
    if (array_key_exists('id', $discussionInfo) && !empty($discussionInfo['id'])) {
      $submitProposal = $discussion->userSubmittedProposal($discussionInfo['id']);
      $proposalSubmitLimit = 0;
      if (defined('PROPOSAL_SUBMIT_LIMIT')) {
        $proposalSubmitLimit = PROPOSAL_SUBMIT_LIMIT;
      }
      if ($submitProposal >= $proposalSubmitLimit) {
        $hasSubmitted = true;
      }
    }
    if (defined('OPINION_SUBMISSION')) {
      $opinionSubmissionStatusArray = json_decode(OPINION_SUBMISSION, true);
      if (array_key_exists($discussionSlug, $opinionSubmissionStatusArray) && $opinionSubmissionStatusArray[$discussionSlug] == OPEN) {
        $opinionSubmissionStatus = OPEN;
      }
    }
    if (defined('LINK_SUBMISSION')) {
      $linkSubmissionStatusArray = json_decode(LINK_SUBMISSION, true);
      if (array_key_exists($discussionSlug, $linkSubmissionStatusArray) && $linkSubmissionStatusArray[$discussionSlug] == OPEN) {
        $linkSubmissionStatus = OPEN;
      }
    }
    if (!empty($_POST) && empty($errorMessage)) {
      Yii::log('', DEBUG, prepareLogMessage('actionProposals', 'Post Data for opnion or link', $_REQUEST));
      Yii::log('', DEBUG, prepareLogMessage('actionProposals', 'Logged in user Data for opnion or link submission', Yii::app()->session['user']));
      $resp = array('status' => false, 'msg' => '');
      if (isset($_POST['action']) && $_POST['action'] != 'getData') {
        $_POST = array_map('userInputPurifier', $_POST);
        if ($linkSubmissionStatus != 1) {
          return false;
        }
        if (array_key_exists('link', $_POST) && !empty($_POST['link'])) {
          $urlPart = parse_url($_POST['link'], PHP_URL_SCHEME);
          if ($urlPart) {
            $_POST['link'] = $_POST['link'];
          } else {
            $_POST['link'] = 'http://' . $_POST['link'];
          }
        }
        if (array_key_exists('description', $_POST) && !empty($_POST['description'])) {
          $_POST['description'] = nl2br($_POST['description']);
        }
        $response = $discussion->saveLink();
        $resp = array('status' => $response['success'], 'msg' => $response['msg'], 'data' => $response['data']);
      } else if (isset($_POST['action']) && $_POST['action'] == 'getData') {
        if (empty($_POST['pid'])) {
          throw new Exception('Id cannot be empty');
        } else {
          header('Content-type: application/json; charset=UTF-8');
          $resp = array('status' => true, 'msg' => $discussion->getOpinionsAndLinks($_POST['pid']));
          if(empty($resp['msg']['answer_on_opinion'])) {
            $resp['msg']['answer_on_opinion'] = 0;
          }
          echo json_encode($resp);
          die;
        }
      } else {
        if ($opinionSubmissionStatus != 1) {
          return false;
        }
        //check for the allowed character limit before purification.
        if (array_key_exists('opiniontext', $_POST) && (!empty($_POST['opiniontext']))) {
          $_POST['opiniontext'] = mb_substr($_POST['opiniontext'], 0, intval(Yii::app()->globaldef->params['max_char_opinion']), "UTF-8");
        }
        $_POST = array_map('userInputPurifier', $_POST);
        $response = $discussion->saveOpinion();
        $opinionText = '';
        if (array_key_exists('opinion_text', $response)) {
          $opinionText = $response['opinion_text'];
        }
        if (array_key_exists('heatmap', $response)) {
          $opinionId = '';
          if (array_key_exists('id', $response)) {
            $opinionId = $response['id'];
          } else if (array_key_exists('opinion_id', $response)) {
            $opinionId = $response['opinion_id'];
          }
          $resp = array('status' => $response['success'], 'msg' => $response['heatmap'],
           'opinion_text'=> $opinionText, 'opinion_id' => $opinionId);
        }
      }
      header('Content-type: application/json; charset=UTF-8');
      echo json_encode($resp);
      die;
    }
    $return = array('success' => false, 'msg' => '', 'data' => array());
    if(array_key_exists('tag', $_GET) && !empty($_GET['tag'])) {
      $discussion->tags = $_GET['tag'] . '{' . TOPIC_TAG_SCHEME . $_GET['slug'] . '/topics}';
    }
    $proposals = $discussion->getOnlyProposals();
    //check whether count is exist in entries array or not
    if (!empty($proposals)) {
      $return['success'] = true;
      $countFromEntries = end($proposals);
    } else {
      $return['msg'] = Yii::t('discussion', 'There are no proposals');
    }
    $proposals = $this->_proposalSorting($proposals);
    $understanding = array();
    $understanding = unserialize(UNDERSTANDING);
    $heatMap = array();
    $heatMap = unserialize(HEATMAP_COLORS);
    $return['data'] = $proposals;
    $this->layout = 'proposalManager';
    $classColor = '';
    if (defined('DIV_COLORS')) {
      $color = unserialize(DIV_COLORS);
      foreach ($color as $key => $value) {
        if (strpos($discussionSlug, $key) !== false) {
          $classColor = 'color-' . $value;
        }
      }
      $proposalLayout = Yii::app()->globaldef->params['proposal_layout'];
      if ($proposalLayout != 1) {
        $proposalLayout = 3;
      }
      $data = array(
        'ccolor' => $classColor,
        'link_st' => $linkSubmissionStatus,
        'op_st' => $opinionSubmissionStatus,
        'submission_status' => $proposalSubmissionStatus,
        'proposals' => $return,
        'summary' => $discussionSummary,
        'title' => $discussionTitle,
        'additionalDescription' => $additionalDescription,
        'understanding' => $understanding,
        'heatMap' => $heatMap,
        'slug' => $discussionSlug,
        'hasSubmitted' => $hasSubmitted,
        'under' => json_encode($understanding),
        'colors' => json_encode($heatMap),
        'title_char' => intval(Yii::app()->globaldef->params['max_char_title']),
        'intro_char' => intval(Yii::app()->globaldef->params['max_char_intro']),
        'max_char_opinion' => intval(Yii::app()->globaldef->params['max_char_opinion']),
        'topics' => $discussionTopics,
        'error_message' => $errorMessage,
        'proposal_detail' => $proposalData,
        'opinion_text' => Yii::app()->globaldef->params['opinion_text'],
        'link_text' => Yii::app()->globaldef->params['link_text'],
        'all_proposal_off' => Yii::app()->globaldef->params['submission'],
        'proposal_text' => Yii::app()->globaldef->params['proposal_text'],
        'proposal_layout' => $proposalLayout,
        'attached_image_on_proposal' => Yii::app()->globaldef->params['attach_img_on_proposal'],
        'homeDetails' => $configuration,
      );
    }
      $this->render('discussionProposals', $data);
    }

  /**
   * actionOpinion
   *
   * This function is used for get opinion for a discussion and manipulate opinion
   * @author Rahul <rahul@incaendo.com>
   */
  public function actionOpinion() {
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    //redirect to login page if it comes throug external url
    if (isUserLogged() == false && isset($_SERVER['REQUEST_URI'])) {
      $this->redirect(BASE_URL . 'login?back=' . $_SERVER['REQUEST_URI']);
    }
    $isAdmin = checkPermission('admin');
    $canShowHideOpinion = checkPermission('can_show_hide_opinion');
    $canAccessReport = checkPermission('access_report');
    if ($isAdmin == false && $canShowHideOpinion == false && $canAccessReport == FALSE) {
      $this->redirect(BASE_URL);
    }
    $opinions = array();
    $activeOpinions = array();
    $proposalOpinions = array();
    $inactiveOpinions = array();
    $title = '';
    $proposalId = '';
    $aggregatorManager = new AggregatorManager();
    if (array_key_exists('id', $_GET) && !empty($_GET['id'])) {
      $proposalId = $_GET['id'];
      $proposalTitle = array();
      $proposalId = $_GET['id'];
      $proposalTitle = $aggregatorManager->getEntry(1, 0, $_GET['id'], 'active', '', '', '', 0, '', '', 1, '', array(), '', 'title', '', '', '', CIVICO, '');
      if (array_key_exists('0', $proposalTitle) && !empty($proposalTitle[0])) {
        if (array_key_exists('title', $proposalTitle[0]) && !empty($proposalTitle[0]['title'])) {
          $title = $proposalTitle[0]['title'];
        }
      }
      $activeOpinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . OPINION_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '', 'status,author,id,content,related,tags', '', '', trim('proposal,' . $_GET['id']), CIVICO);
      $inactiveOpinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'inactive', 'link{' . OPINION_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '', 'status,author,id,content,related,tags', '', '', trim('proposal,' . $_GET['id']), CIVICO);
    }
    $opinions = array_merge($activeOpinions, $inactiveOpinions);
    $author = array();
    foreach ($opinions as $opinion) {
      $proposalOpinion = array();
      if (array_key_exists('count', $opinion)) {
        array_pop($opinions);
        continue;
      }
      if (array_key_exists('id', $opinion) && !empty($opinion['id'])) {
        $proposalOpinion['id'] = $opinion['id'];
      }
      if (array_key_exists('status', $opinion) && !empty($opinion['status'])) {
        $proposalOpinion['status'] = $opinion['status'];
      }
      if (array_key_exists('author', $opinion) && !empty($opinion['author'])) {
        $proposalOpinion['author_name'] = $opinion['author']['name'];
        $proposalOpinion['author_id'] = $opinion['author']['slug'];
        $author[] = $opinion['author']['slug'];
      }
      if (array_key_exists('content', $opinion) && !empty($opinion['content'])) {
        $proposalOpinion['description'] = $opinion['content']['description'];
      }
      if (array_key_exists('related', $opinion) && !empty($opinion['related'])) {
        if (array_key_exists('id', $opinion['related']) && !empty($opinion['related']['id'])) {
          $proposalOpinion['discussion_id'] = $opinion['related']['id'];
        }
      }
      if (array_key_exists('tags', $opinion) && !empty($opinion['tags'])) {
        foreach ($opinion['tags'] as $tag) {
          if ($tag['scheme'] == INDEX_TAG_SCHEME) {
            $proposalOpinion['tag_name'] = $tag['weight'];
          }
        }
      }
      $proposalOpinions[] = $proposalOpinion;
    }
    $author = array_unique($author);
    $userIdentityApi = new UserIdentityAPI();
    $userEmail = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('id' => $author), TRUE, false);
    $emails = array();
    if (array_key_exists('_items', $userEmail) && !empty($userEmail['_items'])) {
      foreach ($userEmail['_items'] as $email) {
        $emails[$email['_id']] = $email['email'];
      }
    }
    $discussionSlug = '';
    if (array_key_exists('slug', $_GET) && $_GET['slug'] != '') {
      $discussionSlug = $_GET['slug'];
    }
    $this->render('discussionOpinion', array(
      'opinions' => $proposalOpinions,
      'title' => $title,
      'slug' => $discussionSlug,
      'emails' => $emails,
      'proposalId' => $proposalId
    ));
  }

  /**
   * actionUpdateOpinion
   *
   * This function is used for update status of existing opinion
   * @author Rahul<rahul@incaendo.com>
   */
  public function actionUpdateOpinion() {
    if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
      if (array_key_exists('opinion_id', $_GET) && !empty($_GET['opinion_id'])) {
        $opinionId = $_GET['opinion_id'];
      }
      if (array_key_exists('status', $_GET) && !empty($_GET['status'])) {
        $opinionStatus = $_GET['status'];
      }
      $tagName = '';
      if (array_key_exists('tag_name', $_GET) && !empty($_GET['tag_name'])) {
        $tagName = $_GET['tag_name'];
      }
      $discussionId = '';
      if (array_key_exists('discussion_id', $_GET) && !empty($_GET['discussion_id'])) {
        $discussionId = $_GET['discussion_id'];
      }
      $return = array('success' => false, 'status' => $opinionStatus, 'msg' => '');
      $aggregatorManager = new AggregatorManager();
      $aggregatorManager->id = $opinionId;
      $aggregatorManager->status = 'active';
      $statusHtml = Yii::t('discussion', 'Hide');
      $opinionStatusForMail = Yii::t('discussion', 'shown');
      if (strtolower($opinionStatus) == strtolower(Yii::t('discussion', 'Hide'))) {
        $aggregatorManager->status = 'inactive';
        $statusHtml = Yii::t('discussion', 'Show');
        $opinionStatusForMail = Yii::t('discussion', 'hidden');
      }
      $update = $aggregatorManager->updateStatus();
      if ($update['success']) {
        $updateHeatMap = $this->updateHeatMapTagForProposalStatus($discussionId, $tagName, $opinionStatus);
        if (!empty($updateHeatMap) && array_key_exists('success', $updateHeatMap) && !$updateHeatMap['success']) {
          Yii::log('actionUpdateOpinion ', ERROR, 'Failed in updattion of discussion tags -discussion id: ' . $discussionId);
        }
        if (isEnableFeature('visibility_changes_email')) {
          $discussion = new Discussion();
          $mailIntro = Yii::t('discussion', 'An intro: The moderator {user_name} has {status} the following content',
            array(
              '{user_name}' => Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'],
              '{status}' => $opinionStatusForMail
            ));
          $opinionDescription = '';
          if (array_key_exists('opinion_description', $_GET) && !empty($_GET['opinion_description'])) {
            $opinionDescription = $_GET['opinion_description'];
          }
          $mailBody = $discussion->prepareMailBodyForOpinion($mailIntro, $opinionDescription, $discussionId);
          $subject = Yii::t('discussion', '[{site_theme}] {user_name} has {status} the opinion',
            array(
              '{site_theme}' => SITE_THEME,
              '{user_name}' => Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'],
              '{status}' => $opinionStatusForMail
              ));
          $discussion->sendNotificationEMail($subject, $mailBody);
        }
        $return['success'] = true;
        $return['status'] = $statusHtml;
      } else {
        Yii::log('', ERROR, $update['msg']);
        $return['msg'] = Yii::t('discussion', 'Oops! something wrong');
      }
      echo json_encode($return);
      exit;
    } else {
      $this->render('error404');
      exit;
    }
  }

  /**
   * actionGetLinks
   *
   * This function is used for links
   * @author Rahul<rahul@incaendo.com>
   */
  public function actionGetLinks() {
    //redirect to login page if it comes throug external url
    if (isUserLogged() == false && isset($_SERVER['REQUEST_URI'])) {
      $this->redirect(BASE_URL . 'login?back=' . $_SERVER['REQUEST_URI']);
    }
    $this->setHeader('2.0');
    $canAccessReport = checkPermission('access_report');
    if ($canAccessReport ==false) {
      $this->redirect(BASE_URL);
    }
    $links = array();
    $title = '';
    $proposalLinks = array();
    $aggregatorManager = new AggregatorManager();
    $proposalId = '';
    if (array_key_exists('id', $_GET) && !empty($_GET['id'])) {
      $proposalId = $_GET['id'];
    }
    $proposalTitle = $aggregatorManager->getEntry(1, 0, $proposalId, 'active', '', '', '', 0, '', '', 1, '', array(), '', 'title', '', '', '', CIVICO, '');
    if (array_key_exists(0, $proposalTitle) && array_key_exists('title', $proposalTitle[0]) && !empty($proposalTitle[0]['title'])) {
      $title = $proposalTitle[0]['title'];
    }
    $activeLinks = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . LINK_TAG_SCEME . '}', '', '', 0, '', '', '', '', array(), '', 'status,author,id,content,related', '', '', trim('proposal,' . $_GET['id']), CIVICO);
    $inactiveLinks = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'inactive', 'link{' . LINK_TAG_SCEME . '}', '', '', 0, '', '', '', '', array(), '', 'status,author,id,content,related', '', '', trim('proposal,' . $_GET['id']), CIVICO);
    $links = array_merge($activeLinks, $inactiveLinks);
    if (!empty($links)) {
      foreach ($links as $link) {
        $proposalLink = array();
        if (array_key_exists('status', $link) && !empty($link['status'])) {
          $proposalLink['status'] = $link['status'];
        }
        if (array_key_exists('content', $link) && !empty($link['content'])) {
          if (array_key_exists('description', $link['content']) && !empty($link['content']['description'])) {
            $proposalLink['description'] = $link['content']['description'];
          }
          if (array_key_exists('summary', $link['content']) && !empty($link['content']['summary'])) {
            $proposalLink['summary'] = $link['content']['summary'];
          }
        }
        if (array_key_exists('author', $link) && !empty($link['author'])) {
          $proposalLink['author_name'] = $link['author']['name'];
        }
        if (array_key_exists('id', $link) && !empty($link['id'])) {
          $proposalLink['id'] = $link['id'];
        }
        $proposalLinks[] = $proposalLink;
      }
    }
    if (array_key_exists('slug', $_GET) && !empty($_GET['slug'])) {
      $slug = $_GET['slug'];
    }
    $this->render('discussionLink', array('links' => $proposalLinks, 'title' => $title,
      'slug' => $slug, 'proposal_id' => $proposalId));
  }

  /**
   * actionUpdateLink
   *
   * This function is used for update status of existing link
   * @author Rahul<rahul@incaendo.com>
   */
  public function actionUpdateLink() {
    if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
      if (array_key_exists('link_id', $_GET) && !empty($_GET['link_id'])) {
        $linkId = $_GET['link_id'];
      }
      if (array_key_exists('status', $_GET) && !empty($_GET['status'])) {
        $linkStatus = $_GET['status'];
      }
      $id = '';
      if (array_key_exists('id', $_GET) && !empty($_GET['id'])) {
        $id = $_GET['id'];
      }
      $return = array('success' => false, 'status' => $linkStatus, 'msg' => '');
      $aggregatorManager = new AggregatorManager();
      $aggregatorManager->id = $linkId;
      $aggregatorManager->status = 'active';
      $linkStatusForMail = Yii::t('discussion', 'shown');
      $statusHtml = Yii::t('discussion', 'Hide');
      if (strtolower($linkStatus) == strtolower(Yii::t('discussion', 'Hide'))) {
        $aggregatorManager->status = 'inactive';
        $linkStatusForMail = Yii::t('discussion', 'hidden');
        $statusHtml = Yii::t('discussion', 'Show');
      }
      $update = $aggregatorManager->updateStatus();
      if ($update['success']) {
        $return['success'] = true;
        $return['status'] = $statusHtml;
        $this->updateLinkCount($id, $linkStatus);
        if (isEnableFeature('visibility_changes_email') === TRUE) {
          $discussion = new Discussion();
          $mailIntro = Yii::t('discussion', 'An intro: The moderator {user_name} has {status} the following content',
            array(
              '{user_name}' => Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'],
              '{status}' => $linkStatusForMail
              ));
          $link = '';
          if (array_key_exists('link_summary', $_GET) && !empty($_GET['link_summary'])) {
            $link = $_GET['link_summary'];
          }
          $linkDescription = '';
          if (array_key_exists('link_description', $_GET) && !empty($_GET['link_description'])) {
            $linkDescription = $_GET['link_description'];
          }
          $proposalId = '';
          if (array_key_exists('proposal_id', $_GET) && !empty($_GET['proposal_id'])) {
            $proposalId = $_GET['proposal_id'];
          }
          $mailBody = $discussion->prepareMailBodyForLink($mailIntro, $link, $linkDescription, $proposalId);
          $subject = Yii::t('discussion', '[{site_theme}] {user_name} has {status} a link',
            array(
              '{site_theme}' => SITE_THEME,
              '{user_name}' => Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'],
              '{status}' => $linkStatusForMail
            ));
          $discussion->sendNotificationEMail($subject, $mailBody);
        }
      } else {
        Yii::log('', ERROR, $update['msg']);
        $return['msg'] = Yii::t('discussion', 'Oops! something wrong');
      }
      echo json_encode($return);
      exit;
    } else {
      $this->render('error404');
      exit;
    }
  }

  /**
   * actionGetProposal
   *
   * This function is used for get proposal for existing discussion
   * @author Rahul<rahul@incaendo.com>
   */
  public function actionGetProposal() {
    $isAdmin = checkPermission('admin');
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    $proposals = array();
    $discussionProposal = array();
    $discussionSlug = '';
    $discussionTitle = '';
    if (array_key_exists('slug', $_GET) && !empty($_GET['slug'])) {
      $discussion = new Discussion();
      $discussionSlug = $_GET['slug'];
      $discussion->slug = $_GET['slug'];
      $discussionInfo = $discussion->getDiscussionDetail();
      $discussion->id = $discussionInfo['id'];
      $discussionTitle = ucfirst($discussionInfo['title']);
      $proposals = $discussion->getProposalForAdmin();
      foreach ($proposals as $proposal) {
        $proposl = array();
        if (array_key_exists('id', $proposal) && !empty($proposal['id'])) {
          $proposl['id'] = $proposal['id'];
        }
        if (array_key_exists('title', $proposal) && !empty($proposal['title'])) {
          $proposl['title'] = $proposal['title'];
        }
        if (array_key_exists('content', $proposal) && !empty($proposal['content'])) {
          if (array_key_exists('description', $proposal['content']) && !empty($proposal['content']['description'])) {
            $proposl['description'] = mb_substr($proposal['content']['description'], 0, 1000, "UTF-8");
          }
        }
        if (array_key_exists('author', $proposal) && !empty($proposal['author'])) {
          if (array_key_exists('author', $proposal) && !empty($proposal['author']['name'])) {
            $proposl['author'] = $proposal['author']['name'];
          }
        }
        if (array_key_exists('status', $proposal) && !empty($proposal['status'])) {
          $proposl['status'] = $proposal['status'];
        }
        if (array_key_exists('totalOpinion', $proposal)) {
          $proposl['totalOpinion'] = $proposal['totalOpinion'];
        }
        if (array_key_exists('weightmap', $proposal) && is_array($proposal['weightmap'])) {
          $proposl['weightmap'] = $proposal['weightmap'];
        } else {
          $proposl['weightmap'] = 'none';
        }
        if (array_key_exists('creation_date', $proposal) && !empty($proposal['creation_date'])) {
          $proposl['creation_date'] = $proposal['creation_date'];
        }
        $discussionProposal[] = $proposl;
      }
    }
    $this->render('viewProposal', array('proposals' => $discussionProposal, 'slug' => $discussionSlug, 'discussionTitle' => $discussionTitle));
  }

  /**
   * actionUpdateLink
   *
   * This function is used for update status of existing link
   * @author Rahul<rahul@incaendo.com>
   */
  public function actionProposalStatus() {
    if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
      if (array_key_exists('proposal_id', $_GET) && !empty($_GET['proposal_id'])) {
        $proposalId = $_GET['proposal_id'];
      }
      if (array_key_exists('status', $_GET) && !empty($_GET['status'])) {
        $proposalStatus = trim($_GET['status']);
      }
      if (array_key_exists('discussion_slug', $_GET)) {
        $_GET['slug'] = $_GET['discussion_slug'];
      }
      $return = array('success' => false, 'status' => $proposalStatus, 'msg' => '');
      $aggregatorManager = new AggregatorManager();
      $aggregatorManager->id = $proposalId;
      $aggregatorManager->status = 'active';
      $proposalStatusForMail = Yii::t('discussion', 'shown');
      $statusHtml = Yii::t('discussion', 'Hide');
      if (strtolower($proposalStatus) == strtolower(Yii::t('discussion', 'Hide'))) {
        $aggregatorManager->status = 'inactive';
        $statusHtml = Yii::t('discussion', 'Show');
        $proposalStatusForMail = Yii::t('discussion', 'hidden');
      }
      $update = $aggregatorManager->updateStatus();
      if ($update['success']) {
        if (isEnableFeature('visibility_changes_email') === TRUE) {
          $proposalDetails = $aggregatorManager->getEntry(1, '', $proposalId, $aggregatorManager->status, 'link{' . PROPOSAL_TAG_SCEME . '}', '', '', '', '', '', '', '', array(), '', 'title,id,content', '', '', '', CIVICO);
          $title = '';
          if (array_key_exists('title', $proposalDetails[0]) && !empty($proposalDetails[0]['title'])) {
            $title = $proposalDetails[0]['title'];
          }
          $summary = '';
          if (array_key_exists('content', $proposalDetails[0]) && array_key_exists('summary', $proposalDetails[0]['content'])) {
            $summary = $proposalDetails[0]['content']['summary'];
          }
          $description = '';
          if (array_key_exists('content', $proposalDetails[0]) && array_key_exists('description', $proposalDetails[0]['content'])) {
            $description = $proposalDetails[0]['content']['description'];
          }
          $subject = Yii::t('discussion', '[{site_theme}] Moderator {user_name} has {status} the proposal : {title}',
            array(
              '{site_theme}' => SITE_THEME,
              '{user_name}' => Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'],
              '{status}' => $proposalStatusForMail,
              '{title}' => $title
            ));
          $mailIntro = Yii::t('discussion', 'An intro: The moderator {user_name} has {status} the following content',
            array(
              '{user_name}' => Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'],
              '{status}' => $proposalStatusForMail
            ));
          $discussion = new Discussion();
          $body = $discussion->prepareMailBodyForProposal($mailIntro, $title, $summary, $description);
          $discussion->sendNotificationEMail($subject, $body);
        }
        $return['success'] = true;
        $return['status'] = $statusHtml;
      } else {
        Yii::log('', ERROR, $update['msg']);
        $return['msg'] = Yii::t('discussion', 'Oops! something wrong');
      }
      echo json_encode($return);
      exit;
    } else {
      $this->render('error404');
      exit;
    }
  }

  /**
   * updateHeatMapTagForProposalStatus
   *
   * function is used for update heatmap tad based on opinion status
   */
  public function updateHeatMapTagForProposalStatus($id, $tagName, $proposalStatus) {
    $newTags = array();
    $aggregatorManager = new AggregatorManager();
    $proposal = $aggregatorManager->getEntry('', '', $id, '', '', '', '', '', '', '', '', '', array(), '', 'tags', '', '', '', '', '');
    if (array_key_exists(0, $proposal)) {
      if (array_key_exists('tags', $proposal[0])) {
        $tags = $proposal[0]['tags'];
        foreach ($tags as $tag) {
          $tagss = array();
          $tagss = $tag;
          if (strtolower($proposalStatus) == strtolower(Yii::t('discussion', 'Show'))) {
            if ($tag['scheme'] == TAG_SCHEME && $tag['name'] == $tagName) {
              $tagss['weight'] = $tag['weight'] + 1;
            }
            if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
              $tagss['weight'] = $tag['weight'] + 1;
            }
          } else {
            if ($tag['scheme'] == TAG_SCHEME && $tag['name'] == $tagName) {
              if ($tag['weight'] > 0) {
                $tagss['weight'] = $tag['weight'] - 1;
              }
            }
            if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
              if ($tag['weight'] > 0) {
                $tagss['weight'] = $tag['weight'] - 1;
              }
            }
          }
          $newTags[] = $tagss;
        }
      }
      $aggregatorManager->id = $id;
      $aggregatorManager->tags = $newTags;
      return $aggregatorManager->updateProposalHeatMap();
    }
  }

  /**
   * updateLinkCount
   *
   * This function is used for update link count tag based on link status
   */
  public function updateLinkCount($id, $status) {
    $aggregatorManager = new AggregatorManager();
    $newTags = array();
    $proposal = $aggregatorManager->getEntry('', '', $id, '', '', '', '', '', '', '', '', '', array(), '', 'tags', '', '', '', '', '');
    if (array_key_exists(0, $proposal)) {
      if (array_key_exists('tags', $proposal[0])) {
        $tags = $proposal[0]['tags'];
        foreach ($tags as $tag) {
          $tagss = array();
          $tagss = $tag;
          if (strtolower($status) == strtolower(Yii::t('discussion', 'Show'))) {
            if ($tag['scheme'] == LINK_COUNT_TAG_SCEME) {
              $tagss['weight'] = $tag['weight'] + 1;
            }
          } else {
            if ($tag['scheme'] == LINK_COUNT_TAG_SCEME) {
              if ($tag['weight'] > 0) {
                $tagss['weight'] = $tag['weight'] - 1;
              }
            }
          }
          $newTags[] = $tagss;
        }
      }
      $aggregatorManager->id = $id;
      $aggregatorManager->tags = $newTags;
      return $aggregatorManager->updateProposal();
    }
  }

  /**
   * actionProposalDetails
   * This function is used to get details for single proposal in a discussion.
   */
  public function actionProposalDetails() {
    $discussion = new Discussion;
    $discussion->slug = $_GET['slug'];
    $details = $discussion->getDiscussionDetail();
    $summary = $details['summary'];
    $title = $details['title'];
    $discussion->getDiscussionDetail();
    $aggregatorManager = new AggregatorManager();
    $proposal = $aggregatorManager->getEntry('', '', $_GET['id'], '', '', '', '', '', '', '', '', '', array(), '', 'title,status,author,id,content,related,tags', '', '', '', '', '');
    $allUser = array();
    $adminUser = array();
    $author = array();
    $all = $this->getTriangleLayout();
    $userAdditionInfo = array();
    if (!empty($proposal)) {
      if (array_key_exists('tags', $proposal[0])) {
        foreach ($proposal[0]['tags'] as $tag) {
          if ($tag['scheme'] == TAG_SCHEME) {
            $proposal[0]['weightmap'][$tag['name']] = $tag['weight'];
          }
        }
      }
      $proposal[0]['content']['description'] = htmlspecialchars_decode($proposal[0]['content']['description']);
      $proposal[0]['content']['summary'] = htmlspecialchars_decode($proposal[0]['content']['summary']);
      $author[] = $proposal[0]['author']['slug'];
      $opinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', '', 'link{' . OPINION_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '', 'status,author,id,content,tags,creation_date', '', '', trim('proposal,' . $proposal[0]['id']), CIVICO);
      foreach ($opinions as $key => $opinion) {
        if (array_key_exists('tags', $opinion) && !empty($opinion['tags'])) {
          foreach ($opinion['tags'] as $tag) {
            if ($tag['scheme'] == TAG_SCHEME) {
              $opinions[$key]['weightmap'][$tag['name']] = $tag['weight'];
            }
          }
          $author[] = $opinion['author']['slug'];
        } elseif (array_key_exists('count', $opinion)) {
          unset($opinions[$key]);
        }
      }
      $proposal[0]['opinions'] = $discussion->getClassOfOpinion($opinions);
      $links = $aggregatorManager->getEntry(ALL_ENTRY, '', '', '', 'link{' . LINK_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '-creation_date', 'status,author,id,content', '', '', trim('proposal,' . $proposal[0]['id']), CIVICO);
      foreach ($links as $key => $link) {
        if (array_key_exists('count', $link)) {
          unset($links[$key]);
        }
      }
      $proposal[0]['links'] = $links;
      $discussionController = new UserController('user');
      $userAdditionInfo = $discussionController->getUserAdditionalInfo($author);
      $identityManager = new UserIdentityAPI();
      $authorsEmails = $identityManager->getUserDetail(IDM_USER_ENTITY, array('id' => $author), true, false);
      if (array_key_exists('_items', $authorsEmails) && !empty($authorsEmails['_items'])) {
        if (isModuleExist('rbacconnector') == false) {
          throw new Exception(Yii::t('discussion', 'rbacconnector module is missing'));
        }
        $module = Yii::app()->getModule('rbacconnector');
        if (empty($module)) {
          throw new Exception(Yii::t('discussion', 'rbacconnector module is missing or not defined'));
        }
        foreach ($authorsEmails['_items'] as $user) {
          $allUser[$user['_id']] = $user['email'];
          $isAdmin = User::checkPermission($user['email'], 'is_admin');
          if ($isAdmin) {
            $adminUser[$user['_id']] = $user['email'];
          }
        }
      }
      //update proposal tag for removing admin opinion (on triangle)
      if (!empty($adminUser)) {
        if (array_key_exists('tags', $proposal[0]) && !empty($proposal[0]['opinions'])) {
          foreach ($proposal[0]['opinions'] as $opinion) {
            if (!array_key_exists($opinion['author']['slug'], $adminUser)) {
              continue;
            }
            if (array_key_exists('index', $opinion)) {
              foreach ($proposal[0]['tags'] as &$proposalTag) {
                if ($proposalTag['scheme'] == TAG_SCHEME &&
                        $proposalTag['slug'] == $opinion['index'] && $proposalTag['weight'] > 0) {
                  $proposalTag['weight'] -= 1;
                  break;
                }
              }
              if (array_key_exists('weightmap', $proposal[0])) {
                if (array_key_exists($opinion['index'], $proposal[0]['weightmap']) &&
                        $proposal[0]['weightmap'][$opinion['index']] > 0) {
                  $proposal[0]['weightmap'][$opinion['index']] -= 1;
                }
              }
            }
          }
        }
      }
    }
    $this->layout = 'singleProposal';
    $this->render('singleProposal', array(
      'summary' => $summary,
      'title' => $title,
      'proposal' => $proposal,
      'understanding' => $all,
      'user' => $userAdditionInfo,
      'question' => json_decode(ADDITIONAL_INFORMATION, TRUE),
      'adminUser' => $adminUser
    ));
  }

  /**
   * This function is used to get/set configuraion.
   */
  public function actionConfiguration() {
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    $isAdmin = checkPermission('admin');
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    $config = new Configuration();
    $config->type = 'config';
    $configurations = $config->get();
    $stripped = array();
    $proposalSortingBase = array();
    foreach ($configurations as $configuration) {
      $configuration['value'] = htmlspecialchars_decode($configuration['value']);
      if ($configuration['name_key'] == 'user_additional_info_question') {
        $configuration['value'] = explode(',', $configuration['value']);
        $configuration['value'] = array_map('trim', $configuration['value']);
      }
      if ($configuration['name_key'] == 'footer_html') {
         $configuration['value'] = stripslashes(htmlspecialchars($configuration['value']));
      }
      $stripped[$configuration['name_key']] = $configuration;
    }
    if (!empty($_POST)) {
      if ($_POST['key'] == 'moderators_email' && !empty($_POST['value'])) {
        $this->_saveModeratorsEmail($_POST['value']);
      }
      $value = htmlspecialchars($_POST['value']);
      if ($_POST['key'] == 'footer_html') {
        $value =  trim(addslashes(htmlspecialchars(html_entity_decode($_POST['value']))));
      }
      $config->key = $_POST['key'];
      $config->type = 'config';
      $config->value = $value;
      $config->save();
    }
    $additonalInformationQuestion = array();
    if (defined('ADDITIONAL_INFORMATION')) {
      $additonalInformationQuestion = json_decode(ADDITIONAL_INFORMATION, TRUE);
    }
    if (defined('PROPOSAL_SORTING_BASE')) {
      $proposalSortingBase = json_decode(PROPOSAL_SORTING_BASE, true);
    }
    $cs = Yii::app()->getClientScript();
    $cs->registerScriptFile(THEME_URL . 'js/configuration.js', CClientScript::POS_END);
    $this->render('configuration', array('configurations' => $stripped, 'additional_info_question' => $additonalInformationQuestion,
      'proposal_sorting' => $proposalSortingBase));
  }

  /**
   * actionSaveTranslatedProposal
   * function is used for translate and save proposal
   */
  public function actionSaveTranslatedProposal() {
    $translation = new Translation;
    $translated = $translation->saveTranslatedProposal($_GET['proposal_id'], $_GET['trans_language'], $_GET['translate']);
    echo json_encode($translated);
    die;
  }

  /**
   * actionSaveTranslatedOpinion
   * function is used for translate and save opinion
   */
  public function actionSaveTranslatedOpinion() {
    $translation = new Translation;
    $translated = $translation->saveTranslatedOpinion($_GET['proposal_id'], $_GET['trans_language'], $_GET['translate']);
    echo json_encode($translated);
    die;
  }

  /**
   * actionReports
   *
   * This function is used to show all proposals
   */
  public function actionReports() {
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    //redirect to login page if it comes throug external url
    if (isUserLogged() == false && isset($_SERVER['REDIRECT_URL'])) {
      $this->redirect(BASE_URL . 'login?back=' . $_SERVER['REDIRECT_URL']);
    }
    $isHighlighter = checkPermission('can_mark_highlighted');
    $canShowHideOpinion = checkPermission('can_show_hide_opinion');
    $canAccessReport = checkPermission('access_report');
    if ($isHighlighter == FALSE && $canShowHideOpinion == FALSE && $canAccessReport == FALSE) {
      $this->redirect(BASE_URL);
    }
    $discussion = new Discussion();
    $discussion->slug = $_GET['slug'];
    $discussionInfo = $discussion->getDiscussionDetail();
    if (empty($discussionInfo)) {
      $this->redirect(BASE_URL);
    }
    $discussionTopics = array_filter(explode(',', $discussionInfo['topic']));
    $this->discussionId = $discussionInfo['id'];
    $discussionDetail = $this->getDiscussionProposalOpininonLinksForNonAdminUser();
    if (!empty($discussionDetail)) {
      $discussionDetail['discussionTitle'] = $discussionInfo['title'];
      $discussionDetail['slug'] = $_GET['slug'];
      $discussionDetail['discussionId'] = $discussionInfo['id'];
      $discussionDetail['topics'] = $discussionTopics;
      $discussionDetail['title_char'] = intval(Yii::app()->globaldef->params['max_char_title']);
      $discussionDetail['intro_char'] = intval(Yii::app()->globaldef->params['max_char_intro']);
    }
    $this->render('reports', $discussionDetail);
  }

  /**
   * actionExport
   *
   * This function is used to show all proposals
   */
  public function actionExport() {
    $isAdmin = checkPermission('access_report');
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    $getProposalForAllDiscussion = FALSE;
    $allProposals = array();
    $discussion = new Discussion();
    if ($_GET['id'] == 'all') {
      $getProposalForAllDiscussion = TRUE;
      $discussions = $discussion->getDiscussionDetail();
    } else {
      $discussion->id = $_GET['id'];
      $discussions[] = $discussion->getDiscussionDetail();
    }
    foreach ($discussions as $discusion) {
      $discussionDetail[$discusion['id']] = $discusion;
    }
    if ($_GET['type'] == 'csv') {
      $this->_downloadProposalCsv($discussionDetail);
    }
    if (!empty($discussionDetail)) {
      $discussionDetail = array_pop($discussionDetail);
    }
    $headings = array(
      Yii::t('discussion', 'Discussion Title'),
      Yii::t('discussion', 'Proposal Title'),
      Yii::t('discussion', 'Proposal Summary'),
      Yii::t('discussion', 'Description'),
      Yii::t('discussion', 'Author'),
      Yii::t('discussion', 'Creation Date'),
      Yii::t('discussion', 'Number of Opinions'),
      Yii::t('discussion', 'Number of Links'),
      Yii::t('discussion', 'Status'));
    if ($_GET['type'] == 'excel') {
      goto Excel;
    } else if ($_GET['type'] == 'pdf') {
      $this->discussionId = $_GET['id'];
      $detailContent = $this->getDiscussionProposalOpininonLinksForNonAdminUser();
      $adminController = new AdminController('admin');
      $adminController->actionPdfGenerate($detailContent, $discussionDetail);
    } else {
      $this->redirect(BASE_URL . 'admin/discussion/list');
    }
    Excel:
    $allProposals = $discussion->getProposalForAdmin(true, $getProposalForAllDiscussion);
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator(CIVICO);
    $rowCnt = count($allProposals) + 1;
    //preapre header
    $rowNumber = 1;
    $col = 'A';
    $objPHPExcel->getActiveSheet()->getStyle("A1:H1")->getFont()->setBold(true);
    foreach ($headings as $heading) {
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $heading);
      $col++;
    }
    // Loop through the result set
    $rowNumber++;
    foreach ($allProposals as $proposal) {
      $col = 'A';
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $discussionDetail['title']);
      $col++;
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, htmlspecialchars_decode(strip_tags($proposal['title'])));
      $col++;
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, htmlspecialchars_decode(strip_tags($proposal['content']['description'])));
      $col++;
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $proposal['author']['name']);
      $col++;
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $proposal['creation_date']);
      $col++;
      foreach ($proposal['tags'] as $tag) {
        if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
          $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $tag['weight']);
          $col++;
        }
        if ($tag['scheme'] == LINK_COUNT_TAG_SCEME) {
          $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $tag['weight']);
          $col++;
        }
      }
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $proposal['status']);
      $col++;
      $rowNumber++;
    }
    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);
    foreach (range('A', 'G') as $columnID) {
      $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
    }
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="report_' . date("Ymd") . '.xls"');
    header('Cache-Control: max-age=0');

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;
  }
  /**
   * actionEditProposal
   * function is used for update proposal (title, decription and summary)
   * @author Pradeep Kumar <pradeep@incaendo.com>
   */
  public function actionEditProposal() {
    try {
      if (!empty($_POST)) {
        if (array_key_exists('summary', $_POST) && !empty($_POST['summary'])) {
            $_POST['summary'] = nl2br($_POST['summary']);
        }
        if (array_key_exists('description', $_POST) && !empty($_POST['description'])) {
            $_POST['description'] = nl2br($_POST['description']);
        }
        $postData = array_map('userInputPurifier', $_POST);
        $postData = array_map('trim', $postData);
        if (array_key_exists('title', $postData) && empty($postData['title'])) {
          throw new Exception(Yii::t('discussion', 'Title can not be empty'));
        }
        if (array_key_exists('summary', $postData) && empty($postData['summary'])) {
          throw new Exception(Yii::t('discussion', 'Introduction can not be empty'));
        }
        if (array_key_exists('description', $postData) && empty($postData['description'])) {
          throw new Exception(Yii::t('discussion', 'Description can not be empty'));
        }
        if (array_key_exists('hasTopics', $postData) && $postData['hasTopics'] == true) {
          if (!array_key_exists('topics', $postData)) {
            throw new Exception(Yii::t('discussion', 'One topic should be selected'));
          }
        }
        $aggregatorMgr = new AggregatorManager();
        $aggregatorMgr->updateProposalDescription = true;
        $aggregatorMgr->id = $postData['proposal_id'];
        $aggregatorMgr->title = $postData['title'];
        $aggregatorMgr->summary = $postData['summary'];
        $aggregatorMgr->description = $postData['description'];
        $entrySubmissionResponse = $aggregatorMgr->updateProposal();
        if (array_key_exists('success', $entrySubmissionResponse) && !empty($entrySubmissionResponse['success'])) {
          Yii::log('', INFO, 'Failed to update proposal where proposal id'. $postData['proposal_id']);
        }
      }
    } catch (Exception $e) {
      Yii::log('Error in update proposal', ERROR, $e->getMessage());
    }
    $this->redirect(BASE_URL . 'admin/discussion/proposal/list/' . $_GET['slug']);
  }

  /**actionHighlightProposal
   *
   * This function is used to highlight / unhighlight proposal.
   * It handles only ajax request.
   */
  public function actionHighlightProposal() {
    try {
      if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
        $this->actionError();
        exit;
      }
      $response = array(
        'success' => false,
        'msg' => ''
      );
      $discussion = new Discussion();
      if (!array_key_exists('slug', $_GET) || empty($_GET['slug'])) {
        $this->redirect(BASE_URL);
      }
      if(!array_key_exists('id', $_GET) || empty($_GET['id'])) {
        $this->redirect(BASE_URL);
      }
      if(!array_key_exists('tag', $_GET) || empty($_GET['tag'])) {
        $this->redirect(BASE_URL);
      }
      $discussion->slug = $_GET['slug'];
      $discussion->id = $_GET['id'];
      $proposalTags = strtolower($_GET['tag']);
      $proposalAllTags = $discussion->getProposalTags();
      if(empty($proposalAllTags)) {
        throw new Exception('Proposal tags array is empty.');
      }
      if (!array_key_exists(0, $proposalAllTags) || empty($proposalAllTags[0])
        || !array_key_exists('tags', $proposalAllTags[0]) || empty($proposalAllTags[0]['tags'])) {
        throw new Exception('Proposal tags array is empty.');
      }
      $aggregatorManager = new AggregatorManager();
      $aggregatorManager->id = $discussion->id;
      $proposalTagArray['scheme'] = HIGHLIGHT_PROPOSAL_TAG_SCEME;
      $proposalTagArray['name'] = HIGHLIGHT_TAG_NAME;
      $proposalTagArray['slug'] = sanitization(HIGHLIGHT_TAG_NAME);
      if ($proposalTags == HIGHLIGHT_TAG_NAME) {
        if (!in_array($proposalTagArray, $proposalAllTags[0]['tags'])) {
          array_push($proposalAllTags[0]['tags'], $proposalTagArray);
        }
      } else {
        $key = array_search($proposalTagArray, $proposalAllTags[0]['tags']);
        if (isset($key)) {
          unset($proposalAllTags[0]['tags'][$key]);
        }
      }
      $aggregatorManager->tags = $proposalAllTags[0]['tags'];
      $response = $aggregatorManager->updateProposal();
      if (!$response['success']) {
        Yii::log($response['msg'], ERROR, 'Error occurred in actionHighlightProposal');
      }
    } catch(Exception $e) {
      $response['msg'] = Yii::t('discussion', 'An error occurred');
      Yii::log($e->getMessage() , ERROR, 'Error caused in actionHighlightProposal');
    }
    echo json_encode($response);
    exit;
  }

  /**submitOpinionAnswer
   * This method is used to submit answer on opinion.
   * This method is only for ajax requests.
   */
  public function actionSubmitOpinionAnswer() {
    try {
      if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
        $this->actionError();
        exit;
      }
      $response = array(
        'success' => false,
        'data' => '',
        'msg' => ''
      );
      $postData = $_POST;
      if (!array_key_exists('opinion_id', $postData) || empty($postData['opinion_id'])) {
        throw new Exception('Opinion Id should not be empty.');
      }
      if (!array_key_exists('submitted_answer', $postData) || empty($postData['submitted_answer'])) {
        throw new Exception('Submitted Answer should not be empty.');
      }
      if (!array_key_exists('author_name', $postData) || empty($postData['author_name'])) {
        throw new Exception('Author Name should not be empty.');
      }
      if (!array_key_exists('author_slug', $postData) || empty($postData['author_slug'])) {
        throw new Exception('Author Slug should not be empty.');
      }
      if (!array_key_exists('proposal_id', $postData) || empty($postData['proposal_id'])) {
        throw new Exception('Proposal Id should not be empty.');
      }
      $aggregatorManager = new AggregatorManager();
      $aggregatorManager->description = userInputPurifier($postData['submitted_answer']);
      $aggregatorManager->authorName = $postData['author_name'];
      $aggregatorManager->authorSlug = $postData['author_slug'];
      $aggregatorManager->relationType = 'opinion';
      $aggregatorManager->relatedTo = $postData['opinion_id'];
      $aggregatorManager->source = CIVICO;
      $status = $aggregatorManager->saveAnswerOnOpinion();
      if ($status['success'] == true) {
        $response['success'] = true;
        $response['data'] = $status['opinion_answer_text'];
        $response['id'] = $status['id'];
        $response['msg'] = Yii::t('discussion', 'Your reply has been saved successfully');
      }
    } catch(Exception $e) {
      $response['success'] = false;
      Yii::log('Error caused in actionSubmitOpinionAnswer', ERROR, $e->getMessage());
    }
    echo json_encode($response);
    exit;
  }

  /**
   * actionSaveTrianglePosition
   * function is used for save triangle position only
   * opinion description is empty
   * this function serves only ajax request
   */
  public function actionSaveTrianglePosition() {
    $response = array('success' => false, 'msg' => '', 'data' => array());
    if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
      try {
        $_POST = array_map('userInputPurifier', $_POST);
        $discussion = new Discussion();
        $saveOpinion = $discussion->saveOpinion(true);
        if (array_key_exists('opinion_id', $saveOpinion)) {
          $response['data']['opinion_id'] = $saveOpinion['opinion_id'];
        }
        if (array_key_exists('heatmap', $saveOpinion)) {
          $response['data']['opinion'] = $saveOpinion['heatmap'];
        }
        if (array_key_exists('success', $saveOpinion) && $saveOpinion['success'] == true) {
          $response['success'] = true;
        }
      } catch (Exception $e) {
        $response['msg'] = $e->getMessage();
        Yii::log('Response in actionSaveTrianglePosition', ERROR, $e->getMessage());
      }
      echo json_encode($response);
      exit;
    } else {
      Yii::log('Error in actionSaveTrianglePosition', ERROR, 'this function serve only ajax request');
      $this->render('error404');
    }
  }

  /**
   * actionDocumentation
   * function is used for showing static content
   */
  public function actionDocumentation() {
    $this->render('document');
  }

  /**
   * actionSaveOrder
   * funtion is used to save sorting order
   * @author Kuldeep Singh<kuldeep@incaendo.com>
   */
  public function actionSaveDiscussionOrder() {
    $isAdmin = checkPermission('admin');
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    $discussionAPI = new DiscussionAPI();
    $response = array('success' => false, 'msg' => '', 'data' => array());
    if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
      $this->redirect(BASE_URL);
    }
    try {
      $discussionDetails = array_map('trim', $_GET);
      if (!empty($discussionDetails)) {
        if (array_key_exists('discussion_slug', $discussionDetails) && empty($discussionDetails['discussion_slug'])) {
          throw new Exception(Yii::t('discussion', 'Invalid Discussion'));
        }
        if (array_key_exists('sorting_order', $discussionDetails) && !isset($discussionDetails['sorting_order'])) {
          throw new Exception(Yii::t('discussion', 'Sorting order can not be empty'));
        } else if (!is_numeric($discussionDetails['sorting_order'])) {
          throw new Exception(Yii::t('discussion', 'Only Numbers are allowed'));
        }
        $discussionAPI->discussionSlug = $discussionDetails['discussion_slug'];
        $discussionAPI->sortingOrder = $discussionDetails['sorting_order'];
        $isSaved = $discussionAPI->saveDiscussionSortingOrder();
        if ($isSaved == 1) {
          $response['success'] = true;
        } else {
          Yii::log('Failed to save discussion sorting order', DEBUG, 'Error in actionSaveDiscussionOrder');;
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in actionSaveDiscussionOrder');
      $response['msg'] = $e->getMessage();
    }
    echo json_encode($response);
    exit;
  }

  /**
   * _saveModeratorsEmail
   * function is used for saving moderator email id.
   * Moderator should be admin user.
   * @param string $moderators  - comma seperated email id for moderator
   * @return void
   * @author Pradeep <pradeep@incaendo.com>
   */
  private function _saveModeratorsEmail($moderators) {
    try {
      $response = array('success' => false, 'msg' => '', 'data' => '');
      $isAdmin = checkPermission('admin');
      if (!empty($moderators) && $isAdmin == true) {
        $emailIds = explode(',', $moderators);
        $emailIds = array_map('trim', $emailIds);
        $moderatorEmail = array();
        foreach ($emailIds as $emailId) {
          if (!empty($emailId)) {
            if (filter_var($emailId, FILTER_VALIDATE_EMAIL) == TRUE) {
              if (isModuleExist('rbacconnector') == false) {
                throw new Exception(Yii::t('discussion', 'rbacconnector module is missing'));
              }
              $module = Yii::app()->getModule('rbacconnector');
              if (empty($module)) {
                throw new Exception(Yii::t('discussion', 'rbacconnector module is missing or not defined'));
              }
              $isAdmin = User::checkPermission($emailId, 'is_admin');
              $canPostAnswers = User::checkPermission($emailId, 'can_post_answers_on_opinion');
              $canHighlight = User::checkPermission($emailId, 'can_mark_highlighted');
              $canShowHideOpinion = User::checkPermission($emailId, 'can_show_hide_opinion');
              if (!(($isAdmin == TRUE) || ($canHighlight == TRUE && $canPostAnswers == TRUE && $canShowHideOpinion == TRUE))) {
                throw new Exception(Yii::t('discussion', 'email id is not valid for moderator'));
              }
            } else {
              throw new Exception(Yii::t('discussion', 'is not valid email id'));
            }
            $moderatorEmail[] = $emailId;
          }
        }
        $moderatorEmail = implode(', ', $moderatorEmail);
        $config = new Configuration();
        $config->type = 'config';
        $config->key = $_POST['key'];
        $config->value = $moderatorEmail;
        $saveModerator = $config->save();
        if (is_numeric($saveModerator)) {
          $response['success'] = TRUE;
          $response['data'] = $moderatorEmail;
        } else {
          $response['msg'] = Yii::t('discussion', 'An error occured in saving moderator, Please retry.');
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage() . $emailId, ERROR, 'Error in saving moderators email');
      $response['msg'] = $emailId . ' ' . Yii::t('discussion', $e->getMessage());
    }
    echo json_encode($response);
    exit;
  }
  /**
   * actionUserDetail
   * This function is used to get author email
   * @return void
   * @author Rahul Tripathi <rahul@incaendo.com>
   */
  public function actionUserDetail() {
    try {
      $isAdmin = checkPermission('admin');
      if ($isAdmin == false) {
        $this->redirect(BASE_URL);
      }
      $response = array('success' => false, 'msg' => '', 'data' => array());
      if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
        $this->redirect(BASE_URL);
      }
      $authorId = '';
      if (array_key_exists('author_id', $_GET) && !empty($_GET['author_id'])) {
        $authorId = $_GET['author_id'];
      }
      $identityManager = new UserIdentityAPI();
      $authorDetail = $identityManager->getUserDetail(IDM_USER_ENTITY, array('id' => $authorId));
      $authorEmail = '';
      if (array_key_exists('_items', $authorDetail) && array_key_exists(0, $authorDetail['_items']) && array_key_exists('email', $authorDetail['_items'][0])) {
        $authorEmail = $authorDetail['_items'][0]['email'];
      }
      $response['data']['author_email_id'] = '';
      if (filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
        $response['data']['author_email_id'] = $authorEmail;
      }
      $response['data']['site_name'] = SITE_NAME;
      $response['success'] = TRUE;
    } catch(Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in fetching author email actionUserDetail');
      $response['msg'] = Yii::t('discussion', 'An internal error occured. Please retry');
    }
    echo json_encode($response);
    exit;
  }

  /**
   * actionSaveProposalSortingOrder
   * Function is used for save proposal sorting order.
   * This function is used only by ajax request
   * It update or save sorting order on the basis of proposal id.
   */
  public function actionSaveProposalSortingOrder() {
    if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
      $this->redirect(BASE_URL);
    }
    $response = array('success' => FALSE, 'msg' => '');
    try {
      $isAdmin = checkPermission('admin');
      if ($isAdmin == FALSE) {
        throw new Exception(Yii::t('discussion', 'Proposal is updated only by admin user'));
      }
      if (!array_key_exists('sorting_order', $_GET) || !array_key_exists('proposal_id', $_GET)) {
        throw new Exception(Yii::t('discussion', 'Sorting order or proposal id is empty'));
      }
      $aggregatorManager = new AggregatorManager();
      $aggregatorManager->id = $_GET['proposal_id'];
      $aggregatorManager->tags = $this->_prepareProposalSortOrderTag($_GET['proposal_id'],
        $_GET['sorting_order']);
      $updateProposal = $aggregatorManager->updateProposal();
      if ($updateProposal['success'] == TRUE) {
        $response['success'] = TRUE;
        $response['msg'] = Yii::t('discussion', 'Proposal order has been saved successfully');
      } else {
        $response['msg'] = Yii::t('discussion', 'Some technical problem occurred, For more detail check log file');
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in actionSaveProposalSortingOrder');
      $response['msg'] = $e->getMessage();
    }
    echo json_encode($response);
    exit;
  }

  /**
   * _prepareProposalSortOrderTag
   * function prepare tag for add sorting order on a proposal
   * @param array $extingProposalTag - all tags of proposal
   * @param int $sortingOrder -  sorting order of proposal
   * @return array $proposalTag
   */
  private function _prepareProposalSortOrderTag($proposalId, $sortingOrder) {
    try {
      $tags = array();
      $existSortingOrder = FALSE;
      $discussion = new Discussion();
      $discussion->id = $proposalId;
      $proposalAllTags = $discussion->getProposalTags();
      if (is_array($proposalAllTags) && array_key_exists(0, $proposalAllTags) &&
        array_key_exists('tags', $proposalAllTags[0]) && !empty($proposalAllTags[0]['tags'])) {
        $tags = $proposalAllTags[0]['tags'];
        foreach ($tags as &$tag) {
          if ($tag['scheme'] == PROPOSAL_SORTING_TAG_SCHEME) {
            $tag['weight'] = trim($sortingOrder);
            $existSortingOrder = TRUE;
          }
        }
      }
      if ($existSortingOrder == FALSE) {
        $tags[] = array(
          'name' => 'sort_order',
          'slug' => 'sort_order',
          'scheme' => PROPOSAL_SORTING_TAG_SCHEME,
          'weight' => trim($sortingOrder)
        );
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in actionSaveProposalSortingOrder');
    }
    return $tags;
  }

  /**
   * _proposalSorting
   * function is used for sorting proposal according to set value in config
   * It can sort proposal on the basis of :
   *  1. Alphabetic
   *  2. No of opinion
   *  3. Custom weight
   * @param array $proposals - all proposal to be sorted
   * @return array $sortedProposal - sorted proposal
   */
  private function _proposalSorting($proposals) {
    try {
      $sortedProposal = array();
      $sortOrder = '';
      if (array_key_exists('proposal_sorting_base', Yii::app()->globaldef->params)) {
        $sortOrder = Yii::app()->globaldef->params['proposal_sorting_base'];
      }
      if (empty($sortOrder)) {
        return $proposals;
      }
      $proposalCount = count($proposals);
      switch ($sortOrder) {
        case 'albhabetical':
          foreach ($proposals as $proposal) {
            $sortedProposal[strtoupper($proposal['title'])] = $proposal;
          }
          ksort($sortedProposal, 6);
          break;
        case 'opinion_count':
          $unSortedProposal = array();
          foreach ($proposals as $proposal) {
            if (array_key_exists('tags', $proposal)) {
              $sort = FALSE;
              foreach ($proposal['tags'] as $tag) {
                if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME && $tag['weight'] != 0) {
                  $key = $proposalCount + $tag['weight'];
                  if (array_key_exists($key, $sortedProposal)) {
                    ++$proposalCount;
                    $key = $proposalCount + $tag['weight'];
                  }
                  $sortedProposal[$key] = $proposal;
                  $sort = TRUE;
                }
              }
            }
            if ($sort === FALSE) {
              $unSortedProposal[] = $proposal;
            }
          }
          krsort($sortedProposal);
          $sortedProposal = array_merge($sortedProposal, $unSortedProposal);
          break;
        case 'custom_weight':
          $unSortedProposal = array();
          foreach ($proposals as $proposal) {
            if (array_key_exists('tags', $proposal)) {
              $sort = FALSE;
              foreach ($proposal['tags'] as $tag) {
                if ($tag['scheme'] == PROPOSAL_SORTING_TAG_SCHEME && $tag['weight'] != 0) {
                  $sortedProposal[$tag['weight']] = $proposal;
                  $sort = TRUE;
                }
              }
            }
            if ($sort === FALSE) {
              $unSortedProposal[] = $proposal;
            }
          }
          ksort($sortedProposal);
          $sortedProposal = array_merge($sortedProposal, $unSortedProposal);
          break;
      }
    } catch(Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in _proposalSorting function');
    }
    if (empty($sortedProposal)) {
      $sortedProposal = $proposals;
    }
    return $sortedProposal;
  }

  /**
   * actionHomePageConfig
   * This function is used for saving and getting Home page configuration.
   */
  public function actionHomePageConfig() {
    $isAdmin = checkPermission('configure_home_page');
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    $cs = Yii::app()->getClientScript();
    $config = new Configuration();
    $config->type = 'homeconfig';
    $message = array(
      'success' => 0,
      'msg' => ''
    );
    $homeDetails =array();
    if (!empty($_POST)) {
      try {
        $homeConfigDetails = $_POST;
        if (array_key_exists('homeMainLogo', $_FILES) && $_FILES['homeMainLogo']['error'] != 4) {
          $response = $this->uploadImage(UPLOAD_DIRECTORY, 'homeMainLogo');
          if (array_key_exists('msg', $response) || $response['success'] == false || empty($response['img'])) {
            throw new Exception(Yii::t('discussion', 'Problem uploading home main logo'));
          } else if(array_key_exists('img', $response) && !empty($response['img'])
            && $response['success'] == true) {
            $homeDetails['main_logo'] = $response['img'];
          }
        }
        if (array_key_exists('homeSubLogo', $_FILES) && $_FILES['homeSubLogo']['error'] != 4) {
          $response = $this->uploadImage(UPLOAD_DIRECTORY, 'homeSubLogo');
          if (array_key_exists('msg', $response) || $response['success'] == false || empty($response['img'])) {
            throw new Exception(Yii::t('discussion', 'Problem uploading home sub logo'));
          } else if(array_key_exists('img', $response) && !empty($response['img'])
            && $response['success'] == true) {
            $homeDetails['sub_logo'] = $response['img'];
          }
        }
        if (array_key_exists('homeSubLogoUrl', $homeConfigDetails) && !empty($homeConfigDetails['homeSubLogoUrl'])) {
          if ((!filter_var(trim($homeConfigDetails['homeSubLogoUrl']), FILTER_VALIDATE_URL) === false) || (trim($homeConfigDetails['homeSubLogoUrl']) == '') ) {
            $homeDetails['sub_logo_url'] = trim($homeConfigDetails['homeSubLogoUrl']);
          } else {
            throw new Exception(Yii::t('discussion', 'Please enter a valid URL'));
          }
        }
        if (array_key_exists('homeBanner', $_FILES) && $_FILES['homeBanner']['error'] != 4) {
          $response = $this->uploadImage(UPLOAD_DIRECTORY, 'homeBanner');
          if (array_key_exists('msg', $response) || $response['success'] == false || empty($response['img'])) {
            throw new Exception(Yii::t('discussion', 'Problem uploading home banner'));
          } else if(array_key_exists('img', $response) && !empty($response['img'])
            && $response['success'] == true) {
            $homeDetails['banner'] = $response['img'];
          }
        }
        if (array_key_exists('homeIntroduction', $homeConfigDetails) && !empty($homeConfigDetails['homeIntroduction'])) {
          $homeDetails['introduction_text'] = trim(addslashes(htmlspecialchars(html_entity_decode($homeConfigDetails['homeIntroduction']))));
        }
        if (array_key_exists('homeLayout', $homeConfigDetails) && !empty($homeConfigDetails['homeLayout'])) {
          if (preg_match("/^[1-9]/", $homeConfigDetails['homeLayout']) == 0) {
            throw new Exception(Yii::t('discussion', 'Please enter valid layout value'));
          }
          $homeDetails['layout'] = $homeConfigDetails['homeLayout'];
        }
        foreach ($homeDetails as $homeDetailKey=>$homeDetailValue) {
          $config->key = $homeDetailKey;
          $config->value = $homeDetailValue;
          $config->type = 'homeconfig';
          $config->save();
        }
        $message['success'] = 1;
        $message['msg'] = Yii::t('discussion', 'Configuration Saved Successfully');
      } catch(Exception $e) {
        $message['msg'] = $e->getMessage();
        Yii::log('actionHomePageConfig', ERROR, 'Error : ' . $e->getMessage());
      }
    }
    $homeConfigurations = $config->get();
    foreach($homeConfigurations as $homeConfig) {
      if ($homeConfig['name_key'] == 'introduction_text') {
        $homeDetails[$homeConfig['name_key']] = stripslashes($homeConfig['value']);
      } else {
        $homeDetails[$homeConfig['name_key']] = $homeConfig['value'];
      }
    }
    $cs->registerScriptFile(THEME_URL . 'js/homeConfig.js', CClientScript::POS_END);
    $this->render('homepageconfig', array('homeConfig' => $homeDetails, 'message' => $message));
  }

  /**
   * uploadImage
   * This function is used to check allowed image extensions and upload size limit.
   * Then finally upload image if correct.
   * @param string $directory Name of the path to save the image.
   * @param string $name Name of the image.
   * @return array
   * @throws Exception
   */
  public function uploadImage($directory, $name) {
    $response = array();
    $extention = array();
    $response['success'] = false;
    if ($_FILES[$name]['error'] != 0 ) {
      $errorMessage = setFileUploadError($_FILES[$name]['error']);
      throw new Exception(Yii::t('discussion', $errorMessage));
    }
    if (!empty($_FILES[$name]['name'])) {
      $extention = explode('.', $_FILES[$name]['name']);
      $imageExtension = end($extention);
      $allowedImageExtention = json_decode(ALLOWED_IMAGE_EXTENSION, true);
      if (!in_array($imageExtension, $allowedImageExtention)) {
        $response['msg'] = Yii::t('discussion', 'Allowed image extentions are ') . implode(', ', $allowedImageExtention) . '.';
      } else if ($_FILES[$name]['size'] > UPLOAD_IMAGE_SIZE_LIMIT) {
        $response['msg'] = Yii::t('discussion', 'Image size is big than '. UPLOAD_IMAGE_SIZE_LIMIT);
      } else {
        $imageName = uploadFile($directory, $name);
        if ($imageName) {
          $response['img'] = $directory . $imageName;
          $response['success'] = true;
        } else {
          $response['msg'] = Yii::t('discussion', 'Some error occured in image uploading');
        }
      }
    } else {
      throw new Exception(Yii::t('discussion', 'Missing File Name'));
    }
    return $response;
  }

  /**
   * _getDiscussionProposalOpinionAndAuthor
   * function is used for getting proposal count, opinion count and author
   * @param string $discussionId - discussion id
   * @return array $response - array of proposal count, opinion count and authors
   */
  private function _getDiscussionProposalOpinionAndAuthor($discussionId) {
    try {
      $response = array('proposal_count' => 0, 'opinion_count' => 0, 'author' => array(),
         'author_name' => array(), 'proposal_author_id' => array(), 'opinion_author_id' => array(),
          'opinion_voting' => 0, 'opinion' => array());
      $aggregatorManager = new AggregatorManager();
      $proposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', '', '', '', '',
      '1', '', '', '', '', array(), '', 'id, author', '', '', trim('discussion,' .
      $discussionId), CIVICO);

      foreach ($proposals as $proposal) {
        if (array_key_exists('count', $proposal)) {
          $response['proposal_count'] = $proposal['count'];
        }
        if (array_key_exists('author', $proposal) && array_key_exists('slug', $proposal['author'])
              && array_key_exists('name', $proposal['author'])) {
          $response['author'][] = $proposal['author']['slug'];
          $response['author_name'][$proposal['author']['slug']] = $proposal['author']['name'];
          $response['proposal_author_id'][] = $proposal['author']['slug'];
        }
        if (array_key_exists('id', $proposal)) {
          $opinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', '', '', '',
          '', '1', '', '', '', '', array(), '', 'id,content,author,tags', '', '', trim('proposal,' .
          $proposal['id']), CIVICO);
          $response['opinion'][$proposal['id']] = $opinions;
          foreach ($opinions as $opinion) {
            //opinion count - only for which description is added
            if (array_key_exists('content', $opinion) && array_key_exists('description', $opinion['content'])
              && !empty($opinion['content']['description'])) {
              $response['opinion_count'] += 1;
            }
            if (array_key_exists('tags', $opinion) && !empty($opinion['tags'])) {
              foreach ($opinion['tags'] as $tag) {
                if ($tag['scheme'] == INDEX_TAG_SCHEME) {
                  $response['opinion_voting'] += 1;
                }
              }
            }
            if (array_key_exists('author', $opinion) && array_key_exists('slug', $opinion['author'])
                && array_key_exists('name', $opinion['author'])) {
              $response['author'][] = $opinion['author']['slug'];
              $response['author_name'][$opinion['author']['slug']] = $opinion['author']['name'];
              $response['opinion_author_id'][] = $opinion['author']['slug'];
            }
          }
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), 'Error in _getDiscussionProposalOpinionAndAuthor');
    }

    return $response;
  }

  /**
   * actionStatistics
   * function is used for getting user statistics for a discusson
   * It uses google api for drawing chart
   */
  public function actionStatistics() {
    try {
      $response = array('success' => FALSE, 'msg' => '');
      $disucssionId = '';
      $preparedData = array();
      if (array_key_exists('id', $_GET)) {
        $disucssionId = $_GET['id'];
      }
      $discussion = new Discussion;
      $discussion->id = $disucssionId;
      $discussionInfo = $discussion->getDiscussionDetail();
      $staticsPoint = array();
      $graphData = array('age' => array(), 'age_range' => array(), 'sex' => array(),
          'education_level' => array(), 'citizenship' => array(), 'work' => array(),
          'public_authority' => array(), 'residence' => array(), 'profession' => array(),
          'association' => array());
      $finalArr = array();
      $canAccessReport = checkPermission('access_report');
      if ($canAccessReport == false || !ctype_digit($disucssionId)) {
        $this->redirect(BASE_URL);
      }
      Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
      $this->setHeader('2.0');
      if (isModuleExist('backendconnector') == false) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
      }
      if (defined('STATS')) {
        $staticsPoint = json_decode(STATS, TRUE);
      }
      $discussionDetail = $this->_getDiscussionProposalOpinionAndAuthor($disucssionId);
      $question = json_decode(ADDITIONAL_INFORMATION, TRUE);
      $userIdentityApi = new UserIdentityAPI();
      $author = array_unique($discussionDetail['author']);
      $userEmail = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('id' => $author), TRUE, false);
      $emails = array();
      if (array_key_exists('_items', $userEmail) && !empty($userEmail['_items'])) {
        foreach ($userEmail['_items'] as $email) {
          $emails[] = $email['email'];
        }
      }
      $contributorsEmail = $this->getAuthorEmail($author, TRUE);
      $userInfo = array();
      if (!empty($contributorsEmail)) {
        $userInfo = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => $contributorsEmail['user']));
      }
      if (array_key_exists('_items', $userInfo)) {
        foreach ($userInfo['_items'] as $user) {
          if (array_key_exists('age', $user)) {
            $graphData['age'][] = $user['age'];
          }
          if (array_key_exists('age-range', $user)) {
            $graphData['age_range'][] = $user['age-range'];
          }
          if (array_key_exists('education-level', $user)) {
            if (!array_key_exists($user['education-level'], $question['education_level']['value'])) {
              $graphData['education_level'][] = 'other';
            } else {
              $graphData['education_level'][] = $user['education-level'];
            }
          }
          if (array_key_exists('sex', $user) && array_key_exists(0, $user['sex']) &&
            array_key_exists($user['sex'][0], $question['sex']['value'])) {
            $graphData['sex'][] = $user['sex'][0];
          }
          if (array_key_exists('citizenship', $user) && array_key_exists($user['citizenship'], $question['citizenship']['value'])) {
            $graphData['citizenship'][] = $user['citizenship'];
          }
          if (array_key_exists('work', $user) && array_key_exists($user['work'], $question['work']['value'])) {
            $graphData['work'][] = $user['work'];
          }
          if (array_key_exists('public-authority', $user) && array_key_exists('name', $user['public-authority'])) {
            if (array_key_exists($user['public-authority']['name'], $question['public_authority']['value'])) {
              $graphData['public_authority'][] = $user['public-authority']['name'];
            }
          }
          if (array_key_exists('profile-info', $user) && !empty($user['profile-info'])) {
            if (array_key_exists('residence', $user['profile-info'])) {
              $graphData['residence'][] = $user['profile-info']['residence'];
            }
            if (array_key_exists('profession', $user['profile-info'])) {
              $graphData['profession'][] = $user['profile-info']['profession'];
            }
            if (array_key_exists('association', $user['profile-info']) &&
              array_key_exists($user['profile-info']['association'], $question['association']['value'])) {
              $graphData['association'][] = $user['profile-info']['association'];
            }
          }
        }
        foreach ($graphData as $key => $val) {
          $finalArr[$key] = array_count_values($graphData[$key]);
        }
      }
      if (!empty($finalArr)) {
        $preparedData = $this->_prepareChartData($finalArr, $question);
        $_SESSION['user']['statistics'] = $preparedData;
      } else {
        unset($_SESSION['user']['statistics']);
      }
      if (!empty(Yii::app()->globaldef->params['user_additional_info_question'])) {
        $additionalQuestion = array_map('trim', explode(",", Yii::app()->globaldef->params['user_additional_info_question']));
        foreach ($staticsPoint as $key => $value) {
          if (!in_array($key, $additionalQuestion)) {
            unset($staticsPoint[$key]);
            if (array_key_exists($key, $preparedData)) {
              unset($preparedData[$key]);
            }
          }
        }
      }
      $response['success'] = TRUE;
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in actionStatistics');
      $response['msg'] = Yii::t('discussion', 'Some technical problem occurred, For more detail check log file');
    }
    $this->render('statistics', array('satistics_point' => $staticsPoint,
                                      'response' => $response,
                                      'chart_detail' => $preparedData,
                                      'discussionTitle' => $discussionInfo['title']
                                      )
                  );
  }
  /**
   * _prepareChartData
   * function is used for prepared data showing on line chart
   * @param array $data
   * @param array $question - additional question list for user
   * @return $chartData
   */
  private function _prepareChartData($userData, $question) {
    $chartData = array();
    $chartInfo = array();
    if (defined('USER_STATISTIC_CHART_INFO')) {
      $chartInfo = json_decode(USER_STATISTIC_CHART_INFO, TRUE);
    }
    //allowed stat points for particular theme
    $statPoints = array();
    if (defined('STATS')) {
      $statPoints = json_decode(STATS, TRUE);
    }

    foreach ($userData as $key => $data) {
      $title = '';
      if (array_key_exists($key, $chartInfo) && array_key_exists('title', $chartInfo[$key])) {
        $title = $chartInfo[$key]['title'];
      }
      $header = array('X' => 'Y');
      if (array_key_exists($key, $chartInfo) && array_key_exists('header', $chartInfo[$key])) {
        $header = array($chartInfo[$key]['header'][0] => $chartInfo[$key]['header'][1]);
      }

      //key is in allowed stat points, if not then skip that data
      if (!array_key_exists($key, $statPoints)) {
        continue;
      }
      switch($key) {
        case 'age':
          $chartData['age'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $data
          );
          break;
        case 'age_range':
          $chartData['age_range'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $data
          );
          break;
        case 'sex':
          $finalData = array();
          foreach ($data as $key => $val) {
            $finalData[$question['sex']['value'][$key]] = $val;
          }
          $chartData['sex'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $finalData
          );
          break;
        case 'citizenship':
          $finalData = array();
          foreach ($data as $key => $val) {
            $finalData[$question['citizenship']['value'][$key]] = $val;
          }
          $chartData['citizenship'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $finalData
          );
          break;
        case 'work':
          $finalData = array();
          foreach ($data as $key => $val) {
            $finalData[$question['work']['value'][$key]] = $val;
          }
          $chartData['work'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $finalData
          );
          break;
        case 'education_level':
          $finalData = array();
          foreach ($data as $key => $val) {
            $finalData[$question['education_level']['value'][$key]] = $val;
          }
          $chartData['education_level'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $finalData
          );
          break;
        case 'public_authority':
          $finalData = array();
          foreach ($data as $key => $val) {
            $finalData[$question['public_authority']['value'][$key]] = $val;
          }
          $chartData['public_authority'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $finalData
          );
          break;
        case 'association':
          $finalData = array();
          foreach ($data as $key => $val) {
            $finalData[$question['association']['value'][$key]] = $val;
          }
          $chartData['association'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $finalData
          );
          break;
        case 'profession':
          $chartData['profession'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $data
          );
          break;
        case 'residence':
          $chartData['residence'] = array(
              'title' => $title,
              'header' => $header,
              'data' => $data
          );
          break;
      }
    }
    return $chartData;
  }

  /**
   * actionAllDiscussion
   * This method is used to get all discussions, their proposals, opinions and links.
   */
  public function actionAllDiscussion() {


    $canAccessReport = checkPermission('access_report');
    $canShowHideOpinion = checkPermission('can_show_hide_opinion');
    if ($canAccessReport == FALSE && $canShowHideOpinion == FALSE) {
      $this->redirect(BASE_URL);
    }
    try {
      $this->layout = 'singleProposal';
      $discussion = new Discussion;
      $details = $discussion->getDiscussionDetail();
      $discussionDetails = array();
      $allEmail = array();
      $adminEmail = array();
      foreach ($details as $detail) {
        $allProposal = array();
        $this->discussionId = $detail['id'];
        $detailContent = $this->getDiscussionProposalOpininonLinksForNonAdminUser();
        foreach ($detailContent['allProposals'] as &$proposal) {
          $proposal['content']['description'] = htmlspecialchars_decode($proposal['content']['description']);
          $proposal['content']['summary'] = htmlspecialchars_decode($proposal['content']['summary']);
          if (array_key_exists($proposal['id'], $detailContent['opinions']) &&
            array_key_exists('opinions', $detailContent['opinions'][$proposal['id']])) {
            foreach ($detailContent['opinions'][$proposal['id']]['opinions'] as $author => $opinions) {
              foreach ($opinions as $opinion) {
                $proposal['opinions'][] = $opinion;
              }
            }
          if (array_key_exists($proposal['id'], $detailContent['links']) &&
            array_key_exists('links', $detailContent['links'][$proposal['id']])) {
            foreach ($detailContent['links'][$proposal['id']]['links'] as $author => $links) {
              foreach ($links as $link) {
                $proposal['links'][] = $link;
              }
            }
          }
          }
          $allProposal[] = $proposal;
          $allEmail = array_merge($allEmail, $detailContent['emails']);
          $adminEmail = array_merge($adminEmail, $detailContent['adminEmails']);
        }

        if(isset($detail['additional_description']) && !empty($detail['additional_description'])){
         $detail['additional_description'] = nl2br($detail['additional_description']);
         $detail['additional_description'] = htmlspecialchars_decode($detail['additional_description']);
         }
         else $detail['additional_description'] = null;


        $discussionDetails[] = array(
            'title' => $detail['title'],
            'summary' => $detail['summary'],
            'descrizione_supplementare' => $detail['additional_description'],
            'discussionTimestamp' => $detail['creationDate'],
            'proposal' => $allProposal
        );
      }
      $all = $this->getTriangleLayout();
      $userAdditionInfo = $this->getUserAdditionalInfo($allEmail);
      $this->render('allDiscussion', array(
          'understanding' => $all,
          'discussionDetails' => $discussionDetails,
          'user' => $userAdditionInfo,
          'question' => json_decode(ADDITIONAL_INFORMATION, TRUE),
          'adminUser' => $adminEmail
      ));
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in actionAllDiscussion method :') . $e->getMessage());
    }
  }

  /**
   * actionAllProposal
   * This method is used to get all proposals, their opinions and links for a particular discussion.
   */
  public function actionAllProposal() {

    $isAdmin = checkPermission('admin');
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    try {
      $discussion = new Discussion;
      $discussion->slug = $_GET['slug'];
      $detail = $discussion->getDiscussionDetail();
      if (empty($detail)) {
        $this->redirect(BASE_URL);
      }
 
      $allProposal = array();
      $allEmail = array();
      $adminEmail = array();
      $this->discussionId = $detail['id'];
      $detailContent = $this->getDiscussionProposalOpininonLinksForNonAdminUser();
      foreach ($detailContent['allProposals'] as &$proposal) {

        $proposal['content']['description'] = htmlspecialchars_decode($proposal['content']['description']);
        $proposal['content']['summary'] = htmlspecialchars_decode($proposal['content']['summary']);
        if (array_key_exists($proposal['id'], $detailContent['opinions']) &&
          array_key_exists('opinions', $detailContent['opinions'][$proposal['id']])) {
          foreach ($detailContent['opinions'][$proposal['id']]['opinions'] as $author => $opinions) {
            foreach ($opinions as $opinion) {
              $proposal['opinions'][] = $opinion;
            }
          }
        if (array_key_exists($proposal['id'], $detailContent['links']) &&
          array_key_exists('links', $detailContent['links'][$proposal['id']])) {
          foreach ($detailContent['links'][$proposal['id']]['links'] as $author => $links) {
            foreach ($links as $link) {
              $proposal['links'][] = $link;
            }
          }
          }
        }
        $allProposal[] = $proposal;
        $allEmail = array_merge($allEmail, $detailContent['emails']);
        $adminEmail = array_merge($adminEmail, $detailContent['adminEmails']);
      }
      $triangle = $this->getTriangleLayout();
      $userAdditionInfo = $this->getUserAdditionalInfo($allEmail);

      if(isset($detail['additional_description']) && !empty($detail['additional_description'])){
        $detail['additional_description'] = nl2br($detail['additional_description']);
        $detail['additional_description'] = htmlspecialchars_decode($detail['additional_description']);
       }
       else $detail['additional_description'] = null;

      $this->layout = 'singleProposal';
      $this->render('allProposal', array(
        'summary' => $detail['summary'],
        'title' => $detail['title'],
        'descrizione_supplementare' => $detail['additional_description'],
        'allProposals' => $allProposal,
        'understanding' => $triangle,
        'discussionTimestamp' => $detail['creationDate'],
        'user' => $userAdditionInfo,
        'question' => json_decode(ADDITIONAL_INFORMATION, TRUE),
        'adminUser' => $adminEmail
      ));
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in actionAllProposal method :') . $e->getMessage());
    }
  }

  /**
   * actionDrawChart
   * function is sued for drawing user statistics chart
   */
  public function actionDrawChart() {
    try {
      $response = array('success' => FALSE, 'msg' => '', 'data' => array());
      $isAdmin = checkPermission('admin');
      if ($isAdmin == FALSE) {
        $this->redirect(BASE_URL);
      }
      if (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
        $this->redirect(BASE_URL);
      }
      if (!array_key_exists('chart_data', $_GET) || empty($_GET['chart_data'])) {
        throw new Exception(Yii::t('discussion', 'Data is empty for drawing chart'));
      }
      if (isset(Yii::app()->session['user']) && array_key_exists('statistics', Yii::app()->session['user'])) {
        if (array_key_exists($_GET['chart_data'], Yii::app()->session['user']['statistics'])) {
          $chartDetail = Yii::app()->session['user']['statistics'][$_GET['chart_data']];
          foreach ($chartDetail['header'] as $key => $val) {
            $chartDetail['statistic_data'][] = array((string)$key, $val);
          }
          foreach ($chartDetail['data'] as $key => $val) {
            $chartDetail['statistic_data'][] = array((string)$key, $val);
          }
          $response['data'] = $chartDetail;
        }
      }
      $response['success'] = TRUE;
    } catch (Exception $e) {
      $response['msg'] = $e->getMessage();
      Yii::log($e->getMessage(), ERROR, 'Error in drawChart');
    }
    echo json_encode($response);
    exit;
  }
  public function _downloadProposalCsv($discussionDetail) {
    $header = array(
      'discussion_title' => Yii::t('discussion', 'Discussion Title'),
      'title' => Yii::t('discussion', 'Proposal Title'),
      'summary' => Yii::t('discussion', 'Proposal Summary'),
      'description' => Yii::t('discussion', 'Description'),
      'author' => Yii::t('discussion', 'Author'),
      'creation_date' => Yii::t('discussion', 'Creation Date'),
      'status' => Yii::t('discussion', 'Status'),
      'opinion_voting_count' => Yii::t('discussion', 'Vote on triangle'),
      'text_opinion_count' => Yii::t('discussion', 'Opinion Count'),
      'total_links' => Yii::t('discussion', 'Number of Links'),
      'proposal_id' => Yii::t('discussion', 'Proposal Id'),
    );
    $additionalInfo = json_decode(ADDITIONAL_INFORMATION, TRUE);
    ksort($additionalInfo);
    foreach ($additionalInfo as $infoKey => $info) {
      $header[$infoKey] = Yii::t('discussion', $info['text']);
    }
    $allproposals = array();
    $userInfos = array();
    foreach ($discussionDetail as $discussion) {
      $this->discussionId = $discussion['id'];
      $detailContent = $this->getDiscussionProposalOpininonLinksForNonAdminUser();
      $userEmails = $detailContent['emails'];
      $addInfo = $this->getUserAdditionalInfo($userEmails);
      foreach ($detailContent['allProposals'] as $user) {
        if(array_key_exists($user['author']['slug'], $addInfo)) {
          $userInfos[$user['id']] = $addInfo[$user['author']['slug']];
        }
      }
      foreach ($additionalInfo as $key => $value) {
        foreach($userInfos as &$info) {
          if(!array_key_exists($key, $info)) {
            $info[$key] = '';
          }
          foreach ($info as $keys => &$values) {
            if (!array_key_exists($keys, $additionalInfo)) {
              unset($info[$keys]);
            }
          }
          ksort($info);
        }
      }
      $admin = new AdminController('admin');
      $allproposals[] = $admin->createDataForExport($detailContent, $discussion);
    }
    header("Content-disposition: attachment; filename=report_" . date("Ymd") .".csv");
    header("Content-Type: text/csv");
    $filePath = fopen("php://output", 'w');
    @fputcsv($filePath, $header);
    foreach ($allproposals as &$proposal) {
      foreach($proposal as &$proposalContent) {
        if(array_key_exists($proposalContent['proposal_id'], $userInfos)) {
          $proposalContent = array_merge($proposalContent, $userInfos[$proposalContent['proposal_id']]);
        }
        @fputcsv($filePath, $proposalContent);
      }
    }
    exit;
  }

  public function actionAllStatistics() {
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    $isHighlighter = checkPermission('can_mark_highlighted');
    $can_show_hide_opinion = checkPermission('can_show_hide_opinion');
    $accessReport = checkPermission('access_report');
    if ($isHighlighter == FALSE && $can_show_hide_opinion == FALSE && $accessReport == FALSE) {
      $this->redirect(BASE_URL);
    }
    $chartDetail = array();
    $discussionTitle = array();
    $question = json_decode(ADDITIONAL_INFORMATION, TRUE);
    $staticsPoint = array();
    Yii::app()->clientScript->registerCssFile(THEME_URL . 'css/bootstrap.css');
    $this->setHeader('2.0');
    if (isModuleExist('backendconnector') == false) {
      throw new Exception(Yii::t('discussion', 'backendconnector module is missing'));
    }
    $module = Yii::app()->getModule('backendconnector');
    if (empty($module)) {
      throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
    }
    if (defined('STATS')) {
      $staticsPoint = json_decode(STATS, TRUE);
    }
    $userController = new UserController('user');
    $userIdentityApi = new UserIdentityAPI();
    $discussions = $this->_getDiscussionProposalOpinionLinks();
    foreach ($discussions['discussion'] as $discussion) {
      $emails = array();
      $authors = $discussions['discussion_author'][$discussion['discussionId']];
      foreach ($authors as $author) {
        if (array_key_exists($author, $discussions['emails'])) {
          if (array_key_exists($author, $discussions['user']['user'])) {
            $emails[$author] = $discussions['emails'][$author];
          }
        }
      }
      $userInfo = array();
      if (!empty($emails)) {
        $userInfo = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => $emails));
      }
      $graphData = array();
      $finalArr = array();
      if (array_key_exists('_items', $userInfo)) {
        foreach ($userInfo['_items'] as $user) {
          if (array_key_exists('age', $user)) {
            $graphData['age'][] = $user['age'];
          }
          if (array_key_exists('age-range', $user)) {
            $graphData['age_range'][] = $user['age-range'];
          }
          if (array_key_exists('education-level', $user)) {
            if (!array_key_exists($user['education-level'], $question['education_level']['value'])) {
              $graphData['education_level'][] = 'other';
            } else {
              $graphData['education_level'][] = $user['education-level'];
            }
          }
          if (array_key_exists('sex', $user) && array_key_exists(0, $user['sex']) &&
                  array_key_exists($user['sex'][0], $question['sex']['value'])) {
            $graphData['sex'][] = $user['sex'][0];
          }
          if (array_key_exists('citizenship', $user) && array_key_exists($user['citizenship'], $question['citizenship']['value'])) {
            $graphData['citizenship'][] = $user['citizenship'];
          }
          if (array_key_exists('work', $user) && array_key_exists($user['work'], $question['work']['value'])) {
            $graphData['work'][] = $user['work'];
          }
          if (array_key_exists('public-authority', $user) && array_key_exists('name', $user['public-authority'])) {
            if (array_key_exists($user['public-authority']['name'], $question['public_authority']['value'])) {
              $graphData['public_authority'][] = $user['public-authority']['name'];
            }
          }
          if (array_key_exists('profile-info', $user) && !empty($user['profile-info'])) {
            if (array_key_exists('residence', $user['profile-info'])) {
              $graphData['residence'][] = $user['profile-info']['residence'];
            }
            if (array_key_exists('profession', $user['profile-info'])) {
              $graphData['profession'][] = $user['profile-info']['profession'];
            }
            if (array_key_exists('association', $user['profile-info']) &&
              array_key_exists($user['profile-info']['association'], $question['association']['value'])) {
              $graphData['association'][] = $user['profile-info']['association'];
            }
          }
        }
        foreach ($graphData as $key => $val) {
          $finalArr[$key] = array_count_values($graphData[$key]);
        }
      }
      $preparedData = array();
      if (!empty($finalArr)) {
        $preparedData = $this->_prepareChartData($finalArr, $question);
        foreach ($preparedData as $prepareData) {
          foreach ($prepareData['header'] as $key => $val) {
            $prepareData['statistic_data'][] = array((string) $key, $val);
          }
          foreach ($prepareData['data'] as $key => $val) {
            $prepareData['statistic_data'][] = array((string) $key, $val);
          }
        }
      }
      $chartDetail[$discussion['discussionSlug']] = $preparedData;
      $discussionTitle[$discussion['discussionSlug']] = $discussion['discussionTitle'];
    }
    $authorNames = $discussions['author_name'];
    asort($authorNames);
    $this->render('reportStatistics', array(
        'discussionInfo' => $discussions['discussion'],
        'emails' => $discussions['emails'],
        'authorNames' => $authorNames,
        'chartDetails' => $chartDetail,
        'discussionTitle' => $discussionTitle
    ));
  }

  /**
   * _getDiscussionProposalOpinionLinks
   * function is used for getting
   *  1) discussion detail
   *  2) Proposal count for each discussion
   *  3) Total no of user for each discussion
   * @return array
   */
  private function _getDiscussionProposalOpinionLinks() {
    try {
      $resp = array('discussion' => array(), 'emails' => array(), 'user' => array(),
          'author_name' => array(), 'discussion_author' => array());
      $discussionDetail = array();
      $discussion = new Discussion();
      $discussionInfo = $discussion->getDiscussionDetail();
      $authorName = array();
      $authorId = array();
      $discussionWiseAuthor = array();
      $discussionWiseProposalAuthor = array();
      $discussionWiseOpinionAuthor = array();
      $discussionAuthorId = array();
      if (!empty($discussionInfo)) {
        foreach ($discussionInfo as $info) {
          $discussionContent = $this->_getDiscussionProposalOpinionAndAuthor($info['id']);
          $discussionDetail[] = array(
            'discussionId' => $info['id'],
            'discussionTitle' => $info['title'],
            'discussionSummary' => mb_substr($info['summary'], 0, 20, "UTF-8"),
            'discussionSlug' => $info['slug'],
            'discussionAuthor' => $info['author'],
            'discussionAuthorSlug' => $info['author_id'],
            'discussionOrder' => $info['sort_id'],
            'proposalCount' => $discussionContent['proposal_count'],
            'opinionCount' => $discussionContent['opinion_count'],
            'opinionVoting' => $discussionContent['opinion_voting'],
            'opinions' => $discussionContent['opinion'],
            'userCount' => 0,
            'adminUser' => array('proposalCount' => 0, 'opinionCount' => 0)
          );
          $discussionAuthorId[] = $info['author_id'];
          $authorName = array_merge($authorName, $discussionContent['author_name']);
          $authorId = array_merge($authorId, $discussionContent['author']);
          $discussionWiseAuthor[$info['id']] = $discussionContent['author'];
          $discussionWiseProposalAuthor[$info['id']] = $discussionContent['proposal_author_id'];
          $discussionWiseOpinionAuthor[$info['id']] = $discussionContent['opinion_author_id'];
        }
      }
      $user = $this->getAuthorEmail($authorId, TRUE);
      //get non admin user count for each discussion
      foreach ($discussionDetail as &$discussion) {
        $authorId = array_unique($discussionWiseAuthor[$discussion['discussionId']]);
        foreach ($authorId as $id) {
          if (array_key_exists($id, $user['user'])) {
            $discussion['userCount'] += 1;
          }
        }
        //remove count for proposal that is submitted by admin user
        $proposalAuthorId = $discussionWiseProposalAuthor[$discussion['discussionId']];
        foreach ($proposalAuthorId as $id) {
          if (array_key_exists($id, $user['admin_user'])) {
            if ($discussion['proposalCount'] > 0) {
              $discussion['proposalCount'] -= 1;
            }
            $discussion['adminUser']['proposalCount'] += 1;
          }
        }
        //remove count for opinion that is submitted by admin user  and content is not empty
        foreach ($discussion['opinions'] as $opinions) {
          foreach ($opinions as $opinion) {
            if (array_key_exists('author', $opinion) && array_key_exists($opinion['author']['slug'], $user['admin_user'])) {
              if (!empty($opinion['content']['description']) && $discussion['opinionCount'] > 0) {
                $discussion['opinionCount'] -= 1;
              }
              foreach ($opinion['tags'] as $tag) {
                if ($tag['scheme'] == INDEX_TAG_SCHEME && $discussion['opinionVoting'] > 0) {
                  $discussion['opinionVoting'] -= 1;
                  break;
                }
              }
              $discussion['adminUser']['opinionCount'] += 1;
            }
          }
        }
      }
      $resp['discussion'] = $discussionDetail;
      //get all non admin user name
      foreach ($authorName as $authorId => $author) {
        if (array_key_exists($authorId, $user['user'])) {
          $resp['author_name'][$authorId] = $author;
        }
      }
      //get discussion author's email id
      $discussionAuthorEmail  = $this->getAuthorEmail($discussionAuthorId, TRUE);
      $user['admin_user'] = array_merge($user['admin_user'], $discussionAuthorEmail['admin_user']);
      $resp['emails'] = array_merge($user['user'], $user['admin_user']);
      $resp['user'] = $user;
      $resp['discussion_author'] = $discussionWiseAuthor;
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in _getDiscussionProposalOpinionLinks');
    }
    return $resp;
  }

  /**
   * getDiscussionProposalOpininonLinksForNonAdminUser
   * 1. Get all proposal, opinion and links of discussion
   * 2. Update proposal tag - remove admin user submitted opinion from proposal tag
   * 3. Get all admin and non admin user
   * 4. Get all admin and non admin opinions and link count
   * @return array $response - all proposal, opinions, links, admina and non admin user emails
   * @TODO - Move it to user controller
   */
  public function getDiscussionProposalOpininonLinksForNonAdminUser() {
    $response = array('allProposals' => array(), 'understanding' => array(),
        'heatMap' => array(), 'emails' => array(), 'opinions' => array(),
        'links' => array(), 'adminEmails' => array());
    try {
      $all = $this->getTriangleLayout();
      $author = array();
      $proposalWiseOpinion = array();
      $proposalWiseLink = array();
      $emails = array();
      $adminEmails = array();
      $discussion = new Discussion();
      $discussion->id = $this->discussionId;
      $heatMap = unserialize(HEATMAP_COLORS);
      $allProposals = $discussion->getProposalForAdmin(true);
      foreach ($allProposals as $key => $proposal) {
        //get opinions for each proposals
        $discussionObj = new Discussion();
        $opinionsAndLink = $discussionObj->getOpinionsAndLinks($proposal['id']);
        if (array_key_exists('opinion', $opinionsAndLink) && !empty($opinionsAndLink['opinion'])) {
          foreach ($opinionsAndLink['opinion'] as $opinion) {
            if (array_key_exists('author', $opinion) && array_key_exists('slug', $opinion['author'])) {
              $author[] = $opinion['author']['slug'];
              $proposalWiseOpinion[$proposal['id']]['opinions'][$opinion['author']['slug']][] = $opinion;
            }
          }
        }
        if (array_key_exists('link', $opinionsAndLink) && !empty($opinionsAndLink['link'])) {
          foreach ($opinionsAndLink['link'] as $link) {
            if (array_key_exists('author', $link) && array_key_exists('slug', $link['author'])) {
              $author[] = $link['author']['slug'];
              $proposalWiseLink[$proposal['id']]['links'][$link['author']['slug']][] = $link;
            }
          }
        }
        if (array_key_exists('author', $proposal) && array_key_exists('slug', $proposal['author'])) {
          $author[] = $proposal['author']['slug'];
        }
        if (array_key_exists('content', $proposal) && !empty($proposal['content'])) {
          if (array_key_exists('summary', $proposal['content']) && !empty($proposal['content']['summary'])) {
            $allProposals[$key]['content']['summary'] = htmlspecialchars_decode($proposal['content']['summary']);
          }
        }
        if (array_key_exists('content', $proposal) && !empty($proposal['content'])) {
          if (array_key_exists('description', $proposal['content']) && !empty($proposal['content']['description'])) {
            $allProposals[$key]['content']['description'] = htmlspecialchars_decode($proposal['content']['description']);
          }
        }
      }
      $author = array_unique($author);
      $userIdentityApi = new UserIdentityAPI();
      $userEmail = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('id' => $author), TRUE, false);
      if (array_key_exists('_items', $userEmail) && !empty($userEmail['_items'])) {
        foreach ($userEmail['_items'] as $email) {
          $emails[$email['_id']] = $email['email'];
          $isAdmin = checkRbacPermission($email['email'], 'is_admin');
          if ($isAdmin == TRUE) {
            $adminEmails[$email['_id']] = $email['email'];
          }
        }
      }
      //update proposal tag - remove opinion count and update triangle index for admin user opinion
      if (!empty($adminEmails)) {
        foreach ($allProposals as $key => &$proposal) {
          //set it 0. We count only those opinion for which description is not empty and not submitted by admin user.
          $proposal['totalOpinion'] = 0;
          if (array_key_exists($proposal['id'], $proposalWiseOpinion) && !empty($proposalWiseOpinion[$proposal['id']]['opinions'])) {
            foreach ($proposalWiseOpinion[$proposal['id']]['opinions'] as $userId => $adminOpinion) {
              if (array_key_exists($userId, $adminEmails)) {
                foreach ($adminOpinion as $opinion) {
                  if (array_key_exists('index', $opinion)) {
                    foreach ($proposal['tags'] as &$proposalTag) {
                      if ($proposalTag['scheme'] == TAG_SCHEME &&
                              $proposalTag['slug'] == $opinion['index'] && $proposalTag['weight'] > 0) {
                        $proposalTag['weight'] -= 1;
                        break;
                      }
                    }
                    if (array_key_exists('weightmap', $proposal)) {
                      if (array_key_exists($opinion['index'], $proposal['weightmap']) &&
                              $proposal['weightmap'][$opinion['index']] > 0) {
                        $proposal['weightmap'][$opinion['index']] -= 1;
                      }
                    }
                  }
                }
              } else {
                foreach ($adminOpinion as $opinion) {
                  if (!empty($opinion['content']['description'])) {
                    $proposal['totalOpinion'] += 1;
                  }
                }
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in getDiscussionProposalOpininonLinksForNonAdminUser');
    }
    return array('allProposals' => $allProposals, 'understanding' => $all,
      'heatMap' => $heatMap, 'emails' => $emails, 'opinions' => $proposalWiseOpinion,
      'links' => $proposalWiseLink, 'adminEmails' => $adminEmails);
  }

  /**
   * getTriangleLayout
   * function is used for triagle layout (background color)
   * @return array $triangle - triange color, message & cordinate
   */
  public function getTriangleLayout() {
    $triangle = array();
    $understanding = unserialize(UNDERSTANDING);
    foreach ($understanding as $key => $understand) {
      $xcordinates = array();
      $ycordinates = array();
      $points = explode(' ', $understand['points']);
      foreach ($points as $point) {
        if ($point != '') {
          $poi = explode(',', $point);
          $xcordinates[] = $poi[0];
          $ycordinates[] = $poi[1];
        }
      }
      $understand['x'] = ($xcordinates[0] + $xcordinates[1] + $xcordinates[2]) / 3 - 4;
      $understand['y'] = ($ycordinates[0] + $ycordinates[1] + $ycordinates[2]) / 3 + 8;
      $triangle[$key] = $understand;
    }
    return $triangle;
  }

  /**
   * getUserAdditionalInfo
   * function is used for get additional user detail on the basis of user email id
   * @param array $email  - user email
   * @return array $users - user additional information
   * @TODO - need to move it in User controller
   */
  public function getUserAdditionalInfo($emails) {
    try {
      $users = array();
      if (isModuleExist('backendconnector') == false) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
      }
      if (is_array($emails)) {
        $emails = array_unique($emails);
      }
      $userIdentityApi = new UserIdentityAPI();
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
            if (array_key_exists('sex', $user) && array_key_exists(0, $user['sex'])
            && array_key_exists($user['sex'][0], $question['sex']['value'])) {
              $users[$user['_id']]['sex'] = $question['sex']['value'][$user['sex'][0]];
            }
            if (array_key_exists('citizenship', $user)
            && array_key_exists($user['citizenship'], $question['citizenship']['value'])) {
              $users[$user['_id']]['citizenship'] = $question['citizenship']['value'][$user['citizenship']];
            }
            if (array_key_exists('education-level', $user) &&
            array_key_exists($user['education-level'], $question['education_level']['value'])) {
              $users[$user['_id']]['education_level'] = $question['education_level']['value'][$user['education-level']];
            }
            if (array_key_exists('work', $user)
            && array_key_exists($user['work'], $question['work']['value'])) {
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
              if (array_key_exists('association', $user['profile-info']) &&
                array_key_exists('value', $question['association']) &&
                array_key_exists($user['profile-info']['association'], $question['association']['value'])) {
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
  public function getAuthorEmail($authorIds, $checkAdmin = FALSE) {
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
}
