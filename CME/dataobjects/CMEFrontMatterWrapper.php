<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';

/**
 * A recordset wrapper class for CMEFrontMatter objects
 *
 * @package   CME
 * @copyright 2013-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       CMEFrontMatter
 */
class CMEFrontMatterWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function loadCredits()

	public function loadCredits($read_only = true)
	{
		$wrapper_class = SwatDBClassMap::get('CMECreditWrapper');
		$credits_wrapper = new $wrapper_class();
		$credits_wrapper->setOptions('read_only', $read_only);

		$credits = SwatDB::query(
			$this->db,
			sprintf(
				'select * from CMECredit
				where front_matter in (%s)
				order by front_matter, displayorder, hours',
				$this->db->implodeArray(
					$this->getIndexes(),
					'integer'
				)
			),
			$credits_wrapper
		);

		$this->attachSubRecordset(
			'credits',
			SwatDBClassMap::get('CMECreditWrapper'),
			'front_matter',
			$credits
		);

		// efficiently link back to front-matter from credit
		foreach ($credits as $credit) {
			$credit->front_matter = $this->getByIndex(
				$credit->getInternalValue('front_matter')
			);
		}

		return $credits;
	}

	// }}}
	// {{{ public function loadProviders()

	public function loadProviders($read_only = true)
	{
		$providers_wrapper_class = SwatDBClassMap::get(
			'CMEProviderWrapper'
		);
		$providers_wrapper = new $providers_wrapper_class();
		$providers_wrapper->setOptions('read_only', $read_only);

		$providers = SwatDB::query(
			$this->db,
			'select * from CMEProvider order by id',
			$providers_wrapper
		);

		$sql = sprintf(
			'select front_matter, provider
			from CMEFrontMatterProviderBinding
			where front_matter in (%s)
			order by front_matter, provider',
			$this->db->implodeArray(
				$this->getIndexes(),
				'integer'
			)
		);

		$rows = SwatDB::query($this->db, $sql);
		$front_matter_id = null;
		foreach ($rows as $row) {
			if ($row->front_matter !== $front_matter_id) {
				$front_matter_id = $row->front_matter;
				$front_matter = $this->getByIndex(
					$row->front_matter
				);
				$front_matter->providers = new $providers_wrapper_class();
				$front_matter->providers->setOptions('read_only', $read_only);
			}

			$provider = $providers->getByIndex($row->provider);
			$front_matter->providers->add($provider);
		}

		return $providers;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('CMEFrontMatter');
		$this->index_field = 'id';
	}

	// }}}
}

?>
