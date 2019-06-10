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
 * <ahref Foundation.
 */
ob_start();
class AdminController extends PageController {

  /**
   * beforeAction
   * @param $action
   * @return boolean
   */
  public function beforeAction($action) {
    new JsTrans('js', SITE_LANGUAGE);
    checkAdditionalFormFilled();
    return true;
  }

  /**
   * Function sessionExistence
   * Function is used for check whether session exists or not.
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

  /**
   * actionPdfGenerate
   * This function is used to generate PDF for exporting all proposals inside a
   * discussion along with its opinions and links count.
   * PDF is currently in download form.
   *
   * @param array $allProposals
   * @param array $discussionDetail
   * @param array $headings
   */
  public function actionPdfGenerate($allProposals, $discussionDetail) {
    try {
      $pdf = Yii::createComponent('application.extensions.tcpdf.tcpdf',
        'P', 'mm', 'A4', true, 'UTF-8', false);
      $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
      $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
      $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
      $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
      $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
      $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
      $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
      $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
      $pdf->SetPrintHeader(false);
      $pdf->SetPrintFooter(false);
      if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
        require_once(dirname(__FILE__) . '/lang/eng.php');
        $pdf->setLanguageArray($l);
      }
      $pdf->setFontSubsetting(true);
      $pdf->SetFont('helvetica', '', 10, '', true);
      $pdf->AddPage();

      $headings = array(
        Yii::t('discussion', 'Discussion Title'),
        Yii::t('discussion', 'Proposal Title'),
        Yii::t('discussion', 'Proposal Summary'),
        Yii::t('discussion', 'Description'),
        Yii::t('discussion', 'Author'),
        Yii::t('discussion', 'Creation Date'),
        Yii::t('discussion', 'Status'),
        Yii::t('discussion', 'Vote on triangle'),
        Yii::t('discussion', 'Number of Opinions'),
        Yii::t('discussion', 'Number of Links')
      );

      $allProposalsForSingleDiscussion = $this->createDataForExport($allProposals,
        $discussionDetail);
      $html = $this->renderPartial('//admin/pdfReport',
      array(
        'allProposals' => $allProposalsForSingleDiscussion,
        'headings' => $headings
      ), true);
      $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
      ob_get_clean();
      $pdf->Output('report_' . date("Ymd") . '.pdf', 'D');
      exit();
    } catch(Exception $exception) {
      Yii::log('Error in PDF Generation.', ERROR, $exception->getMessage());
    }
  }

  /**
   * createDataForExport
   * This function is used to create data for csv and pdf for exporting all
   * proposals for a single discussion.
   *
   * @param array $allProposals
   * @param array $discussion
   * @return array
   */
  public function createDataForExport($allProposals, $discussion) {
    $allProposalsForSingleDiscussion = array();
    foreach ($allProposals['allProposals'] as $proposal) {
      $singleProposal = array('discussion_title' => '', 'title' => '', 'summary' => '', 'description' => '', 'author' => '',
        'creation_date' => '', 'status' => '', 'opinion_voting_count' => 0, 'text_opinion_count' => 0, 'total_links' => 0);
      $singleProposal['discussion_title'] = $discussion['title'];
      $singleProposal['proposal_id'] = $proposal['id'];
      $singleProposal['title'] = htmlspecialchars_decode(strip_tags(html_entity_decode($proposal['title'])));
      $singleProposal['summary'] = htmlspecialchars_decode(strip_tags(html_entity_decode($proposal['content']['summary'])));
      $singleProposal['description'] = htmlspecialchars_decode(strip_tags(html_entity_decode($proposal['content']['description'])));
      $singleProposal['author'] = $proposal['author']['name'];
      $singleProposal['creation_date'] = $proposal['creation_date'];
      $singleProposal['status'] = $proposal['status'];
      if (array_key_exists($proposal['id'], $allProposals['opinions']) &&
        array_key_exists('opinions', $allProposals['opinions'][$proposal['id']])) {
        foreach ($allProposals['opinions'][$proposal['id']]['opinions'] as $opinions) {
          foreach ($opinions as $opinion) {
            if (!empty($opinion['content']['description']) && !array_key_exists($opinion['author']['slug'], $allProposals['adminEmails'])) {
              $singleProposal['text_opinion_count'] += 1;
            }
            foreach ($opinion['tags'] as $tag) {
              if ($tag['scheme'] == INDEX_TAG_SCHEME) {
                if (!array_key_exists($opinion['author']['slug'], $allProposals['adminEmails'])) {
                  $singleProposal['opinion_voting_count'] += 1;
                }
                break;
              }
            }
            if (array_key_exists($proposal['id'], $allProposals['links']) &&
              array_key_exists('links', $allProposals['links'][$proposal['id']])) {
              foreach ($allProposals['links'][$proposal['id']]['links'] as $author => $links) {
                if (!array_key_exists($author, $allProposals['adminEmails'])) {
                  $singleProposal['total_links'] += count($links);
                }
              }
            }
          }
        }
      }
      $allProposalsForSingleDiscussion[] = $singleProposal;
    }
    return $allProposalsForSingleDiscussion;
  }

  /**
   * actionExportOpinions
   * This method is used for exporting csv for opinions of discussions and proposals.
   * Current csv mode is download.
   */
  public function actionExportOpinions() {
    try {
      $isAdmin = checkPermission('access_report');
      if ($isAdmin == false) {
        $this->redirect(BASE_URL);
      }
      $proposalOpinions = array();
      if (array_key_exists('type', $_GET) && !empty($_GET['type']) && array_key_exists('id', $_GET) && !empty($_GET['id'])) {
        switch($_GET['type']) {
          case 'discussion':
            $proposalOpinions = $this->_getOpinionForDiscussion($_GET['id']);
            break;
          case 'proposal':
            if (array_key_exists('id', $_GET) && !empty($_GET['id'])) {
              $proposalOpinions = $this->_gettingProposalOpinion($_GET['id']);
            }
            break;
        }
      }
      if (!empty($proposalOpinions)) {
        $this->_downloadOpinionCsv($proposalOpinions);
      }
    } catch (Exception $e) {
      Yii::log('Error in actionExportOpinions.', ERROR, $e->getMessage());
    }
    exit;
  }

  /**
   * _getOpinionForDiscussion
   * function is for getting opinion for one or more discussion
   * @param string $id - discussion id or 'all' for all discussion
   * @return array $allOpinion - all ecxp-ected opinion
   */
  private function _getOpinionForDiscussion($id) {
    $discussion = new Discussion();
    $gettingProposalForAllDiscussion = FALSE;
    if ($id == 'all') {
      $gettingProposalForAllDiscussion = TRUE;
    } else {
      $discussion->id = $id;
    }
    $allOpinion = array();
    $allProposal = $discussion->getProposalForAdmin(TRUE, $gettingProposalForAllDiscussion);
    if (!empty($allProposal)) {
      foreach ($allProposal as $proposal) {
        if (isset($proposal['id'])) {
          $opinionForProposal = $this->_gettingProposalOpinion($proposal['id']);
          $allOpinion = array_merge($allOpinion, $opinionForProposal);
        }
      }
    }
    return $allOpinion;
  }

  /**
   * _gettingProposalOpinion
   * function is used for getting opinion for one proposal only
   * @param int $proposalId - proposal id
   * @param array $proposal - proposal detail
   * @return array $proposalOpinions - opinion for one proposal
   */
  private function _gettingProposalOpinion($proposalId, $proposal = array()) {
    try {
      $proposalOpinions = array();
      $title = '';
      $aggregatorManager = new AggregatorManager();
      if (empty($proposal)) {
        $proposalTitle = $aggregatorManager->getEntry(1, 0, $proposalId, 'active', '', '', '', 0, '', '', 1, '', array(), '', 'title', '', '', '', CIVICO, '');
        if (array_key_exists('0', $proposalTitle) && !empty($proposalTitle[0])) {
          if (array_key_exists('title', $proposalTitle[0]) && !empty($proposalTitle[0]['title'])) {
            $title = $proposalTitle[0]['title'];
          }
        }
      } else {
        $title = isset($proposal['title'])?$proposal['title']:'';
      }
      $activeOpinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . OPINION_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '', 'status,author,id,content,related,tags,creation_date', '', '', trim('proposal,' . $proposalId), CIVICO);
      $inactiveOpinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'inactive', 'link{' . OPINION_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(), '', 'status,author,id,content,related,tags,creation_date', '', '', trim('proposal,' . $proposalId), CIVICO);
      $opinions = array_merge($activeOpinions, $inactiveOpinions);
      $author = array();
      foreach ($opinions as $opinion) {
        $proposalOpinion = array();
        if (array_key_exists('count', $opinion)) {
          array_pop($opinions);
          continue;
        }
        if (array_key_exists('related', $opinion) && !empty($opinion['related'])) {
          if (array_key_exists('id', $opinion['related']) && !empty($opinion['related']['id'])) {
            $proposalOpinion['proposal_id'] = $opinion['related']['id'];
          }
        }
        $proposalOpinion['proposal_title'] = $title;
        if (array_key_exists('content', $opinion) && !empty($opinion['content'])) {
          $proposalOpinion['description'] = $opinion['content']['description'];
        }
        if (array_key_exists('creation_date', $opinion) && !empty($opinion['creation_date'])) {
          $proposalOpinion['creation_date'] = $opinion['creation_date'];
        }
        if (array_key_exists('author', $opinion) && !empty($opinion['author'])) {
          $proposalOpinion['author_name'] = $opinion['author']['name'];
          $proposalOpinion['author_id'] = $opinion['author']['slug'];
          $author[] = $opinion['author']['slug'];
        }
        $proposalOpinions[] = $proposalOpinion;
      }
      $author = array_unique($author);
      $userIdentityApi = new UserIdentityAPI();
      $userEmail = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('id' => $author), TRUE, false);
      $emails = array();
      if (array_key_exists('_items', $userEmail) && !empty($userEmail['_items'])) {
        foreach ($userEmail['_items'] as $email) {
          $emails[$email['_id']] = $email['email'];
        }
      }

      $userController = New UserController('user');
      $userAdditionalInfo = $userController->getUserAdditionalInfo($author);
      $additionalInfo = defined('ADDITIONAL_INFORMATION') ? json_decode(ADDITIONAL_INFORMATION, TRUE) : array();
      ksort($additionalInfo);
      if (!empty($proposalOpinions)) {
        foreach ($proposalOpinions as &$proposalOpinion) {
          $proposalOpinion['author_email'] = '';
          if (array_key_exists($proposalOpinion['author_id'], $emails)) {
            $proposalOpinion['author_email'] = $emails[$proposalOpinion['author_id']];
          }
          //add user additional info header
          $authorInfo = array();
          if (array_key_exists($proposalOpinion['author_id'], $userAdditionalInfo)) {
            $authorInfo = $userAdditionalInfo[$proposalOpinion['author_id']];
          }
          foreach ($additionalInfo as $infoKey => $info) {
            $proposalOpinion[$infoKey] = isset($authorInfo[$infoKey])? $authorInfo[$infoKey] : '';
          }
          unset($proposalOpinion['author_id']);
        }
      }
    } catch (Exception $e) {
      Yii::log('Error in actionExportOpinions.', ERROR, $e->getMessage());
    }
    return $proposalOpinions;
  }

  /**
   * _downloadOpinionCsv
   * This method is used to download csv for opinions.
   * Csv is currently in download mode.
   *
   * @param array $proposalOpinions
   */
  public function _downloadOpinionCsv($proposalOpinions) {
    try {
      $header = array(
        'proposal_id' => Yii::t('discussion', 'Proposal Id'),
        'proposal_title' => Yii::t('discussion', 'Proposal Title'),
        'description' => Yii::t('discussion', 'Description'),
        'creation_date' => Yii::t('discussion', 'Creation Date'),
        'author_name' => Yii::t('discussion', 'Author'),
        'author_email' => Yii::t('discussion', 'Author Email')
      );
      //add user additional info header
      $additionalInfo = defined('ADDITIONAL_INFORMATION') ? json_decode(ADDITIONAL_INFORMATION, TRUE) : array();
      ksort($additionalInfo);
      foreach ($additionalInfo as $infoKey => $info) {
        $header[$infoKey] = Yii::t('discussion', $info['text']);
      }
      header("Content-disposition: attachment; filename=report_" . date("Ymd") .".csv");
      header("Content-Type: text/csv");
      $filePath = fopen("php://output", 'w');
      @fputcsv($filePath, $header);
      foreach ($proposalOpinions as $opinion) {
        @fputcsv($filePath, $opinion);
      }
      exit;
    } catch(Exception $e) {
      Yii::log('Error in CSV Generation for opinions.', ERROR, $e->getMessage());
    }
  }

  /**
   * getUserAdditionalInfo
   * This function is used to get user additional user info.
   * @param array $authorIds
   * @return array
   * @throws Exception
   */
  public function getUserAdditionalInfo($authorIds) {
    try {
      $users = array();
      if (isModuleExist('backendconnector') == false) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing'));
      }
      $module = Yii::app()->getModule('backendconnector');
      if (empty($module)) {
        throw new Exception(Yii::t('discussion', 'backendconnector module is missing or not defined'));
      }
      if (is_array($authorIds)) {
        $authorIds = array_unique($authorIds);
      }
      $userIdentityApi = new UserIdentityAPI();
      $emails = $this->_getContributorsEmail($authorIds);
      $question = json_decode(ADDITIONAL_INFORMATION, TRUE);
      if (!empty($emails)) {
        $userInfo = $userIdentityApi->getUserDetail(IDM_USER_ENTITY, array('email' => $emails));
        if (array_key_exists('_items', $userInfo)) {
          foreach ($userInfo['_items'] as $user) {
            if (array_key_exists('age', $user)) {
              $users[$user['_id']]['age'] = $user['age'];
            }
            if (array_key_exists('age_range', $user)) {
              $users[$user['_id']]['age_range'] = $user['age_range'];
            }
            if (array_key_exists('sex', $user) && array_key_exists(0, $user['sex'])
            && array_key_exists($user['sex'][0], $question['sex']['value'])) {
              $users[$user['_id']]['sex'] = $question['sex']['value'][$user['sex'][0]];
            }
            if (array_key_exists('citizenship', $user)
            && array_key_exists($user['citizenship'], $question['citizenship']['value'])) {
              $users[$user['_id']]['citizenship'] = $question['citizenship']['value'][$user['citizenship']];
            }
            if (array_key_exists('education-level', $user) &&
            array_key_exists($user['education-level'], $question['education_level']['value'])) {
              $users[$user['_id']]['education_level'] = $question['education_level']['value'][$user['education-level']];
            }
            if (array_key_exists('work', $user)
            && array_key_exists($user['work'], $question['work']['value'])) {
              $users[$user['_id']]['work'] = $question['work']['value'][$user['work']];
            }
            if (array_key_exists('public-authority', $user) && array_key_exists('name', $user['public-authority'])) {
              if (array_key_exists($user['public-authority']['name'], $question['public_authority']['value'])) {
                $users[$user['_id']]['public_authority'] = $question['public_authority']['value'][$user['public-authority']['name']];
              }
            }
            if (array_key_exists('profile-info', $user) && !empty($user['profile-info'])) {
              if (array_key_exists('profession', $user['profile-info'])) {
                $users[$user['_id']]['profession'] =  $user['profile-info']['profession'];
              }
              if (array_key_exists('residence', $user['profile-info'])) {
                $users[$user['_id']]['residence'] = $user['profile-info']['residence'];
              }
              if (array_key_exists('association', $user['profile-info']) &&
                array_key_exists('value', $question['association']) &&
                array_key_exists($user['profile-info']['association'], $question['association']['value'])) {
                $users[$user['_id']]['association'] = $question['association']['value'][$user['profile-info']['association']];
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in getUserAdditionalInfo');
    }
    return $users;
  }

  /**
   * _getContributorsEmail
   * function is used for getting author email who have submitted proposal, opinions
   * and links on the basis of contributors id (id)
   * @param array $contributorsId
   * @return array $contributorEmail - email id of contributor (user)
   */
  private function _getContributorsEmail($contributorsId) {
    try {
      $userEmail = array();
      if (empty($contributorsId)) {
        return $userEmail;
      }
      $identityManager = new UserIdentityAPI();
      $authorsEmails = $identityManager->getUserDetail(IDM_USER_ENTITY, array('id' =>
      $contributorsId), true, false);
      if (array_key_exists('_items', $authorsEmails) && !empty($authorsEmails['_items'])) {
        foreach ($authorsEmails['_items'] as $authorEmail) {
          if (array_key_exists('_id', $authorEmail) && !empty($authorEmail['_id'])
            && array_key_exists('email', $authorEmail) && !empty($authorEmail['email'])) {
            $userEmail[$authorEmail['_id']] = $authorEmail['email'];
          }
        }
      }
    } catch (Exception $e) {
      Yii::log($e->getMessage(), ERROR, 'Error in _getContributorsEmail');
    }
    return $userEmail;
  }
}
