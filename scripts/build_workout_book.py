"""Build private workout knowledge indexes from the two supplied PDFs only.

Requires pypdf (pip install -r scripts/requirements-book.txt).
Run from any directory: python scripts/build_workout_book.py
"""

from hashlib import sha256
from pathlib import Path
import json
import re

from pypdf import PdfReader


ROOT = Path(__file__).resolve().parents[1]
PDF = ROOT / "docs/The Worlds Fittest Book.pdf"
OUTPUT = ROOT / "resources/knowledge/worlds-fittest-book.json"
PROGRAM_PDF = ROOT / "docs/Program_Latihan_12_Minggu_yUKVCpR.pdf"
PROGRAM_OUTPUT = ROOT / "resources/knowledge/program-latihan-12-minggu.json"

# Editorial metadata helps retrieval; it is never used as evidence for an answer.
SECTIONS = [
    (1, 1, "Cover", "sampul"),
    (2, 4, "Training principles", "prinsip dasar latihan recovery pemulihan overload adaptasi tujuan"),
    (5, 8, "12-week Tree-athlon", "triathlon renang lari bersepeda endurance"),
    (9, 10, "Recovery and work capacity", "recovery pemulihan kapasitas kerja"),
    (11, 11, "Kettlebell progression", "progresi progressive overload kettlebell swing"),
    (12, 32, "12-week bodyweight workout", "bodyweight berat badan push pull kaki core perut jadwal"),
    (33, 43, "Nutrition and diet", "diet nutrisi makanan"),
    (44, 44, "Strength priorities", "kekuatan strength prioritas"),
    (45, 47, "Squat technique", "squat jongkok teknik barbel"),
    (48, 48, "Bench press technique", "bench press dada teknik"),
    (49, 50, "Deadlift and bench press technique", "deadlift teknik bench press"),
    (51, 51, "One-rep maximum", "1rm beban repetisi persentase maksimal"),
    (52, 54, "12-week strength workout", "strength kekuatan jadwal minggu fase bench press squat deadlift"),
    (55, 55, "Speed and power priorities", "speed power kecepatan daya ledak"),
    (56, 59, "Olympic lifting technique", "snatch clean jerk olympic teknik"),
    (60, 65, "12-week speed and power workout", "speed power kecepatan daya ledak fase push pull"),
    (66, 69, "Endurance and running technique", "endurance daya tahan lari teknik konsistensi"),
    (70, 70, "20-week marathon guide", "marathon maraton jadwal lari mingguan"),
    (71, 73, "Rowing technique and workout", "rowing dayung repetisi daya tahan"),
]

WARNINGS = {
    2: ["The variable ranges are explicitly described as averages, not a personalized beginner prescription."],
    3: ["Only names and short captions of the five laws appear in this PDF; absent chapter explanations must not be invented."],
    9: ["Medical/immunity chart: excluded from workout consultation retrieval."],
    20: ["Internal conflict: the Rest column says 60 secs, while the hands-elevated coaching cue says 30 seconds. Do not choose either as the definitive rest prescription."],
    46: ["Internal unit conflict: the text equates 20–25 cm with 4–6 inches. Do not present these as equivalent or silently correct the source."],
    60: ["Compare with PDF 61: Strength–Speed is 80–100% here versus 80–90% on the chart; Speed–Strength is 30–80% here versus 30–60% on the chart. Do not give a single definitive range for these conflicting zones."],
    61: ["Compare with PDF 60: the prose and chart disagree on Strength–Speed and Speed–Strength percentage ranges. These are guidelines, not a universal individual prescription."],
    62: ["Internal conflict: the Olympic-lifting paragraph says weeks 5–8, while its overview and the detailed table on PDF 65 say weeks 9–12. State ambiguity if asked about that timing; do not silently resolve it."],
    65: ["Related PDF 62 has conflicting phase timing in its prose. This table is labeled weeks 9–12; do not pretend the whole document is consistent."],
    70: ["The printed marathon table uses column positions, red text for race pace, and parentheses for above-threshold pace. Plain extracted text loses red color and some column alignment. Do not infer a day or race-pace row that is not unambiguous in the text. The sum/units of week 1 are not clear enough for a distance prescription."],
}


