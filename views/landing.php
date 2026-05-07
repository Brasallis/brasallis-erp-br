<?php
/**
 * View: landing.php (BRASALLIS ULTRA-CLEAN)
 * Minimalismo, Performance e Foco no Cliente.
 */
$hide_default_nav = true;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Ultra UI Resources -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

<!-- Ambient Animated Background -->
<div class="ambient-bg">
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="ambient-orb orb-3"></div>
</div>

<!-- NAVIGATION: FLOATING GLASS (BRASALLIS CONEXÃO) -->
<nav class="ultra-nav">
    <a href="#" class="ultra-logo-container">
        <img src="/assets/img/pureza.png" alt="Brasallis Logo" class="ultra-logo" style="height: 55px; width: auto; object-fit: contain;">
    </a>

    <div class="nav-links-area">
        <a href="#solucoes" class="nav-link-u">Soluções</a>
        <a href="#para-voce" class="nav-link-u">Para Você</a>
        <a href="#planos" class="nav-link-u">Preços</a>
        <a href="/login.php" class="nav-link-u">Login</a>
        <a href="/register.php" class="nav-cta-btn">Começar Grátis</a>
    </div>

    <!-- Mobile Hamburger Menu Button -->
    <button class="d-lg-none mobile-menu-btn" id="mobileMenuBtn" aria-label="Abrir Menu Mobile">
        <span></span>
        <span></span>
        <span></span>
    </button>
</nav>



