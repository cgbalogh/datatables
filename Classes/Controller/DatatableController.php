<?php
namespace CGB\Datatables\Controller;

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

use \CGB\Datatables\Classes\UserFunc\FlexFormUserFunc;

/**
 * DatatableController
 */
class DatatableController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * dataService
     *
     * @var \CGB\Datatables\Service\DataService
     * @inject
     */
    protected $dataService = NULL;
    
    /**
     * initializeAction
     */
    protected function initializeAction() {
        
        $this->contentObj = $this->configurationManager->getContentObject();

        // get cObjUid eithzer froma request param or form object 
        if ( $cObjUid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('cobj') ) {
            $this->settings = $this->readPluginFlexFormSettings( $cObjUid );
            $this->settings['cobj'] = $cObjUid;
        } else {
            $this->settings['cobj'] = $this->contentObj->data['uid'];
        }

        $flexFormUserFunc = $this->dataService->objectManager->get(\CGB\Datatables\UserFunc\FlexFormUserFunc::class);
        $propertyTypeList = $flexFormUserFunc->getPropertyListFromClassName ( $this->settings['datatables']['domainObject'], [], true );

        // need to get order from listView
        // and initialize the important values starting with _
        $listProperties = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->settings['datatables']['listView']);
        $propertyConfig = [];
        $colId = 0;
        
        // $repositoryClassName = get_class($repository);
        $dataMapper = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper');        
        // $propertyMapper = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper');        
        
        
        foreach ($listProperties as $property ) {
            $splitProperty = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('#', $property);
            $splitPropertyPathArray = explode('.', $splitProperty[0]);
        
            $propertyClass = \CGB\Datatables\UserFunc\FlexFormUserFunc::convertTableNameToClass($this->settings['datatables']['domainObject'], $splitProperty[1]);
            
            if ($propertyClass) {
                if(class_exists($propertyClass)) {
                    $dataMap = $dataMapper->getDataMap($propertyClass);
                    $_tableName = $dataMap->getTableName();
                }
            }
            preg_match('/^.*\((.*)\)$/', $propertyTypeList[$splitProperty[0]], $typeMatches);
            $propertyConfig[$splitProperty[0]] = [
                'property' => $splitProperty[0],
                'propertyContext' => $splitProperty[1],
                'propertyExtKey' => '',
                '_value' => $property,
                '_dm' => $this->settings['datatables']['domainObject'],
                '_class' => $propertyClass,
                '_table' => $_tableName,
                '_id' => $colId++,
                '_property' => $splitProperty[0],
                '_finalproperty' => \end($splitPropertyPathArray),
                '_type' => $typeMatches[1],
            ];
            
            if ($propertyConfig[$splitProperty[0]]['propertyContext']) {
                preg_match('/^tx_(.*)_domain_model_.*$/', $propertyConfig[$splitProperty[0]]['propertyContext'], $matches);
                $propertyConfig[$splitProperty[0]]['propertyExtKey'] = $matches[1];
            }
            
        }
   
        // convert FlexForm settings 
        $propertyId = 0;
        
        if (is_array($this->settings['datatables']['config'])) {
            foreach ($this->settings['datatables']['config'] as $key => $value) {
                $propertyElements = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('#', $value['record']['property']);
                $propertyName = $propertyElements[0];
                if (! is_array($propertyConfig[$propertyName])) {
                    echo $propertyName;
                    var_dump($propertyConfig[$propertyName]);
                } else {
                    ;
                }
                $propertyConfig[$propertyName] = array_merge($value['record'], $propertyConfig[$propertyName]);
                $propertyConfig[$propertyName]['propertyElements'] = $propertyElements;
                $propertyConfig[$propertyName]['propertyPath'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('.', $propertyName);
                $propertyConfig[$propertyName]['propertyContext'] = $propertyElements[1];
            }
        }

        if (($this->actionMethodName == 'printAction') || ($this->actionMethodName == 'exportAction')) {
            unset($propertyConfig['EDIT BUTTON']);
            unset($propertyConfig['SHOW BUTTON']);
            unset($propertyConfig['CHECKBOX']);
            unset($propertyConfig['DELETE BUTTON']);
        }
        
        $this->settings['datatables']['propertyConfig'] = $propertyConfig;
        $this->settings['datatables']['repositoryName'] = str_replace('\\Model\\', '\\Repository\\', $this->settings['datatables']['domainObject']) . 'Repository';
        
        // convert domain model name to table and translation prefix
        $domainModelNameArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('\\', $this->settings['datatables']['domainObject']);
        array_shift($domainModelNameArray);
        
        // extract domain model name and extension key
        preg_match('/^[A-Za-z0-9]*\\\([A-Za-z0-9]*)\\\Domain\\\Model\\\([A-Za-z0-9]*)$/', $this->settings['datatables']['domainObject'], $matches);
        $this->settings['datatables']['domainModelName'] = $matches[2];
        $this->settings['extKey'] = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($matches[1]);
        $this->settings['name'] = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($this->settings['datatables']['domainModelName']);
        $this->settings['prefix'] = 'tx_' . implode('_', array_map('\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility::camelCaseToLowerCaseUnderscored', $domainModelNameArray));
        $this->settings['prefix'] = $flexFormUserFunc->convertClassNameToTableName($this->settings['datatables']['domainObject']);
        $this->dataService->setSettings($this->settings);
        $this->dataService->setRequestParams([
            'pageType' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('type'),
            'pageUid' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('id'),
            'iSortingCols' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('iSortingCols'),
            'iDisplayStart' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('start'),
            'iDisplayLength' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('length'),
            'order' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('order'),
            'search' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('search'),
            'dtfilter' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('dtfilter'),
            'columns' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('columns'),
        ]);
        
        $this->dataService->setAjaxCall($this->dataService->getRequestParams('pageType') > 0);
    }
    
    /**
     * action list
     *
     * @param string $data
     * @ignorevalidation $data
     * @return void
     */
    public function listAction( $data = '' )
    {
        // get settings from DataService object
        $settings = $this->dataService->getSettings();
        $outputConfig = $this->dataService->renderHeaderList();

        // collapse properties from list
        if ($settings['datatables']['collapseProperties']) {
            $collapsePropertyList = array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $settings['datatables']['collapseProperties']));
            $outputConfig['headers'] = $this->dataService->stack($outputConfig['headers'], $collapsePropertyList);
        }
        
        // check user and usergroup permissions
        $neededUsergroups = explode(',', $settings['datatables']['usergroups']);
        $foundUsergroups = $GLOBALS['TSFE']->fe_user->groupData['uid'];
        
        $loggedin = $GLOBALS['TSFE']->fe_user->user['uid'] > 0;
        if ($settings['datatables']['usergroups']) {
            $loggedin = $settings['datatables']['usergroups'] == '';
            $loggedin |= (array_search(-2, $neededUsergroups) !== false) && (count($foundUsergroups) > 0);
            $loggedin |= count(array_intersect($neededUsergroups, $foundUsergroups));
        }
        
        if ($this->dataService->getRequestParams('pageType')) {
            if ($loggedin) {
                return $this->dataService->renderTableData( $outputConfig, $settings['name'], $this->controllerContext, 0, $data);
            } else {
                return json_encode([]);
            }
        }
        
        // print_r($settings);
        // echo $GLOBALS['TSFE']->register['dtfilter'];

        $this->view->assignMultiple(array(
            'settings' => $settings,
            'baseurl' => $GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'],
            'dtfilter' => $GLOBALS['TSFE']->register['dtfilter'],
            'data' => $data,
            'loggedin' => $loggedin,
            'headerList' => $outputConfig['headers'],
            'title' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate( $settings['prefix'] . '.' .  $settings['name'] . '_listing', $settings['extKey']),
        ));
    }
 
    
    /**
     * 
     */
    public function printAction () {
        // get settings from DataService object
        $settings = $this->dataService->getSettings();
        $outputConfig = $this->dataService->renderHeaderList();

        // check user and usergroup permissions
        $neededUsergroups = explode(',', $settings['datatables']['usergroups']);
        $foundUsergroups = $GLOBALS['TSFE']->fe_user->groupData['uid'];
        
        $loggedin = $GLOBALS['TSFE']->fe_user->user['uid'] > 0;
        if ($settings['datatables']['usergroups']) {
            $loggedin = $settings['datatables']['usergroups'] == '';
            $loggedin |= (array_search(-2, $neededUsergroups) !== false) && (count($foundUsergroups) > 0);
            $loggedin |= count(array_intersect($neededUsergroups, $foundUsergroups));
        }
        
        if (! $loggedin) {
            return 'login first!';
        }
        
        // print_r($this->outputConfig);

        $result = $this->dataService->renderTableData( $outputConfig, $settings['name'], $this->controllerContext, \CGB\Datatables\Service\DataService::DATA_PRINT);
        // print_r($result);
        $this->view->assignMultiple(array(
            'settings' => $settings,
            'baseurl' => $GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'],
            'dtfilter' => $GLOBALS['TSFE']->register['dtfilter'],
            'loggedin' => $loggedin,
            'headerList' => $outputConfig['headers'],
            'title' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate( $settings['prefix'] . '.' .  $settings['name'] . '_listing', $settings['extKey']),
            'tableData' => $result->data,
            'totalCount' => count($result),
        ));
    }
    
    /**
     * 
     */
    public function exportAction () {
        // get settings from DataService object
        $settings = $this->dataService->getSettings();
        $outputConfig = $this->dataService->renderHeaderList();

        // check user and usergroup permissions
        $neededUsergroups = explode(',', $settings['datatables']['usergroups']);
        $foundUsergroups = $GLOBALS['TSFE']->fe_user->groupData['uid'];
        
        $loggedin = $GLOBALS['TSFE']->fe_user->user['uid'] > 0;
        if ($settings['datatables']['usergroups']) {
            $loggedin = $settings['datatables']['usergroups'] == '';
            $loggedin |= (array_search(-2, $neededUsergroups) !== false) && (count($foundUsergroups) > 0);
            $loggedin |= count(array_intersect($neededUsergroups, $foundUsergroups));
        }
        
        if (! $loggedin) {
            return 'login first!';
        }
        
        // start export here
		$filename = $settings['datatables']['download']['name'];

		$cUid = $this->configurationManager->getContentObject()->data['uid'];
        $cPid = $this->configurationManager->getContentObject()->data['pid'];

        if ($sheetname == '') {
          $sheetname = 'DT_';
          $sheetname .= $filename;
          $sheetname = substr($sheetname, 0, 31);
        }
        
		$filename .= '-PID' . $cPid . '-UID' . $cUid;
		$filename .= date('-Ymd-His');
		$filename .= '.xls';
        
        // print_r($this->outputConfig);

        $result = $this->dataService->renderTableData( $outputConfig, $settings['name'], $this->controllerContext, \CGB\Datatables\Service\DataService::DATA_EXPORT);
        
        /** @var \ArminVieweg\PhpexcelService\Service\Phpexcel $phpExcelService */
        $phpExcelService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('phpexcel');
        $objPHPExcel = $phpExcelService->getPHPExcel();
        
        $lastName = $GLOBALS['TSFE']->fe_user->user['last_name'];
        $firstName = $GLOBALS['TSFE']->fe_user->user['first_name'];

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("$firstName $lastName")
            ->setLastModifiedBy("$firstName $lastName")
            ->setTitle("Office 2007 XLSX Document")
            ->setSubject("Office 2007 XLSX Document")
            ->setDescription("Generated by datatables fpor Typo3 document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("datatables export file");
        
        // insert Headers
        $colIndex = 0;
        foreach ($outputConfig['headers'] as $headerRow) {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colIndex, 1 , $headerRow['header']);
            // $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colIndex, 1)->getAlignment()->setTextRotation(45);
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colIndex, 1)->getFont()->setBold(true);
            $colIndex++;
        }
        
        
        // insert Data
        $rowIndex = 2;
        foreach ($result->data as $row) {
            foreach ($row as $colIndex => $col) {
                if (is_array($col)) {
                    $col = implode("\n", $col);
                    $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colIndex, $rowIndex)->getAlignment()->setWrapText(true);
                }
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colIndex, $rowIndex, $col);
                $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($colIndex, $rowIndex)->getAlignment()->setVertical('top');
            }
            $rowIndex++;
        }
        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle($settings['datatables']['domainModelName']);
        $objPHPExcel->getActiveSheet()->setSelectedCell('A1');
        
        foreach(range('A','Z') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)
                ->setAutoSize(true);
        }        
        
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header("ForceType: application/octet-stream");
		header("Content-Disposition: attachment; filename=$filename");
		header("Pragma: no-cache");
		header("Expires: 0");	
        
        
        // $phpExcelService->getInstanceOf('PHPExcel_Writer_Excel2007', $phpExcel);
        $objWriter = $phpExcelService->getInstanceOf('PHPExcel_Writer_Excel5', $objPHPExcel);
        $objWriter->save('php://output');
        die();
		return '';
    }
    
    /**
     * readPluginFlexFormSettings
     * 
     * @param int $cObjUid
     * @return mixed
     */
    public function readPluginFlexFormSettings( $cObjUid ) {
        if ($cObjUid > 0) {
            $flexFormField = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('pi_flexform','tt_content','uid=' . $cObjUid);
            $flexformService = new \TYPO3\CMS\Extbase\Service\FlexFormService;
            $flexFormContent = $flexformService->convertFlexFormContentToArray($flexFormField[0]['pi_flexform']);
            return $flexFormContent['settings'];
        } 
        return null;
    }
    
}