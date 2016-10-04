<?php

namespace Tokenly\CopayClient\Provider;

use Illuminate\Support\ServiceProvider;
use Exception;

/*
* A CopayClientServiceProvider for Laravel Applications
*/
class CopayClientServiceProvider extends ServiceProvider
{

    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Tokenly\CopayClient\CopayClient', function($app) {
            $copay_service_url = env('COPAY_SERVICE_URL', 'https://pockets-service.tokenly.com/bws/api');
            $copay_client = new \Tokenly\CopayClient\CopayClient($copay_service_url);
            return $copay_client;
        });

        $this->app->bind('Tokenly\CopayClient\EncryptionService\EncryptionServiceClient', function($app) {
            $encryption_service_url = env('ENCRYPTION_SERVICE_URL', 'http://127.0.0.1:8088');
            $encryption_service_client = new \Tokenly\CopayClient\EncryptionService\EncryptionServiceClient($encryption_service_url);
            return $encryption_service_client;
        });
    }


}

