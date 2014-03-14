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
  //var_dump($params); exit();
  
  /*$maxKey = ( max( array_keys($params) ) );
  foreach($params as $key => $item) {
    if ($item['attributes']['nane'] == 'Administer') {
      $params[$key]['child'][$maxKey+1]['attributes'] = array (
         "label"=> ts('Import postcode from pro6pp'),
          "name"=> ts('Import postcode from pro6pp'),
          "url"=> "civicrm/admin/import/pro6pp",
          "permission" => "administer CiviCRM",
          "operator"  => "",
          "separator" => NULL,
          "parentID" => $key,
          "navID"=> $maxKey+1,
          "active"=>  "1",
        );
      $params[$key]['child'][$maxKey+1]['child'] = NULL;
    }
  }
  var_dump($params); exit();*/
  
  $item = array (
    "name"=> ts('Import postcode from pro6pp'),
    "url"=> "civicrm/admin/import/pro6pp",
    "permission" => "administer CiviCRM",
  );
  _postcodenl_civix_insert_navigation_menu($params, "Administer", $item);
}

