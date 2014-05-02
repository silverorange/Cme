<?php

require_once 'Swat/SwatMessage.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Inquisition/admin/components/Inquisition/Edit.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';

/**
 * Edit page for inquisitions
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEInquisitionEdit extends InquisitionInquisitionEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var CMECredit
	 */
	protected $credit;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initCredit();
		$this->initType();

		if ($this->type === 'quiz') {
			$this->ui->getWidget('quiz_fields')->visible = true;
			$this->setDefaultQuizValues();
		}
	}

	// }}}
	// {{{ protected function setDefaultQuizValues()

	protected function setDefaultQuizValues()
	{
		$this->ui->getWidget('resettable')->value = true;
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'CME/admin/components/Inquisition/edit.xml';
	}

	// }}}
	// {{{ protected function initCredit()

	protected function initCredit()
	{
		$credit_id = SiteApplication::initVar('credit');

		if ($credit_id !== null) {
			$class_name = SwatDBClassMap::get('CMECredit');
			$this->credit = new $class_name();
			$this->credit->setDatabase($this->app->db);
			if (!$this->credit->load($credit_id)) {
				throw new AdminNotFoundException(
					sprintf(
						'CME credit with id ‘%s’ not found.',
						$credit_id
					)
				);
			}
		} else {
			$sql = sprintf(
				'select * from CMECredit
				where evaluation = %1$s or quiz = %1$s',
				$this->app->db->quote($this->inquisition->id, 'integer')
			);

			$this->credit = SwatDB::query(
				$this->app->db,
				$sql,
				SwatDBClassMap::get('CMECreditWrapper')
			)->getFirst();
		}
	}

	// }}}
	// {{{ protected function initType()

	protected function initType()
	{
		if ($this->id === null) {
			$this->type = SiteApplication::initVar('type');
			if ($this->type === null) {
				throw new AdminNotFoundException('No type specified.');
			}
		} else {
			if ($this->id === $this->credit->getInternalValue('evaluation')) {
				$this->type = 'evaluation';
			} elseif ($this->id === $this->credit->getInternalValue('quiz')) {
				$this->type = 'quiz';
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		if ($this->inquisition->id === null) {
			$this->inquisition->enabled = true;
		}

		parent::saveDBData();

		switch ($this->type) {
		case 'evaluation':
			$this->credit->evaluation = $this->inquisition->id;
			break;

		case 'quiz':
			$this->credit->quiz = $this->inquisition->id;
			break;
		}

		if ($this->credit->isModified()) {
			$this->credit->save();
		}
	}

	// }}}
	// {{{ protected function updateInquisition()

	protected function updateInquisition()
	{
		parent::updateInquisition();

		$values = $this->ui->getValues(
			array(
				'description',
				'passing_grade',
				'email_content_pass',
				'email_content_fail',
				'resettable',
			)
		);

		$this->inquisition->description        = $values['description'];
		$this->inquisition->passing_grade      = $values['passing_grade'];
		$this->inquisition->email_content_pass = $values['email_content_pass'];
		$this->inquisition->email_content_fail = $values['email_content_fail'];
		$this->inquisition->resettable         = $values['resettable'];
	}

	// }}}
	// {{{ protected function getSavedMessage()

	protected function getSavedMessage()
	{
		return new SwatMessage(
			sprintf(
				CME::_('%s has been saved.'),
				$this->getTitle()
			)
		);
	}

	// }}}

	// build phase
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();

		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('type', $this->type);
		$form->addHiddenField('credit', $this->credit->id);
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$frame = $this->ui->getWidget('edit_frame');
		$frame->title = $this->getTitle();

		parent::buildInternal();

		$email_help_text = $this->ui->getWidget('email_help_text');
		$email_help_text->content = $this->getHelpText();
		$email_help_text->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$last = $this->navbar->popEntry();

		if ($this->id !== null) {
			$second_last = $this->navbar->popEntry();
		}

		$this->navbar->popEntry();

		if ($this->id === null) {
			$this->navbar->createEntry(
				CME::_(
					sprintf('New %s', $this->getTitle())
				)
			);
		} else {
			$second_last->title = $this->getTitle();
			$this->navbar->addEntry($second_last);
			$this->navbar->addEntry($last);
		}
	}

	// }}}
	// {{{ protected function getHelpText()

	protected function getHelpText()
	{
		return sprintf(
			'<p>%s</p>
			<ul>
				<li><strong>[account-full-name]</strong>%s</li>
				<li><strong>[quiz-grade]</strong>%s</li>
				<li><strong>[quiz-passing-grade]</strong>%s</li>
			</ul>',
			SwatString::minimizeEntities(
				CME::_('The following variables may be used in email content:')
			),
			SwatString::minimizeEntities(
				CME::_(' - the full name of the account holder')
			),
			SwatString::minimizeEntities(
				CME::_(' - the grade the account holder got on the quiz')
			),
			SwatString::minimizeEntities(
				CME::_(' - the grade required to pass the quiz')
			)
		);
	}

	// }}}
	// {{{ protected function getTitle()

	protected function getTitle()
	{
		switch ($this->type) {
		case 'evaluation':
			return sprintf(
				CME::_('%s Evaluation'),
				$this->credit->provider->title
			);

		default:
		case 'quiz':
			return sprintf(
				CME::_('%s Quiz'),
				$this->credit->provider->title
			);
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry('styles/inquisition-edit.css');
	}

	// }}}
}

?>
