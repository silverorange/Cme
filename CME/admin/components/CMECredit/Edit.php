<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Inquisition/dataobjects/InquisitionQuestion.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionOption.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECredit.php';
require_once 'CME/dataobjects/CMECreditType.php';
require_once 'CME/dataobjects/CMECreditTypeWrapper.php';
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
		$credit_type_widget = $this->ui->getWidget('credit_type');
		foreach ($this->getAvailableCreditTypes() as $credit_type) {
			$credit_type_widget->addOption(
				$credit_type->id,
				$credit_type->title
			);
		}

		$this->setDefaultValues();
	}

	// }}}
	// {{{ protected function getAvailableCreditTypes()

	protected function getAvailableCreditTypes()
	{
		$available_credit_types_sql =
			'select * from CMECreditType where id not in (
				select credit_type from CMECredit
			)';

		if ($this->credit->id !== null) {
			$available_credit_types_sql.= sprintf(
				' or id = %s',
				$this->app->db->quote(
					$this->credit->credit_type->id,
					'integer'
				)
			);
		}

		$available_credit_types_sql.= ' order by title';

		return SwatDB::query(
			$this->app->db,
			$available_credit_types_sql,
			SwatDBClassMap::get('CMECreditTypeWrapper')
		);
	}

	// }}}
	// {{{ protected function setDefaultValues()

	protected function setDefaultValues()
	{
		$sql = sprintf(
			'select id from CMECreditType where shortname = %s',
			$this->app->db->quote(
				$this->getDefaultCreditTypeShortname(),
				'text'
				)
		);

		$default_type_id = SwatDB::queryOne($this->app->db, $sql);

		$this->ui->getWidget('credit_type')->value = $default_type_id;
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
		$credit_type_widget = $this->ui->getWidget('credit_type');
		$credit_type = SwatDB::query(
			$this->app->db,
			sprintf(
				'select * from CMECreditType where id = %s',
				$this->app->db->quote($credit_type_widget->value, 'integer')
			),
			SwatDBClassMap::get('CMECreditTypeWrapper')
		)->getFirst();

		if ($this->id === null &&
			!$this->validateCreditType($credit_type)) {
			$message = new SwatMessage(
				sprintf(
					CME::_('%s already has %s CME.'),
					$this->getTitle(),
					$credit_type->title
				),
				'error'
			);
			$credit_type_widget->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateCreditType()

	protected function validateCreditType(CMECreditType $credit_type)
	{
		return true;
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(
			array(
				'credit_type',
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
		$this->credit->credit_type = null;

		$this->credit->credit_type = $values['credit_type'];
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
					$this->credit->credit_type->title,
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
		$this->ui->getWidget('credit_type')->value =
			$this->credit->getInternalValue('credit_type');

		if ($this->credit->review_date instanceof SwatDate) {
			$date = clone $this->credit->review_date;
			$date->convertTZ($this->app->default_time_zone);
			$this->ui->getWidget('review_date')->value = $date;
		}
	}

	// }}}
}

?>
