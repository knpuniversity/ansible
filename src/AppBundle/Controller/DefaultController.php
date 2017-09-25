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

        // Caching
        $uploadsItem = $this->getAppCache()->getItem('total_video_uploads_count');
        if (!$uploadsItem->isHit()) {
            $uploadsItem->set($this->countTotalVideoUploads());
            $uploadsItem->expiresAfter(60);
            // defer cache item saving
            $this->getAppCache()->saveDeferred($uploadsItem);
        }
        $totalVideoUploadsCount = $uploadsItem->get();

        $viewsItem = $this->getAppCache()->getItem('total_video_views_count');
        if (!$viewsItem->isHit()) {
            $viewsItem->set($this->countTotalVideoViews());
            $viewsItem->expiresAfter(60);
            // defer cache item saving
            $this->getAppCache()->saveDeferred($viewsItem);
        }
        $totalVideoViewsCount = $viewsItem->get();

        // save all deferred cache items
        $this->getAppCache()->commit();

        return $this->render('default/index.html.twig', [
            'videos' => $videos,
            'tags' => $tags,
            'totalVideoUploadsCount' => $totalVideoUploadsCount,
            'totalVideoViewsCount' => $totalVideoViewsCount,
        ]);
    }

    /**
     * NOTE: This page should not query the DB!
     *
     * @Route("/about", name="about")
     */
    public function aboutAction()
    {
        return $this->render('default/about.html.twig');
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
     * @return int
     */
    private function countTotalVideoUploads()
    {
        sleep(1); // simulating a long computation: waiting for 1s

        $fakedCount = intval(date('Hms') . rand(1, 9));

        return $fakedCount;
    }

    /**
     * @return int
     */
    private function countTotalVideoViews()
    {
        sleep(1); // simulating a long computation: waiting for 1s

        $fakedCount = intval(date('Hms') . rand(1, 9)) * 111;

        return $fakedCount;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    private function getVideoRepository()
    {
        return $this->get('doctrine')->getRepository(Video::class);
    }

    private function getAppCache()
    {
        return $this->get('cache.app');
    }
}
