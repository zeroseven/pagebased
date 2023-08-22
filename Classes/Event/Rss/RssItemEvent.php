<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event\Rss;

use InvalidArgumentException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pagebased\Domain\Model\AbstractObject;
use Zeroseven\Pagebased\Domain\Model\Topic;

final class RssItemEvent extends AbstractRssObject
{
    protected int $indentionLevel = 2;
    protected RssFeedEvent $feed;
    protected RssChannelEvent $channel;
    protected AbstractObject $object;

    public function __construct(RssChannelEvent $channel, AbstractObject $object)
    {
        $this->tag = GeneralUtility::makeInstance(TagBuilder::class, 'item');
        $this->feed = $channel->getFeed();
        $this->channel = $channel;
        $this->object = $object;
    }

    public function getFeed(): RssFeedEvent
    {
        return $this->feed;
    }

    public function getChannel(): RssChannelEvent
    {
        return $this->channel;
    }

    public function getObject(): AbstractObject
    {
        return $this->object;
    }

    public function render(string $append = null): string
    {
        $this->setIfEmpty('guid', md5($this->feed->getRegistration()->getIdentifier() . $this->object->getUid()), ['isPermaLink' => 'false']);
        $this->setIfEmpty('title', $this->object->getTitle());
        $this->setIfEmpty('description', $this->object->getDescription());
        $this->setIfEmpty('pubDate', date('r', $this->object->getCreateDate()));
        $this->setIfEmpty('lastBuildDate', date('r', $this->object->getLastChangeDate()));
        $this->setIfEmpty('category', $this->object->getCategory()?->getTitle());
        $this->setIfEmpty('author', $this->object->getContact()?->getFullName());
        $this->setIfEmpty('topics', implode(', ', array_map(static fn(Topic $topic) => $topic->getTitle(), ($topics = $this->object->getTopics()) === null ? [] : $topics->toArray())));
        $this->setIfEmpty('tags', implode(', ', $this->object->getTags()));

        if ($this->empty('link')) {
            $uri = GeneralUtility::makeInstance(UriBuilder::class)
                ->setCreateAbsoluteUri(true)
                ->setTargetPageUid($this->object->getUid())
                ->build();

            $uri && $this->set('link', $uri);
        }

        if ($this->empty('content.encoded') && $content = $this->object->getDescription()) {
            $this->set('content.encoded', '<p>' . nl2br($content) . '</p>', null, true);
        }

        if ($this->empty('enclosure') && $image = $this->object->getFirstImage()) {
            $imageService = GeneralUtility::makeInstance(ImageService::class);
            $processedImage = $imageService->applyProcessingInstructions($image, [
                'maxWidth' => 1680,
                'maxHeight' => 1680,
                'extension' => 'web',
                'absolute' => true,
            ]);

            try {
                $processedImage && $this->set('enclosure', '', [
                    'url' => $imageService->getImageUri($processedImage, true),
                    'length' => $processedImage->getSize(),
                    'type' => $processedImage->getMimeType()
                ]);
            } catch (InvalidArgumentException $e) {
            }
        }

        return parent::render($append);
    }
}