def clean(text: str) -> str:
    text = re.sub(r"9780751572544SAMP\.indd[^\n]*", "", text)
    return re.sub(r"\n{3,}", "\n\n", text).strip()


def build() -> None:
    reader = PdfReader(PDF)
    pages = []
    for number, page in enumerate(reader.pages, 1):
        raw = page.extract_text() or ""
        printed = re.search(r"9780751572544SAMP\.indd\s+(\d+)", raw)
        section, keywords = next((title, words) for start, end, title, words in SECTIONS if start <= number <= end)
        section = {
            56: "Clean and jerk: setup and pull",
            57: "Clean and jerk: receiving the bar and standing",
            58: "The jerk; snatch setup and pull",
            59: "Snatch: receiving the bar and standing",
        }.get(number, section)
        text = clean(raw)
        if number == 1:
            text = "The World’s Fittest Book. Ross Edgley."
        if number == 12:
            # Transcribed from the visually reviewed three-column overview (printed p.111).
            text = (
                "YOUR 12-WEEK WORLDWIDE BODYWEIGHT WORKOUT\n"
                "Weeks 1–4: 4-day routine. Key focus: improve strength, refine technique, develop kinaesthetic awareness and better mechanical energy. "
                "Monday: Push. Tuesday: Lower-body. Wednesday: Rest. Thursday: Pull. Friday: Full-body Centurion. Saturday: Rest. Sunday: Rest.\n"
                "Weeks 5–8: 5-day routine. Key focus: improve strength, refine technique, increase work capacity. "
                "Monday: Push. Tuesday: Lower-body. Wednesday: Pull. Thursday: Rest. Friday: Full-body Caveman Conditioning. Saturday: Ab Workout. Sunday: Rest.\n"
                "Weeks 9–12: 6-day routine. Key focus: improve strength, refine technique, specialise. "
                "Monday: Push. Tuesday: Lower-body. Wednesday: Pull. Thursday: Ab Workout. Friday: Full-body Caveman Conditioning. Saturday: Sport Specific. Sunday: Rest."
            )
        if number == 52:
            # Restore overview column associations verified against the PDF image (printed p.211).
            details = text[text.index("3-DAY ROUTINE | WEEKS 1–4 | MONDAY"):]
            text = (
                "YOUR 12-WEEK STRENGTH WORKOUT\n"
                "Weeks 1–4: 3-day routine. Practise technique, increase muscle strength, functional strength training, powerlifting-centric. "
                "Monday: Bench Press. Tuesday: Rest. Wednesday: Squat. Thursday: Rest. Friday: Deadlift. Saturday: Rest. Sunday: Rest.\n"
                "Weeks 5–8: 4-day routine. Practise technique, increase work capacity, functional and structural training, improve muscle size and strength. "
                "Monday: Bench Press. Tuesday: Deadlift. Wednesday: Rest. Thursday: Squat. Friday: Shoulder Press. Saturday: Rest. Sunday: Rest.\n"
                "Weeks 9–12: 5-day routine. Practise technique, increase muscle size, structural strength training, bodybuilding-centric. "
                "Monday: Bench Press. Tuesday: Deadlift. Wednesday: Arms. Thursday: Squat. Friday: Shoulder Press. Saturday: Rest. Sunday: Rest.\n\n"
                + details
            )
        phase = None
        for start, end, weeks in [(13, 16, [1, 4]), (17, 27, [5, 8]), (28, 32, [9, 12]), (53, 53, [5, 8]), (54, 54, [9, 12]), (63, 63, [1, 4]), (64, 64, [5, 8]), (65, 65, [9, 12])]:
            if start <= number <= end:
                phase = weeks
        pages.append({
            "id": f"book-pdf-{number:03d}", "pdf_page": number,
            "printed_page": int(printed.group(1)) if printed else None,
            "title": section, "keywords": keywords,
            "eligible": number != 1 and number != 9 and not 33 <= number <= 43 and len(text) > 110,
            "phase": phase, "excerpt": text, "warnings": WARNINGS.get(number, []),
            "original_text": clean(raw),
            "document_id": "worlds-fittest-book",
        })
    result = {
        "version": 1,
        "source": {"id": "worlds-fittest-book", "title": "The World’s Fittest Book", "author": "Ross Edgley", "file": PDF.name, "sha256": sha256(PDF.read_bytes()).hexdigest(), "pdf_pages": len(reader.pages)},
        "pages": pages,
    }
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Indexed {len(pages)} PDF pages; {sum(p['eligible'] for p in pages)} usable workout passages; {OUTPUT.relative_to(ROOT)}")


