<?php
/**
 * HOME THEME 1 — "WARM EARTH & IMPACT"  v3.0
 * Exact match to ngo_mega_preview.html
 * Palette: Saffron #E8610A + Forest #1B5E30 + Warm Cream
 * Fonts: Playfair Display + DM Sans
 *
 * Required vars: $slides_raw, $slides_json, $about_desc, $about_image,
 *   $totalDonation, $activeVolunteers, $ongoingProjects, $projects,
 *   $galleryImages, $sponsors, $birthdayMembers, $events, $settings
 */
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{--s:#F0A010;--sl:#d48b0a;--g:#1070B0;--gl:#20A0D0;--cr:#fffcf5;--ch:#1F2937;--wg:#fffcf5;--mg:#E5E7EB}
body{font-family:'DM Sans',sans-serif;background:var(--cr);color:var(--ch);overflow-x:hidden;line-height:1.7}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
a{text-decoration:none}img{max-width:100%;display:block}

/* NAV */
.nav{position:sticky;top:52px;z-index:100;background:rgba(253,246,236,.95);backdrop-filter:blur(12px);border-bottom:1px solid rgba(232,97,10,.12);padding:0 3rem;display:flex;align-items:center;justify-content:space-between;height:70px}
.nav-logo{font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;color:var(--g)}
.nav-logo span{color:var(--s)}
.nav-links{display:flex;gap:2.5rem;list-style:none}
.nav-links a{font-size:.88rem;font-weight:500;color:#555;transition:color .2s}
.nav-links a:hover{color:var(--s)}
.nav-donate{background:var(--s);color:#fff;padding:.55rem 1.6rem;border-radius:4px;font-size:.88rem;font-weight:600;transition:background .2s}
.nav-donate:hover{background:var(--sl)}

/* HERO */
.hero{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--ch);text-align:center}
.hero-bg{position:absolute;inset:0;background:linear-gradient(160deg,#0d0d0d 0%,#1a3020 60%,#2d1a0a 100%)}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(26,26,26,.6) 0%,rgba(26,26,26,.82) 70%)}
.hero-particles{position:absolute;inset:0;overflow:hidden;pointer-events:none}
.particle{position:absolute;width:3px;height:3px;background:var(--sl);border-radius:50%;opacity:.4;animation:float1 var(--dur,8s) ease-in-out infinite;animation-delay:var(--del,0s)}
@keyframes float1{0%,100%{transform:translateY(0) translateX(0);opacity:.4}50%{transform:translateY(-60px) translateX(20px);opacity:.8}}
.hero-content{position:relative;z-index:5;padding:2rem;max-width:950px}
.hero-pill{display:inline-flex;align-items:center;gap:.5rem;background:rgba(232,97,10,.2);border:1px solid rgba(232,97,10,.4);color:var(--sl);font-size:.78rem;font-weight:600;letter-spacing:.15em;text-transform:uppercase;padding:.5rem 1.4rem;border-radius:50px;margin-bottom:2rem;backdrop-filter:blur(8px)}
.hero-h1{font-family:'Playfair Display',serif;font-size:clamp(3rem,7.5vw,6.5rem);font-weight:900;color:#fff;line-height:1.08;margin-bottom:1.8rem}
.hero-h1 em{font-style:normal;background:linear-gradient(135deg,var(--sl),#FFD580);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero-sub{color:rgba(255,255,255,.72);font-size:1.15rem;line-height:1.85;max-width:600px;margin:0 auto 3rem}
.hero-btns{display:flex;gap:1.2rem;justify-content:center;flex-wrap:wrap}
.btn-s{background:linear-gradient(135deg,var(--s),var(--sl));color:#fff;padding:.9rem 2.5rem;border-radius:6px;font-weight:700;font-size:1rem;transition:all .3s;box-shadow:0 8px 30px rgba(232,97,10,.35)}
.btn-s:hover{transform:translateY(-4px);box-shadow:0 16px 50px rgba(232,97,10,.5)}
.btn-g{background:linear-gradient(135deg,var(--g),var(--gl));color:#fff;padding:.9rem 2.5rem;border-radius:6px;font-weight:700;font-size:1rem;transition:all .3s;box-shadow:0 8px 30px rgba(27,94,48,.3)}
.btn-g:hover{transform:translateY(-4px)}
.btn-ghost{border:2px solid rgba(255,255,255,.5);color:#fff;padding:.9rem 2.5rem;border-radius:6px;font-weight:700;font-size:1rem;transition:all .3s;backdrop-filter:blur(6px)}
.btn-ghost:hover{background:rgba(255,255,255,.12);border-color:#fff}
.hero-scroll{position:absolute;bottom:2.5rem;left:50%;transform:translateX(-50%);color:rgba(255,255,255,.4);font-size:.75rem;letter-spacing:.15em;text-transform:uppercase;display:flex;flex-direction:column;align-items:center;gap:.6rem;animation:bounce .6s ease-in-out infinite alternate}
.hero-scroll::before{content:'';width:1px;height:40px;background:linear-gradient(to bottom,var(--sl),transparent)}
@keyframes bounce{from{transform:translateX(-50%) translateY(0)}to{transform:translateX(-50%) translateY(-6px)}}

/* TICKER */
.ticker{background:var(--s);overflow:hidden;padding:.65rem 0}
.ttrack{display:flex;white-space:nowrap;animation:mq 40s linear infinite}
.ttrack span{color:#fff;font-size:.82rem;font-weight:600;letter-spacing:.05em;padding:0 2.5rem}
.ttrack .sep{color:rgba(255,255,255,.4)}
@keyframes mq{from{transform:translateX(0)}to{transform:translateX(-50%)}}

/* IMPACT NUMBERS */
.impact{padding:5rem 3rem;background:var(--g)}
.impact-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:1px;border:1px solid rgba(255,255,255,.1)}
.impact-item{padding:3rem 2rem;text-align:center;border-right:1px solid rgba(255,255,255,.1)}
.impact-item:last-child{border-right:none}
.impact-icon{font-size:2rem;margin-bottom:1rem;opacity:.8}
.impact-num{font-family:'Playfair Display',serif;font-size:3.5rem;font-weight:700;color:#fff;line-height:1}
.impact-num sup{font-size:1.4rem;vertical-align:super}
.impact-label{color:rgba(255,255,255,.55);font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;margin-top:.5rem}

/* ABOUT */
.about{padding:7rem 3rem;background:var(--cr)}
.about-inner{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:6rem;align-items:center}
.about-img-stack{position:relative;height:500px}
.about-img-main{position:absolute;top:0;left:0;width:80%;height:420px;object-fit:cover;border-radius:4px;box-shadow:0 25px 60px rgba(0,0,0,.18)}
.about-img-accent{position:absolute;bottom:0;right:0;width:55%;height:260px;object-fit:cover;border-radius:4px;box-shadow:0 20px 50px rgba(0,0,0,.22);border:5px solid var(--cr)}
.about-badge{position:absolute;top:50%;left:65%;transform:translateY(-50%);background:var(--s);color:#fff;padding:1.2rem 1.5rem;border-radius:4px;text-align:center;box-shadow:0 12px 35px rgba(232,97,10,.5);z-index:3}
.about-badge strong{display:block;font-family:'Playfair Display',serif;font-size:2.2rem;line-height:1}
.about-badge span{font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;opacity:.9}
.sec-tag{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem}
.sec-line{width:40px;height:3px;background:var(--s)}
.sec-tag p{font-size:.72rem;font-weight:600;letter-spacing:.15em;text-transform:uppercase;color:var(--s)}
.sec-title{font-family:'Playfair Display',serif;font-size:clamp(2.2rem,4vw,3.2rem);font-weight:700;color:var(--ch);line-height:1.15;margin-bottom:1.5rem}
.about-text{color:#666;line-height:1.85;margin-bottom:2rem}
.about-checks{list-style:none;margin-bottom:2.5rem;display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.about-checks li{display:flex;align-items:center;gap:.6rem;font-size:.88rem;color:#555}
.about-checks li::before{content:'✓';color:var(--s);font-weight:700;flex-shrink:0}

/* FEATURES GRID */
.features{padding:6rem 3rem;background:var(--wg)}
.features-inner{max-width:1200px;margin:0 auto}
.feat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-top:3.5rem}
.feat-card{background:#fff;border-radius:6px;padding:2.5rem 2rem;transition:all .35s;border-bottom:3px solid transparent;box-shadow:0 3px 15px rgba(0,0,0,.06)}
.feat-card:hover{transform:translateY(-8px);border-bottom-color:var(--s);box-shadow:0 20px 50px rgba(0,0,0,.12)}
.feat-icon{font-size:2.5rem;margin-bottom:1.2rem}
.feat-title{font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:700;margin-bottom:.75rem;color:var(--ch)}
.feat-desc{font-size:.85rem;color:#888;line-height:1.7}
.feat-link{display:inline-block;margin-top:1.2rem;font-size:.8rem;font-weight:600;color:var(--s);letter-spacing:.05em}
.feat-link:hover{color:var(--sl)}

/* PROJECTS */
.projects{padding:6rem 3rem;background:var(--cr)}
.projects-inner{max-width:1200px;margin:0 auto}
.proj-header{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:3.5rem;flex-wrap:wrap;gap:1.5rem}
.proj-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2rem}
.proj-card{background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06);transition:.35s;display:flex;flex-direction:column}
.proj-card:hover{transform:translateY(-8px);box-shadow:0 20px 50px rgba(0,0,0,.12)}
.proj-img-wrap{position:relative;height:220px;overflow:hidden}
.proj-img-wrap img{width:100%;height:100%;object-fit:cover;transition:.5s}
.proj-card:hover .proj-img-wrap img{transform:scale(1.08)}
.proj-category-badge{position:absolute;top:.8rem;left:.8rem;background:var(--g);color:#fff;font-size:.65rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:.3rem .75rem;border-radius:2px}
.proj-body{padding:1.6rem;flex:1;display:flex;flex-direction:column}
.proj-title{font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:700;margin-bottom:.6rem;line-height:1.3;color:var(--ch)}
.proj-desc{font-size:.83rem;color:#888;line-height:1.65;flex:1;margin-bottom:1.2rem}
.pbar-wrap{margin-bottom:1.2rem}
.pbar{height:5px;background:#eee;border-radius:3px;overflow:hidden;margin:.5rem 0}
.pfill{height:100%;background:linear-gradient(90deg,var(--g),var(--s));border-radius:3px}
.pmeta{display:flex;justify-content:space-between;font-size:.75rem;color:#aaa}
.proj-btns{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.btn-outline-s{border:1.5px solid var(--s);color:var(--s);padding:.55rem;border-radius:4px;font-size:.82rem;font-weight:600;text-align:center;transition:.2s}
.btn-outline-s:hover{background:var(--s);color:#fff}
.btn-fill-s{background:var(--s);color:#fff;padding:.55rem;border-radius:4px;font-size:.82rem;font-weight:600;text-align:center;transition:.2s}
.btn-fill-s:hover{background:var(--sl)}

/* HOW IT WORKS */
.hiw{padding:6rem 3rem;background:var(--ch);position:relative;overflow:hidden}
.hiw::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 30% 50%,rgba(27,94,48,.3),transparent 60%)}
.hiw-inner{max-width:1100px;margin:0 auto;position:relative;z-index:1}
.hiw-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:2rem;margin-top:4rem;position:relative}
.hiw-steps::before{content:'';position:absolute;top:35px;left:15%;right:15%;height:2px;background:linear-gradient(90deg,var(--s),var(--g));opacity:.4}
.hiw-step{text-align:center;padding:2.5rem 1.5rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:6px;backdrop-filter:blur(10px);transition:.3s}
.hiw-step:hover{background:rgba(255,255,255,.1);transform:translateY(-6px)}
.hiw-step-num{width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,var(--s),var(--sl));display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700;color:#fff;box-shadow:0 8px 25px rgba(232,97,10,.4)}
.hiw-step-title{font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:.6rem}
.hiw-step-desc{font-size:.82rem;color:rgba(255,255,255,.55);line-height:1.7}

/* GALLERY STRIP */
.gallery{padding:5rem 0;background:var(--wg);overflow:hidden}
.gallery-inner{max-width:1200px;margin:0 auto;padding:0 3rem}
.gallery-strip{display:flex;gap:1rem;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;margin-top:3rem;padding-bottom:1rem}
.gallery-strip::-webkit-scrollbar{display:none}
.gallery-item{flex:0 0 280px;height:200px;border-radius:6px;overflow:hidden;scroll-snap-align:start;position:relative;cursor:pointer}
.gallery-item img{width:100%;height:100%;object-fit:cover;transition:.5s}
.gallery-item:hover img{transform:scale(1.1)}
.gallery-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(26,26,26,.7),transparent);opacity:0;transition:.3s;display:flex;align-items:flex-end;padding:1rem}
.gallery-item:hover .gallery-overlay{opacity:1}
.gallery-overlay span{color:#fff;font-size:.8rem;font-weight:600}

/* TESTIMONIALS */
.testimonials{padding:6rem 3rem;background:var(--cr)}
.testi-inner{max-width:1100px;margin:0 auto}
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2rem;margin-top:3.5rem}
.testi-card{background:var(--wg);border-radius:6px;padding:2.5rem;position:relative;border-top:3px solid var(--s)}
.testi-quote{font-size:4rem;font-family:'Playfair Display',serif;color:var(--s);line-height:.5;margin-bottom:1.5rem;opacity:.5}
.testi-text{font-size:.9rem;color:#666;line-height:1.8;font-style:italic;margin-bottom:1.5rem}
.testi-author{display:flex;align-items:center;gap:1rem}
.testi-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--s),var(--sl));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.1rem;flex-shrink:0}
.testi-name{font-weight:600;font-size:.88rem;color:var(--ch)}
.testi-role{font-size:.75rem;color:#999}

/* EVENTS */
.events{padding:6rem 3rem;background:var(--wg)}
.events-inner{max-width:1200px;margin:0 auto}
.events-grid{display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-top:3.5rem}
.event-card{background:#fff;border-radius:6px;overflow:hidden;display:flex;gap:0;box-shadow:0 4px 20px rgba(0,0,0,.07);transition:.3s}
.event-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.12)}
.event-date{background:var(--g);color:#fff;padding:1.5rem 1.8rem;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:90px;flex-shrink:0}
.event-day{font-family:'Playfair Display',serif;font-size:2.5rem;font-weight:700;line-height:1}
.event-month{font-size:.7rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;opacity:.8;margin-top:.25rem}
.event-info{padding:1.5rem}
.event-tag{font-size:.65rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--s);margin-bottom:.4rem}
.event-title{font-weight:700;font-size:1rem;color:var(--ch);margin-bottom:.4rem;line-height:1.3}
.event-meta{font-size:.78rem;color:#999;display:flex;align-items:center;gap.5rem}

/* VOLUNTEER CTA STRIP */
.vol-cta{padding:5rem 3rem;background:linear-gradient(135deg,var(--s) 0%,#B34800 100%);position:relative;overflow:hidden}
.vol-cta::before{content:'';position:absolute;right:-100px;top:-100px;width:500px;height:500px;border-radius:50%;background:rgba(255,255,255,.05)}
.vol-cta-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr auto;gap:3rem;align-items:center;position:relative;z-index:1}
.vol-cta h2{font-family:'Playfair Display',serif;font-size:2.5rem;font-weight:700;color:#fff;line-height:1.2}
.vol-cta p{color:rgba(255,255,255,.8);margin-top:.75rem;font-size:1rem;line-height:1.7}
.vol-cta-btns{display:flex;flex-direction:column;gap:1rem;flex-shrink:0}

/* NEWS */
.news{padding:6rem 3rem;background:var(--cr)}
.news-inner{max-width:1200px;margin:0 auto}
.news-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:2rem;margin-top:3.5rem}
.news-big{border-radius:6px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);background:#fff;transition:.3s}
.news-big:hover{transform:translateY(-5px);box-shadow:0 20px 50px rgba(0,0,0,.12)}
.news-big-img{height:280px;overflow:hidden}
.news-big-img img{width:100%;height:100%;object-fit:cover;transition:.5s}
.news-big:hover .news-big-img img{transform:scale(1.06)}
.news-big-body{padding:2rem}
.news-tag{font-size:.65rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--s);margin-bottom:.5rem}
.news-title{font-family:'Playfair Display',serif;font-size:1.25rem;font-weight:700;margin-bottom:.75rem;line-height:1.3;color:var(--ch)}
.news-excerpt{font-size:.85rem;color:#888;line-height:1.7}
.news-small{display:flex;flex-direction:column;gap:1.5rem}
.news-mini{background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 3px 15px rgba(0,0,0,.07);transition:.3s}
.news-mini:hover{transform:translateY(-3px)}
.news-mini img{width:100%;height:140px;object-fit:cover}
.news-mini-body{padding:1.2rem}
.news-mini .news-title{font-size:1rem}

/* CTA BAND */
.cta{padding:7rem 3rem;background:var(--g);position:relative;overflow:hidden;text-align:center}
.cta::before{content:'';position:absolute;top:-150px;right:-150px;width:600px;height:600px;border-radius:50%;background:rgba(232,97,10,.12)}
.cta::after{content:'';position:absolute;bottom:-100px;left:-100px;width:400px;height:400px;border-radius:50%;background:rgba(255,255,255,.04)}
.cta-inner{position:relative;z-index:1;max-width:700px;margin:0 auto}
.cta-title{font-family:'Playfair Display',serif;font-size:clamp(2.5rem,5vw,4rem);color:#fff;margin-bottom:1.2rem;line-height:1.15}
.cta-sub{color:rgba(255,255,255,.72);font-size:1.1rem;line-height:1.8;margin-bottom:3rem}
.cta-btns{display:flex;gap:1.2rem;justify-content:center;flex-wrap:wrap}

/* PARTNERS */
.partners{padding:4rem 3rem;background:var(--cr);text-align:center}
.partners h3{font-size:1rem;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#bbb;margin-bottom:2.5rem}
.p-track{overflow:hidden;mask-image:linear-gradient(to right,transparent,black 12%,black 88%,transparent)}
.p-row{display:flex;gap:4rem;animation:mq 35s linear infinite;width:max-content}
.p-logo{height:45px;width:120px;background:var(--mg);border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:.7rem;font-weight:600;letter-spacing:.1em}

/* FOOTER NOTE */
.foot-note{background:var(--ch);padding:2.5rem 3rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
.foot-note p{font-size:.8rem;color:rgba(255,255,255,.4)}
.foot-note a{font-size:.8rem;color:var(--sl)}

/* ════════════════════════════════════════
   THEME 2 — BOLD IMPACT
   Syne + Outfit
   Teal / Coral / Ivory
════════════════════════════════════════ */

.hero-slides{position:absolute;inset:0}.t1-slide{position:absolute;inset:0;opacity:0;transition:opacity 1.4s ease}.t1-slide.active{opacity:1}.t1-slide img{width:100%;height:100%;object-fit:cover}.hero-dots{position:absolute;bottom:2.5rem;left:50%;transform:translateX(-50%);display:flex;gap:.6rem;z-index:10}.hero-dot{width:9px;height:9px;border-radius:50%;background:rgba(255,255,255,.35);cursor:pointer;transition:all .35s}.hero-dot.active{background:var(--s);width:28px;border-radius:4px}.p-logo img{height:45px;object-fit:contain;filter:grayscale(1);opacity:.5;transition:all .3s}.p-logo{display:flex;align-items:center;justify-content:center;min-width:120px}
</style>

<?php if(!empty($birthdayMembers)): ?>
<div style="background:linear-gradient(90deg,#FFF3CD,#FFF8E7);border-bottom:3px solid #FBBF24;padding:.8rem 2rem;text-align:center">
  <span style="font-size:.92rem;color:#92400E">🎂 Happy Birthday: <strong><?php echo htmlspecialchars(implode(', ', array_column($birthdayMembers,'full_name'))); ?></strong> 🎉</span>
</div>
<?php endif; ?>

<!-- HERO SLIDER -->
<div class="hero" style="position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--ch);text-align:center"
  x-data="{active:0,slides:[],init(){this.slides=JSON.parse('<?php echo $slides_json; ?>');if(this.slides.length>1)setInterval(()=>{this.active=(this.active+1)%this.slides.length},5800);}}"
>
  <div class="hero-slides">
    <?php foreach($slides_raw as $si=>$ss): ?>
    <div class="t1-slide" :class="{active: active === <?php echo $si; ?>}">
      <img src="<?php echo htmlspecialchars($ss['image_path']); ?>" alt="" loading="<?php echo $si===0?'eager':'lazy'; ?>">
    </div>
    <?php endforeach; ?>
    <?php if(empty($slides_raw)): ?><div class="t1-slide active"><img src="https://placehold.co/1920x1080/1B5E30/ffffff?text=Together+We+Build" alt="" loading="eager"></div><?php endif; ?>
  </div>
  <div class="hero-overlay"></div>
  <div class="hero-particles">
    <div class="particle" style="left:10%;top:20%;--dur:9s;--del:0s"></div>
    <div class="particle" style="left:25%;top:60%;--dur:7s;--del:1s"></div>
    <div class="particle" style="left:60%;top:30%;--dur:11s;--del:2s"></div>
    <div class="particle" style="left:80%;top:70%;--dur:8s;--del:.5s"></div>
    <div class="particle" style="left:45%;top:45%;--dur:10s;--del:1.5s"></div>
  </div>
  <div class="hero-content">
    <div class="hero-pill">🌿 Serving Communities Since 2009</div>
    <h1 class="hero-h1">Together We <em>Transform</em><br>Lives Forever</h1>
    <p class="hero-sub">From remote villages to urban communities — we fund education, healthcare, and sustainable livelihoods. Every rupee you give creates lasting change.</p>
    <div class="hero-btns">
      <a href="donate" class="btn-s">💛 Donate Now</a>
      <a href="projects" class="btn-g">Explore Projects</a>
      <a href="volunteer-register" class="btn-ghost">Volunteer →</a>
    </div>
  </div>
  <div class="hero-dots">
    <?php foreach($slides_raw as $si=>$ss): ?>
    <div class="hero-dot" :class="{active: active === <?php echo $si; ?>}" @click="active = <?php echo $si; ?>"></div>
    <?php endforeach; ?>
  </div>
  <div class="hero-scroll">Scroll</div>
</div>


  <!-- NAV -->
  

  <!-- HERO -->
  <!-- <div class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-particles">
      <div class="particle" style="left:10%;top:20%;--dur:9s;--del:0s"></div>
      <div class="particle" style="left:25%;top:60%;--dur:7s;--del:1s"></div>
      <div class="particle" style="left:60%;top:30%;--dur:11s;--del:2s"></div>
      <div class="particle" style="left:80%;top:70%;--dur:8s;--del:.5s"></div>
      <div class="particle" style="left:45%;top:45%;--dur:10s;--del:1.5s"></div>
    </div>
    <div class="hero-content">
      <div class="hero-pill">🌿 Serving Communities Since 2009</div>
      <h1 class="hero-h1">Together We <em>Transform</em><br>Lives Forever</h1>
      <p class="hero-sub">From remote villages to urban communities — we fund education, healthcare, and sustainable livelihoods. Every rupee you give creates lasting change.</p>
      <div class="hero-btns">
        <a href="donate" class="btn-s">💛 Donate Now</a>
        <a href="projects" class="btn-g">Explore Projects</a>
        <a href="volunteer-register" class="btn-ghost">Volunteer →</a>
      </div>
    </div>
    <div class="hero-scroll">Scroll</div>
  </div> -->

  <!-- TICKER -->
  <div class="ticker">
    <div class="ttrack">
      <span>Transform Lives Today</span><span class="sep">✦</span>
      <span>₹100 = Real Change</span><span class="sep">✦</span>
      <span>5000+ Donors</span><span class="sep">✦</span>
      <span>Certificates & ID Cards</span><span class="sep">✦</span>
      <span>Volunteer Portal Live</span><span class="sep">✦</span>
      <span>80G Tax Receipts</span><span class="sep">✦</span>
      <span>Razorpay Enabled</span><span class="sep">✦</span>
      <span>Transform Lives Today</span><span class="sep">✦</span>
      <span>₹100 = Real Change</span><span class="sep">✦</span>
      <span>5000+ Donors</span><span class="sep">✦</span>
      <span>Certificates & ID Cards</span><span class="sep">✦</span>
      <span>Volunteer Portal Live</span><span class="sep">✦</span>
      <span>80G Tax Receipts</span><span class="sep">✦</span>
      <span>Razorpay Enabled</span><span class="sep">✦</span>
    </div>
  </div>

  <!-- IMPACT NUMBERS -->
  <div class="impact"
    x-data="{d:0,v:0,p:0,l:0,go:false,run(){if(this.go)return;this.go=true;const e=t=>1-Math.pow(1-t,3);const a=(k,end)=>{const dur=2200,s=performance.now();const f=n=>{const pr=Math.min((n-s)/dur,1);this[k]=Math.floor(end*e(pr));if(pr<1)requestAnimationFrame(f);else this[k]=end;};requestAnimationFrame(f);};a('d',<?php echo (int)$totalDonation;?>);a('v',<?php echo (int)$activeVolunteers;?>);a('p',<?php echo (int)$ongoingProjects;?>);a('l',15000);}}"
    x-intersect.once.threshold.0.2="run()">
    <div class="impact-inner">
      <div class="impact-item"><div class="impact-icon">💰</div><div class="impact-num"><sup>₹</sup><span x-text="d.toLocaleString('en-IN')">0</span></div><div class="impact-label">Funds Raised</div></div>
      <div class="impact-item"><div class="impact-icon">🙋</div><div class="impact-num" x-text="v.toLocaleString()">0</div><div class="impact-label">Active Volunteers</div></div>
      <div class="impact-item"><div class="impact-icon">🏗</div><div class="impact-num" x-text="p">0</div><div class="impact-label">Live Projects</div></div>
      <div class="impact-item"><div class="impact-icon">🎓</div><div class="impact-num" x-text="l.toLocaleString()">0</div><div class="impact-label">Lives Touched</div></div>
    </div>
  </div>

  <!-- ABOUT -->
  <div class="about">
    <div class="about-inner">
      <div class="about-img-stack">
        <img class="about-img-main" src="<?php echo $about_image ?: 'https://placehold.co/580x420/1B5E30/ffffff?text=Our+Mission'; ?>" alt="">
        <img class="about-img-accent" src="https://placehold.co/340x260/E8610A/ffffff?text=In+Action" alt="">
        <div class="about-badge"><strong>15+</strong><span>Years of Service</span></div>
      </div>
      <div>
        <div class="sec-tag"><div class="sec-line"></div><p>Who We Are</p></div>
        <h2 class="sec-title">A Journey of Hope, Compassion &amp; Action</h2>
        <p class="about-text"><?php echo mb_substr(strip_tags($about_desc ?? 'We are dedicated to creating lasting change in underserved communities across India. We operate transparent, measurable programs in education, healthcare, and livelihood development.'), 0, 350); ?>...</p>
        <ul class="about-checks">
          <li>80G Tax-Exempt Donations</li>
          <li>Volunteer ID Card System</li>
          <li>Live Project Tracking</li>
          <li>Membership Program</li>
          <li>Event Management</li>
          <li>Online Certificates</li>
          <li>Razorpay Integration</li>
          <li>Admin Dashboard</li>
        </ul>
        <a href="about" class="btn-g">Learn Our Story →</a>
      </div>
    </div>
  </div>

  <!-- FEATURES — What our website has -->
  <div class="features">
    <div class="features-inner">
      <div class="sec-center">
        <div class="sec-tag" style="justify-content:center"><div class="sec-line"></div><p>What We Offer</p><div class="sec-line"></div></div>
        <h2 class="sec-title">Everything You Need,<br>All in One Place</h2>
      </div>
      <div class="feat-grid">
        <div class="feat-card"><div class="feat-icon">💰</div><div class="feat-title">Secure Donations</div><div class="feat-desc">QR code, UPI, bank transfer. Upload payment proof. Get 80G receipt instantly.</div><a class="feat-link" href="#">Donate Now →</a></div>
        <div class="feat-card"><div class="feat-icon">🧑‍🤝‍🧑</div><div class="feat-title">Volunteer Hub</div><div class="feat-desc">Register, get approved, download your ID card with QR verification. Volunteer dashboard included.</div><a class="feat-link" href="#">Join Now →</a></div>
        <div class="feat-card"><div class="feat-icon">🏆</div><div class="feat-title">Membership Portal</div><div class="feat-desc">Designation-based fees, Razorpay payment, referral system, member certificates.</div><a class="feat-link" href="#">Become Member →</a></div>
        <div class="feat-card"><div class="feat-icon">📋</div><div class="feat-title">Live Projects</div><div class="feat-desc">Real-time funding progress, donation targets, campaign galleries. All admin-managed.</div><a class="feat-link" href="#">See Projects →</a></div>
        <div class="feat-card"><div class="feat-icon">📅</div><div class="feat-title">Events & Gallery</div><div class="feat-desc">Upcoming events, photo galleries, video showcases. All editable from admin panel.</div><a class="feat-link" href="#">View Events →</a></div>
        <div class="feat-card"><div class="feat-icon">📜</div><div class="feat-title">Certificates</div><div class="feat-desc">Auto-generated volunteer & member certificates with QR verification codes. PDF download.</div><a class="feat-link" href="#">Certificates →</a></div>
        <div class="feat-card"><div class="feat-icon">📊</div><div class="feat-title">Admin Dashboard</div><div class="feat-desc">Charts, donation analytics, volunteer management, birthday alerts, export CSV/PDF.</div><a class="feat-link" href="#">Admin Panel →</a></div>
        <div class="feat-card"><div class="feat-icon">📱</div><div class="feat-title">PWA Ready</div><div class="feat-desc">Progressive Web App. Works offline. Install on mobile. Push notifications ready.</div><a class="feat-link" href="#">Learn More →</a></div>
      </div>
    </div>
  </div>

  <!-- PROJECTS -->
  <div class="projects">
    <div class="projects-inner">
      <div class="proj-header">
        <div>
          <div class="sec-tag"><div class="sec-line"></div><p>Active Campaigns</p></div>
          <h2 class="sec-title">Projects Needing<br>Your Support</h2>
        </div>
        <a href="projects" class="btn-s">View All Campaigns →</a>
      </div>
      <div class="proj-grid">
        <?php foreach($projects as $p):
          $pct = ($p['target_amount'] > 0) ? min(100, round(($p['raised_amount'] / $p['target_amount']) * 100)) : 0;
          $cats = ['Education','Healthcare','Environment','Livelihood','Women'];
          $cat = $cats[array_rand($cats)];
        ?>
        <div class="proj-card">
          <div class="proj-img-wrap">
            <img src="<?php echo htmlspecialchars($p['thumbnail_image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy">
            <div class="proj-category-badge"><?php echo $cat; ?></div>
          </div>
          <div class="proj-body">
            <h3 class="proj-title"><?php echo htmlspecialchars($p['title']); ?></h3>
            <p class="proj-desc"><?php echo mb_substr(strip_tags($p['description'] ?? ''), 0, 90); ?>…</p>
            <div class="pbar-wrap">
              <div class="pbar"><div class="pfill" style="width:<?php echo $pct; ?>%"></div></div>
              <div class="pmeta"><span>₹<?php echo number_format($p['raised_amount']); ?> raised</span><span><?php echo $pct; ?>%</span></div>
            </div>
            <div class="proj-btns">
              <a href="project-details.php?id=<?php echo (int)$p['id']; ?>" class="btn-outline-s">View Details</a>
              <a href="donate.php?project_id=<?php echo (int)$p['id']; ?>" class="btn-fill-s">Donate 💛</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- HOW IT WORKS -->
  <div class="hiw">
    <div class="hiw-inner">
      <div class="sec-center">
        <div class="sec-tag" style="justify-content:center"><div class="sec-line" style="background:var(--sl)"></div><p style="color:var(--sl)">Simple Process</p><div class="sec-line" style="background:var(--sl)"></div></div>
        <h2 class="sec-title" style="color:#fff">How Donations Work</h2>
      </div>
      <div class="hiw-steps">
        <div class="hiw-step"><div class="hiw-step-num">1</div><div class="hiw-step-title">Choose a Project</div><div class="hiw-step-desc">Browse active campaigns, see real-time funding progress and impact stories.</div></div>
        <div class="hiw-step"><div class="hiw-step-num">2</div><div class="hiw-step-title">Make Payment</div><div class="hiw-step-desc">UPI, QR code, bank transfer. Upload your payment screenshot securely.</div></div>
        <div class="hiw-step"><div class="hiw-step-num">3</div><div class="hiw-step-title">Admin Verifies</div><div class="hiw-step-desc">Our team verifies within 24 hours and updates the donation status.</div></div>
        <div class="hiw-step"><div class="hiw-step-num">4</div><div class="hiw-step-title">Get 80G Receipt</div><div class="hiw-step-desc">Download your tax-exempt 80G receipt instantly with PAN details.</div></div>
      </div>
    </div>
  </div>

  <!-- GALLERY -->
  <div class="gallery">
    <div class="gallery-inner">
      <div class="sec-tag"><div class="sec-line"></div><p>Gallery</p></div>
      <h2 class="sec-title">Moments of <span style="color:var(--s)">Impact</span></h2>
      <div class="gallery-strip">
        <?php
        $gItems = !empty($galleryImages) ? $galleryImages : [
          ['image_path'=>'https://placehold.co/280x200/1B5E30/ffffff?text=Camp+2024','title'=>'Health Camp 2024'],
          ['image_path'=>'https://placehold.co/280x200/E8610A/ffffff?text=School+Drive','title'=>'School Kits Drive'],
          ['image_path'=>'https://placehold.co/280x200/2E7D4F/ffffff?text=Plantation','title'=>'Tree Plantation'],
          ['image_path'=>'https://placehold.co/280x200/FF8C42/ffffff?text=Awards','title'=>'Annual Awards'],
          ['image_path'=>'https://placehold.co/280x200/4A2C0A/ffffff?text=Food+Drive','title'=>'Food Distribution'],
          ['image_path'=>'https://placehold.co/280x200/1A3020/ffffff?text=Volunteer','title'=>'Volunteer Day'],
        ];
        foreach($gItems as $gi): ?>
        <div class="gallery-item">
          <img src="<?php echo htmlspecialchars($gi['image_path']); ?>" alt="<?php echo htmlspecialchars($gi['title'] ?? ''); ?>" loading="lazy">
          <div class="gallery-overlay"><span><?php echo htmlspecialchars($gi['title'] ?? ''); ?></span></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- TESTIMONIALS -->
  <div class="testimonials">
    <div class="testi-inner">
      <div class="sec-tag"><div class="sec-line"></div><p>Testimonials</p></div>
      <h2 class="sec-title">What Our Community Says</h2>
      <div class="testi-grid">
        <div class="testi-card"><div class="testi-quote">"</div><p class="testi-text">The transparency of this NGO is remarkable. I can track exactly where my donation went and see real progress on the projects. Highly recommended for genuine givers.</p><div class="testi-author"><div class="testi-avatar">R</div><div><div class="testi-name">Ramesh Sharma</div><div class="testi-role">Regular Donor, Kanpur</div></div></div></div>
        <div class="testi-card"><div class="testi-quote">"</div><p class="testi-text">Volunteering here changed my life. The ID card, certificate, and dashboard make it feel professional and meaningful. I'm proud to be part of this family.</p><div class="testi-author"><div class="testi-avatar">P</div><div><div class="testi-name">Priya Gupta</div><div class="testi-role">Volunteer, Lucknow</div></div></div></div>
        <div class="testi-card"><div class="testi-quote">"</div><p class="testi-text">My children received scholarships through this organization. The process was simple and dignified. Forever grateful for the education support.</p><div class="testi-author"><div class="testi-avatar">S</div><div><div class="testi-name">Sunita Devi</div><div class="testi-role">Beneficiary, Unnao</div></div></div></div>
      </div>
    </div>
  </div>

  <!-- EVENTS -->
  <div class="events">
    <div class="events-inner">
      <div class="sec-tag"><div class="sec-line"></div><p>Upcoming Events</p></div>
      <h2 class="sec-title">Join Our Next <span style="color:var(--s)">Events</span></h2>
      <div class="events-grid">
        <?php
        $evFallback = [
          ['day'=>'14','month'=>'Apr','tag'=>'Health Camp','title'=>'Free Eye Checkup & Cataract Surgery Camp','loc'=>'District Hospital, Kanpur'],
          ['day'=>'21','month'=>'Apr','tag'=>'Education','title'=>'Annual Scholarship Distribution Ceremony','loc'=>'Town Hall, Lucknow'],
          ['day'=>'05','month'=>'May','tag'=>'Environment','title'=>'100-Tree Plantation Drive','loc'=>'Gomti Riverbank, Lucknow'],
          ['day'=>'12','month'=>'May','tag'=>'Volunteer','title'=>'New Volunteer Orientation & ID Card Day','loc'=>'NGO HQ, Kanpur'],
        ];
        $evList = !empty($events) ? array_map(function($e){ return ['day'=>date('d',strtotime($e['event_date'])),'month'=>date('M',strtotime($e['event_date'])),'tag'=>'Event','title'=>$e['title'],'loc'=>$e['venue'] ?? '']; }, $events) : $evFallback;
        foreach($evList as $ev): ?>
        <div class="event-card">
          <div class="event-date"><div class="event-day"><?php echo $ev['day']; ?></div><div class="event-month"><?php echo $ev['month']; ?></div></div>
          <div class="event-info">
            <div class="event-tag"><?php echo htmlspecialchars($ev['tag']); ?></div>
            <div class="event-title"><?php echo htmlspecialchars($ev['title']); ?></div>
            <div class="event-meta"><i class="fas fa-map-marker-alt" style="color:var(--s)"></i>&nbsp;<?php echo htmlspecialchars($ev['loc']); ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- VOLUNTEER CTA STRIP -->
  <div class="vol-cta">
    <div class="vol-cta-inner">
      <div>
        <h2>Want to Make a Direct Difference?<br>Become a Volunteer.</h2>
        <p>Join our growing family of 342+ active volunteers. Get your official ID card, certificate, and join meaningful missions near you.</p>
      </div>
      <div class="vol-cta-btns">
        <a href="volunteer-register" class="btn-ghost" style="background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.6)">Register as Volunteer</a>
        <a href="about" style="background:#fff;color:var(--s);padding:.85rem 2.2rem;border-radius:6px;font-weight:700;font-size:.95rem">Know More</a>
      </div>
    </div>
  </div>

  <!-- NEWS -->
  <div class="news">
    <div class="news-inner">
      <div class="sec-tag"><div class="sec-line"></div><p>Latest News</p></div>
      <h2 class="sec-title">Stories of Change</h2>
      <div class="news-grid">
        <div class="news-big"><div class="news-big-img"><img src="https://placehold.co/680x280/1B5E30/ffffff?text=Community+Story" alt=""></div><div class="news-big-body"><div class="news-tag">Impact Story</div><h3 class="news-title">How One Village Got Clean Water After 20 Years of Struggle</h3><p class="news-excerpt">Through collective effort, fundraising, and volunteer power, the village of Rampur finally has access to clean drinking water — a story of perseverance and community...</p></div></div>
        <div class="news-small">
          <div class="news-mini"><img src="https://placehold.co/320x140/E8610A/ffffff?text=Volunteer+Story" alt=""><div class="news-mini-body"><div class="news-tag">Volunteer</div><h4 class="news-title">From College Student to Certified Volunteer Leader</h4></div></div>
          <div class="news-mini"><img src="https://placehold.co/320x140/2E7D4F/ffffff?text=Donation+News" alt=""><div class="news-mini-body"><div class="news-tag">Fundraising</div><h4 class="news-title">₹5 Lakh Raised in 48 Hours for Flood Relief</h4></div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- CTA BAND -->
  <div class="cta">
    <div class="cta-inner">
      <h2 class="cta-title">Ready to Create<br>Forever Change?</h2>
      <p class="cta-sub">Every contribution, every volunteer hour, every shared story adds up to something extraordinary. Join thousands building a better India.</p>
      <div class="cta-btns">
        <a href="donate" class="btn-s">💛 Donate Now</a>
        <a href="#" class="btn-ghost">Join as Volunteer</a>
      </div>
    </div>
  </div>

  <!-- PARTNERS -->
  <?php if(!empty($sponsors)): ?>
  <div class="partners">
    <h3>Our Trusted Partners &amp; Sponsors</h3>
    <div class="p-track">
      <div class="p-row">
        <?php foreach(array_merge($sponsors,$sponsors,$sponsors) as $sp): ?>
        <a href="<?php echo htmlspecialchars($sp['website_url'] ?? '#'); ?>" target="_blank" class="p-logo" title="<?php echo htmlspecialchars($sp['name']); ?>">
          <img src="<?php echo htmlspecialchars($sp['logo_path']); ?>" alt="<?php echo htmlspecialchars($sp['name']); ?>" style="height:45px;object-fit:contain;filter:grayscale(1);opacity:.5;transition:all .3s" onmouseover="this.style.filter='none';this.style.opacity='1'" onmouseout="this.style.filter='grayscale(1)';this.style.opacity='.5'">
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
 
</div><!-- /T1 -->

