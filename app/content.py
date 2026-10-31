"""
Public Health Content Provider for Covid-flow.
Reads verified health information, guidelines, myths vs facts,
and helpline directories from data/content.json.
"""

import json
from pathlib import Path
from flask import current_app

DATA_FILE = Path(__file__).resolve().parent.parent / "data" / "content.json"

_CACHED_CONTENT = None

def _normalize_symptom_item(item, default_severity="Mild", default_icon="activity") -> dict:
    """Safely convert any symptom format (str, stringified dict, dict) into a structured dictionary."""
    if isinstance(item, str):
        item_str = item.strip()
        if (item_str.startswith("{") and item_str.endswith("}")):
            try:
                import json, ast
                try:
                    data = json.loads(item_str)
                except Exception:
                    data = ast.literal_eval(item_str)
                if isinstance(data, dict):
                    return _normalize_symptom_item(data, default_severity, default_icon)
            except Exception:
                pass
        return {
            "name": item_str,
            "desc": "Observable clinical manifestation associated with SARS-CoV-2 infection.",
            "severity": default_severity,
            "frequency": "Reported Symptom",
            "action": "",
            "icon": default_icon,
        }
    elif isinstance(item, dict):
        return {
            "name": str(item.get("name") or item.get("title") or "Clinical Indicator"),
            "desc": str(item.get("desc") or item.get("description") or item.get("action") or "Observable clinical sign requiring medical monitoring."),
            "severity": str(item.get("severity") or default_severity),
            "frequency": str(item.get("frequency") or ""),
            "action": str(item.get("action") or ""),
            "icon": str(item.get("icon") or default_icon),
        }
    return {
        "name": str(item),
        "desc": "",
        "severity": default_severity,
        "frequency": "",
        "action": "",
        "icon": default_icon,
    }


def _normalize_symptoms(symptoms: dict) -> dict:
    """Normalize symptom sections so template receives clean, non-empty dictionaries."""
    if not isinstance(symptoms, dict):
        symptoms = {}

    common_raw = symptoms.get("common") or symptoms.get("most_common") or []
    less_raw = symptoms.get("less_common") or []
    emergency_raw = symptoms.get("emergency") or symptoms.get("serious") or []

    # Ensure robust fallbacks if any list is empty
    if not common_raw:
        common_raw = [
            {"name": "Fever or Chills", "desc": "High body temperature (≥ 38°C / 100.4°F), rigors, and sweating.", "severity": "Mild–Moderate", "frequency": "Very Common (80–90%)", "icon": "thermometer"},
            {"name": "Dry Continuous Cough", "desc": "Frequent hacking cough episodes without phlegm lasting several hours.", "severity": "Mild–Moderate", "frequency": "Very Common (60–70%)", "icon": "cough"},
            {"name": "Fatigue & Body Aches", "desc": "Unusual exhaustion, heavy weakness, and generalized muscle aches.", "severity": "Mild–Moderate", "frequency": "Common (40–50%)", "icon": "activity"},
            {"name": "Loss of Taste or Smell", "desc": "Sudden blunting of sensory aroma detection or taste loss (anosmia/ageusia).", "severity": "Mild", "frequency": "Common (30–40%)", "icon": "sparkles"}
        ]
    if not less_raw:
        less_raw = [
            {"name": "Sore Throat & Hoarseness", "desc": "Scratchy throat, pharyngeal inflammation, and painful swallowing.", "severity": "Mild", "frequency": "Moderate (15–20%)", "icon": "mic-off"},
            {"name": "Headache & Sinus Pressure", "desc": "Frontal or band-like tension headache resistant to mild analgesics.", "severity": "Mild–Moderate", "frequency": "Moderate (10–15%)", "icon": "shield-alert"},
            {"name": "Nasal Congestion", "desc": "Stuffy, blocked nose or persistent rhinorrhea.", "severity": "Mild", "frequency": "Occasional (10%)", "icon": "wind"},
            {"name": "Gastrointestinal Symptoms", "desc": "Nausea, diarrhea, vomiting, or abdominal cramping.", "severity": "Mild", "frequency": "Occasional (5–10%)", "icon": "alert-circle"}
        ]
    if not emergency_raw:
        emergency_raw = [
            {"name": "Difficulty Breathing or Shortness of Breath", "desc": "Gasping for breath, dyspnea at rest, or SpO2 oxygen saturation below 94%.", "severity": "Critical", "action": "Immediate hospital admission and oxygen therapy required.", "icon": "lungs"},
            {"name": "Persistent Chest Pain or Pressure", "desc": "Continuous retrosternal heaviness or sharp pleuritic chest discomfort.", "severity": "Critical", "action": "Call emergency helpline (1075 / 112) or reach an ER without delay.", "icon": "heart"},
            {"name": "Confusion or Inability to Stay Awake", "desc": "Acute delirium, altered mental status, or neurological lethargy.", "severity": "Critical", "action": "Immediate neuro-critical resuscitation required.", "icon": "zap-off"},
            {"name": "Cyanosis (Pale, Gray, or Blue Lips/Nails)", "desc": "Visible bluish discoloration of oral mucosa or nail beds from hypoxemia.", "severity": "Emergency", "action": "Sign of arterial hypoxia — emergency oxygen required.", "icon": "alert-octagon"}
        ]

    normalized_common = [_normalize_symptom_item(item, "Mild–Moderate", "activity") for item in common_raw]
    normalized_less = [_normalize_symptom_item(item, "Mild", "alert-circle") for item in less_raw]
    normalized_emergency = [_normalize_symptom_item(item, "Critical", "alert-triangle") for item in emergency_raw]

    return {
        "common": normalized_common,
        "most_common": normalized_common,
        "less_common": normalized_less,
        "emergency": normalized_emergency,
        "serious": normalized_emergency,
        "source": symptoms.get("source", "WHO Clinical Management of COVID-19"),
        "sourceUrl": symptoms.get("sourceUrl", "https://www.who.int/publications/i/item/WHO-2019-nCoV-clinical-2023.2")
    }


