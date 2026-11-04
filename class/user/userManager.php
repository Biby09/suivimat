<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user/user.php';

// Levée par updateUser() quand l'e-mail appartient déjà à un autre compte
class EmailAlreadyUsedException extends Exception
{
}

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

    /**
     * Adresses e-mail des membres d'une organisation (ex. message de la personne qui a trouvé un objet perdu)
     */
    public function getEmailsOfOrganisation(int $organisationId): array
    {
        $stmt = $this->pdo->prepare('SELECT users.usr_email FROM users
            JOIN users_organisations ON users_organisations.idx_user = users.id_user
            WHERE users_organisations.idx_organisation = :organisation');
        $stmt->execute(['organisation' => $organisationId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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

    /**
     * Enregistre le prénom, le nom et l'e-mail d'un utilisateur.
     * L'e-mail est unique (contrainte usr_email UNIQUE) : EmailAlreadyUsedException s'il appartient déjà à un autre compte.
     */
    public function updateUser(User $user): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET usr_firstname = :firstname, usr_lastname = :lastname, usr_email = :email WHERE id_user = :id');
        try {
            $stmt->execute([
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'id' => $user->getId()
            ]);
        } catch (PDOException $exception) {
            // 1062 : code MySQL « valeur en double » pour une colonne UNIQUE
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new EmailAlreadyUsedException('Email already used: ' . $user->getEmail());
            }
            throw $exception;
        }
    }

    /**
     * Change le mot de passe après vérification du mot de passe actuel.
     * Lève une Exception si le mot de passe actuel est faux, une InvalidArgumentException (message affichable)
     * si le nouveau ne respecte pas les règles de User::setPassword() (8 caractères, majuscule, minuscule, chiffre, caractère spécial).
     */
    public function updateUserPassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$user->passwordVerify($currentPassword)) {
            throw new Exception("Current password is incorrect");
        }

        // Mêmes règles qu'à l'inscription ; calcule aussi l'empreinte (hachage) du nouveau mot de passe
        $user->setPassword($newPassword);

        $stmt = $this->pdo->prepare('UPDATE users SET usr_password = :password WHERE id_user = :id');
        $stmt->execute([
            'password' => $user->getPasswordHash(),
            'id' => $user->getId()
        ]);
    }

    /**
     * Noms des organisations dont l'utilisateur est le SEUL membre : il ne peut pas supprimer son compte avant
     * de les avoir confiées à quelqu'un d'autre (sinon personne ne pourrait plus gérer leur matériel).
     */
    public function getSoleMemberOrganisationNames(User $user): array
    {
        // Sous-requête : nombre de membres de chaque organisation de l'utilisateur ; on garde celles qui n'en ont qu'un
        $stmt = $this->pdo->prepare('SELECT organisations.org_name FROM organisations
            JOIN users_organisations ON users_organisations.idx_organisation = organisations.id_organisation
            WHERE users_organisations.idx_user = :user
            AND (SELECT COUNT(*) FROM users_organisations AS members WHERE members.idx_organisation = organisations.id_organisation) = 1
            ORDER BY organisations.org_name');
        $stmt->execute(['user' => $user->getId()]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Supprime le compte : invitations, appartenances aux organisations, puis l'utilisateur. Tout ou rien.
     * À appeler seulement si getSoleMemberOrganisationNames() est vide (les organisations restent à leurs autres membres).
     */
    public function deleteUser(User $user): void
    {
        $this->pdo->beginTransaction();

        try {
            // Ordre imposé par les clés étrangères : d'abord ce qui fait référence à l'utilisateur
            $this->pdo->prepare('DELETE FROM invitations WHERE idx_user = :user')->execute(['user' => $user->getId()]);
            $this->pdo->prepare('DELETE FROM users_organisations WHERE idx_user = :user')->execute(['user' => $user->getId()]);
            $this->pdo->prepare('DELETE FROM users WHERE id_user = :user')->execute(['user' => $user->getId()]);

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
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

        $user->hydrate(['id_user' => (int)$this->pdo->lastInsertId(), 'usr_firstname' => $user->getFirstname(), 'usr_lastname' => $user->getLastname(), 'usr_email' => $user->getEmail(), 'usr_password' => $user->getPasswordHash()]);
        return $user;
    }
}