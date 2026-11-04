<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisation.php';
class OrganisationManager{

    private PDO $pdo;
    private User $user;

    public function __construct(PDO $pdo, User $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function getOrganisationById(int $id): Organisation
    {
        $stmt = $this->pdo->prepare('SELECT organisations.* FROM organisations JOIN users_organisations ON organisations.id_organisation = users_organisations.idx_organisation WHERE organisations.id_organisation = :id AND users_organisations.idx_user = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $this->user->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $organisation = new Organisation();
            $organisation->hydrate($ligne);
            return $organisation;
            
        } else {
            throw new Exception("Organisation not found with ID: " . $id);
        }
    }

    /**
     * Organisation à laquelle appartient l'article de ce code, SANS vérifier que l'utilisateur en est membre
     * (null si le code n'existe pas). Pour les codes QR scannés par n'importe qui (fiche d'un objet perdu).
     * static : s'appelle sans utilisateur connecté, OrganisationManager::findByArticleCode($pdo, $code)
     */
    public static function findByArticleCode(PDO $pdo, string $code): ?Organisation
    {
        $stmt = $pdo->prepare('SELECT organisations.* FROM organisations
            JOIN articles ON articles.idx_organisation = organisations.id_organisation
            WHERE articles.art_code = :code');
        $stmt->execute(['code' => $code]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            return null;
        }
        $organisation = new Organisation();
        $organisation->hydrate($ligne);
        return $organisation;
    }

    /**
     * Organisation à laquelle appartient un client, seulement si l'utilisateur en est membre (exception sinon)
     */
    public function getOrganisationOfClient(int $clientId): Organisation
    {
        $stmt = $this->pdo->prepare('SELECT organisations.* FROM organisations
            JOIN clients ON clients.idx_organisation = organisations.id_organisation
            JOIN users_organisations ON users_organisations.idx_organisation = organisations.id_organisation
            WHERE clients.id_client = :client AND users_organisations.idx_user = :user_id');
        $stmt->execute(['client' => $clientId, 'user_id' => $this->user->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception("Organisation not found for client ID: " . $clientId);
        }

        $organisation = new Organisation();
        $organisation->hydrate($ligne);
        return $organisation;
    }

    /**
     * Organisation à laquelle appartient un article, seulement si l'utilisateur en est membre
     * (sinon exception : on ne révèle pas l'existence d'un article d'une autre organisation).
     */
    public function getOrganisationOfArticle(int $articleId): Organisation
    {
        $stmt = $this->pdo->prepare('SELECT organisations.* FROM organisations
            JOIN articles ON articles.idx_organisation = organisations.id_organisation
            JOIN users_organisations ON users_organisations.idx_organisation = organisations.id_organisation
            WHERE articles.id_article = :article AND users_organisations.idx_user = :user_id');
        $stmt->execute(['article' => $articleId, 'user_id' => $this->user->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception("Organisation not found for article ID: " . $articleId);
        }

        $organisation = new Organisation();
        $organisation->hydrate($ligne);
        return $organisation;
    }

    public function getAllOrganisationsForUser(): array
    {
        $stmt = $this->pdo->prepare('SELECT organisations.* FROM organisations JOIN users_organisations ON organisations.id_organisation = users_organisations.idx_organisation WHERE users_organisations.idx_user = :user_id ORDER BY org_last_open DESC');
        $stmt->execute(['user_id' => $this->user->getId()]);
        $organisations = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $organisation = new Organisation();
            $organisation->hydrate($ligne);
            $organisations[] = $organisation;
        }

        return $organisations;
    }

    // Date de dernière ouverture : les organisations récentes apparaissent en premier sur le tableau de bord
    public function markAsOpened(Organisation $organisation): void
    {
        $stmt = $this->pdo->prepare('UPDATE organisations SET org_last_open = CURRENT_TIMESTAMP WHERE id_organisation = :id');
        $stmt->execute(['id' => $organisation->getId()]);
    }

    public function createOrganisation(string $name): Organisation
    {
        // Création de l'organisation et ajout de l'utilisateur comme membre : tout ou rien
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('INSERT INTO organisations (org_name) VALUES (:name)');
            $stmt->execute(['name' => $name]);
            $organisationId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('INSERT INTO users_organisations (idx_user, idx_organisation) VALUES (:user_id, :organisation_id)');
            $stmt->execute(['user_id' => $this->user->getId(), 'organisation_id' => $organisationId]);

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        $organisation = new Organisation();
        $organisation->hydrate(['id_organisation' => $organisationId, 'org_name' => $name]);
        return $organisation;
    }

    /**
     * Organisation à laquelle appartient un prêt (par l'objet prêté), seulement si l'utilisateur en est membre
     */
    public function getOrganisationOfLoan(int $loanId): Organisation
    {
        $stmt = $this->pdo->prepare('SELECT organisations.* FROM organisations
            JOIN articles ON articles.idx_organisation = organisations.id_organisation
            JOIN loans ON loans.idx_article = articles.id_article
            JOIN users_organisations ON users_organisations.idx_organisation = organisations.id_organisation
            WHERE loans.id_loan = :loan AND users_organisations.idx_user = :user_id');
        $stmt->execute(['loan' => $loanId, 'user_id' => $this->user->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception("Organisation not found for loan ID: " . $loanId);
        }

        $organisation = new Organisation();
        $organisation->hydrate($ligne);
        return $organisation;
    }

    // ---- Réglages (page « Paramètres de l'organisation ») ----

    public function rename(Organisation $organisation, string $name): void
    {
        $stmt = $this->pdo->prepare('UPDATE organisations SET org_name = :name WHERE id_organisation = :id');
        $stmt->execute(['name' => $name, 'id' => $organisation->getId()]);
    }

    /**
     * Prêts : durée par défaut (null = indéterminée), rappel au client, conditions du contrat (null = texte par défaut)
     */
    public function updateLoanSettings(Organisation $organisation, ?int $defaultLoanDays, bool $remindersEnabled, ?string $contractTerms): void
    {
        $stmt = $this->pdo->prepare('UPDATE organisations SET org_default_loan_days = :days, org_reminders_enabled = :reminders,
            org_contract_terms = :terms WHERE id_organisation = :id');
        $stmt->execute([
            'days' => $defaultLoanDays,
            'reminders' => (int) $remindersEnabled, // true → 1, false → 0 (colonne TINYINT)
            'terms' => $contractTerms,
            'id' => $organisation->getId(),
        ]);
    }

    /**
     * Objets perdus : page publique activée ou non, message affiché (null = aucun)
     */
    public function updateLostSettings(Organisation $organisation, bool $enabled, ?string $message): void
    {
        $stmt = $this->pdo->prepare('UPDATE organisations SET org_lost_page_enabled = :enabled, org_lost_message = :message WHERE id_organisation = :id');
        $stmt->execute(['enabled' => (int) $enabled, 'message' => $message, 'id' => $organisation->getId()]);
    }

    // ---- Membres ----

    /**
     * Membres de l'organisation, triés par nom : User[]
     */
    public function getMembers(Organisation $organisation): array
    {
        $stmt = $this->pdo->prepare('SELECT users.* FROM users
            JOIN users_organisations ON users_organisations.idx_user = users.id_user
            WHERE users_organisations.idx_organisation = :organisation
            ORDER BY users.usr_lastname, users.usr_firstname');
        $stmt->execute(['organisation' => $organisation->getId()]);
        $members = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $member = new User();
            $member->hydrate($ligne);
            $members[] = $member;
        }

        return $members;
    }

    public function isMember(Organisation $organisation, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users_organisations WHERE idx_organisation = :organisation AND idx_user = :user');
        $stmt->execute(['organisation' => $organisation->getId(), 'user' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Retire un membre (ou l'utilisateur lui-même : « quitter l'organisation »)
     */
    public function removeMember(Organisation $organisation, int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users_organisations WHERE idx_organisation = :organisation AND idx_user = :user');
        $stmt->execute(['organisation' => $organisation->getId(), 'user' => $userId]);
    }

    /**
     * Supprime l'organisation et TOUTES ses données (objets, photos, prêts, dégâts, ventes, pertes, catégories,
     * clients, invitations, membres). Tout ou rien ; les fichiers photo sont effacés après la validation.
     */
    public function deleteOrganisation(Organisation $organisation): void
    {
        $id = $organisation->getId();
        // Photos des objets de l'organisation (y compris celles des dégâts) : fichiers à effacer après la suppression
        $stmt = $this->pdo->prepare('SELECT images.im_name FROM images JOIN articles ON articles.id_article = images.idx_article WHERE articles.idx_organisation = :id');
        $stmt->execute(['id' => $id]);
        $imageNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Ordre imposé par les clés étrangères : d'abord les lignes qui font référence aux autres.
        // « DELETE table FROM … JOIN … » : supprime les lignes de la table nommée, choisies grâce à la jointure
        $statements = [
            'DELETE images FROM images JOIN articles ON articles.id_article = images.idx_article WHERE articles.idx_organisation = :id',
            'DELETE degats FROM degats JOIN loans ON loans.id_loan = degats.idx_loan JOIN articles ON articles.id_article = loans.idx_article WHERE articles.idx_organisation = :id',
            'DELETE loans FROM loans JOIN articles ON articles.id_article = loans.idx_article WHERE articles.idx_organisation = :id',
            'DELETE sales FROM sales JOIN articles ON articles.id_article = sales.idx_article WHERE articles.idx_organisation = :id',
            'DELETE losses FROM losses JOIN articles ON articles.id_article = losses.idx_article WHERE articles.idx_organisation = :id',
            'DELETE additional_fields_value FROM additional_fields_value JOIN articles ON articles.id_article = additional_fields_value.idx_article WHERE articles.idx_organisation = :id',
            'DELETE FROM articles WHERE idx_organisation = :id',
            'DELETE additional_fields FROM additional_fields JOIN categories ON categories.id_category = additional_fields.idx_category WHERE categories.idx_organisation = :id',
            // Sous-catégories : on détache d'abord les parentes, sinon une catégorie parente ne pourrait pas être supprimée
            'UPDATE categories SET idx_cat_parent = NULL WHERE idx_organisation = :id',
            'DELETE FROM categories WHERE idx_organisation = :id',
            'DELETE FROM clients WHERE idx_organisation = :id',
            'DELETE FROM invitations WHERE idx_organisation = :id',
            'DELETE FROM users_organisations WHERE idx_organisation = :id',
            'DELETE FROM organisations WHERE id_organisation = :id',
        ];

        $this->pdo->beginTransaction();
        try {
            foreach ($statements as $sql) {
                $this->pdo->prepare($sql)->execute(['id' => $id]);
            }
            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        foreach ($imageNames as $name) {
            @unlink($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $name . '.jpg');
        }
    }
}