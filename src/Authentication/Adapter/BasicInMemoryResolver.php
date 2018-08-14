<?php
declare(strict_types=1);

namespace NetgluePostmark\Authentication\Adapter;

use Zend\Authentication\Adapter\Http\ResolverInterface;

class BasicInMemoryResolver implements ResolverInterface
{
    /** @var string */
    private $username;

    /** @var string */
    private $password;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Resolve username/realm to password/hash/etc.
     *
     * @param  string $username Username
     * @param  string $realm    Authentication Realm
     * @param  string $password Password (optional)
     * @return string|array|false User's shared secret as string if found in realm, or User's identity as array
     *         if resolved, false otherwise.
     */
    public function resolve($username, $realm, $password = null)
    {
        if ($username === $this->username) {
            return $this->password;
        }

        return false;
    }
}
