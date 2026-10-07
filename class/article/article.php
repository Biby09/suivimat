<?php

require_once $_SERVER['DOCUMENT_ROOT'] .  '/config/init.php';
class Article{

    private $id;
    private $organisation;
    private $category;
    private $name;
    private $insert_date;
    private $code;
    private $status;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_article'];
        $this->organisation = $organisationManager->getOrganisationById($ligne['idx_organisation']);
        $this->category = $categoryManager->getCategoryById($ligne['idx_category']);
        $this->name = $ligne['art_name'];
        $this->insert_date = $ligne['art_insert_date'];
        $this->code = $ligne['art_code'];
        $this->status = $ligne['art_status'];
    }
}