<style>
    :root {
        --u-bg: #ffffff; 
        --u-soft: #f8fafc;
        --u-dark: #121212; /* Brasallis deep focus */
        --u-brand-blue: #0070F2; /* Brasallis Primary Blue */
        --u-wa-green: #25D366; /* Support Channel Green */
        --u-accent: var(--u-brand-blue);
        --u-border: rgba(0, 0, 0, 0.08); 
        --u-text-main: #1c1c1c;
        --u-text-dim: #6b7280;
        --u-shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
        --u-shadow-md: 0 10px 30px rgba(0,0,0,0.04);
        --section-pad: 180px; 
    }

    /* Brasallis Hub Style Fixed Header */
    .ultra-nav {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 80px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--u-border);
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 8%;
        transition: all 0.3s ease;
    }

    .ultra-logo-container {
        display: flex;
        align-items: center;
        height: 100%;
    }

    .ultra-logo {
        height: 40px;
        width: auto;
        display: block;
    }

    .nav-links-area {
        display: flex;
        gap: 40px;
        align-items: center;
    }

    .nav-link-u {
        text-decoration: none !important;
        color: var(--u-text-main) !important;
        font-weight: 600;
        font-size: 0.95rem;
        transition: color 0.2s ease;
    }

    .nav-link-u:hover {
        color: var(--u-brand-blue) !important;
    }

    .nav-cta-btn {
        background: var(--u-brand-blue);
        color: #fff !important;
        padding: 12px 28px;
        border-radius: 8px; /* Brasallis proprietary style */
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        text-decoration: none !important;
    }

    .nav-cta-btn:hover {
        background: #003080;
        transform: translateY(-1px);
    }

    /* Mobile Menu styles */
    .mobile-menu-btn {
        background: transparent;
        border: none;
        width: 32px;
        height: 24px;
        position: relative;
        cursor: pointer;
        z-index: 2005;
        padding: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .mobile-menu-btn span {
        display: block;
        width: 100%;
        height: 2px;
        background: var(--u-dark);
        border-radius: 2px;
        transition: all 0.3s ease;
        transform-origin: left center;
    }
    .mobile-menu-btn.active span:nth-child(1) { transform: rotate(45deg); width: 26px; top: -2px; position: relative; }
    .mobile-menu-btn.active span:nth-child(2) { opacity: 0; }
    .mobile-menu-btn.active span:nth-child(3) { transform: rotate(-45deg); width: 26px; top: 2px; position: relative; }

    @media (max-width: 991px) {
        .ultra-nav { width: 100%; padding: 0 20px; border-bottom: none; }
        .nav-links-area {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(250, 250, 250, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 2001;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 35px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
            margin: 0;
            padding: 0;
        }
        .nav-links-area.mobile-active {
            opacity: 1;
            visibility: visible;
        }
        .nav-link-u, .nav-cta-btn {
            font-size: 2rem;
            font-weight: 700;
            opacity: 0;
            transform: translateY(25px);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .nav-links-area.mobile-active .nav-link-u,
        .nav-links-area.mobile-active .nav-cta-btn {
            opacity: 1;
            transform: translateY(0);
        }
        .nav-cta-btn {
            font-size: 1.25rem;
            padding: 16px 42px;
            margin-top: 10px;
        }
        /* Staggered Animation Delays */
        .nav-links-area.mobile-active .nav-link-u:nth-child(1) { transition-delay: 0.10s; }
        .nav-links-area.mobile-active .nav-link-u:nth-child(2) { transition-delay: 0.15s; }
        .nav-links-area.mobile-active .nav-link-u:nth-child(3) { transition-delay: 0.20s; }
        .nav-links-area.mobile-active .nav-link-u:nth-child(4) { transition-delay: 0.25s; }
        .nav-links-area.mobile-active .nav-cta-btn { transition-delay: 0.30s; }
    }

    body {
        margin: 0;
        padding: 0;
        background-color: transparent; /* Changed to transparent so ambient-bg shows */
        color: var(--u-text-main);
        font-family: 'Plus Jakarta Sans', sans-serif;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
    }

    /* Google/Stripe Style Animated Floating Orbs */
    .ambient-bg {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: -1;
        overflow: hidden;
        background-color: var(--u-bg);
        pointer-events: none;
    }
    
    .ambient-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.5;
        animation: floatOrb 20s infinite alternate ease-in-out;
    }

    .orb-1 {
        width: 600px; height: 600px;
        background: rgba(0, 64, 176, 0.08);
        top: -200px; left: -200px;
        animation-duration: 25s;
    }
    
    .orb-2 {
        width: 500px; height: 500px;
        background: rgba(37, 211, 102, 0.06);
        bottom: -100px; right: -100px;
        animation-duration: 28s;
        animation-delay: -5s;
    }

    .orb-3 {
        width: 400px; height: 400px;
        background: rgba(66, 133, 244, 0.05);
        top: 40%; left: 30%;
        animation-duration: 22s;
        animation-delay: -10s;
    }

    @keyframes floatOrb {
        0% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(50px, -50px) scale(1.1); }
        100% { transform: translate(-30px, 30px) scale(0.95); }
    }
    
    /* Modern Glass Effect on Light */
    .glass-light {
        background: var(--u-glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--u-glass-border);
    }

    /* Brasallis Tint Synergy - Subtle Brand Backgrounds */
    .u-tint-green {
        background: rgba(16, 185, 129, 0.06);
        border: 1px solid rgba(16, 185, 129, 0.08);
        border-radius: 32px;
        position: relative;
    }

    .u-tint-dark {
        background: var(--u-dark);
        color: #fff;
        border-radius: 32px;
        box-shadow: 0 40px 100px -20px rgba(15, 23, 42, 0.4);
    }

    h1, h2, h3, .u-title { font-family: 'Outfit', sans-serif; letter-spacing: -0.03em; }

    .sec-ultra { padding: var(--section-pad) 0; }

    /* Solutions Grid (Brasallis Hub Card Architecture) */
    .solutions-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
        margin-top: 60px;
    }

    .u-card-clean {
        background: #fff;
        border: 1px solid var(--u-border);
        border-radius: 16px;
        padding: 48px 40px;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        gap: 20px;
        box-shadow: var(--u-shadow-sm);
        text-align: center;
        align-items: center;
    }
    
    .u-card-clean:hover {
        border-color: var(--u-wa-green);
        box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.08);
        transform: translateY(-8px);
    }

    .u-card-icon {
        width: 64px;
        height: 64px;
        background: var(--u-soft);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: var(--u-brand-blue);
        margin-bottom: 8px;
    }

    .u-icon-anim {
        width: 32px;
        height: 32px;
        transition: all 0.5s ease;
    }

    .u-card-clean:hover .u-icon-anim {
        transform: scale(1.15);
        color: var(--u-wa-green) !important;
    }

    .u-icon-anim path, .u-icon-anim circle, .u-icon-anim line, .u-icon-anim polyline {
        stroke-dasharray: 100;
        stroke-dashoffset: 0;
        transition: stroke-dashoffset 1s ease-in-out;
    }

    .u-card-clean:hover .u-icon-anim path,
    .u-card-clean:hover .u-icon-anim circle,
    .u-card-clean:hover .u-icon-anim line,
    .u-card-clean:hover .u-icon-anim polyline {
        animation: drawIcon 1.2s forwards cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes drawIcon {
        0% { stroke-dashoffset: 100; opacity: 0; }
        30% { opacity: 1; }
        100% { stroke-dashoffset: 0; opacity: 1; }
    }

    .u-card-title { font-size: 1.5rem; font-weight: 800; color: var(--u-dark); margin: 0; }
    .u-card-desc { font-size: 1rem; color: var(--u-text-dim); line-height: 1.7; margin: 0; }

    @media (max-width: 991px) {
        .solutions-grid { grid-template-columns: 1fr; }
    }

    /* Centered Hero Architecture (Brasallis Hub) */
    .hero-ultra-clean {
        padding: 160px 0 100px;
        background: #fff;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .hero-tag {
        display: inline-block;
        padding: 8px 16px;
        background: var(--u-soft);
        color: var(--u-brand-blue);
        border-radius: 100px;
        font-size: 0.85rem;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 24px;
    }

    .hero-h1-clean {
        font-size: clamp(2.5rem, 8vw, 4.5rem);
        font-weight: 900;
        color: var(--u-dark);
        line-height: 1.1;
        letter-spacing: -0.04em;
        margin-bottom: 24px;
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }

    .hero-p-clean {
        font-size: 1.25rem;
        color: var(--u-text-dim);
        line-height: 1.6;
        max-width: 700px;
        margin: 0 auto 48px;
    }

    .hero-mockup-wrap {
        margin-top: 80px;
        position: relative;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }

    .mockup-img-clean {
        width: 100%;
        height: auto;
        border-radius: 16px;
        border: 1px solid var(--u-border);
        box-shadow: 0 50px 100px -20px rgba(0,0,0,0.15);
    }

    /* --- BRASALLIS ELITE PRICING --- */
    .pricing-master-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 60px;
        margin-top: 40px;
    }

    .sec-ultra {
        padding: 100px 0;
        width: 100%;
        overflow: hidden;
    }

    .master-billing-control {
        background: #f2f2f7;
        padding: 6px;
        border-radius: 24px;
        display: inline-flex;
        gap: 4px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.04);
        margin-bottom: 40px;
    }

    .master-pill {
        border: none;
        background: transparent;
        padding: 12px 24px;
        font-size: 0.85rem;
        font-weight: 700;
        color: #86868b;
        border-radius: 20px;
        transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        cursor: pointer;
    }

    .master-pill.active {
        background: #fff;
        color: #000;
        box-shadow: 0 8px 16px rgba(0,0,0,0.06);
    }

    .master-pill .discount-tag {
        font-size: 0.65rem;
        background: #e1f0ff;
        color: #0071e3;
        padding: 2px 8px;
        border-radius: 100px;
        font-weight: 800;
    }

    /* Elite Glass Cards */
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 32px;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
    }

    .pricing-card {
        background: #ffffff;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 40px;
        padding: 60px 40px;
        transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
    }

    .pricing-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 8px;
        background: rgba(var(--brand-rgb), 0.1);
        opacity: 0;
        transition: 0.3s;
    }

    .pricing-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 50px 100px rgba(0,0,0,0.06);
        border-color: rgba(var(--brand-rgb), 0.2);
    }

    .pricing-card:hover::before {
        opacity: 1;
        background: rgba(var(--brand-rgb), 1);
    }

    .pricing-card.featured {
        background: #fff;
        border: 2px solid #0071e3;
        box-shadow: 0 30px 60px rgba(0, 113, 227, 0.12);
        z-index: 2;
    }
    
    .pricing-card.featured::after {
        content: 'MAIS POPULAR';
        position: absolute;
        top: 25px;
        right: -35px;
        background: #0071e3;
        color: #fff;
        padding: 5px 40px;
        font-size: 0.6rem;
        font-weight: 800;
        transform: rotate(45deg);
        letter-spacing: 1px;
    }

    .plan-potencial {
        font-size: 0.9rem;
        color: #6e6e73;
        line-height: 1.6;
        margin-bottom: 35px;
        font-weight: 500;
    }

    .hero-icon-box {
        width: 80px;
        height: 80px;
        border-radius: 24px;
        background: linear-gradient(135deg, #f5f5f7 0%, #e5e5e7 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        margin-bottom: 30px;
        transition: all 0.5s ease;
        box-shadow: 0 10px 20px rgba(0,0,0,0.03);
    }

    .pricing-card.featured .hero-icon-box {
        background: linear-gradient(135deg, #0071e3 0%, #00c6ff 100%);
        color: #fff;
        box-shadow: 0 15px 30px rgba(0, 113, 227, 0.2);
    }

    .price-container {
        margin-bottom: 40px;
    }

    .price-value {
        font-size: 3.5rem;
        font-weight: 800;
        letter-spacing: -2px;
        line-height: 1;
        color: #1d1d1f;
    }

    /* Benefits Elite */
    .benefits-list {
        width: 100%;
        margin-bottom: 45px;
        text-align: left;
        padding: 0 10px;
    }

    .benefit-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 18px;
    }

    .benefit-item i {
        font-size: 0.9rem;
        margin-top: 4px;
        color: #34c759;
    }

    .benefit-info {
        display: flex;
        flex-direction: column;
    }

    .benefit-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #1d1d1f;
    }

    .benefit-desc {
        font-size: 0.75rem;
        color: #86868b;
    }

    .btn-elite {
        border-radius: 20px;
        padding: 18px 30px;
        font-weight: 700;
        font-size: 1rem;
        transition: all 0.3s;
        border: none;
    }

    .benefit-item i {
        font-size: 1.2rem;
        margin-bottom: 8px;
        display: block;
    }

    .btn-elite {
        padding: 20px 40px;
        border-radius: 24px;
        font-weight: 800;
        font-size: 1.1rem;
        transition: 0.4s;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .price-container {
        margin-bottom: 40px;
    }

    .price-value {
        font-family: 'Outfit', sans-serif;
        font-size: 4rem;
        line-height: 1;
    }
    }

    .fw-black { font-weight: 900; }
    .x-small { font-size: 0.7rem; }

    .price-value {
        font-size: 3.5rem;
        font-weight: 900;
        color: var(--u-dark);
        letter-spacing: -2px;
    }

    .price-period {
        font-size: 1rem;
        color: var(--u-text-dim);
        font-weight: 600;
    }

    .benefits-list {
        margin: 40px 0;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .benefit-item {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--u-text-main);
        font-size: 0.95rem;
    }

    .benefit-item i {
        color: var(--u-wa-green);
        font-size: 0.85rem;
    }

    .btn-u-outline {
        border: 2px solid var(--u-dark);
        color: var(--u-dark);
        padding: 16px;
        text-align: center;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-u-outline:hover {
        background: var(--u-dark);
        color: #fff;
    }

    .btn-u-black {
        background: var(--u-dark);
        color: #fff;
        padding: 16px;
        text-align: center;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
    }

    .btn-u-black:hover {
        opacity: 0.9;
        transform: scale(0.98);
    }

    /* --- VIDEO HUB & NEWSLETTER (BRASALLIS ENGAGEMENT) --- */
    .u-video-card {
        background: var(--u-surface);
        border: 1px solid var(--u-border);
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .u-video-card:hover {
        border-color: var(--u-brand-blue);
        transform: translateY(-5px);
    }

    .u-video-thumb {
        width: 100%;
        height: 200px;
        background: #1a1a1a;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .u-play-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.8;
        transition: opacity 0.3s;
    }

    .u-video-card:hover .u-play-overlay {
        opacity: 1;
        background: rgba(0, 112, 242, 0.2);
    }

    .u-play-icon {
        width: 50px;
        height: 50px;
        background: var(--u-brand-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        padding-left: 4px;
        box-shadow: 0 0 20px rgba(0, 112, 242, 0.4);
    }

    .newsletter-hub {
        background: radial-gradient(circle at top right, rgba(0, 112, 242, 0.05), transparent 70%);
        border: 1px solid var(--u-border);
        border-radius: 32px;
        padding: 80px 40px;
        text-align: center;
        margin-top: 60px;
    }

    .newsletter-input-group {
        max-width: 500px;
        margin: 0 auto;
        position: relative;
    }

    .u-input-premium {
        background: #fff;
        border: 2px solid var(--u-border);
        border-radius: 12px;
        padding: 18px 24px;
        width: 100%;
        font-weight: 500;
        transition: all 0.2s;
    }

    .u-input-premium:focus {
        outline: none;
        border-color: var(--u-brand-blue);
        box-shadow: 0 0 0 4px rgba(0, 112, 242, 0.1);
    }

    .u-btn-subscribe {
        position: absolute;
        right: 8px;
        top: 8px;
        bottom: 8px;
        background: var(--u-dark);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0 24px;
        font-weight: 700;
        transition: all 0.2s;
    }

    .u-btn-subscribe:hover {
        background: var(--u-brand-blue);
    }

    /* --- SECTION: HERO ULTRA-CLEAN --- */
    .hero-ultra-clean {
        padding: 160px 0 100px;
        background: var(--u-soft);
        position: relative;
        overflow: hidden;
    }

    .hero-h1-clean {
        font-family: 'Outfit', sans-serif;
        font-weight: 900;
        font-size: 4rem;
        color: var(--u-dark);
        line-height: 1.1;
        margin-bottom: 25px;
    }

    .hero-p-clean {
        font-size: 1.25rem;
        color: var(--u-text-dim);
        max-width: 700px;
        margin: 0 auto 40px;
        line-height: 1.6;
    }

    .hero-tag {
        display: inline-block;
        background: var(--u-brand-blue);
        color: #fff;
        padding: 6px 16px;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 20px;
    }

    .mockup-img-clean {
        width: 100%;
        max-width: 1100px;
        border-radius: 20px;
        box-shadow: 0 30px 60px rgba(0,0,0,0.12);
        border: 1px solid var(--u-border);
    }

    /* WhatsApp Floating Button */
    .wa-float {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 65px;
        height: 65px;
        background-color: var(--u-wa-green);
        color: #FFF !important;
        border-radius: 50px;
        text-align: center;
        font-size: 30px;
        box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4);
        z-index: 2100;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        text-decoration: none !important;
        animation: pulseWA 2s infinite;
    }

    .wa-float:hover {
        transform: scale(1.1) rotate(5deg);
        background-color: #128C7E;
    }

    @keyframes pulseWA {
        0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.5); }
        70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
        100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
    }

    @media (max-width: 991px) {
        .hero-h1-clean { font-size: 2.5rem; }
        .pricing-grid { grid-template-columns: 1fr; }
        .wa-float { bottom: 90px; right: 20px; width: 55px; height: 55px; font-size: 24px; }
    }
