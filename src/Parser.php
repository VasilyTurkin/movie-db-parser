<?php

namespace Src;

use DiDom\Document;
use Exception;
use RuntimeException;
use PDO;
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

    /**
     * @throws InvalidSelectorException
     */

    public function run(): void
    {
        $user = '';

        $pass = null;

        $db = new PDO();

        $movieMaxIndex = 9999999;

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

        $startTotalTime = microtime(true);

        for ($i = $startIndex; $i <= $movieMaxIndex; $i++) {
            $sourceId = sprintf('%07d', $i);

            if ($this->checkMovieExists($db, $sourceId)) {
                echo "Movie $sourceId already exists in the databases" . PHP_EOL;
                continue;
            }

            $movieUrl = self::BASE_URL . 'title/tt' . $sourceId;

            $content = @file_get_contents($movieUrl);

            if (!$content) {
                echo "Not data for parsing. Skip $movieUrl \n";
                continue;
            } else {
                echo "Try parsing:  $sourceId \n";
            }

            $startParsingMovie = microtime(true);

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

            if (!empty($releaseYearElement[0])) {
                $releaseYear = $releaseYearElement[0]->text();
            } else {
                $releaseYear = null;
            }

            $ratingElement = $document->find(self::RATING);

            if (!empty($ratingElement[0])) {
                $rating = $ratingElement[0]->text();
            } else {
                $rating = null;
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

            $movieData = [
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

            try {
                $movieId = $this->saveMovie($db, $movieData);
            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                continue;
            }

            echo $sourceId . " added to database" . PHP_EOL;

            foreach ($genres as $genre) {
                $this->addGenre($db, $genre, $movieId);
            }

            foreach ($directors as $personName) {
                $position = 'directors';
                $this->addPerson($db, $personName, $position, $movieId);
            }

            foreach ($actors as $personName) {
                $position = 'actor';
                $this->addPerson($db, $personName, $position, $movieId);
            }

            $progress['lastSeen']['sourceId'] = $i;

            file_put_contents($progressDumpFile, json_encode($progress));

            $endParsingMovie = microtime(true);

            $timeParsingMovie = round(($endParsingMovie - $startParsingMovie), 1);

            echo "Save movie: $movieName (Runtime: $timeParsingMovie)\n";
        }

        $endTotalTime = microtime(true);

        $totalTime = gmdate("H:i:s", round($endTotalTime - $startTotalTime));

        echo "Total time: $totalTime";
    }

    private function checkMovieExists(PDO $db, string $movieSourceId): bool
    {
        $checkQuery = 'SELECT id FROM movies WHERE source_id = :source_id LIMIT 1';
        $checkQuery = $db->prepare($checkQuery);
        $checkQuery->bindParam(':source_id', $movieSourceId);
        $checkQuery->execute();

        return $checkQuery->fetch();
    }

    private function saveMovie(PDO $db, array $movieData): int
    {
        $insertMovieQuery = 'INSERT INTO movies (source_id, name, link, release_year, rating, poster, description)
                    VALUES (:source_id, :name, :link, :release_year, :rating, :poster, :description)';
        $insertMovie = $db->prepare($insertMovieQuery);

        $insertMovie->bindParam(':source_id', $movieData['sourceId']);
        $insertMovie->bindParam(':name', $movieData['name']);
        $insertMovie->bindParam(':link', $movieData['link']);
        $insertMovie->bindParam(':release_year', $movieData['releaseYear']);
        $insertMovie->bindParam(':rating', $movieData['rating']);
        $insertMovie->bindParam(':poster', $movieData['poster']);
        $insertMovie->bindParam(':description', $movieData['description']);

        if (!$insertMovie->execute()) {
            throw new RuntimeException('Can\'t save movie');
        }

        return $db->lastInsertId();
    }

    private function addPerson(PDO $db, string $personName, string $position, int $movieId): void
    {
        $selectPersonQuery = 'SELECT id FROM crew_members WHERE full_name = :full_name LIMIT 1';

        $selectPerson = $db->prepare($selectPersonQuery);
        $selectPerson->bindParam(':full_name', $personName);
        $selectPerson->execute();

        if ($existingPerson = $selectPerson->fetch(PDO::FETCH_ASSOC)) {
            $personId = $existingPerson['id'];
        } else {
            $insertPersonQuery = 'INSERT INTO crew_members (full_name, position)
                          VALUES (:full_name, :position)';

            $insertPerson = $db->prepare($insertPersonQuery);
            $insertPerson->bindParam(':full_name', $personName);
            $insertPerson->bindParam(':position', $position);
            $insertPerson->execute();
            $personId = $db->lastInsertId();
        }
        $assignQuery = 'INSERT IGNORE INTO movie_crew_member (movie_id, crew_member_id)
                                   VALUES (:movie_id, :crew_member_id)';
        $assignStatement = $db->prepare($assignQuery);
        $assignStatement->bindParam(':movie_id', $movieId);
        $assignStatement->bindParam(':crew_member_id', $personId);
        $assignStatement->execute();
    }

    private function addGenre(PDO $db, string $genre, int $movieId): void
    {
        $selectGenreQuery = 'SELECT id FROM genres WHERE name = :name_genre LIMIT 1';
        $selectGenre = $db->prepare($selectGenreQuery);
        $selectGenre->bindParam(':name_genre', $genre);
        $selectGenre->execute();

        if ($findGenre = $selectGenre->fetch(PDO::FETCH_ASSOC)) {
            $genreId = $findGenre['id'];
        } else {
            $insertGenreQuery = 'INSERT INTO genres (name) VALUES (:name_genre)';
            $insertGenre = $db->prepare($insertGenreQuery);
            $insertGenre->bindParam(':name_genre', $genre);
            $insertGenre->execute();

            $genreId = $db->lastInsertId();
        }

        $insertMovieGenreQuery = 'INSERT IGNORE INTO movie_genre (movie_id, genre_id) VALUES (:movie_id, :genre_id)';
        $insertMovieGenre = $db->prepare($insertMovieGenreQuery);
        $insertMovieGenre->bindParam(':movie_id', $movieId);
        $insertMovieGenre->bindParam(':genre_id', $genreId);
        $insertMovieGenre->execute();
    }
}
