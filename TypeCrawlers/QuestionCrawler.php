<?php

namespace TypeCrawlers;

use Core\Crawler;
use Core\CrawlerInterface;


class QuestionCrawler extends Crawler implements CrawlerInterface 
{
    public function crawl()
    {
        $xpath = $this->xpath;
        $hrefs = $xpath->query("//td[@class='Question']/a/@href");
        $urls = [];  
        foreach ($hrefs as $i => $href) {
            $url = $this->baseURI . $href->value;
            $urls[] = $url; 
        }
        $urls = array_unique($urls);
        foreach ($urls as $url) {
            $this->data[] = [
                'url' => $url,
                'class' => 'TypeCrawlers\DataCrawler'
            ];
        }
    }

    public function action()
    {
        $this->addToQueue();
    }
}