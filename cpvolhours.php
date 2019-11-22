<?php

require_once 'cpvolhours.civix.php';
use CRM_Cpvolhours_ExtensionUtil as E;

/**
 * Implements hook_civicrm_pageRun().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_pageRun/
 */
function cpvolhours_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  if (
    $pageName == 'CRM_Admin_Page_Options'
    && $page::$_gName == 'cpvolhours_hours_per_role'
    && CRM_Core_BAO_CustomField::getCustomFieldId('Service_Type', 'Volunteer_details')
  ) {
    // Add JS to manipulate the page.
    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.cpvolhours', 'js/CRM_Admin_Page_Options.js');
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm/
 */
function cpvolhours_civicrm_buildForm($formName, &$form) {
  if (
    $formName == 'CRM_Admin_Form_Options'
    && $form->getVar('_gName') == 'cpvolhours_hours_per_role'
    && (
      $form->_action == CRM_Core_Action::BROWSE
      || $form->_action == CRM_Core_Action::ADD
      || $form->_action == CRM_Core_Action::UPDATE
    )
  ) {

    $customFieldId = CRM_Core_BAO_CustomField::getCustomFieldId('Service_Type', 'Volunteer_details');
    if (!$customFieldId) {
      // If the "Service Type" custom field doesn't exist, we should quit.
      return;
    }

    // Replace the 'description' textarea with a 'description' text input,
    // labeled 'Hours', and required,
    $form->removeElement('description');
    $form->add('text', 'description', E::ts('Hours'), array(), TRUE);

    // Build the list of options for '"Service Type" value field.
    // Start with all available options in the 'Service Type' option group.
    $optionGroupId = civicrm_api3('customField', 'getvalue', array(
      'id' => $customFieldId,
      'return' => 'option_group_id',
    ));
    $serviceTypeOptions = CRM_Core_BAO_OptionValue::getOptionValuesAssocArray($optionGroupId);
    // Note all used options, so we can remove them from the select list.
    $usedServiceTypeOptions = CRM_Core_BAO_OptionValue::getOptionValuesAssocArray($form->_gid);
    // But we don't want to remove the current value from the select list, so
    // deleted it from the "used Options" set.
    unset($usedServiceTypeOptions[$form->_defaultValues['value']]);
    // Now remoe all used Service Types, so we can't double-select them.
    $unusedServiceTypeOptions = array_diff_key($serviceTypeOptions, $usedServiceTypeOptions);
    // Pass all values to the form in JS vars.
    $vars = array(
      'serviceTypeOptions' => $unusedServiceTypeOptions,
    );

    // Add js and css.
    CRM_Core_Resources::singleton()->addVars('cpvolhours', $vars);
    CRM_Core_Resources::singleton()->addStyleFile('com.joineryhq.cpvolhours', 'css/CRM_Admin_Form_Options.css');
    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.cpvolhours', 'js/CRM_Admin_Form_Options.js');

    // Modify some elements directly.
    $form->getElement('value')->setLabel('"Service Type" value');
    $form->getElement('is_active')->freeze();
    $form->getElement('weight')->freeze();

  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/ 
 */
function cpvolhours_civicrm_config(&$config) {
  _cpvolhours_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function cpvolhours_civicrm_xmlMenu(&$files) {
  _cpvolhours_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function cpvolhours_civicrm_install() {
  _cpvolhours_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function cpvolhours_civicrm_postInstall() {
  _cpvolhours_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function cpvolhours_civicrm_uninstall() {
  _cpvolhours_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function cpvolhours_civicrm_enable() {
  _cpvolhours_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function cpvolhours_civicrm_disable() {
  _cpvolhours_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function cpvolhours_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _cpvolhours_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function cpvolhours_civicrm_managed(&$entities) {
  _cpvolhours_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function cpvolhours_civicrm_caseTypes(&$caseTypes) {
  _cpvolhours_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function cpvolhours_civicrm_angularModules(&$angularModules) {
  _cpvolhours_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function cpvolhours_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _cpvolhours_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function cpvolhours_civicrm_entityTypes(&$entityTypes) {
  _cpvolhours_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function cpvolhours_civicrm_themes(&$themes) {
  _cpvolhours_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *
function cpvolhours_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
function cpvolhours_civicrm_navigationMenu(&$menu) {
  _cpvolhours_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _cpvolhours_civix_navigationMenu($menu);
} // */
