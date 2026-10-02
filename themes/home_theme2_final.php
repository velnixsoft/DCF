<?php
/**
 * HOME THEME 2 — "BOLD IMPACT"  v5.0
 * ─────────────────────────────────────────────────────
 * Colors  : Teal #0A4D68 · Coral #EE4E34 · Ivory #F9F5F0
 * Fonts   : Syne + Outfit
 * UX Fixes: Hero 85vh, tight 8px-grid spacing, font scale
 *           tightened, slider images contained, responsive
 * ─────────────────────────────────────────────────────
 */
?>
<link rel="preconnect" href="https://fonts.googleapis.com">


<style>
/* ── DESIGN TOKENS ── */
:root{
  /* ── 3 PRIMARY BRAND COLORS (FROM LOGO) ── */
  --tl:#1070B0;   /* Primary Blue */
  --tm:#0d5b90;   /* Darker Blue */
  --co:#F0A010;   /* Primary Warm Orange */
  --cl:#ffd07d;   /* Light Warm Orange */
  --iv:#fffcf5;   /* Background Warm Cream */

  /* Derived from logo colors */
  --dk:#1F2937;   /* Dark Navy */
  --tx:#1F2937;   /* Body text */
  --muted:#4B5563;
  --white:#FFFFFF;

  /* Shadows */
  --sh1:0 1px 4px rgba(15,139,141,.07);
  --sh2:0 4px 18px rgba(15,139,141,.1);
  --sh3:0 14px 44px rgba(244,166,64,.14);

  /* Radii */
  --r:18px; --rl:18px;

  /* Transition */
  --ease:all .28s cubic-bezier(.4,0,.2,1);
}

