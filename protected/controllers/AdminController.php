<?php


/**
 * AdminController
 * 
 * AdminController class inherit controller (base) class .
 * It is used for all admin panel related activity. 
 * Copyright (c) 2015 <ahref Foundation -- All rights reserved.
 * Author: Pradeep Kumar<pradeep@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
  <ahref Foundation.
 */

class AdminController extends PageController {
  
  /**
   * Function sessionExistence
   * 
   * Function is used for check whether session exists or not.
   * 
   * Function handles only ajax request. 
   */
  public function actionSessionExistence() {
    if (Yii::app()->request->isAjaxRequest) {
      $response = array('session_exist' => FALSE);
      if (isset(Yii::app()->session['user'])) {
        $response = array('session_exist' => TRUE);
      }
      echo CJSON::encode($response);
      Yii::app()->end();
    } else {
      Yii::app()->redirect(BASE_URL);
    }
  }  
}

?> 
