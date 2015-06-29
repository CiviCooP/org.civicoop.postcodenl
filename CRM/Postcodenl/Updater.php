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
      $u->parseAddress($params);
      $u->updateAddressFields($id, $params, false);
    }
  }

  public static function post($op, $objectName, $objectId, &$objectRef) {
    if ($objectName == 'Address' && ($op == 'create' || $op == 'edit')) {
      $u = self::singleton();
      $params = array();
      CRM_Core_DAO::storeValues($objectRef, $params);
      $u->updateCustomValues($objectId, $params);
    }
  }

  /**
   * Check and update an address
   * 
   * This function updates the adress given by id address_id based on the data in params
   * returns true when the address is changed or false when nothing is changed
   * 
   * @param int $address_id
   * @param array $params
   * @param bool $check_street
   * @return boolean
   */
  public static function checkAddress($address_id, $params, $check_street) {
    $u = self::singleton();
    $update_params = $u->updateAddressFields($address_id, $params, $check_street);
    $updated = false;

    if (count($update_params)) {
      $dao = new CRM_Core_DAO_Address();
      $dao->id = $address_id;
      if ($dao->find()) {
        $dao->copyValues($update_params);
        $update_params2 = $u->parseAddress($params);        
        $dao->copyValues($update_params2);
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
      if (isset($params['country_id']) && $params['country_id'] == 1152 && isset($params['street_number']) && isset($params['postal_code'])) {
        $info = civicrm_api3('PostcodeNL', 'get', array('postcode' => $params['postal_code'], 'huisnummer' => $params['street_number']));
        if (isset($info['values']) && is_array($info['values'])) {
          $values = reset($info['values']);

          if ($check_street && (!isset($params['street_name']) || strtolower($values['adres']) != strtolower($params['street_name']))) {
            $params['street_name'] = $values['adres'];
            $update_params['street_name'] = $values['adres'];
          }
          if (!isset($params['city']) || strtolower($values['woonplaats']) != strtolower($params['city'])) {
            $params['city'] = $values['woonplaats'];
            $update_params['city'] = $values['woonplaats'];
          }
          if (!isset($params['geo_code_1']) || ($params['geo_code_1'] != $values['latitude'])) {
            $params['geo_code_1'] = $values['latitude'];
            $update_params['geo_code_1'] = $values['latitude'];
            $params['geo_code_2'] = $values['longitude'];
            $update_params['geo_code_2'] = $values['longitude'];
            $params['manual_geo_code'] = true;
          } elseif (!isset($params['geo_code_2']) || ($params['geo_code_2'] != $values['longitude'])) {
            $params['geo_code_1'] = $values['latitude'];
            $update_params['geo_code_1'] = $values['latitude'];
            $params['geo_code_2'] = $values['longitude'];
            $update_params['geo_code_2'] = $values['longitude'];
            $params['manual_geo_code'] = true;
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

  /**
   * Parse the address for Dutch addresses
   * Glues together the different parts of an address or explode
   * the the street_adress into the different parts
   * 
   * Returns an array with the changed parts of the address
   * 
   * @param array $params
   * @return array
   */
  protected function parseAddress(&$params) {
    $update_params = array();
    if (isset($params['country_id']) && $params['country_id'] == 1152) {
      /*
       * glue if street_name <> empty and street_number <> empty, split otherwise if street_address not empty
       */
      if (!empty($params['street_address']) && (empty($params['street_name']) || empty($params['street_number']))) {
        $streetParts = $this->splitStreetAddressNl($params['street_address']);
        $params['street_name'] = $streetParts['street_name'];
        $update_params['street_name'] = $streetParts['street_name'];
        if (isset($streetParts['street_number']) && !empty($streetParts['street_number'])) {
          $params['street_number'] = $streetParts['street_number'];
          $update_params['street_number'] = $streetParts['street_number'];
        }
        if (isset($streetParts['street_unit']) && !empty($streetParts['street_unit'])) {
          $params['street_unit'] = $streetParts['street_unit'];
          $update_params['street_unit'] = $streetParts['street_unit'];
        }
        $params['street_address'] = $this->glueStreetAddressNl($streetParts);
        $update_params['street_address'] = $this->glueStreetAddressNl($streetParts);
      } elseif (!empty($params['street_name']) && !empty($params['street_number'])) {
        $params['street_address'] = $this->glueStreetAddressNl($params);
        $update_params['street_address'] = $this->glueStreetAddressNl($params);
      }
    }
    
    return $update_params;
  }

  /**
   * function to glue street address from components in params
   * @param array, expected street_name, street_number and possibly street_unit
   * @return $parsedStreetAddressNl
   */
  protected function glueStreetAddressNl($params) {
    $parsedStreetAddressNl = "";
    /*
     * do nothing if no street_name in params
     */
    if (isset($params['street_name'])) {
      $parsedStreetAddressNl = trim($params['street_name']);
      if (isset($params['street_number']) && !empty($params['street_number'])) {
        $parsedStreetAddressNl .= " " . trim($params['street_number']);
      }
      if (isset($params['street_unit']) && !empty($params['street_unit'])) {
        $parsedStreetAddressNl .= " " . trim($params['street_unit']);
      }
    }
    return $parsedStreetAddressNl;
  }

  /**
   * function to split street_address into components according to Dutch formats.
   * @param streetAddress, containing parsed address in possible sequence
   *        street_number, street_name, street_unit
   *        street_name, street_number, street_unit
   * @return $result, array holding street_number, street_name and street_unit
   */
  protected function splitStreetAddressNl($streetAddress) {
    $result = array();
    /*
     * do nothing if streetAddress is empty
     */
    if (!empty($streetAddress)) {
      /*
       * split into parts separated by spaces
       */
      $addressParts = explode(" ", $streetAddress);
      $foundStreetNumber = false;
      $streetName = null;
      $streetNumber = null;
      $streetUnit = null;
      foreach ($addressParts as $partKey => $addressPart) {
        /*
         * if the part is numeric, there are several possibilities:
         * - if the partKey is 0 so it is the first element, it is
         *   assumed it is part of the street_name to cater for 
         *   situation like 2e Wormenseweg
         * - if not the first part and there is no street_number yet (foundStreetNumber
         *   is false), it is assumed this numeric part contains the street_number
         * - if not the first part but we already have a street_number (foundStreetNumber
         *   is true) it is assumed this is part of the street_unit
         */
        if (is_numeric($addressPart)) {
          if ($foundStreetNumber == false) {
            $streetNumber = $addressPart;
            $foundStreetNumber = true;
          } elseif ($foundStreetNumber) {
            $streetUnit .= " " . $addressPart;
          }
        } else {
          /*
           * if part is not numeric, there are several possibilities:
           * - if the street number is found, set the whole part to streetUnit
           * - if there is no streetNumber yet and it is the first part, set the
           *   whole part to streetName
           * - if there is no streetNumber yet and it is not the first part,
           *   check all digits:
           *   - if the first digit is numeric, put the numeric part in streetNumber
           *     and all non-numerics to street_unit
           *   - if the first digit is not numeric, put the lot into streetName
           */
          if ($foundStreetNumber == true) {
            if (!empty($streetName)) {
              $streetUnit .= " " . $addressPart;
            } else {
              $streetName .= " " . $addressPart;
            }
          } else {
            if ($partKey == 0) {
              $streetName .= $addressPart;
            } else {
              $partLength = strlen($addressPart);
              if (is_numeric(substr($addressPart, 0, 1))) {
                for ($i = 0; $i < $partLength; $i++) {
                  if (is_numeric(substr($addressPart, $i, 1))) {
                    $streetNumber .= substr($addressPart, $i, 1);
                    $foundStreetNumber = true;
                  } else {
                    $streetUnit .= " " . substr($addressPart, $i, 1);
                  }
                }
              } else {
                $streetName .= " " . $addressPart;
              }
            }
          }
        }
      }
      $result['street_name'] = trim($streetName);
      $result['street_number'] = $streetNumber;
      $result['street_unit'] = trim($streetUnit);
    }
    return $result;
  }

  /**
   * Update the custom values for an address
   * 
   * The address data is given in params. The custom values are the community
   * cbs_area code etc...
   * 
   * Returns true when the custom values are updated
   *  
   * @param int $address_id
   * @param array $params
   * @return boolean
   */
  protected function updateCustomValues($address_id, $params) {
    $custom_values = CRM_Core_BAO_CustomValueTable::getEntityValues($address_id, 'Address');

    $update_params = array();
    $this->checkCustomValue($this->gemeente_field, '', $custom_values, $update_params);
    $this->checkCustomValue($this->buurt_field, '', $custom_values, $update_params);
    $this->checkCustomValue($this->buurtcode_field, '', $custom_values, $update_params);
    $this->checkCustomValue($this->wijkcode_field, '', $custom_values, $update_params);

    try {
      if (isset($params['country_id']) && $params['country_id'] == 1152 && isset($params['postal_code']) && isset($params['street_number'])) {
        $info = civicrm_api3('PostcodeNL', 'get', array('postcode' => $params['postal_code'], 'huisnummer' => $params['street_number']));
        if (isset($info['values']) && is_array($info['values'])) {
          $values = reset($info['values']);

          $this->checkCustomValue($this->gemeente_field, $values['gemeente'], $custom_values, $update_params);
          $this->checkCustomValue($this->buurt_field, $values['cbs_buurtnaam'], $custom_values, $update_params);
          $this->checkCustomValue($this->buurtcode_field, $values['cbs_buurtcode'], $custom_values, $update_params);
          $this->checkCustomValue($this->wijkcode_field, $values['cbs_wijkcode'], $custom_values, $update_params);
        }
      }

      if (count($update_params) > 0) {
        $update_params['entityID'] = $address_id;
        CRM_Core_BAO_CustomValueTable::setValues($update_params);
        return true;
      }

    } catch (Exception $e) {
      //do nothing on exception, possibly the postcode doesn't exist
    }

    return false;
  }

  protected function checkCustomValue($custom_field, $checkValue, $custom_values, &$update_params) {
    if (empty($custom_values[$custom_field['id']])) {
      $update_params['custom_' . $custom_field['id']] = $checkValue;
    } elseif (!empty($custom_values[$custom_field['id']]) && strtolower($checkValue) != strtolower($custom_values[$custom_field['id']])) {
      $update_params['custom_' . $custom_field['id']] = $checkValue;
    } elseif (isset($update_params['custom_'.$custom_field['id']])) {
      unset($update_params['custom_'.$custom_field['id']]);
    }
  }

}
