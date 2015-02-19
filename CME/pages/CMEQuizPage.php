<?php

require_once 'Swat/SwatYUI.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Swat/SwatDisplayableContainer.php';
require_once 'Swat/SwatContentBlock.php';
require_once 'Swat/SwatMessageDisplay.php';
require_once 'Site/pages/SiteDBEditPage.php';
require_once 'Inquisition/dataobjects/InquisitionInquisitionWrapper.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionWrapper.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionOptionWrapper.php';
require_once 'CME/CMEFrontMatterCompleteMailMessage.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';
require_once 'CME/dataobjects/CMEAccountEarnedCMECredit.php';

/**
 * @package   CME
 * @copyright 2011-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEQuizPage extends SiteDBEditPage
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
	 * @var InquisitionInquisition
	 */
	protected $quiz;

	/**
	 * @var InquisitionResponse
	 */
	protected $response;

	/**
	 * Saved references to question controls for processing because they are
	 * not part of the SwatUI.
	 *
	 * @var array
	 */
	protected $question_views = array();

	/**
	 * Array of response values indexed by question id for restoring form state
	 * from partially completed quiz
	 *
	 * @var array
	 */
	protected $response_values_by_binding_id = array();

	/**
	 * @var SwatMessageDisplay
	 */
	protected $response_message_display;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'CME/pages/cme-quiz.xml';
	}

	// }}}
	// {{{ protected function getCacheKey()

	protected function getCacheKey()
	{
		return 'cme-quiz-page-'.$this->progress->id;
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
	// {{{ abstract protected function getEvaluationURI()

	abstract protected function getEvaluationURI();

	// }}}
	// {{{ abstract protected function getCertificateURI()

	abstract protected function getCertificateURI();

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initCredits();
		$this->initFrontMatter();
		$this->initProgress();
		$this->initQuiz();
		$this->initResponse();

		// if there is no quiz, go to evaluation page
		if (!$this->quiz instanceof CMEQuiz) {
			if (!$this->response->complete_date instanceof SwatDate) {
				$this->relocateToEvaluation();
			}
		}

		if ($this->isComplete()) {
			// If earned credit was accidentally deleted but quiz is already
			// complete, recreate earned credit before displaying quiz results.
			$this->saveEarnedCredit();
		} else {
			foreach ($this->quiz->question_bindings as $question_binding) {
				$this->addQuestionToUi($question_binding);
			}
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
	// {{{ protected function initQuiz()

	protected function initQuiz()
	{
		$this->quiz = $this->app->getCacheValue($this->getCacheKey());

		if ($this->quiz === false) {
			if (!$this->progress->quiz instanceof CMEQuiz) {
				$this->progress->quiz = $this->generateQuiz();
				$this->progress->save();
			}

			$this->quiz = $this->progress->quiz;

			if (!$this->quiz instanceof CMEQuiz) {
				throw new SiteNotFoundException(
					'Quiz not found for CME credit.'
				);
			}

			$questions = $this->quiz->question_bindings->loadAllSubDataObjects(
				'question',
				$this->app->db,
				'select * from InquisitionQuestion where id in (%s)',
				SwatDBClassMap::get('InquisitionQuestionWrapper')
			);

			if ($questions instanceof InquisitionQuestionWrapper) {
				// efficiently load correct options
				$questions->loadAllSubDataObjects(
					'correct_option',
					$this->app->db,
					'select * from InquisitionQuestionOption where id in (%s)',
					SwatDBClassMap::get('InquisitionQuestionOptionWrapper')
				);

				// efficiently load question options
				$questions->loadAllSubRecordsets(
					'options',
					SwatDBClassMap::get('InquisitionQuestionOptionWrapper'),
					'InquisitionQuestionOption',
					'question',
					'',
					'displayorder, id'
				);
			}

			$this->addCacheValue($this->quiz, $this->getCacheKey());
		} else {
			$this->quiz->setDatabase($this->app->db);
		}
	}

	// }}}
	// {{{ protected function generateQuiz()

	protected function generateQuiz()
	{
		$class_name = SwatDBClassMap::get('CMEQuiz');

		$quiz = new $class_name();
		$quiz->setDatabase($this->app->db);

		$quiz->createdate = new SwatDate();
		$quiz->createdate->toUTC();
		$quiz->save();

		$this->generateQuizQuestions($quiz);

		return $quiz;
	}

	// }}}
	// {{{ protected function generateQuizQuestions()

	protected function generateQuizQuestions(CMEQuiz $quiz)
	{
		$count = 0;

		foreach ($this->credits as $credit) {
			if ($credit->quiz instanceof CMEQuiz) {
				foreach ($credit->quiz->question_bindings as $binding) {
					$sql = sprintf(
						'insert into InquisitionInquisitionQuestionBinding
						(inquisition, question, displayorder) values
						(%s, %s, %s)',
						$this->app->db->quote($quiz->id, 'integer'),
						$this->app->db->quote($binding->question->id, 'integer'),
						$this->app->db->quote($count, 'integer')
					);

					SwatDB::exec($this->app->db, $sql);
					$count++;
				}
			}
		}
	}

	// }}}
	// {{{ protected function initResponse()

	protected function initResponse()
	{
		$this->response = $this->quiz->getResponseByAccount(
			$this->app->session->account
		);

		if ($this->response instanceof InquisitionResponse) {
			// efficiently load question options for response values
			$this->response->values->loadAllSubDataObjects(
				'question_option',
				$this->app->db,
				'select * from InquisitionQuestionOption where id in (%s)',
				SwatDBClassMap::get('InquisitionQuestionOptionWrapper')
			);

			// efficiently load question bindings for response values
			$question_bindings = $this->response->values->loadAllSubDataObjects(
				'question_binding',
				$this->app->db,
				'select * from InquisitionInquisitionQuestionBinding
					where id in (%s)',
				SwatDBClassMap::get(
					'InquisitionInquisitionQuestionBindingWrapper'
				)
			);

			// efficiently load questions for question bindings
			if ($question_bindings instanceof
				InquisitionInquisitionQuestionBindingWrapper) {
				$question_bindings->loadAllSubDataObjects(
					'question',
					$this->app->db,
					'select * from InquisitionQuestion where id in (%s)',
					SwatDBClassMap::get('InquisitionQuestionWrapper')
				);
			}

			// index responses by question binding id
			foreach ($this->response->values as $value) {
				$binding_id = $value->question_binding->id;
				$this->response_values_by_binding_id[$binding_id] = $value;
			}
		}
	}

	// }}}
	// {{{ protected function addQuestionToUi()

	protected function addQuestionToUi(
		InquisitionInquisitionQuestionBinding $question_binding)
	{
		$container = new SwatDisplayableContainer();
		$container->classes[] = 'question';

		$content_container = new SwatDisplayableContainer();
		$content_container->classes[] = 'question-bodytext';

		$content_block = new SwatContentBlock();
		$content_block->content = $question_binding->question->bodytext;
		$content_block->content_type = 'text/xml';

		$content_container->addChild($content_block);
		$container->addChild($content_container);

		$response_value = $this->getResponseValue($question_binding);
		$view = $question_binding->getView();
		$this->question_views[$question_binding->id] = $view;

		$form_field = new SwatFormField();
		$form_field->display_messages = false;
		$form_field->addChild($view->getWidget($response_value));

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
		if ($this->response instanceof InquisitionResponse) {
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
		return ($this->response instanceof InquisitionResponse &&
			$this->response->complete_date instanceof SwatDate);
	}

	// }}}

	// process phase
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		switch ($form->id) {
		case 'quiz_form' :
			$this->saveQuizData($form);
			break;
		case 'reset_form' :
			$this->resetQuiz($form);
			break;
		}
	}

	// }}}
	// {{{ protected function saveQuizData()

	protected function saveQuizData(SwatForm $form)
	{
		if (!$this->response instanceof InquisitionResponse) {
			$class_name = SwatDBClassMap::get('CMEQuizResponse');
			$this->response = new $class_name();

			$this->response->account     = $this->app->session->account->id;
			$this->response->inquisition = $this->quiz->id;
			$this->response->createdate  = new SwatDate();
			$this->response->createdate->toUTC();
		} else {
			// delete old response values
			$this->response->values->delete();
		}

		// set complete date
		$this->response->complete_date = new SwatDate();
		$this->response->complete_date->toUTC();

		// save response
		$this->response->setDatabase($this->app->db);
		$this->response->save();

		// set new response values
		$wrapper = SwatDBClassMap::get('InquisitionResponseValueWrapper');
		$response_values = new $wrapper();

		foreach ($this->quiz->question_bindings as $question_binding) {
			$view = $this->question_views[$question_binding->id];

			$response_value = $view->getResponseValue();
			$response_value->response = $this->response->id;

			$response_values[] = $response_value;
		}

		$this->response->values = $response_values;

		// save response values
		$this->response->values->save();
		$this->saveEarnedCredit();
		$this->sendCompletionEmail();
		$this->addCompletionMessage();

		// clear CME hours cache for this account
		$key = 'cme-hours-'.$this->app->session->account->id;
		$this->app->deleteCacheValue($key, 'cme-hours');
	}

	// }}}
	// {{{ protected function saveEarnedCredit()

	protected function saveEarnedCredit()
	{
		$account = $this->app->session->account;

		foreach ($this->credits as $credit) {
			if ($credit->isEarned($account)) {
				// check for existing earned credit before saving
				$sql = sprintf(
					'select count(1)
					from AccountEarnedCMECredit
					where credit = %s and account = %s',
					$this->app->db->quote($credit->id, 'integer'),
					$this->app->db->quote($account->id, 'integer')
				);

				if (SwatDB::queryOne($this->app->db, $sql) === 0) {
					$earned_date = new SwatDate();
					$earned_date->toUTC();

					$class_name = SwatDBClassMap::get(
						'CMEAccountEarnedCMECredit'
					);

					$earned_credit = new $class_name();
					$earned_credit->setDatabase($this->app->db);

					$earned_credit->account = $account->id;
					$earned_credit->credit = $this->credit->id;
					$earned_credit->earned_date = $earned_date;

					$earned_credit->save();
				}
			}
		}
	}

	// }}}
	// {{{ protected function resetQuiz()

	protected function resetQuiz(SwatForm $form)
	{
		// response can be null when refreshing the quiz page immediately after
		// resetting a quiz, or resetting it in another window, and attempting
		// to reset a second time.
		if (!$this->front_matter->resettable ||
			!$this->response instanceof InquisitionResponse) {
			return;
		}

		$now = new SwatDate();
		$now->toUTC();

		$sql = sprintf(
			'update InquisitionResponse set
			reset_date = %s where id = %s',
			$this->app->db->quote($now->getDate(), 'date'),
			$this->app->db->quote($this->response->id, 'integer')
		);

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function sendCompletionEmail()

	protected function sendCompletionEmail()
	{
		// only send email if credits are earned
		$account = $this->app->session->account;
		if (!$this->credits->getFirst()->isEarned($account)) {
			return;
		}

		try {
			$class_name = $this->getCompletionEmailClass();
			$message = new $class_name(
				$this->app,
				$account,
				$this->front_matter,
				$this->response
			);
			$message->send();
		} catch (SiteMailException $e) {
			$e->processAndContinue();
		}
	}

	// }}}
	// {{{ protected function addCompletionMessage()

	protected function addCompletionMessage()
	{
		if ($this->response->isPassed()) {
			$message = new SwatMessage(
				sprintf(
					CME::_('Congratulations on passing the %s quiz'),
					$this->getQuizTitle()
				)
			);

			$account = $this->app->session->account;
			if (!$account->isEvaluationComplete($this->credits->getFirst())) {
				$message->secondary_content = CME::_(
					'Take a moment to complete this evaluation, and then '.
					'you’ll be able to print your certificate.'
				);
			}

			$this->app->messages->add($message);
		}
	}

	// }}}
	// {{{  protected function relocateToCertificate()

	 protected function relocateToCertificate()
	 {
		 $this->app->relocate($this->getCertificateURI());
	 }

	// }}}
	// {{{  protected function relocateToEvaluation()

	 protected function relocateToEvaluation()
	 {
		 $this->app->relocate($this->getEvaluationURI());
	 }

	// }}}
	// {{{ abstract protected function getCompletionEmailClass()

	abstract protected function getCompletionEmailClass();

	// }}}
	// {{{ abstract protected function getQuizTitle()

	abstract protected function getQuizTitle();

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->isComplete()) {
			$this->buildQuizResponse();
		} else {

			ob_start();
			$this->displayQuizDetailsIncomplete();
			$content_block = $this->ui->getWidget('quiz_content');
			$content_block->content = ob_get_clean();
			$content_block->content_type = 'text/xml';

		}
	}

	// }}}
	// {{{ protected function buildQuizResponse()

	protected function buildQuizResponse()
	{
		// quiz description
		$description = $this->getQuizDescription();
		if ($description != '') {
			ob_start();
			echo '<div class="quiz-description">';
			echo $description;
			echo '</div>';
			$content_block = $this->ui->getWidget('quiz_response_description');
			$content_block->content = ob_get_clean();
			$content_block->content_type = 'text/xml';
		}

		// messages
		$this->buildQuizResponseMessages();

		// answers
		if ($this->front_matter->resettable &&
			!$this->response->isPassed()) {

			$this->ui->getWidget('reset_form')->visible = true;
		} else {
			ob_start();
			$this->displayQuizResponse();
			$content_block = $this->ui->getWidget('quiz_response');
			$content_block->content = ob_get_clean();
			$content_block->content_type = 'text/xml';
		}

		$this->ui->getWidget('quiz_frame')->visible = false;
		$this->ui->getWidget('quiz_keyboard_help')->visible = false;
		$this->ui->getWidget('quiz_response_container')->visible = true;
	}

	// }}}
	// {{{ protected function buildQuizResponseMessages()

	protected function buildQuizResponseMessages()
	{
		$locale  = SwatI18NLocale::get();
		$correct = $this->response->getCorrectCount();
		$total   = count($this->quiz->question_bindings);
		$grade   = $this->response->getGrade();

		ob_start();

		// quiz grade
		echo '<p class="quiz-response-grade">';

		printf(
			CME::ngettext(
				'You got %s out of %s answer correct for a grade of %s%%.',
				'You got %s out of %s answers correct for a grade of %s%%.',
				$total
			),
			SwatString::minimizeEntities($locale->formatNumber($correct)),
			SwatString::minimizeEntities($locale->formatNumber($total)),
			SwatString::minimizeEntities(
				$locale->formatNumber(round($grade * 1000) / 10)
			)
		);

		echo '</p>';
		echo '<p class="quiz-response-status">';

		$complete_date = clone $this->response->complete_date;
		$complete_date->convertTZ($this->app->default_time_zone);
		echo SwatString::minimizeEntities(
			sprintf(
				CME::_('You completed this quiz on %s.'),
				$complete_date->formatLikeIntl(CME::_('MMMM d, yyyy'))
			)
		);

		if (!$this->front_matter->resettable) {
			echo ' ';
			echo SwatString::minimizeEntities(
				CME::_(
					'Once you’ve taken the quiz, it may not be taken again.'
				)
			);
		}

		echo '</p>';

		if ($this->response->isPassed()) {

			$account = $this->app->session->account;
			if ($account->isEvaluationComplete($this->credits->getFirst())) {
				echo '<p>';
				echo SwatString::minimizeEntities(
					CME::_('You’ve already completed the evaluation.')
				);
				echo '</p>';

				$certificate_link = new SwatHtmlTag('a');
				$certificate_link->class = 'btn btn-primary';
				$certificate_link->href = $this->getCertificateURI();
				$certificate_link->setContent(CME::_('Print Certificate'));
				$certificate_link->display();
			} else {
				$evaluation_link = new SwatHtmlTag('a');
				$evaluation_link->class = 'btn btn-primary';
				$evaluation_link->href = $this->getEvaluationURI();
				$evaluation_link->setContent(CME::_('Complete Evaluation'));
				$evaluation_link->display();
			}

		} else {

			// quiz failed message
			$p_tag = new SwatHtmlTag('p');
			$p_tag->class = 'quiz-response-failed';
			$p_tag->setContent(
				sprintf(
					CME::_(
						'A grade of %s%% is required to qualify for CME '.
						'credit.'
					),
					$locale->formatNumber(
						$this->front_matter->passing_grade * 100
					)
				)
			);

			$p_tag->display();
		}

		$content_block = $this->ui->getWidget('quiz_response_description');
		$content_block->content.= ob_get_clean();
		$content_block->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function displayQuizDetailsIncomplete()

	protected function displayQuizDetailsIncomplete()
	{
		$locale = SwatI18NLocale::get();

		// quiz description
		$description = $this->getQuizDescription();
		if ($description != '') {
			echo '<div class="quiz-description">';
			echo $description;
			echo '</div>';
		}

		// passing grade
		echo '<div class="quiz-passing-grade">';

		$grade_span = new SwatHtmlTag('span');
		$grade_span->setContent(
			$locale->formatNumber($this->front_matter->passing_grade * 100).'%'
		);

		printf(
			CME::_('A grade of %s is required'),
			$grade_span
		);

		echo '</div>';

		// number of questions and time estimate
		echo '<div class="quiz-intro-status">';

		$total_questions = count($this->quiz->question_bindings);

		if ($total_questions > 30) {
			$time_estimate = round($total_questions * 2 / 30) / 2;
			$time_estimate = sprintf(
				CME::ngettext(
					'one hour',
					'%s hours',
					$time_estimate
				),
				$locale->formatNumber($time_estimate)
			);
		} else {
			$time_estimate = ceil($total_questions * 2 / 10) * 10;
			$time_estimate = sprintf(
				CME::_('%s minutes'),
				$locale->formatNumber($time_estimate)
			);
		}

		printf(
			CME::_('%s questions, about %s.'),
			$locale->formatNumber($total_questions),
			$time_estimate
		);

		echo '</div>';
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		parent::buildContent();

		if (!$this->isComplete()) {
			$this->layout->startCapture('content');
			Swat::displayInlineJavaScript($this->getInlineJavaScript());
			$this->layout->endCapture();
		}
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		$this->layout->data->title = sprintf(
			CME::_('%s Quiz'),
			SwatString::minimizeEntities(
				$this->front_matter->getProviderTitleList()
			)
		);
	}

	// }}}
	// {{{ protected function getQuizDescription()

	protected function getQuizDescription()
	{
		return '';
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		static $shown = false;

		if (!$shown) {
			$javascript = $this->getInlineJavaScriptTranslations();
			$shown = true;
		} else {
			$javascript = '';
		}

		$quiz_uri = $this->app->getBaseHref(true).$this->source;
		$response_server = $quiz_uri.'/response';

		// get current question (first unanswered after last answered)
		$count = 0;
		$current_question = 0;
		foreach ($this->quiz->question_bindings as $question_binding) {
			$binding_id = $question_binding->id;

			if (isset($this->response_values_by_binding_id[$binding_id])) {
				$current_question = $count + 1;
			}
			$count++;
		}

		// limit current question in case last question is present in the
		// response
		$current_question = min(
			$current_question,
			count($this->quiz->question_bindings) - 1
		);

		$javascript.= sprintf(
			"var quiz_page = new %s('quiz_container', %s, %s);\n",
			$this->getJavaScriptClassName(),
			SwatString::quoteJavaScriptString($response_server),
			$current_question
		);

		return $javascript;
	}

	// }}}
	// {{{ protected function getInlineJavaScriptTranslations()

	protected function getInlineJavaScriptTranslations()
	{
		$strings = array(
			'start_text'                  => CME::_('Start Quiz'),
			'continue_text'               => CME::_('Continue Quiz'),
			'next_text'                   => CME::_('Next Question'),
			'previous_text'               => CME::_('Previous Question'),
			'quiz_status_text'            => CME::_('Question %s of %s'),
			'submit_text'                 => CME::_('Submit Quiz'),
			'review_text'                 => CME::_('Review Answers'),
			'intro_text'                  => CME::_('Return to Introduction'),
			'close_text'                  => CME::_('Close'),
			'question_title_text'         => CME::_('Question %s'),
			'change_text'                 => CME::_('Change'),
			'answer_text'                 => CME::_('Answer'),
			'intro_status_review_text'    => CME::_(
				'%s of %s questions completed'
			),
			'intro_status_start_text'     => CME::_('%s questions, about %s'),
			'intro_status_continue_text'  => CME::_(
				'%s of %s questions completed, about %s remaining'
			),
			'review_status_text_0'        => CME::_(
				'All questions are answered.'
			),
			'review_status_text_1'        => CME::_('%s is not answered.'),
			'review_status_text_2_to_5'   => CME::_('%s are unanswered.'),
			'review_status_text_many'     => CME::_(
				'%s questions are unanswered.'
			),
			'review_status_required_text' => CME::_(
				'All questions must be answered before the quiz can be '.
				'submitted.'
			),
			'time_hours_text_1'           => CME::_('one hour'),
			'time_hours_text_many'        => CME::_('%s hours'),
			'time_minutes_text_many'      => CME::_('%s minutes'),
		);

		$javascript = '';
		foreach ($strings as $key => $text) {
			$javascript.= sprintf(
				"CMEQuizPage.%s = %s;\n",
				$key,
				SwatString::quoteJavaScriptString($text)
			);
		}
		return $javascript;
	}

	// }}}
	// {{{ protected function getJavaScriptClassName()

	protected function getJavaScriptClassName()
	{
		return 'CMEQuizPage';
	}

	// }}}
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
	}

	// }}}
	// {{{ protected function displayQuizResponse()

	protected function displayQuizResponse()
	{
		// build lookup array for response values
		$response_values = array();
		foreach ($this->response->values as $value) {
			$response_values[
				$value->getInternalValue('question_binding')
			] = $value;
		}

		echo '<ol class="quiz-response">';

		foreach ($this->quiz->question_bindings as $question_binding) {
			$question = $question_binding->question;

			$correct_option_id = $question->getInternalValue('correct_option');
			$response_option_id = null;
			$correct = false;

			// if there's a response, check it
			if (isset($response_values[$question_binding->id])) {
				$response_value = $response_values[$question_binding->id];
				$response_option_id =
					$response_value->getInternalValue('question_option');

				$correct = ($correct_option_id === $response_option_id);
			}

			$question_li = new SwatHtmlTag('li');
			$question_li->class = 'quiz-question';
			if ($correct) {
				$question_li->class.= ' quiz-question-correct';
			} else {
				$question_li->class.= ' quiz-question-incorrect';
			}
			$question_li->open();

			$icon = new SwatHtmlTag('span');
			$icon->class = 'quiz-response-question-icon glyphicon';
			if ($correct) {
				$icon->class.= ' glyphicon-ok';
			} else {
				$icon->class.= ' glyphicon-remove';
			}
			$icon->setContent('');
			$icon->display();

			echo '<div class="quiz-question-question">';
			echo $question->bodytext;
			echo '</div>';

			echo '<dl class="quiz-question-options">';

			$option = $question->correct_option;

			// your option
			if ($option instanceof InquisitionQuestionOption &&
				$response_option_id !== null) {

				$dt_tag = new SwatHtmlTag('dt');
				$dt_tag->setContent(CME::_('Your Answer:'));
				$dt_tag->display();
				$response_option = $question->options[$response_option_id];
				$dd_tag = new SwatHtmlTag('dd');
				if ($option->id !== $response_option_id) {
					$dd_tag->class =
						'quiz-question-option quiz-question-option-incorrect';
				} else {
					$dd_tag->class =
						'quiz-question-option';
				}

				$dd_tag->setContent($response_option->title);
				$dd_tag->display();
			}

			// correct option (shown if your option is wrong)
			if ($option instanceof InquisitionQuestionOption &&
				$option->id !== $response_option_id) {

				$dt_tag = new SwatHtmlTag('dt');
				$dt_tag->setContent(CME::_('Correct Answer:'));
				$dt_tag->display();
				$dd_tag = new SwatHtmlTag('dd');
				$dd_tag->class =
					'quiz-question-option quiz-question-option-correct';

				$dd_tag->setContent($option->title);
				$dd_tag->display();
			}

			echo '</dl>';

			$question_li->close();
		}

		echo '</ol>';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addBodyClass('cme-quiz-page');

		$yui = new SwatYUI(
			array(
				'dom',
				'event',
				'connection',
				'json',
				'animation',
				'container_core',
			)
		);
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());
		$this->layout->addHtmlHeadEntry(
			'packages/swat/javascript/swat-z-index-manager.js'
		);
		$this->layout->addHtmlHeadEntry(
			'packages/site/javascript/site-dialog.js'
		);
		$this->layout->addHtmlHeadEntry(
			'packages/cme/javascript/cme-quiz-page.js'
		);

		if ($this->response_message_display instanceof SwatUIObject) {
			$this->layout->addHtmlHeadEntrySet(
				$this->response_message_display->getHtmlHeadEntrySet());
		}
	}

	// }}}
}

?>
