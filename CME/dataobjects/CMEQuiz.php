<?php

/**
 * @package   CME
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuiz extends InquisitionInquisition
{


	/**
	 * Excludes quiz responses that were reset. We save the old quiz response
	 * but don't use it for display or for credit calculations.
	 */
	public function getResponseByAccount(SiteAccount $account)
	{
		$this->checkDB();

		$sql = sprintf(
			'select * from InquisitionResponse
			where account = %s and inquisition = %s and reset_date is null',
			$this->db->quote($account->id, 'integer'),
			$this->db->quote($this->id, 'integer')
		);

		$wrapper = $this->getResolvedResponseWrapperClass();
		$response = SwatDB::query($this->db, $sql, $wrapper)->getFirst();

		if ($response instanceof CMEQuizResponse) {
			$response->inquisition = $this;
		}

		return $response;
	}




	protected function getResponseWrapperClass()
	{
		return 'CMEQuizResponseWrapper';
	}


}

?>
