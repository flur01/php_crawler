<?php

namespace TypeCrawlers;

use Core\Crawler;
use Core\CrawlerInterface;


class StartPageCrawler extends Crawler implements CrawlerInterface 
{
    public function crawl()
    {
        $xpath = $this->xpath;
        $hrefs = $xpath->query("//ul[@class='dnrg']/li/a/@href");  
        foreach ($hrefs as $i => $href) {
            $url = $this->baseURI . $href->value;
            $this->data[] = [
                'url' => $url,
                'class' => 'TypeCrawlers\SitesCrawler'
            ];
        }
    }

    public function action()
    {
        $this->addToQueue();
    }
}