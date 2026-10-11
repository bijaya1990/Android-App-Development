/**
 * Guide: the whole way from sign-up to printed cards, step by step, in English
 * and Hindi. Route: guide, or guide/{step} to open one step (used by the
 * "Help for this step" links on every screen of the card journey).
 */

import { esc, icon } from '../ui.js';

const KEY = 'pkc_guide_lang';

function getLang() {
	try {
		return window.localStorage.getItem( KEY ) === 'en' ? 'en' : 'hi';
	} catch ( e ) {
		return 'hi';
	}
}

function setLang( l ) {
	try {
		window.localStorage.setItem( KEY, l );
	} catch ( e ) {}
}

/** Words used around the steps. */
const UI = {
	en: {
		title: 'How to use Pikacart',
		lead: 'Everything from your first login to printed ID cards, one step at a time. Tap a step to open it. Button names are written exactly as you see them on the screen.',
		start: 'Start making ID cards',
		open: 'Open all steps',
		close: 'Close all steps',
		what: 'What is this?',
		how: 'How to do it',
		options: 'Options you will see',
		tip: 'Tip',
		go: 'Go to this page',
		path: 'The full path',
		faq: 'Common questions',
		help: 'Still stuck?',
		helpText: 'Write to us from Support. We reply in English, Hindi or Odia.',
		support: 'Open Support',
		lang: 'Language',
		step: 'Step',
	},
	hi: {
		title: 'Pikacart कैसे चलाएँ',
		lead: 'पहली बार लॉगिन से लेकर ID कार्ड प्रिंट होने तक सब कुछ, एक-एक स्टेप में। किसी भी स्टेप पर टैप करें, वह खुल जाएगा। बटन के नाम ठीक वैसे ही लिखे हैं जैसे स्क्रीन पर दिखते हैं (अंग्रेज़ी में)।',
		start: 'ID कार्ड बनाना शुरू करें',
		open: 'सारे स्टेप खोलें',
		close: 'सारे स्टेप बंद करें',
		what: 'यह क्या है?',
		how: 'कैसे करें',
		options: 'यहाँ मिलने वाले ऑप्शन',
		tip: 'टिप',
		go: 'यह पेज खोलें',
		path: 'पूरा रास्ता',
		faq: 'आम सवाल',
		help: 'फिर भी समझ नहीं आया?',
		helpText: 'Support में लिखिए। हम हिंदी, English या ओड़िया में जवाब देते हैं।',
		support: 'Support खोलें',
		lang: 'भाषा',
		step: 'स्टेप',
	},
};

/**
 * Steps in the order a new user does them.
 * route: page the "Go to this page" button opens.
 */
