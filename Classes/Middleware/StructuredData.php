<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\Rampage\Domain\Model\AbstractPage;
use Zeroseven\Rampage\Event\StructuredDataEvent;
use Zeroseven\Rampage\Utility\ObjectUtility;

class StructuredData implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController
        && ($uid = $GLOBALS['TSFE']->id ?? 0)
        && ($row = $GLOBALS['TSFE']->page ?? BackendUtility::getRecord(AbstractPage::TABLE_NAME, $uid))
        && ($registration = ObjectUtility::isObject($uid, $row))
        && ($structuredData = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new StructuredDataEvent($registration, $uid, $row))->parse())
        && GeneralUtility::makeInstance(PageRenderer::class)->addFooterData('<script type="application/ld+json">' . $structuredData . '</script>');

        return $handler->handle($request);
    }
}
