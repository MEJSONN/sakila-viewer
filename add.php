<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("Błąd połączenia z bazą danych: " . $baza->connect_error);

$languagesList = $baza->query("SELECT language_id, name FROM `language`");
$categoriesList = $baza->query("SELECT category_id, name FROM `category`");
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodawanie filmu</title>
    <link rel="stylesheet" href="styl.css">
</head>

<body>
    <header>
        <button onclick="location.href='admin.php'">Panel administracyjny</button>
        <h1>Dodawanie nowego filmu</h1>
        <button onclick="changeTheme()">Zmień motyw</button>
    </header>
    <form method="post">
        <section class="movie-info">
            <label for="title">
                <p>Tytuł:</p>
                <input type="text" name="title" required>
            </label>
            <label for="description">
                <p>Opis:</p>
                <textarea name="description" required rows="3" cols="50"></textarea>
            </label>
            <details>
                <summary>Aktorzy:</summary>
                <?php
                $actors = $baza->query("SELECT actor_id, first_name, last_name FROM actor");
                foreach ($actors as $actor) {
                    echo "
                    <label for=\"actor_{$actor['actor_id']}\">
                        <input type=\"checkbox\" name=\"actors[]\" value=\"{$actor['actor_id']}\">
                        {$actor['first_name']} {$actor['last_name']}
                    </label><br>
                    ";
                }
                ?>
            </details>
            <label for="raiting">
                <p>Ocena:</p>
                <select name="raiting" required>
                    <option value="G">G</option>
                    <option value="PG">PG</option>
                    <option value="PG-13">PG-13</option>
                    <option value="R">R</option>
                    <option value="NC-17">NC-17</option>
                </select>
            </label>
            <label for="releaseYear">
                <p>Rok wydania:</p>
                <input type="number" name="releaseYear" min="1900" max="2099" required>
            </label>
            <label for="rentalDuration">
                <p>Czas wypożyczenia (dni):</p>
                <input type="number" name="rentalDuration" min="1" required>
            </label>
            <label for="rentalRate">
                <p>Koszt wypożyczenia:</p>
                <input type="number" name="rentalRate" min="0" step="0.01" required>
            </label>
            <label for="length">
                <p>Długość filmu (min):</p>
                <input type="number" name="length" min="1" required>
            </label>
            <label for="replacementCost">
                <p>Koszt wymiany:</p>
                <input type="number" name="replacementCost" min="0" step="0.01" required>
            </label>
            <label for="language">
                <p>Język:</p>
                <select name="language" required>
                    <?php
                    foreach ($languagesList as $languageList) {
                        echo "<option value=\"{$languageList['language_id']}\">{$languageList['name']}</option>";
                    }
                    ?>
                </select>
            </label>
            <label for="originalLanguage">
                <p>Język oryginalny:</p>
                <select name="originalLanguage">
                    <option value="">Brak informacji</option>
                    <?php
                    foreach ($languagesList as $languageList) {
                        echo "<option value=\"{$languageList['language_id']}\">{$languageList['name']}</option>";
                    }
                    ?>
                </select>
            </label>
            <label for="categorie">
                <p>Kategoria:</p>
                <select name="categorie" required>
                    <?php
                    foreach ($categoriesList as $categorieList) {
                        echo "<option value=\"{$categorieList['category_id']}\">{$categorieList['name']}</option>";
                    }
                    ?>
                </select>
            </label>
            <label for="count">
                <p>Ilość kopii:</p>
                <input type="number" name="count" min="1" required>
                <small>(Wszystkie nowe kopie będą dostępne do wypożyczenia)</small>
            </label>
        </section>
        <section class="movie-info">
            <button type="submit">Dodaj film</button>
        </section>
    </form>
</body>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $releaseYear = (int)($_POST['releaseYear'] ?? 0);
    $rentalDuration = (int)($_POST['rentalDuration'] ?? 0);
    $rentalRate = (float)($_POST['rentalRate'] ?? 0);
    $length = (int)($_POST['length'] ?? 0);
    $replacementCost = (float)($_POST['replacementCost'] ?? 0);
    $languageId = (int)($_POST['language'] ?? 0);
    $originalLanguageId = empty($_POST['originalLanguage']) ? null : (int)$_POST['originalLanguage'];
    $categoryId = (int)($_POST['categorie'] ?? 0);
    $count = max(1, (int)($_POST['count'] ?? 1));
    $actors = $_POST['actors'] ?? [];
    if (!is_array($actors)) {
        $actors = [$actors];
    }
    $raiting = $_POST['raiting'] ?? 'G';

    $stmt = $baza->prepare("
        INSERT INTO film 
            (title, description, release_year, rental_duration, rental_rate, length, replacement_cost, language_id, original_language_id, last_update, rating)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");

    $types = 'ssiidid iis';
    $types = str_replace(' ', '', $types);

    $stmt->bind_param(
        $types,
        $title,
        $description,
        $releaseYear,
        $rentalDuration,
        $rentalRate,
        $length,
        $replacementCost,
        $languageId,
        $originalLanguageId,
        $raiting
    );

    if ($stmt->execute()) {
        $filmId = $baza->insert_id;

        if ($categoryId > 0) {
            $stmtCat = $baza->prepare("INSERT INTO film_category (film_id, category_id) VALUES (?, ?)");
            $stmtCat->bind_param("ii", $filmId, $categoryId);
            $stmtCat->execute();
        }

        echo "<section class='movie-info'><p>Pomyślnie dodano film!</p></section>";
    } else {
        echo "<section class='movie-info'><p>Niestety nie udało się dodać filmu!</p><p>Błąd: {$stmt->error}</p></section>";
    }

    $baza->begin_transaction();
    try {
        $stmtInventory = $baza->prepare("INSERT INTO inventory (film_id, store_id, last_update) VALUES (?, 1, NOW())");
        $stmtInventory->bind_param("i", $filmId);
        for ($i = 0; $i < $count; $i++) {
            $stmtInventory->execute();
        }
        
        if (!empty($actors)) {
        $stmtActors = $baza->prepare("INSERT INTO film_actor (actor_id, film_id, last_update) VALUES (?, ?, NOW())");
        $stmtActors->bind_param("ii", $actorId, $filmId);
        foreach ($actors as $a) {
            $actorId = (int)$a;
            if ($actorId > 0) {
                $stmtActors->execute();
            }
        }
    }
}
?>

<script>
    const theme = localStorage.getItem('theme');
    if (theme === 'dark') document.body.classList.add('dark');

    function changeTheme() {
        document.body.classList.toggle('dark');
        localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    }
</script>

</html>