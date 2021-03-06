<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 15-Oct-2019
 * @license AGPL-3.0
 */
class CRM_CMS_Lookup {

  var $countryGroupId;
  var $repGroupId;
  var $countryCoordinatorsGroupId;
  var $projectOfficersGroupId;

  /**
   * CRM_CMS_Lookup constructor.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct()
    {
        $this->countryGroupId = civicrm_api3('Group', 'getvalue', [
                'title' => 'Active countries',
                'return' => 'id'
            ]
        );
        $this->repGroupId = civicrm_api3('Group', 'getvalue', [
                'title' => 'Representatives',
                'return' => 'id'
            ]
        );
      $this->countryCoordinatorsGroupId = civicrm_api3('Group', 'getvalue', [
          'title' => 'Country Coordinators',
          'return' => 'id'
        ]
      );
      $this->projectOfficersGroupId = civicrm_api3('Group', 'getvalue', [
          'title' => 'Project Officers',
          'return' => 'id'
        ]
      );
    }

  /**
   * posts the list with countrycoordinators to the CMS Api
   */
  public function countryCoordinators(){
    $sql = <<<SQL
SELECT distinctrow max(cr.id) contact_id
                 , rep.display_name         display_name
                 , rep.sort_name            sort_name
                 , em.email                 email
                 , ph.phone                 phone
                 , vc.civicrm_country_id    country_id_responsible
FROM civicrm_contact rep
         JOIN civicrm_relationship cr ON rep.id = cr.contact_id_b AND cr.relationship_type_id = %1 AND cr.is_active=1 and (cr.end_date >= CURDATE() or cr.end_date is null) AND (cr.start_date <= CURDATE() or cr.start_date is null)
         JOIN civicrm_contact cntr ON (cr.contact_id_a = cntr.id) AND cntr.contact_type = 'Organization' AND cntr.contact_sub_type LIKE '%Country%'
         JOIN civicrm_group_contact cgc  ON (cgc.contact_id = cntr.id and cgc.group_id=%2 and cgc.status='Added')
         JOIN civicrm_group_contact cgcr  ON (cgcr.contact_id = rep.id and cgcr.group_id=%3 and cgcr.status='Added')
         LEFT JOIN civicrm_email em ON (rep.id = em.contact_id AND em.is_primary = 1)
         LEFT JOIN civicrm_phone ph ON (rep.id = ph.contact_id AND ph.is_primary = 1)
         LEFT JOIN civicrm_value_country vc ON (vc.entity_id=cntr.id)
group by rep.id,rep.sort_name,vc.civicrm_country_id
order by rep.sort_name
SQL;
    set_time_limit(0);
    $rest = new CRM_CMS_Rest();
    $remote = $rest->getAll('Countrycoordinator');
    $remoteReps = [];
    foreach ($remote['Items'] as $key => $item) {
      $remoteReps[$item['Item']['contact_id']] = $item['Item']['Id'];
    }
    $dao = CRM_Core_DAO::executeQuery($sql, [
        1 => [CRM_Core_DAO::getFieldValue('CRM_Contact_BAO_RelationshipType', 'Country Coordinator is', 'id', 'name_a_b'), 'Integer'],
        2 => [$this->countryGroupId, 'Integer'],
        3 => [$this->countryCoordinatorsGroupId, 'Integer'],
      ]
    );
    while($dao->fetch()){
      $result = [
        'contact_id' => $dao->contact_id,
        'display_name'           => $dao->display_name,
        'sort_name'              => $dao->sort_name,
        'email'                  => $dao->email,
        'phone'                  => $dao->phone,
        'country_id_responsible' => $dao->country_id_responsible,
      ];
      if(key_exists($dao->contact_id,$remoteReps)){
        $rest->update('Countrycoordinator',$remoteReps[$dao->contact_id],$result);
        unset($remoteReps[$dao->contact_id]);
      } else {
        $rest->create('Countrycoordinator',$result);
      };
    }

    foreach ($remoteReps as $remoteRepsId) {
      $rest->delete('Countrycoordinator', $remoteRepsId);
    }
  }


