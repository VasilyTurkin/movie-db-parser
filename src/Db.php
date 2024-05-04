<?php

namespace Src;

use PDO;
use RuntimeException;

class Db
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function checkMovieExists(string $sourceId): bool
    {
        $checkQuery = 'SELECT id FROM movies WHERE source_id = :source_id LIMIT 1';
        $checkQuery = $this->db->prepare($checkQuery);
        $checkQuery->bindParam(':source_id', $sourceId);
        $checkQuery->execute();

        return (bool)$checkQuery->fetch();
    }

    public function saveMovie(array $movieData): void
    {
        $insertMovieQuery = 'INSERT INTO movies (source_id, name, link, release_year, rating, poster, description)
                    VALUES (:source_id, :name, :link, :release_year, :rating, :poster, :description)';
        $insertMovie = $this->db->prepare($insertMovieQuery);

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

        $movieId = $this->db->lastInsertId();

        foreach ($movieData['genres'] as $genre) {
            $this->addGenre($genre, $movieId);
        }

        foreach ($movieData['directors'] as $personName) {
            $position = 'director';
            $this->addPerson($personName, $position, $movieId);
        }

        foreach ($movieData['actors'] as $personName) {
            $position = 'actor';
            $this->addPerson($personName, $position, $movieId);
        }
    }

    private function addPerson(string $personName, string $position, int $movieId): void
    {
        $selectPersonQuery = 'SELECT id FROM crew_members WHERE full_name = :full_name LIMIT 1';

        $selectPerson = $this->db->prepare($selectPersonQuery);
        $selectPerson->bindParam(':full_name', $personName);
        $selectPerson->execute();

        if ($existingPerson = $selectPerson->fetch(PDO::FETCH_ASSOC)) {
            $personId = $existingPerson['id'];
        } else {
            $insertPersonQuery = 'INSERT INTO crew_members (full_name, position)
                          VALUES (:full_name)';

            $insertPerson = $this->db->prepare($insertPersonQuery);
            $insertPerson->bindParam(':full_name', $personName);
            $insertPerson->execute();
            $personId = $this->db->lastInsertId();
        }
        $assignQuery = 'INSERT IGNORE INTO movie_crew_member (movie_id, crew_member_id, position)
                                   VALUES (:movie_id, :crew_member_id, :position)';
        $assignStatement = $this->db->prepare($assignQuery);
        $assignStatement->bindParam(':movie_id', $movieId);
        $assignStatement->bindParam(':crew_member_id', $personId);
        $assignStatement->bindParam(':position', $position);
        $assignStatement->execute();
    }

    private function addGenre(string $genre, int $movieId): void
    {
        $selectGenreQuery = 'SELECT id FROM genres WHERE name = :name_genre LIMIT 1';
        $selectGenre = $this->db->prepare($selectGenreQuery);
        $selectGenre->bindParam(':name_genre', $genre);
        $selectGenre->execute();

        if ($findGenre = $selectGenre->fetch(PDO::FETCH_ASSOC)) {
            $genreId = $findGenre['id'];
        } else {
            $insertGenreQuery = 'INSERT INTO genres (name) VALUES (:name_genre)';
            $insertGenre = $this->db->prepare($insertGenreQuery);
            $insertGenre->bindParam(':name_genre', $genre);
            $insertGenre->execute();

            $genreId = $this->db->lastInsertId();
        }

        $insertMovieGenreQuery = 'INSERT IGNORE INTO movie_genre (movie_id, genre_id) VALUES (:movie_id, :genre_id)';
        $insertMovieGenre = $this->db->prepare($insertMovieGenreQuery);
        $insertMovieGenre->bindParam(':movie_id', $movieId);
        $insertMovieGenre->bindParam(':genre_id', $genreId);
        $insertMovieGenre->execute();
    }
}
