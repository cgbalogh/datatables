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

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Form\Exception as FormException;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Lang\LanguageService;

/**
 * DatatableController
 */
class DatatablesManagerController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Backend\View\BackendTemplateView::class;    
    
    /**
     * 
     */
    public function indexAction () {
        $this->registerDocheaderButtons();
        /*
        $this->view->getModuleTemplate()->setModuleName($this->request->getPluginName() . '_' . $this->request->getControllerName());
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $this->view->assign('datatables', $this->getAvailableDatatableDefinitions());
        $this->view->assign('stylesheets', $this->resolveResourcePaths($this->formSettings['datatablesManager']['stylesheets']));
        $this->view->assign('dynamicRequireJsModules', $this->formSettings['formManager']['dynamicRequireJsModules']);
        $this->view->assign('formManagerAppInitialData', $this->getFormManagerAppInitialData());
        if (!empty($this->formSettings['formManager']['javaScriptTranslationFile'])) {
            $this->getPageRenderer()->addInlineLanguageLabelFile($this->formSettings['formManager']['javaScriptTranslationFile']);
        }
        */
        $this->view->assignMultiple([
            'test' => 'test',
        ]);
    }
    
    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();

        $mayMakeShortcut = $this->getBackendUser()->mayMakeShortcut();
        if ($mayMakeShortcut) {
            $extensionName = $currentRequest->getControllerExtensionName();
            if (count($getVars) === 0) {
                $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
                $getVars = ['id', 'M', $modulePrefix];
            }

            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName($moduleName)
                ->setDisplayName($this->getLanguageService()->sL('LLL:EXT:datatables/Resources/Private/Language/locallang_moddatatable.xlf:datatablesManager.headline'))
                ->setGetVariables($getVars);
            $buttonBar->addButton($shortcutButton);
        }

        if (isset($getVars['action']) && $getVars['action'] !== 'index') {
            $backButton = $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:back'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-up', Icon::SIZE_SMALL))
                ->setHref($this->getModuleUrl($moduleName));
            $buttonBar->addButton($backButton);
        } else {
            $addFormButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'newDatatablesDefinition'])
                ->setHref('#')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:datatables/Resources/Private/Language/locallang_moddatatable.xlf:datatablesManager.create_new_definition'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL));
            $buttonBar->addButton($addFormButton, ButtonBar::BUTTON_POSITION_LEFT);
        }
    }
    
    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the Language Service
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the page renderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }    
}