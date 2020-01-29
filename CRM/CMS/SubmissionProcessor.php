<?php

/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 16-Oct-2019
 * @license AGPL-3.0
 */
class CRM_CMS_SubmissionProcessor
{

    var $newsLetterGroupId;
    var $config;
    var $postal_loc_type_id;
    var $work_loc_type_id;
    var $main_loc_type_id;
    var $website_type_id;
    var $facebook_type_id;
    var $mobile_type_id;
    var $genderTable = [];
    var $prefixTable = [];
    var $skype_custom_id;
    var $initials_custom_id;
    var $first_contact_custom_id;
    var $relationship_type_id;
    var $case_type_id;
    var $case_status_id;
    var $medium_id;
    var $motivation_custom_id;
    var $agreement_custom_id;

    /**
     * CRM_CMS_SubmissionProcessor constructor.
     */
    public function __construct()
    {
        $this->newsLetterGroupId = civicrm_api3('Group', 'getvalue', [
                'name' => 'Newsletter_partners_38',
                'return' => 'id'
            ]
        );

        $this->postal_loc_type_id = $this->findLocationTypeId('Postaladdress');
        $this->work_loc_type_id = $this->findLocationTypeId('Work');
        $this->main_loc_type_id = $this->findLocationTypeId('Main');
        $this->website_type_id = $this->findOptionValue('website_type', 'Work');
        $this->facebook_type_id = $this->findOptionValue('website_type', 'Facebook');
        $this->mobile_type_id = $this->findOptionValue('phone_type', 'Mobile');

        $this->genderTable = [
            'male' => 2, 'female' => 1
        ];

        $this->agreementTable = [
            'Y' => 'Yes', 'N' => 'No'
        ];

        $prefixes = civicrm_api3('OptionValue', 'get', ['option_group_id' => 'individual_prefix']);
        foreach ($prefixes['values'] as $value) {
            $this->prefixTable[$value['label']] = $value['value'];
        }

        $this->skype_custom_id = $this->findCustomFieldId('Skype_Name');
        $this->initials_custom_id = $this->findCustomFieldId('Initials');
        $this->first_contact_custom_id = $this->findCustomFieldId('First_contact_with_PUM_via');
        $this->motivation_custom_id = $this->findCustomFieldId('motivation_expert_application');
        $this->agreement_custom_id  = $this->findCustomFieldId('Gentlemen_s_Agreement');

        $this->relationship_type_id = civicrm_api3('RelationshipType', 'getvalue', [
            'return' => 'id',
            'name_a_b' => 'Has authorised',
        ]);

        $this->case_type_id = $this->findOptionValue('case_type', 'Expertapplication');
        $this->case_status_id = $this->findOptionValue('case_status', 'Assess CV');
        $this->medium_id = $this->findOptionValue('encounter_medium', 'Webform');

    }

    /**
     * @param $dateString
     * @return string
     */
    function formatDate($dateString)
    {
        return substr($dateString, 0, 4) . substr($dateString, 5, 2) . substr($dateString, 8, 2);
    }

    /**
     * @param $submissionId
     * @param $entity
     * @return string
     */
    function checkIfProcessed($submission, $entity)
    {
        $sql = <<< SQL
        select id from pum_cms_submission 
        where submission_id=%1 and entity = %2 and state in ('P','D','F')
SQL;
        $submissionId = $submission['Id'];
        $submitted = CRM_Core_DAO::singleValueQuery($sql, [
            1 => [$submissionId, 'Integer'],
            2 => [$entity, 'String']
        ]);
        if($submitted){
            return $submissionId;
        } else {
            CRM_Core_DAO::executeQuery('insert into pum_cms_submission(entity,submission_id,state,submission) values (%1,%2,%3,%4)', [
                2 => [$submissionId, 'Integer'],
                1 => [$entity, 'String'],
                3 => ['P','String'],
                4 => [json_encode($submission,JSON_PRETTY_PRINT),'String'],
            ]);
            return FALSE;
        }
    }

