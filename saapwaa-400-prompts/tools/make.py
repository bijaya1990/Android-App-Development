"""Build the 400-prompt book.

scenes/*.txt format:
    ## <episode number> | <episode title>
    - <LOCATION> | <camera> | <scene title> | <other characters, comma separated, optional>
      A: <one main action>
      S: <Saapwaa's Hindi line>
      DIDI: <another character's Hindi line>

Every prompt is assembled from the fixed character lock, a location from
LOCATIONS, a camera from CAMERAS, any extra character locks, the action and the
dialogue. The build stops if a prompt reaches 900 characters.

    python3 tools/make.py
"""
import html
import pathlib
import re
import subprocess
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
LIMIT = 900

LOCK = ("Saapwaa, a cute cartoon snake: leaf-green body with darker green spots, cream-yellow belly "
        "and snout, big brown eyes, small fangs, red-and-white striped scarf, brown backpack, no sunglasses.")

CAMERAS = {
    "selfie": "Static selfie close-up, phone on a short bamboo stick held in his curled tail.",
    "walk": "Selfie walk with a slight handheld bob, phone on a bamboo stick held in his tail.",
    "prop": "Static wide shot from a phone propped on a rock.",
    "low": "Static low-angle shot from a phone set on the ground.",
}

LOCATIONS = {
    "CAVE": "Inside his small rock-cut cave home: flat grey stone bed, clay oil lamp in a wall niche, basalt-grey walls, warm amber light.",
    "KITCHEN": "Rock cave kitchen corner: three-stone stove, terracotta water pot, steel tiffin on a shelf, warm lamp light.",
    "BATH": "Rock cave bathing corner: a thin warm trickle from a rock crack into a stone basin, light steam.",
    "DOOR": "Arched rock doorway of his cave with a wall torch, stone lane outside, smoky maroon sky.",
    "LANE": "Narrow stone lane of cave homes with oil lamps by the doors, smoky maroon sky, drifting embers.",
    "ADDA": "Small rock tea stall with a stone counter, stacks of clay cups and a big copper kettle, torch light.",
    "WORKSHOP": "Cluttered rock workshop full of oil lamps, wicks, pulleys and odd brass gadgets, lamp light.",
    "SIGNAL": "Top of a tall lone rock above a cave neighbourhood, smoky maroon sky, wind.",
    "BENCH": "Carved stone bench under a rock overhang, the only shade on a hot rocky path.",
    "MAZE": "Maze of identical branching stone paths between rust-red boulders, smoky maroon sky.",
    "HAAT": "Small rocky market with woven mats, baskets and clay pots laid on the ground, torch light.",
    "BRIDGE": "Rope-and-plank bridge over a dry rocky ravine, smoky maroon sky, warm haze.",
    "WINDOW": "Small arched stone office window stacked with palm-leaf bundles, oil lamp on the sill.",
    "WATER": "Stone water spout with a long-handled ladle in a rocky lane, a few calm residents waiting.",
    "STEPS": "Long staircase cut into a rust-red cliff, smoky maroon sky.",
    "BARBER": "Tiny rock-cut barber nook with a cracked mirror, a wooden stool and combs on a shelf, lamp light.",
    "TAILOR": "Small tailor's alcove with bolts of faded cloth, a wooden sewing table and an oil lamp.",
    "VAID": "Herbal healer's rock room with hanging dried herbs, clay jars and a low wooden table, lamp light.",
    "GYM": "Open rocky yard with stone dumbbells, a clay wrestling pit and a wooden club, torch light.",
    "ROOF": "Flat rock rooftop above the cave homes with a washing line, smoky maroon sky, distant torches.",
    "SAUMYA": "Gentle green riverside with a huge old fig tree and a calm clear river, soft golden light.",
    "VIEW": "High rocky ridge overlooking a vast dark hazy river far below, glowing mist, solemn calm mood.",
    "COLD": "Frosty rocky outskirts with icicles and blue-white mist, cold blue light, breath visible.",
    "NIGHT": "Dark stone lane at night lit by a few dim oil lamps, deep shadows.",
    "CAVENIGHT": "Dark cave room lit only by one small flickering oil lamp.",
    "MUD": "Warm bubbling mud pool beside a rocky path, gentle steam, smoky maroon sky.",
    "CHOWK": "Small rocky village square with a stone platform and oil-lamp posts, smoky maroon sky.",
    "PATH": "Hot open rocky path with heat shimmer, rust-red boulders, smoky maroon sky.",
}