const STEPS = [
	{
		key: 'start', icon: 'check-circle', route: '',
		en: {
			t: 'Before you start: keep these ready',
			s: 'Logo, signature and the list of people',
			what: 'Making cards is quick when these things are ready on your phone or computer.',
			how: [
				'Logo of the school, college or office (photo or PNG).',
				'Signature of the principal or authority on white paper, photographed clearly. A seal/stamp is optional.',
				'Full address, phone number and website of the organisation.',
				'The list of people: name, ID or roll number, class, mobile and so on. An Excel sheet is best, but you can also type them one by one.',
				'A photo of every person: front-facing, plain background. Photos can be inside the Excel or in a folder/ZIP.',
			],
			tip: 'You do not need everything on day one. You can add the signature or more people later; the cards update by themselves.',
		},
		hi: {
			t: 'शुरू करने से पहले: ये चीज़ें तैयार रखें',
			s: 'लोगो, सिग्नेचर और लोगों की लिस्ट',
			what: 'ये चीज़ें फ़ोन या कंप्यूटर में तैयार हों तो कार्ड बहुत जल्दी बनते हैं।',
			how: [
				'स्कूल, कॉलेज या ऑफ़िस का लोगो (फ़ोटो या PNG)।',
				'प्रिंसिपल या अधिकारी का सिग्नेचर सफ़ेद काग़ज़ पर, साफ़ फ़ोटो लेकर। सील/स्टैम्प चाहें तो।',
				'संस्था का पूरा पता, फ़ोन नंबर और वेबसाइट।',
				'लोगों की लिस्ट: नाम, ID या रोल नंबर, क्लास, मोबाइल वगैरह। Excel सबसे अच्छा है, पर एक-एक करके टाइप भी कर सकते हैं।',
				'हर व्यक्ति की फ़ोटो: सामने से, सादा बैकग्राउंड। फ़ोटो Excel के अंदर या किसी फ़ोल्डर/ZIP में हो सकती हैं।',
			],
			tip: 'पहले दिन सब कुछ होना ज़रूरी नहीं। सिग्नेचर या और लोग बाद में भी जोड़ सकते हैं, कार्ड अपने आप अपडेट हो जाते हैं।',
		},
	},
	{
		key: 'account', icon: 'home', route: '',
		en: {
			t: 'Your account and the Dashboard',
			s: 'Sign up, verify email, the left menu',
			what: 'One account can make cards for many schools, colleges or offices. The Dashboard shows your organisation, your projects and your plan.',
			how: [
				'Sign up with your name or shop name, email, mobile and a password.',
				'Open the email we send and press the link to verify your email.',
				'After login you land on the Dashboard. The menu on the left (the ☰ button on mobile) opens every page.',
				'The box at the top, "Working for …", shows which organisation you are working in now. Tap it to switch.',
			],
			options: [
				[ 'Dashboard', 'Your summary and quick buttons.' ],
				[ 'Guide', 'This page. Open it any time.' ],
				[ 'Organisations', 'All schools/colleges/offices you make cards for.' ],
				[ 'Create ID card', 'Start a new card project.' ],
				[ 'My Projects', 'All card projects of the organisation you are working in.' ],
				[ 'Members', 'Jump straight to the people of a project.' ],
				[ 'Print Sheets', 'Print or download cards of a project.' ],
				[ 'My Designs', 'Your own uploaded designs and Design on Demand.' ],
				[ 'Organisation details', 'Logo, address, signature of the current organisation.' ],
				[ 'Subscription', 'Your plan and payments.' ],
				[ 'Support', 'Chat with the Pikacart team.' ],
			],
		},
		hi: {
			t: 'आपका अकाउंट और Dashboard',
			s: 'साइन-अप, ईमेल वेरिफ़ाई, बाईं ओर का मेन्यू',
			what: 'एक अकाउंट से कई स्कूल, कॉलेज या ऑफ़िस के कार्ड बना सकते हैं। Dashboard पर आपकी संस्था, प्रोजेक्ट और प्लान दिखते हैं।',
			how: [
				'अपना नाम या दुकान का नाम, ईमेल, मोबाइल और पासवर्ड डालकर Sign up करें।',
				'हमारा भेजा ईमेल खोलें और लिंक दबाकर ईमेल वेरिफ़ाई करें।',
				'लॉगिन के बाद Dashboard खुलता है। बाईं ओर का मेन्यू (मोबाइल पर ☰ बटन) हर पेज खोलता है।',
				'ऊपर "Working for …" वाला बॉक्स बताता है कि अभी आप किस संस्था में काम कर रहे हैं। बदलने के लिए उस पर टैप करें।',
			],
			options: [
				[ 'Dashboard', 'आपका सार और जल्दी वाले बटन।' ],
				[ 'Guide', 'यही पेज। कभी भी खोलें।' ],
				[ 'Organisations', 'जिन स्कूल/कॉलेज/ऑफ़िस के कार्ड बनाते हैं, उनकी लिस्ट।' ],
				[ 'Create ID card', 'नया कार्ड प्रोजेक्ट शुरू करें।' ],
				[ 'My Projects', 'अभी वाली संस्था के सारे कार्ड प्रोजेक्ट।' ],
				[ 'Members', 'सीधे किसी प्रोजेक्ट के लोगों पर जाएँ।' ],
				[ 'Print Sheets', 'प्रोजेक्ट के कार्ड प्रिंट या डाउनलोड करें।' ],
				[ 'My Designs', 'आपके अपलोड किए डिज़ाइन और Design on Demand।' ],
				[ 'Organisation details', 'अभी वाली संस्था का लोगो, पता, सिग्नेचर।' ],
				[ 'Subscription', 'आपका प्लान और पेमेंट।' ],
				[ 'Support', 'Pikacart टीम से चैट।' ],
			],
		},
	},
	{
		key: 'org', icon: 'building', route: 'organisations',
		en: {
			t: 'Add the organisation (school, college, office)',
			s: 'Each organisation is its own folder',
			what: 'Every school, college or office you make cards for is added once. Its name, logo, address and signature are printed on its cards, and its projects and people stay in its own folder. They never mix.',
			how: [
				'Open Organisations and press "Add organisation" (or "Add a new organisation" in Create ID card).',
				'Choose the category, e.g. School, College, Coaching, Company, Hospital.',
				'Type the name and the full address, then press Next.',
				'Upload the logo and the signature (seal is optional), then press Next. You can skip and add them later.',
				'To work for another organisation later, tap "Working for …" at the top and choose it.',
			],
			options: [
				[ 'Open & work', 'Makes this organisation the current one and opens its projects.' ],
				[ 'New card', 'Starts a new card for this organisation.' ],
				[ 'Edit (pencil)', 'Change its name, logo, address or signature.' ],
				[ 'Delete (bin)', 'Deletes it with its projects. People stay in the recycle bin for 30 days.' ],
			],
			tip: 'A computer shop can add 50 schools. The same ID number can be used in two different schools without any clash.',
		},
		hi: {
			t: 'संस्था जोड़ें (स्कूल, कॉलेज, ऑफ़िस)',
			s: 'हर संस्था का अपना अलग फ़ोल्डर',
			what: 'जिस भी स्कूल, कॉलेज या ऑफ़िस के कार्ड बनाने हैं, उसे एक बार जोड़ें। उसका नाम, लोगो, पता और सिग्नेचर उसके कार्ड पर छपते हैं, और उसके प्रोजेक्ट व लोग उसके अपने फ़ोल्डर में रहते हैं। कभी मिक्स नहीं होते।',
			how: [
				'Organisations खोलें और "Add organisation" दबाएँ (या Create ID card में "Add a new organisation")।',
				'कैटेगरी चुनें, जैसे School, College, Coaching, Company, Hospital।',
				'नाम और पूरा पता लिखें, फिर Next दबाएँ।',
				'लोगो और सिग्नेचर अपलोड करें (सील ज़रूरी नहीं), फिर Next दबाएँ। चाहें तो छोड़कर बाद में भी जोड़ सकते हैं।',
				'बाद में दूसरी संस्था का काम करना हो तो ऊपर "Working for …" पर टैप करके उसे चुनें।',
			],
			options: [
				[ 'Open & work', 'इस संस्था को अभी वाली बनाता है और उसके प्रोजेक्ट खोलता है।' ],
				[ 'New card', 'इस संस्था के लिए नया कार्ड शुरू करता है।' ],
				[ 'Edit (पेंसिल)', 'नाम, लोगो, पता या सिग्नेचर बदलें।' ],
				[ 'Delete (डस्टबिन)', 'प्रोजेक्ट समेत हटाता है। लोग 30 दिन तक रीसायकल बिन में रहते हैं।' ],
			],
			tip: 'कंप्यूटर शॉप 50 स्कूल भी जोड़ सकती है। एक ही ID नंबर दो अलग स्कूलों में बिना टकराव के चल सकता है।',
		},
	},
	{
		key: 'type', icon: 'idcard', route: 'create',
		en: {
			t: 'Create ID card: pick the organisation and card type',
			s: 'Step 1 and 2 of the card journey',
			what: 'Every new card starts from "Create ID card". A bar at the top always shows "Step X of 8", so you know where you are. Big Back / Next buttons at the bottom take you forward.',
			how: [
				'Press "Create ID card" in the left menu (or "New project").',
				'Step 1: tap the organisation the card is for. "Last used" marks the one you used before.',
				'Step 2: choose the card type, e.g. Student, Teacher / Staff, Visitor, Employee.',
			],
			tip: 'The project is saved by itself as "College name · Student". You can rename it any time from the name box at the top.',
		},
		hi: {
			t: 'Create ID card: संस्था और कार्ड टाइप चुनें',
			s: 'कार्ड बनाने का स्टेप 1 और 2',
			what: 'हर नया कार्ड "Create ID card" से शुरू होता है। ऊपर एक पट्टी हमेशा "Step X of 8" दिखाती है, ताकि पता रहे आप कहाँ हैं। नीचे बड़े Back / Next बटन आगे-पीछे ले जाते हैं।',
			how: [
				'बाईं ओर मेन्यू में "Create ID card" (या "New project") दबाएँ।',
				'स्टेप 1: जिस संस्था का कार्ड है, उस पर टैप करें। पिछली बार वाली पर "Last used" लिखा होता है।',
				'स्टेप 2: कार्ड का टाइप चुनें, जैसे Student, Teacher / Staff, Visitor, Employee।',
			],
			tip: 'प्रोजेक्ट अपने आप "कॉलेज का नाम · Student" नाम से सेव होता है। ऊपर नाम वाले बॉक्स से कभी भी नाम बदल सकते हैं।',
		},
	},
	{
		key: 'design', icon: 'palette', route: 'create',
		en: {
			t: 'Choose a design',
			s: 'Step 3: 50 ready designs for every type',
			what: 'Each card type has many ready designs. Your logo, address and a sample person are shown on them, so you see the real look.',
			how: [
				'Use the style filter at the top if you want (e.g. Modern, Classic).',
				'Tap a design to see the front and back bigger.',
				'Press "Use this design".',
			],
			options: [
				[ 'Use my own design', 'Upload your own card background made in CorelDraw/Photoshop.' ],
				[ 'Design on Demand', 'Send us your idea; our team makes the design for you.' ],
				[ 'Change design (later)', 'Switch the design any time. Your people and details are kept.' ],
			],
		},
		hi: {
			t: 'डिज़ाइन चुनें',
			s: 'स्टेप 3: हर टाइप के 50 तैयार डिज़ाइन',
			what: 'हर कार्ड टाइप के कई तैयार डिज़ाइन हैं। उन पर आपका लोगो, पता और एक सैंपल व्यक्ति दिखता है, ताकि असली लुक दिख जाए।',
			how: [
				'चाहें तो ऊपर स्टाइल फ़िल्टर लगाएँ (जैसे Modern, Classic)।',
				'किसी डिज़ाइन पर टैप करें, आगे और पीछे का हिस्सा बड़ा दिखेगा।',
				'"Use this design" दबाएँ।',
			],
			options: [
				[ 'Use my own design', 'CorelDraw/Photoshop में बना अपना कार्ड बैकग्राउंड अपलोड करें।' ],
				[ 'Design on Demand', 'अपना आइडिया भेजें, हमारी टीम डिज़ाइन बनाकर देगी।' ],
				[ 'Change design (बाद में)', 'कभी भी डिज़ाइन बदलें। लोग और डिटेल्स वैसे ही रहते हैं।' ],
			],
		},
	},
	{
		key: 'setup', icon: 'card', route: 'projects',
		en: {
			t: 'Size & colour',
			s: 'Step 4: match your card holder',
			what: 'Pick the size of the card and its colours. The live preview on the right changes at once.',
			how: [
				'Orientation: Portrait (standing) or Landscape (lying).',
				'Card size: most PVC cards and holders are 54 × 86 mm. Choose "Custom size" for any other size in mm.',
				'Colours: tap a suggested colour set, or "Match my brand colours" to pick your own.',
			],
			options: [
				[ 'Add 2 mm bleed', 'Switch on only if your PVC printer or print shop asks for bleed.' ],
			],
		},
		hi: {
			t: 'साइज़ और रंग',
			s: 'स्टेप 4: अपने कार्ड होल्डर के हिसाब से',
			what: 'कार्ड का साइज़ और रंग चुनें। दाईं ओर का लाइव प्रीव्यू तुरंत बदल जाता है।',
			how: [
				'Orientation: Portrait (खड़ा) या Landscape (लेटा)।',
				'Card size: ज़्यादातर PVC कार्ड और होल्डर 54 × 86 mm के होते हैं। कोई और साइज़ हो तो "Custom size" में mm लिखें।',
				'Colours: कोई सुझाया रंग सेट चुनें, या "Match my brand colours" से अपने रंग चुनें।',
			],
			options: [
				[ 'Add 2 mm bleed', 'तभी ऑन करें जब आपका PVC प्रिंटर या प्रिंट शॉप bleed माँगे।' ],
			],
		},
	},
	{
		key: 'details', icon: 'edit', route: 'projects',
		en: {
			t: 'Details: what is printed on the card',
			s: 'Step 5: fields, dates, ID numbers',
			what: 'Here you decide which lines appear on the card. A field that is switched off, or empty for a person, simply disappears with no blank gap.',
			how: [
				'Organisation details: check the logo, address and signature. Press Edit to change them.',
				'Card title (e.g. "Student ID Card") and Session / year (e.g. 2026-27).',
				'Card validity: "Valid upto" date for all cards, as DD-MM-YYYY, e.g. 31-03-2027.',
				'ID numbers: switch on "Make ID numbers automatically" and set the prefix (e.g. STU), first number and digits. Then you never type ID numbers: everyone gets STU0001, STU0002…',
				'Fields on the card: switch on what you need, e.g. Roll No., Registration No., Class, Father name, Blood group, Mobile. You can switch the ID number off too.',
				'Your own fields (up to 5): type a name like "House" or "Bus route". It switches on by itself and shows as [House] in the preview until people are filled.',
			],
			options: [
				[ 'QR code on front / back', 'Scanning it opens a secure page that proves the card is real.' ],
				[ 'Barcode on front / back', 'For libraries and attendance scanners.' ],
				[ 'Renewal box', 'Session and signature table on the back for yearly renewal.' ],
				[ 'Terms and conditions', 'Rules printed on the back side.' ],
			],
			tip: 'Everything saves by itself. Look for "Saved" at the top.',
		},
		hi: {
			t: 'Details: कार्ड पर क्या छपेगा',
			s: 'स्टेप 5: फ़ील्ड, तारीख़, ID नंबर',
			what: 'यहाँ तय करते हैं कि कार्ड पर कौन-कौन सी लाइन आएगी। जो फ़ील्ड बंद है, या किसी व्यक्ति के लिए खाली है, वह बिना खाली जगह छोड़े हट जाती है।',
			how: [
				'Organisation details: लोगो, पता और सिग्नेचर देख लें। बदलना हो तो Edit दबाएँ।',
				'Card title (जैसे "Student ID Card") और Session / year (जैसे 2026-27)।',
				'Card validity: सारे कार्ड की "Valid upto" तारीख़, DD-MM-YYYY में, जैसे 31-03-2027।',
				'ID numbers: "Make ID numbers automatically" ऑन करें और prefix (जैसे STU), पहला नंबर और digits सेट करें। फिर ID नंबर कभी टाइप नहीं करना: सबको STU0001, STU0002… मिलेगा।',
				'Fields on the card: जो चाहिए वो ऑन करें, जैसे Roll No., Registration No., Class, Father name, Blood group, Mobile। ID number भी बंद कर सकते हैं।',
				'Your own fields (5 तक): "House" या "Bus route" जैसा नाम लिखें। वह अपने आप ऑन हो जाता है और लोगों की वैल्यू भरने तक प्रीव्यू में [House] दिखता है।',
			],
			options: [
				[ 'QR code on front / back', 'स्कैन करने पर एक सुरक्षित पेज खुलता है जो बताता है कि कार्ड असली है।' ],
				[ 'Barcode on front / back', 'लाइब्रेरी और अटेंडेंस स्कैनर के लिए।' ],
				[ 'Renewal box', 'हर साल रिन्यूअल के लिए पीछे session और सिग्नेचर की टेबल।' ],
				[ 'Terms and conditions', 'पीछे छपने वाले नियम।' ],
			],
			tip: 'सब कुछ अपने आप सेव होता है। ऊपर "Saved" लिखा दिखेगा।',
		},
	},
	{
		key: 'editor', icon: 'tools', route: 'projects',
		en: {
			t: 'Fine-tune (optional)',
			s: 'Step 6: move or restyle anything',
			what: 'If the card already looks right, just press Next. If you want, you can change any text or picture by hand.',
			how: [
				'Tap any text, photo or logo on the card to select it.',
				'Drag to move it, pull the corners to resize it.',
				'Change font, size, colour or alignment from the panel.',
				'Use Undo if you make a mistake.',
			],
		},
		hi: {
			t: 'Fine-tune (ज़रूरी नहीं)',
			s: 'स्टेप 6: कुछ भी खिसकाएँ या बदलें',
			what: 'कार्ड पहले से सही दिख रहा हो तो बस Next दबाएँ। चाहें तो कोई भी टेक्स्ट या फ़ोटो हाथ से बदल सकते हैं।',
			how: [
				'कार्ड पर किसी टेक्स्ट, फ़ोटो या लोगो पर टैप करके उसे चुनें।',
				'खींचकर जगह बदलें, कोने खींचकर छोटा-बड़ा करें।',
				'पैनल से फ़ॉन्ट, साइज़, रंग या अलाइनमेंट बदलें।',
				'ग़लती हो जाए तो Undo दबाएँ।',
			],
		},
	},
	{
		key: 'people', icon: 'users', route: 'members',
		en: {
			t: 'Add the people',
			s: 'Step 7: one by one, from Excel, or a self-fill link',
			what: 'Every person becomes one card. There are three ways to add people; use whichever is easiest.',
			how: [
				'One person: press "Add person", fill the form, add the photo, crop it and save.',
				'Many people from Excel: press "Blank Excel" at the top. It downloads a sheet with the right columns. Fill one person per row.',
				'Photos in Excel: paste each photo into that person\'s Photo cell. Or write the photo file name (e.g. aarav.jpg), or name the photos by ID number (1001.jpg).',
				'Press "Import Excel / CSV", choose the filled file (and the photos or ZIP if they are separate), check the columns and press "Import".',
				'Self-fill link: press "Self-fill link", then "Create link", and share it on WhatsApp. Students fill their own details and photo.',
				'Entries from the link arrive as "Pending". Check them and press "Approve".',
			],
			options: [
				[ 'Draft / Ready / Printed', 'Status of each card. Printed cards are marked by themselves.' ],
				[ 'Search and filters', 'Find a person, or show only one class or department.' ],
				[ 'Recycle bin', 'Deleted people stay here for 30 days. You can restore them.' ],
				[ 'New session', 'Next year: copy the project for the new session.' ],
			],
			tip: '1000 people with photos import in about a minute. Keep the column headings of the Blank Excel as they are.',
		},
		hi: {
			t: 'लोग जोड़ें',
			s: 'स्टेप 7: एक-एक करके, Excel से, या self-fill लिंक से',
			what: 'हर व्यक्ति का एक कार्ड बनता है। लोग जोड़ने के तीन तरीके हैं, जो आसान लगे वो अपनाएँ।',
			how: [
				'एक व्यक्ति: "Add person" दबाएँ, फ़ॉर्म भरें, फ़ोटो लगाकर क्रॉप करें और सेव करें।',
				'Excel से बहुत सारे लोग: ऊपर "Blank Excel" दबाएँ। सही कॉलम वाली शीट डाउनलोड होगी। हर लाइन में एक व्यक्ति भरें।',
				'Excel में फ़ोटो: हर फ़ोटो उस व्यक्ति के Photo सेल में पेस्ट करें। या फ़ोटो फ़ाइल का नाम लिखें (जैसे aarav.jpg), या फ़ोटो का नाम ID नंबर रखें (1001.jpg)।',
				'"Import Excel / CSV" दबाएँ, भरी हुई फ़ाइल चुनें (फ़ोटो अलग हों तो फ़ोटो या ZIP भी), कॉलम देखें और "Import" दबाएँ।',
				'Self-fill लिंक: "Self-fill link" फिर "Create link" दबाएँ और WhatsApp पर भेजें। स्टूडेंट अपनी डिटेल और फ़ोटो ख़ुद भरते हैं।',
				'लिंक से आई एंट्री "Pending" में आती हैं। देखकर "Approve" दबाएँ।',
			],
			options: [
				[ 'Draft / Ready / Printed', 'हर कार्ड का स्टेटस। प्रिंट हुए कार्ड अपने आप Printed हो जाते हैं।' ],
				[ 'Search और फ़िल्टर', 'किसी व्यक्ति को ढूँढें, या सिर्फ़ एक क्लास/डिपार्टमेंट दिखाएँ।' ],
				[ 'Recycle bin', 'हटाए गए लोग 30 दिन यहाँ रहते हैं। वापस ला सकते हैं।' ],
				[ 'New session', 'अगले साल: नए सेशन के लिए प्रोजेक्ट कॉपी करें।' ],
			],
			tip: 'फ़ोटो समेत 1000 लोग लगभग एक मिनट में इम्पोर्ट हो जाते हैं। Blank Excel के कॉलम के नाम मत बदलिए।',
		},
	},
	{
		key: 'download', icon: 'printer', route: 'print',
		en: {
			t: 'Print or download the cards',
			s: 'Step 8: A4 paper, PVC printer or WhatsApp',
			what: 'Choose how you will print. Pick which cards (all, only Ready, one class) and which sides (front + back, front only, back only).',
			how: [
				'Print on A4 paper: many cards are arranged on each A4 page by themselves, with light dotted cut lines and scissors marks in the middle of the gap. Print at 100% scale, cut on the lines and put the card in the cover.',
				'PVC card printer (Evolis, Zebra, Fargo, HiTi): choose the card size, then "Both sides together" for a dual-side printer, or "One side only" (print fronts, put the cards back, then press "Print the backs"). Press "Print 1 test card" first, then "Print all".',
				'Photos for WhatsApp: every card as an image in a ZIP, to share or email.',
				'Print shop wants a file? Use "Download card PDF (one card per page)".',
			],
			tip: 'In the print window always choose scale 100% (not "Fit to page"), so the card size stays exact.',
		},
		hi: {
			t: 'कार्ड प्रिंट या डाउनलोड करें',
			s: 'स्टेप 8: A4 पेपर, PVC प्रिंटर या WhatsApp',
			what: 'कैसे प्रिंट करना है वो चुनें। कौन से कार्ड (सारे, सिर्फ़ Ready, एक क्लास) और कौन सी साइड (front + back, सिर्फ़ front, सिर्फ़ back) चुनें।',
			how: [
				'Print on A4 paper: हर A4 पेज पर कई कार्ड अपने आप सज जाते हैं, हल्की डॉटेड कटिंग लाइन और कैंची के निशान बीच के गैप में। 100% स्केल पर प्रिंट करें, लाइन पर काटें और कवर में डालें।',
				'PVC card printer (Evolis, Zebra, Fargo, HiTi): कार्ड साइज़ चुनें, फिर दोनों तरफ़ छापने वाले प्रिंटर के लिए "Both sides together", या "One side only" (पहले front छापें, कार्ड वापस डालें, फिर "Print the backs" दबाएँ)। पहले "Print 1 test card", फिर "Print all" दबाएँ।',
				'Photos for WhatsApp: हर कार्ड एक फ़ोटो बनकर ZIP में, शेयर या ईमेल करने के लिए।',
				'प्रिंट शॉप को फ़ाइल चाहिए? "Download card PDF (one card per page)" इस्तेमाल करें।',
			],
			tip: 'प्रिंट विंडो में हमेशा स्केल 100% चुनें ("Fit to page" नहीं), ताकि कार्ड का साइज़ सही रहे।',
		},
	},
	{
		key: 'verify', icon: 'shield', route: 'organisation',
		en: {
			t: 'QR code verification',
			s: 'Anyone can check if a card is real',
			what: 'Every card has its own QR code. Scanning it with any phone camera opens a secure Pikacart page with the person\'s photo, name and status, so fake cards are easy to catch.',
			how: [
				'Scan the QR code on a printed card with your phone camera to test it.',
				'In Organisation details → Verification page privacy, choose what the page shows (e.g. hide mobile number).',
			],
		},
		hi: {
			t: 'QR कोड से वेरिफ़िकेशन',
			s: 'कोई भी जाँच सकता है कि कार्ड असली है',
			what: 'हर कार्ड का अपना QR कोड होता है। किसी भी फ़ोन कैमरे से स्कैन करने पर Pikacart का एक सुरक्षित पेज खुलता है जिसमें व्यक्ति की फ़ोटो, नाम और स्टेटस दिखता है, जिससे नकली कार्ड आसानी से पकड़े जाते हैं।',
			how: [
				'टेस्ट करने के लिए प्रिंट हुए कार्ड का QR कोड फ़ोन कैमरे से स्कैन करें।',
				'Organisation details → Verification page privacy में चुनें कि पेज पर क्या दिखे (जैसे मोबाइल नंबर छुपाना)।',
			],
		},
	},
	{
		key: 'plan', icon: 'rupee', route: 'subscription',
		en: {
			t: 'Plan and payment',
			s: 'Free plan, and ₹59/month without watermark',
			what: 'On the free plan everything works, but downloaded cards carry a small "Made with pikacart.in" watermark. Upgrade to remove it.',
			how: [
				'Open Subscription and choose your plan.',
				'Pay safely with UPI, card or net banking (Razorpay).',
				'The plan starts at once. Your invoices stay in Subscription.',
			],
		},
		hi: {
			t: 'प्लान और पेमेंट',
			s: 'फ़्री प्लान, और बिना वॉटरमार्क ₹59/महीना',
			what: 'फ़्री प्लान में सब कुछ चलता है, पर डाउनलोड हुए कार्ड पर छोटा "Made with pikacart.in" वॉटरमार्क आता है। हटाने के लिए अपग्रेड करें।',
			how: [
				'Subscription खोलें और अपना प्लान चुनें।',
				'UPI, कार्ड या नेट बैंकिंग (Razorpay) से सुरक्षित पेमेंट करें।',
				'प्लान तुरंत चालू हो जाता है। बिल Subscription में ही मिलते हैं।',
			],
		},
	},
];

