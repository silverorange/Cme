<?php


/**
 * @package   CME
 * @copyright 2014-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEOptionHelper
{
	// {{{ protected properties

	/**
	 * @var SiteApplication
	 */
	protected $app;

	/**
	 * @var CMEQuestionHelper
	 */
	protected $question_helper;

	/**
	 * @var InquisitionQuestion
	 */
	protected $question;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app,
		CMEQuestionHelper $question_helper,
		InquisitionQuestion $question)
	{
		$this->app = $app;
		$this->question = $question;
		$this->question_helper = $question_helper;
	}

	// }}}
	// {{{ public function isEvaluation()

	public function isEvaluation()
	{
		return $this->question_helper->isEvaluation();
	}

	// }}}
	// {{{ public function isQuiz()

	public function isQuiz()
	{
		return $this->question_helper->isQuiz();
	}

	// }}}

	// init phase
	// {{{ public function initInternal()

	public function initInternal()
	{
		$this->question_helper->initInternal();
	}

	// }}}

	// process phase
	// {{{ public function getRelocateURI()

	public function getRelocateURI()
	{
		$uri = null;

		if ($this->isQuiz()) {
			$uri = sprintf(
				'Credit/Details?id=%s',
				$this->credit->id
			);
		} elseif ($this->isEvaluation()) {
			$uri = sprintf(
				'Evaluation/Details?id=%s',
				$this->inquisition->id
			);
		}

		return $uri;
	}

	// }}}

	// build phase
	// {{{ public function buildNavBar()

	public function buildNavBar(SwatNavBar $navbar)
	{
		// save add/edit title defined in Inquisition package
		$title = $navbar->popEntry();

		$this->question_helper->buildNavBar($navbar);

		// remove question defined in Inquisition package
		$question = $navbar->popEntry();

		// add question
		$inquisition = $this->question_helper->getInquisition();
		if ($inquisition instanceof InquisitionInquisition) {
			$navbar->createEntry(
				$this->getQuestionTitle(),
				sprintf(
					'Question/Details?id=%s&inquisition=%s',
					$this->question->id,
					$inquisition->id
				)
			);
		} else {
			$navbar->createEntry(
				$this->getQuestionTitle(),
				sprintf(
					'Question/Details?id=%s',
					$this->question->id
				)
			);
		}

		// add back edit/add title
		$navbar->addEntry($title);
	}

	// }}}
	// {{{ protected function getQuestionTitle()

	protected function getQuestionTitle()
	{
		// TODO: Update this with some version of getPosition().
		return CME::_('Question');
	}

	// }}}
}

?>
