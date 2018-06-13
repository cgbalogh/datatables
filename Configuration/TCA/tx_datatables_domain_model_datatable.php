<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:datatables/Resources/Private/Language/locallang_db.xlf:tx_datatables_domain_model_datatable',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'enablecolumns' => [
        ],
        'searchFields' => 'name,domain_model,columnsettings',
        'iconfile' => 'EXT:datatables/Resources/Public/Icons/tx_datatables_domain_model_datatable.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, name, domain_model, columnsettings',
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, name, domain_model, columnsettings'],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ]
                ],
                'default' => 0,
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_datatables_domain_model_datatable',
                'foreign_table_where' => 'AND tx_datatables_domain_model_datatable.pid=###CURRENT_PID### AND tx_datatables_domain_model_datatable.sys_language_uid IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ],
        ],

        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:datatables/Resources/Private/Language/locallang_db.xlf:tx_datatables_domain_model_datatable.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ],
        ],
        'domain_model' => [
            'exclude' => true,
            'label' => 'LLL:EXT:datatables/Resources/Private/Language/locallang_db.xlf:tx_datatables_domain_model_datatable.domain_model',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'columnsettings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:datatables/Resources/Private/Language/locallang_db.xlf:tx_datatables_domain_model_datatable.columnsettings',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_datatables_domain_model_columnsetting',
                'foreign_field' => 'datatable',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => 0,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],

        ],
    
    ],
];