def build_program() -> None:
    reader = PdfReader(PROGRAM_PDF)
    pages = []
    titles = {
        1: "Program Latihan 12 Minggu — 4-day workout splits",
        2: "Tujuan program: kekuatan, teknik, mind-muscle connection",
        3: "Istilah latihan: repetisi, set, RPE, ROM, DB, EZ",
        4: "Compound, isolation, upper-lower split, progressive overload, tempo",
        5: "Jadwal upper/lower split 4 hari dan hari pemulihan",
        6: "Minggu 1–4: Day 1 upper body dan Day 2 lower body",
        7: "Minggu 1–4: Day 4 upper body dan Day 5 lower body",
        8: "Minggu 5–8: Day 1 upper body dan Day 2 lower body",
        9: "Minggu 5–8: Day 4 upper body dan Day 5 lower body",
        10: "Minggu 9–12: Day 1 upper body dan Day 2 lower body",
        11: "Minggu 9–12: Day 4 upper body dan Day 5 lower body",
    }
    for number, page in enumerate(reader.pages, 1):
        original = (page.extract_text() or "").strip()
        # Remove repeated decorative letter-spaced footers, preserving all actual table rows.
        excerpt = re.sub(r"P R O G R A M[^\n]*|\( 4 D A Y S \)[^\n]*", "", original).strip()
        phase = [1, 4] if number in [6, 7] else [5, 8] if number in [8, 9] else [9, 12] if number in [10, 11] else None
        title = titles.get(number, "Ilustrasi gerakan: " + " ".join(original.split()))
        warnings = [
            "This is the separate Program Latihan 12 Minggu (4 days) upper/lower program, not the strength/bodyweight program from The World's Fittest Book. Keep its days, phases, RPE and exercise variations together; do not mix their parameters."
        ]
        if number >= 12:
            warnings.append("Illustration labels only; do not infer detailed coaching instructions absent from the extracted text.")
        if number == 4:
            warnings.append("The page contains a CASE STUDY template label; use the explicit progression explanation, not that label as an exercise category.")
        if phase == [9, 12]:
            warnings.append("Later-phase RPE 9 is not a default starting prescription for an unassessed beginner.")
        pages.append({
            "id": f"program12-pdf-{number:03d}", "document_id": "program-latihan-12-minggu",
            "pdf_page": number, "printed_page": None, "title": title,
            "keywords": "program latihan 12 minggu 4 hari 4 days upper lower split rpe rom dumbbell progresi " + title,
            "eligible": 2 <= number <= 11, "phase": phase,
            "excerpt": title + "\n\n" + excerpt, "original_text": original, "warnings": warnings,
        })
    result = {
        "version": 1,
        "source": {"id": "program-latihan-12-minggu", "title": "Program Latihan 12 Minggu (4 days)", "file": PROGRAM_PDF.name, "sha256": sha256(PROGRAM_PDF.read_bytes()).hexdigest(), "pdf_pages": len(reader.pages)},
        "pages": pages,
    }
    PROGRAM_OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    PROGRAM_OUTPUT.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Indexed {len(pages)} additional PDF pages; {sum(p['eligible'] for p in pages)} usable passages; {PROGRAM_OUTPUT.relative_to(ROOT)}")


if __name__ == "__main__":
    build()
    build_program()
