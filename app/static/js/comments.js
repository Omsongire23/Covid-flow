/**
 * comments.js — Community page comment fetch, post, delete, pagination.
 */
import { getCsrf, showToast, escHtml, apiFetch } from './utils.js';
import { loadPatterns, attachValidation } from './validate.js';
import { initCharCounter } from './forms.js';

let currentPage = 1;
let totalPages  = 1;
let _patterns   = null;

async function init() {
  const list    = document.getElementById('comments-list');
  const form    = document.getElementById('comment-form');
  const loadMore= document.getElementById('load-more-btn');
  if (!list) return;

  _patterns = await loadPatterns();

  // Load initial comments
  await loadComments(1, list);

  // Load more
  if (loadMore) {
    loadMore.addEventListener('click', async () => {
      if (currentPage < totalPages) {
        await loadComments(currentPage + 1, list, true);
      }
    });
  }

  // Comment form
  if (form) {
    const bodyEl = form.querySelector('#comment-body');
    initCharCounter(bodyEl);
    if (bodyEl) attachValidation(bodyEl, _patterns, 'comment');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body = bodyEl?.value ?? '';

      // Client-side validation
      const re = new RegExp(_patterns?.comment?.pattern ?? '.+', 'u');
      if (!re.test(body)) {
        showToast(_patterns?.comment?.message ?? 'Invalid comment.', 'error');
        return;
      }

      const btn = form.querySelector('[type="submit"]');
      if (btn) { btn.disabled = true; btn.textContent = 'Posting…'; }

      try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const res  = await fetch('/api/comments.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
          body: JSON.stringify({ action: 'create', body }),
        });
        const data = await res.json();
        if (data.ok) {
          form.reset();
          if (bodyEl) bodyEl.dispatchEvent(new Event('input'));
          // Optimistic prepend
          prependComment(data.comment, list);
          showToast('Comment posted!', 'success');
        } else {
          showToast(data.error?.message ?? 'Could not post comment.', 'error');
        }
      } catch {
        showToast('Network error. Please try again.', 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Post Comment'; }
      }
    });
  }
}

async function loadComments(page, list, append = false) {
  if (!append) {
    list.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div></div>';
  }
  try {
    const res  = await fetch(`/api/comments.php?page=${page}`);
    const data = await res.json();
    if (!data.ok) throw new Error(data.error?.message);

    currentPage = data.data.page;
    totalPages  = data.data.pages;

    const items = data.data.items;
    if (!append) list.innerHTML = '';

    if (items.length === 0 && !append) {
      list.innerHTML = `<div class="empty-state">
        <div class="empty-state-icon">💬</div>
        <h3>No comments yet</h3>
        <p>Be the first to share your thoughts.</p>
      </div>`;
    } else {
      items.forEach(c => list.appendChild(buildComment(c)));
    }

    const loadMore = document.getElementById('load-more-btn');
    if (loadMore) loadMore.hidden = currentPage >= totalPages;

  } catch (err) {
    list.innerHTML = `<div class="empty-state">
      <div class="empty-state-icon">⚠️</div>
      <h3>Could not load comments</h3>
      <p>${escHtml(err.message)}</p>
      <button class="btn btn-outline mt-4" data-action="reload">Retry</button>
    </div>`;
  }
}

function buildComment(c) {
  const div = document.createElement('div');
  div.className = 'comment-card';
  div.id = `comment-${c.id}`;
  const initial = c.username[0].toUpperCase();
  const body    = escHtml(c.body).replace(/\n/g, '<br>');
  div.innerHTML = `
    <div class="comment-header">
      <div class="comment-meta">
        <div class="comment-avatar" aria-hidden="true">${initial}</div>
        <div>
          <div class="comment-username">${escHtml(c.username)}</div>
          <div class="comment-time">${escHtml(c.createdAt)}</div>
        </div>
      </div>
      ${c.mine ? `<button class="btn btn-sm btn-ghost delete-comment-btn"
        data-id="${c.id}" aria-label="Delete your comment"
        style="color:var(--danger)">Delete</button>` : ''}
    </div>
    <div class="comment-body">${body}</div>
  `;

  if (c.mine) {
    div.querySelector('.delete-comment-btn').addEventListener('click', () => confirmDelete(c.id, div));
  }
  return div;
}

function prependComment(c, list) {
  const el = buildComment(c);
  const empty = list.querySelector('.empty-state');
  if (empty) empty.remove();
  list.prepend(el);
}

const deleteDialog = document.getElementById('delete-comment-dialog');

async function confirmDelete(id, el) {
  if (deleteDialog) {
    deleteDialog.showModal();
    deleteDialog.querySelector('#confirm-delete-btn').onclick = () => {
      deleteDialog.close();
      doDelete(id, el);
    };
    deleteDialog.querySelector('#cancel-delete-btn').onclick = () => deleteDialog.close();
  } else {
    if (confirm('Delete this comment?')) doDelete(id, el);
  }
}

async function doDelete(id, el) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  try {
    const res  = await fetch('/api/comments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ action: 'delete', id }),
    });
    const data = await res.json();
    if (data.ok) {
      el.style.transition = 'opacity 200ms';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 200);
      showToast('Comment deleted.', 'success');
    } else {
      showToast(data.error?.message ?? 'Could not delete.', 'error');
    }
  } catch {
    showToast('Network error.', 'error');
  }
}

document.addEventListener('DOMContentLoaded', init);
