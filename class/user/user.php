<?php

class User{
   
    private $id;
    private $firstname;
    private $lastname;
    private $email;
    private $passwordHash;
    
    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['user_id'];
        $this->firstname = $ligne['usr_firstname'];
        $this->lastname = $ligne['usr_lastname'];
        $this->email = $ligne['usr_email'];
        $this->passwordHash = $ligne['usr_password'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getFullname(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = (string) $firstname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = (string) $lastname;
    }

    public function setEmail(string $email): void
    {
        $this->email = (string) $email;
    }

    public function setPassword(string $password): bool
    {

        // Controle de la complexité du mot de passe
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins une lettre majuscule.');
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins une lettre minuscule.');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins un chiffre.');
        }
        if (!preg_match('/[\W_]/', $password)) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins un caractère spécial.');
        }

        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
        return true;
    }

    public function passwordVerify(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}