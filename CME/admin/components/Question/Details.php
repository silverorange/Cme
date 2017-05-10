<?php


/**
 * Question edit page for inquisitions
 *
 * @package   CME
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuestionDetails extends InquisitionQuestionDetails
{
	// {{{ protected properties

	/**
	 * @var CMEQuestionHelper
	 */
	protected $helper;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->helper = $this->getQuestionHelper();
		$this->helper->initInternal();

		// for evaluations, hide correct option column
		if ($this->helper->isEvaluation()) {
			$view = $this->ui->getWidget('option_view');
			$view->getColumn('correct_option')->visible = false;

			$toollink = $this->ui->getWidget('correct_option');
			$toollink->visible = false;
		}
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
	// {{{ protected function getQuestionHelper()

	protected function getQuestionHelper()
	{
		return new CMEQuestionHelper($this->app, $this->inquisition);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		// hide hints frame for CME quizzes and evaluations
		if ($this->helper->isEvaluation() || $this->helper->isQuiz()) {
			$this->ui->getWidget('hints_frame')->visible = false;
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->helper->buildNavBar($this->navbar);
	}

	// }}}
}

?>
