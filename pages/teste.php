<?php
/**
 * Exemplo de consumo da API REST do PKP OJS usando PHP + cURL
 * Compatível com OJS 3.3 e 3.4
 */

// CONFIGURAÇÕES
$baseUrl = "https://seu-dominio.com/index.php/seuContexto/api/v1"; // URL base da API
$apiKey  = "SUA_API_KEY_AQUI"; // Gere no perfil do usuário no OJS
$endpoint = "/submissions"; // Endpoint que deseja acessar

// Função para fazer requisições GET à API
function ojsApiGet($baseUrl, $endpoint, $apiKey, $queryParams = []) {
    $url = $baseUrl . $endpoint;

    // Adiciona parâmetros de consulta, se houver
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }

    $ch = curl_init($url);

    // Configurações do cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Segurança

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception("Erro de conexão: " . curl_error($ch));
    }

    curl_close($ch);

    // Valida resposta HTTP
    if ($httpCode >= 400) {
        throw new Exception("Erro da API OJS ({$httpCode}): " . $response);
    }

    // Decodifica JSON
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
    }

    return $data;
}

// Exemplo de uso
try {
    // Buscar submissões (pode filtrar por status, editor, etc.)
    $result = ojsApiGet($baseUrl, $endpoint, $apiKey, [
        'status' => 1, // 1 = Ativo, 3 = Publicado, etc.
        'count'  => 5  // Limitar resultados
    ]);

    echo "✅ Conexão bem-sucedida! Resultados:\n";
    print_r($result);

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}