<?php

namespace Src;

use PDO;

$user = 'turkin';

$pass = 'Ajnc3&32__dw62';

$moviesData = [];

$moviesStorageFile = __DIR__ . '/../data/movies.json';

ini_set('memory_limit', '2000M');

if (file_exists($moviesStorageFile)) {
    $moviesData = json_decode(file_get_contents($moviesStorageFile), true) ?? [];
}

$db = new PDO('mysql:host=74.119.192.202:3306;dbname=movies', $user, $pass);

function addGenre(object $db, string $genre, int $movieId): void
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

function addPerson(object $db, string $personName, string $position, int $movieId): void
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

foreach ($moviesData as $movie) {
    $selectMovieQuery = 'SELECT id FROM movies WHERE source_id = :source_id LIMIT 1';
    $selectMovie = $db->prepare($selectMovieQuery);
    $selectMovie->bindParam(':source_id', $movie['sourceId']);
    $selectMovie->execute();

    $movieId = null;

    if ($foundMovieId = $selectMovie->fetch(PDO::FETCH_ASSOC)) {
        $movieId = $foundMovieId['id'];
    } else {
        $insertMovieQuery = 'INSERT INTO movies (source_id, name, link, release_year, rating, poster, description)
                    VALUES (:source_id, :name, :link, :release_year, :rating, :poster, :description)';
        $insertMovie = $db->prepare($insertMovieQuery);

        $insertMovie->bindParam(':source_id', $movie['sourceId']);
        $insertMovie->bindParam(':name', $movie['name']);
        $insertMovie->bindParam(':link', $movie['link']);
        $insertMovie->bindParam(':release_year', $movie['release_year']);
        $insertMovie->bindParam(':rating', $movie['rating']);
        $insertMovie->bindParam(':poster', $movie['poster']);
        $insertMovie->bindParam(':description', $movie['description']);

        if ($insertMovie->execute()) {
            echo $movie['sourceId'] . " added to database" . PHP_EOL;
        }

        $movieId = $db->lastInsertId();
    }

    foreach ($movie['genres'] as $genre) {
        addGenre($db, $genre, $movieId);
    }

    foreach ($movie['directors'] as $personName) {
        $position = 'directors';
        addPerson($db, $personName, $position, $movieId);
    }

    foreach ($movie['actors'] as $personName) {
        $position = 'actor';
        addPerson($db, $personName, $position, $movieId);
    }
}
