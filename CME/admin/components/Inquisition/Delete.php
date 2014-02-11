<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Inquisition/admin/components/Inquisition/Delete.php';
require_once 'Inquisition/dataobjects/InquisitionInquisitionWrapper.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';

/**
 * Delete page for inquisitions
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEInquisitionDelete extends InquisitionInquisitionDelete
{
	// {{{ protected properties

	/**
	 * @var CMECredit
	 */
	protected $credit;

	/**
	 * @var string
	 */
	protected $type;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initCredit();
		$this->initType();
	}

	// }}}
	// {{{ protected function initType()

	protected function initType()
	{
		$id = intval($this->getFirstItem());

		if ($id === $this->credit->getInternalValue('evaluation')) {
			$this->type = 'evaluation';
		} elseif ($id === $this->credit->getInternalValue('quiz')) {
			$this->type = 'quiz';
		}
	}

	// }}}
	// {{{ protected function initCredit()

	protected function initCredit()
	{
		$sql = sprintf('select * from CMECredit where
			evaluation = %1$s or quiz = %1$s',
			$this->app->db->quote($this->inquisition->id, 'integer')
		);

		$this->credit = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		)->getFirst();
	}

	// }}}

	// build phase
	// {{{ protected function getTitle()

	protected function getTitle()
	{
		switch ($this->type) {
		case 'evaluation':
			return CME::_('Evaluation');

		default:
		case 'quiz':
			return CME::_('Quiz');
		}
	}

	// }}}
}

?>
