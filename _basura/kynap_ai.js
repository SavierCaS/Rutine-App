async function sendMessage() {
    const message = document.getElementById('user-message').value;
    if (!message) return;

    const chatBox = document.getElementById('chat-box');
    
    const userName = localStorage.getItem('firstName') || 'Usuario';

    chatBox.innerHTML += `<div class="chat-message user-message"><strong>${userName}:</strong> ${message}</div>`;
    document.getElementById('user-message').value = ''; 

    const response = await fetch('kynap_ai.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ message })
    });

    const data = await response.json();
    if (data.response) {
        chatBox.innerHTML += `<div class="chat-message ai-message"><strong>Kynap AI:</strong> ${data.response}</div>`;
        chatBox.scrollTop = chatBox.scrollHeight; 
    } else {
        chatBox.innerHTML += `<div class="chat-message ai-message"><strong>Kynap AI:</strong> Error al obtener respuesta.</div>`;
    }
}
