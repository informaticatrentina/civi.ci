<?php

/**
 * Discussion
 * 
 * Discussion class is used  for get contest entry,  create contest.
 * 
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra<sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of <ahref Foundation.
 */
class Discussion {

  public $slug;
  public $id;
  public $tags = array();

  public function __construct() {
    return 'In Discussion class';
  }

  /**
   * createDiscussion
   * 
   * function is used for create discussion
   * @return (array) $response
   */
  public function createDiscussion() {
    $discussionAPI = new DiscussionAPI();
    $response = array();
    $discussionDetails = array();
    $discussionDetails = $_POST;
    try {
      if (!empty($discussionDetails)) {
        if (array_key_exists('discussionTitle', $discussionDetails) && empty($discussionDetails['discussionTitle'])) {
          throw new Exception(Yii::t('discussion', 'Discussion title should not be empty'));
        } else {
          $discussionAPI->discussionTitle = trim($discussionDetails['discussionTitle']);
        }
        if (array_key_exists('discussionSlug', $discussionDetails) && empty($discussionDetails['discussionSlug'])) {
          throw new Exception(Yii::t('discussion', 'Discussion slug should not be empty'));
        } else {
          $discussionAPI->discussionSlug = preg_replace('/\s+/', '', $discussionDetails['discussionSlug']);
        }
        if (array_key_exists('discussiontSummary', $discussionDetails) && empty($discussionDetails['discussiontSummary'])) {
          throw new Exception(Yii::t('discussion', 'Discription Summary should not be empty'));
        } else {
          $discussionAPI->discussionSummary = trim($discussionDetails['discussionSummary']);
        }
        if (array_key_exists('additional_description', $discussionDetails) && !empty($discussionDetails['additional_description'])) {
          $additionalDescription = array('additional_description' => trim($discussionDetails['additional_description']));
          $additionalDescription = str_replace('<ahref', 'foundation_logo_text', $additionalDescription);
          $additionalDescription = array_map('userInputPurifier', $additionalDescription);
          $discussionAPI->additionalDescription = $additionalDescription['additional_description'];
        }
        if (array_key_exists('status', $discussionDetails) && !empty($discussionDetails['status'])) {
          if ($discussionDetails['status'] == 'active') {
            $discussionAPI->status = ACTIVE;
          } else {
            $discussionAPI->status = INACTIVE;
          }
        }
        if (array_key_exists('proposal_status', $discussionDetails) && !empty($discussionDetails['proposal_status'])) {
          if ($discussionDetails['proposal_status'] == 'open') {
            $discussionAPI->proposal_status = OPEN;
          } else {
            $discussionAPI->proposal_status = CLOSED;
          }
        }
        $discussionAPI->discussionTopics  = '';
        if (array_key_exists('discussionTopics', $discussionDetails) && !empty($discussionDetails['discussionTopics'])) {
          $discussionTopics = explode(',', $discussionDetails['discussionTopics']);
          $discussionTopics = removeEmptyArrayValue($discussionTopics);
          $discussionAPI->discussionTopics = implode(',',$discussionTopics);
        }
        //replace by function in function.php
        $discussionAPI->creationDate = date('Y-m-d H:i:s');

        //check if discussion already exists
        $exist = $discussionAPI->getDiscussionDetailBySlug();
        if ($exist) {
          throw new Exception(Yii::t('discussion', 'This discussion title is already exist'));
        }
        $response['success'] = $discussionAPI->save();
        $response['msg'] = Yii::t('discussion', 'You have created a discussion Succesfully');
      }
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in createDiscussion :') . $e->getMessage());
      $response['success'] = '';
      $response['msg'] = $e->getMessage();
    }
    return $response;
  }

  /**
   * getDiacussionDetail
   * 
   * This function is used for get discussion and manipulate it
   */
  public function getDiscussionDetail() {
    $discussionAPI = new DiscussionAPI();
    $discussionDetail = array();

    if (!empty($this->slug)) {
      $discussionAPI->discussionSlug = $this->slug;
      $discussionDetail = $discussionAPI->getDiscussionDetailBySlug();
    } else if (!empty($this->id)) {
      $discussionAPI->discussionId = $this->id;
      $discussionDetail = $discussionAPI->getDiscussionById();
    } else {
      $discussionDetail = $discussionAPI->getDiscussionDetail();
    }
    return $discussionDetail;
  }

