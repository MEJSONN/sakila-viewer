<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("Błąd połączenia z bazą danych: {$baza->connect_error}");

$filmPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $filmPerPage;

$titleInput = $_GET['title'] ?? '';
$titleSQL = "%$titleInput%";
$categoriesFilter = $_GET['category'] ?? [];

$where = ["title LIKE ?"];
$params = [$titleSQL];
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

$totalStmt = $baza->prepare("SELECT COUNT(*) as count FROM film_list WHERE $whereSQL");
$totalStmt->bind_param(substr($paramTypes, 0, -2), ...array_slice($params, 0, -2));
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$pages = ceil($totalRow['count'] / $filmPerPage);
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
        <button onclick="location.href='admin.php'">Panel administracyjny</button>
        <h1>Wypożyczalnia filmów</h1>
        <button onclick="changeTheme()">Zmień motyw</button>
    </header>

    <main>
        <menu class="menu">
            <form method="get" class="filters">
                <h4>Szukaj filmu po tytule:</h4>
                <input type="text" name="title" placeholder="Tytuł..." value="<?php echo $titleInput; ?>">
                <hr>
                <section class="category-list">
                    <h4>Filtruj przez kategorie:</h4>
                    <?php
                    $categories = $baza->query("SELECT * FROM category ORDER BY name ASC");
                    foreach ($categories as $category) {
                        $checked = in_array($category['name'], $categoriesFilter) ? 'checked' : '';
                        echo "<label><input type='checkbox' name='category[]' value='{$category['name']}' $checked> {$category['name']}</label><br>";
                    }
                    ?>
                </section>
                <hr>
                <button type="submit">Filtruj</button>
            </form>
        </menu>

        <section class="catalog">
            <?php
            foreach ($films as $film) {
                $fid = $film['fid'];
                $datas = $baza->query("
                    SELECT 
                        COUNT(i.inventory_id) AS wszystkie,
                        SUM(CASE WHEN r.rental_id IS NULL OR r.return_date IS NOT NULL THEN 1 ELSE 0 END) AS dostepne
                    FROM film f 
                    LEFT JOIN inventory i ON f.film_id = i.film_id 
                    LEFT JOIN rental r ON i.inventory_id = r.inventory_id AND r.return_date IS NULL
                    WHERE f.film_id = $fid
                    GROUP BY f.film_id
                ");

                foreach ($datas as $data) {
                    $wszystkie = $data['wszystkie'] ?? 0;
                    $dostepne = $data['dostepne'] ?? 0;
                    $disabled = ($dostepne <= 0) ? 'disabled' : '';
                }

                echo "
                    <article class='film'>
                        <h2 class='film-title'>{$film['title']}</h2>
                        <p class='film-description'>{$film['description']}</p>
                        <section class='buttons'>
                            <button onclick=\"location.href='film.php?fid=$fid'\">Szczegóły</button>
                            <button $disabled onclick=\"location.href='rent.php?fid=$fid'\">Wypożycz ($dostepne/$wszystkie)</button>
                        </section>
                    </article>";
            }
            ?>
        </section>
    </main>

    <footer>
        <?php
        $queryParams = $_GET;
        if ($page > 1) {
            $queryParams['page'] = $page - 1;
            echo "<button onclick=\"location.href='?" . http_build_query($queryParams) . "'\">Poprzednia</button>";
        }
        echo "<strong class='current-page'>$page/$pages</strong>";
        if ($page < $pages) {
            $queryParams['page'] = $page + 1;
            echo "<button onclick=\"location.href='?" . http_build_query($queryParams) . "'\">Następna</button>";
        }
        ?>
    </footer>
</body>

<script>
    const theme = localStorage.getItem('theme');
    if (theme === 'dark') document.body.classList.add('dark');

    function changeTheme() {
        document.body.classList.toggle('dark');
        localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    }
</script>

</html>