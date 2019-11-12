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
    $result = [];
    $dao = CRM_Core_DAO::executeQuery($sql,[
       1 => [$config->getRepresepentativeRelationshipTypeId(),'Integer']
      ]
      );
    while($dao->fetch()){
      $result[] = [
        'contact_id' => $dao->contact_id,
        'display_name'   => $dao->display_name,
        'sort_name'       => $dao->sort_name,
        'email'           => $dao->email,
        'phone'           => $dao->phone,
        'city'            => $dao->city,
      ];
    }
    return $result;
  }

  public function countries()
  {
    $dao = CRM_Core_DAO::executeQuery('select id, iso_code, name from civicrm_country');
    $rest = new CRM_CMS_Rest();
    while($dao->fetch()){
      $result = [
         //'country_id' => $dao->id,
         'iso_code'   => $dao->iso_code,
         'name'       => $dao->name,
      ];
      $rest->create('Country',$result);
    }
  }

  public function sectors()
  {
    $result = [];
    $dao = CRM_Core_DAO::executeQuery('select id, label from civicrm_segment where is_active=1');
    while($dao->fetch()){
      $result =  [
        'segment_id' => $dao->id,
        'name'       => $dao->label,
      ];
    }
    return $result;
  }
}
