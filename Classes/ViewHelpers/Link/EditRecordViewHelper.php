<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\ViewHelpers\Link;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class EditRecordViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function __construct(
        private readonly UriBuilder $uriBuilder
    ) {
        parent::__construct();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'int', 'uid of record to be edited', true);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('fields', 'string', 'Edit only these fields (comma separated list)');
        $this->registerArgument('module', 'string', 'Set module identifier for context - marking as active when editing the record', false, '');
        $this->registerArgument('returnUrl', 'string', 'return to this URL after closing the edit dialog', false, '');
        $this->registerArgument('workspaceId', 'int', 'workspace uid to respect in edit view', false, 0);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws RouteNotFoundException
     */
    public function render(): string
    {
        if ($this->arguments['uid'] < 1) {
            throw new \InvalidArgumentException('Uid must be a positive integer, ' . $this->arguments['uid'] . ' given.', 1766135800);
        }
        $request = $this->renderingContext->hasAttribute(ServerRequestInterface::class) ?
            $this->renderingContext->getAttribute(ServerRequestInterface::class) : null;

        if (empty($this->arguments['returnUrl'])
            && $request instanceof ServerRequestInterface
        ) {
            $this->arguments['returnUrl'] = $request->getAttribute('normalizedParams')->getRequestUri();
        }

        $params = [
            'edit' => [$this->arguments['table'] => [$this->arguments['uid'] => 'edit']],
            'module' => ($this->arguments['module'] ?? '') ?: ($request?->getAttribute('module')?->getIdentifier() ?? ''),
            'returnUrl' => $this->arguments['returnUrl'],
            'workspaceId' => $this->arguments['workspaceId'],
        ];
        if ($this->arguments['fields'] ?? false) {
            $params['columnsOnly'] = [
                $this->arguments['table'] => GeneralUtility::trimExplode(',', $this->arguments['fields'], true),
            ];
        }
        $uri = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $params);
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
