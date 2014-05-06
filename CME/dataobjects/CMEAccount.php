<?php

require_once 'Store/dataobjects/StoreAccount.php';
require_once 'CME/dataobjects/CMECredit.php';
require_once 'CME/dataobjects/CMEEvaluation.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMEQuizResponse.php';

/**
 * CME specific Account object
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEAccount extends StoreAccount
{
	// {{{ abstract public function hasCMEAccess()

	abstract public function hasCMEAccess();

	// }}}
	// {{{ public function hasCMEAttested()

	public function hasAttested(CMEFrontMatter $front_matter)
	{
		$this->checkDB();

		$sql = sprintf(
			'select count(1) from AccountAttestedCMEFrontMatter
			where account = %s and front_matter = %s',
			$this->db->quote($this->id, 'integer'),
			$this->db->quote($front_matter->id, 'integer')
		);

		return (SwatDB::queryOne($this->db, $sql) > 0);
	}

	// }}}
	// {{{ public function isEvaluationComplete()

	public function isEvaluationComplete(CMEFrontMatter $front_matter)
	{
		$complete = false;

		$evaluation = $front_matter->evaluation;
		if ($evaluation instanceof CMEEvaluation) {
			$evaluation_response = $evaluation->getResponseByAccount($this);
			$complete = ($evaluation_response instanceof InquisitionResponse &&
				$evaluation_response->complete_date instanceof SwatDate);
		}

		return $complete;
	}

	// }}}
	// {{{ public function isQuizComplete()

	public function isQuizComplete(CMECredit $credit)
	{
		$complete = false;

		if ($credit->quiz instanceof CMEQuiz) {
			$quiz_response = $credit->quiz->getResponseByAccount($this);
			$complete = ($quiz_response instanceof CMEQuizResponse &&
				$quiz_response->complete_date instanceof SwatDate);
		}

		return $complete;
	}

	// }}}
	// {{{ public function isQuizPassed()

	public function isQuizPassed(CMECredit $credit)
	{
		$passed = false;

		if ($this->isQuizComplete($credit)) {
			$quiz_response = $credit->quiz->getResponseByAccount($this);
			$passed = $quiz_response->isPassed();
		}

		return $passed;
	}

	// }}}
	// {{{ public function getEarnedCMECreditHours()

	public function getEarnedCMECreditHours()
	{
		$hours = 0;

		foreach ($this->earned_cme_credits as $earned_credit) {
			$hours += $earned_credit->credit->hours;
		}

		return $hours;
	}

	// }}}
	// {{{ public function getEnabledEarnedCMECreditHours()

	public function getEnabledEarnedCMECreditHours(SwatDate $start_date = null,
		SwatDate $end_date = null)
	{
		$hours = 0;

		foreach ($this->earned_cme_credits as $earned_credit) {
			if ($earned_credit->credit->front_matter->enabled) {
				$hours += $earned_credit->credit->hours;
			}
		}

		return $hours;
	}

	// }}}
	// {{{ public function getAvailableCMECredits()

	public function getAvailableCMECredits()
	{
		require_once 'CME/dataobjects/CMECreditWrapper.php';

		$this->checkDB();

		if ($this->hasCMEAccess()) {
			$sql = sprintf(
				'select CMECredit.*
				from CMECredit
					inner join CMEFrontMatter
						on CMECredit.front_matter = CMEFrontMatter.id
				where CMECredit.hours > 0
					and CMEFrontMatter.enabled = %s
					and CMECredit.id not in (
						select credit from AccountEarnedCMECredit
						where account = %s
					)
				order by CMEFrontMatter.provider, CMECredit.displayorder',
				$this->db->quote(true, 'boolean'),
				$this->db->quote($this->id, 'integer')
			);

			$available_credits = SwatDB::query(
				$this->db,
				$sql,
				$wrapper
			);
		} else {
			$wrapper = SwatDBClassMap::get('CMECreditWrapper');
			$available_credits = new $wrapper();
		}

		$available_credits->setDatabase($this->db);
		return $available_credits;
	}

	// }}}

	// loader methods
	// {{{ protected function loadEarnedCMECredits()

	protected function loadEarnedCMECredits()
	{
		require_once 'CME/dataobjects/CMEAccountEarnedCMECreditWrapper.php';
		require_once 'CME/dataobjects/CMECreditWrapper.php';
		require_once 'CME/dataobjects/CMEFrontMatterWrapper.php';
		require_once 'CME/dataobjects/CMEProviderWrapper.php';

		$sql = sprintf(
			'select AccountEarnedCMECredit.* from AccountEarnedCMECredit
				inner join CMECredit
					on AccountEarnedCMECredit.credit = CMECredit.id
				inner join CMEFrontMatter
					on CMECredit.front_matter = CMEFrontMatter.id
			where account = %s
			order by CMEFrontMatter.provider, CMECredit.displayorder',
			$this->db->quote($this->id, 'integer')
		);

		$earned_credits = SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMEAccountEarnedCMECreditWrapper')
		);

		foreach ($earned_credits as $earned_credit) {
			$earned_credit->account = $this;
		}

		$credits = $earned_credits->loadAllSubDataObjects(
			'credit',
			$this->db,
			'select * from CMECredit where id in (%s)',
			SwatDBClassMap::get('CMECreditWrapper')
		);

		$front_matters = $credits->loadAllSubDataObjects(
			'front_matter',
			$this->db,
			'select * from CMEFrontMatter where id in(%s)',
			SwatDBClassMap::get('CMEFrontMatterWrapper')
		);

		$providers = $front_matters->loadAllSubDataObjects(
			'provider',
			$this->db,
			'select * from CMEProvider where id in(%s)',
			SwatDBClassMap::get('CMEProviderWrapper')
		);

		return $earned_credits;
	}

	// }}}
	// {{{ protected function loadAttestedCMECredits()

	protected function loadAttestedCMECredits()
	{
		require_once 'CME/dataobjects/CMECreditWrapper.php';
		require_once 'CME/dataobjects/CMEFrontMatterWrapper.php';
		require_once 'CME/dataobjects/CMEProviderWrapper.php';

		$sql = sprintf(
			'select CMECredit.* from CMECredit
				inner join CMEFrontMatter
					on CMECredit.front_matter = CMEFrontMatter.id
				inner join AccountAttestedCMEFrontMatter on
					CMEFrontMatter.id =
						AccountAttestedCMEFrontMatter.front_matter and
						account = %s
			where CMECredit.hours > 0
			order by CMEFrontMatter.provider, CMECredit.displayorder',
			$this->db->quote($this->id, 'integer')
		);

		$credits = SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		);

		$front_matters = $credits->loadAllSubDataObjects(
			'front_matter',
			$this->db,
			'select * from CMEFrontMatter where id in(%s)',
			SwatDBClassMap::get('CMEFrontMatterWrapper')
		);

		$providers = $front_matters->loadAllSubDataObjects(
			'provider',
			$this->db,
			'select * from CMEProvider where id in(%s)',
			SwatDBClassMap::get('CMEProviderWrapper')
		);

		return $credits;
	}

	// }}}
	// {{{ protected function loadCMEFrontMatters()

	protected function loadCMEFrontMatters()
	{
		require_once 'CME/dataobjects/CMEFrontMatterWrapper.php';

		$wrapper = SwatDBClassMap::get('CMEFrontMatterWrapper');
		$front_matters = new $wrapper();
		$wrapper->setDatabase($this->db);

		foreach ($this->cme_credits as $credit) {
			$front_matter = $credit->front_matter;
			if ($wrapper->getByIndex($front_matter->id) === null) {
				$wrapper->add($front_matter);
			}
		}

		return $wrapper;
	}

	// }}}
}

?>