</style>

<!-- Floating WhatsApp -->
<a href="https://wa.me/5511937748884?text=Ol%C3%A1!%20Gostaria%20de%20saber%20mais%20sobre%20as%20solu%C3%A7%C3%B5es%20do%20Brasallis%20Hub." class="wa-float" target="_blank" title="Falar com Consultor">
    <i class="fab fa-whatsapp"></i>
</a>


<!-- SECTION: BRASALLIS HUB ENTERPRISE HERO -->
<section id="hero" class="hero-ultra-clean text-center">
    <div class="container">
        <div data-aos="fade-up">
            <span class="hero-tag">Simplicidade & Performance</span>
            <h1 class="hero-h1-clean">
                O Próximo Nível da <br><span style="color:var(--u-brand-blue);">Inteligência Operacional.</span>
            </h1>
            <p class="hero-p-clean">
                A plataforma definitiva para empresas que buscam automação inteligente e controle total em tempo real com a robustez do ecossistema Brasallis.
            </p>
            <div class="d-flex justify-content-center gap-4">
                <!-- Diagnóstico Brasallis 360 -->
                <?php include __DIR__ . '/diagnostic_form.php'; ?>
            </div>
        </div>
 
        <div class="mt-5" data-aos="fade-up" data-aos-delay="200">
            <img src="/assets/img/dashboard_attachment.jpg" alt="Brasallis Dashboard" class="mockup-img-clean">
        </div>
    </div>
