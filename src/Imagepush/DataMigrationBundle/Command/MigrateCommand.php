<?php

namespace Imagepush\DataMigrationBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\ClassMetadata;
use Imagepush\ImagepushBundle\Entity\BestImage;
use Imagepush\ImagepushBundle\Document\BestImage as OldBestImage;
use Imagepush\ImagepushBundle\Entity\Image;
use Imagepush\ImagepushBundle\Document\Image as OldImage;
use Imagepush\ImagepushBundle\Entity\Link;
use Imagepush\ImagepushBundle\Document\Link as OldLink;
use Imagepush\ImagepushBundle\Entity\ProcessedHash;
use Imagepush\ImagepushBundle\Document\ProcessedHash as OldHash;
use Imagepush\ImagepushBundle\Entity\Tag;
use Imagepush\ImagepushBundle\Document\Tag as OldTag;
use Imagepush\ImagepushBundle\Entity\LatestTag;
use Imagepush\ImagepushBundle\Document\LatestTag as OldLatestTag;
use Imagepush\ImagepushBundle\Enum\LinkStatusEnum;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends ContainerAwareCommand
{

    /**
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $dm;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('imagepush:migrate')
            ->setDescription('Migrate data from MongoDB to Mysql (should be run once)')
            ->addOption('links')
            ->addOption('images')
            ->addOption('tags')
            ->addOption('hashes')
            ->addOption('all')
            ;
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $this->getContainer()->get('profiler')->disable();

        if ($input->getOption('links') || $input->getOption('all')) {
            $this->migrateLinks();
        }

        if ($input->getOption('tags') || $input->getOption('all')) {
//            $this->migrateTags();
            $this->migrateLatestTags();
        }

        if ($input->getOption('images') || $input->getOption('all')) {
            $this->migrateImages();
        }

        if ($input->getOption('hashes') || $input->getOption('all')) {
            $this->migrateProcessedHashes();
        }

//        if ($input->getOption('votes') || $input->getOption('all')) {
//            $this->migrateBestImages();
//            //$this->migrateVotes();
//        }

        $this->em->flush();
        $output->writeln('Done');
    }

    protected function migrateImages()
    {
        $i = 0;

        // Set ID to NONE during import, because ID is assigned manually
        $metadata = $this->em->getClassMetaData('Imagepush\ImagepushBundle\Entity\Image');
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $images = $this->dm->getRepository('ImagepushBundle:Image')
            ->createQueryBuilder()
            //->field('id')
            //->gt(44028)
            ->sort('id', 'ASC')
            //->limit(100)
            ->getQuery()
            ->toArray()
        ;

        foreach ($images as $oldImage) {
            $matchedTags = new ArrayCollection();

            if (null != $oldImage->getTags()) {
                $expr = Criteria::expr();
                $criteria = Criteria::create()->where($expr->in('text', $oldImage->getTags()));
                $matchedTags = $this->em->getRepository('ImagepushBundle:Tag')->matching($criteria);
            }

            /** @var OldImage $oldImage */
            //var_dump($oldImage);
            $image = new Image();
            $image
                ->setId($oldImage->getId())
                ->setAvailable($oldImage->getIsAvailable())
                ->setFile($oldImage->getFile())
                ->setInProcess((bool) $oldImage->getIsInProcess())
                ->setLink($oldImage->getLink())
                ->setMimeType($oldImage->getMimeType())
                ->setSlug($oldImage->getSlug())
                ->setTitle($oldImage->getTitle())
                ->setSourceType($oldImage->getSourceType())
                ->setSourceTags($oldImage->getSourceTags())
                ->setCreatedAt($oldImage->getDatetime())
                ->setUpdatedAt($oldImage->getDatetime())
                ->setMimeType($oldImage->getMimeType())
                ->setTags($matchedTags)
                ->setTagsFound($oldImage->getTagsFound())
                ->setThumbs($oldImage->getThumbs())
            ;

            if (null == $image->getFile()) {
                continue;
            }

            $this->em->persist($image);

            if ($i++ % 200 == 0) {
                try {
                    $this->em->flush();
                } catch (\Exception $e) {
                    echo $oldImage->getId().' => '.$e->getMessage();
                    echo "\n";
                }
            }
        }

    }

    protected function migrateLinks()
    {
        $i = 0;

        $links = $this->dm->getRepository('ImagepushBundle:Link')
            ->createQueryBuilder()
            //->sort('timestamp', 'ASC')
            //->limit(5000)
            ->getQuery()
            ->toArray()
        ;

        foreach ($links as $oldLink) {
            /** @var OldLink $oldLink */
            $link = new Link();
            $link
                ->setLink($oldLink->getLink())
                ->setStatus(LinkStatusEnum::get($oldLink->getStatus()))
            ;

            $this->em->persist($link);

            if ($i++ % 2000 == 0) {
                try {
                    $this->em->flush();
                } catch (\Exception $e) {
                    echo $oldLink->getMongoId().' => '.$e->getMessage();
                    echo "\n";
                }
            }
        }

    }

    protected function migrateProcessedHashes()
    {
        $i = 0;

        $hashes = $this->dm->getRepository('ImagepushBundle:ProcessedHash')
            ->createQueryBuilder()
            //->limit(5000)
            ->getQuery()
            ->toArray()
        ;

        foreach ($hashes as $oldHash) {
            /** @var OldHash $oldHash */

            if (null == $oldHash->getHash()) {
                continue;
            }

            $hash = new ProcessedHash();
            $hash
                ->setHash($oldHash->getHash())
            ;

            $this->em->persist($hash);

            if ($i++ % 2000 == 0) {
                try {
                    $this->em->flush();
                } catch (\Exception $e) {
                    echo $oldHash->getMongoId().' => '.$e->getMessage();
                    echo "\n";
                }
            }
        }

    }

    protected function migrateBestImages()
    {
        $i = 0;

        $images = $this->dm->getRepository('ImagepushBundle:BestImage')
            ->createQueryBuilder()
            ->getQuery()
            ->toArray()
        ;

        foreach ($images as $oldBestImage) {
            /** @var OldBestImage $oldBestImage */

            $image = new BestImage();
            $image
                ->setImageId($oldBestImage->getImageId())
                ->setTimestamp(new \DateTime("@" . $oldBestImage->getTimestamp()->__toString()))
            ;

            $this->em->persist($image);

            if ($i++ % 2000 == 0) {
                try {
                    $this->em->flush();
                } catch (\Exception $e) {
                    echo $oldBestImage->getMongoId().' => '.$e->getMessage();
                    echo "\n";
                }
            }
        }

    }

    protected function migrateTags()
    {
        $i = 0;

        $tags = $this->dm->getRepository('ImagepushBundle:Tag')
            ->createQueryBuilder()
            ->getQuery()
            ->toArray()
        ;

        foreach ($tags as $oldTag) {
            /** @var OldTag $oldTag */

            $tag = new Tag();
            $tag
                ->setText($oldTag->getText())
                ->setUsedInAvailable((int) $oldTag->getUsedInAvailable())
                ->setUsedInUpcoming((int) $oldTag->getUsedInUpcoming())
            ;

            $this->em->persist($tag);

            if ($i++ % 2000 == 0) {
                try {
                    $this->em->flush();
                } catch (\Exception $e) {
                    echo $oldTag->getMongoId().' => '.$e->getMessage();
                    echo "\n";
                }
            }
        }

    }

    protected function migrateLatestTags()
    {
        $i = 0;

        $oldLatestTags = $this->dm->getRepository('ImagepushBundle:LatestTag')
            ->createQueryBuilder()
            //->limit(20)
            ->getQuery()
            ->toArray()
        ;

        foreach ($oldLatestTags as $oldLatestTag) {
            /** @var OldLatestTag $oldLatestTag */

            $matchedTag = $this->em->getRepository('ImagepushBundle:Tag')->findOneByText($oldLatestTag->getText());

            if (null == $matchedTag) {
                continue;
            }

            $latestTag = new LatestTag();
            $latestTag
                ->setTag($matchedTag)
                ->setCreatedAt(new \DateTime("@" . $oldLatestTag->getTimestamp()->__toString()))
            ;

            $this->em->persist($latestTag);

            if ($i++ % 2000 == 0) {
                try {
                    $this->em->flush();
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    echo "\n";
                }
            }
        }

    }

}
