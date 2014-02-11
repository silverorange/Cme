<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTitleLinkCellRenderer.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECreditTypeWrapper.php';
require_once 'CME/dataobjects/QuizReportWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuizReportIndex extends AdminIndex
{
	// {{{ protected properties

	/**
	 * @var array
	 */
	protected $reports_by_quarter = array();

	/**
	 * @var CMECreditTypeWrapper
	 */
	protected $credit_types;

	/**
	 * @var SwatDate
	 */
	protected $start_date;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML('CME/admin/components/QuizReport/index.xml');
		$this->initStartDate();
		$this->initCreditTypes();
		$this->initReportsByQuarter();
		$this->initTableViewColumns();
	}

	// }}}
	// {{{ protected function initStartDate()

	protected function initStartDate()
	{
		$oldest_date_string = SwatDB::queryOne(
			$this->app->db,
			'select min(complete_date) from InquisitionResponse
			where complete_date is not null
				and reset_date is null
				and inquisition in (select quiz from CMECredit)'
		);

		$this->start_date = new SwatDate($oldest_date_string);
	}

	// }}}
	// {{{ protected function initCreditTypes()

	protected function initCreditTypes()
	{
		$this->credit_types = SwatDB::query(
			$this->app->db,
			'select * from CMECreditType order by title, id',
			SwatDBClassMap::get('CMECreditTypeWrapper')
		);
	}

	// }}}
	// {{{ protected function initReportsByQuarter()

	protected function initReportsByQuarter()
	{
		$sql = 'select * from QuizReport order by quarter';
		$reports = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMEQuizReportWrapper')
		);

		$reports->attachSubDataObjects(
			'credit_type',
			$this->credit_types
		);

		foreach ($reports as $report) {
			$quarter = clone $report->quarter;
			$quarter->convertTZ($this->app->default_time_zone);
			$quarter = $quarter->formatLikeIntl('yyyy-qq');
			$credit_type = $report->credit_type->shortname;
			if (!isset($this->reports_by_quarter[$quarter])) {
				$this->reports_by_quarter[$quarter] = array();
			}
			$this->reports_by_quarter[$quarter][$credit_type] = $report;
		}
	}

	// }}}
	// {{{ protected function initTableViewColumns()

	protected function initTableViewColumns()
	{
		$view = $this->ui->getWidget('index_view');
		foreach ($this->credit_types as $credit_type) {
			$renderer = new AdminTitleLinkCellRenderer();
			$renderer->link = sprintf(
				'QuizReport/Download?type=%s&quarter=%%s',
				$credit_type->shortname
			);
			$renderer->stock_id = 'download';
			$renderer->text = $credit_type->title;

			$column = new SwatTableViewColumn();
			$column->id = 'credit_type_'.$credit_type->shortname;
			$column->addRenderer($renderer);
			$column->addMappingToRenderer(
				$renderer,
				'quarter',
				'link_value'
			);

			$column->addMappingToRenderer(
				$renderer,
				'is_'.$credit_type->shortname.'_sensitive',
				'sensitive'
			);

			$view->appendColumn($column);
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$now = new SwatDate();
		$now->convertTZ($this->app->default_time_zone);

		$year = $this->start_date->getYear();

		$start_date = new SwatDate();
		$start_date->setTime(0, 0, 0);
		$start_date->setDate($year, 1, 1);
		$start_date->setTZ($this->app->default_time_zone);

		$end_date = clone $start_date;
		$end_date->addMonths(3);

		$display_end_date = clone $end_date;
		$display_end_date->subtractMonths(1);

		$store = new SwatTableStore();

		while ($end_date->before($now)) {
			for ($i = 1; $i <= 4; $i++) {

				$ds = new SwatDetailsStore();

				$quarter = $start_date->formatLikeIntl('yyyy-qq');

				$ds->date    = clone $start_date;
				$ds->year    = $year;
				$ds->quarter = $quarter;

				$ds->quarter_title = sprintf(
					CME::_('Q%s - %s to %s'),
					$i,
					$start_date->formatLikeIntl('MMMM yyyy'),
					$display_end_date->formatLikeIntl('MMMM yyyy')
				);

				foreach ($this->credit_types as $credit_type) {
					$ds->{'is_'.$credit_type->shortname.'_sensitive'} =
						(isset($this->reports_by_quarter[$quarter][$credit_type->shortname]));
				}

				$store->add($ds);

				$start_date->addMonths(3);
				$end_date->addMonths(3);
				$display_end_date->addMonths(3);
			}

			$year++;
		}

		return $store;
	}

	// }}}
}

?>
