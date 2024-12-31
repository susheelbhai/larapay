<?php

namespace Susheelbhai\Larapay\Commands;

use Illuminate\Console\Command;

class InitialSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larapay:initial_settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To change some initial configuration which is required on starting new project';

    /**
     * Execute the console command.
     */

    public $env_values = array(
         'PAYMENT_ENV' => 'local',
         'PAYMENT_GATEWAY_ID' => '2',
         'PAYMENT_REDIRECTION_WAITIONG_TIME' => '7',
         'UNABLE_PAYMENT_RESPONSE' => '0',
         'PAYMENT_FAVICON' => '0',

        'RAZORPAY_KEY_ID' => '',
        'RAZORPAY_KEY_SECRET' => '',

        'PINELAB_MERCHANT_ID' => '',
        'PINELAB_ACCESS_CODE' => '',
        'PINELAB_SECRET_CODE' => '',
        
        'STRIPE_PUBLISHABLE_KEY' => '',
        'STRIPE_SECRET_KEY' => '',
        
        'PHONEPE_MERCHANT_ID' => '',
        'PHONEPE_API_KEY' => '',
        'PHONEPE_SALT_KEY' => '',
        'PHONEPE_SALT_INDEX' => '1',
        
        'CCAVANUE_MERCHANT_ID' => '',
        'CCAVANUE_ACCESS_CODE' => '',
        'CCAVANUE_WORKING_KEY' => '',

        'CASHFREE_APP_ID' => '',
        'CASHFREE_SECRET_KEY' => '',
    );
    

    public function handle()
    {

        $this->setEnvironmentValue($this->env_values);
    }

    public function setEnvironmentValue(array $values)
    {

        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        if (count($values) > 0) {
            $str .= "\n\n"; // In case the searched variable is in the last line without \n
            foreach ($values as $envKey => $envValue) {

                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    // $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }
            }
        }

        $str = substr($str, 0, -1);
        if (!file_put_contents($envFile, $str)) return false;
        $this->line("Environment Variable changed");
        return true;
    }

}
