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
function postcodenl_civicrm_navigationMenu( &$params ) {
  $maxKey = _postcodenl_getMenuKeyMax($params);

  $parent =_postcodenl_get_parent_id_navigation_menu($params, 'Administer');

  $parent['child'][$maxKey+1] = array (
    'attributes' => array (
      "label"=> ts('Import postcode from pro6pp'),
      "name"=> ts('Import postcode from pro6pp'),
      "url"=> "civicrm/admin/import/pro6pp",
      "permission" => "administer CiviCRM",
      "parentID" => $parent['attributes']['navID'],
      "active" => 1,
      //"navID" => $maxKey + 1,
    ),
    'child' => array(),
  );

  $contactParent =_postcodenl_get_parent_id_navigation_menu($params, 'Contacts');
  $contactParent['child'][$maxKey+2] = array (
    'attributes' => array (
      "label"=> ts('Update addresses'),
      "name"=> ts('Update addresses'),
      "url"=> "civicrm/contact/updateaddresses",
      "permission" => "administer CiviCRM",
      "parentID" => $contactParent['attributes']['navID'],
      "active" => 1,
      //"navID" => $maxKey + 1,
    ),
    'child' => array(),
  );
}

function _postcodenl_get_parent_id_navigation_menu(&$menu, $path, &$parent = NULL) {
  // If we are done going down the path, insert menu
  if (empty($path)) {
    return $parent;
  } else {
    // Find an recurse into the next level down
    $found = false;
    $path = explode('/', $path);
    $first = array_shift($path);
    foreach ($menu as $key => &$entry) {
      if ($entry['attributes']['name'] == $first) {
        if (!$entry['child']) $entry['child'] = array();
        $found = _postcodenl_get_parent_id_navigation_menu($entry['child'], implode('/', $path), $entry);
      }
    }
    return $found;
  }
}

function _postcodenl_getMenuKeyMax($menuArray) {
  $max = array(max(array_keys($menuArray)));
  foreach($menuArray as $v) {
    if (!empty($v['child'])) {
      $max[] = _postcodenl_getMenuKeyMax($v['child']);
    }
  }
  return max($max);
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
      'title' => ts('Update address from newest Postcode database'),
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