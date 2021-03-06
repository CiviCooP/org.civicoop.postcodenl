<?php

/**
 * Collection of upgrade steps
 */
class CRM_Postcodenl_Upgrader extends CRM_Postcodenl_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed
   */
  public function install() {
    $this->executeSqlFile('sql/install.sql');
  }

  public function upgrade_1001() {
    $this->ctx->log->info('Applying update 1001');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_1001.sql');
    return TRUE;
  }

  public function upgrade_1002() {
    $this->executeCustomDataFile('xml/auto_install.xml');
    return true;
  }

  public function upgrade_1003() {
    $this->executeSqlFile('sql/upgrade_1003.sql');
    return true;
  }

  public function upgrade_1004() {
    $this->executeCustomDataFile('xml/auto_install.xml');
    return TRUE;
  }

  public function upgrade_1005() {
    CRM_Core_DAO::executeQuery("
      update civicrm_value_adresgegevens_12 ag
      inner join
	      ( select provincie collate utf8_general_ci as provincie, gemeente collate utf8_general_ci as gemeente
	        from civicrm_postcodenl group by gemeente
        ) as p on ag.gemeente_24 = p.gemeente
      SET ag.provincie_28 = p.provincie;
    ");
    return true;
  }

  public function upgrade_1006() {
    $this->executeSqlFile('sql/upgrade_1006.sql');
    return true;
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled
   */
  public function uninstall() {
    $this->removeCustomGroup('Adresgegevens');
   $this->executeSqlFile('sql/uninstall.sql');
  }

  protected function removeCustomGroup($group_name) {
    $gid = civicrm_api3('CustomGroup', 'getValue', array('return' => 'id', 'name' => $group_name));
    if ($gid) {
      civicrm_api3('CustomGroup', 'delete', array('id' => $gid));
    }
  }

  /**
   * Example: Run a simple query when a module is enabled
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
