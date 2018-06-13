<?php
namespace CGB\Datatables\Service;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Christoph Balogh <cb@lustige-informatik.at>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Various helper routines
 *
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU protected License, version 2
 */
class DataService implements \TYPO3\CMS\Core\SingletonInterface {

    const EDIT_BUTTON = 'EDIT BUTTON';
    const SHOW_BUTTON = 'SHOW BUTTON';
    const DELETE_BUTTON = 'DELETE BUTTON';
    const CHECKBOX = 'CHECKBOX';
    const CUSTOM = 'CUSTOM';

    const DATA_PRINT = 1;
    const DATA_EXPORT = 2;
    
    /**
     *
     * @var \TYPO3\CMS\Extbase\Service\ImageService 
     * @inject
     */
    protected $imageService;
    
    /**
     *
     * @var array 
     */
    protected $settings = null;
    
    /**
     *
     * @var array 
     */
    protected $requestParams = null;

    /**
     *
     * @var mixed 
     */
    protected $currentObject;
    
    /**
     *
     * @var int 
     */
    protected $virtualPropertyListCounter = 0;
    
    /**
     *
     * @var array 
     */
    protected $virtualPropertyList = [];
    
    /**
     *
     * @var int 
     */
    protected $childReferenceCounter = 0;

    /**
     * @var array $dcValues
     */
    protected $dcValues = [];
    
    /**
     *
     * @var bool $doctrine 
     */
    protected $doctrine;
    
    /**
     *
     * @var bool $queryByStatement 
     */
    protected $queryByStatement = false;

    /**
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser $queryParser 
     */
    protected $queryParser;
    
    /**
     *
     * @var bool $ajaxCall 
     */
    protected $ajaxCall = false;
    
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public $objectManager = null;

