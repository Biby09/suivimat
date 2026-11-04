<?php

/**
 * Invitation d'un utilisateur à rejoindre une organisation (table invitations).
 * Lue avec les noms utiles à l'affichage (jointures) : organisation, personne invitée, auteur de l'invitation.
 */
class Invitation
{
    private int $id;
    private int $userId;              // personne invitée
    private int $organisationId;
    private string $organisationName;
    private string $userName;         // « Prénom Nom » de la personne invitée
    private string $userEmail;
    private ?string $invitedByName;   // membre qui a invité (null si inconnu)
    private string $createdAt;        // AAAA-MM-JJ HH:MM:SS

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_invitation'];
        $this->userId = (int) $ligne['idx_user'];
        $this->organisationId = (int) $ligne['idx_organisation'];
        $this->organisationName = $ligne['org_name'] ?? '';
        $this->userName = trim(($ligne['usr_firstname'] ?? '') . ' ' . ($ligne['usr_lastname'] ?? ''));
        $this->userEmail = $ligne['usr_email'] ?? '';
        $invitedBy = trim(($ligne['inviter_firstname'] ?? '') . ' ' . ($ligne['inviter_lastname'] ?? ''));
        $this->invitedByName = $invitedBy !== '' ? $invitedBy : null;
        $this->createdAt = $ligne['inv_created_at'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }

    public function getOrganisationName(): string
    {
        return $this->organisationName;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getInvitedByName(): ?string
    {
        return $this->invitedByName;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
