<?php
/**
 * Canonical public webinar page.
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$webinarId = (int) get_the_ID();
$userId = (int) get_current_user_id();
$page = duplication_webinars_get_public_page_view($webinarId, $userId);

if (! is_array($page) || ! isset($page['entity']) || ! is_array($page['entity'])) {
    echo '<main class="duplication-webinar-page">';
    echo '<h1>' . esc_html__('Webinar page unavailable', 'duplication-webinars') . '</h1>';
    echo '</main>';
    get_footer();
    return;
}

$entity = $page['entity'];
$access = is_array($page['access'] ?? null) ? $page['access'] : [];
$cta = is_array($page['cta'] ?? null) ? $page['cta'] : [];
?>
<main class="duplication-webinar-page">
    <article class="duplication-webinar-page__article">
        <header class="duplication-webinar-page__header">
            <h1><?php echo esc_html((string) ($entity['title'] ?? '')); ?></h1>
            <p><strong><?php esc_html_e('page_mode', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($entity['page_mode'] ?? '')); ?></p>
            <p><strong><?php esc_html_e('mode_label', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($page['mode_label'] ?? '')); ?></p>
            <p><strong><?php esc_html_e('status', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($entity['status'] ?? '')); ?></p>
            <p><strong><?php esc_html_e('format', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($entity['format'] ?? '')); ?></p>
            <p><strong><?php esc_html_e('minimum_access_level', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($entity['minimum_access_level'] ?? '')); ?></p>
            <p><strong><?php esc_html_e('public_webinar_page', 'duplication-webinars'); ?>:</strong> <?php echo ! empty($page['is_public_page']) ? 'yes' : 'no'; ?></p>
        </header>

        <section class="duplication-webinar-page__content">
            <?php echo wp_kses_post((string) ($entity['content'] ?? '')); ?>
        </section>

        <section class="duplication-webinar-page__source">
            <h2><?php esc_html_e('Source', 'duplication-webinars'); ?></h2>
            <p><strong><?php esc_html_e('source_type', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($entity['source_type'] ?? '')); ?></p>
            <p><strong><?php esc_html_e('source_value', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($entity['source_value'] ?? '')); ?></p>
        </section>

        <section class="duplication-webinar-page__access">
            <h2><?php esc_html_e('Access', 'duplication-webinars'); ?></h2>
            <p><strong><?php esc_html_e('reason', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($access['reason'] ?? '')); ?></p>
            <p><strong><?php esc_html_e('message', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($access['message'] ?? '')); ?></p>
        </section>

        <section class="duplication-webinar-page__cta">
            <h2><?php esc_html_e('CTA', 'duplication-webinars'); ?></h2>
            <p><strong><?php esc_html_e('cta_code', 'duplication-webinars'); ?>:</strong> <?php echo esc_html((string) ($cta['code'] ?? '')); ?></p>
            <?php if (! empty($cta['enabled']) && ! empty($cta['url'])) : ?>
                <p><a href="<?php echo esc_url((string) $cta['url']); ?>"><?php echo esc_html((string) ($cta['label'] ?? 'Open')); ?></a></p>
            <?php else : ?>
                <p><?php echo esc_html((string) ($cta['label'] ?? 'Not available')); ?></p>
            <?php endif; ?>
        </section>
    </article>
</main>
<?php
get_footer();
