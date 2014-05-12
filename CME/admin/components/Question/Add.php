<?php

require_once 'Inquisition/admin/components/Question/Add.php';
require_once 'CME/CME.php';
require_once 'CME/admin/components/Question/include/CMEQuestionHelper.php';

/**
 * Question edit page for inquisitions
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEQuestionAdd extends InquisitionQuestionAdd
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
		$this->helper->initInternal($this->inquisition);

		// for evaluations, hide correct option column
		if ($this->helper->isEvaluation()) {
			$view = $this->ui->getWidget('question_option_table_view');
			$correct_column = $view->getColumn('correct_option');
			$correct_column->visible = false;
		}
	}

	// }}}
	// {{{ abstract protected function getQuestionHelper()

	abstract protected function getQuestionHelper();

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->helper->buildNavBar($this->navbar);
	}

	// }}}
}

?>
