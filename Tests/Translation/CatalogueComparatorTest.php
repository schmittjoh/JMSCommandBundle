<?php

namespace JMS\CommandBundle\Tests\Translation;

use JMS\CommandBundle\Translation\CatalogueComparator;

use Symfony\Component\Translation\MessageCatalogue;

class CatalogueComparatorTest extends \PHPUnit_Framework_TestCase
{
    public function testCompare()
    {
        $a = new MessageCatalogue('de');
        $a->add(array('a' => 'foo', 'b' => 'foo'), 'messages');

        $b = new MessageCatalogue('de');
        $b->add(array('b' => 'bar', 'c' => 'bar'), 'messages');

        $rs = $this->compare($a, $b);
        $this->assertEquals(array('messages' => array('c' => 'bar')), $rs->getAddedMessages());
        $this->assertEquals(array('messages' => array('a' => 'foo')), $rs->getDeletedMessages());
    }

    private function compare(MessageCatalogue $a, MessageCatalogue $b)
    {
        $c = new CatalogueComparator();

        return $c->compare($a, $b);
    }
}