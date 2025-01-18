-- Create Document Table
CREATE TABLE Document (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    document_title VARCHAR(255) NOT NULL,
    logo VARCHAR(255) NOT NULL
);

-- Create Section Table
CREATE TABLE Section (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    section_name VARCHAR(255) NOT NULL,
    content TEXT,
    FOREIGN KEY (document_id) REFERENCES Document(document_id) ON DELETE CASCADE
);

-- Create Content Table
CREATE TABLE Content (
    content_id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    content_type ENUM('text', 'blank', 'table', 'image') NOT NULL,
    content_detail TEXT,
    FOREIGN KEY (section_id) REFERENCES Section(section_id) ON DELETE CASCADE
);

-- Create Text Table
CREATE TABLE TextContent (
    text_id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    text_detail TEXT NOT NULL,
    FOREIGN KEY (section_id) REFERENCES Section(section_id) ON DELETE CASCADE
);

-- Create Image Table
CREATE TABLE ImageContent (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    FOREIGN KEY (section_id) REFERENCES Section(section_id) ON DELETE CASCADE
);

-- Create Table Table (for structured data)
CREATE TABLE TableContent (
    table_id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    table_data TEXT NOT NULL, -- You can store JSON or serialized data here
    FOREIGN KEY (section_id) REFERENCES Section(section_id) ON DELETE CASCADE
);