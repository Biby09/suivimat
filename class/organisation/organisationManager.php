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

    public function getAllOrganisationsForUser(): array
    {
        $stmt = $this->pdo->prepare('SELECT id_organisation, org_name FROM organisations JOIN users_organisations ON organisations.id_organisation = users_organisations.idx_organisation WHERE users_organisations.idx_user = :user_id ORDER BY org_last_open DESC');
        $stmt->execute(['user_id' => $this->user->getId()]);
        $organisations = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $organisation = new Organisation();
            $organisation->hydrate($ligne);
            $organisations[] = $organisation;
        }

        return $organisations;
    }

    // Date de dernière ouverture : les organisations récentes apparaissent en premier sur le tableau de bord
    public function markAsOpened(Organisation $organisation): void
    {
        $stmt = $this->pdo->prepare('UPDATE organisations SET org_last_open = CURRENT_TIMESTAMP WHERE id_organisation = :id');
        $stmt->execute(['id' => $organisation->getId()]);
    }

    public function createOrganisation(string $name): Organisation
    {
        // Création de l'organisation et ajout de l'utilisateur comme membre : tout ou rien
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('INSERT INTO organisations (org_name) VALUES (:name)');
            $stmt->execute(['name' => $name]);
            $organisationId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('INSERT INTO users_organisations (idx_user, idx_organisation) VALUES (:user_id, :organisation_id)');
            $stmt->execute(['user_id' => $this->user->getId(), 'organisation_id' => $organisationId]);

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        $organisation = new Organisation();
        $organisation->hydrate(['id_organisation' => $organisationId, 'org_name' => $name]);
        return $organisation;
    }
}