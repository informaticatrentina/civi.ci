<?php

/**
 * FeedController
 * 
 * FeedController class inherit controller (base) class .
 * Actions are defined in FeedController.
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra <sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
  <ahref Foundation.
 */
class FeedController extends Controller {

  /**
   * actionProposal
   * 
   * This function is used to generate RSS feed containg all proposals
   */
  public function actionProposal() {
    $discussion = new Discussion();
    $aggregatorManager = new AggregatorManager();
    $proposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . PROPOSAL_TAG_SCEME . '}', '', '', '', '', '', '', '', array(), '', 'tags,status,title,author,id,content,related,creation_date', '', '', '', CIVICO);
    $feed = "<?xml version='1.0' encoding='UTF-8'?> 
          <feed xmlns='http://www.w3.org/2005/Atom'>
            <title>" . SITE_TITLE . "-Proposals</title>";
    foreach ($proposals as $proposal) {
      $discussion->id = $proposal['related']['id'];
      $link = Yii::app()->getBaseUrl(true) . '/discussion/' . $discussion->id . '/' . $proposal['id'];
      $feed.= "<entry> 
            <title>" . $proposal['title'] . "</title>
            <link>" . $link . "</link>  
            <author>
              <name>" . $proposal['author']['name'] . "</name>
            </author>
            <published>" . $proposal['creation_date'] . "</published>
            <id>" . $proposal['id'] . "</id>
            <summary>" . $proposal['content']['description'] . "</summary>";
      foreach ($proposal['tags'] as $tag) {
        $feed.="<category term=" . $tag['name'] . " scheme=" . $tag['scheme'] . "></category>";
      }
      $feed.="</entry>";
    }
    $feed.= "</feed>";
    echo $feed;
  }

  /**
   * actionOpinion
   * 
   * This function is used to generate RSS feed containg all opinion
   */
  public function actionOpinion() {
    $discussion = new Discussion();
    $aggregatorManager = new AggregatorManager();
    $opinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . OPINION_TAG_SCEME . '}', '', '', '', '', '', '', '', array(), '', 'tags,status,title,author,id,content,related,creation_date', '', '', '', CIVICO);
    $feed = "<?xml version='1.0' encoding='UTF-8'?> 
          <feed xmlns='http://www.w3.org/2005/Atom'>
            <title>".SITE_TITLE."-Opinions</title>";
    foreach ($opinions as $opinion) {
      $discussion->id = $opinion['related']['id'];
      $link = Yii::app()->getBaseUrl(true) . '/discussion/' . $discussion->id . '/' . $opinion['id'];
      $feed.= "<entry> 
            <title>" . $opinion['content']['description'] . "</title>
            <link>" . $link . "</link>  
            <author>
              <name>" . $opinion['author']['name'] . "</name>
            </author>
            <published>" . $opinion['creation_date'] . "</published>  
            <id>" . $opinion['id'] . "</id>
            <summary>" . $opinion['content']['description'] . "</summary>";
      foreach ($opinion['tags'] as $tag) {
        $feed.="<category term=" . $tag['name'] . " scheme=" . $tag['scheme'] . "></category>";
      }
      $feed.="</entry>";
    }
    $feed.= "</feed>";
    echo $feed;
  }

  /**
   * actionProposalFeed
   *
   * This function is used to generate RSS feed of proposal
   *   1. if discussion slug pass in url, return proposal feed of that discussion
   *   2. if discussion slug is not passing in url, return all proposal feed
   * @author Pradeep<pradeep@incaendo.com>
   */
  public function actionProposalFeed() { 
    try {
      $discussion = new Discussion();
      $related = '';
      $isinvalidSlug = false;
      $aggregatorManager = new AggregatorManager();
      if (array_key_exists('discussion', $_GET) && !empty($_GET['discussion'])) {
        $discussion->slug = $_GET['discussion'];
        $discussionDetail = $discussion->getDiscussionDetail();
        if (is_array($discussionDetail) && array_key_exists('id', $discussionDetail)) {
          $related = 'discussion,' . $discussionDetail['id'];
        } else {
          $isinvalidSlug = true;
          Yii::log('Invalid discussion slug', 'debug', 'Debug :: actionProposalFeed in FeedController');
        }
      }
      $proposalFeedLimit = PROPOSAL_FEED_LIMIT;
      if (array_key_exists('max-results', $_GET)) {
        $proposalFeedLimit = $_GET['max-results'];
      }
      $proposalTags = 'link{' . PROPOSAL_TAG_SCEME . '}';
      if (array_key_exists('highlighted', $_GET)) {
        $proposalTags = 'link{' . PROPOSAL_TAG_SCEME . '},'. HIGHLIGHT_TAG_NAME .'{' . HIGHLIGHT_PROPOSAL_TAG_SCEME . '}';
      }
      $proposals = array();
      if ($isinvalidSlug == false) {
        $proposals = $aggregatorManager->getEntry($proposalFeedLimit, '', '', 'active', $proposalTags,
          '', '', '', '', '', '', '', array(), '-creation_date', 'tags,status,title,author,id,content,related,creation_date',
          '', '', trim($related), CIVICO);
      }
      $urlString = str_replace('&', '&amp;', $_SERVER['REQUEST_URI']);
      $atomLinkUrl = Yii::app()->getBaseUrl(true) . $urlString;
      $feed = "<?xml version='1.0' encoding='UTF-8'?> 
          <rss version='2.0'  xmlns:atom='http://www.w3.org/2005/Atom'>
          <channel>
            <title>" . SITE_TITLE . "-Proposals</title>
            <atom:link href='"  . $atomLinkUrl . "' rel='self' type='application/rss+xml' />"; 
      $feed .= '<description>'. SITE_TITLE . '</description>';
      $feed .= '<link>'. Yii::app()->getBaseUrl(true) .'</link>';
      $feed .= '<lastBuildDate>'.date('r').'</lastBuildDate>';
      $discussionList = array();
      foreach ($proposals as $proposal) {
        //get discussion slug
        if (array_key_exists($proposal['related']['id'], $discussionList)) {
          $discussionSlug = $discussionList[$proposal['related']['id']]['slug'];
          $discussionTitle = $discussionList[$proposal['related']['id']]['title'];
        } else {
          $discussionApi = new DiscussionAPI();
          $discussionApi->discussionId = $proposal['related']['id'];
          $discussionInfo = $discussionApi->getDiscussionById();
          $discussionSlug = $discussionInfo['slug'];
          $discussionTitle = $discussionInfo['title'];
          $discussionList[$proposal['related']['id']] = array(
            'slug' => $discussionSlug, 'title' => $discussionTitle
          );
        }
        $link = Yii::app()->getBaseUrl(true) . '/discussion/proposals/' . $discussionSlug . '?proposal_id=' . $proposal['id'];
        $discussion->id = $proposal['related']['id'];
        $guid = Yii::app()->getBaseUrl(true) . '/discussion/proposals/' . $discussionSlug . '?proposal_id=' . $proposal['id'];
        $feed.= "<item> 
            <title>" . $proposal['title'] . "</title>
            <link>" . $link . "</link>
            <author>" . FEED_EMAIL . "(" . $proposal['author']['name'] . ')' . "</author>
            <pubDate>" . date('r',strtotime($proposal['creation_date'])) . "</pubDate>
            <guid>" . $guid . "</guid>
            <description><![CDATA[" . $proposal['content']['description'] . "]]></description>
            <category domain='http://ahref.eu/scheme/discussion/title'>" . $discussionTitle . "</category>";
        foreach ($proposal['tags'] as $tag) {
          $feed.="<category domain=" .  '"' . $tag['scheme'] . '"' . ">".$tag['name']."</category>";
        }
        $feed.="</item>";
      }
      $feed.= "</channel></rss>";
      header('Content-type: application/xml');
      echo $feed;
    } catch (Exception $e) {
      Yii::log($e->getMessage(), 'debug', 'Debug :: actionProposalFeed in FeedController');
      echo $e->getMessage();
    }
    exit;
  }

  /**
   * actionOpinionFeed
   *
   * This function is used to generate RSS feed of opinion
   *   1. if opinion id pass in url, return opinion feed of that proposal
   *   2. if  opinion id is not passing in url, return all opinion feed
   * @author Pradeep<pradeep@incaendo.com>
   */
  public function actionOpinionFeed() { 
    $discussion = new Discussion();
    $related = '';
    if (array_key_exists('proposal_id', $_GET) && !empty($_GET['proposal_id'])) {
      $related = 'proposal,' . $_GET['proposal_id'];      
    }
    $opinionFeedLimit = OPINION_FEED_LIMIT;
    if (array_key_exists('max-results', $_GET)) {
      $opinionFeedLimit = $_GET['max-results'];
    }
    $aggregatorManager = new AggregatorManager();
    $opinions = $aggregatorManager->getEntry($opinionFeedLimit, '', '', 'active', 'link{' . OPINION_TAG_SCEME . '}', '', 
      '', '', '', '', '', '', array(), '-creation_date', 'tags,status,title,author,id,content,related,creation_date', '', 
      '', $related, CIVICO);
    if (empty($opinions)) {
      Yii::log('Invalid proposal id', 'debug', 'Debug :: actionOpinionFeed in FeedController');
    }
    $urlString = str_replace('&', '&amp;', $_SERVER['REQUEST_URI']);
    $atomLinkUrl = Yii::app()->getBaseUrl(true) .  $urlString;
    $feed = "<?xml version='1.0' encoding='UTF-8'?> 
      <rss version='2.0'  xmlns:atom='http://www.w3.org/2005/Atom'>
        <channel>
          <title>" . SITE_TITLE . "-Opinions</title>
          <atom:link href='"  . $atomLinkUrl . "' rel='self' type='application/rss+xml' />";  
    $feed .= '<description>'. SITE_TITLE . '</description>';
    $feed .= '<link>'. Yii::app()->getBaseUrl(true) .'</link>';
    $feed .= '<lastBuildDate>'.date('r').'</lastBuildDate>';
    $discussionList = array();
    foreach ($opinions as $opinion) {
      //get discussion slug
      if (array_key_exists($opinion['related']['id'], $discussionList)) {
        $discussionSlug = $discussionList[$opinion['related']['id']]['slug'];
        $discussionTitle = $discussionList[$opinion['related']['id']]['title'];
      } else {
        $proposal = $aggregatorManager->getEntry(ALL_ENTRY, '', $opinion['related']['id'], 'active', '', '', '', '', '',
          '', '', '', array(), '', 'id,related', '', '', '', CIVICO);
        $discussionApi = new DiscussionAPI();
        $discussionApi->discussionId = $proposal[0]['related']['id'];
        $discussionInfo = $discussionApi->getDiscussionById();
        $discussionSlug = $discussionInfo['slug'];
        $discussionTitle = $discussionInfo['title'];
        $discussionList[$opinion['related']['id']] = array(
          'slug' => $discussionSlug, 'title' => $discussionTitle
        );
      }
      $link = Yii::app()->getBaseUrl(true) . '/discussion/proposals/' . $discussionSlug;
      $discussion->id = $opinion['related']['id'];
      $guid = Yii::app()->getBaseUrl(true) . '/discussion/' . $discussion->id . '/' . $opinion['id'];
      $feed.= "<item>
        <link>" . $link . "</link>
        <author> noreply@civiclinks.it (" . $opinion['author']['name'] . ')' . "</author>
        <pubDate>" . date('r',strtotime($opinion['creation_date'])) . "</pubDate>
        <guid>" . $guid . "</guid>
        <description>><![CDATA[" . $opinion['content']['description'] . "]]></description>
        <category domain='http://ahref.eu/scheme/discussion/title'>" . $discussionTitle . "</category>";
      foreach ($opinion['tags'] as $tag) {
        $feed.="<category domain=" .  '"' . $tag['scheme'] . '"' . ">".$tag['name']."</category>";
      }
      $feed.="</item>";
    }
    $feed.= "</channel></rss>";
    header('Content-type:application/xml');
    echo $feed;
    exit;
  }
}