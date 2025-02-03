from flask import Flask, send_file, jsonify
import fitz  # PyMuPDF
import json
import mysql.connector
from PIL import Image
import aspose.pdf as ap
import os

app = Flask(__name__)

def fetch_data_from_db(document_id):
    conn = mysql.connector.connect(
        host="localhost", user="root", password="", database="test-database"
    )
    cursor = conn.cursor(dictionary=True)

    cursor.execute(
        "SELECT document_id, document_title, logo FROM document WHERE document_id = %s",
        (document_id,),
    )
    document = cursor.fetchone()

    cursor.execute("SELECT * FROM section WHERE document_id = %s", (document_id,))
    sections = cursor.fetchall()

    conn.close()
    return document, sections

def wrap_text(text, max_width, font_size):
    words = text.split()
    lines = []
    line = ""

    for word in words:
        if fitz.get_text_length(line + " " + word, fontsize=font_size) > max_width:
            lines.append(line)
            line = word
        else:
            line += " " + word if line else word

    if line:
        lines.append(line)

    return lines

def get_image_dimensions(image_path, max_width):
    with Image.open(image_path) as img:
        width, height = img.size
        scale_factor = max_width / width
        return max_width, height * scale_factor

def generate_pdf(filename, document_id):
    document, sections = fetch_data_from_db(document_id)
    doc = fitz.open()
    y_position = 50
    max_y = 780  # Leave space for footer/margins
    line_height = 15  # Default line spacing
    image_height = 50
    text_width = 495  # Allow some margins
    image_max_width = 495

    bookmarks = []  # To store bookmarks for sections
    page = None  # Define the variable in the enclosing scope
    link_rects = []
    page_numbers = []

    def add_new_page():
        nonlocal page, y_position
        page = doc.new_page(width=595, height=842)  # A4 size in points (595x842)
        y_position = 50

    add_new_page()

    if document:
        if document["logo"]:
            try:
                if y_position + image_height > max_y:
                    add_new_page()
                rect = fitz.Rect(50, y_position, 100, y_position + image_height)
                alt_text = "Logo"
                page.add_freetext_annot(rect, alt_text, fontsize=12)
                page.insert_image(rect, filename=document["logo"])
                y_position += image_height + 10
            except Exception as e:
                page.insert_text(
                    (50, y_position), f"Logo not found: {document['logo']}"
                )
                y_position += 20
        title_text = document['document_title']
        global title_text1
        title_text1 = title_text
        title_width = fitz.get_text_length(title_text, fontsize=16, fontname="helvetica-Bold")
        title_x = (595 - title_width) / 2  # Center align
        page.insert_text((title_x, y_position), title_text, fontsize=16, fontname="helvetica-Bold")
        y_position += 30

    for index, section in enumerate(sections, start=1):
        if y_position + line_height > max_y:
            add_new_page()
        section_name = f"{index}. {section['section_name']}"
        page.insert_text((50, y_position), section_name, fontsize=14)
        link_rects.append(fitz.Rect(50, y_position - 12, 495, y_position + 2))
        y_position += 50

    for section in sections:
        if y_position + line_height > max_y:
            add_new_page()

        section_name = section["section_name"]
        content = json.loads(section["content"])
        page.insert_text((50, y_position), section_name, fontsize=14)
        bookmarks.append((1, section_name, page.number + 1))  # Add bookmark (level 1)
        page_numbers.append([page.number, y_position])
        y_position += 20

        for item in content:
            for key, value in item.items():
                if key == "text":
                    wrapped_text = wrap_text(value, text_width, 12)
                    for line in wrapped_text:
                        if y_position + line_height > max_y:
                            add_new_page()
                        page.insert_text((50, y_position), line, fontsize=12)
                        y_position += line_height
                elif key == "blankline":
                    y_position += line_height
                elif key == "image":
                    try:
                        img_width, img_height = get_image_dimensions(value, image_max_width)
                        if y_position + img_height > max_y:
                            add_new_page()
                        rect = fitz.Rect(50, y_position, 50 + img_width, y_position + img_height)
                        alt_text = value
                        page.add_freetext_annot(rect, alt_text, fontsize=12)
                        page.insert_image(rect, filename=value)
                        y_position += img_height + 10
                    except Exception as e:
                        page.insert_text((50, y_position), f"Image not found: {value}")
                        y_position += 20
                elif key == "table":
                    table_height = len(value) * 20  # Estimate table height
                    if y_position + table_height > max_y:
                        add_new_page()
                    for i, row in enumerate(value):
                        for j, cell in enumerate(row):
                            x, y = 50 + j * 100, y_position + i * 20
                            page.insert_text((x + 5, y + 5), str(cell), fontsize=10)
                            rect = fitz.Rect(x, y - 10, x + 100, y + 10)
                            page.draw_rect(rect, color=(0, 0, 0), width=1)

                    y_position += table_height + 10  # Move below the table
    print(link_rects)
    print(page_numbers)
    for index, section in enumerate(sections):
        print(index)
        link_rect = link_rects[index]
        page_num, pos_y = page_numbers[index]
        doc[0].insert_link({"kind": fitz.LINK_GOTO, "from": link_rect, "page": page_num, "to": fitz.Point(50, pos_y)})

    doc.save(filename)
    doc.close()
    print(f"PDF '{filename}' generated successfully.")
    return bookmarks

