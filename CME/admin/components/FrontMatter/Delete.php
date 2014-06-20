<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * @package   CME
 * @copyright 2013-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEFrontMatterDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from CMEFrontMatter where id in (%s);';

		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$locale = SwatI18NLocale::get();

		$message = new SwatMessage(
			sprintf(
				CME::ngettext(
					'One CME front matter has been deleted.',
					'%s CME front matters have been deleted.',
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
		$dep->setTitle(
			CME::_('CME front matter'),
			CME::_('CME front matters')
		);

		$sql = sprintf(
			'select CMEFrontMatter.id, sum(CMECredit.hours) as hours,
				CMEProvider.title
			from CMEFrontMatter
				left outer join CMECredit
					on CMECredit.front_matter = CMEFrontMatter.id
				inner join CMEProvider
					on CMEFrontMatter.provider = CMEProvider.id
			where CMEFrontMatter.id in (%s)
			group by CMEFrontMatter.id, CMEProvider.title',
			$item_list
		);

		$front_matters = SwatDB::query($this->app->db, $sql);

		foreach ($front_matters as $front_matter) {
			$front_matter->status_level = AdminDependency::DELETE;
			$front_matter->parent = null;
			$front_matter->title = sprintf(
				CME::ngettext(
					'%s (%s hour)',
					'%s (%s hours)',
					$front_matter->hours
				),
				$front_matter->title,
				$locale->formatNumber($front_matter->hours)
			);
			$dep->entries[] = new AdminDependencyEntry($front_matter);
		}

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) === 0) {
			$this->switchToCancelButton();
		}

	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntries(1);

		$this->navbar->createEntry(
			CME::ngettext(
				'Delete CME Front Matter',
				'Delete CME Front Matters',
				count($this->items)
			)
		);
	}

	// }}}
}

?>
