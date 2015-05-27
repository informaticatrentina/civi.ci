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
ob_start();
class AdminController extends PageController {
  
  /**
   * beforeAction
   *
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
      $singleProposal = array('discussion_title' => '', 'title' => '', 'description' => '', 'author' => '',
        'creation_date' => '', 'status' => '', 'opinion_voting_count' => 0, 'text_opinion_count' => 0, 'total_links' => 0);
      $singleProposal['discussion_title'] = $discussion['title'];
      $singleProposal['proposal_id'] = $proposal['id'];
      $singleProposal['title'] = htmlspecialchars_decode(strip_tags(html_entity_decode($proposal['title'])));
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

}

?> 
