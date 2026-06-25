<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Page\PageRenderer;
use Zeroseven\Pagebased\Domain\Model\AbstractPage;
use Zeroseven\Pagebased\Event\StructuredDataEvent;
use Zeroseven\Pagebased\Utility\FrontendRequestUtility;
use Zeroseven\Pagebased\Utility\ObjectUtility;

class StructuredData implements MiddlewareInterface
{
    public function __construct(private readonly EventDispatcher $eventDispatcher, private readonly PageRenderer $pageRenderer) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($uid = FrontendRequestUtility::getPageId($request))
        && ($row = FrontendRequestUtility::getPageRecord($request) ?? BackendUtility::getRecord(AbstractPage::TABLE_NAME, $uid))
        && ($registration = ObjectUtility::isObject($uid, $row))
        && ($structuredData = $this->eventDispatcher->dispatch(new StructuredDataEvent($registration, $uid, $row))->parse())) {
            $this->pageRenderer->addFooterData('<script type="application/ld+json">' . $structuredData . '</script>');
        }

        return $handler->handle($request);
    }
}
