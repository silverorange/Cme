<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Inquisition/InquisitionFileParser.php';
require_once 'Inquisition/InquisitionImporter.php';
require_once 'Inquisition/admin/components/Inquisition/Edit.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECredit.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';

/**
 * @package   CME
 * @copyright 2013-2014 silverorange
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
	 * @var CMEFrontMatter
	 */
	protected $front_matter;

	/**
	 * @var integer
	 */
	protected $new_question_count;

	// }}}
	// {{{ abstract protected function getTitle()

	abstract protected function getTitle();

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'CME/admin/components/Credit/edit.xml';
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
		if ($this->inquisition->id !== null) {
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

		if ($this->id !== null) {
			if (!$this->credit->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(
						'A CME credit with the id of ‘%s’ does not exist.',
						$this->id
					)
				);
			}
		}
	}

	// }}}
	// {{{ protected function initFrontMatter()

	protected function initFrontMatter()
	{
		if ($this->credit->id === null) {
			$front_matter_id = SiteApplication::initVar('front-matter');
			$class_name = SwatDBClassMap::get('CMEFrontMatter');
			$this->front_matter = new $class_name();
			$this->front_matter->setDatabase($this->app->db);
			if (!$this->front_matter->load($front_matter_id)) {
				throw new AdminNotFoundException(
					sprintf(
						'A CME front matter with the id of ‘%s’ does not '.
						'exist.',
						$front_matter_id
					)
				);
			}
		} else {
			$this->front_matter = $this->credit->front_matter;
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

		$questions_file = $this->ui->getWidget(
			'questions_file'
		)->getTempFileName();

		// Import questions file in validate step so we can show error
		// messages. The importer only modifies the inquisition object and does
		// not save it to the database.
		if ($questions_file !== null) {
			$this->importInquisition($questions_file);
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
				'passing_grade',
				'email_content_pass',
				'email_content_fail',
				'resettable',
			)
		);

		$this->credit->hours              = $values['hours'];
		$this->credit->passing_grade      = $values['passing_grade'];
		$this->credit->email_content_pass = $values['email_content_pass'];
		$this->credit->email_content_fail = $values['email_content_fail'];
		$this->credit->resettable         = $values['resettable'];

		$this->credit->quiz = $this->inquisition;
		$this->credit->front_matter = $this->front_matter->id;

		// if hours updated, clear all cached hours for accounts
		if ($this->id !== null &&
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
				CME::_('%s CME Credit for %s has been saved.'),
				$this->credit->front_matter->provider->title,
				$this->getTitle()
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
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		AdminDBEdit::buildNavBar();

		$this->navbar->popEntry();

		if ($this->credit->id === null) {
			$this->navbar->createEntry(CME::_('New CME Credit'));
		} else {
			$this->navbar->createEntry(CME::_('Edit CME Credit'));
		}
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();

		if ($this->credit->id === null) {
			$this->ui->getWidget('edit_form')->addHiddenField(
				'front-matter',
				$this->front_matter->id
			);
		}
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		parent::loadDBData();

		$this->ui->setValues(get_object_vars($this->credit));
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/cme/admin/styles/cme-credit-edit.css'
		);
	}

	// }}}
}

?>
