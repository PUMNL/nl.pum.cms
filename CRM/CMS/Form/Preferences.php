<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 06-Dec-2019
 * @license  AGPL-3.0
 */

class CRM_CMS_Form_Preferences extends CRM_Core_Form {


    private $keys = ['drupal_cms_url', 'drupal_cms_authtoken', 'drupal_cms_contact_id', 'drupal_cms_template_id','drupal_cms_basicauth'];

    private function mailTemplates()
    {
        $options = [];
        $dao = CRM_Core_DAO::executeQuery('select id, msg_title from civicrm_msg_template where is_active=1 and workflow_id is null order by msg_title');
        while ($dao->fetch()) {
            $options[$dao->id] = $dao->msg_title;
        }
        return $options;
    }

    public function buildQuickForm()
    {
        CRM_Utils_System::setTitle(ts('CMS Drupal Services - connectivity configuration'));
        // add form elements
        $this->add(
            'text', // field type
            'drupal_cms_url', // field name
            ts('Remote REST Api URL'), // field label
            ['size' => 64], // attributes
            true, // is required
            null
        );
        $this->add(
            'text', // field type
            'drupal_cms_authtoken', // field name
            ts('Authorization token for the Remote call'), // field label
            ['size' => 64], // attributes
            true, // is required
            null
        );
        $this->add(
            'text', // field type
            'drupal_cms_basicauth', // field name
            ts('Authorization token for basic authentication (Should be obsolete in remote release)'), // field label
            ['size' => 64], // attributes
            true, // is required
            null
        );
        $this->add('text', 'drupal_cms_contact_id', ts('Who gets the exception Mail (Fill in the contact_id)'), [], true, null);
        $this->add(
            'select', // field type
            'drupal_cms_template_id', // field name
            'Message Template of the Error Email', // field label
            $this->mailTemplates(), // list of options
            true, // is required
            []
        );
        $this->addButtons([
            [
                'type' => 'submit',
                'name' => ts('Submit'),
                'isDefault' => TRUE,
            ],
        ]);

        // export form elements
        $this->assign('elementNames', $this->getRenderableElementNames());
        parent::buildQuickForm();
    }

    /**
     * Get the fields/elements defined in this form.
     *
     * @return array (string)
     */
    function getRenderableElementNames() {
        $elementNames = [];
        foreach ($this->_elements as $element) {
            $label = $element->getLabel();
            if (!empty($label)) {
                $elementNames[] = $element->getName();
            }
        }
        return $elementNames;
    }

    function setDefaultValues() {
        parent::setDefaultValues();
        foreach($this->keys as $key) {
            $values[$key] = CRM_Core_BAO_Setting::getItem('Drupal CMS Api', $key);
        }
        return $values;
    }

    function postProcess() {
        $values = $this->exportValues();
        foreach($this->keys as $key)
        {
          CRM_Core_BAO_Setting::setItem($values[$key], 'Drupal CMS Api', $key);
        }
        parent::postProcess();
    }
}
