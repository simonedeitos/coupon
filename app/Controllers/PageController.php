<?php

declare(strict_types=1);

namespace App\Controllers;

final class PageController
{
    public function show(string $slug): array
    {
        $page = config('app.static_pages.' . $slug);
        if (! is_array($page)) {
            return response_view('frontend/pages/404', ['meta' => app('seo')->meta(['title' => 'Pagina non trovata', 'path' => request_path()])], 'app', 404);
        }
        $breadcrumbs = [['label' => $page['title'], 'url' => '/' . $slug]];
        $meta = app('seo')->meta(['title' => $page['title'], 'description' => $page['summary'], 'path' => '/' . $slug, 'breadcrumbs' => $breadcrumbs]);
        return response_view('frontend/pages/static', compact('page', 'meta', 'slug', 'breadcrumbs'));
    }

    public function newsletter(): array
    {
        $email = filter_var((string) request_input('email', ''), FILTER_VALIDATE_EMAIL);
        if ($email) {
            app('cache')->appendJsonLine('logs', 'newsletter.log', ['email' => $email, 'created_at' => date('c')]);
            flash('success', 'Iscrizione newsletter registrata.');
        } else {
            flash('error', 'Inserisci un indirizzo email valido.');
        }
        return redirect('/');
    }

    public function sitemap(): array
    {
        $urls = [
            ['path' => '/', 'priority' => '1.0'], ['path' => '/categorie'], ['path' => '/negozi'], ['path' => '/coupon'], ['path' => '/come-funziona'], ['path' => '/chi-siamo'], ['path' => '/privacy'], ['path' => '/cookie']
        ];
        foreach (app('categoryRepository')->all() as $category) {
            $urls[] = ['path' => '/categoria/' . $category['slug']];
        }
        foreach (app('storeRepository')->all() as $store) {
            $urls[] = ['path' => '/negozio/' . $store['slug']];
        }
        foreach (app('offerRepository')->all() as $offer) {
            $urls[] = ['path' => '/coupon/' . $offer['slug'], 'priority' => '0.8'];
        }
        return \App\Helpers\Response::xml(app('sitemap')->generate($urls), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
