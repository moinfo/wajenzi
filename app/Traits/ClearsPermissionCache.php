<?php

namespace App\Traits;

use Spatie\Permission\PermissionRegistrar;

trait ClearsPermissionCache
{
    /**
     * Clear the permission cache
     */
    protected function clearPermissionCache(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Clear permission cache after successful operations
     */
    protected function clearCacheAfterSuccess($response)
    {
        if (is_object($response) && method_exists($response, 'getStatusCode')) {
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $this->clearPermissionCache();
            }
        }
        
        return $response;
    }
}