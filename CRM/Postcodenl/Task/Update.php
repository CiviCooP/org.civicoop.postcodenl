<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Postcodenl_Task_Update extends CRM_Contact_Form_Task {

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Update addresses'));
    $this->addDefaultButtons(ts('Update addresses'));
  }

  public function postProcess() {
    $queue = CRM_Queue_Service::singleton()->create(array(
      'type' => 'Sql',
      'name' => 'org.civicoop.postcodenl.updateaddresses',
      'reset' => TRUE, //do not flush queue upon creation
    ));
    $i = 1;
    $total = count($this->_contactIds);
    foreach($this->_contactIds as $contactId) {
      $title = ts('Updating addresses %1/%2', array(
        1 => $i,
        2 => $total,
      ));

      //create a task without parameters
      $task = new CRM_Queue_Task(
        array(
          'CRM_Postcodenl_UpdaterTask',
          'UpdateFromQueueByContactId'
        ), //call back method
        array($contactId), //parameters,
        $title
      );
      //now add this task to the queue
      $queue->createItem($task);
      $i++;
    }

    $session = CRM_Core_Session::singleton();
    $url = $session->readUserContext();

    $runner = new CRM_Queue_Runner(array(
      'title' => ts('Updating addresses'), //title fo the queue
      'queue' => $queue, //the queue object
      'errorMode'=> CRM_Queue_Runner::ERROR_ABORT, //abort upon error and keep task in queue
      'onEnd' => array('CRM_Postcodenl_Task_Update', 'onEnd'), //method which is called as soon as the queue is finished
      'onEndUrl' => $url,
    ));

    $runner->runAllViaWeb(); // does not return

    parent::postProcess();
  }

  static function onEnd(CRM_Queue_TaskContext $ctx) {
    //set a status message for the user
    CRM_Core_Session::setStatus('Updated addresses', '', 'success');
  }
}