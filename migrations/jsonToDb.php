<?php

namespace Src;

use PDO;

$user = 'root';

$pass = null;

$moviesData = [];

$moviesStorageFile = __DIR__ . '/../data/movies.json';

if (file_exists($moviesStorageFile)) {
    $moviesData = json_decode(file_get_contents($moviesStorageFile), true) ?? [];
}

$db = new PDO('mysql:host=localhost;dbname=Movie', $user, $pass);

foreach ($moviesData as $movie) {

    $selectMovieQuery = 'SELECT source_id FROM movies WHERE source_id = :source_id LIMIT 1';

    $selectMovie = $db->prepare($selectMovieQuery);

    $selectMovie->bindParam(':source_id', $movie['sourceId']);

    $selectMovie->execute();

    if ($selectMovie->fetch()) {
        continue;
    }

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
        echo $movie['sourceId'] . " added to data base" . PHP_EOL;
    }

    $lastMovieId = $db->lastInsertId();

    echo $lastMovieId . PHP_EOL;

    foreach ($movie['genres'] as $genre) {

        $selectGenreQuery = 'SELECT id FROM genres WHERE name = :name LIMIT 1';

        $selectGenre = $db->prepare($selectGenreQuery);

        $selectGenre->bindParam(':name', $genre);

        $selectGenre->execute();

        if ($selectGenre->fetch()) {
            continue;
        }

        $insertGenreQuery = 'INSERT INTO genres (name) VALUE (:name)';

        $insertGenre = $db->prepare($insertGenreQuery);

        $insertGenre->bindParam(':name', $genre);

        $insertGenre->execute();

//        $lastGenreId = $db->lastInsertId();

    }
}
