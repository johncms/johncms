<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\ValueObjects;

use Johncms\Modules\Forum\Domain\Enums\HiddenListTab;

/**
 * What the list of the hidden content of the forum shows and what it is narrowed to.
 *
 * The search looks in the name of the author and in the name of the topic. The topic narrows the
 * posts, the section narrows the topics; the author and who hid it narrow both.
 */
final readonly class HiddenListFilter
{
    public function __construct(
        public HiddenListTab $tab = HiddenListTab::POSTS,
        public string $search = '',
        public ?int $author = null,
        public ?int $topic = null,
        public ?int $section = null,
        public ?string $hiddenBy = null,
    ) {
    }

    public function withSearch(string $search): self
    {
        return new self($this->tab, $search, $this->author, $this->topic, $this->section, $this->hiddenBy);
    }

    public function withAuthor(?int $author): self
    {
        return new self($this->tab, $this->search, $author, $this->topic, $this->section, $this->hiddenBy);
    }

    public function withTopic(?int $topic): self
    {
        return new self($this->tab, $this->search, $this->author, $topic, $this->section, $this->hiddenBy);
    }

    public function withSection(?int $section): self
    {
        return new self($this->tab, $this->search, $this->author, $this->topic, $section, $this->hiddenBy);
    }

    public function withHiddenBy(?string $hiddenBy): self
    {
        return new self($this->tab, $this->search, $this->author, $this->topic, $this->section, $hiddenBy);
    }
}
