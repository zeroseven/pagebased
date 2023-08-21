<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Event\Rss;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pagebased\Registration\Registration;

abstract class AbstractRssObject
{
    protected ?TagBuilder $tag = null;
    protected Registration $registration;
    protected ServerRequestInterface $request;
    protected array $settings;
    protected int $indentionLevel = 0;

    /** @var TagBuilder[] */
    protected array $properties = [];

    public function getTag(): ?TagBuilder
    {
        return $this->tag;
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

    public function has(string $tagName): bool
    {
        return isset($this->properties[$tagName]);
    }

    public function empty(string $tagName): bool
    {
        return ($property = $this->get($tagName)) === null
            || (empty($property->getContent()) && empty($property->getAttributes()));
    }

    public function get(string $tagName): ?TagBuilder
    {
        return $this->properties[$tagName] ?? null;
    }

    public function set(string $tagName, string $value = null, array $attributes = null, bool $cdata = null): self
    {
        $content = $value ? ($cdata ? '<![CDATA[' . $value . ']]>' : htmlspecialchars(strip_tags(trim($value)))) : $value;
        $this->properties[$tagName] = GeneralUtility::makeInstance(TagBuilder::class, $tagName, $content);

        if ($attributes) {
            $this->properties[$tagName]->addAttributes($attributes);
        }

        return $this;
    }

    public function setIfEmpty(string $tagName, string $value = null, array $attributes = null, bool $cdata = null): self
    {
        $this->empty($tagName) && $this->set($tagName, $value, $attributes, $cdata);

        return $this;
    }

    public function delete(string $tagName): self
    {
        if ($this->has($tagName)) {
            unset($this->properties[$tagName]);
        }

        return $this;
    }

    public function render(string $prepend = null, string $append = null): string
    {
        $properties = '';

        if ($this->tag) {
            $indention = "\t";
            $newLinePrefix = "\n" . str_repeat($indention, $this->indentionLevel);

            foreach ($this->properties as $property) {
                if (!$this->empty($property->getTagName())) {
                    $properties .= $newLinePrefix . $indention . trim($property->render());
                }
            }

            $this->tag->setContent($prepend . $properties . $append . $newLinePrefix);

            return $newLinePrefix . $this->tag->render();
        }

        return $properties;
    }
}
