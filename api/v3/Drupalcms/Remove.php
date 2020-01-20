<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 06-Dec-2019
 * @license  AGPL-3.0
 */

function _civicrm_api3_Drupalcms_remove_spec(&$spec) {
}


function civicrm_api3_Drupalcms_remove($params)
{
    try {
        $returnValues = [];
        $rest = new CRM_CMS_Rest();
        $count = 0;

        $dao = CRM_Core_DAO::executeQuery("select id, entity, submission_id from pum_cms_submission where state='P'");
        while ($dao->fetch()) {
            $rest->delete($dao->entity, $dao->submission_id);
            CRM_Core_DAO::executeQuery("update pum_cms_submission set state = 'D' where id=%1", [
                1 => [$dao->id, 'Integer'],
            ]);
            $count++;
        }
        $return['count'] = $count;
        return civicrm_api3_create_success($returnValues, $params, 'Drupalcms', 'Remove');
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