/* ── RESET ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{
  font-family:'Outfit',system-ui,sans-serif;
  background:var(--iv);color:var(--tx);
  line-height:1.6;overflow-x:hidden;font-size:15px;
}
a{text-decoration:none;color:inherit}
img{max-width:100%;height:auto;display:block}
.w{width:100%;max-width:1200px;margin:0 auto;padding:0 20px}

/* ── TYPOGRAPHY ── */
.label{
  font-size:.68rem;font-weight:700;letter-spacing:.18em;
  text-transform:uppercase;color:var(--co);display:block;margin-bottom:5px;
}
.h-xl{
  font-family:'Syne',sans-serif;
  font-size:clamp(1.5rem,3vw,2.3rem);
  font-weight:800;line-height:1.2;color:var(--dk);
}
.h-lg{
  font-family:'Syne',sans-serif;
  font-size:clamp(1.3rem,2.4vw,1.85rem);
  font-weight:800;line-height:1.25;color:var(--dk);
}
.body-txt{font-size:.92rem;color:#718096;line-height:1.75}

/* ── BUTTONS ── */
.btn{
  display:inline-flex;align-items:center;justify-content:center;
  gap:6px;font-weight:700;border-radius:var(--r);
  transition:var(--ease);font-size:.86rem;
  padding:10px 22px;white-space:nowrap;line-height:1;
  font-family:'Outfit',sans-serif;
}
.btn-co{
  background:linear-gradient(135deg,var(--co),var(--cl));
  color:#fff;box-shadow:0 4px 18px rgba(238,78,52,.3);
}
.btn-co:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(238,78,52,.45)}
.btn-tl{background:var(--tl);color:#fff}
.btn-tl:hover{background:var(--tm);transform:translateY(-2px)}
.btn-ghost{border:1.5px solid rgba(255,255,255,.5);color:#fff;backdrop-filter:blur(6px)}
.btn-ghost:hover{background:rgba(255,255,255,.1);border-color:#fff}
.btn-white{background:#fff;color:var(--co)}
.btn-white:hover{box-shadow:var(--sh2);transform:translateY(-2px)}
.btn-border-white{border:1.5px solid rgba(255,255,255,.55);color:#fff}
.btn-border-white:hover{background:rgba(255,255,255,.12)}
.btn-donate{
  display:block;text-align:center;background:var(--co);color:#fff;
  padding:9px;border-radius:var(--r);font-size:.84rem;font-weight:700;
  transition:var(--ease);
}
.btn-donate:hover{background:var(--cl)}
.btn-sm{padding:8px 18px;font-size:.82rem}

/* ── BIRTHDAY ── */
.bday{
  background:linear-gradient(135deg,var(--tl),var(--tm));
  padding:9px 20px;text-align:center;
}
.bday p{color:rgba(255,255,255,.92);font-size:.82rem;font-weight:500}
.bday strong{color:#FCD34D}

/* ─────────────────────────────────────
   HERO — SPLIT SCREEN
   85vh max, slider image contained
───────────────────────────────────── */
.hero{
  height:85vh;           /* ← not 100vh */
  min-height:480px;
  max-height:780px;
  display:grid;
  grid-template-columns:1fr 1.2fr;
  overflow:hidden;
}
/* LEFT PANEL */
.hero-left{
  background:linear-gradient(165deg,var(--dk) 0%,var(--tl) 100%);
  display:flex;flex-direction:column;justify-content:center;
  padding:48px 52px;position:relative;z-index:2;overflow:hidden;
}
/* Diagonal slash effect */
.hero-left::after{
  content:'';position:absolute;right:-70px;top:0;bottom:0;width:140px;
  background:var(--dk);clip-path:polygon(0 0,0 100%,100% 100%);z-index:1;
}
.hero-left-content{position:relative;z-index:2}

/* Tag badge */
.hero-tag{
  background:linear-gradient(135deg,var(--co),var(--cl));
  color:#fff;font-size:.65rem;font-weight:700;letter-spacing:.18em;
  text-transform:uppercase;padding:0px 14px;border-radius:var(--r);
  display:inline-block;margin-bottom:10px;
  margin-top:10px;
  box-shadow:0 6px 20px rgba(238,78,52,.38);
  animation:fu .7s ease .2s both;
}
@keyframes fu{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

/* Hero title — TIGHTENED */
.hero-h1{
  font-family:'Syne',sans-serif;
  font-size:clamp(1.8rem,4.5vw,3.4rem);  /* was 5.8rem — way too big */
  font-weight:800;color:#fff;line-height:1.1;
  letter-spacing:-.02em;margin-bottom:14px;
  animation:fu .7s ease .4s both;
}
.hero-h1 em{
  font-style:normal;
  background:linear-gradient(135deg,var(--cl),#FFA585);
  -webkit-background-clip:text;background-clip:text;
  -webkit-text-fill-color:transparent;
}
.hero-sub{
  color:rgba(255,255,255,.68);
  font-size:clamp(.82rem,1.8vw,.95rem);  /* tightened */
  line-height:1.72;max-width:420px;margin-bottom:24px;
  animation:fu .7s ease .6s both;
}
.hero-btns{
  display:flex;gap:10px;flex-wrap:wrap;
  animation:fu .7s ease .8s both;
}

/* Counters below hero buttons */
.hero-counts{
  display:flex;gap:24px;margin-top:28px;
  padding-top:24px;border-top:1px solid rgba(255,255,255,.1);
  flex-wrap:wrap;
  animation:fu .7s ease 1s both;
}
.hc-num{
  font-family:'Syne',sans-serif;
  font-size:clamp(1.4rem,2.5vw,2rem);  /* was 2.4rem */
  font-weight:800;color:#fff;line-height:1;
}
.hc-lbl{
  color:rgba(255,255,255,.45);font-size:.62rem;
  letter-spacing:.12em;text-transform:uppercase;margin-top:3px;
}

/* RIGHT PANEL — image slider */
.hero-right{position:relative;overflow:hidden}
.hero-img{
  position:absolute;inset:0;width:100%;height:100%;
  object-fit:cover;object-position:center;  /* contained */
  opacity:0;transition:opacity 1.8s cubic-bezier(.4,0,.2,1);
}
.hero-img.on{opacity:1}
.hero-right-ov{
  position:absolute;inset:0;
  background:linear-gradient(to right,rgba(13,27,42,.52),transparent 55%);
}

/* ── TICKER / MARQUEE ── */
.ticker{
  background:linear-gradient(90deg,var(--tm),var(--tl));
  overflow:hidden;padding:9px 0;
}
.ticker-track{
  display:flex;white-space:nowrap;
  animation:tick 36s linear infinite;
}
.ticker-track span{
  color:rgba(255,255,255,.92);font-size:.76rem;font-weight:600;
  padding:0 24px;letter-spacing:.05em;
}
@keyframes tick{from{transform:translateX(0)}to{transform:translateX(-50%)}}

/* ── STATS BAR ── */
.stats{
  background:linear-gradient(135deg,var(--tl),var(--tm));
  padding:32px 20px;
}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.stat{
  background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.13);
  border-radius:var(--rl);padding:20px 16px;text-align:center;
  transition:var(--ease);
}
.stat:hover{background:rgba(255,255,255,.15);transform:translateY(-4px)}
.stat-icon{font-size:1.6rem;margin-bottom:7px;display:block}
.stat-num{
  font-family:'Syne',sans-serif;
  font-size:clamp(1.5rem,3vw,2.2rem);  /* tightened */
  font-weight:800;color:#fff;line-height:1;margin-bottom:4px;
}
.stat-lbl{color:rgba(255,255,255,.52);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase}

/* ── SECTION SPACING — 8px grid ── */
.sec{padding:52px 20px}
.sec-sm{padding:36px 20px}
.sec-hd{text-align:center;margin-bottom:28px}
.sec-hd.left{text-align:left}
.sec-sub{color:var(--muted);font-size:.88rem;max-width:480px;margin:5px auto 0;line-height:1.65}

/* ── ABOUT — full-bleed split ── */
.about{
  background:var(--white);
  display:grid;grid-template-columns:1.1fr 1fr;
}
.about-img-col{position:relative;min-height:420px;overflow:hidden}  /* was 580px */
.about-img-col img{width:100%;height:100%;object-fit:cover;background:#ccc}
.about-badge{
  position:absolute;bottom:24px;right:-14px;
  background:linear-gradient(135deg,var(--co),#C83520);color:#fff;
  padding:14px 20px;border-radius:var(--rl) 0 0 var(--rl);
  box-shadow:0 12px 36px rgba(238,78,52,.42);text-align:center;
}
.about-badge strong{
  display:block;font-family:'Syne',sans-serif;
  font-size:1.9rem;font-weight:800;line-height:1;
}
.about-badge span{font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;opacity:.9}
.about-text{padding:40px 40px;display:flex;flex-direction:column;justify-content:center}
.overtitle{
  font-size:.68rem;font-weight:700;letter-spacing:.18em;
  text-transform:uppercase;color:var(--tm);margin-bottom:7px;display:block;
}
.about-h2{
  font-family:'Syne',sans-serif;
  font-size:clamp(1.5rem,3vw,2.2rem);  /* tightened */
  font-weight:800;color:var(--dk);line-height:1.18;margin-bottom:14px;
}
.about-h2 em{font-style:normal;color:var(--co)}
.about-body{color:#718096;line-height:1.78;margin-bottom:18px;font-size:.9rem}
.val-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:20px}
.val{
  display:flex;align-items:center;gap:10px;padding:10px 12px;
  background:var(--iv);border-radius:var(--r);font-size:.83rem;font-weight:500;
  transition:var(--ease);
}
.val:hover{background:#EEF6F8;transform:translateX(2px)}
.val-icon{font-size:1.2rem}

/* ── FEATURES ── */
.feat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.feat-card{
  background:var(--white);border-radius:var(--rl);
  padding:18px 14px;border-top:3px solid transparent;
  box-shadow:var(--sh1);transition:var(--ease);
}
.feat-card:hover{border-top-color:var(--co);transform:translateY(-5px);box-shadow:var(--sh2)}
.feat-icon{font-size:1.9rem;margin-bottom:9px;display:block}
.feat-title{font-family:'Syne',sans-serif;font-size:.92rem;font-weight:700;color:var(--dk);margin-bottom:5px}
.feat-desc{font-size:.78rem;color:var(--muted);line-height:1.62;margin-bottom:7px}
.feat-lnk{font-size:.72rem;font-weight:700;color:var(--co)}

/* ── PROJECTS ── */
.proj-hd{
  display:flex;justify-content:space-between;
  align-items:flex-end;margin-bottom:22px;flex-wrap:wrap;gap:12px;
}
.proj-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.proj-card{
  background:var(--iv);border-radius:var(--rl);overflow:hidden;
  box-shadow:var(--sh1);transition:var(--ease);
}
.proj-card:hover{transform:translateY(-5px);box-shadow:var(--sh2)}
.proj-img{position:relative;height:185px;overflow:hidden}  /* tightened */
.proj-img img{
  width:100%;height:100%;object-fit:cover;
  transition:.5s cubic-bezier(.4,0,.2,1);background:#ddd;
}
.proj-card:hover .proj-img img{transform:scale(1.06)}
.proj-ov{position:absolute;inset:0;background:linear-gradient(to top,rgba(13,27,42,.52),transparent 50%)}
.proj-pct{
  position:absolute;bottom:8px;right:8px;
  background:var(--co);color:#fff;font-size:.68rem;font-weight:700;
  padding:3px 8px;border-radius:3px;
}
.proj-body{padding:14px}
.proj-title{font-family:'Syne',sans-serif;font-size:.96rem;font-weight:700;color:var(--dk);margin-bottom:5px;line-height:1.28}
.proj-desc{font-size:.78rem;color:var(--muted);line-height:1.6;margin-bottom:10px}
.pbar{height:4px;background:#D9E5EC;border-radius:2px;overflow:hidden;margin-bottom:5px}
.pfill{height:100%;background:linear-gradient(90deg,var(--tl),var(--tm));border-radius:2px}
.pmeta{display:flex;justify-content:space-between;font-size:.7rem;color:#a0aec0;margin-bottom:10px}
.view-all{text-align:center;margin-top:22px}

/* ── HOW IT WORKS ── */
.hiw{padding:52px 20px;background:var(--dk)}
.hiw-grid{
  display:grid;grid-template-columns:repeat(4,1fr);
  gap:14px;margin-top:28px;position:relative;
}
.hiw-grid::before{
  content:'';position:absolute;top:34px;left:12%;right:12%;height:1px;
  background:repeating-linear-gradient(90deg,var(--co) 0,var(--co) 14px,transparent 14px,transparent 28px);
  opacity:.28;
}
.hiw-step{text-align:center;position:relative;z-index:1}
.hiw-num{
  width:60px;height:60px;border-radius:7px;  /* tightened from 80px */
  background:linear-gradient(135deg,var(--co),var(--cl));
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 12px;font-family:'Syne',sans-serif;
  font-size:1.5rem;font-weight:800;color:#fff;  /* was 2rem */
  box-shadow:0 8px 24px rgba(238,78,52,.4);transition:var(--ease);
}
.hiw-step:hover .hiw-num{transform:translateY(-4px) rotate(4deg)}
.hiw-title{font-family:'Syne',sans-serif;font-size:.9rem;font-weight:700;color:#fff;margin-bottom:5px}
.hiw-desc{font-size:.76rem;color:rgba(255,255,255,.48);line-height:1.65}

/* ── GALLERY ── */
.gallery{padding:52px 0;background:var(--iv);overflow:hidden}
.gallery-hd{
  max-width:1200px;margin:0 auto 16px;padding:0 20px;
  display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:10px;
}
.gstrip{
  display:flex;gap:10px;overflow-x:auto;
  scroll-snap-type:x mandatory;
  scrollbar-width:thin;scrollbar-color:var(--co) transparent;
  padding:0 20px 8px;-webkit-overflow-scrolling:touch;
}
.gstrip::-webkit-scrollbar{height:3px}
.gstrip::-webkit-scrollbar-thumb{background:var(--co);border-radius:2px}
.g-item{
  flex:0 0 220px;height:155px;border-radius:var(--r);  /* tightened */
  overflow:hidden;scroll-snap-align:start;position:relative;
}
.g-item img{
  width:100%;height:100%;object-fit:cover;
  transition:.5s cubic-bezier(.4,0,.2,1);background:#ccc;
}
.g-item:hover img{transform:scale(1.1)}

/* ── TESTIMONIALS ── */
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:26px}
.tc{
  background:var(--iv);border-radius:var(--rl);
  padding:20px;border-left:3px solid var(--co);transition:var(--ease);
}
.tc:hover{transform:translateY(-3px);box-shadow:var(--sh2)}
.tc-stars{color:#F59E0B;font-size:.8rem;letter-spacing:.12em;margin-bottom:9px}
.tc-txt{font-size:.83rem;color:#718096;line-height:1.78;font-style:italic;margin-bottom:12px}
.tc-auth{display:flex;align-items:center;gap:9px}
.tc-av{
  width:38px;height:38px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--co),var(--cl));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:700;font-size:.95rem;
}
.tc-name{font-weight:700;font-size:.83rem;color:var(--dk)}
.tc-role{font-size:.7rem;color:#a0aec0}

/* ── EVENTS ── */
.ev-hd{
  display:flex;justify-content:space-between;align-items:flex-end;
  margin-bottom:20px;flex-wrap:wrap;gap:10px;
}
.ev-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.ev-card{
  background:var(--white);border-radius:var(--rl);overflow:hidden;
  display:flex;box-shadow:var(--sh1);transition:var(--ease);
  text-decoration:none;color:inherit;
}
.ev-card:hover{transform:translateX(4px);box-shadow:var(--sh2)}
.ev-date{
  background:linear-gradient(180deg,var(--tl),var(--tm));color:#fff;
  padding:14px 16px;display:flex;flex-direction:column;
  align-items:center;justify-content:center;min-width:76px;flex-shrink:0;
}
.ev-day{font-family:'Syne',sans-serif;font-size:1.85rem;font-weight:800;line-height:1}  /* was 2.2rem */
.ev-mo{font-size:.6rem;letter-spacing:.12em;text-transform:uppercase;opacity:.78;margin-top:2px}
.ev-info{padding:12px 14px;flex:1;min-width:0}
.ev-tag{font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--co);margin-bottom:3px}
.ev-title{font-weight:700;font-size:.88rem;color:var(--dk);margin-bottom:3px;line-height:1.28;word-break:break-word}
.ev-loc{font-size:.72rem;color:#a0aec0;display:flex;align-items:center;gap:4px}
.ev-loc i{color:var(--co);font-size:.65rem;flex-shrink:0}

/* ── VOLUNTEER CTA ── */
.vol-cta{
  background:linear-gradient(135deg,var(--co),#C83520);
  padding:44px 20px;position:relative;overflow:hidden;
}
.vol-cta::before{
  content:'';position:absolute;right:-60px;top:-60px;
  width:300px;height:300px;border-radius:50%;
  background:rgba(255,255,255,.07);pointer-events:none;
}
.vol-inner{
  max-width:1200px;margin:0 auto;
  display:grid;grid-template-columns:1fr auto;
  gap:24px;align-items:center;position:relative;z-index:1;
}
.vol-h2{
  font-family:'Syne',sans-serif;
  font-size:clamp(1.2rem,2.4vw,1.85rem);  /* tightened */
  color:#fff;line-height:1.22;margin-bottom:6px;font-weight:800;
}
.vol-p{color:rgba(255,255,255,.8);font-size:.86rem;line-height:1.65}
.vol-btns{display:flex;flex-direction:column;gap:9px;flex-shrink:0}

/* ── NEWS ── */
.news-grid{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-top:26px}
.news-big{
  border-radius:var(--rl);overflow:hidden;background:var(--iv);
  box-shadow:var(--sh1);transition:var(--ease);display:block;color:inherit;
}
.news-big:hover{transform:translateY(-3px);box-shadow:var(--sh2)}
.news-big-img{height:210px;overflow:hidden}  /* tightened from 290px */
.news-big-img img{
  width:100%;height:100%;object-fit:cover;
  transition:.5s cubic-bezier(.4,0,.2,1);background:#ccc;
}
.news-big:hover .news-big-img img{transform:scale(1.04)}
.news-big-body{padding:16px}
.news-tag{font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--co);margin-bottom:4px}
.news-title{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:var(--dk);margin-bottom:6px;line-height:1.32}
.news-exc{font-size:.78rem;color:var(--muted);line-height:1.68}
.news-small{display:flex;flex-direction:column;gap:12px}
.news-mini{
  border-radius:var(--rl);overflow:hidden;background:var(--iv);
  box-shadow:var(--sh1);transition:var(--ease);display:block;color:inherit;
}
.news-mini:hover{transform:translateY(-2px);box-shadow:var(--sh2)}
.news-mini img{width:100%;height:118px;object-fit:cover;background:#ccc}
.news-mini-body{padding:10px 12px}
.news-mini .news-title{font-size:.88rem}

/* ── CTA BAND ── */
.cta-band{
  padding:60px 20px;background:var(--dk);
  text-align:center;position:relative;overflow:hidden;
}
.cta-band::before,.cta-band::after{
  content:'';position:absolute;border-radius:50%;background:var(--co);opacity:.06;
}
.cta-band::before{width:500px;height:500px;top:-180px;left:-120px}
.cta-band::after{width:350px;height:350px;bottom:-120px;right:-80px}
.cta-inner{position:relative;z-index:1;max-width:580px;margin:0 auto}
.cta-h2{
  font-family:'Syne',sans-serif;
  font-size:clamp(1.7rem,4vw,2.9rem);  /* was 4rem */
  font-weight:800;color:#fff;line-height:1.16;margin-bottom:12px;
}
.cta-h2 em{font-style:normal;color:var(--co)}
.cta-sub{color:rgba(255,255,255,.62);font-size:.9rem;line-height:1.75;margin-bottom:26px}
.cta-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}

/* ── PARTNERS ── */
.partners{padding:36px 20px;background:var(--white);text-align:center}
.partners-lbl{
  font-family:'Syne',sans-serif;font-size:.72rem;font-weight:700;
  letter-spacing:.18em;text-transform:uppercase;color:#CBD5E0;margin-bottom:20px;display:block;
}
.p-track{overflow:hidden;mask-image:linear-gradient(to right,transparent,black 10%,black 90%,transparent)}
.p-row{display:flex;gap:32px;animation:tick 34s linear infinite;width:max-content}
.p-item img{height:38px;object-fit:contain;filter:grayscale(1);opacity:.42;transition:var(--ease)}
.p-item img:hover{filter:none;opacity:1;transform:scale(1.06)}

/* ─────────────────────────────────────
   RESPONSIVE — TABLET ≤1024px
───────────────────────────────────── */
@media(max-width:1024px){
  .feat-grid{grid-template-columns:repeat(2,1fr)}
  .proj-grid{grid-template-columns:repeat(2,1fr)}
  .testi-grid{grid-template-columns:repeat(2,1fr)}
  .hiw-grid{grid-template-columns:repeat(2,1fr)}
  .hiw-grid::before{display:none}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .news-grid{grid-template-columns:1fr}
  .news-small{flex-direction:row}
  .news-mini{flex:1}
  .about{grid-template-columns:1fr}
  .about-img-col{min-height:320px}
  .about-badge{right:0;border-radius:var(--rl) 0 0 var(--rl)}
}

/* ─────────────────────────────────────
   RESPONSIVE — MOBILE ≤768px
───────────────────────────────────── */
@media(max-width:768px){
  /* Hero — stack vertically */
  .hero{
    grid-template-columns:1fr;
    height:auto;min-height:auto;max-height:none;
  }
  .hero-left{padding:36px 20px 28px;min-height:420px}
  .hero-left::after{display:none}
  .hero-right{height:220px}
  .hero-h1{font-size:clamp(1.6rem,7vw,2.4rem)}
  .hero-btns .btn{padding:9px 16px;font-size:.82rem}
  .hero-counts{gap:18px;margin-top:20px;padding-top:18px}

  /* Sections */
  .sec{padding:36px 16px}
  .hiw{padding:36px 16px}
  .cta-band{padding:44px 16px}
  .stats{padding:24px 16px}

  /* Stats */
  .stats-grid{grid-template-columns:1fr 1fr}
  .stat{padding:16px 12px}

  /* About */
  .about-text{padding:28px 20px}
  .val-grid{grid-template-columns:1fr 1fr}

  /* Features */
  .feat-grid{grid-template-columns:1fr 1fr}

  /* Projects */
  .proj-grid{grid-template-columns:1fr}
  .proj-hd{flex-direction:column;align-items:flex-start}

  /* HIW */
  .hiw-grid{grid-template-columns:1fr 1fr;gap:12px;margin-top:22px}

  /* Gallery */
  .g-item{flex:0 0 180px;height:135px}
  .gallery-hd{flex-direction:column;align-items:flex-start}

  /* Testimonials */
  .testi-grid{grid-template-columns:1fr}

  /* Events */
  .ev-grid{grid-template-columns:1fr}
  .ev-hd{flex-direction:column;align-items:flex-start}

  /* Vol CTA */
  .vol-inner{grid-template-columns:1fr;gap:18px}
  .vol-btns{flex-direction:row;flex-wrap:wrap}

  /* News */
  .news-grid{grid-template-columns:1fr}
  .news-small{flex-direction:column}

  /* CTA */
  .cta-btns{flex-direction:column;align-items:center}
  .cta-btns .btn{width:100%;max-width:280px}
}

/* ─────────────────────────────────────
   RESPONSIVE — SMALL ≤480px
───────────────────────────────────── */
@media(max-width:480px){
  .hero-left{padding:28px 16px}
  .hero-btns{flex-direction:column;align-items:stretch}
  .hero-btns .btn{justify-content:center}
  .feat-grid{grid-template-columns:1fr}
  .hiw-grid{grid-template-columns:1fr}
  .stats-grid{grid-template-columns:1fr 1fr}
  .vol-btns{flex-direction:column}
  .vol-btns .btn{width:100%}
  .ev-card{flex-direction:column}
  .ev-date{flex-direction:row;gap:8px;min-width:auto;padding:10px 14px}
  .ev-day{font-size:1.5rem}
  .val-grid{grid-template-columns:1fr}
  .sec{padding:28px 14px}
}
.bday-bar{
    z-index: 100;
    margin: 100px;
}
</style>


<!-- ══ HERO ══ -->
<section class="hero"
  x-data="{
    on:0,
    sl:<?php echo $slides_json??'[]'; ?>,
    init(){if(this.sl.length>1)setInterval(()=>{this.on=(this.on+1)%this.sl.length},5500);}
  }">
  <div class="hero-left">
    <div class="hero-left-content">
      <div class="hero-tag">
        <?php echo htmlspecialchars($settings['site_name']??'Impact NGO'); ?> — Since 2009
      </div>
      <h1 class="hero-h1">Changing Lives with <em>Purpose</em> &amp; <em>Passion</em></h1>
      <p class="hero-sub">Where compassion meets action — powering education, healthcare, and livelihoods for underserved communities across India.</p>
      <div class="hero-btns">
        <a href="donate"             class="btn btn-co">Donate Now</a>
        <a href="projects"           class="btn btn-tl">Projects</a>
        <a href="volunteer-register" class="btn btn-ghost">Volunteer →</a>
      </div>
      <!-- Counters -->
      <div class="hero-counts"
        x-data="{
          d:0,v:0,p:0,go:false,
          run(){
            if(this.go)return;this.go=true;
            const e=t=>1-Math.pow(1-t,3);
            const a=(k,end)=>{const dur=2000,s=performance.now();
              const f=n=>{const pr=Math.min((n-s)/dur,1);this[k]=Math.floor(end*e(pr));
              pr<1?requestAnimationFrame(f):this[k]=end;};requestAnimationFrame(f);};
            a('d',<?php echo (int)($totalDonation??0);?>);
            a('v',<?php echo (int)($activeVolunteers??0);?>);
            a('p',<?php echo (int)($ongoingProjects??0);?>);
          }
        }"
        x-intersect.once.threshold.0.1="run()">
        <div>
          <div class="hc-num">₹<span x-text="d.toLocaleString('en-IN')">0</span></div>
          <div class="hc-lbl">Funds Raised</div>
        </div>
        <div>
          <div class="hc-num" x-text="v.toLocaleString()">0</div>
          <div class="hc-lbl">Volunteers</div>
        </div>
        <div>
          <div class="hc-num" x-text="p">0</div>
          <div class="hc-lbl">Live Projects</div>
        </div>
      </div>
    </div>
  </div>
  <div class="hero-right">
    <?php foreach($slides_raw as $i=>$s): ?>
    <img class="hero-img" :class="{on: on===<?php echo $i;?>}"
         src="<?php echo htmlspecialchars($s['image_path']);?>"
         alt="<?php echo htmlspecialchars($s['title']??'');?>"
         loading="<?php echo $i===0?'eager':'lazy';?>">
    <?php endforeach; ?>
    <?php if(empty($slides_raw)): ?>
    <img class="hero-img on" src="https://placehold.co/1200x900/088395/ffffff?text=Bold+Impact" alt="" loading="eager">
    <?php endif; ?>
    <div class="hero-right-ov"></div>
  </div>
</section>

<!-- ══ TICKER ══ -->
<div class="ticker" aria-hidden="true">
  <div class="ticker-track">
    <?php $tk=['Architecture of Hope','Launch Your Impact','5000+ Change Agents','80G Receipts Ready','Volunteer ID Cards','Razorpay Enabled','Certificates PDF','Events & Gallery'];
    foreach(array_merge($tk,$tk) as $m): ?>
    <span><?php echo htmlspecialchars($m);?></span><span>◆</span>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══ STATS ══ -->
<div class="stats"
  x-data="{
    d:0,v:0,p:0,l:0,go:false,
    run(){
      if(this.go)return;this.go=true;
      const e=t=>1-Math.pow(1-t,3);
      const a=(k,end)=>{const dur=2000,s=performance.now();
        const f=n=>{const pr=Math.min((n-s)/dur,1);this[k]=Math.floor(end*e(pr));
        pr<1?requestAnimationFrame(f):this[k]=end;};requestAnimationFrame(f);};
      a('d',<?php echo (int)($totalDonation??0);?>);
      a('v',<?php echo (int)($activeVolunteers??0);?>);
      a('p',<?php echo (int)($ongoingProjects??0);?>);
      a('l',15000);
    }
  }"
  x-intersect.once.threshold.0.2="run()">
  <div class="w">
    <div class="stats-grid">
      <div class="stat"><span class="stat-icon">💰</span><div class="stat-num">₹<span x-text="d.toLocaleString('en-IN')">0</span></div><div class="stat-lbl">Funds Raised</div></div>
      <div class="stat"><span class="stat-icon">🙋</span><div class="stat-num" x-text="v.toLocaleString()">0</div><div class="stat-lbl">Active Volunteers</div></div>
      <div class="stat"><span class="stat-icon">🚀</span><div class="stat-num" x-text="p">0</div><div class="stat-lbl">Live Projects</div></div>
      <div class="stat"><span class="stat-icon">🌟</span><div class="stat-num" x-text="l.toLocaleString()">0</div><div class="stat-lbl">Lives Touched</div></div>
    </div>
  </div>
</div>

<!-- ══ ABOUT ══ -->
<section class="about">
  <div class="about-img-col">
    <img src="<?php echo htmlspecialchars($about_image?:'https://placehold.co/700x500/0A4D68/ffffff?text=Our+Story');?>"
         alt="About <?php echo htmlspecialchars($settings['site_name']??'');?>">
    <div class="about-badge"><strong>10+</strong><span>Years of Impact</span></div>
  </div>
  <div class="about-text">
    <span class="overtitle">Our Organization</span>
    <h2 class="about-h2">We Believe in <em>Human Potential</em> — Everywhere</h2>
    <p class="about-body"><?php echo htmlspecialchars(mb_substr(strip_tags($about_desc??''),0,340));?>…</p>
    <div class="val-grid">
      <div class="val"><span class="val-icon">🎯</span>Mission-Driven</div>
      <div class="val"><span class="val-icon">🔍</span>Transparent</div>
      <div class="val"><span class="val-icon">💡</span>Tech-Enabled</div>
      <div class="val"><span class="val-icon">🤝</span>Community First</div>
    </div>
    <a href="about" class="btn btn-tl btn-sm">Discover Our Blueprint →</a>
  </div>
</section>

<!-- ══ FEATURES ══ -->
<section class="sec" style="background:var(--iv)">
  <div class="w">
    <div class="sec-hd">
      <span class="label">Full Ecosystem</span>
      <h2 class="h-xl">Everything on This Platform</h2>
      <p class="sec-sub">One system for donations, volunteers, membership, events, certificates — admin se sab manage.</p>
    </div>
    <div class="feat-grid">
      <?php foreach([
        ['💳','Secure Payments','donate.php','Razorpay, QR, UPI, bank. 80G receipts auto.'],
        ['🪪','Volunteer System','volunteer-register.php','ID card PDF with QR, approval, dashboard.'],
        ['👥','Member Portal','member-register.php','Designations, fees, referral, appointment.'],
        ['📈','Project Analytics','projects.php','Live funding, charts, targets, CSV export.'],
        ['🎉','Events System','events.php','Creation, galleries, registrations — admin.'],
        ['📄','Certificates','certificates.php','FPDF certificates for volunteers & members.'],
        ['⚙️','Admin Control','admin/dashboard.php','Sliders, gallery, sponsors, settings, branding.'],
        ['📧','Email & Alerts','#','SMTP, birthday alerts, OTP, WhatsApp upcoming.'],
      ] as $f): ?>
      <a href="<?php echo $f[2];?>" class="feat-card">
        <span class="feat-icon"><?php echo $f[0];?></span>
        <div class="feat-title"><?php echo $f[1];?></div>
        <p class="feat-desc"><?php echo $f[3];?></p>
        <span class="feat-lnk">Learn more →</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ PROJECTS ══ -->
<section class="sec" style="background:var(--white)">
  <div class="w">
    <div class="proj-hd">
      <div>
        <span class="label">Active Campaigns</span>
        <h2 class="h-xl">Projects Needing You Now</h2>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <a href="apply-health-card.php" class="btn btn-sm" style="background:#0F8B8D;color:#fff;font-weight:700">🏥 Health Card (Apply / Renew)</a>
        <a href="projects" class="btn btn-co btn-sm">All Projects →</a>
      </div>
    </div>
    <div class="proj-grid">
      <?php foreach(($projects??[]) as $p):
        $pct=($p['target_amount']>0)?min(100,round(($p['raised_amount']/$p['target_amount'])*100)):0;
      ?>
      <div class="proj-card">
        <div class="proj-img">
          <a href="project-details.php?id=<?php echo (int)$p['id'];?>">
            <img src="<?php echo htmlspecialchars($p['thumbnail_image']);?>"
                 alt="<?php echo htmlspecialchars($p['title']);?>" loading="lazy">
          </a>
          <div class="proj-ov"></div>
          <div class="proj-pct"><?php echo $pct;?>% Funded</div>
        </div>
        <div class="proj-body">
          <h3 class="proj-title"><?php echo htmlspecialchars($p['title']);?></h3>
          <p class="proj-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($p['description']??''),0,85));?>…</p>
          <div class="pbar"><div class="pfill" style="width:<?php echo $pct;?>%"></div></div>
          <div class="pmeta">
            <span>₹<?php echo number_format($p['raised_amount']);?></span>
            <span>Goal: ₹<?php echo number_format($p['target_amount']);?></span>
          </div>
          <a href="donate.php?project_id=<?php echo (int)$p['id'];?>" class="btn-donate">Donate to Campaign</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="view-all" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px">
      <a href="projects" class="btn btn-tl btn-sm">Browse All Projects →</a>
      <a href="apply-health-card.php" class="btn btn-sm" style="background:#0F8B8D;color:#fff;font-weight:700">Apply / Renew / Download Health Card</a>
    </div>
  </div>
</section>

<!-- ══ HOW IT WORKS ══ -->
<section class="hiw">
  <div class="w">
    <div class="sec-hd">
      <span class="label" style="color:rgba(255,255,255,.5)">Simple Steps</span>
      <h2 class="h-xl" style="color:#fff">How to Donate</h2>
      <p class="sec-sub" style="color:rgba(255,255,255,.45)">Four steps from choosing a campaign to getting your 80G receipt</p>
    </div>
    <div class="hiw-grid">
      <?php foreach([
        ['1','Browse Campaigns','Find a cause from our active project listings.'],
        ['2','Pay Securely','UPI, QR, bank transfer. Upload screenshot as proof.'],
        ['3','Admin Approves','Verified within 24 hours with confirmation.'],
        ['4','Get 80G Receipt','Download tax-exempt receipt with PAN instantly.'],
      ] as $st): ?>
      <div class="hiw-step">
        <div class="hiw-num"><?php echo $st[0];?></div>
        <h4 class="hiw-title"><?php echo $st[1];?></h4>
        <p class="hiw-desc"><?php echo $st[2];?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ GALLERY ══ -->
<section class="gallery">
  <div class="gallery-hd">
    <div>
      <span class="label">Gallery</span>
      <h2 class="h-lg" style="margin-bottom:0">Moments of <span>Seva</span></h2>
    </div>
    <a href="gallery" class="btn btn-purple btn-sm">Full Gallery →</a>
  </div>

  <div class="gstrip">
    <?php
    // ✅ Default fallback data (agar DB empty ho)
    $gf = [
      ['file_path'=>'https://placehold.co/218x155/3B0764/FCD34D?text=Health+Camp','title'=>'Health Camp 2024'],
      ['file_path'=>'https://placehold.co/218x155/F59E0B/1C1917?text=School+Drive','title'=>'School Drive'],
      ['file_path'=>'https://placehold.co/218x155/EC4899/ffffff?text=Plantation','title'=>'Tree Plantation'],
      ['file_path'=>'https://placehold.co/218x155/6D28D9/ffffff?text=Award+Night','title'=>'Annual Awards'],
      ['file_path'=>'https://placehold.co/218x155/1C1917/FCD34D?text=Volunteer','title'=>'Volunteer Day'],
      ['file_path'=>'https://placehold.co/218x155/BE185D/ffffff?text=Women+Power','title'=>'Women Empowerment'],
    ];

    try {
        // ✅ DB se latest images lao
        $stmt = $pdo->prepare("
            SELECT file_path, title 
            FROM gallery 
            WHERE type = 'image' 
            AND file_path IS NOT NULL 
            AND file_path != ''
            ORDER BY id DESC 
            LIMIT 6
        ");
        $stmt->execute();
        $galleryImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ✅ agar DB empty ho to fallback use karo
        $gi = !empty($galleryImages) ? $galleryImages : $gf;

    } catch (Exception $e) {
        // ✅ agar DB error aaye to fallback use karo
        $gi = $gf;
    }

    // ✅ loop safe banaya
    foreach ($gi as $g):
        $gp = !empty($g['file_path']) ? $g['file_path'] : 'https://placehold.co/218x155?text=No+Image';
        $gt = !empty($g['title']) ? $g['title'] : 'Gallery Image';
    ?>

    <a href="gallery" class="g-item" title="<?php echo htmlspecialchars($gt); ?>">
      <img 
        src="<?php echo htmlspecialchars($gp); ?>" 
        alt="<?php echo htmlspecialchars($gt); ?>" 
        loading="lazy"
        onerror="this.src='https://placehold.co/218x155?text=No+Image';"
      >
      <div class="g-ov">
        <span><?php echo htmlspecialchars($gt); ?></span>
      </div>
    </a>

    <?php endforeach; ?>
  </div>
</section>

<!-- ══ TESTIMONIALS ══ -->
<section class="sec" style="background:var(--white)">
  <div class="w">
    <div class="sec-hd">
      <span class="label">Community Voices</span>
      <h2 class="h-xl">What They Say</h2>
    </div>
    <div class="testi-grid">
<?php

$testimonials = [];

try {
  $stmt = $pdo->prepare("
    SELECT name, role, content, stars, initial 
    FROM testimonials 
    WHERE status = 'Active'
    ORDER BY id DESC
    LIMIT 3
  ");
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!empty($rows)) {
    foreach ($rows as $t) {
      $testimonials[] = [
        htmlspecialchars($t['initial'] ?? strtoupper(substr($t['name'],0,1))),
        htmlspecialchars($t['name']),
        htmlspecialchars($t['role']),
        htmlspecialchars($t['stars'] ?? '★★★★★'),
        htmlspecialchars($t['content'])
      ];
    }
  }

} catch (Exception $e) {
  error_log($e->getMessage());
}

// fallback SAME structure
if (empty($testimonials)) {
  $testimonials = [
    ['R','Ramesh Sharma','Donor, Kanpur','★★★★★','The donation system is smooth. I got my 80G receipt within hours and could track my contribution in real time.'],
    ['P','Priya Gupta','Volunteer, Lucknow','★★★★★','My ID card with QR verification makes me feel official. The volunteer dashboard is absolutely brilliant.'],
    ['A','Anil Verma','Member, Delhi','★★★★★','Membership portal is feature-rich. Referral system, appointment letters — everything automated!'],
  ];
}

// render
foreach ($testimonials as $t): ?>

<div class="tc">
  <div class="tc-stars"><?php echo $t[3]; ?></div>
  <p class="tc-txt">"<?php echo $t[4]; ?>"</p>

  <div class="tc-auth">
    <div class="tc-av"><?php echo $t[0]; ?></div>
    <div>
      <div class="tc-name"><?php echo $t[1]; ?></div>
      <div class="tc-role"><?php echo $t[2]; ?></div>
    </div>
  </div>
</div>

<?php endforeach; ?>
</div>
  </div>
</section>

<!-- ══ EVENTS ══ -->
<section class="sec" style="background:var(--iv)">
  <div class="w">
    <div class="ev-hd">
      <div>
        <span class="label">Calendar</span>
        <h2 class="h-xl">Upcoming Events</h2>
      </div>
      <a href="events" class="btn btn-co btn-sm">All Events →</a>
    </div>

    <div class="ev-grid">
      <?php

      $el = [];

      try {
        $stmt = $pdo->prepare("
          SELECT id, title, location, event_date 
          FROM events 
          WHERE status = 'Upcoming'
          AND event_date >= CURDATE()
          ORDER BY event_date ASC 
          LIMIT 4
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
          foreach ($rows as $e) {
            $date = strtotime($e['event_date']);

            $el[] = [
              date('d', $date),
              date('M', $date),
              'Event',
              htmlspecialchars($e['title']),
              htmlspecialchars($e['location']),
              (int)$e['id']
            ];
          }
        }

      } catch (Exception $e) {
        error_log($e->getMessage());
      }

      // ✅ RENDER SAFE
      if (!empty($el)):
        foreach ($el as $ev): ?>

        <a href="event-details?id=<?php echo $ev[5]; ?>" class="ev-card">

          <div class="ev-date">
            <div class="ev-day"><?php echo $ev[0]; ?></div>
            <div class="ev-mon"><?php echo $ev[1]; ?></div>
          </div>

          <div class="ev-info">
            <div class="ev-tag"><?php echo $ev[2]; ?></div>
            <div class="ev-title"><?php echo $ev[3]; ?></div>
            <div class="ev-meta">
              <i class="fas fa-map-marker-alt"></i>
              <?php echo $ev[4]; ?>
            </div>
          </div>

        </a>

        <?php endforeach;

      else: ?>

        <!-- ✅ EMPTY STATE (no CSS break) -->
        <div style="grid-column:1/-1;text-align:center;padding:20px;">
          No upcoming events found
        </div>

      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ══ VOLUNTEER CTA ══ -->
<section class="vol-cta">
  <div class="w">
    <div class="vol-inner">
      <div>
        <h2 class="vol-h2">Be Part of the Movement.<br>Volunteer with Us.</h2>
        <p class="vol-p">Join <?php echo (int)($activeVolunteers??0);?>+ active volunteers. Get certified, make real impact, build your NGO career.</p>
      </div>
      <div class="vol-btns">
        <a href="volunteer-register" class="btn btn-white btn-sm">Register Now</a>
        <a href="about"           class="btn btn-border-white btn-sm">Know More →</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ NEWS ══ -->
<section class="sec" style="background:var(--white)">
  <div class="w">
    <div class="sec-hd left">
      <span class="label">Latest News</span>
      <h2 class="h-xl">Stories of Change</h2>
    </div>
    <div class="news-grid">
      <a href="news" class="news-big">
        <div class="news-big-img">
          <img src="https://placehold.co/680x210/0A4D68/ffffff?text=Community+Story" alt="" loading="lazy">
        </div>
        <div class="news-big-body">
          <div class="news-tag">Impact Story</div>
          <h3 class="news-title">How One Village Got Clean Water After 20 Years of Struggle</h3>
          <p class="news-exc">Through collective effort and volunteer power, a village finally has clean drinking water — a story of perseverance and solidarity...</p>
        </div>
      </a>
      <div class="news-small">
        <a href="news" class="news-mini">
          <img src="https://placehold.co/360x118/EE4E34/ffffff?text=Volunteer+Story" alt="" loading="lazy">
          <div class="news-mini-body">
            <div class="news-tag">Volunteer</div>
            <h4 class="news-title">From College Student to Certified Volunteer Leader</h4>
          </div>
        </a>
        <a href="news" class="news-mini">
          <img src="https://placehold.co/360x118/088395/ffffff?text=Fundraising" alt="" loading="lazy">
          <div class="news-mini-body">
            <div class="news-tag">Fundraising</div>
            <h4 class="news-title">₹5 Lakh Raised in 48 Hours for Flood Relief</h4>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ══ CTA BAND ══ -->
<section class="cta-band">
  <div class="cta-inner">
    <span class="label" style="color:rgba(255,255,255,.48)">Take Action</span>
    <h2 class="cta-h2">Your ₹100 Can <em>Change</em> a Life Today</h2>
    <p class="cta-sub">Every contribution powers education, feeds families, and restores hope. Join this movement for a better India.</p>
    <div class="cta-btns">
      <a href="donate"             class="btn btn-co">Donate Now</a>
      <a href="volunteer-register" class="btn btn-ghost">Volunteer With Us</a>
      <a href="projects"           class="btn btn-tl">View Projects</a>
    </div>
  </div>
</section>

<!-- ══ PARTNERS ══ -->
<?php if(!empty($sponsors)): ?>
<section class="partners">
  <span class="partners-lbl">Our Strategic Partners &amp; Sponsors</span>
  <div class="p-track">
    <div class="p-row" aria-hidden="true">
      <?php foreach(array_merge($sponsors,$sponsors,$sponsors) as $sp): ?>
      <a href="<?php echo htmlspecialchars($sp['website_url']??'#');?>" target="_blank"
         class="p-item" title="<?php echo htmlspecialchars($sp['name']);?>">
        <img src="<?php echo htmlspecialchars($sp['logo_path']);?>"
             alt="<?php echo htmlspecialchars($sp['name']);?>" loading="lazy">
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>