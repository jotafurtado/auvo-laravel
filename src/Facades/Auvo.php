<?php

namespace Jcf\Auvo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Auvo API Facade
 *
 * @method static \Jcf\Auvo\Http\Client getHttpClient()
 * @method static \Jcf\Auvo\Auth\AuthManager getAuthManager()
 * @method static \Jcf\Auvo\Models\Token signIn()
 * @method static \Jcf\Auvo\Models\Token|null getToken()
 * @method static \Jcf\Auvo\Models\Token getValidToken()
 * @method static string getAccessToken()
 * @method static \Jcf\Auvo\Query\UserQuery users()
 * @method static \Jcf\Auvo\Query\TaskQuery tasks()
 * @method static \Jcf\Auvo\Query\CustomerQuery customers()
 * @method static \Jcf\Auvo\Query\TeamQuery teams()
 */
class Auvo extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'auvo';
    }

    /**
     * Obtém o gerenciador de autenticação.
     */
    public static function auth(): \Jcf\Auvo\Auth\AuthManager
    {
        return static::getFacadeRoot()->getAuthManager();
    }
}
