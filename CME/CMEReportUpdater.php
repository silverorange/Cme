<?php

/**
 * @package   CME
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEReportUpdater extends SiteCommandLineApplication
{


	/**
	 * @var integer
	 */
	protected $start_date;

	/**
	 * @var CMEProviderWrapper
	 */
	protected $providers;

	/**
	 * @var array
	 */
	protected $reports_by_quarter = null;




	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		$this->initStartDate();
		$this->initProviders();
		$this->initReportsByQuarter();

		$this->debug($this->getStatusLine(), true);

		$this->lock();

		foreach ($this->providers as $provider) {
			$this->debug("{$provider->title}:\n", true);
			$shortname = $provider->shortname;

			foreach ($this->getQuarters($provider) as $quarter) {
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
						$provider,
						$this->getFilename($quarter, $provider)
					);

					$this->debug("   => generating report ... ");
					$this->saveReport(
						$quarter,
						$provider,
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




	/**
	 * @return string
	 */
	abstract protected function getStatusLine();




	abstract protected function getReports();




	abstract protected function getReportClassName();




	abstract protected function getReportGenerator(
		CMEProvider $provider,
		$year,
		$quarter
	);




	abstract protected function getFileBase();




	abstract protected function getFilenamePattern();




	protected function initStartDate()
	{
		$oldest_date_string = SwatDB::queryOne(
			$this->db,
			'select min(earned_date) from AccountEarnedCMECredit
				inner join CMECredit
					on AccountEarnedCMECredit.credit = CMECredit.id
				inner join CMEFrontMatter
					on CMECredit.front_matter = CMEFrontMatter.id'
		);

		$this->start_date = new SwatDate($oldest_date_string);
	}




	protected function initProviders()
	{
		$this->providers = SwatDB::query(
			$this->db,
			'select * from CMEProvider order by title, id',
			SwatDBClassMap::get('CMEProviderWrapper')
		);
	}




	protected function initReportsByQuarter()
	{
		$this->reports_by_quarter = array();

		$reports = $this->getReports();
		$reports->attachSubDataObjects(
			'provider',
			$this->providers
		);

		foreach ($reports as $report) {
			$quarter = clone $report->quarter;
			$quarter->convertTZ($this->default_time_zone);
			$quarter = $quarter->formatLikeIntl('qqq-yyyy');
			$provider = $report->provider->shortname;
			if (!isset($this->reports_by_quarter[$quarter])) {
				$this->reports_by_quarter[$quarter] = array();
			}
			$this->reports_by_quarter[$quarter][$provider] = $report;
		}
	}




	protected function getQuarters(CMEProvider $provider)
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

				$num_credits = $this->getQuarterEarnedCredits(
					$provider,
					$year,
					$quarter
				);

				if ($num_credits > 0) {
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




	protected function getQuarterEarnedCredits(
		CMEProvider $provider,
		$year,
		$quarter
	) {
		$start_month = (($quarter - 1) * 3) + 1;

		$start_date = new SwatDate();
		$start_date->setTime(0, 0, 0);
		$start_date->setDate($year, $start_month, 1);
		$start_date->setTZ($this->default_time_zone);

		$end_date = clone $start_date;
		$end_date->addMonths(3);

		$sql = sprintf(
			'select count(1)
			from AccountCMEProgressCreditBinding
			inner join AccountCMEProgress on
				AccountCMEProgressCreditBinding.progress = AccountCMEProgress.id
			inner join AccountEarnedCMECredit on
				AccountEarnedCMECredit.account = AccountCMEProgress.account
				and AccountCMEProgressCreditBinding.credit =
					AccountEarnedCMECredit.credit
			inner join CMECredit on
				CMECredit.id = AccountEarnedCMECredit.credit
			inner join Account on AccountCMEProgress.account = Account.id
			where CMECredit.front_matter in (
					select CMEFrontMatterProviderBinding.front_matter
					from CMEFrontMatterProviderBinding
					where CMEFrontMatterProviderBinding.provider = %s
				)
			and convertTZ(earned_date, %s) >= %s
			and convertTZ(earned_date, %s) < %s
			and Account.delete_date is null',
			$this->db->quote($provider->id, 'integer'),
			$this->db->quote($this->config->date->time_zone, 'text'),
			$this->db->quote($start_date->getDate(), 'date'),
			$this->db->quote($this->config->date->time_zone, 'text'),
			$this->db->quote($end_date->getDate(), 'date')
		);

		return SwatDB::queryOne($this->db, $sql);
	}




	protected function getDataObject(
		SwatDate $quarter,
		CMEProvider $provider,
		$filename
	) {
		$class_name = $this->getReportClassName();
		$report = new $class_name();
		$report->setDatabase($this->db);
		$report->setFileBase($this->getFileBase());

		$quarter = clone $quarter;
		$quarter->toUTC();

		$report->quarter    = $quarter;
		$report->provider   = $provider;
		$report->filename   = $filename;
		$report->createdate = new SwatDate();
		$report->createdate->toUTC();

		return $report;
	}




	protected function saveReport(
		SwatDate $quarter,
		CMEProvider $provider,
		$filepath
	) {
		$year    = $quarter->getYear();
		$quarter = intval($quarter->formatLikeIntl('qq'));

		$report = $this->getReportGenerator(
			$provider,
			$year,
			$quarter
		);

		$report->saveFile($filepath);
	}




	protected function getFilename(
		SwatDate $quarter,
		CMEProvider $provider
	) {
		// replace spaces with dashes
		$title = str_replace(' ', '-', $provider->title);

		// strip non-word or dash characters
		$title = preg_replace('/[^\w-]/', '', $title);

		return sprintf(
			$this->getFilenamePattern(),
			$title,
			$quarter->formatLikeIntl('QQQ-yyyy')
		);
	}




	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array_merge(
			parent::getDefaultModuleList(),
			[
				'config' => SiteCommandLineConfigModule::class,
				'database' => SiteDatabaseModule::class,
			]
		);
	}


}

?>