/**
 * Screenshots of the real screens for each step (assets/img/guide/*.webp).
 * The orange box marks what to tap. [ file, English caption, Hindi caption ]
 */
const SHOTS = {
	start: [
		[ 'dashboard', 'After login: the Dashboard and the menu on the left.', 'लॉगिन के बाद: Dashboard और बाईं ओर का मेन्यू।' ],
	],
	account: [
		[ 'signup', 'Sign up: your name or shop name, email, mobile, password, then "Create free account".', 'साइन-अप: आपका या दुकान का नाम, ईमेल, मोबाइल, पासवर्ड, फिर "Create free account"।' ],
		[ 'dashboard', 'The Dashboard. The left menu opens every page.', 'Dashboard। बाईं ओर का मेन्यू हर पेज खोलता है।' ],
	],
	org: [
		[ 'org-pick', 'Create ID card → "Add a new organisation".', 'Create ID card → "Add a new organisation"।' ],
		[ 'org-category', 'Pick the category, e.g. College / University.', 'कैटेगरी चुनें, जैसे College / University।' ],
		[ 'org-form', 'Name and address (phone, email, website optional) → "Save and continue".', 'नाम और पता (फ़ोन, ईमेल, वेबसाइट चाहें तो) → "Save and continue"।' ],
		[ 'org-logo', 'Upload the logo, signature and seal. You can also do this later.', 'लोगो, सिग्नेचर और सील अपलोड करें। बाद में भी कर सकते हैं।' ],
		[ 'orgs', 'Organisations: every college as its own folder.', 'Organisations: हर कॉलेज का अलग फ़ोल्डर।' ],
		[ 'switcher', '"Working for …" at the top: switch to another organisation.', 'ऊपर "Working for …": दूसरी संस्था पर जाएँ।' ],
	],
	type: [
		[ 'type', 'Step 2: choose the card type, e.g. Student.', 'स्टेप 2: कार्ड टाइप चुनें, जैसे Student।' ],
		[ 'projects', 'My Projects: each project shows the college and card type.', 'My Projects: हर प्रोजेक्ट पर कॉलेज और कार्ड टाइप दिखता है।' ],
	],
	design: [
		[ 'design', 'Step 3: all designs with your own logo. Tap one.', 'स्टेप 3: आपके लोगो के साथ सारे डिज़ाइन। किसी एक पर टैप करें।' ],
		[ 'design-modal', 'See the front and back, then "Use this design".', 'आगे-पीछे देखें, फिर "Use this design"।' ],
	],
	setup: [
		[ 'setup', 'Step 4: card size (54 × 86 mm is the usual PVC card) and colours.', 'स्टेप 4: कार्ड साइज़ (आम PVC कार्ड 54 × 86 mm) और रंग।' ],
	],
	details: [
		[ 'details-top', 'Card title, session and "Valid upto" date.', 'कार्ड टाइटल, सेशन और "Valid upto" तारीख़।' ],
		[ 'details-id', 'ID numbers made automatically: MMV0001, MMV0002…', 'अपने आप बनने वाले ID नंबर: MMV0001, MMV0002…' ],
		[ 'details-fields', 'Fields on the card: switch on Roll No., Class, Blood group…', 'Fields on the card: Roll No., Class, Blood group… ऑन करें।' ],
		[ 'details-own', 'Your own field: type "House" and it switches on.', 'अपनी फ़ील्ड: "House" लिखें, वह अपने आप ऑन हो जाएगी।' ],
	],
	editor: [
		[ 'editor', 'Step 6: tap anything on the card to move, resize or restyle it.', 'स्टेप 6: कार्ड पर किसी भी चीज़ पर टैप करके खिसकाएँ, छोटा-बड़ा करें या स्टाइल बदलें।' ],
	],
	people: [
		[ 'people-empty', 'Step 7: Add person, Blank Excel, Import Excel / CSV, Bulk photos, Self-fill link.', 'स्टेप 7: Add person, Blank Excel, Import Excel / CSV, Bulk photos, Self-fill link।' ],
		[ 'people-import', '"Import Excel / CSV" → "Choose file".', '"Import Excel / CSV" → "Choose file"।' ],
		[ 'people-map', 'Check the rows, then "Import 5 people". Photos inside the Excel come too.', 'लाइनें देखें, फिर "Import 5 people"। Excel के अंदर की फ़ोटो भी आ जाती हैं।' ],
		[ 'people-list', 'Everyone is in the list with photo, ID and status.', 'सब लोग फ़ोटो, ID और स्टेटस के साथ लिस्ट में।' ],
		[ 'selffill', 'Self-fill link: copy it or send it on WhatsApp.', 'Self-fill लिंक: कॉपी करें या WhatsApp पर भेजें।' ],
		[ 'fill-form', 'What students see on their phone.', 'स्टूडेंट को फ़ोन पर ऐसा दिखता है।' ],
	],
	download: [
		[ 'download', 'Step 8: A4 paper, PVC card printer or photos for WhatsApp.', 'स्टेप 8: A4 पेपर, PVC कार्ड प्रिंटर या WhatsApp के लिए फ़ोटो।' ],
		[ 'a4', 'A4 print sheet: cards arranged by themselves, with cut marks.', 'A4 प्रिंट शीट: कार्ड अपने आप सजे, कटिंग निशान के साथ।' ],
		[ 'pvc', 'PVC printer: card size, both sides or one side, then Print.', 'PVC प्रिंटर: कार्ड साइज़, दोनों साइड या एक साइड, फिर Print।' ],
	],
	verify: [
		[ 'verify', 'The page that opens when the QR code is scanned.', 'QR कोड स्कैन करने पर खुलने वाला पेज।' ],
	],
	plan: [
		[ 'plan', 'Subscription: free plan, or ₹59 per month without watermark.', 'Subscription: फ़्री प्लान, या ₹59 महीना बिना वॉटरमार्क।' ],
	],
};

