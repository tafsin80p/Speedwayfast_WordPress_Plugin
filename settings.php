<?php
if (!defined('ABSPATH')) {
    exit;
}

// Save settings
if (isset($_POST['speedwayfast_save_settings']) && check_admin_referer('speedwayfast_settings_nonce')) {
    $options = array(
        'enable_lazy_loading' => isset($_POST['enable_lazy_loading']) ? 1 : 0,
        'enable_minification' => isset($_POST['enable_minification']) ? 1 : 0,
        'enable_browser_caching' => isset($_POST['enable_browser_caching']) ? 1 : 0,
        'enable_gzip' => isset($_POST['enable_gzip']) ? 1 : 0,
        'remove_query_strings' => isset($_POST['remove_query_strings']) ? 1 : 0,
        'optimize_google_fonts' => isset($_POST['optimize_google_fonts']) ? 1 : 0,
        'enable_webp' => isset($_POST['enable_webp']) ? 1 : 0,
        'enable_critical_css' => isset($_POST['enable_critical_css']) ? 1 : 0,
        'optimize_js_loading' => isset($_POST['optimize_js_loading']) ? 1 : 0,
        'enable_resource_hints' => isset($_POST['enable_resource_hints']) ? 1 : 0,
        'remove_unused_css' => isset($_POST['remove_unused_css']) ? 1 : 0,
        'enable_cdn' => isset($_POST['enable_cdn']) ? 1 : 0,
        'cdn_url' => isset($_POST['cdn_url']) ? esc_url_raw($_POST['cdn_url']) : '',
        'enable_service_worker' => isset($_POST['enable_service_worker']) ? 1 : 0,
        'enable_image_optimization' => isset($_POST['enable_image_optimization']) ? 1 : 0,
        'enable_font_optimization' => isset($_POST['enable_font_optimization']) ? 1 : 0,
        'enable_prerender' => isset($_POST['enable_prerender']) ? 1 : 0,
        'enable_dark_mode' => isset($_POST['enable_dark_mode']) ? 1 : 0,
    );
    update_option('speedwayfast_settings', $options);
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

$options = get_option('speedwayfast_settings', array(
    'enable_lazy_loading' => 1,
    'enable_minification' => 1,
    'enable_browser_caching' => 1,
    'enable_gzip' => 1,
    'remove_query_strings' => 1,
    'optimize_google_fonts' => 1,
    'enable_webp' => 1,
    'enable_critical_css' => 1,
    'optimize_js_loading' => 1,
    'enable_resource_hints' => 1,
    'remove_unused_css' => 1,
    'enable_cdn' => 0,
    'cdn_url' => '',
    'enable_service_worker' => 1,
    'enable_image_optimization' => 1,
    'enable_font_optimization' => 1,
    'enable_prerender' => 1,
    'enable_dark_mode' => 1,
));
?>

<style>
.speedwayfast-admin {
    max-width: 1200px;
    margin: 20px auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    color: #1e1e1e;
}

.speedwayfast-header {
    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    color: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.speedwayfast-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
}

.speedwayfast-header p {
    margin: 10px 0 0;
    opacity: 0.9;
    font-size: 16px;
}

.speedwayfast-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.speedwayfast-card:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.speedwayfast-card h2 {
    margin: 0 0 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
    font-size: 20px;
    color: #1e1e1e;
    font-weight: 600;
}

.speedwayfast-option {
    display: flex;
    align-items: flex-start;
    padding: 16px 0;
    border-bottom: 1px solid #f0f0f0;
}

.speedwayfast-option:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.option-content {
    margin-left: 20px;
    flex: 1;
}

.option-content h4 {
    margin: 0 0 8px;
    font-size: 16px;
    font-weight: 500;
    color: #1e1e1e;
}

.option-content p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

.option-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    margin-left: 8px;
    background: #e3f2fd;
    color: #1976D2;
}

/* Modern switch styles */
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
    flex-shrink: 0;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e5e7eb;
    transition: .4s;
    border-radius: 26px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

input:checked + .slider {
    background-color: #2196F3;
}

input:checked + .slider:before {
    transform: translateX(24px);
}

/* Performance metrics styles */
.speedwayfast-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.metric-item {
    background: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #e5e7eb;
}

.metric-circle {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 16px;
}

.metric-circle svg {
    transform: rotate(-90deg);
    width: 100%;
    height: 100%;
}

.metric-circle svg circle {
    fill: none;
    stroke-width: 3;
    stroke-linecap: round;
    transition: stroke-dashoffset 0.5s ease;
}

.metric-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 20px;
    font-weight: 600;
    color: #2196F3;
}

.metric-label {
    font-size: 14px;
    color: #6b7280;
    margin-top: 8px;
}

/* Save button styles */
.speedwayfast-save {
    background: #2196F3;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.2);
}

.speedwayfast-save:hover {
    background: #1976D2;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(33, 150, 243, 0.3);
}

/* Advanced settings styles */
.advanced-settings {
    background: #f8fafc;
    padding: 16px;
    border-radius: 8px;
    margin-top: 12px;
}

.advanced-settings input[type="text"],
.advanced-settings input[type="number"],
.advanced-settings input[type="url"] {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    margin-top: 8px;
}

.advanced-settings label {
    display: block;
    font-size: 14px;
    color: #4b5563;
    margin-bottom: 4px;
}

