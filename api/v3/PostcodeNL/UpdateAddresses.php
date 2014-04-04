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
  $spec['start'] = array('title' => 'Lmit of addresses to update at once');
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

  $limit = 25;
  if (isset($inputParams['limit'])) {
    $limit = (int) $inputParams['limit'];
  }

  $select = "SELECT `a`.`id` AS `address_id`, `p`.*, `a`.`street_number`, `a`.`street_unit`, `a`.`contact_id`";

  $from = " `civicrm_address` `a`";
  $from .= " INNER JOIN `civicrm_postcodenl` `p` ON (
          LOWER(SUBSTR(`a`.`postal_code` FROM 1 FOR 4)) = LOWER(`p`.`postcode_nr`) COLLATE utf8_unicode_ci
          AND
          LOWER(SUBSTR(`a`.`postal_code` FROM -2)) = LOWER(`p`.`postcode_letter`) COLLATE utf8_unicode_ci
          AND
          IF((`a`.`street_number`%2) = 0, 1, 0) = `p`.`even` 
          AND 
          (`a`.`street_number` BETWEEN `p`.`huisnummer_van` AND `p`.`huisnummer_tot`)
            )";
  $from .= " LEFT JOIN `civicrm_state_province` `prov` ON `a`.`state_province_id` = `prov`.`id`";
  $where = " WHERE `a`.`country_id` = 1152 ";
  $clause = "`a`.`street_name` != `p`.`adres` COLLATE utf8_unicode_ci OR `a`.`city` != `p`.`woonplaats` COLLATE utf8_unicode_ci";
  $clause .= " OR `prov`.`id` IS NULL or `prov`.`name` != `p`.`provincie` COLLATE utf8_unicode_ci";
  $update = " SET `a`.`street_name` = `p`.`adres`, `a`.`city` = `p`.`woonplaats`";


  $group = civicrm_api('CustomGroup', 'getsingle', array('name' => 'Adresgegevens', 'extends' => 'Address', 'version' => 3));
  $gemeente = civicrm_api('CustomField', 'getsingle', array('name' => 'Gemeente', 'custom_group_id' => $group['id'], 'version' => 3));
  $buurt = civicrm_api('CustomField', 'getsingle', array('name' => 'Buurt', 'custom_group_id' => $group['id'], 'version' => 3));
  $buurtcode = civicrm_api('CustomField', 'getsingle', array('name' => 'Buurtcode', 'custom_group_id' => $group['id'], 'version' => 3));
  $wijkcode = civicrm_api('CustomField', 'getsingle', array('name' => 'Wijkcode', 'custom_group_id' => $group['id'], 'version' => 3));

  if (isset($group['table_name'])) {
    $from .= " LEFT JOIN `" . $group['table_name'] . "` `g` ON `a`.`id` = `g`.`entity_id` ";
  }
  if (isset($gemeente['column_name'])) {
    $clause .= " OR `p`.`gemeente` !=  `g`.`" . $gemeente['column_name'] . "` COLLATE utf8_unicode_ci";
    $update .= ", `g`.`" . $gemeente['column_name'] . "` = `p`.`gemeente`";
  }
  if (isset($buurt['column_name'])) {
    $clause .= " OR `p`.`cbs_buurtnaam` !=  `g`.`" . $buurt['column_name'] . "` COLLATE utf8_unicode_ci";
    $update .= ", `g`.`" . $buurt['column_name'] . "` = `p`.`cbs_buurtnaam`";
  }
  if (isset($buurtcode['column_name'])) {
    $clause .= " OR `p`.`cbs_buurtcode` !=  `g`.`" . $buurtcode['column_name'] . "` COLLATE utf8_unicode_ci";
    $update .= ", `g`.`" . $buurtcode['column_name'] . "` = `p`.`cbs_buurtcode`";
  }
  if (isset($wijkcode['column_name'])) {
    $clause .= " OR `p`.`cbs_wijkcode` !=  `g`.`" . $wijkcode['column_name'] . "` COLLATE utf8_unicode_ci";
    $update .= ", `g`.`" . $wijkcode['column_name'] . "` = `p`.`cbs_wijkcode`";
  }

  $sql = $select . " FROM " . $from . $where . " AND (" . $clause . ") LIMIT " . $limit;
  //$updateSql = "UPDATE " . $from . $update . $where . " AND (" . $clause . ") LIMIT " . $limit;

  $count = 0;
  $dao = CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    $params['id'] = $dao->address_id;
    $params['city'] = $dao->woonplaats;
    $params['street_name'] = $dao->adres;
    $params['state_province'] = $dao->provincie;
    $params['street_address'] = trim($dao->adres . " " . $dao->street_number . $dao->street_unit);
    $params['street_parsing'] = 0;
    $params['contact_id'] = $dao->contact_id;

    $customFields = array();
    if (isset($gemeente['column_name'])) {
      $customFields['custom_'.$gemeente['id']] = $dao->gemeente;
    }
    if (isset($buurt['column_name'])) {
      $customFields['custom_'.$buurt['id']] = $dao->cbs_buurtnaam;
    }
    if (isset($buurtcode['column_name'])) {
      $customFields['custom_'.$buurtcode['id']] = $dao->cbs_buurtcode;
    }
    if (isset($wijkcode['column_name'])) {
      $customFields['custom_'.$wijkcode['id']] = $dao->cbs_wijkcode;
    }
    
    $params['version'] = 3;
    $result = civicrm_api('Address', 'Create', $params);
    
    if (count($customFields)) {
      $customFields['entity_table'] = 'civicrm_address';
      $customFields['entity_id'] = $dao->address_id;
      $customFields['version']  = 3;
      civicrm_api('CustomValue', 'Create', $customFields);
    }
    
    $count++;
    
  }
  
  return civicrm_api3_create_success(array('message' => 'Updated '.$count.' addresses'));
}
