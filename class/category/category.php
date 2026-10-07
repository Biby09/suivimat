<?php

class Category{

    private $id;
    private $organisationId;
    private $name;
    private $imgName;
    private $parentId;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_category'];
        $this->organisationId = (int) $ligne['idx_organisation'];
        $this->name = $ligne['cat_name'];
        $this->imgName = $ligne['cat_img_name'];
        $this->parentId = $ligne['idx_cat_parent'] !== null ? (int) $ligne['idx_cat_parent'] : null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImgName(): ?string
    {
        return $this->imgName;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }
}
