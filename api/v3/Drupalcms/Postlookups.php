<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 06-Dec-2019
 * @license  AGPL-3.0
 */

function _civicrm_api3_Drupalcms_postlookups_spec(&$spec) {
}


function civicrm_api3_Drupalcms_postlookups($params)
{
    try {
        $returnValues = [];
        $lookup = new CRM_CMS_Lookup();
        $lookup->representatives();
        $lookup->projectOfficers();
        $lookup->countryCoordinators();
        $lookup->countries();
        $lookup->sectors();
        $lookup->optionValues();
        return civicrm_api3_create_success($returnValues, $params, 'Drupalcms', 'PostLookups');

    } catch (Exception $ex) {
        $mailError = new CRM_CMS_MailError();
        //CRM_Core_Error::debug_log_message($ex);
        $mailError->setError($ex);
        civicrm_api3('Email', 'Send', [
            'contact_id' => CRM_Core_BAO_Setting::getItem('Drupal CMS Api', 'drupal_cms_contact_id'),
            'template_id' => CRM_Core_BAO_Setting::getItem('Drupal CMS Api', 'drupal_cms_template_id'),
        ]);
        throw new Exception($ex);

    }
}
