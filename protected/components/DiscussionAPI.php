<?php

/**
 *  DiscussionAPI
 * 
 * DiscussionAPI class is used to get, save,update, delete discussion.
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra<sankalpl@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
 *  <ahref Foundation.
 */
class DiscussionAPI {

  public $discussionId;
  public $creationDate;
  public $discussionTitle;
  public $discussionAuthor;
  public $discussionSummary;
  public $discussionSlug;
  public $status = INACTIVE;
  public $proposal_status = CLOSED;
  public $author_id;
  public $discussionTopics;
  public $additionalDescription;
  public $sortingOrder;

  /**
   * save
   * 
   * This function is used for inserting discussion details    
   * @return (int) $response
   */
  public function save() {
    $response = '';
    $connection = Yii::app()->db;
    if (empty($this->creationDate)) {
      throw new Exception(Yii::t('discussion', 'Creation date should not be empty'));
    }
    if (empty($this->discussionTitle)) {
      throw new Exception(Yii::t('discussion', 'Discussion title should not be empty'));
    }
    if (empty($this->discussionSummary)) {
      throw new Exception(Yii::t('discussion', 'Discussion summary should not be empty'));
    }
    $this->discussionAuthor = Yii::app()->session['user']['firstname'] . ' ' . Yii::app()->session['user']['lastname'];
    $this->author_id = Yii::app()->session['user']['id'];
    $sql = "INSERT INTO discussion (creationDate, title, summary, slug, 
      author, status, proposal_status, author_id, topic, additional_description) VALUES( :creationDate, :title, :summary,
      :slug, :author, :status, :proposal_status, :author_id, :topic, :additionalDescription)";
    $query = $connection->createCommand($sql);
    $query->bindParam(":creationDate", $this->creationDate);
    $query->bindParam(":title", $this->discussionTitle);
    $query->bindParam(":summary", $this->discussionSummary);
    $query->bindParam(":slug", $this->discussionSlug);
    $query->bindParam(":author", $this->discussionAuthor);
    $query->bindParam(":status", $this->status);
    $query->bindParam(":proposal_status", $this->proposal_status);
    $query->bindParam(":author_id", $this->author_id);
    $query->bindParam(":topic", $this->discussionTopics);
    $query->bindParam(":additionalDescription", $this->additionalDescription);
    $response = $query->execute();
    return $response;
  }

  /**
   * getContestDetail
   * 
   * This function is used for getting contest details.  
   * @return (array) $contestDetails
   */
  public function getDiscussionDetail() {
    $connection = Yii::app()->db;
    $sql = "SELECT id, creationDate, title, summary, slug, 
      author, author_id, topic, additional_description, sort_id FROM discussion ORDER BY sort_id = 0, sort_id ";
    $query = $connection->createCommand($sql);
    $discussiontDetails = $query->queryAll();
    return $discussiontDetails;
  }

  /**
   * getContestDetailByContestSlug
   * 
   * This function is used for get contest detail on the basis of contest slug
   * @return (array) $contestDetails
   */
  public function getDiscussionDetailBySlug() {
    $discussionDetails = array();
    $connection = Yii::app()->db;
    if (empty($this->discussionSlug)) {
      return array();
    }
    $sql = "SELECT id, creationDate, title, summary, slug, 
      author, status, proposal_status, author_id,topic, additional_description, 
      sort_id FROM discussion where slug = :slug ";
    $query = $connection->createCommand($sql);
    $query->bindParam(":slug", $this->discussionSlug);
    $discussionDetails = $query->queryRow();

    return $discussionDetails;
  }

  /**
   * update
   * 
   * This function is used for update discussion
   * @return (boolean)
   */
  public function update() {
    $connection = Yii::app()->db;
    if (empty($this->discussionSlug)) {
      return false;
    }
    $sql = "UPDATE discussion SET title =:title, summary =:summary, status =:status,
      proposal_status =:proposal_status, topic =:topics, additional_description = :additionalDescription WHERE slug = :slug ";
    $query = $connection->createCommand($sql);
    $query->bindParam(":title", $this->discussionTitle);
    $query->bindParam(":summary", $this->discussionSummary);
    $query->bindParam(":status", $this->status);
    $query->bindParam(":proposal_status", $this->proposal_status);
    $query->bindParam(":slug", $this->discussionSlug);
    $query->bindParam(":topics", $this->discussionTopics);
    $query->bindParam(":additionalDescription", $this->additionalDescription);
    $response = $query->execute();
    return $response;
  }

  /**
   * getContestDetailByContestSlug
   * 
   * This function is used for get contest detail on the basis of contest slug
   * @return (array) $contestDetails
   */
  public function getDiscussionById() {
    $discussionDetails = array();
    $connection = Yii::app()->db;
    if (empty($this->discussionId)) {
      return array();
    }
    $sql = "SELECT id, creationDate, title, summary, slug, 
      author, status, proposal_status, author_id, topic, additional_description, sort_id FROM discussion where id = :id ";
    $query = $connection->createCommand($sql);
    $query->bindParam(":id", $this->discussionId);
    $discussionDetails = $query->queryRow();
    return $discussionDetails;
  }
  
  /**
   * saveDiscussionSortingOrder
   * This function is used for save sorting order
   * @author Kuldeep Singh<kuldeep@incaendo.com>
   * @return (int) $response
   */
  public function saveDiscussionSortingOrder() {
    $connection = Yii::app()->db;
    $sql = "UPDATE discussion SET sort_id =:sortId WHERE slug = :slug ";
    $query = $connection->createCommand($sql);
    $query->bindParam(":sortId", $this->sortingOrder);
    $query->bindParam(":slug", $this->discussionSlug);
    return $query->execute();
  }
}
