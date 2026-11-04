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
    private string $clientSortName; // « Nom Prénom » : tri par nom de famille
    private ?string $articleName;  // nom et code de l'objet prêté (lus avec le prêt par LoanManager)
    private ?string $articleCode;
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
        $this->clientSortName = trim(($ligne['cli_lastname'] ?? '') . ' ' . ($ligne['cli_firstname'] ?? ''));
        $this->articleName = $ligne['art_name'] ?? null;
        $this->articleCode = $ligne['art_code'] ?? null;
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

    public function getClientSortName(): string
    {
        return $this->clientSortName;
    }

    public function getArticleName(): ?string
    {
        return $this->articleName;
    }

    public function getArticleCode(): ?string
    {
        return $this->articleCode;
    }

    /**
     * Nombre de jours entre aujourd'hui et le retour prévu : négatif = en retard, 0 = aujourd'hui, null = sans date.
     */
    public function getDaysUntilDue(): ?int
    {
        if ($this->dueDate === null) {
            return null;
        }
        // diff() renvoie un DateInterval : days = nombre de jours (toujours positif), invert = 1 si la date est passée
        $interval = (new DateTime('today'))->diff(new DateTime($this->dueDate));
        return $interval->invert ? -$interval->days : $interval->days;
    }

    /**
     * Échéance d'un prêt en cours, pour l'affichage (includes/loan-card.php) et les filtres :
     * ['category' => late | soon (7 jours) | later | none, 'label' => texte du badge, 'class' => couleur Bootstrap]
     */
    public function getDueStatus(): array
    {
        $days = $this->getDaysUntilDue();
        // match (true) : la première ligne dont la condition est vraie donne le résultat
        return match (true) {
            $days === null => ['category' => 'none', 'label' => 'Sans date de retour', 'class' => 'text-bg-secondary'],
            $days < 0 => ['category' => 'late', 'label' => 'En retard de ' . -$days . ' j', 'class' => 'text-bg-danger'],
            $days === 0 => ['category' => 'soon', 'label' => 'À rendre aujourd\'hui', 'class' => 'text-bg-warning'],
            $days === 1 => ['category' => 'soon', 'label' => 'À rendre demain', 'class' => 'text-bg-warning'],
            $days <= 7 => ['category' => 'soon', 'label' => 'Dans ' . $days . ' j', 'class' => 'text-bg-warning'],
            default => ['category' => 'later', 'label' => 'Dans ' . $days . ' j', 'class' => 'text-bg-light border'],
        };
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
