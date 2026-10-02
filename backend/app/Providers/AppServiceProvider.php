<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\WellbeingEntry;
use App\Policies\ConversationPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PlanPolicy;
use App\Policies\WellbeingEntryPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
        Gate::policy(Plan::class, PlanPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(WellbeingEntry::class, WellbeingEntryPolicy::class);

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });
    }
}
