<?php

declare(strict_types=1);

namespace MsAuth;

/**
 * Implementacao minima de JWT (JSON Web Token) com algoritmo HS256.
 *
 * Evita dependencias externas para fins didaticos, mantendo o microsservico
 * autocontido. Em producao, considere a biblioteca firebase/php-jwt.
 */
final class Jwt
{
    /**
     * Gera um token assinado.
     *
     * @param array<string,mixed> $claims Dados adicionais (ex.: sub, role)
     */
    public static function encode(array $claims, string $secret, string $issuer, int $ttl): string
    {
        $now = time();
        $header  = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = array_merge($claims, [
            'iss' => $issuer,
            'iat' => $now,
            'exp' => $now + $ttl,
        ]);

        $segments = [
            self::base64UrlEncode((string) json_encode($header)),
            self::base64UrlEncode((string) json_encode($payload)),
        ];

        $signature  = self::sign(implode('.', $segments), $secret);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Decodifica e valida assinatura + expiracao.
     *
     * @return array<string,mixed>|null Claims se valido; null caso contrario.
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $expected = self::sign("{$encodedHeader}.{$encodedPayload}", $secret);
        $provided = self::base64UrlDecode($encodedSignature);

        if (!hash_equals($expected, $provided)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private static function sign(string $data, string $secret): string
    {
        return hash_hmac('sha256', $data, $secret, true);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
