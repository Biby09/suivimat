<?php
class Client{
    private $id;
    private $firstname;
    private $lastname;
    private $organisationId;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_client'];
        $this->firstname = $ligne['cli_firstname'];
        $this->lastname = $ligne['cli_lastname'];
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

    public function getFullname(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }
}
