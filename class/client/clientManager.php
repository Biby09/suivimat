<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/client/client.php';
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

    public function getAllClients(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE idx_organisation = :organisation ORDER BY cli_lastname, cli_firstname');
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        $clients = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $client = new Client();
            $client->hydrate($ligne);
            $clients[] = $client;
        }

        return $clients;
    }

    public function createClient(string $firstname, string $lastname): Client
    {
        $stmt = $this->pdo->prepare('INSERT INTO clients (cli_firstname, cli_lastname, idx_organisation) VALUES (:firstname, :lastname, :organisation)');
        $stmt->execute(['firstname' => $firstname, 'lastname' => $lastname, 'organisation' => $this->organisation->getId()]);

        return $this->getClientById((int) $this->pdo->lastInsertId());
    }
}