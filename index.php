<?php
require_once __DIR__.'/includes/bootstrap.php'; $S=site(); $H=pages()['home'];
$initialRoute??='#beranda';$routePath=rawurldecode(explode('?',ltrim($initialRoute,'#'),2)[0]);
$pageKey=['beranda'=>'home','produk'=>'shop','keranjang'=>'cart','order-success'=>'success'][$routePath]??$routePath;
$metaPage=pages()[$pageKey]??$H;$metaProduct=str_starts_with($routePath,'produk/')?product_by_slug(substr($routePath,7)):null;
$metaTitle=$metaProduct?($metaProduct['meta_title']?:$metaProduct['title'].' — '.$S['brand']):($metaPage['title']??$S['brand']).' — '.($pageKey==='home'?'Plastik, Alat Tulis & Perlengkapan Hajatan':$S['brand']);
$metaDescription=$metaProduct?($metaProduct['meta_description']?:$metaProduct['short_description']):($metaPage['description']??'');
$metaCanonical=$metaProduct?product_public_url($metaProduct):page_canonical($pageKey);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="<?=h($S["colors"]["primary"])?>">
  <title><?=h($metaTitle)?></title>
  <meta name="description" content="<?=h($metaDescription)?>">
  <link rel="canonical" href="<?=h($metaCanonical)?>">
  <?php if(noindex_page($pageKey)):?><meta name="robots" content="noindex,follow"><?php endif;?>
  <meta property="og:title" content="<?=h($metaTitle)?>">
  <meta property="og:description" content="<?=h($metaDescription)?>">
  <meta property="og:type" content="website">
  <link rel="icon" href="<?=h($S["logo"])?>" type="image/webp">
  <link rel="preload" as="image" href="<?=h($H["hero_image"])?>" <?=str_replace("srcset=", "imagesrcset=",responsive_srcset($H["hero_image"]))?> imagesizes="(max-width: 767px) 100vw, 1180px">
  <link rel="stylesheet" href="<?=h(asset_version("styles.css"))?>">
  <script>window.PLASMART_DATA=<?=seo_json(["site"=>$S,"products"=>active_products(),"categories"=>active_categories(),"pages"=>pages(),"flash"=>flash_sale(),"initialRoute"=>$initialRoute??"#beranda"])?>;</script><script defer src="<?=h(asset_version("app.js"))?>"></script>