    /**
     * 
     */
    public function __construct(){
        $this->doctrine =  (float) TYPO3_branch >= 8;
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        if ($this->doctrine) {
            $this->queryParser = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser::class);
        }
    }
    
    /**
     * 
     * @param type $errorLevel
     * @param type $errorMessage
     * @param type $errorFile
     * @param type $errorLine
     */
    public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine) {
        echo $errorLevel, $errorMessage;
    }


    /**
     * 
     * @param mixed $settings
     */
    public function setSettings($settings) {
        $this->settings = $settings;
    }
    
    /**
     * 
     * @return mixed
     */
    public function getSettings() {
        return $this->settings;
    }
    
    /**
     * 
     * @param array $requestData
     */
    function setRequestParams($requestParams) {
        $this->requestParams = $requestParams;
    }

    /**
     * @param string $param
     * @return array
     */
    function getRequestParams( $param = '' ) {
        if ( $param ) {
            return $this->requestParams[$param];
        } else {
            return $this->requestParams;
        }
    }

    /**
     * 
     * @return bool
     */
    function getAjaxCall() {
        return $this->ajaxCall;
    }

    /**
     * 
     * @param bool $ajaxCall
     */
    function setAjaxCall($ajaxCall) {
        $this->ajaxCall = $ajaxCall;
    }
    
    /**
     * readPluginPid
     * Gets the uid of the page containing the plugin
     * 
     * @param int $cObjUid
     * @return mixed
     */
    public function readPluginPid( $cObjUid ) {
        if ($cObjUid > 0) {
            $pages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('pages','tt_content','uid=' . $cObjUid);
            return $pages[0]['pages'];
        } 
        return 0;
    }

    /**
     * renderHeaderList 
     * generates the header list and returns an array
     */
    public function renderHeaderList () {
        // get feUser and feGroupArray
        if (! empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
            $feUser = intval($GLOBALS['TSFE']->fe_user->user['uid']);
            $feGroupArray = $GLOBALS['TSFE']->fe_user->groupData['uid'];
		}

        // initialize headerList 
        $headerList = $this->settings['datatables']['propertyConfig'];
        $propertyList = [];
        foreach ($headerList as $key => $property) {
            // add plain property name to $propertyList
            $propertyList[] = $property['_property'];

            // set access 
            if ($property['allowedusers']) {
                $headerList[$key]['noaccess'] = ! \TYPO3\CMS\Core\Utility\GeneralUtility::inList($property['allowedusers'], $feUser);
            }

            if ($property['allowedgroups']) {
                $allowedgroupsList = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $property['allowedgroups']);
                $headerList[$key]['noaccess'] = count(array_intersect($feGroupArray, $allowedgroupsList)) === 0;
            }
            
            // set properties of special columns
            switch ($property['_finalproperty']) {
                case 'uid':
                    $headerList[$key]['header'] = '#';
                    break;
                case self::DELETE_BUTTON:
                case self::EDIT_BUTTON:
                case self::SHOW_BUTTON:
                case self::CUSTOM:
                case self::CHECKBOX:
                    $headerList[$key]['header'] = '';
                    $headerList[$key]['dontsort'] = 1;
                    $headerList[$key]['searchColumn'] = '';
                    break;
                default:
                    // set header from property values
                    if ($property['customheader']) {
                        $headerList[$key]['header'] = $property['customheader'];
                    } else {
                        $headerList[$key]['header'] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                            ($headerList[$key]['propertyContext'] ? $headerList[$key]['propertyContext'] : $this->settings['prefix']) . 
                                '.' . \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($property['_finalproperty']), 
                            ($headerList[$key]['propertyExtKey'] ? $headerList[$key]['propertyExtKey'] : $this->settings['extKey'])
                        );
                        if ($headerList[$key]['header'] == '') {
                            $headerList[$key]['header'] = 
                                ($headerList[$key]['propertyContext'] ? $headerList[$key]['propertyContext'] : $this->settings['prefix']) . 
                                    '.' . \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($property['_finalproperty']) . 
                                    '#' . 
                                    ($headerList[$key]['propertyExtKey'] ? $headerList[$key]['propertyExtKey'] : $this->settings['extKey']);
                        }
                    }
            }
            
            if ((int) $property['searchColumn'] == 2) {
                $elements = null;
                if ($property['_finalproperty'] && $property['propertyContext'] && ! $property['customSearchOptions']) {
                    $elements = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($property['_finalproperty'],$property['propertyContext'],'DELETED=0','','1#');
                }
                $optionArray = [];
                if (is_array($elements) && ! $property['customSearchOptions'] ) {
                    foreach ($elements as $index => $element) {
                        $optionArray[] = $element[$property['_finalproperty']] . ':' . $element[$property['_finalproperty']];
                    }
                    $headerList[$key]['searchoptions'] = implode(',', $optionArray);
                } elseif ( $property['customSearchOptions'] ) {
                    $headerList[$key]['searchoptions'] = $property['customSearchOptions'];
                }
                // ['searchColumn'] = 1;
            }
            
        }

        $formatList['dateformat'] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_datatables.date', 'datatables');
        $formatList['datetimeformat'] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_datatables.datetime', 'datatables');
        $formatList['timeformat'] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_datatables.time', 'datatables');
        return [
            'properties' => $propertyList,
            'headers' => $headerList,
            'formats' => $formatList,
        ];
    }

    /**
     * 
     * @param array $outputConfig
     * @param string $name
     * @param object $controllerContext
     * @param int $mode
     * @param string $data
     * @return type
     */
    public function renderTableData ( $outputConfig, $name = 'datatables', $controllerContext = null, $mode = 0, $data = '') {
        if ($this->settings['datatables']['repositoryName'] == 'Repository') {
            $result = new \stdClass();
            $result->recordsTotal = 0;
            $result->recordsFiltered = 0;
            $result->data = [];;
            return json_encode($result);
        }
        
        $this->controllerContext = $controllerContext;
        $domainRepository = $this->objectManager->get($this->settings['datatables']['repositoryName']);
        $domainObject = $this->objectManager->get($this->settings['datatables']['domainObject']);
        $gettableProperties = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettablePropertyNames($domainObject);
        
        if (method_exists ($domainRepository, 'countAll')) {
            $totalCount = $domainRepository->countAll();
        }
        
        // print_r($this->requestParams);
        
        $totalFilteredCount = $this->findDatatable( $domainRepository, $outputConfig, true, 0, $data );
        $resultObjects = $this->findDatatable( $domainRepository, $outputConfig, false, $mode, $data, $sql );

        $elementsView = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
        $templatePathAndFilename = PATH_site . 'typo3conf/ext/datatables/Resources/Private/Templates/Datatable/Elements.html';
        $elementsView->setTemplatePathAndFilename($templatePathAndFilename);
        
        $objectList = [];
        $indexedHeaderList = [];
        $firstHeader = null;
        foreach ($outputConfig['headers'] as $key => $oneHeader) {
            $indexedHeaderList[] = $oneHeader;
            if (is_null($firstHeader)) {
                $firstHeader = $key;
            }
        }
        foreach ($resultObjects as $resultObject) {
            $resultObjectProperties = [];
            if (is_array($resultObject)) {
                // TODO: adjust for proper rendering of dataMatrix
                $this->currentObject = $resultObject;
                $resultPropertyIndex = 0;
                foreach ($resultObject as $key => $value) {
                    switch ($indexedHeaderList[$resultPropertyIndex]['_type']) {
                        case 'DateTime':
                            $date = new \DateTime;
                            $date->setTimestamp($value);
                            $value = $date->format($outputConfig['formats']['dateformat']);
                            break;
                        default:
                            break;
                    }
                    $resultObjectProperties[] = $value;
                    $resultPropertyIndex++;
                }
                
                if ($firstHeader == 'uid') {
                    if (isset($objectList[$resultObjectProperties[0]])) {
                        $recordUid = $resultObjectProperties[0];
                        foreach($resultObjectProperties as $resultPropertyIndex => $resultPropertyValue) {
                            // anything stored in property
                            if ($objectList[$recordUid]) {
                                if (is_array($objectList[$recordUid][$resultPropertyIndex])) {
                                    // subproperty is already an array
                                    // append value if not already stored
                                    if (array_search($resultPropertyValue, $objectList[$recordUid][$resultPropertyIndex]) === false) {
                                        $objectList[$recordUid][$resultPropertyIndex][] = $resultPropertyValue;
                                    }
                                } else {
                                    // not yet an array. make array if vcalues are different and append
                                    if ($objectList[$recordUid][$resultPropertyIndex] !== $resultPropertyValue) {
                                        $objectList[$recordUid][$resultPropertyIndex] = [
                                            $objectList[$recordUid][$resultPropertyIndex],
                                            $resultPropertyValue
                                        ];
                                    }
                                }
                            } else {
                                $objectList[$recordUid] = $resultPropertyValue;
                            }
                        }
                        // $objectList[$resultObjectProperties[0]] = $resultObjectProperties;
                    } else {
                        $objectList[$resultObjectProperties[0]] = $resultObjectProperties;
                    }
                } else {
                    $objectList[] = $resultObjectProperties;
                }
            } else {
                $resultObjectProperties['DT_RowId'] = $name . '_' . $resultObject->getUid();
                $resultObjectProperties['DT_RowClass'] = "dt-mainrow";

                // remeber current object;
                $this->currentObject = $resultObject;
                foreach ($outputConfig['headers'] as $property => $header) {
                    
                    if ($header['noaccess']) {
                        continue;
                    }
                    $settings = $this->settings;
                    $objectReference = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($this->settings['name']);
                    $parameter = [ $objectReference => $resultObject->getUid()];

                    switch ($property) {
                        case self::CUSTOM:
                            if ( ($property == self::CUSTOM) and (count($callPathArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $settings['datatables']['action']['editAction'])) == 7)) {
                                $settings['datatables']['action']['pageUid'] = $callPathArray[0];
                                $settings['extKey'] = $callPathArray[1];
                                $settings['datatables']['action']['ajaxPlugin'] = $callPathArray[2];
                                $settings['datatables']['domainModelName'] = $callPathArray[3];
                                $settings['datatables']['action']['editAction'] = $callPathArray[4];
                                $parameter = [$callPathArray[5] => 
                                    \CGB\Relax5core\Service\ObjectAccessService::getObjectProperty(['object' => $resultObject], $callPathArray[6])
                                ];
                            }
                        case self::EDIT_BUTTON:
                            if ( ($property == self::EDIT_BUTTON) and (count($callPathArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $settings['datatables']['action']['editAction'])) == 7)) {
                                $settings['datatables']['action']['pageUid'] = $callPathArray[0];
                                $settings['extKey'] = $callPathArray[1];
                                $settings['datatables']['action']['ajaxPlugin'] = $callPathArray[2];
                                $settings['datatables']['domainModelName'] = $callPathArray[3];
                                $settings['datatables']['action']['editAction'] = $callPathArray[4];
                                $parameter = [$callPathArray[5] => 
                                    \CGB\Relax5core\Service\ObjectAccessService::getObjectProperty(['object' => $resultObject], $callPathArray[6])
                                ];
                            }
                        case self::SHOW_BUTTON:
                            if ( ($property == self::SHOW_BUTTON) and (count($callPathArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $settings['datatables']['action']['showAction'])) == 7)) {
                                $settings['datatables']['action']['pageUid'] = $callPathArray[0];
                                $settings['extKey'] = $callPathArray[1];
                                $settings['datatables']['action']['ajaxPlugin'] = $callPathArray[2];
                                $settings['datatables']['domainModelName'] = $callPathArray[3];
                                $settings['datatables']['action']['showAction'] = $callPathArray[4];
                                $parameter = [$callPathArray[5] => 
                                    \CGB\Relax5core\Service\ObjectAccessService::getObjectProperty(['object' => $resultObject], $callPathArray[6])
                                ];
                            }
                        case self::DELETE_BUTTON:
                        case self::CHECKBOX:
                            $elementsView->assignMultiple(array(
                                    'property' => $property,
                                    'cobj' => $this->settings['cobj'],
                                    'uid' => $resultObject->getUid(),
                                    'settings' => $settings,
                                    'header' => $header,
                                    'parameter' => $parameter,
                                )
                            );
                            $elements = $elementsView->render();
                            $resultObjectProperties[] = $elements;
                            break;
                        default:
                            $dataMatrix = self::dataMatrix( $resultObject, $property, $header );

                            if (self::array_depth($dataMatrix) == 1) {
                                if ($mode == 0) {
                                    $resultObjectProperties[] = '<span reference="' . $name . '_' . $dataMatrix['_uid'] . '_' . $dataMatrix['_property'] . '">' . $dataMatrix['_value'] . '</span>';
                                } else {
                                    $resultObjectProperties[] = $dataMatrix['_value'];
                                }
                            } else {
                                $htmlAttributes = [
                                    'table' => ['class' => 'tx_dt-innertable'],
                                    'tr' => ['class' => 'tx_dt-innertable_tr'],
                                    'td' => ['class' => 'tx_dt-innertable_td']
                                ];
                                $data = self::dataMatrixToTable($dataMatrix, $htmlAttributes);
                                $resultObjectProperties[] = $data;
                            }
                        
                    }
                }
                $objectList[] = $resultObjectProperties;
            }
        }
        
        $result = new \stdClass();
        $result->recordsTotal = $totalCount;
        $result->recordsFiltered = $totalFilteredCount;
        $result->data = $objectList;
        $result->sql = $sql;
        switch ($mode) {
            case self::DATA_PRINT: 
                return $result;
            case self::DATA_EXPORT: 
                return $result;
            default: 
                return json_encode($result);
        }
        
    }
 
    /**
     * 
     * @param type $repository
     * @param type $outputConfig
     * @param type $count
     * @param int $mode
     * @param string $data
     * @param string $sql
     * @return type
     */
    public function findDatatable( $repository, $outputConfig, $count = false, $mode = 0, $data = '', &$sql = '' ) {
        // check if the repüository supports queries
        if (! method_exists($repository, 'createQuery')) {
            return [];
        } 
        $this->virtualPropertyListCounter = 0;
        $this->virtualPropertyList = [];
        $this->dcValues = [];

        $repositoryClassName = get_class($repository);
        $dataMapper = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper');        
        $propertyMapper = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper');        
        $dataMap = $dataMapper->getDataMap($this->settings['datatables']['domainObject']);
        $tableName = $dataMap->getTableName();
        
        $properties = $outputConfig['properties'];
		$query = $repository->createQuery();

        // print_r($properties);
        // print_r($this->requestParams);
        $pages = $this->readPluginPid($this->settings['cobj']);
        if ($pages) {
            $query->getQuerySettings()->setStoragePageIds(explode(',', $pages));
        }
        // $query->getQuerySettings()->setRespectStoragePage(false);

        // set general filter
        if ($this->getRequestParams('dtfilter')) {
            $filterExplode = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $this->getRequestParams('dtfilter'));
            $generalConstraint = $query->equals($filterExplode[0], $filterExplode[1]);
            $this->dcValues[] = $filterExplode[1];
        }
        
        if ($this->settings['datatables']['action']['globalFilter']) {
            $conditionList = [];
            $globalFilterString = $this->settings['datatables']['action']['globalFilter'];
            if (substr($globalFilterString,0,5) == 'PARAM') {
                $overrideStatement = $data;
                $globalFilterString = trim(substr($globalFilterString, 6));
            }
            
            $globalFilterString = str_replace('FEUSERID', $GLOBALS['TSFE']->fe_user->user['uid'], $globalFilterString);
            // $globalFilterString = str_replace('DATA', $data, $globalFilterString);
            $globalFilterString = str_replace('NOW', time(), $globalFilterString);
            
            $feuserArray = $GLOBALS['TSFE']->fe_user->user;
            
            $conjList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('&', $globalFilterString);

            $additionalRepository = $this->objectManager->get('CGB\\Relax5core\\Domain\\Repository\\OwnerRepository');
            if ($additionalRepository) {
                $feuserObject = $additionalRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
            }
                    // $this->objectManager->get($this->settings['datatables']['additionalRepository']);

            foreach($conjList as $conjCondition) {
                // echo $conjCondition;
                if (preg_match('/^([\.\w]*)\s*(==|<=|>=|<|>|!=|IN|CONTAINS|LIKE|ANY)\s*([\,\.\w]*)$/', $conjCondition, $matches)) {
                    // print_r($matches);
                    
                    $matches[3] = str_replace('DATA', $data, $matches[3]);
                    
                    if (is_string($matches[3])) {
                        // $matches[3] = "'" . $matches[3] . "'";
                    }

                    $inStructure = null;
                    if (substr($matches[3],0,12) == 'feuserobject') {
                        $matches[3] = substr($matches[3],13);
                        $inStructure = $feuserObject->_getProperty($matches[3]);
                    }

                    if (substr($matches[3],0,6) == 'feuser') {
                        $matches[3] = $feuserArray[substr($matches[3],7)];
                    }
                    
                    if ($matches[1] == 'OWNERORGROUP') {
                        $conditionList[] = $query->logicalOr(
                            $query->equals('owner', $GLOBALS['TSFE']->fe_user->user['uid']),
                            $query->logicalAnd(    
                                $query->equals('usergroup', $matches[3]),
                                $query->equals('owner', 0)
                            )
                        );
                        $matches[2] = '';
                    }
                    
                    // TODO: ATtention might break other rules if not rawStatement is used!
                    $this->dcValues[] = $matches[3];
                    
                    switch($matches[2]) {
                        case 'IN':
                            if (is_null($inStructure)) {
                                $list = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $matches[3]);
                                $conditionList[] = $query->in($matches[1], $list);
                            } elseif ($inStructure->count() > 0 ) {
                                $conditionList[] = $query->in($matches[1], $inStructure);
                            }
                            break;
                        case 'CONTAINS':
                            $conditionList[] = $query->contains($matches[1], $matches[3]);
                            break;
                        case '==':
                            $conditionList[] = $query->equals($matches[1], $matches[3]);
                            break;
                        case '<=':
                            $conditionList[] = $query->lessThanOrEqual($matches[1], $matches[3]);
                            break;
                        case '>=':
                            $conditionList[] = $query->greaterThanOrEqual($matches[1], $matches[3]);
                            break;
                        case '<':
                            $conditionList[] = $query->lessThan($matches[1], $matches[3]);
                            break;
                        case '>':
                            $conditionList[] = $query->greaterThan($matches[1], $matches[3]);
                            break;
                        case '!=':
                            $conditionList[] = $query->logicalNot($query->equals($matches[1], $matches[3]));
                            break;
                        case 'LIKE':
                            $conditionList[] = $query->like($matches[1], $matches[3]);
                            break;
                        case 'ANY':
                            $anyCondition = 
                                $query->logicalOr([
                                    $query->greaterThan($matches[1], 0),
                                    $query->equals($matches[1], null)
                                ]);
                            $conditionList[] = $anyCondition;
                            break;

                    }
                }
            }

            // TODO: There must be a reason for this, but I dont know what it was :-(
            if (($_GET['tx_datatables_datatable']['data']) && ! $overrideStatement) {
                // $overrideStatement = $_GET['tx_datatables_datatable']['data'];
            }
            
            // $filterExplode = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $this->settings['datatables']['action']['globalFilter']);
            
            // $feUser = intval($GLOBALS['TSFE']->fe_user->user['uid']);
            // if ($filterExplode[1] == 'FEUSERID') {
            //    $filterExplode[1] = $feUser;
            // }
            
            if (count($conditionList)>0) {
                // print_r($generalConstraint);
                if ($generalConstraint) {
                    $conditionList[] = $generalConstraint;
                }
                $generalConstraint = $query->logicalAnd($conditionList);
                
                // print_r($generalConstraint);
            }
            
        }
        
        $globalSearch = $this->getRequestParams('search');
        if ($globalSearch['value']) {
            $globalSearchValue = str_replace('?', '_', str_replace('*', '%', $globalSearch['value']));

            foreach( $outputConfig['headers'] as $property => $propertyConfig) {
                if (! is_null($propertyDefinition['virtualProperty']) && ($propertyDefinition['virtualProperty'] !== '')) {
                    continue;
                }
                
                if ($propertyConfig['noaccess']) {
                    continue;
                }
                
                if ((strpos($property, '.') !== false) && ($this->settings['datatables']['action']['limitQueriesToMainRepository'])) {
                    $property = false;
                }
                
                if ($property && ($property != self::SHOW_BUTTON) && ($property != self::EDIT_BUTTON) && ($property != self::DELETE_BUTTON) && ($property != self::CHECKBOX)) {
                    $globalSearchConstraints[] = $query->like($property, $globalSearchValue . '%');
                }
            }
        }
        
        
        $columns = $this->getRequestParams('columns');

        if ($mode > 0) {
            if (is_array($columns)) {
                foreach ($columns as $key => $column) {
                    if ($column['search']['value'] == '') {
                        switch ($column['name']) {
                            case self::EDIT_BUTTON:
                            case self::SHOW_BUTTON:
                            case self::DELETE_BUTTON:
                            case self::CHECKBOX:
                                break;
                            default:
                                // $columns[$key]['search']['value'] = '%';
                        }
                    }
                }
            }
        }

