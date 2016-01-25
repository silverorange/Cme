<?php

require_once 'Inquisition/dataobjects/InquisitionResponse.php';
require_once 'CME/dataobjects/CMEAccount.php';

/**
 * An evaluation response
 *
 * @package   CME
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEEvaluationResponse extends InquisitionResponse
{
	// {{{ public function getFrontMatter()

	public function getFrontMatter()
	{
		require_once 'CME/dataobjects/CMEFrontMatterWrapper.php';

		$this->checkDB();

		$inquisition_id = $this->getInternalValue('inquisition');

		$sql = sprintf(
			'select * from CMEFrontMatter where evaluation = %s',
			$this->db->quote($inquisition_id, 'integer')
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMEFrontMatterWrapper')
		)->getFirst();
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->registerInternalProperty(
			'account',
			SwatDBClassMap::get('CMEAccount')
		);
	}

	// }}}
}

?>
