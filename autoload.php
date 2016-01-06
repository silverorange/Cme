<?php

namespace Silverorange\Autoloader;

$package = new Package('silverorange/cme');

$package->addRule(
	new Rule(
		'dataobjects',
		'CME',
		array(
			'AccountCMEProgress',
			'AccountEarnedCMECredit',
			'Account',
			'Credit',
			'Evaluation',
			'EvaluationReport',
			'EvaluationResponse',
			'FrontMatter',
			'Provider',
			'Quiz',
			'QuizReport',
			'QuizResponse',
			'Wrapper'
		)
	)
);
$package->addRule(new Rule('certificates', 'CME', 'Certificate'));
$package->addRule(new Rule('pages', 'CME', array('Page', 'Server')));
$package->addRule(new Rule('', 'CME'));

Autoloader::addPackage($package);

?>
