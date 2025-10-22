<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) {
    die("Błąd połączenia z bazą danych: " . $baza->connect_error);
}

$filmPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $filmPerPage;

$titleInput = $_GET['title'] ?? '';
$title = "%$titleInput%";
$categoriesFilter = $_GET['category'] ?? [];

$where = ["title LIKE ?"];
$params = [$title];
$paramTypes = "s";

if (!empty($categoriesFilter)) {
    $placeholders = implode(',', array_fill(0, count($categoriesFilter), '?'));
    $where[] = "category IN ($placeholders)";
    foreach ($categoriesFilter as $cat) {
        $params[] = $cat;
        $paramTypes .= "s";
    }
}

$whereSQL = implode(' AND ', $where);
$params[] = $filmPerPage;
$params[] = $offset;
$paramTypes .= "ii";

$stmt = $baza->prepare("SELECT fid, title, category, description FROM film_list WHERE $whereSQL LIMIT ? OFFSET ?");
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$films = $stmt->get_result();

$rentedQuery = $baza->query("SELECT film.film_id, film.rental_duration, COUNT(rental.rental_id) AS wypozyczone FROM film JOIN inventory ON film.film_id = inventory.film_id LEFT JOIN rental ON inventory.inventory_id = rental.inventory_id AND rental.return_date IS NULL GROUP BY film.film_id;");

$rentedFilms = [];
foreach ($rentedQuery as $row) {
    $rentedFilms[$row['film_id']] = [
        'limit' => $row['rental_duration'],
        'rented' => $row['wypozyczone']
    ];
}

$totalStmt = $baza->prepare("SELECT COUNT(*) as count FROM film_list WHERE $whereSQL");
$totalStmt->bind_param(substr($paramTypes, 0, -2), ...array_slice($params, 0, -2));
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$total = $totalRow['count'];
$pages = ceil($total / $filmPerPage);
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia filmów</title>
    <link rel="stylesheet" href="styl.css">
</head>

<body>
    <header>
        <h1>Wypożyczalnia filmów</h1>
    </header>
    <main>
        <menu class="filters">
            <form method="get">
                <p>Szukaj filmu po tytule:</p>
                <label for="film">
                    <input type="text" name="title" placeholder="Tytuł..." value="<?php echo $titleInput; ?>">
                </label>
                <p>Filtruj przez kategorie:</p>

                <?php
                $categories = $baza->query("SELECT * FROM category ORDER BY name ASC");
                foreach ($categories as $category) {
                    $checked = in_array($category['name'], $categoriesFilter) ? 'checked' : '';
                    echo "
                    <label>
                        <input type='checkbox' name='category[]' value='{$category['name']}' $checked>
                        {$category['name']}
                    </label><br>
                    ";
                }
                ?>
                <button type="submit">Filtruj</button>
            </form>
            <button onclick="changeTheme()">Zmień motyw</button>
        </menu>
        <section class="catalog">
            <?php
            foreach ($films as $film) {
                $fid = $film['fid'];
                $limit = $rentedFilms[$fid]['limit'] ?? 0;
                $rented = $rentedFilms[$fid]['rented'] ?? 0;
                $avaible = max(0, $limit - $rented);
                $disabled = ($avaible == 0) ? 'disabled' : "onclick=\"location.href='./rent.php?fid=$fid'\"";
                echo "
                    <article class='film'>
                        <h2>" . htmlspecialchars($film['title']) . "</h2>
                        <p>" . htmlspecialchars($film['description']) . "</p>
                        <section class='buttons'>
                            <button onclick=\"location.href='film.php?fid=$fid'\">Szczegóły</button>
                            <button $disabled>Wypożycz ($avaible dostępnych)</button>
                        </section>
                    </article>
                    ";
            }
            ?>
        </section>
    </main>
    <footer>
        <?php
        $queryParams = $_GET;
        if ($page > 1) {
            $queryParams['page'] = $page - 1;
            echo "<button><a href='?" . http_build_query($queryParams) . "'>Poprzednia</a></button>";
        }
        echo "<strong class='current-page'>$page / $pages</strong>";
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