<?php

namespace JMS\CommandBundle\Translation;

use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Compares two message catalogues.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class CatalogueComparator
{
    private $ignoredDomains = array();

    public function addIgnoredDomain($domain)
    {
        $this->ignoredDomains[$domain] = true;
    }

    /**
     * Compares two message catalogues.
     *
     * @param MessageCatalogueInterface $a
     * @param MessageCatalogueInterface $b
     * @throws \RuntimeException
     * @return \JMS\CommandBundle\Translation\ComparisonResult
     */
    public function compare(MessageCatalogueInterface $a, MessageCatalogueInterface $b)
    {
        if ($a->getLocale() !== $b->getLocale()) {
            throw new \RuntimeException(sprintf('Both catalogues must have the same locale, but got "%s" and "%s".', $a->getLocale(), $b->getLocale()));
        }

        $addedMessages = $this->computeDiff($a, $b);
        $deletedMessages = $this->computeDiff($b, $a);

        return new ComparisonResult($addedMessages, $deletedMessages);
    }

    /**
     * Computes all the messages that are in b, but not in a.
     *
     * @param MessageCatalogueInterface $a
     * @param MessageCatalogueInterface $b
     * @return array
     */
    private function computeDiff(MessageCatalogueInterface $a, MessageCatalogueInterface $b)
    {
        $aDomains = $a->getDomains();
        $bDomains = $b->getDomains();
        $diff = array();
        foreach ($bDomains as $domain) {
            if (isset($this->ignoredDomains[$domain])) {
                continue;
            }

            if (!in_array($domain, $aDomains, true)) {
                $diff[$domain] = $b->all($domain);
                continue;
            }

            $kDiff = array_diff_key($b->all($domain), $a->all($domain));
            if (!empty($kDiff)) {
                $diff[$domain] = $kDiff;
            }
        }

        return $diff;
    }
}