<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * @package   CME
 * @copyright 2013-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEProvider extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var string
	 */
	public $shortname;

	/**
	 * @var string
	 */
	public $title;

	// }}}
	// {{{ public function loadByShortname()

	public function loadByShortname($shortname)
	{
		$this->checkDB();

		$row = null;

		if ($this->table !== null) {
			$sql = sprintf(
				'select * from %s where shortname = %s',
				$this->table,
				$this->db->quote($shortname, 'text')
			);

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row === null) {
			return false;
		}

		$this->initFromRow($row);
		$this->generatePropertyHashes();

		return true;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'CMEProvider';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