function shotsHTML( key, lang ) {
	const list = SHOTS[ key ] || [];
	if ( ! list.length ) {
		return '';
	}
	const base = window.PKC.assets + 'img/guide/';
	return `<div class="guide-shots${ list.length === 1 ? ' is-one' : '' }">${ list.map( ( s, i ) => {
		const cap = lang === 'hi' ? s[ 2 ] : s[ 1 ];
		const tall = s[ 0 ] === 'fill-form' || s[ 0 ] === 'verify';
		return `<figure class="guide-shot${ tall ? ' is-tall' : '' }">
			<button type="button" class="guide-zoom" data-zoom="${ esc( base + s[ 0 ] + '.webp' ) }" data-cap="${ esc( cap ) }">
				<img src="${ esc( base + s[ 0 ] + '.webp' ) }" alt="${ esc( cap ) }" loading="lazy" decoding="async">
				<span class="guide-zoom-i">${ icon( 'search' ) }</span>
			</button>
			<figcaption><span>${ i + 1 }</span>${ esc( cap ) }</figcaption>
		</figure>`;
	} ).join( '' ) }</div>`;
}

const FAQ = {
	en: [
		[ 'A field does not show on the card.', 'Switch it on in Details → Fields on the card, and fill that person\'s value. Empty values are hidden on purpose.' ],
		[ 'I don\'t want ID numbers on the card.', 'Switch "ID number" off in Details → Fields on the card. You will not need to type it anywhere.' ],
		[ 'The card printed smaller or bigger.', 'Print again with scale 100% (not "Fit to page") in the print window.' ],
		[ 'Photos did not come with the Excel.', 'Paste photos inside the Photo cell, or name each photo by the ID number (1001.jpg) and choose the photos/ZIP while importing.' ],
		[ 'Where are the cards of another college?', 'Tap "Working for …" at the top and choose that college. Each college keeps its own projects.' ],
		[ 'Can I change things after printing?', 'Yes. Change the design, details or people any time and print again. Nothing is locked.' ],
	],
	hi: [
		[ 'कोई फ़ील्ड कार्ड पर नहीं दिख रही।', 'Details → Fields on the card में उसे ऑन करें, और उस व्यक्ति की वैल्यू भरें। खाली वैल्यू जानबूझकर छुपाई जाती है।' ],
		[ 'मुझे कार्ड पर ID नंबर नहीं चाहिए।', 'Details → Fields on the card में "ID number" बंद कर दें। फिर कहीं भी टाइप नहीं करना पड़ेगा।' ],
		[ 'कार्ड छोटा या बड़ा छपा।', 'प्रिंट विंडो में स्केल 100% ("Fit to page" नहीं) रखकर दोबारा प्रिंट करें।' ],
		[ 'Excel के साथ फ़ोटो नहीं आईं।', 'फ़ोटो Photo सेल के अंदर पेस्ट करें, या हर फ़ोटो का नाम ID नंबर रखें (1001.jpg) और इम्पोर्ट करते समय फ़ोटो/ZIP चुनें।' ],
		[ 'दूसरे कॉलेज के कार्ड कहाँ हैं?', 'ऊपर "Working for …" पर टैप करके वो कॉलेज चुनें। हर कॉलेज के प्रोजेक्ट अलग रहते हैं।' ],
		[ 'प्रिंट के बाद भी बदलाव कर सकते हैं?', 'हाँ। डिज़ाइन, डिटेल या लोग कभी भी बदलें और फिर से प्रिंट करें। कुछ भी लॉक नहीं होता।' ],
	],
};