    /**
     * @param integer $submissionId
     * @param string $entity
     */
    function setProcessed($submissionId, $entity)
    {
        $sql = <<< SQL
        update pum_cms_submission set state='P' 
        where  entity =%1
        and    submission_id = %2
SQL;
        CRM_Core_DAO::executeQuery($sql, [
            2 => [$submissionId, 'Integer'],
            1 => [$entity, 'String']
        ]);
    }

    /**
     * @param integer $submissionId
     * @param string $entity
     */
    function setFailed($submissionId, $entity,$failure)
    {
        $sql = <<< SQL
        update pum_cms_submission 
        set    state='F' 
        ,      failure=%3
        where  entity =%2
        and    submission_id = %1
SQL;
        CRM_Core_DAO::executeQuery($sql, [
            1 => [$submissionId, 'Integer'],
            2 => [$entity, 'String'],
            3 => [$failure, 'String']
        ]);
    }

    /**
     * workhorse of the processing
     */
    function process()
    {
        $rest = new CRM_CMS_Rest();
        $entity = 'NewsLetterSubscription';
        $result = $rest->getAll($entity);
        foreach ($result['Items'] as $item) {

            if ($this->checkIfProcessed($item['Item'], $entity)) {
                // do nothing already processed
            } else {
                try {
                    $this->processNewsLetterSubscription($item['Item']);
                    $this->setProcessed($item['Item']['Id'], $entity);
                } catch (CiviCRM_API3_Exception $e) {
                    $this->setFailed($item['Item']['Id'], $entity, $e);
                }
            };
        }

        $entity = 'ClientRegistration';
        $result = $rest->getAll($entity);
        foreach ($result['Items'] as $item) {
            if ($this->checkIfProcessed($item['Item'], $entity)) {
                // do nothing already processed
            } else {
                try {
                    $this->processClientRegistration($item['Item']);
                    $this->setProcessed($item['Item']['Id'], $entity);
                } catch (CiviCRM_API3_Exception $e) {
                    $this->setFailed($item['Item']['Id'], $entity, $e);
                }
            };
        }

        $entity = 'ExpertApplication';
        $result = $rest->getAll($entity);
        foreach ($result['Items'] as $item) {
            if ($this->checkIfProcessed($item['Item'], $entity)) {
                // do nothing already processed
            } else {
                try {
                    $this->processExpertApplication($item['Item']);
                    $this->setProcessed($item['Item']['Id'], $entity);
                } catch (CiviCRM_API3_Exception $e) {
                    $this->setFailed($item['Item']['Id'], $entity, $e);
                }
            };
        }
    }

    /**
     * Process one NewsLetter subscription
     * @param array $subscription
     * @throws CiviCRM_API3_Exception
     */
    function processNewsLetterSubscription($subscription)
    {
        $apiParams = [
            'first_name' => $subscription['first_name'],
            'middle_name' => $subscription['middle_name'],
            'last_name' => $subscription['last_name'],
            'contact_type' => 'Individual',
            'email' => $subscription['email'],
        ];
        $result = civicrm_api3('Contact', 'create', $apiParams);
        $contactId = $result['id'];
        civicrm_api3('GroupContact', 'create', [
            'contact_id' => $contactId,
            'group_id' => $this->newsLetterGroupId
        ]);
    }

