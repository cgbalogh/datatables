<?php
$ext_path =   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('datatables');
require_once($ext_path . 'Classes/UserFunc/TcaUserFunc.php');
$items = \CGB\Datatables\UserFunc\TcaUserFunc::getClasses();

$GLOBALS['TCA']['tx_datatables_domain_model_datatable']['columns']['domain_model'] = array(
    'exclude' => 1,
    'label' => 'LLL:EXT:datatables/Resources/Private/Language/locallang_db.xlf:tx_datatables_domain_model_datatable.domain_model',
    'config' => array(
        'type' => 'select',
        'renderType' => 'selectSingle',
        'items' => $items,
        'size' => 1,
        'maxitems' => 1,
        'minitems' => 0,
    ),
);
