<?php
require_once('class.ilSelfEvaluationScaleUnit.php');
/**
 * ilSelfEvaluationScale
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version
 */
class ilSelfEvaluationScale {

	const TABLE_NAME = 'rep_robj_xsev_scale';
	/**
	 * @var int
	 */
	protected $id = 0;
	/**
	 * @var int
	 */
	protected $parent_id = 0;
	/**
	 * @var int
	 */
	protected $amount = 0;


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
		//		$this->initDB();
		if ($id != 0) {
			$this->read();
		}
		$this->units = ilSelfEvaluationScaleUnit::_getAllInstancesByParentId($this->getId());
	}


	/**
	 * @param bool $flipped
	 *
	 * @return array
	 */
	public function getUnitsAsArray($flipped = false) {
		$return = array();
		foreach ($this->units as $k => $u) {
			if ($flipped) {
				$return[$this->units[count($this->units) - $k - 1]->getValue()] = $u->getTitle();
			} else {
				$return[$u->getValue()] = $u->getTitle();
			}
		}

		return $return;
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
			if (! in_array($k, array( 'db', 'units' ))) {
				$e[$k] = array( self::_getType($v), $this->$k );
			}
		}

		return $e;
	}


	final function initDB() {
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


	/**
	 * @return bool
	 */
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
		$this->db->manipulate('DELETE FROM ' . self::TABLE_NAME . ' WHERE id = '
		. $this->db->quote($this->getId(), 'integer'));

		return true;
	}


	public function update() {
		$this->db->update(self::TABLE_NAME, $this->getArrayForDb(), array(
			'id' => array(
				'integer',
				$this->getId()
			),
		));
	}


	//
	// Static
	//
	/**
	 * @param $parent_id
	 *
	 * @return ilSelfEvaluationScale
	 */
	public static function _getInstanceByRefId($parent_id) {
		global $ilDB;
		// Existing Object
		$set = $ilDB->query("SELECT * FROM " . self::TABLE_NAME . " " . " WHERE parent_id = "
		. $ilDB->quote($parent_id, "integer"));
		while ($rec = $ilDB->fetchObject($set)) {
			return new self($rec->id);
		}
		$obj = new self();
		$obj->setParentId($parent_id);

		return $obj;
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
	 * @param int $amount
	 */
	public function setAmount($amount) {
		$this->amount = $amount;
	}


	/**
	 * @return int
	 */
	public function getAmount() {
		return $this->amount;
	}


	/**
	 * @param int $parent_id
	 */
	public function setParentId($parent_id) {
		$this->parent_id = $parent_id;
	}


	/**
	 * @return int
	 */
	public function getParentId() {
		return $this->parent_id;
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
