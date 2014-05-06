<?php

/**
 * PostcodeNL.UpdateAddresses API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_postcode_n_l_updateaddresses_spec(&$spec) {
  $spec['limit']['title'] = 'Lmit of addresses to update at once';
  $spec['limit']['api.default'] = 1000;
  $spec['check_street']['api.default'] = 0;
}

/**
 * PostcodeNL.UpdateAddresses API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_postcode_n_l_updateaddresses($inputParams) {
  //get custom group and fields for cbs data

  set_time_limit(-1); //make sure this job gets enough time to run
  
  $limit = 1000;
  if (isset($inputParams['limit'])) {
    $limit = (int) $inputParams['limit'];
  }
  
  $check_street = false;
  if (isset($inputParams['check_street']) && !empty($inputParams['check_street'])) {
    $check_street = true;
  }

  $count = 0;
  $processed = 0;
  $offset = CRM_Core_BAO_Setting::getItem('org.civicoop.postcodenl', 'job.updateaddresses.offset', NULL, 0);
  $dao = CRM_Core_DAO::executeQuery("SELECT * FROM `civicrm_address` ORDER BY `id` LIMIT ".$offset.", ".$limit, array(), true, 'CRM_Core_DAO_Address');
  while($dao->fetch()) {
    $params = array();
    CRM_Core_DAO::storeValues($dao, $params);
    if (CRM_Postcodenl_Updater::checkAddress($dao->id, $params, $check_street)) {
      $count ++;
    }       
    $processed++;
  }
  if ($processed === 0) {
    $offset = 0;
  } else {
    $offset = $offset + $processed;
  }
  
  CRM_Core_BAO_Setting::setItem($offset, 'org.civicoop.postcodenl', 'job.updateaddresses.offset');
  
  return civicrm_api3_create_success(array('message' => 'Updated '.$count.' addresses'));
}
