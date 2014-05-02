<?php

require_once 'Inquisition/admin/components/Question/Delete.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';

/**
 * Delete confirmation page for question images
 *
 * @package   CME
 * @copyright 2012-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuestionDelete extends InquisitionQuestionDelete
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

	// helper methods
	// {{{ public function setId()

	public function setId($id)
	{
		parent::setId($id);

		$this->initCredit();
		$this->initType();
	}

	// }}}
	// {{{ protected function initCredit()

	protected function initCredit()
	{
		$sql = sprintf(
			'select * from CMECredit where
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
	// {{{ protected function initType()

	protected function initType()
	{
		$evaluation_id = $this->credit->getInternalValue('evaluation');
		$quiz_id       = $this->credit->getInternalValue('quiz');

		if ($this->inquisition->id === $evaluation_id) {
			$this->type = 'evaluation';
		} elseif ($this->inquisition->id === $quiz_id) {
			$this->type = 'quiz';
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if ($this->credit instanceof CMECredit) {
			$entries = $this->navbar->popEntries(3);

			array_shift($entries);
			$entries[0]->title = $this->getQuizTitle();

			$this->navbar->addEntries($entries);
		}
	}

	// }}}
	// {{{ protected function getQuizTitle()

	protected function getQuizTitle()
	{
		switch ($this->type) {
		case 'evaluation':
			return sprintf(
				CME::_('% Evaluation'),
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
