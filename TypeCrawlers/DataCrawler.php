<?php

namespace TypeCrawlers;

use Core\Crawler;
use Core\CrawlerInterface;
use Core\Database\QueryBilder;


class DataCrawler extends Crawler implements CrawlerInterface 
{
    public function crawl()
    {
        $xpath = $this->xpath;
        $question = $xpath->query("//span[@id='HeaderString']");
        if (!isset($question[0]->textContent)) {
            $this->data[] = $this->queueData;
            $this->addToQueue();
            $logMessage = $this->queueData['url'] . "\n   content error return to QUEUE \n\n";
            echo $logMessage;
            fwrite($GLOBALS['logFile'], $logMessage);
            die();
        }

        $answer = $xpath->query("//td[@class='Answer']/a");
        $length = $xpath->query("//td[@class='Length']"); 
        $questionText = $question[0]->textContent;

        for ($i=0; $i<$answer->length; $i++) {
            $answerText = $answer[$i]->textContent;
            $lengthText = $length[$i]->textContent;
    

            $queryBilder = new QueryBilder();
            $questionId = $queryBilder->selectOneWhere('questions', 'id', ['question', $questionText]);
    
            if (!$questionId) {
                $questionId = $queryBilder->insert('questions', ['question' => $questionText]);
            } else {
                $questionId = $questionId['id'];
            }
    
            $answerId = $queryBilder->selectOneWhere('answers', 'id', ['answer', $answerText]);
            if (!$answerId) {
                $answerId = $queryBilder->insert('answers', [
                    'answer' => $answerText,
                    'length' => $lengthText
                    ]);
            } else {
                $answerId = $answerId['id'];
            }
    
            $this->data[] = [
                'question_id' => $questionId,
                'answer_id' => $answerId
            ];
        }
    }

    public function action()
    {
        $this->insertData();
    }
}