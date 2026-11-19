<?php
declare(strict_types=1);

final class News
{
    public function __construct(
        private int $id,
        private string $title,
        private string $category,
        private string $description,
        private string $publicationDate,
        private ?string $image,
        private ?string $authorName
    ) {}

    public function getId(): int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getCategory(): string { return $this->category; }
    public function getDescription(): string { return $this->description; }
    public function getPublicationDate(): string { return $this->publicationDate; }
    public function getImage(): ?string { return $this->image; }
    public function getAuthorName(): ?string { return $this->authorName; }
}
