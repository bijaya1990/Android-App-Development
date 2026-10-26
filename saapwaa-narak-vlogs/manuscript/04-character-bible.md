# 3 · Saapwaa — The Definitive Character Bible

*Everything about Saapwaa that must never drift between clips. All of it is {FIC}.*

## The locked design

| Feature | Locked value | Common drift to reject |
|---|---|---|
| Species and style | Cute, expressive cartoon snake; cinematic 3D cartoon render | Realistic snake; 2D flat style; cobra hood |
| Body | Leaf-green, with **darker green spots** | Yellow-green, teal, stripes instead of spots |
| Belly and snout | **Cream-yellow** | White belly; green snout |
| Eyes | Large, expressive **brown eyes with white sclera** | Slit pupils; yellow or red eyes; black button eyes |
| Mouth | Small visible fangs; wide, flexible smile | Big venom fangs; forked tongue in every shot |
| Scarf | **Red-and-white striped scarf**, knotted at the neck, one tail end hanging | Solid red; plaid; missing |
| Backpack | **Brown backpack** worn on the upper back behind the head | Missing; different colour |
| Eyewear | **None.** Sunglasses only if a scene explicitly needs them | Default sunglasses |
| Size | About two metres long; when he rears up, his head reaches an adult’s chest | Pocket-sized or giant |
| Phone | Small black phone on a short bamboo selfie stick, gripped by the curled tip of his tail | Phone held in a “hand”; floating phone |

### Prompt lock — copy into every prompt

<div class="box lock" markdown="1">
**Short lock (video prompts):**
`Saapwaa, a cute cartoon snake: leaf-green body with darker green spots, cream-yellow belly and snout, big brown eyes with white sclera, small fangs, red-and-white striped scarf, brown backpack, no sunglasses.`

**Long lock (image prompts):** the short lock, plus: `expressive eyebrows, smooth glossy skin, about two metres long, holding a small phone on a short bamboo selfie stick with the curled tip of his tail.`
</div>

The checker in `tools/scenes.py` refuses to build a scene whose image or video prompt is missing any of the six core lock phrases.

## Movement

- Slithers in smooth S-curves; never walks, never has hands.
- Rears the front third of his body to talk to people, like standing up.
- Uses the tail tip as a hand: phone stick, lifting a lid, pointing, scratching his chin.
- The head does the acting: tilts, slow blinks, eyebrow lifts, sideways looks to camera.
- Moves a little slower when it is hot. He complains about this.

## Personality

Friendly, confident, mischievous, curious and sarcastic. He likes being on camera and believes he is a professional. Circumstances keep undermining that belief, and his best moments come from the gap between how he presents himself and what actually happens.

- **Vain but likeable.** Fixes his scarf before important shots.
- **Curious.** Cannot pass an odd object without inspecting it.
- **Stingy with snacks.** A small, recurring flaw that pays off in the Vaitarani episode, where the sources’ theme of giving turns gently against him.
- **Never cruel.** He roasts situations, systems and himself. When he teases a resident, the resident usually wins the exchange.
- **Never explains why he is in Narak.** If asked: “वो लंबी कहानी है।” This is a running mystery, never resolved, never theological.

## Voice

A young Indian vlogger speaking casual Hindi with natural English words. Short sentences. He talks *to* the viewer, not *at* them.

| Use | Sparingly | Never |
|---|---|---|
| भाई, अरे यार, देखो, सच बताऊँ, सीरियसली | गाइज (at most once per clip), वेट गाइज (once per episode) | Formal narration (“प्रिय दर्शकों…”), preaching, news-anchor tone, long greetings |

**Voice-over direction for TTS or a voice actor:** young adult male, warm and playful, medium pace, slightly nasal comic edge, quick deadpan drops after punchlines.

**Signature lines (each limited to the episodes listed in the running-joke ledger):**

- “डे काउंटर अपडेट” — the daily count of days in Narak.
- “रिव्यू टाइम” — before rating anything.
- “ये नॉर्मल नहीं है… पर यहाँ नॉर्मल है।” — maximum twice in the whole series.

## Camera grammar

