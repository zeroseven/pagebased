<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event\Rss;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pagebased\Domain\Model\AbstractObject;
use Zeroseven\Pagebased\Domain\Model\Topic;
use Zeroseven\Pagebased\Registration\Registration;

final class RssItemEvent extends AbstractRssObject
{
    protected AbstractObject $object;

    public function __construct(Registration $registration, ServerRequestInterface $request, array $settings, AbstractObject $object)
    {
        $this->tag = GeneralUtility::makeInstance(TagBuilder::class, 'item');
        $this->registration = $registration;
        $this->request = $request;
        $this->settings = $settings;
        $this->object = $object;
    }

    public function getObject(): AbstractObject
    {
        return $this->object;
    }

    public function render(string $prepend = null, string $append = null): string
    {
        $this->setIfEmpty('guid', md5($this->registration->getIdentifier() . $this->object->getUid()), ['isPermaLink' => 'false']);
        $this->setIfEmpty('title', $this->object->getTitle());
        $this->setIfEmpty('description', $this->object->getDescription());
        $this->setIfEmpty('pubDate', date('r', $this->object->getCreateDate()));
        $this->setIfEmpty('lastBuildDate', date('r', $this->object->getLastChangeDate()));
        $this->setIfEmpty('category', $this->object->getCategory()?->getTitle());
        $this->setIfEmpty('contact', $this->object->getContact()?->getFullName());
        $this->setIfEmpty('topics', implode(', ', array_map(static fn(Topic $topic) => $topic->getTitle(), ($topics = $this->object->getTopics()) === null ? [] : $topics->toArray())));
        $this->setIfEmpty('tags', implode(', ', $this->object->getTags()));

        if ($this->empty('link')) {
            $uri = GeneralUtility::makeInstance(UriBuilder::class)
                ->setCreateAbsoluteUri(true)
                ->setTargetPageUid($this->object->getUid())
                ->build();

            $this->set('link', $uri);
        }

        if ($this->empty('content.encoded') && $content = $this->object->getDescription()) {
            $this->set('content.encoded', '<p>' . nl2br($content) . '</p>', null, true);
        }

        if ($this->empty('enclosure')) {
            $image = null; // Todo!

            $image && $this->set('enclosure', '', [
                'url' => $image->getOriginalResource()->getPublicUrl(),
                'length' => $image->getOriginalResource()->getSize(),
                'type' => $image->getMimeType()
            ]);
        }

        return parent::render($prepend, $append);
    }
}
