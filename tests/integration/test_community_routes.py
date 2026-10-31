"""
Integration Tests for Community Discussion Routes.
Covers guest restrictions, posting, input validation, and comment deletion authorization.
"""

def test_community_page_renders_for_guests(client):
    response = client.get("/community")
    assert response.status_code == 200
    assert b"Latest Community Comments" in response.data
    assert b"Join the Conversation" in response.data

def test_guest_post_comment_redirects_to_login(client):
    response = client.post(
        "/community",
        data={"comment": "Hello community!"},
        follow_redirects=True
    )
    assert response.status_code == 200
    assert b"You must be logged in to post a comment." in response.data

def test_authenticated_user_can_post_comment(auth_client, app):
    response = auth_client.post(
        "/community",
        data={"comment": "Staying safe in Pune with double masking!"},
        follow_redirects=True
    )
    assert response.status_code == 200
    assert b"Your comment has been posted successfully!" in response.data
    assert b"Staying safe in Pune with double masking!" in response.data

    with app.app_context():
        from app.database import get_db
        comment = get_db().execute("SELECT * FROM comments WHERE body LIKE '%double masking%'").fetchone()
        assert comment is not None

def test_post_comment_rejects_xss_script_tag(auth_client):
    response = auth_client.post(
        "/community",
        data={"comment": "<script>alert('xss')</script>"},
        follow_redirects=True
    )
    assert response.status_code == 200
    assert b"no HTML tags" in response.data

def test_user_can_delete_own_comment(auth_client, app):
    # First post comment
    auth_client.post("/community", data={"comment": "Temporary comment to be deleted"})
    with app.app_context():
        from app.database import get_db
        row = get_db().execute("SELECT id FROM comments WHERE body = 'Temporary comment to be deleted'").fetchone()
        comment_id = row["id"]

    response = auth_client.post(f"/community/delete/{comment_id}", follow_redirects=True)
    assert response.status_code == 200
    assert b"Comment deleted successfully." in response.data
