<?php
namespace CGB\Datatables\UserFunc;

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
* Class FlexFormUserFunc
*/
class FlexFormUserFunc {

    
    
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
     * @inject 
     */
    protected $dataMapFactory;
    
    
    /**
    * @param array $fConfig
     * 
     * this method reads the autoregistered classes from
    *
    * @return void
    */
    public function getClasses(&$fConfig) {
        // select only autoregistered classes from the following vendors
        $vendorList = array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fConfig['row']['settings.datatables.vendorList'], 1));
        
        // get autoloaded classes from autoloader cached classmap
        if ( file_exists(PATH_site.'typo3temp/autoload/autoload_classmap.php')) {
            // for Typo3 7.6.x
            $classes = include PATH_site.'typo3temp/autoload/autoload_classmap.php';
        } elseif ( file_exists(PATH_site.'typo3conf/autoload/autoload_classmap.php')) {
            // for Typo3 8.x.x
            $classes = include PATH_site.'typo3conf/autoload/autoload_classmap.php';
        } else {
            $classes = [];
        }
        
        // add empyt selection to item list
        array_push($fConfig['items'], array('',''));
        
        
        // 
        // cycle all classes and add to item list if class name containes \Domain\Model
        //
        foreach($classes as $classname => $location) {
            // if no vendor is given, all classes will be listed
            if ($fConfig['row']['settings.datatables.vendorList'] == '') {
                if (strpos($classname, '\\Domain\\Repository\\') !== false) {
                    $classname = str_replace('\\Repository\\', '\\Model\\', $classname);
                    $classname = str_replace('Repository', '', $classname);
                    array_push($fConfig['items'], array($classname,$classname));
                }
            } else {
                foreach ($vendorList as $vendor) {
                    if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($classname, $vendor . '\\')) {
                        if ((strpos($classname, 'Domain\\Repository')) && (strpos($classname, '\\Domain\\Repository\\') !== false)) {
                            $classname = str_replace('\\Repository\\', '\\Model\\', $classname);
                            $classname = str_replace('Repository', '', $classname);
                            array_push($fConfig['items'], array($classname,$classname));
                        }
                    }
                }
            }
        }
    }

    /**
    * getProperties
    * 
    * gets properties from flexform configuration
    * 
    * @param array $fConfig
    *
    * @return void
    */
    public function getProperties(&$fConfig) {
        // get selected class 
        $class = $fConfig ['flexParentDatabaseRow']['pi_flexform']['data']['sheet2']['lDEF']['settings.datatables.domainObject']['vDEF'][0];
        // get property list from classname renders a property, type pair to be added to the propertyList selector
        foreach (self::getPropertyListFromClassName ( $class ) as $key => $value) {
            \array_push($fConfig['items'], array($value ,$key));
        }

        // add default items for editing, deleting and selecting
        \array_push($fConfig['items'], array('EDIT BUTTON','EDIT BUTTON'));
        \array_push($fConfig['items'], array('SHOW BUTTON','SHOW BUTTON'));
        \array_push($fConfig['items'], array('DELETE BUTTON','DELETE BUTTON'));
        \array_push($fConfig['items'], array('CHECKBOX','CHECKBOX'));
        \array_push($fConfig['items'], array('CUSTOM','CUSTOM'));
    }

    /**
    * getPropertiesTree
    * 
    * gets properties from flexform configuration as tree
    * 
    * @param array $fConfig
    *
    * @return void
    */
    public function getPropertiesTree(&$fConfig) {
        // get selected class 
        $class = $fConfig ['flexParentDatabaseRow']['pi_flexform']['data']['sheet2']['lDEF']['settings.datatables.domainObject']['vDEF'][0];
        // get property list from classname renders a property, type pair to be added to the propertyList selector
        foreach (self::getPropertyListFromClassName ( $class ) as $key => $value) {
            \array_push($fConfig['items'], array($value ,$key));
        }

        // add default items for editing, deleting and selecting
        \array_push($fConfig['items'], array('EDIT BUTTON','EDIT BUTTON'));
        \array_push($fConfig['items'], array('SHOW BUTTON','SHOW BUTTON'));
        \array_push($fConfig['items'], array('DELETE BUTTON','DELETE BUTTON'));
        \array_push($fConfig['items'], array('CHECKBOX','CHECKBOX'));
        \array_push($fConfig['items'], array('CUSTOM','CUSTOM'));
    }
    
    /**
    * getSelectedProperties
    * 
    * @param array $fConfig
    *
    * @return void
    */
    public function getSelectedProperties(&$fConfig) {
        // get selected class 
        $class = $fConfig ['flexParentDatabaseRow']['pi_flexform']['data']['sheet2']['lDEF']['settings.datatables.domainObject']['vDEF'][0];
   
        // get property list from classname renders a property, type pair to be added to the propertyList selector
        $allProperties = self::getPropertyListFromClassName ( $class );

        // get properties from flex form configuration
        if (is_array($fConfig ['flexParentDatabaseRow']['pi_flexform']['data']['sheet3']['lDEF']['settings.datatables.listView']['vDEF'])) {
            $properties = $fConfig ['flexParentDatabaseRow']['pi_flexform']['data']['sheet3']['lDEF']['settings.datatables.listView']['vDEF'];
        } else {
            $properties = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fConfig ['flexParentDatabaseRow']['pi_flexform']['data']['sheet3']['lDEF']['settings.datatables.listView']['vDEF']);
        }
        if (! is_array($properties)) { 
            die(); 
        }
        foreach ($properties as $property) {
            $displayProperty = $allProperties[$property] ? $allProperties[$property] : $property;
            \array_push($fConfig['items'], array($displayProperty, $property));
        }
    }
    
    /**
     * getPropertyListFromClassName
     * 
     * @param string $class
     * @param array $classList
     * @param bool $noHeaders
     */
    public function getPropertyListFromClassName ( $class, $classList = [], $noHeaders=false, $level = 0 ) {
        $retVal = [];
        $retValAppend = [];
        $properties = [];
        static $recursion = [ 'counter' => 0, 'classList' => []];
                
        $level++;
        $classList[$class]++;

        // print_r($classList);
        
        if ($reset){
            $recursion = [ 'counter' => 0, 'classList' => []];
        }
        
        if ( $class ) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            
            if (method_exists($class, '__construct')) {
                $methodReflection = new \TYPO3\CMS\Extbase\Reflection\MethodReflection($class, '__construct');
                $parameters = array_merge([$class], $methodReflection->getParameters());
            
//                echo $class; 
//                print_r($parameters);
            
                switch( count($parameters)) {
                    case 0: 
                        $obj = $objectManager->get($class);
                        break;
                    case 1:
                        $obj = $objectManager->get($class, $parameters[0]);
                        break;
                    case 2:
                        $obj = $objectManager->get($class, $parameters[0], $parameters[1]);
                        break;
                    case 3:
                        $obj = $objectManager->get($class, $parameters[0], $parameters[1], $parameters[2]);
                        break;
                    case 4:
                        $obj = $objectManager->get($class, $parameters[0], $parameters[1], $parameters[2], $parameters[3]);
                        break;
                    case 5:
                        $obj = $objectManager->get($class, $parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
                        break;
                    case 6:
                        $obj = $objectManager->get($class, $parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4], $parameters[5]);
                        break;
                    case 7:
                        $obj = $objectManager->get($class, $parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4], $parameters[5], $parameters[6]);
                        break;
                    case 8:
                        $obj = $objectManager->get($class, $parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4], $parameters[5], $parameters[6], $parameters[7]);
                        break;

                }
            } else {
                $obj = $objectManager->get($class);
            }
            
            $properties = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettablePropertyNames($obj);
            $tableName = self::convertClassNameToTableName($class);
        }
        
        if ($this->dataMapFactory !== null) {
            $dataMap = $this->dataMapFactory->buildDataMap($class);
        }
        
        foreach ($properties as $propertyName) {
            $getter = 'get' . ucfirst($propertyName);
            if (method_exists($obj, $getter)) {
                $property = $obj->{$getter}();
                // _getProperty($propertyName);
            } else {
                continue;
            }
            $fieldName = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName);

            if ($this->dataMap !== null) {
                $columnMap = $dataMap->getColumnMap($propertyName);
                if ($columnMap !== null) {
                    $fieldName = $columnMap->getColumnName($propertyName);
                }
            }

            $type = gettype($property);
            $childTableName = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['foreign_table'];
            $childClassName = self::convertTableNameToClass($class, $childTableName);
            $descr = '';
            
            if (property_exists($obj, $propertyName)) {
                $reflection = new \TYPO3\CMS\Extbase\Reflection\PropertyReflection($obj, $propertyName);

                if ($reflection->isTaggedWith('var')) {
                    $varAnnotationArray = $reflection->getTagValues('var');
                    $varAnnotationWords = $varAnnotationArray[0];
                    $varAnnotationSplit = explode(' ', $varAnnotationWords);
                    $varAnnotation = $varAnnotationSplit[0];

                    if (strpos($varAnnotation, '\\TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage' ) === 0 ) {
                        $childClassName = substr($varAnnotation, strpos($varAnnotation, '<') + 2, strpos($varAnnotation, '>') - strpos($varAnnotation, '<') - 2);
                        $descr = $childClassName;
                        $storageObject = true;
                    } elseif ( strpos($varAnnotation, '\\DateTime') === 0) {
                        $childClassName = '\\DateTime';
                        $descr = 'DateTime';
                    } elseif (strpos($varAnnotation, '\\TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUser' ) === 0 ) {
                        $childClassName = substr($varAnnotation,1);
                        $childTableName = 'fe_users';
                        $descr = $varAnnotation;
                        if (! isset($GLOBALS['TCA'][$childTableName])) {
                            // $childClassName = '';
                        }
                    } elseif (strpos($varAnnotation, '\\TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup' ) === 0 ) {
                        $childClassName = substr($varAnnotation,1);
                        $childTableName = 'fe_groups';
                        $descr = $varAnnotation;
                        if (! isset($GLOBALS['TCA'][$childTableName])) {
                            // $childClassName = '';
                        }
                    } elseif ( substr($varAnnotation, 0, 1) == '\\') {
                        $childClassName = substr($varAnnotation,1);
                        $childTableName = self::convertClassNameToTableName($childClassName);
                        $descr = $varAnnotation;
                        if (! isset($GLOBALS['TCA'][$childTableName])) {
                            // $childClassName = '';
                        }
                    } elseif ($type === 'NULL') {
                        $type = $varAnnotation;
                        $descr = $varAnnotation;
                    } elseif ($propertyName === 'pid') {
                        $type = 'int';
                        $descr = 'int';
                    } elseif ($propertyName === 'uid') {
                        $type = 'int';
                        $descr = 'int';
                    } else {
                        $descr = $type;
                    }
                } 
            }

            if ($reflection) {
                $dontDiveIntoObject = $reflection->isTaggedWith('datatablesdontdive');
            }
            
            // at the moment these repos are hardcoded.
            // this should be replaced by using the t3 BE functions
            if ($childTableName == '') {
              switch ($childClassName) {
                case 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FileReference':
                  // $childTableName = 'sys_file_reference';
                  break;
                case 'CGB\\Relax5core\\Domain\\Model\\Owner':
                case 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUser':
                  $childTableName = 'fe_users';
                  break;
                case 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup':
                  $childTableName = 'fe_groups';
                  break;
              }
            }
            
            // echo " ### $tableName $fieldName $childClassName - $childTableName ~~~";
            if ( ($level < 4) && $childTableName && ($classList[$childClassName] <= 1) && (! $dontDiveIntoObject))  {
//                $classList[] = $class; 
                $childPropertyList = self::getPropertyListFromClassName ( $childClassName, $classList, $noHeaders, $level );
                
                foreach ($childPropertyList as $childKey => $childValue) {
                    // $headerSuffix = $storageObject ? '#' . self::convertClassNameToTableName($childClassName) : '';
                    // $headerSuffix = '#' . $childTableName;

                    // append header context only if $noHeader is false
                    if (! $noHeaders) {
                        $headerSuffix = '#' . self::convertClassNameToTableName($childClassName);
                            // self::getExtFromClassName($class);
                        // self::convertClassNameToTableName($childClassName) .
                    }
                    // if ($childTableName == 'tx_dastool_domain_model_kontakt') echo $childClassName;
                    
                    $retValAppend[ $propertyName . '.' . $childKey . $headerSuffix ] = ' ' . $propertyName . ' -> ' . $childValue;
                }      
            }

            // $propertyName .= '|' . $descr;
            $retVal[$propertyName] = $propertyName . " ($descr)";
            $retVal = array_merge($retVal, $retValAppend);
        }
        return $retVal;
    }
 
    /**
     * 
     * @param type $tableName
     */
    static function convertTableNameToClass($class, $tableName) {
        // CGB\Dastool\Domain\Model\StudieCGB\Dastool\Domain\Model\Studietx_dastool_domain_model_institut        

        if ($tableName == '') {
            return '';
        } elseif ($tableName == 'sys_file_reference') {
            $className = 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FileReference';
        } elseif ($tableName == 'fe_users') {
            $className = 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUser';
        } elseif ($tableName == 'fe_groups') {
            $className = 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup';
        } else {
            $extFromTablename = ucfirst(explode('_', $tableName)[1]);
                    
            $classExploded = explode('\\', $class);
            // $classPrefix = $classExploded[0] . '\\' . $classExploded[1] . '\\' . $classExploded[2] . '\\' . $classExploded[3] . '\\';
            $classPrefix = $classExploded[0] . '\\' . $extFromTablename . '\\' . $classExploded[2] . '\\' . $classExploded[3] . '\\';
            $classSuffix = substr($tableName, strpos($tableName, 'domain_model') + 13);
            $classSuffixExploded = array_map('ucfirst', explode('_', $classSuffix));
            $className = $classPrefix . implode('\\', $classSuffixExploded);
            // echo $className;
        }
        return $className;
    }
    
    /**
     * 
     * @param string $class
     */
    static function convertClassNameToTableName($class) {
        $classExploded = \explode('\\', $class);
        if ($classExploded[0] !== 'TYPO3') {
            $prefix = 'tx_';
            array_shift($classExploded);

            $prefix .= strtolower(array_shift($classExploded)) . '_';
            $prefix .= strtolower(array_shift($classExploded)) . '_';
            $prefix .= strtolower(array_shift($classExploded)) . '_';
            return $prefix . \implode('', array_map('strtolower', $classExploded));

            
//            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
//            $dataMapper = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper');        
//            $dataMap = $dataMapper->getDataMap($class);
//            return $dataMap->getTableName();
        }
        
        return '';
    }

    /**
     * 
     * @param string $class
     */
    static function getExtFromClassName($class) {
         $classExploded = \explode('\\', $class);
        if ($classExploded[0] !== 'TYPO3') {
          
            $extKey = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored ($classExploded[1]);
            return $extKey;
        }
        
        return '';
     
    }

    
}