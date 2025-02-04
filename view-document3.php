<?php
require 'vendor/autoload.php'; // Include Composer's autoloader if using Composer
use Dompdf\Dompdf;
use Dompdf\Options;

// Database connection settings
$host = 'localhost'; 
$db_name = 'pdfdoc'; 
$db_user = 'root'; 
$db_password = ''; 

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare("SELECT * FROM pdf_documents WHERE id_document=" . $_GET['id']);
    $stmt->execute();
    
    // Fetch the document
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    $document_id = $document['id_document'];
    // $document_logo = $document['logo'];
    $document_description = $document['descrizione'];
    $document_title = $document['document_name'];
    
    // Fetch sections
    $stmt = $pdo->prepare("SELECT * FROM pdf_sections WHERE id_document=" . $_GET['id'] . " ORDER BY 'order_sec'");
    $stmt->execute();
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM pdf_subsections WHERE id_document=" . $_GET['id'] . " ORDER BY 'order_num'");
    $stmt->execute();
    $subsections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM pdf_section_text WHERE id_document=" . $_GET['id']. " ORDER BY order_num");
    $stmt->execute();
    $section_texts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM pdf_section_tables WHERE id_document=" . $_GET['id']. " ORDER BY id_table");
    $stmt->execute();
    $section_tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Check if the user requested a PDF
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
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        h1, h2, h3, h4, h5 { margin: 0.5rem; }
    </style>
</head>
<body>';
// $imagePath = realpath($document_logo); // Converts to absolute path
// if ($imagePath && file_exists($imagePath)) {
//     $imageBase64 = base64_encode(file_get_contents($imagePath));
//     $htmlContent .= '<img src="data:image/jpeg;base64,' . $imageBase64 . '" alt="Logo" width="50px;">';
// } else {
//     $htmlContent .= '<p>Image not found: ' . htmlspecialchars($cont) . '</p>';
// }
$htmlContent .= '<h1>' . htmlspecialchars($document_title) . '</h1>
<h3>' . htmlspecialchars($document_description) . '</h3>
<div class="sections">';
// Loop through sections and add named anchors for bookmarks
foreach ($sections as $key => $section) {
    $sectionName = htmlspecialchars($section['section_title']);
    $sectionSubTitle = htmlspecialchars($section['sub_title']);
    $htmlContent .= '<div>';
    $htmlContent .= '<a href="#bookmark-' . ($key + 1) . '">';
    $htmlContent .= '<h2>' . $section['order_sec'] . '. ' . $sectionName . '</h2></a>';
    $htmlContent .= '<h4>' . $sectionSubTitle . '</h4></div>';
    foreach ($subsections as $subKey => $subsection) {
        if ($subsection["id_section"] == $section["id_section"]) {
            $subsectionName = htmlspecialchars($subsection['title_subsection']);
            $subsectionSubTitle = htmlspecialchars($subsection['description']);
            $htmlContent .= '<div>';
            $htmlContent .= '<a href="#bookmark-sub-' . ($subKey + 1) . '">';
            $htmlContent .= '<h3>' . $subsection['order_num'] . '). ' . $subsectionName . '</h3></a>';
            $htmlContent .= '<h5>' . $subsectionSubTitle . '</h5></div>';
        }
    }
}

