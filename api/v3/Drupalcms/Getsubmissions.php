<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 06-Dec-2019
 * @license  AGPL-3.0
 */

function _civicrm_api3_Drupalcms_getsubmissions_spec(&$spec) {
}


function civicrm_api3_Drupalcms_getsubmissions($params) {
    try {
        $returnValues = [];
        $submissionProcessor = new CRM_CMS_SubmissionProcessor();
        $submissionProcessor->process();

        $failures = $submissionProcessor->reportFailures();
        if(!empty($failures)){
            CRM_Core_Error::debug_log_message('nl.pum.cms Getsubmissions Failures:');
            CRM_Core_Error::debug_log_message(print_r($failures,TRUE));
            $error = "<pre>\n";
            foreach($failures as $failure){

                $error .= "{$failure['entity']}\n";
                $error .= "{$failure['submission']}\n--\n";
                $error .= "{$failure['failure']}\n--------\n";
            }
            $error .= "</pre>";
            $mailError = new CRM_CMS_MailError();
            $mailError->setError($error);
            civicrm_api3('Email', 'Send', [
                'contact_id' => CRM_Core_BAO_Setting::getItem('Drupal CMS Api', 'drupal_cms_contact_id'),
                'template_id' => CRM_Core_BAO_Setting::getItem('Drupal CMS Api', 'drupal_cms_template_id'),
            ]);
        }

        return civicrm_api3_create_success($returnValues, $params, 'Drupalcms', 'Getsubmissions');
    } catch (Exception $ex) {
        $mailError = new CRM_CMS_MailError();
        $mailError->setError($ex->getMessage());
        civicrm_api3('Email', 'Send', [
            'contact_id' => CRM_Core_BAO_Setting::getItem('Drupal CMS Api', 'drupal_cms_contact_id'),
            'template_id' => CRM_Core_BAO_Setting::getItem('Drupal CMS Api', 'drupal_cms_template_id'),
        ]);
        throw new Exception($ex);
    }
}
