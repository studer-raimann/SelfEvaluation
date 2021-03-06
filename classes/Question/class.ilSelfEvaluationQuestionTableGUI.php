<?php
require_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.ilSelfEvaluationQuestion.php');
/**
 * TableGUI ilSelfEvaluationQuestionTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id:
 *
 */
class ilSelfEvaluationQuestionTableGUI extends ilTable2GUI {

	/**
	 * @param ilSelfEvaluationQuestionGUI $a_parent_obj
	 * @param string                      $a_parent_cmd
	 * @param ilSelfEvaluationBlock       $block
	 */
	function __construct(ilSelfEvaluationQuestionGUI $a_parent_obj, $a_parent_cmd, ilSelfEvaluationBlock $block) {
		global $ilCtrl, $ilTabs, $tpl;
		/**
		 * @var $ilCtrl ilCtrl
		 * @var $ilTabs ilTabsGUI
		 */
		$this->pl = new ilSelfEvaluationPlugin();
		$this->ctrl = $ilCtrl;
		$this->tabs = $ilTabs;
		$this->setId('sev_feedbacks');
		$this->block = $block;
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setTitle($block->getTitle() . ': ' . $this->pl->txt('question_table_title'));
		// Columns
		if ($this->block->isBlockSortable()) {
			$tpl->addJavaScript($this->pl->getDirectory() . '/templates/js/sortable.js');
			$this->addColumn('', 'position', '20px');
			$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
			$this->addMultiCommand('saveSorting', $this->pl->txt('save_sorting'));
			$sorting = false;
		} else {
			$sorting = true;
		}
		$this->addColumn($this->pl->txt('title'), $sorting ? 'title' : false, 'auto');
		$this->addColumn($this->pl->txt('question_body'), $sorting ? 'question_body' : false, 'auto');
		$this->addColumn($this->pl->txt('is_inverted'), $sorting ? 'is_inverted' : false, 'auto');
		$this->addColumn($this->pl->txt('actions'), $sorting ? 'actions' : false, 'auto');
		//
		// Header
		$this->ctrl->setParameterByClass('ilSelfEvaluationQuestionGUI', 'question_id', NULL);
		$this->ctrl->setParameterByClass('ilSelfEvaluationQuestionGUI', 'block_id', $block->getId());
		$this->setRowTemplate($this->pl->getDirectory() . '/templates/default/Question/tpl.template_question_row.html');
		$this->setData(ilSelfEvaluationQuestion::_getAllInstancesForParentId($block->getId(), true));
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		$obj = new ilSelfEvaluationQuestion($a_set['id']);
		$this->ctrl->setParameterByClass('ilSelfEvaluationQuestionGUI', 'question_id', $obj->getId());
		if ($this->block->isBlockSortable()) {
			$this->tpl->setVariable('ID', $obj->getId());
		}
		$this->tpl->setVariable('TITLE', $obj->getTitle() ? $obj->getTitle() :
			$this->pl->txt('question') . ' ' . $this->block->getPosition() . '.' . $obj->getPosition());
		$this->tpl->setVariable('EDIT_LINK', $this->ctrl->getLinkTargetByClass('ilSelfEvaluationQuestionGUI', 'editQuestion'));
		$this->tpl->setVariable('BODY', strip_tags($obj->getQuestionBody()));
		$this->tpl->setVariable('IS_INVERTED', $obj->getIsInverse() ? './Customizing/global/plugins/Services/Repository/RepositoryObject/SelfEvaluation/templates/images/ok.png' : './Customizing/global/plugins/Services/Repository/RepositoryObject/SelfEvaluation/templates/images/blank.png');
		// Actions
		$ac = new ilAdvancedSelectionListGUI();
		$ac->setId('question_' . $obj->getId());
		$ac->addItem($this->pl->txt('edit_question'), 'edit_question', $this->ctrl->getLinkTargetByClass('ilSelfEvaluationQuestionGUI', 'editQuestion'));
		$ac->addItem($this->pl->txt('delete_question'), 'delete_question', $this->ctrl->getLinkTargetByClass('ilSelfEvaluationQuestionGUI', 'deleteQuestion'));
		$ac->setListTitle($this->pl->txt('actions'));
		//
		$this->tpl->setVariable('ACTIONS', $ac->getHTML());
	}
}

?>