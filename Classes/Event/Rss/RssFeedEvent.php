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
    protected Registration $registration;
    protected ServerRequestInterface $request;
    protected array $settings;
    protected array $content;
    private ?QueryResultInterface $objects;

    public function __construct(Registration $registration, ServerRequestInterface $request, array $settings, array $content = [], QueryResultInterface $objects = null)
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
        // @extensionScannerIgnoreLine
        $this->content = $content;
        $this->objects = $objects;
    }

    public function getRegistration(): Registration
    {
        return $this->registration;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getContent(): array
    {
        // @extensionScannerIgnoreLine
        return $this->content;
    }

    public function getObjects(): ?QueryResultInterface
    {
        return $this->objects;
    }

    public function render(string $append = null): string
    {
        $channel = GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new RssChannelEvent($this))->render();

        return parent::render($channel);
    }
}
