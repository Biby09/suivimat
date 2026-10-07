<?php
class Client{
    private $id;
    private $firstname;
    private $lastname;
    private $organisation;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_client'];
        $this->firstname = $ligne['cli_firstname'];
        $this->lastname = $ligne['cli_lastname'];
        $this->organisation = $organisationManager->getOrganisationById($ligne['idx_organisation']);
    }
}