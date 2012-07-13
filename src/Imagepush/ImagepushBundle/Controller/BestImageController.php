<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Imagepush\ImagepushBundle\Document\BestImage;

class BestImageController extends Controller
{

    /**
     * @Route("/bestimage/{id}", requirements={"id"="\d+"}, name="markBestImage")
     * @Template()
     */
    public function markBestImageAction(Request $request, $id)
    {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        if ($request->query->get('hash') == md5($id)) {
            $image = $dm
                ->getRepository('ImagepushBundle:Image')
                ->findOneById((int) $id);
        } else {
            throw new \Exception("Incorrect hash");
        }

        //\D::dump($id);

        if (!$image) {
            throw new NotFoundHttpException('Image doesn\'t exist');
        } elseif ($image->getIsAvailable()) {
            throw new NotFoundHttpException('Image is already published. Try another.');
        }

        $bestImage = new BestImage();
        $bestImage->setImageId($id);
        $bestImage->setTimestamp(time());
        $dm->persist($bestImage);
        $dm->flush();

        return new Response("OK. Image id:" . $id);
    }

}
