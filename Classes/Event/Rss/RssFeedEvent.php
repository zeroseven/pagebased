<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event\Rss;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pagebased\Registration\Registration;

final class RssFeedEvent extends AbstractRssObject
{
    public function __construct(private readonly Registration $registration, private readonly ServerRequestInterface $serverRequest, private readonly array $settings, private readonly array $content = [], private readonly ?QueryResultInterface $queryResult = null)
    {
        $this->tag = GeneralUtility::makeInstance(TagBuilder::class, 'rss');
        $this->tag->addAttributes([
            'version' => '2.0',
            'xmlns:content' => 'http://purl.org/rss/1.0/modules/content/',
            'xmlns:atom' => 'http://www.w3.org/2005/Atom',
        ]);
    }

    public function getRegistration(): Registration
    {
        return $this->registration;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->serverRequest;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function getObjects(): ?QueryResultInterface
    {
        return $this->queryResult;
    }

    public function render(string $append = null): string
    {
        $channel = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new RssChannelEvent($this))->render();

        return parent::render($channel);
    }
}
