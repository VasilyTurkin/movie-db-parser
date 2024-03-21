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

$select = 'SELECT source_id FROM movies WHERE source_id = :source_id LIMIT 1';

$insert = 'INSERT INTO movies (source_id, name, link, release_year, rating, poster, description)
              VALUES (:source_id, :name, :link, :release_year, :rating, :poster, :description)';

foreach ($moviesData as $movie) {

    $sqlSelect = $db->prepare($select);

    $sqlSelect->bindParam(':source_id', $movie['sourceId']);

    $sqlSelect->execute();

    if ($sqlSelect->fetch()) {
        continue;
    }

    $sqlInsert = $db->prepare($insert);

    $sqlInsert->bindParam(':source_id', $movie['sourceId']);
    $sqlInsert->bindParam(':name', $movie['name']);
    $sqlInsert->bindParam(':link', $movie['link']);
    $sqlInsert->bindParam(':release_year', $movie['release_year']);
    $sqlInsert->bindParam(':rating', $movie['rating']);
    $sqlInsert->bindParam(':poster', $movie['poster']);
    $sqlInsert->bindParam(':description', $movie['description']);

    $sqlInsert->execute();
}
