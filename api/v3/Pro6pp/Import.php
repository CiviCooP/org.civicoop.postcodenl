<?php

/* 
 * CiviCRM Api function to import the csv file
 */

/**
 * This function imports the pro6pp.zip file 
 * and puts in the database
 * After that it parses the cbsbuurt.zip for the CBS neighboor data
 * and puts that in the same database
 * And after all is finished correctly the database is copied into the 
 * postcodeNL data set
 * 
 * No parameters needed for this functions.
 * 
 * @param array $params
 */
function civicrm_api3_pro6pp_import($params) {
  try {
    
    if (!isset($params['authkey'])) {
      return civicrm_api3_create_error(ts('Authkey is required'), $params);
    }

    $authkey = $params['authkey'];
    
    set_time_limit(-1);
    
    $pro6pp = new CRM_Postcodenl_ImportPro6pp($authkey);
    $importedPostcodes = $pro6pp->importPro6pp();  
    $importedBuurten = $pro6pp->importCBSBuurten();  
    $pro6pp->copy();
    $return['imported_postcode'] = $importedPostcodes;
    $return['imported_buurten'] = $importedBuurten;
    return civicrm_api3_create_success($return);
  } catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage(), $params);
  }
}

