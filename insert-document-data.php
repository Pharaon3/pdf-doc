<p><a href="index.php">Back</a></p>
<?php
// Start the session and include necessary files if needed
session_start();

// Include database connection file (update with your actual connection file)
$host = 'localhost'; // Change if your database is hosted elsewhere
$db_name = 'test-database'; // Replace with your database name
$db_user = 'root'; // Replace with your database username
$db_password = ''; // Replace with your database password

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize input data
    $document_title = $_POST['document_title'];
    
    // Handle logo upload
    $logo = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/"; // Ensure this directory exists and is writable
        $target_file = $target_dir . basename($_FILES["logo"]["name"]);
        move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file);
        $logo = $target_file; // Store path to logo
    }

    // Insert document into the database
    $stmt = $pdo->prepare("INSERT INTO Document (document_title, logo) VALUES (?, ?)");
    $stmt->execute([$document_title, $logo]);
    
    // Get the last inserted document ID
    $document_id = $pdo->lastInsertId();

    // Check if section titles are set and process sections and their content
    if (isset($_POST['section_title']) && is_array($_POST['section_title'])) {
        foreach ($_POST['section_title'] as $index => $section_title) {
            if (!empty($section_title)) {
                // Insert section into Section table
                $contents = $_POST['content'][$index];
                foreach ($contents as $content_index => $content) {
                    if (isset($content['image'])) {
                        $image = $_FILES['image_' . $index . '_' . $content_index];
                        if ($image['error'] == UPLOAD_ERR_OK) {
                            move_uploaded_file($image["tmp_name"], "uploads/" . basename($image["name"]));
                            $image_url = "uploads/" . basename($image["name"]);
                            $contents[$content_index]['image'] = $image_url;
                        }
                    }
                }
                $stmt = $pdo->prepare("INSERT INTO Section (document_id, section_name, content) VALUES (?, ?, ?)");
                $stmt->execute([$document_id, $section_title, json_encode($contents)]);
            }
        }
    }

    echo "Document saved successfully!";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Document</title>
    <style>
        .section { border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; }
        .drop-area { border: 2px dashed #ccc; padding: 20px; text-align: center; margin-bottom: 10px; }
        .hidden { display: none; }
    </style>
</head>
<body>

<h1>Create Document</h1>

<form action="" method="post" enctype="multipart/form-data">
    <label for="document_title">Document Title:</label>
    <input type="text" id="document_title" name="document_title" required><br><br>

    <label for="logo">Upload Logo:</label>
    <input type="file" id="logo" name="logo"><br><br>

    <div id="sections"></div>

    <button type="button" onclick="addSection()">Add a New Section</button><br><br>
    
    <input type="submit" value="Save Document">
</form>

<script>
let sectionCount = 0;
let contentCount = 0;

function addSection() {
    sectionCount++;
    contentCount = 0;

    const sectionDiv = document.createElement('div');
    sectionDiv.className = 'section';
    
    sectionDiv.innerHTML = `
        <h3>Section ${sectionCount}</h3>
        <label for="section_title_${sectionCount}">Section Title:</label>
        <input type="text" id="section_title_${sectionCount}" name="section_title[]"><br><br>

        <input type="hidden" name="image_url[]" id="image_url_${sectionCount}">

        <button type="button" onclick="addContent(${sectionCount}, 'text')">Text</button>
        <button type="button" onclick="addContent(${sectionCount}, 'blankline')">Blank Line</button>
        <button type="button" onclick="addContent(${sectionCount}, 'image')">Image</button>
        <button type="button" onclick="addContent(${sectionCount}, 'table')">Table</button>

        <div id="content_${sectionCount}"></div>
    `;

    document.getElementById('sections').appendChild(sectionDiv);
}

function allowDrop(event) {
    event.preventDefault();
}

function drop(event, sectionId) {
    event.preventDefault();
    
    const files = event.dataTransfer.files;
    
    if (files.length > 0) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('image_url_' + sectionId).value = e.target.result; // Store image URL (base64)
            alert('Image uploaded successfully!');
        };

        reader.readAsDataURL(files[0]); // Convert image to base64
    }
}

function addContent(sectionId, contentType) {
    const contentDiv = document.getElementById('content_' + sectionId);
    
    if (contentType === 'text') {
        const input = document.createElement('textarea');
        input.name = `content[${sectionId - 1}][${contentCount}][text]`;
        input.placeholder = "Enter text...";
        contentDiv.appendChild(input);
        
    } else if (contentType === 'blankline') {
        const blankLine = document.createElement('div');
        blankLine.innerHTML = "<hr>";
        contentDiv.appendChild(blankLine);
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `content[${sectionId - 1}][${contentCount}][blankline]`;
        contentDiv.appendChild(input);
        
    } else if (contentType === 'image') {
        const imageInput = document.createElement('input');
        imageInput.type = "file";
        imageInput.name = `image_${sectionId - 1}_${contentCount}`;
        contentDiv.appendChild(imageInput);
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `content[${sectionId - 1}][${contentCount}][image]`;
        contentDiv.appendChild(input);
        
    } else if (contentType === 'table') {
        const rows = prompt("Enter number of rows:");
        const cols = prompt("Enter number of columns:");
        
        if (rows && cols) {
            const tableDiv = document.createElement('table');
            for (let i = 0; i < rows; i++) {
                const tr = document.createElement('tr');
                for (let j = 0; j < cols; j++) {
                    const td = document.createElement('td');
                    const cellInput = document.createElement('input');
                    cellInput.type = "text";
                    cellInput.name = `content[${sectionId - 1}][${contentCount}][table][${i}][${j}]`;
                    td.appendChild(cellInput);
                    tr.appendChild(td);
                }
                tableDiv.appendChild(tr);
            }
            contentDiv.appendChild(tableDiv);
        }
    }

    contentCount ++;
}
</script>

</body>
</html>