<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 16-Oct-2019
 * @license AGPL-3.0
 */
class CRM_CMS_SubmissionProcessor {

  var $newsLetterGroupId;
  var $config;
  var $postal_loc_type_id;
  var $work_loc_type_id;
  var $website_type_id;
  var $facebook_type_id;
  var $mobile_type_id;
  var $genderTable;
  var $skype_custom_id;

  /**
   * CRM_CMS_SubmissionProcessor constructor.
   */
  public function __construct() {
    $this->newsLetterGroupId = civicrm_api3('Group','getvalue',[
         'name' => 'Newsletter_partners_38',
         'return' => 'id'
      ]
    );
    $this->postal_loc_type_id = civicrm_api3('LocationType', 'getvalue', [
      'return' => 'id',
      'name' => 'Postaladdress',
    ]);
    $this->work_loc_type_id = civicrm_api3('LocationType', 'getvalue', [
      'return' => 'id',
      'name' => 'Work',
    ]);

    $websiteGroupId = civicrm_api3('OptionGroup','getvalue',[
      'return' => 'id',
      'name'   => 'website_type',
    ]);

    $this->website_type_id = civicrm_api3('OptionValue','getvalue',[
      'return' => 'value',
      'name' => 'Work',
      'option_group_id'   => $websiteGroupId,
    ]);

    $this->facebook_type_id = civicrm_api3('OptionValue','getvalue',[
      'return' => 'value',
      'name' => 'Facebook',
      'option_group_id'   => $websiteGroupId,
    ]);

    $phoneGroupId = civicrm_api3('OptionGroup','getvalue',[
      'return' => 'id',
      'name'   => 'phone_type',
    ]);

    $this->mobile_type_id = civicrm_api3('OptionValue','getvalue',[
      'return' => 'value',
      'name' => 'Mobile',
      'option_group_id'   => $phoneGroupId,
    ]);

    $this->genderTable = [
      'male'=>2,'female'=>1
    ];

    $this->skype_custom_id = civicrm_api3('CustomField','getvalue',[
      'return' => 'id',
      'name'   => 'Skype_Name',
    ]);

    $this->config = CRM_Newcustomer_Config::singleton();
  }

    function checkIfProcessed($submissionId, $entity)
    {
        return CRM_Core_DAO::singleValueQuery('select id from pum_cms_submission where submission_id=%1 and entity = %2', [
            1 => [$submissionId, 'Integer'],
            2 => [$entity, 'String']
        ]);
    }

    function setProcessed($submissionId, $entity)
    {
        CRM_Core_DAO::executeQuery('insert into pum_cms_submission(entity,submission_id) values (%1,%2)', [
            2 => [$submissionId, 'Integer'],
            1 => [$entity, 'String']
        ]);
    }


    function process()
    {
        $entity = 'NewsletterSubscription';
        $rest = new CRM_CMS_Rest();
        $result = $rest->getAll($entity);
        foreach ($result['Items'] as $item) {
            if ($this->checkIfProcessed($item['Item']['Id'], $entity)) {
                // do nothing already processed
            } else {
                $this->processNewsLetterSubscription($item['Item']);
                $this->setProcessed($item['Item']['Id'], $entity);
             };
        }
    }

  function processNewsLetterSubscription($subscription){
      $apiParams = [
        'first_name' => $subscription['first_name'],
        'middle_name' => $subscription['middle_name'],
        'last_name'   => $subscription['last_name'],
        'contact_type' => 'Individual',
        'email' => $subscription['email'],
      ];
      $result = civicrm_api3('Contact','create',$apiParams);
      $contactId = $result['id'];
      civicrm_api3('GroupContact','create',[
         'contact_id' => $contactId,
         'group_id'   => $this->newsLetterGroupId
      ]);
  }

  function processExpertApplication($application){

  }

  /*

   {
  "organization_name": "string",
  "visit_address": {
    "street_address": "string",
    "postal_code": "string",
    "city": "string",
    "country_id": 0
  },
  "postal_address": {
    "street_address": "string",
    "postal_code": "string",
    "city": "string",
    "country_id": 0
  },
  "phone": "string",
  "phone_2": "string",
  "website": "string",
  "facebook": "string",
  "agreement_terms_and_conditions": "Y",
  "representative_id": 0,
  "gender": "female",
  "first_name": "string",
  "last_name": "string",
  "contact_phone": "string",
  "skype_name": "string",
  "contact_email": "string",
  "job_title": "string",
  "newsletter_subscription": "Y"
}

   */

