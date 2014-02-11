<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteCommandLineArgument.php';
require_once 'Site/SiteCommandLineConfigModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'CME/dataobjects/CMECreditTypeWrapper.php';
require_once 'CME/dataobjects/CMEEvaluationReport.php';
require_once 'CME/dataobjects/CMEEvaluationReportWrapper.php';
require_once 'CME/CMEEvaluationReportGenerator.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEEvaluationReportUpdater extends SiteCommandLineApplication
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $start_date;

	/**
	 * @var CMECreditTypeWrapper
	 */
	protected $credit_types;

	/**
	 * @var array
	 */
	protected $reports_by_quarter = null;

	// }}}
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		$this->initStartDate();
		$this->initCreditTypes();
		$this->initReportsByQuarter();

		$this->debug("Generating Quarterly Evaluation Reports\n\n", true);

		$this->lock();

		foreach ($this->credit_types as $credit_type) {
			$this->debug("{$credit_type->title}:\n", true);
			$shortname = $credit_type->shortname;

			foreach ($this->getQuarters($credit_type) as $quarter) {
				$quarter_id = $quarter->formatLikeIntl('qqq-yyyy');
				$this->debug("=> Quarter {$quarter_id}:\n");
				if (isset($this->reports_by_quarter[$quarter_id][$shortname])) {
					$this->debug("   => report exists\n");
				} else {
					// Make the dataobject first so we can use its file path
					// methods but only save after the file has been
					// generated.
					$report = $this->getDataObject(
						$quarter,
						$credit_type,
						$this->getFilename($quarter, $credit_type)
					);

					$this->debug("   => generating report ... ");
					$this->saveReport(
						$quarter,
						$credit_type,
						$report->getFilePath()
					);
					$this->debug("[done]\n");

					$this->debug("   => saving data object ... ");
					$report->save();
					$this->debug("[done]\n");
				}

				$this->debug("\n");
			}

			$this->debug("\n");
		}

		$this->unlock();

		$this->debug("All done.\n", true);
	}

	// }}}
	// {{{ protected function initStartDate()

	protected function initStartDate()
	{
		$oldest_date_string = SwatDB::queryOne($this->db,
			'select min(complete_date) from InquisitionResponse
			where complete_date is not null and
			inquisition in (select evaluation from Episode)');

		$this->start_date = new SwatDate($oldest_date_string);
	}

	// }}}
	// {{{ protected function initCreditTypes()

	protected function initCreditTypes()
	{
		$this->credit_types = SwatDB::query(
			$this->db,
			'select * from CMECreditType order by title, id',
			SwatDBClassMap::get('CMECreditTypeWrapper')
		);
	}

	// }}}
	// {{{ protected function initReportsByQuarter()

	protected function initReportsByQuarter()
	{
		$this->reports_by_quarter = array();

		$sql = 'select * from EvaluationReport order by quarter';
		$reports = SwatDB::query(
			$this->db,
			$sql,
			'CMEEvaluationReportWrapper'
		);

		$reports->attachSubDataObjects(
			'credit_type',
			$this->credit_types
		);

		foreach ($reports as $report) {
			$quarter = clone $report->quarter;
			$quarter->convertTZ($this->default_time_zone);
			$quarter = $quarter->formatLikeIntl('qqq-yyyy');
			$credit_type = $report->credit_type->shortname;
			if (!isset($this->reports_by_quarter[$quarter])) {
				$this->reports_by_quarter[$quarter] = array();
			}
			$this->reports_by_quarter[$quarter][$credit_type] = $report;
		}
	}

	// }}}
	// {{{ protected function getQuarters()

	protected function getQuarters(CMECreditType $credit_type)
	{
		$quarters = array();

		$now = new SwatDate();
		$now->convertTZ($this->default_time_zone);

		$year = $this->start_date->getYear();

		$start_date = new SwatDate();
		$start_date->setTime(0, 0, 0);
		$start_date->setDate($year, 1, 1);
		$start_date->setTZ($this->default_time_zone);

		$end_date = clone $start_date;
		$end_date->addMonths(3);

		$display_end_date = clone $end_date;
		$display_end_date->subtractMonths(1);

		while ($end_date->before($now)) {
			for ($quarter = 1; $quarter <= 4; $quarter++) {
				// Make sure the quarter has ended before generating the
				// report. Reports are cached and are not regenerated when new
				// data is available. If reports are generated for partial
				// quarters, the partial report is cached until the cache is
				// manually cleared.
				if ($end_date->after($now)) {
					break;
				}

				$num_credits = $this->getQuarterCredits(
					$credit_type,
					$year,
					$quarter
				);

				$num_responses = $this->getQuarterResponses(
					$credit_type,
					$year,
					$quarter
				);

				if ($num_credits > 0 && $num_responses > 0) {
					$quarters[] = clone $start_date;
				}

				$start_date->addMonths(3);
				$end_date->addMonths(3);
				$display_end_date->addMonths(3);
			}

			$year++;
		}

		return $quarters;
	}

	// }}}
	// {{{ protected function getQuarterCredits()

	protected function getQuarterCredits(CMECreditType $credit_type, $year,
		$quarter)
	{
		$start_month = (($quarter - 1) * 3) + 1;

		$start_date = new SwatDate();
		$start_date->setTime(0, 0, 0);
		$start_date->setDate($year, $start_month, 1);
		$start_date->setTZ($this->default_time_zone);

		$end_date = clone $start_date;
		$end_date->addMonths(3);

		$sql = sprintf(
			'select count(1) from CMECredit
			where evaluation in (
				select inquisition from InquisitionResponse
				where complete_date is not null
				and convertTZ(complete_date, %1$s) >= %2$s
				and convertTZ(complete_date, %1$s) < %3$s
			) and credit_type = %4$s',
			$this->db->quote($this->config->date->time_zone, 'text'),
			$this->db->quote($start_date->getDate(), 'date'),
			$this->db->quote($end_date->getDate(), 'date'),
			$this->db->quote($credit_type->id, 'integer')
		);

		return SwatDB::queryOne($this->db, $sql);
	}

	// }}}
	// {{{ protected function getQuarterResponses()

	protected function getQuarterResponses(CMECreditType $credit_type, $year,
		$quarter)
	{
		$start_month = (($quarter - 1) * 3) + 1;

		$start_date = new SwatDate();
		$start_date->setTime(0, 0, 0);
		$start_date->setDate($year, $start_month, 1);
		$start_date->setTZ($this->default_time_zone);

		$end_date = clone $start_date;
		$end_date->addMonths(3);

		$sql = sprintf(
			'select count(1) from InquisitionResponse
			where complete_date is not null
				and convertTZ(complete_date, %1$s) >= %2$s
				and convertTZ(complete_date, %1$s) < %3$s
				and inquisition in (
					select evaluation from CMECredit where credit_type = %4$s
				) and account in (
					select id from Account where Account.delete_date is null
				)',
			$this->db->quote($this->config->date->time_zone, 'text'),
			$this->db->quote($start_date->getDate(), 'date'),
			$this->db->quote($end_date->getDate(), 'date'),
			$this->db->quote($credit_type->id, 'integer')
		);

		return SwatDB::queryOne($this->db, $sql);
	}

	// }}}
	// {{{ protected function getDataObject()

	protected function getDataObject(SwatDate $quarter,
		CMECreditType $credit_type, $filename)
	{
		$report = new CMEEvaluationReport();
		$report->setDatabase($this->db);
		$report->setFileBase(__DIR__);

		$quarter = clone $quarter;
		$quarter->toUTC();

		$report->quarter     = $quarter;
		$report->credit_type = $credit_type;
		$report->filename    = $filename;
		$report->createdate  = new SwatDate();
		$report->createdate->toUTC();

		return $report;
	}

	// }}}
	// {{{ protected function saveReport()

	protected function saveReport(SwatDate $quarter, CMECreditType $credit_type,
		$filepath)
	{
		$year    = $quarter->getYear();
		$quarter = intval($quarter->formatLikeIntl('qq'));

		$report = new CMEEvaluationReportGenerator(
			$this,
			$credit_type,
			$year,
			$quarter
		);

		$report->saveFile($filepath);
	}

	// }}}
	// {{{ protected function getFilename()

	protected function getFilename(SwatDate $quarter,
		CMECreditType $credit_type)
	{
		// replace spaces with dashes
		$title = str_replace(' ', '-', $credit_type->title);

		// strip non-word or dash characters
		$title = preg_replace('/[^\w-]/', '', $title);

		return sprintf(
			$this->getFilenamePattern(),
			$title,
			$quarter->formatLikeIntl('QQQ-yyyy')
		);
	}

	// }}}
	// {{{ abstract protected function getFileBase()

	abstract protected function getFileBase();

	// }}}
	// {{{ abstract protected function getFilenamePattern()

	abstract protected function getFilenamePattern();

	// }}}
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteCommandLineConfigModule',
			'database' => 'SiteDatabaseModule',
		);
	}

	// }}}
}

?>
