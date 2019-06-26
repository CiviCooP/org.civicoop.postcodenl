<?php

/*
 * This class updates addresses to their info from the postcode table
 */

class CRM_Postcodenl_Updater {

  /**
   * Array holding the submitted street_units
   * We use this because somehow civicrm throws the street unit away and adds it to the street_address.
   *
   * @var array
   */
  protected $street_units = array();

  protected static $_singleton;
  protected $custom_group;
  protected $gemeente_field;
  protected $buurt_field;
  protected $buurtcode_field;
  protected $wijkcode_field;
  protected $provincie_field;
  protected $manual_processing;

  protected function __construct() {
    $this->custom_group = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'Adresgegevens'));
    $this->gemeente_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Gemeente', 'custom_group_id' => $this->custom_group['id']));
    $this->buurt_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Buurt', 'custom_group_id' => $this->custom_group['id']));
    $this->buurtcode_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Buurtcode', 'custom_group_id' => $this->custom_group['id']));
    $this->wijkcode_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Wijkcode', 'custom_group_id' => $this->custom_group['id']));
    $this->provincie_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'Provincie', 'custom_group_id' => $this->custom_group['id']));
    $this->manual_processing = civicrm_api3('CustomField', 'getsingle', array('name' => 'cbs_manual_entry', 'custom_group_id' => $this->custom_group['id']));
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
      // Set manual processing to true, when address is not linked to an contact.
      // This is usually the case with events and in that case it is not really important to autocomplete the address
      $contactIdIsEmpty = empty($params['contact_id']) || $params['contact_id'] == 'null' ? true : false;
      if (!$id && $contactIdIsEmpty && !isset($params['custom_'.$u->manual_processing['id']])) {
        var_dump($params);
        $params['custom_'.$u->manual_processing['id']] = 1;
      } elseif ($contactIdIsEmpty && !isset($params['custom_'.$u->manual_processing['id']])) {
        // Retrieve current value from database for manual processing
        $dao = CRM_Core_DAO::singleValueQuery("SELECT {$u->manual_processing['column_name']} as manual_processing from `{$u->custom_group['table_name']}` WHERE entity_id = %1", array(1 => array($id, 'Integer')));
        if ($dao->fetch) {
          $params['custom_' . $u->manual_processing['id']] = $dao->manual_processing ? 1 : 0;
        } else {
          // Value is not found so set to manual processing.
          $params['custom_'.$u->manual_processing['id']] = 1;
        }
      }
      $u->parseAddress($params);
      $u->updateAddressFields($id, $params);
      $u->parseAddress($params);
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
   * When the country of an address is belgium set the right street_address in the right formatting.
   *
   * @param \CRM_Core_Form $form
   */
  public static function setStreetAddressOnForm(CRM_Core_Form $form) {
    if (!$form instanceof CRM_Contact_Form_Inline_Address && !$form instanceof CRM_Contact_Form_Contact) {
      return;
    }
    // Set the all address Field Values
    $values = $form->getVar('_values');
    $allAddressFields = $form->get_template_vars('allAddressFieldValues');
    $allAddressFields = json_decode($allAddressFields, TRUE);
    foreach($values['address'] as $locBlockNo => $address) {
      if ($address['country_id'] == 1152) {
        if ($allAddressFields && isset($allAddressFields['street_address_' . $locBlockNo]) && isset($address['street_address'])) {
          $allAddressFields['street_address_' . $locBlockNo] = $address['street_address'];
        }
        $defaults = array();
        $defaults['address'][$locBlockNo]['street_address'] = $address['street_address'];
        $form->setDefaults($defaults);
      }
    }
    if ($allAddressFields) {
      $form->assign('allAddressFieldValues', json_encode($allAddressFields));
    }
  }

  /**
   * The build form hook retrieves the submitted Street unit which we could use later
   * to glue the address together. CiviCRM somehow adds the street unit to the street address and makes the street unit field empty.
   *
   * @param \CRM_Core_Form $form
   *
   */
  public static function storetreetUnitFromFormSubmission(CRM_Core_Form $form) {
    if (!$form instanceof CRM_Contact_Form_Inline_Address && !$form instanceof CRM_Contact_Form_Contact) {
      return;
    }

    $parser = self::singleton();
    $parser->street_units = array();

    $submittedValues = $form->exportValues();
    foreach($submittedValues['address'] as $locBlockNo => $address) {
      if (isset($address['country_id']) && $address['country_id'] == 1152) {
        $street_unit = $address['street_unit'];
        $parser->street_units[] = $street_unit;
      }
    }
  }

  /**
   * Updates an address to their corresponding information from the postcode table.
   * 
   * @param int $id
   * @param array $params
   * @return void|array
   */
  protected function updateAddressFields($id, &$params) {
    if (isset($params['state_province_id']) && $params['state_province_id'] == 'null') {
      unset($params['state_province_id']);
    }
    $update_params = array();

    if ($id) {
      $custom_id = CRM_Core_DAO::singleValueQuery("SELECT id from `{$this->custom_group['table_name']}` WHERE entity_id = %1", array(
        1 => array(
          $id,
          'Integer'
        )
      ));
      if ($custom_id && !empty($params['custom_'.$this->manual_processing['id'].'_'.$custom_id])) {
        return;
      }
    }
    if (!empty($params['custom_'.$this->manual_processing['id']])) {
      return;
    }

    try {
      if (isset($params['country_id']) && $params['country_id'] == 1152 && isset($params['city']) && !empty($params['city'])) {
        //check whether the city name is alternativly spelled
        $official_city = CRM_Core_DAO::executeQuery("SELECT * FROM `civicrm_postcodenl_alt_city` WHERE alt_city = %1", array(1=>array($params['city'], 'String')));
        if ($official_city->fetch()) {
          $params['city'] = $official_city->city;
          $update_params['city'] = $official_city->city;
        }
      }
      
      if (isset($params['country_id']) && $params['country_id'] == 1152 && isset($params['street_number']) && isset($params['street_name']) && isset($params['city']) && !empty($params['street_number']) && !empty($params['street_name']) && !empty($params['city']) && (!isset($params['postal_code']) || empty($params['postal_code']))) {
        $info = civicrm_api3('PostcodeNL', 'get', array('adres' => $params['street_name'], 'huisnummer' => $params['street_number'], 'woonplaats' => $params['city']));
        if (isset($info['values']) && is_array($info['values']) && count($info['values']) > 0) {
          $values = reset($info['values']);
          if (!isset($params['postal_code']) || empty($params['postal_code'])) {
            $params['postal_code'] = $values['postcode_nr']." ".$values['postcode_letter'];
            $update_params['postal_code'] = $values['postcode_nr']." ".$values['postcode_letter'];
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

          $state_province_id = $this->getProvinceIdByDutchName($values['provincie']);
          if (!empty($state_province_id)) {
            $params['state_province_id'] = $state_province_id;
            $update_params['state_province_id'] = $state_province_id;
          }
        }
      } elseif (isset($params['country_id']) && $params['country_id'] == 1152 && isset($params['street_number']) && isset($params['postal_code']) && !empty($params['street_number']) && !empty($params['postal_code'])) {
        $info = civicrm_api3('PostcodeNL', 'get', array('postcode' => $params['postal_code'], 'huisnummer' => $params['street_number']));
        if (isset($info['values']) && is_array($info['values']) && count($info['values']) > 0) {
          $values = reset($info['values']);
          if (!isset($params['street_name']) || strtolower($values['adres']) != strtolower($params['street_name'])) {
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

          $state_province_id = $this->getProvinceIdByDutchName($values['provincie']);
          if (!empty($state_province_id)) {
            $params['state_province_id'] = $state_province_id;
            $update_params['state_province_id'] = $state_province_id;
          }
        }
      } elseif (isset($params['country_id']) && $params['country_id'] == 1152 && isset($params['city']) && !empty($params['city']) && empty($params['state_province_id'])) {
        $dao = CRM_Core_DAO::executeQuery("SELECT provincie FROM civicrm_postcodenl WHERE woonplaats = %1 GROUP BY provincie", array(
          1 => array(
            $params['city'],
            'String'
          )
        ));
        if ($dao->N == 1 && $dao->fetch()) {
          $state_province_id = $this->getProvinceIdByDutchName($dao->provincie);
          if (!empty($state_province_id)) {
            $params['state_province_id'] = $state_province_id;
            $update_params['state_province_id'] = $state_province_id;
          }
        }
      } elseif (isset($params['country_id']) && $params['country_id'] == 1152 && isset($params['postal_code']) && !empty($params['postal_code']) && empty($params['city'])) {
        $postcode = str_replace(" ", "", $params['postal_code']);
        $dao = CRM_Core_DAO::executeQuery("SELECT provincie, woonplaats from civicrm_postcodenl where postcode_nr = '".substr($postcode, 0, 4)."' and postcode_letter = '".substr($postcode, 4, 2)."' GROUP BY woonplaats, provincie");
        if ($dao->fetch() && $dao->N == 1) {
          $state_province_id = $this->getProvinceIdByDutchName($dao->provincie);
          if (!empty($state_province_id)) {
            $params['state_province_id'] = $state_province_id;
            $update_params['state_province_id'] = $state_province_id;
          }
        }
      }
    } catch (Exception $e) {
      //do nothing on exception, possibly the postcode doesn't exist
    }
    return $update_params;
  }

  protected function getProvinceIdByDutchName($province) {
    $result = civicrm_api3('Address', 'getoptions', array(
      'sequential' => 1,
      'field' => "state_province_id",
      'country_id' => 1152,
    ));
    foreach($result['values'] as $state_province) {
      if ($state_province['value'] == $province) {
        return $state_province['key'];
      }
    }
    return false;
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
      // Fix street unit
      if (empty($params['street_unit']) && is_array($this->street_units)) {
        $street_unit = array_shift($this->street_units);
        if (!empty($street_unit)) {
          $params['street_unit'] = $street_unit;
          // Check if street unit is part of the street_name and if so remove it
          $matches = [];
          if (preg_match('/^(.*) (' . $street_unit . ')$/', $params['street_name'], $matches)) {
            $params['street_name'] = substr($params['street_name'], 0, (-1 * strlen($street_unit) - 1));
          }
        }
      }
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
      } elseif (!empty($params['street_name']) || !empty($params['street_number'])) {
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
    $this->checkCustomValue($this->provincie_field, '', $custom_values, $update_params);

    try {
      $postcodeParams = array();

      if (isset($params['country_id']) && $params['country_id'] == 1152) {
        if (isset($params['postal_code'])) {
          $postcodeParams['postcode'] = $params['postal_code'];
        }
        if (isset($params['street_number'])) {
          $postcodeParams['huisnummer'] = $params['street_number'];
        }
        if (isset($params['city'])) {
          $postcodeParams['woonplaats'] = $params['city'];
        }
        if (isset($params['state_province_id']) && !empty($params['state_province_id'])) {
          $provincie = new CRM_Core_DAO_StateProvince();
          $provincie->id = $params['state_province_id'];
          if ($provincie->find(true)) {
            $postcodeParams['provincie'] = $provincie->name;
          }
        }
      }

      if (count($postcodeParams)) {
        $info = civicrm_api3('PostcodeNL', 'get', $postcodeParams);
        if (isset($info['values']) && is_array($info['values'])) {
          if (count($info['values']) == 1) {
            // we have exactly one address, we can update buurt and wijk
            $updateBuurtEnWijk = TRUE;
          }
          else {
            // more addresses are returned, so don't update buurt and wijk
            $updateBuurtEnWijk = FALSE;
          }

          $values = reset($info['values']);

          $this->checkCustomValue($this->gemeente_field, $values['gemeente'], $custom_values, $update_params);
          $this->checkCustomValue($this->provincie_field, $values['provincie'], $custom_values, $update_params);
          if ($updateBuurtEnWijk) {
            $this->checkCustomValue($this->buurt_field, $values['cbs_buurtnaam'], $custom_values, $update_params);
            $this->checkCustomValue($this->buurtcode_field, $values['cbs_buurtcode'], $custom_values, $update_params);
            $this->checkCustomValue($this->wijkcode_field, $values['cbs_wijkcode'], $custom_values, $update_params);
          }
        }
      }

      if (count($update_params) > 0) {
        $update_params['entityID'] = $address_id;
        CRM_Core_BAO_CustomValueTable::setValues($update_params);
        return TRUE;
      }
    } catch (Exception $e) {
      //do nothing on exception, possibly the postcode doesn't exist
    }

    return FALSE;
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
