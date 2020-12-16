<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

use CRM_Postcodenl_ExtensionUtil as E;

class CRM_Postcodenl_Pro6pp_BatchImport {

  const MAX_LINES_PER_SPLITTED_FILE = 1000;

  public static function prepare($authKey, $includeCBSBuurten=true) {
    $queue = CRM_Queue_Service::singleton()->create(array(
      'type' => 'Sql',
      'name' => 'org.civicoop.postcodenl.importpro6pp',
      'reset' => TRUE,
    ));
    self::downloadAndSplitFile($queue, $authKey, 'download_nl_sixpp.zip', 'postcode', ['CRM_Postcodenl_Pro6pp_BatchImport', 'importPostcodes'], 'Importing postcodes %1');
    CRM_Core_DAO::executeQuery("TRUNCATE `civicrm_pro6pp_import`;");
    if ($includeCBSBuurten) {
      self::downloadAndSplitFile($queue, $authKey, 'download_nl_sixpp_cbs_buurt_utf8.zip', 'cbsbuurten', ['CRM_Postcodenl_Pro6pp_BatchImport', 'importCbsBuurten'], 'Importing CBS Buurten %1');
      $task = new CRM_Queue_Task(['CRM_Postcodenl_Pro6pp_BatchImport', 'finishImportCbsBuurt'], [], E::ts('Finishing CBS Buurten import'));
      $queue->createItem($task);
      CRM_Core_DAO::executeQuery("TRUNCATE `civicrm_pro6pp_import_cbsbuurt`;");
    }
    self::downloadAndSplitFile($queue, $authKey, 'download_nl_city.zip', 'cities', ['CRM_Postcodenl_Pro6pp_BatchImport', 'importCities'], 'Importing cities %1');
    CRM_Core_DAO::executeQuery("TRUNCATE `civicrm_postcodenl_alt_city`;");


    $runner = new CRM_Queue_Runner(array(
      'title' => E::ts('Importing postcodes'), //title fo the queue
      'queue' => $queue, //the queue object
      'errorMode'=> CRM_Queue_Runner::ERROR_ABORT, //abort upon error and keep task in queue
      'onEnd' => array('CRM_Postcodenl_Pro6pp_BatchImport', 'onEnd'), //method which is called as soon as the queue is finished
      'onEndUrl' => CRM_Utils_System::url('civicrm', 'reset=1'), //go to page after all tasks are finished
    ));
    $runner->runAllViaWeb();
  }

  public static function importPostcodes($ctx, $filename) {
    $fp = fopen($filename, 'r');
    if ($fp === false) {
      throw new \Exception('Error during import');
    }
    //read csv file line for line
    $sql = "INSERT INTO `civicrm_pro6pp_import` "
      . " (`postcode_nr`, `postcode_letter`, `huisnummer_van`, `huisnummer_tot`, `adres`, `even`, `provincie`, `gemeente`, `woonplaats`, `latitude`, `longitude`)"
      . " VALUES ";
    $values = "";
    $headers = array();
    $lineNr = 0;
    while (($data = fgetcsv($fp, 0, ',')) !== false) {
      $lineNr++;
      if ($lineNr == 1) {
        $headers = array_flip($data);
        continue;
      }

      //escape data for database
      foreach($data as $n => $val) {
        $data[$n] = CRM_Core_DAO::escapeString($val);
      }

      $postcode_letter = substr($data[$headers[utf8_encode('nl_sixpp')]], 4, 2);
      $postcode_cijfer = substr($data[$headers[utf8_encode('nl_sixpp')]], 0, 4);
      $adres = $data[$headers[utf8_encode('street')]];
      $provincie = $data[$headers[utf8_encode('province')]];
      $gemeente = $data[$headers[utf8_encode('municipality')]];
      $woonplaats = $data[$headers[utf8_encode('city')]];
      $lat = $data[$headers[utf8_encode('lat')]];
      $lng = $data[$headers[utf8_encode('lng')]];
      if (empty($lat)) {
        $lat = 'NULL';
      } else {
        $lat = "'".$lat."'";
      }
      if (empty($lng)) {
        $lng = 'NULL';
      } else {
        $lng = "'".$lng."'";
      }
      $huisnummers = explode(";", $data[$headers[utf8_encode('streetnumbers')]]);
      //one records could contain multiple sets of housenummbers, seperated by 1-10;40-50;
      foreach ($huisnummers as $huisnr) {
        $nrs = explode("-", $huisnr);
        $start = false;
        $eind = false;
        $even = false;
        if (isset($nrs[0])) {
          if (empty($nrs[0])) {
            $start = 0;
            $eind = 0;
          } else {
            $start = $nrs[0];
            $eind = $nrs[0];
          }
          $even = ($start % 2 == 0 ? 1 : 0);
        }
        if (isset($nrs[1])) {
          $eind = $nrs[1];
        }
        if ($start !== false && $eind !== false) {
          if (strlen($values)) {
            $values .= ",";
          }
          $values .= " ('" . $postcode_cijfer . "', '" . $postcode_letter . "', '" . $start . "', '" . $eind . "', '" . $adres . "', '" . $even . "', '" . $provincie . "', '" . $gemeente . "', '" . $woonplaats . "', " . $lat . ", " . $lng . ")";
        }
      }
    }

    if (strlen($values)) {
      CRM_Core_DAO::executeQuery($sql . $values . ";");
    }
    fclose($fp);
    return TRUE;
  }