// print_r($this->getRequestParams('columns'));
        if (is_array($columns)) {
            foreach($columns as $column) {
                if(strpos($outputConfig['headers'][$column['name']]['property'],'.') !== false) {
                    $this->childReferenceCounter++;
                    // $this->queryByStatement = true;
                }
                // ((strpos($property, '.') !== false) && ($this->settings['datatables']['action']['limitQueriesToMainRepository'])) {
                if ($column['search']['value'] !== '') {
                    $columnSearchValue = str_replace('?', '_', str_replace('*', '%', $column['search']['value']) );
                    $propertyDefinition = $outputConfig['headers'][$column['name']];

                    if ($propertyDefinition['_type'] === 'DateTime') {
                        $minDate = \CGB\Datatables\UserFunc\DateConverter::convert($columnSearchValue, '', \CGB\Datatables\UserFunc\DateConverter::LOWER_LIMIT);
                        $maxDate = \CGB\Datatables\UserFunc\DateConverter::convert($columnSearchValue, '', \CGB\Datatables\UserFunc\DateConverter::UPPER_LIMIT);

                        if (! is_null($minDate) && (!is_null($maxDate))) {
                            // echo " L: $minDate U:$maxDate ";
                            $this->dcValues[] = $minDate;
                            $this->dcValues[] = $maxDate;
                            $columnSearchConstraints[] = $query->logicalAnd(
                                [
                                    $query->greaterThanOrEqual($column['name'], $minDate),
                                    $query->lessThanOrEqual($column['name'], $maxDate) 
                                ]
                            );
                        }
                    } else {
                        if (! is_null($propertyDefinition['virtualProperty']) && ($propertyDefinition['virtualProperty'] !== '')) {
                            $whereProperty = 'virtual_property_' . $this->virtualPropertyListCounter;
                            $this->virtualPropertyList[$this->virtualPropertyListCounter++] = $propertyDefinition['virtualProperty'];
                            if (chop($columnSearchValue, '%') != '') {
                                $columnSearchConstraints[] = $query->like($whereProperty, $columnSearchValue . '%');
                                $this->dcValues[] = $columnSearchValue . '%';
                                $this->queryByStatement = true;
                            }
                        } else {
                            if (chop($columnSearchValue, '%') != '') {
                                $columnSearchConstraints[] = $query->like($propertyDefinition['_property'], $columnSearchValue . '%');
                                $this->dcValues[] = $columnSearchValue . '%';
                            }
                        }
                    }
                }
            }
        }

        if ($columnSearchConstraints && $generalConstraint) {
            $query->matching(
                $query->logicalAnd([
                    $generalConstraint,
                    $query->logicalAnd($columnSearchConstraints),
                ])
            );
        } elseif ($columnSearchConstraints) {
            $query->matching(
                $query->logicalAnd($columnSearchConstraints)
            );
        } elseif ($globalSearchConstraints && $generalConstraint) {
            $query->matching(
                $query->logicalAnd([
                    $generalConstraint,
                    $query->logicalOr($globalSearchConstraints),
                ])
            );
        } elseif ($globalSearchConstraints) {
            $query->matching(
                $query->logicalOr($globalSearchConstraints)
            );
        } elseif ($generalConstraint) {
            $query->matching($generalConstraint);
        }

        // generate order from request string
        if (! $count) {
            $order = [];
            if (is_array($this->getRequestParams('order'))) {
                foreach ($this->getRequestParams('order') as $columnOrder) {
                    $orderProperty = $outputConfig['properties'][$columnOrder['column']];
                    $orderExpression = $outputConfig['headers'][$orderProperty]['virtualProperty'];
            $fp = fopen('tx_datatables.log', 'a+');
            fwrite($fp, "=== (1) order expression ===\n\n");
            fwrite($fp, $orderExpression . "\n\n");
            fclose($fp);

                    if ($orderExpression) {
                        // sort by virtual property
                        $this->virtualPropertyList[$this->virtualPropertyListCounter] = $orderExpression;
                        $orderProperty = 'virtual_property_' . $this->virtualPropertyListCounter++;
                        
                        $order[$orderProperty] = strtoupper($columnOrder['dir']);
                        $this->queryByStatement = true;
                    } else {
                        // real property
                        $order[$orderProperty] = strtoupper($columnOrder['dir']);
                    }
                }
            }
        }
