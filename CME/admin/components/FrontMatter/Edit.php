<?php

/**
 * @package   CME
 * @copyright 2013-2016 silverorange
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
			'objectives',
			'release_date',
			'review_date',
			'enabled',
			'passing_grade',
			'email_content_pass',
			'email_content_fail',
			'resettable',
		);
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// add available providers
		$providers_widget = $this->ui->getWidget('providers');
		$providers = $this->getAvailableProviders();
		foreach ($providers as $provider) {
			$providers_widget->addOption(
				$provider->id,
				$provider->title
			);
		}

		if ($this->isNew()) {
			$this->setDefaultProviders($providers);
			$this->setDefaultValues();

			// if there's just one provider, select it by default
			if (count($providers) === 1) {
				$providers_widget->values = array($providers->getFirst()->id);
			}
		}
	}

	// }}}
	// {{{ protected function getDefaultProviderShortnames()

	/**
	 * @return array the default provider shortnames. If an empty array is
	 *                returned, the first provider in the list is selected
	 *                by default.
	 */
	protected function getDefaultProviderShortnames()
	{
		return array();
	}

	// }}}
	// {{{ protected function getAvailableProviders()

	protected function getAvailableProviders()
	{
		return SwatDB::query(
			$this->app->db,
			'select * from CMEProvider order by id',
			SwatDBClassMap::get('CMEProviderWrapper')
		);
	}

	// }}}
	// {{{ protected function setDefaultProviders()

	protected function setDefaultProviders(CMEProviderWrapper $providers)
	{
		$shortnames = $this->getDefaultProviderShortnames();
		if (count($shortnames) > 0) {
			$sql = sprintf(
				'select id from CMEProvider where shortname in (%s)',
				$this->app->db->datatype->implodeArray($shortnames, 'text')
			);

			$rows = SwatDB::query($this->app->db, $sql);
			foreach ($rows as $row) {
				$this->ui->getWidget('providers')->values[] = $row->id;
			}
		}

		if (count($this->ui->getWidget('providers')->values) === 0) {
			$this->ui->getWidget('providers')->values[] =
				$providers->getFirst()->id;
		}
	}

	// }}}
	// {{{ protected function setDefaultValues()

	protected function setDefaultValues()
	{
		$this->ui->getWidget('enabled')->value = true;
		$this->ui->getWidget('passing_grade')->value =
			$this->getDefaultPassingGrade();

		$this->ui->getWidget('objectives')->value =
			$this->getDefaultObjectives();
	}

	// }}}
	// {{{ protected function getDefaultPassingGrade()

	protected function getDefaultPassingGrade()
	{
		return 0.70;
	}

	// }}}
	// {{{ protected function getDefaultObjectives()

	protected function getDefaultObjectives()
	{
		return <<<HTML
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

		$class_name = SwatDBClassMap::get('CMEProviderWrapper');
		$providers = new $class_name();
		$providers->setDatabase($this->app->db);
		$providers->load($this->ui->getWidget('providers')->values);

		foreach ($providers as $provider) {
			if ($this->isNew() && !$this->validateProvider($provider)) {
				$message = new SwatMessage(
					sprintf(
						CME::_('%s already has %s CME.'),
						$this->getTitle(),
						$provider->title
					),
					'error'
				);
				$this->ui->getWidget('providers')->addMessage($message);
			}
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
		parent::updateObject();

		if ($this->isNew()) {
			$front_matter = $this->getObject();
			$front_matter->planning_committee_no_disclosures =
				$this->getPlanningCommitteeNoDisclosures();

			$front_matter->planning_committee_with_disclosures =
				$this->getPlanningCommitteeWithDisclosures();

			$front_matter->support_staff_no_disclosures =
				$this->getSupportStaffNoDisclosures();

			$front_matter->support_staff_with_disclosures =
				$this->getSupportStaffWithDisclosures();

			$evaluation = $this->createEvaluation();
			if ($evaluation instanceof CMEEvaluation) {
				$front_matter->evaluation = $evaluation;
				$front_matter->evaluation->save();
			}
		}
	}

	// }}}
	// {{{ protected function postSaveObject()

	protected function postSaveObject()
	{
		parent::postSaveObject();

		$this->saveProviderBindingTable();
	}

	// }}}
	// {{{ protected function saveProviderBindingTable()

	protected function saveProviderBindingTable()
	{
		$front_matter = $this->getObject();

		$providers = $this->ui->getWidget('providers')->values;

		$delete_sql = sprintf(
			'delete from CMEFrontMatterProviderBinding
			where front_matter = %s',
			$this->app->db->quote($front_matter->id, 'integer')
		);

		SwatDB::exec($this->app->db, $delete_sql);

		$insert_sql = sprintf(
			'insert into CMEFrontMatterProviderBinding
			(front_matter, provider)
			select %s, id from CMEProvider where id in (%s)',
			$this->app->db->quote($front_matter->id, 'integer'),
			$this->app->db->datatype->implodeArray($providers, 'integer')
		);

		SwatDB::exec($this->app->db, $insert_sql);
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
	// {{{ protected function getPlanningCommitteeNoDisclosures()

	protected function getPlanningCommitteeNoDisclosures()
	{
		return '';
	}

	// }}}
	// {{{ protected function getPlanningCommitteeWithDisclosures()

	protected function getPlanningCommitteeWithDisclosures()
	{
		return '';
	}

	// }}}
	// {{{ protected function getSupportStaffNoDisclosures()

	protected function getSupportStaffNoDisclosures()
	{
		return '';
	}

	// }}}
	// {{{ protected function getSupportStaffWithDisclosures()

	protected function getSupportStaffWithDisclosures()
	{
		return '';
	}

	// }}}
	// {{{ protected function getSavedMessagePrimaryContent()

	protected function getSavedMessagePrimaryContent()
	{
		return sprintf(
			CME::_('%s CME front matter for %s has been saved.'),
			$this->getObject()->getProviderTitleList(),
			$this->getTitle()
		);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildEmailHelp();
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		parent::loadDBData();

		foreach ($this->getObject()->providers as $provider) {
			$this->ui->getWidget('providers')->values[] = $provider->id;
		}
	}

	// }}}
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
	// {{{ protected function buildEmailHelp()

	protected function buildEmailHelp()
	{
		$help = $this->ui->getWidget('email_help_text');

		ob_start();

		$p_tag = new SwatHtmlTag('p');
		$p_tag->setContent(
			CME::_(
				'The following variables may be used in email content:'
			)
		);
		$p_tag->display();

		echo '<table><tbody>';

		$definitions = $this->getEmailHelpVariableDefinitions();
		$keys = array_keys($definitions);
		$half_index = ceil(count($definitions) / 2);
		for ($i = 0; $i < $half_index; $i++) {
			echo '<tr>';

			$th = new SwatHtmlTag('th');
			$th->setContent('['.$keys[$i].']');
			$th->display();

			$td = new SwatHtmlTag('td');
			$td->setContent($definitions[$keys[$i]]);
			$td->display();

			if (isset($keys[$i + $half_index])) {
				$th = new SwatHtmlTag('th');
				$th->setContent('['.$keys[$i + $half_index].']');
				$th->display();

				$td = new SwatHtmlTag('td');
				$td->setContent($definitions[$keys[$i + $half_index]]);
				$td->display();
			}
			echo '</tr>';
		}

		echo '</tbody></table>';

		$help->content = ob_get_clean();
		$help->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function getEmailHelpVariableDefinitions()

	protected function getEmailHelpVariableDefinitions()
	{
		return array(
			'account-full-name' => CME::_(
				'the full name of the user'
			),
			'cme-certificate-link' => CME::_(
				'the link to download the CME certificates'
			),
			'quiz-grade' => CME::_(
				'the grade the user got on the quiz'
			),
			'quiz-passing-grade' => CME::_(
				'the grade required to pass the quiz'
			),
		);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/cme/admin/styles/cme-front-matter-edit.css'
		);
	}

	// }}}
}

?>
