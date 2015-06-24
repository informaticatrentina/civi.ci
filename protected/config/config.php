<?php

/**
 * This file is used for define constant and configuration of Aggregator project 
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra <sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
  <ahref Foundation.
 */
/**
 * Including local configuration file.
 */
require_once(dirname(__FILE__) . '/local_config.php');
require_once(dirname(__FILE__).'/featurePermission.php');
require_once(dirname(__FILE__) . '/../function.php');

/**
 * define constant for response format
 */
define('RESPONSE_FORMAT', 'json');

/**
 * define constant for entry
 */
define('ENTRY', 'entry');

setThemeForUrl();
/**
 * define constant for default limit (number of entry to be show)
 */
define('DEFAULT_LIMIT', 1);

/**
 * define constant for default offset 
 */
define('DEFAULT_OFFSET', 0);

/**
 * define constant for curl timeout (execution time) 
 */
define('CURL_TIMEOUT', 60);

/**
 * define log message level
 */
define('INFO', 'info');
define('ERROR', 'error');
define('DEBUG', 'trace');
define('WARNING', 'warning');



/**
 * define constant for minor
 */
define('ADULT', 0);
define('MINOR', 1);

/**
 * define constant for entry limit 
 */
define('ENTRY_LIMIT', 40);

/**
 * Constants for status of discussion
 */
define('ACTIVE', 1);
define('INACTIVE', 0);

/**
 * Constants for status of proposals on discussion
 */
define('OPEN', 1);
define('CLOSED', 0);

/**
 * Constant for proposal understanding and comprehension.
 */
$understanding = array();
$understanding = array(1 => array('a' => 0, 'c' => 0, 'fill' => "#F9F8F9", 'points' => "80.351,140.188 56.09,98.165 104.612,98.165", 'msg' => 'la proposta è incomprensibile '),
    2 => array('a' => 1, 'c' => 1, 'fill' => "#C0E7D2", 'points' => "108.143,92.084 83.882,50.061 132.405,50.061 ", 'msg' => "sono d'accordo ma la proposta non è del tutto comprensibile"),
    4 => array('a' => 1, 'c' => -1, 'fill' => "#FCC3CD", 'points' => "52.752,92.084 28.491,50.061 77.014,50.061 ", 'msg' => "non sono d'accordo ma la proposta non è del tutto comprensibile"),
    3 => array('a' => 1, 'c' => 0, 'fill' => "#F0EDEE", 'points' => "104.65,94.235 56.128,94.235 80.39,52.212 ", 'msg' => 'la proposta è poco comprensibile e non ho una posizione'),
    5 => array('a' => 2, 'c' => 2, 'fill' => "#0E9935", 'points' => "136.041,44.023 111.78,2 160.303,2 ", 'msg' => "sono pienamente d'accordo"),
    7 => array('a' => 2, 'c' => 0, 'fill' => "#E2DEDE", 'points' => "80.651,44.023 56.39,2 104.914,2 ", 'msg' => 'la proposta è comprensibile ma non ho una posizione'),
    6 => array('a' => 2, 'c' => 1, 'fill' => "#A5DDBE", 'points' => "132.549,46.177 84.028,46.177 108.289,4.155", 'msg' => "sono d'accordo con qualche riserva"),
    9 => array('a' => 2, 'c' => -2, 'fill' => "#FE364E", 'points' => "25.262,44.024 1,2.001 49.524,2.001 ", 'msg' => 'sono in completo disaccordo'),
    8 => array('a' => 2, 'c' => -1, 'fill' => "#F9A8B6", 'points' => "77.161,46.177 28.636,46.177 52.898,4.153 ", 'msg' => 'sono in disaccordo su quasi tutto'));
define('UNDERSTANDING', serialize($understanding));

/**
 * constant for source
 */
//define('CIVICO', 'civico');

/*
 * define constant for profile image size
 * Profile image will be square like(350*350) 
 */
define('PROFILE_IMAGE_SIZE', 350);
/**
 * constant for tag scheme of user generated tags for proposal
 */
define('USER_TAGS_SCHEME', 'http://ahref.eu/scheme/proposal/ugt');

/**
 * constant for colors in heat map
 */
$colors = array(0 => '#ffffe2', 1 => '#ffffc6', 2 => '#ffff71', 3 => '#ffff00', 4 => '#ffe200', 5 => '#ffc600', 6 => '#ffaa00',
    7 => '#ff8d00', 8 => '#ff7100', 9 => '#e06300');
