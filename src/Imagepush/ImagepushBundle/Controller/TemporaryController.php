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

        die();

        // 1) Update filesize in db
        // 2) Change content-type for thumbs
        // 3) Add header - Cache-Control: max-age=31536000, public

        $i = 0;
        $results = $ids = array();

        $imageFilters = array("in/463x1548", "in/625x2090", "out/140x140");

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $bucket = $this->container->getParameter('s3_bucket_name');

        $amazonS3 = $this->container->get('imagepush.amazon.s3');

        $images = $dm->getRepository('ImagepushBundle:Image')
            ->createQueryBuilder()
            ->sort('id', 'ASC')
            //->limit(10)
            ->getQuery()
            ->execute();

        if (!count($images)) {
            die("No images");
        }

        foreach ($images as $image) {
            //\D::dump($image);
            $mimeType = $image->getMimeType();
            if (empty($mimeType)) {
                $mimeType = "image/jpeg";
            }
            //\D::dump($mimeType);

            foreach ($imageFilters as $filter) {

                $path = $filter . '/i/' . $image->getFile();

                //\D::dump($path);

                $metadata = $amazonS3->get_object_metadata($bucket, $path);
                //\D::dump($metadata);
                //die();
                // Update content type for thumb
                if (empty($metadata["Headers"]["cache-control"]) ||
                    strlen($metadata["Headers"]["cache-control"]) < 10 ||
                    empty($metadata["ContentType"]) ||
                    $metadata["ContentType"] == "image/jpg" ||
                    $metadata["ContentType"] != $mimeType) {
                    $opt['headers']['Cache-Control'] = "max-age=31536000, public";

                    if (empty($metadata["ContentType"]) || $metadata["ContentType"] == "image/jpg" || $mimeType == "image/jpg") {
                        $mimeType = "image/jpeg";
                        $image->setMimeType($mimeType);
                    }

                    $result = $amazonS3->change_content_type($bucket, $path, $mimeType, $opt);

                    if (!$result->isOK()) {
                        $results[] = "Fail updating Content-Type or Cache-control (Status: " . $result->status . '. ID: ' . $image->getId() . '. File: ' . $path . ')';
                        $ids[] = $image->getId();
                    }
                }

                // Update filesize for thumb
                if (empty($metadata["Size"])) {
                    $results[] = "Empty Size for file " . $path;
                    $ids[] = $image->getId();
                } else {
                    $filterData = explode("/", $filter);
                    $filterDataSizes = explode("x", $filterData[1]);

                    if (!$image->getThumbProperty($filterData[0], $filterDataSizes[0], $filterDataSizes[1], "s")) {
                        $w = $image->getThumbProperty($filterData[0], $filterDataSizes[0], $filterDataSizes[1], "w");
                        $h = $image->getThumbProperty($filterData[0], $filterDataSizes[0], $filterDataSizes[1], "h");

                        //\D::dump($metadata["Size"] . "==" . $w . "==" . $h);

                        $image->addThumbs($filterData[0], $filterData[1], $w, $h, $metadata["Size"]);
                    }
                }
            }

            $dm->persist($image);

            if (++$i % 10 == 0) {
                $dm->flush();
                $dm->clear();

                $this->get('logger')->err('SUCCESSFULLY PROCESSED ID: ' . $image->getId());
            }
        }

        $dm->flush();
        $dm->clear();

        $this->get('logger')->err('SUCCESSFULLY PROCESSED ALL!!!!');

        $ids = array_unique($ids);
        echo "<br>Failed IDs: " . serialize($ids);

        echo "<pre>";
        print_r($results);
        echo "</pre>";

        //\D::dump($results);

        return array();
    }

}

