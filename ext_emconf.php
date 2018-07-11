<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "datatables"
 *
 * Auto generated by Extension Builder 2017-02-20
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'CGB DataTables',
	'description' => 'Integrates the DataTables extension for jQuery.',
	'category' => 'plugin',
	'author' => 'Christoph Balogh',
	'author_email' => 'cb@lustige-informatik.at',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
    'version' => '8.7.8-180704',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-8.9.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

/**
 * 8.7.8-180704
 * added no login required
 * 
 * 8.7.8-180626
 * added support for fe_users and fe_groups
 * 
 * 8.7.2-rc80531
 * added image translation for boolean types
 * 
 * 8.7.2-rc80527
 * added object reference to relax5core
 * changed behaviour of grouped function in order to support any text
 * 
 * 8.7.2-rc80525
 * added constraint CONTAINS
 * 
 * 8.7.2-rc80509
 * added possibility to do global filtering on feuser attributes
 * use feuser.<attribute> as second operator in comparision like:
 * currentState.usergroup == 6 & owner.team == feuser.team
 */