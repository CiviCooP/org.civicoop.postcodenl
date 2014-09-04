<?php

/**
 * PostcodeNL.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
/*function _civicrm_api3_postcode_n_l_get_spec(&$spec) {
  $spec['magicword']['api.required'] = 1;
}*/

/**
 * PostcodeNL.Get API
 * 
 * Returns the found postcode, woonplaats, gemeente with the queried paramaters
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_postcode_n_l_get($params) {
  $validParamFields = array(
    'id',
    'postcode',
    'huisnummer',
    'adres',
    'woonplaats',
    'gemeente',
  );
  $returnFields = array(
    'id',
    'postcode_nr',
    'postcode_letter',
    'huisnummer_van',
    'huisnummer_tot',
    'adres',
    'even',
    'provincie',
    'gemeente',
    'woonplaats',
    'cbs_wijkcode',
    'cbs_buurtcode',
    'cbs_buurtnaam',
    'latitude',
    'longitude'
  );
  
  /* 
   * check if at least one parameter is valid 
   * Also break up an postcode into postcode number (4 digits) and postcode letter (2 letters).
   *
   */
  $validatedParams = array();
  foreach($params as $key => $value) {
    if (in_array($key, $validParamFields)) {
      if ($key == 'postcode') {
        //extra validation is needed to break the postcode up into 4 digits and 2 letters
        $postcode = preg_replace('/[^\da-z]/i', '', $value);
        $postcode_4pp = substr($postcode, 0, 4); //select the four digist
        $postcode_2pp = substr($postcode, 4, 2); //select the 2 letters
        if (strlen($postcode_4pp) == 4) {
          $validatedParams['postcode_nr'] = $postcode_4pp;
        }
        if (strlen($postcode_2pp) == 2) {
          $validatedParams['postcode_letter'] = strtoupper($postcode_2pp);
        }
      } elseif (!empty($value)) {
        $validatedParams[$key] = $value;
      }
    }
  }
  
  $sql = "SELECT * FROM `civicrm_postcodenl` WHERE 1";
  
  /**
   * Build the where clausule of the postcode
   */
  $where = "";
  $values = array();
  $i = 1;
  foreach($validatedParams as $field => $value) {
    if ($field == 'huisnummer') {
      //huisnummer needs an between huisnummer_van and huisnummer_tot
      //also there needs to be a check on even or odd
      $even = ($value % 2 == 0 ? 1 : 0);
      $where .= " AND `even` = %".$i;
      $values[$i] = array($even, 'Integer');
      $i++;
      
      $where .= " AND ((%".$i." BETWEEN `huisnummer_van` AND `huisnummer_tot`) XOR (`adres` = 'Postbus'))";
      $values[$i] = array($value, 'Integer');
      $i++;      
    } else {
      $where .= " AND `".$field."` = %".$i;
      $values[$i] = array($value, 'String');
      $i++;
    }    
  }
  $sql .= $where . " LIMIT 0, 25";
  $dao = CRM_Core_DAO::executeQuery($sql, $values);
  
  $returnValues = array();
  while($dao->fetch()) {
    $row = array();
    foreach($returnFields as $field) {
      if (isset($dao->$field)) {
        $row[$field] = $dao->$field;
      }
    }
    $returnValues[$dao->id] = $row;
  }
  
  $hooks = CRM_Utils_Hook::singleton();
  $hooks->invoke(1,
      $returnValues, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject,
      'civicrm_postcodenl_get'
      );
  
  return civicrm_api3_create_success($returnValues, $params, 'PostcodeNL', 'get');
}