def get_health_content() -> dict:
    """Load and return health guidance, symptoms, prevention, myths, and helplines."""
    global _CACHED_CONTENT
    if _CACHED_CONTENT is not None and not current_app.config.get("TESTING"):
        return _CACHED_CONTENT

    content = None
    if DATA_FILE.exists():
        try:
            with open(DATA_FILE, "r", encoding="utf-8") as f:
                content = json.load(f)
        except Exception as e:
            current_app.logger.error(f"Error loading data/content.json: {e}")

    if not content:
        # Default fallback content
        content = {
            "lastReviewed": "2026-10-31",
            "disclaimer": "This portal provides educational public-health information and does not substitute for professional medical advice.",
            "prevention": [
                {
                    "id": "masks",
                    "title": "Wear a Well-Fitted Mask",
                    "desc": "Wear high-filtration masks (N95/FFP2 or surgical masks) in crowded or poorly ventilated indoor spaces.",
                    "category": "personal",
                    "source": "WHO Infection Prevention Guidance",
                    "sourceUrl": "https://www.who.int"
                },
                {
                    "id": "ventilation",
                    "title": "Improve Indoor Ventilation",
                    "desc": "Keep windows and doors open to maximize natural airflow and reduce aerosol transmission.",
                    "category": "environment",
                    "source": "WHO Guidelines on Ventilation",
                    "sourceUrl": "https://www.who.int"
                },
                {
                    "id": "hygiene",
                    "title": "Frequent Hand Hygiene",
                    "desc": "Wash hands with soap and water for at least 20 seconds or use an alcohol-based sanitizer.",
                    "category": "personal",
                    "source": "MoHFW India Guidelines",
                    "sourceUrl": "https://www.mohfw.gov.in"
                }
            ],
            "symptoms": {},
            "vaccination": {
                "title": "COVID-19 Vaccination Facts",
                "summary": "Vaccination significantly reduces the risk of severe illness, hospitalization, and death.",
                "doses": "Primary series plus booster doses recommended for high-risk individuals."
            },
            "myths": [
                {
                    "myth": "COVID-19 is just a common cold.",
                    "fact": "COVID-19 is caused by SARS-CoV-2 and can cause severe pneumonia, organ failure, and Long COVID.",
                    "source": "World Health Organization"
                },
                {
                    "myth": "Vaccines alter human DNA.",
                    "fact": "mRNA and viral vector vaccines never enter the nucleus of human cells and cannot alter DNA.",
                    "source": "CDC & WHO"
                }
            ],
            "helplines": {
                "national": [
                    {"name": "National COVID-19 Helpline", "number": "1075", "desc": "Toll-free 24/7 national helpline (MoHFW)"},
                    {"name": "Emergency Medical Response", "number": "112", "desc": "National emergency unified number"},
                    {"name": "Mental Health Support (KIRAN)", "number": "1800-599-0019", "desc": "Toll-free psychological support helpline"}
                ]
            }
        }

    content["symptoms"] = _normalize_symptoms(content.get("symptoms", {}))
    _CACHED_CONTENT = content
    return _CACHED_CONTENT
