<?php
/**
 * JsTrans
 *
 * Use Yii translations in Javascript
 *
 */

/**
 * Publish translations in JSON and append to the page
 *
 * @param mixed $categories the categories that are exported (accepts array and string)
 * @param mixed $languages the languages that are exported (accepts array and string)
 * @param string $defaultLanguage the default language used in translations
 */
class JsTrans
{
    public function __construct($categories, $languages, $defaultLanguage = null)
    {
    // set default language
        if (!$defaultLanguage) $defaultLanguage = Yii::app()->language;

    // create arrays from params
        if (!is_array($categories)) $categories = array($categories);
        if (!is_array($languages)) $languages = array($languages);

    // publish assets folder
    $assets = dirname(__FILE__) . '/assets';
    $baseUrl = Yii::app()->assetManager->publish($assets);

    // create hash
    $hash = substr(md5(implode($categories) . ':' . implode($languages)), 0, 10);

    $dictionaryFile = "dictionary-{$hash}.js";

    // generate dictionary file if not exists or YII DEBUG is set
    if (!file_exists($assets . '/' . $dictionaryFile) || YII_DEBUG) {
      // declare config (passed to JS)
      $config = array('language' => $defaultLanguage);

      // base folder for message translations
      $messagesFolder = rtrim(Yii::app()->messages->basePath, '\/');

      // loop message files and store translations in array
      $dictionary = array();
      foreach ($languages as $lang) {
        if (!isset($dictionary[$lang]))
          $dictionary[$lang] = array();

        foreach ($categories as $cat) {
          $messagefile = $messagesFolder . '/' . $lang . '/' . $cat . '.po';
          if (file_exists($messagefile)) {
            $dictionary[$lang][$cat] = $this->getPoFileMessage($messagefile);
          } else {
            $messagefile = $messagesFolder . '/' . $lang . '/' . $cat . '.php';
            if (file_exists($messagefile)) {
              $dictionary[$lang][$cat] = array_filter(require($messagefile));
            }
          }
        }
      }

      // save config/dictionary
      $data = 'Yii.translate.config=' . CJSON::encode($config) . ';' .
              'Yii.translate.dictionary=' . CJSON::encode($dictionary);


      // save to dictionary file
     if(!file_put_contents($assets . '/' . $dictionaryFile, $data))
        Yii::log('Error: Could not write dictionary file, check file permissions', 'trace', 'jstrans');
    }

    // publish library and dictionary
    if (file_exists($assets . '/' . $dictionaryFile)) {
      Yii::app()->getClientScript()
              ->registerScriptFile($baseUrl . '/JsTrans.min.js', CClientScript::POS_HEAD)
              ->registerScriptFile($baseUrl . '/' . $dictionaryFile, CClientScript::POS_HEAD);
    } else {
      Yii::log('Error: Could not publish dictionary file, check file permissions', 'trace', 'jstrans');
    }
  }
  
 /**
   * getPoFileMessage
   * function is used for fetching message from po file
   * @param string $messagefile (path of po message file)
   * @return array $messages
   * @author Pradeep Kumar <pradeep@incaendo.com>
   */
  public function getPoFileMessage($messagefile) {
    $messages = array();
    $fileContent = strstr(file_get_contents($messagefile, true), 'msgctxt');
    $fileContentArr = explode('msgctxt', $fileContent); 
    $fileContentArr = array_map("trim", $fileContentArr);
    foreach ($fileContentArr as $val) {
      if (empty($val)) {
        continue;
      }
      $msg = explode('"', $val);
      $messages[$msg[3]] = $msg[5];
    }
    return $messages;
  }
}
