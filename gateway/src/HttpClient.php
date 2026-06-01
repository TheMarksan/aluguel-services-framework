<?php

declare(strict_types=1);

namespace Gateway;

/**
 * Cliente HTTP minimalista baseado em cURL.
 *
 * Responsavel por toda a comunicacao interna do Gateway com os microsservicos.
 * E intencionalmente "burro": apenas encaminha metodo, corpo e cabecalhos,
 * mantendo o Gateway stateless (nao guarda sessao nem estado entre chamadas).
 */
final class HttpClient
{
    public function __construct(private readonly int $timeout = 10)
    {
    }

    /**
     * Encaminha uma requisicao para um microsservico interno.
     *
     * @param array<string,string> $headers Cabecalhos a repassar (ex.: Authorization)
     * @return array{status:int,body:string,content_type:string}
     */
    public function forward(string $method, string $url, string $body = '', array $headers = []): array
    {
        $ch = curl_init();

        $curlHeaders = ['Accept: application/json'];
        foreach ($headers as $name => $value) {
            $curlHeaders[] = "{$name}: {$value}";
        }
        if ($body !== '') {
            $curlHeaders[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $curlHeaders,
        ]);

        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response    = curl_exec($ch);
        $errorNo     = curl_errno($ch);
        $errorMsg    = curl_error($ch);
        $statusCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($errorNo !== 0) {
            return [
                'status'       => 502,
                'body'         => json_encode([
                    'error'   => 'Bad Gateway',
                    'message' => "Falha ao contatar o microsservico: {$errorMsg}",
                ], JSON_UNESCAPED_UNICODE),
                'content_type' => 'application/json',
            ];
        }

        return [
            'status'       => $statusCode,
            'body'         => is_string($response) ? $response : '',
            'content_type' => $contentType !== '' ? $contentType : 'application/json',
        ];
    }
}
