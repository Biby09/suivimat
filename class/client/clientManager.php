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
}