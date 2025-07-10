class ChatSystem {
    constructor(userId, tradeId) {
        this.userId = userId;
        this.tradeId = tradeId;
        this.ws = new WebSocket(`ws://${window.location.hostname}:8080`);
        this.initializeWebSocket();
    }

    initializeWebSocket() {
        this.ws.onopen = () => {
            console.log('Connected to chat server');
            this.sendJoinMessage();
        };

        this.ws.onmessage = (event) => {
            const message = JSON.parse(event.data);
            this.displayMessage(message);
        };

        this.ws.onclose = () => {
            console.log('Disconnected from chat server');
            setTimeout(() => this.reconnect(), 1000);
        };
    }

    sendMessage(message) {
        const messageData = {
            type: 'message',
            userId: this.userId,
            tradeId: this.tradeId,
            message: message,
            timestamp: new Date().toISOString()
        };
        this.ws.send(JSON.stringify(messageData));
    }

    displayMessage(message) {
        const chatContainer = document.querySelector('.chat-messages');
        const messageElement = document.createElement('div');
        messageElement.classList.add('message');
        messageElement.classList.add(message.userId === this.userId ? 'sent' : 'received');
        
        messageElement.innerHTML = `
            <div class="message-content">
                <p>${message.message}</p>
                <span class="timestamp">${new Date(message.timestamp).toLocaleTimeString()}</span>
            </div>
        `;
        
        chatContainer.appendChild(messageElement);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    reconnect() {
        this.ws = new WebSocket(`ws://${window.location.hostname}:8080`);
        this.initializeWebSocket();
    }
} 