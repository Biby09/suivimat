<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisation.php';
class OrganisationManager{

    private PDO $pdo;
    private User $user;

    public function __construct(PDO $pdo, User $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function getOrganisationById(int $id): Organisation
    {
        $stmt = $this->pdo->prepare('SELECT id_organisation, org_name FROM organisations JOIN users_organisations ON organisations.id_organisation = users_organisations.idx_organisation WHERE organisations.id_organisation = :id AND users_organisations.idx_user = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $this->user->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $organisation = new Organisation();
            $organisation->hydrate($ligne);
            return $organisation;
            
        } else {
            throw new Exception("Organisation not found with ID: " . $id);
        }
    }
}