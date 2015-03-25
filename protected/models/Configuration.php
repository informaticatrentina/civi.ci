<?php

/**
 * Configuration
 * 
 * Configuration class is used to perform CRUD operations.
 * 
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra<sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of <ahref Foundation.
 */
class Configuration {

  public $key;
  public $value;
  public $type;

  /**
   * get
   * 
   * This function is used to get all configurations.  
   * @return (array) $contestDetails
   */
  public function get() {
    $connection = Yii::app()->db;
    $sql = "SELECT * FROM configuration WHERE config_key = :config_key ORDER BY 
      display_order ASC";
    $query = $connection->createCommand($sql);
    $query->bindParam(":config_key", $this->type);
    $configurations = $query->queryAll();
    return $configurations;
  }

  /**
   * save
   * 
   * This function is used to get all configurations.  
   * @return (array) $contestDetails
   */
  public function save() {
    try {
      $connection = Yii::app()->db;
      $lastmodified = time();
      $editorEmail = '';
      if (isset(Yii::app()->session['user']['email'])) {
        $editorEmail = Yii::app()->session['user']['email'];
      }
      $sql = "UPDATE configuration SET value =:value, last_modified = :last_modified,
        editor_email = :editor_email WHERE name_key = :key AND config_key = :configKey";
      $query = $connection->createCommand($sql);
      $query->bindParam(':value', $this->value);
      $query->bindParam(":key", $this->key);
      $query->bindParam(":configKey", $this->type);
      $query->bindParam(":last_modified", $lastmodified);
      $query->bindParam(":editor_email", $editorEmail);
      $response = $query->execute();
    } catch (Exception $e) {
      Yii::log('save', ERROR, 'Exception while updating : ' . $e->getMessage());
    }
    return $response;
  }

}

?>
