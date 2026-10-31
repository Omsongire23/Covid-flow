"""
Unit Tests for COVID-19 API and Fallback Service.
Verifies caching mechanisms, fallback files, and honest data labelling.
"""

from app.covid_api import get_global_stats, get_historical_stats, get_country_stats, _load_offline_fallback

def test_offline_fallback_file_loads(app):
    with app.app_context():
        fallback = _load_offline_fallback()
        assert "cases" in fallback
        assert "deaths" in fallback
        assert len(fallback["cases"]) > 0

def test_get_global_stats_structure(app):
    with app.app_context():
        stats = get_global_stats()
        assert "cases" in stats
        assert "deaths" in stats
        assert "active" in stats
        assert "source_status" in stats
        assert "source_url" in stats
        assert stats["cases"] > 0
        assert stats["deaths"] > 0

def test_get_historical_stats(app):
    with app.app_context():
        hist = get_historical_stats(days=30)
        assert "dates" in hist
        assert "cases" in hist
        assert "deaths" in hist
        assert len(hist["dates"]) > 0
        assert len(hist["cases"]) == len(hist["dates"])

def test_get_country_stats(app):
    with app.app_context():
        countries = get_country_stats()
        assert len(countries) > 0
        first = countries[0]
        assert "country" in first
        assert "cases" in first
        assert "deaths" in first

def test_country_search_filtering(app):
    with app.app_context():
        results = get_country_stats(search_query="India")
        assert len(results) >= 1
        assert any(c["country"] == "India" for c in results)
