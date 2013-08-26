<?php
require_once('class.ilObjSelfEvaluationGUI.php');
require_once('class.ilSelfEvaluationBlock.php');
require_once('class.ilSelfEvaluationQuestionTableGUI.php');
require_once('class.ilSelfEvaluationQuestion.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
/**
 * GUI-Class ilSelfEvaluationBlockGUI
 *
 * @author            Fabian Schmid <fabian.schmid@ilub.unibe.ch>
 * @version           $Id:
 *
 * @ilCtrl_Calls      ilSelfEvaluationBlockGUI:  ilObjSelfEvaluationGUI
 * @ilCtrl_IsCalledBy ilSelfEvaluationBlockGUI:  ilObjSelfEvaluationGUI
 */
class ilSelfEvaluationBlockGUI {

	/**
	 * @var ilTabsGUI
	 */
	protected $tabs_gui;
	/**
	 * @var ilSelfEvaluationBlock
	 */
	public $object;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;


	function __construct(ilObjSelfEvaluationGUI $parent, $block_id = 0) {
		global $tpl, $ilCtrl;
		/**
		 * @var $tpl    ilTemplate
		 * @var $ilCtrl ilCtrl
		 */
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent = $parent;
		$this->tabs_gui = $this->parent->tabs_gui;
		$this->pl = new ilSelfEvaluationPlugin();
		$this->object = new ilSelfEvaluationBlock($block_id ? $block_id : $_GET['block_id']);
	}


	public function executeCommand() {
		$cmd = ($this->ctrl->getCmd()) ? $this->ctrl->getCmd() : $this->getStandardCommand();
		$this->ctrl->saveParameter($this, 'position');
		$this->ctrl->saveParameter($this, 'block_id');
		$this->tabs_gui->setTabActive('administration');
		switch ($cmd) {
			default:
				$this->performCommand($cmd);
				break;
		}

		return true;
	}


	/**
	 * @return string
	 */
	public function getStandardCommand() {
		return 'addNew';
	}


	/**
	 * @param $cmd
	 */
	function performCommand($cmd) {
		switch ($cmd) {
			case 'addBlock':
			case 'createObject':
			case 'editBlock':
			case 'updateObject':
			case 'deleteBlock':
			case 'deleteObject':
			case 'editQuestions':
				//				$this->parent->checkPermission('write');
				$this->$cmd();
				break;
			case 'cancel':
				//				$this->parent->checkPermission('read');
				$this->$cmd();
				break;
		}
	}


	public function addBlock() {
		$this->initForm();
		$this->tpl->setContent($this->form->getHTML());
	}


	public function cancel() {
		$this->ctrl->redirectByClass('ilSelfEvaluationAdministrationGUI');
	}


	public function initForm($mode = 'create') {
		$this->form = new  ilPropertyFormGUI();
		$this->form->setTitle($this->pl->txt($mode . '_block'));
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		$this->form->addCommandButton($mode . 'Object', $this->pl->txt($mode . '_block_button'));
		$this->form->addCommandButton('cancel', $this->pl->txt('cancel'));
		$te = new ilTextInputGUI($this->pl->txt('title'), 'title');
		$te->setRequired(true);
		$this->form->addItem($te);
		$te = new ilTextAreaInputGUI($this->pl->txt('description'), 'description');
		$this->form->addItem($te);
	}


	public function createObject() {
		$this->initForm();
		if ($this->form->checkInput()) {
			$this->object->setTitle($this->form->getInput('title'));
			$this->object->setDescription($this->form->getInput('description'));
			$this->object->setPosition($_GET['position']);
			$this->object->setParentId($this->parent->object->getId());
			$this->object->create();
			ilUtil::sendSuccess($this->pl->txt('msg_block_created'));
			$this->cancel();
		}
	}


	public function editBlock() {
		$this->initForm('update');
		$this->setObjectValues();
		$this->tpl->setContent($this->form->getHTML());
	}


	public function setObjectValues() {
		$values['title'] = $this->object->getTitle();
		$values['description'] = $this->object->getDescription();
		$this->form->setValuesByArray($values);
	}


	public function updateObject() {
		$this->initForm();
		$this->form->setValuesByPost();
		if ($this->form->checkInput()) {
			$this->object->setTitle($this->form->getInput('title'));
			$this->object->setDescription($this->form->getInput('description'));
			$this->object->update(false);
			ilUtil::sendSuccess($this->pl->txt('msg_block_created'));
			$this->cancel();
		}
	}


	public function deleteBlock() {
		ilUtil::sendQuestion($this->pl->txt('qst_delete_block'));
		$conf = new ilConfirmationGUI();
		$conf->setFormAction($this->ctrl->getFormAction($this));
		$conf->setCancel($this->pl->txt('cancel'), 'cancel');
		$conf->setConfirm($this->pl->txt('delete_block'), 'deleteObject');
		$conf->addItem('block_id', $this->object->getId(), $this->object->getTitle());
		$this->tpl->setContent($conf->getHTML());
	}


	public function deleteObject() {
		ilUtil::sendSuccess($this->pl->txt('msg_block_deleted'), true);
		$this->object->delete();
		$this->cancel();
	}


	public function editQuestions() {
		$table = new ilSelfEvaluationQuestionTableGUI($this, 'editQuestions', $this->object);
		$this->tpl->setContent($table->getHTML());
	}

	public function setParentForm() {

}

	public function getBLockHTML() {
		$block_tpl = $this->pl->getTemplate('tpl.block.html');
		$block_tpl->setVariable('BLOCK_TITLE', $this->object->getTitle());
		$block_tpl->setVariable('BLOCK_DESCRIPTION', $this->object->getDescription());
		//Questions
		$form = new ilPropertyFormGUI();
		$qst_gui = new ilRadioMatrixInputGUI($this->object->getTitle(), 'block_' . $this->object->getId());
		$qst_gui->setOptions(array(array(1=>'lorem', 2=>'ipüsum'), array(1=>'lorem', 2=>'ipüsum')));
		//foreach(ilSelfEvaluationQuestion::_getAllInstancesForParentId($this->object->getId()) as $qst) {

		//}

		$form->addItem($qst_gui);

		return $form->getHTML();
		//		foreach
		//		return $block_tpl->get();
	}
}

?>