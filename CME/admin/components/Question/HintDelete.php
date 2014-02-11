<?php

require_once 'Inquisition/admin/components/Question/HintDelete.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';

/**
 * Delete confirmation page for question hints
 *
 * @package   CME
 * @copyright 2013-2014 silverorange
 */
class CMEQuestionHintDelete extends InquisitionQuestionHintDelete
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
	// {{{ public function setInquisition()

	public function setInquisition(InquisitionInquisition $inquisition)
	{
		parent::setInquisition($inquisition);

		if (!$this->inquisition instanceof InquisitionInquisition) {
			// if we got here from the question index, load the inquisition
			// from the binding as we only have one inquisition per question
			$sql = 'select inquisition
				from InquisitionInquisitionQuestionBinding where question = %s';

			$sql = sprintf(
				$sql,
				$this->app->db->quote($this->question->id)
			);

			$inquisition_id = SwatDB::queryOne($this->app->db, $sql);

			$this->inquisition = $this->loadInquisition($inquisition_id);
		}

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
			$entries = $this->navbar->popEntries(5);

			array_shift($entries);
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
				$this->credit->credit_type->title
			);

		default:
		case 'quiz':
			return sprintf(
				CME::_('%s Quiz'),
				$this->credit->credit_type->title
			);
		}
	}

	// }}}
}

?>
