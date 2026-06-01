<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Framework Whitelabel de Aluguel</title>
    <style>
        :root { --brand: #2563eb; --bg: #0f172a; --card: #1e293b; --text: #e2e8f0; }
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: var(--bg); color: var(--text); min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 2rem;
        }
        .card {
            background: var(--card); border-radius: 16px; padding: 2.5rem;
            max-width: 720px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }
        h1 { margin: 0 0 .5rem; font-size: 1.6rem; }
        p.lead { color: #94a3b8; margin-top: 0; }
        .badge {
            display: inline-block; background: var(--brand); color: #fff;
            font-size: .75rem; padding: .25rem .6rem; border-radius: 999px; margin-bottom: 1rem;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { text-align: left; padding: .6rem .8rem; border-bottom: 1px solid #334155; }
        th { color: #94a3b8; font-weight: 600; font-size: .85rem; }
        code { background: #0b1220; padding: .15rem .4rem; border-radius: 6px; color: #7dd3fc; }
        footer { margin-top: 1.5rem; font-size: .8rem; color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">API Gateway &middot; porta 8003</span>
        <h1>Framework Whitelabel para Aluguel de Imoveis</h1>
        <p class="lead">Ponto de entrada unico do ecossistema. As requisicoes sao roteadas
        de forma <strong>stateless</strong> para os microsservicos internos via cURL.</p>

        <table>
            <thead>
                <tr><th>Rota (Gateway)</th><th>Microsservico</th><th>Porta</th></tr>
            </thead>
            <tbody>
                <tr><td><code>/api/auth/*</code></td><td>ms-auth</td><td>8000</td></tr>
                <tr><td><code>/api/imoveis/*</code></td><td>ms-catalogo</td><td>8001</td></tr>
                <tr><td><code>/api/reservas/*</code></td><td>ms-reservas</td><td>8002</td></tr>
            </tbody>
        </table>

        <footer>UFAL 2026.1 &middot; Reuso de Software e Metodologias Ageis</footer>
    </div>
</body>
</html>
