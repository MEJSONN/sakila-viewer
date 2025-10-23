<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("Błąd połączenia z bazą danych: " . $baza->connect_error);

$fid = isset($_GET['fid']) ? (int)$_GET['fid'] : 0;
if ($fid <= 0) {
    die("Niepoprawne ID filmu.");
}

$baza->begin_transaction();

try {
    // Usuń wypożyczenia związane z egzemplarzami filmu
    $baza->query("
        DELETE r
        FROM rental r
        JOIN inventory i ON r.inventory_id = i.inventory_id
        WHERE i.film_id = $fid
    ");

    // Usuń egzemplarze filmu
    $baza->query("DELETE FROM inventory WHERE film_id = $fid");

    // Usuń powiązania z aktorami
    $baza->query("DELETE FROM film_actor WHERE film_id = $fid");

    // Usuń powiązania z kategoriami
    $baza->query("DELETE FROM film_category WHERE film_id = $fid");

    // Usuń sam film
    $baza->query("DELETE FROM film WHERE film_id = $fid");

    $baza->commit();

    // Automatyczny powrót do admin.php po 2 sekundach
    echo "<p>Film i wszystkie powiązane dane zostały usunięte pomyślnie. Za chwilę nastąpi powrót do panelu administracyjnego...</p>";
    echo "<script>
            setTimeout(function(){
                window.location.href = 'admin.php';
            }, 2000);
          </script>";

} catch (Exception $e) {
    $baza->rollback();
    echo "<p>Wystąpił błąd: " . $e->getMessage() . "</p>";
    echo "<p>Powrót do panelu administracyjnego za chwilę...</p>";
    echo "<script>
            setTimeout(function(){
                window.location.href = 'admin.php';
            }, 3000);
          </script>";
}
?>