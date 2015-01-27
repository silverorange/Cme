<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';

/**
 * @package   CME
 * @copyright 2013-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMECredit extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var float
	 */
	public $hours;

	/**
	 * @var integer
	 */
	public $passing_grade;

	/**
	 * @var string
	 */
	public $email_content_pass;

	/**
	 * @var string
	 */
	public $email_content_fail;

	/**
	 * @var boolean
	 */
	public $resettable;

	// }}}
	// {{{ public function isEarned()

	public function isEarned(CMEAccount $account)
	{
		// assume the evaluation is always required
		return (
				$account->hasAttested($this->front_matter)
			) && (
				!$this->quiz instanceof CMEQuiz ||
				$account->isQuizPassed($this)
			) && (
				!$this->front_matter->evaluation instanceof CMEEvaluation ||
				$account->isEvaluationComplete($this->front_matter)
			);
	}

	// }}}
	// {{{ abstract protected function getQuizLink()

	abstract protected function getQuizLink();

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'CMECredit';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty(
			'front_matter',
			SwatDBClassMap::get('CMEFrontMatter')
		);
	}

	// }}}

	// saver methods
	// {{{ protected function saveQuestionBindings()

	protected function saveQuestionBindings()
	{
		foreach ($this->question_bindings as $question_binding) {
			$question_binding->credit = $this;
		}

		$this->question_bindings->setDatabase($this->db);
		$this->question_bindings->save();
	}

	// }}}

	// loader methods
	// {{{ protected function loadQuestionBindings()

	protected function loadQuestionBindings()
	{
		$sql = sprintf(
			'select * from CMECreditQuestionBinding
			where credit = %s order by displayorder, id',
			$this->db->quote($this->id, 'integer')
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMECreditQuestionBindingWrapper')
		);
	}

	// }}}
}

?>