    /**
     *  Process one exxpert application
     *
     * @param $application
     * @throws CiviCRM_API3_Exception
     */
    function processExpertApplication($application)
    {
        $rest = new CRM_CMS_Rest();
        if(isset($application['home_address'])){
            $application['home_address'] = json_decode($application['home_address'],true);
        };

        $apiParams = [
            'first_name' => $application['first_name'],
            'last_name' => $application['last_name'],
            'middle_name' => $application['middle_name'],
            'contact_type' => 'Individual',
            'contact_sub_type' => 'Expert',
            'gender_id' => $this->genderTable[$application['gender']],
            'source' => 'New Customer - form drupal CMS'
        ];

        if (isset($application['initials'])) {
            $apiParams['custom_' . $this->initials_custom_id] = $application['initials'];
        }

        if (isset($application['first_contact'])) {
            $apiParams['custom_' . $this->first_contact_custom_id] = $application['first_contact'];
        }

        if (isset($application['birth_date'])) {
            $apiParams['birth_date'] = $this->formatDate($application['birth_date']);
        }

        if (isset($application['name_prefix']) && key_exists($application['name_prefix'], $this->prefixTable)) {
            $apiParams['prefix_id'] = $this->prefixTable[$application['name_prefix']];
        }

        if (isset($application['email'])) {
            $apiParams['email'] = $application['email'];
        }

        if (isset($application['phone'])) {
            $apiParams['phone'] = $application['phone'];
        }

        $result = civicrm_api3('Contact', 'create', $apiParams);

        $contactId = $result['id'];
        if (isset($application['home_address'])) {
            $apiParams = [
                'contact_id' => $contactId,
                'location_type_id' => $this->work_loc_type_id,
                'is_primary' => 1,
                'street_address' => $application['home_address']['street_address'],
                'postal_code' => $application['home_address']['postal_code'],
                'city' => $application['home_address']['city'],
                'country_id' => $application['home_address']['country_id']
            ];
            civicrm_api3('Address', 'create', $apiParams);
        }

        if(isset($application['sector_id'])){
            $this->addSector($contactId,$application['sector_id']);
        }

        $caseId = $this->createExpertCase($contactId,$application['motivation']);

        $uuid = uniqid('',true);
        $photoName = "photo_{$uuid}.jpg";

        $rest->getBlob("/api/v1/ExpertApplication/{$application['Id']}/photo", file_directory_temp()."/{$photoName}",true);
        $this->uploadDocument($contactId,$caseId,file_directory_temp()."/{$photoName}",'Photo');
        if(isset($application['cv'])){
            $ext = pathinfo($application['cv'], PATHINFO_EXTENSION);
            $name = pathinfo($application['cv'], PATHINFO_BASENAME);
            $name = substr($name,0,strlen($name)-strlen($ext)-1);
            $cvFilename=file_directory_temp()."/cv_{$name}_{$uuid}.$ext";
            $rest->getBlob("/api/v1/ExpertApplication/{$application['Id']}/cvDownload","$cvFilename",false);
            $this->uploadDocument($contactId,$caseId,$cvFilename,'Curriculum Vitae');
        }
        civicrm_api3('Contact','create',[
          'id' => $contactId,
          'image_URL' => CRM_Utils_System::url('civicrm/contact/imagefile', ['photo' => $photoName], true)
        ]);
    }

    /**
     * @param $contactId
     * @return integer the id of the case
     * @throws CiviCRM_API3_Exception
     */
    function createExpertCase($contactId,$motivation)
    {
        $displayName = civicrm_api3('Contact','getvalue',[
            'id' => $contactId,
            'return' => 'display_name',
        ]);

        $result = civicrm_api3('Case', 'create', [
            'contact_id' => $contactId,
            'subject' => "{$displayName}-Expertapplication",
            'case_type_id' => $this->case_type_id,
            'status_id' => $this->case_status_id,
            'creator_id' => $contactId,
            'medium_id' => $this->medium_id,
        ]);
        $caseId = $result['id'];

        civicrm_api3('Case','create',[
            'id'=>$caseId,
            'subject' => "{$displayName}-Expertapplication-{$caseId}",
        ]);
        CRM_Core_DAO::executeQuery('insert into civicrm_value_motivation_expert(entity_id,motivation) values (%1,%2)',[
            1 => [$caseId,'Integer'],
            2 => [$motivation,'String']
        ]);
        return $caseId;
    }

