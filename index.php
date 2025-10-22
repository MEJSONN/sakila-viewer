<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia filmów</title>
    <link rel="stylesheet" href="styl.css">
</head>

<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) {
    die("Błąd połączenia z bazą danych: " . $baza->connect_error);
}

$title = isset($_GET['title']) ? trim($_GET['title']) : '';
?>

<body>
    <header>
        <h1>Wypożyczalnia filmów</h1>
    </header>
    <main>
        <menu class="filters">
            <form method="get">
                <p>Szukaj filmu po tytule:</p>
                <label for="film">
                    <input type="text" name="title" placeholder="Tytuł..." value="<?php echo $title; ?>">
                </label>
                <p>Filtruj przez kategorie:</p>

                <?php
                $categories = $baza->query("SELECT * FROM `category`");
                $categoriesFilter = $_GET['category'] ?? [];

                foreach ($categories as $category) {
                    $checked = in_array($category['name'], $categoriesFilter) ? 'checked' : '';
                    echo "<label>
                        <input type='checkbox' name='category[]' value='{$category['name']}' $checked>
                        <span>{$category['name']}</span>
                        </label><br>";
                }
                ?>
                <button type="submit">Filtruj</button>
            </form>
            <button onclick="changeTheme()">Zmień motyw</button>
        </menu>
        <section class="catalog">
            <?php
            $films = $baza->query("SELECT count(*) AS count, fid, title, category, description FROM `film_list`");

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

            $filmPerPage = 10;
            $offset = ($page - 1) * $filmPerPage;

            $rentedQuery = $baza->query("SELECT film.film_id, film.rental_duration, COUNT(rental.rental_id) AS wypozyczone FROM film JOIN inventory ON film.film_id = inventory.film_id LEFT JOIN rental ON inventory.inventory_id = rental.inventory_id AND rental.return_date IS NULL GROUP BY film.film_id;");

            $rentedFilms = [];
            foreach ($rentedQuery as $row) {
                $rentedFilms[$row['film_id']] = [
                    'limit' => $row['rental_duration'],
                    'rented' => $row['wypozyczone']
                ];
            }

            $where = "title LIKE '%$title%'";

            if (!empty($categoriesFilter)) {
                $cats = "'" . implode("','", $categoriesFilter) . "'";
                $where .= " AND category IN ($cats)";
            }

            $films = $baza->query("SELECT fid, title, category, description FROM film_list WHERE $where LIMIT $filmPerPage OFFSET $offset");

            foreach ($films as $film) {
                $fid = $film['fid'];
                $limit = $rentedFilms[$fid]['limit'] ?? 0;
                $rented = $rentedFilms[$fid]['rented'] ?? 0;
                $available = $limit - $rented;

                $disabled = ($available <= 0) ? 'disabled' : "onclick=\"location.href='./rent.php?fid=$fid'\"";

                echo "
                    <article class='film'>
                        <h2>{$film['title']}</h2>
                        <p>{$film['description']}</p>
                        <section class='buttons'>
                            <button onclick=\"location.href='film.php?fid={$fid}'\">Zobacz szczegóły</button>
                            <section class='rent-info'>
                                <span>Dostępna ilość: $available</span><br>
                                <button class='button-link' $disabled>Wypożycz</button>
                            </section>
                        </section>
                    </article>
                    ";
            }
            ?>

        </section>
    </main>
    <footer>

        <?php
        // Paginacja
        $total = $baza->query("SELECT COUNT(*) as count FROM `film_list` WHERE $where");

        foreach ($total as $t) {
            $total = $t['count'];
        }

        $pages = ceil($total / $filmPerPage);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $queryParams = $_GET;

        if ($page > 1) {
            $queryParams['page'] = $page - 1;
            echo "<button><a href='?" . http_build_query($queryParams) . "'>Poprzednia</a></button>";
        }

        echo "<strong class='current-page'>$page / $pages</strong> ";

        if ($page < $pages) {
            $queryParams['page'] = $page + 1;
            echo "<button><a href='?" . http_build_query($queryParams) . "'>Następna</a></button>";
        }
        ?>
    </footer>

    <script>
        function changeTheme() {
            document.body.classList.toggle('dark');
            if (document.body.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        }
    </script>

</body>

</html>