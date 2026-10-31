<?php

namespace App\Providers;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $mode = env('APP_MODE');

        try {
            $emailServices = Helpers::get_business_settings('mail_config');
            if ($emailServices) {
                if (is_string($emailServices)) {
                    $emailServices = json_decode($emailServices, true);
                }
                if (is_array($emailServices)) {
                    $driver = $emailServices['driver'] ?? 'smtp';
                    $config = array(
                        'driver' => $driver,
                        'host' => $emailServices['host'] ?? '',
                        'port' => $emailServices['port'] ?? 587,
                        'username' => $emailServices['username'] ?? '',
                        'password' => $emailServices['password'] ?? '',
                        'encryption' => $emailServices['encryption'] ?? 'tls',
                        'from' => array('address' => $emailServices['email_id'] ?? '', 'name' => $emailServices['name'] ?? ''),
                        'sendmail' => '/usr/sbin/sendmail -bs',
                        'pretend' => false,
                    );
                    Config::set('mail', $config);

                    Config::set('mail.default', $driver);
                    Config::set('mail.mailers.smtp.transport', $driver);
                    Config::set('mail.mailers.smtp.host', $emailServices['host'] ?? '');
                    Config::set('mail.mailers.smtp.port', (int)($emailServices['port'] ?? 587));
                    Config::set('mail.mailers.smtp.encryption', $emailServices['encryption'] ?? 'tls');
                    Config::set('mail.mailers.smtp.username', $emailServices['username'] ?? '');
                    Config::set('mail.mailers.smtp.password', $emailServices['password'] ?? '');
                    Config::set('mail.from.address', $emailServices['email_id'] ?? '');
                    Config::set('mail.from.name', $emailServices['name'] ?? '');
                }
            }


            $timezone = Helpers::get_business_settings('timezone');
            if ($timezone) {
                Config::set('timezone', $timezone->value);
                date_default_timezone_set($timezone->value);
            }

            //time format setup
            $time_format = Helpers::get_business_settings('time_format');
            if ($time_format && $time_format == '12') {
                Config::set('time_format', 'h:i:s A');
            }
            else{
                Config::set('time_format', 'H:i:s');
            }

        } catch (\Exception $ex) {
        }
    }
}
