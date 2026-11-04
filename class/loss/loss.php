<?php

/**
 * Perte d'un article (table losses) : date, remarque éventuelle, date à laquelle il a été retrouvé.
 */
class Loss
{
    private int $id;
    private int $articleId;
    private string $date;         // AAAA-MM-JJ
    private ?string $comment;
    private ?string $foundDate;   // null : toujours perdu

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_loss'];
        $this->articleId = (int) $ligne['idx_article'];
        $this->date = $ligne['los_date'];
        $this->comment = $ligne['los_comment'];
        $this->foundDate = $ligne['los_found_date'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getArticleId(): int
    {
        return $this->articleId;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getFoundDate(): ?string
    {
        return $this->foundDate;
    }
}
