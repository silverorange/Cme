<?php

require_once 'Inquisition/dataobjects/InquisitionInquisition.php';
require_once 'CME/dataobjects/CMEAccount.php';
require_once 'CME/dataobjects/CMEQuizResponseWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuiz extends InquisitionInquisition
{
	// {{{ public function getResponseByAccount()

	/**
	 * Excludes quiz responses that were reset. We save the old quiz response
	 * but don't use it for display or for credit calculations.
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

		$wrapper  = SwatDBClassMap::get('CMEQuizResponseWrapper');
		$response = SwatDB::query($this->db, $sql, $wrapper)->getFirst();

		if ($response instanceof CMEQuizResponse) {
			$response->inquisition = $this;
		}

		return $response;
	}

	// }}}
}

?>
