<?php

/*
 * Importer class to import the pro6pp data
 * 
 */

class CRM_Postcodenl_ImportPro6pp {

  private $key;

  private $downloadUrl = 'http://api.pro6pp.nl/v1/download';

  private $metaUrl = 'https://api.pro6pp.nl/v1/download/metadata';
  
  public function __construct($authkey) {
    $this->key = $authkey;
  }

  /**
   * Imports the postcode data
   * 
   * @param String $zipfile filename for the zip file
   */
  public function importPro6pp() {

    $fp = $this->getStreamToCSV('download_nl_sixpp.zip');
    $headers = array();

    $lineNr = 0;

    //truncate the import table
    CRM_Core_DAO::executeQuery("TRUNCATE `civicrm_pro6pp_import`;");

    //read csv file line for line
    $sql = "INSERT INTO `civicrm_pro6pp_import` "
          . " (`postcode_nr`, `postcode_letter`, `huisnummer_van`, `huisnummer_tot`, `adres`, `even`, `provincie`, `gemeente`, `woonplaats`, `latitude`, `longitude`)"
          . " VALUES ";
    $values = "";
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
      $adres = $data[$headers['street']];
      $provincie = $data[$headers['province']];
      $gemeente = $data[$headers['municipality']];
      $woonplaats = $data[$headers['city']];
      $lat = $data[$headers['lat']];
      $lng = $data[$headers['lng']];
      $huisnummers = explode(";", $data[$headers['streetnumbers']]);
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
          $values .= " ('" . $postcode_cijfer . "', '" . $postcode_letter . "', '" . $start . "', '" . $eind . "', '" . $adres . "', '" . $even . "', '" . $provincie . "', '" . $gemeente . "', '" . $woonplaats . "', '" . $lat . "', '" . $lng . "')";
        }
      }
      
      if (strlen($values) && ($lineNr % 1000 == 0)) {
        CRM_Core_DAO::executeQuery($sql . $values . ";");
        $values = "";
        usleep(50); //wait for the database, so the query below is executed faster
      }
    }
    if (strlen($values)) {
      CRM_Core_DAO::executeQuery($sql . $values . ";");
      usleep(100); //wait for the database, so the query below is executed faster
    }
    
    //CRM_Core_Transaction::willCommit();
    
    $this->closeFP($fp);

    return $lineNr;
  }
  
  public function importCBSBuurten() {
    $fp = $this->getStreamToCSV('download_nl_sixpp_cbs_buurt_utf8.zip', false);
    $headers = array();

    $lineNr = 0;
    
    CRM_Core_DAO::executeQuery("TRUNCATE `civicrm_pro6pp_import_cbsbuurt`;");

    //read csv file line for line
    $sql = "INSERT INTO `civicrm_pro6pp_import_cbsbuurt` "
          . " (`postcode_nr`, `postcode_letter`, `cbs_buurtcode`, `cbs_buurtnaam`, `cbs_wijkcode`)"
          . " VALUES ";
    $values = "";    
    //read csv file line for line
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
      
      if (strlen($values) && ($lineNr % 1000 == 0)) {
        CRM_Core_DAO::executeQuery($sql . $values . ";");
        $values = "";
        usleep(50); //wait for the database, so the query below is executed faster
      }
    }
    if (strlen($values)) {
      CRM_Core_DAO::executeQuery($sql . $values . ";");
      usleep(50); //wait for the database, so the query below is executed faster
    }
    
    //CRM_Core_Transaction::willCommit();
    
    $this->closeFP($fp);
    
    sleep(5); //wait for the database, so the query below is executed faster
    
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_pro6pp_import` `i` "
        . "INNER JOIN `civicrm_pro6pp_import_cbsbuurt` `cbs` ON `i`.`postcode_letter` = `cbs`.`postcode_letter` AND `i`.`postcode_nr` = `cbs`.`postcode_nr`"
        . "SET `i`.`cbs_buurtcode` = `cbs`.`cbs_buurtcode`, `i`.`cbs_buurtnaam` = `cbs`.`cbs_buurtnaam`, `i`.`cbs_wijkcode` = `cbs`.`cbs_wijkcode`;");
    
    //CRM_Core_Transaction::willCommit();
    
    return $lineNr;
  }
  
  public function copy() {
    CRM_Core_DAO::executeQuery("TRUNCATE `civicrm_postcodenl`;");
    CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_postcodenl` SELECT * FROM `civicrm_pro6pp_import`");
  }

  /**
   * Returns the filepointer to the first file in the zip archive
   * 
   * @param String $zipfile
   * @return filepointer
   * @throws CRM_Core_Exception
   */
  protected function getStreamToCSV($asset, $useMeta=true) {
    
    $temp_file = tempnam(sys_get_temp_dir(), 'pro6pp');

    if ($useMeta) {
      $json = file_get_contents($this->metaUrl . '?auth_key=' . $this->key . '&asset=' . $asset);
      $meta_data = json_decode($json);
      $zipfile = $meta_data->results->download_link;
    } else {
      $zipfile = $this->downloadUrl . '?auth_key=' . $this->key . '&asset=' . $asset;
    }

    if (!copy($zipfile, $temp_file)) {
      throw new CRM_Core_Exception("Unable to download zipfile for " . $asset . ": " . $zipfile);
    }

    $zip = new ZipArchive();
    if (!$zip->open($temp_file)) {
      throw new CRM_Core_Exception("Unable to open zipfile: " . $zipfile);
    }
    //only read first file in zip
    $name = $zip->getNameIndex(0);
    $fp = $zip->getStream($name);
    if (!$fp) {
      throw new CRM_Core_Exception("Unable to retrieve CSV from zipfile: " . $zipfile);
    }
    
    $this->fopen_utf8($fp);
    
    return $fp;
  }

  protected function closeFP($fp) {
    fclose($fp);
  }

  protected function fopen_utf8($handle) {
    $encoding = '';
    $bom = fread($handle, 2);
    rewind($handle);
    
    if ($bom === chr(0xff) . chr(0xfe)) {
      // UTF16 Byte Order Mark present
      $encoding = 'UTF-16LE';
    } 
    elseif( $bom === chr(0xfe) . chr(0xff)) {
      // UTF16 Byte Order Mark present
      $encoding = 'UTF-16';
    } else {
      $file_sample = fread($handle, 1000) + 'e'; //read first 1000 bytes
      // + e is a workaround for mb_string bug
      rewind($handle);

      $encoding = mb_detect_encoding($file_sample, 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP');
    }
    if ($encoding) {
      stream_filter_append($handle, 'convert.iconv.' . $encoding . '/UTF-8');
    }
    return ($handle);
  }

}
