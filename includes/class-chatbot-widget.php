<?php
if (!defined('ABSPATH')) {
    exit;
}

class Chatbot_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'chatbot_widget',
            __('OpenAi Chatbot', 'chatbot_domain'),
            ['description' => __('Um widget de chat usando OpenAI API', 'chatbot_domain')]
        );
        
        // Registra o shortcode
        add_shortcode('chatbot_widget', [$this, 'render_chatbot_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_chatbot_script']);
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo '<div id="chatbot-container"></div>';
        echo $args['after_widget'];
    }
    

    // Enfileira o script e o estilo
    function enqueue_chatbot_script() {
        // Enfileirando o CSS
        wp_enqueue_style('chatbot-style', CHATBOT_PLUGIN_URL . 'assets/css/chat.css');
        
        // Enfileirando o JS
        // wp_enqueue_script('chatbot-script', CHATBOT_PLUGIN_URL . 'assets/js/chatbot-script.js', ['jquery'], null, true);
    
    }

    
    // Função para renderizar o conteúdo do shortcode
    public function render_chatbot_shortcode($atts) {
        // Shortcode pode ter atributos, ex: [chatbot_widget title="Chatbot"]
        $atts = shortcode_atts(
            [
                'title' => 'Chatbot', // Título padrão
            ],
            $atts,
            'chatbot_widget'
        );

        ob_start(); // Inicia o buffer de saída

        Chatbot_Widget_Plugin::layout();
        $options = get_option('chatbot_training_settings');
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
                const sseUrl =  '<?php echo CHATBOT_PLUGIN_URL . 'includes/sse.php';?>';  // Define a URL correta do arquivo sse.php
                
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

        return ob_get_clean(); // Retorna o conteúdo gerado
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Chatbot', 'chatbot_domain');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Título:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

// Registrar o widget
function register_chatbot_widget() {
    register_widget('Chatbot_Widget');
}
add_action('widgets_init', 'register_chatbot_widget');


// Função para renderizar o chatbot após o header
function render_chatbot_shortcode() {
    echo do_shortcode('[chatbot_widget title="Chatbot IA"]');
}


add_action('woocommerce_before_shop_loop', 'render_chatbot_shortcode');
