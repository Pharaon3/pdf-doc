<p><a href="document-list.php">Back</a></p>
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
    $stmt = $pdo->prepare("SELECT document_id, document_title, logo FROM document WHERE document_id=" . $_GET['id']);
    $stmt->execute();
    
    // Fetch all documents
    $document = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $document_id = $document[0]['document_id'];
    $document_logo = $document[0]['logo'];
    $document_title = $document[0]['document_title'];
    
    $stmt = $pdo->prepare("SELECT * FROM section WHERE document_id=" . $_GET['id']);
    $stmt->execute();
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<html>
    <head>
        <style>
            #title-container {
                display: flex;
                align-items: center;
            }
            #title-container h1 {
                width: calc(100vw - 66px);
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
        </style>
    </head>
    <body>
        <div id="title-container">
            <img src='<?php echo($document_logo); ?>' alt='Logo' style='width:50px; height:auto;'>
            <h1> <?php echo($document_title); ?> </h1>
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
                            echo "<img src='" . htmlspecialchars($cont) . "' alt='Logo' style='width:auto; height:auto;'>";
                        }
                    }
                }
                ?>

            <?php } ?>
        </div>
    </body>
</html>