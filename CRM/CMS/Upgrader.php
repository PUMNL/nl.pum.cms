<?php
use CRM_CMS_ExtensionUtil as E;

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
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  public function postInstall() {

    $apiParams = [
      'name' => 'DrupalCms: PostLookups',
      'description' => 'Post lookup tables to the DrupalCMS with rest call',
      'run_frequency' => 'Hourly',
      'api_entity' => 'Drupalcms',
      'api_action' => 'postlookups',
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

    civicrm_api3('Job', 'create', $apiParams
    );

    $apiParams = [
      'name' => 'DrupalCms: Getsubmissions',
      'description' => 'Get information from Drupal CMS an process it in CiviCRM',
      'run_frequency' => 'Hourly',
      'api_entity' => 'Drupalcms',
      'api_action' => 'getsubmissions',
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

    civicrm_api3('Job', 'create', $apiParams
    );
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
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


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
