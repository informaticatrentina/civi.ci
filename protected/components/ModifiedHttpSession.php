<?php

/**
 * ModifiedHttpSession
 * 
 * ModifiedHttpSession class inherit CHttpSession (base) class .
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra <sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
  <ahref Foundation.
 */
class ModifiedHttpSession extends CHttpSession {

  public $lifetime = false;

  public function init() {
    if ($this->lifetime !== false) {
      $cook_p = $this->getCookieParams();
      $cook_p['lifetime'] = $this->lifetime;
      $this->setCookieParams($cook_p);
      $this->setTimeout($this->lifetime);
    }
    parent::init();
  }

}

?>