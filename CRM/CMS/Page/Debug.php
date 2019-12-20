<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 06-Dec-2019
 * @license  AGPL-3.0
 */

/**
 * Class CRM_CMS_Page_Debug Used for debugging - can go in the last version
 */
class CRM_CMS_Page_Debug extends CRM_Core_Page {

  public function run() {


    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('Debug'));
    $processor = new CRM_CMS_SubmissionProcessor();
    $processor->process();
    parent::run();

  }


}