<style>:root{--blue:<?=h($S["colors"]["primary"])?>;--ink:<?=h($S["colors"]["navy"])?>;--yellow:<?=h($S["colors"]["accent"])?>}</style></head>
<body>
  <a class="skip-link" href="#content">Lewati ke konten</a>
  <svg class="icon-defs" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="7.2"/><path d="m16 16 4.5 4.5"/></symbol>
    <symbol id="i-cart" viewBox="0 0 24 24"><path d="M3 3h2l2.1 12h11.4L21 6H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
    <symbol id="i-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7M5 9v12h5v-7h4v7h5V9"/></symbol>
    <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
    <symbol id="i-chevron" viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></symbol>
    <symbol id="i-close" viewBox="0 0 24 24"><path d="m6 6 12 12M6 18 18 6"/></symbol>
    <symbol id="i-minus" viewBox="0 0 24 24"><path d="M5 12h14"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M5 12h14M12 5v14"/></symbol>
    <symbol id="i-trash" viewBox="0 0 24 24"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></symbol>
    <symbol id="i-filter" viewBox="0 0 24 24"><path d="M3 7h7m4 0h7M3 17h12m4 0h2"/><circle cx="12" cy="7" r="2"/><circle cx="17" cy="17" r="2"/></symbol>
    <symbol id="i-sort" viewBox="0 0 24 24"><path d="M7 4v16m-4-4 4 4 4-4M17 20V4m-4 4 4-4 4 4"/></symbol>
    <symbol id="i-truck" viewBox="0 0 24 24"><path d="M2 4h12v13H2zM14 9h4l4 5v3h-8"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
    <symbol id="i-store" viewBox="0 0 24 24"><path d="M4 10v11h16V10M3 3h18l1 7a3 3 0 0 1-5 2 3 3 0 0 1-5 0 3 3 0 0 1-5 0 3 3 0 0 1-5-2zM9 21v-7h6v7"/></symbol>
    <symbol id="i-headset" viewBox="0 0 24 24"><path d="M4 13v-1a8 8 0 0 1 16 0v6a4 4 0 0 1-4 4h-3"/><rect x="2" y="11" width="4" height="8" rx="2"/><rect x="18" y="11" width="4" height="8" rx="2"/></symbol>
    <symbol id="i-flash" viewBox="0 0 24 24"><path d="M14 2 4 14h7l-1 8 10-12h-7z"/></symbol>
    <symbol id="i-whatsapp" viewBox="0 0 24 24"><path d="M20.3 3.7a11 11 0 0 0-17.6 13L1 23l6.4-1.7a11 11 0 0 0 12.9-17.6z"/><path d="M8.3 6.6c-.5-.2-1 .3-1.3.8-.8 1.3.2 3.7 2.1 5.8 2 2.1 4.5 3.6 6.1 3 .6-.2 1.7-1.6 1.3-2-.5-.5-1.9-1.3-2.3-1.2-.4.1-.8 1-1.3 1-1.2-.3-3.8-2.9-3.8-4 .1-.4.9-.8 1-1.2.1-.4-1.3-2.1-1.8-2.2z"/></symbol>
  </defs></svg>
  <header class="site-header">
    <div class="header-row wrap">
      <div class="header-left"><button class="icon-button" data-action="search" aria-label="Cari produk"><svg class="icon"><use href="#i-search"/></svg></button><nav class="desktop-nav" aria-label="Navigasi utama"><a href="#beranda">Beranda</a><a href="#produk">Semua Produk</a></nav></div>
      <a class="brand" href="#beranda" aria-label="CV PLASMART — Beranda"><img src="<?=h($S["logo"])?>" alt="Plasmart" width="1080" height="465" fetchpriority="high"></a>
      <div class="header-right"><a class="header-contact" href="<?=h(wa_url())?>" target="_blank" rel="noopener">Hubungi Kami</a><a class="icon-button cart-link" href="#keranjang" aria-label="Keranjang belanja"><svg class="icon"><use href="#i-cart"/></svg><span class="cart-badge" hidden>0</span></a><button class="icon-button" data-action="menu" aria-label="Buka menu"><svg class="icon"><use href="#i-menu"/></svg></button></div>
    </div>
    <div class="announcement" aria-label="<?=h($H["announcement"])?>"><div class="ticker-track"><span><svg class="icon"><use href="#i-truck"/></svg> <span data-page-field="announcement"><?=h($H["announcement"])?></span> <i>✦</i> <span data-page-field="announcement"><?=h($H["announcement"])?></span> <i>✦</i></span><span aria-hidden="true"><svg class="icon"><use href="#i-truck"/></svg> <span data-page-field="announcement"><?=h($H["announcement"])?></span> <i>✦</i> <span data-page-field="announcement"><?=h($H["announcement"])?></span> <i>✦</i></span></div></div>
  </header>
  <main id="content" class="wrap" tabindex="-1">
    <section class="home" id="homePage">
      <section class="hero" data-banner-slider aria-label="Pilihan kebutuhan CV PLASMART" aria-roledescription="carousel">
        <div class="hero-slides">
          <article class="hero-slide is-active" aria-label="Slide 1 dari 3">
            <img class="hero-image" data-page-image="hero_image" src="<?=h($H["hero_image"])?>" <?=responsive_srcset($H["hero_image"])?> sizes="(max-width:767px) 100vw, 1180px" width="1360" height="765" alt="Pilihan plastik dan kresek putih serta kuning" fetchpriority="high">
            <div class="hero-copy"><span class="eyebrow" data-page-field="hero_1_kicker"><?=nl2br(h($H["hero_1_kicker"]))?></span><h1 data-page-field="hero_1_title"><?=nl2br(h($H["hero_1_title"]))?></h1><p data-page-field="hero_1_description"><?=nl2br(h($H["hero_1_description"]))?></p><a href="#produk?kategori=plastik" class="button"><span data-page-field="hero_1_button"><?=h($H["hero_1_button"])?></span> <svg class="icon"><use href="#i-arrow"/></svg></a></div>
          </article>
          <article class="hero-slide" aria-label="Slide 2 dari 3" aria-hidden="true" inert>
            <img class="hero-image" data-page-image="hero_2_image" src="<?=h($H["hero_2_image"])?>" <?=responsive_srcset($H["hero_2_image"])?> sizes="(max-width:767px) 100vw, 1180px" width="1360" height="765" alt="Alat tulis dengan nuansa biru dan kuning" loading="lazy">
            <div class="hero-copy"><span class="eyebrow" data-page-field="hero_2_kicker"><?=nl2br(h($H["hero_2_kicker"]))?></span><h2 data-page-field="hero_2_title"><?=nl2br(h($H["hero_2_title"]))?></h2><p data-page-field="hero_2_description"><?=nl2br(h($H["hero_2_description"]))?></p><a href="#produk?kategori=alat-tulis" class="button"><span data-page-field="hero_2_button"><?=h($H["hero_2_button"])?></span> <svg class="icon"><use href="#i-arrow"/></svg></a></div>
          </article>
          <article class="hero-slide" aria-label="Slide 3 dari 3" aria-hidden="true" inert>
            <img class="hero-image" data-page-image="hero_3_image" src="<?=h($H["hero_3_image"])?>" <?=responsive_srcset($H["hero_3_image"])?> sizes="(max-width:767px) 100vw, 1180px" width="1360" height="765" alt="Dus kraft dan wadah makanan untuk perlengkapan hajatan" loading="lazy">
            <div class="hero-copy"><span class="eyebrow" data-page-field="hero_3_kicker"><?=nl2br(h($H["hero_3_kicker"]))?></span><h2 data-page-field="hero_3_title"><?=nl2br(h($H["hero_3_title"]))?></h2><p data-page-field="hero_3_description"><?=nl2br(h($H["hero_3_description"]))?></p><a href="#produk?kategori=hajatan" class="button"><span data-page-field="hero_3_button"><?=h($H["hero_3_button"])?></span> <svg class="icon"><use href="#i-arrow"/></svg></a></div>
          </article>
        </div>
        <div class="hero-controls"><div class="hero-dots" role="group" aria-label="Pilih banner"><button class="is-active" data-slide="0" aria-label="Banner plastik" aria-current="true"></button><button data-slide="1" aria-label="Banner alat tulis"></button><button data-slide="2" aria-label="Banner perlengkapan hajatan"></button></div><div class="hero-arrows"><button class="icon-button" data-action="prev-slide" aria-label="Banner sebelumnya"><svg class="icon rotate"><use href="#i-arrow"/></svg></button><button class="icon-button" data-action="next-slide" aria-label="Banner berikutnya"><svg class="icon"><use href="#i-arrow"/></svg></button></div></div>
      </section>
      <nav class="categories" aria-label="Kategori produk"><?php foreach(active_categories() as $c): ?><a href="#produk?kategori=<?=h($c['slug'])?>" class="category"><?php if($c['image']):?><img src="<?=h($c['image'])?>" width="768" height="432" alt="<?=h($c['name'])?>" loading="lazy"><?php endif;?><span><?=h($c['name'])?><small><?=h($c['caption']??'PILIHAN KATEGORI')?></small></span><svg class="icon"><use href="#i-arrow"/></svg></a><?php endforeach;?></nav>
      <section class="flash-section section" aria-labelledby="flashHeading"><div class="section-top"><h2 id="flashHeading"><svg class="icon flash-icon"><use href="#i-flash"/></svg> <?=h(flash_sale()["heading"])?></h2><div class="countdown" aria-label="Waktu tersisa flash sale"><span data-countdown="hours">05</span><b>:</b><span data-countdown="minutes">24</span><b>:</b><span data-countdown="seconds">18</span></div></div><div class="flash-track" id="flashProducts" aria-label="Produk flash sale"></div><div class="flash-caption"><span><?=h(flash_sale()["subheading"])?></span><a href="#produk?promo=1">Lihat promo <svg class="icon"><use href="#i-chevron"/></svg></a></div></section>
      <section class="section" aria-labelledby="featuredHeading"><div class="section-top"><h2 id="featuredHeading" data-page-field="featured_heading"><?=nl2br(h($H["featured_heading"]))?></h2><a href="#produk" class="text-link">Lihat semua <svg class="icon"><use href="#i-chevron"/></svg></a></div><div class="product-grid" id="featuredProducts"></div></section>
      <a class="delivery-banner" href="#produk"><div class="delivery-icon"><svg class="icon"><use href="#i-truck"/></svg></div><div><h2>Gratis Ongkir</h2><p>Minimal pembelian Rp200.000</p></div><span class="round-arrow"><svg class="icon"><use href="#i-arrow"/></svg></span></a>
      <section class="about section" id="tentang"><img data-page-image="workshop_image" src="<?=h($H["workshop_image"])?>" <?=responsive_srcset($H["workshop_image"])?> sizes="(max-width:767px) 100vw, 540px" width="1360" height="1020" alt="Ilustrasi penataan kebutuhan plastik dan perlengkapan toko" loading="lazy"><div class="about-copy"><span class="eyebrow">KENAL LEBIH DEKAT</span><h2 data-page-field="about_heading"><?=nl2br(h($H["about_heading"]))?></h2><p data-page-field="about_body"><?=nl2br(h($H["about_body"]))?></p><div class="about-note"><svg class="icon"><use href="#i-check"/></svg><span data-page-field="about_note"><?=nl2br(h($H["about_note"]))?></span></div><a class="text-link" href="<?=h(wa_url())?>" target="_blank" rel="noopener">Tanyakan kebutuhanmu <svg class="icon"><use href="#i-arrow"/></svg></a></div></section>
    </section>
    <section id="dynamicPage" hidden></section>
    <noscript><p class="empty-note">Aktifkan JavaScript untuk melihat katalog dan menggunakan keranjang belanja.</p></noscript>
  </main>
  <footer class="site-footer wrap"><div class="footer-main"><a href="#beranda" class="footer-brand"><img src="<?=h($S["logo"])?>" alt="Plasmart" width="1080" height="465" loading="lazy"></a><p data-page-field="footer_description"><?=nl2br(h($H["footer_description"]))?></p><a class="contact-card" href="<?=h(wa_url())?>" target="_blank" rel="noopener"><svg class="icon"><use href="#i-whatsapp"/></svg><span><small>WhatsApp</small><strong><?=h($S["contact"]["whatsapp_label"])?></strong></span><svg class="icon"><use href="#i-arrow"/></svg></a></div><div class="footer-bottom"><span>© <span id="year"><?=date("Y")?></span> <?=h($S["brand"])?></span><span>Harga dan ketersediaan dikonfirmasi melalui WhatsApp.</span><?php if(!empty($S["powered_by"])):?><span>Powered by <?=h($S["powered_by"])?></span><?php endif;?></div></footer>
  <nav class="bottom-nav" aria-label="Navigasi mobile"><a href="#beranda" data-nav="home"><svg class="icon"><use href="#i-home"/></svg><span>Beranda</span></a><a href="#produk" data-nav="products"><svg class="icon"><use href="#i-grid"/></svg><span>Produk</span></a><a href="#keranjang" data-nav="cart" class="cart-link"><svg class="icon"><use href="#i-cart"/></svg><span class="cart-badge" hidden>0</span><span>Keranjang</span></a><button data-action="menu"><svg class="icon"><use href="#i-menu"/></svg><span>Menu</span></button></nav>
  <dialog id="menuDialog" class="sheet menu-sheet"><div class="sheet-heading"><img src="<?=h($S["logo"])?>" alt="Plasmart" width="1080" height="465"><button class="icon-button" data-close aria-label="Tutup menu"><svg class="icon"><use href="#i-close"/></svg></button></div><nav class="menu-links" aria-label="Menu"><a href="#beranda"><svg class="icon"><use href="#i-home"/></svg> Beranda <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#produk"><svg class="icon"><use href="#i-grid"/></svg> Semua Produk <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#keranjang"><svg class="icon"><use href="#i-cart"/></svg> Keranjang <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#tentang"><svg class="icon"><use href="#i-store"/></svg> Tentang Kami <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#categories"><svg class="icon"><use href="#i-grid"/></svg> Kategori <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#promo"><svg class="icon"><use href="#i-flash"/></svg> Promo <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#cara-belanja"><svg class="icon"><use href="#i-check"/></svg> Cara Belanja <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#delivery"><svg class="icon"><use href="#i-truck"/></svg> Pembayaran & Pengiriman <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#faq"><svg class="icon"><use href="#i-headset"/></svg> FAQ <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#contact"><svg class="icon"><use href="#i-whatsapp"/></svg> Kontak <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#terms"><svg class="icon"><use href="#i-check"/></svg> Informasi Pemesanan <svg class="icon"><use href="#i-chevron"/></svg></a><a href="#privacy"><svg class="icon"><use href="#i-check"/></svg> Privasi <svg class="icon"><use href="#i-chevron"/></svg></a></nav><div class="menu-categories"><p class="eyebrow">BELANJA BERDASARKAN KATEGORI</p><?php foreach(active_categories() as $c):?><a href="#produk?kategori=<?=h($c["slug"])?>"><?=h($c["name"])?></a><?php endforeach;?></div><a class="button whatsapp-button" href="<?=h(wa_url())?>" target="_blank" rel="noopener"><svg class="icon"><use href="#i-whatsapp"/></svg> Chat via WhatsApp</a></dialog>
  <dialog id="searchDialog" class="sheet search-sheet"><div class="sheet-heading"><h2>Cari kebutuhanmu</h2><button class="icon-button" data-close aria-label="Tutup pencarian"><svg class="icon"><use href="#i-close"/></svg></button></div><form id="headerSearch" class="search-form"><label class="sr-only" for="headerSearchInput">Cari produk</label><svg class="icon"><use href="#i-search"/></svg><input type="search" id="headerSearchInput" name="q" placeholder="Cari produk di Plasmart..." maxlength="100" autocomplete="off"><button class="button" type="submit">Cari</button></form><p class="muted">Coba: kresek, cup, atau MAXY CLIN.</p></dialog>
  <dialog id="filterDialog" class="sheet filter-sheet"><div class="sheet-heading"><h2>Filter produk</h2><button class="icon-button" data-close aria-label="Tutup filter"><svg class="icon"><use href="#i-close"/></svg></button></div><form id="filterForm"><fieldset class="filter-categories"><legend>Kategori</legend><?php foreach(active_categories() as $c):?><label class="checkbox"><input type="checkbox" name="filter_category" value="<?=h($c["slug"])?>"><?=h($c["name"])?></label><?php endforeach;?></fieldset><div class="two-fields"><label class="field">Harga minimum<input name="min" type="number" min="0" max="99999999" step="1000" placeholder="Rp0"></label><label class="field">Harga maksimum<input name="max" type="number" min="0" max="99999999" step="1000" placeholder="Tanpa batas"></label></div><label class="checkbox"><input type="checkbox" name="promo"> Hanya produk flash sale</label><div class="form-buttons"><button type="button" class="button button-light" data-action="reset-filters">Reset</button><button class="button" type="submit">Tampilkan produk</button></div></form></dialog>
  <div class="toast" id="toast" role="status" aria-live="polite" hidden><svg class="icon"><use href="#i-check"/></svg><span id="toastText"></span><a href="#keranjang">Lihat keranjang</a></div>
  <div id="purchaseBar" class="purchase-bar" hidden></div>
</body>
</html>
