<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'CGB.Datatables',
            'Datatable',
            [
                'Datatable' => 'list, export, print'
            ],
            // non-cacheable actions
            [
                'Datatable' => 'list, export, print'
            ]
        );

    // wizards
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    datatable {
                        icon = ' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('datatables') . 'Resources/Public/Icons/module_datatables.svg
                        title = LLL:EXT:datatables/Resources/Private/Language/locallang_db.xlf:tx_datatables_domain_model_datatable
                        description = LLL:EXT:datatables/Resources/Private/Language/locallang_db.xlf:tx_datatables_domain_model_datatable.description
                        tt_content_defValues {
                            CType = list
                            list_type = datatables_datatable
                        }
                    }
                }
                show = *
            }
       }'
    );
    }
);
## EXTENSION BUILDER DEFAULTS END TOKEN - Everything BEFORE this line is overwritten with the defaults of the extension builder

// \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('datatables', 'setup', 'config.tx_extbase.features.requireCHashArgumentForActionArguments = 0', 1);

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
  'datatables-icon',
  \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
  ['source' => 'EXT:datatables/Resources/Public/Images/datatables-icon.png']
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    'mod {
        wizards.newContentElement.wizardItems.plugins {
            elements {
                datatable {
                    icon >
                    iconIdentifier = datatables-icon
                    title = LLL:EXT:datatables/Resources/Private/Language/locallang_db.xlf:tx_datatables_domain_model_datatable.ce
                    description = LLL:EXT:datatables/Resources/Private/Language/locallang_db.xlf:tx_datatables_domain_model_datatable.description.ce
                    tt_content_defValues {
                        CType = list
                        list_type = datatables_datatable
                    }
                }
            }
            show = *
        }
   }'
);