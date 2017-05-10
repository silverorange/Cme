<?php

/**
 * @package   CME
 * @copyright 2011-2016 silverorange
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

		return SwatDB::query(
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

			$front_matter = $this->getFrontMatter();
			if (!$front_matter instanceof CMEFrontMatter) {
				return $this->getErrorResponse('CME front matter not found.');
			}

			// only save on a POST request
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$this->saveAccountAttestedCMEFrontMatter(
					$account,
					$front_matter
				);
			}

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

		$this->saveEarnedCredits($account, $front_matter);
	}

	// }}}
	// {{{ protected function saveEarnedCredits()

	protected function saveEarnedCredits(CMEAccount $account,
		CMEFrontMatter $front_matter)
	{
		$wrapper = SwatDBClassMap::get('CMEAccountEarnedCMECreditWrapper');
		$class_name = SwatDBClassMap::get('CMEAccountEarnedCMECredit');
		$earned_credits = new $wrapper();
		$now = new SwatDate();
		$now->toUTC();
		foreach ($front_matter->credits as $credit) {
			if ($credit->isEarned($account)) {
				// check for existing earned credit before saving
				$sql = sprintf(
					'select count(1)
					from AccountEarnedCMECredit
					where credit = %s and account = %s',
					$this->app->db->quote($credit->id, 'integer'),
					$this->app->db->quote($account->id, 'integer')
				);

				if (SwatDB::queryOne($this->app->db, $sql) == 0) {
					$earned_credit = new $class_name();
					$earned_credit->account = $account->id;
					$earned_credit->credit = $credit->id;
					$earned_credit->earned_date = $now;
					$earned_credits->add($earned_credit);
				}
			}
		}
		$earned_credits->setDatabase($this->app->db);
		$earned_credits->save();
	}

	// }}}
}

?>
