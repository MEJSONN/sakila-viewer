<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("Błąd połączenia z bazą danych: " . $baza->connect_error);

$fid = $_GET['fid'];

$filmInfo = $baza->query("
    SELECT 
        f.*, 
        l.name AS language, 
        ol.name AS original_language,
        GROUP_CONCAT(c.name) AS categories
    FROM film f
    JOIN language l ON f.language_id = l.language_id
    LEFT JOIN language ol ON f.original_language_id = ol.language_id
    LEFT JOIN film_category fc ON f.film_id = fc.film_id
    LEFT JOIN category c ON fc.category_id = c.category_id
    WHERE f.film_id = $fid
    GROUP BY f.film_id;
");

foreach ($filmInfo as $film) {
    $title = $film['title'];
}

$languagesList = $baza->query("SELECT language_id, name FROM `language`");
$categoriesList = $baza->query("SELECT category_id, name FROM `category`");
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="styl.css">
</head>

<?php
if ($filmInfo->num_rows === 0) {
    echo "<h1>Film nie znaleziony!</h1>";
    exit;
}
?>

<body>
    <header>
        <button onclick="location.href='admin.php'">Panel administracyjny</button>
        <h1><?php echo $title; ?></h1>
        <button onclick="changeTheme()">Zmień motyw</button>
    </header>
    <?php
    foreach ($filmInfo as $film) {
        $title = $film['title'];
        $description = $film['description'];
        $releaseYear = $film['release_year'];
        $rentalDuration = $film['rental_duration'];
        $rentalRate = $film['rental_rate'];
        $length = $film['length'];
        $replacementCost = $film['replacement_cost'];
        $lastUpdate = $film['last_update'];
        $language = $film['language'];
        $originalLanguage = ($film['original_language'] === null) ? 'Brak' : $film['original_language'];
        $categories = isset($film['categories']) && $film['categories'] !== null ? $film['categories'] : 'Brak';
    }
    ?>
    <form method="post">
        <section class="movie-info">
            <label for="title">
                <p>Tytuł:</p>
                <input type="text" name="title" value="<?php echo $title; ?>" required>
            </label>
            <label for="description">
                <p>Opis:</p>
                <textarea name="description" required rows="3" cols="50"><?php echo $description; ?></textarea>
            </label>
            <label for="releaseYear">
                <p>Rok wydania:</p>
                <input type="number" name="releaseYear" value="<?php echo $releaseYear; ?>" min="1900" max="2099" required>
            </label>
            <label for="rentalDuration">
                <p>Czas wypożyczenia (dni):</p>
                <input type="number" name="rentalDuration" value="<?php echo $rentalDuration; ?>" min="1" required>
            </label>
            <label for="rentalRate">
                <p>Koszt wypożyczenia:</p>
                <input type="number" name="rentalRate" value="<?php echo $rentalRate; ?>" min="0" step="0.01" required>
            </label>
            <label for="length">
                <p>Długość filmu (min):</p>
                <input type="number" name="length" value="<?php echo $length; ?>" min="1" required>
            </label>
            <label for="replacementCost">
                <p>Koszt wymiany:</p>
                <input type="number" name="replacementCost" value="<?php echo $replacementCost; ?>" min="0" step="0.01" required>
            </label>
            <label for="language">
                <p>Język:</p>
                <select name="language" require>
                    <?php
                    foreach ($languagesList as $languageList) {
                        echo "
                        <option value=\"{$languageList['language_id']}\">{$languageList['name']}</option>
                        ";
                    }
                    ?>
                </select>
            </label>
            <label for="originalLanguage">
                <p>Język oryginalny:</p>
                <select name="originalLanguage">
                    <option value="">Brak</option>
                    <?php
                    foreach ($languagesList as $languageList) {
                        $selected = ($originalLanguage === $languageList['name']) ? 'selected' : '';
                        echo "<option value=\"{$languageList['language_id']}\" $selected>{$languageList['name']}</option>";
                    }
                    ?>
                </select>
            </label>
            <label for="categorie">
                <p>Kategoria:</p>
                <select name="categorie" require>
                    <?php
                    foreach ($categoriesList as $categorieList) {
                        echo "
                        <option value=\"{$categorieList['categorie_id']}\">{$categorieList['name']}</option>
                        ";
                    }
                    ?>
                </select>
            </label>
            <label for="lastUpdate">
                <p>Ostatnia aktualizacja:</p>
                <input type="datetime-local" name="lastUpdate" value="<?php echo date('Y-m-d\TH:i', strtotime($lastUpdate)); ?>" readonly>
            </label><br>
        </section>
        <section class="movie-info">
            <button type="submit">Zapisz zmiany</button>
        </section>
    </form>
</body>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pobranie danych z formularza
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

    $stmt = $baza->prepare("
        UPDATE film SET 
            title = ?, 
            description = ?, 
            release_year = ?, 
            rental_duration = ?, 
            rental_rate = ?, 
            length = ?, 
            replacement_cost = ?, 
            language_id = ?, 
            original_language_id = ?, 
            last_update = NOW()
        WHERE film_id = ?
    ");
    $stmt->bind_param(
        "ssiiddiiii",
        $title,
        $description,
        $releaseYear,
        $rentalDuration,
        $rentalRate,
        $length,
        $replacementCost,
        $languageId,
        $originalLanguageId,
        $fid
    );

    if ($stmt->execute()) {
        echo "
            <section class='movie-info'>
                <p>Pomyślnie zedytowano film!</p>
            </section>
        ";
    } else {
        echo "
            <section class='movie-info'>
                <p>Niestety nie udało się edytować filmu!</p>
                <p>Błąd: {$stmt->error}</p>
            </section>
        ";
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