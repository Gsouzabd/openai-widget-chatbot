<?php

if (!defined('ABSPATH')) {
    exit;
}

class Chatbot_Widget_Plugin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']); // Carregar o Bootstrap
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_uploader']);

    }

    public function enqueue_admin_styles() {
        wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
        wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', ['jquery'], null, true);
        wp_enqueue_style(
            'chatbot-admin-style', // Handle (nome do arquivo ou identificador único)
            CHATBOT_PLUGIN_URL . 'assets/css/admin.css', // Caminho para o arquivo CSS
            array(), // Dependências (nenhuma nesse caso)
            '1.0.0', // Versão do arquivo CSS
            'all' // Mídia (opcional, geralmente 'all' para qualquer tipo de dispositivo)
        );
        wp_enqueue_style('chatbot-style', CHATBOT_PLUGIN_URL . 'assets/css/chat.css');

    }

    public function add_admin_menu() {
        add_menu_page(
            'OpenAi Widget Chatbot', 
            'Chatbot OpenAi', 
            'manage_options', 
            'chatbot-widget-settings', 
            [$this, 'settings_page'], 
            'dashicons-format-chat', 
            25
        );

        add_submenu_page(
            'chatbot-widget-settings',     // Slug da página pai
            'Treinamento do Modelo',       // Título da página
            'Treinamento do Modelo',       // Nome no submenu
            'manage_options',              // Capacidade necessária
            'chatbot-training-settings',   // Slug da página do submenu
            [$this, 'training_settings_page'] // Função que renderiza a página do submenu
        );
    }

    public function register_settings() {
        // Registra os campos de configurações
        register_setting('chatbot_general_settings_group', 'chatbot_settings');
        register_setting('chatbot_training_settings_group', 'chatbot_training_settings');
        

        // Adiciona a seção de configurações gerais
        add_settings_section(
            'chatbot_general_settings', 
            'Configurações',     
            null,                       
            'chatbot-widget-settings'   
        );

        // Adiciona a seção de treinamento do modelo
        add_settings_section(
            'chatbot_training_settings', 
            'Treinamento do Modelo',     
            null,                       
            'chatbot-widget-training-settings'  
        );


         // Campos de Treinamento do Modelo
        add_settings_field(
            'chatbot_persona', 
            'Persona do Chatbot', 
            [$this, 'persona_field'], 
            'chatbot-widget-training-settings', 
            'chatbot_training_settings'
        );

        add_settings_field(
            'chatbot_script', 
            'Roteiro do Chatbot', 
            [$this, 'script_field'], 
            'chatbot-widget-training-settings', 
            'chatbot_training_settings'
        );

        add_settings_field(
            'chatbot_objective', 
            'Objetivo do Chatbot', 
            [$this, 'objective_field'], 
            'chatbot-widget-training-settings', 
            'chatbot_training_settings'
        );

        add_settings_field(
            'chatbot_context', 
            'Contexto do Chatbot', 
            [$this, 'context_field'], 
            'chatbot-widget-training-settings', 
            'chatbot_training_settings'
        );

        add_settings_field(
            'chatbot_introduction_prompt', 
            'Prompt de Introdução', 
            [$this, 'introduction_prompt_field'], 
            'chatbot-widget-training-settings', 
            'chatbot_training_settings'
        );

        add_settings_field(
            'chatbot_interaction_prompt', 
            'Prompt de Interação', 
            [$this, 'interaction_prompt_field'], 
            'chatbot-widget-training-settings', 
            'chatbot_training_settings'
        );

        add_settings_field(
            'chatbot_default_responses', 
            'Respostas Padrão', 
            [$this, 'default_responses_field'], 
            'chatbot-widget-training-settings', 
            'chatbot_training_settings'
        );
        add_settings_field(
            'chatbot_training', 
            'Base de informacoes', 
            [$this, 'training_field'], 
            'chatbot-widget-training-settings',  // Este deve ser o nome da página de configurações, igual ao da seção
            'chatbot_training_settings' // A seção à qual este campo pertence
        );

        // Adiciona o campo para o título do chatbot
        add_settings_field(
            'chatbot_title', 
            'Gerais', 
            [$this, 'title_field'], 
            'chatbot-widget-settings', 
            'chatbot_general_settings'
        );

        // Adiciona o campo para a chave API OpenAI
        add_settings_field(
            '', 
            '', 
            [$this, 'api_key_field'], 
            'chatbot-widget-settings', 
            ''
        );



        // Adiciona o campo para o ícone do bot
        add_settings_field(
            'chatbot_icon', 
            'Ícone do Bot', 
            [$this, 'icon_field'], 
            'chatbot-widget-settings', 
            'chatbot_general_settings'
        );

        add_settings_field(
            'chatbot_background_color', 
            '', 
            [$this, 'background_color_field'], 
            'chatbot-widget-settings', 
            ''
        );

        add_settings_field(
            'chatbot_text_color', 
            '', 
            [$this, 'text_color_field'], 
            '', 
            ''
        );

        add_settings_field(
            'chatbot_user_background_color', 
            '', 
            [$this, 'user_background_color_field'], 
            '', 
            ''
        );

        add_settings_field(
            'chatbot_bot_background_color', 
            '', 
            [$this, 'bot_background_color_field'], 
            '', 
            ''
        );
    }

    // Função para o campo "Título"
    public function title_field() {
        $options = get_option('chatbot_settings');
        ?>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="chatbot_title" class="form-label">Título do Chatbot</label>
                <input type="text" id="chatbot_title" name="chatbot_settings[chatbot_title]" value="<?php echo esc_attr($options['chatbot_title'] ?? ''); ?>" class="form-control">
            </div>
            
            <!-- Campo para a Chave API OpenAI -->
            <div class="col-md-6">
                <label for="chatbot_api_key" class="form-label">Chave API OpenAI</label>
                <input type="text" id="chatbot_api_key" name="chatbot_settings[chatbot_api_key]" value="<?php echo esc_attr($options['chatbot_api_key'] ?? ''); ?>" class="form-control">
            </div>
        </div>
        <?php
    }




    // Função para o campo "Treinamento do Modelo" (com repeater manual)
    public function training_field() {
        $options = get_option('chatbot_training_settings'); // Alterado para chatbot_training_settings
        $training = isset($options['chatbot_training']) ? $options['chatbot_training'] : [];
        ?>
        <ul id="chatbot-training-list" class="list-group">
            <?php
            if ($training) {
                foreach ($training as $index => $train_item) {
                    echo '<li class="d-flex justify-content-between align-items-center">';
                    echo '<textarea name="chatbot_training_settings[chatbot_training][' . $index . ']" class="form-control">' . esc_textarea($train_item) . '</textarea>';
                    echo ' <a href="#" class="remove-training btn btn-danger btn-sm mx-3">Remover</a>';
                    echo '</li>';
                }
            }
            ?>
        </ul>
        <p><a href="#" id="add-training" class="btn btn-primary btn-sm">Adicionar Nova</a></p>
        <script>
            document.getElementById('add-training').addEventListener('click', function(e) {
                e.preventDefault();
                var list = document.getElementById('chatbot-training-list');
                var newItem = document.createElement('li');
                newItem.classList.add('d-flex', 'justify-content-between', 'align-items-center');
                // Corrigido para adicionar um <textarea> em vez de <input>
                newItem.innerHTML = '<textarea name="chatbot_training_settings[chatbot_training][]" class="form-control"></textarea> <a href="#" class="remove-training btn btn-danger btn-sm mx-3">Remover</a>';
                list.appendChild(newItem);
            });
    
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-training')) {
                    e.preventDefault();
                    e.target.closest('li').remove();
                }
            });
        </script>
        <?php
    }
    

    // Função para o campo "Ícone do Bot"
    public function icon_field() {
        $options = get_option('chatbot_settings');
        $icon_url = isset($options['chatbot_icon']) ? esc_url($options['chatbot_icon']) : ''; // Recupera a URL do ícone
        ?>
        <div>
            <input type="text" name="chatbot_settings[chatbot_icon]" id="chatbot_icon_url" value="<?php echo $icon_url; ?>" class="form-control">
            <button type="button" class="button button-primary" id="chatbot_icon_button">Escolher Imagem</button>
    
            <?php if ($icon_url) : ?>
                <div style="margin-top: 10px;">
                    <img id="chatbot_icon_preview" src="<?php echo $icon_url; ?>" alt="Ícone do Bot" style="max-width: 100px; height: auto;">
                </div>
            <?php else: ?>
                <div id="chatbot_icon_preview" style="margin-top: 10px;"></div>
            <?php endif; ?>
    
            <p class="description">Insira o URL do ícone do bot ou selecione uma imagem da sua biblioteca de mídia.</p>
        </div>
        <?php
    }
    
    // Função para exibir o campo Persona
    public function persona_field() {
        // Recupera as configurações salvas para o chatbot_training_settings
        $options = get_option('chatbot_training_settings');
        $persona = isset($options['chatbot_persona']) ? esc_textarea($options['chatbot_persona']) : '';
        
        // Cria o campo de texto para a persona do chatbot
        echo '<textarea name="chatbot_training_settings[chatbot_persona]" class="form-control">' . $persona . '</textarea>';
        ?>
        <p class="description">Descreva a persona do chatbot (exemplo: Você é um atendente da empresa X).</p>
        <?php
    }
    

    public function script_field() {
        $options = get_option('chatbot_training_settings');
        $script = isset($options['chatbot_script']) ? esc_textarea($options['chatbot_script']) : ''; // Recupera o Roteiro
        ?>
        <textarea name="chatbot_training_settings[chatbot_script]" id="chatbot_script" class="form-control" rows="4"><?php echo $script; ?></textarea>
        <p class="description">Descreva o roteiro do chatbot para interação (exemplo: Como cumprimentar o usuário, como fazer perguntas, etc.).</p>
        <?php
    }
    
    // Função para exibir o campo de Objetivo
    public function objective_field() {
        $options = get_option('chatbot_training_settings');
        $objective = isset($options['chatbot_objective']) ? esc_textarea($options['chatbot_objective']) : '';
        echo '<textarea name="chatbot_training_settings[chatbot_objective]" class="form-control">' . $objective . '</textarea>';
    }
    

    // Função para exibir o campo de Contexto
    public function context_field() {
        $options = get_option('chatbot_training_settings');
        $context = isset($options['chatbot_context']) ? esc_textarea($options['chatbot_context']) : '';
        echo '<textarea name="chatbot_training_settings[chatbot_context]" class="form-control">' . $context . '</textarea>';
    }
    

    // Função para exibir o campo de Prompt de Introdução
    public function introduction_prompt_field() {
        $options = get_option('chatbot_training_settings');
        $introduction_prompt = isset($options['chatbot_introduction_prompt']) ? esc_textarea($options['chatbot_introduction_prompt']) : '';
        echo '<textarea name="chatbot_training_settings[chatbot_introduction_prompt]" class="form-control">' . $introduction_prompt . '</textarea>';
    }
    

    // Função para exibir o campo de Prompt de Interação
    public function interaction_prompt_field() {
        $options = get_option('chatbot_training_settings');
        $interaction_prompt = isset($options['chatbot_interaction_prompt']) ? esc_textarea($options['chatbot_interaction_prompt']) : '';
        echo '<textarea name="chatbot_training_settings[chatbot_interaction_prompt]" class="form-control">' . $interaction_prompt . '</textarea>';
    }
    

    // Função para exibir o campo de Respostas Padrão
    public function default_responses_field() {
        $options = get_option('chatbot_training_settings');
        $default_responses = isset($options['chatbot_default_responses']) ? esc_textarea($options['chatbot_default_responses']) : '';
        echo '<textarea name="chatbot_training_settings[chatbot_default_responses]" class="form-control">' . $default_responses . '</textarea>';
    }
    


    // Adicionando o script e os estilos necessários para o Media Uploader
    public function enqueue_media_uploader() {
        if (isset($_GET['page']) && $_GET['page'] === 'chatbot-widget-settings') {
            // Carrega o Media Uploader do WordPress
            wp_enqueue_media();
    
            // Registra e enfileira o script personalizado
            wp_enqueue_script(
                'chatbot-admin-script',
                plugin_dir_url(__FILE__) . '../assets/js/admin-scripts.js', // Caminho para o arquivo JS
                array('jquery'), // Dependência do jQuery
                '1.0.0',
                true // Carrega no footer
            );
        }
    }

    // Função para os campos de cores (background, texto, usuário, bot)
    public function layout_fields() {
        $options = get_option('chatbot_settings');
        
        // Valores padrões para as cores, caso não existam no banco de dados
        $background_color = isset($options['chatbot_background_color']) ? esc_attr($options['chatbot_background_color']) : '#ffffff';
        $text_color = isset($options['chatbot_text_color']) ? esc_attr($options['chatbot_text_color']) : '#000000';
        $user_background_color = isset($options['chatbot_user_background_color']) ? esc_attr($options['chatbot_user_background_color']) : '#f1f1f1';
        $bot_background_color = isset($options['chatbot_bot_background_color']) ? esc_attr($options['chatbot_bot_background_color']) : '#d3d3d3';
        
        ?>
        <h3>Layout</h3>
        <div class="row col-md-12 p-5">
            <!-- Cor de fundo do chatbot -->
            <div class="col-md-3">
                <label for="chatbot_background_color" class="form-label">Cor de Fundo do Chatbot</label>
                <input type="color" name="chatbot_settings[chatbot_background_color]" value="<?php echo $background_color; ?>" class="form-control" />
                <p class="description">Selecione a cor de fundo do chatbot.</p>
            </div>

            <!-- Cor do texto -->
            <div class="col-md-3">
                <label for="chatbot_text_color" class="form-label">Cor do Texto</label>
                <input type="color" name="chatbot_settings[chatbot_text_color]" value="<?php echo $text_color; ?>" class="form-control" />
                <p class="description">Selecione a cor do texto do chatbot.</p>
            </div>

            <!-- Cor de fundo do usuário -->
            <div class="col-md-3">
                <label for="chatbot_user_background_color" class="form-label">Cor de Fundo do Usuário</label>
                <input type="color" name="chatbot_settings[chatbot_user_background_color]" value="<?php echo $user_background_color; ?>" class="form-control" />
                <p class="description">Selecione a cor de fundo das mensagens do usuário.</p>
            </div>

            <!-- Cor de fundo do bot -->
            <div class="col-md-3">
                <label for="chatbot_bot_background_color" class="form-label">Cor de Fundo do Bot</label>
                <input type="color" name="chatbot_settings[chatbot_bot_background_color]" value="<?php echo $bot_background_color; ?>" class="form-control" />
                <p class="description">Selecione a cor de fundo das mensagens do bot.</p>
            </div>
        </div>
        <?php
    }



    static function layout() {
        // Obtém as configurações salvas no banco de dados
        $options = get_option('chatbot_settings');
        
        // Cores de fundo e texto
        $background_color = isset($options['chatbot_background_color']) ? esc_attr($options['chatbot_background_color']) : '#ffffff'; // Cor padrão
        $text_color = isset($options['chatbot_text_color']) ? esc_attr($options['chatbot_text_color']) : '#000000'; // Cor padrão
        $user_background_color = isset($options['chatbot_user_background_color']) ? esc_attr($options['chatbot_user_background_color']) : '#f1f1f1'; // Cor padrão
        $bot_background_color = isset($options['chatbot_bot_background_color']) ? esc_attr($options['chatbot_bot_background_color']) : '#d3d3d3'; // Cor padrão
        $icon = isset($options['chatbot_icon']) ? esc_attr($options['chatbot_icon'])  : null;
        $chatTitle = isset($options['chatbot_title']) ? esc_attr($options['chatbot_title'])  : 'Chat';

        ?>
        <h4 class="text-center mb-3 " style="
            color: #787c82;
            font-weight: 300;
        ">Preview</h4>         
        <body>
            <div class="chat-container">
                <h2> <?=esc_html($chatTitle);?> <?php if($icon) :?> <img src="<?= esc_url($icon); ?>"  width="100"> <?php endif;?> </h2>
                <div class="messages" id="messages">
                    <?php if(is_admin()):?>
                        <div class="user-message">
                            <strong>Usuário:</strong> Olá, assistente!
                        </div>
                        <div class="assistant-message">
                            <strong>Assistente:</strong> <span id="typing">Olá! Como posso lhe ajudar?</span>
                        </div>
                    <?php endif;?>
                </div>                    
                
                <?php if(is_admin()):?>
                    <div id="chatForm">
                        <input type="text" id="userMessage" name="userMessage" placeholder="Digite sua mensagem..." >
                        <button disabled>Enviar</button>
                    </div>
                <?php else:?>
                    <form id="chatForm">
                        <input type="text" id="userMessage" name="userMessage" placeholder="Digite sua mensagem..." >
                        <button type="submit" >Enviar</button>
                    </form>
                <?php endif;?>
            </div>                    

    
            <style>
                .chat-container {
                    background-color: <?php echo $background_color; ?>;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    max-width: 100%;
                    min-width: 80%;
                    margin: auto;
                    font-size: 21px;
                }
    
                .chat-container .messages {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    max-height: 400px;
                    height: 400px;
                    overflow-y: auto;
                }
    
                .chat-container .user-message, .assistant-message {
                    margin: 10px 0;
                    padding: 10px;
                    border-radius: 5px;
                    max-width: 80%;
                    word-wrap: break-word;
                }
    
                .chat-container .user-message {
                    background-color: <?php echo $user_background_color; ?>;
                    color: <?php echo $text_color; ?>;
                    align-self: flex-end;
                    max-width: 50%;
                    text-align: left;
                    padding: 10px;
                    border-radius: 10px;
                    min-width: 50%;
                }
    
                .chat-container .assistant-message {
                    background-color: <?php echo $bot_background_color; ?>;
                    color: <?php echo $text_color; ?>;
                    align-self: flex-start;
                    max-width: 50%;
                    text-align: left;
                    padding: 10px;
                    border-radius: 10px;
                }
    
                #chatForm input[type="text"] {
                    width: calc(100% - 110px);
                    padding: 10px;
                    height: 66px;
                    border-radius: 20px 0px 0px 20px;
                    border: 1px solid #cccccc33;
                    font-size: 20px;
                    background: #1e1e1e12;
                    color: white;
                }
    
                #chatForm {
                    display: flex;
                }
    
                #chatForm input[type="text"]::placeholder, #chatForm input[type="text"] {
                    color: black;
                }
    
                #chatForm button {
                    padding: 10px 20px;
                    background-color: #ffffff70;
                    color: #000000;
                    border:1px solid #e9e9e9;
                    border-radius: 0px 20px 20px 1px;
                    cursor: pointer;
                    font-size: 17px;
                    font-weight: 800;
                    width: auto;
                }
    
                #chatFormbutton:hover {
                    background-color: #0056b3;
                }
    
                #chatForm .message-container {
                    display: flex;
                    flex-direction: column;
                }


            </style>
        </body>
        </html>
        <?php
    }
    

    public function settings_page() {
        ?>
        <div class="wrap" id="chatbot-widget-plugin-admin">
        <h2>Chatbox OpenAi - Painel de Controle</h2>
            <form method="post" action="options.php">
                <div class="card shadow rounded col-md-12">
                    <?php
                        settings_fields('chatbot_general_settings_group');
                        do_settings_sections('chatbot-widget-settings');
                    ?>
                </div>
                
                <div class="space-mid"></div>

                <div class="card shadow rounded col-md-12">
                    <!-- Exibe todos os campos de cor em uma linha -->
                    <?php $this->layout_fields(); ?>

                    <?php $this->layout(); ?>
                </div>

                <?php
                submit_button('Salvar Configurações', 'primary', 'submit', true, ['class' => 'btn btn-success']);
                ?>
            </form>
        </div>
        <?php
    }


    public function training_settings_page() {
        ?>
        <div class="wrap" id="chatbot-widget-plugin-admin">
        <h2>Chatbox OpenAi - Painel de Treinamento</h2>

            <form method="post" action="options.php">
                <div class="card shadow rounded col-md-12">

                    <?php
                    settings_fields('chatbot_training_settings_group');
                    do_settings_sections('chatbot-widget-training-settings');
                    submit_button();
                    ?>
                </div>
            </form>
        </div>
        <?php
    }
}