foreach ($sections as $key => $section) {
    $sectionName = htmlspecialchars($section['section_title']);
    $sectionSubTitle = htmlspecialchars($section['sub_title']);
    $htmlContent .= '<div>';
    $htmlContent .= '<h2 font-size="18" style="color: white">_BM_' . substr($sectionName, 0, 30) . '</h2>';
    $htmlContent .= '<h2 id="bookmark-' . ($key + 1) . '">' . $section['order_sec'] . '. ' . $sectionName . '</h2>';
    $htmlContent .= '<h4>' . $sectionSubTitle . '</h4></div>';

    foreach ($section_texts as $textKey => $section_text) {
        if ($section_text["id_section"] == $section["id_section"]) {
            if ($section_text["id_subsection"] == 0) {
                if ($section_text["image"] == "1") { // Image
                    $imagePath = realpath($section_text["image_path"]); // Converts to absolute path
                    if ($imagePath && file_exists($imagePath)) {
                        $imageBase64 = base64_encode(file_get_contents($imagePath));
                        $htmlContent .= '<p><img src="data:image/jpeg;base64,' . $imageBase64 . '" alt="' . $section_text["content"] . '" style="max-width: 450px; max-height: 50px;"></p>';
                    } else {
                        $htmlContent .= '<p>Image not found: ' . htmlspecialchars($imagePath) . '</p>';
                    }
                } else {    // Text
                    $htmlContent .= '<p>' . $section_text["content"] . '</p>';
                }
            }
        }
    }

    $tables = [];
    $tableIds = [];
    foreach ($section_tables as $tableKey => $section_table) {
        if ($section_table["id_section"] == $section["id_section"]) {
            if ($section_table["id_subsection"] == 0) {
                if (!array_key_exists($section_table["id_table"], $tableIds)) {
                    $tableIds[] = ($section_table["id_table"]);
                }
                $tableId = array_search($section_table["id_table"], $tableIds);
                $tables[$tableId][] = ($section_table);
            }
        }
    }
    foreach ($tables as $table) {
        $htmlContent .= '<table>';
        $colNum = 0;
        foreach ($table as $row) {
            if ($row['data_type'] == "title") {
                $htmlContent .= "<thead><tr>";
                for ($x = 1; $x <= 16; $x++) {
                    if ($row['col_' . $x]) {
                        $htmlContent .= "<th>" . $row['col_' . $x] . "</th>";
                        $colNum = $x;
                    }
                }
                $htmlContent .= "</tr></thead>";
            }
        }
        $htmlContent .= "<tbody>";
        foreach ($table as $row) {
            if ($row['data_type'] != "title") {
                $htmlContent .= "<tr>";
                for ($x = 1; $x <= 16; $x++) {
                    if ($row['col_' . $x] || $x <= $colNum) {
                        $htmlContent .= "<td>" . $row['col_' . $x] . "</td>";
                    }
                }
                $htmlContent .= "</tr>";
            }
        }
        $htmlContent .= "</tbody></table>";
    }

    foreach ($subsections as $subKey => $subsection) {
        if ($subsection["id_section"] == $section["id_section"]) {
            $subsectionName = htmlspecialchars($subsection['title_subsection']);
            $subsectionSubTitle = htmlspecialchars($subsection['description']);
            $htmlContent .= '<div>';
            $htmlContent .= '<h2 font-size="18" style="color: white">_SBM_' . substr($subsectionName, 0, 30) . '</h2>';
            $htmlContent .= '<h3 id="bookmark-sub-' . ($subKey + 1) . '">' . $subsection['order_num'] . '). ' . $subsectionName . '</h3>';
            $htmlContent .= '<h5>' . $subsectionSubTitle . '</h5></div>';

            foreach ($section_texts as $textKey => $section_text) {
                if ($section_text["id_section"] == $section["id_section"]) {
                    if ($section_text["id_subsection"] == $subsection["id_subsection"]) {
                        if ($section_text["image"] == "1") { // Image
                            $imagePath = realpath($section_text["image_path"]); // Converts to absolute path
                            if ($imagePath && file_exists($imagePath)) {
                                $imageBase64 = base64_encode(file_get_contents($imagePath));
                                $htmlContent .= '<p><img src="data:image/jpeg;base64,' . $imageBase64 . '" alt="' . $section_text["content"] . '" style="max-width: 450px; max-height: 50px;"></p>';
                            } else {
                                $htmlContent .= '<p>Image not found: ' . htmlspecialchars($imagePath) . '</p>';
                            }
                        } else {    // Text
                            $htmlContent .= '<p>' . $section_text["content"] . '</p>';
                        }
                    }
                }
            }

            $tables = [];
            $tableIds = [];
            foreach ($section_tables as $tableKey => $section_table) {
                if ($section_table["id_section"] == $section["id_section"]) {
                    if ($section_table["id_subsection"] == $subsection["id_subsection"]) {
                        if (!array_key_exists($section_table["id_table"], $tableIds)) {
                            $tableIds[] = ($section_table["id_table"]);
                        }
                        $tableId = array_search($section_table["id_table"], $tableIds);
                        $tables[$tableId][] = ($section_table);
                    }
                }
            }
            foreach ($tables as $table) {
                $htmlContent .= '<table>';
                $colNum = 0;
                foreach ($table as $row) {
                    if ($row['data_type'] == "title") {
                        $htmlContent .= "<thead><tr>";
                        for ($x = 1; $x <= 16; $x++) {
                            if ($row['col_' . $x]) {
                                $htmlContent .= "<th>" . $row['col_' . $x] . "</th>";
                                $colNum = $x;
                            }
                        }
                        $htmlContent .= "</tr></thead>";
                    }
                }
                $htmlContent .= "<tbody>";
                foreach ($table as $row) {
                    if ($row['data_type'] != "title") {
                        $htmlContent .= "<tr>";
                        for ($x = 1; $x <= 16; $x++) {
                            if ($row['col_' . $x] || $x <= $colNum) {
                                $htmlContent .= "<td>" . $row['col_' . $x] . "</td>";
                            }
                        }
                        $htmlContent .= "</tr>";
                    }
                }
                $htmlContent .= "</tbody></table>";
            }
            
        }
    }
}

$htmlContent .= '</div></body></html>';

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
$pdfFilePath = './docs/temp.pdf';
file_put_contents($pdfFilePath, $dompdf->output());
$command = escapeshellcmd('python bookmark.py');
shell_exec($command);
echo '<p><a href="document-list.php">Back</a></p>' . $htmlContent . '<p>Your PDF will start downloading automatically. If not, <a href="./out.pdf" download>click here</a>.</p>
<script>
    // Auto trigger the PDF download
    window.onload = function() {
        var link = document.createElement("a");
        link.href = "out.pdf";
        link.download = "out.pdf";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };
</script>';
// header('Location: ./out.pdf');
exit;
?>