<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Inquisition/dataobjects/InquisitionInquisition.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';
require_once 'CME/dataobjects/CMEFrontMatterWrapper.php';

/**
 * @package   Rap
 * @copyright 2014 silverorange
 */
abstract class CMEQuestionHelper
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

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
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

	// init phase
	// {{{ public function initInternal()

	public function initInternal(InquisitionInquisition $inquisition = null)
	{
		$this->inquisition = $inquisition;
		$this->initCredit($inquisition);
		$this->initFrontMatter($inquisition);
		$this->initType($inquisition);
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
		// save add/edit title defined in Building package
		$title = $navbar->popEntry();

		// pop question component
		$navbar->popEntry();

		// pop inquisition title defined in Inquisition package
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
	// {{{ abstract protected function getCreditNavBarTitle()

	abstract protected function getCreditNavBarTitle();

	// }}}
	// {{{ abstract protected function getEvaluationNavBarTitle()

	protected function getEvaluationNavBarTitle()
	{
		return sprintf(
			CME::_('%s Evaluation'),
			$this->front_matter->provider->title
		);
	}

	// }}}
}