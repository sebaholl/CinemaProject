<?php
declare(strict_types=1);

// One movie row. Plain value object: constructor takes the data, getters read it.
final class Movie
{
    public function __construct(
        private int $id,
        private string $title,
        private string $genre,
        private string $description,
        private int $durationMinutes,
        private ?string $coverImage,
        private ?string $backdropImage = null,
        private bool $isFeatured = false
    ) {}

    public function getId(): int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getGenre(): string { return $this->genre; }
    public function getDescription(): string { return $this->description; }
    public function getDurationMinutes(): int { return $this->durationMinutes; }
    public function getCoverImage(): ?string { return $this->coverImage; }
    public function getBackdropImage(): ?string { return $this->backdropImage; }
    public function isFeatured(): bool { return $this->isFeatured; }
}
