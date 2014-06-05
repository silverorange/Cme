<?php

require_once 'Site/pages/SiteArticlePage.php';
require_once 'CME/dataobjects/CMEAccount.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';
require_once 'CME/dataobjects/CMEFrontMatterWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEFrontMatterAttestationServerPage extends SiteArticlePage
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
			'front_matter' => array(0, null),
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
	// {{{ protected function getFrontMatter()

	protected function getFrontMatter()
	{
		$front_matter_id = $this->getArgument('front_matter');

		$sql = sprintf(
			'select * from CMEFrontMatter where id = %s and enabled = %s',
			$this->app->db->quote($front_matter_id, 'integer'),
			$this->app->db->quote(true, 'boolean')
		);

		return SwatBD::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMEFrontMatterWrapper')
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

			$front_matter = $this->getCMEFrontMatter();
			if (!$front_matter instanceof CMEFrontMatter) {
				return $this->getErrorResponse('CME front matter not found.');
			}

			$this->saveAccountAttestedCMEFrontMatter($account, $front_matter);

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
	// {{{ protected function saveAccountAttestedCMEFrontMatter()

	protected function saveAccountAttestedCMEFrontMatter(CMEAccount $account,
		CMEFrontMatter $front_matter)
	{
		$sql = sprintf(
			'delete from AccountAttestedCMEFrontMatter
			where account = %s and front_matter = %s',
			$this->app->db->quote($account->id, 'integer'),
			$this->app->db->quote($front_matter->id, 'integer')
		);

		SwatDB::exec($this->app->db, $sql);

		$now = new SwatDate();
		$now->toUTC();

		$sql = sprintf(
			'insert into AccountAttestedCMEFrontMatter (
				account, front_matter, attested_date
			) values (%s, %s, %s)',
			$this->app->db->quote($account->id, 'integer'),
			$this->app->db->quote($front_matter->id, 'integer'),
			$this->app->db->quote($now, 'date')
		);

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
}

?>
