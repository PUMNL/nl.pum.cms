<?php

use CRM_CMS_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_CMS_Form_Preferences extends CRM_Admin_Form_Preferences {
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('CMS Drupal Services - connectivity params'));
    $this->_varNames = [
      'Drupal CMS Api' => [
        'drupal_cms_url' =>  [
          'html_type' => 'text',
          'title' => ts('Remote REST Api URL'),
          'size'  => 64,
          'weight' => 3,
          'description' => ts('Example https://example.com/sites/all/modules/civicrm/extern/rest.php'),
        ],
        'drupal_cms_authtoken' =>  [
          'html_type' => 'text',
          'title' => ts('Authorization token for the Remote call'),
          'size'  => 64,
          'weight' => 4,
          'description' => ts('jdwuubbdyebeu'),
        ],
        ],
      ];
    parent::preProcess();
  }
}
