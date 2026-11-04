<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/loss/loss.php';

// Levée quand le statut de l'objet a changé entre-temps (déjà perdu, vendu, déjà retrouvé…)
class LossConflictException extends Exception
{
}

/**
 * Pertes des articles d'une organisation (mode « perdu »).
 * La table losses n'a pas de colonne idx_organisation : l'organisation est vérifiée par la jointure avec articles.
 */
class LossManager
{
    private PDO $pdo;
    private Organisation $organisation;

    public function __construct(PDO $pdo, Organisation $organisation)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
    }

    /**
     * Perte en cours d'un article (pas encore retrouvé), ou null.
     */
    public function getCurrentLoss(Article $article): ?Loss
    {
        $stmt = $this->pdo->prepare('SELECT losses.* FROM losses
            JOIN articles ON articles.id_article = losses.idx_article
            WHERE losses.idx_article = :article AND articles.idx_organisation = :organisation AND losses.los_found_date IS NULL
            ORDER BY losses.los_date DESC, losses.id_loss DESC LIMIT 1');
        $stmt->execute(['article' => $article->getId(), 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            return null;
        }
        $loss = new Loss();
        $loss->hydrate($ligne);
        return $loss;
    }

    /**
     * Passe un article disponible ou emprunté en mode « perdu ».
     * $ongoingLoan : prêt en cours (null s'il n'est pas prêté) ; il est clôturé à la date de la perte.
     */
    public function markLost(Article $article, string $date, ?string $comment, ?Loan $ongoingLoan): void
    {
        $this->pdo->beginTransaction();

        try {
            // Le WHERE sur le statut évite de déclarer deux fois la même perte (deux personnes en même temps)
            $stmt = $this->pdo->prepare("UPDATE articles SET art_status = 'Perdu'
                WHERE id_article = :article AND idx_organisation = :organisation AND art_status IN ('Disponible', 'Emprunté')");
            $stmt->execute(['article' => $article->getId(), 'organisation' => $this->organisation->getId()]);
            if ($stmt->rowCount() === 0) {
                throw new LossConflictException("Article cannot be marked as lost: " . $article->getId());
            }

            if ($ongoingLoan !== null) {
                $stmt = $this->pdo->prepare('UPDATE loans SET lo_end_date = :date WHERE id_loan = :loan AND lo_end_date IS NULL');
                $stmt->execute(['date' => $date, 'loan' => $ongoingLoan->getId()]);
            }

            $stmt = $this->pdo->prepare('INSERT INTO losses (idx_article, los_date, los_comment) VALUES (:article, :date, :comment)');
            $stmt->execute(['article' => $article->getId(), 'date' => $date, 'comment' => $comment]);

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Désactive le mode « perdu » : l'objet est retrouvé aujourd'hui et redevient « Disponible ».
     */
    public function markFound(Article $article): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("UPDATE articles SET art_status = 'Disponible'
                WHERE id_article = :article AND idx_organisation = :organisation AND art_status = 'Perdu'");
            $stmt->execute(['article' => $article->getId(), 'organisation' => $this->organisation->getId()]);
            if ($stmt->rowCount() === 0) {
                throw new LossConflictException("Article is not lost: " . $article->getId());
            }

            // CURDATE() : date du jour selon la base (même fuseau que le reste de l'application)
            $stmt = $this->pdo->prepare('UPDATE losses SET los_found_date = CURDATE() WHERE idx_article = :article AND los_found_date IS NULL');
            $stmt->execute(['article' => $article->getId()]);

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
