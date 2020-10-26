<?php
/*
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 26-Oct-2020
 * @license  AGPL-3.0
 */

class CRM_CMS_Dedup {

  /* deduplicates a remote entity */
  public function process($entity, $key){
    set_time_limit(0);
    $rest = new CRM_CMS_Rest();
    // get the complete table of the remote entity
    $remote = $rest->getAll($entity);
    $remoteReps = [];
    foreach ($remote['Items'] as $k => $item) {
      if(key_exists($item['Item'][$key],$remoteReps)){
        // if a key is found for the second time - delete it - its a double
        $rest->delete($entity, $item['Item']['Id']);
      } else {
        // if a key is not found store it (its allowed to stay - its the first
        $remoteReps[$item['Item'][$key]] = $item['Item']['Id'];
      }
    }
  }
}
