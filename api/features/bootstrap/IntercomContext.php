<?php

use Behat\Behat\Context\Context;
use ContinuousPipe\Authenticator\Tests\Intercom\TraceableIntercomClient;

class IntercomContext implements Context
{
    /**
     * @var TraceableIntercomClient
     */
    private $traceableIntercomClient;

    /**
     * @param TraceableIntercomClient $traceableIntercomClient
     */
    public function __construct(TraceableIntercomClient $traceableIntercomClient)
    {
        $this->traceableIntercomClient = $traceableIntercomClient;
    }

    /**
     * @Then an intercom lead should be created for the email :email
     */
    public function anIntercomLeadShouldBeCreatedForTheEmail($email)
    {
        $matchingLeads = array_filter($this->traceableIntercomClient->getCreatedLeads(), function(array $lead) use ($email) {
            return $lead['email'] == $email;
        });

        if (count($matchingLeads) == 0) {
            throw new \RuntimeException('No matching created lead found');
        }
    }

    /**
     * @Then an intercom message should have been sent to :email
     */
    public function anIntercomMessageShouldHaveBeenSentTo($email)
    {
        $matchingMessages = array_filter($this->traceableIntercomClient->getSentMessages(), function(array $message) use ($email) {
            return $message['to']['email'] == $email;
        });

        if (count($matchingMessages) == 0) {
            throw new \RuntimeException('No matching message found');
        }

    }
}
