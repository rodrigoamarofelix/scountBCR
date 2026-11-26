#!/usr/bin/env python3
from pathlib import Path
import re

ROOT = Path.home() / "projetos/scountBCR"
SRC = ROOT / "src"

def patch_env(path: Path, key: str, value: str) -> None:
  if not path.exists():
    return
  text = path.read_text()
  pattern = rf"^{re.escape(key)}=.*$"
  replacement = f"{key}={value}"
  if re.search(pattern, text, flags=re.M):
    text = re.sub(pattern, replacement, text, flags=re.M)
  else:
    text += f"\n{replacement}\n"
  path.write_text(text)

# .env e .env.example
for key, val in {
  "APP_LOCALE": "pt_BR",
  "APP_FALLBACK_LOCALE": "pt_BR",
  "APP_FAKER_LOCALE": "pt_BR",
  "APP_TIMEZONE": "America/Sao_Paulo",
}.items():
  patch_env(SRC / ".env", key, val)
  patch_env(SRC / ".env.example", key, val)

# config/app.php defaults
app_cfg = SRC / "config/app.php"
text = app_cfg.read_text()
text = text.replace("env('APP_LOCALE', 'en')", "env('APP_LOCALE', 'pt_BR')")
text = text.replace("env('APP_FALLBACK_LOCALE', 'en')", "env('APP_FALLBACK_LOCALE', 'pt_BR')")
text = text.replace("env('APP_FAKER_LOCALE', 'en_US')", "env('APP_FAKER_LOCALE', 'pt_BR')")
text = text.replace("env('APP_TIMEZONE', 'UTC')", "env('APP_TIMEZONE', 'America/Sao_Paulo')")
app_cfg.write_text(text)

# docker-compose locale
compose = ROOT / "docker-compose.yml"
ct = compose.read_text()
if "APP_LOCALE" not in ct:
  ct = ct.replace(
    "      APP_URL: http://localhost:8082\n",
    "      APP_URL: http://localhost:8082\n"
    "      APP_LOCALE: pt_BR\n"
    "      APP_FALLBACK_LOCALE: pt_BR\n"
    "      APP_FAKER_LOCALE: pt_BR\n"
    "      APP_TIMEZONE: America/Sao_Paulo\n",
  )
  compose.write_text(ct)

# AppServiceProvider
(SRC / "app/Providers/AppServiceProvider.php").write_text("""<?php

namespace App\\Providers;

use Carbon\\Carbon;
use Illuminate\\Support\\Facades\\App;
use Illuminate\\Support\\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        App::setLocale(config('app.locale'));
        Carbon::setLocale(config('app.locale'));
    }
}
""")

# AdminPanelProvider — locale pt_BR
panel = SRC / "app/Providers/Filament/AdminPanelProvider.php"
pt = panel.read_text()
if "->locale('pt_BR')" not in pt:
  pt = pt.replace(
    "->path('admin')\n",
    "->path('admin')\n            ->locale('pt_BR')\n",
  )
if "->databaseNotifications" not in pt:
  pass
panel.write_text(pt)

# Shield pt-BR overrides
shield_lang = SRC / "lang/pt_BR/filament-shield.php"
shield_lang.parent.mkdir(parents=True, exist_ok=True)
shield_lang.write_text("""<?php

return [
    'column.name' => 'Nome',
    'column.guard_name' => 'Guarda',
    'column.roles' => 'Perfis',
    'column.permissions' => 'Permissões',
    'column.updated_at' => 'Atualizado em',
    'field.name' => 'Nome',
    'field.guard_name' => 'Guarda',
    'field.permissions' => 'Permissões',
    'field.select_all.name' => 'Selecionar todos',
    'field.select_all.message' => 'Habilitar todas as permissões para este perfil',
    'nav.group' => 'Segurança',
    'nav.role.label' => 'Perfis de acesso',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Perfil',
    'resource.label.roles' => 'Perfis de acesso',
    'section' => 'Entidades',
    'resources' => 'Recursos',
    'widgets' => 'Widgets',
    'pages' => 'Páginas',
    'custom' => 'Permissões personalizadas',
];
""")

# Breeze / app strings (merge into pt_BR.json)
import json

json_path = SRC / "lang/pt_BR.json"
data = json.loads(json_path.read_text(encoding="utf-8"))
custom = {
    "Dashboard": "Painel",
    "Profile": "Meu perfil",
    "Register": "Criar conta",
    "Log Out": "Sair",
    "Log in": "Entrar",
    "Save": "Salvar",
    "Cancel": "Cancelar",
    "Delete": "Excluir",
    "Edit": "Editar",
    "Create": "Criar",
    "Search": "Buscar",
    "Actions": "Ações",
    "ScountBCR": "ScountBCR",
}
data.update(custom)
json_path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

# Traduções para Filament actions (lang custom)
filament_extra = SRC / "lang/pt_BR/scountbcr.php"
filament_extra.write_text("""<?php

return [
    'groups' => [
        'cadastros' => 'Cadastros',
        'competicao' => 'Competição',
        'seguranca' => 'Segurança',
    ],
];
""")

print("pt-BR configurado")