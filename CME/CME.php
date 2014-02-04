<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * Container for package wide static methods
 *
 * @package   CME
 * @copyright 2014 silverorange
 */
class CME
{
	// {{{ constants

	/**
	 * The package identifier
	 */
	const PACKAGE_ID = 'CME';

	/**
	 * The gettext domain for CME
	 *
	 * This is used to support multiple locales.
	 */
	const GETTEXT_DOMAIN = 'cme';

	// }}}
	// {{{ public static function _()

	/**
	 * Translates a phrase
	 *
	 * This is an alias for {@link CME::gettext()}.
	 *
	 * @param string $message the phrase to be translated.
	 *
	 * @return string the translated phrase.
	 */
	public static function _($message)
	{
		return CME::gettext($message);
	}

	// }}}
	// {{{ public static function gettext()

	/**
	 * Translates a phrase
	 *
	 * This method relies on the php gettext extension and uses dgettext()
	 * internally.
	 *
	 * @param string $message the phrase to be translated.
	 *
	 * @return string the translated phrase.
	 */
	public static function gettext($message)
	{
		return dgettext(CME::GETTEXT_DOMAIN, $message);
	}

	// }}}
	// {{{ public static function ngettext()

	/**
	 * Translates a plural phrase
	 *
	 * This method should be used when a phrase depends on a number. For
	 * example, use ngettext when translating a dynamic phrase like:
	 *
	 * - "There is 1 new item" for 1 item and
	 * - "There are 2 new items" for 2 or more items.
	 *
	 * This method relies on the php gettext extension and uses dngettext()
	 * internally.
	 *
	 * @param string $singular_message the message to use when the number the
	 *                                  phrase depends on is one.
	 * @param string $plural_message the message to use when the number the
	 *                                phrase depends on is more than one.
	 * @param integer $number the number the phrase depends on.
	 *
	 * @return string the translated phrase.
	 */
	public static function ngettext($singular_message, $plural_message, $number)
	{
		return dngettext(
			CME::GETTEXT_DOMAIN,
			$singular_message,
			$plural_message,
			$number
		);
	}

	// }}}
	// {{{ public static function setupGettext()

	public static function setupGettext()
	{
		$path = '@DATA-DIR@/CME/locale';
		if (substr($path, 0, 1) === '@') {
			$path = __DIR__.'/../locale';
		}

		bindtextdomain(CME::GETTEXT_DOMAIN, $path);
		bind_textdomain_codeset(CME::GETTEXT_DOMAIN, 'UTF-8');
	}

	// }}}
	// {{{ public static function getDependencies()

	/**
	 * Gets the packages this package depends on
	 *
	 * @return array an array of package IDs that this package depends on.
	 */
	public static function getDependencies()
	{
		require_once 'Swat/SwatYUI.php';
		return array(SwatYUI::PACKAGE_ID);
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Don't allow instantiation of the CME object
	 *
	 * This class contains only static methods and should not be instantiated.
	 */
	private function __construct()
	{
	}

	// }}}
}

CME::setupGettext();

?>
