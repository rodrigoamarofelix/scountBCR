<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>ScountBCR — Manual do sistema</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; line-height: 1.45; }
        h1 { font-size: 22px; margin: 0 0 6px; color: #b45309; }
        h2 { font-size: 15px; margin: 22px 0 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        h3 { font-size: 12px; margin: 14px 0 6px; }
        .meta { color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        code { background: #f3f4f6; padding: 1px 4px; font-size: 10px; }
        pre { background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px; font-size: 9px; white-space: pre-wrap; }
        ul { margin: 6px 0 12px 18px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>ScountBCR</h1>
    <p class="meta">Manual de operação e implantação · Gerado em {{ $generated_at }} · Laravel 11 + Filament 3 + PostgreSQL</p>

    <h2>1. Visão geral</h2>
    <p>Sistema de <strong>scouting estatístico de basquete</strong>: cadastro de equipes, atletas, partidas, registro ao vivo de arremessos, dashboards, comparativos e relatórios PDF/Excel.</p>
    <table>
        <tr><th>Componente</th><th>Descrição</th></tr>
        <tr><td>Stack</td><td>Laravel 11, Filament 3, Livewire 3, Breeze, Shield (Spatie)</td></tr>
        <tr><td>Banco</td><td>PostgreSQL 16 (Docker)</td></tr>
        <tr><td>Pasta do projeto</td><td><code>~/projetos/scountBCR</code> (código Laravel em <code>src/</code>)</td></tr>
    </table>

    <h2>2. Subir o ambiente</h2>
    <pre>cd ~/projetos/scountBCR
docker compose up -d --build
docker compose exec app composer install
docker compose exec app cp .env.example .env   # se ainda não existir
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
docker compose exec app npm install && npm run build</pre>

    <h2>3. Portas e URLs</h2>
    <table>
        <tr><th>Serviço</th><th>Porta (host)</th><th>URL</th></tr>
        <tr><td>Nginx / aplicação</td><td>8082</td><td>http://localhost:8082</td></tr>
        <tr><td>Painel Filament</td><td>8082</td><td>http://localhost:8082/admin</td></tr>
        <tr><td>Scouting ao vivo</td><td>8082</td><td>http://localhost:8082/partidas/{id}/scout</td></tr>
        <tr><td>PostgreSQL</td><td>5435</td><td>host: localhost, db: scountbcr</td></tr>
        <tr><td>PHP-FPM</td><td>9002</td><td>interno Docker</td></tr>
    </table>

    <h2>4. Acesso inicial</h2>
    <table>
        <tr><th>Campo</th><th>Valor (desenvolvimento)</th></tr>
        <tr><td>E-mail</td><td><code>admin@scountbcr.local</code></td></tr>
        <tr><td>Senha</td><td><code>password</code> (trocar em produção)</td></tr>
        <tr><td>Papéis do admin</td><td><code>super_admin</code>, <code>administrador</code>, <code>panel_user</code></td></tr>
    </table>

    <h2>5. Roadmap por etapas</h2>
    <table>
        <tr><th>Etapa</th><th>Entregável</th></tr>
        <tr><td>1</td><td>Migrations, models, enums, ShotStatisticsService, seeders base</td></tr>
        <tr><td>2</td><td>Resources Filament (equipes, atletas, partidas, convocação)</td></tr>
        <tr><td>3</td><td>Scouting ao vivo (Livewire), policy de scout</td></tr>
        <tr><td>4</td><td>Dashboard estatístico, comparativo de atletas (Chart.js)</td></tr>
        <tr><td>5</td><td>Relatórios PDF/Excel, perfil estatístico do atleta</td></tr>
        <tr><td>6</td><td>Shield, papéis, testes Domain, Docker com extensão GD</td></tr>
    </table>

    <div class="page-break"></div>

    <h2>6. Permissões (Filament Shield)</h2>
    <h3>6.1 Papéis</h3>
    <table>
        <tr><th>Papel</th><th>Uso</th></tr>
        <tr><td>super_admin</td><td>Acesso total (Shield)</td></tr>
        <tr><td>administrador</td><td>Todas as permissões do painel</td></tr>
        <tr><td>treinador</td><td>Operação diária; scouting e relatórios</td></tr>
        <tr><td>analista</td><td>Leitura + análises; atualiza partida/atleta</td></tr>
        <tr><td>consulta</td><td>Somente leitura e dashboards; <strong>sem</strong> scouting nem relatórios</td></tr>
    </table>

    <h3>6.2 Ordem correta dos comandos</h3>
    <p><strong>Importante:</strong> executar <code>ShieldRolePermissionsSeeder</code> sozinho <strong>não recria o usuário admin</strong>. Se a tabela <code>users</code> estiver vazia, o login falha.</p>
    <pre>docker compose exec app php artisan shield:generate --all --panel=admin --option=permissions --no-interaction
docker compose exec app php artisan db:seed --class=RoleAndAdminSeeder --force
docker compose exec app php artisan db:seed --class=ShieldRolePermissionsSeeder --force
docker compose exec app php artisan permission:cache-reset</pre>
    <p>Alternativa (recomendada após clonar o projeto):</p>
    <pre>docker compose exec app php artisan db:seed --force</pre>
    <p>O <code>ShieldRolePermissionsSeeder</code> atualizado garante o usuário <code>admin@scountbcr.local</code> e os papéis acima.</p>

    <h2>7. Funcionalidades principais</h2>
    <h3>7.1 Painel Filament (<code>/admin</code>)</h3>
    <ul>
        <li><strong>Cadastros:</strong> equipes, adversários, competições, atletas, partidas</li>
        <li><strong>Análises:</strong> dashboard, comparativo, central de relatórios</li>
        <li><strong>Partidas:</strong> ação «Scouting ao vivo» (mesma aba do navegador)</li>
        <li><strong>Atletas:</strong> ação «Perfil estatístico»</li>
    </ul>

    <h3>7.2 Scouting ao vivo</h3>
    <ul>
        <li>Selecionar atleta (cartão com foto circular)</li>
        <li>Registrar 2pts, 3pts e lance livre (convertido / errado)</li>
        <li>Placar e período (1º–4º); botão «Voltar às partidas»</li>
        <li>Papel <code>consulta</code> não pode acessar (policy <code>scout</code>)</li>
    </ul>

    <h3>7.3 Relatórios</h3>
    <ul>
        <li>Tipos: partida, atleta, equipe, comparativo, evolução</li>
        <li>PDF: abre no navegador (inline); Excel: download <code>.xlsx</code></li>
        <li>Requer permissão <code>page_ReportsHub</code> (exportação pelo perfil usa <code>page_AthleteProfile</code>)</li>
        <li>Pacote Excel: <code>maatwebsite/excel</code> 3.x + extensão PHP <code>gd</code></li>
    </ul>

    <h2>8. Testes automatizados</h2>
    <pre>docker compose exec app php artisan test --testsuite=Domain</pre>
    <p>Cobre estatísticas, scouting (placar/desfazer) e policy de scout. Usa SQLite em memória (<code>.env.testing</code>).</p>

    <div class="page-break"></div>

    <h2>9. Solução de problemas</h2>
    <table>
        <tr><th>Sintoma</th><th>Causa provável</th><th>Ação</th></tr>
        <tr><td>Não consigo logar no /admin</td><td>Tabela users vazia ou sem papéis</td><td><code>db:seed --class=RoleAndAdminSeeder</code> e <code>ShieldRolePermissionsSeeder</code></td></tr>
        <tr><td>422 «Selecione um atleta» no relatório</td><td>URL de export desatualizada</td><td>Preencher filtros; usar botões do cabeçalho (sincroniza formulário)</td></tr>
        <tr><td>Excel: interface FromArray não encontrada</td><td>Pacote Excel 1.x antigo</td><td><code>composer require maatwebsite/excel:^3.1</code> + GD</td></tr>
        <tr><td>ERR_CONNECTION_REFUSED</td><td>Docker parado</td><td><code>docker compose up -d</code></td></tr>
        <tr><td>ParseError em GameMatchResource</td><td>Vírgula após ação scout</td><td>Corrigir array <code>->actions([...])</code></td></tr>
    </table>

    <h2>10. Manutenção</h2>
    <pre>docker compose exec app php artisan optimize:clear
docker compose exec app php artisan db:seed --class=DemoDataSeeder --force   # dados demo
docker compose build app && docker compose up -d   # reconstruir PHP (ext. gd)</pre>

    <h2>11. Regenerar este PDF</h2>
    <pre>docker compose exec app php artisan scount:docs-pdf</pre>
    <p>Saída: <code>docs/ScountBCR-Manual.pdf</code> (raiz do repositório).</p>

    <p class="meta" style="margin-top: 24px;">ScountBCR · Documentação interna · Não commitar credenciais de produção.</p>
</body>
</html>