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

        $selectGenreQuery = 'SELECT id FROM genres WHERE name = :name_genre LIMIT 1';
        $selectGenre = $db->prepare($selectGenreQuery);
        $selectGenre->bindParam(':name_genre', $genre);
        $selectGenre->execute();

        $genreId = null;

        if ($foundGenreId = $selectGenre->fetch(PDO::FETCH_ASSOC)) {
            $genreId = $foundGenreId['id'];
            continue;
        } else {

            $insertGenreQuery = 'INSERT INTO genres (name) VALUES (:name_genre)';
            $insertGenre = $db->prepare($insertGenreQuery);
            $insertGenre->bindParam(':name_genre', $genre);
            $insertGenre->execute();

            $genreId = $db->lastInsertId();
        }

        $insertMovieGenreQuery = 'INSERT INTO movie_genre (movie_id, genre_id) VALUES (:movie_id, :genre_id)';
        $insertMovieGenre = $db->prepare($insertMovieGenreQuery);
        $insertMovieGenre->bindParam(':movie_id', $movieId);
        $insertMovieGenre->bindParam(':genre_id', $genreId);
        $insertMovieGenre->execute();
    }

    $selectPersonQuery = 'SELECT id FROM crew_members WHERE full_name = :full_name LIMIT 1';

    $insertPersonQuery = 'INSERT INTO crew_members (full_name, position) 
                          VALUES (:full_name, :position)';


    $insertMovieCrewMemberQuery = 'INSERT INTO movie_crew_member (movie_id, crew_member_id) 
                                   VALUES (:movie_id, :crew_member_id)';

    foreach ($movie['directors'] as $directorName) {

        $director = 'director';

        $selectDirector = $db->prepare($selectPersonQuery);
        $selectDirector->bindParam(':full_name', $directorName);
        $selectDirector->execute();

        $directorId = null;

        if ($existingDirector = $selectDirector->fetch(PDO::FETCH_ASSOC)) {
            $directorId = $existingDirector['id'];
        } else {
            $insertDirector = $db->prepare($insertPersonQuery);
            $insertDirector->bindParam(':full_name', $directorName);
            $insertDirector->bindValue(':position', $director);
            $insertDirector->execute();

            $directorId = $db->lastInsertId();
        }

        $insertMovieCrewMember = $db->prepare($insertMovieCrewMemberQuery);
        $insertMovieCrewMember->bindParam(':movie_id', $movieId);
        $insertMovieCrewMember->bindParam(':crew_member_id', $directorId);
        $insertMovieCrewMember->execute();
    }

    foreach ($movie['actors'] as $actorName) {

        $actor = 'actor';

        $selectActor = $db->prepare($selectPersonQuery);
        $selectActor->bindParam(':full_name', $actorName);
        $selectActor->execute();

        $actorId = null;

        if ($existingActor = $selectActor->fetch(PDO::FETCH_ASSOC)) {
            $actorId = $existingActor['id'];
        } else {
            $insertActor = $db->prepare($insertPersonQuery);
            $insertActor->bindParam(':full_name', $actorName);
            $insertActor->bindValue(':position', $actor);
            $insertActor->execute();

            $actorId = $db->lastInsertId();
        }

        $insertMovieCrewMember = $db->prepare($insertMovieCrewMemberQuery);
        $insertMovieCrewMember->bindParam(':movie_id', $movieId);
        $insertMovieCrewMember->bindParam(':crew_member_id', $actorId);
        $insertMovieCrewMember->execute();
    }
}
