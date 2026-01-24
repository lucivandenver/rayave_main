(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const launcher = document.createElement('button');
        launcher.type = 'button';
        launcher.className = 'rayave-chatbot__launcher';
        launcher.setAttribute('aria-label', 'Open RayAve Assistant');
        launcher.setAttribute('aria-expanded', 'false');
        launcher.innerHTML = '<i class="fas fa-seedling"></i>';

        const wrapper = document.createElement('section');
        wrapper.className = 'rayave-chatbot';
        wrapper.setAttribute('aria-hidden', 'true');

        wrapper.innerHTML = `
            <div class="rayave-chatbot__card" role="dialog" aria-modal="false" aria-label="RayAve Assistant">
                <div class="rayave-chatbot__header">
                    <div style="display:flex;align-items:start;justify-content:space-between;gap:12px;position:relative;z-index:1;">
                        <div>
                            <h3 style="margin:0 0 2px;font-size:1.45rem;">Hi! I'm RayAve.</h3>
                            <p class="rayave-chatbot__meta">Ask me about bouquets, delivery windows, or custom greens.</p>
                        </div>
                        <button class="rayave-chatbot__close" type="button" data-chatbot-close aria-label="Close chat">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <span class="rayave-chatbot__status-dot" aria-hidden="true"></span>
                </div>
                <div class="rayave-chatbot__body">
                    <div class="rayave-chatbot__thread" data-chatbot-thread>
                        <div class="rayave-chatbot__bubble rayave-chatbot__bubble--assistant">
                            <div class="rayave-chatbot__bubble-text">
                                Welcome to Raymundo Avenue! I'm here with fresh picks, delivery advice, and care tips for your next arrangement.
                            </div>
                        </div>
                    </div>
                    <div class="rayave-chatbot__chips">
                        <button type="button" class="rayave-chatbot__chip" data-chatbot-chip>Any bestseller bouquets?</button>
                        <button type="button" class="rayave-chatbot__chip" data-chatbot-chip>Same-day delivery?</button>
                        <button type="button" class="rayave-chatbot__chip" data-chatbot-chip>Can I customize?</button>
                    </div>
                    <form class="rayave-chatbot__form" data-chatbot-form autocomplete="off">
                        <input type="text" class="rayave-chatbot__input" data-chatbot-input placeholder="Type your message..." aria-label="Type your message">
                        <button class="rayave-chatbot__send" type="submit" aria-label="Send message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        `;

        document.body.appendChild(wrapper);
        document.body.appendChild(launcher);

        const closeBtn = wrapper.querySelector('[data-chatbot-close]');
        const form = wrapper.querySelector('[data-chatbot-form]');
        const input = wrapper.querySelector('[data-chatbot-input]');
        const thread = wrapper.querySelector('[data-chatbot-thread]');
        const chips = wrapper.querySelectorAll('[data-chatbot-chip]');

        let isOpen = false;
        let typingBubble = null;

        function toggleChat(forceState) {
            isOpen = typeof forceState === 'boolean' ? forceState : !isOpen;
            wrapper.classList.toggle('rayave-chatbot--open', isOpen);
            wrapper.setAttribute('aria-hidden', String(!isOpen));
            launcher.classList.toggle('rayave-chatbot__launcher--active', isOpen);
            launcher.setAttribute('aria-expanded', String(isOpen));
            if (isOpen) {
                setTimeout(() => input.focus(), 240);
            }
        }

        function showTypingIndicator() {
            if (typingBubble) {
                return;
            }
            typingBubble = document.createElement('div');
            typingBubble.className = 'rayave-chatbot__bubble rayave-chatbot__bubble--assistant';
            typingBubble.innerHTML = '<div class="rayave-chatbot__bubble-text">…</div>';
            thread.appendChild(typingBubble);
            thread.scrollTop = thread.scrollHeight;
        }

        function hideTypingIndicator() {
            if (typingBubble) {
                typingBubble.remove();
                typingBubble = null;
            }
        }

        function appendMessage(text, role) {
            const bubble = document.createElement('div');
            bubble.className = 'rayave-chatbot__bubble rayave-chatbot__bubble--' + role;
            const bubbleText = document.createElement('div');
            bubbleText.className = 'rayave-chatbot__bubble-text';
            bubbleText.innerHTML = renderMessageContent(text);
            bubble.appendChild(bubbleText);
            thread.appendChild(bubble);
            thread.scrollTop = thread.scrollHeight;
        }

        function handleUserMessage(message) {
            const clean = message.trim();
            if (!clean) {
                return;
            }
            appendMessage(clean, 'user');
            input.value = '';
            showTypingIndicator();
            requestAssistantReply(clean)
                .then((reply) => {
                    hideTypingIndicator();
                    appendMessage(reply, 'assistant');
                })
                .catch((error) => {
                    console.error('Chatbot error:', error);
                    hideTypingIndicator();
                    const fallback = error && error.message ? error.message : 'RayAve Assistant is unavailable right now. Please try again shortly.';
                    appendMessage(fallback, 'assistant');
                });
        }

        function requestAssistantReply(userMessage) {
            return fetch('chatbot/rayave-assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: userMessage })
            })
                .then(async (response) => {
                    let data;
                    try {
                        data = await response.json();
                    } catch (parseError) {
                        throw new Error('Server returned an unreadable response.');
                    }

                    if (!response.ok) {
                        const serverMessage = data && data.error ? data.error : 'Unable to reach RayAve Assistant right now.';
                        throw new Error(serverMessage);
                    }

                    if (!data || !data.success) {
                        const errorMessage = data && data.error ? data.error : 'RayAve Assistant cannot answer right now.';
                        throw new Error(errorMessage);
                    }

                    return data.reply;
                });
        }

        launcher.addEventListener('click', function () {
            toggleChat();
        });

        closeBtn.addEventListener('click', function () {
            toggleChat(false);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            handleUserMessage(input.value);
        });

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                handleUserMessage(chip.textContent || '');
                toggleChat(true);
            });
        });
    });

    function renderMessageContent(text) {
        const safe = escapeHtml(String(text ?? ''));
        const withLinks = safe.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, function (_, label, url) {
            return '<a href="' + url + '" target="_blank" rel="noopener">' + label + '</a>';
        });
        return withLinks.replace(/\n/g, '<br>');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
})();
