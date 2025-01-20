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
    // Initialize DOMPDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $dompdf = new Dompdf($options);

    // Start output buffering
    ob_start();
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
                                echo "<img src='" . htmlspecialchars($cont) . "' alt='Image' style='width:auto; height:auto;'>";
                            }
                        }
                    }
                } ?>
            </div>
        </body>
    </html>
    
    <?php
    // Get the HTML content
    $html = ob_get_clean();

    // Load HTML into DOMPDF
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    
    // Render the PDF
    $dompdf->render();
    
    // Output the generated PDF
    $dompdf->stream($document_title . ".pdf", ["Attachment" => true]);
    exit; // Stop further script execution
}
?>

<html>
    <head>
        <style>
            /* Same styles as before */
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
                            echo "<img src='" . htmlspecialchars($cont) . "' alt='Image' style='width:auto; height:auto;'>";
                        }
                    }
                }
            } ?>
        </div>
        <p><a href="?id=<?php echo $_GET['id']; ?>&pdf=true">Export to PDF</a></p>
    </body>
</html>
