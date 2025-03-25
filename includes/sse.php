<?php

// Verifica se a requisição é POST para salvar as mensagens
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);

    if (isset($dados['messages'])) {
        file_put_contents('mensagens.json', json_encode($dados['messages']));
        echo json_encode(["status" => "ok"]);
        exit;
    }
}

// Configuração dos cabeçalhos para SSE (somente em requisições GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header("Content-Type: text/event-stream");
    header("Cache-Control: no-cache");
    header("Connection: keep-alive");
    header("Access-Control-Allow-Origin: *");

    // Carrega as mensagens salvas
    $mensagens = json_decode(file_get_contents('mensagens.json'), true);

    // Se não houver mensagens, encerra
    if (!$mensagens) {
        echo "data: " . json_encode(["message" => "Erro: Nenhuma mensagem encontrada."]) . "\n\n";
        ob_flush();
        flush();
        exit;
    }

    gerarTexto($mensagens);
}

// Função para processar o stream e enviar para o frontend
function gerarTexto($mensagens) {
    $options = get_option('chatbot_settings');

    $apiKey = esc_attr($options['chatbot_api_key'] ?? '');

    $url = 'https://api.openai.com/v1/chat/completions';

    $data = [
        'model'       => 'gpt-4',
        'messages'    => $mensagens,
        'temperature' => 0.7,
        'max_tokens'  => 200,
        'stream'      => true
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // O streaming será tratado manualmente
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Processa o stream da OpenAI e envia para o EventSource
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) {
        $linhas = explode("\n", $chunk);

        foreach ($linhas as $linha) {
            if (empty(trim($linha)) || strpos($linha, 'data: ') !== 0) {
                continue;
            }

            $json = trim(substr($linha, 5)); // Remove "data: " do início

            if ($json === "[DONE]") {
                echo "data: " . json_encode(["message" => "[FIM]"]) . "\n\n";
                ob_flush();
                flush();
                return 0;
            }

            $dados = json_decode($json, true);
            if (isset($dados['choices'][0]['delta']['content'])) {
                $mensagem = $dados['choices'][0]['delta']['content'];
                echo "data: " . json_encode(["message" => $mensagem]) . "\n\n";
                ob_flush();
                flush();
            }
        }

        return strlen($chunk);
    });

    curl_exec($ch);
    curl_close($ch);
}

?>