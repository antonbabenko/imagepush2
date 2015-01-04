<?php

namespace Imagepush\ImagepushBundle\DataFixtures\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Imagepush\ImagepushBundle\Entity\Image;
use Imagepush\ImagepushBundle\Entity\LatestTag;

class LoadImageData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    public static $tagCounter = [];

    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $om)
    {
        $this->om = $om;

        $this->loadImage(1, true, ['tag-1', 'tag-2']);
        $this->loadImage(2, true, ['tag-1', 'tag-3']);
        $this->loadImage(3, true, ['tag-1', 'tag-3']);
        $this->loadImage(4, true, ['tag-1', 'tag-3']);
        $this->loadImage(5, true, ['tag-1', 'tag-5']);
        $this->loadImage(6, false, ['tag-1', 'tag-2']);
        $this->loadImage(7, false, ['tag-1', 'tag-4']);

        $this->recountUsedTags();
    }

    /**
     * @param integer $id
     * @param boolean $available
     * @param string[] $tags
     */
    protected function loadImage($id, $available, $tags)
    {

        $image = new Image();
        $image->setLink('http://example.com/article-' . $id);
        $image->setAvailable($available);
        $image->setTitle('Article title ' . $id);
        $image->setInProcess(false);
        $image->setSourceType('reddit');
        $image->setMimeType('image/jpeg');
        $image->setFile('file' . $id . '.jpg');
        $image->setThumbs(
            [
                "in/463x1548" => [
                    "w" => 463,
                    "h" => 463,
                    "s" => 54496
                ],
                "out/140x140" => [
                    "w" => 140,
                    "h" => 140,
                    "s" => 8046
                ],
                "in/625x2090" => [
                    "w" => 625,
                    "h" => 625,
                    "s" => 95527
                ]
            ]
        );

        $tagRefs = new ArrayCollection();
        foreach ($tags as $tag) {
            $tagRefs->add($this->getReference($tag));

            if ($available) {
                (isset(static::$tagCounter[$tag]['available']) ? static::$tagCounter[$tag]['available']++ : static::$tagCounter[$tag]['available'] = 1);
            } else {
                (isset(static::$tagCounter[$tag]['upcoming']) ? static::$tagCounter[$tag]['upcoming']++ : static::$tagCounter[$tag]['upcoming'] = 1);
            }
        }

        $image->setTags($tagRefs);

        $this->addReference('image-' . $id, $image);

        $this->om->persist($image);
        $this->om->flush();
    }

    protected function recountUsedTags()
    {

        foreach (static::$tagCounter as $tagRef => $tagCounter) {

            $counterAvailable = (isset($tagCounter['available']) ? $tagCounter['available'] : 0);
            $counterUpcoming = (isset($tagCounter['upcoming']) ? $tagCounter['upcoming'] : 0);

            $tag = $this->getReference($tagRef);
            $tag->setUsedInAvailable($counterAvailable);
            $tag->setUsedInUpcoming($counterUpcoming);

            $this->om->persist($tag);

            // latest tags
            for ($i=0;$i<$counterAvailable+$counterUpcoming;$i++) {
                $latestTag = new LatestTag();
                $latestTag->setTag($tag);

                $this->om->persist($latestTag);
            }

        }
        $this->om->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'Imagepush\\ImagepushBundle\\DataFixtures\\ORM\\LoadTagData',
        ];
    }

}
