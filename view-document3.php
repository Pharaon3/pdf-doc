<?php
require 'vendor/autoload.php'; // Include Composer's autoloader if using Composer
use Dompdf\Dompdf;
use Dompdf\Options;

// Database connection settings
$host = 'localhost'; 
$db_name = 'test-database'; 
$db_user = 'root'; 
$db_password = ''; 

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare("SELECT document_id, document_title, logo FROM document WHERE document_id=" . $_GET['id']);
    $stmt->execute();
    
    // Fetch the document
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    $document_id = $document['document_id'];
    $document_logo = $document['logo'];
    $document_title = $document['document_title'];
    
    // Fetch sections
    $stmt = $pdo->prepare("SELECT * FROM section WHERE document_id=" . $_GET['id']);
    $stmt->execute();
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Check if the user requested a PDF
if (isset($_GET['pdf']) && $_GET['pdf'] == 'true') {
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            max-width: 800px;
            margin: auto;
            margin-top: 50px;
            background-color: ghostwhite;
        }
        #container {
            padding: 20px;
            background-color: white;
            overflow: hidden;
        }
        #title-container {
            display: flex;
            align-items: center;
        }
        #title-container img {
            width:50px; 
            height:auto; 
            position: absolute;
        }
        #title-container h1 {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        p {
            margin: 0;
        }
        .blankline {
            padding: 10px;
        }
        #section-container h2 {
            display: flex;
            justify-content: center;
        }
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        table {
            width: 100%;
            text-align: center;
        }
    </style>
</head>
<body>
    <p><a href="document-list.php">Back</a></p>
    <div id="container">
        <div id="title-container">
            <img src='<?php echo($document_logo); ?>' alt='Logo'>
            <h1> <?php echo($document_title); ?> </h1>
        </div>
        <div id="section-title-list">
            <?php foreach($sections as $key => $section) { 
                $section_name = $section['section_name'];
                echo "<h2>" . ($key + 1) . " - " . $section_name . "</h2>";
            } ?>
        </div>
        <div id="section-container">
            <?php foreach($sections as $section) { 
                $section_name = $section['section_name'];
                echo "<h2>" . $section_name . "</h2>";
                $contents = json_decode($section['content']);
                foreach ($contents as $content) {
                    foreach ($content as $key => $cont) {
                        if ($key == 'text') {
                            echo "<p>" . $cont . "</p>";
                        } elseif ($key == 'blankline') {
                            echo("<p class='blankline'></p>");
                        } elseif ($key == 'image') {
                            echo "<img src='" . htmlspecialchars($cont) . "' alt='Image' style='width:auto; height:auto;'>";
                        } elseif ($key == 'table') {
                            echo "<table>";
                            foreach ($cont as $row) {
                                echo "<tr>";
                                foreach ($row as $column) {
                                    echo "<td>" . $column . "</td>";
                                }
                                echo "</tr>";
                            }
                            echo "</table>";
                        }
                    }
                }
            } ?>
        </div>
    </div>
    <a href='<?php echo "view-document3.php?id=" . $_GET["id"] . "&pdf=true"; ?>'>Download PDF</a>
</body>
</html>
