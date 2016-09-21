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
        $tags = $this->getUniqueOrderedTags($videos);

        return $this->render('default/index.html.twig', [
            'videos' => $videos,
            'tags' => $tags,
        ]);
    }

    /**
     * @param Video[] $videos
     *
     * @return array
     */
    private function getUniqueOrderedTags(array $videos)
    {
        $tags = [];

        foreach ($videos as $video) {
            foreach ($video->getTags() as $tag) {
                if (!in_array($tag, $tags)) {
                    $tags[] = $tag;
                }
            }
        }

        sort($tags);

        return $tags;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    private function getVideoRepository()
    {
        return $this->get('doctrine')->getRepository(Video::class);
    }
}
