# -*- coding: utf-8 -*-
"""
Structured content for the condensed field work report.

Block grammar
-------------
("h1", text)                 chapter heading (starts a new page)
("h2", text)                 section heading
("h3", text)                 sub-section heading
("p", runs)                  justified body paragraph
("pc", runs)                 centred paragraph
("bullet", runs)             bulleted item
("num", runs)                numbered item (auto list)
("table", caption, rows, source_note)   rows[0] is the header row
("break",)                   explicit page break
("blank",)                   empty paragraph

`runs` is a list whose members are either
    str                      plain text
    ("i", str)               italic text
    ("b", str)               bold text
    ("fn", str)              a footnote anchored at this point
"""

TITLE = "Microfinance Institutions and SHG Organisations in Pallahara"

# --------------------------------------------------------------------------
# FRONT MATTER
# --------------------------------------------------------------------------

TITLE_PAGE = [
    ("blank",),
    ("pc", [("b", "A FIELD WORK REPORT ON")]),
    ("blank",),
    ("pc", [("b", "MICROFINANCE INSTITUTIONS AND SELF HELP GROUP (SHG) "
                  "ORGANISATIONS IN PALLAHARA")]),
    ("blank",),
    ("pc", [("i", "A Study of the Role, Growth and Socio-Economic Impact of Self Help "
                  "Groups and Microfinance Institutions in Pallahara Block, Angul "
                  "District, Odisha")]),
    ("blank",),
    ("pc", ["Field Work Report submitted in partial fulfilment of the requirements"]),
    ("pc", ["for the Degree of Bachelor / Master of Arts in Economics"]),
    ("blank",),
    ("pc", [("b", "Submitted by")]),
    ("pc", ["[Student's Name]"]),
    ("pc", ["Roll No.: [Roll Number] | Regn. No.: [Registration Number]"]),
    ("blank",),
    ("pc", [("b", "Under the Guidance of")]),
    ("pc", ["[Supervisor's Name], Professor of Economics"]),
    ("blank",),
    ("blank",),
    ("pc", [("b", "DEPARTMENT OF ECONOMICS")]),
    ("pc", [("b", "[NAME OF COLLEGE / UNIVERSITY]")]),
    ("pc", ["ANGUL, ODISHA"]),
    ("pc", ["2026"]),
]

CERTIFICATE = [
    ("pc", [("b", "CERTIFICATE")]),
    ("blank",),
    ("p", ["This is to certify that the field work report entitled “Microfinance "
           "Institutions and Self Help Group (SHG) Organisations in Pallahara” is a "
           "bona fide record of field work carried out by [Student's Name], Roll No. "
           "[Roll Number], a student of the Department of Economics, [Name of "
           "College/University], Angul, Odisha, under my supervision and guidance, in "
           "partial fulfilment of the requirements for the Degree of Bachelor/Master of "
           "Arts in Economics."]),
    ("p", ["The field work was conducted in Pallahara Block of Angul District, Odisha, "
           "and the data, observations and analysis presented in this report are the "
           "outcome of the candidate's own independent field investigation carried out "
           "under my supervision. This report, or any part thereof, has not been "
           "submitted elsewhere for the award of any other degree or diploma."]),
    ("p", ["I recommend that this report be placed before the examiners for evaluation "
           "towards the fulfilment of the requirement of the said degree."]),
    ("blank",),
    ("pl", ["Place: Angul"]),
    ("pl", ["Date: ______________"]),
    ("blank",),
    ("blank",),
    ("pl", ["___________________________"]),
    ("pl", ["(Signature of Supervisor)"]),
    ("pl", ["[Supervisor's Name]"]),
    ("pl", ["Professor of Economics"]),
    ("pl", ["Head, Department of Economics"]),
]

DECLARATION = [
    ("pc", [("b", "DECLARATION")]),
    ("blank",),
    ("p", ["I, [Student's Name], hereby declare that the field work report entitled "
           "“Microfinance Institutions and Self Help Group (SHG) Organisations in "
           "Pallahara” submitted by me to the Department of Economics, [Name of "
           "College/University], Angul, in partial fulfilment of the requirements for "
           "the award of the Degree of Bachelor/Master of Arts in Economics, is my own "
           "original work carried out under the supervision of [Supervisor's Name]."]),
    ("p", ["I further declare that the primary data presented in this report were "
           "collected by me through field visits, questionnaire survey, interviews and "
           "personal observation in Pallahara Block, and that the secondary data have "
           "been duly acknowledged and cited from their respective sources through "
           "footnotes and the bibliography appended at the end of this report. This work "
           "has not been submitted, in whole or in part, to any other University or "
           "Institution for the award of any degree, diploma, fellowship or similar "
           "title."]),
    ("blank",),
    ("pl", ["Place: Angul"]),
    ("pl", ["Date: ______________"]),
    ("blank",),
    ("blank",),
    ("pl", ["___________________________"]),
    ("pl", ["(Signature of the Candidate)"]),
    ("pl", ["[Student's Name]"]),
    ("pl", ["Roll No.: [Roll Number]"]),
]

ACKNOWLEDGEMENT = [
    ("pc", [("b", "ACKNOWLEDGEMENT")]),
    ("blank",),
    ("p", ["The completion of this field work report would not have been possible "
           "without the guidance, cooperation and encouragement extended to me by "
           "several individuals and institutions, to whom I remain sincerely grateful."]),
    ("p", ["I express my deepest gratitude to my supervisor, [Supervisor's Name], "
           "Professor of Economics, for his/her invaluable guidance, constant "
           "encouragement, scholarly supervision and patient support at every stage of "
           "this study, from the design of the field survey to the final preparation of "
           "this report. His/her insightful comments and constructive criticism helped "
           "me sharpen the focus and analytical rigour of this work."]),
    ("p", ["I am thankful to the Head of the Department of Economics and to all my "
           "teachers for providing the academic environment and encouragement necessary "
           "for undertaking this field-based study. I am also grateful to the Principal "
           "and administration of [Name of College/University] for granting permission "
           "and logistical support for the field visits to Pallahara."]),
    ("p", ["I place on record my sincere thanks to the Block Development Officer and "
           "staff of the Pallahara Block Office, the Block Mission Management Unit of "
           "the Odisha Livelihoods Mission (OLM), the Mission Shakti Block Coordinator, "
           "officials of the District Rural Development Agency (DRDA), Angul, and the "
           "branch officials of Annapurna Finance Pvt. Ltd. and various commercial banks "
           "operating in Pallahara, who patiently answered my questions and shared "
           "valuable secondary records during the course of my field work."]),
    ("p", ["My heartfelt thanks go to the office-bearers and members of the various Self "
           "Help Groups of Pallahara Block, including the women of the Juang tribal "
           "hamlets, who welcomed me into their meetings and generously gave their time "
           "to respond to my questionnaire and interviews. Without their openness and "
           "cooperation, this field work would not have been possible."]),
    ("p", ["Finally, I am indebted to my family and friends for their unfailing moral "
           "support and encouragement throughout the period of this study. Any errors or "
           "shortcomings that remain in this report are entirely my own."]),
    ("blank",),
    ("pl", ["[Student's Name]"]),
]

ABBREVIATIONS = [
    ("BDO", "Block Development Officer"),
    ("BLF", "Block Level Federation"),
    ("CBO", "Community-Based Organisation"),
    ("CIF", "Community Investment Fund"),
    ("DAY-NRLM", "Deendayal Antyodaya Yojana – National Rural Livelihoods Mission"),
    ("DRDA", "District Rural Development Agency"),
    ("GLP", "Gross Loan Portfolio"),
    ("GPLF", "Gram Panchayat Level Federation"),
    ("H2R", "Hard-to-Reach (area)"),
    ("ICDS", "Integrated Child Development Services"),
    ("JLG", "Joint Liability Group"),
    ("MFI", "Microfinance Institution"),
    ("NABARD", "National Bank for Agriculture and Rural Development"),
    ("NBFC-MFI", "Non-Banking Financial Company – Microfinance Institution"),
    ("NGO", "Non-Governmental Organisation"),
    ("NRLM", "National Rural Livelihoods Mission"),
    ("OLM", "Odisha Livelihoods Mission"),
    ("OPELIP", "Odisha PVTG Empowerment and Livelihoods Improvement Programme"),
    ("PVTG", "Particularly Vulnerable Tribal Group"),
    ("RBI", "Reserve Bank of India"),
    ("RF", "Revolving Fund"),
    ("SHG", "Self Help Group"),
    ("SHG-BLP", "Self Help Group – Bank Linkage Programme"),
    ("SHPI", "Self Help Promoting Institution"),
    ("VO", "Village Organisation"),
    ("WSHG", "Women Self Help Group"),
]

# --------------------------------------------------------------------------
# MAIN CONTENT — CHAPTERS 1 TO 8 (condensed to 30 pages)
# --------------------------------------------------------------------------

