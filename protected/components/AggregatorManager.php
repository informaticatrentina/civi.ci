<?php

/**
 * AggregatorManager
 * 
 * AggregatorManager class is used for interacting with AggregatorAPI class.
 * AggregatorManager class is used for manipulate(get, save, delete, update) entries. 
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra <sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
 *   <ahref Foundation.
 */
class AggregatorManager {

  public $authorName;
  public $authorSlug;
  public $title;
  public $id;
  public $description;
  public $relationType;
  public $relatedTo;
  public $discussionSlug;
  public $source = CIVICO;
  public $index;
  public $comprehension;
  public $understanding;
  public $summary;
  public $tags = array();
  public $status;
  public $flag = 0;
  public $links;
  public $lang = 'english';
  public $updateProposalDescription = false;
  public $videoUrl;
  public $imagePath;

/**
 * This function is used to import backendconnector modules
 */
  public function __construct() {
    try {
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in Aggregator Manager _constructor');
    }
  }

  /**
   * getEntry$search
   * 
   * This function is used for get entries from aggregator
   * @param (int) $limit (default 1),
   * @param (int) $offset (default 1),
   * @param (string) $id (id of entries) 
   * @param (string) $status 
   * @param $guid
   * @param (string) $tags
   * @param (string) $tagName
   * @param (int) $count (1 for all entry with count and 2 for only count) 
   * @param (date) $dateFrom
   * @param (date) $dateTo
   * @param (int) $enclosures (default 1), 
   * @param (int) $range, 
   * @param $sort (sorting parameter like date_changed, date_published   default ascending order)
   * @param (array) $cordinate 
   * @param (string) $returnField (return these field as output)
   * @param (string) $returnContent 
   * @param (string) $returnTag
   * @return (array) $entry
   */
  public function getEntry($limit = 1, $offset = 0, $id, $status = 'active', $tags = '', $tagsName = '', $guid = '', $count = 1, $dateFrom = '', $dateTo = '', $enclosures = 1, $range = '', $cordinate = array(), $sort, $returnField, $returnContent, $returnTag, $related = '', $source = '', $author = '') {
    $data = array();
    $entry = array();
    $inputData = array();
    $inputParam = '';
    if (!empty($limit) && is_numeric($limit)) {
      $inputData['limit'] = $limit;
    } else {
      $inputData['limit'] = DEFAULT_LIMIT;
    }

    if (!empty($offset) && is_numeric($offset)) {
      $inputData['offset'] = $offset;
    } else {
      $inputData['offset'] = DEFAULT_OFFSET;
    }

    if (!empty($id)) {
      $inputData['id'] = $id;
    }

    if (!empty($status) && is_string($status)) {
      $inputData['status'] = $status;
    }

    if (!empty($guid) && filter_var($guid, FILTER_VALIDATE_URL)) {
      $inputData['guid'] = $guid;
    }

    if (!empty($tags)) {
      $inputData['tags'] = $tags;
    }

    if (!empty($tagsName)) {
      $inputData['tagsname'] = $tagsName;
    }

    if (!empty($count) && ($count == 1 || $count == 2)) {
      $inputData['count'] = $count;
    }

    if (!empty($dateFrom) && !empty($dateTo) && $dateFrom < $dateTo && $dateTo < time()) {
      $inputData['interval'] = $dateFrom . ',' . $dateTo;
    }

    if (isset($enclosures) && is_numeric($enclosures)) {
      $inputData['enclosures'] = $enclosures;
    }

    if (!empty($range) && is_numeric($range)) {
      $inputData['range'] = $range;
    }

    if (!empty($sort)) {
      $inputData['sort'] = $sort;
    }

    if (array_key_exists('NE', $cordinate) && !empty($cordinate['NE']) && (array_key_exists('SW', $cordinate) && !empty($cordinate['SW']))) {
      $inputData['NE'] = $cordinate['NE'];
      $inputData['SW'] = $cordinate['SW'];
    } else if (array_key_exists('radius', $cordinate) && !empty($cordinate['radius']) && (array_key_exists('center', $cordinate) && !empty($cordinate['center']))) {
      $inputData['radius'] = $cordinate['radius'];
      $inputData['center'] = $cordinate['center'];
    }

    if (!empty($returnField)) {
      $inputData['return_fields'] = $returnField;
    }

    if (!empty($returnContent)) {
      $inputData['returnContent'] = $returnContent;
    }

    if (!empty($returnTag)) {
      $inputData['returnTag'] = $returnTag;
    }

    if (!empty($related)) {
      $inputData['related'] = $related;
    }

    if (!empty($source)) {
      $inputData['source'] = $source;
    }

    if (!empty($author)) {
      $inputData['author'] = $author;
    }
    // encode array into a query string
    $inputParam = http_build_query($inputData);
    try {
      if (empty($returnField) && empty($returnContent) && empty($returnTag)) {
        throw new Exception(Yii::t('discussion', 'Return fields should not be empty'));
      }
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI;
      Yii::log('', INFO, Yii::t('discussion', 'Input data in getEntry : ') . $inputParam);
      $data = $aggregatorAPI->curlGet(ENTRY, $inputParam);
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in getEntry method :') . $e->getMessage());
    }
    if (array_key_exists('status', $data) && $data['status'] == 'true') {
      $entry = $data['data'];
    }
    return $entry;
  }

