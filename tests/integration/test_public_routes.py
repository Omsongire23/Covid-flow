"""
Integration Tests for Public Informational Routes:
Dashboard, Statistics, Prevention, Symptoms, Vaccination, Myths, Helplines.
"""

def test_home_page_displays_metrics(client):
    response = client.get("/")
    assert response.status_code == 200
    assert b"COVID-19 Health Intelligence" in response.data
    assert b"Total Confirmed Cases" in response.data
    assert b"Active Cases" in response.data
    assert b"Total Deaths" in response.data

def test_statistics_page_renders_chart_and_table(client):
    response = client.get("/statistics")
    assert response.status_code == 200
    assert b"covidTrendChart" in response.data
    assert b"Country-Wise Surveillance Data" in response.data

def test_statistics_country_search_query(client):
    response = client.get("/statistics?q=India")
    assert response.status_code == 200
    assert b"India" in response.data

def test_prevention_page_content(client):
    response = client.get("/prevention")
    assert response.status_code == 200
    assert b"COVID-19 Prevention" in response.data

def test_symptoms_page_content(client):
    response = client.get("/symptoms")
    assert response.status_code == 200
    assert b"Emergency Warning Signs" in response.data
    assert b"Most Common Symptoms" in response.data

def test_vaccination_page_content(client):
    response = client.get("/vaccination")
    assert response.status_code == 200
    assert b"Vaccination Intelligence" in response.data

def test_myths_page_content(client):
    response = client.get("/myths")
    assert response.status_code == 200
    assert b"Myths vs. Scientific Facts" in response.data

def test_helplines_page_content(client):
    response = client.get("/helplines")
    assert response.status_code == 200
    assert b"1075" in response.data
    assert b"112" in response.data
