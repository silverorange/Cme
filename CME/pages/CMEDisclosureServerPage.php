<?php

require_once 'Site/pages/SiteArticlePage.php';
require_once 'CME/dataobjects/CMEAccount.php';
require_once 'CME/dataobjects/CMECredit.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEDisclosureServerPage extends SiteArticlePage
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
	// {{{ protected function getCredit()

	protected function getCredit()
	{
		$credit_id = $this->getArgument('credit');

		$sql = sprintf(
			'select * from CMECredit where id = %s',
			$this->app->db->quote($credit_id, 'integer')
		);

		return SwatBD::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		)->getFirst();
	}

	// }}}
	// {{{ protected function getJSONResponse()

	protected function getJSONResponse()
	{
		$transaction = new SwatDBTransaction($this->app->db);
		try {
			if (!$this->app->session->isLoggedIn()) {
				return $this->getErrorResponse('Not logged in.');
			}

			$account = $this->app->session->account;

			$credit = $this->getCMECredit();
			if (!$credit instanceof CMECredit) {
				return $this->getErrorResponse('CME credit not found.');
			}

			$this->saveAccountCMECreditBinding($account, $credit);

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
		);
	}

	// }}}
	// {{{ protected function saveAccountCMECreditBinding()

	protected function saveAccountCMECreditBinding(CMEAccount $account,
		CMECredit $credit)
	{
		$sql = sprintf(
			'delete from AccountCMECreditBinding
			where account = %s and credit = %s',
			$this->app->db->quote($account->id, 'integer'),
			$this->app->db->quote($credit->id, 'integer')
		);

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf(
			'insert into AccountCMECreditBinding (account, credit)
			values (%s, %s)',
			$this->app->db->quote($account->id, 'integer'),
			$this->app->db->quote($credit->id, 'integer')
		);

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
}

?>
