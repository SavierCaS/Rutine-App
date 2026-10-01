document.addEventListener('DOMContentLoaded', function () {
    const user_id = document.getElementById('userData')?.getAttribute('data-user-id');
    const limiteMensajes = document.getElementById('userData')?.getAttribute('data-limite-mensajes') === 'true';

    if (limiteMensajes) {
        showMessageLimitOverlay();
        startCountdown();
    }

    function formatAIMessage(message) {
        if (!message) return '';

        let text = String(message);

        text = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        text = text.replace(/\r\n/g, '\n');
        text = text.replace(/\r/g, '\n');

        text = text.replace(
            /```(?:[a-zA-Z0-9_+-]+)?\n?([\s\S]*?)```/g,
            '<pre class="kynap-code-block"><code>$1</code></pre>'
        );

        text = text.replace(
            /`([^`\n]+)`/g,
            '<code class="kynap-inline-code">$1</code>'
        );

        text = text.replace(
            /\*\*(.+?)\*\*/g,
            '<strong>$1</strong>'
        );

        text = text.replace(
            /(?<!\*)\*([^*\n]+)\*(?!\*)/g,
            '<em>$1</em>'
        );

        text = text.replace(
            /(?:^|\n)[ \t]*[-•][ \t]+(.+)(?=\n|$)/g,
            '\n<li>$1</li>'
        );

        text = text.replace(
            /(?:^|\n)[ \t]*\d+\.[ \t]+(.+)(?=\n|$)/g,
            '\n<li>$1</li>'
        );

        text = text.replace(
            /((?:\n<li>.*?<\/li>)+)/gs,
            function (match) {
                return '<ul>' + match.replace(/^\n/, '') + '</ul>';
            }
        );

        text = text.replace(
            /^###[ \t]+(.+)$/gm,
            '<h4>$1</h4>'
        );

        text = text.replace(
            /^##[ \t]+(.+)$/gm,
            '<h3>$1</h3>'
        );

        text = text.replace(
            /^#[ \t]+(.+)$/gm,
            '<h2>$1</h2>'
        );

        text = text.replace(
            /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,
            '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
        );

        const parts = text.split(/(<pre class="kynap-code-block">[\s\S]*?<\/pre>)/g);

        text = parts.map(function (part) {
            if (part.startsWith('<pre class="kynap-code-block">')) {
                return part;
            }

            return part.replace(/\n/g, '<br>');
        }).join('');

        return text;
    }

    function sendMessage() {
        const message = document.getElementById('user-message').value.trim();

        if (message === '') return;

        appendMessage('user', message);
        document.getElementById('user-message').value = '';

        fetch('chat_kynap_ai.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                user_id: user_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.response === 'Límite de mensajes alcanzado') {
                Swal.fire({
                    icon: 'error',
                    title: 'Límite de mensajes alcanzado',
                    text: 'Has alcanzado el límite de 5 mensajes.',
                });

                showMessageLimitOverlay();
                startCountdown();

            } else if (data.response === 'Chat no activado') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chat no activado',
                    text: 'Por favor, activa el chat para comenzar.',
                });

            } else {
                appendMessage('ai', data.response);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function enableChat() {
        fetch('dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'activar_chat=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const overlay = document.getElementById('chatOverlay');

                if (overlay) {
                    overlay.style.display = 'none';
                }
            } else {
                alert('Error al activar el chat. Intenta de nuevo.');
            }
        })
        .catch(error => {
            console.error('Error al activar el chat:', error);
        });
    }

    window.enableChat = enableChat;

    function appendMessage(sender, message) {
        const chatBox = document.getElementById('chat-box');
        if (!chatBox) return;

        const messageElement = document.createElement('div');

        messageElement.classList.add(
            sender === 'user' ? 'user-message' : 'ai-message'
        );

        if (sender === 'ai') {
            messageElement.innerHTML = formatAIMessage(message);
        } else {
            messageElement.textContent = message;
        }

        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function showMessageLimitOverlay() {
        const chatBox = document.getElementById('chat-box');
        if (!chatBox) return;

        let overlay = document.getElementById('messageLimitOverlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'messageLimitOverlay';
            overlay.classList.add('chat-overlay');

            overlay.innerHTML = `
                <div class="overlay-content">
                    <p>Has alcanzado el límite de 5 mensajes.</p>
                    <p id="countdown-timer">Tiempo restante: 05:00</p>
                    <button onclick="openPricesModal()">Activar nuevamente</button>
                </div>
            `;

            chatBox.appendChild(overlay);
        }

        overlay.style.display = 'flex';
    }

    function removeMessageLimitOverlay() {
        const overlay = document.getElementById('messageLimitOverlay');

        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    function startCountdown() {
        const countdownElement = document.getElementById('countdown-timer');
        if (!countdownElement) return;

        let countdownTime = localStorage.getItem('countdownTime');

        if (!countdownTime) {
            countdownTime = 12 * 60 * 60 * 1000;
            localStorage.setItem('countdownTime', countdownTime);
        }

        updateCountdownDisplay(countdownTime);

        let interval = setInterval(function () {

            if (countdownTime <= 0) {
                clearInterval(interval);

                removeMessageLimitOverlay();
                localStorage.removeItem('countdownTime');

                fetch('chat_kynap_ai.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        reset_messages: true,
                        user_id: user_id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.response === 'Contador de mensajes restablecido') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Contador de mensajes restablecido',
                            text: 'Ahora puedes enviar más mensajes.',
                        });
                    }
                })
                .catch(error =>
                    console.error('Error al restablecer el contador:', error)
                );

            } else {
                countdownTime -= 1000;

                localStorage.setItem('countdownTime', countdownTime);

                updateCountdownDisplay(countdownTime);
            }

        }, 1000);
    }

    function updateCountdownDisplay(countdownTime) {
        const countdownElement = document.getElementById('countdown-timer');

        if (!countdownElement) return;

        let hours = Math.floor(
            countdownTime / (1000 * 60 * 60)
        );

        let minutes = Math.floor(
            (countdownTime % (1000 * 60 * 60)) / (1000 * 60)
        );

        let seconds = Math.floor(
            (countdownTime % (1000 * 60)) / 1000
        );

        countdownElement.textContent =
            `Tiempo restante: ${padTime(hours)}:${padTime(minutes)}:${padTime(seconds)}`;
    }

    function padTime(value) {
        return value < 10 ? `0${value}` : value;
    }

    document.querySelector('.chat-input button')?.addEventListener(
        'click',
        sendMessage
    );

    document.getElementById('user-message')?.addEventListener(
        'keypress',
        function (event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }
    );
});

function openPricesModal() {
    const overlay = document.createElement('div');

    overlay.id = 'pricesModalOverlay';

    overlay.style.position = 'fixed';
    overlay.style.top = 0;
    overlay.style.left = 0;
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.background = 'rgba(0, 0, 0, 0.7)';
    overlay.style.zIndex = 1000;
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';

    const modalContent = document.createElement('div');

    modalContent.style.background = 'white';
    modalContent.style.padding = '20px';
    modalContent.style.maxWidth = '800px';
    modalContent.style.width = '90%';
    modalContent.style.borderRadius = '10px';

    const iframe = document.createElement('iframe');

    iframe.src = 'prices.php';
    iframe.style.width = '100%';
    iframe.style.height = '80vh';
    iframe.style.border = 'none';

    modalContent.appendChild(iframe);

    const closeButton = document.createElement('button');

    closeButton.textContent = 'Cerrar';
    closeButton.style.marginTop = '10px';
    closeButton.style.background = '#f44336';
    closeButton.style.color = 'white';
    closeButton.style.border = 'none';
    closeButton.style.padding = '5px 10px';
    closeButton.style.cursor = 'pointer';

    closeButton.onclick = () => {
        document.body.removeChild(overlay);
    };

    modalContent.appendChild(closeButton);

    overlay.appendChild(modalContent);

    document.body.appendChild(overlay);
}

document.addEventListener('DOMContentLoaded', function () {

    const user_id =
        document.getElementById('userData')?.getAttribute('data-user-id');

    const limiteMensajes =
        document.getElementById('userData')
        ?.getAttribute('data-limite-mensajes') === 'true';

    const chatBox = document.getElementById('chat-box');
    const userInput = document.getElementById('user-message');
    const sendButton = document.querySelector('.chat-input button');

    let modalOpen = false;
    let modalElements = null;


    function openChatModal() {

        if (modalOpen) return;

        modalOpen = true;

        const overlay = document.createElement('div');

        overlay.id = 'chatModalOverlay';

        Object.assign(overlay.style, {
            position: 'fixed',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            background: 'rgba(0,0,0,0.7)',
            zIndex: '9999',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
        });


        const modal = document.createElement('div');

        Object.assign(modal.style, {
            background: 'white',
            width: '90%',
            maxWidth: '800px',
            height: '80vh',
            borderRadius: '10px',
            display: 'flex',
            flexDirection: 'column',
            padding: '20px',
        });


        modal.innerHTML = `
            <h2>Asistente Kynap AI 🤖</h2>

            <div id="modal-chat-box"
                style="flex:1; overflow-y:auto; border:1px solid #ccc; padding:10px; margin-bottom:10px; border-radius:5px; background:#f9f9f9;">
            </div>

            <div style="display:flex;">
                <input
                    type="text"
                    id="modal-user-message"
                    placeholder="Escribe tu mensaje..."
                    style="flex:1; padding:10px; font-size:16px;">

                <button
                    id="modal-send-button"
                    style="padding:10px 20px; font-size:16px; margin-left:10px;">
                    Enviar
                </button>
            </div>

            <button
                id="modal-close-button"
                style="margin-top:10px; background:#f44336; color:#fff; border:none; padding:10px 20px; cursor:pointer; border-radius:5px;">
                Cerrar
            </button>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        modalElements = {
            overlay,
            modal,
            chatBox: modal.querySelector('#modal-chat-box'),
            input: modal.querySelector('#modal-user-message'),
            sendBtn: modal.querySelector('#modal-send-button'),
            closeBtn: modal.querySelector('#modal-close-button'),
        };


        modalElements.sendBtn.addEventListener('click', () => {
            sendMessageModal(modalElements.input.value.trim());
        });

        modalElements.input.addEventListener('keydown', e => {

            if (e.key === 'Enter') {
                e.preventDefault();

                sendMessageModal(
                    modalElements.input.value.trim()
                );
            }

        });

        modalElements.closeBtn.addEventListener(
            'click',
            closeChatModal
        );


        modalElements.input.focus();
    }

    function closeChatModal() {

        if (!modalOpen) return;

        document.body.removeChild(modalElements.overlay);

        modalOpen = false;
        modalElements = null;
    }

    function appendMessageSmall(sender, message) {

        const messageElement = document.createElement('div');

        messageElement.classList.add(
            sender === 'user'
                ? 'user-message'
                : 'ai-message'
        );

        if (sender === 'ai') {
            messageElement.innerHTML = formatAIMessage(message);
        } else {
            messageElement.textContent = message;
        }

        chatBox.appendChild(messageElement);

        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function appendMessageModal(sender, message) {

        if (!modalOpen || !modalElements) return;

        const messageElement = document.createElement('div');

        messageElement.style.margin = '8px 0';
        messageElement.style.padding = '8px 12px';
        messageElement.style.borderRadius = '12px';
        messageElement.style.maxWidth = '70%';
        messageElement.style.wordWrap = 'break-word';

        if (sender === 'user') {

            messageElement.style.backgroundColor = '#0084ff';
            messageElement.style.color = 'white';
            messageElement.style.alignSelf = 'flex-end';
            messageElement.style.marginLeft = 'auto';

        } else {

            messageElement.style.backgroundColor = '#e5e5ea';
            messageElement.style.color = 'black';
            messageElement.style.alignSelf = 'flex-start';
            messageElement.style.marginRight = 'auto';

        }

        if (sender === 'ai') {
            messageElement.innerHTML = formatAIMessage(message);
        } else {
            messageElement.textContent = message;
        }

        modalElements.chatBox.appendChild(messageElement);

        modalElements.chatBox.scrollTop =
            modalElements.chatBox.scrollHeight;
    }

    function sendMessageMain(message, sendFromModal = false) {

        if (!message) return;

        if (!modalOpen && !sendFromModal) {
            openChatModal();
        }

        appendMessageSmall('user', message);

        if (modalOpen) {
            appendMessageModal('user', message);
        }

        if (modalOpen) {

            modalElements.input.disabled = true;
            modalElements.sendBtn.disabled = true;

        } else {

            userInput.disabled = true;
            sendButton.disabled = true;

        }

        fetch('chat_kynap_ai.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({
                message,
                user_id
            })

        })

        .then(res => res.json())

        .then(data => {

            if (data.response === 'Límite de mensajes alcanzado') {

                if (modalOpen) {
                    closeChatModal();
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Límite de mensajes alcanzado',
                    text: 'Has alcanzado el límite de 5 mensajes.',
                });

                showMessageLimitOverlay();
                startCountdown();

            } else if (data.response === 'Chat no activado') {

                if (modalOpen) {
                    closeChatModal();
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Chat no activado',
                    text: 'Por favor, activa el chat para comenzar.',
                });

            } else {

                appendMessageSmall(
                    'ai',
                    data.response
                );

                if (modalOpen) {

                    appendMessageModal(
                        'ai',
                        data.response
                    );

                }

            }

        })

        .catch(() => {

            appendMessageSmall(
                'ai',
                'Error al enviar mensaje.'
            );

            if (modalOpen) {

                appendMessageModal(
                    'ai',
                    'Error al enviar mensaje.'
                );

            }

        })

        .finally(() => {

            if (modalOpen) {

                modalElements.input.disabled = false;
                modalElements.sendBtn.disabled = false;
                modalElements.input.value = '';
                modalElements.input.focus();

            } else {

                userInput.disabled = false;
                sendButton.disabled = false;
                userInput.value = '';
                userInput.focus();

            }

        });
    }

    sendButton.addEventListener('click', () => {

        const msg = userInput.value.trim();

        sendMessageMain(msg, false);

    });


    userInput.addEventListener('keydown', e => {

        if (e.key === 'Enter') {

            e.preventDefault();

            const msg = userInput.value.trim();

            sendMessageMain(msg, false);
        }

    });
    
    function sendMessageModal(message) {
        sendMessageMain(message, true);
    }

});
