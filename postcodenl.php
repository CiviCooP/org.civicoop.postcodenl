<?php

require_once 'postcodenl.civix.php';

function postcodenl_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  if ((strtolower($entity) == strtolower('postcode_n_L') || strtolower($entity) == strtolower('PostcodeNL')) && $action == 'get') {
    $params['check_permissions'] = false; //allow everyone to use the postcode api
  }
}

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function postcodenl_civicrm_config(&$config) {
  _postcodenl_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function postcodenl_civicrm_xmlMenu(&$files) {
  _postcodenl_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function postcodenl_civicrm_install() {
  return _postcodenl_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function postcodenl_civicrm_uninstall() {
  return _postcodenl_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function postcodenl_civicrm_enable() {
  return _postcodenl_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function postcodenl_civicrm_disable() {
  return _postcodenl_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function postcodenl_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _postcodenl_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function postcodenl_civicrm_managed(&$entities) {
  return _postcodenl_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function postcodenl_civicrm_caseTypes(&$caseTypes) {
  _postcodenl_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function postcodenl_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _postcodenl_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_navigationMenu
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function postcodenl_civicrm_navigationMenu( &$menu ) {
  _postcodenl_civix_insert_navigation_menu($menu, 'Administer', array(
    "label"=> ts('Import postcode from pro6pp'),
    "name"=> ts('Import postcode from pro6pp'),
    "url"=> "civicrm/admin/import/pro6pp",
    "permission" => "administer CiviCRM",
    'operator' => 'OR',
    'separator' => 0
  ));
  _postcodenl_civix_insert_navigation_menu($menu, 'Contacts', array(
    "label"=> ts('Update addresses'),
    "name"=> ts('Update addresses'),
    "url"=> "civicrm/contact/updateaddresses",
    "permission" => "administer CiviCRM",
    'operator' => 'OR',
    'separator' => 0
  ));
  _postcodenl_civix_navigationMenu($menu);
}

/**
 * Implementation hook_civicrm_pre
 *
 * Used to updated the info on gemeneete, buurtnaam, buurtcode, wijkcode
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_pre
 */
function postcodenl_civicrm_pre($op, $objectName, $id, &$params) {
  CRM_Postcodenl_Updater::pre($op, $objectName, $id, $params);
  if (isset($params['country_id']) && $params['country_id'] == 1152) {
    // skip_geocode is an optional parameter through the api.
    // manual_geo_code is on the contact edit form. They do the same thing....
    if (empty($params['skip_geocode']) && empty($params['manual_geo_code'])) {
      CRM_Core_BAO_Address::addGeocoderData($params);
    }
  }
}

/**
 * Implementation hook_civicrm_post
 *
 * Used to updated the info on gemeneete, buurtnaam, buurtcode, wijkcode
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_post
 */
function postcodenl_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  CRM_Postcodenl_Updater::post($op, $objectName, $objectId, $objectRef);
}

function postcodenl_civicrm_searchTasks( $objectName, &$tasks ) {
  if ($objectName == 'contact' && CRM_Core_Permission::check('administer CiviCRM')) {
    $tasks['postcodenl_update_addresses'] = array(
      'title' => ts('Update addresses from Dutch postcode database'),
      'class' => 'CRM_Postcodenl_Task_Update'
    );
  }
}

function postcodenl_civicrm_alterContent(  &$content, $context, $tplName, &$object ) {
  if ($object instanceof CRM_Contact_Form_Inline_Address) {
    $locBlockNo = CRM_Utils_Request::retrieve('locno', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, $_REQUEST);
    $template = CRM_Core_Smarty::singleton();
    $template->assign('blockId', $locBlockNo);
    $content .= $template->fetch('CRM/Contact/Form/Edit/Address/postcodenl_js.tpl');
  }
  if ($object instanceof CRM_Contact_Form_Contact) {
    $template = CRM_Core_Smarty::singleton();
    $content .= $template->fetch('CRM/Contact/Form/Edit/postcodenl_contact_js.tpl');
  }
  if ($object instanceof CRM_Event_Form_ManageEvent_Location || $object instanceof CRM_Event_Form_ManageEvent_EventInfo) {
    $template = CRM_Core_Smarty::singleton();
    $content .= $template->fetch('CRM/Event/Form/ManageEvent/Location_js.tpl');
  }

}

function postcodenl_civicrm_buildForm( $formName, &$form ) {
  if ($formName == 'CRM_Event_Form_ManageEvent_Location') {
    //make sure the right action and parse street address are set
    //action = 2 will show the street_number, street_name and street_unit fields
    //in event location parse street address is not set for template.
    // If this is zero no street_number, street_name or street_unit fields
    // are available and we need those for the postcode database
    $form->assign('action', 2);
    $form->assign('parseStreetAddress', 1);

    //also assign allAddressFieldValues for location block.
    //This will make sure that edit individual fields is available
    $defaultValues = $form->getVar('_defaultValues');
    if ($defaultValues['location_option'] == 2) {
      $loc_id = $form->getVar('_oldLocBlockId');
      $address_id = civicrm_api3('LocBlock', 'getvalue', array('return' => 'address_id', 'id' => $loc_id));
      $address = civicrm_api3('Address', 'getsingle', array('id' => $address_id));
      $allAddressFieldValues = array();
      foreach ($address as $key => $val) {
        $allAddressFieldValues[$key . '_1'] = $val;
      }
      $form->assign('allAddressFieldValues', json_encode($allAddressFieldValues));
    }
  }

  if ($formName == 'CRM_Contact_Form_Contact') {
    CRM_Postcodenl_Updater::storetreetUnitFromFormSubmission($form);
    CRM_Postcodenl_Updater::setStreetAddressOnForm($form);

  }
  if ($formName == 'CRM_Contact_Form_Inline_Address') {
    CRM_Postcodenl_Updater::storetreetUnitFromFormSubmission($form);
    CRM_Postcodenl_Updater::setStreetAddressOnForm($form);
  }

  if ($formName == 'CRM_Contact_Form_Contact' || $formName == 'CRM_Event_Form_ManageEvent_Location' || $formName == 'CRM_Event_Form_ManageEvent_EventInfo') {
    CRM_Core_Resources::singleton()->addScriptFile('org.civicoop.postcodenl', 'postcodenl.js');
  }
}

function postcodenl_civicrm_pageRun( &$page ) {
  if ($page instanceof CRM_Contact_Page_View_Summary) {
    CRM_Core_Resources::singleton()->addScriptFile('org.civicoop.postcodenl', 'postcodenl.js');
  }
}

function postcodenl_civicrm_alterTemplateFile($formName, &$form, $context, &$tplName) {

}
