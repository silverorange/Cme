<?php

require_once 'Site/dataobjects/SiteAccount.php';
require_once 'Inquisition/dataobjects/InquisitionInquisition.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuiz extends InquisitionInquisition
{
	// {{{ public properties

	/**
	 * @var string
	 */
	public $description;

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
	public $enabled;

	/**
	 * @var boolean
	 */
	public $resettable;

	// }}}
	// {{{ public function getResponseByAccount()

	public function getResponseByAccount(SiteAccount $account)
	{
		$this->checkDB();

		$sql = sprintf(
			'select * from InquisitionResponse
			where account = %s and inquisition = %s and reset_date is null',
			$this->db->quote($account->id, 'integer'),
			$this->db->quote($this->id, 'integer')
		);

		$wrapper  = SwatDBClassMap::get('InquisitionResponseWrapper');
		$response = SwatDB::query($this->db, $sql, $wrapper)->getFirst();

		if ($response !== null) {
			$response->inquisition = $this;
		}

		return $response;
	}

	// }}}
}

?>
