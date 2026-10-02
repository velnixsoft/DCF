<?php
/**
 * HOME THEME 2 — "BOLD IMPACT"  v3.0
 * Exact match to ngo_mega_preview.html
 * Palette: Deep Teal #0A4D68 + Coral #EE4E34 + Ivory
 * Fonts: Syne + Outfit
 */
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{--tl:#1070B0;--tm:#0d5b90;--co:#F0A010;--cl:#ffd07d;--iv:#fffcf5;--dk:#1F2937;--tx:#1F2937}
body{font-family:'Outfit',sans-serif;background:var(--iv);color:var(--tx);overflow-x:hidden}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
a{text-decoration:none}img{max-width:100%;display:block}

/* NAV */
.nav{position:sticky;top:52px;z-index:100;background:rgba(13,27,42,.97);backdrop-filter:blur(15px);padding:0 3rem;display:flex;align-items:center;justify-content:space-between;height:72px;border-bottom:1px solid rgba(255,255,255,.05)}
.nav-logo{font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:#fff}
.nav-logo em{font-style:normal;color:var(--co)}
.nav-links{display:flex;gap:2.5rem;list-style:none}
.nav-links a{font-size:.88rem;font-weight:500;color:rgba(255,255,255,.6);transition:.2s}
.nav-links a:hover{color:#fff}
.nav-donate{background:var(--co);color:#fff;padding:.55rem 1.6rem;border-radius:4px;font-size:.88rem;font-weight:700;transition:.2s}
.nav-donate:hover{background:var(--cl)}

/* HERO SPLIT */
.hero{min-height:100vh;display:grid;grid-template-columns:1fr 1.2fr;overflow:hidden}
.hero-left{background:var(--dk);display:flex;flex-direction:column;justify-content:center;padding:6rem 5rem;position:relative;z-index:2}
.hero-left::after{content:'';position:absolute;right:-80px;top:0;bottom:0;width:160px;background:var(--dk);clip-path:polygon(0 0,0 100%,100% 100%);z-index:1}
.hero-right{position:relative;overflow:hidden}
.hero-right img{width:100%;height:100%;object-fit:cover;min-height:100vh}
.hero-right-ov{position:absolute;inset:0;background:linear-gradient(to right,rgba(13,27,42,.5),transparent 50%)}
.hero-tag{background:linear-gradient(135deg,var(--co),var(--cl));color:#fff;font-size:.72rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;padding:.5rem 1.5rem;border-radius:4px;display:inline-block;margin-bottom:2.5rem;box-shadow:0 8px 25px rgba(238,78,52,.4)}
.hero-h1{font-family:'Syne',sans-serif;font-size:clamp(3rem,6.5vw,5.8rem);font-weight:800;color:#fff;line-height:1.05;margin-bottom:2rem;letter-spacing:-.02em}
.hero-h1 em{font-style:normal;background:linear-gradient(135deg,var(--cl),#FFA088);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero-sub{color:rgba(255,255,255,.65);font-size:1.05rem;line-height:1.85;max-width:480px;margin-bottom:3rem}
.hero-actions{display:flex;gap:1.2rem;flex-wrap:wrap}
.btn-co{background:linear-gradient(135deg,var(--co),var(--cl));color:#fff;padding:.85rem 2.4rem;font-weight:700;font-size:.95rem;border-radius:4px;transition:.3s;box-shadow:0 10px 30px rgba(238,78,52,.4)}
.btn-co:hover{transform:translateY(-5px);box-shadow:0 20px 50px rgba(238,78,52,.55)}
.btn-tl{background:var(--tl);color:#fff;padding:.85rem 2.4rem;font-weight:700;font-size:.95rem;border-radius:4px;transition:.3s}
.btn-tl:hover{background:var(--tm);transform:translateY(-5px)}
.btn-wh{border:2px solid rgba(255,255,255,.4);color:#fff;padding:.85rem 2.4rem;font-weight:700;font-size:.95rem;border-radius:4px;transition:.3s}
.btn-wh:hover{background:rgba(255,255,255,.1);border-color:#fff}
.hero-counters{display:flex;gap:3rem;margin-top:4rem;padding-top:3rem;border-top:1px solid rgba(255,255,255,.1);flex-wrap:wrap}
.hc-num{font-family:'Syne',sans-serif;font-size:2.5rem;font-weight:800;color:#fff;line-height:1}
.hc-label{color:rgba(255,255,255,.45);font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;margin-top:.3rem}

/* MARQUEE */
.mq{background:var(--tm);overflow:hidden;padding:.7rem 0}
.mqt{display:flex;white-space:nowrap;animation:mq 30s linear infinite}
.mqt span{color:rgba(255,255,255,.9);font-size:.82rem;font-weight:600;padding:0 3rem;letter-spacing:.06em}

/* STATS */
.stats{padding:6rem 3rem;background:linear-gradient(135deg,var(--tl),var(--tm))}
.stats-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:2rem}
.stat-card{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:2.5rem 2rem;text-align:center;transition:.35s}
.stat-card:hover{background:rgba(255,255,255,.14);transform:translateY(-6px)}
.stat-icon{font-size:2rem;margin-bottom:1rem;opacity:.8}
.stat-num{font-family:'Syne',sans-serif;font-size:3rem;font-weight:800;color:#fff;line-height:1;margin-bottom:.5rem}
.stat-label{color:rgba(255,255,255,.55);font-size:.7rem;letter-spacing:.12em;text-transform:uppercase}

/* ABOUT */
.about{background:#fff;display:grid;grid-template-columns:1.1fr 1fr;gap:0}
.about-img-col{position:relative;min-height:560px;overflow:hidden}
.about-img-col img{width:100%;height:100%;object-fit:cover}
.about-badge{position:absolute;bottom:3rem;right:-1.5rem;background:var(--co);color:#fff;padding:1.5rem 2rem;border-radius:6px 0 0 6px;box-shadow:0 15px 40px rgba(238,78,52,.4);text-align:center}
.about-badge strong{display:block;font-family:'Syne',sans-serif;font-size:2.5rem;font-weight:800;line-height:1}
.about-badge span{font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;opacity:.85}
.about-text-col{padding:5rem 4.5rem;display:flex;flex-direction:column;justify-content:center}
.overtitle{font-size:.7rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--tm);margin-bottom:.75rem}
.big-title{font-family:'Syne',sans-serif;font-size:clamp(2rem,3.5vw,2.8rem);font-weight:800;color:var(--dk);line-height:1.12;margin-bottom:1.5rem}
.big-title em{font-style:normal;color:var(--co)}
.body-txt{color:#718096;line-height:1.85;margin-bottom:2rem}
.val-list{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:2.5rem}
.val-item{display:flex;align-items:center;gap:.8rem;padding:1rem;background:var(--iv);border-radius:4px;font-size:.85rem;font-weight:500}
.val-icon{font-size:1.3rem}

/* FEATURES */
.features{padding:6rem 3rem;background:var(--iv)}
.feat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-top:3.5rem}
.feat-card{background:#fff;border-radius:6px;padding:2.5rem 2rem;border-top:4px solid transparent;transition:.35s;box-shadow:0 3px 15px rgba(0,0,0,.05)}
.feat-card:hover{border-top-color:var(--co);transform:translateY(-8px);box-shadow:0 20px 45px rgba(0,0,0,.1)}
.feat-icon{font-size:2.5rem;margin-bottom:1.2rem}
.feat-title{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:.7rem;color:var(--dk)}
.feat-desc{font-size:.84rem;color:#8a9ab0;line-height:1.7}

/* PROJECTS */
.projects{padding:6rem 3rem;background:#fff}
.proj-inner{max-width:1200px;margin:0 auto}
.ph{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:3.5rem;flex-wrap:wrap;gap:1.5rem}
.p-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2rem}
.pc{background:var(--iv);border-radius:6px;overflow:hidden;transition:.35s;box-shadow:0 3px 15px rgba(0,0,0,.05)}
.pc:hover{transform:translateY(-8px);box-shadow:0 20px 45px rgba(0,0,0,.1)}
.pc-img{position:relative;height:215px;overflow:hidden}
.pc-img img{width:100%;height:100%;object-fit:cover;transition:.5s}
.pc:hover .pc-img img{transform:scale(1.08)}
.pc-pct{position:absolute;bottom:.75rem;right:.75rem;background:var(--co);color:#fff;font-size:.7rem;font-weight:700;padding:.25rem .65rem;border-radius:3px}
.pc-body{padding:1.6rem}
.pc-title{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:.6rem;line-height:1.3;color:var(--dk)}
.pc-desc{font-size:.82rem;color:#8a9ab0;line-height:1.65;margin-bottom:1.2rem}
.pbar{height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden;margin-bottom:.5rem}
.pfill{height:100%;background:linear-gradient(90deg,var(--tl),var(--tm));border-radius:2px}
.pm{display:flex;justify-content:space-between;font-size:.75rem;color:#a0aec0;margin-bottom:1.2rem}
.donate-btn{display:block;text-align:center;background:var(--co);color:#fff;padding:.65rem;border-radius:4px;font-size:.88rem;font-weight:700;transition:.2s}
.donate-btn:hover{background:var(--cl)}

/* HOW IT WORKS */
.hiw{padding:6rem 3rem;background:var(--dk)}
.hiw-inner{max-width:1100px;margin:0 auto}
.hiw-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:2rem;margin-top:4rem;position:relative}
.hiw-grid::before{content:'';position:absolute;top:40px;left:12%;right:12%;height:1px;background:repeating-linear-gradient(90deg,var(--co) 0,var(--co) 20px,transparent 20px,transparent 40px);opacity:.3}
.hiw-step{text-align:center}
.hiw-num{width:80px;height:80px;border-radius:8px;background:var(--co);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:#fff;box-shadow:0 10px 30px rgba(238,78,52,.4);transition:.3s}
.hiw-step:hover .hiw-num{transform:translateY(-5px) rotate(5deg)}
.hiw-title{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:.6rem}
.hiw-desc{font-size:.82rem;color:rgba(255,255,255,.5);line-height:1.7}

/* EVENTS */
.events{padding:6rem 3rem;background:var(--iv)}
.events-inner{max-width:1200px;margin:0 auto}
.events-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:2rem;margin-top:3.5rem}
.ev-card{background:#fff;border-radius:6px;overflow:hidden;display:flex;align-items:stretch;box-shadow:0 3px 15px rgba(0,0,0,.06);transition:.3s}
.ev-card:hover{transform:translateX(4px);box-shadow:0 15px 40px rgba(0,0,0,.1)}
.ev-date{background:var(--tl);color:#fff;padding:1.5rem;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:85px}
.ev-day{font-family:'Syne',sans-serif;font-size:2.2rem;font-weight:800;line-height:1}
.ev-mo{font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;opacity:.75;margin-top:.25rem}
.ev-info{padding:1.5rem;flex:1}
.ev-tag{font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--co);margin-bottom:.35rem}
.ev-title{font-weight:700;font-size:.95rem;color:var(--dk);margin-bottom:.35rem;line-height:1.3}
.ev-loc{font-size:.78rem;color:#a0aec0;display:flex;align-items:center;gap:.4rem}

/* TESTIMONIALS */
.testi{padding:6rem 3rem;background:#fff}
.testi-inner{max-width:1100px;margin:0 auto}
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2rem;margin-top:3.5rem}
.tc{background:var(--iv);border-radius:6px;padding:2.5rem;border-left:4px solid var(--co)}
.tc-stars{color:#F59E0B;font-size:.9rem;margin-bottom:1.2rem;letter-spacing:.15em}
.tc-text{font-size:.88rem;color:#718096;line-height:1.8;font-style:italic;margin-bottom:1.5rem}
.tc-author{display:flex;align-items:center;gap.75rem;gap:.75rem}
.tc-av{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--co),var(--cl));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0}
.tc-name{font-weight:700;font-size:.88rem;color:var(--dk)}
.tc-role{font-size:.75rem;color:#a0aec0}

/* GALLERY */
.gallery{padding:5rem 0;background:var(--iv);overflow:hidden}
.g-inner{max-width:1200px;margin:0 auto;padding:0 3rem}
.g-strip{display:flex;gap:1rem;overflow-x:auto;scrollbar-width:none;margin-top:3rem;padding-bottom:1rem;scroll-snap-type:x mandatory}
.g-strip::-webkit-scrollbar{display:none}
.g-item{flex:0 0 260px;height:185px;border-radius:4px;overflow:hidden;scroll-snap-align:start;position:relative}
.g-item img{width:100%;height:100%;object-fit:cover;transition:.5s}
.g-item:hover img{transform:scale(1.1)}

/* VOLUNTEER CTA */
.vol-cta{background:linear-gradient(135deg,var(--co),#C23A1C);padding:5rem 3rem;position:relative;overflow:hidden}
.vol-cta::before{content:'';position:absolute;right:-80px;top:-80px;width:400px;height:400px;border-radius:50%;background:rgba(255,255,255,.06)}
.vol-cta-in{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr auto;gap:3rem;align-items:center;position:relative;z-index:1;flex-wrap:wrap}
.vc-h2{font-family:'Syne',sans-serif;font-size:2.5rem;font-weight:800;color:#fff;line-height:1.15}
.vc-p{color:rgba(255,255,255,.8);margin-top:.75rem;font-size:1rem;line-height:1.7}
.vc-btns{display:flex;flex-direction:column;gap:1rem;flex-shrink:0}

/* CTA */
.cta{padding:7rem 3rem;background:var(--dk);text-align:center;position:relative;overflow:hidden}
.cta::before,.cta::after{content:'';position:absolute;border-radius:50%;background:var(--co);opacity:.06}
.cta::before{width:600px;height:600px;top:-200px;left:-150px}
.cta::after{width:400px;height:400px;bottom:-150px;right:-100px}
.cta-inner{position:relative;z-index:1;max-width:700px;margin:0 auto}
.cta-title{font-family:'Syne',sans-serif;font-size:clamp(2.5rem,5vw,4rem);font-weight:800;color:#fff;margin-bottom:1.2rem}
.cta-title em{font-style:normal;color:var(--co)}
.cta-sub{color:rgba(255,255,255,.6);font-size:1.05rem;line-height:1.8;margin-bottom:3rem}
.cta-btns{display:flex;gap:1.2rem;justify-content:center;flex-wrap:wrap}

/* PARTNERS */
.partners{padding:4rem 3rem;background:#fff;text-align:center}
.partners h3{font-size:.9rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:#cbd5e0;margin-bottom:2.5rem;font-family:'Syne',sans-serif}
.p-track{overflow:hidden;mask-image:linear-gradient(to right,transparent,black 10%,black 90%,transparent)}
.p-row{display:flex;gap:4rem;animation:mq 35s linear infinite;width:max-content}
.p-logo{height:40px;width:110px;background:#f0f4f8;border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#cbd5e0;font-size:.65rem;font-weight:600;letter-spacing:.1em}

/* ════════════════════════════════════════
   THEME 3 — FESTIVE SEVA
   Yeseva One + Poppins
   Purple / Gold / Pink
════════════════════════════════════════ */

.hero-right{position:relative;overflow:hidden}.hero-right-slides{position:absolute;inset:0}.p-logo img{height:40px;object-fit:contain;filter:grayscale(1);opacity:.45;transition:all .3s}.p-logo{display:flex;align-items:center}
</style>

<?php if(!empty($birthdayMembers)): ?>
<div style="background:linear-gradient(135deg,#0A4D68,#088395);padding:.8rem 2rem;text-align:center">
  <span style="color:rgba(255,255,255,.9);font-size:.9rem">🎂 Birthday Heroes: <strong style="color:#FCD34D"><?php echo htmlspecialchars(implode(', ', array_column($birthdayMembers,'full_name'))); ?></strong> 🎉</span>
</div>
<?php endif; ?>

  <nav class="nav">
    <div class="nav-logo">Bold<em>Hope</em></div>
    <ul class="nav-links">
      <li><a href="/">Home</a></li><li><a href="about">About</a></li><li><a href="projects">Projects</a></li><li><a href="events">Events</a></li><li><a href="volunteer-register">Volunteer</a></li><li><a href="gallery">Gallery</a></li><li><a href="contact">Contact</a></li>
    </ul>
    <a href="#" href="donate" class="nav-donate">Donate</a>
  </nav>

  <div class="hero"
  x-data="{slide:0,slides:[],init(){this.slides=JSON.parse('<?php echo $slides_json; ?>');if(this.slides.length>1)setInterval(()=>{this.slide=(this.slide+1)%this.slides.length},5800);}}">
    <div class="hero-left">
      <div class="hero-tag">Impact Architects Since 2009</div>
      <h1 class="hero-h1">Changing Lives with <em>Purpose</em> &amp; <em>Passion</em></h1>
      <p class="hero-sub">Where compassion meets action — powering education, healthcare, and sustainable livelihoods for underserved communities.</p>
      <div class="hero-actions">
        <a href="#" class="btn-co">Donate Now</a>
        <a href="#" class="btn-tl">Explore Projects</a>
        <a href="#" class="btn-wh">Join Volunteer</a>
      </div>
      <div class="hero-counters"
        x-data="{d:0,v:0,p:0,started:false,run(){if(this.started)return;this.started=true;const ease=t=>1-Math.pow(1-t,3);const go=(k,end)=>{const dur=2200,s=performance.now();const f=n=>{const pr=Math.min((n-s)/dur,1);this[k]=Math.floor(end*ease(pr));if(pr<1)requestAnimationFrame(f);else this[k]=end;};requestAnimationFrame(f);};go('d',<?php echo (int)$totalDonation;?>);go('v',<?php echo (int)$activeVolunteers;?>);go('p',<?php echo (int)$ongoingProjects;?>);}}"
        x-intersect.once.threshold.0.1="run()">
        <div><div class="hc-num">₹<span x-text="d.toLocaleString('en-IN')">0</span></div><div class="hc-label">Funds Raised</div></div>
        <div><div class="hc-num" x-text="v">0</div><div class="hc-label">Volunteers</div></div>
        <div><div class="hc-num" x-text="p">0</div><div class="hc-label">Live Projects</div></div>
      </div>
    </div>
    <div class="hero-right">
      <?php foreach($slides_raw as $si2=>$ss2): ?><img src="<?php echo htmlspecialchars($ss2['image_path']); ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 1.5s" :style="'opacity:'+(slide===<?php echo $si2; ?>?1:0)" loading="<?php echo $si2===0?'eager':'lazy'; ?>"><?php endforeach; ?><?php if(empty($slides_raw)): ?><img src="https://placehold.co/1200x1200/088395/ffffff?text=Bold+Impact" alt="" style="width:100%;height:100%;object-fit:cover"><?php endif; ?>
      <div class="hero-right-ov"></div>
    </div>
  </div>

  <div class="mq">
    <div class="mqt">
      <span>Architecture of Hope</span><span>◆</span><span>Launch Your Impact</span><span>◆</span><span>5000+ Change Agents</span><span>◆</span><span>80G Receipts Ready</span><span>◆</span><span>Volunteer ID Cards</span><span>◆</span>
      <span>Architecture of Hope</span><span>◆</span><span>Launch Your Impact</span><span>◆</span><span>5000+ Change Agents</span><span>◆</span><span>80G Receipts Ready</span><span>◆</span><span>Volunteer ID Cards</span><span>◆</span>
    </div>
  </div>

  <div class="stats"
    x-data="{d:0,v:0,p:0,l:0,go:false,run(){if(this.go)return;this.go=true;const e=t=>1-Math.pow(1-t,3);const a=(k,end)=>{const dur=2200,s=performance.now();const f=n=>{const pr=Math.min((n-s)/dur,1);this[k]=Math.floor(end*e(pr));if(pr<1)requestAnimationFrame(f);else this[k]=end;};requestAnimationFrame(f);};a('d',<?php echo (int)$totalDonation;?>);a('v',<?php echo (int)$activeVolunteers;?>);a('p',<?php echo (int)$ongoingProjects;?>);a('l',15000);}}"
    x-intersect.once.threshold.0.2="run()">
    <div class="stats-inner">
      <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-num">₹<span x-text="d.toLocaleString('en-IN')">0</span></div><div class="stat-label">Funds Raised</div></div>
      <div class="stat-card"><div class="stat-icon">🙋</div><div class="stat-num" x-text="v">0</div><div class="stat-label">Active Volunteers</div></div>
      <div class="stat-card"><div class="stat-icon">🚀</div><div class="stat-num" x-text="p">0</div><div class="stat-label">Live Projects</div></div>
      <div class="stat-card"><div class="stat-icon">🎓</div><div class="stat-num" x-text="l.toLocaleString()">0</div><div class="stat-label">Lives Touched</div></div>
    </div>
  </div>

  <div class="about">
    <div class="about-img-col">
      <img src="<?php echo $about_image ?: 'https://placehold.co/700x600/0A4D68/ffffff?text=Our+Story'; ?>" alt="About Us">
      <div class="about-badge"><strong>10+</strong><span>Years of Impact</span></div>
    </div>
    <div class="about-text-col">
      <p class="overtitle">Our Organization</p>
      <h2 class="big-title">We Believe in <em>Human Potential</em> — Everywhere</h2>
      <p class="body-txt"><?php echo mb_substr(strip_tags($about_desc ?? 'We work at the intersection of compassion and technology — connecting donors, volunteers, and beneficiaries with transparency at the core.'), 0, 350); ?>...</p>
      <div class="val-list">
        <div class="val-item"><span class="val-icon">🎯</span>Mission-Driven</div>
        <div class="val-item"><span class="val-icon">🔍</span>Transparent</div>
        <div class="val-item"><span class="val-icon">💡</span>Tech-Enabled</div>
        <div class="val-item"><span class="val-icon">🤝</span>Community First</div>
      </div>
      <a href="#" class="btn-tl">Discover Our Blueprint →</a>
    </div>
  </div>

  <div class="features">
    <div style="max-width:1200px;margin:0 auto">
      <div style="text-align:center;margin-bottom:3.5rem">
        <p class="overtitle" style="text-align:center">Full Ecosystem</p>
        <h2 class="big-title" style="font-size:2.5rem;text-align:center">Everything on This Platform</h2>
      </div>
      <div class="feat-grid">
        <div class="feat-card"><div class="feat-icon">💳</div><div class="feat-title">Secure Payments</div><div class="feat-desc">Razorpay, QR, UPI, bank transfer. Admin-verified. 80G receipts auto-generated.</div></div>
        <div class="feat-card"><div class="feat-icon">🪪</div><div class="feat-title">Volunteer System</div><div class="feat-desc">Registration, approval workflow, ID card PDF with QR code, public verification.</div></div>
        <div class="feat-card"><div class="feat-icon">👥</div><div class="feat-title">Member Portal</div><div class="feat-desc">Designations, fees, referral codes, appointment letters, member ID cards.</div></div>
        <div class="feat-card"><div class="feat-icon">📈</div><div class="feat-title">Project Analytics</div><div class="feat-desc">Live funding progress, donation charts, target tracking. Admin dashboard with CSV export.</div></div>
        <div class="feat-card"><div class="feat-icon">🎉</div><div class="feat-title">Events System</div><div class="feat-desc">Event creation, photo galleries, registrations — fully managed from admin panel.</div></div>
        <div class="feat-card"><div class="feat-icon">📄</div><div class="feat-title">Certificates</div><div class="feat-desc">FPDF-generated certificates for volunteers, members, visitors — QR verified.</div></div>
        <div class="feat-card"><div class="feat-icon">⚙️</div><div class="feat-title">Admin Control</div><div class="feat-desc">Everything manageable from admin: sliders, gallery, sponsors, settings, branding.</div></div>
        <div class="feat-card"><div class="feat-icon">📧</div><div class="feat-title">Email & Alerts</div><div class="feat-desc">SMTP email, birthday alerts, OTP verification, WhatsApp notifications (upcoming).</div></div>
      </div>
    </div>
  </div>

  <div class="projects">
    <div class="proj-inner">
      <div class="ph">
        <div><p class="overtitle">Active Campaigns</p><h2 class="big-title" style="font-size:2.5rem">Projects Needing You Now</h2></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <a href="apply-health-card.php" class="btn-co" style="background:#0F8B8D;color:#fff">🏥 Health Card (Apply / Renew)</a>
          <a href="projects.php" class="btn-co">All Projects →</a>
        </div>
      </div>
      <div class="p-grid">
        <div class="pc"><div class="pc-img"><img src="https://placehold.co/400x215/0A4D68/ffffff?text=Education" alt=""><div class="pc-pct">72% Funded</div></div><div class="pc-body"><h3 class="pc-title">Scholarships for Rural Children</h3><p class="pc-desc">Quality education for underprivileged children in remote villages.</p><div class="pbar"><div class="pfill" style="width:72%"></div></div><div class="pm"><span>₹72,000 raised</span><span>Goal ₹1,00,000</span></div><a href="donate.php" class="donate-btn">Donate to Campaign</a></div></div>
        <div class="pc"><div class="pc-img"><img src="https://placehold.co/400x215/EE4E34/ffffff?text=Healthcare" alt=""><div class="pc-pct">55% Funded</div></div><div class="pc-body"><h3 class="pc-title">Free Medical Camps — Rural UP</h3><p class="pc-desc">Free health checkups, medicines, and specialist consultations.</p><div class="pbar"><div class="pfill" style="width:55%"></div></div><div class="pm"><span>₹55,000 raised</span><span>Goal ₹1,00,000</span></div><a href="donate.php" class="donate-btn">Donate to Campaign</a></div></div>
        <div class="pc"><div class="pc-img"><img src="https://placehold.co/400x215/088395/ffffff?text=Water" alt=""><div class="pc-pct">38% Funded</div></div><div class="pc-body"><h3 class="pc-title">Clean Water for 500 Families</h3><p class="pc-desc">Water purification units in drought-affected regions.</p><div class="pbar"><div class="pfill" style="width:38%"></div></div><div class="pm"><span>₹38,000 raised</span><span>Goal ₹1,00,000</span></div><a href="donate.php" class="donate-btn">Donate to Campaign</a></div></div>
      </div>
      <div style="display:flex;justify-content:center;gap:12px;margin-top:24px;flex-wrap:wrap">
        <a href="projects.php" class="btn-co">Browse All Projects →</a>
        <a href="apply-health-card.php" class="btn-co" style="background:#0F8B8D;color:#fff">Apply / Renew / Download Health Card</a>
      </div>
    </div>
  </div>

  <div class="hiw">
    <div class="hiw-inner">
      <div style="text-align:center;margin-bottom:1rem"><p class="overtitle" style="color:rgba(255,255,255,.5)">Simple Steps</p><h2 class="big-title" style="color:#fff;font-size:2.5rem">How to Donate</h2></div>
      <div class="hiw-grid">
        <div class="hiw-step"><div class="hiw-num">1</div><div class="hiw-title">Browse Campaigns</div><div class="hiw-desc">Find a cause that resonates with you from our active projects.</div></div>
        <div class="hiw-step"><div class="hiw-num">2</div><div class="hiw-title">Pay Securely</div><div class="hiw-desc">UPI, QR, bank transfer. Upload screenshot as proof.</div></div>
        <div class="hiw-step"><div class="hiw-num">3</div><div class="hiw-title">Admin Approves</div><div class="hiw-desc">Verified within 24 hours with confirmation notification.</div></div>
        <div class="hiw-step"><div class="hiw-num">4</div><div class="hiw-title">Get 80G Receipt</div><div class="hiw-desc">Download tax-exempt receipt with PAN integration.</div></div>
      </div>
    </div>
  </div>

  <div class="events">
    <div class="events-inner">
      <div class="ph"><div><p class="overtitle">Calendar</p><h2 class="big-title" style="font-size:2.2rem">Upcoming Events</h2></div><a href="#" class="btn-co">All Events →</a></div>
      <div class="events-grid">
        <div class="ev-card"><div class="ev-date"><div class="ev-day">14</div><div class="ev-mo">Apr</div></div><div class="ev-info"><div class="ev-tag">Health Camp</div><div class="ev-title">Free Eye Checkup Camp</div><div class="ev-loc"><i class="fas fa-map-marker-alt"></i>&nbsp;District Hospital, Kanpur</div></div></div>
        <div class="ev-card"><div class="ev-date"><div class="ev-day">21</div><div class="ev-mo">Apr</div></div><div class="ev-info"><div class="ev-tag">Education</div><div class="ev-title">Annual Scholarship Distribution</div><div class="ev-loc"><i class="fas fa-map-marker-alt"></i>&nbsp;Town Hall, Lucknow</div></div></div>
        <div class="ev-card"><div class="ev-date"><div class="ev-day">05</div><div class="ev-mo">May</div></div><div class="ev-info"><div class="ev-tag">Environment</div><div class="ev-title">100-Tree Plantation Drive</div><div class="ev-loc"><i class="fas fa-map-marker-alt"></i>&nbsp;Gomti Riverbank</div></div></div>
        <div class="ev-card"><div class="ev-date"><div class="ev-day">12</div><div class="ev-mo">May</div></div><div class="ev-info"><div class="ev-tag">Volunteer</div><div class="ev-title">New Volunteer Orientation Day</div><div class="ev-loc"><i class="fas fa-map-marker-alt"></i>&nbsp;NGO HQ, Kanpur</div></div></div>
      </div>
    </div>
  </div>

  <div class="gallery">
    <div class="g-inner">
      <div class="ph"><div><p class="overtitle">Gallery</p><h2 class="big-title" style="font-size:2.2rem">Our Impact in Photos</h2></div><a href="#" class="btn-tl">Full Gallery →</a></div>
      <div class="g-strip">
        <div class="g-item"><img src="https://placehold.co/260x185/0A4D68/ffffff?text=Health+Camp" alt=""></div>
        <div class="g-item"><img src="https://placehold.co/260x185/EE4E34/ffffff?text=School+Drive" alt=""></div>
        <div class="g-item"><img src="https://placehold.co/260x185/088395/ffffff?text=Water+Project" alt=""></div>
        <div class="g-item"><img src="https://placehold.co/260x185/FF7B5B/ffffff?text=Award+Night" alt=""></div>
        <div class="g-item"><img src="https://placehold.co/260x185/0D1B2A/ffffff?text=Volunteer+Day" alt=""></div>
        <div class="g-item"><img src="https://placehold.co/260x185/1B6B7A/ffffff?text=Women+Power" alt=""></div>
      </div>
    </div>
  </div>

  <div class="testi">
    <div class="testi-inner">
      <div style="text-align:center;margin-bottom:3.5rem"><p class="overtitle">Community Voices</p><h2 class="big-title" style="font-size:2.5rem">What They Say</h2></div>
      <div class="testi-grid">
        <div class="tc"><div class="tc-stars">★★★★★</div><p class="tc-text">"The donation system is incredibly smooth. I got my 80G receipt within hours and could track my contribution in real time."</p><div class="tc-author"><div class="tc-av">R</div><div><div class="tc-name">Ramesh Sharma</div><div class="tc-role">Regular Donor, Kanpur</div></div></div></div>
        <div class="tc"><div class="tc-stars">★★★★★</div><p class="tc-text">"As a volunteer, having my own ID card with QR verification makes me feel official and respected. The dashboard is brilliant."</p><div class="tc-author"><div class="tc-av">P</div><div><div class="tc-name">Priya Gupta</div><div class="tc-role">Volunteer, Lucknow</div></div></div></div>
        <div class="tc"><div class="tc-stars">★★★★★</div><p class="tc-text">"The membership portal is feature-rich. Referral system, appointment letters, everything automated. Excellent system!"</p><div class="tc-author"><div class="tc-av">A</div><div><div class="tc-name">Anil Verma</div><div class="tc-role">Member, Delhi</div></div></div></div>
      </div>
    </div>
  </div>

  <div class="vol-cta">
    <div class="vol-cta-in">
      <div><div class="vc-h2">Be Part of the Movement.<br>Volunteer with Us.</div><p class="vc-p">Join 342+ active volunteers. Get certified, make real impact, and build your NGO career.</p></div>
      <div class="vc-btns"><a href="#" style="background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.5);color:#fff;padding:.85rem 2.2rem;border-radius:4px;font-weight:700;font-size:.95rem">Register Now</a><a href="#" style="background:#fff;color:var(--co);padding:.85rem 2.2rem;border-radius:4px;font-weight:700;font-size:.95rem">Know More</a></div>
    </div>
  </div>

  <div class="cta">
    <div class="cta-inner">
      <h2 class="cta-title">Your ₹100 Can <em>Change</em> a Life Today</h2>
      <p class="cta-sub">Every contribution powers education, feeds families, and restores hope. Be part of this movement for a better India.</p>
      <div class="cta-btns"><a href="#" class="btn-co">Donate Now</a><a href="#" class="btn-wh">Volunteer With Us</a></div>
    </div>
  </div>

  <?php if(!empty($sponsors)): ?><div class="partners"><h3>Our Strategic Partners</h3><div class="p-track"><div class="p-row"><?php foreach(array_merge($sponsors,$sponsors,$sponsors) as $sp): ?><a href="<?php echo htmlspecialchars($sp['website_url']??'#'); ?>" target="_blank" class="p-logo" title="<?php echo htmlspecialchars($sp['name']); ?>"><img src="<?php echo htmlspecialchars($sp['logo_path']); ?>" alt="<?php echo htmlspecialchars($sp['name']); ?>" style="height:40px;object-fit:contain;filter:grayscale(1);opacity:.45;transition:all .3s" onmouseover="this.style.filter='none';this.style.opacity='1'" onmouseout="this.style.filter='grayscale(1)';this.style.opacity='.45'"></a><?php endforeach; ?></div></div></div><?php endif; ?>
</div><!-- /T2 -->

