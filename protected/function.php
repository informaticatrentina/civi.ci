<?php

function p($value) {
  print '<pre>';
  print_r($value);
  print '</pre>';
  die;
}

function vd($value) {
  print '<pre>';
  var_dump($value);
  print '</pre>';
  die;
}

function generateRandomString($length) {
  $charset = "abcdefghijklmnopqrstuvwxyz";
  $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $charset .= "0123456789";
  $randomStr = '';
  for ($i = 0; $i < $length; $i++) {
    $randomStr .= $charset[(mt_rand(0, (strlen($charset) - 1)))];
  }
  return $randomStr;
}

/**
 * validateDate
 * 
 * This function is used for validate date
 * @param (date) $date
 * return (boolean) - true on valid date
 */
function validateDate($date) {
  if (empty($date)) {
    return false;
  }
  $dateArr = explode('/', $date);
  if (count($dateArr) != 3) {
    return false;
  }
  return checkdate($dateArr[0], $dateArr[1], $dateArr[2]);
}

/**
 * uploadFile
 * 
 * This function is used for uplaod file (image, text)
 * @param (string) $directory
 * @param (string) $name
 * @return (string) $imageUrl
 */
function uploadFile($directory, $name) {
  $image = CUploadedFile::getInstanceByName($name);
  $imageInfo = pathinfo($image->getName());
  $imageName = $imageInfo['filename'] . generateRandomString(10) . '.' . $imageInfo['extension'];
  $imagePath = $directory . $imageName;
  $imageUrl = BASE_URL . $imagePath;
  $ret = $image->saveAs($imagePath);
  if (!$ret) {
    $imageName = '';
  }
  return $imageName;
}

/**
 * Function to resize image
 */
function resizeImageByPath($imagePath, $width, $height,  $crop = 0, $resizeBy = '') {
    if (empty($imagePath)) {
      return false;
    }
    $resizeWidth = $width;
    $resizeHeight = $height;
    if ($crop == 1) {
      $resizeWidth = $width + 50;
      $resizeHeight = $height + 50;
    }
    $imageInfo = pathinfo($imagePath);
    $resultImage = $imagePath;
    if (!empty($imageInfo)) {
      $resizeDirectoryName = $imageInfo['dirname'] .'/resize';
      if (is_dir($imageInfo['dirname']) && !is_dir($resizeDirectoryName)) {
        mkdir($resizeDirectoryName, 0755, false);
      }
      $resizedImageName = $imageInfo['filename'] .'_r_' .$width .'_'.$height .'.' .$imageInfo['extension'];
      $resizedImageAbPath = dirname(__FILE__) . '/../' . $resizeDirectoryName . '/'. $resizedImageName;
      if (!file_exists($resizedImageAbPath)) {
        $imageResize = Yii::app()->image->load(dirname(__FILE__) . '/../' . $imagePath);
        switch($resizeBy) {
          case 'none':
            $imageResize->resize($resizeWidth, $resizeHeight, Image::NONE);
            break;
          case 'height':
            $imageResize->resize($resizeWidth, $resizeHeight, Image::HEIGHT);
            break;
          case 'width':
            $imageResize->resize($resizeWidth, $resizeHeight, Image::WIDTH);
            break;
          default :
            $imageResize->resize($resizeWidth, $resizeHeight, Image::WIDTH);
            break;
        }
        $imageResize->save($resizedImageAbPath);
      }
      $resultImage = $resizeDirectoryName . '/' . $resizedImageName;
      if ($crop == 1) {
        $imageCrop = Yii::app()->image->load($resultImage);
        $imageCrop->crop($width, $height);
        $imageCrop->save($resizedImageAbPath);
        $resultImage = $resizeDirectoryName . '/' . $resizedImageName;
      }
    return $resultImage;
  }
}

/**
 * Function to check weather current user is logged in or not
 */
function userIsLogged() {
  $flag = false;
  if (isset(Yii::app()->session['user'])) {
    $flag = true;
  }
  return $flag;
}

