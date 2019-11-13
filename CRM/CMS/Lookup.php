<?php

/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 15-Oct-2019
 * @license AGPL-3.0
 */

class CRM_CMS_Lookup {

  public function representatives()
  {
    $config = CRM_Newcustomer_Config::singleton();
    $sql = <<<SQL
      SELECT distinctrow rep.id contact_id
     , rep.display_name         display_name
     , rep.sort_name            sort_name
     , em.email                 email
     , ph.phone                 phone
     , adr.city                 city
     FROM civicrm_contact rep
     JOIN civicrm_relationship cr ON rep.id = cr.contact_id_b AND cr.relationship_type_id = %1 AND is_active=1
     JOIN civicrm_contact cntr ON (cr.contact_id_a = cntr.id) AND cntr.contact_type = 'Organization' AND cntr.contact_sub_type LIKE '%Country%'
     LEFT JOIN civicrm_email em ON (rep.id = em.contact_id AND em.is_primary = 1)
     LEFT JOIN civicrm_phone ph ON (rep.id = ph.contact_id AND ph.is_primary = 1)
     LEFT JOIN civicrm_address adr ON (rep.id = adr.contact_id AND adr.is_primary = 1)
     ORDER BY rep.sort_name
SQL;
      set_time_limit(0);
      $rest = new CRM_CMS_Rest();
      $remote = $rest->getAll('Representative');
      $remoteReps = [];
      foreach ($remote['Items'] as $key => $item) {
          $remoteReps[$item['Item']['contact_id']] = $item['Item']['Id'];
      }
    $dao = CRM_Core_DAO::executeQuery($sql,[
       1 => [$config->getRepresepentativeRelationshipTypeId(),'Integer']
      ]
      );
    while($dao->fetch()){
      $result = [
        'contact_id' => $dao->contact_id,
        'display_name'   => $dao->display_name,
        'sort_name'       => $dao->sort_name,
        'email'           => $dao->email,
        'phone'           => $dao->phone,
        'city'            => $dao->city,
      ];
        if(key_exists($dao->contact_id,$remoteReps)){
            $rest->update('Representative',$remoteReps[$dao->contact_id],$result);
            unset($remoteReps[$dao->contact_id]);
        } else {
            $rest->create('Representative',$result);
        };
    }

  }

  public function countries()
  {

    set_time_limit(0);
    $rest = new CRM_CMS_Rest();
    $remote = $rest->getAll('Country');
    $remoteCountries=[];
    foreach($remote['Items'] as $key => $item){
        $remoteCountries[$item['Item']['country_id']] = $item['Item']['Id'];
    }

    $dao = CRM_Core_DAO::executeQuery('select id as country_id, iso_code, name from civicrm_country');

    while($dao->fetch()){
      $result = [
         'country_id' => $dao->country_id,
         'iso_code'   => $dao->iso_code,
         'name'       => $dao->name,
      ];
      if(key_exists($dao->country_id,$remoteCountries)){
          $rest->update('Country',$remoteCountries[$dao->country_id],$result);
          unset($remoteCountries[$dao->country_id]);
      } else {
          $rest->create('Country',$result);
      };
    }

    foreach($remoteCountries as $remoteCountryId){
        $rest->delete('Country',$remoteCountryId);
    }
  }

    public function sectors()
    {
        set_time_limit(0);
        $rest = new CRM_CMS_Rest();
        $remote = $rest->getAll('Sector');
        $remoteSectors = [];
        foreach ($remote['Items'] as $key => $item) {
            $remoteSectors[$item['Item']['sector_id']] = $item['Item']['Id'];
        }

        $dao = CRM_Core_DAO::executeQuery('select id as sector_id, label from civicrm_segment where is_active=1');
        while ($dao->fetch()) {
            $result = [
                'sector_id' => $dao->sector_id,
                'name' => $dao->label,
            ];
            if(key_exists($dao->sector_id,$remoteSectors)){
                $rest->update('Sector',$remoteSectors[$dao->sector_id],$result);
                unset($remoteSectors[$dao->sector_id]);
            } else {
                $rest->create('Sector',$result);
            };
        }
    }
}