CHARS = {
    "DIDI": ("Kulhad Didi", "Kulhad Didi, a calm middle-aged woman in a mustard saree and sooty white apron"),
    "CHACHA": ("Tapu Chacha", "Tapu Chacha, an elderly thin bald man with white stubble, sleepy eyes, maroon kurta"),
    "LAL": ("Lalten Lal", "Lalten Lal, a lanky young man with sooty cheeks, brass goggles on his forehead, olive vest"),
    "KALLU": ("Kallu", "Kallu, a glossy black crow with a bent tail feather and a red thread on one ankle"),
    "MUNSHI": ("Munshi", "Munshi, a plump clerk with round wire spectacles, cream kurta, reed pen behind his ear"),
    "NANU": ("Nanu", "Nanu, an elderly man with a neat white beard, indigo kurta, a wooden oar as a staff"),
    "PAPPU": ("Pehlwan Pappu", "Pehlwan Pappu, a big friendly wrestler with a curled moustache and a towel on his shoulder"),
    "MAUSI": ("Gappu Mausi", "Gappu Mausi, a chatty plump old woman with a silver bun, green saree and a woven basket"),
    "USTAD": ("Ustad", "Ustad, a barber with a huge moustache, checked lungi and scissors in hand"),
    "MASTER": ("Masterji", "Masterji, a tailor with thick glasses, a pencil moustache and a measuring tape on his neck"),
    "VAIDJI": ("Vaidji", "Vaidji, an old herbal healer with round glasses and a white dhoti"),
    "BABLU": ("Bablu", "Bablu, a skinny young snack seller in a cloth cap with a tray hung from his neck"),
    "RES": ("Resident", "a calm resident in faded cotton clothes"),
}

HEAD_RE = re.compile(r"^## (\d+) \| (.+)$")
SCENE_RE = re.compile(r"^- (\w+) \| (\w+) \| ([^|]+?)(?: \| (.+))?$")


def parse():
    episodes = []
    for path in sorted((ROOT / "scenes").glob("*.txt")):
        ep = None
        sc = None
        for n, raw in enumerate(path.read_text(encoding="utf-8").splitlines(), 1):
            line = raw.strip()
            if not line or line.startswith("#!"):
                continue
            where = f"{path.name}:{n}"
            if m := HEAD_RE.match(line):
                ep = {"num": int(m.group(1)), "title": m.group(2).strip(), "scenes": []}
                episodes.append(ep)
            elif m := SCENE_RE.match(line):
                chars = [c.strip() for c in (m.group(4) or "").split(",") if c.strip()]
                sc = {"loc": m.group(1), "cam": m.group(2), "title": m.group(3).strip(),
                      "chars": chars, "action": "", "lines": [], "where": where}
                ep["scenes"].append(sc)
            elif line.startswith("A:"):
                sc["action"] = line[2:].strip()
            elif ":" in line:
                who, _, text = line.partition(":")
                sc["lines"].append((who.strip(), text.strip()))
            else:
                sys.exit(f"{where}: cannot read line: {line}")
    return episodes


def build_prompt(sc):
    parts = ["Vertical 9:16, cinematic 3D cartoon, 10-second vlog clip.", LOCK, LOCATIONS[sc["loc"]]]
    for c in sc["chars"]:
        parts.append(CHARS[c][1] + " is with him.")
    parts.append(CAMERAS[sc["cam"]])
    parts.append(sc["action"])
    for who, text in sc["lines"]:
        name = "Saapwaa" if who == "S" else CHARS[who][0]
        parts.append(f'{name} says in Hindi: "{text}"')
    parts.append("No text, subtitles, logos or watermarks.")
    return " ".join(p for p in parts if p)


