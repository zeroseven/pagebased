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
    private ?QueryResultInterface $objects;

    public function __construct(Registration $registration, ServerRequestInterface $request, array $settings, QueryResultInterface $objects = null)
    {
        $this->tag = GeneralUtility::makeInstance(TagBuilder::class, 'rss');
        $this->tag->addAttributes([
            'version' => '2.0',
            'xmlns:content' => 'http://purl.org/rss/1.0/modules/content/',
            'xmlns:atom' => 'http://www.w3.org/2005/Atom'
        ]);

        $this->registration = $registration;
        $this->request = $request;
        $this->settings = $settings;
        $this->objects = $objects;
    }

    public function getObjects(): ?QueryResultInterface
    {
        return $this->objects;
    }

    public function render(string $append = null): string
    {
        $channel = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new RssChanelEvent($this->registration, $this->request, $this->settings, $this->objects))->render();

        return parent::render($channel);
    }
}
