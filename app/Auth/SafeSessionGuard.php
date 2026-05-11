<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;

/**
 * Named subclass of SessionGuard so that `static::class` (used by
 * SessionGuard::getName() and getRecallerName()) is stable across
 * PHP-FPM workers. The previous implementation used an anonymous class
 * inside AuthServiceProvider, whose generated class name varied between
 * workers and caused login session keys to drift — users authenticated
 * successfully but the very next request couldn't find their login key
 * and bounced them back to /login.
 */
class SafeSessionGuard extends SessionGuard
{
    /**
     * Recover gracefully from corrupted remember-me cookies.
     */
    protected function recaller()
    {
        try {
            return parent::recaller();
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'unserialize') !== false) {
                $this->getCookieJar()->queue(
                    $this->getCookieJar()->forget($this->getRecallerName())
                );
                return null;
            }
            throw $e;
        }
    }
}
