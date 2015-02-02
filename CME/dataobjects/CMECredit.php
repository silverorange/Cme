<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Inquisition/dataobjects/InquisitionInquisition.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';

/**
 * @package   CME
 * @copyright 2013-2015 silverorange
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

	/**
	 * @var boolean
	 */
	public $is_free;

	// }}}
	// {{{ protected function getFormattedHours()

	public function getFormattedHours()
	{
		$locale  = SwatI18NLocale::get();

		// When displaying credit hours round to single place except when there
		// are quarter hours, aka two digits past the decimal, where the last
		// digit is not zero.
		// Examples:
		// 4    -> 4.0
		// 4.5  -> 4.5
		// 4.50 -> 4.5
		// 4.25 -> 4.25
		$decimal_places = (
			strlen(substr(strrchr($this->hours, "."), 1)) === 2 &&
			substr($this->hours, -1) !== '0'
			)
			? 2
			: 1;

		return $locale->formatNumber($this->hours, $decimal_places);
	}

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
	// {{{ public function getTitle()

	public function getTitle()
	{
		return sprintf(
			CME::_('%s CME Credit'),
			$this->front_matter->getProviderTitleList()
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

		$this->registerInternalProperty(
			'quiz',
			SwatDBClassMap::get('CMEQuiz')
		);
	}

	// }}}
}

?>
