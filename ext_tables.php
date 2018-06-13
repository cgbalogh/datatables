<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'CGB.Datatables',
            'Datatable',
            'Datatable'
        );

        $pluginSignature = str_replace('_', '', 'datatables') . '_datatable';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:datatables/Configuration/FlexForms/flexform_datatable.xml');

        if (TYPO3_MODE === 'BE') {

            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'CGB.Datatables',
                'web', // Make module a submodule of 'web'
                'configdatatable', // Submodule key
                '', // Position
                [
                    'DatatablesManager' => 'index, create',
                    
                ],
                [
                    'access' => 'user,group',
                    'icon'   => 'EXT:datatables/Resources/Public/Icons/module_datatables.svg',
                    'labels' => 'LLL:EXT:datatables/Resources/Private/Language/locallang_configdatatable.xlf',
                    'navigationComponentId' => '',
                ]
            );

        }

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('datatables', 'Configuration/TypoScript', 'DataTables');

    }
);
## EXTENSION BUILDER DEFAULTS END TOKEN - Everything BEFORE this line is overwritten with the defaults of the extension builder