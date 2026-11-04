<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/sale/sale.php';

// Levée par sellArticle() quand l'objet ne peut plus être vendu (déjà vendu, perdu… entre-temps)
class ArticleNotSellableException extends Exception
{
}

/**
 * Ventes des articles d'une organisation.
 * La table sales n'a pas de colonne idx_organisation : l'organisation est vérifiée par la jointure avec articles.
 */
class SaleManager
{
    private PDO $pdo;
    private Organisation $organisation;

    public function __construct(PDO $pdo, Organisation $organisation)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
    }

    /**
     * Dernière vente d'un article (avec le nom de l'acheteur), ou null s'il n'a jamais été vendu.
     */
    public function getLatestSale(Article $article): ?Sale
    {
        $stmt = $this->pdo->prepare('SELECT sales.*, clients.cli_firstname, clients.cli_lastname
            FROM sales
            JOIN articles ON articles.id_article = sales.idx_article
            JOIN clients ON clients.id_client = sales.idx_client
            WHERE sales.idx_article = :article AND articles.idx_organisation = :organisation
            ORDER BY sales.sa_date DESC, sales.id_sale DESC LIMIT 1');
        $stmt->execute(['article' => $article->getId(), 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            return null;
        }
        $sale = new Sale();
        $sale->hydrate($ligne);
        return $sale;
    }

    /**
     * Vend un article disponible ou emprunté : il passe en « Vendu » et la vente est enregistrée.
     * $ongoingLoan : prêt en cours de l'article (null s'il n'est pas prêté) ; il est clôturé à la date de vente.
     * Si une transaction est déjà ouverte (ex. acheteur créé juste avant), elle est utilisée : l'appelant valide ou annule.
     */
    public function sellArticle(Article $article, Client $client, string $date, ?Loan $ongoingLoan): void
    {
        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            // Le WHERE sur le statut évite une double vente (deux personnes qui valident en même temps)
            $stmt = $this->pdo->prepare("UPDATE articles SET art_status = 'Vendu'
                WHERE id_article = :article AND idx_organisation = :organisation AND art_status IN ('Disponible', 'Emprunté')");
            $stmt->execute(['article' => $article->getId(), 'organisation' => $this->organisation->getId()]);
            if ($stmt->rowCount() === 0) {
                throw new ArticleNotSellableException("Article not sellable: " . $article->getId());
            }

            if ($ongoingLoan !== null) {
                // L'objet ne reviendra pas : le prêt se termine le jour de la vente
                $stmt = $this->pdo->prepare('UPDATE loans SET lo_end_date = :date WHERE id_loan = :loan AND lo_end_date IS NULL');
                $stmt->execute(['date' => $date, 'loan' => $ongoingLoan->getId()]);
            }

            $stmt = $this->pdo->prepare('INSERT INTO sales (idx_article, idx_client, sa_date) VALUES (:article, :client, :date)');
            $stmt->execute(['article' => $article->getId(), 'client' => $client->getId(), 'date' => $date]);

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
}
