<?php


/**
 * @package   CME
 * @copyright 2013-2016 silverorange
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
			'select CMEFrontMatter.id, sum(CMECredit.hours) as hours
			from CMEFrontMatter
			left outer join CMECredit
				on CMECredit.front_matter = CMEFrontMatter.id
			where CMEFrontMatter.id in (%s)
			group by CMEFrontMatter.id',
			$item_list
		);

		$rs = SwatDB::query($this->app->db, $sql);

		$class_name = SwatDBClassMap::get('CMEFrontMatter');

		foreach ($rs as $row) {
			$front_matter = new $class_name($row);
			$front_matter->setDatabase($this->app->db);

			$row->status_level = AdminDependency::DELETE;
			$row->parent = null;

			// not using ngettext because hours is a float
			$row->title = sprintf(
				($row->hours == 1)
					? CME::_('%s (1 hour)')
					: CME::_('%s (%s hours)'),
				$front_matter->getProviderTitleList(),
				$locale->formatNumber($row->hours)
			);
			$dep->entries[] = new AdminDependencyEntry($row);
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
