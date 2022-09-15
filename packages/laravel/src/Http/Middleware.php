<?php

namespace Monolikit\Http;

use Closure;
use Illuminate\Http\Request;
use Monolikit\Concerns;
use Monolikit\Monolikit;
use Symfony\Component\HttpFoundation\Response;

class Middleware
{
    use Concerns\SharesValidationErrors;
    use Concerns\SharesFlashNotifications;

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (method_exists($this, 'beforeHandle')) {
            app()->call([$this, 'beforeHandle']);
        }

        monolikit()->setRootView(fn () => $this->rootView($request));
        monolikit()->setVersion(fn () => $this->version($request));

        if (method_exists($this, 'share')) {
            monolikit()->share(app()->call([$this, 'share']));
        }

        if ($this->shareValidationErrors) {
            monolikit()->share($this->shareValidationErrors($request));
        }

        if ($this->shareFlashNotifications) {
            monolikit()->share($this->shareFlashNotifications($request));
        }

        $response = $next($request);

        // Browsers need the Vary header in order to properly cache the response
        // based on its content type. This is specifically important for the
        // monolikit protocol because an endpoint can send JSON and HTML.
        $response->headers->set('Vary', Monolikit::MONOLIKIT_HEADER);

        if (!$request->header(Monolikit::MONOLIKIT_HEADER)) {
            return $next($request);
        }

        // When handling GET requests, we need to check the version header received
        // from the client to determine if they match. If not, we trigger the version change
        // event.
        if ($request->method() === 'GET' && $request->header(Monolikit::VERSION_HEADER) !== monolikit()->getVersion()) {
            $response = $this->onVersionChange($request, $response);
        }

        // If the response has no content, eg. the developer did not return anything from
        // a controller, we can transform the response to have a default behavior.
        if ($response->isOk() && empty($response->getContent())) {
            $response = $this->onEmptyResponse($request, $response);
        }

        if ($response->getStatusCode() === Response::HTTP_FOUND && \in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
            $response->setStatusCode(Response::HTTP_SEE_OTHER);
        }

        return $response;
    }

    /**
     * Determines what to do when the asset version has changed.
     * By default, we'll initiate a client-side external visit to force an update.
     */
    public function onVersionChange(Request $request, Response $response): Response
    {
        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        return monolikit()->external($request->fullUrl());
    }

    /**
     * Determines what to do when an action returned with no response.
     */
    public function onEmptyResponse(Request $request, Response $response): Response
    {
        return $response;
    }

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): \Closure|string|false|null
    {
        if (class_exists($vite = Innocenzi\Vite\Vite::class)) {
            return resolve($vite)->getHash();
        }

        if (config('app.asset_url')) {
            return md5(config('app.asset_url'));
        }

        if (file_exists($manifest = public_path('build/manifest.json'))) {
            return md5_file($manifest);
        }

        if (file_exists($manifest = public_path('mix-manifest.json'))) {
            return md5_file($manifest);
        }

        return null;
    }

    /**
     * Sets the root template that's loaded on the first page visit.
     */
    public function rootView(Request $request): \Closure|string
    {
        return config('monolikit.root_view');
    }

    /**
     * Resolves and prepares validation errors in such
     * a way that they are easier to use client-side.
     */
    public function resolveValidationErrors(Request $request): object
    {
        if (!$request->hasSession()) {
            return (object) [];
        }

        if (!$errors = $request->session()->get('errors')) {
            return (object) [];
        }

        return (object) collect($errors->getBags())
            ->map(function ($bag) {
                return (object) collect($bag->messages())
                    ->map(fn ($errors) => $errors[0])
                    ->toArray();
            })
            ->pipe(function ($bags) use ($request) {
                if ($bags->has('default') && $request->header(Monolikit::ERROR_BAG_HEADER)) {
                    return [$request->header(Monolikit::ERROR_BAG_HEADER) => $bags->get('default')];
                }

                if ($bags->has('default')) {
                    return $bags->get('default');
                }

                return $bags->toArray();
            });
    }
}
