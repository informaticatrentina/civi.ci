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

  /**
   * get
   * 
   * This function is used to get all configurations.  
   * @return (array) $contestDetails
   */
  public function get() {
    $connection = Yii::app()->db;
    $sql = "SELECT * FROM configuration ORDER BY display_order ASC";
    $query = $connection->createCommand($sql);
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
    $connection = Yii::app()->db;
    $sql = "UPDATE configuration SET value =:value WHERE name_key = :key ";
    $query = $connection->createCommand($sql);
    $query->bindParam(":value", $this->value);
    $query->bindParam(":key", $this->key);
    $response = $query->execute();
    return $response;
  }

}

?>
