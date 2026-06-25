<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event\Rss;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pagebased\Domain\Model\AbstractObject;
use Zeroseven\Pagebased\Domain\Model\Topic;

final class RssItemEvent extends AbstractRssObject
{
    protected int $indentionLevel = 2;

    private readonly RssFeedEvent $rssFeedEvent;

    private readonly RssChannelEvent $rssChannelEvent;

    public function __construct(RssChannelEvent $rssChannelEvent, private readonly AbstractObject $object, private readonly ImageService $imageService)
    {
        $this->tag = GeneralUtility::makeInstance(TagBuilder::class, 'item');
        $this->rssFeedEvent = $rssChannelEvent->getFeed();
        $this->rssChannelEvent = $rssChannelEvent;
    }

    public function getFeed(): RssFeedEvent
    {
        return $this->rssFeedEvent;
    }

    public function getChannel(): RssChannelEvent
    {
        return $this->rssChannelEvent;
    }

    public function getObject(): AbstractObject
    {
        return $this->object;
    }

    public function render(string $append = null): string
    {
        $this->setIfEmpty('guid', md5($this->rssFeedEvent->getRegistration()->getIdentifier() . $this->object->getUid()), ['isPermaLink' => 'false']);
        $this->setIfEmpty('title', $this->object->getTitle());
        $this->setIfEmpty('description', $this->object->getAbstract() ?: $this->object->getDescription());
        $this->setIfEmpty('pubDate', $this->object->getCreateDate()?->format('r') ?? date('r'));
        $this->setIfEmpty('lastBuildDate', $this->object->getLastChangeDate()?->format('r') ?? date('r'));
        $this->setIfEmpty('category', $this->object->getCategory()?->getTitle());
        $this->setIfEmpty('author', $this->object->getContact()?->getFullName());
        $this->setIfEmpty('topics', implode(', ', array_map(static fn(Topic $topic): string => $topic->getTitle(), ($topics = $this->object->getTopics()) instanceof ObjectStorage ? $topics->toArray() : [])));
        $this->setIfEmpty('tags', implode(', ', $this->object->getTags()));

        if ($this->empty('link')) {
            $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($this->rssFeedEvent->getRequest());

            $uri = $contentObjectRenderer->createUrl([
                'parameter' => $this->object->getUid(),
                'forceAbsoluteUrl' => true,
            ]);

            $uri && $this->set('link', $uri) instanceof AbstractRssObject;
        }

        if ($this->empty('content.encoded') && $content = ($this->object->getAbstract() ?: $this->object->getDescription())) {
            $this->set('content.encoded', '<p>' . nl2br($content) . '</p>', null, true);
        }

        if ($this->empty('enclosure') && $image = $this->object->getFirstImage()) {
            $imageService = $this->imageService;
            $processedImage = $imageService->applyProcessingInstructions($image, [
                'maxWidth' => 1680,
                'maxHeight' => 1680,
                'extension' => 'web',
                'absolute' => true,
            ]);

            try {
                $this->set('enclosure', '', [
                    'url' => $imageService->getImageUri($processedImage, true),
                    'length' => $processedImage->getSize(),
                    'type' => $processedImage->getMimeType(),
                ]);
            } catch (\InvalidArgumentException) {
            }
        }

        return parent::render($append);
    }
}
