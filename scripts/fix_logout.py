from pathlib import Path

SRC = Path.home() / "projetos/scountBCR/src"
ROOT = Path.home() / "projetos/scountBCR"

boot = SRC / "bootstrap/app.php"
t = boot.read_text()
if "trustProxies" not in t:
    t = t.replace(
        "function (Middleware $middleware) {\n        //\n    }",
        'function (Middleware $middleware) {\n        $middleware->trustProxies(at: "*");\n    }',
    )
    boot.write_text(t)

panel = SRC / "app/Providers/Filament/AdminPanelProvider.php"
t = panel.read_text()
t = t.replace("use Filament\\Http\\Middleware\\AuthenticateSession;\n", "")
t = t.replace("                AuthenticateSession::class,\n", "")
panel.write_text(t)

ctrl = SRC / "app/Http/Controllers/Auth/AuthenticatedSessionController.php"
t = ctrl.read_text()
t = t.replace("return redirect('/');", "return redirect()->route('login');")
ctrl.write_text(t)

compose = ROOT / "docker-compose.yml"
ct = compose.read_text()
if "SESSION_DRIVER" not in ct:
    ct = ct.replace(
        "      DB_PASSWORD: scountbcr\n",
        "      DB_PASSWORD: scountbcr\n      SESSION_DRIVER: file\n      SESSION_SECURE_COOKIE: \"false\"\n",
    )
    compose.write_text(ct)

nav = SRC / "resources/views/layouts/navigation.blade.php"
nav.write_text(nav.read_text().replace(
    """                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>""",
    """                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit"
                                class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                {{ __('Log Out') }}
                            </button>
                        </form>""",
).replace(
    """                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>""",
    """                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out">
                        {{ __('Log Out') }}
                    </button>
                </form>""",
))

print("ok")