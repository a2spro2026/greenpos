/**
 * GreenPOS AI — floating assistant client
 */
function qs(sel, root = document) {
    return root.querySelector(sel);
}

function markdownLite(text) {
    return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/_(.+?)_/g, '<em>$1</em>')
        .replace(/\[(.+?)\]\((https?:\/\/[^)]+|\/[^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
        .replace(/^### (.+)$/gm, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
}

function createAi() {
    const root = qs('#gp-ai-root');
    if (! root) return null;

    const fab = qs('#gp-ai-fab', root);
    const panel = qs('#gp-ai-panel', root);
    const closeBtn = qs('#gp-ai-close', root);
    const form = qs('#gp-ai-form', root);
    const input = qs('#gp-ai-input', root);
    const sendBtn = qs('#gp-ai-send', root);
    const messages = qs('#gp-ai-messages', root);
    const hints = qs('#gp-ai-hints', root);
    const personaSelect = qs('#gp-ai-persona', root);
    const contextLabel = qs('#gp-ai-context-label', root);

    let conversationId = null;
    let busy = false;
    let context = null;

    async function api(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': root.dataset.csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            ...options,
        });
        if (! res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || `Erreur ${res.status}`);
        }
        return res.json();
    }

    function appendMessage(role, content, extra = {}) {
        const el = document.createElement('div');
        el.className = `gp-ai-msg gp-ai-msg-${role}`;
        el.innerHTML = markdownLite(content);

        if (extra.citations?.length) {
            const c = document.createElement('div');
            c.className = 'gp-ai-citations';
            c.innerHTML = extra.citations
                .slice(0, 5)
                .map((x) => `<div>• <a href="${x.url}">${x.type_label}: ${x.title}</a></div>`)
                .join('');
            el.appendChild(c);
        }

        if (extra.actions?.length) {
            const box = document.createElement('div');
            box.className = 'gp-ai-actions';
            extra.actions.forEach((action) => {
                if (action.type === 'hint') return;
                if (action.requires_confirmation && action.action_log_id) {
                    const ok = document.createElement('button');
                    ok.type = 'button';
                    ok.className = 'gp-ai-action';
                    ok.textContent = `Confirmer — ${action.label || action.type}`;
                    ok.addEventListener('click', () => confirmAction(action));
                    box.appendChild(ok);

                    const no = document.createElement('button');
                    no.type = 'button';
                    no.className = 'gp-ai-action gp-ai-action-danger';
                    no.textContent = 'Annuler';
                    no.addEventListener('click', () => cancelAction(action));
                    box.appendChild(no);
                } else if (action.url) {
                    const a = document.createElement('a');
                    a.className = 'gp-ai-action';
                    a.href = action.url;
                    a.textContent = action.label || 'Ouvrir';
                    box.appendChild(a);
                }
            });
            if (box.childNodes.length) el.appendChild(box);
        }

        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
        return el;
    }

    async function confirmAction(action) {
        const url = root.dataset.confirmUrlTemplate.replace('__ID__', action.action_log_id);
        try {
            const res = await api(url, { method: 'POST', body: '{}' });
            appendMessage('assistant', res.message || 'Action confirmée.');
            if (res.redirect) {
                window.location.href = res.redirect;
            }
        } catch (e) {
            appendMessage('assistant', e.message);
        }
    }

    async function cancelAction(action) {
        const url = root.dataset.cancelUrlTemplate.replace('__ID__', action.action_log_id);
        try {
            await api(url, { method: 'POST', body: '{}' });
            appendMessage('assistant', 'Action annulée.');
        } catch (e) {
            appendMessage('assistant', e.message);
        }
    }

    function renderHints(list = []) {
        hints.innerHTML = '';
        list.forEach((text) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'gp-ai-hint';
            b.textContent = text;
            b.addEventListener('click', () => {
                input.value = text;
                form.requestSubmit();
            });
            hints.appendChild(b);
        });
    }

    async function loadContext() {
        const url = `${root.dataset.contextUrl}?route=${encodeURIComponent(root.dataset.route || '')}&path=${encodeURIComponent(root.dataset.path || '')}`;
        const data = await api(url);
        context = data.context;
        contextLabel.textContent = `Contexte · ${context.label}`;
        renderHints(context.hints || []);
        personaSelect.innerHTML = '';
        (data.prompts || []).forEach((p) => {
            const opt = document.createElement('option');
            opt.value = p.persona;
            opt.textContent = `${p.icon || ''} ${p.name}`.trim();
            if (p.persona === context.persona) opt.selected = true;
            personaSelect.appendChild(opt);
        });
    }

    async function open(existingConversationId = null) {
        panel.hidden = false;
        if (existingConversationId) conversationId = existingConversationId;
        if (! context) {
            try {
                await loadContext();
            } catch {
                contextLabel.textContent = 'Assistant prêt';
            }
        }
        if (messages.childElementCount === 0) {
            appendMessage(
                'assistant',
                `Bonjour — je suis **GreenPOS AI**.\nJe suis sur la page **${context?.label || 'GreenPOS'}**.\nPosez une question, demandez une recherche ou une création (toujours validée par vous).`
            );
        }
        input.focus();
    }

    function close() {
        panel.hidden = true;
    }

    async function send(message) {
        if (! message || busy) return;
        busy = true;
        sendBtn.disabled = true;
        appendMessage('user', message);
        const typing = appendMessage('assistant', 'Réflexion…');
        typing.classList.add('gp-ai-typing');

        try {
            const data = await api(root.dataset.chatUrl, {
                method: 'POST',
                body: JSON.stringify({
                    message,
                    conversation_id: conversationId,
                    persona: personaSelect.value || context?.persona,
                    context_route: root.dataset.route,
                    context_path: root.dataset.path,
                }),
            });
            conversationId = data.conversation_id;
            typing.remove();
            appendMessage('assistant', data.message.content, {
                actions: data.message.actions,
                citations: data.message.citations,
            });
            if (data.context) {
                context = data.context;
                contextLabel.textContent = `Contexte · ${context.label}`;
            }
        } catch (e) {
            typing.remove();
            appendMessage('assistant', `Désolé, une erreur est survenue : ${e.message}`);
        } finally {
            busy = false;
            sendBtn.disabled = false;
            input.focus();
        }
    }

    fab?.addEventListener('click', () => (panel.hidden ? open() : close()));
    closeBtn?.addEventListener('click', close);
    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        const msg = input.value.trim();
        if (! msg) return;
        input.value = '';
        send(msg);
    });
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && ! e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    return { open, close, send };
}

const GreenPosAI = createAi();
window.GreenPosAI = GreenPosAI;

document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'a') {
        e.preventDefault();
        GreenPosAI?.open();
    }
});
