<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event\Rss;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pagebased\Domain\Model\AbstractObject;

final class RssChannelEvent extends AbstractRssObject
{
    protected int $indentionLevel = 1;

    public function __construct(private readonly RssFeedEvent $rssFeedEvent, private readonly EventDispatcher $eventDispatcher)
    {
        $this->tag = GeneralUtility::makeInstance(TagBuilder::class, 'channel');
    }

    public function getFeed(): RssFeedEvent
    {
        return $this->rssFeedEvent;
    }

    public function render(string $append = null): string
    {
        $this->setIfEmpty('title', $this->rssFeedEvent->getContent()['header'] ?? '');
        $this->setIfEmpty('generator', 'TYPO3 (powered by pagebased)');
        $this->setIfEmpty('link', (string)$this->rssFeedEvent->getRequest()->getUri()->withQuery(''));
        $this->setIfEmpty('atom:link', null, ['href' => (string)$this->rssFeedEvent->getRequest()->getUri()->withQuery(''), 'rel' => 'self', 'type' => 'application/rss+xml']);
        $this->setIfEmpty('pubDate', date('r', $this->rssFeedEvent->getContent()['crdate'] ?? time()));
        $this->setIfEmpty('lastBuildDate', date('r'));

        if ($this->empty('language') && $siteLanguage = $this->rssFeedEvent->getRequest()->getAttribute('language')) {
            $this->set('language', $siteLanguage->getHreflang());
        }

        $items = ($objects = $this->rssFeedEvent->getObjects()) instanceof QueryResultInterface ? implode('', array_map(fn(AbstractObject $object) => $this->eventDispatcher->dispatch(new RssItemEvent($this, $object))->render(), $objects->toArray())) : '';

        return parent::render($append . $items);
    }
}
