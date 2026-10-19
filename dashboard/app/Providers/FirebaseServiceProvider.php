<?php

namespace App\Providers;

use App\CentralLogics\Helpers;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('firebase.firestore', function ($app) {
            $serviceAccountKey = Helpers::get_business_settings('push_notification_service_file_content')??[];
            if(count($serviceAccountKey)>0){
                $serviceAccount = $serviceAccountKey;
                return (new Factory)
                    ->withServiceAccount($serviceAccount)
                    ->createMessaging();
            }
            return false;
        });

        $this->app->singleton('firebase.messaging', function ($app) {
            $serviceAccountKey = \App\CentralLogics\Helpers::get_business_settings('push_notification_service_file_content') ?? [];

            $serviceAccount = null;
            if (is_array($serviceAccountKey)) {
                if (!empty($serviceAccountKey['service_file_content'])) {
                    $serviceAccount = is_array($serviceAccountKey['service_file_content'])
                        ? $serviceAccountKey['service_file_content']
                        : json_decode($serviceAccountKey['service_file_content'], true);
                } elseif (!empty($serviceAccountKey['project_id'])) {
                    $serviceAccount = $serviceAccountKey;
                }
            } elseif (is_string($serviceAccountKey)) {
                $serviceAccount = json_decode($serviceAccountKey, true);
            }

            if (is_array($serviceAccount) && !empty($serviceAccount['project_id'])) {
                try {
                    return (new Factory)
                        ->withServiceAccount($serviceAccount)
                        ->createMessaging();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Firebase messaging initialization failed: ' . $e->getMessage());
                    return false;
                }
            }
            return false;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
