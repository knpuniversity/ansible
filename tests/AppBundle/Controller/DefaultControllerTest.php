<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('MooTube Bovine Fitness', $crawler->filter('h1')->text());
        $this->assertContains('Filter by Tag', $crawler->text());
        $this->assertContains('Bovine Fitness Routines', $crawler->text());
    }

    public function testAboutAction()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/about');

        $this->assertContains('Welcome to MooTube', $crawler->text());

        $linkElement = $crawler->filter('.btn-explore');
        $this->assertContains('Explore the content', $linkElement->text());
        $this->assertEquals('/', $linkElement->attr('href'));
    }
}
