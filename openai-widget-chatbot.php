<?php
/**
 * Plugin Name: OpenAi Widget Chatbot
 * Description: Um widget de chat baseado na API OpenAI.
 * Version: 1.0.0
 * Author: Seu Nome
 */

if (!defined('ABSPATH')) {
    exit; // Segurança
}

// Definir constantes
define('CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Inclui os arquivos necessários
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-widget-plugin.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-widget.php';

// Inicializa o plugin
new Chatbot_Widget_Plugin();