</section>

<!-- SECTION: SOLUTIONS ULTRA-CLEAN (PLATFORM CORE) -->
<section id="solucoes" class="sec-ultra" style="background: var(--u-soft);">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="hero-tag">Tudo o que você precisa</span>
            <h2 class="display-5 fw-bold text-dark">Potencializando sua <br>Automação <span style="color:var(--u-wa-green);">Inteligente.</span></h2>
        </div>

        <div class="solutions-grid">
            <!-- Card 1 -->
            <div class="u-card-clean" data-aos="fade-up">
                <div class="u-card-icon">
                    <svg class="u-icon-anim" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <h3 class="u-card-title">Venda em Segundos</h3>
                <p class="u-card-desc">Nosso PDV é otimizado para velocidade. Busque produtos, aplique descontos e finalize pagamentos em tempo recorde.</p>
            </div>

            <!-- Card 2 -->
            <div class="u-card-clean" data-aos="fade-up" data-aos-delay="100">
                <div class="u-card-icon">
                    <svg class="u-icon-anim" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                </div>
                <h3 class="u-card-title">Controle de Estoque</h3>
                <p class="u-card-desc">Sincronização 360°. Tenha visão total de suas movimentações, alertas de estoque baixo e relatórios preditivos.</p>
            </div>

            <!-- Card 3 -->
            <div class="u-card-clean" data-aos="fade-up" data-aos-delay="200">
                <div class="u-card-icon">
                    <svg class="u-icon-anim" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </div>
                <h3 class="u-card-title">Relatórios Inteligentes</h3>
                <p class="u-card-desc">Decisórias baseadas em dados. Acompanhe seu faturamento, lucro líquido e ticket médio em tempo real.</p>
            </div>
        </div>
    </div>