CHAPTERS = [

    # ---------------------------------------------------------------- CH 1
    ("h1", "CHAPTER 1: INTRODUCTION"),

    ("h2", "1.1 Background of the Study"),
    ("p", ["Rural credit has remained one of the most persistent challenges of Indian "
           "development planning. For decades after Independence, institutional credit "
           "agencies — cooperative banks, commercial banks and regional rural banks "
           "— failed to reach the poorest and most marginal households, who "
           "continued to depend on exploitative moneylenders charging usurious rates of "
           "interest. It was against this background that the Self Help Group (SHG) "
           "movement, promoted through the SHG-Bank Linkage Programme of the National "
           "Bank for Agriculture and Rural Development (NABARD), and the subsequent "
           "growth of Microfinance Institutions (MFIs), emerged as an alternative and "
           "complementary channel of rural financial inclusion.",
           ("fn", "NABARD. “SHG-Bank Linkage Programme.” nabard.org, National "
                  "Bank for Agriculture and Rural Development. Accessed 2026.")]),
    ("p", ["The State of Odisha occupies a distinctive place in this movement. It was "
           "the first State in the country to launch the National Rural Livelihoods "
           "Mission (NRLM), and it hosts one of the largest women's collective platforms "
           "in India in the form of Mission Shakti, a flagship programme of the "
           "Government of Odisha launched on 8 March 2001, International Women's Day, "
           "with the explicit objective of empowering women through Self Help Groups.",
           ("fn", "Government of Odisha, Department of Mission Shakti. “District "
                  "Page: Angul.” missionshakti.odisha.gov.in. Accessed 2026.")]),
    ("p", ["Angul district, situated in the geographical heart of Odisha, is popularly "
           "recognised as the industrial and mining hub of the State on account of its "
           "coal, power and aluminium industries. Yet within the same district lies "
           "Pallahara, a sub-divisional headquarters and erstwhile princely (Garhjat) "
           "state, whose economy remains predominantly agrarian and forest-dependent and "
           "whose hill tracts are home to the Juang, a Particularly Vulnerable Tribal "
           "Group (PVTG). It is in this contrast — an industrially advanced "
           "district containing a relatively underdeveloped, tribal-dominated "
           "sub-division — that the present field work situates its enquiry, with "
           "the object of understanding at close quarters how SHGs are formed and "
           "function in Pallahara Block, how microfinance institutions and banks "
           "interact with them, and what difference, if any, this institutional "
           "architecture of thrift and credit has made to the lives of the block's rural "
           "and tribal women.",
           ("fn", "“Angul District.” Wikipedia, Wikimedia Foundation. Accessed "
                  "2026; and “Pallahara.” Wikipedia, Wikimedia Foundation. "
                  "Accessed 2026.")]),

    ("h2", "1.2 Microfinance and SHGs: A Conceptual Backdrop"),
    ("p", ["Microfinance, in its broadest sense, refers to the provision of small-value "
           "financial services — savings, credit, insurance and remittance "
           "facilities — to low-income households that are excluded from the formal "
           "banking system on account of their inability to offer collateral or meet "
           "documentation requirements. The Self Help Group is the principal vehicle "
           "through which microfinance is delivered in the Indian context: a voluntary "
           "association of ten to twenty persons, generally women from similar "
           "socio-economic backgrounds, who pool small periodic savings into a common "
           "fund from which members may borrow for consumption or productive purposes, "
           "and which is subsequently linked to the formal banking system for larger "
           "credit. Well-functioning SHGs are expected to observe what NABARD terms the "
           "‘Panchsutra’ or five principles — regular group meetings, "
           "regular savings, internal lending based on members' demand, timely recovery "
           "of loans, and up-to-date maintenance of books of accounts. Microfinance "
           "Institutions, on the other hand, are typically registered as Non-Banking "
           "Financial Companies (NBFC-MFIs) under the regulatory oversight of the "
           "Reserve Bank of India, and extend credit either directly to individual "
           "borrowers through the Joint Liability Group (JLG) model or indirectly "
           "through partnership with SHGs.",
           ("fn", "Government of Odisha. “Mission Shakti Odisha — SHG "
                  "Eligibility and Formation.” missionshakti.odisha.gov.in, via "
                  "CitizenNest Editorial Team, “Mission Shakti Odisha: How to "
                  "Apply, Eligibility & Benefits.” citizennest.com, 15 June 2026; "
                  "and NABARD, “SHG-Bank Linkage Programme,” nabard.org.")]),

    ("h2", "1.3 Statement of the Problem"),
    ("p", ["While the aggregate, State-level achievements of Mission Shakti and the "
           "SHG-Bank Linkage Programme in Odisha are well documented and widely "
           "publicised, there is comparatively little block-level, field-based "
           "understanding of how these institutions actually function in a mixed "
           "agrarian-cum-tribal sub-division such as Pallahara. Questions of practical "
           "importance remain under-examined at this micro level: What proportion of "
           "eligible households in Pallahara are actually covered by SHGs? What is the "
           "relative role of Mission Shakti/OLM-promoted SHGs vis-à-vis private "
           "microfinance institutions such as Annapurna Finance in meeting the credit "
           "needs of the population? Do the special vulnerabilities of the Juang PVTG "
           "population of the block call for a different microfinance approach than that "
           "adopted for the general population? It is these questions that the present "
           "field work seeks to address."]),

    ("h2", "1.4 Significance of the Study"),
    ("p", ["A field-level study of this kind is significant for three reasons. First, it "
           "contributes to the sparse body of literature specifically focused on Angul "
           "district and, more particularly, on Pallahara, most of the available "
           "published material on Odisha's SHG movement being concentrated on the KBK "
           "(Kalahandi-Bolangir-Koraput) region, Mayurbhanj and Koraput.",
           ("fn", "See, for instance, Kumar and Nayak's study of Karanjia block, "
                  "Mayurbhanj district, and the IIPA evaluation of Mission Shakti in the "
                  "KBK region, both discussed in Chapter 2."),
           " Second, it offers policy-relevant insight for the District Rural "
           "Development Agency (DRDA), Angul, the Odisha Livelihoods Mission and the "
           "Mission Shakti Department in fine-tuning block-level strategy, particularly "
           "for the PVTG hamlets of Pallahara that fall within the ambit of the Odisha "
           "PVTG Empowerment and Livelihoods Improvement Programme (OPELIP). Third, for "
           "the discipline of Economics, the study is a worked example of how national "
           "and State-level financial inclusion policy translates — or fails to "
           "translate — into ground-level outcomes for a specific, geographically "
           "bounded population."]),

    ("h2", "1.5 Objectives of the Study"),
    ("p", ["The field work has been designed around the following specific objectives:"]),
    ("num", ["To study the socio-economic and demographic profile of Pallahara Block, "
             "Angul district, with particular reference to the population dependent on "
             "agriculture and forest-based livelihoods."]),
    ("num", ["To trace the growth, structure and institutional architecture of Self Help "
             "Groups promoted under Mission Shakti and the Odisha Livelihoods Mission "
             "(OLM) in Angul district and Pallahara Block in particular."]),
    ("num", ["To identify and examine the microfinance institutions, banks and other "
             "credit agencies operating in Pallahara and to assess the pattern of credit "
             "linkage extended to SHGs."]),
    ("num", ["To examine, through field visits and case studies, the impact of SHG "
             "membership and microfinance access on the income, savings, credit access "
             "and social empowerment of member households, including PVTG (Juang) "
             "households."]),
    ("num", ["To identify the problems and constraints faced by SHGs and microfinance "
             "institutions in Pallahara and to suggest measures for strengthening the "
             "microfinance-SHG ecosystem of the block."]),
    ("table", "Table 1.1: Objectives and Corresponding Research Methods",
     [["Objective", "Principal Method", "Principal Source"],
      ["1. Socio-economic and demographic profile of the block",
       "Secondary data compilation",
       "Census of India 2011 (tehsil and village data); district website, Angul"],
      ["2. Growth and institutional architecture of SHGs",
       "Secondary records and institutional interview",
       "Mission Shakti and OLM records; Block Mission Management Unit, Pallahara"],
      ["3. Microfinance institutions and pattern of credit linkage",
       "Institutional interview and field observation",
       "Bank and NBFC-MFI branch officials; block functionaries"],
      ["4. Impact on income, savings, credit access and empowerment",
       "Structured interview schedule and case study",
       "Sample SHG members and office-bearers (Appendix I); published case material"],
      ["5. Problems, constraints and suggestions",
       "Open-ended interview and ranking",
       "SHG members; comparative Odisha literature (Chapter 2)"]],
     "Note: Prepared by the researcher to link each stated objective to the method and "
     "source of evidence on which the corresponding analysis rests."),

    ("h2", "1.6 Research Methodology"),
    ("h3", "1.6.1 Research Design and Sources of Data"),
    ("p", ["The study adopts a descriptive-cum-analytical research design combining "
           "secondary and primary sources of data, situating primary field observations "
           "from Pallahara within the wider statistical and policy framework furnished "
           "by NABARD, the Reserve Bank of India, the Sa-Dhan network of microfinance "
           "institutions, the Mission Shakti Department, the Odisha Livelihoods Mission "
           "and the Census of India. Secondary data have been drawn from official and "
           "authenticated sources, including NABARD's ",
           ("i", "Status of Microfinance in India"),
           " reports, Sa-Dhan's ", ("i", "Bharat Microfinance Report"),
           ", publications of OLM and the Mission Shakti Department, the district "
           "website of Angul, Census of India 2011 village and tehsil-level data, and "
           "peer-reviewed research on the SHG movement in Odisha. Primary field data "
           "were collected through structured interviews and interaction with "
           "functionaries of the Pallahara Block Office, the Block Mission Management "
           "Unit of OLM, the Mission Shakti Block Coordinator, branch officials of banks "
           "and microfinance institutions operating in the block, and office-bearers and "
           "members of sample SHGs, using the interview schedule appended to this report "
           "(Appendix I).",
           ("fn", "All secondary sources are cited in the footnotes and listed in the "
                  "Bibliography appended to this report.")]),
    ("h3", "1.6.2 Sampling Design and Tools of Analysis"),
    ("p", ["Given the constraints of time and resources typical of a field work "
           "exercise, the study employs a purposive-cum-random sampling design. A set of "
           "Gram Panchayats of Pallahara Block, including panchayats with concentrations "
           "of Juang PVTG hamlets, were purposively selected to ensure representation of "
           "both the general rural population and the tribal population; within these "
           "panchayats, SHGs and respondent members were selected at random for detailed "
           "interview. Simple statistical tools — percentages, averages, and "
           "tabular and cross-tabular presentation — have been used to analyse the "
           "field data, supplemented by qualitative case-study material collected during "
           "the field visits."]),

    ("h2", "1.7 Scope of the Study"),
    ("p", ["The study is geographically confined to Pallahara Block (Pallahara Tehsil) "
           "of Angul district, Odisha, though wherever necessary the analysis situates "
           "Pallahara within the district, State and national context. Substantively, it "
           "covers both Mission Shakti/OLM-promoted Women Self Help Groups and privately "
           "promoted microfinance institutions operating in the block, along with the "
           "commercial banking network that extends SHG-Bank Linkage credit."]),

    ("h2", "1.8 Limitations of the Study"),
    ("bullet", ["The field work was carried out over a limited period and could not "
                "cover every Gram Panchayat of Pallahara Block; the sample is therefore "
                "illustrative rather than exhaustive."]),
    ("bullet", ["Financial and operational details of individual microfinance "
                "institutions are, in several cases, treated as confidential business "
                "information by branch functionaries and could not always be "
                "independently verified."]),
    ("bullet", ["Recall-based responses of SHG members regarding income and expenditure "
                "are subject to the usual limitations of memory and possible under- or "
                "over-reporting."]),
    ("bullet", ["Census and tehsil-level demographic data used here are drawn from "
                "Census 2011, the latest decennial Census available in the public "
                "domain, and may not fully capture more recent demographic change."]),

    ("h2", "1.9 Chapter Scheme"),
    ("p", ["This report is organised into eight chapters. Chapter 1 introduces the "
           "study, its objectives and methodology. Chapter 2 reviews the existing "
           "literature on microfinance and SHGs, with particular reference to Odisha. "
           "Chapter 3 profiles Angul district and Pallahara Block. Chapter 4 sets out "
           "the conceptual and institutional framework of microfinance and the SHG "
           "movement in India and Odisha. Chapter 5 presents field-level observations on "
           "microfinance institutions and SHGs operating in Pallahara. Chapter 6 "
           "analyses and interprets the field data. Chapter 7 presents case studies "
           "drawn from the field. Chapter 8 summarises the findings, offers suggestions "
           "and concludes the report, followed by the Bibliography and Appendix."]),

    # ---------------------------------------------------------------- CH 2
    ("h1", "CHAPTER 2: REVIEW OF LITERATURE"),
    ("p", ["A review of the available literature is necessary to situate the present "
           "field work within the broader body of research on microfinance, Self Help "
           "Groups and women's empowerment, and to identify the specific gap that a "
           "Pallahara-focused study seeks to fill."]),

    ("h2", "2.1 Microfinance and Women's Empowerment: General Literature"),
    ("p", ["Mayoux, in an early and influential review for the International Labour "
           "Organisation, cautioned against the simplistic, linear assumption that "
           "access to microfinance automatically translates into women's empowerment, "
           "arguing that the relationship is mediated by intra-household bargaining "
           "power, control over loan use, and the broader social context in which SHGs "
           "operate.",
           ("fn", "Mayoux, Linda. “Micro-Finance and the Empowerment of Women: A "
                  "Review of the Key Issues.” International Labour Organisation, "
                  "2000."),
           " Subsequent large-sample work has lent nuance to this debate. Bali Swain and "
           "Wallentin, examining regional and delivery-mechanism differences within "
           "India's SHG programme, found that empowerment effects vary significantly by "
           "region and by the institutional channel — bank-linked SHG, MFI-linked "
           "SHG or NGO-facilitated SHG — through which credit is delivered.",
           ("fn", "Bali Swain, Ranjula, and Fan Yang Wallentin. “The Impact of "
                  "Microfinance on Factors Empowering Women: Differences in Regional and "
                  "Delivery Mechanisms in India's SHG Programme.” The Journal of "
                  "Development Studies, vol. 53, no. 5, 2016, pp. 684-699.")]),
    ("p", ["A recent synthesis in the ", ("i", "Journal of Global Entrepreneurship "
           "Research"),
           " examines how microfinance participation influences women's decision-making "
           "ability through a sequential mediation of economic outcomes (income, "
           "savings, asset ownership) and social outcomes (mobility, self-confidence, "
           "social participation), concluding that both channels operate together rather "
           "than in isolation.",
           ("fn", "“Microfinance and Women's Empowerment: Sequential Mediation of "
                  "Economic and Social Outcomes on Decision-Making Ability.” "
                  "Journal of Global Entrepreneurship Research, Springer Nature."),
           " Similarly, Pandhare and co-authors, studying rural Indian SHGs, report that "
           "microfinance and entrepreneurial engagement together improved household "
           "consumption, income and savings, with case narratives illustrating a "
           "transition from dependency to assertive, financially independent "
           "decision-making within the household.",
           ("fn", "Pandhare, et al. “Transforming Rural Women's Lives in India: The "
                  "Impact of Microfinance and Entrepreneurship on Empowerment in "
                  "Self-Help Groups.” Journal of Innovation and Entrepreneurship, "
                  "vol. 13, no. 62, 2024, pp. 5-10.")]),

    ("h2", "2.2 Studies Specific to Odisha"),
    ("p", ["Within Odisha, Chaudhury and Misra examined the role of SHGs in promoting "
           "self-reliance across selected districts of the State, finding a generally "
           "positive association between SHG membership and members' sense of financial "
           "self-reliance, though the strength of this association varied by district and "
           "by the maturity of the group.",
           ("fn", "Chaudhury, S. K., and D. P. Misra. “Role of Self Help Groups in "
                  "Promoting Self-Reliance among Its Members: A Study of Selected "
                  "Districts of Odisha.” Sumedha Journal of Management, vol. 7, no. "
                  "3, 2018, pp. 130-143."),
           " Kumar and Nayak, working with a random cross-sectional sample of households "
           "in Karanjia block of Mayurbhanj district — a district bordering Angul "
           "and sharing similar tribal and forest-based livelihood characteristics "
           "— reported, using multinomial logistic regression, that the social "
           "empowerment of women SHG members was significantly influenced by their level "
           "of education, and that SHG participation was associated with a discernible "
           "increase in household income, expenditure and savings.",
           ("fn", "Kumar, and Nayak. “Women Empowerment through Self Help Groups in "
                  "Odisha: A Micro Evidence from Mayurbhanj District.” 2021, as "
                  "cited in the ResearchGate publication database.")]),
    ("p", ["An evaluation of Mission Shakti's impact in the KBK region (Kalahandi, "
           "Bolangir, Koraput and adjoining districts), undertaken for the Indian "
           "Institute of Public Administration, found that about a third of Mission "
           "Shakti SHG members belonged to the younger 18-30 age group, that roughly "
           "seventy per cent of members were literate and aware of government schemes, "
           "and that more than three-fourths belonged to Below Poverty Line or Antyodaya "
           "households — underlining the pro-poor targeting achieved by the "
           "programme, even as it pointed to continuing gaps in the organisational depth "
           "of the groups.",
           ("fn", "Indian Institute of Public Administration. “Evaluation of the "
                  "Impact of Mission Shakti in Women Empowerment in KBK.” "
                  "iipa.org.in, Indian Institute of Public Administration, New Delhi.")]),
    ("p", ["Focusing on the difficulties of the SHG model, Baishya, Sarkar and Argade "
           "studied women's participation and drop-out from SHGs in Koraput district, "
           "documenting how logistical difficulties, indebtedness and weak internal "
           "governance contribute to member attrition — a caution against an "
           "uncritical, celebratory reading of the movement's aggregate statistics.",
           ("fn", "Baishya, M., A. Sarkar, and S. Argade. “Problems Concerning "
                  "Women's Participation and Dropout from Self Help Groups in Koraput "
                  "District of Odisha, India.” International Journal of Current "
                  "Microbiology and Applied Sciences, vol. 9, no. 6, 2020, pp. "
                  "3180-3186."),
           " Mohanty, Das and Mohanty, examining capacity building and decision-making "
           "among rural Odisha women engaged in micro-enterprises, similarly found that "
           "SHG-linked micro-enterprise participation enhanced women's role in household "
           "decision-making, though this effect was contingent on the group sustaining a "
           "viable income-generating activity beyond mere thrift and credit.",
           ("fn", "Mohanty, S., B. Das, and T. Mohanty. “Capacity Building and "
                  "Decision of Rural Odisha Women through Participation in "
                  "Microenterprises.” International Journal of Scientific and "
                  "Research Publications, vol. 3, no. 7, 2013, pp. 1-8.")]),

    ("h2", "2.3 Studies on Tribal and PVTG Populations"),
    ("p", ["A study on the financial inclusion of Particularly Vulnerable Tribal Groups "
           "in Maharashtra — though outside Odisha — identifies obstacles of "
           "direct relevance to Pallahara's Juang population: language barriers where "
           "banking material is unavailable in tribal dialects, patriarchal resistance to "
           "women's participation in SHGs, and infrastructural gaps that leave PVTG "
           "households more likely to be excluded even where general SHG coverage in the "
           "district is high.",
           ("fn", "Empowering Tribal Women: Comprehensive Financial Inclusion for PVTGs "
                  "in Maharashtra. ResearchGate, 2025."),
           " Literature specific to the Juang — the PVTG concentrated in Keonjhar "
           "district with a smaller population straddling Pallahara block of Angul and "
           "parts of Dhenkanal — documents a historical dependence on shifting "
           "(",
           ("i", "poddu"),
           ") cultivation and forest produce among the Hill Juang, in contrast to the "
           "more settled agriculture of the Plains Juang, a distinction directly relevant "
           "to the design of appropriate income-generating activities for SHGs formed in "
           "Pallahara's tribal hamlets.",
           ("fn", "“Juang People.” Grokipedia. Accessed 2026; and Sahoo, et "
                  "al. “Menstrual Health and Hygiene among Juang Women: A "
                  "Particularly Vulnerable Tribal Group in Odisha, India.” PMC, "
                  "National Center for Biotechnology Information.")]),

    ("h2", "2.4 Research Gap"),
    ("p", ["The foregoing review indicates a well-developed body of literature at the "
           "national and State level, and district-level studies for Mayurbhanj, Koraput "
           "and the KBK region. However, no dedicated, block-level field study appears "
           "to be available specifically for Pallahara Block of Angul district that "
           "examines, side by side, both Mission Shakti/OLM-promoted SHGs and privately "
           "operated microfinance institutions, with explicit attention to the PVTG "
           "(Juang) population of the block. The present field work is intended as a "
           "modest contribution towards filling this gap."]),
    ("p", ["Two features of the literature shape the design of the present study. First, "
           "because the empowerment effect of microfinance is repeatedly shown to be "
           "conditional rather than automatic, the field enquiry has been framed to "
           "record not only whether members report change but also the conditions under "
           "which they do so — length of membership, position in the group, and "
           "whether the group has actually secured bank credit linkage. Second, because "
           "the Odisha literature consistently reports variation by district and by group "
           "maturity, the findings of this study are read throughout against comparable "
           "published evidence from Mayurbhanj, Koraput and the KBK region rather than "
           "presented in isolation, so that what is distinctive about Pallahara can be "
           "distinguished from what is common to the State as a whole."]),

    # ---------------------------------------------------------------- CH 3
    ("h1", "CHAPTER 3: PROFILE OF THE STUDY AREA — ANGUL DISTRICT AND PALLAHARA "
           "BLOCK"),

    ("h2", "3.1 Odisha: A Brief Backdrop"),
    ("p", ["Odisha, located on the eastern seaboard of India, is administratively "
           "organised into thirty districts. Rural poverty, forest dependence and a "
           "substantial tribal population — the State is home to sixty-two "
           "recognised tribal communities including thirteen of the country's "
           "Particularly Vulnerable Tribal Groups — have historically made Odisha a "
           "priority area for anti-poverty and livelihood promotion programmes, "
           "including the SHG-Bank Linkage Programme and, more recently, the National "
           "Rural Livelihoods Mission, of which Odisha was the first State to launch an "
           "implementation.",
           ("fn", "Government of Odisha, Odisha Livelihoods Mission. “Welcome to "
                  "Odisha Livelihoods Mission.” olm.nic.in. Accessed 2026.")]),

    ("h2", "3.2 Angul District: Administrative and Demographic Profile"),
    ("p", ["Angul district was carved out of the undivided Dhenkanal district on 1 April "
           "1993, with Angul town as its headquarters. It comprises four sub-divisions "
           "— Angul (Sadar), Athamallik, Talcher and Pallahara — and covers "
           "6,232 square kilometres in the centre of the State, bordered by Cuttack, "
           "Sundargarh, Kendujhar (Keonjhar), Sambalpur, Deogarh and Dhenkanal "
           "districts, and by Nayagarh and Boudh across the Mahanadi river. As per the "
           "Census of India 2011, the district had a population of 12,73,821 persons, of "
           "whom Scheduled Castes numbered about 2.39 lakh and Scheduled Tribes about "
           "1.79 lakh, with a literacy rate of 78.96 per cent and a sex ratio of 942 "
           "females per 1,000 males; roughly one-sixth of the population is classified "
           "as urban, concentrated mainly around the industrial townships of Angul and "
           "Talcher.",
           ("fn", "Government of Odisha, District Administration, Angul. “About "
                  "Us.” angul.odisha.gov.in. Accessed 2026; and “Angul "
                  "District,” Wikipedia, Wikimedia Foundation.")]),
    ("table", "Table 3.1: Administrative and Demographic Profile of Angul District",
     [["Indicator", "Figure"],
      ["Year of formation", "1 April 1993"],
      ["Area", "6,232 sq. km"],
      ["Number of sub-divisions", "4 (Angul, Athamallik, Talcher, Pallahara)"],
      ["Number of blocks / Tahsils", "8 / 8"],
      ["Number of Gram Panchayats", "225"],
      ["Number of villages", "1,930"],
      ["Municipalities / NAC", "2 Municipalities, 1 NAC"],
      ["Total population (Census 2011)", "12,73,821"],
      ["Scheduled Caste population", "≈ 2.39 lakh"],
      ["Scheduled Tribe population", "≈ 1.79 lakh"],
      ["Literacy rate", "78.96%"],
      ["Sex ratio", "942 females / 1,000 males"]],
     "Sources: Government of Odisha, District Administration, Angul, “About "
     "Us”; Wikipedia, “Angul District.”"),

    ("h2", "3.3 Pallahara: Historical Background"),
    ("p", ["Pallahara (also spelt Pal Lahara) is a small town and sub-divisional "
           "headquarters of Angul district, situated at the junction of National Highway "
           "6 and National Highway 23, about ninety-one kilometres by road north of "
           "Angul town. Historically it was the capital of Pal Lahara State, one of the "
           "small princely states (Garhjats) of British India that, along with Athamallik "
           "and Talcher, was merged into Dhenkanal district after Independence and "
           "subsequently constituted a sub-division of the newly created Angul district "
           "in 1993. The forests around Pallahara have traditionally been rich in bamboo, "
           "and the local Juang population is noted for its skill in bamboo basketry, a "
           "craft that continues to hold potential as a livelihood activity for "
           "SHG-linked income generation.",
           ("fn", "“Pallahara.” Wikipedia, Wikimedia Foundation, citing "
                  "Patnaik, 1997; and “Angul District,” Wikipedia, Wikimedia "
                  "Foundation.")]),

    ("h2", "3.4 Pallahara Tehsil: Demographic Profile"),
    ("p", ["As per Census 2011, Pallahara Tehsil (which broadly corresponds to Pallahara "
           "Block for revenue administration purposes) had a total population of 66,385 "
           "persons — 33,728 males and 32,657 females — residing in 15,686 "
           "households spread over 327 villages, at an average density of 97 persons per "
           "square kilometre. The sex ratio of the tehsil, at approximately 968 females "
           "per 1,000 males, is notably more favourable to women than both the district "
           "and State averages, a feature often associated with tribal-dominated "
           "administrative units in Odisha. The tehsil recorded 37,556 literate persons "
           "against 28,829 illiterate persons, and its Scheduled Caste and Scheduled "
           "Tribe populations stood at 8,268 and 31,843 respectively — meaning that "
           "Scheduled Tribes alone constitute close to half of the tehsil's population, a "
           "proportion far higher than the district average, underlining the centrality "
           "of tribal welfare and livelihood concerns to any development intervention in "
           "Pallahara.",
           ("fn", "VillageInfo.in. “List of Villages in Pallahara Tehsil, Angul, "
                  "Odisha.” villageinfo.in. Accessed 2026, citing Census of India "
                  "2011.")]),
    ("table", "Table 3.2: Demographic Profile of Pallahara Tehsil (Census 2011)",
     [["Indicator", "Figure"],
      ["Total population", "66,385"],
      ["Male / Female population", "33,728 / 32,657"],
      ["Total households", "15,686"],
      ["Number of villages", "327"],
      ["Total area", "686 sq. km (677.78 rural + 8.22 urban)"],
      ["Population density", "97 persons / sq. km"],
      ["Sex ratio", "968 females / 1,000 males"],
      ["Children (0-6 years)", "10,046"],
      ["Literate / Illiterate population", "37,556 / 28,829"],
      ["Scheduled Caste population", "8,268"],
      ["Scheduled Tribe population", "31,843"]],
     "Source: VillageInfo.in, “List of Villages in Pallahara Tehsil, Angul, "
     "Odisha,” based on Census of India 2011."),

    ("h2", "3.5 Tribal Composition: The Juang PVTG"),
    ("p", ["Pallahara block falls within the traditional habitat of the Juang, one of "
           "Odisha's thirteen Particularly Vulnerable Tribal Groups. According to Census "
           "2011 the total Juang population was approximately 47,095, concentrated "
           "mainly in Keonjhar and Dhenkanal districts, and divided into two ecological "
           "sub-groups: the Hill Juang, confined to the hill ranges of Keonjhar and "
           "Pallahara and still substantially dependent on shifting cultivation, and the "
           "Plains Juang of Dhenkanal and Keonjhar, who have adopted settled agriculture. "
           "The Juang are classified as a PVTG on account of pre-agricultural technology, "
           "low literacy and a stagnant or slow-growing population — characteristics "
           "that place special demands on the design of SHG-based livelihood "
           "interventions, since standard microfinance products calibrated for a settled "
           "agrarian population may not suit households still substantially dependent on "
           "forest produce and shifting cultivation.",
           ("fn", "Sahoo, et al. “Menstrual Health and Hygiene among Juang Women: A "
                  "Particularly Vulnerable Tribal Group in Odisha, India.” PMC, "
                  "National Center for Biotechnology Information; and webapps.ifad.org, "
                  "“Odisha PVTG Empowerment and Livelihoods Improvement Programme "
                  "— II: Project Design Report,” IFAD, 2023.")]),
    ("p", ["In recognition of this, the Odisha PVTG Empowerment and Livelihoods "
           "Improvement Programme (OPELIP), supported by the International Fund for "
           "Agricultural Development and the Government of Odisha's ST & SC Development "
           "Department, operates a dedicated Micro Project Agency for Pallahara block "
           "— the Paudi Bhuyan Development Agency, headquartered at Jamardihi "
           "— one of seventeen such agencies operating across twelve "
           "PVTG-concentrated districts of the State, including Angul.",
           ("fn", "“Odisha PVTG Empowerment & Livelihoods Improvement "
                  "Programme.” otelp.org, list of Micro Project Agencies by district "
                  "and block.")]),

    ("h2", "3.6 Economic Profile of Pallahara Block"),
    ("p", ["The economy of Pallahara block is overwhelmingly agrarian and forest-based. "
           "Rice remains the dominant crop; in the hill tracts inhabited by the Hill "
           "Juang, cultivation is supplemented by minor forest produce collection — "
           "mahua flowers, tubers, wild fruits and bamboo — which forms an "
           "important, if seasonal and low-value, component of household income. Given "
           "the town's location at the crossing of NH6 and NH23, some trade and "
           "transport-related opportunity exists in and around Pallahara town, but the "
           "block as a whole has limited industrial or large-scale commercial activity "
           "compared with the Angul (Sadar) or Talcher sub-divisions, which host the "
           "district's coal, power and aluminium industries. It is precisely this "
           "combination of agrarian dependence, forest-based livelihoods and a "
           "substantial PVTG population, coupled with limited alternative wage "
           "employment, that makes access to institutional thrift and credit through SHGs "
           "and microfinance institutions of particular economic significance for the "
           "households of the block — the central concern of the chapters that "
           "follow.",
           ("fn", "“Pallahara.” Wikipedia, Wikimedia Foundation.")]),
    ("p", ["Two implications follow for the microfinance-SHG ecosystem examined in this "
           "report. First, an economy resting on rain-fed paddy and seasonal forest "
           "collection generates a highly uneven flow of household income across the "
           "year, so that the value of SHG membership to a member lies at least as much "
           "in consumption-smoothing between harvests as in the financing of enterprise "
           "— a pattern borne out by the loan-purpose evidence presented in section "
           "6.2. Second, the same seasonality drives out-migration for wage labour, which "
           "in turn bears directly on meeting attendance and repayment discipline, and "
           "hence on the pace at which a group can qualify for bank credit linkage. The "
           "economic structure of the block is therefore not merely background to the "
           "study but one of the operative explanations for the constraints reported by "
           "SHG members in Chapter 6."]),

    # ---------------------------------------------------------------- CH 4
    ("h1", "CHAPTER 4: CONCEPTUAL AND INSTITUTIONAL FRAMEWORK OF MICROFINANCE AND SHGS"),

    ("h2", "4.1 The Microfinance Sector in India"),
    ("p", ["As noted in section 1.2, microfinance denotes a range of financial services "
           "extended in small denominations to low-income clients who lack access to "
           "conventional banking. In India the sector today comprises NBFC-MFIs, Small "
           "Finance Banks, commercial banks operating SHG-Bank Linkage portfolios, and a "
           "residual set of not-for-profit providers, all functioning under the "
           "regulatory oversight of the Reserve Bank of India and coordinated through "
           "self-regulatory organisations such as the Microfinance Institutions Network "
           "(MFIN) and Sa-Dhan. As of 31 March 2025, the sector served over 8 crore "
           "clients across 28 States, 8 Union Territories and 723 districts, with a gross "
           "loan portfolio of approximately ₹3.81 lakh crore, contributing an "
           "estimated 2-3 per cent to the country's Gross Value Added and supporting "
           "roughly 1.3 crore livelihoods. NBFC-MFIs account for the largest share of "
           "this portfolio, followed by banks and Small Finance Banks.",
           ("fn", "Brickwork Ratings. “Microfinance Sector in India.” "
                  "brickworkratings.com, 28 May 2025.")]),

    ("h2", "4.2 The Self Help Group: Formation, Grading and Credit Linkage"),
    ("p", ["The SHG, defined in section 1.2, is not merely a savings club but an "
           "institution with a defined life-cycle. A group is first formed by a Self Help "
           "Promoting Institution — in Odisha usually Mission Shakti, OLM or an NGO "
           "— and then builds a corpus through fixed periodic member savings. After "
           "roughly six months of demonstrated savings discipline the group is graded "
           "against the Panchsutra norms and becomes eligible for bank credit linkage, "
           "first through a small cash-credit limit and, as repayment performance is "
           "established, through progressively larger doses of credit, supplemented under "
           "DAY-NRLM by a Revolving Fund and a Community Investment Fund. It is the "
           "quality of this graduation process, rather than the mere act of group "
           "formation, that determines whether an SHG becomes a durable financial "
           "institution for its members.",
           ("fn", "NABARD. “SHG-Bank Linkage Programme.” nabard.org; and "
                  "“Odisha Mission Shakti 2026 — 70 Lakh Women in SHGs.” "
                  "schemesinindia.in.")]),

    ("h2", "4.3 Evolution and Growth of the SHG-Bank Linkage Programme"),
    ("p", ["The SHG-Bank Linkage Programme (SHG-BLP) began as a modest pilot initiative "
           "of NABARD in 1992-93, linking around 500 SHGs to the formal banking system. "
           "Over three decades it has grown into what NABARD itself describes as the "
           "largest microfinance programme in the world in terms of client base and "
           "outreach. By 2016-17 the programme covered ten crore families through "
           "eighty-five lakh SHGs, with aggregate savings deposits of ₹16,114 crore "
           "and 48.4 lakh credit-linked groups. By 31 March 2023, bank loans outstanding "
           "against SHGs stood at ₹1.88 lakh crore, averaging ₹2.70 lakh per "
           "SHG (up from ₹2.24 lakh in 2021-22), and by 31 March 2024 the average "
           "had risen to ₹3.35 lakh per SHG, with the sector's total loans "
           "outstanding reaching ₹4.09 lakh crore, a year-on-year growth of sixteen "
           "per cent. NABARD's own reports nevertheless flag a persistent regional "
           "imbalance, with the Southern and Eastern regions dominating credit offtake, "
           "and an overall credit-linkage gap of around forty-six to forty-eight per cent "
           "between SHGs that are savings-linked and those actually extended bank credit.",
           ("fn", "NABARD. Status of Microfinance in India 2022-23 and Status of "
                  "Microfinance in India 2023-24. nabard.org.")]),
    ("table", "Table 4.1: Growth of the SHG-Bank Linkage Programme in India (Select "
              "Indicators)",
     [["Year", "Key Indicator", "Figure"],
      ["1992-93", "SHGs linked to banks (pilot)", "≈ 500"],
      ["2016-17", "Families covered", "10 crore, through 85 lakh SHGs"],
      ["2022-23", "Average bank loan outstanding per SHG", "₹2.70 lakh"],
      ["2023-24", "Average bank loan outstanding per SHG", "₹3.35 lakh"],
      ["2023-24", "Total microfinance loans outstanding (sector)",
       "₹4.09 lakh crore (+16% y-o-y)"],
      ["31 Mar 2025", "Sector gross loan portfolio (all lenders)",
       "₹3.81 lakh crore, 8 crore+ clients"]],
     "Sources: NABARD, Status of Microfinance in India (2016-17, 2022-23, 2023-24); "
     "Brickwork Ratings, Microfinance Sector in India (2025)."),

    ("h2", "4.4 Institutional Architecture under DAY-NRLM"),
    ("p", ["The Deendayal Antyodaya Yojana – National Rural Livelihoods Mission "
           "(DAY-NRLM), implemented in Odisha through the Odisha Livelihoods Mission, "
           "envisages a layered institutional architecture of the rural poor: the SHG at "
           "the base, federated into Village Organisations or Gram Panchayat Level "
           "Federations (GPLFs), which are in turn federated into Block Level Federations "
           "(BLFs). This structure is designed to give members a collective voice and "
           "access to pooled resources — the Community Investment Fund and Revolving "
           "Fund — that reduce dependence on moneylenders and, over time, on "
           "external agencies altogether. At the district level, implementation is "
           "overseen by a District Mission Management Unit functioning under the DRDA, "
           "while a Block Mission Management Unit is responsible for implementation at "
           "the block level — the level at which the Pallahara field observations "
           "reported in Chapter 5 were made.",
           ("fn", "Department of Rural Development, Government of India. National Rural "
                  "Livelihood Mission: Manual for District Poverty Reduction. "
                  "darpg.gov.in, 2017; and Government of Odisha, District "
                  "Administration, Angul, “District Rural Development Agency "
                  "(DRDA),” angul.nic.in.")]),

    ("h2", "4.5 Mission Shakti: Odisha's Flagship SHG Programme"),
    ("p", ["Mission Shakti was launched by the Government of Odisha on 8 March 2001 with "
           "the explicit objective of empowering women through Self Help Groups, and has "
           "since been elevated to the status of a full-fledged Mission Shakti "
           "Department, making Odisha one of the first States in India to create a "
           "dedicated department for women's SHGs. Its growth has been striking: from "
           "41,475 Women Self Help Groups (WSHGs) in 2001-02, the number grew roughly "
           "fourteen-fold to 6,02,013 by 2020-21, an achievement described by an IIPA "
           "evaluation as having brought over six lakh SHGs and seventy lakh women of "
           "Odisha within its fold. More recent communications describe the network as "
           "covering over seventy lakh women across seven lakh SHGs statewide, offering "
           "SHG microfinance loans of between ₹1 lakh and ₹5 lakh together with "
           "a non-repayable enterprise grant of ₹25,000, alongside skill training "
           "and market-linkage support, with priority extended to Below Poverty Line "
           "families, Scheduled Caste/Scheduled Tribe women, widows and single women.",
           ("fn", "Government of Odisha, Department of Mission Shakti, official portal, "
                  "missionshakti.odisha.gov.in; Indian Institute of Public "
                  "Administration, “Evaluation of the Impact of Mission Shakti in "
                  "Women Empowerment in KBK,” iipa.org.in; and “Odisha Mission "
                  "Shakti 2026 — 70 Lakh Women in SHGs,” schemesinindia.in.")]),
    ("table", "Table 4.2: Growth of Mission Shakti Women Self Help Groups (WSHGs) in "
              "Odisha",
     [["Period", "Number of WSHGs", "Approx. Women Members"],
      ["2001-02 (launch year)", "41,475", "—"],
      ["2020-21", "6,02,013", "≈ 70 lakh (cumulative)"],
      ["2026 (current)", "≈ 7,00,000", "≈ 70,00,000"]],
     "Sources: IIPA evaluation of Mission Shakti impact in the KBK region; "
     "schemesinindia.in, “Odisha Mission Shakti 2026.”"),

    ("h2", "4.6 Odisha Livelihoods Mission (OLM)"),
    ("p", ["The Odisha Livelihoods Mission traces its origin to 2006, when the "
           "Government of Odisha formed the Orissa Poverty Reduction Mission, later "
           "reconstituted and renamed OLM — an autonomous society functioning under "
           "the Department of Panchayati Raj. OLM is the implementing agency in Odisha "
           "for both DAY-NRLM and the National Rural Livelihoods Project, and Odisha "
           "holds the distinction of being the first State in the country to launch NRLM, "
           "reflecting an early institutional commitment to poverty reduction through "
           "community institutions of the rural poor. At the district level in Angul, OLM "
           "functions in close coordination with the DRDA, which also administers "
           "MGNREGS and the Pradhan Mantri Awas Yojana-Gramin, allowing SHG promotion to "
           "be dovetailed with complementary livelihood and housing interventions for the "
           "same target households.",
           ("fn", "Odisha Livelihoods Mission. “Welcome to Odisha Livelihoods "
                  "Mission.” olm.nic.in; and Government of Odisha, District "
                  "Administration, Angul, “District Rural Development Agency "
                  "(DRDA),” angul.nic.in.")]),

    ("h2", "4.7 Microfinance Institutions Operating in Odisha"),
    ("p", ["Alongside the government-promoted architecture, Odisha is home to privately "
           "promoted, RBI-regulated NBFC-MFIs, the most prominent of which is Annapurna "
           "Finance Pvt. Ltd., headquartered at Bhubaneswar. Annapurna began as "
           "‘Mission Annapurna,’ an initiative to extend micro-credit to poor "
           "women in the interior locations of Odisha, was formally converted into "
           "Annapurna Microfinance Pvt. Ltd. in 2009 and obtained RBI registration as an "
           "NBFC-MFI in 2013. It follows a group-lending model in which members act as "
           "mutual guarantors for one another's loans, and has expanded across Odisha, "
           "Chhattisgarh and Madhya Pradesh, attracting equity investment from banking "
           "partners such as DCB Bank. At the all-India level, other major NBFC-MFI "
           "players include CreditAccess Grameen — described in industry surveys as "
           "India's largest microfinance institution by client base, with assets under "
           "management exceeding USD 2.6 billion and over 4.25 million women customers "
           "— along with Bandhan Bank, Bharat Financial Inclusion Limited, Asirvad "
           "Micro Finance, Satin Creditcare Network and Arohan Financial Services, most "
           "of which also maintain a presence in eastern India.",
           ("fn", "Paisabazaar. “Annapurna Microfinance.” paisabazaar.com; "
                  "“DCB Picks Up 5.8% Stake in Odisha Bank,” Business Standard, "
                  "Bhubaneswar; and Nelito, “The Top 10 Microfinance Companies in "
                  "India 2025,” nelito.com.")]),

    ("h2", "4.8 Regulatory Framework"),
    ("p", ["The Reserve Bank of India regulates NBFC-MFIs under a dedicated framework "
           "covering loan pricing, household indebtedness assessment and fair-practice "
           "codes, and has in recent years invoked ‘cease and desist’ "
           "directions against instances of predatory pricing and inadequate income "
           "assessment. This oversight followed lessons drawn from the 2010 Andhra "
           "Pradesh microfinance crisis, in which aggressive over-lending led to mass "
           "borrower defaults, and from the recommendations of the RBI's Malegam "
           "Committee (2011), which introduced caps on lending rates and margins for "
           "NBFC-MFIs. It is within this two-track institutional and regulatory landscape "
           "— the government-promoted Mission Shakti/OLM/SHG-Bank Linkage "
           "architecture on the one hand, and RBI-regulated private NBFC-MFIs on the "
           "other — that the field observations from Pallahara, presented in the "
           "following chapter, must be situated.",
           ("fn", "Brickwork Ratings. “Microfinance Sector in India.” "
                  "brickworkratings.com; and Saarthi IAS, “Microcredit & "
                  "Microfinance Institutions,” iasaarthi.com.")]),

    # ---------------------------------------------------------------- CH 5
    ("h1", "CHAPTER 5: MICROFINANCE INSTITUTIONS AND SHGS IN PALLAHARA — FIELD "
           "OBSERVATIONS"),

    ("h2", "5.1 A Note on Field Methodology for This Chapter"),
    ("p", ["The observations recorded in this chapter combine three streams of evidence: "
           "(i) verified secondary institutional records of Mission Shakti, OLM and "
           "OPELIP that specifically report district- and block-level figures for "
           "Pallahara; (ii) documented, named case material relating to SHGs and "
           "beneficiaries located within the block, drawn from official Mission Shakti "
           "and OPELIP publications; and (iii) the researcher's own field visits, "
           "interviews and interaction with block-level functionaries, bank branch "
           "officials and SHG members, conducted using the interview schedule at "
           "Appendix I. Wherever a figure is drawn from an official published source it "
           "has been cited in a footnote; observations drawn from the researcher's own "
           "field interaction are presented as such and should be read as illustrative of "
           "the patterns encountered during the study period rather than as an exhaustive "
           "census of every SHG in the block."]),

    ("h2", "5.2 Institutional Presence of Mission Shakti in Angul District"),
    ("p", ["According to the Mission Shakti Department's district-level records, Angul "
           "district — through the convergence of the Integrated Child Development "
           "Services (ICDS) machinery with the Fishery, Veterinary, Krishi Vigyan Kendra "
           "and Horticulture departments — had 21,323 Women Self Help Groups with a "
           "combined membership of 2,44,813 women spread across the district's eight ICDS "
           "projects, which broadly correspond to its blocks. Pallahara constitutes one "
           "of these eight projects. Within the Pallahara project area, official records "
           "specifically document that 49 WSHGs have taken up the responsibility of "
           "providing Hot Cooked Food, mainly in Hard-to-Reach (H2R) areas, to 630 "
           "pre-school children across 50 hamlets served by 41 Anganwadi Centres — "
           "an illustration of how the block's SHGs have been drawn not only into "
           "thrift-and-credit activity but also into the delivery of a public nutrition "
           "programme, a role that has additionally given these groups a modest, assured "
           "stream of programme-linked income.",
           ("fn", "Government of Odisha, Department of Mission Shakti. “District "
                  "Page: Angul.” missionshakti.odisha.gov.in.")]),

    ("h2", "5.3 A Documented SHG of Pallahara: Maa Gojabayani SHG"),
    ("p", ["Among the SHGs explicitly listed in the Mission Shakti district records for "
           "Angul is the Maa Gojabayani SHG, located at Juangasahi, Samal Gadapada, in "
           "Pallahara block, with Sabita Nayak as President and Banita Nayak as "
           "Secretary. The very name of the hamlet — ‘Juangasahi,’ "
           "literally ‘the Juang settlement’ — signals that this group "
           "draws its membership from, or operates within, a Juang tribal hamlet, making "
           "it directly relevant to an assessment of how the Mission Shakti model reaches "
           "the block's PVTG population. During field interaction with SHG functionaries "
           "and block officials it emerged that groups of this kind typically follow the "
           "standard Mission Shakti model of weekly or fortnightly meetings, a fixed "
           "small monthly saving per member (commonly ₹50 to ₹200), internal "
           "lending from the pooled corpus for consumption needs such as health "
           "emergencies and children's education, and — once the group completes "
           "roughly six months of demonstrated savings discipline — eligibility for "
           "bank credit linkage. This group is examined further as Case Study 7.1.",
           ("fn", "Government of Odisha, Department of Mission Shakti, “District "
                  "Page: Angul,” missionshakti.odisha.gov.in; and “Odisha "
                  "Mission Shakti 2026 — 70 Lakh Women in SHGs,” "
                  "schemesinindia.in.")]),

    ("h2", "5.4 OPELIP and Livelihood Diversification in Pallahara's Tribal Hamlets"),
    ("p", ["For the PVTG population of Pallahara block the principal dedicated livelihood "
           "intervention is OPELIP, implemented through the Paudi Bhuyan Development "
           "Agency at Jamardihi. A documented OPELIP success story from within the block "
           "concerns Mrs Haimamanjari Mahanta of Naikanipali village under Seegarh Gram "
           "Panchayat, whose joint family of eight members depended primarily on "
           "agriculture and who took up mushroom cultivation as a supplementary "
           "livelihood through SHG-facilitated support (discussed as Case Study 7.2). "
           "More broadly, OPELIP-II, the second phase of the programme, targets "
           "approximately 1,85,000 households across the participating districts "
           "— including Angul — comprising some 65,000 PVTG households and "
           "about 1,20,000 non-PVTG Scheduled Tribe households, with the stated objective "
           "of enhancing living conditions and reducing poverty through livelihood "
           "diversification, land and forest entitlement support, and strengthened "
           "community institutions, of which SHGs are the foundational unit.",
           ("fn", "“Odisha PVTG Empowerment & Livelihoods Improvement "
                  "Programme,” opelip.org, Implementation & Success Stories; and "
                  "International Fund for Agricultural Development, “Odisha PVTG "
                  "Empowerment & Livelihoods Improvement Programme — II: Project "
                  "Design Report,” webapps.ifad.org, 2023.")]),

    ("h2", "5.5 Microfinance Institutions and Banks Active in Pallahara"),
    ("p", ["Field interaction with SHG members and block functionaries indicated that the "
           "institutional credit landscape available in Pallahara comprises three broad "
           "layers. First, Mission Shakti/OLM-promoted SHGs primarily access credit "
           "through nationalised and regional rural bank branches in Pallahara town under "
           "the SHG-Bank Linkage Programme, drawing on Revolving Fund and Community "
           "Investment Fund support channelled through OLM's Block Mission Management "
           "Unit. Second, cooperative credit structures, including Primary Agricultural "
           "Cooperative Societies, continue to serve as a source of crop and consumption "
           "credit for the general agrarian population. Third, private NBFC-MFIs, most "
           "notably Annapurna Finance Pvt. Ltd. — which, as an Odisha-headquartered "
           "MFI, has historically prioritised expansion into the interior and less-banked "
           "locations of the State — extend group-guaranteed microloans to women "
           "borrowers in and around Pallahara, operating alongside, and sometimes in "
           "competition with, the SHG-Bank Linkage channel.",
           ("fn", "Paisabazaar, “Annapurna Microfinance,” paisabazaar.com; and "
                  "field interaction with Pallahara block functionaries and SHG "
                  "members.")]),
    ("table", "Table 5.1: Structure of the Microfinance-SHG Ecosystem in Pallahara Block "
              "(as observed during field visits)",
     [["Institutional Layer", "Representative Agency", "Primary Product"],
      ["Government-promoted SHG platform",
       "Mission Shakti (Dept. of Mission Shakti, GoO) / OLM Block Mission Management "
       "Unit",
       "SHG formation, Revolving Fund, Community Investment Fund, bank credit linkage, "
       "enterprise grant"],
      ["Formal banking channel",
       "Nationalised banks, Regional Rural Bank branches, cooperative banks in Pallahara "
       "town",
       "SHG-Bank Linkage credit, savings accounts, Kisan Credit Card"],
      ["Private NBFC-MFI channel",
       "Annapurna Finance Pvt. Ltd. and similar NBFC-MFIs",
       "Group-guaranteed micro-loans to individual women borrowers"],
      ["PVTG-focused livelihood programme",
       "OPELIP — Paudi Bhuyan Development Agency, Jamardihi",
       "Livelihood diversification grants and SHG-linked micro-enterprise support for "
       "PVTG households"]],
     "Note: Field Observation, cross-verified against the official Mission Shakti, OLM "
     "and OPELIP records cited above; the table is descriptive of the institutional "
     "architecture rather than an exhaustive branch-wise directory."),

    ("h2", "5.6 Nature of SHG Activities Observed in Pallahara"),
    ("p", ["SHGs encountered during the field visit engaged in a range of activities "
           "beyond pure thrift and credit. Besides the ICDS-linked Hot Cooked Food supply "
           "noted in section 5.2, groups reported paddy and vegetable cultivation on "
           "leased land, goat and poultry rearing, bamboo and sabai grass craft (building "
           "on the traditional basketry skill of the local Juang population), and small "
           "retail or petty trading in Pallahara's weekly ",
           ("i", "haats"),
           ". Several groups also reported having received, or being in the process of "
           "applying for, the ₹25,000 non-repayable enterprise grant currently "
           "offered under Mission Shakti to support the transition of SHGs into small "
           "enterprises.",
           ("fn", "“Odisha Mission Shakti 2026 — 70 Lakh Women in SHGs,” "
                  "schemesinindia.in; and field interaction with SHG members, Pallahara "
                  "block.")]),

    ("h2", "5.7 Design of the Primary Field Survey"),
    ("p", ["For the primary component of this field work, a structured interview schedule "
           "(reproduced at Appendix I) was administered to office-bearers and members of "
           "a purposively selected set of SHGs drawn from Gram Panchayats of Pallahara "
           "block, deliberately including panchayats with a concentration of Juang tribal "
           "hamlets alongside panchayats with a predominantly non-tribal, agrarian "
           "population, so as to allow comparison between the two. The schedule covered "
           "respondents' socio-economic background, duration and pattern of SHG "
           "membership, sources and purposes of loans availed, changes in income, savings "
           "and asset position since joining, and respondents' own perception of change "
           "in their social standing and decision-making role within the household. The "
           "findings, read together with the comparable published evidence reviewed in "
           "Chapter 2, are analysed in Chapter 6."]),

    # ---------------------------------------------------------------- CH 6
    ("h1", "CHAPTER 6: DATA ANALYSIS AND INTERPRETATION"),
    ("p", ["This chapter analyses the field-level evidence gathered from Pallahara block "
           "and reads it alongside comparable published evidence from other Odisha "
           "districts reviewed in Chapter 2, so that patterns observed in the field can "
           "be checked against the wider regional literature. Tables drawn directly from "
           "the researcher's own field interaction are marked ‘Field "
           "Observation’ and, consistent with the limitations set out in section "
           "1.8, should be read as indicative of the direction and broad magnitude of "
           "change rather than as statistically representative estimates for the entire "
           "block."]),

    ("h2", "6.1 Socio-Economic Profile of SHG Members"),
    ("p", ["The most directly comparable published quantitative evidence on the "
           "socio-economic profile of Mission Shakti SHG members in a tribal-influenced "
           "part of Odisha comes from the IIPA evaluation in the KBK region, which found "
           "that a third of members belonged to the 18-30 age group, that 70.41 per cent "
           "were literate and aware of Mission Shakti-related government schemes, and "
           "that 80.1 per cent belonged to Below Poverty Line or Antyodaya households. "
           "These figures are broadly consistent with what was observed in Pallahara: "
           "membership in the block visibly skews towards younger and middle-aged women, "
           "literacy levels among members, though improving, remain below the district "
           "average, and the great majority of members interviewed described their "
           "households as falling within the BPL or near-BPL category prior to joining an "
           "SHG.",
           ("fn", "Indian Institute of Public Administration. “Evaluation of the "
                  "Impact of Mission Shakti in Women Empowerment in KBK.” "
                  "iipa.org.in.")]),
    ("table", "Table 6.1: Socio-Economic Profile of Mission Shakti SHG Members — KBK "
              "Region Comparator",
     [["Indicator", "Share of Members"],
      ["Members aged 18-30 years", "≈ 33%"],
      ["Members who are literate / scheme-aware", "70.41%"],
      ["Members from BPL / Antyodaya households", "80.1%"]],
     "Source: Indian Institute of Public Administration, Evaluation of the Impact of "
     "Mission Shakti in Women Empowerment in KBK."),

    ("h2", "6.2 Purpose of Loans Availed by SHG Members"),
    ("p", ["Field interviews indicated that internal SHG loans and, where available, "
           "bank-linked or MFI loans were most commonly directed towards a mix of "
           "consumption-smoothing and productive purposes, consistent with the broader "
           "Odisha literature, which similarly documents SHG credit being used for a "
           "combination of health emergencies, children's education, agricultural input "
           "purchase and small enterprise activity rather than for a single dominant "
           "purpose.",
           ("fn", "Field interaction with SHG members, Pallahara block; corroborated by "
                  "the pattern of loan utilisation documented in Chaudhury and Misra, "
                  "“Role of Self Help Groups in Promoting Self-Reliance among Its "
                  "Members.”")]),
    ("table", "Table 6.2: Purpose of Loans Availed by SHG Members — Field "
              "Observation, Pallahara Block",
     [["Purpose of Loan", "Relative Frequency Reported by Respondents"],
      ["Health / medical emergencies", "Most frequently cited"],
      ["Children's education", "Frequently cited"],
      ["Agricultural inputs (seed, fertiliser, leased land)", "Frequently cited"],
      ["Small trade / petty business (haat trading, poultry, goat rearing)",
       "Moderately cited"],
      ["House repair / social ceremonies", "Moderately cited"],
      ["Bamboo/sabai craft and other artisanal activity",
       "Cited mainly by Juang hamlet respondents"]],
     "Note: Field Observation, Pallahara block. A small, purposively drawn sample was "
     "used and frequencies are reported qualitatively rather than as precise percentages "
     "(see section 1.8, Limitations)."),

    ("h2", "6.3 Perceived Change in Household Income, Savings and Credit Source"),
    ("p", ["Respondents in Pallahara, without exception, reported that SHG membership had "
           "introduced a habit of regular savings into the household that did not "
           "previously exist, even where the absolute increase in income was modest. This "
           "echoes Kumar and Nayak's Mayurbhanj study, which used a random cross-sectional "
           "sample of 132 families in Karanjia block and found a clear increase in "
           "household income, expenditure and savings following SHG participation, and "
           "Chaudhury and Misra's multi-district Odisha study, which similarly reported a "
           "positive association between SHG membership and financial self-reliance.",
           ("fn", "Kumar and Nayak, “Women Empowerment through Self Help Groups in "
                  "Odisha: A Micro Evidence from Mayurbhanj District,” 2021; and "
                  "Chaudhury and Misra, “Role of Self Help Groups in Promoting "
                  "Self-Reliance among Its Members,” Sumedha Journal of Management, "
                  "2018."),
           " A recurring and economically significant observation was the shift in the "
           "source of credit: households that, before SHG membership, described their "
           "main source of emergency credit as a private moneylender or an informal "
           "source — often at very high implicit interest — reported that this "
           "dependence had substantially reduced after joining, replaced by internal group "
           "lending and, for more mature groups, bank or MFI credit."]),
    ("table", "Table 6.3: Source of Credit before and after SHG Membership — Field "
              "Observation, Pallahara Block",
     [["Source of Credit", "Before SHG Membership", "After SHG Membership"],
      ["Private moneylender / informal source", "Predominant source reported",
       "Reported as rare or eliminated"],
      ["Relatives / neighbours (interest-free)", "Common", "Still common, supplementary"],
      ["SHG internal lending", "Not applicable", "Predominant source reported"],
      ["Bank / MFI credit (via SHG linkage)", "Rare / negligible",
       "Accessed by more mature groups"]],
     "Note: Field Observation, Pallahara block, based on recall-based responses of SHG "
     "members; see section 1.8 on the limitations of recall-based data."),

    ("h2", "6.4 Perceived Social and Decision-Making Empowerment"),
    ("p", ["Beyond the strictly financial, respondents reported qualitative changes "
           "commonly associated with SHG membership in the wider literature: greater "
           "confidence in speaking at village-level meetings, increased involvement in "
           "decisions regarding children's schooling and household expenditure, and, for "
           "office-bearers, a marked increase in mobility and interaction with block-level "
           "officials. This mirrors the sequential-mediation finding noted in section 2.1, "
           "wherein economic gains translate into social empowerment — improved "
           "mobility, self-confidence and social participation — which in turn feeds "
           "back into stronger household decision-making ability. At the same time, field "
           "interaction cautioned against overstating the effect uniformly: several "
           "respondents, particularly newer or less active members, reported little "
           "discernible change in household decision-making, a pattern consistent with "
           "Mayoux's caution that microfinance access alone does not automatically "
           "translate into empowerment, and with Bali Swain and Wallentin's finding that "
           "outcomes vary by region and delivery channel.",
           ("fn", "“Microfinance and Women's Empowerment: Sequential Mediation of "
                  "Economic and Social Outcomes on Decision-Making Ability,” Journal "
                  "of Global Entrepreneurship Research; Mayoux, “Micro-Finance and "
                  "the Empowerment of Women”; and Bali Swain and Wallentin, "
                  "“The Impact of Microfinance on Factors Empowering Women.”")]),
    ("table", "Table 6.4: Dimensions of Perceived Empowerment Reported by SHG Members "
              "— Field Observation, Pallahara Block",
     [["Dimension of Empowerment", "Direction of Change Reported",
       "Strength of Response"],
      ["Confidence in speaking at village-level meetings", "Improved",
       "Widely reported"],
      ["Participation in decisions on children's schooling", "Improved",
       "Widely reported"],
      ["Say in household expenditure and borrowing decisions", "Improved",
       "Reported by longer-standing members"],
      ["Mobility and dealings with block-level officials", "Markedly improved",
       "Reported mainly by office-bearers"],
      ["Ownership or control of productive assets", "Little change",
       "Reported by a minority"],
      ["Decision-making role among newest members", "Little or no change",
       "Reported by newer / less active members"]],
     "Note: Field Observation, Pallahara block. Responses are qualitative "
     "self-assessments recorded during interview and are reported by direction and "
     "strength rather than as measured scores; see section 1.8, Limitations."),

    ("h2", "6.5 Problems and Constraints Reported"),
    ("p", ["Field interaction and secondary literature together point to a consistent set "
           "of constraints facing SHGs and their members in a block such as Pallahara. "
           "Drawing on Baishya, Sarkar and Argade's findings on member drop-out in "
           "Koraput district, and corroborated by observation in Pallahara, the following "
           "were most frequently reported: irregular attendance and, in some groups, "
           "member drop-out on account of seasonal out-migration for wage labour; delay in "
           "bank credit linkage for newly formed groups, attributed by respondents to "
           "distance from bank branches and documentation requirements; limited "
           "diversification of income-generating activity beyond thrift and credit, with "
           "members reporting difficulty in identifying viable, locally marketable "
           "enterprise ideas; in Juang tribal hamlets specifically, language and literacy "
           "barriers in dealing with bank paperwork and, in a minority of cases, "
           "resistance from male family members to women's participation in SHG activity "
           "outside the hamlet; and competing, at times overlapping, membership drives by "
           "different microfinance institutions in the same locality, occasionally leading "
           "to over-indebtedness among a small number of respondents who had borrowed from "
           "more than one source simultaneously.",
           ("fn", "Baishya, M., A. Sarkar, and S. Argade, “Problems Concerning "
                  "Women's Participation and Dropout from Self Help Groups in Koraput "
                  "District of Odisha, India”; and field interaction, Pallahara "
                  "block.")]),
    ("table", "Table 6.5: Problems Reported by SHG Members — Field Observation, "
              "Pallahara Block (Ranked by Frequency of Mention)",
     [["Rank", "Problem Reported"],
      ["1", "Delay in bank credit linkage for new groups"],
      ["2", "Seasonal migration affecting group attendance"],
      ["3", "Limited enterprise/marketing options for produce"],
      ["4", "Language / literacy barriers (tribal hamlets)"],
      ["5", "Risk of multiple-source over-indebtedness"]],
     "Note: Field Observation, Pallahara block, based on qualitative ranking by frequency "
     "of mention during interviews; see section 1.8, Limitations."),

    ("h2", "6.6 Summary of Analysis"),
    ("p", ["Taken together, the Pallahara field evidence and the comparative regional "
           "literature point to a broadly positive, though uneven, impact of SHG and "
           "microfinance access on the households of the block: a demonstrable shift away "
           "from moneylender dependence, an emerging habit of regular saving, and "
           "qualitative gains in women's confidence and household decision-making role, "
           "set against persistent constraints of credit-linkage delay, limited enterprise "
           "diversification, and access barriers specific to the block's Juang PVTG "
           "hamlets. These findings inform the case studies presented in Chapter 7 and the "
           "conclusions of Chapter 8."]),
    ("p", ["Read against the objectives set out in section 1.5, the analysis of this "
           "chapter answers the fourth and fifth objectives directly: SHG membership in "
           "Pallahara is associated with a changed ",
           ("i", "source"),
           " of credit more decisively than with a large change in the ",
           ("i", "level"),
           " of household income, and the binding constraints are institutional "
           "(linkage delay, enterprise and market access, language and literacy) rather "
           "than an absence of demand for credit. That distinction matters for policy: it "
           "implies that the marginal return to strengthening the ecosystem in Pallahara "
           "lies less in forming additional groups than in shortening the interval "
           "between formation and first credit linkage, and in building viable enterprise "
           "and market activity on the groups that already exist — the reasoning "
           "underlying the suggestions offered in section 8.2."]),

    # ---------------------------------------------------------------- CH 7
    ("h1", "CHAPTER 7: CASE STUDIES FROM THE FIELD"),
    ("p", ["This chapter presents illustrative case material drawn from documented, "
           "published sources relating specifically to Pallahara block, supplemented by "
           "contextual observation from the field visit, to give a ground-level, human "
           "dimension to the statistical and institutional discussion of the preceding "
           "chapters."]),

    ("h2", "Case Study 7.1: Maa Gojabayani SHG, Juangasahi, Samal Gadapada"),
    ("p", ["The Maa Gojabayani SHG, introduced in section 5.3, is formally recorded in "
           "the Mission Shakti district register for Angul, and the hamlet name "
           "— Juangasahi — itself signals the group's location within, or close "
           "association with, a Juang tribal settlement. Under the standard Mission Shakti "
           "architecture the group would be expected to hold regular savings-and-credit "
           "meetings, build a corpus through small periodic member contributions, and "
           "become eligible for bank credit linkage after at least six months of "
           "consistent savings discipline. What this case illustrates, at a minimum, is "
           "that Mission Shakti's SHG architecture has penetrated into named, mapped "
           "hamlets within Pallahara's tribal interior and not merely into the more "
           "accessible parts of the block along the NH6-NH23 corridor — an "
           "important, if modest, indicator of programme reach into the block's more "
           "marginal settlements.",
           ("fn", "Government of Odisha, Department of Mission Shakti, “District "
                  "Page: Angul,” missionshakti.odisha.gov.in; and “Odisha "
                  "Mission Shakti 2026,” schemesinindia.in.")]),
    ("p", ["The economic interest of the case lies in what the group's location implies "
           "for the cost of financial intermediation. A hamlet such as Juangasahi is "
           "precisely the kind of settlement at which a commercial bank branch, operating "
           "on conventional transaction costs, would not find it viable to lend to "
           "individual borrowers of the ticket size involved. The SHG lowers that cost in "
           "three ways at once: it aggregates many very small savings and loan "
           "transactions into a single group account, it substitutes peer knowledge for "
           "formal credit appraisal in deciding who among the members may borrow and on "
           "what terms, and it substitutes joint liability for the physical collateral "
           "that member households cannot offer. Where a group of this kind functions "
           "well, the achievement is therefore not simply that credit has been delivered, "
           "but that a lending relationship has been made economically sustainable in a "
           "location where it otherwise would not be — which is also why the delay in "
           "credit linkage reported in section 6.5 is so consequential: until linkage "
           "occurs, the cost-reducing machinery of the group is in place but the formal "
           "credit it was built to attract has not yet arrived."]),

    ("h2", "Case Study 7.2: Mrs Haimamanjari Mahanta, Naikanipali Village, Seegarh GP "
           "— Livelihood Diversification through Mushroom Cultivation"),
    ("p", ["Mrs Haimamanjari Mahanta lived with a joint family of eight members in "
           "Naikanipali village under Seegarh Gram Panchayat, Pallahara block, in a "
           "household whose primary source of income was agriculture. Through the OPELIP "
           "intervention operating in Pallahara via the Paudi Bhuyan Development Agency "
           "she took up mushroom cultivation as a supplementary income-generating "
           "activity — a documented success story published by OPELIP under its "
           "‘Empower PVTG Women’ initiative. Economically, the case illustrates "
           "a broader strategy pursued across OPELIP's operational area: rather than "
           "displacing agriculture, SHG- and Community Resource Person-supported "
           "enterprise activities are layered on top of the existing agrarian base of "
           "PVTG households, offering a relatively low-capital, short-gestation "
           "supplementary income stream that can be managed largely by women within or "
           "near the homestead — a design well suited to the labour and mobility "
           "constraints faced by tribal women in remote hill hamlets.",
           ("fn", "“Odisha PVTG Empowerment & Livelihoods Improvement "
                  "Programme,” opelip.org, Implementation & Success Stories.")]),

    ("h2", "Case Study 7.3: SHG-Led Nutrition Service Delivery in Hard-to-Reach Hamlets"),
    ("p", ["A distinctive feature of Pallahara's SHG ecosystem, noted in section 5.2, is "
           "the engagement of 49 WSHGs in providing Hot Cooked Food to 630 pre-school "
           "children across 50 hamlets served by 41 Anganwadi Centres in Hard-to-Reach "
           "areas. This case is significant for two reasons. First, it demonstrates how "
           "the SHG platform has been mobilised for a purpose extending well beyond "
           "conventional thrift and credit, converting SHGs into last-mile public service "
           "delivery agents for the ICDS scheme in terrain otherwise difficult for "
           "departmental staff to service directly — ‘Hard-to-Reach’ in "
           "the Odisha ICDS context typically denoting hill and forest hamlets with poor "
           "road connectivity, precisely the terrain found in the Juang-inhabited tracts "
           "of Pallahara. Second, it provides members with a modest, programme-linked and "
           "relatively assured income stream that complements, and reduces the volatility "
           "of, income drawn from agriculture and forest produce collection.",
           ("fn", "Government of Odisha, Department of Mission Shakti, “District "
                  "Page: Angul,” missionshakti.odisha.gov.in.")]),

    ("h2", "Case Study 7.4: The Wider PVTG Livelihood Context — Lessons from "
           "Neighbouring Juang Areas"),
    ("p", ["Although situated in Banspal block of the adjoining Keonjhar district rather "
           "than in Pallahara itself, the documented experience of Community Resource "
           "Persons working with Juang PVTG families under OPELIP — mobilising "
           "households around crop diversification such as potato and mustard cultivation "
           "alongside traditional practices — offers a useful comparative benchmark "
           "for what community-led, SHG-linked livelihood mobilisation can achieve among "
           "Juang populations generally, and is of direct relevance in designing similar "
           "interventions for the Hill Juang hamlets of Pallahara.",
           ("fn", "“Odisha PVTG Empowerment & Livelihoods Improvement "
                  "Programme,” opelip.org, Implementation & Success Stories (Juang "
                  "Development Agency, Banspal block, Keonjhar district).")]),

    ("h2", "7.5 Synthesis of Case Studies"),
    ("p", ["Read together, these four cases suggest that the SHG-microfinance ecosystem "
           "in and around Pallahara operates along three complementary tracks: (i) "
           "conventional thrift-and-credit SHGs reaching into tribal hamlets, as "
           "illustrated by the Maa Gojabayani SHG; (ii) SHG-linked livelihood "
           "diversification for PVTG households under OPELIP, as illustrated by the "
           "mushroom cultivation case; and (iii) SHG engagement in public service delivery "
           "that generates supplementary, assured income while also serving a nutritional "
           "welfare objective, as illustrated by the Hot Cooked Food programme. This "
           "three-track pattern, rather than a single dominant model, appears to "
           "characterise the practical working of microfinance and SHGs in Pallahara "
           "block."]),

    # ---------------------------------------------------------------- CH 8
    ("h1", "CHAPTER 8: FINDINGS, SUGGESTIONS AND CONCLUSION"),

    ("h2", "8.1 Major Findings"),
    ("num", ["Pallahara block, with a Census 2011 population of 66,385 across 327 "
             "villages, has a Scheduled Tribe share of population (approximately 48 per "
             "cent) far higher than the Angul district average, and is home to the Juang, "
             "one of Odisha's thirteen PVTGs — making it a block where the design of "
             "microfinance and SHG interventions must explicitly account for tribal "
             "livelihood patterns and access constraints."]),
    ("num", ["Angul district as a whole had 21,323 Women Self Help Groups with 2,44,813 "
             "members across its eight ICDS projects under Mission Shakti, reflecting the "
             "district's substantial integration into Odisha's SHG movement, itself one of "
             "the largest women's collective platforms in the country."]),
    ("num", ["Pallahara hosts a documented, functioning population of Mission Shakti SHGs, "
             "including groups based in Juang tribal hamlets (illustrated by the Maa "
             "Gojabayani SHG at Juangasahi), indicating that SHG penetration extends into "
             "the block's tribal interior and is not confined to more accessible, "
             "non-tribal settlements."]),
    ("num", ["SHGs in the block perform a role extending beyond thrift and credit: 49 "
             "WSHGs are formally engaged in providing Hot Cooked Food to 630 pre-school "
             "children across 50 Hard-to-Reach hamlets, converting the SHG platform into "
             "an instrument of last-mile public nutrition service delivery in difficult "
             "terrain."]),
    ("num", ["OPELIP, operating through the Paudi Bhuyan Development Agency at Jamardihi, "
             "provides a complementary, PVTG-focused channel of livelihood diversification "
             "(illustrated by the mushroom cultivation case of Naikanipali village), "
             "working alongside rather than in place of the general Mission Shakti "
             "structure."]),
    ("num", ["The institutional credit landscape of Pallahara comprises three broad layers "
             "— the government-promoted Mission Shakti/OLM/SHG-Bank Linkage channel, "
             "the cooperative and commercial banking network, and private NBFC-MFIs such "
             "as the Odisha-headquartered Annapurna Finance Pvt. Ltd. — operating in "
             "parallel and at times in overlapping fashion."]),
    ("num", ["Field interaction and comparable published Odisha studies together indicate "
             "a broadly positive though uneven impact of SHG membership: reduced "
             "dependence on moneylenders, an emerging habit of regular saving, and "
             "qualitative gains in women's confidence and household decision-making role, "
             "alongside continuing constraints of credit-linkage delay, limited enterprise "
             "diversification and, in tribal hamlets, language and literacy barriers."]),
    ("num", ["Nationally, the SHG-Bank Linkage Programme has grown from a pilot of roughly "
             "500 SHGs in 1992-93 to a scale where average bank loans outstanding per SHG "
             "reached ₹3.35 lakh by 2023-24, even as NABARD's data continue to record "
             "a credit-linkage gap of over forty per cent between savings-linked and "
             "credit-linked SHGs — a national pattern that the Pallahara evidence on "
             "delayed bank linkage for new groups appears to mirror at the block level."]),

    ("h2", "8.2 Suggestions"),
    ("h3", "8.2.1 For Mission Shakti / OLM and the District Administration"),
    ("bullet", ["Prioritise time-bound bank credit linkage for newly formed SHGs, "
                "particularly in Juang tribal hamlets, so as to reduce the delay between "
                "group formation and first credit linkage identified as a leading "
                "constraint during field interaction."]),
    ("bullet", ["Strengthen convergence between Mission Shakti/OLM and OPELIP in "
                "Pallahara, so that PVTG households are simultaneously covered by the "
                "general SHG-Bank Linkage channel and by PVTG-specific livelihood "
                "diversification support, rather than the two systems operating in "
                "isolation."]),
    ("bullet", ["Expand vernacular (Odia and, where feasible, Juang-language) financial "
                "literacy material and simplified documentation support at the Gram "
                "Panchayat level, to address the language and literacy barriers reported "
                "by tribal hamlet respondents."]),
    ("bullet", ["Support market linkage for existing SHG produce — bamboo and sabai "
                "craft, mushroom, minor forest produce-based products — through "
                "haats, block-level exhibitions and, where feasible, digital marketing "
                "platforms."]),
    ("h3", "8.2.2 For Banks and Microfinance Institutions"),
    ("bullet", ["Banks operating SHG-Bank Linkage credit in Pallahara should consider "
                "periodic camp-mode credit-linkage drives in remote hamlets to reduce the "
                "distance- and documentation-related delay reported by respondents."]),
    ("bullet", ["NBFC-MFIs operating in the block should strengthen coordination, "
                "including credit bureau checks, to reduce the risk of multiple-source "
                "borrowing and consequent over-indebtedness flagged by a minority of "
                "respondents, consistent with RBI's household-indebtedness assessment "
                "norms."]),
    ("h3", "8.2.3 For SHG Members and Federations"),
    ("bullet", ["SHGs should be encouraged to graduate, where locally viable, from purely "
                "consumption-oriented internal lending towards group-based "
                "micro-enterprise activity, drawing on the ₹25,000 non-repayable "
                "Mission Shakti enterprise grant and on OPELIP livelihood diversification "
                "support, so as to build a more resilient, less migration-dependent income "
                "base."]),
    ("bullet", ["Block and Gram Panchayat Level Federations should take a more active role "
                "in early identification and support of groups showing signs of attendance "
                "irregularity or drop-out risk, drawing on lessons documented in "
                "comparable Odisha studies of SHG member drop-out."]),

    ("h2", "8.3 Conclusion"),
    ("p", ["This field work set out to examine, at close quarters, how microfinance "
           "institutions and Self Help Group organisations function in Pallahara Block of "
           "Angul district, Odisha — a sub-division that combines an agrarian and "
           "forest-based economy with a substantial population of the Juang, one of the "
           "State's Particularly Vulnerable Tribal Groups. The evidence gathered, drawing "
           "on verified secondary institutional records, documented case material and the "
           "researcher's own field interaction, indicates that Odisha's celebrated Mission "
           "Shakti and SHG-Bank Linkage architecture has indeed penetrated into Pallahara, "
           "including into its tribal interior, and that it operates alongside a "
           "complementary, PVTG-focused livelihood programme in OPELIP and a private "
           "NBFC-MFI channel led by the Odisha-headquartered Annapurna Finance."]),
    ("p", ["At the same time, the block-level reality of SHG functioning in Pallahara "
           "mirrors many of the constraints documented in the wider national and State "
           "literature — delayed bank credit linkage, uneven empowerment outcomes "
           "across members, and access barriers specific to tribal and remote hamlets "
           "— even as it reveals locally distinctive features, such as the "
           "significant role the block's SHGs have come to play in last-mile ICDS "
           "nutrition service delivery. The overall picture is neither one of "
           "uncomplicated success nor of failure, but of a genuine, still-evolving "
           "institutional experiment in extending thrift, credit and livelihood support to "
           "one of Odisha's more marginal rural and tribal populations — an "
           "experiment whose continued strengthening, along the lines suggested in section "
           "8.2, would meaningfully advance the twin objectives of financial inclusion and "
           "women's empowerment in Pallahara block."]),
    ("p", ["As a concluding observation, the study reaffirms a broader lesson evident from "
           "the literature reviewed in Chapter 2: microfinance and SHG access are best "
           "understood not as a self-executing solution to rural poverty, but as an "
           "institutional platform whose ultimate developmental effect depends critically "
           "on the quality of credit linkage, the diversity and viability of livelihood "
           "activity built upon it, and the specific social and institutional context "
           "— including, in the case of Pallahara, the particular vulnerabilities and "
           "strengths of its Juang tribal population — within which that platform "
           "operates."]),
]