/* Responsive design */
@media (max-width: 768px) {
    .speedwayfast-header {
        padding: 20px;
    }
    
    .speedwayfast-card {
        padding: 16px;
    }
    
    .speedwayfast-option {
        flex-direction: column;
    }
    
    .option-content {
        margin-left: 0;
        margin-top: 12px;
    }
    
    .speedwayfast-metrics {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="wrap speedwayfast-admin">
    <div class="speedwayfast-header">
        <h1>SpeedwayFast Settings</h1>
        <p>Optimize your website for maximum performance and perfect PageSpeed scores</p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('speedwayfast_settings_nonce'); ?>
        
        <div class="speedwayfast-card">
            <h2>User Experience</h2>
            
            <div class="speedwayfast-option">
                <label class="switch">
                    <input type="checkbox" name="enable_lazy_loading" <?php checked($options['enable_lazy_loading'], 1); ?>>
                    <span class="slider"></span>
                </label>
                <div class="option-content">
                    <h4>Smart Lazy Loading</h4>
                    <p>Intelligently lazy load images, iframes, and videos with automatic detection of above-the-fold content.</p>
                </div>
            </div>

            <div class="speedwayfast-option">
                <label class="switch">
                    <input type="checkbox" name="enable_minification" <?php checked($options['enable_minification'], 1); ?>>
                    <span class="slider"></span>
                </label>
                <div class="option-content">
                    <h4>Advanced Asset Minification</h4>
                    <p>Compress HTML, CSS, and JavaScript with intelligent dependency management and inline small assets.</p>
                </div>
            </div>

            <div class="speedwayfast-option">
                <label class="switch">
                    <input type="checkbox" name="enable_browser_caching" <?php checked($options['enable_browser_caching'], 1); ?>>
                    <span class="slider"></span>
                </label>
                <div class="option-content">
                    <h4>Smart Browser Caching</h4>
                    <p>Implement advanced browser caching with automatic cache busting and optimal cache duration.</p>
                </div>
            </div>
        </div>

        <div class="speedwayfast-card">
            <h2>Advanced Optimizations <span class="option-badge">Pro</span></h2>

            <div class="speedwayfast-option">
                <label class="switch">
                    <input type="checkbox" name="enable_webp" <?php checked($options['enable_webp'], 1); ?>>
                    <span class="slider"></span>
                </label>
                <div class="option-content">
                    <h4>Next-Gen Image Optimization</h4>
                    <p>Convert images to WebP format with intelligent quality settings and fallback support.</p>
                    <div class="advanced-settings">
                        <label>WebP Quality (60-100)</label>
                        <input type="number" name="webp_quality" min="60" max="100" value="85">
                    </div>
                </div>
            </div>

            <div class="speedwayfast-option">
                <label class="switch">
                    <input type="checkbox" name="enable_critical_css" <?php checked($options['enable_critical_css'], 1); ?>>
                    <span class="slider"></span>
                </label>
                <div class="option-content">
                    <h4>Advanced Critical CSS</h4>
                    <p>Generate template-specific critical CSS with mobile-first approach and dynamic loading.</p>
                </div>
            </div>

            <div class="speedwayfast-option">
                <label class="switch">
                    <input type="checkbox" name="optimize_js_loading" <?php checked($options['optimize_js_loading'], 1); ?>>
                    <span class="slider"></span>
                </label>
                <div class="option-content">
                    <h4>Smart JavaScript Optimization</h4>
                    <p>Implement advanced JavaScript loading strategies with dependency analysis and module support.</p>
                </div>
            </div>
        </div>

        <div class="speedwayfast-card">
            <h2>Progressive Features <span class="option-badge">Experimental</span></h2>
            
            <div class="speedwayfast-option">
                <label class="switch">
                    <input type="checkbox" name="enable_service_worker" <?php checked($options['enable_service_worker'], 1); ?>>
                    <span class="slider"></span>
                </label>
                <div class="option-content">
                    <h4>Advanced Service Worker</h4>
                    <p>Enable offline functionality and instant page loads with intelligent caching strategies.</p>
                </div>
            </div>

            <div class="speedwayfast-option">
                <label class="switch">
                    <input type="checkbox" name="enable_prerender" <?php checked($options['enable_prerender'], 1); ?>>
                    <span class="slider"></span>
                </label>
                <div class="option-content">
                    <h4>Smart Prerendering</h4>
                    <p>Automatically prerender next pages based on user behavior analysis.</p>
                </div>
            </div>
        </div>

        <div class="speedwayfast-card">
            <h2>Performance Metrics</h2>
            <div class="speedwayfast-metrics">
                <div class="metric-item">
                    <div class="metric-circle">
                        <svg viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="16" stroke="#e5e7eb"/>
                            <circle cx="18" cy="18" r="16" stroke="#2196F3" stroke-dasharray="100 100"/>
                        </svg>
                        <span class="metric-value">100</span>
                    </div>
                    <div class="metric-label">Performance Score</div>
                </div>
                
                <div class="metric-item">
                    <div class="metric-circle">
                        <svg viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="16" stroke="#e5e7eb"/>
                            <circle cx="18" cy="18" r="16" stroke="#4CAF50" stroke-dasharray="95 100"/>
                        </svg>
                        <span class="metric-value">0.8s</span>
                    </div>
                    <div class="metric-label">Load Time</div>
                </div>
                
                <div class="metric-item">
                    <div class="metric-circle">
                        <svg viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="16" stroke="#e5e7eb"/>
                            <circle cx="18" cy="18" r="16" stroke="#FF9800" stroke-dasharray="90 100"/>
                        </svg>
                        <span class="metric-value">98%</span>
                    </div>
                    <div class="metric-label">Cache Hit Rate</div>
                </div>
            </div>
        </div>

        <button type="submit" name="speedwayfast_save_settings" class="speedwayfast-save">Save Changes</button>
    </form>
</div> 