<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event\Rss;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pagebased\Domain\Model\AbstractObject;

final class RssChanelEvent extends AbstractRssObject
{
    protected int $indentionLevel = 1;
    protected RssFeedEvent $feed;

    public function __construct(RssFeedEvent $feed)
    {
        $this->tag = GeneralUtility::makeInstance(TagBuilder::class, 'channel');
        $this->feed = $feed;
    }

    public function getFeed(): RssFeedEvent
    {
        return $this->feed;
    }

    public function render(string $append = null): string
    {
        $this->setIfEmpty('title', $this->feed->getContent()['header'] ?? '');
        $this->setIfEmpty('generator', 'TYPO3 (powered by pagebased)');
        $this->setIfEmpty('link', (string)$this->feed->getRequest()->getUri()->withQuery(''));
        $this->setIfEmpty('atom:link', null, ['href' => (string)$this->feed->getRequest()->getUri()->withQuery(''), 'rel' => 'self', 'type' => 'application/rss+xml']);
        $this->setIfEmpty('pubDate', date('r', $this->feed->getSettings()['crdate'] ?? time()));
        $this->setIfEmpty('lastBuildDate', date('r'));

        if ($this->empty('language') && $siteLanguage = $this->feed->getRequest()->getAttribute('language')) {
            $this->set('language', $siteLanguage->getHreflang());
        }

        $items = ($objects = $this->feed->getObjects()) === null ? '' : implode('', array_map(function (AbstractObject $object) {
            return GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new RssItemEvent($this, $object))->render();
        }, $objects->toArray()));

        return parent::render($append . $items);
    }
}
