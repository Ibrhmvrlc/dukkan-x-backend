<?php

namespace App\Providers;

use App\Models\SupportThread;
use App\Policies\SupportThreadPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 🔴 Buraya policy eşlemesini ekliyoruz
        SupportThread::class => SupportThreadPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // (Opsiyonel) admin rolü her şeye izinli olsun:
        Gate::before(function ($user, $ability) {
            // Eğer pivot üzerinden roles[] getiriyorsan:
            if (isset($user->roles) && is_array($user->roles) && in_array('admin', $user->roles, true)) {
                return true;
            }
            // Spatie kullanıyorsan:
            // return $user->hasRole('admin') ? true : null;
            return null;
        });
    }
}