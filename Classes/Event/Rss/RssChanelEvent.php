<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event\Rss;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pagebased\Domain\Model\AbstractObject;
use Zeroseven\Pagebased\Registration\Registration;

final class RssChanelEvent extends AbstractRssObject
{
    protected ?QueryResultInterface $objects;
    protected int $indentionLevel = 1;

    public function __construct(Registration $registration, ServerRequestInterface $request, array $settings, QueryResultInterface $objects = null)
    {
        $this->tag = GeneralUtility::makeInstance(TagBuilder::class, 'channel');
        $this->registration = $registration;
        $this->request = $request;
        $this->settings = $settings;
        $this->objects = $objects;
    }

    public function getObjects(): ?QueryResultInterface
    {
        return $this->objects;
    }

    public function render(string $prepend = null, string $append = null): string
    {
        $this->setIfEmpty('title', $this->settings['header'] ?? '');
        $this->setIfEmpty('generator', 'TYPO3 (powered by pagebased)');
        $this->setIfEmpty('link', (string)$this->request->getUri()->withQuery(''));
        $this->setIfEmpty('atom:link', null, ['href' => (string)$this->request->getUri()->withQuery(''), 'rel' => 'self', 'type' => 'application/rss+xml']);
        $this->setIfEmpty('pubDate', date('r', $this->settings['crdate'] ?? time()));
        $this->setIfEmpty('lastBuildDate', date('r'));

        if ($this->empty('language') && $siteLanguage = $this->request->getAttribute('language')) {
            $this->set('language', $siteLanguage->getHreflang());
        }

        $items = implode('', array_map(function (AbstractObject $object) {
            return GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new RssItemEvent($this->registration, $this->request, $this->settings, $object))->render();
        }, $this->objects->toArray()));

        return parent::render($prepend, $append . $items);
    }
}