  /**
   * updateDiscussion
   * 
   * function is used for update discussion
   * @return (array) $response
   */
  public function updateDiscussion() {
    $discussionAPI = new DiscussionAPI();
    $response = array();
    $discussionDetails = array();
    $discussionDetails = $_POST;

    try {
      if (!empty($discussionDetails)) {
        if (array_key_exists('discussionTitle', $discussionDetails) && empty($discussionDetails['discussionTitle'])) {
          throw new Exception(Yii::t('discussion', 'Discussion title should not be empty'));
        } else {
          $discussionAPI->discussionTitle = trim($discussionDetails['discussionTitle']);
        }
        $discussionAPI->discussionTopics = '';
        if (array_key_exists('discussionTopics', $discussionDetails) && !empty($discussionDetails['discussionTopics'])) {
          $discussionTopics = explode(',', $discussionDetails['discussionTopics']);
          $discussionTopics = removeEmptyArrayValue($discussionTopics);
          $discussionAPI->discussionTopics = implode(',', $discussionTopics);
        }
        if (array_key_exists('discussionSummary', $discussionDetails) && empty($discussionDetails['discussionSummary'])) {
          throw new Exception(Yii::t('discussion', 'Discription Summary should not be empty'));
        } else {
          $discussionAPI->discussionSummary = $discussionDetails['discussionSummary'];
        }
        if (array_key_exists('additional_description', $discussionDetails) && !empty($discussionDetails['additional_description'])) {
          $additionalDescription = array('additional_description' => trim($discussionDetails['additional_description']));
          $additionalDescription = str_replace('<ahref', 'foundation_logo_text', $additionalDescription);
          $additionalDescription = array_map('userInputPurifier', $additionalDescription);
          $discussionAPI->additionalDescription = $additionalDescription['additional_description'];
        }
        if (array_key_exists('status', $discussionDetails) && !empty($discussionDetails['status'])) {
          if ($discussionDetails['status'] == 'active') {
            $discussionAPI->status = ACTIVE;
          } else {
            $discussionAPI->status = INACTIVE;
          }
        }
        if (array_key_exists('proposal_status', $discussionDetails) && !empty($discussionDetails['proposal_status'])) {
          if ($discussionDetails['proposal_status'] == 'open') {
            $discussionAPI->proposal_status = OPEN;
          } else {
            $discussionAPI->proposal_status = CLOSED;
          }
        }
        $discussionAPI->discussionSlug = $discussionDetails['discussionSlug'];
        $discussionAPI->creationDate = date('Y-m-d H:i:s');
        //check if discussion already exists
        $response['success'] = $discussionAPI->update();
        $response['msg'] = Yii::t('discussion', 'You have updated this discussion Succesfully');
      }
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in createDiscussion :') . $e->getMessage());
      $response['success'] = '';
      $response['msg'] = $e->getMessage();
    }
    return $response;
  }

  /**
   * submitProposal
   * 
   * This function is used for submit a proposal
   * @return (array) $response
   */
  public function submitProposal($id, $slug) {
    try {
      if (!empty($_POST)) {
        $aggregatorManager = new AggregatorManager();
        $proposalTitle = '';
        $proposalDescription = '';
        $proposalSummary = '';
        if (!Yii::app()->session['user']['canSubmitProposal']) {
          throw new Exception(Yii::t('discussion', 'You have already submitted allowed number of proposals on this discussion'));
        }
        if (array_key_exists('title', $_POST) && (!empty($_POST['title']))) {
          $aggregatorManager->title = $_POST['title'];
          $proposalTitle = $_POST['title'];
        }
        if (array_key_exists('summary', $_POST) && (!empty($_POST['summary']))) {
          $aggregatorManager->summary = $_POST['summary'];
          $proposalSummary = $_POST['summary'];
        }
        if (array_key_exists('video_url', $_POST)) {
          $_POST['video_url'] = trim($_POST['video_url']);
          if(!empty($_POST['video_url'])) {
            $aggregatorManager->videoUrl = $_POST['video_url'];
          }
        }
        if(array_key_exists('proposal_image', $_FILES)) {
          if($_FILES['proposal_image']['error'] == 0) {
            if (defined('UPLOAD_DIRECTORY') && is_dir(dirname(__FILE__) . '/../../' . UPLOAD_DIRECTORY)) {
              $imagePath = uploadFile(UPLOAD_DIRECTORY, 'proposal_image');
              $aggregatorManager->imagePath = BASE_URL . 'uploads/' . $imagePath;
            } else {
              Yii::log('Upload directory is missing', ERROR, 'Error in submitProposal');
              throw new Exception(Yii::t('discussion', 'Some technical problem occurred, contact administrator'));
            }
          }
        }
        if (array_key_exists('topics', $_POST) && (!empty($_POST['topics']))) {
          foreach ($_POST['topics'] as $topic) {
            $aggregatorManager->tags[] = array('name' => $topic, 'scheme' => TOPIC_TAG_SCHEME . $slug . '/topics', 'slug' => $topic);
          }
        }
        if (array_key_exists('body', $_POST) && (!empty($_POST['body']))) {
          $body = nl2br($_POST['body']);
          $aggregatorManager->description = trim($body);
          $proposalDescription = trim($body);
        }

        $aggregatorManager->authorName = Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'];
        $aggregatorManager->authorSlug = Yii::app()->session['user']['id'];
        $aggregatorManager->contestSlug = $slug;
        $aggregatorManager->relationType = 'discussion';
        $aggregatorManager->relatedTo = $id;
        $aggregatorManager->source = CIVICO;
        $understanding = array();
        $understanding = unserialize(UNDERSTANDING);
        foreach ($understanding as $key => $tag) {
          $aggregatorManager->tags[] = array('name' => (string) $key, 'scheme' => TAG_SCHEME, 'slug' => (string) $key, 'weight' => 0);
        }
        $aggregatorManager->tags[] = array('name' => 'Link', 'scheme' => PROPOSAL_TAG_SCEME, 'slug' => 'link', 'weight' => 0);
        $aggregatorManager->tags[] = array('name' => 'OpinionCount', 'scheme' => OPINION_COUNT_TAG_SCEME, 'slug' => 'OpinionCount', 'weight' => 0);
        $aggregatorManager->tags[] = array('name' => 'LinkCount', 'scheme' => LINK_COUNT_TAG_SCEME, 'slug' => 'LinkCount', 'weight' => 0);
        if (defined('GOOGLE_TRANSLATION_ENABLED') && GOOGLE_TRANSLATION_ENABLED == 1) {
          $translation = new Translation;
          $language = $translation->languageDetectAndTranslator(array($aggregatorManager->description));
          if (array_key_exists('source_language', $language) && !empty($language['source_language'])) {
            switch ($language['source_language']) {
              case 'en':
                $lang = 'English';
                break;
              case 'it':
                $lang = 'Italian';
                break;
              case 'sl':
                $lang = 'Slovenian';
                break;
              default :
                $lang = 'English';
                break;
            }
            $aggregatorManager->tags[] = array('name' => $lang, 'scheme' => LANGUAGE_SCHEME, 'slug' => strtolower($lang));
          }
        }
        $response = $aggregatorManager->saveProposal();
        if ($response['success']) {
          if (isEnableFeature('notification_email')) {
            $subject = Yii::t('discussion', '[{site_name}] {user_name} added a new proposal: Proposal Title [{site_theme}] {title}',
              array(
                '{site_name}' => SITE_NAME,
                '{user_name}' => Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'],
                '{site_theme}' => SITE_THEME,
                '{title}' => $proposalTitle
                ));
            $mailIntro = Yii::t('discussion', 'A new proposal has been added.');
            $body = $this->prepareMailBodyForProposal($mailIntro, $proposalTitle, $proposalSummary, $proposalDescription);
            $this->sendNotificationEMail($subject, $body);
          }
          $response['msg'] = Yii::t('discussion', 'You have succesfully submited a proposal');
        } else {
          $response['msg'] = Yii::t('discussion', 'Some technical problem occurred, contact administrator');
        }
      }
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in submitContestEntry :') . $e->getMessage());
      $response['success'] = false;
      $response['msg'] = $e->getMessage();
    }
    return $response;
  }

