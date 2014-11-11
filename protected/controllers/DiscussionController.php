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
  <ahref Foundation.
 */
class DiscussionController  extends PageController {

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
    $discussions = array_chunk($stripedContent, CHUNK_SIZE);
    if (defined('DIV_COLORS')) {
      $color = unserialize(DIV_COLORS);
      $this->render('index', array('color' => $color, 'submission' => Yii::app()->globaldef->params['submission'], 'discussions' => $discussions, 'text' => Yii::app()->globaldef->params['homepage_text']));
    } else {
      $this->render('index', array('submission' => Yii::app()->globaldef->params['submission'], 'discussions' => $discussions, 'text' => Yii::app()->globaldef->params['homepage_text']));
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
    try {
      if (isModuleExist('backendconnector') == false) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
      }
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
        }
      }
    } catch (Exception $e) {
      $response['success'] = false;
      $response['msg'] = $e->getMessage();
      Yii::log('', ERROR, Yii::t('discussion', 'Error in actionRegisterUser method :') . $e->getMessage());
    }
    $this->layout = 'userManager';
    $this->render('login', array('message' => $response, 'back_url' => $backUrl, 'user' => $admin));
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
    $isAdmin = checkPermission('admin');
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
    if ($isHighlighter == false) {
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
        $discussionDetail[$i]['discussionSummary'] = substr($info['summary'], 0, 20);
        $discussionDetail[$i]['discussionSlug'] = $info['slug'];
        $discussionDetail[$i]['discussionAuthor'] = $info['author'];
        $discussionDetail[$i]['discussionOrder'] = $info['sort_id'];
        $discussion->discussionSlug = $info['slug'];
        $discussion->count = 2;
        $i++;
      }
    }
    $this->render('discussionList', array('discussionInfo' => $discussionDetail));
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
        $discussionDetail[$i]['discussionSummary'] = substr($info['summary'], 0, 20);
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
          $_POST['title'] = substr(trim($_POST['title']), 0, intval(Yii::app()->globaldef->params['max_char_title']));
        }
        if (array_key_exists('summary', $postData) && empty($postData['summary'])) {
          throw new Exception(Yii::t('discussion', 'Introduction can not be empty'));
        } else {
          //check for the allowed character limit before purification.
          $_POST['summary'] = nl2br(trim($_POST['summary']));
          $_POST['summary'] = substr($_POST['summary'], 0, intval(Yii::app()->globaldef->params['max_char_intro']));
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
        if (array_key_exists('hasTopics', $postData) && $postData['hasTopics'] == true) {
          if (!array_key_exists('topics', $postData)) {
            throw new Exception(Yii::t('discussion', 'One topic should be selected'));
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
    $this->setHeader('3.0');
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
      if ($discussionInfo['proposal_status'] == OPEN) {
        $proposalSubmissionStatus = $discussionInfo['proposal_status'];
      }
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
          $_POST['opiniontext'] = substr($_POST['opiniontext'], 0, intval(Yii::app()->globaldef->params['max_char_opinion']));
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
    $proposals = $discussion->getOnlyProposals();
    //check whether count is exist in entries array or not 
    if (!empty($proposals)) {
      $return['success'] = true;
      $countFromEntries = end($proposals);
    } else {
      $return['msg'] = Yii::t('discussion', 'There are no proposals');
    }
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
        'link_text' => Yii::app()->globaldef->params['link_text']
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
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    $opinions = array();
    $activeOpinions = array();
    $proposalOpinions = array();
    $inactiveOpinions = array();
    $title = '';
    $aggregatorManager = new AggregatorManager();
    if (array_key_exists('id', $_GET) && !empty($_GET['id'])) {
      $proposalTitle = array();
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
    $discussionSlug = '';
    if (array_key_exists('slug', $_GET) && $_GET['slug'] != '') {
      $discussionSlug = $_GET['slug'];
    }
    $this->render('discussionOpinion', array('opinions' => $proposalOpinions, 'title' => $title, 'slug' => $discussionSlug));
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
    $isAdmin = checkPermission('admin');
    if ($isAdmin ==false) {
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
            $proposl['description'] = substr($proposal['content']['description'], 0, 1000);
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

  public function actionProposalDetails() {
    $discussion = new Discussion;
    $discussion->slug = $_GET['slug'];
    $details = $discussion->getDiscussionDetail();
    $summary = $details['summary'];
    $discussion->getDiscussionDetail();
    $aggregatorManager = new AggregatorManager();
    $proposal = $aggregatorManager->getEntry('', '', $_GET['id'], '', '', '', '', '', '', '', '', '', array(), '', 'title,status,author,id,content,related', '', '', '', '', '');
    $understanding = array();
    $understanding = unserialize(UNDERSTANDING);
    $heatMap = array();
    $heatMap = unserialize(HEATMAP_COLORS);
    $tags = $discussion->getHeatMap($_GET['id']);
    $opinionsAndLinks = $discussion->getOpinionsAndLinks($_GET['id']);
    $this->layout = 'singleProposal';
    $this->render('singleProposal', array('summary' => $summary, 'heatmap' => $heatMap, 'proposal' => $proposal, 'data' => $opinionsAndLinks, 'tags' => $tags, 'understanding' => $understanding));
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
    $configurations = $config->get();
    $stripped = array();

    foreach ($configurations as $configuration) {
      $configuration['value'] = htmlspecialchars_decode($configuration['value']);
      $stripped[] = $configuration;
    }
    if (!empty($_POST)) {
      if ($_POST['key'] == 'moderators_email' && !empty($_POST['value'])) {
        $this->_saveModeratorsEmail($_POST['value']);
      }
      $value = htmlspecialchars($_POST['value']);
      $config->key = $_POST['key'];
      $config->value = $value;
      $config->save();
    }
    $cs = Yii::app()->getClientScript();
    $cs->registerScriptFile(THEME_URL . 'js/configuration.js', CClientScript::POS_END);
    $this->render('configuration', array('configurations' => $stripped));
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
    if ($isHighlighter == false) {
      $this->redirect(BASE_URL);
    }
    $all = array();
    $allProposals = array();
    $discussionTopics = '';
    $discussion = new Discussion();
    $discussion->slug = $_GET['slug'];
    $discussionInfo = $discussion->getDiscussionDetail();
    if (empty($discussionInfo)) {
      $this->redirect(BASE_URL);
    }
    $discussion->id = $discussionInfo['id'];
    $discussionTitle = $discussionInfo['title'];
    $discussionTopics = array_filter(explode(',', $discussionInfo['topic']));
    $understanding = array();
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
      $all[$key] = $understand;
    }
    $heatMap = array();
    $heatMap = unserialize(HEATMAP_COLORS);
    $allProposals = $discussion->getProposalForAdmin(true);
    $this->render('reports', array('allProposals' => $allProposals, 'understanding' => $all, 
        'heatMap' => $heatMap, 'discussionTitle' => $discussionTitle, 'slug' => $_GET['slug'],
        'discussionId' => $discussionInfo['id'], 'topics' => $discussionTopics, 
        'title_char' => intval(Yii::app()->globaldef->params['max_char_title']),
        'intro_char' => intval(Yii::app()->globaldef->params['max_char_intro'])));
  }

  /**
   * actionExport
   * 
   * This function is used to show all proposals
   */
  public function actionExport() {
    $isAdmin = checkPermission('admin');
    if ($isAdmin == false) {
      $this->redirect(BASE_URL);
    }
    $allProposals = array();
    $discussion = new Discussion();
    $discussion->id = $_GET['id'];
    $allProposals = $discussion->getProposalForAdmin(true);    
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator(CIVICO);
    $headings = array('Titolo', 'Descrizione', 'Autore', 'Creation Date', 'Number of Opinions', 'Number of Opinions', 'Stato');
    $rowCnt = count($allProposals) + 1;
    //preapre header
    $rowNumber = 1;
    $col = 'A';
    foreach ($headings as $heading) {
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $heading);
      $col++;
    }
    // Loop through the result set 
    $rowNumber++;
    foreach ($allProposals as $proposal) {
      $col = 'A';
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $proposal['title']);
      $col++;
      $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $proposal['content']['description']);
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
    header('Content-Disposition: attachment;filename="report' . time() . '.xls"');
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
    $this->redirect(BASE_URL . 'discussion/proposal/list/' . $_GET['slug']);
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
              if ($isAdmin == false) {
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
}

?>