  /**
   * saveEnrty
   * 
   * This function is used for saved entry
   */
  public function saveProposal() {
    $inputParam = array();
    try {
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discsussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI();
      if (empty($this->authorName)) {
        throw new Exception(Yii::t('discussion', 'Please login to submit an entry'));
      }
      if (empty($this->authorSlug)) {
        throw new Exception(Yii::t('discussion', 'Please login to submit an entry'));
      }
      if (empty($this->title) || !is_string($this->title)) {
        throw new Exception(Yii::t('discussion', 'Proposal title should not be empty'));
      }
      if (empty($this->summary)) {
        throw new Exception(Yii::t('discussion', 'Proposal title should not be empty'));
      }

      // prepare data accordind to aggregator API input (array)
      $inputParam = array(
          'content' => array('summary' => $this->summary, 'description' => $this->description),
          'title' => $this->title,
          'status' => 'active',
          'author' => array('name' => $this->authorName, 'slug' => $this->authorSlug),
          'related' => array('type' => $this->relationType, 'id' => $this->relatedTo),
          'tags' => $this->tags,
          'creation_date' => time(),
          'source' => $this->source
      );
      if (!empty($this->videoUrl)) {
        $inputParam['links']['enclosures'][] = array('type' => 'video', 'uri' => $this->videoUrl);
      }
      if (!empty($this->imagePath)) {
        $inputParam['links']['enclosures'][] = array('type' => 'image', 'uri' => $this->imagePath);
      }
      $entryStatus = $aggregatorAPI->curlPOST(ENTRY, $inputParam);
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in saveEntry method :') . $e->getMessage());
      $entryStatus['success'] = false;
      $entryStatus['msg'] = $e->getMessage();
    }
    return $entryStatus;
  }

  /**
   * getEntryForPagination
   * 
   * This function is used for get entry for pagination
   * @return array $entry
   */
  public function getEntryForPagination() {
    $inputParam = '';
    $inputData = array();
    $entry = array();
    try {
      if (empty($this->range)) {
        throw new Exception(Yii::t('discussion', 'Range can not be empty for pagination'));
      }
      if (empty($this->returnField)) {
        throw new Exception(Yii::t('discussion', 'Return fields should not be empty'));
      }

      $inputData['range'] = $this->range;
      $inputData['return_fields'] = $this->returnField;
      $inputData['tags'] = $this->contestSlug . '[contest]';

      // encode array into a query string
      $inputParam = http_build_query($inputData);
      Yii::log('', INFO, Yii::t('discussion', 'Input data in getEntryForPagination : ') . $inputParam);
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discsussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI();
      $data = $aggregatorAPI->curlGet(ENTRY, $inputParam);
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in getEntryForPagination method :') . $e->getMessage());
    }

    if (array_key_exists('status', $data) && $data['status'] == 'true') {
      $entry = $data['data'];
    }
    return $entry;
  }

