import PyPDF2
import os

pdf_files = ["Escavador Referencia API V1 Precos.pdf", "Escavador Referencia API V2 Precos.pdf"]

with open("pdf_urls.txt", "w", encoding="utf-8") as out:
    for idx, filename in enumerate(pdf_files):
        if not os.path.exists(filename):
            out.write(f"File not found: {filename}\n")
            continue
            
        out.write(f"\n--- {filename} ---\n")
        try:
            reader = PyPDF2.PdfReader(filename)
            for page in reader.pages:
                text = page.extract_text()
                if text:
                    out.write(text + "\n")
                
                # Extract annotations/URIs if any exist as clickable links
                if "/Annots" in page:
                    for annot in page["/Annots"]:
                        if hasattr(annot, "get_object"):
                            annot_obj = annot.get_object()
                            if "/A" in annot_obj and "/URI" in annot_obj["/A"]:
                                out.write(f"[LINK] {annot_obj['/A']['/URI']}\n")
        except Exception as e:
            out.write(f"Error reading {filename}: {str(e)}\n")
