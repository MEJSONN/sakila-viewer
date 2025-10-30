<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("Błąd połączenia z bazą danych: " . $baza->connect_error);

$fid = $_GET['fid'] ?? 0;
if (!$fid) {
    die("Nie podano ID filmu");
}

$stmt = $baza->prepare("
    SELECT f.*, l.name as language_name, ol.name as original_language_name, 
           c.category_id, c.name as category_name,
           (SELECT COUNT(*) FROM inventory WHERE film_id = f.film_id) as copy_count,
           (SELECT COUNT(*) FROM inventory i 
            WHERE i.film_id = f.film_id 
            AND i.inventory_id NOT IN (SELECT inventory_id FROM rental WHERE return_date IS NULL)
           ) as available_copies
    FROM film f
    LEFT JOIN language l ON f.language_id = l.language_id
    LEFT JOIN language ol ON f.original_language_id = ol.language_id
    LEFT JOIN film_category fc ON f.film_id = fc.film_id
    LEFT JOIN category c ON fc.category_id = c.category_id
    WHERE f.film_id = ?
");
$stmt->bind_param("i", $fid);
$stmt->execute();
$filmInfo = $stmt->get_result()->fetch_assoc();

if (!$filmInfo) {
    die("Film nie został znaleziony");
}

$actorStmt = $baza->prepare("
    SELECT a.actor_id 
    FROM film_actor fa 
    JOIN actor a ON fa.actor_id = a.actor_id 
    WHERE fa.film_id = ?
");
$actorStmt->bind_param("i", $fid);
$actorStmt->execute();
$result = $actorStmt->get_result();
$selectedActors = [];
while ($row = $result->fetch_assoc()) {
    $selectedActors[] = $row['actor_id'];
}

$languagesList = $baza->query("SELECT language_id, name FROM language ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$categoriesList = $baza->query("SELECT category_id, name FROM category ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT COUNT(*) as count FROM inventory i WHERE i.film_id = " . (int)$fid . " AND i.inventory_id NOT IN (SELECT inventory_id FROM rental)";
$deletableRow = $baza->query($sql)->fetch_assoc();
$deletableCount = (int)($deletableRow['count'] ?? 0);
$minCopies = max(1, (int)$filmInfo['copy_count'] - $deletableCount);
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytowanie filmu</title>
    <link rel="stylesheet" href="styl.css">
</head>


<body>

    <script>
        const theme = localStorage.getItem('theme');
        if (theme === 'dark') document.body.classList.add('dark');

        function changeTheme() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        }
    </script>

    <header>
        <button onclick="location.href='admin.php'">Panel administracyjny</button>
        <h1>Edytowanie filmu</h1>
        <button onclick="changeTheme()">Zmień motyw</button>
    </header>
    <form method="post">
        <section class="movie-info">
            <label for="title">
                <p>Tytuł:</p>
                <input type="text" name="title" value="<?php echo htmlspecialchars($filmInfo['title']); ?>" required>
            </label>
            <label for="description">
                <p>Opis:</p>
                <textarea name="description" required rows="3" cols="50"><?php echo htmlspecialchars($filmInfo['description']); ?></textarea>
            </label>
            <details>
                <summary>Aktorzy:</summary>
                <?php
                $actors = $baza->query("SELECT actor_id, first_name, last_name FROM actor ORDER BY first_name, last_name");
                while ($actor = $actors->fetch_assoc()) {
                    $isChecked = in_array($actor['actor_id'], $selectedActors) ? 'checked' : '';
                    echo "
                    <label for=\"actor_{$actor['actor_id']}\">
                        <input type=\"checkbox\" id=\"actor_{$actor['actor_id']}\" name=\"actors[]\" value=\"{$actor['actor_id']}\" {$isChecked}>
                        " . htmlspecialchars("{$actor['first_name']} {$actor['last_name']}") . "
                    </label><br>
                    ";
                }
                ?>
            </details>
            <label for="raiting">
                <p>Ocena:</p>
                <select name="raiting" required>
                    <?php
                    $ratings = ['G', 'PG', 'PG-13', 'R', 'NC-17'];
                    foreach ($ratings as $r) {
                        $selected = ($filmInfo['rating'] === $r) ? 'selected' : '';
                        echo "<option value=\"{$r}\" {$selected}>{$r}</option>";
                    }
                    ?>
                </select>
            </label>
            <label for="releaseYear">
                <p>Rok wydania:</p>
                <input type="number" name="releaseYear" min="1900" max="2099" value="<?php echo $filmInfo['release_year']; ?>" required>
            </label>
            <label for="rentalDuration">
                <p>Czas wypożyczenia (dni):</p>
                <input type="number" name="rentalDuration" min="1" value="<?php echo $filmInfo['rental_duration']; ?>" required>
            </label>
            <label for="rentalRate">
                <p>Koszt wypożyczenia:</p>
                <input type="number" name="rentalRate" min="0" step="0.01" value="<?php echo $filmInfo['rental_rate']; ?>" required>
            </label>
            <label for="length">
                <p>Długość filmu (min):</p>
                <input type="number" name="length" min="1" value="<?php echo $filmInfo['length']; ?>" required>
            </label>
            <label for="replacementCost">
                <p>Koszt wymiany:</p>
                <input type="number" name="replacementCost" min="0" step="0.01" value="<?php echo $filmInfo['replacement_cost']; ?>" required>
            </label>
            <label for="language">
                <p>Język:</p>
                <select name="language" required>
                    <?php
                    foreach ($languagesList as $lang) {
                        $selected = ($lang['language_id'] == $filmInfo['language_id']) ? 'selected' : '';
                        echo "<option value=\"{$lang['language_id']}\" {$selected}>" .
                            htmlspecialchars($lang['name']) . "</option>";
                    }
                    ?>
                </select>
            </label>
            <label for="originalLanguage">
                <p>Język oryginalny:</p>
                <select name="originalLanguage">
                    <option value="">Brak informacji</option>
                    <?php
                    foreach ($languagesList as $lang) {
                        $selected = ($lang['language_id'] == $filmInfo['original_language_id']) ? 'selected' : '';
                        echo "<option value=\"{$lang['language_id']}\" {$selected}>" .
                            htmlspecialchars($lang['name']) . "</option>";
                    }
                    ?>
                </select>
            </label>
            <label for="categorie">
                <p>Kategoria:</p>
                <select name="categorie" required>
                    <?php
                    foreach ($categoriesList as $cat) {
                        $selected = ($cat['category_id'] == $filmInfo['category_id']) ? 'selected' : '';
                        echo "<option value=\"{$cat['category_id']}\" {$selected}>" .
                            htmlspecialchars($cat['name']) . "</option>";
                    }
                    ?>
                </select>
            </label>
            <label for="count">
                <p>Ilość kopii:</p>
                <input type="number" name="count" min="<?php echo $minCopies; ?>" value="<?php echo $filmInfo['copy_count']; ?>" required>
                <small>(Minimalna możliwa ilość kopii: <?php echo $minCopies; ?>)</small>
            </label>
        </section>
        <section class="movie-info">
            <button type="submit">Zapisz zmiany</button>
        </section>
    </form>
</body>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baza->begin_transaction();

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
    $rating = $_POST['raiting'] ?? 'G';
    $selectedActors = $_POST['actors'] ?? [];
    $newCopyCount = (int)($_POST['count'] ?? 0);
    $currentCopyCount = (int)$filmInfo['copy_count'];

    $stmt = $baza->prepare("
            UPDATE film 
            SET title = ?, 
                description = ?, 
                release_year = ?, 
                rental_duration = ?, 
                rental_rate = ?, 
                length = ?, 
                replacement_cost = ?, 
                language_id = ?, 
                original_language_id = ?,
                rating = ?,
                last_update = NOW()
            WHERE film_id = ?
        ");

    $stmt->bind_param(
        "ssiiddiiisi",
        $title,
        $description,
        $releaseYear,
        $rentalDuration,
        $rentalRate,
        $length,
        $replacementCost,
        $languageId,
        $originalLanguageId,
        $rating,
        $fid
    );

    $stmt->execute();

    $baza->query("DELETE FROM film_category WHERE film_id = $fid");
    if ($categoryId > 0) {
        $stmtCat = $baza->prepare("INSERT INTO film_category (film_id, category_id, last_update) VALUES (?, ?, NOW())");
        $stmtCat->bind_param("ii", $fid, $categoryId);
        $stmtCat->execute();
    }

    $baza->query("DELETE FROM film_actor WHERE film_id = $fid");
    if (!empty($selectedActors)) {
        $stmtActors = $baza->prepare("
                INSERT INTO film_actor (actor_id, film_id, last_update) 
                VALUES (?, ?, NOW())
            ");

        $stmtActors->bind_param("ii", $actorId, $fid);
        foreach ($selectedActors as $actorId) {
            $stmtActors->execute();
        }
    }

    $deletableCount = $baza->query("
        SELECT COUNT(*) as count
        FROM inventory i
        WHERE i.film_id = $fid 
        AND i.inventory_id NOT IN (
            SELECT inventory_id 
            FROM rental
        )")->fetch_assoc()['count'];

    if ($newCopyCount > $currentCopyCount) {
        $stmtAddInventory = $baza->prepare("
            INSERT INTO inventory (film_id, store_id, last_update) 
            VALUES (?, 1, NOW())
        ");
        $stmtAddInventory->bind_param("i", $fid);
        for ($i = 0; $i < ($newCopyCount - $currentCopyCount); $i++) {
            $stmtAddInventory->execute();
        }
    } elseif ($newCopyCount < $currentCopyCount) {
        $toDelete = min($currentCopyCount - $newCopyCount, $deletableCount);
        if ($toDelete > 0) {
            // Only delete inventory rows that have no rental records at all.
            // Deleting inventory with any rental history will violate the FK constraint
            // (rental.inventory_id -> inventory.inventory_id). Wrap the operation to
            // handle any database exceptions gracefully.
            try {
                $baza->query("
                    DELETE FROM inventory 
                    WHERE film_id = $fid 
                    AND inventory_id NOT IN (
                        SELECT inventory_id 
                        FROM rental
                    )
                    LIMIT $toDelete
                ");
            } catch (mysqli_sql_exception $e) {
                $baza->rollback();
                echo "<script>\n" .
                    "alert('Nie można usunąć kopii filmu ze względu na powiązane rekordy wypożyczeń.');\n" .
                    "document.getElementsByName('count')[0].value = " . ($currentCopyCount) . ";\n" .
                    "</script>";
                exit();
            }
        }

        if ($toDelete < ($currentCopyCount - $newCopyCount)) {
            echo "<script>
                alert('Nie można zmniejszyć ilości kopii do " . $newCopyCount .
                " ponieważ niektóre kopie mają historię wypożyczeń. " .
                "Minimalna możliwa ilość kopii to " . ($currentCopyCount - $toDelete) . "');
                document.getElementsByName('count')[0].value = " . ($currentCopyCount - $toDelete) . ";
            </script>";
            exit();
        }
    }

    $baza->commit();

    echo "<script>
        window.location.href = 'edit.php?fid=" . $fid . "';
    </script>";
    exit();
}
?>

</html>