<?php

/**
 * BackgroundConsoleRunner
 * 
 * BackgroundConsoleRunner class is used for running console command in background.
 * Copyright (c) 2014 <ahref Foundati on -- All rights reserved.
 * Author: Pradeep Kumar <pradeep@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission 
 * of <ahref Foundation.
 */

class BackgroundConsoleRunner extends CComponent {

  private $_consoleFile;

  /**
   * __construct
   * function is used for assign console application file path that will executed
   * @param string $consoleFile filename for console application in root directory
   */
  public function __construct($consoleFile) {
    $this->_consoleFile = $consoleFile;
  }

  /**
   * run
   * Running console command on background
   * @param string $command argument that passed to console application
   * @param $priority integer  - it is optional 
   * @return boolean
   */
  public function run($command, $priority = 0) {
    $command = PHP_BINDIR . "/php " . Yii::app()->basePath . '/../' . 
               $this->_consoleFile . ' ' . $command;
    $processId = '';
    if ($priority) {
      $processId = shell_exec("nohup nice -n $priority $command > /dev/null & echo $!");
    } else {
      $processId = shell_exec("nohup $command > /dev/null & echo $!");
    }
    return $processId;
  }

  /**
   * isProcessRunning
   * Check if the process running
   * @param $processId  - id of process
   * @return boolean
   */
  public function isProcessRunning($processId) {
    return exec::is_running($processId);
  }

  /**
   * killProcess
   * function is used for kill a process
   * @param $processId  - id of process
   * @return boolean
   */
   public function killProcess($processId) {
    if (exec::is_running($processId)) {
      exec("kill -KILL $processId");
      return true;
    } else {
      return false;
    }
  }
}
