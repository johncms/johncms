<?php

declare(strict_types=1);

namespace Johncms\View;

class MetaTagManager
{
    protected string $title = '';
    protected string $pageTitle = '';
    protected string $description = '';
    protected string $keywords = '';
    protected string $canonical = '';

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
        return htmlspecialchars($this->title);
    }

    public function getDescription(): string
    {
        return htmlspecialchars($this->description);
    }

    public function getPageTitle(): ?string
    {
        return htmlspecialchars($this->pageTitle);
    }

    public function getKeywords(): string
    {
        return htmlspecialchars($this->keywords);
    }

    public function getCanonical(): string
    {
        return htmlspecialchars($this->canonical);
    }

    /**
     * This method sets the following tags: title, pageTitle, description, keywords
     *
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
