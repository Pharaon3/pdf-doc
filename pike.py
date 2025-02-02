import os
import fitz  # PyMuPDF
import pikepdf
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
import subprocess

# File paths
pdf_file = "structured.pdf"
pdfa_file = "structured_pdfa.pdf"
icc_profile = "sRGB.icc"  # Ensure you have this ICC profile
img_path = "example.jpg"
updated_pdf_file = "updated_structured1.pdf"

# Register and embed fonts
pdfmetrics.registerFont(TTFont("Helvetica", "Helvetica.ttf"))

PAGE_WIDTH, PAGE_HEIGHT = A4  # A4 size (595.27 x 841.89 points)

# 1️⃣ **Create a Structured PDF with XMP Metadata & MarkInfo**
def create_structured_pdf(pdf_path):
    c = canvas.Canvas(pdf_path, pagesize=A4)
    c.setFont("Helvetica", 12)

    # **🔹 Add Metadata (XMP)**
    c.setTitle("Sample PDF/A-1a Document")
    c.setAuthor("John Doe")
    c.setSubject("Example of PDF/A-1a Compliance")
    c.setKeywords("PDF/A, Compliance, Python")

    # **🔹 Add text content (first page)**
    c.drawString(100, 750, "PDF/A-1a Compliant Document - Page 1")
    c.drawString(100, 730, "This is the first sentence of the first page.")
    c.drawString(100, 710, "This is the second sentence of the first page.")
    c.drawString(100, 690, "This is the third sentence of the first page.")

    # **🔹 Embed an image on the first page with alternative text**
    img = ImageReader(img_path)
    img_x, img_y = 100, 600  # X and Y position of the image (ReportLab)
    img_width, img_height = 200, 100  # Image dimensions
    # c.drawImage(img, img_x, img_y, width=img_width, height=img_height)

    # **🔹 Add a new page**
    c.showPage()

    # **🔹 Add text content (second page)**
    c.drawString(100, 750, "PDF/A-1a Compliant Document - Page 2")
    c.drawString(100, 730, "This is the first sentence of the second page.")
    c.drawString(100, 710, "This is the second sentence of the second page.")
    c.drawString(100, 690, "This is the third sentence of the second page.")

    # **🔹 Embed an image on the second page with alternative text**
    c.drawImage(img, 100, 500, width=200, height=100)


    # **🔹 Add a new page**
    c.showPage()

    # **🔹 Add text content (second page)**
    c.drawString(100, 750, "PDF/A-1a Compliant Document - Page 3")
    c.drawString(100, 730, "This is the first sentence of the second page.")
    c.drawString(100, 710, "This is the second sentence of the second page.")
    c.drawString(100, 690, "This is the third sentence of the second page.")

    # **🔹 Embed an image on the second page with alternative text**
    c.drawImage(img, 100, 500, width=200, height=100)

    # **🔹 Add a new page**
    c.showPage()

    # **🔹 Add text content (second page)**
    c.drawString(100, 750, "PDF/A-1a Compliant Document - Page 4")
    c.drawString(100, 730, "This is the first sentence of the second page.")
    c.drawString(100, 710, "This is the second sentence of the second page.")
    c.drawString(100, 690, "This is the third sentence of the second page.")

    # **🔹 Embed an image on the second page with alternative text**
    c.drawImage(img, 100, 500, width=200, height=100)
    c.save()
    
    print(f"📄 Structured PDF saved as {pdf_path}")
    doc = fitz.open(pdf_path)
    page = doc[0]

    # Create an annotation for the image with Alt text (tooltip)
    converted_y1 = PAGE_HEIGHT - (img_y + img_height)  # Top-Left corner
    converted_y2 = PAGE_HEIGHT - img_y  # Bottom-Left corner
    rect = fitz.Rect(img_x, converted_y1, img_x + img_width, converted_y2)  # The coordinates of the image
    alt_text = "Alternative Text: This is an example image."
    page.add_freetext_annot(rect, alt_text, fontsize=12)
    page.insert_image(rect, filename=img_path)
    # Save the updated PDF with alternative text (tooltip) for the image
    doc.save(updated_pdf_file)
    doc.close()
    print(f"🔖 Alternative text (tooltip) added. Saved as {updated_pdf_file}")

create_structured_pdf(pdf_file)


# 2️⃣ **Add Bookmarks, Metadata & MarkInfo Dictionary**
def add_bookmarks_and_metadata(pdf_path):
    doc = fitz.open(pdf_path)

    # **🔹 Add metadata**
    doc.set_metadata({
        "title": "PDF/A-1a Document",
        "author": "John Doe",
        "subject": "Example of PDF/A-1a Compliance",
        "keywords": "PDF/A, Compliance, Python",
    })

    # **🔹 Add a bookmark (outline)**
   #  page = doc[0]
   #  doc.outline = [(1, "Main Section", page.number, 0)]

    toc = doc.get_toc()  # Get current Table of Contents (TOC)
    toc.append([1, "Section 1", 1])  # [Level, Title, Page number]
    toc.append([2, "Section 1-1", 2])  # [Level, Title, Page number]
    toc.append([2, "Section 1-2", 3])  # [Level, Title, Page number]
    toc.append([1, "Section 2", 4])  # [Level, Title, Page number]
    doc.set_toc(toc)  # Update TOC

    # **🔹 Add MarkInfo dictionary**
    doc.pdf_markinfo = {"Marked": True}

    # Save as a new file
    updated_pdf_path = "updated_" + pdf_path
    doc.save(updated_pdf_path)
    doc.close()

    print(f"🔖 Bookmarks & metadata added. Saved as {updated_pdf_path}")
    return updated_pdf_path

updated_pdf = add_bookmarks_and_metadata(updated_pdf_file)

# 3️⃣ **Convert to PDF/A-1a with ICC Profile (Ghostscript Alternative)**
def convert_to_pdfa(input_pdf, output_pdf, icc_profile):
    with pikepdf.open(input_pdf, allow_overwriting_input=True) as pdf:
        # Read the ICC profile
        with open(icc_profile, "rb") as icc_file:
            icc_data = icc_file.read()

        # Create an ICC profile stream with the required "N" key (Number of color components)
        icc_stream = pikepdf.Stream(pdf, icc_data)
        icc_stream["/N"] = 3  # Set to 3 for RGB

        # Ensure OutputIntent dictionary is correctly added
        output_intent = pikepdf.Dictionary({
            "/Type": "/OutputIntent",
            "/S": "/GTS_PDFA1",  # Fix: Explicitly add /S key
            "/OutputConditionIdentifier": "sRGB IEC61966-2.1",
            "/DestOutputProfile": icc_stream
        })
        # Attach output intent to PDF root
        pdf.Root["/OutputIntents"] = [output_intent]

        # Save the updated PDF
        pdf.save(output_pdf, linearize=True)

    print(f"✅ Successfully converted {input_pdf} to PDF/A-1a: {output_pdf}")

# Example usage
convert_to_pdfa("updated_structured.pdf", "structured_pdfa.pdf", "sRGB.icc")

print("🚀 PDF/A-1a creation complete!")
