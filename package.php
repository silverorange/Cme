<?php

/**
 * This is the package.xml generator for the CME pacakge
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright 2011-2014 silverorange
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package   CME
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$api_version     = '0.1.0';
$api_state       = 'beta';

$release_version = '0.1.3';
$release_state   = 'beta';
$release_notes   = 'beta release';

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
$package->setLicense(
	'MIT License',
	'http://www.opensource.org/licenses/mit-license.html'
);

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
	'2.1.14'
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
	'0.0.28'
);

$package->addPackageDepWithChannel(
	'optional',
	'DOMPDF',
	'pear.silverorange.com',
	'0.6.0'
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
