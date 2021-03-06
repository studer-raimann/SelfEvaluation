<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2014 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/
require_once(dirname(__FILE__) . '/Table/class.ilSelfEvaluationBlockTableGUI.php');
require_once(dirname(__FILE__) . '/class.ilSelfEvaluationBlockFactory.php');

/**
 * Class ilSelfEvaluationListBlocksGUI
 *
 * @ilCtrl_isCalledBy ilSelfEvaluationListBlocksGUI: ilObjSelfEvaluationGUI
 *
 * @author  Fabio Heer <fabio.heer@ilub.unibe.ch>
 * @version $Id$
 */
class ilSelfEvaluationListBlocksGUI {

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilObjSelfEvaluationGUI
	 */
	protected $parent;

    /**
     * @var ilToolbarGUI
     */
    protected $toolbar;


	/**
	 * @param ilObjSelfEvaluationGUI $parent
	 */
	public function __construct(ilObjSelfEvaluationGUI $parent) {
		global $ilCtrl,$ilToolbar;
        /**
         * @var $ilCtrl    ilCtrl
         * @var $ilToolbar ilToolbarGUI
         */
		$this->ctrl = $ilCtrl;
		$this->parent = $parent;
        $this->toolbar = $ilToolbar;
	}


	public function executeCommand() {
		$cmd = ($this->ctrl->getCmd()) ? $this->ctrl->getCmd() : $this->getStandardCommand();
		$this->ctrl->saveParameter($this, 'block_id');
		$this->parent->tabs_gui->setTabActive('administration');
		switch ($cmd) {
			default:
				$this->performCommand($cmd);
				break;
		}

		return true;
	}


	/**
	 * @param $cmd
	 */
	function performCommand($cmd) {
		switch ($cmd) {
			case 'showContent':
			case 'saveSorting':
			case 'editOverall':
			    $this->parent->permissionCheck('write');
				$this->$cmd();
				break;
		}
	}


	/**
	 * @return string
	 */
	public function getStandardCommand() {
		return 'showContent';
	}


	public function showContent() {
		global $tpl;

		$tpl->addJavaScript($this->parent->getPluginObject()->getDirectory() . '/templates/js/sortable.js');
		$table = new ilSelfEvaluationBlockTableGUI($this->parent, 'showContent');

		$this->ctrl->setParameterByClass('ilSelfEvaluationQuestionBlockGUI', 'block_id', NULL);
        $this->toolbar->addButton($this->txt('add_new_question_block'),$this->ctrl->getLinkTargetByClass('ilSelfEvaluationQuestionBlockGUI', 'addBlock'));

		$this->ctrl->setParameterByClass('ilSelfEvaluationMetaBlockGUI', 'block_id', NULL);
        $this->toolbar->addButton($this->txt('add_new_meta_block'),$this->ctrl->getLinkTargetByClass('ilSelfEvaluationMetaBlockGUI', 'addBlock'));

        $this->ctrl->setParameterByClass('ilSelfEvaluationFeedbackGUI', 'parent_overall', 1);
        $this->toolbar->addButton($this->txt('edit_overal_feedback'),$this->ctrl->getLinkTargetByClass('ilSelfEvaluationFeedbackGUI', 'listObjects'));
        $this->ctrl->setParameterByClass('ilSelfEvaluationFeedbackGUI', 'parent_overall', 0);

        $factory = new ilSelfEvaluationBlockFactory($this->getSelfEvalId());
		$blocks = $factory->getAllBlocks();

		$table_data = array();
		foreach ($blocks as $block) {
			$table_data[] = $block->getBlockTableRow()->toArray();
		}

		$table->setData($table_data);
		$tpl->setContent($table->getHTML());
	}


	public function saveSorting() {
		$factory = new ilSelfEvaluationBlockFactory($this->getSelfEvalId());
		$blocks = $factory->getAllBlocks();
		$positions = $_POST['position'];
		foreach ($blocks as $block) {
			$position = (int)array_search($block->getPositionId(), $positions) + 1;
			if ($position) {
				$block->setPosition($position);
				$block->update();
			}
		}

		ilUtil::sendSuccess($this->txt('sorting_saved'), true);
		$this->ctrl->redirect($this, 'showContent');
	}

    public function editOverall() {
	    global $DIC;

        $DIC['tpl']->setContent("hello World");

    }

	/**
	 * @return int
	 */
	protected function getSelfEvalId() {
		return $this->parent->object->getId();
	}


	/**
	 * @param $lng_var
	 *
	 * @return string
	 */
	protected function txt($lng_var) {
		return $this->parent->getPluginObject()->txt($lng_var);
	}
}