</section>






<!-- ====================================================
     SECTION: 3-AUDIENCE VALUE PROPOSITION
     Para cada tipo de cliente: Iniciante, Crescimento, Líder
===================================================== -->
<!-- SECTION: AUDIENCE ULTRA-CLEAN (SEGMENTATION) -->
<section id="para-voce" class="sec-ultra" style="background: #fff;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="hero-tag">Sob Medida para Você</span>
            <h2 class="display-5 fw-bold text-dark">A base que você precisa,<br>para o <span style="color:var(--u-wa-green);">Tamanho</span> que você quer.</h2>
        </div>

        <div class="solutions-grid">
            <!-- MEI -->
            <div class="u-card-clean" data-aos="fade-up">
                <div class="u-card-subtitle" style="color:var(--u-brand-blue); font-size:0.7rem; font-weight:800; letter-spacing:1px; margin-bottom:10px;">BRASALLIS START</div>
                <h3 class="u-card-title">Empresas MEI</h3>
                <p class="u-card-desc">Ganhe tempo e profissionalismo. Saia das planilhas e organize suas vendas e estoque em segundos.</p>
                <div class="mt-4">
                    <a href="/register.php" class="btn btn-dark w-100 py-3 fw-bold" style="border-radius:12px;">Começar Agora</a>
                </div>
            </div>

            <!-- PME -->
            <div class="u-card-clean" data-aos="fade-up" data-aos-delay="100">
                <div class="u-card-subtitle" style="color:var(--u-wa-green); font-size:0.7rem; font-weight:800; letter-spacing:1px; margin-bottom:10px;">BRASALLIS PRO</div>
                <h3 class="u-card-title">Pequenas e Médias</h3>
                <p class="u-card-desc">Para quem já escala. Conte com relatórios financeiros avançados, multi-lojas e automação de processos.</p>
                <div class="mt-4">
                    <a href="#planos" class="btn btn-primary w-100 py-3 fw-bold" style="border-radius:12px; background:var(--u-brand-blue); border:none;">Ver Planos Pro</a>
                </div>
            </div>

            <!-- Enterprise -->
            <div class="u-card-clean" data-aos="fade-up" data-aos-delay="200">
                <div class="u-card-subtitle" style="color:var(--u-accent); font-size:0.7rem; font-weight:800; letter-spacing:1px; margin-bottom:10px;">BRASALLIS ENTERPRISE</div>
                <h3 class="u-card-title">Corporativo</h3>
                <p class="u-card-desc">Soluções customizadas para grandes operações. Infraestrutura dedicada, SLAs e gerente de conta exclusivo.</p>
                <div class="mt-4">
                    <a href="https://wa.me/5511937748884" class="btn btn-outline-dark w-100 py-3 fw-bold" style="border-radius:12px;">Atendimento VIP</a>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- SECTION: PRICING (Elite Elite) -->
