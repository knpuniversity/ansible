<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Video;
use Predis\Connection\ConnectionException;
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

        // Redis cache
        try {
            if ($this->getRedisClient()->exists('total_video_uploads_count')) {
                $totalVideoUploadsCount = $this->getRedisClient()->get('total_video_uploads_count');
            } else {
                $totalVideoUploadsCount = $this->countTotalVideoUploads();
                $this->getRedisClient()->set('total_video_uploads_count', $totalVideoUploadsCount, 'ex', 60); // 60s
            }

            if ($this->getRedisClient()->exists('total_video_views_count')) {
                $totalVideoViewsCount = $this->getRedisClient()->get('total_video_views_count');
            } else {
                $totalVideoViewsCount = $this->countTotalVideoViews();
                $this->getRedisClient()->set('total_video_views_count', $totalVideoViewsCount, 'ex', 60); // 60s
            }
        } catch (ConnectionException $e) {
            $totalVideoUploadsCount = $this->countTotalVideoUploads();
            $totalVideoViewsCount = $this->countTotalVideoViews();
        }

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

    /**
     * @return object|\Predis\Client
     */
    private function getRedisClient()
    {
        return $this->get('snc_redis.default_client');
    }
}
