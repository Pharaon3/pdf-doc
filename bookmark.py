import fitz  # PyMuPDF
import aspose.pdf as ap
import os

def add_tooltips_and_bookmarks(pdf_path, output_path):
    doc = fitz.open(pdf_path)
    bookmarks = []

    for page_num, page in enumerate(doc):
        image_list = page.get_images(full=True)

        # Add tooltip to each image
        for img_index, img in enumerate(image_list):
            xref = img[0]
            img_info = doc.extract_image(xref)
            img_width = img_info["width"]
            img_height = img_info["height"]
            
            # Get image position
            for img_rect in page.get_image_rects(xref):
                rect = fitz.Rect(img_rect.x0, img_rect.y0, img_rect.x1, img_rect.y1)
                alt_text = f"Image {img_index + 1}"
                page.add_freetext_annot(rect, alt_text, fontsize=12)

        # Find section titles (assumed to be font size 18)
        for text in page.get_text("dict")["blocks"]:
            if "lines" in text:
                for line in text["lines"]:
                    for span in line["spans"]:
                        if span["size"] == 18:  # Assuming section titles are font size 18
                            if "_BM_" in span["text"]:
                                x, y = span["origin"]
                                bookmarks.append([1, span["text"].replace("_BM_", ""), page_num + 1, {"kind": fitz.LINK_GOTO, "to": fitz.Point(x, y)}])
                            if "_SBM_" in span["text"]:
                                x, y = span["origin"]
                                bookmarks.append([2, span["text"].replace("_SBM_", ""), page_num + 1, {"kind": fitz.LINK_GOTO, "to": fitz.Point(x, y)}])
    # Update TOC with bookmarks
    if bookmarks:
        toc = doc.get_toc()
        toc.extend(bookmarks)
        doc.set_toc(toc)

    # Mark the PDF as tagged
    doc.pdf_markinfo = {"Marked": True}

    # Save the modified PDF
    doc.save(output_path)
    doc.close()

# Usage Example
input_pdf = "./docs/temp.pdf"
output_pdf = "output.pdf"
add_tooltips_and_bookmarks(input_pdf, output_pdf)

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

doc = ap.Document("output.pdf")
options = ap.PdfFormatConversionOptions(ap.PdfFormat.PDF_A_1A)
temp_pdf_name = "temp.pdf"
if doc.convert(options):
    final_pdf_path = "out.pdf"
    doc.save(temp_pdf_name)
    remove_text(temp_pdf_name, final_pdf_path, "Evaluation Only. Created with Aspose.PDF. Copyright 2002-2024 Aspose Pty Ltd.")
    os.remove(input_pdf)
    os.remove(temp_pdf_name)
    os.remove("output.pdf")