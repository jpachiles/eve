<!DOCTYPE html>
<html lang="pt-BR">
<head>

    <meta charset="UTF-8">

    <title>Eve</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, Helvetica, sans-serif;
        }

        body{
            background:#0f172a;
            color:white;
        }

        #chat{
            flex:1;
            height:100vh;
            display:flex;
            flex-direction:column;
        }

        header{

            padding:20px;
            font-size:22px;
            font-weight:bold;

            border-bottom:1px solid #1e293b;

        }

        #messages{

            flex:1;
            overflow:auto;

            padding:25px;

            display:flex;
            flex-direction:column;
            gap:15px;

        }

        .message{

            max-width:75%;
            padding:15px;
            border-radius:15px;
            line-height:1.5;

        }

        .user{

            background:#2563eb;
            align-self:flex-end;

        }

        .assistant{

            background:#1e293b;
            align-self:flex-start;

        }

        footer{

            display:flex;
            gap:10px;

            padding:20px;

            border-top:1px solid #1e293b;

        }

        input{

            flex:1;

            padding:15px;

            border:none;

            border-radius:10px;

            background:#1e293b;

            color:white;

            font-size:16px;

        }

        button{

            padding:15px 20px;

            border:none;

            border-radius:10px;

            cursor:pointer;

            background:#2563eb;

            color:white;

        }

        #app{
            display:flex;
            height:100vh;
        }

        #sidebar{
            width:260px;
            background:#111827;
            border-right:1px solid #1e293b;
            overflow:auto;
            padding:20px;
        }

        .conversation{
            padding:12px;
            border-radius:8px;
            cursor:pointer;
            margin-bottom:6px;
            background:transparent;
        }

        .conversation:hover{
            background:#1e293b;
        }

    </style>

</head>

<body>

<div id="app">

    <aside id="sidebar">

        <h3 style="margin-bottom:20px;">Conversas</h3>

        <div id="conversation-list"></div>

    </aside>

    <div id="chat">

        <header>
            Eve
        </header>

        <div id="messages"></div>

        <footer>

            <input
                id="message"
                placeholder="Digite uma mensagem..."
            >

            <button onclick="send()">
                Enviar
            </button>

            <button onclick="newChat()">
                Nova
            </button>

        </footer>

    </div>

</div>

<script>

    let conversationId = Number(localStorage.getItem('conversation_id')) || null;

    const messages = document.getElementById('messages');
    loadConversations();

    if (conversationId) {
        loadConversation();
    }

    function addMessage(text, type){

        messages.innerHTML += `
            <div class="message ${type}">
                ${text}
            </div>
        `;

        messages.scrollTop = messages.scrollHeight;

    }

    async function send() {

        const input = document.getElementById('message');

        const text = input.value.trim();

        if (!text) return;

        addMessage(text, 'user');

        input.value = '';

        // cria a bolha vazia da Eve
        messages.innerHTML += `
        <div class="message assistant"></div>
    `;

        const assistantMessage = messages.lastElementChild;

        messages.scrollTop = messages.scrollHeight;

        const response = await fetch('/api/chat/stream', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({
                message: text,
                conversation_id: conversationId
            })

        });

        const reader = response.body.getReader();

        const decoder = new TextDecoder();

        let buffer = '';

        while (true) {

            const { value, done } = await reader.read();

            if (done) break;

            buffer += decoder.decode(value, { stream: true });

            const events = buffer.split("\n\n");

            buffer = events.pop();

            for (const event of events) {

                if (!event.startsWith("data: ")) {
                    continue;
                }

                const json = event.replace("data: ", "");

                try {

                    const data = JSON.parse(json);

                    if (data.text) {
                        assistantMessage.innerHTML += data.text;
                        messages.scrollTop = messages.scrollHeight;
                    }

                    if (data.conversation_id) {

                        conversationId = data.conversation_id;

                        localStorage.setItem(
                            'conversation_id',
                            conversationId
                        );

                    }

                } catch (e) {}

            }

        }

        loadConversations();

    }

    async function loadConversation() {

        const response = await fetch(
            `/api/conversation/${conversationId}`
        );

        if (!response.ok) {
            localStorage.removeItem('conversation_id');
            conversationId = null;
            return;
        }

        const history = await response.json();

        messages.innerHTML = '';

        history.forEach(message => {

            addMessage(
                message.content,
                message.role === 'user'
                    ? 'user'
                    : 'assistant'
            );

        });

    }

    async function loadConversations() {

        const response = await fetch('/api/conversations');

        const conversations = await response.json();

        const list = document.getElementById('conversation-list');

        list.innerHTML = '';

        conversations.forEach(conversation => {

            list.innerHTML += `
            <div
                class="conversation"
                onclick="openConversation(${conversation.id})"
            >
                ${conversation.title ?? 'Nova conversa'}
            </div>
        `;

        });

    }

    async function openConversation(id){

        conversationId = id;

        localStorage.setItem('conversation_id', id);

        await loadConversation();

    }

    function newChat() {

        conversationId = null;

        localStorage.removeItem('conversation_id');

        messages.innerHTML = '';

    }

    document.getElementById('message').addEventListener('keydown', e => {

        if(e.key === "Enter"){

            send();

        }

    });

</script>

</body>
</html>
