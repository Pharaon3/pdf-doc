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
    // Build HTML content with bookmarks
    $htmlContent = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($document_title) . '</title>
        <style>
            body { font-family: Tahoma, sans-serif; margin: 20px; }
            h1 { text-align: center; }
            .section { margin-top: 20px; }
            .section h2 { margin-bottom: 10px; }
            .blankline { height: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            table, th, td { border: 1px solid black; text-align: center; padding: 5px; }
            img { display: block; margin: 10px auto; max-width: 100%; }
            a { color: black; text-decoration: auto; }
        </style>
    </head>
    <body>';
    $imagePath = realpath($document_logo); // Converts to absolute path
    if ($imagePath && file_exists($imagePath)) {
        $imageBase64 = base64_encode(file_get_contents($imagePath));
        $htmlContent .= '<img src="data:image/jpeg;base64,' . $imageBase64 . '" alt="Logo" width="50px;">';
    } else {
        $htmlContent .= '<p>Image not found: ' . htmlspecialchars($cont) . '</p>';
    }
    $htmlContent .= '<h1>' . htmlspecialchars($document_title) . '</h1>
    <div class="sections">';
    // Loop through sections and add named anchors for bookmarks
    foreach ($sections as $key => $section) {
        $sectionName = htmlspecialchars($section['section_name']);
        $htmlContent .= '<div>';
        $htmlContent .= '<a href="#bookmark-' . ($key + 1) . '">';
        $htmlContent .= '<h2>' . ($key + 1) . '. ' . $sectionName . '</h2></a></div>';
    }
    foreach ($sections as $key => $section) {
        $sectionName = htmlspecialchars($section['section_name']);
        $htmlContent .= '<div class="section" id="bookmark-' . ($key + 1) . '">';
        $htmlContent .= '<h2>' . $sectionName . '</h2>';
        
        // Parse and add content
        $contents = json_decode($section['content'], true);
        foreach ($contents as $content) {
            foreach ($content as $key => $cont) {
                if ($key === 'text') {
                    $htmlContent .= '<p>' . htmlspecialchars($cont) . '</p>';
                } elseif ($key === 'blankline') {
                    $htmlContent .= '<div class="blankline"></div>';
                } elseif ($key === 'image') {
                    $imagePath = realpath($cont); // Converts to absolute path
                    if ($imagePath && file_exists($imagePath)) {
                        $imageBase64 = base64_encode(file_get_contents($imagePath));
                        $htmlContent .= '<img src="data:image/jpeg;base64,' . $imageBase64 . '" alt="Image">';
                    } else {
                        $htmlContent .= '<p>Image not found: ' . htmlspecialchars($cont) . '</p>';
                    }
                } elseif ($key === 'table') {
                    $htmlContent .= '<table>';
                    foreach ($cont as $row) {
                        $htmlContent .= '<tr>';
                        foreach ($row as $column) {
                            $htmlContent .= '<td>' . htmlspecialchars($column) . '</td>';
                        }
                        $htmlContent .= '</tr>';
                    }
                    $htmlContent .= '</table>';
                }
            }
        }

        $htmlContent .= '</div>';
    }

    $htmlContent .= '
        </div>
    </body>
    </html>';

    // Generate the PDF using Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('isFontSubsettingEnabled', true);
    $options->set('defaultFont', 'Tahoma');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($htmlContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Save or stream the PDF
    $pdfFilePath = './docs/' . $document_title . '.pdf';
    file_put_contents($pdfFilePath, $dompdf->output());
    header('Location: ' . $pdfFilePath);
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
