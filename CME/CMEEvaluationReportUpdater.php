<?php

require_once 'CME/CMEReportUpdater.php';
require_once 'CME/dataobjects/CMEEvaluationReport.php';
require_once 'CME/dataobjects/CMEEvaluationReportWrapper.php';
require_once 'CME/CMEEvaluationReportGenerator.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEEvaluationReportUpdater extends CMEReportUpdater
{
	// {{{ protected function getStatusLine()

	protected function getStatusLine()
	{
		return CME::_("Generating Quarterly Evaluation Reports\n\n");
	}

	// }}}
	// {{{ protected function getReports()

	protected function getReports()
	{
		return SwatDB::query(
			$this->db,
			'select * from EvaluationReport order by quarter',
			SwatDBClassMap::get('CMEEvaluationReportWrapper')
		);
	}

	// }}}
	// {{{ protected function getReportClassName()

	protected function getReportClassName()
	{
		return SwatDBClassMap::get('CMEEvaluationReport');
	}

	// }}}
	// {{{ protected function getReportGenerator()

	protected function getReportGenerator(CMEProvider $provider,
		$year, $quarter)
	{
		$generator_class_name = $this->getReportGeneratorClassName();

		return new $generator_class_name(
			$this,
			$provider,
			$year,
			$quarter
		);
	}

	// }}}
	// {{{ protected function getReportGeneratorClassName()

	protected function getReportGeneratorClassName()
	{
		return 'CMEEvaluationReportGenerator';
	}

	// }}}
}

?>
