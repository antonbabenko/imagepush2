<?php

namespace Imagepush\ImagepushBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Imagepush\ImagepushBundle\Entity\Tag;

class LoadTagData extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $om)
    {
        $tags = [
            'tag1',
            'tag2',
            'tag3',
            'tag4',
            'tag5',
        ];

        $ref = 0;

        foreach ($tags as $text) {
            $tag = new Tag();
            $tag->setText($text);

            $this->addReference('tag-' . ++$ref, $tag);
            $om->persist($tag);

            $om->flush();
        }

    }

}
