<?php

namespace JMS\CommandBundle\Translation;

class ComparisonResult
{
    private $addedMessages;
    private $deletedMessages;

    public function __construct(array $addedMessages, array $deletedMessages)
    {
        $this->addedMessages = $addedMessages;
        $this->deletedMessages = $deletedMessages;
    }

    public function getAddedMessages($domain = null)
    {
        if (null === $domain) {
            return $this->addedMessages;
        }

        if (!isset($this->addedMessages[$domain])) {
            return array();
        }

        return $this->addedMessages[$domain];
    }

    public function getDeletedMessages($domain = null)
    {
        if (null === $domain) {
            return $this->deletedMessages;
        }

        if (!isset($this->deletedMessages[$domain])) {
            return array();
        }

        return $this->deletedMessages[$domain];
    }
}