<?php
/**
 * Frontend principal - Radio Miraflores PHP
 * Optimizado para bajo consumo
 */

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/cache.php';
require_once 'includes/functions.php';

// Obtener todas las secciones con caché
$hero = getSection('hero');
$videoRanking = getSection('video_ranking');
$videoRankingItems = $videoRanking ? getSectionItems($videoRanking['id']) : [];
$ranking = getSection('ranking');
$rankingItems = $ranking ? getSectionItems($ranking['id']) : [];
$nosotros = getSection('nosotros');
$nosotrosItems = $nosotros ? getSectionItems($nosotros['id']) : [];
$noticias = getSection('noticias');
$noticiasItems = $noticias ? getSectionItems($noticias['id']) : [];
$testimonios = getSection('testimonios');
$testimoniosItems = $testimonios ? getSectionItems($testimonios['id']) : [];
$redes = getSection('redes_sociales');
$redesItems = $redes ? getSectionItems($redes['id']) : [];
$info = getSection('info');
$footer = getSection('footer');

// Headers de caché del navegador
header('Cache-Control: public, max-age=300');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');

// Headers de seguridad
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(SITE_NAME) ?></title>
    <meta name="description" content="La estación de rock que mueve tu mundo">
    <meta property="og:title" content="<?= htmlspecialchars(SITE_NAME) ?>">
    <meta property="og:description" content="La estación de rock que mueve tu mundo">
    <meta property="og:type" content="website">
    
    <link rel="preconnect" href="https://www.youtube.com" crossorigin>
    <link rel="preconnect" href="https://i.ytimg.com" crossorigin>
    <link rel="dns-prefetch" href="https://www.youtube.com">
    <link rel="dns-prefetch" href="https://i.ytimg.com">
    
    <style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen,Ubuntu,sans-serif;line-height:1.6;color:#333;background:#fff}.container{max-width:1200px;margin:0 auto;padding:0 20px}.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;transition:all .3s}.navbar.scrolled{background:rgba(255,255,255,.95);backdrop-filter:blur(10px);box-shadow:0 2px 20px rgba(0,0,0,.1)}.navbar-inner{display:flex;align-items:center;justify-content:space-between;height:70px;padding:0 20px;max-width:1200px;margin:0 auto}.navbar-brand{display:flex;align-items:center;gap:12px;text-decoration:none}.navbar-brand-text{font-weight:700;font-size:14px;color:#fff;transition:color .3s}.navbar.scrolled .navbar-brand-text{color:#8B1A2B}.navbar-brand-sub{font-size:12px;color:rgba(255,255,255,.8)}.navbar.scrolled .navbar-brand-sub{color:#A63346}.navbar-links{display:flex;align-items:center;gap:4px}.navbar-links a{padding:8px 12px;border-radius:8px;font-size:14px;font-weight:500;color:rgba(255,255,255,.9);text-decoration:none;transition:all .2s}.navbar.scrolled .navbar-links a{color:#374151}.navbar-links a:hover{background:rgba(255,255,255,.1);color:#fff}.navbar.scrolled .navbar-links a:hover{color:#8B1A2B;background:rgba(139,26,43,.05)}.navbar-cta{padding:8px 20px;background:#8B1A2B;color:#fff!important;border-radius:50px;font-weight:600;box-shadow:0 4px 15px rgba(139,26,43,.25)}.navbar-cta:hover{background:#6B0F1E!important}.mobile-menu-btn{display:none;background:none;border:none;color:#fff;font-size:24px;cursor:pointer}.navbar.scrolled .mobile-menu-btn{color:#8B1A2B}.mobile-menu{display:none;background:rgba(255,255,255,.95);backdrop-filter:blur(10px);border-top:1px solid #e5e7eb;padding:12px 16px}.mobile-menu a{display:block;padding:10px 16px;border-radius:8px;color:#374151;text-decoration:none;font-weight:500;font-size:14px}.mobile-menu a:hover{color:#8B1A2B;background:rgba(139,26,43,.05)}@media(max-width:768px){.navbar-links{display:none}.mobile-menu-btn{display:block}.mobile-menu.open{display:block}}.hero{min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:80px 20px 20px}.hero h1{font-size:clamp(2rem,5vw,4rem);font-weight:900;margin-bottom:1rem}.hero h1 span{color:#ffd700}.hero p{font-size:clamp(1rem,2.5vw,1.5rem);margin-bottom:2rem;opacity:.9}.hero-buttons{display:flex;gap:15px;justify-content:center;flex-wrap:wrap}.btn{display:inline-block;padding:12px 30px;background:#fff;color:#667eea;text-decoration:none;border-radius:50px;font-weight:700;transition:transform .2s}.btn:hover{transform:translateY(-2px)}.btn-secondary{background:transparent;border:2px solid #fff;color:#fff}.video-ranking{background:linear-gradient(180deg,#fff7f8 0%,#fff 42%,#fff5eb 100%);padding:80px 0;position:relative;overflow:hidden}.video-ranking-header{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:40px;flex-wrap:wrap;gap:20px}.video-ranking-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(139,26,43,.1);padding:6px 16px;border-radius:50px;font-size:14px;font-weight:600;color:#8B1A2B;margin-bottom:16px}.video-ranking h2{font-size:clamp(1.8rem,4vw,3rem);font-weight:800;color:#111827;margin-bottom:16px}.video-ranking-desc{font-size:18px;color:#6b7280;max-width:600px}.video-grid{display:grid;grid-template-columns:1.8fr 1fr;gap:24px}@media(max-width:1024px){.video-grid{grid-template-columns:1fr}}.video-main{background:rgba(255,255,255,.9);border-radius:28px;border:1px solid rgba(139,26,43,.1);overflow:hidden;box-shadow:0 30px 80px rgba(139,26,43,.12)}.video-main-header{padding:20px 24px;border-bottom:1px solid rgba(139,26,43,.1)}.video-main-label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.22em;color:rgba(139,26,43,.7)}.video-main-title{font-size:24px;font-weight:700;color:#111827;margin-top:8px}.video-main-artist{font-size:14px;color:#6b7280}.video-main-player{padding:16px}.video-main-player .aspect-video{position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:16px;background:#000}.video-main-player iframe{position:absolute;top:0;left:0;width:100%;height:100%;border:0}.video-playlist{background:rgba(255,255,255,.9);border-radius:28px;border:1px solid rgba(139,26,43,.1);overflow:hidden;max-height:720px}.video-playlist-header{padding:20px 24px;border-bottom:1px solid rgba(139,26,43,.1);display:flex;justify-content:space-between;align-items:center}.video-playlist-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.22em;color:rgba(139,26,43,.7)}.video-playlist-count{font-size:18px;font-weight:700;color:#111827;margin-top:4px}.video-playlist-badge{background:#8B1A2B;color:#fff;padding:8px 12px;border-radius:50px;font-size:14px;font-weight:700}.video-playlist-items{padding:12px;overflow-y:auto;max-height:600px}.video-item{display:flex;align-items:center;gap:12px;padding:12px;border-radius:16px;border:1px solid #f3f4f6;background:#fff;margin-bottom:12px;cursor:pointer;transition:all .2s;text-decoration:none;color:inherit}.video-item:hover,.video-item.active{border-color:rgba(139,26,43,.4);background:rgba(139,26,43,.03)}.video-item-thumb{position:relative;width:128px;height:80px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#f3f4f6}.video-item-thumb img{width:100%;height:100%;object-fit:cover}.video-item-thumb::after{content:'\25B6';position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.2);color:#fff;font-size:20px}.video-item.active .video-item-thumb::after{background:rgba(139,26,43,.35)}.video-item-info{flex:1;min-width:0}.video-item-position{display:inline-flex;align-items:center;gap:8px;background:#f3f4f6;padding:4px 10px;border-radius:50px;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px}.video-item.active .video-item-position{color:#8B1A2B}.video-item-title{font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.video-item-artist{font-size:14px;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:4px}section{padding:80px 0}.section-title{text-align:center;margin-bottom:50px}.section-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(139,26,43,.1);padding:6px 16px;border-radius:50px;font-size:14px;font-weight:600;color:#8B1A2B;margin-bottom:16px}.section-title h2{font-size:clamp(1.8rem,4vw,3rem);font-weight:800;margin-bottom:10px;color:#111827}.section-title p{color:#6b7280;font-size:18px;max-width:600px;margin:0 auto}#ranking{background:#f8f9fa}.ranking-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:30px;margin-top:40px}.ranking-item{background:#fff;border-radius:15px;padding:25px;box-shadow:0 5px 15px rgba(0,0,0,.1);transition:transform .3s}.ranking-item:hover{transform:translateY(-5px)}.ranking-position{font-size:2rem;font-weight:900;color:#8B1A2B;margin-bottom:10px}.ranking-song{font-size:1.3rem;font-weight:700;margin-bottom:5px}.ranking-artist{color:#666;margin-bottom:5px}.ranking-album{color:#999;font-size:.9rem}.ranking-trend{display:inline-block;margin-left:8px;font-size:.8rem}.ranking-trend.up{color:#10b981}.ranking-trend.down{color:#ef4444}.ranking-trend.same{color:#6b7280}#nosotros{background:linear-gradient(to bottom,#FFF9F2,#fff,#FFF0F2)}.nosotros-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px;margin-top:40px}.nosotros-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.08);transition:all .3s;border:1px solid #f3f4f6;position:relative}.nosotros-card:hover{transform:translateY(-8px);box-shadow:0 20px 50px rgba(0,0,0,.15)}.nosotros-card-image{height:200px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative;overflow:hidden}.nosotros-card-image::after{content:attr(data-year);position:absolute;bottom:12px;left:50%;transform:translateX(-50%);background:#8B1A2B;color:#fff;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:700}.nosotros-card-content{padding:20px}.nosotros-card-icon{display:flex;align-items:center;gap:8px;margin-bottom:12px}.nosotros-card-icon span{font-size:20px}.nosotros-card-title{font-weight:700;font-size:18px;color:#111827}.nosotros-card-desc{color:#6b7280;font-size:14px;line-height:1.6;margin-top:8px}.nosotros-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-top:60px}.nosotros-stat{text-align:center;padding:20px;background:rgba(255,255,255,.8);backdrop-filter:blur(10px);border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,.08);border:1px solid #fff}.nosotros-stat-value{font-size:32px;font-weight:800;color:#8B1A2B}.nosotros-stat-label{color:#6b7280;font-size:14px;margin-top:4px;font-weight:600}#noticias{background:#f8f9fa}.noticias-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:30px;margin-top:40px}.noticia{background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,.1);transition:transform .3s}.noticia:hover{transform:translateY(-5px)}.noticia-img{width:100%;height:200px;object-fit:cover}.noticia-content{padding:25px}.noticia h3{font-size:1.3rem;margin-bottom:10px}.noticia-meta{color:#999;font-size:.85rem;margin-bottom:15px}.noticia-excerpt{color:#666;line-height:1.6}.noticia-embed{margin-top:15px;border-radius:12px;overflow:hidden}.noticia-embed iframe{width:100%;border:0}.testimonios-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px;margin-top:40px}.testimonio{background:#f8f9fa;border-radius:15px;padding:30px;position:relative}.testimonio::before{content:'\201C';position:absolute;top:10px;left:20px;font-size:4rem;color:#8B1A2B;opacity:.2}.testimonio-quote{font-style:italic;margin:20px 0;line-height:1.8}.testimonio-author{display:flex;align-items:center;gap:15px;margin-top:20px}.testimonio-avatar{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}.testimonio-info h4{margin:0;font-size:1rem}.testimonio-info p{margin:0;color:#666;font-size:.9rem}.testimonio-rating{color:#fbbf24;margin-top:8px}#redes{background:linear-gradient(to bottom,#fff,#FFF5F6,#FFF9F2)}.redes-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin-top:40px}.red-card{border-radius:16px;overflow:hidden;border:2px solid;transition:all .3s}.red-card:hover{transform:translateY(-8px);box-shadow:0 20px 50px rgba(0,0,0,.15)}.red-card-header{padding:20px;color:#fff;position:relative;overflow:hidden}.red-card-header::before{content:'';position:absolute;top:-50%;right:-20%;width:200px;height:200px;background:rgba(255,255,255,.1);border-radius:50%}.red-card-icon{width:48px;height:48px;background:rgba(255,255,255,.2);backdrop-filter:blur(10px);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:12px}.red-card-name{font-weight:800;font-size:18px}.red-card-username{opacity:.7;font-size:14px}.red-card-body{padding:20px;background:#fff}.red-card-followers{font-size:12px;color:#9ca3af;font-weight:600;margin-bottom:4px}.red-card-count{font-weight:700;color:#111827;font-size:14px}.red-card-link{display:block;text-align:center;padding:12px;margin-top:16px;border-radius:12px;background:#f9fafb;border:1px solid #e5e7eb;color:#374151;text-decoration:none;font-weight:600;font-size:12px;transition:all .2s}.red-card-link:hover{background:#8B1A2B;color:#fff;border-color:#8B1A2B}.red-youtube{border-color:#fecaca;background:#fef2f2}.red-youtube .red-card-header{background:linear-gradient(to right,#dc2626,#b91c1c)}.red-instagram{border-color:#e9d5ff;background:linear-gradient(to bottom right,#faf5ff,#fdf2f8)}.red-instagram .red-card-header{background:linear-gradient(to right,#9333ea,#ec4899,#f97316)}.red-twitter{border-color:#e5e7eb;background:#f9fafb}.red-twitter .red-card-header{background:linear-gradient(to right,#1f2937,#111827)}.red-facebook{border-color:#bfdbfe;background:#eff6ff}.red-facebook .red-card-header{background:linear-gradient(to right,#1877F2,#0D65D9)}.red-tiktok{border-color:#e5e7eb;background:#f9fafb}.red-tiktok .red-card-header{background:linear-gradient(to right,#000,#1f2937)}#contacto{background:linear-gradient(to bottom,#FFF9F2,#fff,#FFF0F2)}.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-top:40px}@media(max-width:768px){.info-grid{grid-template-columns:1fr}}.info-cards{display:flex;flex-direction:column;gap:16px}.info-card{display:flex;align-items:center;gap:16px;padding:16px;background:rgba(255,255,255,.95);backdrop-filter:blur(10px);border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,.08);border:1px solid #f3f4f6;transition:all .3s;text-decoration:none;color:inherit}.info-card:hover{transform:translateX(6px);border-color:rgba(139,26,43,.2);box-shadow:0 8px 25px rgba(0,0,0,.12)}.info-card-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;flex-shrink:0}.info-card-icon.email{background:linear-gradient(135deg,#8B1A2B,#A63346)}.info-card-icon.phone{background:linear-gradient(135deg,#F5A623,#FFD166)}.info-card-icon.address{background:linear-gradient(135deg,#10b981,#059669)}.info-card-icon.schedule{background:linear-gradient(135deg,#3b82f6,#2563eb)}.info-card-label{font-size:12px;color:#9ca3af;font-weight:600}.info-card-value{font-weight:700;color:#111827;font-size:14px}.info-newsletter{padding:24px;background:linear-gradient(135deg,#8B1A2B,#A63346);border-radius:12px;box-shadow:0 10px 30px rgba(139,26,43,.2);margin-top:24px}.info-newsletter h4{color:#fff;font-weight:800;font-size:20px;margin-bottom:8px}.info-newsletter p{color:rgba(255,255,255,.8);font-size:14px;margin-bottom:16px}.info-newsletter-form{display:flex;gap:8px}.info-newsletter-form input{flex:1;padding:10px 16px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:8px;color:#fff;font-size:14px}.info-newsletter-form input::placeholder{color:rgba(255,255,255,.4)}.info-newsletter-form button{padding:10px 20px;background:#F5A623;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;transition:all .2s}.info-newsletter-form button:hover{background:#FFD166;color:#8B1A2B}.info-map{border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.1);border:1px solid #f3f4f6;min-height:400px;background:linear-gradient(135deg,#f3f4f6,#e5e7eb);display:flex;align-items:center;justify-content:center}.info-map-placeholder{text-align:center;color:#9ca3af}.info-map-placeholder span{font-size:48px;display:block;margin-bottom:8px}.footer{position:relative;overflow:hidden;background:#1a1a1a;color:#fff;padding:60px 0 20px}.footer::before{content:'';position:absolute;inset:0;background:linear-gradient(to bottom,rgba(139,26,43,.95),rgba(107,15,30,.95),rgba(58,8,18,.98))}.footer-content{position:relative;z-index:1}.footer-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:40px;margin-bottom:40px}.footer-brand{display:flex;align-items:center;gap:12px;margin-bottom:16px}.footer-brand-name{font-weight:700;font-size:18px}.footer-brand-sub{color:rgba(255,255,255,.6);font-size:12px}.footer-desc{color:rgba(255,255,255,.6);font-size:14px;line-height:1.6;margin-bottom:16px}.footer-live{display:flex;align-items:center;gap:8px;color:#F5A623;font-size:14px;font-weight:600}.footer-title{font-weight:700;font-size:14px;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px}.footer-links{list-style:none}.footer-links li{margin-bottom:10px}.footer-links a{color:rgba(255,255,255,.6);font-size:14px;text-decoration:none;transition:color .2s;display:flex;align-items:center;gap:8px}.footer-links a:hover{color:#FFD166}.footer-links a::before{content:'';width:4px;height:4px;background:#8B1A2B;border-radius:50%}.footer-contact p{color:rgba(255,255,255,.6);font-size:14px;margin-bottom:12px}.footer-social{display:flex;flex-direction:column;gap:12px}.footer-social a{display:flex;align-items:center;gap:12px;color:rgba(255,255,255,.6);font-size:14px;text-decoration:none;transition:color .2s}.footer-social a:hover{color:#FFD166}.footer-social-icon{width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;transition:background .2s}.footer-social a:hover .footer-social-icon{background:rgba(255,255,255,.2)}.footer-bottom{border-top:1px solid rgba(255,255,255,.1);padding-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px}.footer-copyright{color:rgba(255,255,255,.4);font-size:14px}.footer-made{color:rgba(255,255,255,.4);font-size:14px;display:flex;align-items:center;gap:4px}.footer-made span{color:#8B1A2B}.scroll-top{position:fixed;bottom:24px;right:24px;width:48px;height:48px;background:#8B1A2B;color:#fff;border:none;border-radius:50%;font-size:20px;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,.2);transition:all .3s;z-index:100}.scroll-top:hover{background:#6B0F1E;transform:scale(1.1)}@media(max-width:768px){.ranking-grid,.testimonios-grid,.noticias-grid,.redes-grid,.nosotros-cards{grid-template-columns:1fr}section{padding:50px 0}.video-ranking-header{flex-direction:column;align-items:flex-start}}
