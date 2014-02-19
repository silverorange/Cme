<?php

require_once 'Swat/SwatDisplayableContainer.php';
require_once 'Swat/SwatYUI.php';
require_once 'Site/pages/SiteDBEditPage.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionWrapper.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionOptionWrapper.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMEEvaluationWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEEvaluationPage extends SiteDBEditPage
{
	// {{{ protected properties

	/**
	 * @var CMECredit
	 */
	protected $credit;

	/**
	 * @var InquisitionInquisition
	 */
	protected $evaluation;

	/**
	 * @var InquisitionResponse
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
		return 'cme-evaluation-page-'.$this->credit->id;
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'credit' => array(0, null),
		);
	}

	// }}}
	// {{{ abstract protected function getCertificateURI()

	abstract protected function getCertificateURI();

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initCredit();
		$this->initEvaluation();
		$this->initResponse();

		if ($this->isComplete()) {
			$this->relocateForCompletedEvaluation();
		}

		$count = 0;
		foreach ($this->evaluation->question_bindings as $question_binding) {
			$this->addQuestionToUi($question_binding, ++$count);
		}
	}

	// }}}
	// {{{ protected function initCredit()

	protected function initCredit()
	{
		$credit_id = $this->getArgument('credit');

		$sql = sprintf(
			'select * from CMECredit where id = %s',
			$this->app->db->quote($credit_id, 'integer')
		);

		$this->credit = SwatBD::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		)->getFirst();

		if (!$this->credit instanceof CMECredit) {
			throw new SiteNotFoundException(
				sprintf(
					'CME credit %s not found.',
					$credit_id
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
			$this->evaluation = $this->credit->evaluation;

			if (!$this->evaluation instanceof InquisitionInquisition ||
				!$this->evaluation->enabled) {
				throw new SiteNotFoundException(
					'Evaluation not found for CME credit.'
				);
			}

			// efficiently load questions
			$bindings = $this->evaluation->question_bindings;
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
		$this->inquisition_response =
			$this->evaluation->getResponseByAccount(
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
		if ($this->inquisition_response !== null) {
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

		return ($response !== null && $response->complete_date !== null);
	}

	// }}}

	// process phase
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		$class = SwatDBClassMap::get('InquisitionResponse');
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

		foreach ($this->evaluation->question_bindings as $question_binding) {
			$view = $this->question_views[$question_binding->id];

			$response_value = $view->getResponseValue();
			$response_value->response = $this->inquisition_response->id;

			$this->inquisition_response->values[] = $response_value;
		}

		// save responses
		$this->inquisition_response->save();

		// clear CME hours cache for this account
		$key = 'cme-hours-'.$this->app->session->account->id;
		$this->app->deleteCacheValue($key, 'cme-hours');

		$this->app->messages->add($this->getMessage($form));
	}

	// }}}
	// {{{ protected function getMessage()

	protected function getMessage(SwatForm $form)
	{
		$message = new SwatMessage(
			sprintf(
				CME::_(
					'Thank you for completing the <em>%s</em> %s evaluation.'
				),
				SwatString::minimizeEntities($this->getTitle()),
				SwatString::minimizeEntities($this->credit->credit_type->title)
			)
		);

		if (!$this->credit->quiz instanceof InquisitionInquisition ||
			$this->app->session->account->isQuizPassed($this->credit)) {
			$message->secondary_content = sprintf(
				CME::_('You can now %sprint your certificate%s.'),
				sprintf(
					'<a href="%s">',
					SwatString::minimizeEntities(
						$this->getCertificateURI()
					)
				),
				'</a>'
			);
		}

		$message->content_type = 'text/xml';

		return $message;
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
			SwatString::minimizeEntities($this->credit->credit_type->title)
		);

		$this->layout->data->html_title = sprintf(
			CME::_('%s Evaluation - %s'),
			$this->credit->credit_type->title,
			$this->app->getHtmlTitle()
		);
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
		$this->layout->addHtmlHeadEntry('javascript/cme-evaluation-page.js');
	}

	// }}}
}

?>