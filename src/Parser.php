<?phpnamespace Src;use DiDom\Document;use DiDom\Exceptions\InvalidSelectorException;class Parser{    public const BASE_URL = 'https://www.imdb.com/';    public const MOVIE_HEADER = 'main > div > section > section > div > section > section > div > div > h1 > span';    public const ORIGINAL_TITLE = 'section > section > div > section > section > div > div > div';    public const YEAR = 'main > div > section > section > div > section > section > div> div > ul > li > a';    public const RATING = 'section > section > div > div > div > div > a > span > div > div > div > span';    public const POSTER = 'section > div > section > section > div > div > div > div > div > img';    public const DESCRIPTION = 'div > section > section > div > section > section > div > div > div > section > p';    public const DIRECTOR = 'section > div > div > div > section > div > div > ul > li:nth-child(1) > div > ul > li';    public const ACTORS = 'section.ipc-page-section.ipc-page-section--base.sc-bfec09a1-0 > div > div  > div > div > a';    public const GENRES = 'section > div > section > section > div > div > div > section > div > div > a > span';    /**     * @throws InvalidSelectorException     */    public function run(): void    {        $movieMaxIndex = 12;        $moviesData = [];        $moviesStorageFile = __DIR__ . '/../data/movies.json';        if (file_exists($moviesStorageFile)) {            $moviesData = json_decode(file_get_contents($moviesStorageFile), true) ?? [];        }        for ($i = 1; $i <= $movieMaxIndex; $i++) {            $movieId = sprintf('%07d', $i);            $match = false;            foreach ($moviesData as $movie) {                if ($movie['sourceId'] === $movieId) {                    $match = true;                    break;                }            }            if ($match) {                continue;            }            $movieLink = self::BASE_URL . 'title/tt' . $movieId;            // Проверка наличия страницы            $content = @file_get_contents($movieLink);            if (!$content) {                continue;            }            $document = new Document($movieLink, true);            $movieName = $document->find(self::ORIGINAL_TITLE)[2]->text();            if (empty($movieName)) {                $movieName = $document->find(self::MOVIE_HEADER)[0]->text();            }            $movieName = str_replace('Original title: ', '', $movieName);            $year = $document->find(self::YEAR)[0]->text();            if (empty($year)) {                $year = null;            }            $rating = $document->find(self::RATING)[0]->text() . '/10';            $poster = $document->find(self::POSTER)[0]->getAttribute('src');            $description = $document->find(self::DESCRIPTION)[0]->text();            $directors = $document->find(self::DIRECTOR);            $directorsList = [];            foreach ($directors as $director) {                $directorsList[] = $director->text();            }            $actors = $document->find(self::ACTORS);            $actorsList = [];            foreach ($actors as $actor) {                $actorsName = $actor->text();//проверку на пустоту                $actorsList[] = $actorsName;            }            $genres = $document->find(self::GENRES);            $genresList = [];            foreach ($genres as $genre) {                $genresList[] = $genre->text();            }            $moviesData[] = [                'sourceId' => $movieId,                'name' => $movieName,                'link' => $movieLink,                'year' => $year,                'rating' => $rating,                'poster' => $poster,                'description' => $description,                'director' => $directorsList,                'actor' => $actorsList,                'genres' => $genresList            ];            file_put_contents($moviesStorageFile, json_encode($moviesData));            print_r($moviesData) . PHP_EOL;        }    }}//                                                                 удаление блоков                    кол во блоков//$$('#__next > main > div > section > div > section > div > div > section:nth-child(20) > div > ul > li:nth-child(2) > div > ul > li > a')//$$('#__next > main > div > section > div > section > div > div > section:nth-child(16) > div > ul > li:nth-child(1) > div > ul > li > a')//$$('#__next > main > div > section > div > section > div > div > section:nth-child(24) > div > ul > li:nth-child(1) > div > ul > li > a')//$$('#__next > main > div > section > div > section > div > div > section:nth-child(12) > div > ul > li:nth-child(1) > div > ul > li > a')