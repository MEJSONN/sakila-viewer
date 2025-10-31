<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("Błąd połączenia z bazą danych: {$baza->connect_error}");

$languagesList = $baza->query("SELECT language_id, name FROM `language`");
$categoriesList = $baza->query("SELECT category_id, name FROM `category`");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $raiting = $_POST['raiting'];
    $releaseYear = (int)$_POST['releaseYear'];
    $rentalDuration = (int)$_POST['rentalDuration'];
    $rentalRate = (float)$_POST['rentalRate'];
    $length = (int)$_POST['length'];
    $replacementCost = (float)$_POST['replacementCost'];
    $language = (int)$_POST['language'];
    $originalLanguage = !empty($_POST['originalLanguage']) ? (int)$_POST['originalLanguage'] : null;
    $categorie = (int)$_POST['categorie'];
    $count = (int)$_POST['count'];
    $actors = isset($_POST['actors']) ? (is_array($_POST['actors']) ? $_POST['actors'] : array($_POST['actors'])) : [];

    $stmt = $baza->prepare("INSERT INTO film (title, description, release_year, language_id, original_language_id, rental_duration, rental_rate, length, replacement_cost, rating, last_update) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) die("Błąd przygotowania zapytania: {$baza->error}");
    $stmt->bind_param('ssiiididds', $title, $description, $releaseYear, $language, $originalLanguage, $rentalDuration, $rentalRate, $length, $replacementCost, $raiting);
    if (!$stmt->execute()) die("Błąd wykonania zapytania: {$stmt->error}");
    $filmId = $stmt->insert_id ? $stmt->insert_id : $baza->insert_id;
    $stmt->close();

    foreach ($actors as $actorId) {
        $actorIdEscaped = (int)$actorId;
        $baza->query("INSERT INTO film_actor (actor_id, film_id, last_update) VALUES ($actorIdEscaped, $filmId, NOW())");
    }

    $baza->query("INSERT INTO film_category (film_id, category_id, last_update) VALUES ($filmId, $categorie, NOW())");

    for ($i = 0; $i < $count; $i++) {
        $baza->query("INSERT INTO inventory (film_id, store_id, last_update) VALUES ($filmId, 1, NOW())");
    }
    header('Location: ./admin.php');
    exit;
}
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
                        $id = 'actor_' . $actor['actor_id'];
                        echo "
                        <label for=\"{$id}\" class='category-label'>
                            <input id=\"{$id}\" type=\"checkbox\" name=\"actors[]\" value=\"{$actor['actor_id']}\">
                            <span>{$actor['first_name']} {$actor['last_name']}</span>
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
            </label>
        </section>
        <section class="movie-info">
            <button type="submit">Dodaj film</button>
        </section>
    </form>
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

<?php
$baza->close();
?>