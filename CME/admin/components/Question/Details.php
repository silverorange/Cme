<?php

require_once 'Inquisition/admin/components/Question/Details.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';

/**
 * Question edit page for inquisitions
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuestionDetails extends InquisitionQuestionDetails
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

		// for evaluations, hide correct option column
		$sql = sprintf(
			'select count(1) from CMECredit
			where evaluation = %s',
			$this->app->db->quote($this->inquisition->id, 'integer')
		);

		$is_evaluation = (SwatDB::queryOne($this->app->db, $sql) > 0);

		if ($is_evaluation) {
			$view = $this->ui->getWidget('option_view');
			$view->getColumn('correct_option')->visible = false;

			$toollink = $this->ui->getWidget('correct_option');
			$toollink->visible = false;
		}

		$this->initCredit();
		$this->initType();
	}

	// }}}
	// {{{ protected function initInquisition()

	protected function initInquisition()
	{
		parent::initInquisition();

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

			$entries[1]->title = $this->getQuizTitle();

			$this->navbar->addEntry($entries[1]);
			$this->navbar->addEntry($entries[2]);
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
