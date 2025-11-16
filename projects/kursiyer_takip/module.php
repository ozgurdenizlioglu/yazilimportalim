<?php
$pageTitle = "Kursiyer Takip Programı";
include INCLUDES_PATH . 'header.php';
?>

<h2>Kursiyer Takip Modülü</h2>
<p>Bu basit örnekte kursiyerleri görüntüleyebilir, yeni kayıt ekleyebilirsiniz.</p>

<?php
$result = $conn->query("SELECT * FROM kursiyerler ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Ad</th><th>Soyad</th><th>E-posta</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['ad']}</td><td>{$row['soyad']}</td><td>{$row['email']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Henüz kursiyer eklenmemiş.</p>";
}
?>

<?php include INCLUDES_PATH . 'footer.php'; ?>