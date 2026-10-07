<?php

class Organisation{

    private $id;
    private $name;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_organisation'];
        $this->name = $ligne['org_name'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}