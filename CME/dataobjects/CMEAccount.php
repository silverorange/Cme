<?php

require_once 'Store/dataobjects/StoreAccount.php';

/**
 * CME specific Account object
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEAccount extends StoreAccount
{
	// {{{ abstract public function hasCMEAccess()

	abstract public function hasCMEAccess();

	// }}}
	// {{{ public function getCMECreditHours()

	public function getCMECreditHours(SwatDate $start_date = null,
		SwatDate $end_date = null)
	{
		$credits = $this->getCMEAttestedCredits($start_date, $end_date);
		$hours = 0;

		foreach ($credits as $credit) {
			if ($this->isCertificateEarned($credit)) {
				$hours += $credit->hours;
			}
		}

		return $hours;
	}

	// }}}
	// {{{ public function getCMEEnabledCreditHours()

	public function getCMEEnabledCreditHours(SwatDate $start_date = null,
		SwatDate $end_date = null)
	{
		$credits = $this->getCMEAttestedCredits($start_date, $end_date);
		$hours = 0;

		foreach ($credits as $credit) {
			if ($credit->enabled && $this->isCertificateEarned($credit)) {
				$hours += $credit->hours;
			}
		}

		return $hours;
	}

	// }}}
	// {{{ public function getCMEAttestedCredits()

	public function getCMEAttestedCredits(SwatDate $start_date = null,
		SwatDate $end_date = null)
	{
		return $this->cme_credits->getArray();
	}

	// }}}
	// {{{ public function hasCMEAttested()

	public function hasCMEAttested(CMECredit $credit)
	{
		$this->checkDB();

		$sql = sprintf(
			'select count(1) from AccountCMECreditBinding
			where account = %s and credit = %s',
			$this->db->quote($this->id, 'integer'),
			$this->db->quote($credit->id, 'integer')
		);

		return (SwatDB::queryOne($this->db, $sql) > 0);
	}

	// }}}
	// {{{ public function isEvaluationComplete()

	public function isEvaluationComplete(CMECredit $credit)
	{
		$complete = false;

		$evaluation = $credit->evaluation;
		if ($evaluation instanceof InquisitionInquisition) {
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

		if ($credit->quiz instanceof InquisitionInquisition) {
			$quiz_response = $credit->quiz->getResponseByAccount($this);
			$complete = ($quiz_response instanceof InquisitionResponse &&
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
	// {{{ public function isCertificateEarned()

	public function isCertificateEarned(CMECredit $credit)
	{
		// assume the evaluation is always required
		return (
				!$credit->quiz instanceof InquisitionInquisition ||
				$this->isQuizPassed($credit)
			) && (
				!$credit->evaluation instanceof InquisitionInquisition ||
				$this->isEvaluationComplete($credit)
			);
	}

	// }}}
	// {{{ public function getAvailableCMECredits()

	public function getAvailableCMECredits()
	{
		require_once 'CME/dataobjects/CMECreditWrapper.php';

		$this->checkDB();

		$wrapper = SwatDBClassMap::get('CMECreditWrapper');
		$available_credits = new $wrapper();

		if ($this->hasCMEAccess()) {
			$sql = sprintf(
				'select CMECredit.*
				from CMECredit
				where CMECredit.hours > 0 and
					CMECredit.enabled = %s
				order by CMECredit.provider',
				$this->db->quote(true, 'boolean')
			);

			$all_credits = SwatDB::query(
				$this->db,
				$sql,
				$wrapper
			);

			foreach ($all_credits as $credit) {
				if (!$this->isCertificateEarned($credit)) {
					$available_credits->add($credit);
				}
			}
		}

		$available_credits->setDatabase($this->db);
		return $available_credits;
	}

	// }}}

	// loader methods
	// {{{ protected function loadCMECredits()

	protected function loadCMECredits()
	{
		require_once 'CME/dataobjects/CMECreditWrapper.php';
		require_once 'CME/dataobjects/CMEProviderWrapper.php';

		$sql = sprintf(
			'select CMECredit.* from CMECredit
				inner join AccountCMECreditBinding on
					CMECredit.id = AccountCMECreditBinding.credit and
						account = %s
			where CMECredit.hours > 0
			order by CMECredit.provider',
			$this->db->quote($this->id, 'integer')
		);

		$credits = SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		);

		$providers = $credits->loadAllSubDataObjects(
			'provider',
			$this->db,
			'select * from CMEProvider where id in(%s)',
			SwatDBClassMap::get('CMEProviderWrapper')
		);

		return $credits;
	}

	// }}}
}

?>
