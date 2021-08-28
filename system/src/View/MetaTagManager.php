<?php

declare(strict_types=1);

namespace Johncms\View;

class MetaTagManager
{
    protected string $title = '';
    protected ?string $pageTitle = null;
    protected string $description = '';
    protected string $keywords = '';
    protected ?string $canonical = null;

    public function __construct()
    {
        $this->setTitle(config('johncms.meta_title', ''));
        $this->setDescription(config('johncms.meta_description', ''));
        $this->setKeywords(config('johncms.meta_keywords', ''));
    }

    public function __invoke(): self
    {
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPageTitle(): ?string
    {
        return $this->pageTitle;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }

    public function getCanonical(): ?string
    {
        return $this->canonical;
    }

    /**
     * This method sets the following tags: title, pageTitle, description, keywords
     *
     * @param string $value
     * @return $this
     */
    public function setAll(string $value): self
    {
        $this->setTitle($value);
        $this->setPageTitle($value);
        $this->setDescription($value);
        $this->setKeywords($value);
        return $this;
    }

    public function setTitle(string $value): self
    {
        $this->title = $value;
        return $this;
    }

    public function setPageTitle(string $value): self
    {
        $this->pageTitle = $value;
        return $this;
    }

    public function setDescription(string $value): self
    {
        $this->description = $value;
        return $this;
    }

    public function setKeywords(string $value): self
    {
        $this->keywords = $value;
        return $this;
    }

    public function setCanonical(string $value): self
    {
        $this->canonical = $value;
        return $this;
    }
}
