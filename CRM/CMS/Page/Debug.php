<?php
use CRM_CMS_ExtensionUtil as E;

class CRM_CMS_Page_Debug extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('Debug'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));
    $rest = new CRM_CMS_Rest();
    $result = $rest->get('/api/v1/Country/');
    $this->assign('Items',$result['Items']);
    $config = CRM_Core_Config::singleton();
    parent::run();

  }

}
