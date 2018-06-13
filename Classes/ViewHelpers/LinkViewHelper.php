<?php
namespace CGB\Datatables\ViewHelpers;

class LinkViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper
{
    public function initializeArguments() 
    {
        parent::initializeArguments();
    }
    
    /**
     * 
     * @param string $action
     * @param array $arguments
     * @param string $controller
     * @param string $extensionName
     * @param string $pluginName
     * @param int $pageUid
     * @param int $pageType
     * @param bool $noCache
     * @param bool $noCacheHash
     * @param string $section
     * @param string $format
     * @param bool $linkAccessRestrictedPages
     * @param array $additionalParams
     * @param bool $absolute
     * @param bool $addQueryString
     * @param array $argumentsToBeExcludedFromQueryString
     * @param string $addQueryStringMethod
     * @param string $objectname
     * @param string $objectuid
     * @return string
     */
    public function render($action = null, array $arguments = [], $controller = null, $extensionName = null, $pluginName = null, $pageUid = null, $pageType = 0, $noCache = false, $noCacheHash = false, $section = '', $format = '', $linkAccessRestrictedPages = false, array $additionalParams = [], $absolute = false, $addQueryString = false, array $argumentsToBeExcludedFromQueryString = [], $addQueryStringMethod = null, $objectname = '', $objectuid = '') {
        // print_r($arguments);
        $arguments[ $objectname ] = $objectuid;
        // echo "$action, $arguments, $controller, $extensionName, $pluginName, $pageUid, $pageType, $noCache, $noCacheHash, $section, $format, $linkAccessRestrictedPages,$additionalParams, $absolute, $addQueryString, $argumentsToBeExcludedFromQueryString, $addQueryStringMethod";
        $link = parent::render($action, $arguments, $controller, $extensionName, $pluginName, $pageUid, $pageType, $noCache, $noCacheHash, $section, $format, $linkAccessRestrictedPages,$additionalParams, $absolute, $addQueryString, $argumentsToBeExcludedFromQueryString, $addQueryStringMethod);
        return $link;
    }
    
}

