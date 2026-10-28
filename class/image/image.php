<?php

class Image{

    private $id;
    private $name;
    private $articleId;
    private $degatId;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_image'];
        $this->name = $ligne['im_name'];
        $this->articleId = (int) $ligne['idx_article'];
        $this->degatId = $ligne['idx_degat'] !== null ? (int) $ligne['idx_degat'] : null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArticleId(): int
    {
        return $this->articleId;
    }

    public function getDegatId(): ?int
    {
        return $this->degatId;
    }

    // Les fichiers sont toujours enregistrés en JPEG : /upload/<im_name>.jpg
    public function getUrl(): string
    {
        return '/upload/' . $this->name . '.jpg';
    }
}
