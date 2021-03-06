<?php
require_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.ilSelfEvaluationDataset.php');
/**
 * TableGUI ilSelfEvaluationDatasetTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id:
 *
 */
class ilSelfEvaluationDatasetTableGUI extends ilTable2GUI {

	/**
	 * @param ilSelfEvaluationDatasetGUI $a_parent_obj
	 * @param string                     $a_parent_cmd
	 * @param ilSelfEvaluationPlugin     $plugin
	 * @param int                        $obj_id
	 */
	function __construct(ilSelfEvaluationDatasetGUI $a_parent_obj, $a_parent_cmd, $plugin, $obj_id = 0) {
		global $ilCtrl, $ilTabs, $ilToolbar;
		/**
		 * @var $ilCtrl ilCtrl
		 * @var $ilTabs ilTabsGUI
		 * @var $ilToolbar ilToolbarGUI
		 */
		$this->pl = $plugin;
		$this->ctrl = $ilCtrl;
		$this->tabs = $ilTabs;
		$this->setId('');
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setTitle($this->pl->txt('dataset_table_title'));
		//
		// Columns
		$this->addColumn($this->pl->txt('identity_type'), false, '100px');
		$this->addColumn($this->pl->txt('date'), false, 'auto');
		$this->addColumn($this->pl->txt('identity'), false, 'auto');
		$this->addColumn($this->pl->txt('average_all'), false, 'auto');
		$this->addColumn($this->pl->txt('actions'), false, 'auto');
		$this->ctrl->setParameterByClass('ilSelfEvaluationDatasetGUI', 'dataset_id', NULL);
		$this->setFormAction($ilCtrl->getFormActionByClass('ilSelfEvaluationDatasetGUI'));
		$this->setRowTemplate($this->pl->getDirectory() . '/templates/default/Dataset/tpl.template_dataset_row.html');
		$ilToolbar->addButton($this->pl->txt('delete_all_datasets'), $this->ctrl->getLinkTargetByClass('ilSelfEvaluationDatasetGUI', 'confirmDeleteAll'));
        $ilToolbar->addButton($this->pl->txt('export_csv'), $this->ctrl->getLinkTargetByClass('ilSelfEvaluationDatasetGUI', 'exportCSV'));

		switch ($a_parent_cmd) {
			case 'index':
				$this->setData(ilSelfEvaluationDataset::_getAllInstancesByObjectId($obj_id, true));
				break;
		}
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		$obj = new ilSelfEvaluationDataset($a_set['id']);
		$identifier = new ilSelfEvaluationIdentity($obj->getIdentifierId());
		$this->ctrl->setParameterByClass('ilSelfEvaluationDatasetGUI', 'dataset_id', $obj->getId());
		// Row
		$this->tpl->setVariable('DATE', date('d.m.Y - H:i:s', $obj->getCreationDate()));
        $this->tpl->setVariable('EDIT_LINK', $this->ctrl->getLinkTargetByClass('ilSelfEvaluationDatasetGUI', 'show'));
		switch ($identifier->getType()) {
			case ilSelfEvaluationIdentity::TYPE_EXTERNAL:
				$this->tpl->setVariable('TYPE', $this->pl->txt('identity_type_'
					. ilSelfEvaluationIdentity::TYPE_EXTERNAL));
				$this->tpl->setVariable('IDENTITY', $identifier->getIdentifier());
				break;
			case ilSelfEvaluationIdentity::TYPE_LOGIN:
				$this->tpl->setVariable('TYPE', $this->pl->txt('identity_type_'
					. ilSelfEvaluationIdentity::TYPE_LOGIN));
				$username = ilObjUser::_lookupName($identifier->getIdentifier());
				$this->tpl->setVariable('IDENTITY', $username['login']);
				break;
		}
		$this->tpl->setVariable('PERCENTAGE', $obj->getOverallPercentage());
		$this->tpl->setVariable('ID', $obj->getId());
		// Actions
		$ac = new ilAdvancedSelectionListGUI();
		$ac->setId('dataset_' . $obj->getId());
		$ac->addItem($this->pl->txt('show_feedback'), 'show_dataset', $this->ctrl->getLinkTargetByClass('ilSelfEvaluationDatasetGUI', 'show'), true);
		$ac->addItem($this->pl->txt('delete_dataset'), 'delete_dataset', $this->ctrl->getLinkTargetByClass('ilSelfEvaluationDatasetGUI', 'confirmDelete'));
		$ac->setListTitle($this->pl->txt('actions'));
		//
		$this->ctrl->setParameterByClass('ilSelfEvaluationDatasetGUI', 'dataset_id', 0);
		$this->tpl->setVariable('ACTIONS', $ac->getHTML());
	}
}

?>