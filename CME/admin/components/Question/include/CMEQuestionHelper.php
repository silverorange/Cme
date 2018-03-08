<?php

/**
 * @package   CME
 * @copyright 2014-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuestionHelper
{
	// {{{ protected properties

	/**
	 * @var SiteApplication
	 */
	protected $app;

	/**
	 * @var InquisitionInquisition
	 */
	protected $inquisition;

	/**
	 * @var CMECredit
	 */
	protected $credit;

	/**
	 * @var CMEFrontMatter
	 */
	protected $front_matter;

	/**
	 * @var string
	 */
	protected $type;

	// }}}
	// {{{ public function __construct()

	public function __construct(
		SiteApplication $app,
		InquisitionInquisition $inquisition
	) {
		$this->app = $app;
		$this->inquisition = $inquisition;
	}

	// }}}
	// {{{ public function isEvaluation()

	public function isEvaluation()
	{
		return ($this->type == 'evaluation');
	}

	// }}}
	// {{{ public function isQuiz()

	public function isQuiz()
	{
		return ($this->type == 'quiz');
	}

	// }}}
	// {{{ public function getInquisition()

	public function getInquisition()
	{
		return $this->inquisition;
	}

	// }}}

	// init phase
	// {{{ public function initInternal()

	public function initInternal()
	{
		$this->initCredit();
		$this->initFrontMatter();
		$this->initType();
	}

	// }}}
	// {{{ protected function initCredit()

	protected function initCredit()
	{
		if ($this->inquisition instanceof InquisitionInquisition) {
			$sql = sprintf(
				'select * from CMECredit where quiz = %s',
				$this->app->db->quote($this->inquisition->id, 'integer')
			);

			$this->credit = SwatDB::query(
				$this->app->db,
				$sql,
				SwatDBClassMap::get('CMECreditWrapper')
			)->getFirst();
		}
	}

	// }}}
	// {{{ protected function initFrontMatter()

	protected function initFrontMatter()
	{
		if ($this->inquisition instanceof InquisitionInquisition) {
			if ($this->credit instanceof CMECredit) {
				$this->front_matter = $this->credit->front_matter;
			} else {
				$sql = sprintf(
					'select * from CMEFrontMatter where evaluation = %s',
					$this->app->db->quote($this->inquisition->id, 'integer')
				);

				$this->front_matter = SwatDB::query(
					$this->app->db,
					$sql,
					SwatDBClassMap::get('CMEFrontMatterWrapper')
				)->getFirst();
			}
		}
	}

	// }}}
	// {{{ protected function initType()

	protected function initType()
	{
		if ($this->credit instanceof CMECredit) {
			$this->type = 'quiz';
		} elseif ($this->front_matter instanceof CMEFrontMatter) {
			$this->type = 'evaluation';
		}
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

		// pop inquisition title defined in Inquisition package
		$navbar->popEntry();

		// pop question component
		$navbar->popEntry();

		// add inquisition
		if ($this->isQuiz()) {
			$navbar->createEntry(
				$this->getCreditNavBarTitle(),
				sprintf(
					'Credit/Details?id=%s',
					$this->credit->id
				)
			);
		} elseif ($this->isEvaluation()) {
			$navbar->createEntry(
				$this->getEvaluationNavBarTitle(),
				sprintf(
					'Evaluation/Details?id=%s',
					$this->inquisition->id
				)
			);
		} elseif ($this->inquisition instanceof InquisitionInquisition) {
			$navbar->createEntry(
				$this->inquisition->title,
				sprintf(
					'Inquisition/Details?id=%s',
					$this->inquisition->id
				)
			);
		}

		// add back edit/add title
		$navbar->addEntry($title);
	}

	// }}}
	// {{{ protected function getCreditNavBarTitle()

	protected function getCreditNavBarTitle()
	{
		return sprintf(
			CME::_('%s Credit'),
			$this->credit->front_matter->getProviderTitleList()
		);
	}

	// }}}
	// {{{ protected function getEvaluationNavBarTitle()

	protected function getEvaluationNavBarTitle()
	{
		return sprintf(
			CME::_('%s Evaluation'),
			$this->front_matter->getProviderTitleList()
		);
	}

	// }}}
}

?>
