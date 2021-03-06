<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 06-Dec-2019
 * @license  AGPL-3.0
 */

/**
 * Collection of upgrade steps.
 */
class CRM_CMS_Upgrader extends CRM_CMS_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   */
  public function install() {
    $this->executeSqlFile('sql/create_submission.sql');
    CRM_CMS_Upgrader::upgrade_1001();
    CRM_CMS_Upgrader::upgrade_1002();
    CRM_CMS_Upgrader::upgrade_1003();
    CRM_CMS_Upgrader::upgrade_1004();
    CRM_CMS_Upgrader::upgrade_1005();
    CRM_CMS_Upgrader::upgrade_1006();
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */

  public function postInstall(){
     $this->createDrupalCMSJob(
         'DrupalCms: PostLookups', // name
         'Post lookup tables to the DrupalCMS with rest call', // description
         'postlookups',// action
         'Hourly'// frequency
     );

      $this->createDrupalCMSJob(
          'DrupalCms: Getsubmissions', // name
          'Get information from Drupal CMS an process it in CiviCRM', // description
          'getsubmissions',// action
          'Always'// frequency
      );

      $this->createDrupalCMSJob(
          'DrupalCms: Remove', // name
          'Removes the subscriptions from the CMS', // description
          'remove',// action
          'Daily'// frequency
      );
  }

  private function createDrupalCMSJob($name,$description,$action,$frequency) {

    $apiParams = [
      'name' => $name,
      'description' => $description,
      'run_frequency' => $frequency,
      'api_entity' => 'Drupalcms',
      'api_action' => $action,
      'is_active'  => 0,
      'parameters' => '',
    ];

    $jobId = CRM_Core_DAO::singleValueQuery('select id from civicrm_job where name=%1',[
        1 => [$apiParams['name'],'String']
      ]
    );

    if($jobId){
      $apiParams['id'] = $jobId;
    }

    civicrm_api3('Job', 'create', $apiParams);
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   **/
  public function uninstall() {
    $names = ['DrupalCms: PostLookups','DrupalCms: Getsubmissions'];
    foreach($names as $name){
      CRM_Core_DAO::executeQuery('delete from civicrm_job where name=%1',[
        1 => [$name,'String']
      ]);
    }
    $this->executeSqlFile('sql/drop_submission.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  public function enable() {
    $names = ['DrupalCms: PostLookups','DrupalCms: Getsubmissions'];
    foreach($names as $name){
      CRM_Core_DAO::executeQuery('update civicrm_job set is_active=1 where name=%1',[
        1 => [$name,'String']
      ]);
    }
  }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  public function disable() {
     $names = ['DrupalCms: PostLookups','DrupalCms: Getsubmissions'];
     foreach($names as $name){
       CRM_Core_DAO::executeQuery('update civicrm_job set is_active=0 where name=%1',[
         1 => [$name,'String']
       ]);
     }
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


    /**
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_1001()
    {
        $this->executeCustomDataFile('xml/1001_install_custom_group.xml');
        return TRUE;
    }

    public function upgrade_1002()
    {

        try {
            civicrm_api3('Group', 'create', [
                'name' => 'Active_Countries',
                'title' => 'Active Countries',
                'description' => 'Used to mark the countries where PUM is active',
                'is_active' => 1,
            ]);
        } catch (CiviCRM_API3_Exception $ex) {
            // in case it already exists - just ignore
        }
        return TRUE;
    }

    public function upgrade_1003()
    {
        $this->executeSqlFile('sql/1003_update_submission.sql');
        return TRUE;
    }

    public function upgrade_1004()
    {
      $this->executeSqlFile('sql/1004_update_submission.sql');
      return TRUE;
    }

  public function upgrade_1005() {
    $this->createDrupalCMSJob(
      'DrupalCms: Dedup', // name
      'Deduplicates possible doubles in the lookup tables of the CMS', // description
      'dedup',// action
      'Always'// frequency
    );
    return TRUE;
  }

  /**
   * CRM_CMS_Upgrader::upgrade_1006()
   *
   * Update field label Motivatian Expert => Motivation Expert
   *
   * @return
   */
  public function upgrade_1006() {
    try {
      $params_motivation_expert = array(
        'version' => 3,
        'sequential' => 1,
        'custom_group_id' => 'motivation_expert',
        'name' => 'motivation_expert_application',
      );
      $result_motivation_expert = civicrm_api('CustomField', 'getsingle', $params_motivation_expert);
    } catch (CiviCRM_API3_Exception $ex) {

    }

    if(!empty($result_motivation_expert['id'])){
      $params_fix_motivation_expert_label = array(
        'version' => 3,
        'sequential' => 1,
        'id' => $result_motivation_expert['id'],
        'label' => 'Motivation Expert'
      );

      $result_fix_motivation_expert_label = civicrm_api('CustomField', 'update', $params_fix_motivation_expert_label);
    }

    if(isset($result_fix_motivation_expert_label['is_error']) && $result_fix_motivation_expert_label['is_error'] == 0) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = E::ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
