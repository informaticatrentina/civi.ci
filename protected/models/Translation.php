<?php

/**
 * Translation
 * 
 * Translation class is used to do handle all translation actions.
 * 
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra<sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of <ahref Foundation.
 */
class Translation {

  public $message;
  public $targetLang = 'it';

  /**
   * detectAndTranslateLanguage
   * function is used for detaect language and message translatation
   * @param  $message - array of string
   * @param  $targetLang - language for translation
   * @return array $resp - detected source language and translation 
   * @author Sankalp Mishra<sankalp@incaendo.com>
   */
  public function detectAndTranslateLanguage() {
    try {
      $resp = array();
      if (empty($this->message)) {
        return $resp;
      }
      if (is_array($this->message)) {
        $strToBeTranslate = '';
        foreach ($this->message as $msg) {
          $strToBeTranslate .= '&q=' . urlencode($msg);
        }
      } else {
        $strToBeTranslate = '&q=' . urlencode($this->message);;
      }
      $url = 'https://www.googleapis.com/language/translate/v2?key=' . TRANSLATION_API_KEY . '&target=' . $this->targetLang . $strToBeTranslate;
      $defaultParams = array(CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_HEADER => 0,
          CURLOPT_TIMEOUT => CURL_TIMEOUT);
      $curlHandle = curl_init();
      curl_setopt_array($curlHandle, $defaultParams);
      $translateTxt = json_decode(curl_exec($curlHandle));
      $headers = curl_getinfo($curlHandle);
      curl_close($curlHandle);
      if ($headers['http_code'] != '200') {
        throw new Exception(Yii::t('discussion', 'Google api for translation is not respondind'));
      }
      if (!empty($translateTxt->data->translations)) {
        $resp['source_language'] = $translateTxt->data->translations[0]->detectedSourceLanguage;
        foreach ($translateTxt->data->translations as $text) {
          $resp['translated_text'][] = $text;
        }
      }
      return $resp;
    } catch (Exception $e) {
      Yii::log('Error in languageDetectAndTranslator : ', ERROR, $e->getMessage());
    }
  }

  public function languageDetectAndTranslator($message, $targetLang = 'it') {
    $this->message = $message;
    $this->targetLang = $targetLang;
    $resp = $this->detectAndTranslateLanguage();
    return $resp;
  }

  /**
   * saveTranslatedProposal
   * function is used for save proposal after translation
   * @param $id  - proposal id
   * @param $translatedLang  -  langiuage for translation
   * @return $response
   * @author Rahul & Sankalp
   */
  public function saveTranslatedProposal($id, $translatedLang) {
    $resp = array('success' => false, 'msg' => '');
    if (empty($id)) {
      $resp['msg'] = Yii::t('discussion', 'Proposal id is empty');
      return $resp;
    }
    if ($translatedLang == 'sl') {
      $nextTranslation = 'italian';
    } else if ($translatedLang == 'it') {
      $nextTranslation = 'slovenian';
    }
    $resp['msg']['nextTranslation'] = $nextTranslation;
    $aggregatorManager = new AggregatorManager();
    $related = trim('proposal-translated,' . $id);
    $proposal = $aggregatorManager->getEntry('', '', '', 'active', $translatedLang, '', '', 0, '', '', '', '', array(), '', 'status,title,content,author,tags', '', '', $related, '', '');
    if (!empty($proposal)) {
      $resp['msg']['title'] = $proposal[0]['title'];
      $resp['msg']['description'] = preg_replace('#<br\s*/?>#i', "\n", $proposal[0]['content']['description']);
      $resp['msg']['summary'] = preg_replace('#<br\s*/?>#i', "\n", $proposal[0]['content']['summary']);
    } else {
      $proposal = $aggregatorManager->getEntry('', '', $id, 'active', '', '', '', 0, '', '', '', '', array(), '', 'status,title,content,author,tags', '', '', '', '', '');
      $proposal = $proposal[0];
      $str = array();
      if (array_key_exists('title', $proposal) && !empty($proposal['title'])) {
        $str[] = $proposal['title'];
      }
      if (array_key_exists('content', $proposal) && !empty($proposal['content'])) {
        if (array_key_exists('description', $proposal['content']) && !empty($proposal['content']['description'])) {
          $str[] = $proposal['content']['description'];
        }
        if (array_key_exists('summary', $proposal['content']) && !empty($proposal['content']['summary'])) {
          $str[] = $proposal['content']['summary'];
        }
      }
      $translatedStr = $this->languageDetectAndTranslator($str, $translatedLang);
      if (array_key_exists('translated_text', $translatedStr)) {
        if (array_key_exists(0, $translatedStr['translated_text']) && array_key_exists('translatedText', $translatedStr['translated_text'][0])) {
          $aggregatorManager->title = $translatedStr['translated_text'][0]->translatedText;
          $resp['msg']['title'] = $translatedStr['translated_text'][0]->translatedText;
        }
        if (array_key_exists(1, $translatedStr['translated_text']) && array_key_exists('translatedText', $translatedStr['translated_text'][0])) {
          $aggregatorManager->description = $translatedStr['translated_text'][1]->translatedText;
          $resp['msg']['description'] = preg_replace('#<br\s*/?>#i', "\n", $translatedStr['translated_text'][1]->translatedText);
        }
        if (array_key_exists(2, $translatedStr['translated_text']) && array_key_exists('translatedText', $translatedStr['translated_text'][0])) {
          $aggregatorManager->summary = $translatedStr['translated_text'][2]->translatedText;
          $resp['msg']['summary'] = preg_replace('#<br\s*/?>#i', "\n", $translatedStr['translated_text'][2]->translatedText);
        }
      }
      if (array_key_exists('author', $proposal)) {
        $aggregatorManager->authorName = $proposal['author']['name'];
        $aggregatorManager->authorSlug = $proposal['author']['slug'];
      }
      $aggregatorManager->relationType = 'proposal-translated';
      $aggregatorManager->relatedTo = $id;
      foreach ($proposal['tags'] as $tag) {
        if ($tag['scheme'] == LANGUAGE_SCHEME) {
          $tag['name'] = $translatedLang;
          $tag['slug'] = strtolower($translatedLang);
        }
        $tags[] = $tag;
      }
      $aggregatorManager->tags = $tags;
      $aggregatorManager->saveProposal();
    }
    return $resp;
  }

