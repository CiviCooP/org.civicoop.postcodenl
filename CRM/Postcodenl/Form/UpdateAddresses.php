<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Postcodenl_Form_UpdateAddresses extends CRM_Core_Form {

  function buildQuickForm() {

    $groupHierarchy = CRM_Contact_BAO_Group::getGroupsHierarchy(CRM_Core_PseudoConstant::nestedGroup(), NULL, '&nbsp;&nbsp;', TRUE);

    // add select for groups
    $group = array('' => ts('- any group -')) + $groupHierarchy;
    $this->_groupElement = &$this->addElement('select', 'group_id', ts('Group'), $group);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  function postProcess() {
    $formValues = $this->exportValues();

    $queue = CRM_Queue_Service::singleton()->create(array(
      'type' => 'Sql',
      'name' => 'org.civicoop.postcodenl.updateaddresses',
      'reset' => TRUE, //do not flush queue upon creation
    ));


    if (!empty($formValues['group_id'])) {
      $params = array(array('group', 'IN', array($formValues['group_id'] => 1), 0, 0));
    } else {
      $params = array();
    }
    //list($contacts, $_) = CRM_Contact_BAO_Query::apiQuery($params, array('contact_id'), NULL, NULL, 0, 0);
    list($contacts, $_) = CRM_Contact_BAO_Query::apiQuery($params, array('contact_id'), NULL, NULL, 0, 0, TRUE, TRUE, FALSE);
    for($i=0; $i<$contacts; $i = $i + 100) {
      $title = ts('Updating addresses %1/%2', array(
        1 => $i,
        2 => $contacts,
      ));

      //create a task without parameters
      $task = new CRM_Queue_Task(
        array(
          'CRM_Postcodenl_UpdaterTask',
          'UpdateFromQueue'
        ), //call back method
        array(serialize($params), $i, 100), //parameters,
        $title
      );
      //now add this task to the queue
      $queue->createItem($task);
    }


    $runner = new CRM_Queue_Runner(array(
      'title' => ts('Updating addresses'), //title fo the queue
      'queue' => $queue, //the queue object
      'errorMode'=> CRM_Queue_Runner::ERROR_ABORT, //abort upon error and keep task in queue
      'onEnd' => array('CRM_Postcodenl_Form_UpdateAddresses', 'onEnd'), //method which is called as soon as the queue is finished
      'onEndUrl' => CRM_Utils_System::url('civicrm', 'reset=1'), //go to page after all tasks are finished
    ));

    $runner->runAllViaWeb(); // does not return

    parent::postProcess();
  }

  static function onEnd(CRM_Queue_TaskContext $ctx) {
    //set a status message for the user
    CRM_Core_Session::setStatus('Updated addresses', '', 'success');
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