/**
 * function to get tweets of fondazioneahref
 * 
 * @return tweets
 */
function getTweets($userName) {
  if (!empty($userName)) {
    $tweets = '';
    $tweets_result = file_get_contents("https://api.twitter.com/1/statuses/user_timeline.json?include_entities=true&include_rts=true&screen_name=" . $userName . "&count=2");
    $data = json_decode($tweets_result);
    foreach ($data as $tweet) {
      $time1 = strtotime($tweet->created_at);
      $present = time();
      $diff = $present - $time1;
      $days = floor($diff / 86400);
      $hours = floor(($diff - ($days * 86400)) / 3600);
      $content = preg_replace('/(^|[^a-z0-9_])@([a-z0-9_]+)/i', '$1<a class="footer-link3" href="http://twitter.com/$2">@$2</a>', $tweet->text);
      $tweets.= '<div class="tweetbox"><p class="tweet">' . $content . '</p><span class="tweettime">' . $days . ' giorni,  ' . $hours . ' ore fa</span></div><div style="margin-top:5px;"></div>';
    }
    return $tweets;
  }
}

/**
 * isAdminUser 
 * 
 * This function is used for check whether user is admin or not
 * @return boolean
 */
function isAdminUser() {
  $isAdmin = false;
  $adminUsers = array();
  if (defined('DISCUSSION_ADMIN_USERS')) {
    $adminUsers = json_decode(DISCUSSION_ADMIN_USERS, true);
  }
  if (isset(Yii::app()->session['user'])) {
    if (in_array(Yii::app()->session['user']['email'], $adminUsers)) {
      $_SESSION['user']['admin'] = TRUE;
      $isAdmin = true;
    }
  }
  return $isAdmin;
}

/**
 * getFirstContest
 * 
 * This function is used for return contest  slug
 * @return (string) contest slug
 */
function getFirstContest() {
  $contestSlug = '';
  $contest = new Contest();
  $contestInfo = $contest->getContestDetail();
  if (is_array($contestInfo) && !empty($contestInfo)) {
    $contestInfo = array_shift($contestInfo);
    $contestSlug = $contestInfo['contestSlug'];
  }
  return $contestSlug;
}

/**
 * isUserLogged 
 * 
 * This function is used for check whether user is admin or not
 * @return boolean
 */
function isUserLogged() {
  $isLogged = false;
  if (isset(Yii::app()->session['user'])) {
    $isLogged = true;
  }
  return $isLogged;
}

/**
 * userInputPurifier
 * Purifies the HTML content by removing malicious code.
 * @param mixed $userInput the content to be purified.
 * @return mixed the purified content 
 * @author Pradeep kumar <pradeep@incando.com>
 */
function userInputPurifier($userInput) {
  if (empty($userInput)) {
    return '';
  } 
  if (is_array($userInput)) {
    foreach ($userInput as $key => $input) {
       $data[$key] = userInputPurifier($input);
    }   
    return $data;
  }  
  return htmlspecialchars($userInput);
}

/**
 * removeEmtyArrayValue
 * function is used for remove empty value from an array
 * @param array $array
 * @return array - containing all non empty value
 * @author Pradeep kumar <pradeep@incando.com>
 */
function removeEmptyArrayValue($array) {
  $result = array();
  if (!empty($array)) {
    foreach ($array as $key => $val) {
      if (is_array($val)) {
        $result[$key] = removeEmptyArrayValue($val);
      }
      $val = trim($val);
      if (!empty($val)) {
        $result[$key] = $val;
      }
    }
  }
  return $result;
}

function prepareLogMessage($marker, $actionInfo, $data) {
    $message = $marker . ':' .$actionInfo  . ':' . print_r($data, true);
    return $message;
}

/**
 * checkPermission
 * function is used for check permission
 * @param string $permission - permission to be checked
 * @return boolean true if user have permission 
 */
