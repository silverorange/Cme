<?php

/**
 * Factory for creating certificates.
 *
 * This factory provides an easy method for overriding the default certificates
 * provided by CME or other packages.
 *
 * Example usage:
 *
 * <code>
 * <?php
 *
 * // get a new AAEM certificate
 * $certificate = CMECertificateFactory::get($this->app, 'aaem');
 *
 * // register a new certificate class for AAEM
 * CMECertificateFactory::registerCertificate('aaem', 'MyCertificate');
 *
 * // create a new certificate of class 'MyCertificate'
 * $certificate = CMECertificateFactory::get($this->app, 'aaem');
 *
 * ?>
 * </code>
 *
 * When an undefined certificate class is requested, the factory attempts to
 * find and require a class-definition file for the certificate class using the
 * factory search path. All search paths are relative to the PHP include path.
 * The search path '<code>CME/certificates</code>' is included by default.
 * Search paths can be added and removed using the
 * {@link CMECertificateFactory::addPath()} and
 * {@link CMECertificateFactory::removePath()} methods.
 *
 * @copyright 2014-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @see       CMECertificate
 */
class CMECertificateFactory extends SwatObject
{
    /**
     * List of registered certificate classes indexed by the certificate type.
     *
     * @var array
     */
    private static $certificate_class_names_by_type = [];

    /**
     * Paths to search for class-definition files.
     *
     * @var array
     */
    private static $search_paths = ['CME/certificates'];

    /**
     * Gets a certificate of the specified type.
     *
     * @param SiteApplication $app  the application in which to get the
     *                              certificate
     * @param string          $type the type of certificate to get. There must
     *                              be a certificate class registered for this
     *                              type.
     *
     * @return CMECertificate the certificate of the specified type. The
     *                        certificate will be an instance of whatever class
     *                        was registered for the certificate type.
     *
     * @throws InvalidArgumentException if there is no certificate registered
     *                                  for the requested <i>$type</i>
     */
    public static function get(SiteApplication $app, $type)
    {
        $type = strval($type);
        if (!array_key_exists($type, self::$certificate_class_names_by_type)) {
            throw new InvalidArgumentException(
                sprintf(
                    'No certificates are registered with the type "%s".',
                    $type
                )
            );
        }

        $certificate_class_name = self::$certificate_class_names_by_type[$type];
        self::loadCertificateClass($certificate_class_name);

        $certificate = new $certificate_class_name();
        $certificate->setApplication($app);

        return $certificate;
    }

    /**
     * Registers a certificate class with the factory.
     *
     * Certificate classes must be registed with the factory before they are
     * used. When a certificate class is registered for a particular type, an
     * instance of the certificate class is returned whenever a certificate of
     * that type is requested.
     *
     * @param string $type                   the certificate type
     * @param string $certificate_class_name the class name of the certificate.
     *                                       The class does not need to be
     *                                       defined until a certificate of the
     *                                       specified type is requested.
     */
    public static function registerCertificate($type, $certificate_class_name)
    {
        $type = strval($type);
        self::$certificate_class_names_by_type[$type] = $certificate_class_name;
    }

    /**
     * Adds a search path for class-definition files.
     *
     * When an undefined certificate class is requested, the factory attempts
     * to find and require a class-definition file for the certificate class.
     *
     * All search paths are relative to the PHP include path. The search path
     * '<code>CME/certificates</code>' is included by default.
     *
     * @param string $search_path the path to search for certificate
     *                            class-definition files
     *
     * @see CMECertificateFactory::removePath()
     */
    public static function addPath($search_path)
    {
        if (!in_array($search_path, self::$search_paths, true)) {
            // add path to front of array since it is more likely we will find
            // class-definitions in manually added search paths
            array_unshift(self::$search_paths, $search_path);
        }
    }

    /**
     * Removes a search path for certificate class-definition files.
     *
     * @param string $path the path to remove
     *
     * @see CMECertificateFactory::addPath()
     */
    public static function removePath($path)
    {
        $index = array_search($path, self::$paths);
        if ($index !== false) {
            array_splice(self::$paths, $index, 1);
        }
    }

    /**
     * Loads a certificate class-definition if it is not defined.
     *
     * This checks the factory search path for an appropriate source file.
     *
     * @param string $certificate_class_name the name of the certificate class
     *
     * @throws SwatClassNotFoundException if the certificate class is not
     *                                    defined and no suitable file in the
     *                                    certificate search path contains the
     *                                    class definition
     */
    private static function loadCertificateClass($certificate_class_name)
    {
        if (!class_exists($certificate_class_name)) {
            throw new SwatClassNotFoundException(
                sprintf(
                    'Certificate class "%s" does not exist and could not ' .
                    'be found in the search path.',
                    $certificate_class_name
                ),
                0,
                $certificate_class_name
            );
        }
    }

    /**
     * This class contains only static methods and should not be instantiated.
     */
    private function __construct() {}
}
