<?php
declare(strict_types=1);

// One news / event row.
final class News
{
    public function __construct(
        private int $id,
        private string $title,
        private string $category,
        private string $description,
        private string $publicationDate, // 'Y-m-d'
        private ?string $image
    ) {}

    public function getId(): int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getCategory(): string { return $this->category; }
    public function getDescription(): string { return $this->description; }
    public function getPublicationDate(): string { return $this->publicationDate; }
    public function getImage(): ?string { return $this->image; }
}
