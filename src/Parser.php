<?php

namespace Src;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;

class Parser
{
    public const BASE_URL = 'https://www.imdb.com';

    public const MOVIE_NAME_SELECTOR = '#__next > main > div > section > section > div > section > section > div > div > h1 > span';

    /**
     * @throws InvalidSelectorException
     */

    public function run(): void
    {
        $movieStartIndex = 1;
        $movieMaxIndex = 9;

        $movieLink = [];

        $moviesData = [];
        
        $start = microtime(true);

        for ($i = $movieStartIndex; $i <= $movieMaxIndex; $i++) {
            $movieIdGenerate = sprintf('/title/tt%07d/', $i);
            $movieLink[] = self::BASE_URL . $movieIdGenerate;
        }

        foreach ($movieLink as $item) {
            $document = new Document($item, true);
            $movieElement = $document->find(self::MOVIE_NAME_SELECTOR);
            
            foreach ($movieElement as $movie){
                $movieName = $movie->text();
                $moviesData[] = [$movieName => $item];
            }
        }
        var_dump($moviesData);
        echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
    }
}
