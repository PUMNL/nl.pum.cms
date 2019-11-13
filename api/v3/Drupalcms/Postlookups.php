<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 15-Oct-2019
 * @license AGPL-3.0
 */
use CRM_CMS_ExtensionUtil as E;

function _civicrm_api3_Drupalcms_postlookups_spec(&$spec) {
}


function civicrm_api3_Drupalcms_postlookups($params) {
    $returnValues = [];
    $lookup = new CRM_CMS_Lookup();
    $lookup->representatives();
    return civicrm_api3_create_success($returnValues, $params, 'Drupalcms', 'PostLookups');
}
