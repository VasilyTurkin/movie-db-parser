<?php

namespace Src;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;

class Parser
{
    public const URL = "https://www.imdb.com/search/title/?genres=action";

    private array $movieData = [];

    private int $count = 1;

    /**
     * @throws InvalidSelectorException
     */
    public function run(): void
    {
        $document = new Document(self::URL, true);

        $foundLinks = $document->find('#main > div > div > div > div > div > h3 > a');

        foreach ($foundLinks as $link) {
            $movieName =  $link->text();
            $movieLink =  $link->getAttribute('href');

            $this->movieData[] = [$movieName => $movieLink];
        }

        print_r($this->movieData);
    }
}

// Action	 Adventure	 Animation	 Biography
// Comedy	 Crime	 Documentary	 Drama
// Family	 Fantasy	 Film-Noir	 Game-Show
// History	 Horror	 Music	 Musical
// Mystery	 News	 Reality-TV	 Romance
// Sci-Fi	 Sport	 Talk-Show	 Thriller
// War	 Western

// Добавлять по кооличеству фильмов
//<a href="/title/tt2560140/?ref_=adv_li_tt">Атака титанов</a>