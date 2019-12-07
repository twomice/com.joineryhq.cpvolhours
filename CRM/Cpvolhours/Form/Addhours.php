<?php

use CRM_Cpvolhours_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Cpvolhours_Form_Addhours extends CRM_Core_Form {
  private $teamCid;
  private $volunteerCids = [];
  private $helpTypeCustomFieldId;
  private $serviceTypeCustomFieldId;

  public function buildQuickForm() {
    $this->teamCid = CRM_Utils_Request::retrieve('cid', 'Int', $this);
    
    // Build the list of options for '"Help Type" value field.n
    $this->helpTypeCustomFieldId = CRM_Core_BAO_CustomField::getCustomFieldId('Help_Type', 'Service_details');
    $helpTypeOptionGroupId = civicrm_api3('customField', 'getvalue', array(
      'id' => $this->helpTypeCustomFieldId,
      'return' => 'option_group_id',
    ));
    $helpTypeOptions = CRM_Core_BAO_OptionValue::getOptionValuesAssocArray($helpTypeOptionGroupId);

    // Build the list of options for '"Service Type" value field.
    // Start with all available options in the 'Service Type' option group.
    $this->serviceTypeCustomFieldId = CRM_Core_BAO_CustomField::getCustomFieldId('Service_Type', 'Volunteer_details');
    $serviceTypeOptionGroupId = civicrm_api3('customField', 'getvalue', array(
      'id' => $this->serviceTypeCustomFieldId,
      'return' => 'option_group_id',
    ));
    $serviceTypeOptions = CRM_Core_BAO_OptionValue::getOptionValuesAssocArray($serviceTypeOptionGroupId);

    // Get configured hours-per-role from optionGroup 'cpvolhours_hours_per_role'
    $serviceHoursOptionValues = civicrm_api3('optionValue', 'get', array(
      'option_group_id' => 'cpvolhours_hours_per_role',
    ));
    $serviceHoursDefaults = array();
    foreach ($serviceHoursOptionValues['values'] as $serviceHoursOptionValue) {
      $serviceHoursDefaults[$serviceHoursOptionValue['label']] = $serviceHoursOptionValue['description'];
    }

    // Get all individuals with current 'team/volunteer' relationships, noting
    //   Service Type for each.
    $relationshipTypeId = civicrm_api3('relationshipType', 'getvalue', array(
      'name_a_b' => 'Has_team_volunteer',
      'return' => 'id',
    ));
    $relationships = civicrm_api3('relationship', 'get', array(
      'relationship_type_id' => $relationshipTypeId,
      'is_active' => 1,
      'contact_id_a' => $this->teamCid,
      'api.Contact.getSingle' => ['id' => "\$value.contact_id_b", 'return' => ["sort_name"]],
      'options' => array(
        'limit' => 0,
      ),
    ));

    // For each relationship, add fields: "hours", "help type"
    // For each relationship, set default value for "hours" field based on Service Type.
    $rows = array();
    $sortRows = array();
    $defaultValues = array();
    foreach ($relationships['values'] as $relationship) {
      $serviceTypeValue = CRM_Utils_Array::value("custom_{$this->serviceTypeCustomFieldId}", $relationship);
      $volunteerCid = $relationship['api.Contact.getSingle']['id'];
      $this->volunteerCids[] = $volunteerCid;
      $row['volunteerCid'] = $volunteerCid;
      $row['sortName'] = $relationship['api.Contact.getSingle']['sort_name'];
      $row['serviceTypeLabel'] = CRM_Utils_Array::value($serviceTypeValue, $serviceTypeOptions);
      $row['hoursElementName'] = "hours_{$volunteerCid}";
      $row['helpTypeElementName'] = "helpType_{$volunteerCid}";
      $this->add(
        'text', // field type
        $row['hoursElementName'] , // field name
        ts('Hours'), // field label
        TRUE // is required
      );
      $this->add(
        'select',
        $row['helpTypeElementName'],
        ts('Help Type'),
        $helpTypeOptions,
        TRUE
      );

      $defaultValues[$row['hoursElementName']] = CRM_Utils_Array::value($serviceTypeValue, $serviceHoursDefaults, 0);
      $defaultValues[$row['helpTypeElementName']] = 'HC';
      $sortRows[] = $row['sortName'];
      $rows[] = $row;

    }
    // Sort rows by sort_name.
    array_multisort($sortRows, $rows);
    $this->assign('rows', $rows);


    // Add "service date" field, defaulting to current date.
    $attributes = array(
      'class' => 'dateplugin' // this css class prevents the datepicker from being autofocused on popup load
    );
    $this->add('datepicker', 'service_date', ts('Service date'), $attributes, TRUE, ['time' => FALSE]);
    $defaultValues['service_date'] = CRM_Utils_Date::getToday();

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ),
    ));

    $this->setDefaults($defaultValues);

    $this->assign('team', civicrm_api3('contact', 'getSingle', array('id' => $this->teamCid)));

    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.cpvolhours', 'js/CRM_Cpvolhours_Form_Addhours.js', 1000, 'page-footer');
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $userCid = CRM_Core_Session::getLoggedInContactID();
    $isLegacyCustomFieldId = CRM_Core_BAO_CustomField::getCustomFieldId('Is_legacy', 'Service_details');
    foreach ($this->volunteerCids as $volunteerCid) {
      $hours = CRM_Utils_Array::value("hours_{$volunteerCid}", $values, 0);
      if (!$hours) {
        // No hours recorded, so skip it.
        continue;
      }
      // If we're still here, create a Service Hours activity.
      $apiParams = array(
        "duration" => (60 * $hours),
        "custom_{$this->helpTypeCustomFieldId}" => $values["helpType_{$volunteerCid}"],
        'source_contact_id' => $userCid,
        'target_id' => $this->teamCid,
        'assignee_id' => $volunteerCid,
        'activity_date_time' => $values['service_date'],
        'activity_type_id' => 'Service hours',
        'subject' => ts('Team service hours (batch entry)'),
        "custom_{$isLegacyCustomFieldId}" => 0,
      );
      $activity = civicrm_api3('activity', 'create', $apiParams);
    }
    parent::postProcess();
  }


  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function zz_getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
