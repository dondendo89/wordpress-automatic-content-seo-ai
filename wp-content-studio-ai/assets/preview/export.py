import os
from pathlib import Path
from io import BytesIO

import cairosvg
from PIL import Image

BASE = Path(__file__).resolve().parent
SRC = BASE
OUT_PNG = BASE / 'png'
OUT_THUMB = BASE / 'thumb'

SVG_FILES = [
    'preview-hero.svg',
    'preview-features.svg',
    'preview-single-post.svg',
    'preview-bulk.svg',
    'preview-woocommerce.svg',
    'preview-settings.svg',
]

TARGET_W, TARGET_H = 590, 300
THUMB_SIZE = 80

OUT_PNG.mkdir(parents=True, exist_ok=True)
OUT_THUMB.mkdir(parents=True, exist_ok=True)


def svg_to_png_bytes(svg_path: Path, width: int | None = None, height: int | None = None) -> bytes:
    with open(svg_path, 'rb') as f:
        svg_data = f.read()
    return cairosvg.svg2png(bytestring=svg_data, output_width=width, output_height=height)


def save_main_preview(png: Image.Image, name: str):
    # Resize to width 590 keeping aspect, then center-crop height to 300
    w = TARGET_W
    h_scaled = int(round(png.height * (w / png.width)))
    resized = png.resize((w, h_scaled), Image.LANCZOS)
    top = max(0, (h_scaled - TARGET_H) // 2)
    box = (0, top, w, top + TARGET_H)
    cropped = resized.crop(box)
    out_path = OUT_PNG / f"{name}.png"
    cropped.save(out_path, format='PNG', optimize=True)
    return out_path


def save_thumbnail(png: Image.Image, name: str):
    # Create square center-crop then downscale to 80x80
    side = min(png.width, png.height)
    left = (png.width - side) // 2
    top = (png.height - side) // 2
    square = png.crop((left, top, left + side, top + side))
    thumb = square.resize((THUMB_SIZE, THUMB_SIZE), Image.LANCZOS)
    out_path = OUT_THUMB / f"{name}.png"
    thumb.save(out_path, format='PNG', optimize=True)
    return out_path


def main():
    exported = []
    for svg in SVG_FILES:
        svg_path = SRC / svg
        name = svg_path.stem
        png_bytes = svg_to_png_bytes(svg_path, width=1280)  # render base raster first
        img = Image.open(BytesIO(png_bytes)).convert('RGBA')
        main_out = save_main_preview(img, name)
        thumb_out = save_thumbnail(img, name)
        exported.append((str(main_out), str(thumb_out)))
    print('Exported files:')
    for main_out, thumb_out in exported:
        print('-', main_out)
        print('  ', thumb_out)


if __name__ == '__main__':
    main()