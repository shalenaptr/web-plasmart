<?php
require __DIR__.'/bootstrap.php';
require_login();
$site=load_json('site');
$products=load_json('products');
$cats=load_json('categories');
$flash=load_json('flash-sale');
$pages=pages();
$brands=array_values(array_unique(array_column($products,'brand')));
$pageUrls=[
    'home'=>'../index.php',
    'shop'=>'../shop.php',
    'categories'=>'../categories.php',
    'cara-belanja'=>'../cara-belanja.php',
    'delivery'=>'../delivery.php',
    'tentang'=>'../tentang.php',
    'faq'=>'../faq.php',
    'contact'=>'../contact.php',
    'promo'=>'../promo.php',
    'terms'=>'../terms.php',
    'privacy'=>'../privacy.php',
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Admin Dashboard — <?=h($site['brand']??'CV PLASMART')?></title>
<link rel="stylesheet" href="assets/admin.css?v=<?=filemtime(__DIR__.'/assets/admin.css')?>">
</head>
<body>
<div class="admin-shell">
<aside class="admin-side" data-side>
    <div class="admin-brand">
        <span class="admin-brand-mark"><?php if(!empty($site['logo'])):?><img src="../<?=h($site['logo'])?>" alt=""><?php else:?>a.<?php endif;?></span>
        <div><strong><?=h($site['brand'])?></strong><span>Admin Website</span></div>
    </div>
    <nav class="admin-nav" aria-label="Navigasi admin">
        <button class="active" type="button" data-tab="dashboard"><span class="nav-dot"></span>Dashboard</button>
        <button type="button" data-tab="produk"><span class="nav-dot"></span>Produk</button>
        <button type="button" data-tab="categories"><span class="nav-dot"></span>Kategori</button>
        <button type="button" data-tab="flash"><span class="nav-dot"></span>Flash Sale</button>
        <button type="button" data-tab="pages"><span class="nav-dot"></span>Editor Halaman</button>
        <button type="button" data-tab="settings"><span class="nav-dot"></span>Logo, Warna & WhatsApp</button>
        <button type="button" data-tab="security"><span class="nav-dot"></span>Akun Admin</button><div class="nav-divider"></div>
        <a href="../index.php" target="_blank" rel="noopener"><span class="nav-dot"></span>Lihat Website ↗</a>
        <form action="logout.php" method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><button type="submit"><span class="nav-dot"></span>Keluar</button></form>
    </nav>
    <div class="admin-side-foot"><span>Panel Admin</span><small>Kelola konten dan pengaturan website.</small></div>
</aside>

<main class="admin-main">
    <header class="admin-top admin-top-compact">
        <div class="admin-top-left">
            <button class="admin-btn secondary admin-mobile-menu" type="button" data-open-side aria-label="Buka menu admin">Menu</button>
        </div>
        <div class="save-status-wrap"><span class="status-dot"></span><span class="status" data-save-status>Siap</span></div>
    </header>

    <section class="panel active" data-panel="dashboard">
        <div class="dashboard-intro admin-card">
            <div><span class="section-kicker">Ringkasan</span><h2>Kelola <?=h($site['brand'])?></h2><p>Kelola produk, kategori, promo, halaman, dan pengaturan website dari satu panel.</p></div>
            <a class="admin-btn secondary" href="../index.php" target="_blank" rel="noopener">Buka website ↗</a>
        </div>
        <div class="admin-grid">
            <div class="stat"><span class="stat-icon">P</span><div><span>Produk</span><strong><?=count($products)?></strong><small>Item katalog</small></div></div>
            <div class="stat"><span class="stat-icon">C</span><div><span>Kategori</span><strong><?=count($cats)?></strong><small>Kategori katalog</small></div></div>
            <div class="stat"><span class="stat-icon">F</span><div><span>Promo</span><strong><?=count($flash['products']??[])?></strong><small>Produk terpilih</small></div></div>
            <div class="stat"><span class="stat-icon">W</span><div><span>Halaman</span><strong><?=count($pages)?></strong><small>Halaman dapat diedit</small></div></div>
        </div>
        <div class="admin-card system-note-card">
            <div class="card-title-row"><div><span class="section-kicker">Pengelolaan Website</span><h2>Semua pengaturan dalam satu panel</h2></div><span class="sync-badge">Aktif</span></div>
            <p>Gunakan menu Produk, Kategori, Promo, Editor Halaman, serta Logo, Warna & WhatsApp untuk memperbarui isi website.</p>
        </div>
    </section>

    <section class="panel" data-panel="produk">
        <div class="section-title product-section-title"><div><span class="section-kicker">Katalog</span><h2>Produk</h2><p>Edit produk yang ada atau tambahkan produk baru ke katalog.</p></div><button class="admin-btn primary add-product-cta" type="button" data-add-product><span class="plus-mark" aria-hidden="true">+</span> Tambah Produk</button></div>
        <div class="device-editor produk-editor">
            <div class="admin-card list-card">
                <div class="card-title-row"><div><h2>Daftar Produk</h2><p><?=count($products)?> produk</p></div><input class="compact-search" type="search" placeholder="Cari produk…" data-product-search></div>
                <div class="product-list" data-product-list>
                <?php foreach($products as $p):?>
                    <button type="button" class="product-row" data-product-row data-edit-product='<?=h(json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?>' data-search-text="<?=h(strtolower(($p['title']??'').' '.($p['brand']??'').' '.($p['sku']??'')))?>">
                        <?php if(!empty($p['image'])):?><img src="../<?=h($p['image'])?>" alt=""><?php else:?><span class="admin-product-noimage">Tanpa foto</span><?php endif;?>
                        <span class="product-row-copy"><strong><?=h($p['title'])?></strong><span><?=h($p['brand'])?> · <?=money($p['price'])?></span></span>
                        <span class="product-edit-action">Edit <b>→</b></span>
                    </button>
                <?php endforeach;?>
                </div>
            </div>
            <div class="admin-card editor-card">
                <div class="card-title-row"><div><span class="section-kicker">Editor Produk</span><h2 data-product-form-title>Tambah Produk</h2><p data-product-mode-copy>Buat produk baru dan publikasikan ke seluruh website.</p></div><span class="editor-badge" data-product-mode-badge>Produk baru</span></div>
                <div class="product-form-message" data-product-form-message hidden></div>
                <form class="admin-form" data-product-form>
                    <input type="hidden" name="id">
                    <div class="form-grid">
                        <label>Judul Produk<input name="title" required></label>
                        <label>Slug<input name="slug"></label>
                        <label>Merek<input name="brand" list="brands"></label>
                        <label>Kategori<select name="category_slug"><?php foreach($cats as $c):?><option value="<?=h($c['slug'])?>"><?=h($c['name'])?></option><?php endforeach;?></select></label>
                        <label>Harga (Rp)<input name="price" type="number" min="0" step="1"></label>
                        <div class="full wholesale-editor" data-wholesale-product><strong>Harga grosir produk (opsional)</strong><p class="help" data-wholesale-help>Isi keduanya untuk memberi potongan berdasarkan jumlah. Untuk produk bervarian, atur harga grosir pada setiap varian di bawah.</p><div class="form-grid"><label>Minimal pembelian<input name="wholesale_min" type="number" min="2" max="1000000" step="1" placeholder="Contoh: 10"></label><label>Harga satuan grosir (Rp)<input name="wholesale_price" type="number" min="1" max="1000000000" step="1" placeholder="Contoh: 11500"></label></div></div>
                        <label>Stok produk<input name="stock" type="number" min="0" max="1000000" value="0"><span class="help">Untuk produk tanpa varian. Stok tiap varian diatur di bawah.</span></label><label>Berat / kemasan<input name="weight"></label>
                        <label>SKU<input name="sku"></label>
                        <label class="full">Deskripsi Singkat <span class="label-note">Ditampilkan Maksimal 2 baris pada kartu</span><textarea name="short_description"></textarea></label>
                        <label class="full">Deskripsi Produk<textarea name="description"></textarea></label>
                        <div class="full variant-editor"><div class="card-title-row"><div><strong>Varian Produk</strong><p>Nama pilihan, harga normal, stok, dan harga grosir opsional setiap varian.</p></div><button class="admin-btn secondary" type="button" data-add-variant>+ Varian</button></div><div data-variant-list></div></div><label class="full">Spesifikasi tambahan<textarea name="specs_text" placeholder="Kapasitas: 25–90 L"></textarea><span class="help">Satu keterangan per baris, misalnya Kapasitas: 25–90 L.</span></label>
                        <div class="full"><input type="hidden" name="image"><input type="hidden" name="images_json"><strong>Galeri Produk</strong><div class="product-gallery-editor" data-gallery-list></div><span class="help">Foto pertama menjadi foto utama. Unggah gambar dari perangkat melalui tombol di bawah.</span></div>
                        <label>Judul meta<input name="meta_title"></label>
                        <label>Deskripsi meta<input name="meta_description"></label>
                    </div>
                    <div class="toggle-grid">
                        <label class="check-control"><input name="featured" type="checkbox" value="1"><span><strong>Produk Unggulan</strong><small>Dapat ditampilkan sebagai produk unggulan.</small></span></label>
                        <label class="check-control"><input name="active" type="checkbox" value="1" checked><span><strong>Aktif</strong><small>Tampil di katalog publik.</small></span></label>
                    </div>
                    <div class="admin-actions sticky-actions"><button class="admin-btn primary" data-save-product>Tambah Produk</button><button type="button" class="admin-btn secondary" data-reset-product>Kosongkan Form</button><button type="button" class="admin-btn danger" data-delete-product hidden>Hapus Produk</button></div>
                </form>
                <form class="admin-form upload-box" data-upload-form>
                    <label>Upload foto produk → WebP<input type="file" name="image" accept="image/jpeg,image/png,image/webp" multiple></label>
                    <button class="admin-btn secondary">Upload Gambar</button>
                    <div class="help">Maksimal 5 foto per produk, termasuk foto utama. Pilih beberapa foto sekaligus; gambar otomatis dikonversi dan dikompres ke WebP.</div>
                </form>
            </div>
        </div>
        <datalist id="brands"><?php foreach($brands as $b):?><option value="<?=h($b)?>"><?php endforeach;?></datalist>
    </section>

    <section class="panel" data-panel="categories">
        <div class="section-title"><div><span class="section-kicker">Katalog</span><h2>Kategori</h2><p>Kelola nama kategori, slug, deskripsi, dan gambar kategori.</p></div></div>
        <div class="admin-card">
            <div class="category-head-row" aria-hidden="true"><span>Status</span><span>Nama</span><span>Slug</span><span>Deskripsi</span><span>Gambar</span></div>
            <div class="category-list" data-category-list>
            <?php foreach($cats as $c):
                $catImage=(string)($c['image']??'');
                $catPreview=preg_match('#^https?://#i',$catImage)?$catImage:($catImage!==''?'../'.$catImage:'');
            ?>
                <div class="category-item" data-previous-slug="<?=h($c['slug'])?>">
                    <label class="category-active"><input type="checkbox" data-c-active <?=$c['active']?'checked':''?>><span>Aktif</span></label>
                    <label><span class="mobile-field-label">Nama</span><input data-c-name value="<?=h($c['name'])?>"></label>
                    <label><span class="mobile-field-label">Slug</span><input data-c-slug value="<?=h($c['slug'])?>"></label>
                    <label><span class="mobile-field-label">Deskripsi</span><input data-c-desc value="<?=h($c['description'])?>"></label>
                    <div class="category-image-field">
                        <span class="mobile-field-label">Gambar</span>
                        <input type="hidden" data-c-image value="<?=h($catImage)?>">
                        <div class="category-image-control">
                            <span class="category-image-preview<?=$catPreview===''?' is-empty':''?>" data-c-preview>
                                <?php if($catPreview!==''):?><img src="<?=h($catPreview)?>" alt="Preview <?=h($c['name'])?>"><?php else:?><span>Belum ada gambar</span><?php endif;?>
                            </span>
                            <label class="category-upload-btn">Pilih Gambar<input type="file" data-c-upload accept="image/jpeg,image/png,image/webp"></label>
                            <small data-c-upload-status>Pilih gambar dari perangkat</small>
                        </div>
                    </div>
                    <button class="admin-btn danger category-delete" type="button" data-delete-category>Hapus kategori</button>
                </div>
            <?php endforeach;?>
            </div>
            <div class="admin-actions category-actions"><button class="admin-btn secondary" type="button" data-add-category>+ Tambah Kategori</button><button class="admin-btn primary" type="button" data-save-categories>Simpan Kategori</button></div>
        </div>
    </section>

    <section class="panel" data-panel="flash">
        <div class="section-title"><div><span class="section-kicker">Promosi</span><h2>Flash Sale</h2><p>Atur jadwal, harga, dan stok untuk maksimal 7 produk.</p></div></div>
        <div class="admin-card">
            <form class="admin-form" data-flash-form>
                <label class="check-control standalone"><input type="checkbox" name="active" <?=!empty($flash['active'])?'checked':''?>><span><strong>Aktifkan Flash Sale</strong><small>Harga promo berlaku selama jadwal berjalan dan sisa stok tersedia.</small></span></label>
                <div class="form-grid flash-head-fields"><label>Judul<input name="heading" value="<?=h($flash['heading']??'Flash Sale')?>" maxlength="120"></label><label>Subjudul<textarea name="subheading" maxlength="500"><?=h($flash['subheading']??'')?></textarea></label><label>Mulai (WIB, opsional)<input name="starts_at" type="datetime-local"><span class="help">Kosongkan untuk langsung dimulai setelah diaktifkan.</span></label><label>Selesai (WIB)<input name="ends_at" type="datetime-local"><span class="help">Countdown mengikuti waktu ini. Promo berakhir otomatis.</span></label></div>
                <div class="flash-toolbar"><div><strong>Pilih produk</strong><span data-flash-count><?=count($flash['products']??[])?> / 7 dipilih</span></div><input class="compact-search" type="search" placeholder="Cari produk…" data-flash-search aria-label="Cari produk flash sale"></div>
                <div class="flash-list" data-flash-list>
                <?php foreach($products as $p):$offer=$flash['offers'][$p['slug']]??[];$selected=in_array($p['slug'],$flash['products']??[],true);$normal=!empty($p['variants'])?min(array_column($p['variants'],'price')):$p['price'];$enabled=($p['active']??true)!==false;?>
                    <div class="flash-item" data-flash-row data-search-text="<?=h(strtolower(($p['title']??'').' '.($p['brand']??'').' '.($p['sku']??'')))?>">
                        <input type="checkbox" name="flash_product" value="<?=h($p['slug'])?>" <?=$selected?'checked':''?> <?=!$enabled&&!$selected?'disabled':''?> data-inactive="<?=$enabled?'false':'true'?>" aria-label="Pilih <?=h($p['title'])?>">
                        <span class="flash-product-name"><strong><?=h($p['title'])?></strong><small><?=money((int)$normal)?><?=$enabled?'':' · Tidak aktif'?><?=!empty($p['variants'])?' · Diskon disesuaikan dengan harga normal tiap varian':''?></small></span>
                        <div class="flash-offer-fields">
                            <label><span>Harga flash sale (Rp)</span><input type="number" step="1" min="1" max="<?=$normal?>" data-flash-price value="<?=h((string)($offer['price']??''))?>" aria-label="Harga flash sale <?=h($p['title'])?>"></label>
                            <label><span>Stok awal</span><input type="number" step="1" min="1" max="1000000" data-flash-total value="<?=h((string)($offer['total_stock']??''))?>" aria-label="Stok awal <?=h($p['title'])?>"></label>
                            <label><span>Sisa stok</span><input type="number" step="1" min="0" max="1000000" data-flash-remaining value="<?=h((string)($offer['remaining_stock']??''))?>" aria-label="Sisa stok <?=h($p['title'])?>"></label>
                        </div>
                    </div>
                <?php endforeach;?>
                </div>
                <p class="help">Perbarui sisa stok setelah pesanan dikonfirmasi lewat WhatsApp. Stok tidak berkurang hanya karena produk masuk ke keranjang.</p>
                <div class="admin-alert error" data-flash-error role="alert" hidden></div>
                <div class="flash-save-bar"><span class="help">Jadwal menggunakan WIB (Asia/Jakarta).</span><button class="admin-btn primary">Simpan Flash Sale</button></div>
            </form>
        </div>
    </section>

    <section class="panel" data-panel="pages">
        <div class="section-title"><div><span class="section-kicker">Konten</span><h2>Editor Halaman</h2><p>Edit konten halaman dan lihat hasilnya melalui preview responsif.</p></div></div>
        <div class="device-editor page-editor-layout">
            <div class="admin-card editor-card">
                <div class="card-title-row"><div><h2>Konten Halaman</h2><p>Atur teks halaman dan pratinjau perubahan sebelum disimpan.</p></div><span class="editor-badge">Konten</span></div>
                <form class="admin-form" data-pages-form>
                    <label>Pilih halaman<select data-page-select><?php foreach($pages as $key=>$pg):?><option value="<?=h($key)?>"><?=h(ucwords(str_replace('-',' ',$key)))?></option><?php endforeach;?></select></label>
                    <div class="page-editor-note" data-page-editor-note></div>
                    <label>Judul Halaman<textarea data-page-title rows="2"></textarea></label>
                    <label>Deskripsi Halaman<textarea data-page-description></textarea></label>
                    <label data-page-body-wrap hidden>Isi / konten tambahan<textarea data-page-body></textarea><span class="help">Teks biasa. Pergantian baris dipertahankan pada halaman yang mendukung.</span></label>
                    <div class="page-extra-fields" data-page-extra></div>
                    <div class="locked"><strong>Catatan:</strong> Produk, kategori, dan promo dikelola melalui menu masing-masing agar lebih mudah diatur.</div>
                    <div class="admin-actions sticky-actions"><button class="admin-btn primary">Simpan Konten Halaman</button></div>
                </form>
            </div>
            <div class="preview-wrap">
                <div class="preview-toolbar"><div class="device-switch"><button type="button" class="admin-btn secondary active" data-preview-device="desktop">Desktop</button><button type="button" class="admin-btn secondary" data-preview-device="mobile">Mobile</button></div><a class="admin-btn secondary" data-open-preview href="../index.php" target="_blank" rel="noopener">Buka halaman ↗</a></div>
                <div class="preview-status"><span class="status-dot"></span><span>Preview website aktual</span><small>Perubahan draf ditampilkan langsung pada field halaman yang dapat diedit.</small></div>
                <div class="preview-stage" data-preview-stage><div class="preview-canvas" data-preview-canvas><iframe title="Website preview" data-site-preview src="../index.php"></iframe></div></div>
            </div>
        </div>
    </section>

    <section class="panel" data-panel="settings">
        <div class="section-title"><div><span class="section-kicker">Merek & Kontak</span><h2>Pengaturan Global</h2><p>Nilai ini digunakan kembali di seluruh website.</p></div></div>
        <div class="admin-card editor-card">
            <form class="admin-form" data-site-form>
                <div class="form-grid">
                    <label>Nama merek<input name="brand" value="<?=h($site['brand'])?>"></label>
                    <label>Powered by<input name="powered_by" value="<?=h($site['powered_by'])?>"></label>
                    <label>Logo terpilih<input name="logo" readonly value="<?=h($site['logo'])?>"><span class="help">Logo digunakan di header dan footer.</span></label>
                    <label>URL Kanonis Website<input name="canonical_base" type="url" placeholder="https://example.com" value="<?=h($site['seo']['canonical_base']??'')?>"><span class="help">Dipakai untuk URL kanonis, Open Graph, dan sitemap.</span></label>
                    <label>Warna utama<input name="primary" type="color" value="<?=h($site['colors']['primary'])?>"></label>
                    <label>Warna gelap<input name="navy" type="color" value="<?=h($site['colors']['navy'])?>"></label>
                    <label>Warna aksen<input name="accent" type="color" value="<?=h($site['colors']['accent'])?>"></label>
                    <label class="full whatsapp-setting">Nomor WhatsApp utama<input name="whatsapp" type="tel" inputmode="tel" required maxlength="22" placeholder="0812… atau 62812…" value="<?=h($site['contact']['whatsapp'])?>"><span class="help">Digunakan oleh seluruh tombol chat, footer, halaman kontak, detail produk, dan checkout. Format 08 otomatis diubah menjadi 628.</span></label>
                    <label>Email<input name="email" value="<?=h($site['contact']['email'])?>"></label>
                    <label>Minimal gratis ongkir (Rp)<input name="free_minimum" type="number" min="0" value="<?=h((string)($site['shipping']['free_minimum']??200000))?>"></label><label>Jam operasional<input name="hours" value="<?=h($site['contact']['hours'])?>"></label>
                    <label class="full">Alamat<textarea name="address"><?=h($site['contact']['address'])?></textarea></label>
                    <label>Username Instagram<input name="instagram" value="<?=h($site['social']['instagram']??'')?>"></label>
                    <label>Instagram URL<input name="instagram_url" value="<?=h($site['social']['instagram_url']??'')?>"></label>
                    <label>Username TikTok<input name="tiktok" value="<?=h($site['social']['tiktok']??'')?>"></label>
                    <label>TikTok URL<input name="tiktok_url" value="<?=h($site['social']['tiktok_url']??'')?>"></label>
                </div>
                <div class="admin-actions sticky-actions"><button class="admin-btn primary">Simpan Pengaturan</button></div>
            </form>
            <form class="admin-form upload-box" data-logo-upload>
                <label>Upload logo baru → WebP<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                <button class="admin-btn secondary">Upload & gunakan sebagai logo</button>
                <div class="help">Gambar dari perangkat dikompres ke WebP sebelum disimpan.</div>
            </form>
        </div>
    </section>
<section class="panel" data-panel="security"><div class="section-title"><div><span class="section-kicker">Akun</span><h2>Akses Admin</h2><p>Perbarui password akun admin.</p></div></div><div class="admin-card"><form class="admin-form" data-password-form><label>Password saat ini<input name="current" type="password" required autocomplete="current-password"></label><label>Password baru<input name="new_password" type="password" required minlength="10" autocomplete="new-password"><span class="help">Minimal 10 karakter.</span></label><label>Ulangi password baru<input name="confirm_password" type="password" required minlength="10" autocomplete="new-password"></label><button class="admin-btn primary">Simpan Password</button></form></div></section>
</main>
</div>
<div class="admin-toast-stack" data-admin-toasts aria-live="polite" aria-atomic="true"></div>
<script>window.ADMIN_DATA=<?=seo_json(['site'=>$site,'pages'=>$pages,'categories'=>$cats,'products'=>$products,'flash'=>$flash,'csrf'=>csrf(),'pageUrls'=>$pageUrls])?>;</script>
<script src="assets/admin.js?v=<?=filemtime(__DIR__.'/assets/admin.js')?>"></script>
</body>
</html>
