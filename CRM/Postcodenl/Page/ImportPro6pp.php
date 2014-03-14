<?php

require_once 'CRM/Core/Page.php';

class CRM_Postcodenl_Page_ImportPro6pp extends CRM_Core_Page {
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('Import postcode data from Pro6pp'));

    parent::run();
  }
}