  public function saveOpinion($savePositionOnly = false) {
    $inputParam = array();
    try {
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discsussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI();
      if (empty($this->authorName)) {
        throw new Exception(Yii::t('discussion', 'Please login to submit an entry'));
      }
      if (empty($this->authorSlug)) {
        throw new Exception(Yii::t('discussion', 'Please login to submit an entry'));
      }
      if (empty($this->summary)) {
        if ($savePositionOnly == false) {
          throw new Exception(Yii::t('discussion', 'Please enter proper opinion text'));
        }
        //set empty string - otherwise it save null in database
        $this->summary = '';
      }
      $creationTime = time();
      // prepare data accordind to aggregator API input (array)
      $inputParam = array(
          'content' => array('description' => $this->summary),
          'status' => 'active',
          'author' => array('name' => $this->authorName, 'slug' => $this->authorSlug),
          'related' => array('type' => $this->relationType, 'id' => $this->relatedTo),
          'creation_date' => $creationTime,
          'source' => $this->source
      );
      if (isset($this->index)) {
        $inputParam['tags'] = array(array('name' => 'triangle', 'slug' => 'triangle', 'scheme' => INDEX_TAG_SCHEME, 'weight' => $this->index),
          array('name' => 'understanding', 'slug' => 'understanding', 'scheme' => UNDERSTANDING_TAG_SCHEME, 'weight' => $this->understanding),
          array('name' => 'comprehension', 'slug' => 'comprehension', 'scheme' => COMPREHENSION_TAG_SCHEME, 'weight' => $this->comprehension),
          array('name' => $this->lang, 'scheme' => LANGUAGE_SCHEME, 'slug' => strtolower($this->lang)));
      }
      $inputParam['tags'][] =  array('name' => 'Link', 'slug' => 'link', 'scheme' => OPINION_TAG_SCEME, 'weight' => 0);
      $entryStatus = $aggregatorAPI->curlPOST(ENTRY, $inputParam);
      $entryStatus['date'] = $creationTime;
      $entryStatus['opinion_text'] = $this->summary;
      $entryStatus['opinion_id'] = $entryStatus['id'];
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in saveEntry method :') . $e->getMessage());
      $entryStatus['success'] = false;
      $entryStatus['msg'] = $e->getMessage();
    }
    return $entryStatus;
  }

  public function updateOpinion($savePositionOnly = false) {
    $inputParam = array();
    try {
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discsussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI();
      if (empty($this->summary)) {
         if ($savePositionOnly == false) {
          throw new Exception(Yii::t('discussion', 'Please enter proper opinion text'));
        }
        //set empty string - otherwise it save null in database
        $this->summary = '';
      }
      // prepare data according to aggregator API input (array)
      $inputParam = array(
        'id' => $this->id,
        'content' => array('description' => $this->summary)
      );
      if (isset($this->index)) {
        $inputParam['tags'] = array(array('name' => 'triangle', 'slug' => 'triangle', 'scheme' => INDEX_TAG_SCHEME, 'weight' => $this->index),
          array('name' => 'understanding', 'slug' => 'understanding', 'scheme' => UNDERSTANDING_TAG_SCHEME, 'weight' => $this->understanding),
          array('name' => 'comprehension', 'slug' => 'comprehension', 'scheme' => COMPREHENSION_TAG_SCHEME, 'weight' => $this->comprehension),
          array('name' => 'Link', 'slug' => 'link', 'scheme' => OPINION_TAG_SCEME, 'weight' => 0),
          array('name' => $this->lang, 'scheme' => LANGUAGE_SCHEME, 'slug' => strtolower($this->lang)));
      }
      $entryStatus = $aggregatorAPI->curlPut(ENTRY, $inputParam);
      $entryStatus['opinion_id'] = $this->id;
      $entryStatus['opinion_text'] = $this->summary;
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in saveEntry method :') . $e->getMessage());
      $entryStatus['success'] = false;
      $entryStatus['msg'] = $e->getMessage();
    }
    return $entryStatus;
  }

  /**
   * updateStatus
   * 
   * This function is used for update status either active or inactive
   * @author  Pradeep Kumar<pradeep@incaendo.com>
   */
  public function updateStatus() {
    $inputParam = array();
    try {
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discsussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI();
      if (empty($this->id)) {
        throw new Exception(Yii::t('discussion', 'Id can not be empty'));
      }
      if (empty($this->status)) {
        throw new Exception(Yii::t('discussion', 'status can not be empty'));
      }
      $inputParam = array(
          'id' => $this->id,
          'status' => $this->status
      );
      $entryStatus = $aggregatorAPI->curlPUT(ENTRY, $inputParam);
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in updateStatus method :') . $e->getMessage());
      $entryStatus['success'] = false;
      $entryStatus['msg'] = $e->getMessage();
    }
    return $entryStatus;
  }

  public function saveLink() {
    $inputParam = array();
    try {
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discsussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI();
      if (empty($this->authorName)) {
        throw new Exception(Yii::t('discussion', 'Please login to submit an entry'));
      }
      if (empty($this->authorSlug)) {
        throw new Exception(Yii::t('discussion', 'Please login to submit an entry'));
      }
      if (empty($this->summary)) {
        throw new Exception(Yii::t('discussion', 'Please provide a link'));
      }
      $creationTime = time();
      // prepare data accordind to aggregator API input (array)
      $inputParam = array(
          'content' => array('description' => $this->description, 'summary' => $this->summary),
          'status' => 'active',
          'author' => array('name' => $this->authorName,
              'slug' => $this->authorSlug),
          'related' => array('type' => $this->relationType, 'id' => $this->relatedTo),
          'tags' => array(array('name' => 'Link', 'slug' => 'link', 'scheme' => LINK_TAG_SCEME)),
          'creation_date' => $creationTime,
          'links' => array('enclosures' => array(array('type' => 'uri', 'uri' => $this->links))),
          'source' => $this->source
      );
      $entryStatus = $aggregatorAPI->curlPOST(ENTRY, $inputParam);
      $entryStatus['date'] = $creationTime;
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in saveEntry method :') . $e->getMessage());
      $entryStatus['success'] = false;
      $entryStatus['msg'] = $e->getMessage();
    }
    return $entryStatus;
  }