  public static function importCities($ctx, $filename) {
    $fp = fopen($filename, 'r');
    if ($fp === false) {
      throw new \Exception('Error during import');
    }
    //read csv file line for line
    $sql = "INSERT INTO `civicrm_postcodenl_alt_city` "
      . " (`provincie`, `city`, `alt_city`)"
      . " VALUES ";
    $values = "";
    $headers = array();
    $lineNr = 0;
    while (($data = fgetcsv($fp, 0, ',')) !== false) {
      $lineNr++;
      if ($lineNr == 1) {
        //firstline is heading
        $headers = array_flip($data);
        continue;
      }

      //escape data for database
      foreach($data as $n => $val) {
        $data[$n] = CRM_Core_DAO::escapeString($val);
      }

      $provincie = $data[$headers['province']];
      $city = $data[$headers['city']];
      $alt_city = $data[$headers['city_alt']];

      if (!empty($alt_city)) {
        if (strlen($values)) {
          $values .= ",";
        }
        $values .= " ('".$provincie."', '".$city."', '".$alt_city."')";
      }
    }

    if (strlen($values)) {
      CRM_Core_DAO::executeQuery($sql . $values . ";");
    }
    fclose($fp);
    return TRUE;
  }

  public static function importCbsBuurten($ctx, $filename) {
    $fp = fopen($filename, 'r');
    if ($fp === false) {
      throw new \Exception('Error during import');
    }
    //read csv file line for line
    $sql = "INSERT INTO `civicrm_pro6pp_import_cbsbuurt` "
      . " (`postcode_nr`, `postcode_letter`, `cbs_buurtcode`, `cbs_buurtnaam`, `cbs_wijkcode`)"
      . " VALUES ";
    $values = "";
    $headers = array();
    $lineNr = 0;
    while (($data = fgetcsv($fp, 0, ',')) !== false) {
      $lineNr++;
      if ($lineNr == 1) {
        //firstline is heading
        $headers = array_flip($data);
        continue;
      }

      //escape data for database
      foreach($data as $n => $val) {
        $data[$n] = CRM_Core_DAO::escapeString($val);
      }

      $postcode_letter = substr($data[$headers['nl_sixpp']], 4, 2);
      $postcode_cijfer = substr($data[$headers['nl_sixpp']], 0, 4);
      $buurtcode = $data[$headers['cbs_buurtcode']];
      $buurtnaam = $data[$headers['cbs_buurtnaam']];
      $wijkcode = $data[$headers['cbs_wijkcode']];

      if (strlen($values)) {
        $values .= ",";
      }
      $values .= " ('".$postcode_cijfer."', '".$postcode_letter."', '".$buurtcode."', '".$buurtnaam."', '".$wijkcode."')";
    }

    if (strlen($values)) {
      CRM_Core_DAO::executeQuery($sql . $values . ";");
    }
    fclose($fp);
    return TRUE;
  }

  public static function finishImportCbsBuurt($ctx) {
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_pro6pp_import` `i` "
      . "INNER JOIN `civicrm_pro6pp_import_cbsbuurt` `cbs` ON `i`.`postcode_letter` = `cbs`.`postcode_letter` AND `i`.`postcode_nr` = `cbs`.`postcode_nr`"
      . "SET `i`.`cbs_buurtcode` = `cbs`.`cbs_buurtcode`, `i`.`cbs_buurtnaam` = `cbs`.`cbs_buurtnaam`, `i`.`cbs_wijkcode` = `cbs`.`cbs_wijkcode`;");
    return TRUE;
  }

  public static function onEnd() {
    CRM_Core_DAO::executeQuery("TRUNCATE `civicrm_postcodenl`;");
    CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_postcodenl` SELECT * FROM `civicrm_pro6pp_import`");
    CRM_Core_Session::setStatus(E::ts('Finished Postcode Import'), E::ts('Import Postcodes'), 'success');
  }

  protected static function downloadAndSplitFile(CRM_Queue_Queue $queue, $authKey, $file, $prefix, $callBack, $title) {
    $pro6pp = new CRM_Postcodenl_Pro6pp_Downloader($authKey);
    $fp = $pro6pp->getStreamToCSV($file, true, false);
    $header = false;
    $lineNr = 0;
    $lineInFileOut = 0;
    $contents = '';

    $tempDirectory = \Civi::paths()->getPath(CIVICRM_TEMPLATE_COMPILEDIR."pro6pp");
    CRM_Utils_File::createDir($tempDirectory);

    $fileCount = 1;
    while($line = fgets($fp)) {
      if ($lineNr == 0) {
        $header = $line;
      } else {
        $contents .= $line;
        $lineInFileOut ++;
        if ($lineInFileOut > self::MAX_LINES_PER_SPLITTED_FILE) {
          $baseFileName = $prefix.'-'.date('YmdHi').'-'.$fileCount;
          $fileOutName = \Civi::paths()->getPath(CIVICRM_TEMPLATE_COMPILEDIR."pro6pp/".$baseFileName.'.csv');
          file_put_contents($fileOutName, $header.$contents);
          $fileCount ++;
          $lineInFileOut = 0;
          $contents = '';

          $taskParams = [$fileOutName];
          $taskTitle = E::ts($title, [1=>$lineNr]);
          $task = new CRM_Queue_Task($callBack, $taskParams, $taskTitle);
          $queue->createItem($task);
        }
      }
      $lineNr ++;
    }
    if (strlen($contents)) {
      $baseFileName = $prefix.'-'.date('YmdHi').'-'.$fileCount;
      $fileOutName = \Civi::paths()->getPath(CIVICRM_TEMPLATE_COMPILEDIR."pro6pp/".$baseFileName.'.csv');
      file_put_contents($fileOutName, $header.$contents);

      $taskParams = [$fileOutName];
      $taskTitle = E::ts($title, [1=>$lineNr]);
      $task = new CRM_Queue_Task($callBack, $taskParams, $taskTitle);
      $queue->createItem($task);
    }
    fclose($fp);
  }



}
