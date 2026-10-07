<?php

class Loan{

    private $id;
    private $article;
    private $client;
    private $start_date;
    private $end_date;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_loan'];
        $this->article = $articleManager->getArticleById($ligne['idx_article']);
        $this->client = $clientManager->getClientById($ligne['idx_client']);
        $this->start_date = $ligne['loan_start_date'];
        $this->end_date = $ligne['loan_end_date'];
    }
}