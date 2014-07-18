<?php

require_once 'Site/pages/SiteArticlePage.php';
require_once 'Inquisition/dataobjects/InquisitionQuestionWrapper.php';
require_once 'Inquisition/dataobjects/InquisitionResponse.php';
require_once 'Inquisition/dataobjects/InquisitionResponseWrapper.php';
require_once 'Inquisition/dataobjects/InquisitionInquisitionWrapper.php';
require_once 'Inquisition/dataobjects/InquisitionInquisition.php';
require_once 'CME/dataobjects/CMECredit.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMEQuizResponse.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuizResponseServer extends SiteArticlePage
{
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);
		$this->setLayout(
			new SiteLayout(
				$this->app,
				'Site/layouts/xhtml/json.php'
			)
		);
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

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->layout->startCapture('content');
		echo json_encode($this->getJSONResponse());
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getJSONResponse()

	protected function getJSONResponse()
	{
		// remember per-question timestamps to handle race conditions
		if (!isset($this->app->session->quiz_question_timestamp)) {
			$this->app->session->quiz_question_timestamp = new ArrayObject();
		}
		$quiz_question_timestamp = $this->app->session->quiz_question_timestamp;

		$transaction = new SwatDBTransaction($this->app->db);
		try {
			if (!$this->app->session->isLoggedIn()) {
				return $this->getErrorResponse('Not logged in.');
			}

			$account = $this->app->session->account;

			$credit = $this->getCredit();
			if (!$credit instanceof CMECredit) {
				return $this->getErrorResponse('CME credit not found.');
			}

			$quiz = $this->getQuiz($credit);
			if (!$quiz instanceof CMEQuiz) {
				return $this->getErrorResponse('Quiz not found.');
			}

			$binding_id = SiteApplication::initVar(
				'binding_id',
				null,
				SiteApplication::VAR_POST
			);

			if ($binding_id === null) {
				return $this->getErrorResponse(
					'Question binding not specified.'
				);
			}

			$binding = $this->getQuestionBinding($quiz, $binding_id);

			$timestamp = SiteApplication::initVar(
				'timestamp',
				null,
				SiteApplication::VAR_POST
			);

			if ($timestamp === null) {
				return $this->getErrorResponse('Timestamp not specified.');
			}

			if (isset($quiz_question_timestamp[$binding_id]) &&
				$timestamp < $quiz_question_timestamp[$binding_id]) {
				return $this->getErrorResponse('Request is out of sequence.');
			}

			$quiz_question_timestamp[$binding_id] = $timestamp;

			$option_id = SiteApplication::initVar(
				'option_id',
				null,
				SiteApplication::VAR_POST
			);

			if ($option_id === null) {
				return $this->getErrorResponse(
					'Response option id not specified.'
				);
			}

			$response = $this->getResponse($quiz);
			$response_value = $this->getResponseValue(
				$quiz,
				$response,
				$binding,
				$option_id
			);

			if ($response_value === null) {
				return $this->getErrorResponse(
					'Response option id not valid for the specified question.'
				);
			}

			$this->saveResponseValue($response, $response_value);

			$transaction->commit();
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		return array(
			'status'      => array(
				'code'    => 'ok',
				'message' => '',
			),
			'timestamp'   => time(),
		);
	}

	// }}}
	// {{{ protected function getErrorResponse()

	protected function getErrorResponse($message)
	{
		return array(
			'status'      => array(
				'code'    => 'error',
				'message' => $message,
			),
			'timestamp'   => time(),
		);
	}

	// }}}
	// {{{ protected function getCredit()

	protected function getCredit()
	{
		$credit_id = $this->getArgument('credit');

		$sql = sprintf(
			'select CMECredit.* from CMECredit
				inner join CMEFrontMatter
					on CMECredit.front_matter = CMEFrontMatter.id
			where CMECredit.id = %s and CMEFrontMatter.enabled = %s',
			$this->app->db->quote($credit_id, 'integer'),
			$this->app->db->quote(true, 'boolean')
		);

		return SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		)->getFirst();
	}

	// }}}
	// {{{ protected function getQuiz()

	protected function getQuiz(CMECredit $credit)
	{
		$quiz = $this->app->getCacheValue(
			$this->getCacheKey($credit)
		);

		if ($quiz === false) {
			$quiz = $credit->quiz;
		} else {
			$quiz->setDatabase($this->app->db);
		}

		return $quiz;
	}

	// }}}
	// {{{ protected function getQuestionBinding()

	protected function getQuestionBinding(InquisitionInquisition $quiz,
		$binding_id)
	{
		$sql = sprintf(
			'select * from InquisitionInquisitionQuestionBinding
			where inquisition = %s and id = %s',
			$this->app->db->quote($quiz->id, 'integer'),
			$this->app->db->quote($binding_id, 'integer')
		);

		$wrapper = SwatDBClassMap::get(
			'InquisitionInquisitionQuestionBindingWrapper'
		);

		return SwatDB::query($this->app->db, $sql, $wrapper)->getFirst();
	}

	// }}}
	// {{{ protected function getResponse()

	protected function getResponse(InquisitionInquisition $quiz)
	{
		$response = $quiz->getResponseByAccount($this->app->session->account);

		// get new response
		if (!$response instanceof CMEQuizResponse) {
			$class_name = SwatDBClassMap::get('CMEQuizResponse');
			$response = new $class_name();

			$response->account     = $this->app->session->account;
			$response->inquisition = $quiz;
			$response->createdate  = new SwatDate();
			$response->createdate->toUTC();

			$wrapper = SwatDBClassMap::get(
				'InquisitionResponseValueWrapper'
			);

			$response->values = new $wrapper();

			$response->setDatabase($this->app->db);
		}

		return $response;
	}

	// }}}
	// {{{ protected function getResponseValue()

	protected function getResponseValue(CMEQuiz $quiz,
		CMEQuizResponse $response,
		InquisitionInquisitionQuestionBinding $question_binding,
		$option_id)
	{
		$response_value = null;

		$question_id = $question_binding->getInternalValue('question');

		// make sure option is valid for question
		$sql = sprintf(
			'select count(1) from InquisitionQuestionOption
			where question = %s and id = %s',
			$this->app->db->quote($question_id, 'integer'),
			$this->app->db->quote($option_id, 'integer')
		);

		if (SwatDB::queryOne($this->app->db, $sql) === 1) {

			// check for existing response
			$sql = sprintf(
				'select *
					from InquisitionResponseValue
				where response = %s and question_binding = %s',
				$this->app->db->quote($response->id, 'integer'),
				$this->app->db->quote($question_binding->id, 'integer')
			);

			$wrapper = SwatDBClassMap::get('InquisitionResponseValueWrapper');
			$response_value = SwatDB::query(
				$this->app->db,
				$sql,
				$wrapper
			)->getFirst();

			// if no existing response, make a new one
			if ($response_value === null) {
				$class_name = SwatDBClassMap::get('InquisitionResponseValue');
				$response_value = new $class_name();
				$response_value->setDatabase($this->app->db);
			}

			// set question option and question
			$response_value->question_option = $option_id;
			$response_value->question_binding = $question_binding->id;
		}

		return $response_value;
	}

	// }}}
	// {{{ protected function saveResponseValue()

	protected function saveResponseValue(CMEQuizResponse $response,
		InquisitionResponseValue $response_value)
	{
		// save new response object if it wasn't already saved
		$response->save();

		// set response on value and save value
		$response_value->response = $response->id;
		$response_value->save();
	}

	// }}}
	// {{{ protected function getCacheKey()

	protected function getCacheKey(CMECredit $credit)
	{
		return 'cme-quiz-page-'.$credit->id;
	}

	// }}}
}

?>
