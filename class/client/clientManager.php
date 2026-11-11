<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/client.php';

// Levée par ClientManager::setArchived() : on n'archive pas un client qui a encore du matériel
class ClientHasOngoingLoansException extends Exception {}

class ClientManager{

    private PDO $pdo;
    private Organisation $organisation;

    public function __construct(PDO $pdo, Organisation $organisation)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
    }

    public function getClientById(int $id): Client
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE id_client = :id AND idx_organisation = :organisation');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $client = new Client();
            $client->hydrate($ligne);
            return $client;
            
        } else {
            throw new Exception("Client not found with ID: " . $id);
        }
    }

    /**
     * Clients actifs de l'organisation (choix du client d'un prêt ou d'une vente) : les clients archivés ne sont pas renvoyés
     */
    public function getAllClients(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE idx_organisation = :organisation AND cli_archived_at IS NULL ORDER BY cli_lastname, cli_firstname');
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        $clients = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $client = new Client();
            $client->hydrate($ligne);
            $clients[] = $client;
        }

        return $clients;
    }

    /**
     * Clients de l'organisation avec le nombre de leurs prêts (page « Clients »), triés par nom de famille :
     * [['client' => Client, 'total' => tous les prêts, 'ongoing' => en cours, 'late' => en cours et en retard], ...]
     */
    public function getClientsWithLoanCounts(): array
    {
        // LEFT JOIN : les clients sans aucun prêt sont aussi renvoyés (leurs colonnes de prêt valent NULL).
        // COUNT(colonne) ne compte que les valeurs non NULL ; SUM(condition) compte les lignes où la condition est vraie (1).
        // COALESCE(…, 0) : 0 au lieu de NULL quand le client n'a aucun prêt.
        $stmt = $this->pdo->prepare('SELECT clients.*,
                COUNT(loans.id_loan) AS total,
                COALESCE(SUM(loans.lo_end_date IS NULL), 0) AS ongoing,
                COALESCE(SUM(loans.lo_end_date IS NULL AND loans.lo_due_date < CURDATE()), 0) AS late
            FROM clients
            LEFT JOIN loans ON loans.idx_client = clients.id_client
            WHERE clients.idx_organisation = :organisation
            GROUP BY clients.id_client
            ORDER BY clients.cli_lastname, clients.cli_firstname');
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        $rows = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $client = new Client();
            $client->hydrate($ligne);
            $rows[] = ['client' => $client, 'total' => (int) $ligne['total'], 'ongoing' => (int) $ligne['ongoing'], 'late' => (int) $ligne['late']];
        }

        return $rows;
    }

    /**
     * Modifie le prénom, le nom et l'e-mail (null = aucun) d'un client de l'organisation
     */
    public function updateClient(Client $client, string $firstname, string $lastname, ?string $email): Client
    {
        $stmt = $this->pdo->prepare('UPDATE clients SET cli_firstname = :firstname, cli_lastname = :lastname, cli_email = :email WHERE id_client = :id AND idx_organisation = :organisation');
        $stmt->execute(['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'id' => $client->getId(), 'organisation' => $this->organisation->getId()]);

        return $this->getClientById($client->getId());
    }

    /**
     * Archive un client ($archived = true) ou le réactive ($archived = false).
     * Un client qui a encore un prêt en cours ne peut pas être archivé : l'objet doit d'abord être rendu.
     */
    public function setArchived(Client $client, bool $archived): Client
    {
        if ($archived && $this->countOngoingLoans($client) > 0) {
            throw new ClientHasOngoingLoansException('Le client a encore un prêt en cours');
        }

        // NOW() : date et heure actuelles du serveur MySQL ; NULL = client de nouveau actif
        $stmt = $this->pdo->prepare('UPDATE clients SET cli_archived_at = ' . ($archived ? 'NOW()' : 'NULL') . ' WHERE id_client = :id AND idx_organisation = :organisation');
        $stmt->execute(['id' => $client->getId(), 'organisation' => $this->organisation->getId()]);

        return $this->getClientById($client->getId());
    }

    // Nombre de prêts pas encore rendus (sans date de retour) du client
    private function countOngoingLoans(Client $client): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM loans WHERE idx_client = :id AND lo_end_date IS NULL');
        $stmt->execute(['id' => $client->getId()]);

        // fetchColumn() : valeur de la première colonne de la première ligne (ici le COUNT)
        return (int) $stmt->fetchColumn();
    }

    // $email : optionnel (null = aucun)
    public function createClient(string $firstname, string $lastname, ?string $email = null): Client
    {
        $stmt = $this->pdo->prepare('INSERT INTO clients (cli_firstname, cli_lastname, cli_email, idx_organisation) VALUES (:firstname, :lastname, :email, :organisation)');
        $stmt->execute(['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'organisation' => $this->organisation->getId()]);

        return $this->getClientById((int) $this->pdo->lastInsertId());
    }
}