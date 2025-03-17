<?php
if (!defined('ABSPATH')) {
    exit;
}

class SpeedwayFast_Optimizer {
    private $options;

    public function __construct() {
        $this->options = get_option('speedwayfast_settings', array());
        $this->init();
    }

    public function init() {
        // Initialize optimization features based on settings
        if (!empty($this->options['enable_lazy_loading'])) {
            add_filter('the_content', array($this, 'add_lazy_loading'));
            add_filter('post_thumbnail_html', array($this, 'add_lazy_loading'));
            add_filter('widget_text', array($this, 'add_lazy_loading'));
            add_filter('get_avatar', array($this, 'add_lazy_loading'));
        }

        // Priority loading for LCP elements
        add_filter('wp_get_attachment_image_attributes', array($this, 'optimize_image_loading_priority'), 10, 2);
        add_action('wp_head', array($this, 'add_preload_critical_assets'), 1);
        add_action('wp_footer', array($this, 'add_layout_stability_fixes'), 1);
        
        // Enhanced resource loading
        add_filter('style_loader_tag', array($this, 'optimize_css_delivery'), 10, 4);
        add_filter('script_loader_tag', array($this, 'optimize_script_loading'), 10, 3);
        
        // Font optimization for FCP
        add_action('wp_head', array($this, 'optimize_font_loading'), 1);
        
        // Enhanced minification
        if (!empty($this->options['enable_minification'])) {
            add_action('wp_print_styles', array($this, 'minify_css'), 100);
            add_action('wp_print_scripts', array($this, 'minify_js'), 100);
            add_filter('script_loader_tag', array($this, 'add_module_type'), 10, 3);
            add_action('wp_footer', array($this, 'inline_small_scripts'), 999);
        }

        // Advanced font optimization
        if (!empty($this->options['optimize_google_fonts'])) {
            add_action('wp_head', array($this, 'optimize_google_fonts'), 1);
            add_action('wp_head', array($this, 'preload_google_fonts'), 1);
        }

        // Enhanced query string removal
        if (!empty($this->options['remove_query_strings'])) {
            add_filter('style_loader_src', array($this, 'remove_query_strings'), 15);
            add_filter('script_loader_src', array($this, 'remove_query_strings'), 15);
            add_filter('get_avatar_url', array($this, 'remove_query_strings'), 15);
        }

        // Initialize all other optimizations
        $this->init_advanced_optimizations();

        // Add dark mode support
        add_action('wp_head', array($this, 'add_dark_mode_support'), 1);
        add_action('wp_footer', array($this, 'add_dark_mode_toggle'), 99);
        add_action('wp_ajax_speedwayfast_save_theme', array($this, 'save_theme_preference'));
        add_action('wp_ajax_nopriv_speedwayfast_save_theme', array($this, 'save_theme_preference'));
    }

    private function init_advanced_optimizations() {
        // Add preload for critical resources
        add_action('wp_head', array($this, 'add_preload_hints'), 1);
        
        // Optimize WordPress core
        $this->optimize_wordpress_core();

        // Add WebP support with quality control
        add_filter('wp_handle_upload', array($this, 'convert_to_webp'));
        add_filter('wp_calculate_image_srcset', array($this, 'add_webp_srcset'), 10, 5);
        
        // Enhanced Critical CSS with template awareness
        add_action('wp_head', array($this, 'add_critical_css'), 1);
        
        // Advanced JavaScript optimization
        add_action('wp_enqueue_scripts', array($this, 'optimize_js_loading'), 99);
        
        // Resource hints with priority
        add_action('wp_head', array($this, 'add_resource_hints'), 1);

        // HTTP/2 Server Push optimization
        add_action('send_headers', array($this, 'http2_server_push'), 99);

        // Advanced image optimization
        add_filter('wp_generate_attachment_metadata', array($this, 'optimize_image_metadata'), 10, 2);
        add_filter('image_make_intermediate_size', array($this, 'optimize_intermediate_size'));

        // Memory usage optimization
        add_action('init', array($this, 'optimize_memory_usage'));
        
        // Database optimization
        add_action('wp_scheduled_delete', array($this, 'cleanup_database'));
    }

