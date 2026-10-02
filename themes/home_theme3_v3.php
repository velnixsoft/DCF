<?php
/**
 * HOME THEME 3 — "FESTIVE SEVA"  v3.0
 * Exact match to ngo_mega_preview.html
 * Palette: Purple #3B0764 + Gold #F59E0B + Pink #EC4899
 * Fonts: Yeseva One + Poppins
 */
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Yeseva+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{--p:#1070B0;--pm:#0d5b90;--g:#F0A010;--gl:#ffd07d;--pk:#F0A010;--cr:#fffcf5;--ch:#1F2937;--bt:#1F2937}
body{font-family:'Poppins',sans-serif;background:var(--cr);color:var(--bt);overflow-x:hidden}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
a{text-decoration:none}img{max-width:100%;display:block}

/* NAV */
.nav{position:sticky;top:52px;z-index:100;background:var(--p);padding:0 3rem;display:flex;align-items:center;justify-content:space-between;height:72px}
.nav-logo{font-family:'Yeseva One',serif;font-size:1.5rem;color:#fff}
.nav-logo span{color:var(--gl)}
.nav-links{display:flex;gap:2.5rem;list-style:none}
.nav-links a{font-size:.85rem;font-weight:500;color:rgba(255,255,255,.65);transition:.2s}
.nav-links a:hover{color:var(--gl)}
.nav-donate{background:linear-gradient(135deg,var(--g),var(--gl));color:var(--ch);padding:.55rem 1.6rem;border-radius:50px;font-size:.88rem;font-weight:700;transition:.2s}

/* HERO */
.hero{position:relative;min-height:100vh;overflow:hidden;background:var(--ch);display:flex;align-items:center;justify-content:center;text-align:center}
.hero-bg{position:absolute;inset:0;background:linear-gradient(160deg,var(--p) 0%,#1a0a3c 60%,#2d1252 100%)}
.hero-ov{position:absolute;inset:0;background:linear-gradient(180deg,rgba(59,7,100,.88) 0%,rgba(59,7,100,.6) 50%,rgba(245,158,11,.1) 100%)}
.deco-ring{position:absolute;right:-100px;top:50%;transform:translateY(-50%);width:800px;height:800px;border-radius:50%;border:2px solid rgba(245,158,11,.15);pointer-events:none}
.deco-ring-2{position:absolute;left:-150px;top:50%;transform:translateY(-50%);width:600px;height:600px;border-radius:50%;border:1px solid rgba(236,72,153,.12);pointer-events:none}
.hero-content{position:relative;z-index:5;padding:2rem 3rem;max-width:980px}
.lotus{font-size:3.5rem;display:block;margin-bottom:1.5rem;animation:float2 5s ease-in-out infinite}
@keyframes float2{0%,100%{transform:translateY(0)}50%{transform:translateY(-15px)}}
.hero-eyebrow{font-size:.8rem;font-weight:600;letter-spacing:.25em;text-transform:uppercase;color:var(--gl);margin-bottom:1.5rem;display:block}
.hero-h1{font-family:'Yeseva One',serif;font-size:clamp(3.2rem,7.5vw,6.5rem);color:#fff;line-height:1.06;margin-bottom:2rem}
.hero-h1 mark{background:none;color:var(--g);font-family:'Yeseva One',serif}
.hero-sub{color:rgba(255,255,255,.72);font-size:1.1rem;line-height:1.85;max-width:600px;margin:0 auto 3rem}
.hero-btns{display:flex;gap:1.2rem;justify-content:center;flex-wrap:wrap}
.btn-gd{background:linear-gradient(135deg,var(--g),var(--gl));color:var(--ch);padding:.9rem 2.5rem;border-radius:50px;font-weight:700;font-size:1rem;transition:.35s;box-shadow:0 10px 35px rgba(245,158,11,.4)}
.btn-gd:hover{transform:translateY(-5px);box-shadow:0 20px 55px rgba(245,158,11,.55)}
.btn-pk{background:var(--pk);color:#fff;padding:.9rem 2.5rem;border-radius:50px;font-weight:700;font-size:1rem;transition:.35s;box-shadow:0 10px 35px rgba(236,72,153,.35)}
.btn-pk:hover{transform:translateY(-5px);filter:brightness(1.1)}
.btn-pp{background:var(--p);color:#fff;padding:.9rem 2.5rem;border-radius:50px;font-weight:700;font-size:1rem;transition:.35s}
.btn-pp:hover{background:var(--pm);transform:translateY(-4px)}
.btn-pk-outline{border:2px solid var(--pk);color:var(--pk);padding:.9rem 2.5rem;border-radius:50px;font-weight:700;font-size:1rem;transition:.35s}
.btn-pk-outline:hover{background:var(--pk);color:#fff}

/* TICKER */
.ticker{background:linear-gradient(90deg,var(--g),var(--gl));overflow:hidden;padding:.7rem 0}
.ttrack{display:flex;white-space:nowrap;animation:mq 38s linear infinite}
.ttrack span{font-size:.85rem;font-weight:700;color:var(--ch);padding:0 3rem;letter-spacing:.06em}
.ttrack .sep{color:rgba(28,25,23,.35)}

/* BIRTHDAY */
.bday{background:linear-gradient(135deg,var(--p),var(--pm));padding:1.2rem 3rem;text-align:center;box-shadow:0 4px 20px rgba(59,7,100,.3)}
.bday span{color:rgba(255,255,255,.9);font-size:1rem;font-weight:600}
.bday strong{color:var(--gl)}

/* STATS */
.stats{padding:5rem 3rem;background:var(--p)}
.stats-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem}
.sc{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:2.5rem 2rem;text-align:center;transition:.35s}
.sc:hover{background:rgba(255,255,255,.14);transform:translateY(-6px)}
.sc-icon{font-size:2rem;display:block;margin-bottom:.75rem}
.sc-num{font-family:'Yeseva One',serif;font-size:3rem;color:#fff;line-height:1;margin-bottom:.4rem}
.sc-num sup{font-size:1.2rem;vertical-align:super;color:var(--g)}
.sc-label{color:rgba(255,255,255,.5);font-size:.7rem;letter-spacing:.12em;text-transform:uppercase}

/* ABOUT */
.about{padding:7rem 3rem;background:var(--cr)}
.about-inner{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:6rem;align-items:center}
.img-frame{position:relative}
.img-frame::before{content:'';position:absolute;top:-15px;left:-15px;right:15px;bottom:15px;border:3px solid var(--g);border-radius:8px;z-index:0}
.img-frame img{position:relative;z-index:1;width:100%;border-radius:8px;box-shadow:0 25px 60px rgba(0,0,0,.18);height:480px;object-fit:cover;background:#ddd}
.img-badge{position:absolute;z-index:2;bottom:-1.5rem;right:-1.5rem;background:var(--pk);color:#fff;border-radius:50%;width:110px;height:110px;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:0 10px 30px rgba(236,72,153,.45)}
.img-badge strong{font-family:'Yeseva One',serif;font-size:2rem;line-height:1}
.img-badge span{font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;text-align:center}
.at{font-size:.72rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:var(--pm);margin-bottom:.75rem}
.atitle{font-family:'Yeseva One',serif;font-size:clamp(2.2rem,4vw,3rem);color:var(--ch);line-height:1.2;margin-bottom:1.5rem}
.atitle em{font-style:normal;color:var(--pk)}
.atxt{color:#78716C;line-height:1.85;margin-bottom:2rem}
.feats{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:2.5rem}
.feat{background:#fff;border-radius:8px;padding:1rem;border:1px solid #F3F0EC;display:flex;align-items:center;gap:.75rem}
.feat-i{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--pm));display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;flex-shrink:0}
.feat span{font-size:.85rem;font-weight:500}

/* FEATURES */
.features{padding:6rem 3rem;background:#fff}
.f-inner{max-width:1200px;margin:0 auto}
.feat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-top:3.5rem}
.fc{background:var(--cr);border-radius:12px;padding:2.5rem 2rem;text-align:center;border:1px solid #F3F0EC;transition:.35s}
.fc:hover{transform:translateY(-8px);box-shadow:0 20px 50px rgba(59,7,100,.1);border-color:transparent}
.fc-icon{font-size:2.8rem;margin-bottom:1.2rem;display:block}
.fc-title{font-family:'Yeseva One',serif;font-size:1.1rem;color:var(--ch);margin-bottom:.75rem}
.fc-desc{font-size:.83rem;color:#9CA3AF;line-height:1.7;margin-bottom:1.2rem}
.fc-link{font-size:.78rem;font-weight:600;color:var(--pm);letter-spacing:.04em}

/* PROJECTS */
.projects{padding:6rem 3rem;background:var(--cr)}
.proj-inner{max-width:1200px;margin:0 auto}
.sec-top{text-align:center;margin-bottom:3.5rem}
.sec-top .at{justify-content:center}
.stitle{font-family:'Yeseva One',serif;font-size:clamp(2.2rem,4vw,3rem);color:var(--ch)}
.stitle span{color:var(--g)}
.p-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2rem}
.pc{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.07);transition:.35s}
.pc:hover{transform:translateY(-8px);box-shadow:0 20px 50px rgba(59,7,100,.12)}
.pc-img{position:relative;height:215px;overflow:hidden}
.pc-img img{width:100%;height:100%;object-fit:cover;transition:.5s;background:#ddd}
.pc:hover .pc-img img{transform:scale(1.08)}
.pc-badge{position:absolute;top:.8rem;left:.8rem;background:var(--p);color:#fff;font-size:.65rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;padding:.3rem .8rem;border-radius:50px}
.pc-body{padding:1.6rem}
.pc-title{font-family:'Yeseva One',serif;font-size:1.1rem;color:var(--ch);margin-bottom:.75rem;line-height:1.3}
.pbt{height:6px;background:#E7E5E4;border-radius:3px;overflow:hidden;margin-bottom:.5rem}
.pbf{height:100%;background:linear-gradient(90deg,var(--p),var(--pk));border-radius:3px}
.pm{display:flex;justify-content:space-between;font-size:.75rem;color:#A8A29E;margin-bottom:1.2rem}
.d-btn{display:block;text-align:center;background:linear-gradient(135deg,var(--g),var(--gl));color:var(--ch);padding:.65rem;border-radius:50px;font-weight:700;font-size:.88rem;transition:.25s}
.d-btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(245,158,11,.35)}

/* HOW IT WORKS */
.hiw{padding:6rem 3rem;background:linear-gradient(135deg,var(--p),#1a0a3c)}
.hiw-inner{max-width:1100px;margin:0 auto}
.hiw-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:2rem;margin-top:4rem}
.hiw-step{text-align:center;padding:2.5rem 1.5rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:12px;transition:.3s}
.hiw-step:hover{background:rgba(255,255,255,.1);transform:translateY(-6px)}
.hiw-num{width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,var(--g),var(--gl));display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-family:'Yeseva One',serif;font-size:1.8rem;color:var(--ch);box-shadow:0 8px 25px rgba(245,158,11,.4)}
.hiw-title{font-family:'Yeseva One',serif;font-size:1.1rem;color:#fff;margin-bottom:.6rem}
.hiw-desc{font-size:.82rem;color:rgba(255,255,255,.5);line-height:1.7}

/* EVENTS */
.events{padding:6rem 3rem;background:#fff}
.ev-inner{max-width:1200px;margin:0 auto}
.ev-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2rem;margin-top:3.5rem}
.ev-card{background:var(--cr);border-radius:12px;overflow:hidden;transition:.35s;border:1px solid #F3F0EC}
.ev-card:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(59,7,100,.1)}
.ev-img{height:160px;overflow:hidden;position:relative}
.ev-img img{width:100%;height:100%;object-fit:cover;transition:.5s;background:linear-gradient(135deg,var(--p),var(--pm))}
.ev-card:hover .ev-img img{transform:scale(1.08)}
.ev-date-pill{position:absolute;top:.8rem;left:.8rem;background:var(--g);color:var(--ch);font-size:.7rem;font-weight:700;padding:.3rem .8rem;border-radius:50px}
.ev-body{padding:1.5rem}
.ev-cat{font-size:.65rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--pm);margin-bottom:.4rem}
.ev-title{font-family:'Yeseva One',serif;font-size:1.05rem;color:var(--ch);margin-bottom:.4rem;line-height:1.3}
.ev-loc{font-size:.78rem;color:#A8A29E}

/* TESTIMONIALS */
.testi{padding:6rem 3rem;background:var(--cr)}
.testi-inner{max-width:1100px;margin:0 auto}
.t-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2rem;margin-top:3.5rem}
.tc{background:#fff;border-radius:12px;padding:2.5rem;border-top:3px solid var(--g)}
.tc-stars{color:var(--g);font-size:.9rem;margin-bottom:1rem;letter-spacing:.1em}
.tc-text{font-size:.88rem;color:#78716C;line-height:1.8;font-style:italic;margin-bottom:1.5rem}
.tc-auth{display:flex;align-items:center;gap:.75rem}
.tc-av{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--pm));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0}
.tc-name{font-weight:600;font-size:.88rem;color:var(--ch)}
.tc-role{font-size:.75rem;color:#A8A29E}

/* GALLERY */
.gallery{padding:5rem 0;background:#fff;overflow:hidden}
.g-inner{max-width:1200px;margin:0 auto;padding:0 3rem}
.g-strip{display:flex;gap:1rem;overflow-x:auto;scrollbar-width:none;margin-top:3rem;padding-bottom:1rem;scroll-snap-type:x mandatory}
.g-strip::-webkit-scrollbar{display:none}
.g-item{flex:0 0 260px;height:185px;border-radius:12px;overflow:hidden;scroll-snap-align:start;position:relative}
.g-item img{width:100%;height:100%;object-fit:cover;transition:.5s;background:linear-gradient(135deg,var(--p),var(--pk))}
.g-item:hover img{transform:scale(1.1)}

/* VOLUNTEER CTA */
.vol-cta{padding:5rem 3rem;background:linear-gradient(135deg,var(--pk),#be185d)}
.vol-cta-in{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr auto;gap:3rem;align-items:center}
.vc-h2{font-family:'Yeseva One',serif;font-size:2.5rem;color:#fff;line-height:1.2}
.vc-p{color:rgba(255,255,255,.8);margin-top:.75rem;font-size:1rem;line-height:1.7}
.vc-btns{display:flex;flex-direction:column;gap:1rem;flex-shrink:0}

/* CTA */
.cta{padding:7rem 3rem;background:linear-gradient(135deg,var(--p),#1e0a3c);text-align:center;position:relative;overflow:hidden}
.cta-deco{position:absolute;font-size:20rem;opacity:.04;top:-3rem;left:50%;transform:translateX(-50%);pointer-events:none;color:#fff;font-family:'Yeseva One',serif;line-height:1}
.cta-inner{position:relative;z-index:1;max-width:700px;margin:0 auto}
.cta-title{font-family:'Yeseva One',serif;font-size:clamp(2.5rem,5vw,4rem);color:#fff;margin-bottom:1.2rem;line-height:1.15}
.cta-title em{font-style:normal;color:var(--g)}
.cta-sub{color:rgba(255,255,255,.65);font-size:1.05rem;line-height:1.8;margin-bottom:3rem}
.cta-btns{display:flex;gap:1.2rem;justify-content:center;flex-wrap:wrap}

/* PARTNERS */
.partners{padding:4rem 3rem;background:var(--cr);text-align:center}
.partners h3{font-family:'Yeseva One',serif;font-size:1.3rem;color:#C4B5A5;margin-bottom:2.5rem}
.p-track{overflow:hidden;mask-image:linear-gradient(to right,transparent,black 10%,black 90%,transparent)}
.p-row{display:flex;gap:3.5rem;animation:mq 30s linear infinite;width:max-content}
.p-logo{height:44px;width:120px;background:#F3EEE8;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#C4B5A5;font-size:.65rem;font-weight:600;letter-spacing:.1em}

/* FOOTER NOTE */
.foot-note{background:var(--ch);padding:2.5rem 3rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
.foot-note p{font-size:.8rem;color:rgba(255,255,255,.4)}
.foot-note a{font-size:.8rem;color:var(--gl)}

/* ════ SECTION HEADER SHARED ════ */
.sec-center{text-align:center;margin-bottom:1rem}


.hero-slides-t3{position:absolute;inset:0}.t3-slide{position:absolute;inset:0;opacity:0;transition:opacity 1.6s ease}.t3-slide.active{opacity:1}.t3-slide img{width:100%;height:100%;object-fit:cover}.hero-dots{position:absolute;bottom:2.5rem;left:50%;transform:translateX(-50%);display:flex;gap:.65rem;z-index:10}.hero-dot{width:9px;height:9px;border-radius:50%;background:rgba(255,255,255,.35);cursor:pointer;transition:all .35s}.hero-dot.active{background:var(--g);width:26px;border-radius:5px}.p-logo{display:flex;align-items:center}
</style>

<?php if(!empty($birthdayMembers)): ?>
<div class="bday"><span>🎂 Birthday Celebrations: <strong><?php echo htmlspecialchars(implode(', ', array_column($birthdayMembers,'full_name'))); ?></strong> ✨</span></div>
<?php endif; ?>

  <div class="bday"><span>🎂 Birthday Celebrations: <strong>Ramesh Ji, Priya Devi, Anita Ma'am</strong> ✨</span></div>

  <nav class="nav">
    <div class="nav-logo">सेवा<span>Trust</span></div>
    <ul class="nav-links">
      <li><a href="/">Home</a></li><li><a href="about">About</a></li><li><a href="projects">Projects</a></li><li><a href="events">Events</a></li><li><a href="volunteer-register">Volunteer</a></li><li><a href="gallery">Gallery</a></li><li><a href="contact">Contact</a></li>
    </ul>
    <a href="#" href="donate" class="nav-donate">दान करें</a>
  </nav>

  <div class="hero"
  x-data="{idx:0,slides:[],init(){this.slides=JSON.parse('<?php echo $slides_json; ?>');if(this.slides.length>1)setInterval(()=>{this.idx=(this.idx+1)%this.slides.length},6200);}}">
    <div class="hero-bg"></div>
    <!-- Slider images -->
    <div class="hero-slides-t3">
      <?php foreach($slides_raw as $si3=>$ss3): ?>
      <div class="t3-slide" :class="{active: idx === <?php echo $si3; ?>}">
        <img src="<?php echo htmlspecialchars($ss3['image_path']); ?>" alt="" loading="<?php echo $si3===0?'eager':'lazy'; ?>">
      </div>
      <?php endforeach; ?>
      <?php if(empty($slides_raw)): ?><div class="t3-slide active"><img src="https://placehold.co/1920x1080/3B0764/FCD34D?text=Seva+Service" alt="" loading="eager"></div><?php endif; ?>
    </div>
    <div class="hero-ov"></div>
    <!-- Slide dots -->
    <div class="hero-dots" style="z-index:10">
      <?php foreach($slides_raw as $si3=>$ss3): ?>
      <div class="hero-dot" :class="{active: idx === <?php echo $si3; ?>}" @click="idx = <?php echo $si3; ?>"></div>
      <?php endforeach; ?>
    </div>
    <div class="deco-ring"></div>
    <div class="deco-ring-2"></div>
    <div class="hero-content">
      <span class="lotus">🪷</span>
      <span class="hero-eyebrow">सेवा परमो धर्म · Service is the Highest Duty</span>
      <h1 class="hero-h1">United in <mark>Seva</mark>,<br>Stronger in Hope</h1>
      <p class="hero-sub">Thousands of families. Hundreds of stories. One mission — to create a compassionate and just India, one act of service at a time.</p>
      <div class="hero-btns">
        <a href="#" class="btn-gd">💛 Donate Today</a>
        <a href="#" class="btn-pk">Join the Movement</a>
        <a href="#" class="btn-pp">View Projects</a>
      </div>
    </div>
  </div>

  <div class="ticker">
    <div class="ttrack">
      <span>सेवा परमो धर्म</span><span class="sep">★</span><span>Donate & Change Lives</span><span class="sep">★</span><span>Join 1000+ Volunteers</span><span class="sep">★</span><span>80G Receipts</span><span class="sep">★</span><span>दीपो भव</span><span class="sep">★</span><span>Impact India</span><span class="sep">★</span>
      <span>सेवा परमो धर्म</span><span class="sep">★</span><span>Donate & Change Lives</span><span class="sep">★</span><span>Join 1000+ Volunteers</span><span class="sep">★</span><span>80G Receipts</span><span class="sep">★</span><span>दीपो भव</span><span class="sep">★</span><span>Impact India</span><span class="sep">★</span>
    </div>
  </div>

  <div class="stats"
    x-data="{d:0,v:0,p:0,l:0,go:false,run(){if(this.go)return;this.go=true;const e=t=>1-Math.pow(1-t,3);const a=(k,end)=>{const dur=2200,s=performance.now();const f=n=>{const pr=Math.min((n-s)/dur,1);this[k]=Math.floor(end*e(pr));if(pr<1)requestAnimationFrame(f);else this[k]=end;};requestAnimationFrame(f);};a('d',<?php echo (int)$totalDonation;?>);a('v',<?php echo (int)$activeVolunteers;?>);a('p',<?php echo (int)$ongoingProjects;?>);a('l',15000);}}"
    x-intersect.once.threshold.0.2="run()">
    <div class="stats-inner">
      <div class="sc"><span class="sc-icon">💰</span><div class="sc-num"><sup>₹</sup><span x-text="d.toLocaleString('en-IN')">0</span></div><div class="sc-label">Funds Raised</div></div>
      <div class="sc"><span class="sc-icon">🙋</span><div class="sc-num" x-text="v">0</div><div class="sc-label">Active Volunteers</div></div>
      <div class="sc"><span class="sc-icon">🏗</span><div class="sc-num" x-text="p">0</div><div class="sc-label">Live Projects</div></div>
      <div class="sc"><span class="sc-icon">🌟</span><div class="sc-num" x-text="l.toLocaleString()">0</div><div class="sc-label">Lives Touched</div></div>
    </div>
  </div>

  <div class="about">
    <div class="about-inner">
      <div class="img-frame">
        <img src="<?php echo $about_image ?: 'https://placehold.co/700x480/3B0764/FCD34D?text=Our+Heritage'; ?>" alt="">
        <div class="img-badge"><strong>15+</strong><span>Years Seva</span></div>
      </div>
      <div>
        <p class="at">Our Heritage</p>
        <h2 class="atitle">Rooted in Culture,<br>Driven by <em>Compassion</em></h2>
        <p class="atxt"><?php echo mb_substr(strip_tags($about_desc ?? 'We are rooted in the timeless values of seva and solidarity. Our mission is to uplift every family through education, health, and livelihoods.'), 0, 350); ?>...</p>
        <div class="feats">
          <div class="feat"><div class="feat-i">🎯</div><span>Our Mission</span></div>
          <div class="feat"><div class="feat-i">👁</div><span>Our Vision</span></div>
          <div class="feat"><div class="feat-i">💚</div><span>Core Values</span></div>
          <div class="feat"><div class="feat-i">🌍</div><span>Our Reach</span></div>
          <div class="feat"><div class="feat-i">📜</div><span>80G Certified</span></div>
          <div class="feat"><div class="feat-i">🏛</div><span>Govt. Registered</span></div>
        </div>
        <a href="#" class="btn-pp">Read Our Journey →</a>
      </div>
    </div>
  </div>

  <div class="features">
    <div class="f-inner">
      <div class="sec-top"><p class="at" style="text-align:center;display:block">Our Platform</p><h2 class="stitle">Complete <span>Ecosystem</span> for Impact</h2></div>
      <div class="feat-grid">
        <div class="fc"><span class="fc-icon">💰</span><div class="fc-title">Donations</div><div class="fc-desc">QR, UPI, bank. 80G receipts. OTP history access.</div><a class="fc-link" href="#">Donate →</a></div>
        <div class="fc"><span class="fc-icon">🪪</span><div class="fc-title">Volunteer Hub</div><div class="fc-desc">ID cards, certificates, QR verification, dashboard.</div><a class="fc-link" href="#">Join →</a></div>
        <div class="fc"><span class="fc-icon">👥</span><div class="fc-title">Membership</div><div class="fc-desc">Designations, fees, referral system, Razorpay.</div><a class="fc-link" href="#">Become Member →</a></div>
        <div class="fc"><span class="fc-icon">📋</span><div class="fc-title">Projects</div><div class="fc-desc">Real-time funding, campaign galleries, targets.</div><a class="fc-link" href="#">View →</a></div>
        <div class="fc"><span class="fc-icon">📅</span><div class="fc-title">Events</div><div class="fc-desc">Registration, photo galleries, video showcases.</div><a class="fc-link" href="#">Explore →</a></div>
        <div class="fc"><span class="fc-icon">📜</span><div class="fc-title">Certificates</div><div class="fc-desc">Auto PDF certificates, QR codes, admin control.</div><a class="fc-link" href="#">Download →</a></div>
        <div class="fc"><span class="fc-icon">📊</span><div class="fc-title">Admin Panel</div><div class="fc-desc">Charts, reports, CSV/PDF exports, birthday alerts.</div><a class="fc-link" href="#">Admin →</a></div>
        <div class="fc"><span class="fc-icon">📱</span><div class="fc-title">PWA App</div><div class="fc-desc">Install on mobile, offline support, push alerts.</div><a class="fc-link" href="#">Install →</a></div>
      </div>
    </div>
  </div>

  <div class="projects">
    <div class="proj-inner">
      <div class="sec-top">
        <p class="at" style="display:block;text-align:center">Active Campaigns</p>
        <h2 class="stitle">Projects That <span>Need</span> Your Support</h2>
        <div style="display:flex;justify-content:center;gap:8px;margin-top:10px;flex-wrap:wrap">
          <a href="apply-health-card.php" class="btn-pp" style="background:#0F8B8D;color:#fff;font-size:0.8rem;padding:6px 14px">🏥 Health Card (Apply / Renew)</a>
          <a href="projects.php" class="btn-pp" style="font-size:0.8rem;padding:6px 14px">View All Projects →</a>
        </div>
      </div>
      <div class="p-grid">
        <div class="pc"><div class="pc-img"><img src="https://placehold.co/400x215/3B0764/FCD34D?text=Education" alt=""><div class="pc-badge">Active</div></div><div class="pc-body"><h3 class="pc-title">Scholarships for Rural Children</h3><div class="pbt"><div class="pbf" style="width:72%"></div></div><div class="pm"><span>₹72,000 raised</span><span>72% of goal</span></div><a href="donate.php" class="d-btn">Donate Now 💛</a></div></div>
        <div class="pc"><div class="pc-img"><img src="https://placehold.co/400x215/6D28D9/FCD34D?text=Healthcare" alt=""><div class="pc-badge">Active</div></div><div class="pc-body"><h3 class="pc-title">Free Medical Camps — Rural UP</h3><div class="pbt"><div class="pbf" style="width:55%"></div></div><div class="pm"><span>₹55,000 raised</span><span>55% of goal</span></div><a href="donate.php" class="d-btn">Donate Now 💛</a></div></div>
        <div class="pc"><div class="pc-img"><img src="https://placehold.co/400x215/EC4899/ffffff?text=Water" alt=""><div class="pc-badge">Active</div></div><div class="pc-body"><h3 class="pc-title">Clean Water for 500 Families</h3><div class="pbt"><div class="pbf" style="width:38%"></div></div><div class="pm"><span>₹38,000 raised</span><span>38% of goal</span></div><a href="donate.php" class="d-btn">Donate Now 💛</a></div></div>
      </div>
      <div style="display:flex;justify-content:center;gap:12px;margin-top:24px;flex-wrap:wrap">
        <a href="projects.php" class="btn-pp" style="font-size:0.85rem;padding:8px 18px">Browse All Projects →</a>
        <a href="apply-health-card.php" class="btn-pp" style="background:#0F8B8D;color:#fff;font-size:0.85rem;padding:8px 18px">Apply / Renew / Download Health Card</a>
      </div>
    </div>
  </div>

  <div class="hiw">
    <div class="hiw-inner">
      <div class="sec-top"><p class="at" style="color:rgba(255,255,255,.5);display:block">Easy Steps</p><h2 class="stitle" style="color:#fff">How to <span>Donate</span></h2></div>
      <div class="hiw-grid">
        <div class="hiw-step"><div class="hiw-num">1</div><div class="hiw-title">Choose Campaign</div><div class="hiw-desc">Browse active campaigns with real-time progress.</div></div>
        <div class="hiw-step"><div class="hiw-num">2</div><div class="hiw-title">Pay via QR/UPI</div><div class="hiw-desc">Upload payment screenshot securely.</div></div>
        <div class="hiw-step"><div class="hiw-num">3</div><div class="hiw-title">Admin Verifies</div><div class="hiw-desc">Confirmed within 24 hours with notification.</div></div>
        <div class="hiw-step"><div class="hiw-num">4</div><div class="hiw-title">Download Receipt</div><div class="hiw-desc">80G tax-exempt receipt with your PAN details.</div></div>
      </div>
    </div>
  </div>

  <div class="events">
    <div class="ev-inner">
      <div class="sec-top"><p class="at" style="display:block;text-align:center">Calendar</p><h2 class="stitle">Upcoming <span>Events</span></h2></div>
      <div class="ev-grid">
        <div class="ev-card"><div class="ev-img"><img src="" alt="" style="background:linear-gradient(135deg,#3B0764,#6D28D9)"><div class="ev-date-pill">14 Apr</div></div><div class="ev-body"><div class="ev-cat">Health Camp</div><div class="ev-title">Free Eye Checkup Camp</div><div class="ev-loc">📍 District Hospital, Kanpur</div></div></div>
        <div class="ev-card"><div class="ev-img"><img src="" alt="" style="background:linear-gradient(135deg,#F59E0B,#EC4899)"><div class="ev-date-pill">21 Apr</div></div><div class="ev-body"><div class="ev-cat">Education</div><div class="ev-title">Annual Scholarship Distribution</div><div class="ev-loc">📍 Town Hall, Lucknow</div></div></div>
        <div class="ev-card"><div class="ev-img"><img src="" alt="" style="background:linear-gradient(135deg,#EC4899,#3B0764)"><div class="ev-date-pill">05 May</div></div><div class="ev-body"><div class="ev-cat">Environment</div><div class="ev-title">100-Tree Plantation Drive</div><div class="ev-loc">📍 Gomti Riverbank, Lucknow</div></div></div>
      </div>
    </div>
  </div>

  <div class="gallery">
    <div class="g-inner">
      <div class="sec-top"><p class="at" style="display:block;text-align:center">Gallery</p><h2 class="stitle">Moments of <span>Seva</span></h2></div>
      <div class="g-strip">
        <div class="g-item"><img src="" alt="" style="background:linear-gradient(135deg,#3B0764,#6D28D9)"></div>
        <div class="g-item"><img src="" alt="" style="background:linear-gradient(135deg,#F59E0B,#FCD34D)"></div>
        <div class="g-item"><img src="" alt="" style="background:linear-gradient(135deg,#EC4899,#F472B6)"></div>
        <div class="g-item"><img src="" alt="" style="background:linear-gradient(135deg,#1C1917,#3B0764)"></div>
        <div class="g-item"><img src="" alt="" style="background:linear-gradient(135deg,#6D28D9,#EC4899)"></div>
        <div class="g-item"><img src="" alt="" style="background:linear-gradient(135deg,#F59E0B,#3B0764)"></div>
      </div>
    </div>
  </div>

  <div class="testi">
    <div class="testi-inner">
      <div class="sec-top"><p class="at" style="display:block;text-align:center">Community</p><h2 class="stitle">What Our <span>Family</span> Says</h2></div>
      <div class="t-grid">
        <div class="tc"><div class="tc-stars">★★★★★</div><p class="tc-text">"इस संस्था की पारदर्शिता देखकर मन प्रसन्न हो गया। मेरा दान सीधे ज़रूरतमंदों तक पहुंचा।"</p><div class="tc-auth"><div class="tc-av">R</div><div><div class="tc-name">Ramesh Sharma</div><div class="tc-role">Donor, Kanpur</div></div></div></div>
        <div class="tc"><div class="tc-stars">★★★★★</div><p class="tc-text">"Volunteer ke roop mein ID card aur certificate paana bahut meaningful laga. Ek professional feel."</p><div class="tc-auth"><div class="tc-av">P</div><div><div class="tc-name">Priya Gupta</div><div class="tc-role">Volunteer, Lucknow</div></div></div></div>
        <div class="tc"><div class="tc-stars">★★★★★</div><p class="tc-text">"Membership portal se appointment letter aur ID card turant mila. Bahut achha system hai."</p><div class="tc-auth"><div class="tc-av">A</div><div><div class="tc-name">Anil Verma</div><div class="tc-role">Member, Delhi</div></div></div></div>
      </div>
    </div>
  </div>

  <div class="vol-cta">
    <div class="vol-cta-in">
      <div><div class="vc-h2">सेवा में जुड़ें।<br>Become a Volunteer Today.</div><p class="vc-p">342+ active volunteers. Official ID card, certificate, and dashboard. Real impact, real recognition.</p></div>
      <div class="vc-btns"><a href="#" class="btn-gd">Register Now</a><a href="#" class="btn-pp" style="border-radius:50px">Know More</a></div>
    </div>
  </div>

  <div class="cta">
    <div class="cta-deco">दीप</div>
    <div class="cta-inner">
      <h2 class="cta-title">Every Act of <em>Giving</em><br>Lights a Thousand Lives</h2>
      <p class="cta-sub">Join our family of change-makers. Your generosity fuels education, nutrition, healthcare, and hope across India.</p>
      <div class="cta-btns"><a href="#" class="btn-gd">Donate Now 💛</a><a href="#" class="btn-pk-outline">Volunteer With Us</a></div>
    </div>
  </div>

  <?php if(!empty($sponsors)): ?><div class="partners"><h3>Partners &amp; Supporters</h3><div class="p-track"><div class="p-row"><?php foreach(array_merge($sponsors,$sponsors,$sponsors) as $sp): ?><a href="<?php echo htmlspecialchars($sp['website_url']??'#'); ?>" target="_blank" class="p-logo"><img src="<?php echo htmlspecialchars($sp['logo_path']); ?>" alt="<?php echo htmlspecialchars($sp['name']); ?>" style="height:44px;object-fit:contain;filter:grayscale(1);opacity:.4;transition:all .3s" onmouseover="this.style.filter='none';this.style.opacity='1'" onmouseout="this.style.filter='grayscale(1)';this.style.opacity='.4'"></a><?php endforeach; ?></div></div></div><?php endif; ?>
  <div class="foot-note"><p>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name'] ?? 'SevaTrust'); ?> · सेवा ही धर्म</p><a href="admin/dashboard">Admin Panel →</a></div>
</div><!-- /T3 -->

