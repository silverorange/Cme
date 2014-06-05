<?php

require_once 'Inquisition/dataobjects/InquisitionInquisition.php';
require_once 'CME/dataobjects/CMEAccount.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuiz extends InquisitionInquisition
{
	// {{{ public function getResponseByAccount()

	/**
	 * Adds resettable check to account quiz response fetching
	 */
	public function getResponseByAccount(CMEAccount $account)
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

		if ($response instanceof InquisitionResponse) {
			$response->inquisition = $this;
		}

		return $response;
	}

	// }}}
}

?>
