from fastapi import APIRouter, Query, HTTPException, Response
from urllib.parse import urlparse
from PIL import Image
import requests
import io
from lxml import etree

router = APIRouter()

ALLOWED_IMAGE_TYPES = {
    "image/jpeg",
    "image/jpg",
    "image/png",
    "image/gif",
    "image/webp",
    "image/svg+xml"
}

def is_valid_url(url: str) -> bool:
    try:
        result = urlparse(url)
        return result.scheme in {"http", "https"} and bool(result.netloc)
    except:
        return False

def rotate_svg(svg_content: bytes, angle: float = 10) -> bytes:
    try:
        parser = etree.XMLParser(remove_blank_text=True)
        root = etree.fromstring(svg_content, parser)

        # Extract viewBox center if available
        viewBox = root.attrib.get("viewBox")
        if viewBox:
            parts = list(map(float, viewBox.strip().split()))
            if len(parts) == 4:
                _, _, width, height = parts
                center_x = width / 2
                center_y = height / 2
            else:
                center_x = center_y = 0
        else:
            center_x = center_y = 0  # fallback

        # Create <g> with rotate transform
        group = etree.Element("g")
        group.attrib["transform"] = f"rotate({angle}, {center_x}, {center_y})"

        for child in list(root):
            root.remove(child)
            group.append(child)

        root.append(group)

        return etree.tostring(root, pretty_print=False, xml_declaration=True, encoding="UTF-8")
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Failed to rotate SVG: {str(e)}")


@router.get("/imageproxy")
def image_proxy(url: str = Query(..., description="Direct URL to an image file")):
    if not is_valid_url(url):
        raise HTTPException(status_code=400, detail="Invalid URL")

    try:
        response = requests.get(url, allow_redirects=False, timeout=5)
    except requests.RequestException as e:
        raise HTTPException(status_code=400, detail=f"Request failed: {str(e)}")

    content_type = response.headers.get("Content-Type", "").split(";")[0].strip().lower()
    if content_type not in ALLOWED_IMAGE_TYPES:
        raise HTTPException(status_code=400, detail=f"Unsupported content type: {content_type}")

    image_data = response.content

    if content_type == "image/svg+xml":
        rotated_svg = rotate_svg(image_data, angle=10)
        return Response(content=rotated_svg, media_type="image/svg+xml")

    try:
        image = Image.open(io.BytesIO(image_data))
        tweaked_image = image.rotate(10, expand=True)
        output_buffer = io.BytesIO()
        format = image.format or "PNG"
        tweaked_image.save(output_buffer, format=format)
        output_buffer.seek(0)
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Failed to process image: {str(e)}")

    return Response(content=output_buffer.read(), media_type=content_type)
