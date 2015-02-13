<?php

require_once 'Store/dataobjects/StoreAccount.php';
require_once 'CME/dataobjects/CMECredit.php';
require_once 'CME/dataobjects/CMEEvaluation.php';
require_once 'CME/dataobjects/CMEEvaluationResponse.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMEQuizResponse.php';
require_once 'CME/dataobjects/CMEAccountCMEProgress.php';

/**
 * CME specific Account object
 *
 * @package   CME
 * @copyright 2011-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEAccount extends StoreAccount
{
	// {{{ abstract public function hasCMEAccess()

	abstract public function hasCMEAccess();

	// }}}
	// {{{ public function hasAttested()

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

	public function isEvaluationComplete(CMECredit $credit)
	{
		$complete = false;

		$progress = $this->getCMEProgress($credit);

		if ($progress instanceof CMEAccountCMEProgress &&
			$progress->evaluation instanceof CMEEvaluation) {

			$response = $progress->evaluation->getResponseByAccount($this);
			$complete = (
				$response instanceof CMEEvaluationResponse &&
				$response->complete_date instanceof SwatDate
			);
		}

		return $complete;
	}

	// }}}
	// {{{ public function isQuizComplete()

	public function isQuizComplete(CMECredit $credit)
	{
		$complete = false;

		$progress = $this->getCMEProgress($credit);

		if ($progress instanceof CMEAccountCMEProgress &&
			$progress->quiz instanceof CMEQuiz) {

			$quiz_response = $progress->quiz->getResponseByAccount($this);
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
			$progress = $this->getCMEProgress($credit);
			$quiz_response = $progress->quiz->getResponseByAccount($this);
			$passed = $quiz_response->isPassed();
		}

		return $passed;
	}

	// }}}
	// {{{ public function isCreditEarned()

	public function isCreditEarned(CMECredit $credit)
	{
		$earned = false;

		foreach ($this->earned_cme_credits as $earned_credit) {
			if ($earned_credit->credit->id === $credit->id) {
				$earned = true;
				break;
			}
		}

		return $earned;
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
	// {{{ public function getEarnedCMECreditHoursByProvider()

	public function getEarnedCMECreditHoursByProvider(CMEProvider $provider)
	{
		$hours = 0;

		foreach ($this->earned_cme_credits as $earned_credit) {
			$cme_providers = $earned_credit->credit->front_matter->providers;
			$cme_provider = $cme_providers->getByIndex($provider->id);
			if ($cme_provider instanceof CMEProvider) {
				$hours += $earned_credit->credit->hours;
			}
		}

		return $hours;
	}

	// }}}
	// {{{ public function getEarnedCMECreditHoursByFrontMatter()

	public function getEarnedCMECreditHoursByFrontMatter(
		CMEFrontMatter $front_matter)
	{
		$hours = 0;

		foreach ($this->earned_cme_credits as $earned_credit) {
			$credit = $earned_credit->credit;
			if ($credit->front_matter->id === $front_matter->id) {
				$hours += $earned_credit->credit->hours;
			}
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
				order by CMEFrontMatter.id, CMECredit.displayorder',
				$this->db->quote(true, 'boolean'),
				$this->db->quote($this->id, 'integer')
			);

			$available_credits = SwatDB::query(
				$this->db,
				$sql,
				SwatDBClassMap::get('CMECreditWrapper')
			);
		} else {
			$wrapper = SwatDBClassMap::get('CMECreditWrapper');
			$available_credits = new $wrapper();
		}

		$available_credits->setDatabase($this->db);
		return $available_credits;
	}

	// }}}
	// {{{ public function getCMEProgress()

	public function getCMEProgress(RapCredit $credit)
	{
		require_once 'CME/dataobjects/CMEAccountCMEProgressWrapper.php';

		$this->checkDB();

		$sql = sprintf(
			'select AccountCMEProgress.*
			from AccountCMEProgress
			where AccountCMEProgress.account = %s
			and AccountCMEProgress.id in (
				select progress from AccountCMEProgressCreditBinding
				where AccountCMEProgressCreditBinding.credit = %s
			)',
			$this->db->quote($this->id, 'integer'),
			$this->db->quote($credit->id, 'integer')
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMEAccountCMEProgressWrapper')
		)->getFirst();
	}

	// }}}
	// {{{ public function hasSameCMEProgress()

	public function hasSameCMEProgress(RapCredit $credit1, RapCredit $credit2)
	{
		$progress1 = $this->getCMEProgress($credit1);
		$progress2 = $this->getCMEProgress($credit2);

		// combine credits if they have the same progress
		if ($progress1 instanceof CMEAccountCMEProgress &&
			$progress2 instanceof CMEAccountCMEProgress &&
			$progress1->id === $progress2->id) {

			$combine = true;

		// combine credits if they both haven't been started
		} elseif (!$progress1 instanceof CMEAccountCMEProgress &&
			!$progress2 instanceof CMEAccountCMEProgress) {

			$combine = true;
		} else {
			$combine = false;
		}

		return $combine;
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
			'select AccountEarnedCMECredit.*
			from AccountEarnedCMECredit
				inner join CMECredit
					on AccountEarnedCMECredit.credit = CMECredit.id
				inner join CMEFrontMatter
					on CMECredit.front_matter = CMEFrontMatter.id
			where account = %s
			order by CMEFrontMatter.id, CMECredit.displayorder',
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

		if ($credits instanceof CMECreditWrapper) {
			$front_matters = $credits->loadAllSubDataObjects(
				'front_matter',
				$this->db,
				'select * from CMEFrontMatter where id in(%s)',
				SwatDBClassMap::get('CMEFrontMatterWrapper')
			);

			$front_matters->loadProviders();
		}

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
			order by CMEFrontMatter.id, CMECredit.displayorder',
			$this->db->quote($this->id, 'integer')
		);

		$credits = SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		);

		if ($credits instanceof CMECreditWrapper) {
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
		}

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

			// remove duplicate front matters from recordset
			$wrapper_front_matter = $wrapper->getByIndex($front_matter->id);
			if (!$wrapper_front_matter instanceof CMEFrontMatter) {
				$wrapper->add($front_matter);
			}
		}

		return $wrapper;
	}

	// }}}
}

?>