define('HEATMAP_COLORS', serialize($colors));

define('TAG_SCHEME', 'http://ahref.eu/scheme/heatmap/index');

define('INDEX_TAG_SCHEME', 'http://ahref.eu/scheme/triangle');
define('UNDERSTANDING_TAG_SCHEME', 'http://ahref.eu/scheme/proposal-understanding');
define('COMPREHENSION_TAG_SCHEME', 'http://ahref.eu/scheme/proposal-comprehension');
define('LINK_TAG_SCEME', 'http://ahref.eu/content/link');
define('OPINION_TAG_SCEME', 'http://ahref.eu/content/opinion');
define('PROPOSAL_TAG_SCEME', 'http://ahref.eu/content/proposal');
define('OPINION_COUNT_TAG_SCEME', 'http://ahref.eu/content/opinioncount');
define('LINK_COUNT_TAG_SCEME', 'http://ahref.eu/content/linkcount');
define('LANGUAGE_SCHEME', 'http://ahref.eu/scheme/language');
define('TOPIC_TAG_SCHEME', 'http://ahref.eu/scheme/');
$url = substr(BASE_URL, 0, -1);
define('THEME_URL', $url . '/themes/' . SITE_THEME . '/');
define('ADMIN_THEME_URL', $url . '/themes/admin/');
define('HIGHLIGHT_PROPOSAL_TAG_SCEME', 'http://ahref.eu/scheme/highlight');
define('HIGHLIGHT_TAG_NAME', 'highlight');
define('ANSWER_TAG_SCHEME', 'http://ahref.eu/scheme/answer');
define('PROPOSAL_SORTING_TAG_SCHEME', 'http://ahref.eu/scheme/proposal/sort_order');
/**
 * Log level checking
 */

$logLevel = 'error';
if (defined('LOG_LEVEL_ACTIVE')) {
    $logLevel = LOG_LEVEL_ACTIVE;
}

//consatant for feeds
define('PROPOSAL_FEED_LIMIT', 20);
define('OPINION_FEED_LIMIT', 20);

/*
 * configuration for interaction of file
 */
