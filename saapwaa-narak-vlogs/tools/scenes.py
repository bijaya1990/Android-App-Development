"""Parse and check production scenes in the manuscript.

A scene starts with a level-3 heading "### EPISODE <n> — SCENE <n>" and must
contain the fields below, in this exact order. Prompts and dialogue live in
fenced blocks tagged ~~~image, ~~~video and ~~~dialogue.

Run directly to print a report:  python3 tools/scenes.py
Exit code is 1 if any scene has an error.
"""
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
MANUSCRIPT = ROOT / "manuscript"

VIDEO_LIMIT = 900          # hard limit from the brief
VIDEO_TARGET = (450, 780)  # comfortable band; outside it is a warning only

FIELDS = [
    "Scene title:",
    "Story purpose:",
    "Continuity:",
    "IMAGE PROMPT:",
    "VIDEO PROMPT — 10 SECONDS:",
    "DIALOGUE:",
    "VISUAL COMEDY:",
    "ENDING / NEXT-SCENE LINK:",
    "SOURCE STATUS:",
]

# Words every standalone prompt must carry so Saapwaa never drifts.
LOCK_TERMS = [
    "leaf-green",
    "darker green spots",
    "cream-yellow belly",
    "brown eyes",
    "red-and-white striped scarf",
    "brown backpack",
]

HEAD_RE = re.compile(r"^### EPISODE (\d+) — SCENE (\d+)\s*$", re.M)
FENCE_RE = re.compile(r"^~~~(image|video|dialogue)\n(.*?)\n~~~\s*$", re.M | re.S)
TITLE_RE = re.compile(r"\*\*Scene title:\*\*\s*(.+)")
STATUS_RE = re.compile(r"\*\*SOURCE STATUS:\*\*\s*(.+)")
DEVANAGARI_WORD = re.compile(r"[ऀ-ॿ]+")


def load_scenes():
    scenes = []
    for path in sorted(MANUSCRIPT.glob("*.md")):
        text = path.read_text(encoding="utf-8")
        heads = list(HEAD_RE.finditer(text))
        for i, m in enumerate(heads):
            end = heads[i + 1].start() if i + 1 < len(heads) else len(text)
            # A scene also ends at the next level-1/level-2 heading.
            nxt = re.search(r"^#{1,2} ", text[m.end():end], re.M)
            if nxt:
                end = m.end() + nxt.start()
            body = text[m.end():end]
            blocks = {k: v.strip() for k, v in FENCE_RE.findall(body)}
            title = TITLE_RE.search(body)
            status = STATUS_RE.search(body)
            scenes.append({
                "file": path.name,
                "episode": int(m.group(1)),
                "scene": int(m.group(2)),
                "body": body,
                "title": title.group(1).strip() if title else "",
                "status": status.group(1).strip() if status else "",
                "image": blocks.get("image", ""),
                "video": blocks.get("video", ""),
                "dialogue": blocks.get("dialogue", ""),
            })
    return scenes


def dialogue_lines(dialogue):
    """Return (speaker, line) pairs. '(silent)' means no dialogue."""
    out = []
    for raw in dialogue.splitlines():
        raw = raw.strip()
        if not raw or raw.startswith("("):
            continue
        speaker, _, line = raw.partition(":")
        out.append((speaker.strip(), line.strip()))
    return out


def check(scene, seen):
    errors, warnings = [], []
    body = scene["body"]

    # Field order.
    pos = -1
    for f in FIELDS:
        idx = body.find(f"**{f}**")
        if idx == -1:
            errors.append(f"missing field '{f}'")
        elif idx < pos:
            errors.append(f"field '{f}' is out of order")
        else:
            pos = idx

    key = (scene["episode"], scene["scene"])
    if key in seen:
        errors.append("duplicate scene number")
    seen.add(key)

    for kind in ("image", "video", "dialogue"):
        if not scene[kind]:
            errors.append(f"missing ~~~{kind} block")

    video = scene["video"]
    n = len(video)
    if n >= VIDEO_LIMIT:
        errors.append(f"video prompt is {n} chars (limit {VIDEO_LIMIT})")
    elif not (VIDEO_TARGET[0] <= n <= VIDEO_TARGET[1]):
        warnings.append(f"video prompt is {n} chars (target {VIDEO_TARGET[0]}–{VIDEO_TARGET[1]})")

    for kind in ("image", "video"):
        p = scene[kind]
        if p and "9:16" not in p:
            errors.append(f"{kind} prompt does not state 9:16")
        missing = [t for t in LOCK_TERMS if t not in p]
        if p and missing:
            errors.append(f"{kind} prompt missing character-lock terms: {', '.join(missing)}")
        if p and "no " not in p.lower():
            warnings.append(f"{kind} prompt may lack the no-text/no-watermark line")

    lines = dialogue_lines(scene["dialogue"])
    for speaker, line in lines:
        if line and line not in video:
            errors.append(f"dialogue line by {speaker} is not copied verbatim into the video prompt")
    words = sum(len(DEVANAGARI_WORD.findall(l)) for _, l in lines)
    scene["words"] = words
    scene["chars"] = n
    if lines and not (10 <= words <= 28):
        warnings.append(f"dialogue has {words} Hindi words (aim 15–25)")

    if not scene["status"]:
        errors.append("empty SOURCE STATUS")
    return errors, warnings


def run():
    scenes = load_scenes()
    seen, bad = set(), 0
    for s in scenes:
        errors, warnings = check(s, seen)
        s["errors"], s["warnings"] = errors, warnings
        bad += bool(errors)
    return scenes, bad


if __name__ == "__main__":
    scenes, bad = run()
    for s in scenes:
        tag = "FAIL" if s["errors"] else ("warn" if s["warnings"] else " ok ")
        print(f"[{tag}] E{s['episode']:02d}-S{s['scene']:02d}  video={s['chars']:>3}  "
              f"words={s['words']:>2}  {s['title']}")
        for e in s["errors"]:
            print("        ERROR:", e)
        for w in s["warnings"]:
            print("        warn: ", w)
    print(f"\n{len(scenes)} scenes, {bad} with errors.")
    sys.exit(1 if bad else 0)
