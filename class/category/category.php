<?php

class Category{

    private $id;
    private $organisation;
    private $name;
    private $imgName;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_category'];
        $this->organisation = $organisationManager->getOrganisationById($ligne['idx_organisation']);
        $this->name = $ligne['cat_name'];
        $this->imgName = $ligne['cat_img_name'];
    }
}