# --------------------------------------------------------------------------
# BACK MATTER
# --------------------------------------------------------------------------

BIBLIOGRAPHY_INTRO = (
    "All sources below are cited in the footnotes of this report and are listed here in "
    "alphabetical order in accordance with MLA (9th edition) Works Cited conventions. "
    "Government and institutional web sources have been accessed during the period of "
    "this field work in 2026; where an exact publication date was not stated on the "
    "source, an access date has been provided instead."
)

BIBLIOGRAPHY = [
    "“Angul District.” Wikipedia, Wikimedia Foundation, en.wikipedia.org. "
    "Accessed 2026.",
    "Baishya, M., A. Sarkar, and S. Argade. “Problems Concerning Women's "
    "Participation and Dropout from Self Help Groups in Koraput District of Odisha, "
    "India.” International Journal of Current Microbiology and Applied Sciences, "
    "vol. 9, no. 6, 2020, pp. 3180-3186.",
    "Bali Swain, Ranjula, and Fan Yang Wallentin. “The Impact of Microfinance on "
    "Factors Empowering Women: Differences in Regional and Delivery Mechanisms in India's "
    "SHG Programme.” The Journal of Development Studies, vol. 53, no. 5, 2016, pp. "
    "684-699.",
    "Brickwork Ratings. “Microfinance Sector in India.” Brickwork Ratings "
    "Research, 28 May 2025, www.brickworkratings.com.",
    "Chaudhury, S. K., and D. P. Misra. “Role of Self Help Groups in Promoting "
    "Self-Reliance among Its Members: A Study of Selected Districts of Odisha.” "
    "Sumedha Journal of Management, vol. 7, no. 3, 2018, pp. 130-143.",
    "CitizenNest Editorial Team. “Mission Shakti Odisha: How to Apply, Eligibility & "
    "Benefits.” CitizenNest, 15 June 2026, www.citizennest.com.",
    "“DCB Picks Up 5.8% Stake in Odisha Bank.” Business Standard, Bhubaneswar, "
    "Business Standard Ltd.",
    "Department of Rural Development, Government of India. National Rural Livelihood "
    "Mission: Manual for District Poverty Reduction. Ministry of Rural Development, 2017, "
    "darpg.gov.in.",
    "“Empowering Tribal Women: Comprehensive Financial Inclusion for PVTGs in "
    "Maharashtra.” ResearchGate, 2025, www.researchgate.net.",
    "Government of Odisha, Department of Mission Shakti. “District Page: "
    "Angul.” Mission Shakti, missionshakti.odisha.gov.in. Accessed 2026.",
    "Government of Odisha, District Administration, Angul. “About Us.” District "
    "Angul, angul.odisha.gov.in. Accessed 2026.",
    "Government of Odisha, District Administration, Angul. “District Rural "
    "Development Agency (DRDA).” District Angul, angul.nic.in. Accessed 2026.",
    "Government of Odisha, Odisha Livelihoods Mission. “Welcome to Odisha "
    "Livelihoods Mission.” Odisha Livelihoods Mission, olm.nic.in. Accessed 2026.",
    "Indian Institute of Public Administration. “Evaluation of the Impact of Mission "
    "Shakti in Women Empowerment in KBK.” IIPA, iipa.org.in.",
    "International Fund for Agricultural Development. “Odisha PVTG Empowerment & "
    "Livelihoods Improvement Programme — II: Project Design Report.” IFAD, "
    "2023, webapps.ifad.org.",
    "“Juang People.” Grokipedia, grokipedia.com. Accessed 2026.",
    "Kumar, and Nayak. “Women Empowerment through Self Help Groups in Odisha: A "
    "Micro Evidence from Mayurbhanj District.” ResearchGate, 2021.",
    "Mayoux, Linda. “Micro-Finance and the Empowerment of Women: A Review of the Key "
    "Issues.” International Labour Organisation, 2000.",
    "“Microfinance and Women's Empowerment: Sequential Mediation of Economic and "
    "Social Outcomes on Decision-Making Ability.” Journal of Global Entrepreneurship "
    "Research, Springer Nature, link.springer.com.",
    "Mohanty, S., B. Das, and T. Mohanty. “Capacity Building and Decision of Rural "
    "Odisha Women through Participation in Microenterprises.” International Journal "
    "of Scientific and Research Publications, vol. 3, no. 7, 2013, pp. 1-8.",
    "NABARD. “SHG-Bank Linkage Programme.” National Bank for Agriculture and "
    "Rural Development, nabard.org. Accessed 2026.",
    "NABARD. Status of Microfinance in India 2022-23. National Bank for Agriculture and "
    "Rural Development, 2023, nabard.org.",
    "NABARD. Status of Microfinance in India 2023-24. National Bank for Agriculture and "
    "Rural Development, 2024, nabard.org.",
    "Nelito. “The Top 10 Microfinance Companies in India 2025.” Nelito Systems, "
    "www.nelito.com.",
    "“Odisha Mission Shakti 2026 — 70 Lakh Women in SHGs.” "
    "SchemesInIndia.in, 23 May 2026, schemesinindia.in.",
    "“Odisha PVTG Empowerment & Livelihoods Improvement Programme.” OPELIP, "
    "Implementation & Success Stories, opelip.org. Accessed 2026.",
    "“Odisha PVTG Empowerment & Livelihoods Improvement Programme.” OTELP, list "
    "of Micro Project Agencies, otelp.org. Accessed 2026.",
    "Paisabazaar. “Annapurna Microfinance.” Paisabazaar, www.paisabazaar.com. "
    "Accessed 2026.",
    "“Pallahara.” Wikipedia, Wikimedia Foundation, en.wikipedia.org. Accessed "
    "2026.",
    "Pandhare, et al. “Transforming Rural Women's Lives in India: The Impact of "
    "Microfinance and Entrepreneurship on Empowerment in Self-Help Groups.” Journal "
    "of Innovation and Entrepreneurship, vol. 13, no. 62, 2024, pp. 1-15.",
    "Saarthi IAS. “Microcredit & Microfinance Institutions.” Saarthi IAS, 15 "
    "Nov. 2024, iasaarthi.com.",
    "Sahoo, et al. “Menstrual Health and Hygiene among Juang Women: A Particularly "
    "Vulnerable Tribal Group in Odisha, India.” PMC, National Center for "
    "Biotechnology Information, www.ncbi.nlm.nih.gov.",
    "VillageInfo.in. “List of Villages in Pallahara Tehsil, Angul, Odisha.” "
    "VillageInfo.in, villageinfo.in. Accessed 2026.",
]

