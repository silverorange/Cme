<?php

require_once 'Inquisition/dataobjects/InquisitionResponse.php';
require_once 'Site/dataobjects/SiteAccount.php';
require_once 'CME/dataobjects/CMEQuiz.php';

/**
 * A quiz response
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuizResponse extends InquisitionResponse
{
	// {{{ public properties

	/**
	 * @var SwatDate
	 */
	public $reset_date;

	// }}}
	// {{{ public function getCorrectCount()

	public function getCorrectCount()
	{
		$correct = 0;

		foreach ($this->values as $value) {
			$question           = $value->question_binding->question;
			$correct_option_id  = $question->getInternalValue('correct_option');
			$response_option_id = $value->getInternalValue('question_option');
			if ($response_option_id == $correct_option_id) {
				$correct++;
			}
		}

		return $correct;
	}

	// }}}
	// {{{ public function getGrade()

	public function getGrade()
	{
		$question_count = count($this->inquisition->question_bindings);

		if ($question_count === 0) {
			return 0;
		}

		return $this->getCorrectCount() / $question_count;
	}

	// }}}
	// {{{ public function isPassed()

	public function isPassed()
	{
		return $this->getGrade() >= $this->inquisition->passing_grade;
	}

	// }}}
	// {{{ public function getCredit()

	public function getCredit()
	{
		require_once 'CME/dataobjects/CMECreditWrapper.php';

		$this->checkDB();

		$inquisition_id = $this->getInternalValue('inquisition');

		$sql = sprintf(
			'select * from CMECredit where quiz = %s',
			$this->db->quote($inquisition_id, 'integer')
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		)->getFirst();
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->registerDateProperty('reset_date');
		$this->registerInternalProperty(
			'account',
			SwatDBClassMap::get('SiteAccount')
		);
	}

	// }}}
}

?>
