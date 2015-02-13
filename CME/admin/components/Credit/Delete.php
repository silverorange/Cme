<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';

/**
 * @package   CME
 * @copyright 2013-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMECreditDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from CMECredit where id in (%s);';

		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$locale = SwatI18NLocale::get();

		$message = new SwatMessage(
			sprintf(
				CME::ngettext(
					'One CME credit has been deleted.',
					'%s CME credits have been deleted.',
					$num
				),
				$locale->formatNumber($num)
			)
		);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$locale = SwatI18NLocale::get();

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(CME::_('CME credit'), CME::_('CME credits'));

		$sql = sprintf(
			'select CMECredit.*
			from CMECredit
			where CMECredit.id in (%s)',
			$item_list
		);

		$credits = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMECreditWrapper')
		);

		foreach ($credits as $credit) {
			$data = new stdClass();
			$data->id = $credit->id;
			$data->status_level = AdminDependency::DELETE;
			$data->parent = null;
			$data->title = $credit->getTitle();
			$dep->entries[] = new AdminDependencyEntry($data);
		}

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) === 0) {
			$this->switchToCancelButton();
		}

	}

	// }}}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntries(1);

		$this->navbar->createEntry(
			CME::ngettext(
				'Delete CME Credit',
				'Delete CME Credits',
				count($this->items)
			)
		);
	}

	// }}}
}

?>
