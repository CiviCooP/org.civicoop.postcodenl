<?php

/* 
 * This class updates addresses to their info from the postcode table
 */

class CRM_Postcodenl_Updater {
  
  protected static $_singleton;
  
  protected $custom_group;
  protected $gemeente_field;
  protected $buurt_field;
  protected $buurtcode_field;
  protected $wijkcode_field;
  
  protected function __construct() {
    $this->custom_group = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'Adresgegevens'));
    $this->gemeente_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Gemeente', 'custom_group_id' => $this->custom_group['id']));
    $this->buurt_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Buurt', 'custom_group_id' => $this->custom_group['id']));
    $this->buurtcode_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Buurtcode', 'custom_group_id' => $this->custom_group['id']));
    $this->wijkcode_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Wijkcode', 'custom_group_id' => $this->custom_group['id']));
  }
  
  public static function singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Postcodenl_Updater();
    }
    return self::$_singleton;
  }
  
  public static function pre($op, $objectName, $id, &$params) {
    if (($op == 'edit' || $op == 'create') && $objectName == 'Address') {
      $u = self::singleton();
      $u->updateAddressFields($id, $params, false);
    }
  }
  
  public static function post( $op, $objectName, $objectId, &$objectRef ) {
    if ($objectName == 'Address' && ($op == 'create' || $op == 'edit')) {
      $u = self::singleton();
      $params = array();
      CRM_Core_DAO::storeValues($objectRef, $params);
      $u->updateCustomValues($objectId, $params);
    }
  }
  
  public static function checkAddress($address_id, $params, $check_street) {
    $u = self::singleton();
    $update_params = $u->updateAddressFields($address_id, $params, $check_street);
    $updated = false;
    
    if (count($update_params)) {
      $dao = new CRM_Core_DAO_Address();
      $dao->id = $address_id;
      if ($dao->find()) {
        $dao->copyValues($update_params);
        $dao->save();
        $updated = true;
      }
    }
    
    if ($u->updateCustomValues($address_id, $params)) {
      $updated = true;
    }
    
    return $updated;
  }
  
  /**
   * Updates an address to their corresponding information from the postcode table
   * 
   * @param int $address_id
   * @param type $objAddress
   * @param type $check_street
   */
  protected function updateAddressFields($id, &$params, $check_street) {
    $update_params = array();
    try {
      if (isset($params['country_id']) && $params['country_id'] == 1152) {
        $info = civicrm_api3('PostcodeNL', 'get', array('postcode' => $params['postal_code'], 'huisnummer' => $params['street_number']));
        if (isset($info['values']) && is_array($info['values'])) {
          $values = reset($info['values']);

          if ($check_street && (!isset($params['street_name']) || strtolower($values['adres']) != strtolower($params['street_name']))) {
            $params['street_name']  = $values['adres'];
            $update_params['street_name'] = $values['adres'];
          }
          if (!isset($params['city']) || strtolower($values['woonplaats']) != strtolower($params['city'])) {
            $params['city'] = $values['woonplaats'];
            $update_params['city'] = $values['woonplaats'];
          }
          
          $state_province = new CRM_Core_DAO_StateProvince();
          $state_province->name = $values['provincie'];
          $state_province->country_id = $params['country_id'];      
          if ($state_province->find(TRUE)) {
            if (!isset($params['state_province_id']) || $params['state_province_id'] != $state_province->id) {
              $params['state_province_id'] = $state_province->id;
              $update_params['state_province_id'] = $state_province->id;
            }
          }
        }
      }
    } catch (Exception $e) {
      //do nothing on exception, possibly the postcode doesn't exist
    }
    
    return $update_params;
  }
  
  protected function updateCustomValues($address_id, $params) {
    $custom_values = CRM_Core_BAO_CustomValueTable::getEntityValues($address_id, 'Address');
    
    try {
      if (isset($params['country_id']) && $params['country_id'] == 1152) {
        $info = civicrm_api3('PostcodeNL', 'get', array('postcode' => $params['postal_code'], 'huisnummer' => $params['street_number']));
        if (isset($info['values']) && is_array($info['values'])) {
          $values = reset($info['values']);
          
          $update_params = array();
          $this->checkCustomValue($this->gemeente_field, $values['gemeente'], $custom_values, $update_params);
          $this->checkCustomValue($this->buurt_field, $values['cbs_buurtnaam'], $custom_values, $update_params);
          $this->checkCustomValue($this->buurtcode_field, $values['cbs_buurtcode'], $custom_values, $update_params);
          $this->checkCustomValue($this->wijkcode_field, $values['cbs_wijkcode'], $custom_values, $update_params);
          
          if (count($update_params) > 0) {
            $update_params['entityID'] = $address_id;
            CRM_Core_BAO_CustomValueTable::setValues($update_params);
            return true; 
          }          
        }
      }
    } catch (Exception $e) {
      //do nothing on exception, possibly the postcode doesn't exist
    }
    
    return false;
  }
  
  protected function checkCustomValue($custom_field, $checkValue, $custom_values, &$update_params) {
    if (empty($custom_values[$custom_field['id']])) {
      $update_params['custom_'.$custom_field['id']] = $checkValue;
    }
    if (strtolower($checkValue) != strtolower($custom_values[$custom_field['id']])) {
      $update_params['custom_'.$custom_field['id']] = $checkValue;
    } 
  }
  
}
