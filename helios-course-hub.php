<?php

// Developed with the assistance of Claude Code (claude.ai)

namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class HeliosCourseHubPlugin extends Plugin
{
    /** @var bool Whether the Helios theme is missing or inactive */
    protected $themeMissing       = false;
    protected $themeInstalledOnly = false;

    /** @var bool Guard against onShortcodeHandlers firing more than once */
    protected $shortcodesRegistered = false;

    /** @var string|null Computed "Course | Page Title | Site Title" browser title */
    protected $browserTitle = null;

    /** @var string|null URL of favicon.* found in course root page media */
    protected $courseFaviconUrl = null;

    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    public function onPluginsInitialized()
    {
        // Check theme folder and active status directly, as admin may have switched to Quark/Quark2
        $themeName = 'helios';
        $themePath = GRAV_ROOT . '/user/themes/' . $themeName;
        $themeActive = $this->config->get('system.pages.theme') === $themeName;

        $themeInstalled = is_dir($themePath);

        if (!$themeInstalled || !$themeActive) {
            $fallback = is_dir(GRAV_ROOT . '/user/themes/quark2') ? 'quark2' : 'quark';
            $this->config->set('system.pages.theme', $fallback);
            $this->themeMissing       = true;
            $this->themeInstalledOnly = $themeInstalled && !$themeActive;
        }

        // Register page blueprints in every context so they are discoverable
        // from admin, frontend, CLI, and API requests alike.
        $this->enable([
            'onGetPageBlueprints'         => ['onGetPageBlueprints', 0],
            'onApiDashboardNotifications' => ['onApiDashboardNotifications', 0],
        ]);

        if ($this->isAdmin2Route()) {
            $this->enable([
                'onPagesInitialized' => ['onPagesInitializedAdmin2', 1001],
            ]);
            return;
        }

        if ($this->isAdmin()) {
            $this->enable([
                'onAdminTwigTemplatePaths' => ['onAdminHeliosNotice', 0],
                'onPageInitialized'        => ['onPageInitialized', 0],
                'onOutputGenerated'        => ['onOutputGenerated', 0],
            ]);
            return;
        }

        $this->enable([
            'onThemeInitialized' => ['onThemeInitialized', -1000],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            'onTwigSiteVariables' => ['onTwigSiteVariables', -100],
            'onOutputGenerated'   => ['onOutputGenerated', 0],
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
        ]);
    }

    private function isAdmin2Route(): bool
    {
        if (!$this->config->get('plugins.admin2.enabled', false)) {
            return false;
        }
        $route = $this->config->get('plugins.admin2.route', '');
        if (!$route) {
            return false;
        }
        $base = '/' . trim($route, '/');
        $current = $this->grav['uri']->route();
        return $current === $base || str_starts_with($current, $base . '/');
    }

    public function onPagesInitializedAdmin2(): void
    {
        $css = '';

        if ($this->config->get('plugins.helios-course-hub.admin_label_alignment', true)) {
            $labelCssFile = __DIR__ . '/assets/admin-label-alignment.css';
            if (file_exists($labelCssFile)) {
                $css .= file_get_contents($labelCssFile);
            }
        }

        if ($css === '') {
            return;
        }

        ob_start(function (string $html) use ($css): string {
            if (strpos($html, 'data-sveltekit') === false && strpos($html, '</body>') === false) {
                return $html;
            }
            return str_replace('</head>', '<style>' . $css . '</style></head>', $html);
        });
    }

    protected function themeNoticeKey(): string
    {
        return $this->themeInstalledOnly
            ? 'PLUGIN_HELIOS_COURSE_HUB.THEME_INACTIVE_NOTICE'
            : 'PLUGIN_HELIOS_COURSE_HUB.THEME_REQUIRED_NOTICE';
    }

    public function onAdminHeliosNotice(): void
    {
        if (!$this->themeMissing) {
            return;
        }

        $this->grav['messages']->add(
            $this->grav['language']->translate($this->themeNoticeKey()),
            'warning'
        );
    }

    public function onApiDashboardNotifications(Event $event): void
    {
        if (!$this->themeMissing) {
            return;
        }

        $notifications = $event['notifications'] ?? [];
        $notifications['top'][] = [
            'id'             => 'helios-course-hub-theme-required',
            'date'           => date('c'),
            'level'          => 'warning',
            'icon'           => 'shield-alert',
            'location'       => ['top'],
            'message'        => $this->grav['language']->translate($this->themeNoticeKey()),
            'reappear_after' => '+1 days',
        ];
        $event['notifications'] = $notifications;
    }

    public function onPageInitialized()
    {
        $assets = $this->grav['assets'];
        $path = 'plugin://helios-course-hub/assets';

        if ($this->config->get('plugins.helios-course-hub.admin_styling_enhancements', true)) {
            $assets->addCss("$path/admin.css");
        }

        $assets->addJs("$path/admin.js");

        $this->injectHeliosPreset();
        $this->injectLoginCss();

        if ($this->themeMissing) {
            $heliosLicense = \Grav\Common\GPM\Licenses::get('helios');
            $targetRoute   = $heliosLicense ? '/admin/themes' : '/admin/license-manager';
            $currentRoute  = $this->grav['uri']->path();
            $isLoggedIn    = $this->grav['user']->authenticated ?? false;

            if ($isLoggedIn && $currentRoute === '/admin') {
                $this->grav->redirect($targetRoute);
                return;
            }
        }
    }

    protected function injectHeliosPreset()
    {
        // Only inject if no custom presets are already defined by the user
        $existing = $this->config->get('plugins.admin.whitelabel.custom_presets');
        if (!empty($existing)) {
            return;
        }

        $preset = file_get_contents(__DIR__ . '/helios-preset.yaml');
        $this->config->set('plugins.admin.whitelabel.custom_presets', $preset);
    }

    protected function injectLoginCss()
    {
        // Only inject if no custom_css is already defined by the user
        $existing = $this->config->get('plugins.admin.whitelabel.custom_css');
        if (!empty($existing)) {
            return;
        }

        $this->config->set(
            'plugins.admin.whitelabel.custom_css',
            '#admin-login h1 svg path:first-child { fill: rgba(255, 255, 255, 0.10); }'
        );
    }

    public function onGetPageBlueprints($event)
    {
        $types = $event->types;
        $types->scanBlueprints('plugin://helios-course-hub/blueprints');
    }

    public function onThemeInitialized()
    {
        // Override version switcher labels using active language translations
        $lang       = $this->grav['language'];
        $activeLang = $lang->getLanguage() ?: 'en';
        $courseLabel = $lang->translate('PLUGIN_HELIOS_COURSE_HUB.COURSE_LABEL');
        $latestLabel = $lang->translate('PLUGIN_HELIOS_COURSE_HUB.COURSE_LATEST_LABEL');

        $this->grav['languages']->mergeRecursive([
            $activeLang => [
                'THEME_HELIOS' => [
                    'VERSION'        => $courseLabel,
                    'VERSION_LATEST' => $latestLabel,
                ],
            ],
        ]);
    }

    public function onTwigTemplatePaths()
    {
        if ($this->themeMissing) {
            return;
        }
        $twig = $this->grav['twig'];
        array_unshift($twig->twig_paths, __DIR__ . '/templates');
    }

    public function onShortcodeHandlers()
    {
        if ($this->shortcodesRegistered) {
            return;
        }
        $this->shortcodesRegistered = true;

        $shortcodes = $this->grav['shortcode'];
        $dir = __DIR__ . '/shortcodes';

        // Register only .php files to avoid processing .DS_Store and similar
        foreach (new \DirectoryIterator($dir) as $file) {
            if ($file->isDot() || $file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }
            try {
                $shortcodes->registerShortcode($file->getFilename(), $dir);
            } catch (\RuntimeException $e) {
                // Handler already registered by another plugin (e.g. helios-open-reader)
            }
        }
    }

    public function onTwigSiteVariables()
    {
        if ($this->themeMissing) {
            return;
        }

        $assets = $this->grav['assets'];
        $path = 'plugin://helios-course-hub/assets';

        $assets->addCss("$path/helios.css");
        $assets->addCss("$path/print.css", ['media' => 'print']);
        $assets->addJs("$path/helios.js", ['group' => 'bottom', 'loading' => 'defer']);

        // Inject site-wide chapter card description line override when value differs from theme default (2)
        $chapterDescLines = (int)$this->config->get('plugins.helios-course-hub.chapter_description_lines', 2);
        if ($chapterDescLines !== 2) {
            $css = $chapterDescLines === 0
                ? '#htmx-article p.line-clamp-2 { overflow: visible; display: block; -webkit-line-clamp: unset; }'
                : '#htmx-article p.line-clamp-2 { -webkit-line-clamp: ' . $chapterDescLines . '; }';
            $assets->addInlineCSS($css);
        }

        $githubServer = $this->config->get('plugins.helios-course-hub.github_server', 'github.com');
        $githubLinkIcon = $this->config->get('plugins.helios-course-hub.github_link_icon', 'tabler/file-text.svg');
        $githubLinkMode = $this->config->get('plugins.helios-course-hub.github_link_mode', 'view');
        $showGithubHeaderIcon = $this->config->get('plugins.helios-course-hub.show_github_header_icon', true);
        $showSiteIcon = $this->config->get('plugins.helios-course-hub.show_site_icon', true);
        $siteIcon = $this->config->get('plugins.helios-course-hub.site_icon', '');
        $showPluginCredits = $this->config->get('plugins.helios-course-hub.show_plugin_credits', true);
        // Use card_icon from the course-list page as the default course label icon
        $courseListPage = null;
        foreach ($this->grav['pages']->instances() as $p) {
            if ($p->template() === 'course-list') {
                $courseListPage = $p;
                break;
            }
        }
        $courseLabelIcon = ($courseListPage && ($courseListPage->header()->card_icon ?? false))
            ? $courseListPage->header()->card_icon
            : '';
        $twig = $this->grav['twig'];
        $twig->twig_vars['github_server'] = $githubServer;
        $twig->twig_vars['github_link_icon'] = $githubLinkIcon;
        $twig->twig_vars['github_link_mode'] = $githubLinkMode;
        $twig->twig_vars['show_github_header_icon'] = $showGithubHeaderIcon;
        $twig->twig_vars['show_site_icon'] = $showSiteIcon;
        $twig->twig_vars['site_icon'] = $siteIcon;
        $twig->twig_vars['show_plugin_credits'] = $showPluginCredits;
        $twig->twig_vars['course_label_icon'] = $courseLabelIcon;
        // Hide sidebar and header when ?embedded=true or ?chromeless=true is present in the URL
        $uri = $this->grav['uri'];
        $twig->twig_vars['chromeless'] = (bool) $uri->query('embedded') || (bool) $uri->query('chromeless');
        // Override TOC visibility/position via ?toc_position=hidden|left|right or ?toc=hidden|left|right (null when param absent)
        $tocParam = $uri->query('toc_position') ?: $uri->query('toc') ?: null;
        $twig->twig_vars['toc_url_param'] = ($tocParam !== null && $tocParam !== false) ? $tocParam : null;
        // Hide Git edit link via ?edit_link=false (theme vocab, primary) or ?hidegitlink=true (Docsify alias)
        $twig->twig_vars['hide_git_link'] = $uri->query('edit_link') === 'false' || $uri->query('hidegitlink') === 'true';
        $twig->twig_vars['helios_base_simple'] = 'partials/base-simple.html.twig';

        // Default logo URL to site root; overridden below when only one course is active
        $twig->twig_vars['logo_url'] = $this->grav['base_url'] ?: '/';

        // Filter doc_version_info to respect 'visible: false' in course frontmatter.
        // Runs at priority -100 to ensure the theme has already populated this variable.
        if (isset($twig->twig_vars['doc_version_info'])) {
            $pages = $this->grav['pages'];
            $versionInfo = $twig->twig_vars['doc_version_info'];

            $filteredVersions = array_values(array_filter($versionInfo['versions'], function ($version) use ($pages) {
                $versionId = is_array($version) ? ($version['id'] ?? null) : ($version->id ?? null);
                if (!$versionId) {
                    return true;
                }
                $page = $pages->find('/' . $versionId);
                if (!$page) {
                    return true;
                }
                return $page->published();
            }));

            // Enrich versions with icon from page frontmatter
            $enrichedVersions = [];
            foreach ($filteredVersions as $version) {
                $versionId = is_array($version) ? ($version['id'] ?? null) : ($version->id ?? null);
                if ($versionId) {
                    $versionPage = $pages->find('/' . $versionId);
                    if ($versionPage && ($versionPage->header()->icon ?? false)) {
                        $version['icon'] = $versionPage->header()->icon;
                    }
                }
                $enrichedVersions[] = $version;
            }
            $filteredVersions = $enrichedVersions;

            $versionInfo['versions'] = $filteredVersions;
            $versionInfo['count'] = count($filteredVersions);
            $twig->twig_vars['doc_version_info'] = $versionInfo;

            // Set course_label_url to the first child page of the current course version
            $twig->twig_vars['course_label_url'] = null;
            $twig->twig_vars['course_sidebar_image'] = null;
            foreach ($filteredVersions as $version) {
                $isCurrent = is_array($version) ? ($version['is_current'] ?? false) : ($version->is_current ?? false);
                if ($isCurrent) {
                    $versionId = is_array($version) ? ($version['id'] ?? null) : ($version->id ?? null);
                    if ($versionId) {
                        $versionPage = $pages->find('/' . $versionId);
                        if ($versionPage) {
                            $firstChild = $versionPage->children()->first();
                            if ($firstChild) {
                                $twig->twig_vars['course_label_url'] = $firstChild->url();
                            }
                            // Check for favicon.* in course root page media (convention-based)
                            // Strip any Admin-added numeric prefix (e.g. "130_favicon.png" → "favicon.png")
                            foreach ($versionPage->media()->all() as $filename => $medium) {
                                $basename = preg_replace('/^\d+_/', '', $filename);
                                if (strncmp($basename, 'favicon.', 8) === 0) {
                                    $this->courseFaviconUrl = $medium->url();
                                    break;
                                }
                            }

                            // Check for course card image to show as sidebar banner
                            // Respect show_sidebar_image toggle (default: hide)
                            $showSidebarImage = $versionPage->header()->show_sidebar_image ?? 0;
                            $courseImage = $versionPage->header()->image ?? null;
                            if ($showSidebarImage && $courseImage) {
                                $courseImageBasename = preg_replace('/^\d+_/', '', $courseImage);
                                foreach ($versionPage->media()->all() as $filename => $medium) {
                                    $basename = preg_replace('/^\d+_/', '', $filename);
                                    if ($basename === $courseImageBasename) {
                                        $twig->twig_vars['course_sidebar_image'] = $medium->url();
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    break;
                }
            }

            // When logo_link_target is 'single_course' and only one course is active, point the logo link to its first child page
            $logoLinkTarget = $this->config->get('plugins.helios-course-hub.logo_link_target', 'single_course');
            if ($logoLinkTarget === 'single_course' && $versionInfo['count'] === 1) {
                $singleVersion = $versionInfo['versions'][0] ?? null;
                $versionId = is_array($singleVersion) ? ($singleVersion['id'] ?? null) : ($singleVersion->id ?? null);
                if ($versionId) {
                    $versionPage = $pages->find('/' . $versionId);
                    if ($versionPage) {
                        $firstChild = $versionPage->children()->first();
                        if ($firstChild) {
                            $twig->twig_vars['logo_url'] = $firstChild->url();
                        }
                    }
                }
            }

            // Build "Course | Page Title | Site Title" browser title when a current version exists
            $page = $this->grav['page'];
            $pageTitle = $page ? $page->title() : '';
            $siteTitle = $this->grav['config']->get('site.title', '');

            $courseLabel = null;
            foreach ($filteredVersions as $version) {
                $isCurrent = is_array($version) ? ($version['is_current'] ?? false) : ($version->is_current ?? false);
                if ($isCurrent) {
                    $courseLabel = is_array($version) ? ($version['label'] ?? null) : ($version->label ?? null);
                    break;
                }
            }

            if ($courseLabel && $pageTitle && $siteTitle && $versionInfo['count'] > 1) {
                $this->browserTitle = $pageTitle . ' | ' . $courseLabel . ' | ' . $siteTitle;
            }
        }
    }

    public function onOutputGenerated($event)
    {
        if ($this->isAdmin()) {
            $fontSize = $this->config->get('plugins.helios-course-hub.admin_font_size', 'large');
            if ($fontSize !== 'default') {
                $cssFile = __DIR__ . "/assets/admin-fonts-{$fontSize}.css";
                if (file_exists($cssFile)) {
                    $css = file_get_contents($cssFile);
                    $event['output'] = str_replace('</head>', '<style>' . $css . '</style></head>', $event['output']);
                }
            }
        }

        if ($this->browserTitle !== null) {
            $event['output'] = preg_replace(
                '~<title>[^<]*</title>~',
                '<title>' . htmlspecialchars($this->browserTitle, ENT_QUOTES, 'UTF-8') . '</title>',
                $event['output'],
                1
            );
        }

        if ($this->courseFaviconUrl !== null) {
            $faviconTag = '<link rel="icon" href="' . htmlspecialchars($this->courseFaviconUrl, ENT_QUOTES, 'UTF-8') . '">';
            $event['output'] = preg_replace(
                '~<link rel="icon"[^>]*>~',
                $faviconTag,
                $event['output'],
                1
            );
        }
    }
}
