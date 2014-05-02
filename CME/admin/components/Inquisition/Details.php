<?php

require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Inquisition/admin/components/Inquisition/Details.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';

/**
 * Details page for inquisitions
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEInquisitionDetails extends InquisitionInquisitionDetails
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var CMECredit
	 */
	protected $credit;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initCredit();
		$this->initType();

		if ($this->type !== 'quiz') {
			$view = $this->ui->getWidget('details_view');
			$view->getField('passing_grade_field')->visible = false;
			$view->getField('email_content_pass_field')->visible = false;
			$view->getField('email_content_fail_field')->visible = false;
			$view->getField('resettable_field')->visible = false;
		}
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'CME/admin/components/Inquisition/details.xml';
	}

	// }}}
	// {{{ protected function initCredit()

	protected function initCredit()
	{
		$sql = sprintf(
			'select * from CMECredit
			where evaluation = %1$s or quiz = %1$s',
			$this->app->db->quote($this->inquisition->id, 'integer')
		);

		$this->credit = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		)->getFirst();
	}

	// }}}
	// {{{ protected function initType()

	protected function initType()
	{
		if ($this->id === $this->credit->getInternalValue('evaluation')) {
			$this->type = 'evaluation';
		} elseif ($this->id === $this->credit->getInternalValue('quiz')) {
			$this->type = 'quiz';
		}
	}

	// }}}

	// build phase
	// {{{ protected function getDetailsStore()

	protected function getDetailsStore(InquisitionInquisition $inquisition)
	{
		$ds = parent::getDetailsStore($inquisition);
		$ds->email_content_pass = SwatString::condense(
			$inquisition->email_content_pass
		);
		$ds->email_content_fail = SwatString::condense(
			$inquisition->email_content_fail
		);
		return $ds;
	}

	// }}}
	// {{{ protected function getTitle()

	protected function getTitle()
	{
		switch ($this->type) {
		case 'evaluation':
			return sprintf(
				CME::_('%s Evaluation'),
				$this->credit->provider->title
			);

		default:
		case 'quiz':
			return sprintf(
				CME::_('%s Quiz'),
				$this->credit->provider->title
			);
		}
	}

	// }}}
}

?>
