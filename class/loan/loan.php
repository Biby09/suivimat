<?php

/**
 * Prêt d'un article à un client (table loans).
 * Le nom du client est lu avec le prêt (jointure), les dégâts sont ajoutés par LoanManager.
 */
class Loan
{
    private int $id;
    private int $articleId;
    private int $clientId;
    private string $clientName;
    private string $startDate;   // AAAA-MM-JJ
    private ?string $dueDate;    // retour prévu ; null : date indéterminée
    private ?string $endDate;    // retour réel ; null : prêt en cours
    private array $damages = []; // Damage[]

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_loan'];
        $this->articleId = (int) $ligne['idx_article'];
        $this->clientId = (int) $ligne['idx_client'];
        $this->clientName = trim(($ligne['cli_firstname'] ?? '') . ' ' . ($ligne['cli_lastname'] ?? ''));
        $this->startDate = $ligne['lo_start_date'];
        $this->dueDate = $ligne['lo_due_date'];
        $this->endDate = $ligne['lo_end_date'];
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

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getDueDate(): ?string
    {
        return $this->dueDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    // Pas encore de date de retour : l'objet est chez le client
    public function isOngoing(): bool
    {
        return $this->endDate === null;
    }

    // En cours et date de retour prévue dépassée (les dates AAAA-MM-JJ se comparent comme du texte)
    public function isLate(): bool
    {
        return $this->isOngoing() && $this->dueDate !== null && $this->dueDate < date('Y-m-d');
    }

    public function getDamages(): array
    {
        return $this->damages;
    }

    public function setDamages(array $damages): void
    {
        $this->damages = $damages;
    }
}
