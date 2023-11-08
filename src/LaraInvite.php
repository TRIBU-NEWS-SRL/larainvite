<?php

namespace Junaidnasir\Larainvite;

use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Junaidnasir\Larainvite\Events\InvitationCanceled;
use Junaidnasir\Larainvite\Events\InvitationConsumed;
use Junaidnasir\Larainvite\Events\InvitationExpired;
use Junaidnasir\Larainvite\Events\Invited;
use Junaidnasir\Larainvite\Exceptions\InvalidTokenException;

/**
 *   Laravel Invitation class.
 */
class LaraInvite implements InvitationInterface
{
    /**
     * Email address to invite.
     * @var string
     */
    private $email;

    /**
     * Referral Code for invitation.
     * @var string
     */
    private $code = null;

    /**
     * Allow multiple use of code.
     * @var bool
     */
    private $multiple = false;

    /**
     * Number of times code used.
     * @var int
     */
    private $multipleCount = 0;

    /**
     * Status of code existing in DB.
     * @var bool
     */
    private $exist = false;

    /**
     * integer ID of referral.
     * @var [type]
     */
    private $referral;

    /**
     * DateTime of referral code expiration.
     * @var DateTime
     */
    private $expires;

    /**
     * Invitation Model.
     * @var Junaidnasir\Larainvite\Models\LaraInviteModel
     */
    private $instance = null;

    /**
     * {@inheritdoc}
     */
    public function invite(string $email, int $referral, DateTime $expires, $beforeSave = null): string
    {
        $this->readyPayload($email, $referral, $expires)
             ->createInvite($beforeSave);

        Invited::dispatch($this->instance);

        return $this->code;
    }

    /**
     * {@inheritdoc}
     */
    public function multiple(bool $multiple = true): InvitationInterface
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->multipleCount;
    }

    /**
     * {@inheritdoc}
     */
    public function setCode(string $code)
    {
        $this->code = $code;
        try {
            $this->getModelInstance(false);
        } catch (InvalidTokenException $exception) {
            // handle invalid codes
            $this->exist = false;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): Junaidnasir\Larainvite\Models\LaraInviteModel
    {
        return $this->instance;
    }

    /**
     * {@inheritdoc}
     */
    public function status(): string
    {
        if ($this->isValid()) {
            return $this->instance->status;
        }

        return 'Invalid';
    }

    /**
     * {@inheritdoc}
     */
    public function consume(): bool
    {
        if ($this->isValid()) {
            $this->instance->status = $this->instance->multiple() ? 'pending' : 'successful';
            $this->instance->multiple_count = $this->instance->multiple() ? $this->instance->multiple_count + 1 : 0;
            $this->instance->save();

            InvitationConsumed::dispatch($this->instance);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(): bool
    {
        if ($this->isValid()) {
            $this->instance->status = 'canceled';
            $this->instance->save();

            InvitationCanceled::dispatch($this->instance);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isExisting(): bool
    {
        return $this->exist;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return ! $this->isExpired() && $this->isPending();
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired(): bool
    {
        if (! $this->isExisting()) {
            return true;
        }
        if (strtotime($this->instance->valid_till) >= time()) {
            return false;
        }
        $this->instance->status = 'expired';
        $this->instance->save();

        InvitationExpired::dispatch($this->instance);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isPending(): bool
    {
        if (! $this->isExisting()) {
            return false;
        }

        return $this->instance->status === 'pending';
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed($email): bool
    {
        return $this->isValid() && ($this->instance->email === $email);
    }

    /**
     * Fire junaidnasir.larainvite.invited again for the invitation.
     * @return true
     */
    public function reminder()
    {
        Invited::dispatch($this->instance);

        return true;
    }

    /**
     * generate invitation code and call save.
     * @param null $beforeSave
     * @return self
     * @throws InvalidTokenException
     */
    private function createInvite($beforeSave = null): LaraInvite
    {
        $code = md5(uniqid('', true));

        return $this->save($code, $beforeSave);
    }

    /**
     * saves invitation in DB.
     * @param string $code referral code
     * @param null $beforeSave
     * @return self
     * @throws InvalidTokenException
     */
    private function save($code, $beforeSave = null): LaraInvite
    {
        $this->getModelInstance();
        $this->instance->email = $this->email;
        $this->instance->user_id = $this->referral;
        $this->instance->valid_till = $this->expires;
        $this->instance->code = $code;

        if ($this->multiple) {
            $this->instance->multiple = true;
        }

        if ($beforeSave !== null) {
            if ($beforeSave instanceof Closure) {
                $beforeSave->call($this->instance);
            } elseif (is_callable($beforeSave)) {
                $beforeSave($this->instance);
            }
        }
        $this->instance->save();

        $this->code = $code;
        $this->exist = true;

        return $this;
    }

    /**
     * set $this->instance to Junaidnasir\Larainvite\Models\LaraInviteModel instance.
     * @param bool $allowNew allow new model
     * @return self
     * @throws InvalidTokenException
     */
    private function getModelInstance($allowNew = true): LaraInvite
    {
        $model = config('larainvite.InvitationModel');
        if ($allowNew) {
            $this->instance = new $model;

            return $this;
        }
        try {
            $this->instance = (new $model)->where('code', $this->code)->firstOrFail();
            $this->exist = true;

            return $this;
        } catch (ModelNotFoundException $e) {
            throw new InvalidTokenException("Invalid Token {$this->code}", 401);
        }
    }

    /**
     * set input variables.
     * @param  string   $email    email to invite
     * @param  int  $referral referral id
     * @param  DateTime $expires  expiration of token
     * @return self
     */
    private function readyPayload($email, $referral, $expires): LaraInvite
    {
        $this->email = $email;
        $this->referral = $referral;
        $this->expires = $expires;

        return $this;
    }
}
