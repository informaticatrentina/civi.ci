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
  public function actionPdfGenerate($allProposals, $discussionDetail, $headings) {
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

      foreach ($allProposals as &$proposal) {
        htmlspecialchars_decode(strip_tags($proposal['title']));
        htmlspecialchars_decode(strip_tags($proposal['content']['description']));
      }
      $html = $this->renderPartial('//admin/pdfReport',
        array(
          'allProposals' => $allProposals,
          'discussionDetail' => $discussionDetail,
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
}

?> 
