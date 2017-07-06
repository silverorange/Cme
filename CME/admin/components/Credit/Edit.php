<?php

/**
 * @package   CME
 * @copyright 2013-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMECreditEdit extends InquisitionInquisitionEdit
{
	// {{{ protected properties

	/**
	 * @var CMECredit
	 */
	protected $credit;

	/**
	 * @var integer
	 */
	protected $new_question_count;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/edit.xml';
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		AdminDBEdit::initInternal();

		$this->initCredit();
		$this->initInquisition();
		$this->initFrontMatter();

		$this->ui->loadFromXML($this->getUiXml());

		// hide question import field when editing an existing credit
		if ($this->credit->quiz instanceof CMEQuiz) {
			$this->ui->getWidget('questions_field')->visible = false;
		}

		$this->setDefaultValues();
	}

	// }}}
	// {{{ protected function initInquisition()

	protected function initInquisition()
	{
		if ($this->credit->quiz instanceof CMEQuiz) {
			$this->inquisition = $this->credit->quiz;
		} else {
			$class_name = SwatDBClassMap::get('CMEQuiz');
			$this->inquisition = new $class_name();
			$this->inquisition->setDatabase($this->app->db);
		}
	}

	// }}}
	// {{{ protected function initCredit()

	protected function initCredit()
	{
		$class_name = SwatDBClassMap::get('CMECredit');
		$this->credit = new $class_name();
		$this->credit->setDatabase($this->app->db);

		if (!$this->isNew()) {
			if (!$this->credit->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(
						'A CME credit with the id of ‘%s’ does not exist.',
						$this->id
					)
				);
			}
		} else {
			$this->credit->is_free =
				($this->app->initVar('credit_type') === 'free');
		}
	}

	// }}}
	// {{{ protected function initFrontMatter()

	protected function initFrontMatter()
	{
		if ($this->isNew()) {
			$front_matter_id = SiteApplication::initVar('front-matter');
			$class_name = SwatDBClassMap::get('CMEFrontMatter');
			$this->credit->front_matter = new $class_name();
			$this->credit->front_matter->setDatabase($this->app->db);
			if (!$this->credit->front_matter->load($front_matter_id)) {
				throw new AdminNotFoundException(
					sprintf(
						'A CME front matter with the id of ‘%s’ does not '.
						'exist.',
						$front_matter_id
					)
				);
			}
		}
	}

	// }}}
	// {{{ protected function getDefaultCreditHours()

	protected function getDefaultCreditHours()
	{
		return 1;
	}

	// }}}
	// {{{ protected function setDefaultValues()

	protected function setDefaultValues()
	{
		$this->ui->getWidget('hours')->value = $this->getDefaultCreditHours();
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		parent::validate();

		// Import questions file in validate step so we can show error
		// messages. The importer only modifies the inquisition object and does
		// not save it to the database.
		$questions_file = $this->ui->getWidget('questions_file');
		if ($questions_file->isUploaded()) {
			$this->importInquisition($questions_file->getTempFileName());
		}
	}

	// }}}
	// {{{ protected function importInquisition()

	protected function importInquisition($filename)
	{
		try {
			$file = new InquisitionFileParser($filename);
			$importer = new InquisitionImporter($this->app);
			$importer->importInquisition($this->inquisition, $file);
		} catch (InquisitionImportException $e) {
			$this->ui->getWidget('questions_file')->addMessage(
				new SwatMessage($e->getMessage())
			);
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updateInquisition();
		$modified = $this->inquisition->isModified();
		$this->inquisition->save();

		$this->updateCredit();
		$modified = $modified || $this->credit->isModified();
		$this->credit->save();

		if ($modified) {
			$this->app->messages->add($this->getSavedMessage());
		}
	}

	// }}}
	// {{{ protected function updateCredit()

	protected function updateCredit()
	{
		$values = $this->ui->getValues(
			array(
				'hours',
			)
		);

		$this->credit->hours = $values['hours'];
		$this->credit->quiz = $this->inquisition;
		$this->credit->front_matter = $this->credit->front_matter->id;

		// if hours updated, clear all cached hours for accounts
		if (!$this->isNew() &&
			$this->credit->hours != $values['hours']) {
			$this->app->memcache->flushNs('cme-hours');
		}
	}

	// }}}
	// {{{ protected function getSavedMessage()

	protected function getSavedMessage()
	{
		return new SwatMessage(
			sprintf(
				CME::_('%s has been saved.'),
				$this->credit->getTitle()
			)
		);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate(
			sprintf(
				'Credit/Details?id=%s',
				$this->credit->id
			)
		);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('edit_frame')->title = $this->credit->getTitle();

		$provider_titles = array();
		foreach ($this->credit->front_matter->providers as $provider) {
			$provider_titles[] = $provider->credit_title_plural;
		}

		$this->ui->getWidget('hours_field')->title =
			SwatString::toList($provider_titles);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		AdminDBEdit::buildNavBar();

		$this->navbar->popEntry();

		$title = $this->isNew()
			? CME::_('New %s')
			: CME::_('Edit %s');

		$this->navbar->createEntry(
			sprintf(
				$title,
				$this->credit->getTitle()
			)
		);
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();

		if ($this->isNew()) {
			$this->ui->getWidget('edit_form')->addHiddenField(
				'front-matter',
				$this->credit->front_matter->id
			);
		}
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		parent::loadDBData();

		$this->ui->setValues($this->credit->getAttributes());
	}

	// }}}
}

?>
