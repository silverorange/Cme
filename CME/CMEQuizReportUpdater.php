<?php

require_once 'CME/CMEReportUpdater.php';
require_once 'CME/dataobjects/CMEQuizReport.php';
require_once 'CME/dataobjects/CMEQuizReportWrapper.php';
require_once 'CME/CMEQuizReportGenerator.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEQuizReportUpdater extends CMEReportUpdater
{
	// {{{ protected function getStatusLine()

	protected function getStatusLine()
	{
		return CME::_("Generating Quarterly Quiz Reports\n\n");
	}

	// }}}
	// {{{ protected function initReportsByQuarter()

	protected function initReportsByQuarter()
	{
		$this->reports_by_quarter = array();

		$sql = 'select * from QuizReport order by quarter';
		$reports = SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('CMEQuizReportWrapper')
		);

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

	// }}}
	// {{{ protected function getReportClassName()

	protected function getReportClassName()
	{
		return SwatDBClassMap::get('CMEQuizReport');
	}

	// }}}
	// {{{ protected function getReportGenerator()

	protected function getReportGenerator(CMEProvider $provider,
		$year, $quarter)
	{
		return new CMEQuizReportGenerator(
			$this,
			$provider,
			$year,
			$quarter
		);
	}

	// }}}
}

?>
