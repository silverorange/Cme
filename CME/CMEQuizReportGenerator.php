<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Site/SiteApplication.php';
require_once 'Inquisition/dataobjects/InquisitionResponseWrapper.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';
require_once 'CME/dataobjects/CMECreditType.php';
require_once 'CME/dataobjects/CMEQuizWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEQuizReportGenerator
{
	// {{{ protected properties

	/**
	 * @var SwatDate
	 */
	protected $start_date;

	/**
	 * @var SwatDate
	 */
	protected $end_date;

	/**
	 * @var CMECreditType
	 */
	protected $credit_type;

	/**
	 * @var array
	 */
	protected $credits_by_quiz = array();

	/**
	 * @var SiteApplication
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app,
		CMECreditType $credit_type, $year, $quarter)
	{
		$this->app = $app;
		$this->credit_type = $credit_type;

		$start_month = ((intval($quarter) - 1) * 3) + 1;

		$this->start_date = new SwatDate();
		$this->start_date->setTime(0, 0, 0);
		$this->start_date->setDate($year, $start_month, 1);
		$this->start_date->setTZ($this->app->default_time_zone);

		$this->end_date = clone $this->start_date;
		$this->end_date->addMonths(3);
	}

	// }}}

	// data retrieval methods
	// {{{ protected function getResponses()

	protected function getResponses()
	{
		$sql = sprintf(
			'select * from InquisitionResponse
			where complete_date is not null
				and reset_date is null
				and convertTZ(complete_date, %1$s) >= %2$s
				and convertTZ(complete_date, %1$s) < %3$s
				and inquisition in (
					select quiz from CMECredit where credit_type = %4$s
				) and account in (
					select id from Account where Account.delete_date is null
				)',
			$this->app->db->quote($this->app->config->date->time_zone, 'text'),
			$this->app->db->quote($this->start_date->getDate(), 'date'),
			$this->app->db->quote($this->end_date->getDate(), 'date'),
			$this->app->db->quote($this->credit_type->id, 'integer')
		);

		$responses = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('InquisitionResponseWrapper')
		);

		// efficiently load accounts
		$accounts = $this->loadAccounts($responses);

		// load addresses
		$addresses = $this->loadAddresses($accounts);

		// efficiently load response values
		$values = $responses->loadAllSubRecordsets(
			'values',
			SwatDBClassMap::get('InquisitionResponseValueWrapper'),
			'InquisitionResponseValue',
			'response'
		);

		// efficiently load response value question bindings
		$question_binding_sql =
			'select * from InquisitionInquisitionQuestionBinding
			where id in (%s)';

		$question_bindings = $values->loadAllSubDataObjects(
			'question_binding',
			$this->app->db,
			$question_binding_sql,
			SwatDBClassMap::get('InquisitionInquisitionQuestionBindingWrapper')
		);

		// efficiently load response value questions
		$question_sql = 'select * from InquisitionQuestion where id in (%s)';
		$questions = $question_bindings->loadAllSubDataObjects(
			'question',
			$this->app->db,
			$question_sql,
			SwatDBClassMap::get('InquisitionQuestionWrapper')
		);

		// efficiently load quizzes
		$quiz_sql = 'select * from Inquisition where id in (%s)';
		$quizzes = $responses->loadAllSubDataObjects(
			'inquisition',
			$this->app->db,
			$quiz_sql,
			'CMEQuizWrapper'
		);

		// efficiently load credits
		$credits = SwatDB::query(
			$this->app->db,
			sprintf(
				'select id, hours, quiz, episode
					from CMECredit
				where quiz in (%s)',
				$this->app->db->implodeArray($quizzes->getIndexes(), 'integer')
			),
			SwatDBClassMap::get('CMECreditWrapper')
		);

		$credits->attachSubDataObjects('quiz', $quizzes);

		// index credits by quiz
		foreach ($credits as $credit) {
			$this->credits_by_quiz[$credit->quiz->id] = $credit;
		}

		// efficiently load question bindings
		$wrapper = SwatDBClassMap::get(
			'InquisitionInquisitionQuestionBindingWrapper'
		);

		$sql = sprintf(
			'select * from InquisitionInquisitionQuestionBinding
			where InquisitionInquisitionQuestionBinding.inquisition in (%s)
			order by inquisition, displayorder',
			$this->app->db->implodeArray($quizzes->getIndexes(), 'integer')
		);

		$question_bindings = SwatDB::query($this->app->db, $sql, $wrapper);
		$quizzes->attachSubRecordset(
			'question_bindings',
			$wrapper,
			'inquisition',
			$question_bindings
		);

		// efficiently load questions
		$sql = 'select * from InquisitionQuestion where id in (%s)';
		$questions = $question_bindings->loadAllSubDataObjects(
			'question',
			$this->app->db,
			$question_sql,
			SwatDBClassMap::get('InquisitionQuestionWrapper')
		);

		$response_array = array();
		foreach ($responses as $response) {
			// filter out responses for quizzes with no questions
			if (count($response->inquisition->question_bindings) > 0) {
				$response_array[] = $response;
			}
		}

		// sort responses
		usort($response_array, array($this, 'compareResponse'));

		// index by inquisition
		$responses_by_inquisition = array();

		foreach ($response_array as $response) {
			$inquisition_id = $response->getInternalValue('inquisition');
			if (!isset($responses_by_inquisition[$inquisition_id])) {
				$responses_by_inquisition[$inquisition_id] = array();
			}
			$responses_by_inquisition[$inquisition_id][] = $response;
		}

		return $responses_by_inquisition;
	}

	// }}}
	// {{{ protected function loadAccounts()

	protected function loadAccounts(InquisitionResponseWrapper $responses)
	{
		// efficiently load accounts
		$account_sql = 'select id, email, default_billing_address from Account
			where id in (%s)';

		$accounts = $responses->loadAllSubDataObjects(
			'account',
			$this->app->db,
			$account_sql,
			SwatDBClassMap::get('SiteAccountWrapper')
		);

		return $accounts;
	}

	// }}}
	// {{{ protected function loadAccountAddresses()

	protected function loadAccountAddresses(SiteAccountWrapper $accounts)
	{
		$addresses = $accounts->loadAllSubRecordsets(
			'addresses',
			SwatDBClassMap::get('StoreAccountAddressWrapper'),
			'AccountAddress',
			'account'
		);

		$provstate_sql = 'select * from Provstate where id in (%s)';
		$addresses->loadAllSubDataObjects(
			'provstate',
			$this->app->db,
			$provstate_sql,
			SwatDBClassMap::get('StoreProvstateWrapper')
		);

		$country_sql = 'select * from Country where id in (%s)';
		$addresses->loadAllSubDataObjects(
			'country',
			$this->app->db,
			$country_sql,
			SwatDBClassMap::get('StoreCountryWrapper'),
			'text'
		);

		return $addresses;
	}

	// }}}
	// {{{ protected function getCredit()

	protected function getCredit(CMEQuiz $quiz)
	{
		return $this->credits_by_quiz[$quiz->id];
	}

	// }}}
	// {{{ abstract protected function compareResponse()

	abstract protected function compareResponse(
		InquisitionResponse $a,
		InquisitionResponse $b);

	// }}}

	// output methods
	// {{{ public function saveFile()

	public function saveFile($filename)
	{
		if (!file_exists(dirname($filename))) {
			mkdir(dirname($filename), 0770, true);
		}

		$file = fopen($filename, 'w');
		$this->display($file);
		fclose($file);
	}

	// }}}

	// report display methods
	// {{{ protected function getHeaderRow()

	protected function getHeaderRow()
	{
		return array(
			'Last Name',
			'First Name',
			'Suffix',
			'Email',
			'Address',
			'City',
			'State / Province',
			'ZIP / Postal Code',
			'Country',
			'Phone',
			'Hours',
			'Date Completed',
		);
	}

	// }}}
	// {{{ protected function getQuizResponseRow()

	protected function getQuizResponseRow(InquisitionResponse $response)
	{
		$address = $response->account->getDefaultBillingAddress();

		if ($address === null) {
			$address = $response->account->addresses->getFirst();
		}

		if ($address === null) {
			return;
		}

		$quiz   = $response->inquisition;
		$credit = $this->getCredit($quiz);

		$complete_date = clone $response->complete_date;
		$complete_date->convertTZ($this->app->default_time_zone);

		$address_lines = $this->formatLines($address);
		$address_suffix = $this->formatSuffix($response->account, $address);
		$address_provstate = $this->formatProvState($address);
		$address_postal_code = $this->formatPostalCode($address);

		return array(
			$address->last_name,
			$address->first_name,
			$address_suffix,
			$response->account->email,
			$address_lines,
			$address->city,
			$address_provstate,
			$address_postal_code,
			$address->country->title,
			$address->phone,
			$credit->hours,
			$complete_date->formatLikeIntl('MMMM dd, yyyy'),
		);
	}

	// }}}
	// {{{ protected function display()

	protected function display($file)
	{
		$this->displayHeader($file);

		$responses_by_inquisition = $this->getResponses();
		foreach ($responses_by_inquisition as $responses) {
			$this->displayQuizResponses($file, $responses);
		}
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader($file)
	{
		fputcsv($file, $this->getHeaderRow());
	}

	// }}}
	// {{{ protected function displayQuizResponses()

	protected function displayQuizResponses($file, array $responses)
	{
		foreach ($responses as $response) {
			if ($response->isPassed()) {
				$this->displayQuizResponse($file, $response);
			}
		}
	}

	// }}}
	// {{{ protected function displayQuizResponse()

	protected function displayQuizResponse($file, InquisitionResponse $response)
	{
		fputcsv($file, $this->getQuizResponseRow($response));
	}

	// }}}
	// {{{ protected function formatPostalCode()

	protected function formatPostalCode(StoreAddress $address)
	{
		$postal_code = $address->postal_code;

		switch ($address->country->id) {
		case 'CA':
			$postal_code = str_replace(array(' ', '-'), '', $postal_code);
			$postal_code = strtoupper($postal_code);
			break;

		case 'US':
			$matches = array();
			$postal_code = trim($postal_code);
			$zip_4 = '/([0-9]{5})[- ][0-9]{4}/u';
			if (preg_match($zip_4, $postal_code, $matches) === 1) {
				$postal_code = $matches[1];
			}
			break;
		}

		return $postal_code;
	}

	// }}}
	// {{{ protected function formatSuffix()

	protected function formatSuffix(Account $account, StoreAddress $address)
	{
		return $address->suffix;
	}

	// }}}
	// {{{ protected function formatLines()

	protected function formatLines(StoreAddress $address)
	{
		$address_lines = $address->line1;

		if ($address->line2 != '') {
			$address_lines.= ' '.$address->line2;
		}

		return $address_lines;
	}

	// }}}
	// {{{ protected function formatProvState()

	protected function formatProvState(StoreAddress $address)
	{
		$provstate = $address->provstate_other;

		if ($address->provstate instanceof StoreProvState) {
			$provstate = $address->provstate->abbreviation;
		}

		return $provstate;
	}

	// }}}
}

?>