  public function updateProposalHeatMap() {
    $inputParam = array();
    try {
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discsussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI();
      if (empty($this->id)) {
        throw new Exception(Yii::t('discussion', 'Id can not be empty'));
      }
      if (empty($this->tags)) {
        throw new Exception(Yii::t('discussion', 'tags can not be empty'));
      }
      $inputParam = array(
          'id' => $this->id,
          'tags' => $this->tags
      );
      $entryStatus = $aggregatorAPI->curlPUT(ENTRY, $inputParam);
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in updateStatus method :') . $e->getMessage());
      $entryStatus['success'] = false;
      $entryStatus['msg'] = $e->getMessage();
    }
    return $entryStatus;
  }

  public function updateProposal() {
    $inputParam = array();
    try {
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discsussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI();
      if (empty($this->id)) {
        throw new Exception(Yii::t('discussion', 'Id can not be empty'));
      }
      $inputParam['id']= $this->id;
      //change logic for update proposal
      if ($this->updateProposalDescription) {
        if (empty($this->title)) {
          throw new Exception(Yii::t('discussion', 'Title can not be empty'));
        }
        $inputParam['title'] = $this->title;
        if (empty($this->summary)) {
          throw new Exception(Yii::t('discussion', 'Summary can not be empty'));
        }
        $inputParam['content']['summary'] = $this->summary;
        if (empty($this->description)) {
          throw new Exception(Yii::t('discussion', 'Description can not be empty'));
        }
        $inputParam['content']['description'] = $this->description;
      } else {
        if (empty($this->tags)) {
          throw new Exception(Yii::t('discussion', 'tags can not be empty'));
        }
        $inputParam['tags']= $this->tags;
      }
      $entryStatus = $aggregatorAPI->curlPUT(ENTRY, $inputParam);
    } catch (Exception $e) {
      Yii::log('', ERROR, Yii::t('discussion', 'Error in updateStatus method :') . $e->getMessage());
      $entryStatus['success'] = false;
      $entryStatus['msg'] = $e->getMessage();
    }
    return $entryStatus;
  }

  /**saveAnswerOnOpinion
   * This method is used to save Answers on opinion.
   * @return array
   * @throws Exception
   */
  public function saveAnswerOnOpinion() {
    $inputParam = array();
    try {
      if (isModuleExist('backendconnector') == false) {
        throw  new Exception(Yii::t('discsussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discsussion', 'backendconnector module is missing or not defined'));
      }
      $aggregatorAPI = new AggregatorAPI();
      if (empty($this->description)) {
        throw new Exception('discussion', 'Description cannot be empty');
      }
      if (empty($this->authorName)) {
        throw new Exception('Author Name cannot be empty');
      }
      if (empty($this->authorSlug)) {
        throw new Exception('Author Slug cannot be empty');
      }
      if (empty($this->relatedTo)) {
        throw new Exception('Related To cannot be empty');
      }
      if (empty($this->relationType)) {
        throw new Exception('Relation Type cannot be empty');
      }
      if (empty($this->source)) {
        throw new Exception('Source cannot be empty');
      }
      $creationTime = time();
      // prepare data according to aggregator API input (array)
      $inputParam = array(
          'content' => array('description' => $this->description),
          'status' => 'active',
          'author' => array('name' => $this->authorName, 'slug' => $this->authorSlug),
          'related' => array('type' => $this->relationType, 'id' => $this->relatedTo),
          'tags' => array(array('name' => 'Answer', 'slug' => 'answer', 'scheme' => ANSWER_TAG_SCHEME)),
          'creation_date' => $creationTime,
          'source' => $this->source
      );
      $entryStatus = $aggregatorAPI->curlPOST(ENTRY, $inputParam);
      $entryStatus['opinion_answer_text'] = $this->description;
    } catch (Exception $e) { 
      Yii::log($e->getMessage(), ERROR, 'Error in saveAnswerOnOpinion');
      $entryStatus['success'] = false;
      $entryStatus['msg'] = $e->getMessage();
    }
    return $entryStatus;
  }  
} 
