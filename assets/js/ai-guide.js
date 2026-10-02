/**
 * NGO AI Informer & Guider - Interactive Public Widget
 * Jaysmrutti Foundation
 */
(function () {
    'use strict';

    // State management
    const state = {
        isOpen: false,
        isLoading: false,
        botName: 'NGO Smart Guide',
        messages: [],
        suggestions: []
    };

    // Load persisted chat from sessionStorage
    try {
        const saved = sessionStorage.getItem('ngo_ai_user_chat');
        if (saved) {
            const parsed = JSON.parse(saved);
            if (Array.isArray(parsed.messages)) state.messages = parsed.messages;
            if (parsed.botName) state.botName = parsed.botName;
        }
    } catch (e) {}

    // Markdown / rich text formatter helper
    function formatMarkdown(text) {
        if (!text) return '';
        let escaped = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Headers
        escaped = escaped.replace(/^### (.*$)/gim, '<h4 class="font-bold text-gray-900 dark:text-white text-base mt-2 mb-1.5 flex items-center gap-1.5">$1</h4>');
        escaped = escaped.replace(/^#### (.*$)/gim, '<h5 class="font-semibold text-gray-800 dark:text-gray-100 text-sm mt-2 mb-1">$1</h5>');

        // Bold & Italic
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-gray-900 dark:text-white">$1</strong>');
        escaped = escaped.replace(/\*(.*?)\*/g, '<em class="italic">$1</em>');
        escaped = escaped.replace(/`([^`]+)`/g, '<code class="bg-gray-100 dark:bg-gray-700 text-blue-600 dark:text-blue-400 px-1.5 py-0.5 rounded text-xs font-mono font-semibold">$1</code>');

        // Blockquotes
        escaped = escaped.replace(/^> (.*$)/gim, '<div class="border-l-4 border-amber-500 bg-amber-50 dark:bg-amber-900/20 text-amber-900 dark:text-amber-200 px-3 py-1.5 my-2 rounded text-xs leading-relaxed">$1</div>');

        // Markdown links [label](url)
        escaped = escaped.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-[#1070B0] dark:text-sky-400 font-semibold underline hover:text-[#F0A010] transition-colors">$1</a>');

        // Tables
        const lines = escaped.split('\n');
        let inTable = false;
        let tableHtml = '<div class="overflow-x-auto my-2 rounded-lg border border-gray-200 dark:border-gray-700"><table class="w-full text-xs text-left text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-gray-700">';
        const formattedLines = [];

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line.startsWith('|') && line.endsWith('|')) {
                if (!inTable) {
                    inTable = true;
                    tableHtml = '<div class="overflow-x-auto my-2.5 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm"><table class="w-full text-xs text-left text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-gray-700">';
                }
                const cells = line.split('|').filter((_, idx, arr) => idx > 0 && idx < arr.length - 1).map(c => c.trim());
                if (cells.some(c => c.includes('---'))) {
                    // separator line, skip
                    continue;
                }
                const isHeader = i > 0 && lines[i + 1] && lines[i + 1].includes('---');
                const rowTag = isHeader ? 'th' : 'td';
                const rowClass = isHeader ? 'bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white font-bold px-2.5 py-1.5' : 'px-2.5 py-1.5 odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/40 dark:even:bg-gray-800/40';
                
                tableHtml += '<tr>';
                cells.forEach(c => {
                    tableHtml += `<${rowTag} class="${rowClass}">${c}</${rowTag}>`;
                });
                tableHtml += '</tr>';
            } else {
                if (inTable) {
                    tableHtml += '</table></div>';
                    formattedLines.push(tableHtml);
                    inTable = false;
                }
                formattedLines.push(line);
            }
        }
        if (inTable) {
            tableHtml += '</table></div>';
            formattedLines.push(tableHtml);
        }

        escaped = formattedLines.join('\n');

        // Lists
        escaped = escaped.replace(/^\s*•\s+(.*$)/gim, '<li class="flex items-start gap-1.5 ml-1 my-0.5"><span class="text-[#F0A010] mt-0.5">•</span><span>$1</span></li>');
        escaped = escaped.replace(/(<li.*<\/li>)/s, '<ul class="my-1.5 space-y-0.5 text-xs">$1</ul>');

        // Paragraphs
        escaped = escaped.replace(/\n\n+/g, '<div class="h-2"></div>');
        escaped = escaped.replace(/\n/g, '<br/>');

        return escaped;
    }

    function saveState() {
        try {
            sessionStorage.setItem('ngo_ai_user_chat', JSON.stringify({
                botName: state.botName,
                messages: state.messages.slice(-20) // Keep last 20 messages
            }));
        } catch (e) {}
    }

    // Build DOM Elements
    function initWidget() {
        if (document.getElementById('ngo-ai-widget-root')) return;

        const root = document.createElement('div');
        root.id = 'ngo-ai-widget-root';
        root.className = 'fixed bottom-6 left-6 z-[9999] font-sans text-sm';
        root.innerHTML = `
            <!-- Floating Trigger Button -->
            <div id="ngo-ai-trigger-container" class="relative group">
                <button id="ngo-ai-trigger-btn" type="button" 
                    class="flex items-center gap-2.5 px-4 py-3 bg-gradient-to-r from-[#1070B0] to-[#0d598c] hover:from-[#F0A010] hover:to-[#d48b0a] text-white rounded-full shadow-2xl transition-all duration-300 transform hover:scale-105 active:scale-95 focus:outline-none border-2 border-white/20">
                    <div class="relative w-7 h-7 flex items-center justify-center bg-white/20 rounded-full">
                        <i class="fa-solid fa-sparkles text-sm text-yellow-300 animate-pulse"></i>
                        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-green-400 border-2 border-white rounded-full animate-ping"></span>
                        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-green-400 border-2 border-white rounded-full"></span>
                    </div>
                    <span class="font-bold tracking-wide text-xs sm:text-sm whitespace-nowrap">AI Guide</span>
                </button>

                <!-- Tooltip Callout -->
                <div id="ngo-ai-tooltip" class="absolute bottom-full left-0 mb-3 bg-gray-900 text-white text-xs px-3.5 py-2 rounded-xl shadow-xl whitespace-nowrap pointer-events-none transition-all duration-300 opacity-0 transform translate-y-1 group-hover:opacity-100 group-hover:translate-y-0">
                    👋 Need help? Ask how to donate, join, or volunteer!
                    <div class="absolute top-full left-6 -mt-1 border-4 border-transparent border-t-gray-900"></div>
                </div>
            </div>

            <!-- Chat Modal Window -->
            <div id="ngo-ai-chat-window" 
                class="fixed bottom-6 left-6 sm:left-6 w-[calc(100vw-2rem)] sm:w-[420px] max-w-[420px] h-[580px] max-h-[85vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 flex flex-col overflow-hidden transition-all duration-300 transform scale-95 opacity-0 pointer-events-none origin-bottom-left z-[10000]">
                
                <!-- Window Header -->
                <div class="bg-gradient-to-r from-[#1070B0] via-[#1070B0] to-[#F0A010] text-white px-4 py-3.5 flex items-center justify-between shadow-md shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-white/20 backdrop-blur flex items-center justify-center border border-white/30 text-white shadow-inner">
                            <i class="fa-solid fa-hands-holding-child text-base"></i>
                        </div>
                        <div>
                            <h3 id="ngo-ai-header-title" class="font-bold text-sm leading-tight tracking-wide flex items-center gap-1.5">
                                NGO Smart Guide
                                <span class="bg-emerald-400/90 text-gray-900 text-[10px] font-extrabold px-1.5 py-0.2 rounded-full uppercase">AI</span>
                            </h3>
                            <p class="text-[11px] text-blue-100 flex items-center gap-1 mt-0.5">
                                <span class="w-1.5 h-1.5 bg-emerald-300 rounded-full animate-pulse"></span>
                                Online • 24/7 Informer & Guider
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button id="ngo-ai-reset-btn" title="Clear & Restart Chat" class="p-1.5 hover:bg-white/20 rounded-lg text-white/80 hover:text-white transition">
                            <i class="fa-solid fa-rotate-right text-xs"></i>
                        </button>
                        <button id="ngo-ai-close-btn" title="Close" class="p-1.5 hover:bg-white/20 rounded-lg text-white/80 hover:text-white transition">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Chat Messages Body -->
                <div id="ngo-ai-messages-body" class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-[#f8fafc] dark:bg-gray-900/90 text-xs">
                    <!-- Messages will be dynamically rendered here -->
                </div>

                <!-- Quick Prompt Chips / Suggestions Bar -->
                <div id="ngo-ai-chips-container" class="px-3 py-2 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-100 dark:border-gray-800 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0">
                    <!-- Suggestions rendered here -->
                </div>

                <!-- Input Footer Area -->
                <div class="p-3 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 shrink-0">
                    <form id="ngo-ai-input-form" class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <input id="ngo-ai-text-input" type="text" autocomplete="off"
                                placeholder="Ask how to donate, join, or volunteer..." 
                                class="w-full pl-3.5 pr-8 py-2.5 text-xs bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-white rounded-xl border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-[#1070B0] dark:focus:ring-sky-500 transition placeholder-gray-400 dark:placeholder-gray-500" />
                            <button type="button" id="ngo-ai-clear-input" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <i class="fa-solid fa-circle-xmark text-xs"></i>
                            </button>
                        </div>
                        <button id="ngo-ai-send-btn" type="submit" 
                            class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#1070B0] to-[#F0A010] hover:from-[#0d598c] hover:to-[#d48b0a] text-white flex items-center justify-center shadow-md hover:shadow-lg transition transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                        </button>
                    </form>
                    <div class="flex justify-between items-center mt-1.5 px-1 text-[10px] text-gray-400 dark:text-gray-500">
                        <span>⚡ Instant NGO Assistance</span>
                        <span class="hover:text-[#1070B0] cursor-pointer" onclick="window.location.href='/about'">About Our Mission</span>
                    </div>
                </div>

            </div>
        `;

        document.body.appendChild(root);

        // Bind DOM events
        bindEvents();

        // Fetch initial greeting if no previous chat
        if (state.messages.length === 0) {
            fetchInit();
        } else {
            renderMessages();
            renderSuggestions(state.suggestions);
        }
    }

    function toggleChat(forceOpen) {
        state.isOpen = typeof forceOpen === 'boolean' ? forceOpen : !state.isOpen;
        const modal = document.getElementById('ngo-ai-chat-window');
        const trigger = document.getElementById('ngo-ai-trigger-container');
        const tooltip = document.getElementById('ngo-ai-tooltip');

        if (state.isOpen) {
            modal.classList.remove('scale-95', 'opacity-0', 'pointer-events-none');
            modal.classList.add('scale-100', 'opacity-100', 'pointer-events-auto');
            trigger.classList.add('hidden');
            if (tooltip) tooltip.classList.add('hidden');
            setTimeout(() => {
                const input = document.getElementById('ngo-ai-text-input');
                if (input) input.focus();
                scrollToBottom();
            }, 100);
        } else {
            modal.classList.remove('scale-100', 'opacity-100', 'pointer-events-auto');
            modal.classList.add('scale-95', 'opacity-0', 'pointer-events-none');
            trigger.classList.remove('hidden');
            if (tooltip) tooltip.classList.remove('hidden');
        }
    }

    function bindEvents() {
        const triggerBtn = document.getElementById('ngo-ai-trigger-btn');
        const closeBtn = document.getElementById('ngo-ai-close-btn');
        const resetBtn = document.getElementById('ngo-ai-reset-btn');
        const inputForm = document.getElementById('ngo-ai-input-form');
        const textInput = document.getElementById('ngo-ai-text-input');
        const clearBtn = document.getElementById('ngo-ai-clear-input');

        if (triggerBtn) triggerBtn.addEventListener('click', () => toggleChat(true));
        if (closeBtn) closeBtn.addEventListener('click', () => toggleChat(false));

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                state.messages = [];
                sessionStorage.removeItem('ngo_ai_user_chat');
                fetchInit();
            });
        }

        if (textInput) {
            textInput.addEventListener('input', (e) => {
                if (clearBtn) {
                    if (e.target.value.trim().length > 0) clearBtn.classList.remove('hidden');
                    else clearBtn.classList.add('hidden');
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (textInput) {
                    textInput.value = '';
                    clearBtn.classList.add('hidden');
                    textInput.focus();
                }
            });
        }

        if (inputForm) {
            inputForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const text = textInput ? textInput.value.trim() : '';
                if (!text || state.isLoading) return;
                textInput.value = '';
                if (clearBtn) clearBtn.classList.add('hidden');
                sendMessage(text);
            });
        }
    }

    function scrollToBottom() {
        const body = document.getElementById('ngo-ai-messages-body');
        if (body) {
            body.scrollTop = body.scrollHeight;
        }
    }

    function fetchInit() {
        state.isLoading = true;
        renderLoading(true);

        fetch('/api/ai_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ panel: 'user', action: 'init' })
        })
        .then(res => res.json())
        .then(data => {
            state.isLoading = false;
            renderLoading(false);
            if (data.success) {
                state.botName = data.bot_name || 'NGO Smart Guide';
                const headerTitle = document.getElementById('ngo-ai-header-title');
                if (headerTitle) {
                    headerTitle.innerHTML = `${state.botName} <span class="bg-emerald-400/90 text-gray-900 text-[10px] font-extrabold px-1.5 py-0.2 rounded-full uppercase">AI</span>`;
                }

                // Add greeting as first message
                state.messages.push({
                    role: 'bot',
                    text: data.greeting,
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    actions: []
                });
                state.suggestions = data.suggestions || [];
                renderMessages();
                renderSuggestions(state.suggestions);
                saveState();
            }
        })
        .catch(err => {
            state.isLoading = false;
            renderLoading(false);
            state.messages.push({
                role: 'bot',
                text: "Namaste! 🙏 Welcome to our NGO portal. How can I guide you with Donations, Membership, or Volunteering today?",
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                actions: [
                    { label: '💖 How to Donate', url: '/donate', icon: 'fa-heart' },
                    { label: '🤝 Become a Member', url: '/member-register', icon: 'fa-user-plus' },
                    { label: '🙋 Volunteer Form', url: '/volunteer-register', icon: 'fa-hand-holding-heart' }
                ]
            });
            renderMessages();
        });
    }

    function sendMessage(text) {
        if (!text) return;

        // Push User Message
        const userMsg = {
            role: 'user',
            text: text,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        };
        state.messages.push(userMsg);
        renderMessages();
        saveState();

        state.isLoading = true;
        renderLoading(true);

        fetch('/api/ai_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                panel: 'user',
                action: 'chat',
                message: text,
                history: state.messages.slice(-6)
            })
        })
        .then(res => res.json())
        .then(data => {
            state.isLoading = false;
            renderLoading(false);
            if (data.success) {
                state.messages.push({
                    role: 'bot',
                    text: data.reply,
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    actions: data.actions || []
                });
                if (data.suggestions && data.suggestions.length > 0) {
                    renderSuggestions(data.suggestions);
                }
                renderMessages();
                saveState();
            } else {
                state.messages.push({
                    role: 'bot',
                    text: data.message || "Sorry, I couldn't process that request right now. Please try again or reach our team via [Contact](/contact).",
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    actions: [{ label: 'Contact Us', url: '/contact', icon: 'fa-phone' }]
                });
                renderMessages();
                saveState();
            }
        })
        .catch(err => {
            state.isLoading = false;
            renderLoading(false);
            state.messages.push({
                role: 'bot',
                text: "⚠️ Network connection interrupted. Please check your connection or contact our team directly.",
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                actions: [{ label: 'Contact Page', url: '/contact', icon: 'fa-phone' }]
            });
            renderMessages();
        });
    }

    function renderMessages() {
        const body = document.getElementById('ngo-ai-messages-body');
        if (!body) return;

        body.innerHTML = '';

        state.messages.forEach(msg => {
            const isUser = msg.role === 'user';
            const container = document.createElement('div');
            container.className = `flex gap-2.5 ${isUser ? 'justify-end' : 'justify-start'} animate-fade-in`;

            let innerHtml = '';

            if (!isUser) {
                innerHtml += `
                    <div class="w-6 h-6 rounded-full bg-[#1070B0] text-white flex items-center justify-center shrink-0 mt-0.5 text-[11px] shadow">
                        <i class="fa-solid fa-hands-holding-child"></i>
                    </div>
                `;
            }

            innerHtml += `
                <div class="max-w-[85%] ${isUser ? 'bg-gradient-to-r from-[#1070B0] to-[#0e5c91] text-white rounded-2xl rounded-tr-none px-3.5 py-2.5 shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-2xl rounded-tl-none px-3.5 py-2.5 shadow-sm border border-gray-100 dark:border-gray-700/80'}">
                    <div class="chat-message-content leading-relaxed text-xs">
                        ${isUser ? escapeHtml(msg.text) : formatMarkdown(msg.text)}
                    </div>
            `;

            // Action buttons if any
            if (!isUser && msg.actions && msg.actions.length > 0) {
                innerHtml += `
                    <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-700/60 flex flex-wrap gap-1.5">
                `;
                msg.actions.forEach(act => {
                    const iconTag = act.icon ? `<i class="fa-solid ${act.icon} text-[10px]"></i>` : '';
                    innerHtml += `
                        <a href="${act.url}" ${act.target ? `target="${act.target}"` : ''} 
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gradient-to-r from-amber-50 to-amber-100 hover:from-amber-100 hover:to-amber-200 dark:from-amber-900/30 dark:to-amber-800/30 text-amber-900 dark:text-amber-200 font-bold rounded-lg text-[11px] border border-amber-300 dark:border-amber-700/50 shadow-xs hover:scale-102 transition transform">
                            ${iconTag}
                            <span>${act.label}</span>
                        </a>
                    `;
                });
                innerHtml += `</div>`;
            }

            innerHtml += `
                    <div class="text-[9px] mt-1 text-right ${isUser ? 'text-blue-200' : 'text-gray-400 dark:text-gray-500'}">
                        ${msg.time || ''}
                    </div>
                </div>
            `;

            container.innerHTML = innerHtml;
            body.appendChild(container);
        });

        scrollToBottom();
    }

    function renderSuggestions(suggestions) {
        const chipsContainer = document.getElementById('ngo-ai-chips-container');
        if (!chipsContainer) return;

        chipsContainer.innerHTML = '';
        if (!suggestions || suggestions.length === 0) {
            chipsContainer.classList.add('hidden');
            return;
        }

        chipsContainer.classList.remove('hidden');
        suggestions.forEach(item => {
            const label = typeof item === 'string' ? item : item.label;
            const prompt = typeof item === 'string' ? item : item.prompt;

            const pill = document.createElement('button');
            pill.type = 'button';
            pill.className = 'px-2.5 py-1 text-[11px] bg-white dark:bg-gray-700 hover:bg-[#fffcf5] dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 hover:text-[#1070B0] dark:hover:text-yellow-400 rounded-full border border-gray-200 dark:border-gray-600 shadow-xs whitespace-nowrap transition transform hover:scale-102 shrink-0 font-medium';
            pill.textContent = label;
            pill.addEventListener('click', () => {
                sendMessage(prompt);
            });
            chipsContainer.appendChild(pill);
        });
    }

    function renderLoading(show) {
        const sendBtn = document.getElementById('ngo-ai-send-btn');
        if (sendBtn) sendBtn.disabled = show;

        const body = document.getElementById('ngo-ai-messages-body');
        if (!body) return;

        const existingLoader = document.getElementById('ngo-ai-typing-bubble');
        if (show && !existingLoader) {
            const loader = document.createElement('div');
            loader.id = 'ngo-ai-typing-bubble';
            loader.className = 'flex items-center gap-2 justify-start';
            loader.innerHTML = `
                <div class="w-6 h-6 rounded-full bg-[#1070B0] text-white flex items-center justify-center shrink-0 text-[11px]">
                    <i class="fa-solid fa-hands-holding-child"></i>
                </div>
                <div class="bg-white dark:bg-gray-800 text-gray-500 rounded-2xl rounded-tl-none px-3.5 py-2.5 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-[#1070B0] rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-1.5 bg-[#F0A010] rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 bg-[#1070B0] rounded-full animate-bounce [animation-delay:0.4s]"></span>
                </div>
            `;
            body.appendChild(loader);
            scrollToBottom();
        } else if (!show && existingLoader) {
            existingLoader.remove();
        }
    }

    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWidget);
    } else {
        initWidget();
    }
})();
