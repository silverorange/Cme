<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'CME/dataobjects/CMEEvaluation.php';
require_once 'CME/dataobjects/CMEProvider.php';

/**
 * @package   CME
 * @copyright 2013-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEFrontMatter extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

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
	public $planning_committee_with_disclosures;

	/**
	 * @var string
	 */
	public $support_staff_no_disclosures;

	/**
	 * @var string
	 */
	public $support_staff_with_disclosures;

	/**
	 * @var SwatDate
	 */
	public $release_date;

	/**
	 * @var SwatDate
	 */
	public $review_date;

	/**
	 * @var boolean
	 */
	public $enabled;

	/**
	 * @var integer
	 */
	public $passing_grade;

	/**
	 * @var string
	 */
	public $email_content_pass;

	/**
	 * @var string
	 */
	public $email_content_fail;

	/**
	 * @var boolean
	 */
	public $resettable;

	// }}}
	// {{{ public function getProviderTitleList()

	public function getProviderTitleList()
	{
		$titles = array();
		foreach ($this->providers as $provider) {
			$titles[] = $provider->title;
		}
		return SwatString::toList($titles);
	}

	// }}}
	// {{{ abstract protected function getAttestationLink()

	abstract protected function getAttestationLink();

	// }}}
	// {{{ abstract protected function getEvaluationLink()

	abstract protected function getEvaluationLink();

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'CMEFrontMatter';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty(
			'evaluation',
			SwatDBClassMap::get('CMEEvaluation')
		);

		$this->registerDateProperty('release_date');
		$this->registerDateProperty('review_date');
	}

	// }}}
	// {{{ protected function loadCredits()

	protected function loadCredits()
	{
		require_once 'CME/dataobjects/CMECreditWrapper.php';

		$sql = sprintf(
			'select * from CMECredit where front_matter = %s
			order by displayorder asc, hours desc',
			$this->db->quote($this->id, 'integer')
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		);
	}

	// }}}
	// {{{ protected function loadProviders()

	protected function loadProviders()
	{
		require_once 'CME/dataobjects/CMEProviderWrapper.php';

		$sql = sprintf(
			'select CMEProvider.*
			from CMEProvider
			inner join CMEFrontMatterProviderBinding on
				CMEFrontMatterProviderBinding.provider = CMEProvider.id
			where CMEFrontMatterProviderBinding.front_matter = %s
			order by CMEProvider.displayorder, CMEProvider.id',
			$this->db->quote($this->id, 'integer')
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMEProviderWrapper')
		);
	}

	// }}}
}

?>