export default function guide( el, ctx, params ) {
	let lang = getLang();
	let focus = params[ 0 ] || '';

	function draw() {
		const u = UI[ lang ];
		el.innerHTML = `<div class="guide" lang="${ lang }">
			<section class="card guide-hero">
				<div class="guide-hero-top">
					<span class="guide-hero-icon">${ icon( 'book' ) }</span>
					<div class="guide-lang" role="group" aria-label="${ esc( u.lang ) }">
						<button type="button" data-lang="hi" aria-pressed="${ lang === 'hi' }">हिंदी</button>
						<button type="button" data-lang="en" aria-pressed="${ lang === 'en' }">English</button>
					</div>
				</div>
				<h2 class="h2">${ esc( u.title ) }</h2>
				<p class="muted">${ esc( u.lead ) }</p>
				<div class="guide-hero-actions">
					<a class="btn btn-primary" href="${ esc( ctx.url( 'create' ) ) }" data-link>${ icon( 'plus' ) }${ esc( u.start ) }</a>
					<button type="button" class="btn" data-all>${ esc( u.open ) }</button>
				</div>
			</section>

			<nav class="card guide-map" aria-label="${ esc( u.path ) }">
				<h3>${ esc( u.path ) }</h3>
				<ol>${ STEPS.map( ( s, i ) => `<li><a href="#g-${ s.key }" data-jump="${ s.key }"><span>${ i + 1 }</span>${ esc( s[ lang ].t ) }</a></li>` ).join( '' ) }</ol>
			</nav>

			<div class="guide-steps">
				${ STEPS.map( ( s, i ) => {
					const c = s[ lang ];
					return `<details class="card guide-step" id="g-${ s.key }" data-key="${ s.key }"${ s.key === focus ? ' open' : '' }>
						<summary>
							<span class="guide-num">${ i + 1 }</span>
							<span class="guide-sum"><b>${ esc( c.t ) }</b><small>${ esc( c.s ) }</small></span>
							${ icon( 'chevron-down', 'guide-caret' ) }
						</summary>
						<div class="guide-body">
							<p class="guide-what">${ esc( c.what ) }</p>
							${ shotsHTML( s.key, lang ) }
							<h4>${ esc( u.how ) }</h4>
							<ol class="guide-how">${ c.how.map( ( h ) => `<li>${ esc( h ) }</li>` ).join( '' ) }</ol>
							${ c.options ? `<h4>${ esc( u.options ) }</h4><dl class="guide-opts">${ c.options.map( ( o ) => `<div><dt>${ esc( o[ 0 ] ) }</dt><dd>${ esc( o[ 1 ] ) }</dd></div>` ).join( '' ) }</dl>` : '' }
							${ c.tip ? `<p class="guide-tip">${ icon( 'sparkles' ) }<span><b>${ esc( u.tip ) }:</b> ${ esc( c.tip ) }</span></p>` : '' }
							<div class="guide-foot">
								<a class="btn btn-sm" href="${ esc( ctx.url( s.route ) ) }" data-link>${ esc( u.go ) }${ icon( 'chevron-right' ) }</a>
								${ STEPS[ i + 1 ] ? `<button type="button" class="btn btn-sm btn-ghost" data-next="${ STEPS[ i + 1 ].key }">${ esc( u.step ) } ${ i + 2 }: ${ esc( STEPS[ i + 1 ][ lang ].t ) }</button>` : '' }
							</div>
						</div>
					</details>`;
				} ).join( '' ) }
			</div>

			<section class="card guide-faq">
				<h3>${ esc( u.faq ) }</h3>
				${ FAQ[ lang ].map( ( q ) => `<details><summary>${ esc( q[ 0 ] ) }</summary><p>${ esc( q[ 1 ] ) }</p></details>` ).join( '' ) }
			</section>

			<section class="card guide-help">
				${ icon( 'help' ) }
				<div><b>${ esc( u.help ) }</b><p class="muted">${ esc( u.helpText ) }</p></div>
				<a class="btn" href="${ esc( ctx.url( 'support' ) ) }" data-link>${ esc( u.support ) }</a>
			</section>
		</div>`;
		bind();
		if ( focus ) {
			const target = el.querySelector( `#g-${ focus }` );
			// After the router has finished drawing and scrolling to the top.
			if ( target ) {
				setTimeout( () => target.scrollIntoView( { block: 'start' } ), 150 );
			}
			focus = '';
		}
	}

	/** Full-screen view of a screenshot; tap anywhere or Esc to close. */
	function zoom( src, cap ) {
		const box = document.createElement( 'div' );
		box.className = 'guide-lightbox';
		box.setAttribute( 'role', 'dialog' );
		box.setAttribute( 'aria-modal', 'true' );
		box.innerHTML = `<figure><img src="${ esc( src ) }" alt=""><figcaption>${ esc( cap ) }</figcaption></figure><button type="button" class="guide-lightbox-x" aria-label="Close">${ icon( 'x' ) }</button>`;
		const close = () => {
			box.remove();
			document.removeEventListener( 'keydown', key );
		};
		const key = ( e ) => {
			if ( e.key === 'Escape' ) {
				close();
			}
		};
		box.addEventListener( 'click', close );
		document.addEventListener( 'keydown', key );
		document.body.appendChild( box );
		box.querySelector( 'button' ).focus();
	}

	function open( key ) {
		const d = el.querySelector( `#g-${ key }` );
		if ( d ) {
			d.open = true;
			d.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	}

	function bind() {
		el.querySelectorAll( '[data-lang]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			// Keep the open steps open in the other language.
			const openKeys = [ ...el.querySelectorAll( '.guide-step[open]' ) ].map( ( d ) => d.dataset.key );
			lang = b.dataset.lang;
			setLang( lang );
			draw();
			openKeys.forEach( ( k ) => {
				el.querySelector( `#g-${ k }` ).open = true;
			} );
		} ) );
		el.querySelectorAll( '[data-jump]' ).forEach( ( a ) => a.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			open( a.dataset.jump );
		} ) );
		el.querySelectorAll( '[data-next]' ).forEach( ( b ) => b.addEventListener( 'click', () => open( b.dataset.next ) ) );
		el.querySelectorAll( '[data-zoom]' ).forEach( ( b ) => b.addEventListener( 'click', () => zoom( b.dataset.zoom, b.dataset.cap ) ) );
		const all = el.querySelector( '[data-all]' );
		all.addEventListener( 'click', () => {
			const steps = [ ...el.querySelectorAll( '.guide-step' ) ];
			const openAll = steps.some( ( d ) => ! d.open );
			steps.forEach( ( d ) => {
				d.open = openAll;
			} );
			all.textContent = openAll ? UI[ lang ].close : UI[ lang ].open;
		} );
	}

	draw();
}
