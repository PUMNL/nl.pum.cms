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
    $returnValues = [];
    $rest = new CRM_CMS_Rest();
    $count = 0;

    $dao = CRM_Core_DAO::executeQuery('select id, entity, submission_id from pum_cms_submission');
    while($dao->fetch()){
        $rest->delete($dao->entity,$dao->submission_id);
        CRM_Core_DAO::executeQuery('delete from pum_cms_submission where id=%1',[
            1 => [$dao->id,'Integer'],
        ]);
        $count++;
    }
    $return['count']= $count;
    return civicrm_api3_create_success($returnValues, $params, 'Drupalcms', 'PostLookups');
}
