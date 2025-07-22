<?php

/**
 * @copyright 2013-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @property int                $id
 * @property ?string            $objectives
 * @property ?string            $planning_committee_no_disclosures
 * @property ?string            $planning_committee_with_disclosures
 * @property ?string            $support_staff_no_disclosures
 * @property ?string            $support_staff_with_disclosures
 * @property ?SwatDate          $review_date
 * @property ?SwatDate          $release_date
 * @property ?bool              $enabled
 * @property ?int               $passing_grade
 * @property ?string            $email_content_pass
 * @property ?string            $email_content_fail
 * @property ?bool              $resettable
 * @property ?CMEEvaluation     $evaluation
 * @property CMECreditWrapper   $credits
 * @property CMEProviderWrapper $providers
 */
abstract class CMEFrontMatter extends SwatDBDataObject
{
    public $id;
    public $objectives;
    public $planning_committee_no_disclosures;
    public $planning_committee_with_disclosures;
    public $support_staff_no_disclosures;
    public $support_staff_with_disclosures;
    public $release_date;
    public $review_date;
    public $enabled;
    public $passing_grade;
    public $email_content_pass;
    public $email_content_fail;
    public $resettable;

    public function getProviderTitleList()
    {
        $titles = [];
        foreach ($this->providers as $provider) {
            $titles[] = $provider->title;
        }

        return SwatString::toList($titles);
    }

    abstract protected function getAttestationLink();

    abstract protected function getEvaluationLink();

    protected function init()
    {
        $this->table = 'CMEFrontMatter';
        $this->id_field = 'integer:id';

        $this->registerInternalProperty(
            'evaluation',
            SwatDBClassMap::get(CMEEvaluation::class)
        );

        $this->registerDateProperty('release_date');
        $this->registerDateProperty('review_date');
    }

    protected function loadCredits()
    {
        $sql = sprintf(
            'select * from CMECredit where front_matter = %s
			order by displayorder asc, hours desc',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(CMECreditWrapper::class)
        );
    }

    protected function loadProviders()
    {
        $sql = sprintf(
            'select CMEProvider.*
			from CMEProvider
			inner join CMEFrontMatterProviderBinding on
				CMEFrontMatterProviderBinding.provider = CMEProvider.id
			where CMEFrontMatterProviderBinding.front_matter = %s
			order by CMEProvider.displayorder, CMEProvider.id',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(CMEProviderWrapper::class)
        );
    }
}
