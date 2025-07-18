<?php

/**
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEEvaluationReportDownload extends AdminPage
{
    /**
     * @var CMEEvaluationReport
     */
    protected $report;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();
        $this->initReport();
    }

    protected function initReport()
    {
        $quarter = SiteApplication::initVar(
            'quarter',
            null,
            SiteApplication::VAR_GET
        );

        if ($quarter === null
            || preg_match('/^2[0-9]{3}-0[1-4]$/', $quarter) === 0) {
            throw new AdminNotFoundException('Invalid quarter.');
        }

        [$year, $quarter] = explode('-', $quarter, 2);

        $start_month = ((intval($quarter) - 1) * 3) + 1;

        $quarter = new SwatDate();
        $quarter->setTime(0, 0, 0);
        $quarter->setDate($year, $start_month, 1);
        $quarter->setTZ($this->app->default_time_zone);
        $quarter->toUTC();

        $type = SiteApplication::initVar(
            'type',
            null,
            SiteApplication::VAR_GET
        );

        $provider = SwatDBClassMap::new(CMEProvider::class);
        $provider->setDatabase($this->app->db);
        if (!$provider->loadByShortname($type)) {
            throw new AdminNotFoundException('Invalid CME provider.');
        }

        $sql = sprintf(
            'select * from EvaluationReport
			where quarter = %s and provider = %s',
            $this->app->db->quote($quarter->getDate(), 'date'),
            $this->app->db->quote($provider->id, 'integer')
        );

        $this->report = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(CMEEvaluationReportWrapper::class)
        )->getFirst();

        if (!$this->report instanceof CMEEvaluationReport) {
            throw new AdminNotFoundException(
                sprintf(
                    'Report not found for quarter %s.',
                    $quarter->getDate()
                )
            );
        }

        $this->report->setFileBase('../../system/evaluation-report-updater');
        if (!file_exists($this->report->getFilePath())) {
            throw new AdminNotFoundException(
                sprintf(
                    'Report file ‘%s’ not found',
                    $this->report->getFilePath()
                )
            );
        }
    }

    // build phase

    protected function buildInternal()
    {
        header(
            sprintf(
                'Content-Disposition: attachment;filename="%s"',
                $this->report->filename
            )
        );

        header('Content-Type: application/pdf');

        readfile($this->report->getFilePath());

        exit;
    }
}
