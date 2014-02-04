<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMEEvaluation.php';
require_once 'CME/dataobjects/CMECreditType.php';

/**
 * @package   CME
 * @copyright 2013-2014 silverorange
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

		if ($this->credit_type->shortname === 'aaem') {
			if ($this->quiz instanceof InquisitionInquisition &&
				!$account->isQuizPassed($this)) {
				// quiz needs to be completed or retaken
				$link = $this->episode->getUri(
					$time_zone,
					'/quiz/'.$this->credit_type->shortname
				);
			} elseif ($this->evaluation instanceof InquisitionInquisition &&
				!$account->isEvaluationComplete($this)) {
				// evaluation needs to be taken
				$link = $this->episode->getUri(
					$time_zone,
					'/evaluation/'.$this->credit_type->shortname
				);
			} else {
				// CME agreement needs to be completed
				$link = $this->episode->getUri($time_zone);
			}
		} else {
			if ($this->evaluation instanceof InquisitionInquisition &&
				!$account->isEvaluationComplete($this)) {
				// evaluation needs to be taken
				$link = $this->episode->getUri(
					$time_zone,
					'/evaluation/'.$this->credit_type->shortname
				);
			} elseif ($this->quiz instanceof InquisitionInquisition &&
				!$account->isQuizPassed($this)) {
				// quiz needs to be completed or retaken
				$link = $this->episode->getUri(
					$time_zone,
					'/quiz/'.$this->credit_type->shortname
				);
			} else {
				// CME agreement needs to be completed
				$link = $this->episode->getUri($time_zone);
			}
		}

		return $link;
	}

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
