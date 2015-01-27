<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'CME/dataobjects/CMECredit.php';
require_once 'Inquisition/dataobjects/InquisitionQuestion.php';

/**
 * A binding between a CME credit and an inquisition question
 *
 * @package   CME
 * @copyright 2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMECreditQuestionBinding extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var integer
	 */
	public $displayorder;

	// }}}
	// {{{ public function getView()

	public function getView()
	{
		return $this->question->getView($this);
	}

	// }}}
	// {{{ public function getPosition()

	public function getPosition()
	{
		$sql = sprintf(
			'select position from (
				select id, rank() over (
					partition by inquisition order by displayorder, id
				) as position from CMECreditQuestionBinding
				where credit = %s
			) as temp where id = %s',
			$this->getInternalValue('credit'),
			$this->id
		);

		return SwatDB::queryOne($this->db, $sql);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'CMECreditQuestionBinding';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty(
			'credit',
			SwatDBClassMap::get('CMECredit')
		);

		// We set autosave so that questions are saved before the binding.
		$this->registerInternalProperty(
			'question',
			SwatDBClassMap::get('InquisitionQuestion'),
			true
		);
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array_merge(
			parent::getSerializableSubDataObjects(),
			array('question')
		);
	}

	// }}}
}

?>
