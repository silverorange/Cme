<?php

require_once 'CME/CME.php';
require_once 'Admin/AdminListDependency.php';

/**
 * @package   CME
 * @copyright 2012-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuizResponseResetDependency extends AdminListDependency
{
	// {{{ protected function getStatusLevelText()

	protected function getStatusLevelText($status_level, $count)
	{
		switch ($status_level) {
		case self::DELETE:
			$message = sprintf(
				CME::ngettext(
					'Reset the following %s?',
					'Reset the following %s?',
					$count
				),
				$this->getTitle($count)
			);
			break;

		case self::NODELETE:
			$message = sprintf(
				CME::ngettext(
					'The following %s can not be reset:',
					'The following %s can not be reset:',
					$count
				),
				$this->getTitle($count)
			);
			break;

		default:
			$message = parent::getStatusLevelText($status_level, $count);
			break;
		}
		return $message;
	}

	// }}}
}

?>
