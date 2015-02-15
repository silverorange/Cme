<?php

require_once 'Swat/SwatDisplayableContainer.php';
require_once 'Swat/SwatYUI.php';
require_once 'Site/pages/SiteDBEditPage.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionWrapper.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionOptionWrapper.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMEEvaluationWrapper.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';
require_once 'CME/dataobjects/CMEFrontMatterWrapper.php';
require_once 'CME/dataobjects/CMEEvaluationResponse.php';
require_once 'CME/dataobjects/CMEAccountEarnedCMECredit.php';
require_once 'CME/dataobjects/CMEAccountEarnedCMECreditWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEEvaluationPage extends SiteDBEditPage
{
	// {{{ protected properties

	/**
	 * @var CMECreditWrapper
	 */
	protected $credits;

	/**
	 * @var CMEAccountCMEProgress
	 */
	protected $progress;

	/**
	 * @var CMEFrontMatter
	 */
	protected $front_matter;

	/**
	 * @var CMEEvaluation
	 */
	protected $evaluation;

	/**
	 * @var CMEEvaluationResponse
	 */
	protected $inquisition_response;

	/**
	 * Saved references to question controls for processing because they are
	 * not part of the SwatUI.
	 *
	 * @var array
	 */
	protected $question_views = array();

	/**
	 * Array of response values indexed by question binding id for restoring
	 * form state from partially completed quiz
	 *
	 * @var array
	 */
	protected $response_values_by_binding_id = array();

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'CME/pages/cme-evaluation.xml';
	}

	// }}}
	// {{{ protected function getCacheKey()

	protected function getCacheKey()
	{
		return 'cme-evaluation-page-'.$this->progress->id;
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'credits' => array(0, null),
		);
	}

	// }}}
	// {{{ abstract protected function getCertificateURI()

	abstract protected function getCertificateURI();

	// }}}
	// {{{ abstract protected function getTitle()

	abstract protected function getTitle();

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initCredits();
		$this->initFrontMatter();
		$this->initProgress();
		$this->initEvaluation();
		$this->initResponse();

		if ($this->isComplete()) {
			// If earned credits were accidentally deleted but evaluation
			// is already complete, recreate them before relocating away from
			// page.
			$this->saveEarnedCredits();
			$this->relocateForCompletedEvaluation();
		}

		$count = 0;
		$question_bindings = $this->evaluation->visible_question_bindings;
		foreach ($question_bindings as $question_binding) {
			$this->addQuestionToUi($question_binding, ++$count);
		}
	}

	// }}}
	// {{{ protected function initCredits()

	protected function initCredits()
	{
		$ids = array();
		foreach (explode('-', $this->getArgument('credits')) as $id) {
			if ($id != '') {
				$ids[] = $this->app->db->quote($id, 'integer');
			}
		}

		if (count($ids) === 0) {
			throw new SiteNotFoundException('A CME credit must be provided.');
		}

		$sql = sprintf(
			'select CMECredit.* from CMECredit
				inner join CMEFrontMatter
					on CMECredit.front_matter = CMEFrontMatter.id
			where CMECredit.id in (%s) and CMEFrontMatter.enabled = %s',
			implode(',', $ids),
			$this->app->db->quote(true, 'boolean')
		);

		$this->credits = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		);

		if (count($this->credits) === 0) {
			throw new SiteNotFoundException(
				'No CME credits found for the ids provided.'
			);
		}
	}

	// }}}
	// {{{ protected function initFrontMatter()

	protected function initFrontMatter()
	{
		$this->front_matter = $this->credits->getFirst()->front_matter;
	}

	// }}}
	// {{{ protected function initProgress()

	protected function initProgress()
	{
		$account = $this->app->session->account;

		$progress = $this->getProgress();

		if (!$progress instanceof CMEAccountCMEProgress) {
			$class_name = SwatDBClassMap::get('CMEAccountCMEProgress');

			$progress = new $class_name();
			$progress->setDatabase($this->app->db);
			$progress->account = $account;
			$progress->save();

			foreach ($this->credits as $credit) {
				$sql = sprintf(
					'insert into AccountCMEProgressCreditBinding
						(progress, credit)
					values
						(%s, %s)',
					$this->app->db->quote($progress->id, 'integer'),
					$this->app->db->quote($credit->id, 'integer')
				);

				SwatDB::exec($this->app->db, $sql);
			}
		}

		$this->progress = $progress;
	}

	// }}}
	// {{{ protected function getProgress()

	protected function getProgress()
	{
		$first_run = true;
		$progress1 = null;

		foreach ($this->credits as $credit) {
			$progress2 = $this->app->session->account->getCMEProgress($credit);

			if ($first_run) {
				$first_run = false;

				$progress1 = $progress2;
			}

			$same_object = (
				$progress1 instanceof CMEAccountCMEProgress &&
				$progress2 instanceof CMEAccountCMEProgress &&
				$progress1->id === $progress2->id
			);

			$both_null = (
				!$progress1 instanceof CMEAccountCMEProgress &&
				!$progress2 instanceof CMEAccountCMEProgress
			);

			if ($same_object || $both_null) {
				$progress1 = $progress2;
			} else {
				throw new SiteNotFoundException(
					'CME credits do not share the same progress.'
				);
			}
		}

		return $progress1;
	}

	// }}}
	// {{{ protected function initEvaluation()

	protected function initEvaluation()
	{
		$this->evaluation = $this->app->getCacheValue($this->getCacheKey());

		if ($this->evaluation === false) {
			if (!$this->front_matter->evaluation instanceof CMEEvaluation) {
				throw new SiteNotFoundException(
					'Evaluation not found for CME front matter.'
				);
			}

			if (!$this->progress->evaluation instanceof CMEEvaluation) {
				$this->progress->evaluation = $this->generateEvaluation();
				$this->progress->save();
			}

			$this->evaluation = $this->progress->evaluation;

			// efficiently load questions
			$bindings = $this->evaluation->visible_question_bindings;
			$questions = $bindings->loadAllSubDataObjects(
				'question',
				$this->app->db,
				'select * from InquisitionQuestion where id in (%s)',
				SwatDBClassMap::get('InquisitionQuestionWrapper')
			);

			// efficiently load question options
			if ($questions instanceof InquisitionQuestionWrapper) {
				$options = $questions->loadAllSubRecordsets(
					'options',
					SwatDBClassMap::get('InquisitionQuestionOptionWrapper'),
					'InquisitionQuestionOption',
					'question',
					'',
					'displayorder, id'
				);
			}

			$this->addCacheValue($this->evaluation, $this->getCacheKey());
		} else {
			$this->evaluation->setDatabase($this->app->db);
		}
	}

	// }}}
	// {{{ protected function generateEvaluation()

	protected function generateEvaluation()
	{
		$class_name = SwatDBClassMap::get('CMEEvaluation');

		$evaluation = new $class_name();
		$evaluation->setDatabase($this->app->db);

		$evaluation->createdate = new SwatDate();
		$evaluation->createdate->toUTC();
		$evaluation->save();

		$this->generateEvaluationQuestions($evaluation);

		return $evaluation;
	}

	// }}}
	// {{{ protected function generateEvaluationQuestions()

	protected function generateEvaluationQuestions(CMEEvaluation $evaluation)
	{
		$question_bindings = SwatDB::query(
			$this->app->db,
			sprintf(
				'select * from InquisitionInquisitionQuestionBinding '.
				'where inquisition = %s',
				$this->app->db->quote(
					$this->front_matter->evaluation->id,
					'integer'
				)
			)
		);

		$class_name = SwatDBClassMap::get(
			'InquisitionInquisitionQuestionBinding'
		);

		$id_map = array();
		foreach ($question_bindings as $binding) {
			$binding_obj = new $class_name();
			$binding_obj->setDatabase($this->app->db);
			$binding_obj->inquisition = $evaluation->id;
			$binding_obj->question = $binding->question;
			$binding_obj->displayorder = $binding->displayorder;
			$binding_obj->save();

			$id_map[$binding->id] = $binding_obj->id;
		}

		$dependencies = SwatDB::query(
			$this->app->db,
			sprintf(
				'select * from InquisitionQuestionDependency '.
				'where question_binding in (%s)',
				$this->app->db->datatype->implodeArray(
					array_keys($id_map),
					'integer'
				)
			)
		);

		$class_name = SwatDBClassMap::get(
			'InquisitionInquisitionQuestionDependency'
		);

		foreach ($dependencies as $dependency) {
			$sql = sprintf(
				'insert into InquisitionQuestionDependency
				(question_binding, dependent_question_binding, option)
				values (%s, %s, %s)',
				$this->app->db->quote(
					$id_map[$dependency->question_binding],
					'integer'
				),
				$this->app->db->quote(
					$id_map[$dependency->dependent_question_binding],
					'integer'
				),
				$this->app->db->quote($dependency->option, 'integer')
			);

			SwatDB::exec($this->app->db, $sql);
		}
	}

	// }}}
	// {{{ protected function initResponse()

	protected function initResponse()
	{
		$this->inquisition_response = $this->evaluation->getResponseByAccount(
			$this->app->session->account
		);
	}

	// }}}
	// {{{ protected function addQuestionToUi()

	protected function addQuestionToUi(
		InquisitionInquisitionQuestionBinding $question_binding, $count)
	{
		$container = new SwatDisplayableContainer();
		$container->classes[] = 'question';
		$container->classes[] = 'question'.$count;

		$response_value = $this->getResponseValue($question_binding);
		$view = $question_binding->getView();
		$this->question_views[$question_binding->id] = $view;

		$widget = $view->getWidget($response_value);
		if ($widget instanceof SwatInputControl) {
			$widget->show_field_title_in_messages = false;
		}

		$form_field = new SwatFormField();
		$form_field->show_colon = false;
		$form_field->title = $question_binding->question->bodytext;
		$form_field->title_content_type = 'text/xml';
		$form_field->addChild($widget);

		if ($widget instanceof SwatContainer) {
			$form_field->display_messages = false;
			$form_field->required_status_display = SwatFormField::SHOW_NONE;
		} else {
			$form_field->required_status_display = SwatFormField::SHOW_OPTIONAL;
		}

		$container->addChild($form_field);

		// add to UI
		$this->ui->getWidget('question_container')->add($container);
	}

	// }}}
	// {{{ protected function getResponseValue()

	protected function getResponseValue(
		InquisitionInquisitionQuestionBinding $question_binding)
	{
		$value = null;

		// get response value if it exists
		if ($this->inquisition_response instanceof CMEEvaluationResponse) {
			$binding_id = $question_binding->id;

			if (isset($this->response_values_by_binding_id[$binding_id])) {
				$value = $this->response_values_by_binding_id[$binding_id];
			}
		}

		return $value;
	}

	// }}}
	// {{{ protected function isComplete()

	protected function isComplete()
	{
		$response = $this->inquisition_response;

		return (
			$response instanceof CMEEvaluationResponse &&
			$response->complete_date instanceof SwatDate
		);
	}

	// }}}

	// process phase
	// {{{ protected function processForm()

	protected function processForm(SwatForm $form)
	{
		if ($this->authenticate($form)) {
			$this->preProcessForm($form);

			parent::processForm($form);
		}
	}

	// }}}
	// {{{ protected function preProcessForm()

	protected function preProcessForm(SwatForm $form)
	{
		$bindings = $this->evaluation->visible_question_bindings;
		foreach ($bindings as $binding) {
			$this->preProcessQuestionBinding($binding);
		}
	}

	// }}}
	// {{{ protected function preProcessQuestionBinding()

	protected function preProcessQuestionBinding($question_binding)
	{
		$bindings = $this->evaluation->visible_question_bindings;
		$question = $question_binding->question;
		$options = $question_binding->getDependentOptions();
		$widget = $this->question_views[$question_binding->id]->getWidget();

		if (count($options) > 0) {
			foreach ($options as $option) {
				$this->preProcessQuestionBinding($bindings[$option['binding']]);
			}

			// If the question view isn't visible then remove any data
			// that may have been submited to it. Prevents the form from
			// not validating when imcomplete data is entered on hidden widgets
			if ($this->questionViewIsVisible($question_binding)) {
				$widget->required = $question->required;
			} else {
				$widget->required = false;

				$form = $widget->getForm();
				$data = &$form->getFormData();

				unset($data[$widget->id]);
			}
		} else {
			$widget->required = $question->required;
		}

		if (!$widget->isProcessed()) {
			$widget->process();
		}
	}

	// }}}
	// {{{ protected function questionViewIsVisible()

	protected function questionViewIsVisible(
		InquisitionInquisitionQuestionBinding $question_binding)
	{
		$question_view_visible = true;

		// If the question view is dependent on other options, check to make
		// sure all dependent options are available and selected.
		if (count($question_binding->getDependentOptions()) > 0) {

			foreach ($question_binding->getDependentOptions() as $option) {
				$question_view_visible = false;

				// Check to make sure the dependent view exists. If
				// InquisitionQuestion.enabled has been set to false, it will
				// not exist in the available question views.
				if (isset($this->question_views[$option['binding']])) {
					$view = $this->question_views[$option['binding']];

					$values = $view->getResponseValue();

					if (!is_array($values)) {
						$values = array($values);
					}

					foreach ($values as $value) {
						$selected = $value->getInternalValue('question_option');

						foreach ($option['options'] as $dependent) {
							// If the option this question depends on has been
							// selected than this question's widget is required.
							if ($selected === $dependent) {
								$question_view_visible = true;
							}
						}
					}
				}
			}
		}

		return $question_view_visible;
	}

	// }}}
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		$class = SwatDBClassMap::get('CMEEvaluationResponse');
		$this->inquisition_response = new $class();
		$this->inquisition_response->setDatabase($this->app->db);

		$this->inquisition_response->account =
			$this->app->session->account->id;

		$this->inquisition_response->inquisition =
			$this->evaluation->id;

		$this->inquisition_response->createdate = new SwatDate();
		$this->inquisition_response->createdate->toUTC();

		// set complete date
		$wrapper = SwatDBClassMap::get('InquisitionResponseValueWrapper');
		$this->inquisition_response->complete_date = new SwatDate();
		$this->inquisition_response->complete_date->toUTC();
		$this->inquisition_response->values = new $wrapper();

		$question_bindings = $this->evaluation->visible_question_bindings;
		foreach ($question_bindings as $question_binding) {
			$view = $this->question_views[$question_binding->id];

			$response_id = $this->inquisition_response->id;
			$response_value = $view->getResponseValue();

			if (is_array($response_value)) {
				$response_value_array = $response_value;
				foreach ($response_value_array as $response_value) {
					$response_value->response = $response_id;
					$this->inquisition_response->values[] = $response_value;
				}
			} else {
				$response_value->response = $response_id;
				$this->inquisition_response->values[] = $response_value;
			}
		}

		// save responses
		$this->inquisition_response->save();
		$this->saveEarnedCredits();

		// clear CME hours cache for this account
		$key = 'cme-hours-'.$this->app->session->account->id;
		$this->app->deleteCacheValue($key, 'cme-hours');

		$this->app->messages->add($this->getMessage($form));
	}

	// }}}
	// {{{ protected function saveEarnedCredits()

	protected function saveEarnedCredits()
	{
		$account = $this->app->session->account;
		$wrapper = SwatDBClassMap::get('CMEAccountEarnedCMECreditWrapper');
		$class_name = SwatDBClassMap::get('CMEAccountEarnedCMECredit');
		$earned_credits = new $wrapper();
		$earned_date = new SwatDate();
		$earned_date->toUTC();
		foreach ($this->front_matter->credits as $credit) {
			if ($credit->isEarned($account)) {
				// check for existing earned credit before saving
				$sql = sprintf(
					'select count(1)
					from AccountEarnedCMECredit
					where credit = %s and account = %s',
					$this->app->db->quote($credit->id, 'integer'),
					$this->app->db->quote($account->id, 'integer')
				);

				if (SwatDB::queryOne($this->app->db, $sql) == 0) {
					$earned_credit = new $class_name();
					$earned_credit->account = $account->id;
					$earned_credit->credit = $credit->id;
					$earned_credit->earned_date = $earned_date;
					$earned_credits->add($earned_credit);
				}
			}
		}
		$earned_credits->setDatabase($this->app->db);
		$earned_credits->save();
	}

	// }}}
	// {{{ protected function getMessage()

	protected function getMessage(SwatForm $form)
	{
		$formatted_title = sprintf(
			'<em>%s</em>',
			SwatString::minimizeEntities($this->getTitle())
		);

		$message = new SwatMessage(
			sprintf(
				CME::_(
					'Thank you for completing the %s %s evaluation.'
				),
				$formatted_title,
				SwatString::minimizeEntities(
					$this->front_matter->getProviderTitleList()
				)
			)
		);

		$message->secondary_content = $this->getMessageSecondaryContent($form);
		$message->content_type = 'text/xml';

		return $message;
	}

	// }}}
	// {{{ protected function getMessageSecondaryContent()

	protected function getMessageSecondaryContent(SwatForm $form)
	{
		return null;
	}

	// }}}
	// {{{ abstract protected function relocateForCompletedEvaluation()

	abstract protected function relocateForCompletedEvaluation();

	// }}}

	// build phase
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		$this->layout->data->title = sprintf(
			CME::_('%s Evaluation'),
			SwatString::minimizeEntities(
				$this->front_matter->getProviderTitleList()
			)
		);
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->layout->startCapture('content');
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$questions = array();

		$question_bindings = $this->evaluation->visible_question_bindings;
		foreach ($question_bindings as $question_binding) {
			$question = array();

			$question['binding'] = $question_binding->id;
			$question['question'] = $question_binding->question->id;
			$question['dependencies'] =
				$question_binding->getDependentOptions();

			$questions[] = $question;
		}

		$javascript = sprintf(
			'new CMEEvaluationPage(%s);',
			json_encode($questions)
		);

		return $javascript;
	}

	// }}}
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addBodyClass('cme-evaluation-page');

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());
		$this->layout->addHtmlHeadEntry(
			'packages/cme/javascript/cme-evaluation-page.js'
		);
	}

	// }}}
}

?>
