<?php
if (!defined('ABSPATH')) {
    exit;
}

class SpeedwayFast_Adaptive {
    private $options;
    private $device_type;

    public function __construct() {
        $this->options = get_option('speedwayfast_settings', array());
        $this->device_type = $this->detect_device();
        $this->init();
    }

    public function init() {
        // Adaptive image sizing with CLS prevention
        add_filter('wp_get_attachment_image_attributes', array($this, 'optimize_image_size'), 10, 3);
        
        // Enhanced device-specific optimizations
        add_action('wp_head', array($this, 'add_device_optimizations'), 1);
        
        // Advanced caching with preload
        add_action('template_redirect', array($this, 'setup_page_cache'));
        
        // Dynamic resource loading with priority
        add_filter('script_loader_tag', array($this, 'dynamic_script_loading'), 10, 3);
        add_filter('style_loader_tag', array($this, 'dynamic_style_loading'), 10, 3);
        
        // Layout shift prevention
        add_action('wp_head', array($this, 'add_cls_prevention'), 1);
        
        // Add dark mode script localization
        add_action('wp_enqueue_scripts', array($this, 'enqueue_theme_scripts'));
    }

    private function detect_device() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
            return 'tablet';
        }
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
            return 'mobile';
        }
        return 'desktop';
    }

    public function optimize_image_size($attr, $attachment, $size) {
        // Adaptive image sizing based on device with CLS prevention
        if ($this->device_type === 'mobile') {
            $attr['sizes'] = '(max-width: 480px) 100vw, 480px';
            // Ensure mobile images don't exceed viewport
            $attr['style'] = 'max-width: 100vw; width: 100%; height: auto;';
        } elseif ($this->device_type === 'tablet') {
            $attr['sizes'] = '(max-width: 768px) 100vw, 768px';
        }

        // Add modern image attributes
        $attr['decoding'] = 'async';
        $attr['loading'] = 'lazy';
        
        // Prevent CLS by setting dimensions
        $metadata = wp_get_attachment_metadata($attachment->ID);
        if ($metadata && !empty($metadata['width']) && !empty($metadata['height'])) {
            $attr['width'] = $metadata['width'];
            $attr['height'] = $metadata['height'];
            // Calculate and set aspect ratio
            $ratio = $metadata['height'] / $metadata['width'] * 100;
            $attr['style'] = isset($attr['style']) ? $attr['style'] . ';' : '';
            $attr['style'] .= "aspect-ratio: {$metadata['width']}/{$metadata['height']};";
        }

        return $attr;
    }

    public function add_device_optimizations() {
        // Enhanced viewport settings
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5">';
        echo '<meta name="theme-color" content="#2196F3">';
        
        // Device-specific meta tags
        if ($this->device_type !== 'desktop') {
            echo '<meta name="mobile-web-app-capable" content="yes">';
            echo '<meta name="apple-mobile-web-app-capable" content="yes">';
            echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
        }

        // Add performance CSS
        $this->add_performance_css();
        
        // Add device-specific optimizations
        $this->add_device_specific_css();
        
        // Add performance monitoring
        $this->add_performance_monitoring();
    }

    private function add_performance_css() {
        $css = "
            /* Performance optimizations */
            :root {
                --content-width: " . ($this->device_type === 'mobile' ? '100vw' : '1200px') . ";
                --header-height: 60px;
            }
            
            /* Layout stability */
            body {
                margin: 0;
                min-height: 100vh;
                overflow-x: hidden;
            }
            
            /* Content width constraints */
            .site-content {
                width: min(var(--content-width), 100% - 2rem);
                margin-inline: auto;
                padding-inline: 1rem;
            }
            
            /* Image optimization */
            img {
                max-width: 100%;
                height: auto;
                display: block; /* Prevent line-height spacing */
            }
            
            /* Font optimization */
            @media (prefers-reduced-data: reduce) {
                * {
                    font-family: system-ui, -apple-system, BlinkMacSystemFont, 
                               'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, 
                               Cantarell, sans-serif !important;
                }
            }
            
            /* Layout shift prevention */
            .wp-block-image {
                margin: 0;
                line-height: 0;
            }
            
            /* Content placeholders */
            .content-placeholder {
                background: #f0f0f0;
                position: relative;
                overflow: hidden;
            }
            
            .content-placeholder::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                animation: placeholder-shine 1.5s infinite;
            }
            
            @keyframes placeholder-shine {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }
        ";
        
        echo '<style id="performance-optimizations">' . $this->minify_css($css) . '</style>';
    }

    public function add_cls_prevention() {
        echo '<script>
            // Layout shift prevention
            document.addEventListener("DOMContentLoaded", function() {
                // Reserve space for dynamic content
                function reserveSpace() {
                    const dynamicElements = document.querySelectorAll("[data-dynamic-height]");
                    dynamicElements.forEach(el => {
                        const minHeight = el.getAttribute("data-dynamic-height");
                        if (minHeight && !el.style.minHeight) {
                            el.style.minHeight = minHeight + "px";
                        }
                    });
                }
                
                // Handle images
                function handleImages() {
                    const images = document.getElementsByTagName("img");
                    for (let img of images) {
                        // Set aspect ratio if dimensions are available
                        if (img.width && img.height) {
                            img.style.aspectRatio = img.width + "/" + img.height;
                        }
                        
                        // Add loading animation
                        img.addEventListener("load", function() {
                            this.classList.add("loaded");
                        });
                    }
                }
                
                // Handle fonts
                function handleFonts() {
                    if ("fonts" in document) {
                        document.fonts.ready.then(() => {
                            document.documentElement.classList.add("fonts-loaded");
                        });
                    }
                }
                
                // Initialize
                reserveSpace();
                handleImages();
                handleFonts();
                
                // Handle dynamic content
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length) {
                            reserveSpace();
                            handleImages();
                        }
                    });
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        </script>';
    }

    private function add_accessibility_css() {
        $css = "
            /* Accessibility Improvements */
            :focus {
                outline: 3px solid #2196F3 !important;
                outline-offset: 2px !important;
            }
            
            :focus:not(:focus-visible) {
                outline: none !important;
            }
            
            :focus-visible {
                outline: 3px solid #2196F3 !important;
                outline-offset: 2px !important;
            }
            
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                    scroll-behavior: auto !important;
                }
            }
            
            .screen-reader-text {
                border: 0;
                clip: rect(1px, 1px, 1px, 1px);
                clip-path: inset(50%);
                height: 1px;
                margin: -1px;
                overflow: hidden;
                padding: 0;
                position: absolute;
                width: 1px;
                word-wrap: normal !important;
            }
            
            .screen-reader-text:focus {
                background-color: #f1f1f1;
                border-radius: 3px;
                box-shadow: 0 0 2px 2px rgba(0, 0, 0, 0.6);
                clip: auto !important;
                clip-path: none;
                color: #21759b;
                display: block;
                font-size: 14px;
                font-weight: 700;
                height: auto;
                left: 5px;
                line-height: normal;
                padding: 15px 23px 14px;
                text-decoration: none;
                top: 5px;
                width: auto;
                z-index: 100000;
            }
        ";
        
        echo '<style id="accessibility-improvements">' . $this->minify_css($css) . '</style>';
    }

    private function add_device_specific_css() {
        $css = '';
        if ($this->device_type === 'mobile') {
            $css = "
                /* Mobile optimizations with accessibility */
                * { touch-action: manipulation; }
                body { 
                    font-size: 16px;
                    line-height: 1.5;
                    color: #333;
                }
                input, select, textarea { 
                    font-size: 16px;
                    padding: 12px;
                    border-radius: 4px;
                }
                button { 
                    min-height: 44px;
                    min-width: 44px;
                    padding: 12px 16px;
                    border-radius: 4px;
                }
                a { 
                    padding: 8px;
                    color: #2196F3;
                    text-decoration: underline;
                }
                a:hover, a:focus {
                    text-decoration: none;
                    background-color: rgba(33, 150, 243, 0.1);
                }
                /* High contrast mode support */
                @media (forced-colors: active) {
                    * {
                        border-color: currentColor;
                    }
                }
            ";
        } elseif ($this->device_type === 'tablet') {
            $css = "
                /* Tablet optimizations with accessibility */
                * { touch-action: manipulation; }
                body { 
                    font-size: 16px;
                    line-height: 1.5;
                }
                button { 
                    min-height: 40px;
                    min-width: 40px;
                    padding: 10px 14px;
                }
                input, select, textarea {
                    font-size: 16px;
                    padding: 10px;
                }
            ";
        }

        if ($css) {
            echo '<style id="device-optimizations">' . $this->minify_css($css) . '</style>';
        }
    }

    private function add_performance_monitoring() {
        echo "<script>
            if ('performance' in window) {
                window.addEventListener('load', function() {
                    setTimeout(function() {
                        const timing = performance.getEntriesByType('navigation')[0];
                        const performanceData = {
                            dns: timing.domainLookupEnd - timing.domainLookupStart,
                            tcp: timing.connectEnd - timing.connectStart,
                            ttfb: timing.responseStart - timing.requestStart,
                            dcl: timing.domContentLoadedEventEnd - timing.navigationStart,
                            load: timing.loadEventEnd - timing.navigationStart
                        };
                        
                        // Send performance data to admin
                        if (performanceData.load > 3000) {
                            console.warn('Page load performance issue detected');
                        }
                    }, 0);
                });
            }
        </script>";
    }

    public function dynamic_script_loading($tag, $handle, $src) {
        // Enhanced script loading with performance attributes
        $critical_scripts = array('jquery', 'wp-embed');
        
        if (in_array($handle, $critical_scripts)) {
            return str_replace(' src', ' fetchpriority="high" src', $tag);
        }

        // Module loading for modern browsers
        if (strpos($src, '.module.js') !== false) {
            return str_replace(' src', ' type="module" src', $tag);
        }

        // Enhanced script loading based on device and connection
        if ($this->device_type === 'mobile') {
            $tag = str_replace(' src', ' defer src', $tag);
            
            // Add connection-aware loading for slow connections
            if (strpos($tag, 'defer') !== false) {
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
            }
        }

        return $tag;
    }

    public function dynamic_style_loading($tag, $handle, $src) {
        // Enhanced CSS loading with performance optimization
        if ($this->device_type === 'mobile') {
            if (!in_array($handle, array('critical-css', 'wp-block-library'))) {
                $tag = str_replace(
                    ' rel="stylesheet"',
                    ' rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"',
                    $tag
                );
                
                // Add fallback for browsers that don't support preload
                $tag .= "<noscript><link rel='stylesheet' href='{$src}'></noscript>";
            }
        }

        return $tag;
    }

    public function setup_page_cache() {
        if (!is_user_logged_in() && !is_admin()) {
            // Enhanced caching strategy
            $cache_control = array(
                'public',
                'max-age=3600',
                's-maxage=3600',
                'stale-while-revalidate=86400',
                'stale-if-error=259200'
            );

            if ($this->device_type === 'mobile') {
                $cache_control[] = 'vary-by-device';
            }

            header('Cache-Control: ' . implode(', ', $cache_control));
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('X-Frame-Options: SAMEORIGIN');
            
            // Add security headers
            header('Referrer-Policy: strict-origin-when-cross-origin');
            if (!empty($_SERVER['HTTPS'])) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            }
        }
    }

    public function enqueue_theme_scripts() {
        wp_localize_script('jquery', 'speedwayfast_theme', array(
            'nonce' => wp_create_nonce('speedwayfast_theme_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }

    private function minify_css($css) {
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = str_replace(array("\r\n","\r","\n","\t",'  ','    ','    '), '', $css);
        return $css;
    }
} 
} 