<?php

/**
 * This is the package.xml generator for the CME pacakge
 *
 * PHP version 5
 *
 * Copyright 2011-2014 silverorange
 *
 * All rights reserved.
 *
 * @package   CME
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2011-2014 silverorange
 */

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$api_version     = '0.1.0';
$api_state       = 'beta';

$release_version = '0.1.0';
$release_state   = 'beta';
$release_notes   = 'initial release';

$description =
	"Continuing medical education certification system.";

$package = new PEAR_PackageFileManager2();

$package->setOptions(
	array(
		'filelistgenerator'       => 'file',
		'simpleoutput'            => true,
		'baseinstalldir'          => '/',
		'packagedirectory'        => './',
		'dir_roles'               => array(
			'CME'                 => 'php',
			'sql'                 => 'data',
			'tests'               => 'test',
			'www'                 => 'data',
			'dependencies'        => 'data',
		),
		'exceptions'              => array(
			'LICENSE'             => 'doc',
			'README.md'           => 'doc',
		),
		'ignore'                  => array(
			'package.php',
			'*.tgz',
		),
	)
);

$package->setPackage('CME');
$package->setSummary(
	'Continuing Medical Education (CME) certification system.'
);
$package->setDescription($description);
$package->setChannel('pear.silverorange.com');
$package->setPackageType('php');
$package->setLicense('Proprietary', '');

$package->setNotes($release_notes);
$package->setReleaseVersion($release_version);
$package->setReleaseStability($release_state);
$package->setAPIVersion($api_version);
$package->setAPIStability($api_state);

$package->addMaintainer(
	'lead',
	'gauthierm',
	'Mike Gauthier',
	'mike@silverorange.com'
);

$package->addPackageDepWithChannel(
	'required',
	'Swat',
	'pear.silverorange.com',
	'1.4.65'
);

$package->addPackageDepWithChannel(
	'required',
	'Site',
	'pear.silverorange.com',
	'1.4.65'
);

$package->addPackageDepWithChannel(
	'required',
	'Admin',
	'pear.silverorange.com',
	'1.3.71'
);

$package->addPackageDepWithChannel(
	'required',
	'Inquisition',
	'pear.silverorange.com',
	'0.0.21'
);

$package->setPhpDep('5.2.1');
$package->setPearInstallerDep('1.4.0');
$package->generateContents();

if (   isset($_GET['make'])
	|| (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')
) {
	$package->writePackageFile();
} else {
	$package->debugPackageFile();
}

?>
