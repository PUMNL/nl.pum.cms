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

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));
    $rest = new CRM_CMS_Rest();
  //  $r1 = civicrm_api3('Drupalcms','postlookups');
  //    $lookup = new CRM_CMS_Lookup();
  //    $lookup->optionValues();
  //  $result = $rest->getAll('NewsletterSubscription');
  //  $this->assign('Items',$result['Items']);

  //  $submissionProcessor = new CRM_CMS_SubmissionProcessor();
  //  $submissionProcessor->process();

    $upgrader =  new  CRM_CMS_Upgrader();
    $upgrader->postInstall();
    parent::run();

  }

}