// echo $this->queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL();        
        if ( ! $count && is_array($order) ) {
            // print_r($order);
            $query->setOrderings($order);
        }
// echo $this->queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL();       
        if (! $count and ($mode == 2) and 0) {
            $DQL = $this->queryParser->convertQueryToDoctrineQueryBuilder($query);
            print_r($DQL->getParameters());
            echo get_class($DQL);
            $hilfe = $this->queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL();
            echo $hilfe;
            print_r($this->dcValues);
            echo $this->queryByStatement;
        }

        if ( $this->doctrine && $this->settings['datatables']['action']['debug']) {
            $fp = fopen('tx_datatables.log', 'a+');
            fwrite($fp, "=== (2) query ===\n\n");
            fwrite($fp, $this->queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL() . "\n\n");
            fwrite($fp, print_r($this->dcValues, true) . "\n\n");
            fclose($fp);
        }
   
        if ($this->settings['datatables']['action']['addJoin']) {
            $addJoin = $this->settings['datatables']['action']['addJoin'];
        }
        
        if ( $this->doctrine && ($this->virtualPropertyListCounter || $this->childReferenceCounter || $overrideStatement || $addJoin)) {
            // replace virtual order properties in statement and call query via statement()
            if ($rawSqlStatement == '') {
                $rawSqlStatement = $this->queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL();
            } 
            preg_match_all('/(`[a-zA-Z0-9_\-]*`\.`virtual_property_[0-9]*`)/', $rawSqlStatement, $matches);
            $vCounter = 0;
            foreach ($matches[1] as $match) {
                $rawSqlStatement = str_replace($match, $this->virtualPropertyList[$vCounter++], $rawSqlStatement);
                $this->queryByStatement = true;
            }
            
            // $rawSqlStatement = str_replace('LEFT JOIN', $addJoin . ' LEFT JOIN', $rawSqlStatement, 1);
            $rawSqlStatement = preg_replace('/LEFT JOIN/', $addJoin . ' LEFT JOIN', $rawSqlStatement, 1); 
        }

        if ( $this->doctrine && ( ( $this->virtualPropertyListCounter > 0) || ($this->childReferenceCounter > 0) || $overrideStatement)) {
            preg_match_all('/(`[a-zA-Z0-9_\-]*`\.`virtual_property_[0-9]*`)/', $rawSqlStatement, $matches);    
            $vCounter = 0;
            foreach ($matches[1] as $match) {
                $rawSqlStatement = str_replace($match, $this->virtualPropertyList[$vCounter++], $rawSqlStatement);
                $this->queryByStatement = true;
            }

            $index = 0;
            $dcValues = $this->dcValues;
            
            $DQL = $this->queryParser->convertQueryToDoctrineQueryBuilder($query);
            $dcValues = $DQL->getParameters();
            
            $rawSqlStatement = preg_replace_callback('/(\:dcValue[0-9]*)/', function($w) use ($dcValues) {
                // $index = str_replace(':dcValue', '', $w[1]) - 1; 
                // return "'{$dcValues[$index]}'";
                $key = substr($w[1], 1);
                return "'{$dcValues[$key]}'";
            }, $rawSqlStatement);
            $this->queryByStatement = true;
        } else {
            $rawSqlStatement = preg_replace('/(\:dcValue[0-9]*)/', "'$globalSearchValue%'", $rawSqlStatement);
        }

        if ( $this->doctrine && ( $mode > 0 ) ) {
            if ($rawSqlStatement == '') {
                $rawSqlStatement = $this->queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL();
            }

            preg_match('/SELECT\s(`[a-zA-Z0-9_\-]*`\.\*).*/', $rawSqlStatement, $matches);    
            
            preg_match('/^`([a-zA-Z0-9_\-]*)`.*/', $matches[1], $subMatches);
            $mainTablePrefix = $subMatches[1];
            $fieldlist = [];
            $columnCounter = 0;
            foreach($outputConfig['headers'] as $property => $config) {
                // print_r($config);
                // get real tablename
                $tableName = $config['_table'] ? $config['_table'] : $config['propertyContext'];
                $tableName = $tableName ? $tableName : $mainTablePrefix;
                if ($config['virtualProperty']) {
                    $fieldlist[] = $config['virtualProperty'];
                } else {
                    $fieldlist[] = '`' . $tableName . '`.`' . \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($config['_finalproperty']) . '` AS `col_' . $columnCounter++ . '`';
                }
            } 
          
            $rawSqlStatement = str_replace($matches[1], implode(',', $fieldlist), $rawSqlStatement);
            
            // TODO: find mappings for persistence settings
            // probably change everything at header generation to property and table mapper
            // $rawSqlStatement = str_replace('tx_relax5core_domain_model_owner', 'fe_users', $rawSqlStatement);
            // echo $rawSqlStatement;
            $this->queryByStatement = true;
            
            $index = 0;
            $dcValues = $this->dcValues;
            $rawSqlStatement = preg_replace_callback('/(\:dcValue[0-9]*)/', function($w) use ($dcValues) {
                $index = str_replace(':dcValue', '', $w[1]) - 1; 
                return "'{$dcValues[$index]}'";
            }, $rawSqlStatement);
            
        }

        if ($overrideStatement) {
            // echo $overrideStatement;
            $rawSqlStatement = str_replace('WHERE ', 'WHERE ' . $overrideStatement . ' AND ', $rawSqlStatement);
            // $rawSqlStatement;
            // $rawSqlStatement = $overrideStatement;
        }
    
  // echo $rawSqlStatement;
        if ($count) {
            // return $this->queryByStatement;
            if ( ! $this->queryByStatement ) {
                $result = $query->count();
                return $result;
            } else {
                if (strpos($rawSqlStatement, 'ORDER BY') === false) {
                    // $rawSqlStatement .= ' GROUP BY 1';
                    $rawSqlStatement = str_replace('SELECT `', 'SELECT COUNT(DISTINCT `', $rawSqlStatement);
                    $rawSqlStatement = str_replace('`.* FROM', '`.`uid`) AS count FROM', $rawSqlStatement);
                } else { 
                    $rawSqlStatement = str_replace('SELECT `', 'SELECT COUNT(DISTINCT `', $rawSqlStatement);
                    $rawSqlStatement = str_replace('`.* FROM', '`.`uid`) AS count FROM', $rawSqlStatement);
                    // $rawSqlStatement = str_replace('ORDER BY', 'GROUP BY 1 ORDER BY', $rawSqlStatement);
                }
                // echo $rawSqlStatement;
                $query->statement($rawSqlStatement);
                
                $result = $query->execute(true);
                return $result[0]['count'];
                
                // return count($query->execute(true));
            }
        } else { 
            $limit = (int) $this->getRequestParams('iDisplayLength');
            $offset = (int) $this->getRequestParams('iDisplayStart');

            if ( $this->queryByStatement ) {
                // dont set limit if mode is different from 0 (print or export)
                $sql = $rawSqlStatement;
                if ($mode == 0) {
                    if (strpos($rawSqlStatement, 'ORDER BY') === false) {
                        $rawSqlStatement .= ' GROUP BY 1';
                    } else {
                        $rawSqlStatement = str_replace('ORDER BY', 'GROUP BY 1 ORDER BY', $rawSqlStatement);
                    }
                }
                if ( ( $mode == 0 ) && ( $limit >= 1) ) {
                    $rawSqlStatement .= " LIMIT $limit OFFSET $offset";
                }
                
                if ($mode > 0) {
                     // echo $rawSqlStatement;
                }
                
                $fp = fopen('tx_datatables.log', 'a+');
                fwrite($fp, "=== (3) raw query ===\n\n");
                fwrite($fp, $rawSqlStatement . "\n\n");
                fclose($fp);
                
                $query->statement($rawSqlStatement);
                // not yet
                // echo $rawSqlStatement;
                $result = $query->execute( $mode > 0 );
                // $result = $query->execute();
                return $result;
            } else {
                // dont set limit if mode is different from 0 (print or export)
                if ( ( $mode == 0 ) && ( $limit >= 1) ) {
                    $query->setLimit( $limit );
                    $query->setOffset( $offset );
                }
                // not yet
                $result = $query->execute( $mode > 0 );
                // $result = $query->execute();
            }
            return $result;
        }
    }

    /**
     * 
     * @param object $object
     * @param string $propertyPath
     * @return array
     */
    public function dataMatrix ( $object, $propertyPath, $header ) {
        $singleOnly = ( $header['showfirstchildonly'] == 1);
        $retValue = [];
        $fetchSubAlignArray = false;
        $propertyPathArray = explode('.', $propertyPath);
        // store first property name in $currentProperty
        $currentProperty = array_shift($propertyPathArray);
        
        if ($object instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
            // $object is still a collection of elements, so render either only one or all by 
            // recursevely calling dataMatrix
            if ($singleOnly) {
                $retValue[] = self::dataMatrix($object->current(), $propertyPath, $header);
            } else {
                foreach ($object as $element) {
                    $retValue[] = self::dataMatrix($element, $propertyPath, $header);
                }
            }
        } elseif (gettype($object) == 'object') {
            // $object is of type object
            if (isset($header['_propertylist'])) {
                $subHeader = $header;
                unset($subHeader['_propertylist']);
                
                $storageObject = $object->_getProperty($propertyPath);
                
                foreach($storageObject as $singleObject) {
                    $childRetValue = [];
                    foreach ($header['_propertylist'] as $childIndex => $childProperty) {
                        $childObject = $object->_getProperty($childProperty);
                        $childRetValue[] = self::dataMatrix($singleObject, $childProperty, $subHeader );
                    }
                    $retValue[] = ['_subTable' => $childRetValue];
                    
                    if ($singleOnly) {
                        break;
                    }
                }
                return $retValue;
            } else {
                $_uid = $object->getUid();
                $getter = 'get' . ucfirst($currentProperty);
                if (method_exists($object, $getter)) {
                    $object = $object->{$getter}();
                } else {
                    $object = $object->_getProperty( $currentProperty );
                }
            }
        }
        
        if (count($propertyPathArray) > 0) {
            // more elements, descend into child elements
            $retValue[] = self::dataMatrix($object, implode('.', $propertyPathArray), $header );
        } else {
            // last element, no more descending
            if (! $object instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
                $retValue['_value'] = self::convertToString($object, $header['formats'], $header);
                $retValue['_uid'] = $_uid;
                $retValue['_property'] = $currentProperty;
                $retValue['_align'] = $header[_alignlist];
            }
            return $retValue;
        }
        return $retValue;
    }
 
    /**
     * 
     * @param array $datatMatrix
     * @param array $htmlAttributes
     * @return type
     */
    public function dataMatrixToTable(array $dataMatrix, array $htmlAttributes, $subTable = false, $dontwrap = false) {
        $subAsTable = false;
        
        if (isset($dataMatrix['_value'])) {
            if ($dataMatrix['_value']) {
                if ( ! is_array($dataMatrix['_align'])) {
                    $addClass = "dt-{$dataMatrix['_align']}";
                }
                if ($subAsTable) {
                    return "<td class=\"dt-main $addClass\">" . $dataMatrix['_value'] . '</td>';
                } else {
                    return "{$dataMatrix['_value']} ";
                }
            } else {
                return '';
            }
            
        } elseif (is_array($dataMatrix) && (count($dataMatrix) == 1)) {
            // print_r($dataMatrix); 
            if (isset($dataMatrix['_subTable'])) {
                $innerValue = self::dataMatrixToTable($dataMatrix['_subTable'], $htmlAttributes, true);
                if ( $dontwrap ) {
                    return "$innerValue";
                } else {
                    if ($subAsTable) {
                        $table = '<table ';
                        foreach ($htmlAttributes['table'] as $attribute => $value) {
                            $table .= $attribute . "=\"$value\" ";
                        }
                        $table .= '>';
                        return $table . "<tr>$innerValue</tr></table>";
                    } else {
                        $innerHtml = '<div class="dt-sub">';
                        return $innerHtml . "$innerValue</div>";
                    }
                }
                
            } else {
                return self::dataMatrixToTable($dataMatrix[0], $htmlAttributes);
            }
           
        } elseif (is_array($dataMatrix) && ! $subTable) {
            if ($subAsTable) {
                $table = '<table ';
                foreach ($htmlAttributes['table'] as $attribute => $value) {
                    $table .= $attribute . "=\"$value\" ";
                }
                $table .= '>';
                foreach($dataMatrix as $index => $subMatrix) {
                    $table .= '<tr>' . self::dataMatrixToTable($subMatrix, $htmlAttributes, false, true) . '</tr>';
                }
                $table .= '</table>';
                return $table;
            } else {
                $innerHtml = '<div class="dt-sub">';
                foreach($dataMatrix as $index => $subMatrix) {
                    $data = self::dataMatrixToTable($subMatrix, $htmlAttributes, false, true);
                    if ($data != '') {
                        $innerHtml .= "$data<br />";
                    }
                }
                if (substr($innerHtml, -6, 6) == '<br />') { 
                    $innerHtml = substr($innerHtml, 0, strlen($innerHtml) - 6);
                }
                return "$innerHtml</div>";
            }
            
        } elseif (is_array($dataMatrix) && $subTable) {
            foreach($dataMatrix as $index => $subMatrix) {
                if ($subMatrix['_align']) {
                    $subMatrix['_align'] = $subMatrix['_align'][$index];
                }
                $table .= self::dataMatrixToTable($subMatrix, $htmlAttributes, true);
            }
            return $table;
        }
    }
    
    /**
     * 
     * @param array $array
     * @return int
     */
    public function array_depth(array $array) {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::array_depth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }
        return $max_depth;
    }    
    
    /**
     * 
     * @param mixed $obj
     * @param array $h
     * @return type
     */
    public function convertToString ( $obj, $h, $p ) {
        
        if ((gettype($obj) == 'object') && (get_class($obj) == 'DateTime')) {
            // format DateTime according to formatcode string (translation key)
            if ($p['formatcode']) {
                $formatcode = $p['formatcode'] ? $p['formatcode'] : 'tx_datatables.date';
            }
            $html = $obj->format(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($formatcode, 'datatables'));
            
        } elseif ( (gettype($obj) == 'integer') && ($p['formatcode'] == 'tx_datatables.time') ) {
            $html = $obj > 0 ? gmdate(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($p['formatcode'], 'datatables'), $obj) : '';
            
        } elseif ( ( (gettype($obj) == 'boolean') || (gettype($obj) == 'integer') ) && ($p['images']) ) {
            $imageList = explode(',', $p['images']);
            $plainvalue = $obj;
            // $imageList = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $p['images']);
            if ($imageList[$obj]) {
                if (is_numeric($imageList[$obj])) {
                    $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
                    $file = $resourceFactory->getFileObject($imageList[$obj]);
                    $filename = "fileadmin" . $file->getIdentifier();
                } else {
                    $filename = $imageList[$obj];
                }
                // $fileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
                // $imageRef = $fileRepository->findByUid($imageList[$obj-1]);
                // $fileRef = $imageRef->findFileReferenceByUid($imageList[$obj-1]);
                
                
                // $image = $this->imageService->getImage($fileRef, null, true);
                // $imageUri = $this->imageService->getImageUri($image, false);
                $html = "<image src=\"{$filename}\" alt=\"\" />";
            } else {
                $html = (string) $obj;
            }
            
        } elseif ((gettype($obj) == 'object') && (get_class($obj) == 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FileReference')){
            $image = $this->imageService->getImage($obj->getUid(), null, true);
            $imageUri = $this->imageService->getImageUri($image, false);
            $html = "<image src=\"$imageUri\" width=\"37px;\" alt=\"\" />";
            
        } else {
            $html = (string) $obj;
            
        }
        
        if ($p['formatcode']) {
            $formatlist = explode(',', $p['formatcode']);
            foreach ($formatlist as $format) {
                $formatCodePair = explode(':', $format);
                switch ($formatCodePair[0]) {
                    case 'crop':
                        if (strlen($html) > $formatCodePair[1]) {
                            $html = substr($html, 0, $formatCodePair[1]) . ' &#133;';
                        }
                }
            }
        }
        
        if ($p['mapping'] && $html) {
            $html = str_replace('{value}', $html, $p['mapping']);
            $html = str_replace('{plainvalue}', $plainvalue, $html);
            
            $pattern = '/{([a-zA-Z0-9_\-\.]*)}/';
            $html = preg_replace_callback($pattern, function($w) {
                return \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($this->currentObject, $w[1] ); 
            }, $html);
            
            // $html = sprintf($p['mapping'], $html);
        }
        
        if ($p['link']) {
            $uriBuilder = $this->controllerContext->getUriBuilder();
            
            $link = $p['link'];
            $link = str_replace('{value}', $html, $link);

            return "<a $link>$html</a>";
            
            $href = $uriBuilder
                ->reset()
                ->setTargetPageUid($p['link'])
                ->buildFrontendUri();    
            return $this->wrapInLink($html, $href, $js . 'window.close();return false;');
        }
        return $html;
        
    }
    
    /**
     * 
     * @param string $html
     * @param string $href
     * @return string
     */
    private function wrapInLink ($html, $href, $onclick) {
        if ($href) {
            return "<a href=\"$href\" onclick=\"$onclick\">$html</a>";
        } else {
            return $html;
        }
    }
    
    
    /**
     * 
     * @param mixed $flat
     * @param mixed $propertyList
     * @return type
     */
    function stack ($flat, $propertyList) {
        $retVal = [];
        foreach ($flat as $key => $entry) {
            if ( ($separatorPos = strpos($key, '.')) === false) {
                $retVal[$key] = $entry;
            } elseif (is_array($propertyList) && (array_search(substr($key, 0, $separatorPos), $propertyList) === false)) {
                $retVal[$key] = $entry;
            } else {
                $newKey = substr($key, 0, $separatorPos);
                $newSubkey = substr($key, $separatorPos + 1);
                $retVal[$newKey]['header'] .= (($retVal[$newKey]['header']) ? ' ' : '') . $entry['header'];
                $retVal[$newKey]['showfirstchildonly'] |= $entry['showfirstchildonly'];
                $retVal[$newKey]['searchColumn'] |= $entry['searchColumn'];
                $retVal[$newKey]['dontsort'] |= 1;
                $retVal[$newKey]['_idlist'][] = $entry['_id'];
                $retVal[$newKey]['_propertylist'][] = $newSubkey;
                $retVal[$newKey]['_alignlist'][] = $entry['align'];
            }
        }
        return $retVal;
    }
    
}


