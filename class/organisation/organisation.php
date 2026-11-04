<?php

/**
 * Organisation (table organisations) et ses réglages (page dashboard/organisation/settings/).
 * Les réglages ont une valeur par défaut si la ligne lue ne les contient pas (ex. organisation tout juste créée).
 */
class Organisation
{
    private int $id;
    private string $name;
    private bool $lostPageEnabled;     // page publique « objet perdu » quand quelqu'un scanne un objet perdu
    private ?string $lostMessage;      // message affiché sur cette page (ex. où déposer l'objet)
    private ?int $defaultLoanDays;     // durée de prêt par défaut en jours ; null = indéterminée
    private bool $remindersEnabled;    // rappel par e-mail au client 2 jours avant l'échéance
    private ?string $contractTerms;    // conditions imprimées sur le contrat de prêt

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_organisation'];
        $this->name = $ligne['org_name'];
        // (bool) "1" → true ; ?? : valeur par défaut si la colonne n'a pas été lue
        $this->lostPageEnabled = (bool) ($ligne['org_lost_page_enabled'] ?? true);
        $this->lostMessage = $ligne['org_lost_message'] ?? null;
        $this->defaultLoanDays = isset($ligne['org_default_loan_days']) ? (int) $ligne['org_default_loan_days'] : null;
        $this->remindersEnabled = (bool) ($ligne['org_reminders_enabled'] ?? false);
        $this->contractTerms = $ligne['org_contract_terms'] ?? null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isLostPageEnabled(): bool
    {
        return $this->lostPageEnabled;
    }

    public function getLostMessage(): ?string
    {
        return $this->lostMessage;
    }

    public function getDefaultLoanDays(): ?int
    {
        return $this->defaultLoanDays;
    }

    public function areRemindersEnabled(): bool
    {
        return $this->remindersEnabled;
    }

    public function getContractTerms(): ?string
    {
        return $this->contractTerms;
    }
}