</style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="navbar-inner">
            <a href="#inicio" class="navbar-brand">
                <div>
                    <div class="navbar-brand-text">Radio Miraflores</div>
                    <div class="navbar-brand-sub">Televisión</div>
                </div>
            </a>
            <div class="navbar-links">
                <a href="#inicio">Inicio</a>
                <a href="#video-ranking">Video Ranking</a>
                <a href="#ranking">Ranking</a>
                <a href="#nosotros">Nosotros</a>
                <a href="#noticias">Noticias</a>
                <a href="#testimonios">Testimonios</a>
                <a href="#redes">Redes</a>
                <a href="#contacto">Contacto</a>
                <a href="#contacto" class="navbar-cta">¡Escúchanos!</a>
            </div>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">☰</button>
        </div>
        <div class="mobile-menu" id="mobileMenu">
            <a href="#inicio" onclick="closeMobileMenu()">Inicio</a>
            <a href="#video-ranking" onclick="closeMobileMenu()">Video Ranking</a>
            <a href="#ranking" onclick="closeMobileMenu()">Ranking</a>
            <a href="#nosotros" onclick="closeMobileMenu()">Nosotros</a>
            <a href="#noticias" onclick="closeMobileMenu()">Noticias</a>
            <a href="#testimonios" onclick="closeMobileMenu()">Testimonios</a>
            <a href="#redes" onclick="closeMobileMenu()">Redes</a>
            <a href="#contacto" onclick="closeMobileMenu()">Contacto</a>
            <a href="#contacto" class="navbar-cta" style="text-align:center;margin-top:8px" onclick="closeMobileMenu()">¡Escúchanos!</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <?php if ($hero): ?>
    <section id="inicio" class="hero">
        <div class="container">
            <h1><?= htmlspecialchars($hero['title'] ?? 'Radio') ?> 
                <span><?= htmlspecialchars($hero['title_highlight'] ?? 'Miraflores') ?></span>
            </h1>
            <p><?= htmlspecialchars($hero['subtitle'] ?? 'La estación de rock que mueve tu mundo') ?></p>
            <div class="hero-buttons">
                <?php if (!empty($hero['cta_primary_text'])): ?>
                    <a href="<?= htmlspecialchars($hero['cta_primary_link'] ?? '#ranking') ?>" class="btn">
                        <?= htmlspecialchars($hero['cta_primary_text']) ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($hero['cta_secondary_text'])): ?>
                    <a href="<?= htmlspecialchars($hero['cta_secondary_link'] ?? '#noticias') ?>" class="btn btn-secondary">
                        <?= htmlspecialchars($hero['cta_secondary_text']) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Video Ranking Section -->
    <?php if ($videoRanking && $videoRankingItems): 
        $activeVideos = array_filter($videoRankingItems, fn($i) => $i['activo']);
        $firstVideo = reset($activeVideos);
        $firstContent = $firstVideo ? json_decode($firstVideo['contenido'], true) : null;
    ?>
    <section id="video-ranking" class="video-ranking">
        <div class="container">
            <div class="video-ranking-header">
                <div>
                    <div class="video-ranking-badge">📺 <?= htmlspecialchars($videoRanking['subtitle'] ?? 'Ranking en video') ?></div>
                    <h2><?= htmlspecialchars($videoRanking['title'] ?? 'Video Ranking') ?></h2>
                    <p class="video-ranking-desc"><?= htmlspecialchars($videoRanking['description'] ?? '') ?></p>
                </div>
                <?php if (!empty($videoRanking['cta_text'])): ?>
                <a href="<?= htmlspecialchars($videoRanking['cta_link'] ?? '#') ?>" target="_blank" class="btn" style="background:#8B1A2B;color:#fff">
                    <?= htmlspecialchars($videoRanking['cta_text']) ?> →
                </a>
                <?php endif; ?>
            </div>
            <div class="video-grid">
                <div class="video-main">
                    <div class="video-main-header">
                        <div class="video-main-label">En reproducción</div>
                        <div class="video-main-title"><?= htmlspecialchars($firstContent['title'] ?? 'Video destacado') ?></div>
                        <div class="video-main-artist"><?= htmlspecialchars($firstContent['artist'] ?? 'Radio Miraflores TV') ?></div>
                    </div>
                    <div class="video-main-player">
                        <div class="aspect-video">
                            <?php if ($firstContent && !empty($firstContent['video_id'])): ?>
                            <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($firstContent['video_id']) ?>?autoplay=1&mute=1&controls=1&rel=0&playlist=<?= implode(',', array_map(fn($v) => json_decode($v['contenido'], true)['video_id'] ?? '', array_slice($activeVideos, 0, 10))) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="video-playlist">
                    <div class="video-playlist-header">
                        <div>
                            <div class="video-playlist-title">Playlist activa</div>
                            <div class="video-playlist-count"><?= count($activeVideos) ?> videos en cola</div>
                        </div>
                        <div class="video-playlist-badge">24/7</div>
                    </div>
                    <div class="video-playlist-items">
                        <?php foreach ($activeVideos as $index => $item): 
                            $content = json_decode($item['contenido'], true);
                            $isActive = $index === 0;
                        ?>
                        <a href="<?= htmlspecialchars($content['youtube_url'] ?? '#') ?>" target="_blank" class="video-item <?= $isActive ? 'active' : '' ?>">
                            <div class="video-item-thumb">
                                <img src="https://i.ytimg.com/vi/<?= htmlspecialchars($content['video_id'] ?? '') ?>/hqdefault.jpg" alt="<?= htmlspecialchars($content['title'] ?? '') ?>" loading="lazy">
                            </div>
                            <div class="video-item-info">
                                <div class="video-item-position">#<?= $index + 1 ?> <?= $isActive ? 'Ahora' : '' ?></div>
                                <div class="video-item-title"><?= htmlspecialchars($content['title'] ?? '') ?></div>
                                <div class="video-item-artist"><?= htmlspecialchars($content['artist'] ?? '') ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Ranking Section -->
    <?php if ($ranking && $rankingItems): ?>
    <section id="ranking">
        <div class="container">
            <div class="section-title">
                <div class="section-badge">🏆 Ranking Musical</div>
                <h2><?= htmlspecialchars($ranking['title'] ?? 'Ranking Musical') ?></h2>
                <p>Las canciones más escuchadas de la semana</p>
            </div>
            <div class="ranking-grid">
                <?php foreach (array_slice($rankingItems, 0, 4) as $item): 
                    $content = json_decode($item['contenido'], true);
                    $trend = $content['trend'] ?? 'same';
                    $trendIcon = $trend === 'up' ? '↑' : ($trend === 'down' ? '↓' : '→');
                ?>
                <div class="ranking-item">
                    <div class="ranking-position">#<?= intval($content['position'] ?? $item['orden']) ?></div>
                    <div class="ranking-song"><?= htmlspecialchars($content['song'] ?? $item['titulo']) ?></div>
                    <div class="ranking-artist"><?= htmlspecialchars($content['artist'] ?? '') ?></div>
                    <div class="ranking-album">
                        <?= htmlspecialchars($content['album'] ?? '') ?>
                        <span class="ranking-trend <?= $trend ?>"><?= $trendIcon ?> <?= intval($content['weeks'] ?? 0) ?> semanas</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Nosotros Section -->
    <?php if ($nosotros): ?>
    <section id="nosotros">
        <div class="container">
            <div class="section-title">
                <div class="section-badge">❤️ <?= htmlspecialchars($nosotros['subtitle'] ?? 'Nuestra Historia') ?></div>
                <h2><?= htmlspecialchars($nosotros['title'] ?? 'Nosotros') ?></h2>
                <p><?= htmlspecialchars($nosotros['description'] ?? '') ?></p>
            </div>
            <?php if ($nosotrosItems): ?>
            <div class="nosotros-cards">
                <?php 
                $icons = ['📻', '🎤', '🎧', '❤️'];
                foreach ($nosotrosItems as $index => $item): 
                    $content = json_decode($item['contenido'], true);
                ?>
                <div class="nosotros-card">
                    <div class="nosotros-card-image" data-year="<?= htmlspecialchars($content['year'] ?? '') ?>"></div>
                    <div class="nosotros-card-content">
                        <div class="nosotros-card-icon">
                            <span><?= $icons[$index % count($icons)] ?></span>
                            <div class="nosotros-card-title"><?= htmlspecialchars($content['title'] ?? '') ?></div>
                        </div>
                        <p class="nosotros-card-desc"><?= htmlspecialchars($content['description'] ?? '') ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="nosotros-stats">
                <?php for ($i = 1; $i <= 4; $i++): 
                    $value = $nosotros["stat{$i}_value"] ?? '';
                    $label = $nosotros["stat{$i}_label"] ?? '';
                    if ($value && $label):
                ?>
                <div class="nosotros-stat">
                    <div class="nosotros-stat-value"><?= htmlspecialchars($value) ?></div>
                    <div class="nosotros-stat-label"><?= htmlspecialchars($label) ?></div>
                </div>
                <?php endif; endfor; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Noticias Section -->
    <?php if ($noticias && $noticiasItems): ?>
    <section id="noticias">
        <div class="container">
            <div class="section-title">
                <div class="section-badge">📰 <?= htmlspecialchars($noticias['subtitle'] ?? 'Noticias') ?></div>
                <h2><?= htmlspecialchars($noticias['title'] ?? 'Noticias') ?></h2>
                <p><?= htmlspecialchars($noticias['description'] ?? '') ?></p>
            </div>
            <div class="noticias-grid">
                <?php foreach (array_slice($noticiasItems, 0, $noticias['max_visible'] ?? 3) as $item): 
                    $content = json_decode($item['contenido'], true);
                ?>
                <article class="noticia">
                    <?php if ($item['imagen_url']): ?>
                        <img src="<?= htmlspecialchars($item['imagen_url']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="noticia-img" loading="lazy">
                    <?php endif; ?>
                    <div class="noticia-content">
                        <h3><?= htmlspecialchars($content['title'] ?? $item['titulo']) ?></h3>
                        <div class="noticia-meta">
                            Por <?= htmlspecialchars($content['author'] ?? 'Radio Miraflores') ?> • 
                            <?= timeAgo($item['created_at']) ?>
                        </div>
                        <?php if (!empty($content['facebook_embed_url']) && isAllowedEmbedOrigin($content['facebook_embed_url'])): ?>
                        <div class="noticia-embed">
                            <iframe src="<?= htmlspecialchars($content['facebook_embed_url']) ?>" width="500" height="400" loading="lazy" scrolling="no" frameborder="0" allowfullscreen></iframe>
                        </div>
                        <?php else: ?>
                        <p class="noticia-excerpt"><?= htmlspecialchars(substr($content['excerpt'] ?? '', 0, 150)) ?>...</p>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Testimonios Section -->
    <?php if ($testimonios && $testimoniosItems): ?>
    <section id="testimonios">
        <div class="container">
            <div class="section-title">
                <div class="section-badge">💬 <?= htmlspecialchars($testimonios['subtitle'] ?? 'Testimonios') ?></div>
                <h2><?= htmlspecialchars($testimonios['title'] ?? 'Testimonios') ?></h2>
                <p><?= htmlspecialchars($testimonios['description'] ?? 'Lo que dicen nuestros oyentes') ?></p>
            </div>
            <div class="testimonios-grid">
                <?php foreach (array_slice($testimoniosItems, 0, 4) as $item): 
                    $content = json_decode($item['contenido'], true);
                    $rating = intval($content['rating'] ?? 5);
                ?>
                <div class="testimonio">
                    <div class="testimonio-quote"><?= htmlspecialchars($content['quote'] ?? '') ?></div>
                    <div class="testimonio-rating"><?= str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) ?></div>
                    <div class="testimonio-author">
                        <div class="testimonio-avatar">
                            <?= strtoupper(substr($content['name'] ?? 'U', 0, 2)) ?>
                        </div>
                        <div class="testimonio-info">
                            <h4><?= htmlspecialchars($content['name'] ?? '') ?></h4>
                            <p><?= htmlspecialchars($content['role'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Redes Sociales Section -->
    <?php if ($redes && $redesItems): 
        $activeRedes = array_filter($redesItems, fn($r) => $r['activo']);
        $platformIcons = [
            'youtube' => '▶',
            'instagram' => '📷',
            'twitter' => '𝕏',
            'facebook' => 'f',
            'tiktok' => '♪'
        ];
        $platformLabels = [
            'youtube' => 'YouTube',
            'instagram' => 'Instagram',
            'twitter' => 'X (Twitter)',
            'facebook' => 'Facebook',
            'tiktok' => 'TikTok'
        ];
    ?>
    <section id="redes">
        <div class="container">
            <div class="section-title">
                <div class="section-badge">🌐 <?= htmlspecialchars($redes['subtitle'] ?? 'Síguenos') ?></div>
                <h2><?= htmlspecialchars($redes['title'] ?? 'Redes Sociales') ?></h2>
                <p><?= htmlspecialchars($redes['description'] ?? 'Conecta con nosotros en todas las plataformas') ?></p>
            </div>
            <div class="redes-grid">
                <?php foreach ($activeRedes as $item): 
                    $content = json_decode($item['contenido'], true);
                    $platform = $content['platform'] ?? 'youtube';
                    $icon = $platformIcons[$platform] ?? '🔗';
                    $label = $platformLabels[$platform] ?? $platform;
                ?>
                <div class="red-card red-<?= $platform ?>">
                    <div class="red-card-header">
                        <div class="red-card-icon"><?= $icon ?></div>
                        <div class="red-card-name"><?= $label ?></div>
                        <div class="red-card-username"><?= htmlspecialchars($content['username'] ?? '') ?></div>
                    </div>
                    <div class="red-card-body">
                        <div class="red-card-followers">Seguidores</div>
                        <div class="red-card-count"><?= htmlspecialchars($content['followers'] ?? '') ?></div>
                        <a href="<?= htmlspecialchars($content['url'] ?? '#') ?>" target="_blank" class="red-card-link">
                            Visitar <?= $label ?> →
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Info/Contacto Section -->
    <?php if ($info): ?>
    <section id="contacto">
        <div class="container">
            <div class="section-title">
                <div class="section-badge">📍 <?= htmlspecialchars($info['subtitle'] ?? 'Contáctanos') ?></div>
                <h2><?= htmlspecialchars($info['title'] ?? 'Información') ?></h2>
                <p><?= htmlspecialchars($info['description'] ?? 'Estamos aquí para escucharte') ?></p>
            </div>
            <div class="info-grid">
                <div>
                    <div class="info-cards">
                        <?php if (!empty($info['email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($info['email']) ?>" class="info-card">
                            <div class="info-card-icon email">✉</div>
                            <div>
                                <div class="info-card-label">Email</div>
                                <div class="info-card-value"><?= htmlspecialchars($info['email']) ?></div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($info['phone'])): ?>
                        <a href="tel:<?= htmlspecialchars($info['phone']) ?>" class="info-card">
                            <div class="info-card-icon phone">📞</div>
                            <div>
                                <div class="info-card-label">Teléfono</div>
                                <div class="info-card-value"><?= htmlspecialchars($info['phone']) ?></div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($info['address'])): ?>
                        <div class="info-card">
                            <div class="info-card-icon address">📍</div>
                            <div>
                                <div class="info-card-label">Dirección</div>
                                <div class="info-card-value"><?= htmlspecialchars($info['address']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($info['schedule'])): ?>
                        <div class="info-card">
                            <div class="info-card-icon schedule">🕐</div>
                            <div>
                                <div class="info-card-label">Horario</div>
                                <div class="info-card-value">
                                    <?= htmlspecialchars($info['schedule']) ?>
                                    <?php if (!empty($info['schedule_weekend'])): ?>
                                    | <?= htmlspecialchars($info['schedule_weekend']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="info-newsletter">
                        <h4>¡Suscríbete!</h4>
                        <p>Recibe las últimas noticias y el ranking semanal directamente en tu email</p>
                        <div class="info-newsletter-form">
                            <input type="email" placeholder="Tu correo electrónico">
                            <button type="submit">→</button>
                        </div>
                    </div>
                </div>
                <div class="info-map">
                    <div class="info-map-placeholder">
                        <span>📍</span>
                        <p>Mapa no disponible</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">
                        <div>
                            <div class="footer-brand-name">Radio Miraflores</div>
                            <div class="footer-brand-sub">Televisión</div>
                        </div>
                    </div>
                    <p class="footer-desc"><?= htmlspecialchars($footer['description'] ?? 'La estación de rock que mueve tu mundo desde 1985.') ?></p>
                    <div class="footer-live">📻 ¡Siempre en vivo!</div>
                </div>
                <div>
                    <div class="footer-title">Enlaces</div>
                    <ul class="footer-links">
                        <li><a href="#inicio">Inicio</a></li>
                        <li><a href="#ranking">Ranking</a></li>
                        <li><a href="#nosotros">Nosotros</a></li>
                        <li><a href="#noticias">Noticias</a></li>
                        <li><a href="#testimonios">Testimonios</a></li>
                        <li><a href="#contacto">Contacto</a></li>
                    </ul>
                </div>
                <div>
                    <div class="footer-title">Contacto</div>
                    <div class="footer-contact">
                        <?php if (!empty($info['email'])): ?><p><?= htmlspecialchars($info['email']) ?></p><?php endif; ?>
                        <?php if (!empty($info['phone'])): ?><p><?= htmlspecialchars($info['phone']) ?></p><?php endif; ?>
                        <?php if (!empty($info['address'])): ?><p><?= htmlspecialchars($info['address']) ?></p><?php endif; ?>
                    </div>
                </div>
                <?php if ($redesItems): ?>
                <div>
                    <div class="footer-title">Síguenos</div>
                    <div class="footer-social">
                        <?php foreach (array_filter($redesItems, fn($r) => $r['activo']) as $item): 
                            $content = json_decode($item['contenido'], true);
                            $platform = $content['platform'] ?? '';
                            $label = $platformLabels[$platform] ?? $platform;
                            $icon = $platformIcons[$platform] ?? '🔗';
                        ?>
                        <a href="<?= htmlspecialchars($content['url'] ?? '#') ?>" target="_blank">
                            <div class="footer-social-icon"><?= $icon ?></div>
                            <?= $label ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="footer-bottom">
                <p class="footer-copyright"><?= htmlspecialchars($footer['copyright'] ?? '© 2026 Radio Miraflores Televisión. Todos los derechos reservados.') ?></p>
                <p class="footer-made">Hecho con <span>❤</span> para los amantes del rock</p>
            </div>
        </div>
    </footer>

    <button class="scroll-top" onclick="scrollToTop()">↑</button>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Mobile menu
        function toggleMobileMenu() {
            document.getElementById('mobileMenu').classList.toggle('open');
        }
        function closeMobileMenu() {
            document.getElementById('mobileMenu').classList.remove('open');
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Scroll to top
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
