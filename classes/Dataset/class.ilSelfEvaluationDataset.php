<?php
require_once('class.ilSelfEvaluationData.php');
require_once(dirname(__FILE__) . '/../Question/class.ilSelfEvaluationQuestionGUI.php');
require_once(dirname(__FILE__) . '/../Question/class.ilSelfEvaluationQuestion.php');
require_once(dirname(__FILE__) . '/../Feedback/class.ilSelfEvaluationFeedback.php');
require_once(dirname(__FILE__) . '/../Scale/class.ilSelfEvaluationScale.php');
/**
 * ilSelfEvaluationDataset
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version
 */
class ilSelfEvaluationDataset {

	const TABLE_NAME = 'rep_robj_xsev_ds';
	/**
	 * @var int
	 */
	protected $id = 0;
	/**
	 * @var int
	 */
	protected $identifier_id = 0;
	/**
	 * @var int
	 */
	protected $creation_date = 0;


	/**
	 * @param $id
	 */
	function __construct($id = 0) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$this->id = $id;
		$this->db = $ilDB;
		if ($id != 0) {
			$this->read();
		}
	}


	public function read() {
		$set = $this->db->query('SELECT * FROM ' . self::TABLE_NAME . ' ' . ' WHERE id = '
			. $this->db->quote($this->getId(), 'integer'));
		while ($rec = $this->db->fetchObject($set)) {
			foreach ($this->getArrayForDb() as $k => $v) {
				$this->{$k} = $rec->{$k};
			}
		}
	}


	/**
	 * @return array
	 */
	public function getArrayForDb() {
		$e = array();
		foreach (get_object_vars($this) as $k => $v) {
			if (! in_array($k, array( 'db' ))) {
				$e[$k] = array( self::_getType($v), $this->$k );
			}
		}

		return $e;
	}


	final function initDB() {
		$fields = array();
		foreach ($this->getArrayForDb() as $k => $v) {
			$fields[$k] = array(
				'type' => $v[0],
			);
			switch ($v[0]) {
				case 'integer':
					$fields[$k]['length'] = 4;
					break;
				case 'text':
					$fields[$k]['length'] = 1024;
					break;
			}
			if ($k == 'id') {
				$fields[$k]['notnull'] = true;
			}
		}
		if (! $this->db->tableExists(self::TABLE_NAME)) {
			$this->db->createTable(self::TABLE_NAME, $fields);
			$this->db->addPrimaryKey(self::TABLE_NAME, array( 'id' ));
			$this->db->createSequence(self::TABLE_NAME);
		}
	}


	final private function resetDB() {
		$this->db->dropTable(self::TABLE_NAME);
		$this->initDB();
	}


	public function create() {
		if ($this->getId() != 0) {
			$this->update();

			return true;
		}
		$this->setId($this->db->nextID(self::TABLE_NAME));
		$this->db->insert(self::TABLE_NAME, $this->getArrayForDb());

		return true;
	}


	/**
	 * @return int
	 */
	public function delete() {
		return $this->db->manipulate('DELETE FROM ' . self::TABLE_NAME . ' WHERE id = '
			. $this->db->quote($this->getId(), 'integer'));
	}


	public function update() {
		if ($this->getId() == 0) {
			$this->create();

			return true;
		}
		$this->db->update(self::TABLE_NAME, $this->getArrayForDb(), array(
			'id' => array(
				'integer',
				$this->getId()
			),
		));

		return true;
	}


	/**
	 * @param $array
	 */
	public function saveValuesByArray($array) {
		if ($this->getId() == 0) {
			$this->create();
		}
		foreach ($array as $k => $v) {
			$da = new ilSelfEvaluationData();
			$da->setDatasetId($this->getId());
			$da->setQuestionId($k);
			$da->setValue($v);
			$da->create();
		}
	}


	/**
	 * @param $post
	 */
	public function saveValuesByPost($post) {
		$data = array();
		foreach ($post as $k => $v) {
			$qid = str_replace(ilSelfEvaluationQuestionGUI::POSTVAR_PREFIX, '', $k);
			if (ilSelfEvaluationQuestion::_isObject($qid)) {
				$data[$qid] = $v;
			}
		}
		$this->saveValuesByArray($data);
	}


	/**
	 * @param $array
	 */
	public function updateValuesByArray($array) {
		foreach ($array as $k => $v) {
			$da = ilSelfEvaluationData::_getInstanceForQuestionId($this->getId(), $k);
			$da->setValue($v);
			$da->update();
		}
	}


	/**
	 * @param $post
	 */
	public function updateValuesByPost($post) {
		$data = array();
		foreach ($post as $k => $v) {
			$qid = str_replace(ilSelfEvaluationQuestionGUI::POSTVAR_PREFIX, '', $k);
			if (ilSelfEvaluationQuestion::_isObject($qid)) {
				$data[$qid] = $v;
			}
		}
		$this->updateValuesByArray($data);
	}


	/**
	 * @param $block_id
	 *
	 * @return mixed
	 */
	public function getDataPerBlock($block_id) {
		$sum = array();
		foreach (ilSelfEvaluationQuestion::_getAllInstancesForParentId($block_id) as $qst) {
			$da = ilSelfEvaluationData::_getInstanceForQuestionId($this->getId(), $qst->getId());
			$sum[$qst->getId()] = $da->getValue();
		}

		return $sum;
	}


	/**
	 * @return array
	 */
	public function getPercentagePerBlock() {
		$obj_id = ilSelfEvaluationIdentity::_getObjIdForIdentityId($this->getIdentifierId());
		$return = array();
		foreach (ilSelfEvaluationBlock::_getAllInstancesByParentId($obj_id) as $block) {
			$sum = array();
			foreach (ilSelfEvaluationQuestion::_getAllInstancesForParentId($block->getId()) as $qst) {
				$sum[] = $this->getDataPerBlock($block->getId());
				unset($qst);
			}
			$possible = count($sum) * count(ilSelfEvaluationScale::_getInstanceByRefId($obj_id)->getUnitsAsArray());
			$percentage = array_sum($sum) / $possible * 100;
			$return[$block->getId()] = $percentage;
		}

		return $return;
	}


	/**
	 * @param null $a_block_id
	 *
	 * @return array
	 */
	public function getFeedbacksPerBlock($a_block_id = NULL) {
		$return = array();
		foreach ($this->getPercentagePerBlock() as $block_id => $percentage) {
			$return[$block_id] = ilSelfEvaluationFeedback::_getFeedbackForPercentage($block_id, $percentage);;
		}
		if ($a_block_id) {
			return $return[$a_block_id];
		} else {
			return $return;
		}
	}


	//
	// Static
	//
	/**
	 * @param $identifier_id
	 *
	 * @return ilSelfEvaluationDataset[]
	 */
	public static function _getAllInstancesByIdentifierId($identifier_id) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$return = array();
		$set = $ilDB->query('SELECT * FROM ' . self::TABLE_NAME . ' ' . ' WHERE identifier_id = '
			. $ilDB->quote($identifier_id, 'integer') . ' ORDER BY creation_date ASC');
		while ($rec = $ilDB->fetchObject($set)) {
			$return[] = new self($rec->id);
		}

		return $return;
	}


	/**
	 * @param $identifier_id
	 *
	 * @return bool|ilSelfEvaluationDataset
	 */
	public static function _getInstanceByIdentifierId($identifier_id) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$set = $ilDB->query('SELECT * FROM ' . self::TABLE_NAME . ' ' . ' WHERE identifier_id = '
			. $ilDB->quote($identifier_id, 'integer'));
		while ($rec = $ilDB->fetchObject($set)) {
			return new self($rec->id);
		}

		return false;
	}


	/**
	 * @param $identifier_id
	 *
	 * @return ilSelfEvaluationDataset
	 */
	public static function _getNewInstanceForIdentifierId($identifier_id) {
		$obj = new self();
		$obj->setIdentifierId($identifier_id);
		$obj->setCreationDate(time());

		return $obj;
	}


	/**
	 * @param $identifier_id
	 *
	 * @return bool
	 */
	public static function _datasetExists($identifier_id) {
		global $ilDB;
		$set = $ilDB->query('SELECT * FROM ' . self::TABLE_NAME . ' ' . ' WHERE identifier_id = '
			. $ilDB->quote($identifier_id, 'integer'));
		while ($rec = $ilDB->fetchObject($set)) {
			return true;
		}

		return false;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $identifier_id
	 */
	public function setIdentifierId($identifier_id) {
		$this->identifier_id = $identifier_id;
	}


	/**
	 * @return int
	 */
	public function getIdentifierId() {
		return $this->identifier_id;
	}


	/**
	 * @param int $creation_date
	 */
	public function setCreationDate($creation_date) {
		$this->creation_date = $creation_date;
	}


	/**
	 * @return int
	 */
	public function getCreationDate() {
		return $this->creation_date;
	}


	//
	// Helper
	//
	/**
	 * @param $var
	 *
	 * @return string
	 */
	public static function _getType($var) {
		switch (gettype($var)) {
			case 'string':
			case 'array':
			case 'object':
				return 'text';
			case 'NULL':
			case 'boolean':
				return 'integer';
			default:
				return gettype($var);
		}
	}
}

?>
