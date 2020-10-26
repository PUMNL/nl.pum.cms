<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 06-Dec-2019
 * @license  AGPL-3.0
 */

function _civicrm_api3_Drupalcms_dedup_spec(&$spec) {
}


function civicrm_api3_Drupalcms_dedup($params)
{
    try {
        $returnValues = [];
        $dedup = new CRM_CMS_Dedup();
        $dedup->process('Representative','contact_id');
        $dedup->process('Countrycoordinator','contact_id');
        $dedup->process('Projectofficer','contact_id');
        $dedup->process('Country','country_id');
        $dedup->process('Sector','sector_id');
        $dedup->process('OptionValue','value');
        return civicrm_api3_create_success($returnValues, $params, 'Drupalcms', 'Dedup');
    } catch (Exception $ex) {
        $mailError = new CRM_CMS_MailError();
        $mailError->setError($ex);
        civicrm_api3('Email', 'Send', [
            'contact_id' => CRM_Core_BAO_Setting::getItem('Drupal CMS Api', 'drupal_cms_contact_id'),
            'template_id' => CRM_Core_BAO_Setting::getItem('Drupal CMS Api', 'drupal_cms_template_id'),
        ]);
        throw new Exception($ex);
    }
}
