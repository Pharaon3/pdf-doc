<p><a href="index.php">Back</a></p>
<?php
// Database connection settings
$host = 'localhost'; // Change if your database is hosted elsewhere
$db_name = 'test-database'; // Replace with your database name
$db_user = 'root'; // Replace with your database username
$db_password = ''; // Replace with your database password

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare("SELECT document_id, document_title, logo FROM document");
    $stmt->execute();
    
    // Fetch all documents
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if there are documents
    if ($documents) {
        echo "<h1>Document List</h1>";
        echo "<table border='1'>";
        echo "<tr><th>Document ID</th><th>Document Title</th><th>Logo</th></tr>";
        
        // Loop through documents and display them
        foreach ($documents as $document) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($document['document_id']) . "</td>";
            echo "<td>" . htmlspecialchars($document['document_title']) . "</td>";
            echo "<td><img src='" . htmlspecialchars($document['logo']) . "' alt='Logo' style='width:100px; height:auto;'></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No documents found.</p>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
