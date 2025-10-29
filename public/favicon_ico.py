# Generate a Windows .ico favicon from the same design (magenta sawtooth on dark background)
from PIL import Image, ImageDraw

def draw_icon(size):
    img = Image.new("RGBA", (size, size), "#0a0a0a")  # dark background
    draw = ImageDraw.Draw(img, "RGBA")
    
    # Original 64x64 points scaled up to current size
    scale = size / 64.0
    pts = [(8,40),(22,26),(36,40),(50,26),(58,32)]
    pts = [(int(x*scale), int(y*scale)) for x,y in pts]
    
    stroke = int(8*scale)  # original stroke width 8 at 64px
    color = (255, 42, 123, 255)  # #FF2A7B
    
    # Draw polyline segments
    for i in range(len(pts)-1):
        draw.line([pts[i], pts[i+1]], fill=color, width=stroke)
    
    # Simulate round caps and joins by drawing circles at vertices and endpoints
    r = stroke//2
    for (x,y) in pts:
        draw.ellipse((x-r, y-r, x+r, y+r), fill=color)
    
    return img

# Create a high-res base (256x256) and save ICO with multiple common sizes
base = draw_icon(256)
ico_path = "/mnt/data/russell-klein-favicon.ico"
base.save(ico_path, format="ICO", sizes=[(16,16),(32,32),(48,48),(64,64),(128,128),(256,256)])

ico_path
