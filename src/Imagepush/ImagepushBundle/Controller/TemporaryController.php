<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Document\Tag;
use Imagepush\ImagepushBundle\Document\LatestTag;
use Imagepush\ImagepushBundle\Document\Link;
use Imagepush\ImagepushBundle\Document\ProcessedHash;

class TemporaryController extends Controller
{

    /**
     * @Route("/updateContentType", name="updateS3ContentType")
     * @Template("::base.html.twig")
     */
    public function updateS3ContentTypeAction()
    {

        // 1) Update filesize in db
        // 2) Change content-type for thumbs
        // 3) Add header - Cache-Control: max-age=31536000, public

        $getImageFileSize = false;
        $i = 0;

        $imageFilters = array("in/463x1548", "in/625x2090", "out/140x140");

        //$targetPath = 'out/140x140/i/4/43/438/47.jpg';
        $results = array();

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $bucket = $this->container->getParameter('s3_bucket_name');

        $amazonS3 = $this->container->get('imagepush.amazon.s3');

        $images = $dm->getRepository('ImagepushBundle:Image')
            ->createQueryBuilder()
            ->sort('id', 'ASC')
            ->limit(1)
            ->getQuery()
            ->execute();

        if (!count($images)) {
            die("No images");
        }

        foreach ($images as $image) {
            //\D::dump($image);
            $mimeType = $image->getMimeType();
            \D::dump($mimeType);

            $thumbs = $image->getThumbs();

            foreach ($imageFilters as $filter) {

                $path = $filter . '/i/' . $image->getFile();

                $metadata = $amazonS3->get_object_metadata($bucket, $path);
                \D::dump($metadata);

                // Update content type for thumb
                if (empty($metadata["ContentType"]) || $metadata["ContentType"] == "image/jpg" || $metadata["ContentType"] != $mimeType) {
                    //$result = $amazonS3->change_content_type($bucket, $path, $mimeType);
                    //\D::dump($result);
                }

                // Update filesize for thumb
                if (empty($metadata["Size"])) {
                    $results[] = "Empty Size for file " . $path;
                } else {
                    if ((empty($thumbs[$filter]) || !array_key_exists("s", $thumbs[$filter]) || (int) $thumbs[$filter]["s"] == 0)) {
                        $filterData = explode("/", $filter);
                        $filterDataSizes = explode("x", $filterData[1]);

                        $w = $image->getThumbProperty($filterData[0], $filterDataSizes[0], $filterDataSizes[1], "w");
                        $h = $image->getThumbProperty($filterData[0], $filterDataSizes[0], $filterDataSizes[1], "h");

                        //$image->addThumbs($filterData[0], $filterData[1], $w, $h, $metadata["Size"]);
                    }
                }
            }



            $dm->persist($image);

            if (++$i % 300 == 0) {
                //$dm->flush();
                $dm->clear();
            }
        }



        //$dm->flush();
        $dm->clear();

        return array();
    }

}

