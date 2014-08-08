<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Admin/pages/AdminObjectEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';
require_once 'CME/dataobjects/CMEProvider.php';
require_once 'CME/dataobjects/CMEProviderWrapper.php';
require_once 'CME/dataobjects/CMEEvaluation.php';

/**
 * @package   CME
 * @copyright 2013-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEFrontMatterEdit extends AdminObjectEdit
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'CME/admin/components/FrontMatter/edit.xml';
	}

	// }}}
	// {{{ protected function getObjectClass()

	protected function getObjectClass()
	{
		return 'CMEFrontMatter';
	}

	// }}}
	// {{{ protected function getObjectUiValueNames()

	protected function getObjectUiValueNames()
	{
		return array(
			'provider',
			'objectives',
			'review_date',
			'enabled',
		);
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// add available providers
		$provider_widget = $this->ui->getWidget('provider');
		$providers = $this->getAvailableProviders();
		foreach ($providers as $provider) {
			$provider_widget->addOption(
				$provider->id,
				$provider->title
			);
		}

		// If there is only one provider, don't show the blank option. One
		// less click for admin users.
		if (count($providers) < 2) {
			$provider_widget->show_blank = false;
		}

		$this->setDefaultValues();
	}

	// }}}
	// {{{ protected function getDefaultProviderShortname()

	/**
	 * @return string the default provider shortname. If an empty string is
	 *                returned, the first provider in the list is selected
	 *                by default.
	 */
	protected function getDefaultProviderShortname()
	{
		return '';
	}

	// }}}
	// {{{ protected function getAvailableProviders()

	protected function getAvailableProviders()
	{
		return SwatDB::query(
			$this->app->db,
			'select * from CMEProvider',
			SwatDBClassMap::get('CMEProviderWrapper')
		);
	}

	// }}}
	// {{{ protected function setDefaultValues()

	protected function setDefaultValues()
	{
		$shortname = $this->getDefaultProviderShortname();
		if ($shortname != '') {
			$sql = sprintf(
				'select id from CMEProvider where shortname = %s',
				$this->app->db->quote(
					$this->getDefaultProviderShortname(),
					'text'
				)
			);

			$default_provider_id = SwatDB::queryOne($this->app->db, $sql);
			$this->ui->getWidget('provider')->value = $default_provider_id;
		}

		$this->ui->getWidget('enabled')->value = true;
		$this->ui->getWidget('objectives')->value = <<<HTML
<ul>
<li>objective1</li>
<li>objective2</li>
</ul>

HTML;
	}

	// }}}

	// process phase
	// {{{ abstract protected function getTitle()

	abstract protected function getTitle();

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		parent::validate();

		$provider_widget = $this->ui->getWidget('provider');
		$provider = SwatDB::query(
			$this->app->db,
			sprintf(
				'select * from CMEProvider where id = %s',
				$this->app->db->quote($provider_widget->value, 'integer')
			),
			SwatDBClassMap::get('CMEProviderWrapper')
		)->getFirst();

		if ($this->isNew() && !$this->validateProvider($provider)) {
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
	// {{{ protected function validateProvider()

	protected function validateProvider(CMEProvider $provider = null)
	{
		return true;
	}

	// }}}
	// {{{ protected function updateObject()

	protected function updateObject()
	{
		// Required because of weird behaviour with sub-data-objects in
		// SwatDBDataObject. Setting a new provider id without first setting
		// the subobject to null will update the data-object internal value
		// to the new id but NOT update the loaded provider object itself. This
		// will cause save confirmation messages to display incorrectly.
		$this->getObject()->provider = null;

		parent::updateObject();

		if ($this->isNew()) {
			$credit = $this->getObject();
			$credit->planning_committee_no_disclosures =
				$this->getPlanningCommitteeNoDisclosures();

			$credit->support_staff_no_disclosures =
				$this->getSupportStaffNoDisclosures();

			$evaluation = $this->createEvaluation();
			if ($evaluation instanceof CMEEvaluation) {
				$credit->evaluation = $evaluation;
				$credit->evaluation->save();
			}
		}
	}

	// }}}
	// {{{ protected function createEvaluation()

	protected function createEvaluation()
	{
		$class_name = SwatDBClassMap::get('CMEEvaluation');
		$evaluation = new $class_name();
		$evaluation->createdate = new SwatDate();
		$evaluation->createdate->toUTC();
		$evaluation->setDatabase($this->app->db);
		return $evaluation;
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
	// {{{ protected function getSavedMessagePrimaryContent()

	protected function getSavedMessagePrimaryContent()
	{
		return sprintf(
			CME::_('%s CME front matter for %s has been saved.'),
			$this->getObject()->provider->title,
			$this->getTitle()
		);
	}

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntries(1);

		if ($this->isNew()) {
			$this->navbar->createEntry(CME::_('New CME Front Matter'));
		} else {
			$this->navbar->createEntry(CME::_('Edit CME Front Matter'));
		}
	}

	// }}}
}

?>