    public function add_lazy_loading($content) {
        if (is_admin()) {
            return $content;
        }

        // Add loading="lazy" to images
        $content = preg_replace('/<img(.*?)>/', '<img$1 loading="lazy">', $content);
        
        // Add loading="lazy" to iframes
        $content = preg_replace('/<iframe(.*?)>/', '<iframe$1 loading="lazy">', $content);

        return $content;
    }

    public function minify_css() {
        global $wp_styles;
        if (!is_object($wp_styles)) {
            return;
        }

        foreach ($wp_styles->queue as $handle) {
            $style = $wp_styles->registered[$handle];
            $source = $style->src;

            if (strpos($source, get_site_url()) !== false) {
                // Local file, we can minify it
                $content = file_get_contents($source);
                
                // Basic CSS minification
                $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
                $content = str_replace(array("\r\n", "\r", "\n", "\t"), '', $content);
                $content = preg_replace('/ {2,}/', ' ', $content);
                
                // Save minified version
                $minified_path = dirname($source) . '/' . basename($source, '.css') . '.min.css';
                file_put_contents($minified_path, $content);
                
                // Update source to minified version
                $wp_styles->registered[$handle]->src = str_replace('.css', '.min.css', $source);
            }
        }
    }

    public function minify_js() {
        global $wp_scripts;
        if (!is_object($wp_scripts)) {
            return;
        }

        foreach ($wp_scripts->queue as $handle) {
            $script = $wp_scripts->registered[$handle];
            $source = $script->src;

            if (strpos($source, get_site_url()) !== false) {
                // Local file, we can minify it
                $content = file_get_contents($source);
                
                // Basic JS minification
                $content = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $content);
                $content = str_replace(array("\r\n", "\r", "\n", "\t"), '', $content);
                $content = preg_replace('/ {2,}/', ' ', $content);
                
                // Save minified version
                $minified_path = dirname($source) . '/' . basename($source, '.js') . '.min.js';
                file_put_contents($minified_path, $content);
                
                // Update source to minified version
                $wp_scripts->registered[$handle]->src = str_replace('.js', '.min.js', $source);
            }
        }
    }

    public function optimize_google_fonts() {
        global $wp_styles;
        if (!is_object($wp_styles)) {
            return;
        }

        $google_fonts_urls = array();
        foreach ($wp_styles->queue as $handle) {
            if (isset($wp_styles->registered[$handle]->src) && 
                strpos($wp_styles->registered[$handle]->src, 'fonts.googleapis.com') !== false) {
                $google_fonts_urls[] = $wp_styles->registered[$handle]->src;
                wp_dequeue_style($handle);
            }
        }

        if (!empty($google_fonts_urls)) {
            // Combine all Google Fonts into one request
            $combined_url = 'https://fonts.googleapis.com/css?family=';
            $families = array();
            
            foreach ($google_fonts_urls as $url) {
                parse_str(parse_url($url, PHP_URL_QUERY), $query);
                if (isset($query['family'])) {
                    $families[] = $query['family'];
                }
            }
            
            $combined_url .= implode('|', array_unique($families));
            wp_enqueue_style('google-fonts-combined', $combined_url, array(), null);
            
            // Add preconnect for Google Fonts
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        }
    }

    public function remove_query_strings($src) {
        if (strpos($src, '?ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    public function add_preload_hints() {
        // Preload critical assets
        $critical_resources = array(
            get_stylesheet_uri() => 'style',
            get_template_directory_uri() . '/assets/js/main.js' => 'script',
            get_template_directory_uri() . '/assets/fonts/main-font.woff2' => 'font'
        );

        foreach ($critical_resources as $url => $type) {
            echo "<link rel='preload' href='{$url}' as='{$type}' crossorigin>\n";
        }
    }

    public function convert_to_webp($upload) {
        if (!isset($upload['file']) || !function_exists('imagewebp')) {
            return $upload;
        }

        $file = $upload['file'];
        $image_types = array(IMAGETYPE_JPEG, IMAGETYPE_PNG);
        $file_type = exif_imagetype($file);

        if (!in_array($file_type, $image_types)) {
            return $upload;
        }

        switch ($file_type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($file);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            default:
                return $upload;
        }

        $webp_file = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
        imagewebp($image, $webp_file, 90);
        imagedestroy($image);

        return $upload;
    }

    public function add_webp_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        if (empty($sources)) {
            return $sources;
        }

        foreach ($sources as &$source) {
            $webp_src = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source['url']);
            if (file_exists(str_replace(site_url('/'), ABSPATH, $webp_src))) {
                $source['url'] = $webp_src;
            }
        }

        return $sources;
    }

    public function add_critical_css() {
        // Enhanced critical CSS with template awareness
        $critical_css = $this->generate_critical_css();
        
        // Add CSS optimization
        $critical_css .= "
            /* Performance optimizations */
            img {
                content-visibility: auto;
                contain: size layout paint;
            }
            
            .wp-block-image img {
                height: auto;
                max-width: 100%;
            }
            
            /* Layout stability */
            body {
                overflow-x: hidden;
            }
            
            /* Font optimization */
            @media (prefers-reduced-data: reduce) {
                * {
                    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif !important;
                }
            }
        ";
        
        echo '<style id="critical-css">' . $this->minify_css_content($critical_css) . '</style>';
        
        // Defer non-critical CSS with fallback
        add_filter('style_loader_tag', function($tag, $handle) {
            if ($handle !== 'critical-css') {
                $tag = str_replace(
                    "rel='stylesheet'",
                    "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"",
                    $tag
                );
                
                // Add fallback for browsers that don't support preload
                $src = wp_styles()->registered[$handle]->src;
                $tag .= "<noscript><link rel='stylesheet' href='{$src}'></noscript>";
            }
            return $tag;
        }, 10, 2);
    }

    public function optimize_js_loading() {
        global $wp_scripts;
        
        if (!is_object($wp_scripts)) {
            return;
        }

        $critical_scripts = array('jquery'); // Add other critical scripts here
        
        foreach ($wp_scripts->registered as $handle => $script) {
            if (!in_array($handle, $critical_scripts)) {
                // Enhanced script loading
                $script->extra['defer'] = true;
                
                // Add module type for modern browsers
                if (strpos($script->src, '.module.js') !== false) {
                    $script->extra['module'] = true;
                }
                
                // Add async for non-render blocking scripts
                if (false !== stripos($script->src, 'wp-includes/js/')) {
                    $script->extra['async'] = true;
                }
            }
        }

        // Add script loading optimization
        add_filter('script_loader_tag', function($tag, $handle) use ($wp_scripts) {
            if (!isset($wp_scripts->registered[$handle])) {
                return $tag;
            }

            $script = $wp_scripts->registered[$handle];

            // Add fetchpriority for critical scripts
            if (in_array($handle, array('jquery'))) {
                $tag = str_replace(' src', ' fetchpriority="high" src', $tag);
            }

            // Add module type
            if (!empty($script->extra['module'])) {
                $tag = str_replace(' src', ' type="module" src', $tag);
            }

            // Add connection-aware loading
            $tag = str_replace('></script>', ' data-connection-aware="true"></script>', $tag);
            $tag .= "<script>
                if ('connection' in navigator) {
                    const script = document.currentScript.previousElementSibling;
                    if (navigator.connection.effectiveType === 'slow-2g' || 
                        navigator.connection.effectiveType === '2g') {
                        script.setAttribute('defer', 'true');
                    }
                }
            </script>";

            return $tag;
        }, 10, 2);
    }

    public function add_resource_hints() {
        // DNS prefetch
        $domains = array(
            'fonts.googleapis.com',
            'fonts.gstatic.com',
            'ajax.googleapis.com',
            'cdn.jsdelivr.net'
        );

        foreach ($domains as $domain) {
            echo "<link rel='dns-prefetch' href='//{$domain}'>\n";
            echo "<link rel='preconnect' href='https://{$domain}' crossorigin>\n";
        }
    }

    private function minify_css_content($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove space after colons
        $css = str_replace(': ', ':', $css);
        // Remove whitespace
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        
        return $css;
    }

    private function optimize_wordpress_core() {
        // Remove unnecessary WordPress features
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
        remove_action('wp_head', 'feed_links_extra', 3);
        
        // Disable emojis
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        
        // Disable XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');
        
        // Remove jQuery migrate
        add_action('wp_default_scripts', function($scripts) {
            if (!is_admin() && isset($scripts->registered['jquery'])) {
                $script = $scripts->registered['jquery'];
                if ($script->deps) {
                    $script->deps = array_diff($script->deps, array('jquery-migrate'));
                }
            }
        });

        // Additional core optimizations
        // Disable self pingbacks
        add_action('pre_ping', function(&$links) {
            $home = get_option('home');
            foreach ($links as $l => $link) {
                if (0 === strpos($link, $home)) {
                    unset($links[$l]);
                }
            }
        });

        // Remove REST API links
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('template_redirect', 'rest_output_link_header', 11);

        // Disable embeds
        add_action('init', function() {
            remove_action('rest_api_init', 'wp_oembed_register_route');
            remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
        }, 9999);

        // Remove unnecessary dashboard widgets
        add_action('wp_dashboard_setup', function() {
            remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
            remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
            remove_meta_box('dashboard_primary', 'dashboard', 'normal');
            remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
        });
    }

    public function register_service_worker() {
        if (is_ssl()) {
            echo "<script>
                if ('serviceWorker' in navigator) {
                    window.addEventListener('load', function() {
                        navigator.serviceWorker.register('" . esc_url(plugins_url('assets/js/service-worker.js', dirname(__FILE__))) . "')
                            .then(function(registration) {
                                console.log('ServiceWorker registration successful');
                            })
                            .catch(function(err) {
                                console.log('ServiceWorker registration failed: ', err);
                            });
                    });
                }
            </script>";
        }
    }

    public function optimize_uploaded_image($file) {
        if (!isset($file['file']) || !function_exists('imagecreatefromstring')) {
            return $file;
        }

        $image_path = $file['file'];
        $image_data = file_get_contents($image_path);
        $image = imagecreatefromstring($image_data);

        if ($image === false) {
            return $file;
        }

        // Optimize image quality
        $info = getimagesize($image_path);
        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($image, $image_path, 85); // Balanced quality
                break;
            case 'image/png':
                // Convert PNG to JPG if no transparency
                if (!$this->has_transparency($image)) {
                    $jpg_path = preg_replace('/\.png$/', '.jpg', $image_path);
                    imagejpeg($image, $jpg_path, 85);
                    unlink($image_path); // Remove original PNG
                    $file['file'] = $jpg_path;
                    $file['type'] = 'image/jpeg';
                } else {
                    // Optimize PNG
                    imagepng($image, $image_path, 8); // Balanced compression
                }
                break;
        }

        imagedestroy($image);
        return $file;
    }

    private function has_transparency($image) {
        if (imagecolortransparent($image) >= 0) {
            return true;
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($image, $x, $y);
                if (($rgba & 0x7F000000) >> 24) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function optimize_font_loading() {
        // Add font-display swap
        echo "<style>
            @font-face {
                font-display: swap !important;
            }
        </style>";

        // Preload fonts
        $font_files = $this->get_theme_font_files();
        foreach ($font_files as $font) {
            echo "<link rel='preload' href='{$font}' as='font' type='font/woff2' crossorigin>\n";
        }
    }

    private function get_theme_font_files() {
        $fonts = array();
        $theme_dir = get_template_directory();
        $font_extensions = array('woff2', 'woff', 'ttf');

        if (is_dir($theme_dir . '/assets/fonts')) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme_dir . '/assets/fonts')) as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), $font_extensions)) {
                    $fonts[] = str_replace($theme_dir, get_template_directory_uri(), $file->getPathname());
                }
            }
        }

        return $fonts;
    }

    public function add_prerender_hints() {
        global $post;
        
        if (is_single() || is_page()) {
            // Get next/prev posts
            $next_post = get_next_post();
            $prev_post = get_previous_post();

            if ($next_post) {
                echo "<link rel='prerender' href='" . get_permalink($next_post) . "'>\n";
            }
            if ($prev_post) {
                echo "<link rel='prerender' href='" . get_permalink($prev_post) . "'>\n";
            }
        }
    }

    public function setup_http2_server_push() {
        if (!function_exists('header_remove')) {
            return;
        }

        // Add Link headers for HTTP/2 Server Push
        add_filter('script_loader_src', function($src) {
            if ($src) {
                header("Link: <{$src}>; rel=preload; as=script", false);
            }
            return $src;
        }, 99, 1);

        add_filter('style_loader_src', function($src) {
            if ($src) {
                header("Link: <{$src}>; rel=preload; as=style", false);
            }
            return $src;
        }, 99, 1);
    }

    private function generate_critical_css() {
        // Generate critical CSS based on current template
        $template = get_template();
        $critical_css = "
            /* Base styles */
            body { display: block; margin: 0; }
            .header { width: 100%; position: relative; }
            .main-content { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
            
            /* Mobile-first responsive styles */
            @media (max-width: 768px) {
                .header { position: sticky; top: 0; z-index: 100; }
                .main-content { padding: 0 10px; }
            }
            
            /* Performance optimizations */
            * { text-rendering: optimizeLegibility; -webkit-font-smoothing: antialiased; }
            img, video { max-width: 100%; height: auto; }
            
            /* Layout optimizations */
            .wp-block-image img { height: auto; }
            .entry-content { width: 100%; }
        ";

        return $critical_css;
    }

    public function optimize_memory_usage() {
        // Disable WordPress emoji support
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_action('wp_print_styles', 'print_emoji_styles');

        // Disable XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');

        // Disable pingbacks
        add_filter('xmlrpc_methods', function($methods) {
            unset($methods['pingback.ping']);
            return $methods;
        });
    }

    public function cleanup_database() {
        global $wpdb;

        // Clean up post revisions
        $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'");

        // Clean up auto drafts
        $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");

        // Clean up expired transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%' AND option_value < UNIX_TIMESTAMP()");

        // Optimize tables
        $wpdb->query("OPTIMIZE TABLE {$wpdb->posts}, {$wpdb->postmeta}, {$wpdb->options}, {$wpdb->comments}, {$wpdb->commentmeta}");
    }

    public function optimize_image_metadata($metadata, $attachment_id) {
        if (!is_array($metadata)) {
            return $metadata;
        }

        // Add modern image attributes
        $metadata['responsive'] = true;
        $metadata['loading'] = 'lazy';
        $metadata['decoding'] = 'async';
        $metadata['fetchpriority'] = 'auto';

        // Enhanced WebP generation with quality optimization
        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $data) {
                $file_path = get_attached_file($attachment_id);
                $dir_path = dirname($file_path);
                $webp_path = $dir_path . '/' . pathinfo($data['file'], PATHINFO_FILENAME) . '.webp';
                
                if (function_exists('imagewebp')) {
                    $image = $this->create_image_resource($dir_path . '/' . $data['file']);
                    if ($image) {
                        // Adaptive quality based on image size
                        $quality = $this->calculate_optimal_quality($data['width'], $data['height']);
                        imagewebp($image, $webp_path, $quality);
                        imagedestroy($image);
                        $metadata['sizes'][$size]['webp'] = basename($webp_path);
                        
                        // Add AVIF support if available
                        if (function_exists('imageavif')) {
                            $avif_path = $dir_path . '/' . pathinfo($data['file'], PATHINFO_FILENAME) . '.avif';
                            imageavif($image, $avif_path, 80);
                            $metadata['sizes'][$size]['avif'] = basename($avif_path);
                        }
                    }
                }
            }
        }

        return $metadata;
    }

    private function calculate_optimal_quality($width, $height) {
        $size = $width * $height;
        
        // Adaptive quality based on image size
        if ($size > 1000000) { // Large images
            return 75;
        } elseif ($size > 500000) { // Medium images
            return 80;
        } else { // Small images
            return 85;
        }
    }

    private function create_image_resource($file_path) {
        $type = exif_imagetype($file_path);
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($file_path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($file_path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($file_path);
            default:
                return false;
        }
    }

    public function http2_server_push() {
        if (!function_exists('header_remove')) {
            return;
        }

        $assets = array();
        
        // Get enqueued styles and scripts
        global $wp_styles, $wp_scripts;
        
        // Enhanced resource prioritization
        $priority_resources = array(
            'critical-css' => 'high',
            'jquery' => 'high',
            'wp-block-library' => 'high'
        );
        
        if (is_object($wp_styles)) {
            foreach ($wp_styles->queue as $handle) {
                $style = $wp_styles->registered[$handle];
                if (strpos($style->src, get_site_url()) !== false) {
                    $priority = isset($priority_resources[$handle]) ? $priority_resources[$handle] : 'auto';
                    $assets[] = array(
                        'path' => $style->src,
                        'type' => 'style',
                        'priority' => $priority
                    );
                }
            }
        }
        
        if (is_object($wp_scripts)) {
            foreach ($wp_scripts->queue as $handle) {
                $script = $wp_scripts->registered[$handle];
                if (strpos($script->src, get_site_url()) !== false) {
                    $priority = isset($priority_resources[$handle]) ? $priority_resources[$handle] : 'auto';
                    $assets[] = array(
                        'path' => $script->src,
                        'type' => 'script',
                        'priority' => $priority
                    );
                }
            }
        }

        // Add optimized Link headers for HTTP/2 Server Push
        foreach ($assets as $asset) {
            header(
                sprintf(
                    'Link: <%s>; rel=preload; as=%s; fetchpriority=%s',
                    $asset['path'],
                    $asset['type'],
                    $asset['priority']
                ),
                false
            );
        }
    }

    public function optimize_image_loading_priority($attr, $attachment) {
        // Detect if image is likely to be LCP
        $is_hero = false;
        $classes = isset($attr['class']) ? $attr['class'] : '';
        
        if (strpos($classes, 'hero') !== false || 
            strpos($classes, 'banner') !== false || 
            is_front_page() && has_post_thumbnail()) {
            $is_hero = true;
        }

        // Optimize loading priority
        if ($is_hero) {
            $attr['fetchpriority'] = 'high';
            $attr['loading'] = 'eager';
            $attr['decoding'] = 'sync';
        } else {
            $attr['loading'] = 'lazy';
            $attr['decoding'] = 'async';
        }

        // Add size attributes to prevent CLS
        if (!isset($attr['width']) || !isset($attr['height'])) {
            $metadata = wp_get_attachment_metadata($attachment->ID);
            if ($metadata) {
                $attr['width'] = $metadata['width'];
                $attr['height'] = $metadata['height'];
            }
        }

        return $attr;
    }

    public function add_preload_critical_assets() {
        // Preload critical fonts
        echo '<link rel="preload" href="' . get_theme_file_uri('assets/fonts/primary-font.woff2') . '" as="font" type="font/woff2" crossorigin>';
        
        // Preload critical CSS
        echo '<link rel="preload" href="' . get_stylesheet_uri() . '" as="style">';
        
        // Add resource hints
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link rel="dns-prefetch" href="https://fonts.gstatic.com">';
        
        // Add critical CSS inline
        echo '<style id="critical-css">';
        echo $this->get_critical_css();
        echo '</style>';
    }

    private function get_critical_css() {
        return "
            /* Critical rendering path CSS */
            body { display: block; }
            .site-header { height: var(--header-height, 60px); }
            .hero-section { min-height: 300px; }
            img { max-width: 100%; height: auto; }
            
            /* Layout stability CSS */
            .site-content { 
                min-height: 100vh;
                width: 100%;
                margin-left: auto;
                margin-right: auto;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            /* Font display optimization */
            @font-face {
                font-family: 'Primary Font';
                font-display: swap;
                src: local('Primary Font'), url('assets/fonts/primary-font.woff2') format('woff2');
            }
            
            /* Prevent layout shifts */
            img, video, iframe {
                aspect-ratio: attr(width) / attr(height);
            }
            
            /* Content placeholder for progressive loading */
            .content-placeholder {
                background: #f0f0f0;
                animation: placeholder-pulse 1.5s ease-in-out infinite;
            }
            
            @keyframes placeholder-pulse {
                0% { opacity: 0.6; }
                50% { opacity: 0.8; }
                100% { opacity: 0.6; }
            }
        ";
    }

    public function add_layout_stability_fixes() {
        echo '<script>
            // Fix CLS from dynamic content
            document.addEventListener("DOMContentLoaded", function() {
                // Reserve space for dynamic elements
                const reserveSpace = (selector, defaultHeight) => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(el => {
                        if (!el.style.minHeight) {
                            el.style.minHeight = defaultHeight + "px";
                        }
                    });
                };
                
                // Apply to common dynamic elements
                reserveSpace(".widget-area", 400);
                reserveSpace(".dynamic-content", 200);
                
                // Fix font loading CLS
                document.documentElement.classList.add("fonts-loaded");
                
                // Fix image loading CLS
                const images = document.getElementsByTagName("img");
                for (let img of images) {
                    if (!img.hasAttribute("width") || !img.hasAttribute("height")) {
                        img.style.aspectRatio = "16/9";
                    }
                }
            });
        </script>';
    }

    public function optimize_css_delivery($html, $handle, $href, $media) {
        if (is_admin()) {
            return $html;
        }

        // Critical CSS already inlined, defer non-critical CSS
        if (!in_array($handle, ['critical-css'])) {
            $html = str_replace(
                "rel='stylesheet'",
                "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"",
                $html
            );
            // Add fallback
            $html .= "<noscript><link rel='stylesheet' href='$href'></noscript>";
        }

        return $html;
    }

    public function optimize_script_loading($tag, $handle, $src) {
        // Identify render-blocking scripts
        $critical_scripts = ['jquery', 'jquery-core'];
        
        if (in_array($handle, $critical_scripts)) {
            // Load critical scripts normally but with high priority
            return str_replace(' src', ' fetchpriority="high" src', $tag);
        }
        
        // Defer non-critical scripts
        if (strpos($tag, 'defer') === false && strpos($tag, 'async') === false) {
            $tag = str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }

    public function add_dark_mode_support() {
        echo '<style id="dark-mode-styles">
            /* Dark mode styles */
            [data-theme="dark"] {
                --bg-primary: #121212;
                --bg-secondary: #1e1e1e;
                --text-primary: #ffffff;
                --text-secondary: #e0e0e0;
                --accent-color: #2196F3;
                --border-color: #333333;
                color-scheme: dark;
            }
            
            [data-theme="light"] {
                --bg-primary: #ffffff;
                --bg-secondary: #f5f5f5;
                --text-primary: #121212;
                --text-secondary: #666666;
                --accent-color: #2196F3;
                --border-color: #e0e0e0;
                color-scheme: light;
            }
            
            /* Apply theme variables */
            body {
                background-color: var(--bg-primary);
                color: var(--text-primary);
                transition: background-color 0.3s ease, color 0.3s ease;
            }
            
            /* Dark mode specific styles */
            [data-theme="dark"] img {
                opacity: 0.9;
                filter: brightness(0.9);
            }
            
            [data-theme="dark"] .site-header {
                background-color: var(--bg-secondary);
                border-bottom: 1px solid var(--border-color);
            }
            
            [data-theme="dark"] .site-footer {
                background-color: var(--bg-secondary);
                border-top: 1px solid var(--border-color);
            }
            
            [data-theme="dark"] input,
            [data-theme="dark"] select,
            [data-theme="dark"] textarea {
                background-color: var(--bg-secondary);
                color: var(--text-primary);
                border-color: var(--border-color);
            }
            
            /* Theme toggle styles */
            .theme-toggle {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 999;
                background: var(--bg-secondary);
                border: 1px solid var(--border-color);
                border-radius: 50px;
                padding: 8px 16px;
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }
            
            .theme-toggle:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .theme-toggle svg {
                width: 20px;
                height: 20px;
                fill: var(--text-primary);
            }
            
            .theme-toggle span {
                font-size: 14px;
                font-weight: 500;
            }
            
            @media (prefers-reduced-motion: reduce) {
                body,
                .theme-toggle {
                    transition: none;
                }
            }
        </style>';
        
        // Add theme initialization script
        echo '<script>
            // Check for saved theme preference, otherwise use system preference
            const getThemePreference = () => {
                const savedTheme = localStorage.getItem("theme");
                if (savedTheme) {
                    return savedTheme;
                }
                return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            };
            
            // Apply theme immediately to prevent flash
            document.documentElement.setAttribute("data-theme", getThemePreference());
        </script>';
    }

    public function add_dark_mode_toggle() {
        echo '<div class="theme-toggle" role="button" tabindex="0" aria-label="Toggle dark mode">
            <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0 .39-.39.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96c.39-.39.39-1.03 0-1.41-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36c.39-.39.39-1.03 0-1.41-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/>
            </svg>
            <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-3.03 0-5.5-2.47-5.5-5.5 0-1.82.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/>
            </svg>
            <span class="theme-label">Dark Mode</span>
        </div>';
        
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const toggle = document.querySelector(".theme-toggle");
                const sunIcon = toggle.querySelector(".sun-icon");
                const moonIcon = toggle.querySelector(".moon-icon");
                const label = toggle.querySelector(".theme-label");
                
                // Update toggle state
                const updateToggle = (theme) => {
                    if (theme === "dark") {
                        sunIcon.style.display = "none";
                        moonIcon.style.display = "block";
                        label.textContent = "Light Mode";
                    } else {
                        sunIcon.style.display = "block";
                        moonIcon.style.display = "none";
                        label.textContent = "Dark Mode";
                    }
                };
                
                // Initialize toggle state
                updateToggle(getThemePreference());
                
                // Handle theme toggle
                toggle.addEventListener("click", function() {
                    const currentTheme = document.documentElement.getAttribute("data-theme");
                    const newTheme = currentTheme === "dark" ? "light" : "dark";
                    
                    document.documentElement.setAttribute("data-theme", newTheme);
                    localStorage.setItem("theme", newTheme);
                    updateToggle(newTheme);
                    
                    // Save preference to server
                    fetch(ajaxurl, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: new URLSearchParams({
                            action: "speedwayfast_save_theme",
                            theme: newTheme,
                            nonce: speedwayfast_theme.nonce
                        })
                    });
                });
                
                // Handle keyboard navigation
                toggle.addEventListener("keydown", function(e) {
                    if (e.key === "Enter" || e.key === " ") {
                        e.preventDefault();
                        toggle.click();
                    }
                });
                
                // Listen for system theme changes
                const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
                mediaQuery.addEventListener("change", function(e) {
                    if (!localStorage.getItem("theme")) {
                        const newTheme = e.matches ? "dark" : "light";
                        document.documentElement.setAttribute("data-theme", newTheme);
                        updateToggle(newTheme);
                    }
                });
            });
        </script>';
    }

    public function save_theme_preference() {
        check_ajax_referer('speedwayfast_theme_nonce', 'nonce');
        
        $theme = isset($_POST['theme']) ? sanitize_text_field($_POST['theme']) : 'light';
        
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'speedwayfast_theme_preference', $theme);
        }
        
        wp_send_json_success();
    }
} 