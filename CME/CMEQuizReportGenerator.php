<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Site/SiteApplication.php';
require_once 'Site/dataobjects/SiteAccountWrapper.php';
require_once 'Store/dataobjects/StoreAccountAddressWrapper.php';
require_once 'Store/dataobjects/StoreProvStateWrapper.php';
require_once 'Store/dataobjects/StoreCountryWrapper.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';
require_once 'CME/dataobjects/CMEProvider.php';
require_once 'CME/dataobjects/CMEAccountEarnedCMECreditWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuizReportGenerator
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
	 * @var CMEProvider
	 */
	protected $provider;

	/**
	 * @var SiteApplication
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app,
		CMEProvider $provider, $year, $quarter)
	{
		$this->app = $app;
		$this->provider = $provider;

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
	// {{{ protected function getEarnedCredits()

	/**
	 * Gets earned CME credits to include in the quarterly report
	 *
	 * Credits are included if and only if:
	 *
	 * - the credit is earned
	 * - the provider is the specified provider
	 * - the earned date is within the quarter
	 * - the account is not deleted
	 *
	 * @return array
	 */
	protected function getEarnedCredits()
	{
		$sql = sprintf(
			'select AccountEarnedCMECredit.* from AccountEarnedCMECredit
				inner join Account
					on AccountEarnedCMECredit.account = Account.id
				inner join CMECredit
					on AccountEarnedCMECredit.credit = CMECredit.id
				inner join CMEFrontMatter
					on CMECredit.front_matter = CMEFrontMatter.id
			where CMEFrontMatter.provider = %s
				and convertTZ(earned_date, %s) >= %s
				and convertTZ(earned_date, %s) < %s
				and Account.delete_date is null',
			$this->app->db->quote($this->provider->id, 'integer'),
			$this->app->db->quote($this->app->config->date->time_zone, 'text'),
			$this->app->db->quote($this->start_date->getDate(), 'date'),
			$this->app->db->quote($this->app->config->date->time_zone, 'text'),
			$this->app->db->quote($this->end_date->getDate(), 'date')
		);

		$earned_credits = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMEAccountEarnedCMECreditWrapper')
		);

		// efficiently load accounts
		$accounts = $this->loadAccounts($earned_credits);

		// load addresses
		$addresses = $this->loadAccountAddresses($accounts);

		// efficiently load credits
		$credits = $this->loadCredits($earned_credits);

		// sort earned credits (sorting is application specific)
		$earned_credits_array = $earned_credits->getArray();
		usort($earned_credits_array, array($this, 'compareEarnedCredit'));

		return $earned_credits_array;
	}

	// }}}
	// {{{ protected function loadAccounts()

	/**
	 * Efficiently loads accounts for earned CME credits
	 *
	 * @param CMEAccountEarnedCMECreditWrapper $earned_credits
	 *
	 * @return SiteAccountWrapper
	 */
	protected function loadAccounts(
		CMEAccountEarnedCMECreditWrapper $earned_credits)
	{
		$accounts = $earned_credits->loadAllSubDataObjects(
			'account',
			$this->app->db,
			'select * from Account where id in (%s)',
			SwatDBClassMap::get('SiteAccountWrapper')
		);

		return $accounts;
	}

	// }}}
	// {{{ protected function loadCredits()

	/**
	 * Efficiently loads CME credits for earned CME credits
	 *
	 * @param CMEAccountEarnedCMECreditWrapper $earned_credits
	 *
	 * @return CMECreditWrapper
	 */
	protected function loadCredits(
		CMEAccountEarnedCMECreditWrapper $earned_credits)
	{
		$credit_sql = 'select id, hours from CMECredit where id in (%s)';

		$credits = $earned_credits->loadAllSubDataObjects(
			'credit',
			$this->app->db,
			$credit_sql,
			SwatDBClassMap::get('CMECreditWrapper')
		);

		return $credits;
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
			SwatDBClassMap::get('StoreProvStateWrapper')
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
	// {{{ protected function compareEarnedCredit()

	protected function compareEarnedCredit(
		CMEAccountEarnedCMECredit $a,
		CMEAccountEarnedCMECredit $b)
	{
		return 0;
	}

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
			'Date Earned Credit',
		);
	}

	// }}}
	// {{{ protected function getEarnedCreditRow()

	protected function getEarnedCreditRow(
		CMEAccountEarnedCMECredit $earned_credit)
	{
		$account = $earned_credit->account;
		$credit = $earned_credit->credit;

		$address = $account->getDefaultBillingAddress();

		if (!$address instanceof StoreAccountAddress) {
			$address = $account->addresses->getFirst();
		}

		if (!$address instanceof StoreAddress) {
			// If there is no address, set up an empty address
			$class_name = SwatDBClassMap::get('StoreAccountAddress');
			$address = new $class_name();
			$address->first_name = $account->first_name;
			$address->last_name = $account->last_name;
		}

		$earned_date = clone $earned_credit->earned_date;
		$earned_date->setTimezone($this->app->default_time_zone);

		$address_lines = $this->formatLines($address);
		$address_suffix = $this->formatSuffix($account);
		$address_provstate = $this->formatProvState($address);
		$address_country = $this->formatCountry($address);
		$address_postal_code = $this->formatPostalCode($address);

		return array(
			$address->last_name,
			$address->first_name,
			$address_suffix,
			$account->email,
			$address_lines,
			$address->city,
			$address_provstate,
			$address_postal_code,
			$address_country,
			$address->phone,
			$credit->hours,
			$earned_date->formatLikeIntl('MMMM dd, yyyy'),
		);
	}

	// }}}
	// {{{ protected function display()

	protected function display($file)
	{
		$this->displayHeader($file);

		$earned_credits = $this->getEarnedCredits();
		$this->displayEarnedCredits($file, $earned_credits);
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader($file)
	{
		fputcsv($file, $this->getHeaderRow());
	}

	// }}}
	// {{{ protected function displayEarnedCredits()

	protected function displayEarnedCredits($file, array $earned_credits)
	{
		foreach ($earned_credits as $earned_credit) {
			$this->displayEarnedCredit($file, $earned_credit);
		}
	}

	// }}}
	// {{{ protected function displayEarnedCredit()

	protected function displayEarnedCredit($file,
		CMEAccountEarnedCMECredit $earned_credit)
	{
		fputcsv($file, $this->getEarnedCreditRow($earned_credit));
	}

	// }}}
	// {{{ protected function formatPostalCode()

	protected function formatPostalCode(StoreAddress $address)
	{
		$postal_code = $address->postal_code;

		if ($address->country instanceof StoreCountry) {
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
		}

		return $postal_code;
	}

	// }}}
	// {{{ protected function formatSuffix()

	protected function formatSuffix(SiteAccount $account)
	{
		return ($account->hasPublicProperty('suffix'))
			? $account->suffix
			: '';
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
	// {{{ protected function formatCountry()

	protected function formatCountry(StoreAddress $address)
	{
		return ($address->country instanceof StoreCountry)
			? $address->country->title
			: '';
	}

	// }}}
}

?>
