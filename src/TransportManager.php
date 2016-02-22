<?php
namespace Sichikawa\LaravelSendgridDriver;

use Sichikawa\LaravelSendgridDriver\Transport\SendgridTransport;

class TransportManager extends \Illuminate\Mail\TransportManager
{
    /**
     * Create an instance of the SendGrid Swift Transport driver.
     *
     * @return Transport\SendGridTransport
     */
    protected function createSendgridDriver()
    {
        $config = $this->app['config']->get('services.sendgrid', array());
        return new SendgridTransport($config['api_key']);
    }
}