<section id="planos" class="sec-ultra" style="background: #fbfbfd;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="fw-bold text-uppercase small ls-2 mb-3 d-block" style="color: var(--u-accent); letter-spacing: 2px;">Investimento em Sucesso</span>
            <h2 class="display-4 fw-black" style="color: var(--u-dark);">Escolha seu Plano <span style="color: var(--u-accent)">Ideal.</span></h2>
            <p class="fs-5 mx-auto mb-5" style="max-width: 600px; color: var(--u-text-dim);">Transparência total para que você saiba exatamente como estamos ajudando sua empresa a escalar.</p>
        </div>
            
    <div class="pricing-master-container" data-aos="fade-up">
        <!-- Master Billing Switcher -->
        <div class="master-billing-control">
            <button class="master-pill active" onclick="masterSyncPricing('mensal', this)">Mensal</button>
            <button class="master-pill" onclick="masterSyncPricing('semestral', this)">6 Meses <span class="discount-tag">-10%</span></button>
            <button class="master-pill" onclick="masterSyncPricing('anual', this)">Anual <span class="discount-tag">-20%</span></button>
            <button class="master-pill" onclick="masterSyncPricing('bienal', this)">2 Anos <span class="discount-tag">-30%</span></button>
        </div>

        <div class="pricing-grid">
            <!-- Plano 1: Foundation -->
            <div class="pricing-card" style="--brand-rgb: 108, 117, 125;" onclick="document.getElementById('link-foundation').click()">
                <div class="hero-icon-box"><i class="fas fa-seedling text-muted"></i></div>
                <span class="x-small fw-bold text-muted text-uppercase ls-2 mb-2">O Alicerce Digital</span>
                <h4 class="fw-black mb-3 fs-2" style="letter-spacing: -1.5px;">Foundation</h4>
                <p class="plan-potencial">"Deixe a complexidade das planilhas para trás. Assuma o controle total do seu estoque e vendas com precisão cirúrgica."</p>
                
                <div class="price-container">
                    <div class="d-flex align-items-baseline justify-content-center">
                        <span class="fs-4 fw-bold text-muted me-1">R$</span>
                        <span class="price-value fw-black" id="price-foundation" data-plan="foundation" data-mensal="189" data-semestral="170" data-anual="152" data-bienal="133">189</span>
                        <span class="text-muted small ms-2">/mês*</span>
                    </div>
                </div>

                <div class="benefits-list">
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-info">
                            <span class="benefit-title">Hub de Operações</span>
                            <span class="benefit-desc">PDV, Estoque e Equipe integrados.</span>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-info">
                            <span class="benefit-title">Visibilidade Real</span>
                            <span class="benefit-desc">Relatórios que mostram a saúde do caixa.</span>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-info">
                            <span class="benefit-title">Suporte Standard</span>
                            <span class="benefit-desc">Acesso à base de conhecimento e tickets.</span>
                        </div>
                    </div>
                </div>

                <a href="register.php?plan=foundation&billing=mensal" id="link-foundation" class="btn btn-dark btn-elite w-100 mt-auto">Começar Agora</a>
            </div>

            <!-- Plano 2: Vision -->
            <div class="pricing-card featured" style="--brand-rgb: 0, 113, 227;" onclick="document.getElementById('link-vision').click()">
                <div class="hero-icon-box"><i class="fas fa-brain"></i></div>
                <span class="x-small fw-bold text-primary text-uppercase ls-2 mb-2">A Mente Estratégica</span>
                <h4 class="fw-black mb-3 fs-2" style="letter-spacing: -1.5px;">Vision AI</h4>
                <p class="plan-potencial">"Onde a Inteligência Artificial encontra a gestão. Antecipe tendências e automatize seu financeiro com o Brasallis IQ."</p>
                
                <div class="price-container">
                    <div class="d-flex align-items-baseline justify-content-center text-primary">
                        <span class="fs-4 fw-bold me-1">R$</span>
                        <span class="price-value fw-black" id="price-vision" data-plan="vision" data-mensal="389" data-semestral="350" data-anual="312" data-bienal="273">389</span>
                        <span class="text-muted small ms-2">/mês*</span>
                    </div>
                </div>

                <div class="benefits-list">
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-info">
                            <span class="benefit-title">Automação Inteligente</span>
                            <span class="benefit-desc">CRM Kanban e Fluxo de Caixa Preditivo.</span>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-info">
                            <span class="benefit-title">Decisões com IA</span>
                            <span class="benefit-desc">DRE e indicadores estratégicos em tempo real.</span>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-info">
                            <span class="benefit-title">OCR Ilimitado</span>
                            <span class="benefit-desc">Processamento automático de notas e boletos.</span>
                        </div>
                    </div>
                </div>

                <a href="register.php?plan=vision&billing=mensal" id="link-vision" class="btn btn-primary btn-elite w-100 mt-auto shadow-lg" style="background: linear-gradient(135deg, #0071e3 0%, #00c6ff 100%); border: none;">Escalar com IA</a>
            </div>

            <!-- Plano 3: Enterprise -->
            <div class="pricing-card" style="--brand-rgb: 33, 37, 41;" onclick="document.getElementById('link-enterprise').click()">
                <div class="hero-icon-box"><i class="fas fa-fort-awesome"></i></div>
                <span class="x-small fw-bold text-dark text-uppercase ls-2 mb-2">A Fortaleza Corporativa</span>
                <h4 class="fw-black mb-3 fs-2" style="letter-spacing: -1.5px;">Enterprise</h4>
                <p class="plan-potencial">"Escala sem limites para grandes redes. Governança total, conformidade fiscal absoluta e suporte de elite 24/7."</p>
                
                <div class="price-container">
                    <div class="d-flex align-items-baseline justify-content-center">
                        <span class="fs-4 fw-bold text-dark me-1">R$</span>
                        <span class="price-value fw-black text-dark" id="price-enterprise" data-plan="enterprise" data-mensal="899" data-semestral="809" data-anual="720" data-bienal="630">899</span>
                        <span class="text-muted small ms-2">/mês*</span>
                    </div>
                </div>

                <div class="benefits-list">
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-info">
                            <span class="benefit-title">Governança & Segurança</span>
                            <span class="benefit-desc">Logs de auditoria e multi-filiais ilimitadas.</span>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-info">
                            <span class="benefit-title">Concierge Brasallis</span>
                            <span class="benefit-desc">Gerente de conta dedicado e consultoria VIP.</span>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-info">
                            <span class="benefit-title">SLA de 1 Hora</span>
                            <span class="benefit-desc">Atendimento prioritário para missão crítica.</span>
                        </div>
                    </div>
                </div>

                <a href="register.php?plan=enterprise&billing=mensal" id="link-enterprise" class="btn btn-outline-dark btn-elite w-100 mt-auto">Consultoria Elite</a>
            </div>
        </div>
    </div>
