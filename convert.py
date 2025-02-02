import aspose.pdf as ap
import os

input_pdf = "updated_updated_structured1.pdf"
output_pdf = "output_pdfa.pdf"

# Check if file exists
if not os.path.exists(input_pdf):
    print(f"Error: The file '{input_pdf}' was not found.")
    exit()

# Load the document
doc = ap.Document(input_pdf)

# Ensure the document is not empty
if doc is None:
    print("Error: Failed to load the PDF document.")
    exit()

# Convert to PDF/A-1a
options = ap.PdfFormatConversionOptions(ap.PdfFormat.PDF_A_1A)

# Try conversion
if doc.convert(options):
    doc.save(output_pdf)
    print(f"PDF successfully converted to PDF/A-1a: {output_pdf}")
else:
    print("Error: PDF/A conversion failed.")
