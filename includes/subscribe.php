<?php
/**
 * The newsletter subscription form. It posts to newsletter.daniil.it/subscribe (the "newsletter" service
 * next to listmonk, in Daniil Gentili's home.daniil.it repository), which checks Cloudflare Turnstile
 * and subscribes the address to the list "Psalm"; errors come back to /newsletter as ?error=<code>.
 * /assets/js/newsletter.js loads Turnstile once someone starts filling in a form. The docs theme
 * (theme/main.html) has a copy of the form.
 */
require_once __DIR__ . '/asset.php';

final class NewsletterForm
{
    private static bool $rendered = false;

    /**
     * @param 'page'|'box'|'compact' $variant the form of /newsletter (with the error messages), a call to
     *                                        subscribe (e.g. under articles), or the footer's
     */
    public static function render(string $variant): string
    {
        $script = self::$rendered ? '' : '<script src="' . asset_url('/assets/js/newsletter.js') . '" defer></script>' . "\n";
        self::$rendered = true;
        $intro = $variant === 'box'
            ? '    <p class="newsletter__title">Get Psalm news by email</p>' . "\n"
                . '    <p class="newsletter__text">New articles from the Psalm blog, a few emails a year. You can unsubscribe at any time.</p>' . "\n"
            : '';
        $name = $variant === 'page'
            ? '        <label><span>Name <small>(optional)</small></span><input type="text" name="name" maxlength="200" autocomplete="name" placeholder="Your name"></label>' . "\n"
            : '';

        return '<form class="newsletter newsletter--' . $variant . '"' . ($variant === 'page' ? ' id="subscribe"' : '')
            . ' method="post" action="https://newsletter.daniil.it/subscribe">' . "\n"
            . $intro
            . '    <input type="hidden" name="site" value="psalm">' . "\n"
            // Honeypot: bots fill it, people don't see it
            . '    <input class="newsletter__hp" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true">' . "\n"
            . '    <div class="newsletter__fields">' . "\n"
            . '        <label><span>Email</span><input type="email" name="email" required maxlength="254" autocomplete="email" placeholder="you@example.com"></label>' . "\n"
            . $name
            . '        <button type="submit">Subscribe</button>' . "\n"
            . '    </div>' . "\n"
            . '    <label class="newsletter__consent"><input type="checkbox" name="consent" value="1" required> I accept the <a href="https://blog.daniil.it/privacy-policy/" target="_blank">privacy policy</a></label>' . "\n"
            . '    <div class="newsletter__turnstile" data-sitekey="0x4AAAAAAF79Ff0lSBvHmOcq"></div>' . "\n"
            . '</form>' . "\n"
            . $script;
    }

    /**
     * Whether the page already has a form, so the footer doesn't add a second one
     */
    public static function rendered(): bool
    {
        return self::$rendered;
    }
}
