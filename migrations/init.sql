
CREATE TABLE IF NOT EXISTS movies
(
    id           BIGINT AUTO_INCREMENT NOT NULL,
    source_id    VARCHAR(255)          NOT NULL,
    name         VARCHAR(255)          NOT NULL,
    link         VARCHAR(255)          NULL,
    release_year INT                   NULL,
    rating       FLOAT                 NULL,
    poster       VARCHAR(255)          NULL,
    description  TEXT                  NULL,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS genres
(
    id   BIGINT AUTO_INCREMENT NOT NULL,
    name VARCHAR(50)           NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS movie_genre
(
    movie_id BIGINT NOT NULL,
    genre_id BIGINT NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies (id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres (id) ON DELETE CASCADE,
    UNIQUE (movie_id, genre_id)
);

CREATE TABLE IF NOT EXISTS crew_members
(
    id        BIGINT   AUTO_INCREMENT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    position  VARCHAR(255) NULL,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS movie_crew_member
(
    movie_id       BIGINT NOT NULL,
    crew_member_id BIGINT NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies (id) ON DELETE CASCADE,
    FOREIGN KEY (crew_member_id) REFERENCES crew_members (id) ON DELETE CASCADE
);
