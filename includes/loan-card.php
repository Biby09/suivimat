<?php
/*
    Carte d'un prêt en cours : photo et nom de l'objet, client, dates, badge d'échéance, boutons « Contrat » (PDF) et « Retour ».
    Utilisée par la page « Prêts en cours » (dashboard/organisation/loans/) et la page d'un client (dashboard/organisation/clients/client.php).
    Toute la carte mène à la fiche de l'objet ; « Retour » ouvre la fiche avec le dialog de retour (?retour=1).

    Variables à définir avant l'inclusion :
    - $loan : Loan (prêt en cours, lu avec le nom et le code de l'objet) ;
    - $loanCoverUrl : photo de l'objet, ou null ;
    - $loanOrder : position dans l'ordre d'urgence (tri par défaut de scripts/list-filter.js) ;
    - $loanShowClient (optionnel, true par défaut) : false sur la page d'un client (inutile de répéter son nom).
    Les attributs data-* servent aux filtres, au tri et à la recherche (scripts/list-filter.js).
*/
$loanShowClient = $loanShowClient ?? true;
$due = $loan->getDueStatus();
$articleUrl = '/dashboard/organisation/article/?article_id=' . $loan->getArticleId();
$search = mb_strtolower($loan->getArticleName() . ' ' . $loan->getArticleCode() . ' ' . $loan->getClientName());
?>
<div class="col" data-item data-categories="<?= $due['category'] ?>" data-search="<?= htmlspecialchars($search) ?>"
    data-sort-order="<?= (int) $loanOrder ?>" data-sort-client="<?= htmlspecialchars(mb_strtolower($loan->getClientSortName())) ?>"
    data-sort-start="<?= $loan->getStartDate() ?>">
    <article class="card h-100 shadow-hover <?= $due['category'] === 'late' ? 'border-danger' : '' ?>">
        <div class="card-body d-flex gap-3 p-3">
            <!-- align-self-start : reste carrée (sinon étirée à la hauteur de la carte) -->
            <div class="ratio ratio-1x1 flex-shrink-0 align-self-start rounded overflow-hidden bg-light loan-thumb">
                <?php if ($loanCoverUrl): ?>
                    <img src="<?= htmlspecialchars($loanCoverUrl) ?>" class="object-fit-cover" alt="" loading="lazy">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/></svg>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex-grow-1" style="min-width: 0;">
                <h3 class="fs-6 fw-semibold mb-1 text-truncate">
                    <!-- stretched-link : toute la carte devient cliquable et mène à la fiche de l'objet -->
                    <a href="<?= $articleUrl ?>" class="stretched-link text-reset text-decoration-none"><?= htmlspecialchars($loan->getArticleName() ?? 'Objet') ?></a>
                </h3>
                <div class="small mb-1 text-truncate">
                    <span class="badge text-bg-light border font-monospace me-1"><?= htmlspecialchars($loan->getArticleCode() ?? '') ?></span>
                    <?php if ($loanShowClient): ?><?= htmlspecialchars($loan->getClientName()) ?><?php endif; ?>
                </div>
                <div class="small text-muted">
                    Depuis le <?= formatDate($loan->getStartDate()) ?>
                    <?php if ($loan->getDueDate() !== null): ?>
                        · retour le <?= formatDate($loan->getDueDate()) ?>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                    <span class="badge <?= $due['class'] ?>"><?= $due['label'] ?></span>
                    <!-- position-relative + z-2 : les boutons passent au-dessus du lien qui couvre la carte -->
                    <div class="d-flex gap-1 flex-shrink-0">
                        <!-- Contrat de prêt en PDF, dans un nouvel onglet (api/loan/contract.php) -->
                        <a href="/api/loan/contract.php?loan_id=<?= $loan->getId() ?>" class="btn btn-sm btn-outline-secondary position-relative z-2 text-nowrap"
                            target="_blank" rel="noopener">Contrat</a>
                        <a href="<?= $articleUrl ?>&retour=1" class="btn btn-sm btn-outline-primary position-relative z-2 text-nowrap">Retour</a>
                    </div>
                </div>
            </div>
        </div>
    </article>
</div>
