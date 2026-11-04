<?php

/**
 * Vente d'un article à un client (table sales). Le nom de l'acheteur est lu avec la vente (jointure).
 */
class Sale
{
    private int $id;
    private int $articleId;
    private int $clientId;
    private string $clientName;
    private string $date;            // AAAA-MM-JJ
    private ?string $cancelledAt;    // date d'annulation (« Remettre disponible ») ; null = vente valable

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_sale'];
        $this->articleId = (int) $ligne['idx_article'];
        $this->clientId = (int) $ligne['idx_client'];
        $this->clientName = trim(($ligne['cli_firstname'] ?? '') . ' ' . ($ligne['cli_lastname'] ?? ''));
        $this->date = $ligne['sa_date'];
        $this->cancelledAt = $ligne['sa_cancelled_at'] ?? null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getArticleId(): int
    {
        return $this->articleId;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getCancelledAt(): ?string
    {
        return $this->cancelledAt;
    }
}
