<?php

/**
 * @package   CME
 * @copyright 2013-2016 silverorange
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
	 * @var boolean
	 */
	public $is_free;

	// }}}
	// {{{ public static function formatCreditHours()

	public static function formatCreditHours($hours)
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
			strlen(substr(strrchr($hours, "."), 1)) === 2 &&
			substr($hours, -1) !== '0'
			)
			? 2
			: 1;

		return $locale->formatNumber($hours, $decimal_places);
	}

	// }}}
	// {{{ public function getFormattedHours()

	public function getFormattedHours()
	{
		return static::formatCreditHours($this->hours);
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
				$account->isEvaluationComplete($this)
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
