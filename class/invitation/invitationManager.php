<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitation.php';
class InvitationManager{

    private $pdo;
    private $user;

    public function __construct(PDO $pdo, User $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function getInvitationById(int $id): Invitation
    {
        $stmt = $this->pdo->prepare('SELECT * FROM invitations WHERE id_invitation = :id AND id_user = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $this->user->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $invitation = new Invitation();
            $invitation->hydrate($ligne);
            return $invitation;
            
        } else {
            throw new Exception("Invitation not found with ID: " . $id);
        }
    }
}