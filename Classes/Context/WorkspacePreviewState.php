<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Context;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Marks the current request as a record list managed workspace preview.
 *
 * Backend modules of this extension handle the publishing workflow themselves and deliberately bypass the native
 * workspace GUI, so preview URIs have to point to the frontend directly instead of the workspace split preview
 * module. Since TYPO3 core builds preview URIs in several places, the rewriting happens in an event listener, which
 * uses this state to tell "our" preview requests apart from regular workspace usage of the same installation.
 *
 * @see \Xima\XimaTypo3Recordlist\EventListener\WorkspacePreviewUriRewriter
 */
class WorkspacePreviewState implements SingletonInterface
{
    private bool $active = false;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
