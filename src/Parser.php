<?php

namespace Src;

use DiDom\Document;
use Exception;
use DiDom\Exceptions\InvalidSelectorException;

class Parser
{
    // @codingStandardsIgnoreStart
    private const BASE_URL = 'https://www.imdb.com/';
    private const TITLE = 'main > div > section > section > div:nth-child(5) > section > section > div > div > h1 > span';
    private const ORIGINAL_TITLE = 'main > div > section > section > div:nth-child(5) > section > section > div > div.sc-b7c53eda-0.dUpRPQ > div';
    private const RELEASE_YEAR = 'main > div > section > section > div:nth-child(5) > section > section > div > div > ul > li:nth-child(1) > a';
    private const RATING = 'section > section > div > div > div > div > a > span > div > div > div > span';
    private const POSTER = 'section > div > section > section > div > div > div > div > div > img';
    private const DESCRIPTION = 'div > section > section > div > section > section > div > div > div > section > p';
    private const DIRECTORS = 'section > div > div > div > section > div > div > ul > li:nth-child(1) > div > ul > li';
    private const ACTORS = 'section.ipc-page-section.ipc-page-section--base.sc-bfec09a1-0 > div > div  > div > div > a';
    private const GENRES = 'section > div > section > section > div > div > div > section > div > div > a > span';
    // @codingStandardsIgnoreEnd
    private const MOVIE_MAX_INDEX = 99999999;

    private Progress $progress;

    private Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
        $this->progress = new Progress();
    }

    public function run(): void
    {
        $startIndex = $this->progress->getLastSeenId();
        if ($startIndex === 1) {
            echo "Start parsing\n";
        } else {
            echo "Continue parsing from $startIndex\n";
        }

        $startTotalTime = microtime(true);

        for ($i = $startIndex; $i <= self::MOVIE_MAX_INDEX; $i++) {
            $startParsingMovie = microtime(true);

            $sourceId = sprintf('%07d', $i);

            if ($this->db->checkMovieExists($sourceId)) {
                echo "Movie $sourceId already exists in the databases. Skip" . PHP_EOL;
                continue;
            }

            try {
                $movieData = $this->parseMovie($sourceId);
            } catch (Exception $e) {
                echo 'Can\'t parse movie. Cause: ' . $e->getMessage() . PHP_EOL;
                continue;
            }

            try {
                $this->db->saveMovie($movieData);
            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                continue;
            }

            echo $sourceId . " added to database" . PHP_EOL;

            $this->progress->rememberLastSeenId($i);

            $endParsingMovie = microtime(true);
            $timeParsingMovie = round(($endParsingMovie - $startParsingMovie), 1);
            echo "Save movie: {$movieData['name']} (Runtime: $timeParsingMovie)\n";
        }

        $endTotalTime = microtime(true);
        $totalTime = gmdate("H:i:s", round($endTotalTime - $startTotalTime));
        echo "Total time: $totalTime";
    }

    /**
     * @throws InvalidSelectorException
     * @throws Exception
     */
    private function parseMovie(string $sourceId): array
    {
        $movieUrl = self::BASE_URL . 'title/tt' . $sourceId;
        $content = @file_get_contents($movieUrl);

        if (!$content) {
            throw new Exception('No content on page ' . $movieUrl);
        }

        echo "Try parsing:  $sourceId \n";

        $document = new Document($movieUrl, true);

        $movieNameElement = $document->find(self::ORIGINAL_TITLE);
        if (empty($movieNameElement)) {
            $movieNameElement = $document->find(self::TITLE);
        }

        $movieName = $movieNameElement[0]->text();
        if (str_starts_with($movieName, 'Original title:')) {
            $movieName = str_replace('Original title: ', '', $movieName);
        }

        $releaseYearElement = $document->find(self::RELEASE_YEAR);
        $releaseYear = null;
        if (!empty($releaseYearElement[0])) {
            $releaseYear = $releaseYearElement[0]->text();
        }

        $ratingElement = $document->find(self::RATING);
        $rating = null;
        if (!empty($ratingElement[0])) {
            $rating = $ratingElement[0]->text();
        }

        $posterElement = $document->find(self::POSTER)[0] ?? null;
        $poster = $posterElement?->getAttribute('src');

        $description = $document->find(self::DESCRIPTION)[0]->text() ?? null;

        $genresElement = $document->find(self::GENRES) ?? [];
        $genres = array_map(fn($genre) => $genre->text(), $genresElement);

        $directorsElements = $document->find(self::DIRECTORS) ?? [];
        $directors = array_map(fn($director) => $director->text(), $directorsElements);

        $actorsElement = $document->find(self::ACTORS);
        $actors = array_map(fn($actor) => $actor->text(), $actorsElement);

        return [
            'sourceId' => $sourceId,
            'name' => $movieName,
            'link' => $movieUrl,
            'releaseYear' => $releaseYear,
            'rating' => $rating,
            'poster' => $poster,
            'description' => $description,
            'directors' => $directors,
            'actors' => $actors,
            'genres' => $genres
        ];
    }
}