    /**
     *
     *  process one Client Registration
     *
     * @param $registration
     * @throws CiviCRM_API3_Exception
     */
    function processClientRegistration($registration)
    {
        if(isset($registration['visit_address'])){
            $registration['visit_address'] = json_decode($registration['visit_address'],true);
        };
        if(isset($registration['postal_address'])){
            $registration['postal_address'] = json_decode($registration['postal_address'],true);
        };

        $apiParams = [
            'organization_name' => $registration['organization_name'],
            'contact_type' => 'Organization',
            'contact_sub_type' => 'Customer',
            'source' => 'New Customer - form drupal CMS',
            'custom_'.$this->agreement_custom_id => $this->agreementTable[$registration['agreement_terms_and_conditions']],
        ];

        if($registration['agreement_terms_and_conditions']==='Y'){
            $apiParams['custom_'.$this->agreement_custom_id] ='I Agree';
        }

        $result = civicrm_api3('Contact', 'create', $apiParams);

        $organizationId = $result['id'];

        if (isset($registration['visit_address'])) {
            $apiParams = [
                'contact_id' => $organizationId,
                'location_type_id' => $this->work_loc_type_id,
                'is_primary' => 1,
                'street_address' => $registration['visit_address']['street_address'],
                'postal_code' => $registration['visit_address']['postal_code'],
                'city' => $registration['visit_address']['city'],
                'country_id' => $registration['visit_address']['country_id']
            ];
            civicrm_api3('Address', 'create', $apiParams);
        }

        if (isset($registration['postal_address'])) {
            $apiParams = [
                'contact_id' => $organizationId,
                'location_type_id' => $this->postal_loc_type_id,
                'is_primary' => 1,
                'street_address' => $registration['postal_address']['street_address'],
                'postal_code' => $registration['postal_address']['postal_code'],
                'city' => $registration['postal_address']['city'],
                'country_id' => $registration['postal_address']['country_id']
            ];
            civicrm_api3('Address', 'create', $apiParams);
        }

        if (isset($registration['phone'])) {
            $apiParams = [
                'contact_id' => $organizationId,
                'location_type_id' => $this->work_loc_type_id,
                'phone' => $registration['phone'],
                'is_primary' => 1
            ];
            civicrm_api3('Phone', 'create', $apiParams);
        }

        if (isset($registration['phone_2'])) {
            $apiParams = [
                'contact_id' => $organizationId,
                'location_type_id' => $this->work_loc_type_id,
                'phone_type_id' => $this->mobile_type_id,
                'phone' => $registration['phone_2'],
                'is_primary' => 0
            ];
            civicrm_api3('Phone', 'create', $apiParams);
        }

        if (isset($registration['email'])) {
            $apiParams = [
                'contact_id' => $organizationId,
                'location_type_id' => $this->work_loc_type_id,
                'email' => $registration['email'],
                'is_primary' => 0
            ];
            civicrm_api3('Email', 'create', $apiParams);
        }

        if (isset($registration['website'])) {
            $apiParams = [
                'contact_id' => $organizationId,
                'url' => $registration['website'],
                'website_type_id' => $this->website_type_id
            ];
            civicrm_api3('Website', 'create', $apiParams);
        }

        if (isset($registration['facebook'])) {
            $apiParams = [
                'contact_id' => $organizationId,
                'url' => $registration['facebook'],
                'website_type_id' => $this->facebook_type_id
            ];
            civicrm_api3('Website', 'create', $apiParams);
        }

        $apiParams = [
            'first_name' => $registration['first_name'],
            'last_name' => $registration['last_name'],
            'gender_id' => $this->genderTable[$registration['gender']],
            'contact_type' => 'Individual',
            'job_title' => $registration['job_title'],
            'source' => 'New Customer - form drupal CMS'
        ];

        // add initials if they are part of the registration
        if (isset($registration['initials'])) {
            $apiParams['custom_' . $this->initials_custom_id] = $registration['initials'];
        }

        if (isset($registration['skype_name'])) {
            $apiParams['custom_' . $this->skype_custom_id] = $registration['skype_name'];
        }

        $result = civicrm_api3('Contact', 'create', $apiParams);
        $contactId = $result['id'];

        // create the authorized contact relation

        if (isset($registration['contact_phone'])) {
            civicrm_api3('Phone', 'create', [
                'contact_id' => $contactId,
                'location_type_id' => $this->main_loc_type_id,
                'phone' => $registration['contact_phone'],
            ]);
        }

        if (isset($registration['contact_email'])) {
            civicrm_api3('Email', 'create', [
                'contact_id' => $contactId,
                'location_type_id' => $this->home_loc_type_id,
                'email' => $registration['contact_email'],
            ]);
        }

        $result = civicrm_api3('Relationship', 'create', [
            'contact_id_a' => $organizationId,
            'contact_id_b' => $contactId,
            'relationship_type_id' => $this->relationship_type_id,
        ]);

        $config = CRM_Newcustomer_Config::singleton();
        $result = civicrm_api3('Relationship', 'create', [
            'contact_id_a' => $organizationId,
            'contact_id_b' => $registration['representative_id'],
            'relationship_type_id' =>  $config->getRepresepentativeRelationshipTypeId(),
        ]);

        if($registration['newsletter_subscription']==='Y' or $registration['newsletter_subscription']==='true' ){
            civicrm_api3('GroupContact', 'create', [
                'contact_id' => $contactId,
                'group_id' => $this->newsLetterGroupId
            ]);
        }
    }