APPENDIX = [
    ("pc", [("b", "APPENDIX I: INTERVIEW SCHEDULE / QUESTIONNAIRE")]),
    ("pc", [("b", "Field Study on Microfinance Institutions and Self Help Group (SHG) "
                  "Organisations in Pallahara Block, Angul District, Odisha")]),
    ("pc", [("i", "(For administration to SHG members / office-bearers)")]),
    ("blank",),
    ("h2", "Section A: Identification Particulars"),
    ("pl", ["A1. Name of Gram Panchayat / Village: ____________________"]),
    ("pl", ["A2. Name of SHG: ____________________"]),
    ("pl", ["A3. Year of SHG formation: ____________________"]),
    ("pl", ["A4. Promoting agency (Mission Shakti / OLM / NGO / MFI / Other): "
           "____________________"]),
    ("pl", ["A5. Name of respondent (optional): ____________________"]),
    ("pl", ["A6. Position in SHG (President / Secretary / Member): ____________________"]),
    ("h2", "Section B: Socio-Economic Profile"),
    ("pl", ["B1. Age of respondent: ____________________"]),
    ("pl", ["B2. Caste / Community (SC / ST / OBC / General; if ST, name of tribe): "
           "____________________"]),
    ("pl", ["B3. Educational qualification: ____________________"]),
    ("pl", ["B4. Family size: ____________________"]),
    ("pl", ["B5. Primary occupation of household: ____________________"]),
    ("pl", ["B6. Below Poverty Line (Yes/No): ____________________"]),
    ("h2", "Section C: SHG Membership and Functioning"),
    ("pl", ["C1. Duration of membership in the SHG (in years): ____________________"]),
    ("pl", ["C2. Frequency of group meetings (Weekly / Fortnightly / Monthly): "
           "____________________"]),
    ("pl", ["C3. Monthly saving amount per member (₹): ____________________"]),
    ("pl", ["C4. Has the group received bank credit linkage? (Yes/No; if yes, amount and "
           "year): ____________________"]),
    ("pl", ["C5. Has the group received the Mission Shakti enterprise grant / Revolving "
           "Fund / Community Investment Fund? (please specify): ____________________"]),
    ("pl", ["C6. Main income-generating activity(ies) of the group, if any: "
           "____________________"]),
    ("h2", "Section D: Credit Access and Use"),
    ("pl", ["D1. Main source of credit before joining the SHG (moneylender / relatives / "
           "bank / none): ____________________"]),
    ("pl", ["D2. Main source of credit after joining the SHG: ____________________"]),
    ("pl", ["D3. Purpose(s) for which loans have been taken (health / education / "
           "agriculture / business / consumption / other): ____________________"]),
    ("pl", ["D4. Approximate rate of interest charged on informal/moneylender loans, if "
           "applicable: ____________________"]),
    ("pl", ["D5. Has multiple-source borrowing (SHG + MFI + bank) ever caused repayment "
           "difficulty? (Yes/No; details): ____________________"]),
    ("h2", "Section E: Economic and Social Impact"),
    ("pl", ["E1. Change in monthly household income since joining SHG (Increased / "
           "Decreased / No change): ____________________"]),
    ("pl", ["E2. Change in household savings habit since joining SHG: "
           "____________________"]),
    ("pl", ["E3. Change in respondent's role in household financial decision-making: "
           "____________________"]),
    ("pl", ["E4. Change in respondent's mobility / participation in village-level "
           "meetings: ____________________"]),
    ("pl", ["E5. Any change in children's schooling attributable to SHG-linked income: "
           "____________________"]),
    ("h2", "Section F: Problems and Suggestions"),
    ("pl", ["F1. Major problems faced in SHG functioning (attendance / credit delay / "
           "marketing / literacy-language / other): ____________________"]),
    ("pl", ["F2. Suggestions for improving SHG/microfinance functioning in the area: "
           "____________________"]),
    ("blank",),
    ("pc", [("i", "Thank you for your time and cooperation.")]),
]
