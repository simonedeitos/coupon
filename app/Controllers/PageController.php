<?php

declare(strict_types=1);

namespace App\Controllers;

final class PageController
{
    public function contact(): array
    {
        $breadcrumbs = [['label' => 'Contatti', 'url' => '/contatti']];
        $meta = app('seo')->meta(['title' => 'Contatti - Couponami', 'description' => 'Scrivici per informazioni, segnalazioni o partnership.', 'path' => '/contatti', 'breadcrumbs' => $breadcrumbs]);
        return response_view('frontend/pages/contatti', compact('meta', 'breadcrumbs'));
    }

    public function contactSubmit(): array
    {
        $name = trim((string) request_input('name', ''));
        $email = filter_var(trim((string) request_input('email', '')), FILTER_VALIDATE_EMAIL);
        $subjectRaw = (string) request_input('subject', 'info');
        $allowedSubjects = ['info', 'segnalazione', 'partnership', 'altro'];
        $subject = in_array($subjectRaw, $allowedSubjects, true) ? $subjectRaw : 'info';
        $message = trim((string) request_input('message', ''));
        if (! $email || $name === '' || $message === '' || strlen($message) > 2000) {
            flash('error', 'Compila tutti i campi obbligatori con dati validi (messaggio max 2000 caratteri).');
            set_old_input(['name' => $name, 'email' => (string) request_input('email', ''), 'subject' => $subject, 'message' => mb_substr($message, 0, 2000)]);
            return redirect('/contatti');
        }
        app('cache')->appendJsonLine('logs', 'contact.log', [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => date('c'),
        ]);
        flash('success', 'Messaggio inviato correttamente. Ti risponderemo al più presto.');
        return redirect('/contatti');
    }

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
            ['path' => '/', 'priority' => '1.0'], ['path' => '/categorie'], ['path' => '/negozi'], ['path' => '/coupon'], ['path' => '/come-funziona'], ['path' => '/chi-siamo'], ['path' => '/privacy'], ['path' => '/cookie'], ['path' => '/contatti'],
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

    public function robots(): array
    {
        $content = implode(PHP_EOL, [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Sitemap: ' . rtrim((string) config('app.base_url'), '/') . '/sitemap.xml',
            '',
        ]);

        return ['type' => 'raw', 'content' => $content, 'status' => 200, 'headers' => ['Content-Type' => 'text/plain; charset=utf-8']];
    }
}