return array(
    'defaultController' => 'discussion',
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'runtimePath' => RUNTIME_DIRECTORY,
    'name' => SITE_TITLE,
    'import' => array(
        'application.models.*',
        'application.components.*',
        'application.controllers.*',
        'application.extensions.JsTrans.*',
        'application.extensions.PHPExcel',
        'application.lib.*',
        'application.extensions.tcpdf.tcpdf'
    ),
    'sourceLanguage' => 'en',
    'language' => SITE_LANGUAGE,
    'preload' => array('log'),
    'components' => array(
        array('themeManager' => array('basePath' => dirname(__FILE__) . '../../themes')),
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => $logLevel,
                    'logFile' => APP_LOG_FILE_NAME
                )
            ),
        ),
        'viewRenderer' => array(
            'class' => 'ext.ETwigViewRenderer',
            'fileExtension' => '.html',
            'functions' => array(
                'getTweets' => 'getTweets',
                'getFirstContest' => 'getFirstContest',
                'isAdminUser' => 'isAdminUser',
                't' => 'Yii::t',
                'adminMenuVisible' => 'adminMenuVisible',
                'checkPermission' => 'checkPermission',
            )
        ),
        'db' => array(
            'class' => 'CDbConnection',
            'connectionString' => 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS,
            'emulatePrepare' => true,
        ),
        'image' => array(
            'class' => 'application.extensions.image.CImageComponent',
            'driver' => 'GD',
        ),
        'urlManager' => array(
            'urlFormat' => 'path',
            'showScriptName' => false,
            'caseSensitive' => false,
            'rules' => array(
//afterbaseurl    => 'controller/action'  
                '' => 'discussion/index',
                'discussion/proposal/translate' => 'discussion/saveTranslatedProposal',
                'translate/opinion' => 'discussion/saveTranslatedOpinion',
                'admin/discussion/add' => 'discussion/createDiscussion',
                'admin/discussion/list' => 'discussion/getDiscussion',
                'admin/discussion/delete/<slug:[\w-]+>' => 'discussion/deleteDiscussion',
                'admin/discussion/edit/<slug:[\w-]+>' => 'discussion/updateDiscussion',
                'admin/discussion/entries/<slug:[\w-]+>' => 'discussion/entriesAdminView',
                'discussion/list' => 'discussion/list',
                'discussion/proposal/submit/<slug:[\w-]+>' => 'discussion/submitProposal',
                'discussion/proposal/submit' => 'discussion/submitProposal',
                'discussion/entries/<slug:[\w-]+>' => 'discussion/entries',
                'discussion/entries/<slug:[\w-]+>' => 'discussion/entries',
                'discussion/proposals/<slug:[\w-]+>' => 'discussion/proposals',
                'admin/discussion/proposals/<slug:[\w-]+>/opinion' => 'discussion/opinion',
                'admin/discussion/proposals/<slug:[\w-]+>/opinion/update' => 'discussion/updateOpinion',
                'discussion/brief/<slug:[\w-]+>' => 'discussion/contestBrief',
                'discussion/proposal/view/<slug:[\w-]+>' => 'discussion/getProposal',
                'admin/discussion/proposals/<slug:[\w-]+>/links' => 'discussion/getLinks',
                'admin/discussion/proposals/<slug:[\w-]+>/links/update' => 'discussion/updateLink',
                'admin/discussion/proposal/status' => 'discussion/proposalStatus',
                'discussion/brief/<slug:[\w-]+>' => 'discussion/contestBrief',
                'discussion/submission/<slug:[\w-]+>' => 'discussion/submitEntries',
                'register' => 'user/register',
                'login' => 'discussion/login',
                'logout' => 'discussion/logout',
                'feeds/proposal' => 'feed/proposal',
                'feeds/opinion' => 'feed/opinion',
                'admin/configuration' => 'discussion/configuration',
                'admin/discussion/proposal/list/<slug:[\w-]+>' => 'discussion/reports',
                'admin/export/opinions/<type:[\w-]+>/<id:[\w-]+>/<file:[\w-]+>' => 'admin/exportOpinions',
                'admin/export/<id:[\w-]+>/<type:[\w-]+>' => 'discussion/export',
                'discussion/proposal/edit/<slug:[\w-]+>' => 'discussion/editProposal',
                'admin/discussion/proposal/highlight/<slug:[\w-]+>' => 'discussion/highlightProposal',
                'discussion/proposal/opinion/answer' => 'discussion/submitOpinionAnswer',
                'discussion/proposals/<slug:[\w-]+>/saveposition' => 'discussion/saveTrianglePosition',
                'feed/rss/proposal' => 'feed/proposalFeed',
                'feed/rss/opinion' => 'feed/opinionFeed',
                'documentation' => 'discussion/documentation',
                'admin/discussion/order' => 'discussion/saveDiscussionOrder',
                'user/info' => 'discussion/userDetail',
                'admin/user/export' => 'user/exportUser',
                'user/activate' => 'user/activateUser',
                'user/question' => 'user/saveAdditionalInfo',
                'user/forgot-password' => 'user/forgotPassword',
                'user/change-password' => 'user/changePassword',
                'question/save' => 'user/saveAdditinalInformationQuestion',
                'admin/proposal/order' => 'discussion/saveProposalSortingOrder',
                'admin/config/homepage' => 'discussion/homepageConfig',
                'admin/discussion/statistics/draw' => 'discussion/drawChart',
                'admin/discussion/statistics/<id:[\w-]+>' => 'discussion/statistics',
                'admin/all-discussion' => 'discussion/allDiscussion',
                'admin/discussion/allproposal/<slug:[\w-]+>' => 'discussion/allProposal',
                'admin/discussion/<slug:[\w-]+>/<id:[\w-]+>' => 'discussion/proposalDetails',
                'discussion/proposals/<slug:[\w-]+>/<tag:[\w-]+>' => 'discussion/proposals',
                'admin/statistics' => 'discussion/allStatistics',
                'admin/existuser' => 'admin/SessionExistence'
            ),
        ),
        'session' => array(
            'sessionName' => SITE_SESSION_COOKIE_NAME,
            'class' => 'ModifiedHttpSession',
            'lifetime' => SESSION_TIMEOUT_TIME
        ),
        'errorHandler' => array(
            'errorAction' => 'discussion/error',
        ),
        'messages' => array(
            'class' => 'CGettextMessageSource',
            'useMoFile' => FALSE,
            'catalog' => 'discussion'
        ),
        'globaldef' => array('class' => 'application.components.GlobalDef'),
    ),
    'modules'=> defined('ENABLE_MODULES_LIST') ? json_decode(ENABLE_MODULES_LIST, TRUE) : array()
);


define('FEED_EMAIL', 'noreply@civiclinks.it');