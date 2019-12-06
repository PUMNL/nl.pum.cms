<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 06-Dec-2019
 * @license  AGPL-3.0
 */

function _civicrm_api3_Drupalcms_getsubmissions_spec(&$spec) {
}


function civicrm_api3_Drupalcms_getsubmissions($params) {
    $returnValues = [];
    $submissionProcessor = new CRM_CMS_SubmissionProcessor();
    $submissionProcessor->process();
    return civicrm_api3_create_success($returnValues, $params, 'Drupalcms', 'Getsubmissions');
}