  /**
   * posts the list with countrycoordinators to the CMS Api
   */
  public function projectOfficers(){
    $sql = <<<SQL
SELECT distinctrow max(cr.id) contact_id
                 , rep.display_name         display_name
                 , rep.sort_name            sort_name
                 , em.email                 email
                 , ph.phone                 phone
                 , vc.civicrm_country_id    country_id_responsible
FROM civicrm_contact rep
         JOIN civicrm_relationship cr ON rep.id = cr.contact_id_b AND cr.relationship_type_id = %1 AND cr.is_active=1 and (cr.end_date >= CURDATE() or cr.end_date is null) AND (cr.start_date <= CURDATE() or cr.start_date is null)
         JOIN civicrm_contact cntr ON (cr.contact_id_a = cntr.id) AND cntr.contact_type = 'Organization' AND cntr.contact_sub_type LIKE '%Country%'
         JOIN civicrm_group_contact cgc  ON (cgc.contact_id = cntr.id and cgc.group_id=%2 and cgc.status='Added')
         JOIN civicrm_group_contact cgcr  ON (cgcr.contact_id = rep.id and cgcr.group_id=%3 and cgcr.status='Added')
         LEFT JOIN civicrm_email em ON (rep.id = em.contact_id AND em.is_primary = 1)
         LEFT JOIN civicrm_phone ph ON (rep.id = ph.contact_id AND ph.is_primary = 1)
         LEFT JOIN civicrm_value_country vc ON (vc.entity_id=cntr.id)
group by rep.id,rep.sort_name,vc.civicrm_country_id
order by rep.sort_name
SQL;
    set_time_limit(0);
    $rest = new CRM_CMS_Rest();
    $remote = $rest->getAll('Projectofficer');
    $remoteReps = [];
    foreach ($remote['Items'] as $key => $item) {
      $remoteReps[$item['Item']['contact_id']] = $item['Item']['Id'];
    }
    $dao = CRM_Core_DAO::executeQuery($sql, [
        1 => [CRM_Core_DAO::getFieldValue('CRM_Contact_BAO_RelationshipType', 'Project Officer for', 'id', 'name_a_b'), 'Integer'],
        2 => [$this->countryGroupId, 'Integer'],
        3 => [$this->projectOfficersGroupId, 'Integer'],
      ]
    );
    while($dao->fetch()){
      $result = [
        'contact_id' => $dao->contact_id,
        'display_name'           => $dao->display_name,
        'sort_name'              => $dao->sort_name,
        'email'                  => $dao->email,
        'phone'                  => $dao->phone,
        'country_id_responsible' => $dao->country_id_responsible,
      ];
      if(key_exists($dao->contact_id,$remoteReps)){
        $rest->update('Projectofficer',$remoteReps[$dao->contact_id],$result);
        unset($remoteReps[$dao->contact_id]);
      } else {
        $rest->create('Projectofficer',$result);
      };
    }

    foreach ($remoteReps as $remoteRepsId) {
      $rest->delete('Projectofficer', $remoteRepsId);
    }
  }
  /**
   * posts the list with representatives to the CMS Api
   */
  public function representatives()
  {

    $sql = <<<SQL
      SELECT distinctrow max(cr.id) contact_id
     , rep.display_name         display_name                  
     , rep.sort_name            sort_name
     , em.email                 email
     , ph.phone                 phone
     , adr.city                 city
     , ifnull(adr.country_id,0)           country_id_residence
     , vc.civicrm_country_id    country_id_responsible 
     FROM civicrm_contact rep
     JOIN civicrm_relationship cr ON rep.id = cr.contact_id_b AND cr.relationship_type_id = %1 AND cr.is_active=1 and (cr.end_date >= CURDATE() or cr.end_date is null) AND (cr.start_date <= CURDATE() or cr.start_date is null)
     JOIN civicrm_contact cntr ON (cr.contact_id_a = cntr.id) AND cntr.contact_type = 'Organization' AND cntr.contact_sub_type LIKE '%Country%'
     JOIN civicrm_group_contact cgc  ON (cgc.contact_id = cntr.id and cgc.group_id=%2 and cgc.status='Added')  
     JOIN civicrm_group_contact cgcr  ON (cgcr.contact_id = rep.id and cgcr.group_id=%3 and cgcr.status='Added')        
     LEFT JOIN civicrm_email em ON (rep.id = em.contact_id AND em.is_primary = 1)
     LEFT JOIN civicrm_phone ph ON (rep.id = ph.contact_id AND ph.is_primary = 1)
     LEFT JOIN civicrm_address adr ON (rep.id = adr.contact_id AND adr.is_primary = 1)
     LEFT JOIN civicrm_value_country vc ON (vc.entity_id=cntr.id)
     group by rep.id,rep.sort_name,vc.civicrm_country_id
     order by rep.sort_name
SQL;
      set_time_limit(0);
      $rest = new CRM_CMS_Rest();
      $remote = $rest->getAll('Representative');
      $remoteReps = [];
      foreach ($remote['Items'] as $key => $item) {
          $remoteReps[$item['Item']['contact_id']] = $item['Item']['Id'];
      }
      $dao = CRM_Core_DAO::executeQuery($sql, [
              1 => [CRM_Newcustomer_Config::singleton()->getRepresepentativeRelationshipTypeId(), 'Integer'],
              2 => [$this->countryGroupId, 'Integer'],
              3 => [$this->repGroupId, 'Integer'],
          ]
      );
    while($dao->fetch()){
      $result = [
        'contact_id' => $dao->contact_id,
        'display_name'           => $dao->display_name,
        'sort_name'              => $dao->sort_name,
        'email'                  => $dao->email,
        'phone'                  => $dao->phone,
        'city'                   => $dao->city,
        'country_id_residence'   => $dao->country_id_residence,
        'country_id_responsible' => $dao->country_id_responsible,
      ];
        if(key_exists($dao->contact_id,$remoteReps)){
            $rest->update('Representative',$remoteReps[$dao->contact_id],$result);
            unset($remoteReps[$dao->contact_id]);
        } else {
            $rest->create('Representative',$result);
        };
    }

    foreach ($remoteReps as $remoteRepsId) {
        $rest->delete('Representative', $remoteRepsId);
    }
  }

