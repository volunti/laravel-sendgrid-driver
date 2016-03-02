<?php
namespace Sichikawa\LaravelSendgridDriver\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Swift_Attachment;
use Swift_Events_EventListener;
use Swift_Mime_Message;
use Swift_Transport;

class SendgridTransport implements Swift_Transport
{
    const MAXIMUM_FILE_SIZE = 7340032;

    private $api_key;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        list($from, $fromName) = $this->getFromAddresses($message);

        $data = [
            'from'     => $from,
            'fromname' => isset($fromName) ? $fromName : null,
            'subject'  => $message->getSubject(),
            'html'     => $message->getBody()
        ];

        $this->setTo($data, $message);
        $this->setCc($data, $message);
        $this->setBcc($data, $message);
        $this->setAttachment($data, $message);

        $options = [
            'headers' => ['Authorization' => 'Bearer ' . $this->api_key, 'Content-Type' => 'multipart/form-data']
        ];

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $options += ['form_params' => $data];
        } else {
            $options += ['body' => $data];
        }

        $client = $this->getHttpClient();
        return $client->post('https://api.sendgrid.com/api/mail.send.json', $options);
    }

    /**
     * @param  $data
     * @param  Swift_Mime_Message $message
     */
    protected function setTo(&$data, Swift_Mime_Message $message)
    {
        if ($from = $message->getTo()) {
            $data['to'] = array_keys($from);
            $data['toname'] = array_values($from);
        }
    }

    /**
     * @param $data
     * @param Swift_Mime_Message $message
     */
    protected function setCc(&$data, Swift_Mime_Message $message)
    {
        if ($cc = $message->getCc()) {
            $data['cc'] = array_keys($cc);
            $data['ccname'] = array_values($cc);
        }
    }

    /**
     * @param $data
     * @param Swift_Mime_Message $message
     */
    protected function setBcc(&$data, Swift_Mime_Message $message)
    {
        if ($bcc = $message->getBcc()) {
            $data['bcc'] = array_keys($bcc);
            $data['bccname'] = array_values($bcc);
        }
    }

    /**
     * Set Attachment Files.
     *
     * @param $data
     * @param Swift_Mime_Message $message
     */
    protected function setAttachment(&$data, Swift_Mime_Message $message)
    {
        foreach ($message->getChildren() as $attachment) {
            if (!$attachment instanceof Swift_Attachment || !strlen($attachment->getBody()) > self::MAXIMUM_FILE_SIZE) {
                continue;
            }
            $handler = tmpfile();
            fwrite($handler, $attachment->getBody());
            $data['files[' . $attachment->getFilename() . ']'] = $handler;
        }
    }

    /**
     * Get From Addresses.
     *
     * @param Swift_Mime_Message $message
     * @return array
     */
    protected function getFromAddresses(Swift_Mime_Message $message)
    {
        if ($message->getFrom()) {
            foreach ($message->getFrom() as $address => $name) {
                return [$address, $name];
            }
        }
        return [];
    }

    /**
     * Get a new HTTP client instance.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        return new Client;
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        //
    }
}