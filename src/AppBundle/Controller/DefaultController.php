<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Video;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $videos = $this->getVideoRepository()
            ->findAll();

        return $this->render('default/index.html.twig', [
            'videos' => $videos,
        ]);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    private function getVideoRepository()
    {
        return $this->get('doctrine')->getRepository(Video::class);
    }
}
