<?php

class Invitation{

    private $id;
    private $user;
    private $organisation;
    private $token;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_invitation'];
        $this->user = $userManager->getUserById($ligne['idx_user']);
        $this->organisation = $organisationManager->getOrganisationById($ligne['idx_organisation']);
        $this->token = $ligne['inv_token'];
    }
}