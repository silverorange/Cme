<?php

require_once 'Swat/SwatDisplayableContainer.php';
require_once 'Swat/SwatYUI.php';
require_once 'Site/pages/SiteDBEditPage.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionWrapper.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionOptionWrapper.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMEEvaluationWrapper.php';
require_once 'CME/dataobjects/CMEFrontMatterWrapper.php';
require_once 'CME/dataobjects/CMEEvaluationResponse.php';
require_once 'CME/dataobjects/CMEAccountEarnedCMECredit.php';
require_once 'CME/dataobjects/CMEAccountEarnedCMECreditWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEEvaluationPage extends SiteDBEditPage
{
	// {{{ protected properties

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
		return 'cme-evaluation-page-'.$this->front_matter->id;
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'front_matter' => array(0, null),
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

		$this->initFrontMatter();
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
	// {{{ protected function initFrontMatter()

	protected function initFrontMatter()
	{
		$front_matter_id = $this->getArgument('front_matter');

		$sql = sprintf(
			'select * from CMEFrontMatter where id = %s and enabled = %s',
			$this->app->db->quote($front_matter_id, 'integer'),
			$this->app->db->quote(true, 'boolean')
		);

		$this->front_matter = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMEFrontMatterWrapper')
		)->getFirst();

		if (!$this->front_matter instanceof CMEFrontMatter) {
			throw new SiteNotFoundException(
				sprintf(
					'CME front matter %s not found.',
					$front_matter_id
				)
			);
		}
	}

	// }}}
	// {{{ protected function initEvaluation()

	protected function initEvaluation()
	{
		$this->evaluation = $this->app->getCacheValue($this->getCacheKey());

		if ($this->evaluation === false) {
			$this->evaluation = $this->front_matter->evaluation;

			if (!$this->evaluation instanceof CMEEvaluation) {
				throw new SiteNotFoundException(
					'Evaluation not found for CME front matter.'
				);
			}

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
			$form_field->required_status_display = null;
		} else {
			$form_field->required_status_display =
				SwatFormField::DISPLAY_OPTIONAL;
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
				$form = $widget->getForm();
				$data = &$form->getFormData();

				unset($data[$widget->id]);
			}
		} else {
			$widget->required = $question->required;

			if (!$widget->isProcessed()) {
				$widget->process();
			}
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
			// Only save the reponse values from visible questions
			if ($this->questionViewIsVisible($question_binding)) {
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
					$this->front_matter->provider->title
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
			SwatString::minimizeEntities($this->front_matter->provider->title)
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
