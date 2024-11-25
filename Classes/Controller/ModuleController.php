<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Controller;

use B13\Formdata\Domain\Repository\FormDataRepository;
use B13\Formdata\Service\FormdataService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Domain\Model\Demand;
use Typoheads\Formhandler\Domain\Model\LogData;
use Typoheads\Formhandler\Domain\Repository\LogDataRepository;
use Typoheads\Formhandler\Generator\BackendCsv;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class ModuleController extends ActionController
{
    protected array $gp;
    protected ModuleTemplate $moduleTemplate;
    protected \Typoheads\Formhandler\Utility\GeneralUtility $utilityFuncs;

    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected IconFactory $iconFactory,
        protected PageRenderer $pageRenderer,
        protected LogDataRepository $logDataRepository,
        protected Manager $componentManager
    ) {
        $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
    }

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $this->id = (int)$this->request->getQueryParams()['id'];
        $this->gp = $this->request->getArguments();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Formhandler/FormhandlerModule');

        if (!isset($this->settings['dateFormat'])) {
            $this->settings['dateFormat'] = isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']) ? 'm-d-Y' : 'd-m-Y';
        }

        if (!isset($this->settings['timeFormat'])) {
            $this->settings['timeFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        }

        if ($this->arguments->hasArgument('demand')) {
            $propertyMappingConfiguration = $this->arguments['demand']->getPropertyMappingConfiguration();
            // allow all properties:
            $propertyMappingConfiguration->allowAllProperties();
            $propertyMappingConfiguration->setTypeConverterOption(
                'TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter',
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                true
            );
        }
    }

    /**
     * Displays log data
     */
    public function indexAction(?Demand $demand = null, ?int $page = null): ResponseInterface
    {
        if ($demand === null) {
            $demand = GeneralUtility::makeInstance(Demand::class);
            if (!isset($this->gp['demand']['pid'])) {
                $demand->setPid($this->id);
            }
        }
        if ($page !== null) {
            $demand->setPage($page);
        }
        $this->setStartAndEndTimeFromTimeSelector($demand);

        $logDataRows = $this->logDataRepository->findDemanded($demand);
        $pagination = $this->preparePagination($demand);
        $this->moduleTemplate->assign('demand', $demand);
        $this->moduleTemplate->assign('logDataRows', $logDataRows);
        $this->moduleTemplate->assign('settings', $this->settings);
        $this->moduleTemplate->assign('pagination', $pagination);
        $this->moduleTemplate->assign('permissions', []);
        return $this->moduleTemplate->renderResponse('index');
    }

    public function viewAction(?LogData $logDataRow = null): ResponseInterface
    {
        if ($logDataRow !== null) {
            $logDataRow->setParams(unserialize($logDataRow->getParams()));
            $this->moduleTemplate->assign('data', $logDataRow);
            $this->moduleTemplate->assign('settings', $this->settings);
        }

        return $this->moduleTemplate->renderResponse('view');
    }

    public function selectFieldsAction(string $logDataUids = null, string $filetype = ''): ResponseInterface
    {
        if ($logDataUids !== null) {
            if (isset($this->settings[$filetype]['config']['fields'])) {
                $fields = GeneralUtility::trimExplode(',', $this->settings[$filetype]['config']['fields']);
                return $this->redirect(
                    'export',
                    null,
                    null,
                    [
                        'logDataUids' => $logDataUids,
                        'fields' => $fields,
                        'filetype' => $filetype,
                    ]
                );
            }

            $logDataRows = $this->logDataRepository->findByUids($logDataUids);
            $fields = [
                'global' => [
                    'pid',
                    'ip',
                    'submission_date',
                ],
                'system' => [
                    'randomID',
                    'removeFile',
                    'removeFileField',
                    'submitField',
                    'submitted',
                ],
                'custom' => [],
            ];
            foreach ($logDataRows as $logDataRow) {
                $params = unserialize($logDataRow->getParams());
                if (is_array($params)) {
                    $rowFields = array_keys($params);
                    foreach ($rowFields as $idx => $rowField) {
                        if (in_array($rowField, $fields['system'])) {
                            unset($rowFields[$idx]);
                        } elseif (substr($rowField, 0, 5) === 'step-') {
                            unset($rowFields[$idx]);
                            if (!in_array($rowField, $fields['system'])) {
                                $fields['system'][] = $rowField;
                            }
                        } elseif (!in_array($rowField, $fields['custom'])) {
                            $fields['custom'][] = $rowField;
                        }
                    }
                }
            }
            $this->moduleTemplate->assign('fields', $fields);
            $this->moduleTemplate->assign('logDataUids', $logDataUids);
            $this->moduleTemplate->assign('filetype', $filetype);
            $this->moduleTemplate->assign('settings', $this->settings);

            return $this->moduleTemplate->renderResponse('selectFields');
        }
    }

    /**
     * Exports given rows as file
     * @param string uids to export
     * @param array fields to export
     * @param string export file type (PDF || CSV)
     */
    public function exportAction($logDataUids = null, array $fields = [], $filetype = ''): ResponseInterface
    {
        if ($logDataUids !== null && !empty($fields)) {
            $logDataRows = $this->logDataRepository->findByUids($logDataUids);
            $convertedLogDataRows = [];
            foreach ($logDataRows as $idx => $logDataRow) {
                $convertedLogDataRows[] = [
                    'pid' => $logDataRow->getPid(),
                    'ip' => $logDataRow->getIp(),
                    'crdate' => $logDataRow->getCrdate(),
                    'params' => unserialize($logDataRow->getParams()),
                ];
            }
            if ($filetype === 'pdf') {
                $className = $this->utilityFuncs->getPreparedClassName(
                    $this->settings['pdf'],
                    '\Typoheads\Formhandler\Generator\BackendTcPdf'
                );

                $generator = $this->componentManager->getComponent($className);
                $this->settings['pdf']['config']['records'] = $convertedLogDataRows;
                $this->settings['pdf']['config']['exportFields'] = $fields;
                $generator->init([], $this->settings['pdf']['config']);
                $generator->process();
            } elseif ($filetype === 'csv') {
                $className = $this->utilityFuncs->getPreparedClassName(
                    $this->settings['csv'],
                    BackendCsv::class
                );

                $generator = $this->componentManager->getComponent($className);
                $this->settings['csv']['config']['records'] = $convertedLogDataRows;
                $this->settings['csv']['config']['exportFields'] = $fields;
                $generator->init([], $this->settings['csv']['config']);
                $generator->process();
            }
        }
        return $this->htmlResponse();
    }

    protected function setStartAndEndTimeFromTimeSelector(Demand $demand)
    {
        $startTime = $demand->getManualDateStart() ? $demand->getManualDateStart()->getTimestamp() : 0;
        $endTime = $demand->getManualDateStop() ? $demand->getManualDateStop()->getTimestamp() : 0;
        $demand->setStartTimestamp($startTime);
        $demand->setEndTimestamp($endTime);
    }

    /**
     * Prepares information for the pagination of the module
     */
    protected function preparePagination(Demand $demand): array
    {
        $count = $this->logDataRepository->countRedirectsByByDemand($demand);
        $numberOfPages = ceil($count / $demand->getLimit());
        $endRecord = $demand->getOffset() + $demand->getLimit();
        if ($endRecord > $count) {
            $endRecord = $count;
        }

        $pagination = [
            'count' => $count,
            'current' => $demand->getPage(),
            'numberOfPages' => $numberOfPages,
            'hasLessPages' => $demand->getPage() > 1,
            'hasMorePages' => $demand->getPage() < $numberOfPages,
            'startRecord' => $demand->getOffset() + 1,
            'endRecord' => $endRecord,
        ];
        if ($pagination['current'] < $pagination['numberOfPages']) {
            $pagination['nextPage'] = $pagination['current'] + 1;
        }
        if ($pagination['current'] > 1) {
            $pagination['previousPage'] = $pagination['current'] - 1;
        }
        return $pagination;
    }
}
