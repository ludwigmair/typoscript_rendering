<?php
namespace Helhum\TyposcriptRendering\ViewHelpers\Widget;

/*
 * This file is part of the TypoScript Rendering TYPO3 extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\TyposcriptRendering\Configuration\RecordRenderingConfigurationBuilder;
use Helhum\TyposcriptRendering\Renderer\RenderingContext;

/**
 *
 */
class UriViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
     * @inject
     */
    protected $configurationManager;

    /**
     * Initialize arguments
     *
     * @return void
     *
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('addQueryStringMethod', 'string', 'Method to be used for query string');
    }

    /**
     * Render the Uri.
     *
     * @param string $pluginName
     * @param string $extensionName
     * @param string $action Target action
     * @param array $arguments Arguments
     * @param string $section The anchor to be added to the URI
     * @param string $format The requested format, e.g. ".html
     * @param bool $ajax true if the URI should be to an Ajax widget, false otherwise.
     * @param string $contextRecord The record that the rendering should depend upon. e.g. current (default: record is fetched from current Extbase plugin), tt_content:12 (tt_content record with uid 12), pages:15 (pages record with uid 15), 'currentPage' record of current page
     *
     * @return string The rendered link
     *
     * @throws \Helhum\TyposcriptRendering\Configuration\ConfigurationBuildingException
     */
    public function render($pluginName, $extensionName, $action = null, array $arguments = array(), $section = '', $format = '', $ajax = true, $contextRecord = 'current')
    {
        if ($ajax === true) {
            return $this->getAjaxUri();
        } else {
            return $this->getWidgetUri();
        }
    }

    /**
     * Get the URI for an Ajax Request.
     *
     * @return string the Ajax URI
     *
     * @throws \Helhum\TyposcriptRendering\Configuration\ConfigurationBuildingException
     */
    protected function getAjaxUri()
    {
        $pluginName = $this->arguments['pluginName'];
        $extensionName = $this->arguments['extensionName'];
        $contextRecord = $this->arguments['contextRecord'];
        $arguments = $this->hasArgument('arguments') ? $this->arguments['arguments'] : array();
        if ($contextRecord === 'current') {
            if (
                $pluginName !== $this->controllerContext->getRequest()->getPluginName()
                || $extensionName !== $this->controllerContext->getRequest()->getControllerExtensionName()
            ) {
                $contextRecord = 'currentPage';
            } else {
                $contextRecord = $this->configurationManager->getContentObject()->currentRecord;
            }
        }
        $renderingConfiguration = $this->buildTypoScriptRenderingConfiguration($extensionName, $pluginName, $contextRecord);
        $additionalParams['tx_typoscriptrendering']['context'] = json_encode($renderingConfiguration);

        $uriBuilder = $this->controllerContext->getUriBuilder();
        $argumentPrefix = $this->controllerContext->getRequest()->getArgumentPrefix();

        $uriBuilder->reset()
            ->setArguments(array_merge(array($argumentPrefix => $arguments), $additionalParams))
            ->setSection($this->arguments['section'])
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(array($argumentPrefix, 'cHash'))
            ->setFormat($this->arguments['format'])
            ->setUseCacheHash(true);

        // TYPO3 6.0 compatibility check:
        if (method_exists($uriBuilder, 'setAddQueryStringMethod')) {
            $uriBuilder->setAddQueryStringMethod($this->arguments['addQueryStringMethod']);
        }

        return $uriBuilder->build();
    }

    /**
     * Get the URI for a non-AJAX Request.
     *
     * @return string the Widget URI
     */
    protected function getWidgetUri()
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        $argumentPrefix = $this->controllerContext->getRequest()->getArgumentPrefix();
        $arguments = $this->hasArgument('arguments') ? $this->arguments['arguments'] : array();
        if ($this->hasArgument('action')) {
            $arguments['action'] = $this->arguments['action'];
        }
        if ($this->hasArgument('format') && $this->arguments['format'] !== '') {
            $arguments['format'] = $this->arguments['format'];
        }
        if ($this->hasArgument('addQueryStringMethod') && $this->arguments['addQueryStringMethod'] !== '') {
            $arguments['addQueryStringMethod'] = $this->arguments['addQueryStringMethod'];
        }
        $uriBuilder->reset()
            ->setArguments(array($argumentPrefix => $arguments))
            ->setSection($this->arguments['section'])
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(array($argumentPrefix, 'cHash'))
            ->setFormat($this->arguments['format']);

        // TYPO3 6.0 compatibility check:
        if (method_exists($uriBuilder, 'setAddQueryStringMethod')) {
            $uriBuilder->setAddQueryStringMethod($this->arguments['addQueryStringMethod']);
        }

        return $uriBuilder->build();
    }

    /**
     * @param string $extensionName
     * @param string $pluginName
     * @param string $contextRecord
     *
     * @return string[]
     *
     * @throws \Helhum\TyposcriptRendering\Configuration\ConfigurationBuildingException
     */
    public function buildTypoScriptRenderingConfiguration($extensionName, $pluginName, $contextRecord)
    {
        $configurationBuilder = new RecordRenderingConfigurationBuilder(new RenderingContext($GLOBALS['TSFE']));
        return $configurationBuilder->configurationFor($extensionName, $pluginName, $contextRecord);
    }
}