function checkPermission($permission) {
  $havePermission = false;
  if (empty($permission)) {
    return false;
  }
  if (isset(Yii::app()->session['user'])) {
    if (!empty(Yii::app()->session['user']['admin'])) {
      return true;
    }
    if (array_key_exists('permission', Yii::app()->session['user']) &&
      in_array($permission, Yii::app()->session['user']['permission'])) {
      return true;
    }
    //check if desired permission is a feature or not.
    if (in_array($permission, featurePermission::$features)) {
      if (property_exists('featurePermission', $permission)) {
        $permission = featurePermission::$$permission;
      }
    }
    try {
      if (isModuleExist('rbacconnector') == false) {
        throw new Exception(Yii::t('discussion', 'rbacconnector module is missing'));
      }
      $module = Yii::app()->getModule('rbacconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'rbacconnector module is missing or not defined'));
      }
      $havePermission = User::checkPermission(Yii::app()->session['user']['email'], $permission);
      if ($havePermission) {
        if (array_key_exists('permission', Yii::app()->session['user'])) {
          $perm = Yii::app()->session['user']['permission'];
        }
        $perm[] = $permission;
        $_SESSION['user']['permission'] = $perm;
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in checkPermission');
    }
  }
  return $havePermission;
}

/**
 * adminMenuVisible
 * This function is used to check whether admin Menu should be visible to the user or not.
 * @return type boolean
 */
function adminMenuVisible() {
    $flag = false;
    $flag = checkPermission('role');
    if ($flag == false) {
        $flag = checkPermission('admin');
    }
    return $flag;
}

/**
 * sanitization
 * 
 * This function is used for convert a string in santized string
 * @param $string
 * @return  $sanitizeStr
 */
function sanitization($string){
  $sanitizeStr = '';
  if (!empty($string)) {
    $sanitizeStr = strtolower(preg_replace("/[^a-z0-9]+/i", "_", $string));
  }
  return $sanitizeStr;
}
 /* setFileUploadError
 *
 * set error message for upload file
 * @param numeric $errorCode
 * @return string message
 */
function setFileUploadError($errorCode) {
  $msg = '';
  switch($errorCode) {
    case 1:
      $msg = Yii::t('discussion', 'The uploaded file exceeds the upload file size limit '.ini_get('upload_max_filesize') .  'B');
      break;
    case 3:
      $msg = Yii::t('discussion', 'The uploaded file was only partially uploaded');
      break;
    case 4:
      $msg = Yii::t('discussion', 'file was not uploaded ');
      break;
    case 6:
      $msg = Yii::t('discussion', 'Missing a temporary folder');
      break;
    case 7:
      $msg = Yii::t('discussion', 'Failed to write file to disk');
      break;
    default:
      $msg = Yii::t('discussion', 'Some error occured in file uploading');
      break;
  }
  return $msg;
}

/**
 * isEnableFeature
 * function is used to check whether feature is enable in selected theme or not
 * function get feature list from local config
 * @param string $fetaure  - feature is to be checked
 * @return boolean  true if feature available
 * @author Pradeep<pradeep@incaendo.com>
 */
function isEnableFeature($fetaure) {
  $enableFeature = FALSE;
  if (empty($fetaure)) {
    return $enableFeature;
  }
  if (defined('THEME_FEATURE')) {
    $featureList = json_decode(THEME_FEATURE, true);
    if (array_key_exists($fetaure, $featureList) && $featureList[$fetaure] == true) {
      $enableFeature = TRUE;
    }
  }
  return $enableFeature;
}

/**
 * isModuleExist
 * This function is used to check that given module is exist or not
 * @param string $module module name
 * @author Rahul Tripathi <rahul@incaendo.com>
 * @return boolean $moduleExist
 */
function isModuleExist($module) {
  try {
    $moduleExist = false;
    $basePath = Yii::app()->basePath . '/modules/' . $module;
    if (is_dir($basePath) == true) {
      $moduleExist = true;
    }
  } catch (Exception $e) {
    Yii::log($e->getMessage(), ERROR, 'Error in isModuleExist');
  }
  return $moduleExist;
}

/**
 * encryptDataString
 * function is used for encrypt string using an encryption key
 * @param string $string - string to be encrypted
 * @param string $key - key for encrypt data
 * @return string $encodedData - encoded string
 */
function encryptDataString($string, $key = ENCRYPTION_KEY) {
  $encryptedKey = md5(md5($key));
  $output = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, $encryptedKey);
  $encodedData = base64_encode($output);
  return $encodedData;
}

