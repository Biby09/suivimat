<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitation.php';

/**
 * Invitations à rejoindre une organisation.
 * Une invitation vise un compte SuiviMat existant ; elle est « en attente » tant que inv_active = 1,
 * puis désactivée (0) quand elle est acceptée, refusée ou annulée.
 */
class InvitationManager
{
    private PDO $pdo;
    private User $user; // utilisateur connecté

    // Colonnes lues : l'invitation, l'organisation, la personne invitée et l'auteur de l'invitation
    private const SELECT_INVITATIONS = 'SELECT invitations.*, organisations.org_name,
            invitee.usr_firstname, invitee.usr_lastname, invitee.usr_email,
            inviter.usr_firstname AS inviter_firstname, inviter.usr_lastname AS inviter_lastname
        FROM invitations
        JOIN organisations ON organisations.id_organisation = invitations.idx_organisation
        JOIN users AS invitee ON invitee.id_user = invitations.idx_user
        LEFT JOIN users AS inviter ON inviter.id_user = invitations.idx_invited_by';

    public function __construct(PDO $pdo, User $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    private function fetchAll(PDOStatement $stmt): array
    {
        $invitations = [];
        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $invitation = new Invitation();
            $invitation->hydrate($ligne);
            $invitations[] = $invitation;
        }
        return $invitations;
    }

    /**
     * Invitations en attente reçues par l'utilisateur connecté (tableau de bord)
     */
    public function getAllInvitationsForUser(): array
    {
        $stmt = $this->pdo->prepare(self::SELECT_INVITATIONS . ' WHERE invitations.idx_user = :user AND invitations.inv_active = 1
            ORDER BY invitations.inv_created_at DESC');
        $stmt->execute(['user' => $this->user->getId()]);
        return $this->fetchAll($stmt);
    }

    /**
     * Invitations en attente envoyées par une organisation (page « Paramètres »)
     */
    public function getPendingInvitationsForOrganisation(Organisation $organisation): array
    {
        $stmt = $this->pdo->prepare(self::SELECT_INVITATIONS . ' WHERE invitations.idx_organisation = :organisation AND invitations.inv_active = 1
            ORDER BY invitations.inv_created_at DESC');
        $stmt->execute(['organisation' => $organisation->getId()]);
        return $this->fetchAll($stmt);
    }

    public function hasPendingInvitation(Organisation $organisation, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM invitations WHERE idx_organisation = :organisation AND idx_user = :user AND inv_active = 1');
        $stmt->execute(['organisation' => $organisation->getId(), 'user' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Invite un utilisateur existant ; l'utilisateur connecté est noté comme auteur de l'invitation
     */
    public function invite(Organisation $organisation, User $invitee): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO invitations (idx_user, idx_organisation, idx_invited_by) VALUES (:user, :organisation, :inviter)');
        $stmt->execute(['user' => $invitee->getId(), 'organisation' => $organisation->getId(), 'inviter' => $this->user->getId()]);
    }

    /**
     * Annule une invitation en attente de l'organisation (renvoie false si elle n'existe pas ou n'est plus en attente)
     */
    public function cancel(Organisation $organisation, int $invitationId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE invitations SET inv_active = 0 WHERE id_invitation = :id AND idx_organisation = :organisation AND inv_active = 1');
        $stmt->execute(['id' => $invitationId, 'organisation' => $organisation->getId()]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Réponse de l'utilisateur connecté à une de ses invitations : accepter (devient membre) ou refuser.
     * Renvoie l'ID de l'organisation, ou lève une exception si l'invitation n'existe pas ou n'est plus en attente.
     */
    public function respond(int $invitationId, bool $accept): int
    {
        $this->pdo->beginTransaction();
        try {
            // FOR UPDATE : verrouille la ligne jusqu'à la fin de la transaction (deux clics rapides ne l'acceptent pas deux fois)
            $stmt = $this->pdo->prepare('SELECT idx_organisation FROM invitations WHERE id_invitation = :id AND idx_user = :user AND inv_active = 1 FOR UPDATE');
            $stmt->execute(['id' => $invitationId, 'user' => $this->user->getId()]);
            $organisationId = $stmt->fetchColumn();
            if ($organisationId === false) {
                throw new Exception("Invitation not found or not pending: " . $invitationId);
            }

            if ($accept) {
                // INSERT IGNORE : pas d'erreur si l'utilisateur est déjà membre (clé primaire idx_user + idx_organisation)
                $stmt = $this->pdo->prepare('INSERT IGNORE INTO users_organisations (idx_user, idx_organisation) VALUES (:user, :organisation)');
                $stmt->execute(['user' => $this->user->getId(), 'organisation' => $organisationId]);
            }

            $this->pdo->prepare('UPDATE invitations SET inv_active = 0 WHERE id_invitation = :id')->execute(['id' => $invitationId]);
            $this->pdo->commit();
            return (int) $organisationId;
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
