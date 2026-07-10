<?php

namespace Susheelbhai\Larapay\Commands;

use Illuminate\Console\Command;
use Susheelbhai\Larapay\Support\EnvSectionWriter;

class InitialSettings extends Command
{
    protected $signature = 'larapay:initial_settings';

    protected $description = 'To change some initial configuration which is required on starting new project';

    public const SECTION_HEADER = '# Larapay';

    /** @var array<string, string> */
    public array $env_values = [
        'PAYMENT_TEMPLATE' => 'blade',
        'PAYMENT_ENV' => 'local',
        'PAYMENT_GATEWAY_ID' => '2',
        'PAYMENT_REDIRECTION_WAITING_TIME' => '7',
        'ENABLE_PAYMENT_RESPONSE' => '0',
        'PAYMENT_FAVICON' => '0',
        'RAZORPAY_KEY_ID' => '',
        'RAZORPAY_KEY_SECRET' => '',
        'PINELAB_MERCHANT_ID' => '',
        'PINELAB_ACCESS_CODE' => '',
        'PINELAB_SECRET_CODE' => '',
        'STRIPE_PUBLISHABLE_KEY' => '',
        'STRIPE_SECRET_KEY' => '',
        'PHONEPE_VERSION' => 'M110NES2UDXSUAT',
        'PHONEPE_CLIENT_VERSION' => '1',
        'PHONEPE_CLIENT_ID' => 'sdfsdfasafs',
        'PHONEPE_CLIENT_SECRET' => 'asfdasdfasfd',
        'PHONEPE_MERCHANT_ID' => 'M110NES2UDXSUAT',
        'PHONEPE_API_KEY' => '5afb2d8c-5572-47cf-a5a0-93bb79647ffa',
        'PHONEPE_SALT_KEY' => '5afb2d8c-5572-47cf-a5a0-93bb79647ffa',
        'PHONEPE_SALT_INDEX' => '1',
        'CCAVANUE_MERCHANT_ID' => '',
        'CCAVANUE_ACCESS_CODE' => '',
        'CCAVANUE_WORKING_KEY' => '',
        'CASHFREE_APP_ID' => '',
        'CASHFREE_SECRET_KEY' => '',
        'CASHFREE_API_VERSION' => '2023-08-01',
        'PAYU_MERCHANT_ID' => 'JGHFfgh',
        'PAYU_MERCHANT_SALT' => 'JGHFfgh',
        'PAYU_CLIENT_ID' => 'JGHFfgh',
        'PAYU_CLIENT_SECRET' => 'JGHFfgh',
    ];

    public function handle(): int
    {
        $this->setEnvironmentValue($this->env_values);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $values
     */
    public function setEnvironmentValue(array $values): bool
    {
        $envFile = app()->environmentFilePath();
        $content = file_get_contents($envFile);

        $content = EnvSectionWriter::sync($content, self::SECTION_HEADER, $values);
        $content = rtrim($content, "\n")."\n";

        if (! file_put_contents($envFile, $content)) {
            return false;
        }

        $this->line('Environment Variable changed');

        return true;
    }
}
