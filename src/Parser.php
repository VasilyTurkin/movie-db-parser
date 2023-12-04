<?php

namespace Src;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;

class Parser
{
    private const BASE_URL = 'https://www.imdb.com/';
    private const TITLE = 'section > section > div:nth-child(4) > section > section > div:nth-child(2) > div:first-child > h1';
    private const ORIGINAL_TITLE = 'section > section > div:nth-child(4) > section > section > div:nth-child(2) > div:first-child > div';
    private const RELEASE_YEAR = 'section > section > div:nth-child(4) > section > section > div:nth-child(2) > div:first-child > ul > li:first-child';
    private const RATING = 'section > section > div > div > div > div > a > span > div > div > div > span';
    private const POSTER = 'section > div > section > section > div > div > div > div > div > img';
    private const DESCRIPTION = 'div > section > section > div > section > section > div > div > div > section > p';
    private const DIRECTORS = 'section > div > div > div > section > div > div > ul > li:nth-child(1) > div > ul > li';
    private const ACTORS = 'section.ipc-page-section.ipc-page-section--base.sc-bfec09a1-0 > div > div  > div > div > a';
    private const GENRES = 'section > div > section > section > div > div > div > section > div > div > a > span';

    /**
     * @throws InvalidSelectorException
     */

    public function run(): void
    {

        $movieMaxIndex = 45;

        $moviesData = [];

        $moviesStorageFile = __DIR__ . '/../data/movies.json';
        if (file_exists($moviesStorageFile)) {
            $moviesData = json_decode(file_get_contents($moviesStorageFile), true) ?? [];
        }

        $progressDumpFile = __DIR__ . '/../data/progress.json';

        $defaultProgress = [
            'lastSeen' => [
                'sourceId' => 1,
            ]
        ];
        $progress = $defaultProgress;

        if (file_exists($progressDumpFile)) {
            $progress = json_decode(file_get_contents($progressDumpFile), true) ?? $defaultProgress;
        }

        $startIndex = $progress['lastSeen']['sourceId'] ?? 1;


        if ($startIndex === 1) {
            echo "Start parsing\n";
        } else {
            echo "Continue parsing from $startIndex\n";
        }

        for ($i = 1; $i <= $movieMaxIndex; $i++) {
            $movieId = sprintf('%07d', $i);

            $match = false;

            foreach ($moviesData as $movie) {
                if ($movie['sourceId'] === $movieId) {
                    echo "$movieId: " . $movie['name'] . ", in the data base\n";
                    $match = true;
                    break;
                }
            }

            if ($match) {
                continue;
            }

            $movieUrl = self::BASE_URL . 'title/tt' . $movieId;

            $content = @file_get_contents($movieUrl);
            if (!$content) {
                echo "Not data for parsing. Skip $movieUrl";
                continue;
            } else {
                echo "Try parsing:  $movieId \n";
            }

            $document = new Document($movieUrl, true);

            $movieNameElement = $document->find(self::ORIGINAL_TITLE);

            if (empty($movieNameElement)) {
                $movieNameElement = $document->find(self::TITLE);
            }

            $movieName = $movieNameElement[0]->text();

            if (str_starts_with($movieName, 'Original title:')) {
                $movieName = str_replace('Original title: ', '', $movieName);
            }

            $releaseYear = $document->find(self::RELEASE_YEAR)[0]->text() ?? null;

            $rating = $document->find(self::RATING)[0]->text() ?? null;

            $posterElement = $document->find(self::POSTER)[0] ?? null;

            $poster = $posterElement?->getAttribute('src');

            $description = $document->find(self::DESCRIPTION)[0]->text() ?? null;

            $directorsElements = $document->find(self::DIRECTORS) ?? '';

            $directors = array_map(fn($director) => $director->text(), $directorsElements);

            $actorsElement = $document->find(self::ACTORS);

            $actors = array_map(fn($actor) => $actor->text(), $actorsElement);

            $genresElement = $document->find(self::GENRES);

            $genres = array_map(fn($genre) => $genre->text(), $genresElement);

            $moviesData[] = [
                'sourceId' => $movieId,
                'name' => $movieName,
                'link' => $movieUrl,
                'release_year' => $releaseYear,
                'rating' => $rating,
                'poster' => $poster,
                'description' => $description,
                'directors' => $directors,
                'actor' => $actors,
                'genres' => $genres
            ];

            usort($moviesData, function ($a, $b) {
                return strcmp($a['sourceId'], $b['sourceId']);
            });

            $progress['lastSeen']['sourceId'] = $i;

            echo "Save movie: $movieName\n";

            file_put_contents($moviesStorageFile, json_encode($moviesData));

            file_put_contents($progressDumpFile, json_encode($progress));
        }
    }
}