<?php

/**
 * @copyright 2012-2026 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuizResponseResetDependency extends AdminListDependency
{
    protected function getStatusLevelText($status_level, $count)
    {
        return match ($status_level) {
            self::DELETE => sprintf(
                CME::ngettext(
                    'Reset the following %s?',
                    'Reset the following %s?',
                    $count
                ),
                $this->getTitle($count)
            ),
            self::NODELETE => sprintf(
                CME::ngettext(
                    'The following %s can not be reset:',
                    'The following %s can not be reset:',
                    $count
                ),
                $this->getTitle($count)
            ),
            default => parent::getStatusLevelText($status_level, $count),
        };
    }
}
