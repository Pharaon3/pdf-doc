<?php
require 'vendor/autoload.php'; // Include Composer's autoloader if using Composer
use PhpOffice\PhpWord\Shared\Converter;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\PDF;

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
    // Creating the new document...
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $phpWord->getSettings()->setMirrorMargins(true);
    // $phpWord->addSection();
    /* Note: any element you append to a document must reside inside of a Section. */

    // Adding an empty Section to the document...
    $section = $phpWord->addSection([
        'paperSize' => 'A4',
        'orientation' => 'portrait',
        'pageSizeW' => Converter::cmToTwip(21),
        'pageSizeH' => Converter::cmToTwip(29.7),
    ]);
    $section->addText(' ',
        array('name' => 'Tahoma', 'size' => 12)
    );
    // Adding Text element to the Section having font styled by default...
    $section->addImage($document_logo, array(
        'width' => 30,
        'align' => 'left', // Center alignment
        'wrappingStyle' => 'inline' // Inline wrapping style
    ));
    $section->addText($document_title, array('name' => 'Tahoma', 'size' => 18, 'bold' => true), array('align' => 'center'));

    $section->addText(' ',
        array('name' => 'Tahoma', 'size' => 12)
    );
    foreach($sections as $key => $eachsection) { 
        $section_name = $eachsection['section_name'];
        $section->addText($key + 1 . ' - ' . $section_name,
            array('name' => 'Tahoma', 'size' => 16)
        );
    }
    $section->addText(' ',
        array('name' => 'Tahoma', 'size' => 12)
    );
    foreach($sections as $eachsection) { 
        $section_name = $eachsection['section_name'];
        $section->addText($section_name,
            array('name' => 'Tahoma', 'size' => 16)
        );
        $section->addText(' ',
            array('name' => 'Tahoma', 'size' => 12)
        );
        $contents = json_decode($eachsection['content']);
        foreach ($contents as $content) {
            foreach ($content as $key => $cont) {
                if ($key == 'text') {
                    $section->addText($cont,
                        array('name' => 'Tahoma', 'size' => 12)
                    );
                } elseif ($key == 'blankline') {
                    $section->addText(' ',
                        array('name' => 'Tahoma', 'size' => 12)
                    );
                } elseif ($key == 'image') {
                    $section->addImage($cont, array(
                        'width' => 400,
                        'align' => 'center', // Center alignment
                        'wrappingStyle' => 'inline' // Inline wrapping style
                    ));
                } elseif ($key == 'table') {
                    $table = $section->addTable(array('borderSize' => 6, 'borderColor' => '999999'));
                    foreach ($cont as $row) {
                        $table->addRow();
                        foreach ($row as $column) {
                            $table->addCell(2000)->addText($column, array('bold' => true));
                        }
                    }
                }
            }
        }
    }
    // /*
    // * Note: it's possible to customize font style of the Text element you add in three ways:
    // * - inline;
    // * - using named font style (new font style object will be implicitly created);
    // * - using explicitly created font style object.
    // */

    // // Adding Text element with font customized inline...
    // $section->addText(
    //     '"Great achievement is usually born of great sacrifice, '
    //         . 'and is never the result of selfishness." '
    //         . '(Napoleon Hill)',
    //     array('name' => 'Tahoma', 'size' => 10)
    // );

    // // Adding Text element with font customized using named font style...
    // $fontStyleName = 'oneUserDefinedStyle';
    // $phpWord->addFontStyle(
    //     $fontStyleName,
    //     array('name' => 'Tahoma', 'size' => 10, 'color' => '1B2232', 'bold' => true)
    // );
    // $section->addText(
    //     '"The greatest accomplishment is not in never falling, '
    //         . 'but in rising again after you fall." '
    //         . '(Vince Lombardi)',
    //     $fontStyleName
    // );

    // // Adding Text element with font customized using explicitly created font style object...
    // $fontStyle = new \PhpOffice\PhpWord\Style\Font();
    // $fontStyle->setBold(true);
    // $fontStyle->setName('Tahoma');
    // $fontStyle->setSize(13);
    // $myTextElement = $section->addText('"Believe you can and you\'re halfway there." (Theodor Roosevelt)');
    // $myTextElement->setFontStyle($fontStyle);

    // Saving the document as OOXML file...
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('./docs/' . $document_title . '.docx');

    header('Location: '.'./docs/' . $document_title . '.docx');
    // $writer = IOFactory::createWriter($phpWord, 'PDF');
    // $writer->save($document_title . '.pdf');

    // Saving the document as HTML file...
    // $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
    // $objWriter->save($document_title . '.html');

    /* Note: we skip RTF, because it's not XML-based and requires a different example. */
    /* Note: we skip PDF, because "HTML-to-PDF" approach is used to create PDF documents. */
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
    <a href='<?php echo "view-document2.php?id=" . $_GET["id"] . "&pdf=true"; ?>'>Download PDF</a>

</body>
</html>
