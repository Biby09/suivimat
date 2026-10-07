<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loan.php';
class LoanManager{
    
    private PDO $pdo;
    private Organisation $organisation;

    public function __construct(PDO $pdo, Organisation $organisation)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
    }

    public function getLoanById(int $id): Loan
    {
        $stmt = $this->pdo->prepare('SELECT * FROM loans WHERE id_loan = :id AND idx_organisation = :organisation');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $loan = new Loan();
            $loan->hydrate($ligne);
            return $loan;
            
        } else {
            throw new Exception("Loan not found with ID: " . $id);
        }
    }
}