<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 15-Oct-2019
 * @license AGPL-3.0
 */
use CRM_CMS_ExtensionUtil as E;

function _civicrm_api3_Drupalcms_getsubmissions_spec(&$spec) {
}


function civicrm_api3_Drupalcms_getsubmissions($params) {
    $returnValues = [];
    return civicrm_api3_create_success($returnValues, $params, 'Drupalcms', 'Getsubmissions');
}