  /**
   * getProposals
   * 
   * This function is used to get the proposals of a discussion.
   * @return (array) $response
   */
  public function getProposals() {
    $proposals = array();
    $links = array();
    $preOpionion = array();
    $propose = array();
    $opinion = array();
    $aggregatorManager = new AggregatorManager();
    $proposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', '', '', '', '', '', '', '', '', array(), 'tag:OpinionCount', 'status,title,author,id,content', '', '', trim('discussion,' . $this->id), CIVICO);
    foreach ($proposals as $proposal) {
      if (!array_key_exists('summary', $proposal['content'])) {
        $proposal['content']['summary'] = $proposal['content']['description'];
      }
      $proposal['heatmap'] = array();
      $proposal['opinion'] = array();
      $opinion = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . OPINION_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '', 'status,author,id,content,tags', '', '', trim('proposal,' . $proposal['id']), CIVICO);
      $links = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . LINK_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '-creation_date', 'status,author,id,content', '', '', trim('proposal,' . $proposal['id']), CIVICO);
      $preOpionion = $this->hasUserSubmittedProposal($opinion, Yii::app()->session['user']['id'], $proposal);
      if ($preOpionion) {
        $proposal['submitedopinion'] = $preOpionion;
      } else {
        $proposal['submitedopinion'] = false;
      }
      $proposal['count'] = end($opinion);
      array_pop($opinion);
      $opinion = $this->getClassOfOpinion($opinion);
      if ($proposal['count']['count'] == 0) {
        $proposal['opinion'] = Yii::t('discussion', 'No opinions yet. Be the first to have an opinion');
      } else {
        $proposal['opinion'] = $opinion;
      }
      $proposal['linkcount'] = end($links);
      array_pop($links);
      if ($proposal['linkcount']['count'] == 0) {
        $proposal['links'] = Yii::t('discussion', 'No links yet. Be the first to submit a link.');
      } else {
        $linkInfo = array();
        foreach ($links as $link) {
          $linkDetail = array();
          if (array_key_exists('content', $link) && !empty($link['content'])) {
            $linkDetail['description'] = '';
            if (array_key_exists('description', $link['content']) && !empty($link['content']['description'])) {
              $linkDetail['description'] = $link['content']['description'];
            }
            if (array_key_exists('summary', $link['content']) && !empty($link['content']['summary'])) {
              $linkDetail['link_url'] = $link['content']['summary'];
              $linkDetail['chop_link_url'] = $link['content']['summary'];
              if (strlen($linkDetail['link_url']) > 35) {
                $linkDetail['chop_link_url'] = substr($linkDetail['link_url'], 0, 35) . '...';
              }
            }
          }
          $linkInfo[] = $linkDetail;
        }
        $proposal['links'] = $linkInfo;
      }
      $proposal['heatmap'] = $this->getHeatMap($proposal['id']);
      $proposal['heatMap'] = count($proposal['heatmap']);
      $propose[] = $proposal;
    }
    return $propose;
  }

  /**
   * saveOpinion
   * 
   * This function is used to save an opinion on proposal
   */
  public function saveOpinion($savePositionOnly = false) {
    $response = array('success'=> false, 'msg' => '');
    $heatMap = array();
    try {
      if (!empty($_POST)) {
        $aggregatorManager = new AggregatorManager();
        if (array_key_exists('opiniontext', $_POST) && (!empty($_POST['opiniontext']))) {
          $aggregatorManager->summary = $_POST['opiniontext'];
          $opinionDescription = $_POST['opiniontext'];
        }
        if (array_key_exists('index', $_POST) && (!empty($_POST['index']))) {
          $aggregatorManager->index = $_POST['index'];
        }
        if (array_key_exists('understanding', $_POST) && (!empty($_POST['understanding']))) {
          $aggregatorManager->understanding = $_POST['understanding'];
        }
        if (array_key_exists('comprehension', $_POST) && (!empty($_POST['comprehension']))) {
          $aggregatorManager->comprehension = $_POST['comprehension'];
        }
        if (array_key_exists('opinionid', $_POST) && (!empty($_POST['opinionid']))) {
          $aggregatorManager->id = $_POST['opinionid'];
        }
        if (defined('GOOGLE_TRANSLATION_ENABLED') && GOOGLE_TRANSLATION_ENABLED == 1) {
          $translate = new Translation;
          $language = $translate->languageDetectAndTranslator($aggregatorManager->summary);
          if (array_key_exists('source_language', $language) && !empty($language['source_language'])) {
            switch ($language['source_language']) {
              case 'en':
                $lang = 'English';
                break;
              case 'it':
                $lang = 'Italian';
                break;
              case 'sl':
                $lang = 'Slovenian';
                break;
              default :
                $lang = 'English';
                break;
            }
            $aggregatorManager->lang = $lang;
          }
        }
        $aggregatorManager->authorName = Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'];
        $aggregatorManager->authorSlug = Yii::app()->session['user']['id'];
        $aggregatorManager->relationType = 'proposal';
        $aggregatorManager->relatedTo = $_POST['id'];
        $aggregatorManager->source = CIVICO;
        if (array_key_exists('update', $_POST) && (!empty($_POST['update']))) {
          if (isset($_POST['index']) && isset($_POST['previndex']) && $_POST['index'] !== $_POST['previndex']) {
            $this->updateProposalHeatMapTag($_POST['index'], $_POST['id'], $_POST['previndex'], FALSE);
          }
          $response = $aggregatorManager->updateOpinion($savePositionOnly);
        } else {
          if (isset($_POST['index'])) {
            $this->updateProposalHeatMapTag($_POST['index'], $_POST['id']);
          }
          $opinion = array();
          $opinion = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . OPINION_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '', 'status,author,id,content,tags', '', '', trim('proposal,' . $aggregatorManager->relatedTo), CIVICO, Yii::app()->session['user']['id']);
          if (array_key_exists(1, $opinion)) {
            if (array_key_exists('count', $opinion[1])) {
              if ($opinion[1]['count'] > 0) {
                $response = $aggregatorManager->updateOpinion($savePositionOnly);
              }
            }
          } else {
            $response = $aggregatorManager->saveOpinion($savePositionOnly);
          }
        }
        if ($response['success']) {
          if ($savePositionOnly == false && isEnableFeature('notification_email') === TRUE) {
            $opinionAction = Yii::t('discussion', 'updated');
            if (array_key_exists('id', $response) && !empty($response['id'])) {
              $opinionAction = Yii::t('discussion', 'added');
            }
            $mailIntro = Yii::t('discussion', 'An opinion has been {opinion_action}',
              array(
                '{opinion_action}' =>  $opinionAction
              ));
            $subject = Yii::t('discussion', '[{site_theme}] {user_name} {status} an opinion',
              array(
                '{site_theme}' => SITE_THEME,
                '{user_name}' => Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'],
                '{status}' => $opinionAction
              ));
            $body = $this->prepareMailBodyForOpinion($mailIntro, $opinionDescription, $_POST['id']);
            $this->sendNotificationEMail($subject, $body);
          }
          $response['msg'] = Yii::t('discussion', 'You have succesfully submitted an opinion');
        } else {
          $response['msg'] = Yii::t('discussion', 'Some technical problem occurred, contact administrator');
        }
      }
      $response['heatmap'] = $this->getHeatMap($_POST['id']);
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in submitContestEntry :') . $e->getMessage());
      $response['success'] = false;
      $response['msg'] = $e->getMessage();
    }
    return $response;
  }

  /**
   * hasUserSubmittedProposal
   * 
   * $opinion array 
   * $slug type string
   * @return boolean or Array
   */
  public function hasUserSubmittedProposal($opinion = array(), $slug = '') {
    foreach ($opinion as $opinio) {
      if (array_key_exists('author', $opinio) && $opinio['author']['slug'] == $slug) {
        if (array_key_exists('tags', $opinio) && array_key_exists(2, $opinio['tags'])) {
          $opinio['comprehension'] = $opinio['tags'][2]['weight'];
        }
        if (array_key_exists('tags', $opinio) && array_key_exists(1, $opinio['tags'])) {
          $opinio['understanding'] = $opinio['tags'][1]['weight'];
        }
        if (array_key_exists('tags', $opinio) && array_key_exists(0, $opinio['tags'])) {
          $opinio['index'] = $opinio['tags'][0]['weight'];
        }
        return $opinio;
      }
    }
    return false;
  }

  /**
   * getClassOfOpinion
   */
  public function getClassOfOpinion($opinion = array()) {
    $nextLang = '';
    $list = array();
    $class = array('agree' => array(5, 6, 2),
        'disagree' => array(8, 9, 4),
        'neutral' => array(1, 3, 7));
    foreach ($opinion as $opinio) {
      if (array_key_exists('tags', $opinio) && array_key_exists(0, $opinio['tags'])) {
        $opinio['index'] = $opinio['tags'][0]['weight'];
        if (in_array($opinio['tags'][0]['weight'], $class['agree'])) {
          $opinio['class'] = 'agree';
        } else if (in_array($opinio['tags'][0]['weight'], $class['disagree'])) {
          $opinio['class'] = 'disagree';
        } else {
          $opinio['class'] = 'neutral';
        }
        if (defined('GOOGLE_TRANSLATION_ENABLED') && GOOGLE_TRANSLATION_ENABLED == 1) {
          foreach ($opinio['tags'] as $tag) {
            if ($tag['scheme'] == LANGUAGE_SCHEME) {
              $opinio['source_language'] = $tag['slug'];
              $targetLang = json_decode(TRANSLATE_LANGUAGE);
              if (in_array($opinio['source_language'], $targetLang)) {
                if (($key = array_search($tag['slug'], $targetLang)) !== false) {
                  unset($targetLang[$key]);
                }
                $nextLang = implode(',', $targetLang);
              }
            }
          }
          $opinio['nextLang'] = $nextLang;
        }
      }
      $list[] = $opinio;
    }
    return $list;
  }

  /**
   * saveLink
   * 
   * This function is used to save an link of proposal
   */
  public function saveLink() {
    $response = array();
    $count = 0;
    try {
      if (!empty($_POST)) {
        $aggregatorManager = new AggregatorManager();
        $aggregatorManager->description = '';
        $submittedLink = '';
        $linkDescription = '';
        if (array_key_exists('description', $_POST) && (!empty($_POST['description']))) {
          $aggregatorManager->description = $_POST['description'];
          $linkDescription = $_POST['description'];
        }
        if (array_key_exists('link', $_POST) && (!empty($_POST['link']))) {
          if (!preg_match("/^(https?:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/", $_POST['link'])) {
            throw new Exception(Yii::t('discussion', 'Please enter valid lnk url'));
          }
          $aggregatorManager->summary = $_POST['link'];
          $aggregatorManager->links = $_POST['link'];
          $submittedLink = $_POST['link'];
        }
        $aggregatorManager->authorName = Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'];
        $aggregatorManager->authorSlug = Yii::app()->session['user']['id'];
        $aggregatorManager->relationType = 'proposal';
        $aggregatorManager->relatedTo = $_POST['id'];
        $aggregatorManager->source = CIVICO;
        $proposal = $aggregatorManager->getEntry('', '', $_POST['id'], '', '', '', '', '', '', '', '', '', array(), '', 'tags', '', '', '', '', '');
        if (array_key_exists(0, $proposal)) {
          if (array_key_exists('tags', $proposal[0])) {
            $tags = $proposal[0]['tags'];
            foreach ($tags as $tag) {
              $tagss = array();
              $tagss = $tag;
              if ($tag['scheme'] == LINK_COUNT_TAG_SCEME) {
                $tagss['weight'] = $tag['weight'] + 1;
                $count = $tagss['weight'];
              }
              $newTags[] = $tagss;
            }
          }
          $aggregatorManager->id = $_POST['id'];
          $aggregatorManager->tags = $newTags;
          $aggregatorManager->updateProposal();
        }
        $response = $aggregatorManager->saveLink();
        if ($response['success']) {
          if (isEnableFeature('notification_email') === TRUE) {
            $subject = Yii::t('discussion', '[{site_theme}] {user_name} added a link',
              array(
                '{site_theme}' => SITE_THEME,
                '{user_name}' => Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'],
              ));
            $mailIntro = Yii::t('discussion', 'A new link has been added.');
            $body = $this->prepareMailBodyForLink($mailIntro, $submittedLink, $linkDescription, $_POST['id']);
            $this->sendNotificationEMail($subject, $body);
          }
          $response['msg']['msg'] = Yii::t('discussion', 'You have succesfully submitted a link');
          $response['msg']['count'] = $count;
          $response['data']['link_url'] = $aggregatorManager->links;
          $response['data']['description'] = $aggregatorManager->description;
        } else {
          $response['msg'] = Yii::t('discussion', 'Some technical problem occurred, contact administrator');
        }
      }
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in submitContestEntry :') . $e->getMessage());
      $response['success'] = false;
      $response['msg'] = $e->getMessage();
    }
    return $response;
  }

  public function getProposalForAdmin($all = false) {
    $proposals = array();
    $activeProposals = array();
    $actProposals = array();
    $inactiveProposals = array();
    $inactProposals = array();
    $aggregatorManager = new AggregatorManager();
    if ($all) {
      $actProposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . PROPOSAL_TAG_SCEME . '}', '', '', '', '', '', '', '', array(), '', 'creation_date,status,title,author,id,content,tags', '', '', trim('discussion,' . $this->id), CIVICO);
    } else {
      $actProposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', '', '', '', '', '', '', '', '', array(), '', 'creation_date,status,title,author,id,content,tags', '', '', trim('discussion,' . $this->id), CIVICO);
    }
    foreach ($actProposals as $proposal) {
      if (array_key_exists('content', $proposal) && array_key_exists('summary', $proposal['content'])) {
        $proposal['content']['summary'] = str_replace("&lt;br /&gt;", "<br />", $proposal['content']['summary']);
      }
      if (array_key_exists('content', $proposal) && array_key_exists('description', $proposal['content'])) {
        $proposal['content']['description'] = str_replace("&lt;br /&gt;", "<br />", $proposal['content']['description']);
      }
      if (array_key_exists('tags', $proposal)) {
        foreach ($proposal['tags'] as $tag) {
          if ($tag['scheme'] == TAG_SCHEME) {
            $proposal['weightmap'][$tag['name']] = $tag['weight'];
          }
          if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
            $proposal['totalOpinion'] = $tag['weight'];
          }
          if ($tag['scheme'] == LINK_COUNT_TAG_SCEME) {
            $proposal['totalLinks'] = $tag['weight'];
          }
          if ($tag['scheme'] == HIGHLIGHT_PROPOSAL_TAG_SCEME) {
            $proposal['highlight'] = $tag['name'];
          }
          if ($tag['scheme'] == PROPOSAL_SORTING_TAG_SCHEME) {
            $proposal['sorting_order'] = $tag['weight'];
          }
        }
      }
      $activeProposals[] = $proposal;
    }
    if ($all) {
      $inactProposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'inactive', 'link{' . PROPOSAL_TAG_SCEME . '}', '', '', '', '', '', '', '', array(), '', 'creation_date,status,title,author,id,content,tags', '', '', trim('discussion,' . $this->id), CIVICO);
    } else {
      $inactProposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'inactive', '', '', '', '', '', '', '', '', array(), '', 'creation_date,status,title,author,id,content,tags', '', '', trim('discussion,' . $this->id), CIVICO);
    }
    foreach ($inactProposals as $proposal) {
      if (array_key_exists('content', $proposal) && array_key_exists('summary', $proposal['content'])) {
        $proposal['content']['summary'] = str_replace("&lt;br /&gt;", "<br />", $proposal['content']['summary']);
      }
      if (array_key_exists('content', $proposal) && array_key_exists('description', $proposal['content'])) {
        $proposal['content']['description'] = str_replace("&lt;br /&gt;", "<br />", $proposal['content']['description']);
      }
      if (array_key_exists('tags', $proposal)) {
        foreach ($proposal['tags'] as $tag) {
          if ($tag['scheme'] == TAG_SCHEME) {
            $proposal['weightmap'][$tag['name']] = $tag['weight'];
          }
          if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
            $proposal['totalOpinion'] = $tag['weight'];
          }
          if ($tag['scheme'] == LINK_COUNT_TAG_SCEME) {
            $proposal['totalLinks'] = $tag['weight'];
          }
          if ($tag['scheme'] == HIGHLIGHT_PROPOSAL_TAG_SCEME) {
            $proposal['highlight'] = $tag['name'];
          }
        }
      }
      $inactiveProposals[] = $proposal;
    }
    $proposals = array_merge($activeProposals, $inactiveProposals);
    return $proposals;
  }

  /**
   * updateProposalHeatMapTag
   * 
   * This function updates the weight of heat map tag.
   * In case opinion update, opinion count will not be increase on proposal.
   * @param int $index - triangle position index
   * @param string $id - id of proposal
   * @param int $prev - index of previous selected triangle position
   * @param boolean $updateOpinion - TRUE if opinion will be updated
   * @return  void
   */
  public function updateProposalHeatMapTag($index, $id, $prev = '', $updateOpinion = TRUE) {
    $newTags = array();
    $aggregatorManager = new AggregatorManager();
    $proposal = $aggregatorManager->getEntry('', '', $id, '', '', '', '', '', '', '', '', '', array(), '', 'tags', '', '', '', '', '');
    if (array_key_exists(0, $proposal)) {
      if (array_key_exists('tags', $proposal[0])) {
        $tags = $proposal[0]['tags'];
        foreach ($tags as $tag) {
          $tagss = array();
          $tagss = $tag;
          if (!empty($prev)) {
            if ($tag['scheme'] == TAG_SCHEME && $tag['name'] == $prev) {
              if ($tag['weight'] != 0) {
                $tagss['weight'] = $tag['weight'] - 1;
              }
            }
          } else {
            if ($updateOpinion == TRUE && $tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
              $tagss['weight'] = $tag['weight'] + 1;
            }
          }
          if ($tag['scheme'] == TAG_SCHEME && $tag['name'] == $index) {
            $tagss['weight'] = $tag['weight'] + 1;
          }
          $newTags[] = $tagss;
        }
      }
      $aggregatorManager->id = $id;
      $aggregatorManager->tags = $newTags;
      $aggregatorManager->updateProposalHeatMap();
    }
  }

  /**
   * This function returns an array for ploting Heat Map 
   */
  public function getHeatMap($id) {
    $maxWeight = 1;
    $fraction = 0;
    $weights = array();
    $aggregatorManager = new AggregatorManager();
    $tags = $aggregatorManager->getEntry('', '', $id, '', '', '', '', '', '', '', '', '', array(), '', 'tags', '', '', '', '', '');
    if (array_key_exists(0, $tags) && !empty($tags[0])) {
      foreach ($tags[0]['tags'] as $tag) {
        if (array_key_exists('name', $tag) && array_key_exists('weight', $tag)) {
          $weights[$tag['name']] = $tag['weight'];
        }
      }
    } else {
      return array();
    }
    $maxWeight = max($weights);
    if ($maxWeight == 0) {
      $maxWeight = 1;
    }
    if ($maxWeight <= 9) {
      $scaledWeights = array();
      foreach ($weights as $key => $weight) {
        if ($weight < 0) {
          $weight = 0;
        }
        $scaledWeights[$key] = $weight;
      }
    } else {
      $fraction = 9 / $maxWeight;
      $scaledWeights = array();
      foreach ($weights as $key => $weight) {
        if ($weight < 0) {
          $weight = 0;
        }
        if ($weight == $maxWeight) {
          $scaledWeights[$key] = 9;
        } else {
          $scaledWeights[$key] = ceil($weight * $fraction);
        }
      }
    }

    return $scaledWeights;
  }

  /**
   * userSubmittedProposal
   * 
   * get no of proposal submitted by a user
   * @param $id  - discussion id
   * @return $count  -no of proposal submitted
   */
  public function userSubmittedProposal($id, $data = false) {
    $count = 0;
    $aggregatorManager = new AggregatorManager();
    if ($data == false) {
      $entry = $aggregatorManager->getEntry('', '', '', 'active', '', '', '', 2, '', '', '', '', array(), '', 'title', '', '', trim('discussion,' . $id), CIVICO, Yii::app()->session['user']['id']);
      if (!empty($entry)) {
        $count = $entry[0]['count'];
      }
      return $count;
    } else {
      $entry = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . PROPOSAL_TAG_SCEME . '}', '', '', '', '', '', '', '', array(), '', 'tags', '', '', '', CIVICO, Yii::app()->session['user']['id']);
      return $entry;
    }
  }

  /**
   * getOpinionsAndLinks
   * 
   * This function returns all the opinions an links of a proposal
   * @param $id - proposal id
   * @return array Collection of links and opinions
   */
  public function getOpinionsAndLinks($id) {
    $returnData = array();
    $aggregatorManager = new AggregatorManager();
    $opinions = array();
    $links = array();
    $hasUserSubmitted = array();
    $opinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . OPINION_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '', 'status,author,id,content,tags,creation_date', '', '', trim('proposal,' . $id), CIVICO);
    $hasUserSubmitted = $this->hasUserSubmittedProposal($opinions, Yii::app()->session['user']['id']);
    if (is_array($hasUserSubmitted)) {
      $returnData['hasUserSubmitted'] = $hasUserSubmitted;
    }
    $links = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . LINK_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '-creation_date', 'status,author,id,content', '', '', trim('proposal,' . $id), CIVICO);
    if (array_key_exists(0, $opinions) && array_key_exists('count', $opinions[0])) {
      if ($opinions[0]['count'] == 0) {
        $returnData['opinion'] = 0;
      }
    } else {
      array_pop($opinions);
      $opinions = $this->getClassOfOpinion($opinions);
      $returnData['opinion'] = $opinions;
    }
    if (array_key_exists(0, $links) && array_key_exists('count', $links[0])) {
      if ($links[0]['count'] == 0) {
        $returnData['link'] = 0;
      }
    } else {
      array_pop($links);
      $returnData['link'] = $links;
    }
    $answersOnOpinionArray = array();
    if (array_key_exists('opinion', $returnData) && !empty($returnData['opinion']) && $returnData['opinion'] != 0) {
      foreach($returnData['opinion'] as $opinion) {        
        $answersOnOpinion = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'answer{' . ANSWER_TAG_SCHEME . '}', '', '', 1, '', '', '', '', array(), '', 'status,author,id,content,tags', '', '', trim('opinion,' . $opinion['id']), CIVICO);
        if(array_key_exists(0, $answersOnOpinion) && array_key_exists('count', $answersOnOpinion[0])) {
          if ($answersOnOpinion[0]['count'] == 0) {
            $answersOnOpinion['answer'] = 0;
          }
        } else {
          array_pop($answersOnOpinion);
          $answersOnOpinionArray[$opinion['id']] = $answersOnOpinion;
        }
      }
      $returnData['answer_on_opinion'] = $answersOnOpinionArray;
    }
    return $returnData;
  }

  /**
   * getOnlyProposals
   * 
   * This function returns all the  proposal
   * @return array Collection of proposals
   */
  public function getOnlyProposals() {
    $proposals = array();
    $links = array();
    $preOpionion = array();
    $unhighlightedProposal = array();
    $opinion = array();
    $highlightProposal = array();
    $aggregatorManager = new AggregatorManager();
    $tag = '';
    if(!empty($this->tags)) {
      $tag = $this->tags;
    }
    $proposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', $tag, '', '', '', '', '', '', '', array(),
      'tag:OpinionCount', 'status,title,author,id,content,tags,links', '', '', trim('discussion,' . $this->id), CIVICO);
    foreach ($proposals as $proposal) {
      $proposal['count'] = 0;
      $proposal['highlighted'] = false;
      $proposal['image'] = '';
      $proposal['video'] = '';
      if (array_key_exists('content', $proposal) && array_key_exists('summary', $proposal['content'])) {
        $proposal['content']['summary'] = str_replace("&lt;br /&gt;", "<br />", $proposal['content']['summary']);
      }
      if (array_key_exists('content', $proposal) && array_key_exists('description', $proposal['content'])) {
        $proposal['content']['description'] = str_replace("&lt;br /&gt;", "<br />", $proposal['content']['description']);
      }
      if (array_key_exists('links', $proposal) && array_key_exists('enclosures', $proposal['links'])) {
        foreach ($proposal['links']['enclosures'] as $enclosures) {
          if ($enclosures['type'] == 'image') {
            try {
              $proposalImagePath = parse_url($enclosures['uri']);
              if (array_key_exists('path', $proposalImagePath) &&
                      file_exists(dirname(__DIR__) . '/../' . $proposalImagePath['path'])) {
                $size = getimagesize($enclosures['uri']);
                if (isset($size[0]) && $size[0] <= 590) {
                  $proposal['image'] = $enclosures['uri'];
                } else {
                  $proposal['image'] = BASE_URL . resizeImageByPath($proposalImagePath['path'], 590, 250);
                }
              } else {
                Yii::log($enclosures['uri'] . ' File does not exist', DEBUG, 'Debug in getOnlyProposals');
              }
            } catch (Exception $e) {
              Yii::log($e->getMessage(), ERROR, 'Unexpected error in getOnlyProposals');
            }
          }
          if ($enclosures['type'] == 'video') {
            preg_match('/^.*(player.|www.)?(vimeo\.com|youtu(be\.com|\.be))\/(video\/|embed\/|watch\?v=)?([A-Za-z0-9._%-]*)(\&\S+)?/', $enclosures['uri'], $pattern);
            if (isset($pattern[2]) && ($pattern[2] == 'youtube.com' || $pattern[2] == 'youtu.be')) {
              $proposal['videoType'] = $pattern[2];
              if (isset($pattern[5])) {
                $proposal['video'] = $pattern[5];
              }
            } else if (isset($pattern[2]) && $pattern[2] == 'vimeo.com') {
              $proposal['video'] = $pattern[5];
              $proposal['videoType'] = $pattern[2];
            }
          }
        }
      }
      if (array_key_exists('tags', $proposal)) {
        foreach ($proposal['tags'] as $tag) {
          if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
            $proposal['count'] = $tag['weight'];
          }
          if ($tag['scheme'] == LINK_COUNT_TAG_SCEME) {
            $proposal['linkcount'] = $tag['weight'];
          }
          if ($tag['scheme'] == HIGHLIGHT_PROPOSAL_TAG_SCEME) {
            $proposal['highlighted'] = true;
          }
          if (defined('GOOGLE_TRANSLATION_ENABLED') && GOOGLE_TRANSLATION_ENABLED == 1 &&
                  $tag['scheme'] == LANGUAGE_SCHEME) {
            $proposal['source_language'] = $tag['slug'];
            $targetLang = json_decode(TRANSLATE_LANGUAGE);
            if (in_array($proposal['source_language'], $targetLang)) {
              if (($key = array_search($tag['slug'], $targetLang)) !== false) {
                unset($targetLang[$key]);
              }
              $proposal['target_language'] = implode(',', $targetLang);
            }
          }
        }
      }
      $proposal['heatmap'] = $this->getHeatMap($proposal['id']);
      $proposal['heatMap'] = count($proposal['heatmap']);
      if ($proposal['highlighted'] === true) {
        $highlightProposal[] = $proposal;
      } else {
        $unhighlightedProposal[] = $proposal;
      }
    }
    return array_merge($highlightProposal, $unhighlightedProposal);
  }
  
  /**
   * getProposalTags
   * This function is used to get all tags for a proposal.
   * Required Id of a proposal.
   * @return type array
   */
  public function getProposalTags() {
    $proposalTags = array();
    $aggregatorManager = new AggregatorManager();
    if (isset($this->id)) {
      $proposalTags = $aggregatorManager->getEntry(ALL_ENTRY, '', $this->id, 'active', '', '', '', '', '', '', '', '', array(), '', 'tags', '', '', '', CIVICO);        
    }   
    return $proposalTags;
  }
  
  /**
   * prepareMailBodyForProposal
   * This funtion is used to create email body for proposal
   * @param string $mailIntro - Introductory line in email
   * @param string $title - proposal Title
   * @param string $summary - proposal $summary
   * @param string $description - proposal description
   * @return string $html
   */
  public function prepareMailBodyForProposal($mailIntro, $title, $summary, $description) {
    $html = '';
    $html = file_get_contents(Yii::app()->theme->basePath . '/views/discussion/proposalEmail.html');
    $html = str_replace("{{mail_intro}}", $mailIntro, $html);
    $html = str_replace("{{title_text}}", Yii::t('discussion', 'Title'), $html);
    $html = str_replace("{{proposal_title}}", $title, $html);
    $html = str_replace("{{summary_text}}", Yii::t('discussion', 'Summary'), $html);
    $html = str_replace("{{proposal_summary}}", $summary, $html);
    $html = str_replace("{{description_text}}", Yii::t('discussion', 'Description'), $html);
    $html = str_replace("{{proposal_description}}", $description, $html);
    $html = str_replace("{{admin_link_text}}", Yii::t('discussion', 'Click here to access Admin Page'), $html);
    $adminPageUrl = '';
    if (array_key_exists('slug', $_GET) && !empty($_GET['slug'])) {
      $adminPageUrl = BASE_URL . 'discussion/proposal/list/' . $_GET['slug'];
    }
    $html = str_replace("{{admin_page_url}}", $adminPageUrl, $html);
    return $html;
  }

  /**
   * prepareMailBodyForOpinion
   * This funtion is used to create email body for opinion
   * @param  string $description - opinion description
   * @param string $mailIntro - Introductory line in mail
   * @param string $proposalId - id of proposal
   * @return string $html
   */
  public function prepareMailBodyForOpinion($mailIntro, $description, $proposalId) {
    $html = '';
    $html = file_get_contents(Yii::app()->theme->basePath . '/views/discussion/opinionEmail.html');
    $html = str_replace("{{mail_intro}}", $mailIntro, $html);
    $html = str_replace("{{description_text}}", Yii::t('discussion', 'Description'), $html);
    $html = str_replace("{{opinion_description}}", $description, $html);
    $html = str_replace("{{admin_link_text}}",  Yii::t('discussion', 'Click here to access Admin Page'), $html);
    $adminPageUrl = '';
    if (array_key_exists('slug', $_GET) && trim($_GET['slug']) != '') {
      $adminPageUrl = BASE_URL . 'admin/discussion/proposals/' . $_GET['slug'] . '/opinion?id=' .$proposalId;
    }
    $html = str_replace("{{admin_page_url}}", $adminPageUrl, $html);
    return $html;
  }

  /**
   * prepareMailBodyForProposalLink for proposal links
   * This funtion is used to create email body for link
   * @param  string $link link
   * @param string $link link description
   * @param string $mailIntro - Introductory line in mail
   * @param string $proposalId - id of proposal
   * @return string $html
   */
  public function prepareMailBodyForLink($mailIntro, $link, $description, $proposalId) {
    $html = '';
    $html = file_get_contents(Yii::app()->theme->basePath . '/views/discussion/linkEmail.html');
    $html = str_replace("{{mail_intro}}", $mailIntro, $html);
    $html = str_replace("{{link_text}}", Yii::t('discussion', 'Link'), $html);
    $html = str_replace("{{proposal_link}}", $link, $html);
    $html = str_replace("{{description_text}}", Yii::t('discussion', 'Description'), $html);
    $html = str_replace("{{proposal_link_description}}", $description, $html);
    $html = str_replace("{{admin_link_text}}", Yii::t('discussion', 'Click here to access Admin Page'), $html);
    $adminPageUrl = '';
    if (array_key_exists('slug', $_GET) && trim($_GET['slug']) != '') {
      $adminPageUrl = BASE_URL . 'admin/discussion/proposals/' . $_GET['slug'] . '/links?id=' . $proposalId;
    }
    $html = str_replace("{{admin_page_url}}", $adminPageUrl, $html);
    return $html;
  }

  /**
   * sendNotificationEMail
   * This funtion is used to send mail
   * @param string $subject subject of a mail
   * @param string $body body of a mail
   */
  public function sendNotificationEMail($subject, $body) {
    try {
      $console = new BackgroundConsoleRunner('index-cli.php');
      $subject = str_replace("'", "$3#$", $subject);
      $body = str_replace("'", "$3#$", $body);
      $args = "sendmail '$subject'  '$body'";
      $console->run($args);
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in sendNotificationEMail');
    }
  }
}