/**
 * getRegistrationtKey
 * function is used for generating an encrypted string for combination of email and timestamp
 * @param string $email - user email id
 * @param int $timestamp - current timestamp
 * @return string $key   - encrypted key
 */
function getRegistrationtKey($email, $timestamp = NULL) {
  $email = trim($email);
  $key = sha1($email . EM_SALT . $timestamp);
  return $key;
}

/**
 * decryptDataString
 * function is used for decrypt string using an encryption key
 * @param string $string - string to be decrypt
 * @param string $key - key for decrypt data (same key that is used in encrypt data)
 * @return string $decodedData - decoded string
 */
function decryptDataString($string, $key = ENCRYPTION_KEY) {
  $encryptedKey = md5(md5($key));
  $output = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, $encryptedKey);
  $decodedData = rtrim($output, "");
  return $decodedData;
}


/**
 * createXlsFile
 * function is used for create xls file and  provide an option for downloading file
 * @param string $fileName - name of file to be created
 * @param array $headers  - array of column header (array with numeric keys)
 * @param array $rows  - array of rows  (array with numeric keys)
 * @return void
 */
function createXlsFile($fileName, $headers, $rows) {
  try {
    if (empty($headers) || empty($rows)) {
      throw new Exception('No record found to write in xls file');
    }
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);
    //set headers
    $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(15);
    foreach ($headers as $key => $header) {
      $col = $key + 65;
      $cellName = getXLSXCellName($col, 1);
      $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName, $header);
      $objPHPExcel->getActiveSheet()->getColumnDimension(substr($cellName, 0, 1))->setWidth(30);
    }

    //set Rows
    foreach ($rows as $row => $data) {
      foreach ($headers as $key => $header) {
        $col = $key + 65;
        $objPHPExcel->getActiveSheet()->getRowDimension($row + 2)->setRowHeight(15);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue(getXLSXCellName($col, $row + 2), $data[$key]);
      }
    }
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $fileName);
    header('Cache-Control: max-age=0');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
  } catch (Exception $e) {
    Yii::log( $e->getMessage(), ERROR, 'Error in create xls function');
  }
  exit;
}


/**
 * getXLSXCellName
 * It is an helper function of createXlsFile for getting cell name
 * @param $col
 * @param $row
 * @retrun string cellName
 */
function getXLSXCellName($col, $row) {
  $cellName = '';
  if (empty($col) || empty($row)) {
    return $cellName;
  }
  if ($col < 65) {
    return $cellName;
  }
  if ($col < 90) {
    $cellName = chr($col) . $row;
  } else if ($col > 90 && $col < 116) {
    $col = $col - 26;
    $cellName = 'A'. chr($col);
  }
  return $cellName;
}
/**
 * setThemeForUrl
 * function is used for set theme
 *  1. for admin pages, admin theme will be used
 *  2. For rest pages, theme defined in local config will be used
 */
function setThemeForUrl() {
  $adminPagesUrl = array(
     '/admin/'
  );
  $isAdminPage = FALSE;
  foreach ($adminPagesUrl as $url) {
    if (strpos($_SERVER['REQUEST_URI'], $url) !== FALSE) {
      $isAdminPage = TRUE;
      break;
    }
  }
  if ($isAdminPage === TRUE)  {
    define('SITE_THEME', 'admin');
  } else {
    define('SITE_THEME', SITE_THEMES);
  }
}
