<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Add Template Routes that point one-off page URLs at pages/index.
 *
 * Each route is a plain static pattern (e.g. `about`) -> pages/index.
 * pages/index looks the entry up by {segment_1}, which still holds the
 * real URI segment under a route (EE's router picks the template but does
 * not rewrite ee()->uri). Static patterns don't collide with the
 * functional template groups, so no catch-all / pass-through routes are
 * needed.
 *
 * Only add a slug here once that page's content is composed in the
 * page_content_blocks Fluid field — a route to an unmodelled page renders
 * an empty pages/index.
 *
 * Idempotent: skips any slug that already has a route on pages/index
 * (e.g. one added by hand in the CP).
 */
class AddPagesIndexRoutes extends Migration
{
    /**
     * url_title slugs to route at pages/index. Each must match the
     * `pages` channel entry's url_title.
     */
    private $slugs = [
        // 'about',
        // 'terms-of-service',
        // 'privacy-policy',
        // 'cookie-policy',
        // 'faqs',
        // 'join-the-team',
        // 'website-training',
        // 'local-partners',
    ];

    public function up()
    {
        if (empty($this->slugs)) {
            return;
        }

        $template = $this->getIndexTemplate();

        if (! $template) {
            throw new \Exception('pages/index template not found; sync templates first.');
        }

        $template_id = $template->template_id;

        $existing = ee('Model')->get('TemplateRoute')
            ->filter('template_id', $template_id)
            ->all()
            ->pluck('route');

        $last = ee('Model')->get('TemplateRoute')
            ->order('order', 'desc')
            ->first();
        $order = $last ? (int) $last->order : 0;

        foreach ($this->slugs as $slug) {
            if (in_array($slug, $existing, true)) {
                continue;
            }

            $order++;
            $route = ee('Model')->make('TemplateRoute');
            $route->template_id    = $template_id;
            $route->route          = $slug;
            $route->route_required = 'n';
            $route->order          = $order;
            $route->save();
        }
    }

    public function down()
    {
        if (empty($this->slugs)) {
            return;
        }

        $template = $this->getIndexTemplate();

        if (! $template) {
            return;
        }

        $routes = ee('Model')->get('TemplateRoute')
            ->filter('template_id', $template->template_id)
            ->filter('route', 'IN', $this->slugs)
            ->all();

        foreach ($routes as $route) {
            $route->delete();
        }
    }

    private function getIndexTemplate()
    {
        return ee('Model')->get('Template')
            ->filter('template_name', 'index')
            ->filter('TemplateGroup.group_name', 'pages')
            ->first();
    }
}
