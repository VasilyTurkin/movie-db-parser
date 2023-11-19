<?php

namespace Src;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;

class Parser
{
    public const URL = "https://www.imdb.com/search/title/?genres=action";

    public const MOVIE_NAME_SELECTOR = 'section > div > div > ul > li > div > div > div > div > div > div > a.ipc-title-link-wrapper';

    /**
     * @throws InvalidSelectorException
     */

    public function run(): void
    {
        $moviesData = [];

        $document = new Document(self::URL, true);

        $moviesElement = $document->find(self::MOVIE_NAME_SELECTOR);

        foreach ($moviesElement as $movie) {

            $movieLink = $movie->getAttribute('href');
            $movieName = $movie->text();

            $moviesData[] = [
                'name' => $movieName,
                'link' => $movieLink];
        }

        var_dump($moviesData);
    }
}
