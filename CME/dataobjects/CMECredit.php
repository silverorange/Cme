<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMEEvaluation.php';
require_once 'CME/dataobjects/CMECreditType.php';

/**
 * @package   CME
 * @copyright 2013-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMECredit extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var float
	 */
	public $hours;

	/**
	 * @var string
	 */
	public $objectives;

	/**
	 * @var string
	 */
	public $planning_committee_no_disclosures;

	/**
	 * @var string
	 */
	public $support_staff_no_disclosures;

	/**
	 * @var SwatDate
	 */
	public $review_date;

	/**
	 * @var boolean
	 */
	public $enabled;

	// }}}
	// {{{ public function getActionLink()

	public function getActionLink(Account $account, DateTimeZone $time_zone)
	{
		$link = null;

		if ($this->quiz instanceof InquisitionInquisition &&
			!$account->isQuizPassed($this)) {
			$link = $this->getQuizLink();
		} elseif ($this->evaluation instanceof InquisitionInquisition &&
			!$account->isEvaluationComplete($this)) {
			$link = $this->getEvaluationLink();
		} else {
			$link = $this->getCMEDisclosureLink();
		}

		return $link;
	}

	// }}}
	// {{{ abstract protected function getCMEDisclosureLink()

	abstract protected function getCMEDisclosureLink();

	// }}}
	// {{{ abstract protected function getEvaluationLink()

	abstract protected function getEvaluationLink();

	// }}}
	// {{{ abstract protected function getQuizLink()

	abstract protected function getQuizLink();

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'CMECredit';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty(
			'credit_type',
			SwatDBClassMap::get('CMECreditType')
		);

		$this->registerInternalProperty(
			'quiz',
			SwatDBClassMap::get('CMEQuiz')
		);

		$this->registerInternalProperty(
			'evaluation',
			SwatDBClassMap::get('CMEEvaluation')
		);

		$this->registerDateProperty('review_date');
	}

	// }}}
}

?>
