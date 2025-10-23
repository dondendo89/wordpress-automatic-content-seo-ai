from pathlib import Path
from io import BytesIO

import cairosvg
from PIL import Image

BASE = Path(__file__).resolve().parent
SVG_DIR = BASE
PNG_DIR = BASE / 'png'
JPG_DIR = BASE / 'jpg'
SCREEN_DIR = BASE / 'screenshots'

SVG_FILES = [
    'preview-hero.svg',
    'preview-features.svg',
    'preview-single-post.svg',
    'preview-bulk.svg',
    'preview-woocommerce.svg',
    'preview-settings.svg',
]

JPG_DIR.mkdir(parents=True, exist_ok=True)
SCREEN_DIR.mkdir(parents=True, exist_ok=True)

TARGET_W, TARGET_H = 590, 300


def svg_to_image(svg_path: Path, width: int) -> Image.Image:
    data = svg_path.read_bytes()
    png_bytes = cairosvg.svg2png(bytestring=data, output_width=width)
    return Image.open(BytesIO(png_bytes)).convert('RGB')


def save_inline_jpg(img: Image.Image, name: str) -> Path:
    # Ensure exact 590x300 (center-crop after width fit)
    w = TARGET_W
    h_scaled = int(round(img.height * (w / img.width)))
    resized = img.resize((w, h_scaled), Image.LANCZOS)
    top = max(0, (h_scaled - TARGET_H) // 2)
    cropped = resized.crop((0, top, w, top + TARGET_H))
    out_path = JPG_DIR / f"{name}.jpg"
    cropped.save(out_path, format='JPEG', quality=85, optimize=True, progressive=True)
    return out_path


def save_screenshot_jpg(img: Image.Image, name: str) -> Path:
    # Large screenshot width ~1200px for gallery
    target_w = 1200
    h_scaled = int(round(img.height * (target_w / img.width)))
    resized = img.resize((target_w, h_scaled), Image.LANCZOS)
    out_path = SCREEN_DIR / f"{name}.jpg"
    resized.save(out_path, format='JPEG', quality=85, optimize=True, progressive=True)
    return out_path


def main():
    generated_inline = []
    generated_screens = []
    for svg in SVG_FILES:
        p = SVG_DIR / svg
        base_img = svg_to_image(p, width=1280)
        name = p.stem
        inline = save_inline_jpg(base_img, name)
        screen = save_screenshot_jpg(base_img, name)
        generated_inline.append(str(inline))
        generated_screens.append(str(screen))

    # Also create a canonical single inline image name for upload (use hero)
    hero_inline = JPG_DIR / 'inline-preview-590x300.jpg'
    hero_src = JPG_DIR / 'preview-hero.jpg'
    if hero_src.exists():
        hero_src.replace(hero_inline) if hero_inline.exists() else hero_src.rename(hero_inline)
        generated_inline.append(str(hero_inline))

    print('Inline JPGs (590x300):')
    for p in generated_inline:
        print('-', p)
    print('Screenshots JPG (~1200 width):')
    for p in generated_screens:
        print('-', p)


if __name__ == '__main__':
    main()