</div>
</section>

<script>
    function masterSyncPricing(period, btn) {
        // 1. Atualizar estado visual do seletor master
        document.querySelectorAll('.master-pill').forEach(p => p.classList.remove('active'));
        if (btn) btn.classList.add('active');

        // 2. Sincronizar todos os cards de plano
        const plans = ['foundation', 'vision', 'enterprise'];
        plans.forEach(plan => {
            const priceEl = document.getElementById(`price-${plan}`);
            const linkEl = document.getElementById(`link-${plan}`);
            
            // Atualizar Valor do Plano
            if (priceEl) {
                const newVal = parseFloat(priceEl.getAttribute(`data-${period}`));
                priceEl.innerText = newVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                // Micro-animação reativa
                priceEl.classList.remove('pulse-sync');
                void priceEl.offsetWidth; // trigger reflow
                priceEl.classList.add('pulse-sync');
            }

            // Atualizar Link de Registro com Parâmetros
            if (linkEl) {
                linkEl.href = `register.php?plan=${plan}&billing=${period}`;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar AOS
        if (typeof AOS !== 'undefined') {
            AOS.init({ duration: 1000, once: true });
        }
    });
</script>

<style>
    .pulse-sync { animation: pulseSync 0.4s ease; }
    @keyframes pulseSync {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .price-value { transition: all 0.3s ease; display: inline-block; }
</style>

<!-- SECTION: BRASALLIS EM AÇÃO (VIDEO HUB) -->
<section id="demo" class="sec-ultra" style="background: #fff;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="hero-tag">Demonstração ao Vivo</span>
            <h2 class="display-5 fw-bold text-dark">Veja o Poder do <span style="color:var(--u-brand-blue);">Brasallis IQ</span>.</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">Assista como nossa tecnologia está revolucionando o varejo e a gestão logística.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="u-video-card" onclick="openVideoModal('dQw4w9WgXcQ')">
                    <div class="u-video-thumb">
                        <i class="fas fa-desktop fa-4x opacity-10"></i>
                        <div class="u-play-overlay">
                            <div class="u-play-icon"><i class="fas fa-play"></i></div>
                        </div>
                    </div>
                    <div class="p-4">
                        <h5 class="fw-bold mb-2">Tour Completo pelo Hub</h5>
                        <p class="small text-muted mb-0">Uma visão geral de 5 minutos sobre as funcionalidades do sistema.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="u-video-card" onclick="openVideoModal('dQw4w9WgXcQ')">
                    <div class="u-video-thumb">
                        <i class="fas fa-eye fa-4x opacity-10"></i>
                        <div class="u-play-overlay">
                            <div class="u-play-icon"><i class="fas fa-play"></i></div>
                        </div>
                    </div>
                    <div class="p-4">
                        <h5 class="fw-bold mb-2">Automação Vision AI (OCR)</h5>
                        <p class="small text-muted mb-0">Veja como processamos uma nota fiscal em menos de 2 segundos.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="u-video-card" onclick="openVideoModal('dQw4w9WgXcQ')">
                    <div class="u-video-thumb">
                        <i class="fas fa-brain fa-4x opacity-10"></i>
                        <div class="u-play-overlay">
                            <div class="u-play-icon"><i class="fas fa-play"></i></div>
                        </div>
                    </div>
                    <div class="p-4">
                        <h5 class="fw-bold mb-2">Previsão de Giro com IQ</h5>
                        <p class="small text-muted mb-0">Entenda como a IA avisa o momento exato de repor seu estoque.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION: NEWSLETTER STRATEGIC HUB -->
<section class="sec-ultra" style="background: var(--u-soft);">
    <div class="container">
        <div class="newsletter-hub" data-aos="fade-up">
            <span class="fw-bold text-uppercase small ls-2 mb-3 d-block" style="color: var(--u-brand-blue);">Insights de Mercado</span>
            <h2 class="display-5 fw-black mb-4">Receba a Inteligência <br>do Futuro.</h2>
            <p class="text-muted mb-5 mx-auto" style="max-width: 500px;">Assine nossa newsletter para receber tendências de IA, gestão de estoque e governança corporativa.</p>
            
            <form class="newsletter-input-group">
                <input type="email" class="u-input-premium" placeholder="Seu e-mail profissional..." required>
                <button type="submit" class="u-btn-subscribe">Inscrever</button>
            </form>
            <p class="small text-muted mt-4 opacity-50">Respeitamos sua privacidade. Zero spam.</p>
        </div>
    </div>
</section>

<!-- VIDEO MODAL -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-header border-0 p-0 justify-content-end">
                <button type="button" class="btn-close btn-close-white mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9 shadow-lg rounded-3 overflow-hidden">
                    <iframe id="youtubeIframe" src="" title="YouTube video" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER (Modern Light) -->
<footer class="py-5" style="background: #fff; border-top: 1px solid var(--u-border);">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-3">
                <img src="/assets/img/pureza.png" alt="Brasallis Logo" style="height: 85px; width: auto; object-fit: contain;">
            </div>
            <div class="col-lg-9 text-lg-end">
                <div class="d-flex justify-content-lg-end gap-4 flex-wrap">
                    <a href="register.php" class="text-dark text-decoration-none small opacity-50 hover-opacity-100 fw-bold">CRIAR CONTA</a>
                    <a href="#planos" class="text-dark text-decoration-none small opacity-50 hover-opacity-100 fw-bold">PLANOS</a>
                    <a href="login.php" class="text-dark text-decoration-none small opacity-50 hover-opacity-100 fw-bold">LOGIN</a>
                    <span class="small opacity-30 ms-lg-4 text-black">© 2026 Brasallis Hub. Design Moderno e Solar.</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ 
        duration: 800, 
        once: true,
        easing: 'ease-out-quad' // Smoother, simpler Brasallis feel
    });

    // Mobile Menu Toggle Logic
    document.addEventListener('DOMContentLoaded', function() {
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const navLinksArea = document.querySelector('.nav-links-area');
        const navLinks = document.querySelectorAll('.nav-link-u');

        if (mobileBtn && navLinksArea) {
            mobileBtn.addEventListener('click', function() {
                mobileBtn.classList.toggle('active');
                navLinksArea.classList.toggle('mobile-active');
                document.body.style.overflow = navLinksArea.classList.contains('mobile-active') ? 'hidden' : '';
            });

            // Close menu when a link is clicked
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileBtn.classList.remove('active');
                    navLinksArea.classList.remove('mobile-active');
                    document.body.style.overflow = '';
                });
            });
        }
    });

    // Video Modal Logic for Brasallis Hub
    const videoModalElem = document.getElementById('videoModal');
    const youtubeIframe = document.getElementById('youtubeIframe');
    let bootstrapVideoModal = null;

    if (videoModalElem) {
        bootstrapVideoModal = new bootstrap.Modal(videoModalElem);
        
        // Clean up on close
        videoModalElem.addEventListener('hidden.bs.modal', function () {
            youtubeIframe.src = '';
        });
    }

    function openVideoModal(videoId) {
        if (youtubeIframe && bootstrapVideoModal) {
            youtubeIframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
            bootstrapVideoModal.show();
        }
    }

</script>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