  function processClientRegistration($registration){

    /*
    $apiParams = [
      'organization_name' => $registration['organization_name'],
      'contact_type'      => 'Organization',
      'source'            => 'New Customer - form drupal CMS'
    ];

    $result = civicrm_api3('Contact','create',$apiParams);

    $organizationId = $result['id'];

    if(isset($registration['visit_address'])){
      $apiParams = [
        'contact_id' =>  $organizationId ,
        'location_type_id' => $this->work_loc_type_id,
        'is_primary' => 1,
        'street_address'   => $registration['visit_address']['street_address'],
        'postal_code'      => $registration['visit_address']['postal_code'],
        'city'   => $registration['visit_address']['city'],
        'country_id' => $registration['visit_address']['country_id']
      ];
      civicrm_api3('Address','create',$apiParams);
    }

    if(isset($registration['postal_address'])){
      $apiParams = [
        'contact_id' =>  $organizationId ,
        'location_type_id' => $this->postal_loc_type_id,
        'is_primary' => 1,
        'street_address'   => $registration['postal_address']['street_address'],
        'postal_code'      => $registration['postal_address']['postal_code'],
        'city'   => $registration['postal_address']['city'],
        'country_id' => $registration['postal_address']['country_id']
      ];
      civicrm_api3('Address','create',$apiParams);
    }

    if(isset($registration['phone'])){
      $apiParams = [
        'contact_id' =>  $organizationId ,
        'location_type_id' => $this->work_loc_type_id,
        'phone' => $registration['phone'],
        'is_primary' => 1
        ];
      civicrm_api3('Phone','create',$apiParams);
    }

    if(isset($registration['phone_2'])){
      $apiParams = [
        'contact_id' =>  $organizationId ,
        'location_type_id' => $this->work_loc_type_id,
        'phone_type_id' => $this->mobile_type_id,
        'phone' => $registration['phone_2'],
        'is_primary' => 0
      ];
      civicrm_api3('Phone','create',$apiParams);
    }

    if(isset($registration['email'])){
      $apiParams = [
        'contact_id' =>  $organizationId ,
        'location_type_id' => $this->work_loc_type_id,
        'email' => $registration['phone'],
        'is_primary' => 0
      ];
      civicrm_api3('Email','create',$apiParams);
    }

    if(isset($registration['email'])){
      $apiParams = [
        'contact_id' =>  $organizationId ,
        'location_type_id' => $this->work_loc_type_id,
        'email' => $registration['phone'],
        'is_primary' => 0
      ];
      civicrm_api3('Email','create',$apiParams);
    }

    if(isset($registration['website'])){
      $apiParams = [
        'contact_id' =>  $organizationId ,
        'url' => $registration['website'],
        'website_type_id' => $this->website_type_id
      ];
      civicrm_api3('Website','create',$apiParams);
    }

    if(isset($registration['facebook'])){
      $apiParams = [
        'contact_id' =>  $organizationId ,
        'url' => $registration['facebook'],
        'website_type_id' => $this->facebook_type_id
      ];
      civicrm_api3('Website','create',$apiParams);
    }*/

    $apiParams = [
       'first_name' => $registration['first_name'],
       'last_name' => $registration['last_name'],
       'gender_id' => $this->genderTable[$registration['gender']],
       'contact_type' => 'Individual',
       'job_title' => $registration['job_title'],
    ];

    if(isset($registration['skype_name'])){
      $apiParams['custom_'.$this->skype_custom_id] = $registration['skype_name'];
    }

    if(isset($registration['contact_phone'])){
      $apiParams['phone'] = $registration['contact_phone'];
    }

    if(isset($registration['contact_email'])){
      $apiParams['email'] = $registration['contact_email'];
    }

    $result = civicrm_api3('Contact','create',$apiParams);
    $contactId = $result['id'];
    print_r($contactId);

  }

}