def check(episodes):
    errors, seen_titles = [], set()
    for ep in episodes:
        for sc in ep["scenes"]:
            w = sc["where"]
            if sc["loc"] not in LOCATIONS:
                errors.append(f"{w}: unknown location {sc['loc']}")
                continue
            if sc["cam"] not in CAMERAS:
                errors.append(f"{w}: unknown camera {sc['cam']}")
                continue
            bad = [c for c in sc["chars"] + [x for x, _ in sc["lines"] if x != "S"] if c not in CHARS]
            if bad:
                errors.append(f"{w}: unknown character {bad}")
                continue
            for who, _ in sc["lines"]:
                if who != "S" and who not in sc["chars"]:
                    errors.append(f"{w}: {who} speaks but is not listed in the scene")
            if not sc["action"]:
                errors.append(f"{w}: no action")
            if not sc["lines"]:
                errors.append(f"{w}: no dialogue")
            if '"' in "".join(t for _, t in sc["lines"]):
                errors.append(f"{w}: dialogue contains a double quote")
            sc["prompt"] = build_prompt(sc)
            if len(sc["prompt"]) >= LIMIT:
                errors.append(f"{w}: prompt is {len(sc['prompt'])} chars")
            key = sc["title"].lower()
            if key in seen_titles:
                errors.append(f"{w}: duplicate scene title {sc['title']}")
            seen_titles.add(key)
    return errors


def html_book(episodes, total):
    css = (ROOT / "tools" / "book.css").read_text(encoding="utf-8")
    out = [f"<!doctype html><html lang='en'><head><meta charset='utf-8'><title>Saapwaa Narak Vlogs — 400 Prompts</title>"
           f"<style>{css}</style></head><body>"]
    out.append((ROOT / "tools" / "intro.html").read_text(encoding="utf-8"))
    out.append("<h1 class='toc-h'>Episodes</h1><div class='toc'>")
    n = 0
    for ep in episodes:
        first = n + 1
        n += len(ep["scenes"])
        out.append(f"<div class='toc-row'><span>Episode {ep['num']} · {html.escape(ep['title'])}</span>"
                   f"<span class='dots'></span><span>Prompts {first}–{n}</span></div>")
    out.append("</div>")
    n = 0
    for ep in episodes:
        out.append(f"<h2 class='ep'>Episode {ep['num']} · {html.escape(ep['title'])}</h2>")
        for i, sc in enumerate(ep["scenes"], 1):
            n += 1
            out.append(
                f"<div class='card'><div class='head'><span class='num'>#{n:03d}</span>"
                f"<span class='t'>E{ep['num']:02d}·S{i:02d} — {html.escape(sc['title'])}</span>"
                f"<span class='len'>{len(sc['prompt'])} chars</span></div>"
                f"<pre>{html.escape(sc['prompt'])}</pre></div>")
    out.append("</body></html>")
    return "".join(out)


def main():
    episodes = parse()
    errors = check(episodes)
    total = sum(len(e["scenes"]) for e in episodes)
    lengths = [len(s["prompt"]) for e in episodes for s in e["scenes"] if "prompt" in s]
    print(f"{len(episodes)} episodes, {total} scenes, prompt length {min(lengths)}–{max(lengths)}")
    if errors:
        print("\n".join(errors))
        sys.exit(1)

    dist = ROOT / "dist"
    txt, n = [], 0
    for ep in episodes:
        txt.append(f"################ EPISODE {ep['num']} — {ep['title']} ################\n")
        for i, sc in enumerate(ep["scenes"], 1):
            n += 1
            txt.append(f"#{n:03d}  E{ep['num']:02d}-S{i:02d}  {sc['title']}\n{sc['prompt']}\n")
    (dist / "saapwaa-narak-400-prompts.txt").write_text("\n".join(txt), encoding="utf-8")

    build = ROOT / "build"
    build.mkdir(exist_ok=True)
    (build / "book.html").write_text(html_book(episodes, total), encoding="utf-8")
    pdf = dist / "saapwaa-narak-400-prompts.pdf"
    render = lambda src, dst, *f: subprocess.run(["node", str(ROOT / "tools" / "render.js"), str(src), str(dst), *f], check=True)
    render(build / "book.html", build / "body.pdf", "--label", "Saapwaa’s Narak Vlogs · 400 Prompts")
    render(ROOT / "tools" / "cover.html", build / "cover.pdf", "--no-footer")
    from pypdf import PdfReader, PdfWriter
    w = PdfWriter()
    for src in (build / "cover.pdf", build / "body.pdf"):
        for page in PdfReader(str(src)).pages:
            w.add_page(page)
    w.add_metadata({"/Title": "Saapwaa’s Narak Vlogs — 400 Video Prompts"})
    with open(pdf, "wb") as fh:
        w.write(fh)
    print(f"Wrote {pdf.relative_to(ROOT)} — {len(PdfReader(str(pdf)).pages)} pages")


if __name__ == "__main__":
    main()
