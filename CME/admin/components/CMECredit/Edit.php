<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Inquisition/dataobjects/InquisitionQuestion.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionOption.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECredit.php';
require_once 'CME/dataobjects/CMEProvider.php';
require_once 'CME/dataobjects/CMEProviderWrapper.php';
require_once 'CME/dataobjects/CMEEvaluation.php';

/**
 * @package   CME
 * @copyright 2013-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMECreditEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var CMECredit
	 */
	protected $credit;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'CME/admin/components/CMECredit/edit.xml';
	}

	// }}}

	// init phase
	// {{{ abstract protected function getDefaultCreditTypeShortname()

	abstract protected function getDefaultCreditTypeShortname();

	// }}}
	// {{{ abstract protected function getDefaultCreditHours()

	abstract protected function getDefaultCreditHours();

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initCredit();

		$this->ui->loadFromXML($this->getUiXml());

		// add available credit types
		$provider_widget = $this->ui->getWidget('provider');
		foreach ($this->getAvailableCreditTypes() as $provider) {
			$provider_widget->addOption(
				$provider->id,
				$provider->title
			);
		}

		$this->setDefaultValues();
	}

	// }}}
	// {{{ protected function getAvailableCreditTypes()

	protected function getAvailableCreditTypes()
	{
		$available_providers_sql =
			'select * from CMEProvider where id not in (
				select provider from CMECredit
			)';

		if ($this->credit->id !== null) {
			$available_providers_sql.= sprintf(
				' or id = %s',
				$this->app->db->quote(
					$this->credit->provider->id,
					'integer'
				)
			);
		}

		$available_providers_sql.= ' order by title';

		return SwatDB::query(
			$this->app->db,
			$available_providers_sql,
			SwatDBClassMap::get('CMEProviderWrapper')
		);
	}

	// }}}
	// {{{ protected function setDefaultValues()

	protected function setDefaultValues()
	{
		$sql = sprintf(
			'select id from CMEProvider where shortname = %s',
			$this->app->db->quote(
				$this->getDefaultCreditTypeShortname(),
				'text'
				)
		);

		$default_type_id = SwatDB::queryOne($this->app->db, $sql);

		$this->ui->getWidget('provider')->value = $default_type_id;
		$this->ui->getWidget('hours')->value = $this->getDefaultCreditHours();
		$this->ui->getWidget('enabled')->value = true;
		$this->ui->getWidget('objectives')->value = <<<HTML
<ul>
<li>objective1</li>
<li>objective2</li>
</ul>

HTML;
	}

	// }}}
	// {{{ protected function initCredit()

	protected function initCredit()
	{
		$class_name = SwatDBClassMap::get('CMECredit');
		$this->credit = new $class_name();
		$this->credit->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->credit->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(
						CME::_(
							'A CME credit with the id of ‘%s’ does not exist'
						),
						$this->id
					)
				);
			}
		}
	}

	// }}}

	// process phase
	// {{{ abstract protected function relocate()

	abstract protected function relocate();

	// }}}
	// {{{ abstract protected function getTitle()

	abstract protected function getTitle();

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		$provider_widget = $this->ui->getWidget('provider');
		$provider = SwatDB::query(
			$this->app->db,
			sprintf(
				'select * from CMEProvider where id = %s',
				$this->app->db->quote($provider_widget->value, 'integer')
			),
			SwatDBClassMap::get('CMEProviderWrapper')
		)->getFirst();

		if ($this->id === null &&
			!$this->validateCreditType($provider)) {
			$message = new SwatMessage(
				sprintf(
					CME::_('%s already has %s CME.'),
					$this->getTitle(),
					$provider->title
				),
				'error'
			);
			$provider_widget->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateCreditType()

	protected function validateCreditType(CMEProvider $provider)
	{
		return true;
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(
			array(
				'provider',
				'hours',
				'objectives',
				'review_date',
				'enabled',
			)
		);

		$review_date = $values['review_date'];
		if ($review_date instanceof SwatDate) {
			$review_date->setTZ($this->app->default_time_zone);
			$review_date->toUTC();
		}

		// Required because of weird behaviour with sub-data-objects in
		// SwatDBDataObject.
		$this->credit->provider = null;

		$this->credit->provider = $values['provider'];
		$this->credit->hours       = $values['hours'];
		$this->credit->objectives  = $values['objectives'];
		$this->credit->enabled     = $values['enabled'];
		$this->credit->review_date = $values['review_date'];

		if ($this->credit->id === null) {
			$this->credit->planning_committee_no_disclosures =
				$this->getPlanningCommitteeNoDisclosures();

			$this->credit->support_staff_no_disclosures =
				$this->getSupportStaffNoDisclosures();

			$evaluation = $this->createEvaluation();
			if ($evaluation instanceof CMEEvaluation) {
				$this->credit->evaluation = $evaluation;
				$this->credit->evaluation->save();
			}
		}

		// if hours updated, clear all cached hours for accounts
		if ($this->id !== null &&
			$this->credit->hours != $values['hours']) {
			$this->app->memcache->flushNs('cme-hours');
		}

		$this->credit->save();

		$this->app->messages->add(
			new SwatMessage(
				sprintf(
					CME::_('%s CME Credit for %s has been saved.'),
					$this->credit->provider->title,
					$this->getTitle()
				)
			)
		);
	}

	// }}}
	// {{{ protected function createEvaluation()

	protected function createEvaluation()
	{
		return null;
	}

	// }}}
	// {{{ protected function getSupportStaffNoDisclosures()

	protected function getSupportStaffNoDisclosures()
	{
		return '';
	}

	// }}}
	// {{{ protected function getPlanningCommitteeNoDisclosures()

	protected function getPlanningCommitteeNoDisclosures()
	{
		return '';
	}

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntries(1);

		if ($this->credit->id === null) {
			$this->navbar->createEntry(CME::_('New CME Credit'));
		} else {
			$this->navbar->createEntry(CME::_('Edit CME Credit'));
		}
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->credit));
		$this->ui->getWidget('provider')->value =
			$this->credit->getInternalValue('provider');

		if ($this->credit->review_date instanceof SwatDate) {
			$date = clone $this->credit->review_date;
			$date->convertTZ($this->app->default_time_zone);
			$this->ui->getWidget('review_date')->value = $date;
		}
	}

	// }}}
}

?>
