<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\MaintenanceGate;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\RestrictAdminByIp;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrustConfiguredProxies;
use App\Support\Installer\Environment;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Pre-boot preparation
|--------------------------------------------------------------------------
|
| Runs before the framework reads any configuration, because on a fresh upload
| neither the runtime directories nor a usable .env exist yet — and Laravel
| aborts on either one long before a controller could report the problem.
|
| Every step is a no-op once it has been done.
|
*/

Environment::prepare(dirname(__DIR__));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Every request (web, health, install) gets a reference that ties an error page to its log entry.
        $middleware->prepend(RequestId::class);

        // Applies to every web request, public and admin alike.
        // EnsureAccountIsActive ends a deactivated account's session on its
        // next request, wherever that request goes.
        $middleware->web(append: [
            EnsureAccountIsActive::class,
            SecurityHeaders::class,
        ]);

        // Ahead of everything else: an uninstalled site has no database, so
        // any middleware that touches one would fail before the installer
        // could render.
        //
        // MaintenanceMode sits immediately after it, for the same reason in
        // reverse: it reads a setting, so it must not run before the database
        // exists — but it must run before anything renders a public page,
        // otherwise the page it is meant to replace has already been built.
        // Run the deactivated-account check before ANY authentication
        // middleware (Laravel's and Filament's): otherwise authentication has
        // already passed, or Filament has answered 403 while leaving the
        // session alive. Its position after StartSession is preserved.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: EnsureAccountIsActive::class,
        );

        $middleware->web(prepend: [
            \App\Http\Middleware\RedirectToPreferredHost::class,
            EnsureInstalled::class,
            MaintenanceMode::class,
        ]);

        // Stage 2 of the maintenance gate: after the session has started, so staff can be recognised (see MaintenanceMode).
        $middleware->web(append: [
            MaintenanceGate::class,
        ]);

        // Guests reaching an authenticated admin route outside the panel (the
        // homepage and builder previews, private documents) go to the panel's
        // sign-in page. Without this Laravel looks for a `login` route, which
        // does not exist, and answers 500.
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));

        $middleware->alias([
            'locale' => SetLocale::class,
            'admin.ip' => RestrictAdminByIp::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Outside services (a payment provider, for example) cannot send a CSRF token. Plugin webhooks are the only
        // exempt route, they are rate limited, and each plugin must authenticate the sender itself (signature check).
        $middleware->preventRequestForgery(except: ['plugins/*/hook/*', '_wr/a']);
        // First-party measurement cookies are written by the storefront script (consent choice, session id, browser id) and carry no secret.
        $middleware->encryptCookies(except: ['wr_consent', 'wr_as', 'wr_ab']);

        // Passwords are taken exactly as typed. Laravel trims every input by
        // default, which silently turns a credential with a leading or
        // trailing space into one that does not match — and the only symptom
        // is "Access denied" against a password that looks right.
        TrimStrings::except([
            'db_password',
            'mail_password',
            'admin_password',
            'admin_password_confirmation',
        ]);

        // Trust only the proxies listed in TRUSTED_PROXIES (none by default).
        // Never `trustProxies(at: '*')`: that lets any visitor forge their IP
        // through X-Forwarded-For and walk around login throttling and the
        // admin IP allowlist. Laravel's own TrustProxies is replaced because
        // it also trusts every peer for hosts ending in .on-forge.com.
        $middleware->replace(TrustProxies::class, TrustConfiguredProxies::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Never leak stack traces or query bindings in production logs.
        $exceptions->dontReportDuplicates();

        // New code on an old database: a table or column the code expects is not there yet. Answer with a clear
        // "setup required" page (HTTP 503, with the request reference) instead of an unexplained 500. The exact
        // table stays in the log; the page names no table, query or path.
        // A page that does not exist: before answering 404, ask the general legacy-URL resolver whether an old/indexed address
        // has a current counterpart (301) or never will (410). GET/HEAD only, never admin/assets/JSON; any failure falls back to 404.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 404 || $request->expectsJson() || ! $request->isMethodSafe() || $request->is('admin', 'admin/*', 'storage/*', 'build/*', 'livewire/*', 'up', '_wr/*')) {
                return null;
            }
            try {
                $r = app(\App\Services\Legacy\LegacyUrlResolver::class)->resolve($request);
            } catch (\Throwable $t) {
                report($t);

                return null;
            }
            if ($r === null) {
                return null;
            }
            if ($r['status'] === 410) {
                return response()->view('errors.410', [], 410)->header('X-Robots-Tag', 'noindex');
            }

            return redirect()->to($r['url'], 301);
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            $missing = in_array((string) $e->getCode(), ['42S02', '42S22'], true)
                || preg_match('/no such (table|column)|Base table or view not found|Unknown column/i', $e->getMessage()) === 1;

            if (! $missing || $request->expectsJson()) {
                return null;
            }

            report($e);

            return response()->view('errors.setup-required', ['requestId' => app()->bound('request.id') ? app('request.id') : null], 503);
        });
    })
    ->create();