def add_bookmarks_and_metadata(pdf_path, bookmarks):
    doc = fitz.open(pdf_path)

    # Add metadata
    doc.set_metadata({
        "title": "PDF/A-1a Document",
        "author": "John Doe",
        "subject": "Example of PDF/A-1a Compliance",
        "keywords": "PDF/A, Compliance, Python",
    })

    # Add bookmarks (Table of Contents)
    toc = doc.get_toc()  # Get current Table of Contents (TOC)
    for bookmark in bookmarks:
        toc.append(bookmark)  # [Level, Title, Page number]
    doc.set_toc(toc)  # Update TOC

    # Add MarkInfo dictionary
    doc.pdf_markinfo = {"Marked": True}

    # Save as a new file
    updated_pdf_path = "output_with_bookmarks.pdf"
    doc.save(updated_pdf_path)
    doc.close()

    print(f"Bookmarks & metadata added. Saved as {updated_pdf_path}")
    return updated_pdf_path

def remove_text(input_pdf, output_pdf, search_text):
    # Open the PDF
    doc = fitz.open(input_pdf)
    
    # Iterate through each page
    for page in doc:
        # Search for the text
        text_instances = page.search_for(search_text)
        
        # Apply redaction to found text
        for inst in text_instances:
            rect = fitz.Rect(inst)
            page.add_redact_annot(rect, fill=(1, 1, 1))  # White background
        
        # Execute the redaction
        page.apply_redactions()
    
    # Save the modified PDF
    doc.save(output_pdf)
    doc.close()

@app.route('/generate_pdf/<int:document_id>', methods=['GET'])
def generate_pdf_api(document_id):
    temp_pdf_name = "out.pdf"
    try:
        bookmarks = generate_pdf(temp_pdf_name, document_id)
        updated_pdf_path = add_bookmarks_and_metadata(temp_pdf_name, bookmarks)
        # Convert to PDF/A-1a
        doc = ap.Document(updated_pdf_path)
        options = ap.PdfFormatConversionOptions(ap.PdfFormat.PDF_A_1A)

        if doc.convert(options):
            final_pdf_path = f"{title_text1}.pdf"
            doc.save(temp_pdf_name)
            remove_text(temp_pdf_name, final_pdf_path, "Evaluation Only. Created with Aspose.PDF. Copyright 2002-2024 Aspose Pty Ltd.")
            os.remove(temp_pdf_name)
            os.remove(updated_pdf_path)
            response = send_file(final_pdf_path, as_attachment=True)
            return response
        else:
            return jsonify({"error": "PDF/A conversion failed."}), 500
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True)
