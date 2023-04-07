<?php

namespace Susheelbhai\Larapay\Commands;

use Illuminate\Console\Command;

class initial_settings extends Command
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
        'RAZORPAY_KEY_ID' => 'rzp_test_qmRQ26ltomg3mz',
        'RAZORPAY_KEY_SECRET' => 'K8NDGM0sp2oRTJXXfPRi6Dlx'
    );
    public $config_values = array(
        'timezone' => 'Asia/Kolkata'
    );

    public function handle()
    {

        $this->setEnvironmentValue($this->env_values);
        $this->setConfigValue($this->config_values);
    }

    public function setEnvironmentValue(array $values)
    {

        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {

                $str .= "\n"; // In case the searched variable is in the last line without \n
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }
            }
        }

        $str = substr($str, 0, -1);
        if (!file_put_contents($envFile, $str)) return false;
        $this->line("Environment Variable changed");
        return true;
    }

    public function setConfigValue(array $values)
    {
        $path = base_path('config/app.php');
        $str = file_get_contents($path);

        if (count($values) > 0) {
            foreach ($values as $configKey => $configValue) {

                $str .= "\n'"; // In case the searched variable is in the last line without \n
                $keyPosition = strpos($str, "{$configKey}' => ");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$configKey}' => '{$configValue}',\n";
                } else {
                    $str = str_replace($oldLine, "{$configKey}' => '{$configValue}',", $str);
                }
            }
        }

        $str = substr($str, 0, -1);
        if (!file_put_contents($path, $str)) return false;
        $this->line("Config Variable changed");
        return true;
    }
}