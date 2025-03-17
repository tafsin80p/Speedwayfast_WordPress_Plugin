<?php
if (!defined('ABSPATH')) {
    exit;
}

// Verify user capabilities
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'speedwayfast'));
}

$speedwayfast = SpeedwayFast::get_instance();
?>

<div class="wrap speedwayfast-welcome-page">
    <?php $speedwayfast->render_logo(true); ?>

    <div class="speedwayfast-welcome-content">
        <h1><?php echo esc_html__('Welcome to SpeedwayFast', 'speedwayfast'); ?></h1>
        <p class="speedwayfast-welcome-description">
            <?php echo esc_html__('Thank you for choosing SpeedwayFast - Your ultimate solution for WordPress performance optimization.', 'speedwayfast'); ?>
        </p>

        <div class="speedwayfast-features-grid">
            <div class="speedwayfast-feature-card">
                <div class="feature-icon">
                    <span class="dashicons dashicons-performance"></span>
                </div>
                <h3><?php echo esc_html__('Speed Optimization', 'speedwayfast'); ?></h3>
                <p><?php echo esc_html__('Advanced caching, minification, and compression to boost your site\'s performance.', 'speedwayfast'); ?></p>
            </div>

            <div class="speedwayfast-feature-card">
                <div class="feature-icon">
                    <span class="dashicons dashicons-admin-site"></span>
                </div>
                <h3><?php echo esc_html__('CDN Integration', 'speedwayfast'); ?></h3>
                <p><?php echo esc_html__('Global content delivery network for faster asset loading worldwide.', 'speedwayfast'); ?></p>
            </div>

            <div class="speedwayfast-feature-card">
                <div class="feature-icon">
                    <span class="dashicons dashicons-superhero"></span>
                </div>
                <h3><?php echo esc_html__('AI Optimization', 'speedwayfast'); ?></h3>
                <p><?php echo esc_html__('Smart optimization powered by artificial intelligence for maximum performance.', 'speedwayfast'); ?></p>
            </div>

            <div class="speedwayfast-feature-card">
                <div class="feature-icon">
                    <span class="dashicons dashicons-image-filter"></span>
                </div>
                <h3><?php echo esc_html__('Image Optimization', 'speedwayfast'); ?></h3>
                <p><?php echo esc_html__('Automatic image compression and WebP conversion for faster loading.', 'speedwayfast'); ?></p>
            </div>
        </div>

        <div class="speedwayfast-quick-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=speedwayfast-settings')); ?>" class="speedwayfast-button">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html__('Configure Settings', 'speedwayfast'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=speedwayfast-ai')); ?>" class="speedwayfast-button">
                <span class="dashicons dashicons-superhero"></span>
                <?php echo esc_html__('AI Optimization', 'speedwayfast'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=speedwayfast-cdn')); ?>" class="speedwayfast-button">
                <span class="dashicons dashicons-admin-site"></span>
                <?php echo esc_html__('CDN Setup', 'speedwayfast'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.speedwayfast-welcome-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
    text-align: center;
}

.speedwayfast-welcome-content {
    margin-top: 40px;
}

.speedwayfast-welcome-description {
    font-size: 18px;
    color: #666;
    margin: 20px 0 40px;
}

.speedwayfast-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin: 40px 0;
}

.speedwayfast-feature-card {
    background: white;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    animation: cardSlideUp 0.5s ease-out forwards;
    opacity: 0;
}

.speedwayfast-feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.speedwayfast-feature-card:nth-child(1) { animation-delay: 0.1s; }
.speedwayfast-feature-card:nth-child(2) { animation-delay: 0.2s; }
.speedwayfast-feature-card:nth-child(3) { animation-delay: 0.3s; }
.speedwayfast-feature-card:nth-child(4) { animation-delay: 0.4s; }

.feature-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 20px;
    background: #f5f5f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-icon .dashicons {
    font-size: 30px;
    width: 30px;
    height: 30px;
    color: #2196f3;
}

.speedwayfast-quick-actions {
    margin-top: 40px;
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.speedwayfast-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #2196f3;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.speedwayfast-button:hover {
    background: #1976d2;
    transform: translateY(-2px);
    color: white;
}

.speedwayfast-button .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

@keyframes cardSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style> 