<?php


/**
 * CME specific Account object
 *
 * @package   CME
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEAccount extends StoreAccount
{
	// {{{ protected properties

	/**
	 * @var array
	 */
	protected $cme_progress_by_credit;

	/**
	 * @var array
	 */
	protected $response_by_cme_quiz;

	/**
	 * @var array
	 */
	protected $response_by_cme_eval;

	// }}}
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
			$progress->hasInternalValue('evaluation')) {

			$response = $this->getResponseByCMEEvaluation(
				$progress->getInternalValue('evaluation')
			);

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
			$progress->hasInternalValue('quiz')) {

			$quiz_response = $this->getResponseByCMEQuiz(
				$progress->getInternalValue('quiz')
			);

			$complete = (
				$quiz_response instanceof CMEQuizResponse &&
				$quiz_response->complete_date instanceof SwatDate
			);
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

			if ($progress instanceof CMEAccountCMEProgress &&
				$progress->hasInternalValue('quiz')) {

				$quiz_response = $this->getResponseByCMEQuiz(
					$progress->getInternalValue('quiz')
				);

				$passed = (
					$quiz_response instanceof CMEQuizResponse &&
					$quiz_response->isPassed()
				);
			}
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
	// {{{ public function getEarnedCMECreditsByProvider()

	public function getEarnedCMECreditsByProvider(CMEProvider $provider)
	{
		$class_name = SwatDBClassMap::get('CMECreditWrapper');
		$credits = new $class_name();

		foreach ($this->earned_cme_credits as $earned_credit) {
			$cme_providers = $earned_credit->credit->front_matter->providers;
			$cme_provider = $cme_providers->getByIndex($provider->id);
			if ($cme_provider instanceof CMEProvider) {
				$credits->add($earned_credit->credit);
			}
		}

		return $credits;
	}

	// }}}
	// {{{ public function getEarnedCMECreditHoursByProvider()

	public function getEarnedCMECreditHoursByProvider(CMEProvider $provider)
	{
		$hours = 0;
		foreach ($this->getEarnedCMECreditsByProvider($provider) as $credit) {
			$hours += $credit->hours;
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
	// {{{ public function getCMEProgress()

	public function getCMEProgress(RapCredit $credit)
	{
		require_once 'CME/dataobjects/CMEAccountCMEProgressWrapper.php';

		$this->checkDB();

		if ($this->cme_progress_by_credit === null) {
			$sql = sprintf(
				'select AccountCMEProgress.*,
					AccountCMEProgressCreditBinding.credit
				from AccountCMEProgress
				inner join AccountCMEProgressCreditBinding on
					AccountCMEProgressCreditBinding.progress =
						AccountCMEProgress.id
				where AccountCMEProgress.account = %s',
				$this->db->quote($this->id, 'integer'),
				$this->db->quote($credit->id, 'integer')
			);

			$rows = SwatDB::query($this->db, $sql);

			$this->cme_progress_by_credit = array();
			$class_name = SwatDBClassMap::get('CMEAccountCMEProgress');
			foreach ($rows as $row) {
				$progress = new $class_name($row);
				$progress->setDatabase($this->db);
				$this->cme_progress_by_credit[$row->credit] = $progress;
			}
		}

		return (isset($this->cme_progress_by_credit[$credit->id]))
			? $this->cme_progress_by_credit[$credit->id]
			: null;
	}

	// }}}
	// {{{ public function getResponseByCMEQuiz()

	public function getResponseByCMEQuiz($quiz_id)
	{
		require_once 'CME/dataobjects/CMEQuizResponseWrapper.php';

		$this->checkDB();

		if ($this->response_by_cme_quiz === null) {
			$this->response_by_cme_quiz[] = array();

			$sql = sprintf(
				'select * from InquisitionResponse
				where account = %s and reset_date is null',
				$this->db->quote($this->id, 'integer')
			);

			$responses = SwatDB::query(
				$this->db,
				$sql,
				SwatDBClassMap::get('CMEQuizResponseWrapper')
			);

			foreach ($responses as $response) {
				$id = $response->getInternalValue('inquisition');

				$this->response_by_cme_quiz[$id] = $response;
			}
		}

		return (isset($this->response_by_cme_quiz[$quiz_id]))
			? $this->response_by_cme_quiz[$quiz_id]
			: null;
	}

	// }}}
	// {{{ public function getResponseByCMEEvaluation()

	public function getResponseByCMEEvaluation($evaluation_id)
	{
		require_once 'CME/dataobjects/CMEEvaluationResponseWrapper.php';

		$this->checkDB();

		if ($this->response_by_cme_eval === null) {
			$this->response_by_cme_eval[] = array();

			$sql = sprintf(
				'select * from InquisitionResponse where account = %s',
				$this->db->quote($this->id, 'integer')
			);

			$responses = SwatDB::query(
				$this->db,
				$sql,
				SwatDBClassMap::get('CMEEvaluationResponseWrapper')
			);

			foreach ($responses as $response) {
				$id = $response->getInternalValue('inquisition');

				$this->response_by_cme_eval[$id] = $response;
			}
		}

		return (isset($this->response_by_cme_eval[$evaluation_id]))
			? $this->response_by_cme_eval[$evaluation_id]
			: null;
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
				'select * from CMEFrontMatter where id in (%s)',
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
}

?>
