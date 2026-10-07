<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user/user.php';

class UserManager{

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUserById(int $id): User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id_user = :id');
        $stmt->execute(['id' => $id]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $user = new User();
            $user->hydrate($ligne);
            return $user;
            
        } else {
            throw new Exception("User not found with ID: " . $id);
        }
    }

    public function getUserByEmail(string $email): User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE usr_email = :email');
        $stmt->execute(['email' => $email]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $user = new User();
            $user->hydrate($ligne);
            return $user;
            
        } else {
            throw new Exception("User not found with email: " . $email);
        }
    }

    public function updateUser(User $user): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET usr_firstname = :firstname, usr_lastname = :lastname, usr_email = :email WHERE id_user = :id');
        $stmt->execute([
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'id' => $user->getId()
        ]);
    }

    public function updateUserPassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$user->passwordVerify($currentPassword)) {
            throw new Exception("Current password is incorrect");
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE users SET usr_password = :password WHERE id_user = :id');
        $stmt->execute([
            'password' => $passwordHash,
            'id' => $user->getId()
        ]);
    }

    public function createUser(User $user): User
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (usr_firstname, usr_lastname, usr_email, usr_password) VALUES (:firstname, :lastname, :email, :password)');
        $stmt->execute([
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'password' => $user->getPasswordHash()
        ]);

        $user->hydrate(['user_id' => (int)$this->pdo->lastInsertId(), 'usr_firstname' => $user->getFirstname(), 'usr_lastname' => $user->getLastname(), 'usr_email' => $user->getEmail(), 'usr_password' => $user->getPasswordHash()]);
        return $user;
    }
}