| Mode | When | How to write it in a prompt |
|---|---|---|
| **Selfie walk** | Default talking shots | “handheld selfie view from the phone on his tail-held stick, slight walking bob” |
| **Selfie still** | Confessions, reactions | “static selfie close-up, phone held at arm’s length by his tail” |
| **Rear camera (POV)** | Showing a place or person | “first-person view from his phone, the tip of his snout at the frame edge” |
| **Propped phone** | Physical comedy that needs his whole body | “phone propped on a rock, wide static shot” |

Never write drone shots, orbiting shots or crane moves while Saapwaa is filming himself.

## Belongings (props that must stay consistent)

| Prop | Look | Where it lives |
|---|---|---|
| Phone and stick | Small black phone, short pale bamboo stick | Always with him |
| Backpack | Brown, two buckles, slightly scuffed | On his back |
| Day diary | Small cloth-bound notebook, no visible writing on screen | Backpack |
| Steel tiffin | Three-tier steel tiffin box | Backpack or kitchen shelf |
| Wet cotton cloth | Faded blue, used to cool the stone bed | Cave No. 13 |
| Clay water pot (matka) | Round, terracotta, always warm | Cave No. 13 kitchen |

## Recurring residents

All residents are {FIC}. They are ordinary people of an invented neighbourhood, drawn as gentle cartoon adults. None of them represents a figure from the sources.

### Kulhad Didi — runs the Garam Adda tea stall

<div class="box lock" markdown="1">
`Kulhad Didi: a middle-aged cartoon woman with a round face and kind, tired eyes, mustard-yellow cotton saree with a maroon border, soot-smudged white apron, hair in a bun, holding a big dented copper kettle.`
</div>

Calm, unbothered, sells “ठंडी चाय” that has never once been cold. Deadpan; always wins the exchange.

### Tapu Chacha — next-door neighbour in Cave No. 12

<div class="box lock" markdown="1">
`Tapu Chacha: an elderly thin cartoon man, bald with white side tufts and white stubble, sleepy half-closed eyes, faded maroon kurta and white dhoti, always holding a small brass lota.`
</div>

Kind, slow and permanently postponing. His answer to everything is “कल से पक्का।”

### Lalten Lal — lamp and torch repairman

<div class="box lock" markdown="1">
`Lalten Lal: a lanky young cartoon man with soot-smudged cheeks, big round brass goggles pushed up on his forehead, olive-green vest over a rolled-sleeve shirt, small tools in a waist pouch.`
</div>

An inventor who over-engineers everything and explains his inventions with huge seriousness.

### Kallu — the crow

<div class="box lock" markdown="1">
`Kallu: a glossy black cartoon crow with one bent tail feather and a thin red thread tied on one ankle, clever bright eyes.`
</div>

Does not speak. Steals Saapwaa’s snacks. Crows appear in the source descriptions of the road (S4, ch. 2, PDF p. 9); Kallu himself is invented and only ever plays a cheeky neighbourhood bird.

### Munshi Ratiram — clerk at Window No. 7

<div class="box lock" markdown="1">
`Munshi Ratiram: a plump cartoon clerk with round wire spectacles, ink-stained fingers, cream kurta, a reed pen behind his ear, sitting behind a small arched stone window.`
</div>

Runs an invented neighbourhood office for lamp-oil rations and room repairs. He is **not** Chitragupta and is never placed in Yama’s court. Polite, literal-minded, buried in palm-leaf bundles.

### Nanu — the old guide at the river viewpoint

<div class="box lock" markdown="1">
`Nanu: an elderly cartoon man with a neat white beard, calm wise smile, faded indigo cotton kurta, leaning on a smooth wooden oar used as a walking staff.`
</div>

A retired boatman of the neighbourhood who takes visitors to a distant viewpoint over the Vaitarani and explains what the texts say, accurately and without drama. His information is drawn from the cited sources; he himself is {FIC}.

## Drift checklist for every generated image

1. Spots present and darker green? 2. Belly and snout cream-yellow? 3. Brown eyes with white sclera? 4. Scarf red-and-white striped? 5. Backpack brown and visible? 6. No sunglasses? 7. Phone on a bamboo stick in the tail, not floating?

If two or more fail, regenerate rather than fixing it in the edit.