    /**
     * @param $groupName
     * @param $optionLabel
     * @return array
     * @throws CiviCRM_API3_Exception
     */
    public function findOptionValue($groupName, $optionLabel)
    {
        $websiteGroupId = civicrm_api3('OptionGroup', 'getvalue', [
            'return' => 'id',
            'name' => $groupName,
        ]);
        $optionFieldValue = civicrm_api3('OptionValue', 'getvalue', [
            'return' => 'value',
            'name' => $optionLabel,
            'option_group_id' => $websiteGroupId,
        ]);
        if (!$optionFieldValue) {
            throw new Exception("{$optionLabel} in {$groupName} not found");
        }
        return $optionFieldValue;
    }

    /**
     * @param string $customFieldName
     * @return integer
     * @throws CiviCRM_API3_Exception
     */
    public function findCustomFieldId($customFieldName)
    {
        return civicrm_api3('CustomField', 'getvalue', [
            'return' => 'id',
            'name' => $customFieldName,
        ]);
    }

    /**
     * @param integer $contactId
     * @param integer $sectorId
     * @throws CiviCRM_API3_Exception
     */
    public function addSector($contactId, $sectorId){
        civicrm_api3('ContactSegment','create',[
            'contact_id' => $contactId,
            'segment_id' => $sectorId,
            'role_value' => 'Expert',
            'is_main' => 1,
            'is_active' => 1,
            'start_date' => date('Ymd'),
        ]);
    }

    /**
     * @param string $locationName
     * @return array
     * @throws CiviCRM_API3_Exception
     */
    public function findLocationTypeId($locationName)
    {
        $civicrm_api3 = civicrm_api3('LocationType', 'getvalue', [
            'return' => 'id',
            'name' => $locationName,
        ]);
        return $civicrm_api3;
    }

    public function uploadDocument($contactId, $caseId, $file,$subject)
    {
        $documentsRepo = CRM_Documents_Entity_DocumentRepository::singleton();
        $document = new CRM_Documents_Entity_Document();
        $document->addCaseId($caseId);
        $document->addContactId($contactId);
        $document->setSubject($subject);
        $version = $document->addNewVersion();
        $version->setDescription("$subject v1");
        $documentsRepo->persist($document);
        CRM_Documents_Utils_File::copyFileToDocument($file, mime_content_type($file), $document);
    }

    public function reportFailures(){
        $failures = [];
        $dao = CRM_Core_DAO::executeQuery('select entity,submission, failure from pum_cms_submission where state = %1',[
              1 => ['F','String']
            ]
        );
        while($dao->fetch()){
            $failures [] = [
                'entity' => $dao->entity,
                'submission' => $dao->submission,
                'failure' => $dao->failure,
            ];
        }
        return $failures;
    }

}