  /**
   * saveTranslatedProposal
   * function is used for save proposal after translation
   * @param $id  - proposal id
   * @param $translatedLang  -  langiuage for translation
   * @return $response
   * @author Rahul & Sankalp
   */
  public function saveTranslatedOpinion($id, $translatedLang) {
    $resp = array('success' => false, 'msg' => '');
    if (empty($id)) {
      $resp['msg'] = Yii::t('discussion', 'Proposal id is empty');
      return $resp;
    }
    if ($translatedLang == 'sl') {
      $nextTranslation = 'italian';
    } else if ($translatedLang == 'it') {
      $nextTranslation = 'slovenian';
    }
    $resp['msg']['nextTranslation'] = $nextTranslation;
    $aggregatorManager = new AggregatorManager();
    $proposal = $aggregatorManager->getEntry('', '', '', 'active', $translatedLang, '', '', 0, '', '', '', '', array(), '', 'status,title,content,author,tags', '', '', trim('opinion-translated,' . $id), '', '');
    if (!empty($proposal)) {
      $resp['msg']['title'] = $proposal[0]['content']['description'];
    } else {
      $proposal = $aggregatorManager->getEntry('', '', $id, 'active', '', '', '', 0, '', '', '', '', array(), '', 'status,title,content,author,tags', '', '', '', '', '');
      $proposal = $proposal[0];
      $str = array();
      if (array_key_exists('title', $proposal) && !empty($proposal['title'])) {
        $str[] = $proposal['title'];
      }
      if (array_key_exists('content', $proposal) && !empty($proposal['content'])) {
        if (array_key_exists('description', $proposal['content']) && !empty($proposal['content']['description'])) {
          $str[] = $proposal['content']['description'];
        }
        if (array_key_exists('summary', $proposal['content']) && !empty($proposal['content']['summary'])) {
          $str[] = $proposal['content']['summary'];
        }
      }
      $translatedStr = $this->languageDetectAndTranslator($str, $translatedLang);
      if (array_key_exists('translated_text', $translatedStr)) {
        if (array_key_exists(0, $translatedStr['translated_text']) && array_key_exists('translatedText', $translatedStr['translated_text'][0])) {
          $aggregatorManager->summary = $translatedStr['translated_text'][0]->translatedText;
          $resp['msg']['title'] = $translatedStr['translated_text'][0]->translatedText;
        }
      }
      if (array_key_exists('author', $proposal)) {
        $aggregatorManager->authorName = $proposal['author']['name'];
        $aggregatorManager->authorSlug = $proposal['author']['slug'];
      }
      $aggregatorManager->relationType = 'opinion-translated';
      $aggregatorManager->relatedTo = $id;
      $aggregatorManager->lang = strtolower($translatedLang);
      $aggregatorManager->saveOpinion();
    }
    return $resp;
  }

}

?>
