<?php
class Client{
    private $id;
    private $firstname;
    private $lastname;
    private ?string $email; // optionnel
    private $organisationId;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_client'];
        $this->firstname = $ligne['cli_firstname'];
        $this->lastname = $ligne['cli_lastname'];
        $this->email = $ligne['cli_email'] ?? null;
        $this->organisationId = (int) $ligne['idx_organisation'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getFullname(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }
}