  /**
   * posts the list with countries to the CMS Api
   */
  public function countries()
  {
    // A country is a contact in the PUM database
    $sql = <<<SQL
    SELECT distinctrow  cntr.id country_id
    ,                   cntr.iso_code
    ,                   cntr.name
    FROM civicrm_contact cc
    JOIN civicrm_group_contact cgc  ON (cgc.contact_id = cc.id and cgc.group_id=%1 and cgc.status='Added')
    JOIN civicrm_value_country vc ON (vc.entity_id=cc.id)
    JOIN civicrm_country cntr ON (cntr.id=vc.civicrm_country_id)
    WHERE cc.contact_type = 'Organization' AND cc.contact_sub_type LIKE '%Country%'
SQL;
      set_time_limit(0);
      $rest = new CRM_CMS_Rest();
      $remote = $rest->getAll('Country');
      $remoteCountries = [];
      foreach ($remote['Items'] as $key => $item) {
          $remoteCountries[$item['Item']['country_id']] = $item['Item']['Id'];
      }
      $dao = CRM_Core_DAO::executeQuery($sql, [
              1 => [$this->countryGroupId, 'Integer'],
          ]
      );
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

  /**
   * posts the list with sectors to the CMS Api
   */
  public function sectors()
    {
        set_time_limit(0);
        $rest = new CRM_CMS_Rest();
        $remote = $rest->getAll('Sector');
        $remoteSectors = [];
        foreach ($remote['Items'] as $key => $item) {
            $remoteSectors[$item['Item']['sector_id']] = $item['Item']['Id'];
        }

        $dao = CRM_Core_DAO::executeQuery('select id as sector_id, label from civicrm_segment where is_active=1 and parent_id is null order by label');
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
        foreach ($remoteSectors as $remoteSectorId) {
            $rest->delete('Sector', $remoteSectorId);
        }
    }

  /**
   * posts a list with option values to the CMS Api
   */
  public function optionValues()
    {
        $sql=<<<SQL
        select ov.value
        ,      ov.weight
        ,      ov.label
        ,      'first_contact_with_pum'as `group`
        from   civicrm_option_value ov
        join   civicrm_option_group og on (og.id = ov.option_group_id)
        where  og.name= 'first_contact_with_pum_via_20141103154142' and ov.is_active=1
SQL;
        set_time_limit(0);
        $rest = new CRM_CMS_Rest();
        $remote = $rest->getAll('OptionValue');
        $remoteOptionValues = [];
        foreach ($remote['Items'] as $key => $item) {
            $remoteOptionValues[$item['Item']['value']] = $item['Item']['Id'];
        }
        $dao = CRM_Core_DAO::executeQuery($sql);
        while ($dao->fetch()) {
            $result = [
                'value' => $dao->value,
                'weight' => $dao->weight,
                'label'  => $dao->label,
                'group'  => $dao->group,
            ];
            if(key_exists($dao->value,$remoteOptionValues)){
                $rest->update('OptionValue',$remoteOptionValues[$dao->value],$result);
                unset($remoteOptionValues[$dao->sector_id]);
            } else {
                $rest->create('OptionValue',$result);
            };
        }
    }
}
