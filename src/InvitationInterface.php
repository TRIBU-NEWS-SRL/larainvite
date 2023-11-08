<?php

namespace Junaidnasir\Larainvite;

use DateTime;

interface InvitationInterface
{
    /**
     * Create new invitation
     * @param string $email      Email to invite
     * @param int $referral   Referral
     * @param DateTime $expires    Expiration Date Time
     * @return string               Referral code
     */
    public function invite(string $email, int $referral, DateTime $expires);

    /**
     * Set the code for multiple use
     * @param bool $multiple
     * @return self
     */
    public function multiple(bool $multiple = true): InvitationInterface;
    
    /**
     * Set referral code and LaraInviteModel instance
     * @param string $code referral Code
     */
    public function setCode(string $code);

    /**
     * Returns Invitation record
     * @return Junaidnasir\Larainvite\Models\LaraInviteModel
     */
    public function get(): Junaidnasir\Larainvite\Models\LaraInviteModel;

    /**
     * Returns code usage count
     * @return int the number of usage of the code
     */
    public function count(): int;

    /**
     * Returns invitation status
     * @return string pending | successful | expired | canceled
     */
    public function status(): string;

    /**
     * Set invitation as successful
     * @return boolean true on success | false on error
     */
    public function consume(): bool;

    /**
     * Cancel an invitation
     * @return boolean true on success | false on error
     */
    public function cancel(): bool;

    /**
     * check if a code exist
     *
     * @return boolean true if code found | false if not
     */
    public function isExisting(): bool;

    /**
     * check if invitation is valid
     * @return boolean
     */
    public function isValid(): bool;

    /**
     * check if invitation has expired
     * @return boolean
     */
    public function isExpired(): bool;
    
    /**
     * check if invitation status is pending
     * @return boolean
     */
    public function isPending(): bool;

    /**
     * check if given token is valid and given email is allowed
     * @param $email
     * @return boolean
     */
    public function isAllowed($email): bool;
}
