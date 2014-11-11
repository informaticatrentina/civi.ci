<?php

/**
 * CreateUploadsDirectoryCommand
 * 
 * This class is used to create upload directories and change their owners.
 * Copyright (c) 2014 <ahref Foundation -- All rights reserved.
 * Author: Pradeep Kumar<pradeep@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
   <ahref Foundation.
 */
class CreateUploadsDirectoryCommand extends CConsoleCommand {

  public function run($args) {
    if (0 == posix_getuid()) {
      //get user from command (user for granted permission)
      if (array_key_exists(0, $args) && !empty($args[0])) {
        $user = $args[0];
      } else {
        die( "Please provide a user name for whom permission is to be granted \n");
      }  
      if (posix_getpwnam($user) == FALSE) {
        die( "$user user does not exist in system \n");
      }
      if (!defined('UPLOAD_DIRECTORY')) {
        die('Please define UPLOAD_DIRECTORY constant in local_config.');
      }
      $uploadDirPath = Yii::app()->basePath . '/../' . UPLOAD_DIRECTORY;
      if (!is_dir($uploadDirPath)) {
        mkdir($uploadDirPath);
      }
      $this->recursiveChown($uploadDirPath, $user);
      $resizeDirPath = Yii::app()->basePath . '/../' . UPLOAD_DIRECTORY . '/resize';
      if (!is_dir($resizeDirPath)) {
        mkdir($resizeDirPath);
      }
      $this->recursiveChown($resizeDirPath, $user);
      echo "Script Completed \n";
    } else {
      echo "Invalid access \n";
    }
  }
  
  /**
   * recursiveChown
   * 
   * This function is used to run chown command recursively
   * 
   * @param string $path
   * @param string $owner
   * @return boolean
   */
  public function recursiveChown($path, $owner) {
    if (!file_exists($path)) {
      return(false);
    }
    if (is_file($path)) {
      chown($path, $owner);
      chgrp($path, $owner);
    } elseif (is_dir($path)) {
      $foldersAndFiles = scandir($path);
      $entries = array_slice($foldersAndFiles, 2);
      foreach ($entries as $entry) {
        $this->recursiveChown($path . "/" . $entry, $owner);
      }
      chown($path, $owner);
      chgrp($path, $owner);
    }
    return true;
  }
}

?>
