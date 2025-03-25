<?php 

class EventSource{

    static function Script(){
        $options = get_option('chatbot_settings');
        $persona = isset($options['chatbot_persona']) ? esc_textarea($options['chatbot_persona']) : '';
        $objective = isset($options['chatbot_objective']) ? esc_textarea($options['chatbot_objective']) : '';
        $context = isset($options['chatbot_context']) ? esc_textarea($options['chatbot_context']) : '';
        $introduction_prompt = isset($options['chatbot_introduction_prompt']) ? esc_textarea($options['chatbot_introduction_prompt']) : '';
        $interaction_prompt = isset($options['chatbot_interaction_prompt']) ? esc_textarea($options['chatbot_interaction_prompt']) : '';
        $default_responses = isset($options['chatbot_default_responses']) ? esc_textarea($options['chatbot_default_responses']) : '';
        ?>

        <script>
        let messages = [
            { 
                role: 'system', 
                content: 'Você é um especialista em WooCommerce e e-commerces. Se apresente como a IA da Flow Digital, uma empresa pioneira no desenvolvimento de e-commerces com WooCommerce no Brasil. Utilize esse texto ao se apresentar ou ao receber um "Olá" (em até 2 frases).' 
            },
            { 
                role: 'system', 
                content: 'Persona: ' + <?php echo json_encode($persona); ?>  // Corrigido para garantir que o valor seja interpretado corretamente como string
            },
            { 
                role: 'system', 
                content: 'Objetivo: ' + <?php echo json_encode($objective); ?> 
            },
            { 
                role: 'system', 
                content: 'Contexto: ' + <?php echo json_encode($context); ?> 
            },
            { 
                role: 'system', 
                content: 'Prompt de Introdução: ' + <?php echo json_encode($introduction_prompt); ?> 
            },
            { 
                role: 'system', 
                content: 'Prompt de Interação: ' + <?php echo json_encode($interaction_prompt); ?> 
            },
            { 
                role: 'system', 
                content: 'Respostas Padrão: ' + <?php echo json_encode($default_responses); ?> 
            }
        ];


        function addMessage(role, content) {
            if ( role == 'system'){
                return;
            }
            const messagesContainer = document.getElementById('messages');
            const div = document.createElement('div');
            div.classList.add('message-container', role === 'user' ? 'user-message' : 'assistant-message');
            div.innerHTML = `<strong>${role === 'user' ? 'Você' : 'Assistente'}:</strong> ${content}`;
            div.scrollIntoView({ behavior: 'smooth', block: 'end' });

            messagesContainer.appendChild(div);
            requestAnimationFrame(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            });
            setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 0);


        }

        // Exibir mensagens iniciais
        messages.forEach(msg => addMessage(msg.role, msg.content));

        document.getElementById('chatForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const userMessage = document.getElementById('userMessage').value.trim();
            if (!userMessage) return;

            // Adiciona a mensagem do usuário
            messages.push({ role: 'user', content: userMessage });
            addMessage('user', userMessage);
            const messagesContainer = document.getElementById('messages');
            const div = document.createElement('div');

            messagesContainer.appendChild(div);
            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 1000);
            document.getElementById('userMessage').value = '';

            // Mensagem temporária
            let assistantMessage = { role: 'assistant', content: '' };
            messages.push(assistantMessage);
            const assistantDiv = document.createElement('div');
            assistantDiv.classList.add('assistant-message');
            assistantDiv.innerHTML = `<strong>Assistente:</strong> <span id="typing">Carregando...</span>`;
            document.getElementById('messages').appendChild(assistantDiv);

            try {
                const sseUrl =  '../wp-content/plugins/openai-widget-chatbot/includes/sse.php';  // Define a URL correta do arquivo sse.php
                
                // Enviar mensagens ao backend antes de iniciar o SSE
                let response = await fetch(sseUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ messages: messages })
                });
                
                if (!response.ok) throw new Error('Erro ao enviar mensagens para o backend');
                
                // Iniciar SSE após envio dos dados
                const eventSource = new EventSource(sseUrl);
                

                eventSource.onmessage = function (event) {
                    try {
                        let data = JSON.parse(event.data);

                        if (data.message) {
                            if (data.message === "[FIM]") {
                                eventSource.close(); // Fecha a conexão SSE
                                return;
                            }

                            // Remove a mensagem "Carregando..." apenas se ainda existir
                            let typingElement = document.getElementById('typing');
                            if (typingElement) {
                                typingElement.remove();
                            }

                            // Adiciona o texto recebido ao assistente
                            assistantMessage.content += data.message;
                            assistantDiv.innerHTML = `<strong>Assistente:</strong> ${assistantMessage.content}`;
                        }
                    } catch (error) {
                        console.error("Erro ao processar JSON:", error, event.data);
                    }
                };

                eventSource.onerror = function () {
                    console.error("Erro na conexão SSE.");
                    eventSource.close();
                };
            } catch (error) {
                console.error('Erro:', error);
            }
        });

        </script>

    <?php
    }
    
}