<?php

/* 
 * Autocomplete for postcode nl
 * 
 */

class CRM_Postcodenl_Page_AJAX {
  
  function autocomplete() {
    $available_fields = array(
      'adres' => 'adres',
      'woonplaats' => 'woonplaats',
      'gemeente' => 'gemeente',
      'provincie' => 'provincie',
      'cbs_buurtnaam' => 'cbs_buurtnaam'
    );
    $field = CRM_Utils_Request::retrieve('field', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'gemeente');
    if (!in_array($field, $available_fields)) {
      CRM_Utils_System::civiExit();
    }
    $field_name = array_search($field, $available_fields);
    if (empty($field_name)) {
      CRM_Utils_System::civiExit();
    }
    $s = CRM_Utils_Request::retrieve('s', 'String', CRM_Core_DAO::$_nullObject, TRUE, '');
    if (empty($s)) {
      CRM_Utils_System::civiExit();
    }
    
    $sql = "SELECT DISTINCT `".$field_name."` FROM `civicrm_postcodenl` WHERE `".$field_name."` LIKE '".$s."%' ORDER BY `".$field_name."`";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      echo $dao->$field_name . "|0\n";
    }
    CRM_Utils_System::civiExit();
  }
  
}

