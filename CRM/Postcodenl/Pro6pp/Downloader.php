<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

class CRM_Postcodenl_Pro6pp_Downloader {

  private $key;

  private $downloadUrl = 'http://api.pro6pp.nl/v1/download';

  private $metaUrl = 'https://api.pro6pp.nl/v1/download/metadata';

  public function __construct($authkey) {
    $this->key = $authkey;
  }

  /**
   * Returns the filepointer to the first file in the zip archive
   *
   * @param String $zipfile
   * @return filepointer
   * @throws CRM_Core_Exception
   */
  public function getStreamToCSV($asset, $useMeta=true, $convertToUtf8=true) {
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

    $tempDirectory = \Civi::paths()->getPath(CIVICRM_TEMPLATE_COMPILEDIR."pro6pp");
    CRM_Utils_File::createDir($tempDirectory);
    //only read first file in zip
    $name = $zip->getNameIndex(0);
    $zip->extractTo($tempDirectory);
    $fp = fopen($tempDirectory . DIRECTORY_SEPARATOR . $name, 'r');
    if (!$fp) {
      throw new CRM_Core_Exception("Unable to retrieve CSV from zipfile: " . $zipfile);
    }

    if ($convertToUtf8) {
      $this->fopen_utf8($fp);
    }

    return $fp;
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
