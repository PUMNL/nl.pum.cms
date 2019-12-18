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

        $this->postal_loc_type_id = civicrm_api3('LocationType', 'getvalue', [
            'return' => 'id',
            'name' => 'Postaladdress',
        ]);

        $this->work_loc_type_id = civicrm_api3('LocationType', 'getvalue', [
            'return' => 'id',
            'name' => 'Work',
        ]);

        $this->website_type_id = $this->findOptionValue('website_type', 'Work');
        $this->facebook_type_id = $this->findOptionValue('website_type', 'Facebook');
        $this->mobile_type_id = $this->findOptionValue('phone_type', 'Mobile');

        $this->genderTable = [
            'male' => 2, 'female' => 1
        ];

        $prefixes = civicrm_api3('OptionValue', 'get', ['option_group_id' => 'individual_prefix']);
        foreach ($prefixes['values'] as $value) {
            $this->prefixTable[$value['label']] = $value['value'];
        }

        $this->skype_custom_id = $this->findCustomFieldId('Skype_Name');
        $this->initials_custom_id = $this->findCustomFieldId('Initials');
        $this->first_contact_custom_id = $this->findCustomFieldId('First_contact_with_PUM_via');

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
    function checkIfProcessed($submissionId, $entity)
    {
        return CRM_Core_DAO::singleValueQuery('select id from pum_cms_submission where submission_id=%1 and entity = %2', [
            1 => [$submissionId, 'Integer'],
            2 => [$entity, 'String']
        ]);
    }

    /**
     * @param $submissionId
     * @param $entity
     */
    function setProcessed($submissionId, $entity)
    {
        CRM_Core_DAO::executeQuery('insert into pum_cms_submission(entity,submission_id) values (%1,%2)', [
            2 => [$submissionId, 'Integer'],
            1 => [$entity, 'String']
        ]);
    }


    /**
     *
     */
    function process()
    {
        $rest = new CRM_CMS_Rest();

        $entity = 'NewsLetterSubscription';
        $result = $rest->getAll($entity);
        foreach ($result['Items'] as $item) {
            if ($this->checkIfProcessed($item['Item']['Id'], $entity)) {
                // do nothing already processed
            } else {
                $this->processNewsLetterSubscription($item['Item']);
                $this->setProcessed($item['Item']['Id'], $entity);
            };
        }

        $entity = 'ClientRegistration';
        $result = $rest->getAll($entity);
        foreach ($result['Items'] as $item) {
            $this->processClientRegistration($item['Item']);
            if ($this->checkIfProcessed($item['Item']['Id'], $entity)) {
                // do nothing already processed
            } else {

                $this->setProcessed($item['Item']['Id'], $entity);
            };
        }

        $entity = 'ExpertApplication';
        $result = $rest->getAll($entity);
        foreach ($result['Items'] as $item) {

            if ($this->checkIfProcessed($item['Item']['Id'], $entity)) {
                // do nothing already processed
            } else {
                $this->processExpertApplication($item['Item']);
                $this->setProcessed($item['Item']['Id'], $entity);
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

        $config = CRM_Core_Config::singleton();
        $customUploadDir = $config->customFileUploadDir;
        $rest = new CRM_CMS_Rest();

        $apiParams = [
            'first_name' => $application['first_name'],
            'last_name' => $application['last_name'],
            'contact_type' => 'Individual',
            'contact_sub_type' => 'Expert',
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

        $uuid = uniqid();
        $rest->getBlob("/api/v1/ExpertApplication/{$application['Id']}/photo", $customUploadDir . "photo_{$uuid}.jpg");
        $apiParams['image_URL'] = CRM_Utils_System::url('civicrm/contact/imagefile', ['photo' => "photo_{$uuid}.jpg"], true);

        $result = civicrm_api3('Contact', 'create', $apiParams);

    }

    /**
     * @param $contactId
     * @throws CiviCRM_API3_Exception
     */
    function createExpertCase($contactId)
    {
        $result = civicrm_api3('Case', 'create', [
            'contact_id' => $contactId,
            'case_type_id' => $this->case_type_id,
            'status_id' => $this->case_status_id,
            'subject' => "Case voor Klaas",
            'creator_id' => $contactId,
            'medium_id' => $this->medium_id,
        ]);
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
            'source' => 'New Customer - form drupal CMS'
        ];

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
                'email' => $registration['phone'],
                'is_primary' => 0
            ];
            civicrm_api3('Email', 'create', $apiParams);
        }

        if (isset($registration['email'])) {
            $apiParams = [
                'contact_id' => $organizationId,
                'location_type_id' => $this->work_loc_type_id,
                'email' => $registration['phone'],
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

        if (isset($registration['skype_name'])) {
            $apiParams['custom_' . $this->skype_custom_id] = $registration['skype_name'];
        }

        if (isset($registration['contact_phone'])) {
            $apiParams['phone'] = $registration['contact_phone'];
        }

        if (isset($registration['contact_email'])) {
            $apiParams['email'] = $registration['contact_email'];
        }

        $result = civicrm_api3('Contact', 'create', $apiParams);
        $contactId = $result['id'];

        // create the authorized contact relation

        civicrm_api3('Relationship', 'create', [
            'contact_id_a' => $organizationId,
            'contact_id_b' => $contactId,
            'relationship_type_id' => $this->relationship_type_id,
        ]);
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
     * @param $customFieldName
     * @return array
     * @throws CiviCRM_API3_Exception
     */
    public function findCustomFieldId($customFieldName)
    {
        $customFieldId = civicrm_api3('CustomField', 'getvalue', [
            'return' => 'id',
            'name' => $customFieldName,
        ]);
        return $customFieldId;
    }

}
