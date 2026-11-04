<?php

/**
 * Dégât constaté au retour d'un prêt (table degats), avec ses photos (table images, colonne idx_degat).
 */
class Damage
{
    private int $id;
    private int $loanId;
    private string $description;
    private string $date;         // AAAA-MM-JJ HH:MM:SS
    private array $imageUrls = [];

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_degat'];
        $this->loanId = (int) $ligne['idx_loan'];
        $this->description = $ligne['deg_description'];
        $this->date = $ligne['deg_date'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLoanId(): int
    {
        return $this->loanId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getImageUrls(): array
    {
        return $this->imageUrls;
    }

    public function addImageUrl(string $url): void
    {
        $this->imageUrls[] = $url;
    }
}
