<?php

require_once 'CME/CMEReportUpdater.php';
require_once 'CME/dataobjects/CMEQuizReport.php';
require_once 'CME/dataobjects/CMEQuizReportWrapper.php';
require_once 'CME/CMEQuizReportGenerator.php';

/**
 * @package   CME
 * @copyright 2011-2016 silverorange
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
	// {{{ protected function getReports()

	protected function getReports()
	{
		return SwatDB::query(
			$this->db,
			'select * from QuizReport order by quarter',
			SwatDBClassMap::get('CMEQuizReportWrapper')
		);
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
