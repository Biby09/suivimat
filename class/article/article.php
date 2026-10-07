<?php

class Article{

    private $id;
    private $organisationId;
    private $categoryId;
    private $name;
    private $insert_date;
    private $code;
    private $status;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_article'];
        $this->organisationId = (int) $ligne['idx_organisation'];
        $this->categoryId = $ligne['idx_category'] !== null ? (int) $ligne['idx_category'] : null;
        $this->name = $ligne['art_name'];
        $this->insert_date = $ligne['art_insert_date'];
        $this->code = $ligne['art_code'];
        $this->status = $ligne['art_status'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getInsertDate(): ?string
    {
        return $this->insert_date;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
