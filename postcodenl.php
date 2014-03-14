<?php

require_once 'postcodenl.civix.php';

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
  $item = array (
    "name"=> ts('Import postcode from pro6pp'),
    "url"=> "civicrm/admin/import/pro6pp",
    "permission" => "administer CiviCRM",
  );
  _postcodenl_civix_insert_navigation_menu($params, "Administer", $item);
}

/**
 * Implementation hook_civicrm_post
 * 
 * Used to updated the info on gemeneete, buurtnaam, buurtcode, wijkcode
 * 
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_post
 */
function postcodenl_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if ($objectName == 'Address' && ($op == 'create' || $op == 'edit')) {
    
    $custom_group = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'Adresgegevens'));
    $gemeente_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Gemeente', 'custom_group_id' => $custom_group['id']));
    $buurt_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Buurt', 'custom_group_id' => $custom_group['id']));
    $buurtcode_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Buurtcode', 'custom_group_id' => $custom_group['id']));
    $wijkcode_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Wijkcode', 'custom_group_id' => $custom_group['id']));
    
    if ($objectId) {
      CRM_Core_DAO::executeQuery("DELETE FROM `".$custom_group['table_name']. "` WHERE `entity_id` = '".$objectId."'");
    }
    
    $info = civicrm_api3('PostcodeNL', 'get', array('postcode' => $objectRef->postal_code, 'huisnummer' => $objectRef->street_number));
    if (isset($info['values']) && is_array($info['values'])) {
      $values = reset($info['values']);
      
      CRM_Core_DAO::executeQuery("INSERT INTO `".$custom_group['table_name']."` "
          . "(`entity_id`, `".$gemeente_field['column_name'] ."`, `".$buurt_field['column_name']."`, `".$buurtcode_field['column_name']."`, `".$wijkcode_field['column_name']."`) VALUES "
          . "("
          . "'".$objectId."', '".$values['gemeente']."', '".$values['cbs_buurtnaam']."', '".$values['cbs_buurtcode']."', '".$values['cbs_wijkcode']."'"
          . ");"
      );
      
    }
  }
  
  
}