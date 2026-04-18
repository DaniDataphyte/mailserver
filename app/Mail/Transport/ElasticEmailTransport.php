<?php

namespace App\Mail\Transport;

use ElasticEmail\Api\EmailsApi;
use ElasticEmail\Configuration;
use ElasticEmail\Model\EmailContent;
use ElasticEmail\Model\EmailMessageData;
use ElasticEmail\Model\EmailRecipient;
use ElasticEmail\Model\BodyPart;
use ElasticEmail\Model\EncodingType;
use ElasticEmail\Model\Options;
use GuzzleHttp\Client;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class ElasticEmailTransport extends AbstractTransport
{
    private EmailsApi $api;

    public function __construct(string $apiKey)
    {
        parent::__construct();

        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('X-ElasticEmail-ApiKey', $apiKey);

        $this->api = new EmailsApi(new Client(), $config);
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        // Read the campaign send ID set by NewsletterMailable::headers().
        // Passed as a recipient custom field so Elastic Email echoes it back
        // in every webhook notification payload — allows direct CampaignSend
        // lookup without relying on Elastic Email's own TransactionID.
        $campaignSendId = $email->getHeaders()->get('X-Campaign-Send-Id')
            ?->getBodyAsString();

        // Build recipients
        $recipients = [];
        foreach ($email->getTo() as $address) {
            $recipient = new EmailRecipient();
            $recipient->setEmail($address->getAddress());
            if ($campaignSendId) {
                $recipient->setFields(['send_id' => $campaignSendId]);
            }
            $recipients[] = $recipient;
        }

        // Build body parts
        $bodyParts = [];

        if ($email->getHtmlBody()) {
            $htmlPart = new BodyPart();
            $htmlPart->setContentType('HTML');
            $htmlPart->setContent($email->getHtmlBody());
            $htmlPart->setCharset('UTF-8');
            $bodyParts[] = $htmlPart;
        }

        if ($email->getTextBody()) {
            $textPart = new BodyPart();
            $textPart->setContentType('PlainText');
            $textPart->setContent($email->getTextBody());
            $textPart->setCharset('UTF-8');
            $bodyParts[] = $textPart;
        }

        // Build content
        $content = new EmailContent();
        $content->setSubject($email->getSubject());
        $content->setBody($bodyParts);

        // From address
        $fromAddresses = $email->getFrom();
        if (! empty($fromAddresses)) {
            $from = array_values($fromAddresses)[0];
            $fromString = $from->getName()
                ? "\"{$from->getName()}\" <{$from->getAddress()}>"
                : $from->getAddress();
            $content->setFrom($fromString);
        }

        // Reply-To
        $replyToAddresses = $email->getReplyTo();
        if (! empty($replyToAddresses)) {
            $replyTo = array_values($replyToAddresses)[0];
            $content->setReplyTo($replyTo->getAddress());
        }

        // Custom headers (pass through List-Unsubscribe etc.)
        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            $name = $header->getName();
            if (in_array(strtolower($name), ['list-unsubscribe', 'list-unsubscribe-post'])) {
                $headers[$name] = $header->getBodyAsString();
            }
        }
        if (! empty($headers)) {
            $content->setHeaders($headers);
        }

        // Explicitly enable open + click tracking regardless of account defaults
        $options = new Options();
        $options->setTrackOpens(true);
        $options->setTrackClicks(true);

        // Build and send
        $messageData = new EmailMessageData();
        $messageData->setRecipients($recipients);
        $messageData->setContent($content);
        $messageData->setOptions($options);

        $result = $this->api->emailsPost($messageData);

        // Store the Elastic Email transaction ID on the message for webhook correlation
        if ($result && $result->getTransactionId()) {
            $message->getOriginalMessage()
                ->getHeaders()
                ->addTextHeader('X-ElasticEmail-TransactionId', $result->getTransactionId());
        }
    }

    public function __toString(): string
    {
        return 'elasticemail';
    }
}
