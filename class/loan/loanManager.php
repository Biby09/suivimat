<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/loan.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loan/damage.php';

// Levée par createLoan() quand l'objet n'est plus disponible : se distingue des erreurs SQL (PDOException)
class ArticleNotAvailableException extends Exception
{
}

// Levée par returnLoan() quand l'objet n'est plus emprunté (retour déjà enregistré par quelqu'un d'autre)
class LoanAlreadyReturnedException extends Exception
{
}

/**
 * Prêts des articles d'une organisation.
 * La table loans n'a pas de colonne idx_organisation : l'organisation est vérifiée par la jointure avec articles.
 */
class LoanManager
{
    private PDO $pdo;
    private Organisation $organisation;

    // Colonnes lues pour un prêt : celles du prêt + le nom du client + le nom et le code de l'objet
    private const SELECT_LOANS = 'SELECT loans.*, clients.cli_firstname, clients.cli_lastname, articles.art_name, articles.art_code
        FROM loans
        JOIN articles ON articles.id_article = loans.idx_article
        JOIN clients ON clients.id_client = loans.idx_client';

    public function __construct(PDO $pdo, Organisation $organisation)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
    }

    public function getLoanById(int $id): Loan
    {
        $stmt = $this->pdo->prepare(self::SELECT_LOANS . ' WHERE loans.id_loan = :id AND articles.idx_organisation = :organisation');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception("Loan not found with ID: " . $id);
        }

        $loan = new Loan();
        $loan->hydrate($ligne);
        $this->attachDamages([$loan]);
        return $loan;
    }

    /**
     * Prête un article disponible à un client : crée le prêt et passe l'article en « Emprunté ».
     * $startDate, $dueDate : AAAA-MM-JJ ($dueDate null = retour indéterminé).
     * Si une transaction est déjà ouverte (ex. client créé juste avant dans create-loan.php), elle est utilisée telle quelle :
     * c'est l'appelant qui valide (commit) ou annule (rollBack) le tout.
     */
    public function createLoan(Article $article, Client $client, string $startDate, ?string $dueDate): void
    {
        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            // Le WHERE sur le statut évite de prêter deux fois le même objet (deux personnes qui valident en même temps) :
            // la deuxième requête ne modifie aucune ligne
            $stmt = $this->pdo->prepare("UPDATE articles SET art_status = 'Emprunté' WHERE id_article = :article AND idx_organisation = :organisation AND art_status = 'Disponible'");
            $stmt->execute(['article' => $article->getId(), 'organisation' => $this->organisation->getId()]);
            if ($stmt->rowCount() === 0) {
                throw new ArticleNotAvailableException("Article not available: " . $article->getId());
            }

            $stmt = $this->pdo->prepare('INSERT INTO loans (idx_article, idx_client, lo_start_date, lo_due_date) VALUES (:article, :client, :start, :due)');
            $stmt->execute(['article' => $article->getId(), 'client' => $client->getId(), 'start' => $startDate, 'due' => $dueDate]);

            if ($ownTransaction) {
                $this->pdo->commit();
            }
        } catch (Exception $exception) {
            if ($ownTransaction) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Corrige un prêt EN COURS : client, date de sortie, date de retour prévue (prolongation, erreur de saisie…).
     * Un prêt terminé (retour enregistré) n'est pas modifié : le WHERE lo_end_date IS NULL l'exclut.
     */
    public function updateLoan(Loan $loan, Client $client, string $startDate, ?string $dueDate): void
    {
        // Date de retour prévue changée (ex. prolongation) : le rappel au client pourra être renvoyé avant la nouvelle date.
        // <=> : égalité qui fonctionne aussi avec NULL (NULL <=> NULL est vrai, NULL = NULL ne l'est pas).
        // MySQL applique le SET de gauche à droite : le rappel est remis à zéro AVANT que lo_due_date ne change.
        $stmt = $this->pdo->prepare('UPDATE loans SET lo_reminder_sent_at = IF(lo_due_date <=> :due_check, lo_reminder_sent_at, NULL),
                idx_client = :client, lo_start_date = :start, lo_due_date = :due
            WHERE id_loan = :loan AND lo_end_date IS NULL');
        $stmt->execute(['due_check' => $dueDate, 'client' => $client->getId(), 'start' => $startDate, 'due' => $dueDate, 'loan' => $loan->getId()]);
    }

    /**
     * Tous les prêts en cours de l'organisation (page « Prêts en cours »), du retour prévu le plus proche au plus lointain,
     * puis ceux sans date de retour (les plus anciens d'abord).
     */
    public function getOngoingLoans(): array
    {
        // « lo_due_date IS NULL » vaut 0 ou 1 : trié en premier, il place les prêts sans date à la fin
        $stmt = $this->pdo->prepare(self::SELECT_LOANS . ' WHERE articles.idx_organisation = :organisation AND loans.lo_end_date IS NULL
            ORDER BY loans.lo_due_date IS NULL, loans.lo_due_date, loans.lo_start_date, loans.id_loan');
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        $loans = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $loan = new Loan();
            $loan->hydrate($ligne);
            $loans[] = $loan;
        }

        return $loans;
    }

    /**
     * Prêts d'un client (page d'un client) : en cours d'abord (retour prévu le plus proche en premier),
     * puis terminés, du plus récent au plus ancien ; avec leurs dégâts.
     */
    public function getLoansForClient(Client $client): array
    {
        // 1. lo_end_date IS NOT NULL vaut 0 pour un prêt en cours : en cours d'abord
        // 2. terminés : retour réel le plus récent d'abord (pour les prêts en cours, lo_end_date est NULL partout : sans effet)
        // 3. en cours : retour prévu le plus proche d'abord, sans date à la fin
        $stmt = $this->pdo->prepare(self::SELECT_LOANS . ' WHERE loans.idx_client = :client AND articles.idx_organisation = :organisation
            ORDER BY loans.lo_end_date IS NOT NULL, loans.lo_end_date DESC, loans.lo_due_date IS NULL, loans.lo_due_date, loans.lo_start_date DESC, loans.id_loan DESC');
        $stmt->execute(['client' => $client->getId(), 'organisation' => $this->organisation->getId()]);
        $loans = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $loan = new Loan();
            $loan->hydrate($ligne);
            $loans[] = $loan;
        }

        $this->attachDamages($loans);
        return $loans;
    }

    /**
     * Prêt en cours d'un article (pas encore de date de retour réelle), ou null.
     */
    public function getOngoingLoan(Article $article): ?Loan
    {
        $stmt = $this->pdo->prepare(self::SELECT_LOANS . ' WHERE loans.idx_article = :article AND articles.idx_organisation = :organisation
            AND loans.lo_end_date IS NULL ORDER BY loans.lo_start_date DESC, loans.id_loan DESC LIMIT 1');
        $stmt->execute(['article' => $article->getId(), 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            return null;
        }
        $loan = new Loan();
        $loan->hydrate($ligne);
        return $loan;
    }

    /**
     * Enregistre le retour d'un article emprunté : date de retour du prêt, article de nouveau « Disponible »
     * et, si $damageDescription est donné, un dégât lié au prêt. Renvoie l'ID du dégât créé (ou null).
     * $loan : prêt en cours ; null si l'article est « Emprunté » sans prêt enregistré (données anciennes) :
     * l'article redevient disponible, mais aucun dégât ne peut être enregistré (un dégât appartient à un prêt).
     * Même règle de transaction que createLoan().
     */
    public function returnLoan(Article $article, ?Loan $loan, string $endDate, ?string $damageDescription): ?int
    {
        if ($loan === null && $damageDescription !== null) {
            throw new InvalidArgumentException('A damage needs a loan');
        }

        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            // Même principe que createLoan() : si le retour a déjà été enregistré, aucune ligne n'est modifiée
            $stmt = $this->pdo->prepare("UPDATE articles SET art_status = 'Disponible' WHERE id_article = :article AND idx_organisation = :organisation AND art_status = 'Emprunté'");
            $stmt->execute(['article' => $article->getId(), 'organisation' => $this->organisation->getId()]);
            if ($stmt->rowCount() === 0) {
                throw new LoanAlreadyReturnedException("Article not borrowed: " . $article->getId());
            }

            $damageId = null;
            if ($loan !== null) {
                $stmt = $this->pdo->prepare('UPDATE loans SET lo_end_date = :end WHERE id_loan = :loan AND lo_end_date IS NULL');
                $stmt->execute(['end' => $endDate, 'loan' => $loan->getId()]);
                if ($stmt->rowCount() === 0) {
                    throw new LoanAlreadyReturnedException("Loan already returned: " . $loan->getId());
                }

                if ($damageDescription !== null) {
                    // deg_date : date et heure de l'enregistrement (valeur par défaut de la colonne)
                    $stmt = $this->pdo->prepare('INSERT INTO degats (idx_loan, deg_description) VALUES (:loan, :description)');
                    $stmt->execute(['loan' => $loan->getId(), 'description' => $damageDescription]);
                    $damageId = (int) $this->pdo->lastInsertId();
                }
            }

            if ($ownTransaction) {
                $this->pdo->commit();
            }
            return $damageId;
        } catch (Exception $exception) {
            if ($ownTransaction) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Historique des prêts d'un article, du plus récent au plus ancien, avec leurs dégâts.
     */
    public function getLoansForArticle(Article $article): array
    {
        $stmt = $this->pdo->prepare(self::SELECT_LOANS . ' WHERE loans.idx_article = :article AND articles.idx_organisation = :organisation
            ORDER BY loans.lo_start_date DESC, loans.id_loan DESC');
        $stmt->execute(['article' => $article->getId(), 'organisation' => $this->organisation->getId()]);
        $loans = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $loan = new Loan();
            $loan->hydrate($ligne);
            $loans[] = $loan;
        }

        $this->attachDamages($loans);
        return $loans;
    }

    /**
     * Ajoute à chaque prêt ses dégâts et leurs photos : 2 requêtes au total, quel que soit le nombre de prêts.
     */
    private function attachDamages(array $loans): void
    {
        if (!$loans) {
            return;
        }

        // [id_prêt => Loan] : retrouver le prêt d'un dégât sans parcourir toute la liste
        $loansById = [];
        foreach ($loans as $loan) {
            $loansById[$loan->getId()] = $loan;
        }

        $placeholders = implode(', ', array_fill(0, count($loansById), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM degats WHERE idx_loan IN ($placeholders) ORDER BY deg_date, id_degat");
        $stmt->execute(array_keys($loansById));

        $damagesByLoan = [];  // [id_prêt => Damage[]]
        $damagesById = [];    // [id_dégât => Damage]
        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $damage = new Damage();
            $damage->hydrate($ligne);
            $damagesByLoan[$damage->getLoanId()][] = $damage;
            $damagesById[$damage->getId()] = $damage;
        }

        if ($damagesById) {
            $placeholders = implode(', ', array_fill(0, count($damagesById), '?'));
            $stmt = $this->pdo->prepare("SELECT idx_degat, im_name FROM images WHERE idx_degat IN ($placeholders) ORDER BY id_image");
            $stmt->execute(array_keys($damagesById));
            while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Même règle que Image::getUrl() : fichiers enregistrés en JPEG
                $damagesById[(int) $ligne['idx_degat']]->addImageUrl('/upload/' . $ligne['im_name'] . '.jpg');
            }
        }

        foreach ($loansById as $id => $loan) {
            $loan->setDamages($damagesByLoan[$id] ?? []);
        }
    }
}
