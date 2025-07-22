<?php

/**
 * @copyright 2013-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @property int     $id
 * @property ?string $shortname
 * @property ?string $title
 * @property ?string $credit_title
 * @property ?string $credit_title_plural
 * @property ?int    $displayorder
 */
class CMEProvider extends SwatDBDataObject
{
    public $id;
    public $shortname;
    public $title;
    public $credit_title;
    public $credit_title_plural;
    public $displayorder;

    public function loadByShortname($shortname)
    {
        $this->checkDB();

        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where shortname = %s',
                $this->table,
                $this->db->quote($shortname, 'text')
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row === null) {
            return false;
        }

        $this->initFromRow($row);
        $this->generatePropertyHashes();

        return true;
    }

    public function getCreditTitle($hours, $credit_count = 1, $is_free = false)
    {
        $locale = SwatI18NLocale::get();

        return sprintf(
            SwatString::minimizeEntities(
                ($is_free)
                    ? CME::_('%s Free %s%s%s certified by %s')
                    : CME::_('%s %s%s%s certified by %s')
            ),
            SwatString::minimizeEntities($locale->formatNumber($hours)),
            '<em>',
            (abs($hours - 1.0) < 0.01)
                ? SwatString::minimizeEntities($this->credit_title)
                : SwatString::minimizeEntities($this->credit_title_plural),
            '</em>',
            SwatString::minimizeEntities($this->title)
        );
    }

    protected function init()
    {
        $this->table = 'CMEProvider';
        $this->id_field = 'integer:id';
    }
}
