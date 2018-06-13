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
* Class TcaUserFunc
*/
class TcaUserFunc {

    /**
     * this method reads the autoregistered classes from
    *
    * @return void
    */
    static function getClasses() {

        if ( file_exists(PATH_site.'typo3temp/autoload/autoload_classmap.php')) {
            // for Typo3 7.6.x
            $classes = include PATH_site.'typo3temp/autoload/autoload_classmap.php';
        } elseif ( file_exists(PATH_site.'typo3conf/autoload/autoload_classmap.php')) {
            // for Typo3 8.x.x
            $classes = include PATH_site.'typo3conf/autoload/autoload_classmap.php';
        } else {
            $classes = [];
        }

        // 
        // cycle all classes and add to item list if class name containes \Domain\Model
        //
        $items[] = ['--', ''];
        foreach($classes as $classname => $location) {
            // if no vendor is given, all classes will be listed
            if (strpos($classname, '\\Domain\\Model\\') !== false) {
                $items[] = [$classname, $classname];
            }
        }
        return $items;
    }

    /**
     * 
     * @param type $PA
     * @param type $fObj
     */
    public function getDomainModelObjects($PA, $fObj) {
        if ( file_exists(PATH_site.'typo3temp/autoload/autoload_classmap.php')) {
            // for Typo3 7.6.x
            $classes = include PATH_site.'typo3temp/autoload/autoload_classmap.php';
        } elseif ( file_exists(PATH_site.'typo3conf/autoload/autoload_classmap.php')) {
            // for Typo3 8.x.x
            $classes = include PATH_site.'typo3conf/autoload/autoload_classmap.php';
        } else {
            $classes = [];
        }

        // 
        // cycle all classes and add to item list if class name containes \Domain\Model
        //
        \array_push($PA['items'], array('--',''));
        foreach($classes as $classname => $location) {
            // if no vendor is given, all classes will be listed
            if (strpos($classname, '\\Domain\\Model\\') !== false) {
                \array_push($PA['items'], array($classname ,$classname));
            }
        }
    }

    
    /**
     * 
     * @param type $PA
     * @param type $fObj
     */
    public function getProperties($PA, $fObj) {
        $class = $PA['row']['domain_model_object'];
        if (is_array($class)) {
            $class = $class[0];
        }
        foreach (self::getPropertyListFromClassName ( $class ) as $key => $value) {
            \array_push($PA['items'], array($value ,$key));
        }            
        return;
        
        // fetch conditiongroup
        
        if ($PA['row']['conditiongroup']) {
            $conditiongroup = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                'validator',
                'tx_relax5validator_domain_model_conditiongroup',
                'uid = ' . $PA['row']['conditiongroup']
            );
        }

        // fetch validator based on conditiongroup
        if ($conditiongroup['validator']) {
            // select rowdata of validator to get class name of parent
            $validator = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                'domain_model_object',
                'tx_relax5validator_domain_model_validator',
                'uid = ' . $conditiongroup['validator']
            );
            // $class = $validator['domain_model_object'];
            $class = $PA['row']['domain_model_object'];echo $class;
            foreach (self::getPropertyListFromClassName ( $class ) as $key => $value) {
                \array_push($PA['items'], array($value ,$key));
            }            
        }
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
     */
    private function getPropertyListFromClassName ( $class, $classList = [] ) {
        $retVal = [];
        $retValAppend = [];
        $properties = [];
        static $recursion = [ 'counter' => 0, 'classList' => []];

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
        foreach ($properties as $propertyName) {
            if (method_exists($obj, '_getProperty')) {
                $property = $obj->_getProperty($propertyName);
            } else {
                continue;
            }
            $fieldName = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName);

            $type = gettype($property);
            $childTableName = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['foreign_table'];
            $childClassName = self::convertTableNameToClass($class, $childTableName);            
            $storageObject = false;
            
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
                    } elseif ( substr($varAnnotation, 0, 1) == '\\') {
                        $childClassName = substr($varAnnotation,1);
                        $childTableName = self::convertClassNameToTableName($childClassName);
                        $descr = $varAnnotation;
                        if (! isset($GLOBALS['TCA'][$childTableName])) {
                            $childClassName = '';
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
                $dontDiveIntoObject = $reflection->isTaggedWith('validatordontdive');
            }
            
            if ($childTableName && (array_search( $childClassName, $classList) === false) && (! $dontDiveIntoObject))  {
                $classList[] = $class; 
                $childPropertyList = self::getPropertyListFromClassName ( $childClassName, $classList );
                
                foreach ($childPropertyList as $childKey => $childValue) {
                    // $headerSuffix = $storageObject ? '#' . self::convertClassNameToTableName($childClassName) : '';
                    // $headerSuffix = '#' . $childTableName;
                    // $headerSuffix = '#' . self::convertClassNameToTableName($childClassName);
                    $headerSuffix = '';

                    // if ($childTableName == 'tx_dastool_domain_model_kontakt') echo $childClassName;
                    $accessStorage = $storageObject ? '.#.' : '.';
                    $retValAppend[ $propertyName . $accessStorage . $childKey . $headerSuffix ] = ' ' . $propertyName . ' -> ' . $childValue;
                }      
            }

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
        } else {
            $classExploded = explode('\\', $class);
            $classPrefix = $classExploded[0] . '\\' . $classExploded[1] . '\\' . $classExploded[2] . '\\' . $classExploded[3] . '\\';
            $classSuffix = substr($tableName, strpos($tableName, 'domain_model') + 13);
            $classSuffixExploded = array_map('ucfirst', explode('_', $classSuffix));
            $className = $classPrefix . implode('\\', $classSuffixExploded);
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
            array_shift($classExploded);
            $classExploded[0] = strtolower($classExploded[0]);
            return 'tx_' . \implode('_', array_map('\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility::camelCaseToLowerCaseUnderscored', $classExploded));
        }
        return '';
    }

}