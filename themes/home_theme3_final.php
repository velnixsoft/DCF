<?php
/**
 * HOME THEME 3 — "FESTIVE SEVA"  v5.0
 * ─────────────────────────────────────────────────────
 * Colors  : Purple #3B0764 · Gold #F59E0B · Pink #EC4899
 * Fonts   : Yeseva One + Poppins
 * UX Fixes: Hero 85vh, 8px-grid spacing, font scale
 *           tightened, slider images contained, responsive
 * ─────────────────────────────────────────────────────
 */
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Yeseva+One&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ── DESIGN TOKENS — 3 Colors Only ── */
:root{
  /* Brand — logo palette */
  --p:#1070B0;    /* Primary Blue */
  --pm:#0d5b90;   /* Dark Blue */
  --g:#F0A010;    /* Warm Orange */
  --gl:#ffd07d;   /* Light Orange */
  --pk:#F0A010;   /* Warm Orange accent */
  --pk-d:#d48b0a; /* Darker Orange */

  /* Surfaces — derived from logo */
  --cr:#FFF8F1;   /* warm cream bg */
  --ch:#1F2937;   /* charcoal text */
  --muted:#4B5563;
  --white:#FFFFFF;
  --ivory:#FFF8F1;

  /* Shadows */
  --sh1:0 1px 5px rgba(15,139,141,.07);
  --sh2:0 5px 20px rgba(15,139,141,.11);
  --sh3:0 14px 45px rgba(244,166,64,.16);

  /* Radii */
  --r:18px; --rl:18px;

  /* Transition */
  --ease:all .28s cubic-bezier(.4,0,.2,1);
}

/* ── RESET ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{
  font-family:'Poppins',system-ui,sans-serif;
  background:var(--cr);color:var(--ch);
  line-height:1.65;overflow-x:hidden;font-size:15px;
}
a{text-decoration:none;color:inherit}
img{max-width:100%;height:auto;display:block}
.w{width:100%;max-width:1200px;margin:0 auto;padding:0 20px}

/* ── TYPOGRAPHY ── */
.label{
  font-size:.68rem;font-weight:700;letter-spacing:.18em;
  text-transform:uppercase;color:var(--pm);display:block;margin-bottom:5px;
}
.h-xl{
  font-family:'Yeseva One',serif;
  font-size:clamp(1.55rem,3.2vw,2.4rem);
  line-height:1.2;color:var(--ch);
}
.h-lg{
  font-family:'Yeseva One',serif;
  font-size:clamp(1.35rem,2.5vw,1.9rem);
  line-height:1.25;color:var(--ch);
}
.h-xl span,.h-lg span{color:var(--g)}
.body-txt{font-size:.92rem;color:#6b6360;line-height:1.78}

/* ── BUTTONS ── */
.btn{
  display:inline-flex;align-items:center;justify-content:center;
  gap:6px;font-weight:700;
  transition:var(--ease);font-size:.86rem;
  padding:10px 22px;white-space:nowrap;line-height:1;
  font-family:'Poppins',sans-serif;
}
.btn-gold{
  background:linear-gradient(135deg,var(--g),var(--gl));
  color:var(--ch);border-radius:50px;
  box-shadow:0 4px 18px rgba(245,158,11,.35);
}
.btn-gold:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(245,158,11,.52)}
.btn-pink{
  background:linear-gradient(135deg,var(--pk),var(--pk-d));
  color:#fff;border-radius:50px;
  box-shadow:0 4px 18px rgba(236,72,153,.3);
}
.btn-pink:hover{transform:translateY(-2px);filter:brightness(1.08)}
.btn-purple{
  background:linear-gradient(135deg,var(--p),var(--pm));
  color:#fff;border-radius:50px;
  box-shadow:0 4px 18px rgba(59,7,100,.3);
}
.btn-purple:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(59,7,100,.45)}
.btn-outline-gold{
  border:2px solid rgba(252,211,77,.65);color:var(--gl);
  border-radius:50px;backdrop-filter:blur(6px);
}
.btn-outline-gold:hover{background:rgba(252,211,77,.12);border-color:var(--gl)}
.btn-outline-pk{
  border:2px solid var(--pk);color:var(--pk);border-radius:50px;
}
.btn-outline-pk:hover{background:var(--pk);color:#fff}
.btn-white-pk{background:#fff;color:var(--pk);border-radius:50px}
.btn-white-pk:hover{transform:translateY(-2px);box-shadow:var(--sh2)}
.btn-border-wh{border:2px solid rgba(255,255,255,.55);color:#fff;border-radius:50px;backdrop-filter:blur(6px)}
.btn-border-wh:hover{background:rgba(255,255,255,.14);border-color:#fff}
.btn-sm{padding:8px 18px;font-size:.82rem}
.btn-donate{
  display:block;text-align:center;
  background:linear-gradient(135deg,var(--g),var(--gl));
  color:var(--ch);border-radius:50px;
  padding:9px;font-weight:700;font-size:.84rem;transition:var(--ease);
}
.btn-donate:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(245,158,11,.38)}

/* ── BIRTHDAY ── */
.bday{
  background:linear-gradient(135deg,var(--p),var(--pm));
  padding:9px 20px;text-align:center;
}
.bday p{color:rgba(255,255,255,.92);font-size:.82rem;font-weight:500}
.bday strong{color:var(--gl)}

/* ─────────────────────────────────────
   HERO — 85vh, images contained
───────────────────────────────────── */
.hero{
  position:relative;
  height:85vh;          /* ← NOT 100vh */
  min-height:480px;
  max-height:780px;
  overflow:hidden;
  background:var(--ch);
  display:flex;align-items:center;
  justify-content:center;text-align:center;
}
/* Slider */
.hero-slides{position:absolute;inset:0}
.hero-slide{
  position:absolute;inset:0;opacity:0;
  transition:opacity 1.8s cubic-bezier(.4,0,.2,1);
}
.hero-slide.on{opacity:1}
.hero-slide img{
  width:100%;height:100%;
  object-fit:cover;object-position:center; /* contained */
}
/* Overlay — 3 color purple gradient */
.hero-ov{
  position:absolute;inset:0;
  background:linear-gradient(
    160deg,
    rgba(59,7,100,.92) 0%,
    rgba(59,7,100,.65) 55%,
    rgba(245,158,11,.12) 100%
  );
}
/* Mandala rings — decorative, pointer-events off */
.ring{
  position:absolute;border-radius:50%;pointer-events:none;
  border:1.5px solid rgba(245,158,11,.18);
}
.ring-1{right:-80px;top:50%;transform:translateY(-50%);width:620px;height:620px}
.ring-2{right:20px;top:50%;transform:translateY(-50%);width:440px;height:440px;border-color:rgba(245,158,11,.1)}
.ring-3{left:-80px;top:50%;transform:translateY(-50%);width:500px;height:500px;border-color:rgba(236,72,153,.1)}

/* Hero content */
.hero-body{
  position:relative;z-index:5;
  padding:0 20px;max-width:860px;width:100%;
}
.lotus{
  font-size:2.8rem;display:block;margin-bottom:12px;
  animation:flt 5s ease-in-out infinite;
  filter:drop-shadow(0 0 16px rgba(245,158,11,.45));
}
@keyframes flt{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}

.eyebrow{
  font-size:.68rem;font-weight:700;letter-spacing:.24em;text-transform:uppercase;
  color:var(--gl);margin-bottom:14px;display:block;
  animation:fu .7s ease .3s both;
}
@keyframes fu{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}

/* Hero title — TIGHTENED */
.hero-h1{
  font-family:'Yeseva One',serif;
  font-size:clamp(1.9rem,5.5vw,3.8rem);  /* was 6.5rem — way too big */
  color:#fff;line-height:1.1;
  text-shadow:0 4px 20px rgba(0,0,0,.45);
  margin-bottom:14px;
  animation:fu .7s ease .5s both;
}
.hero-h1 mark{
  background:none;color:var(--g);
  font-family:'Yeseva One',serif;
  filter:drop-shadow(0 0 12px rgba(245,158,11,.4));
}
.hero-sub{
  color:rgba(255,255,255,.72);
  font-size:clamp(.84rem,2vw,.97rem);  /* tightened */
  line-height:1.75;max-width:540px;margin:0 auto 22px;
  animation:fu .7s ease .7s both;
}
.hero-btns{
  display:flex;gap:10px;justify-content:center;flex-wrap:wrap;
  animation:fu .7s ease .9s both;
}
/* Dots */
.hero-dots{
  position:absolute;bottom:16px;left:50%;transform:translateX(-50%);
  display:flex;gap:6px;z-index:10;
}
.hero-dot{
  width:8px;height:8px;border-radius:50%;
  background:rgba(255,255,255,.32);cursor:pointer;transition:var(--ease);
}
.hero-dot.on{background:var(--g);width:24px;border-radius:4px;box-shadow:0 3px 12px rgba(245,158,11,.5)}

/* ── TICKER ── */
.ticker{
  background:linear-gradient(90deg,var(--g),var(--gl));
  overflow:hidden;padding:9px 0;
}
.ticker-track{
  display:flex;white-space:nowrap;
  animation:tick 38s linear infinite;
}
.ticker-track span{
  font-size:.78rem;font-weight:700;color:var(--ch);
  padding:0 24px;letter-spacing:.05em;
}
.ticker-track .sep{color:rgba(28,25,23,.38)}
@keyframes tick{from{transform:translateX(0)}to{transform:translateX(-50%)}}

/* ── STATS ── */
.stats{background:var(--p);padding:32px 20px}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.sc{
  background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);
  border-radius:var(--rl);padding:20px 16px;text-align:center;transition:var(--ease);
}
.sc:hover{background:rgba(255,255,255,.15);transform:translateY(-5px)}
.sc-icon{font-size:1.7rem;display:block;margin-bottom:7px}
.sc-num{
  font-family:'Yeseva One',serif;
  font-size:clamp(1.5rem,3vw,2.2rem);  /* tightened from 3rem */
  color:#fff;line-height:1;margin-bottom:4px;
}
.sc-num sup{font-size:.9rem;vertical-align:super;color:var(--g)}
.sc-lbl{color:rgba(255,255,255,.5);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase}

/* ── SECTION SPACING ── */
.sec{padding:52px 20px}       /* was 6.5-7rem */
.sec-hd{text-align:center;margin-bottom:28px}
.sec-hd.left{text-align:left}
.sec-sub{color:var(--muted);font-size:.88rem;max-width:480px;margin:5px auto 0;line-height:1.65}

/* ── ABOUT ── */
.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:44px;align-items:center}
.img-frame{position:relative;padding:14px 0 14px 14px}
.img-frame::before{
  content:'';position:absolute;top:0;left:0;right:14px;bottom:0;
  border:2.5px solid var(--g);border-radius:var(--rl);z-index:0;
  box-shadow:0 0 0 6px rgba(245,158,11,.06);
}
.img-frame img{
  position:relative;z-index:1;width:100%;border-radius:var(--rl);
  box-shadow:var(--sh3);height:380px;  /* was 490px */
  object-fit:cover;background:#ddd;
}
.img-badge{
  position:absolute;z-index:2;bottom:-14px;right:-12px;
  background:linear-gradient(135deg,var(--pk),var(--pk-d));color:#fff;
  border-radius:50%;width:96px;height:96px;  /* was 115px */
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  box-shadow:0 8px 28px rgba(236,72,153,.45);
}
.img-badge strong{font-family:'Yeseva One',serif;font-size:1.7rem;line-height:1}
.img-badge span{font-size:.58rem;letter-spacing:.1em;text-transform:uppercase;text-align:center;line-height:1.2}
.about-h2{font-family:'Yeseva One',serif;font-size:clamp(1.5rem,3vw,2.2rem);color:var(--ch);line-height:1.2;margin-bottom:12px}
.about-h2 em{font-style:normal;color:var(--pk)}
.about-body{color:#6b6360;line-height:1.78;margin-bottom:16px;font-size:.9rem}
.feat-list{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-bottom:18px}
.fl-item{
  background:#fff;border-radius:var(--r);padding:9px 11px;
  border:1px solid #F0EBE5;display:flex;align-items:center;gap:9px;
  transition:var(--ease);
}
.fl-item:hover{border-color:var(--g);transform:translateX(2px)}
.fl-icon{
  width:32px;height:32px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--p),var(--pm));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:.85rem;
}
.fl-item span{font-size:.82rem;font-weight:500}

/* ── FEATURES ── */
.feat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.fc{
  background:var(--cr);border-radius:var(--rl);
  padding:18px 14px;text-align:center;
  border:1px solid #F0EBE5;transition:var(--ease);cursor:pointer;
}
.fc:hover{transform:translateY(-5px);box-shadow:var(--sh2);border-color:transparent;background:#fff}
.fc-icon{font-size:2rem;margin-bottom:9px;display:block}
.fc-title{font-family:'Yeseva One',serif;font-size:.92rem;color:var(--ch);margin-bottom:5px}
.fc-desc{font-size:.76rem;color:var(--muted);line-height:1.65;margin-bottom:7px}
.fc-lnk{font-size:.7rem;font-weight:700;color:var(--pm)}

/* ── PROJECTS ── */
.proj-hd{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.proj-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.proj-card{
  background:#fff;border-radius:var(--rl);overflow:hidden;
  box-shadow:var(--sh1);transition:var(--ease);
}
.proj-card:hover{transform:translateY(-5px);box-shadow:var(--sh2)}
.proj-img{position:relative;height:185px;overflow:hidden}  /* was 225px */
.proj-img img{
  width:100%;height:100%;object-fit:cover;
  transition:.5s cubic-bezier(.4,0,.2,1);background:#ddd;
}
.proj-card:hover .proj-img img{transform:scale(1.06)}
.proj-badge{
  position:absolute;top:8px;left:8px;background:var(--p);color:#fff;
  font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  padding:3px 9px;border-radius:50px;
}
.proj-pct{
  position:absolute;top:8px;right:8px;
  background:rgba(0,0,0,.58);color:#fff;
  font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:50px;
}
.proj-body{padding:14px}
.proj-title{font-family:'Yeseva One',serif;font-size:.95rem;color:var(--ch);margin-bottom:6px;line-height:1.28}
.proj-desc{font-size:.78rem;color:var(--muted);line-height:1.6;margin-bottom:9px}
.pbt{height:5px;background:#EDE8E1;border-radius:2px;overflow:hidden;margin-bottom:5px}
.pbf{height:100%;background:linear-gradient(90deg,var(--p),var(--pk));border-radius:2px}
.pmeta{display:flex;justify-content:space-between;font-size:.7rem;color:var(--muted);margin-bottom:10px}
.view-all{text-align:center;margin-top:22px}

/* ── HOW IT WORKS ── */
.hiw{
  padding:52px 20px;
  background:linear-gradient(135deg,var(--p),#1a0a3c);
}
.hiw-grid{
  display:grid;grid-template-columns:repeat(4,1fr);
  gap:14px;margin-top:28px;
}
.hiw-step{
  text-align:center;padding:22px 14px;
  background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
  border-radius:var(--rl);transition:var(--ease);
}
.hiw-step:hover{background:rgba(255,255,255,.11);transform:translateY(-5px);border-color:rgba(252,211,77,.28)}
.hiw-num{
  width:58px;height:58px;border-radius:50%;  /* was 74px */
  background:linear-gradient(135deg,var(--g),var(--gl));
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 12px;
  font-family:'Yeseva One',serif;
  font-size:1.4rem;color:var(--ch);  /* was 1.9rem */
  box-shadow:0 8px 22px rgba(245,158,11,.42);transition:var(--ease);
}
.hiw-step:hover .hiw-num{transform:scale(1.1) rotate(-4deg)}
.hiw-title{font-family:'Yeseva One',serif;font-size:.9rem;color:#fff;margin-bottom:5px}
.hiw-desc{font-size:.74rem;color:rgba(255,255,255,.5);line-height:1.65}

/* ── GALLERY ── */
.gallery{padding:52px 0;background:var(--ivory);overflow:hidden}
.gallery-hd{
  max-width:1200px;margin:0 auto 16px;padding:0 20px;
  display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:10px;
}
.gstrip{
  display:flex;gap:10px;overflow-x:auto;
  scroll-snap-type:x mandatory;
  scrollbar-width:thin;scrollbar-color:var(--g) transparent;
  padding:0 20px 8px;-webkit-overflow-scrolling:touch;
}
.gstrip::-webkit-scrollbar{height:3px}
.gstrip::-webkit-scrollbar-thumb{background:var(--g);border-radius:2px}
.g-item{
  flex:0 0 218px;height:155px;border-radius:var(--rl);  /* tightened */
  overflow:hidden;scroll-snap-align:start;position:relative;cursor:pointer;
}
.g-item img{
  width:100%;height:100%;object-fit:cover;
  transition:.5s cubic-bezier(.4,0,.2,1);
  background:linear-gradient(135deg,var(--p),var(--pk));
}
.g-item:hover img{transform:scale(1.1)}
.g-ov{
  position:absolute;inset:0;
  background:linear-gradient(to top,rgba(59,7,100,.72),transparent 55%);
  opacity:0;transition:var(--ease);display:flex;align-items:flex-end;padding:9px;
}
.g-item:hover .g-ov{opacity:1}
.g-ov span{color:#fff;font-size:.72rem;font-weight:600}

/* ── TESTIMONIALS ── */
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:26px}
.tc{
  background:#fff;border-radius:var(--rl);
  padding:18px;border-top:3px solid var(--g);transition:var(--ease);
}
.tc:hover{transform:translateY(-4px);box-shadow:var(--sh2)}
.tc-stars{color:var(--g);font-size:.82rem;margin-bottom:9px;letter-spacing:.1em}
.tc-txt{font-size:.82rem;color:#78716C;line-height:1.75;font-style:italic;margin-bottom:12px}
.tc-auth{display:flex;align-items:center;gap:9px}
.tc-av{
  width:38px;height:38px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--p),var(--pm));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:700;font-size:.95rem;
}
.tc-name{font-weight:600;font-size:.83rem;color:var(--ch)}
.tc-role{font-size:.7rem;color:var(--muted)}

/* ── EVENTS ── */
.ev-hd{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap;gap:10px}
.ev-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.ev-card{
  background:var(--cr);border-radius:var(--rl);overflow:hidden;
  border:1px solid #F0EBE5;transition:var(--ease);display:block;color:inherit;
}
.ev-card:hover{transform:translateY(-5px);box-shadow:var(--sh2);border-color:transparent}
.ev-img{height:130px;overflow:hidden;position:relative}  /* was 165px */
.ev-img-inner{
  width:100%;height:100%;object-fit:cover;
  background:linear-gradient(135deg,var(--p),var(--pm));
  transition:.5s cubic-bezier(.4,0,.2,1);display:block;
}
.ev-card:hover .ev-img-inner{transform:scale(1.06)}
.ev-date-pill{
  position:absolute;top:8px;left:8px;
  background:linear-gradient(135deg,var(--g),var(--gl));color:var(--ch);
  font-size:.65rem;font-weight:700;padding:3px 9px;border-radius:50px;
  box-shadow:0 3px 12px rgba(245,158,11,.38);
}
.ev-body{padding:13px}
.ev-cat{font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--pm);margin-bottom:3px}
.ev-title{font-family:'Yeseva One',serif;font-size:.9rem;color:var(--ch);margin-bottom:3px;line-height:1.3}
.ev-loc{font-size:.72rem;color:var(--muted);display:flex;align-items:center;gap:4px}
.ev-loc i{color:var(--pk);font-size:.62rem;flex-shrink:0}

/* ── VOLUNTEER CTA ── */
.vol-cta{
  background:linear-gradient(135deg,var(--pk),var(--pk-d));
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
  font-family:'Yeseva One',serif;
  font-size:clamp(1.3rem,2.5vw,2rem);  /* tightened */
  color:#fff;line-height:1.22;margin-bottom:6px;
}
.vol-p{color:rgba(255,255,255,.82);font-size:.86rem;line-height:1.65}
.vol-btns{display:flex;flex-direction:column;gap:9px;flex-shrink:0}

/* ── NEWS ── */
.news-grid{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-top:26px}
.news-big{
  border-radius:var(--rl);overflow:hidden;background:#fff;
  box-shadow:var(--sh1);transition:var(--ease);display:block;color:inherit;
}
.news-big:hover{transform:translateY(-3px);box-shadow:var(--sh2)}
.news-big-img{height:210px;overflow:hidden}  /* was 290px */
.news-big-img img{
  width:100%;height:100%;object-fit:cover;
  transition:.5s cubic-bezier(.4,0,.2,1);background:#ddd;
}
.news-big:hover .news-big-img img{transform:scale(1.04)}
.news-big-body{padding:16px}
.news-tag{font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--pk);margin-bottom:4px}
.news-title{font-family:'Yeseva One',serif;font-size:1rem;color:var(--ch);margin-bottom:6px;line-height:1.32}
.news-exc{font-size:.78rem;color:var(--muted);line-height:1.68}
.news-small{display:flex;flex-direction:column;gap:12px}
.news-mini{
  border-radius:var(--rl);overflow:hidden;background:#fff;
  box-shadow:var(--sh1);transition:var(--ease);display:block;color:inherit;
}
.news-mini:hover{transform:translateY(-2px);box-shadow:var(--sh2)}
.news-mini img{width:100%;height:118px;object-fit:cover;background:#ddd}
.news-mini-body{padding:10px 12px}
.news-mini .news-title{font-size:.88rem}

/* ── CTA BAND ── */
.cta-band{
  padding:60px 20px;
  background:linear-gradient(135deg,var(--p),#1e0a3c);
  text-align:center;position:relative;overflow:hidden;
}
.cta-deco{
  position:absolute;font-size:16rem;opacity:.04;
  top:-3rem;left:50%;transform:translateX(-50%);
  pointer-events:none;color:#fff;
  font-family:'Yeseva One',serif;line-height:1;user-select:none;
}
.cta-inner{position:relative;z-index:1;max-width:580px;margin:0 auto}
.cta-h2{
  font-family:'Yeseva One',serif;
  font-size:clamp(1.7rem,4vw,2.9rem);  /* was 4rem */
  color:#fff;line-height:1.15;margin-bottom:12px;
}
.cta-h2 em{font-style:normal;color:var(--g)}
.cta-sub{color:rgba(255,255,255,.65);font-size:.9rem;line-height:1.75;margin-bottom:26px}
.cta-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}

/* ── PARTNERS ── */
.partners{padding:36px 20px;background:var(--cr);text-align:center}
.partners-lbl{font-family:'Yeseva One',serif;font-size:1rem;color:#C4B5A5;margin-bottom:20px;display:block}
.p-track{overflow:hidden;mask-image:linear-gradient(to right,transparent,black 10%,black 90%,transparent)}
.p-row{display:flex;gap:32px;animation:tick 32s linear infinite;width:max-content}
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
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .ev-grid{grid-template-columns:repeat(2,1fr)}
  .news-grid{grid-template-columns:1fr}
  .news-small{flex-direction:row}
  .news-mini{flex:1}
  .about-grid{grid-template-columns:1fr;gap:28px}
  .img-frame img{height:300px}
}

/* ─────────────────────────────────────
   RESPONSIVE — MOBILE ≤768px
───────────────────────────────────── */
@media(max-width:768px){
  /* Hero */
  .hero{height:78vh;min-height:420px;max-height:640px}
  .hero-h1{font-size:clamp(1.7rem,7vw,2.5rem)}
  .hero-btns .btn{padding:9px 16px;font-size:.82rem}
  .ring{display:none}  /* hide rings on mobile */

  /* Sections */
  .sec{padding:36px 16px}
  .hiw{padding:36px 16px}
  .cta-band{padding:44px 16px}
  .stats{padding:24px 16px}

  /* Stats */
  .stats-grid{grid-template-columns:1fr 1fr}
  .sc{padding:16px 12px}

  /* About */
  .about-grid{grid-template-columns:1fr}
  .img-frame{padding:10px 0 10px 10px}
  .img-frame img{height:250px}
  .feat-list{grid-template-columns:1fr 1fr}

  /* Features */
  .feat-grid{grid-template-columns:1fr 1fr}

  /* Projects */
  .proj-grid{grid-template-columns:1fr}
  .proj-hd{flex-direction:column;align-items:flex-start}

  /* HIW */
  .hiw-grid{grid-template-columns:1fr 1fr;gap:12px;margin-top:22px}

  /* Gallery */
  .g-item{flex:0 0 178px;height:135px}
  .gallery-hd{flex-direction:column;align-items:flex-start}

  /* Testimonials */
  .testi-grid{grid-template-columns:1fr}

  /* Events */
  .ev-grid{grid-template-columns:1fr 1fr}
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
  .hero{height:72vh;min-height:380px}
  .hero-btns{flex-direction:column;align-items:stretch}
  .hero-btns .btn{justify-content:center}
  .feat-grid{grid-template-columns:1fr}
  .hiw-grid{grid-template-columns:1fr}
  .ev-grid{grid-template-columns:1fr}
  .stats-grid{grid-template-columns:1fr 1fr}
  .vol-btns{flex-direction:column}
  .vol-btns .btn{width:100%}
  .feat-list{grid-template-columns:1fr}
  .sec{padding:28px 14px}
  .cta-deco{font-size:10rem}
}
</style>





<!-- ══ HERO ══ -->
<section class="hero"
  x-data="{
    on:0,
    sl:<?php echo $slides_json??'[]'; ?>,
    init(){if(this.sl.length>1)setInterval(()=>{this.on=(this.on+1)%this.sl.length},5800);}
  }">
  <div class="hero-slides">
    <?php foreach($slides_raw as $i=>$s): ?>
    <div class="hero-slide" :class="{on: on===<?php echo $i;?>}">
      <img src="<?php echo htmlspecialchars($s['image_path']);?>"
           alt="<?php echo htmlspecialchars($s['title']??'');?>"
           loading="<?php echo $i===0?'eager':'lazy';?>">
    </div>
    <?php endforeach; ?>
    <?php if(empty($slides_raw)): ?>
    <div class="hero-slide on">
      <img src="https://placehold.co/1920x900/3B0764/FCD34D?text=Seva+%26+Service" alt="Hero" loading="eager">
    </div>
    <?php endif; ?>
  </div>
  <div class="hero-ov"></div>
  <div class="ring ring-1" aria-hidden="true"></div>
  <div class="ring ring-2" aria-hidden="true"></div>
  <div class="ring ring-3" aria-hidden="true"></div>
  <div class="hero-body">
    <span class="lotus">🪷</span>
    <span class="eyebrow">सेवा परमो धर्म &nbsp;·&nbsp; Service is the Highest Duty</span>
    <h1 class="hero-h1">United in <mark>Seva</mark>,<br>Stronger in Hope</h1>
    <p class="hero-sub">Thousands of families. Hundreds of stories. One mission — to create a compassionate and just India, one act of service at a time.</p>
    <div class="hero-btns">
      <a href="donate"             class="btn btn-gold">💛 Donate Today</a>
      <a href="volunteer-register" class="btn btn-pink">Join the Movement</a>
      <a href="projects"           class="btn btn-outline-gold">View Projects →</a>
    </div>
  </div>
  <?php if(count($slides_raw)>1): ?>
  <div class="hero-dots">
    <?php foreach($slides_raw as $i=>$s): ?>
    <div class="hero-dot" :class="{on: on===<?php echo $i;?>}" @click="on=<?php echo $i;?>"></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- ══ TICKER ══ -->
<div class="ticker" aria-hidden="true">
  <div class="ticker-track">
    <?php $tk=['सेवा परमो धर्म','Donate & Change Lives','Join 1000+ Volunteers','80G Tax Receipts','दीपो भव','Certificates Ready','Impact India','सत्यं शिवं सुन्दरम्'];
    foreach(array_merge($tk,$tk) as $m): ?>
    <span><?php echo htmlspecialchars($m);?></span><span class="sep">★</span>
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
      <div class="sc"><span class="sc-icon">💰</span><div class="sc-num"><sup>₹</sup><span x-text="d.toLocaleString('en-IN')">0</span></div><div class="sc-lbl">Funds Raised</div></div>
      <div class="sc"><span class="sc-icon">🙋</span><div class="sc-num" x-text="v.toLocaleString()">0</div><div class="sc-lbl">Active Volunteers</div></div>
      <div class="sc"><span class="sc-icon">🌿</span><div class="sc-num" x-text="p">0</div><div class="sc-lbl">Live Projects</div></div>
      <div class="sc"><span class="sc-icon">🌟</span><div class="sc-num" x-text="l.toLocaleString()">0</div><div class="sc-lbl">Lives Touched</div></div>
    </div>
  </div>
</div>

<!-- ══ ABOUT ══ -->
<section class="sec" style="background:var(--cr)">
  <div class="w">
    <div class="about-grid">
      <div class="img-frame">
        <img src="<?php echo htmlspecialchars($about_image?:'https://placehold.co/700x380/3B0764/FCD34D?text=Our+Heritage');?>"
             alt="About <?php echo htmlspecialchars($settings['site_name']??'');?>">
        <div class="img-badge"><strong>15K+</strong><span>Lives Touched</span></div>
      </div>
      <div>
        <span class="label">Our Heritage</span>
        <h2 class="about-h2">Rooted in Culture,<br>Driven by <em>Compassion</em></h2>
        <p class="about-body"><?php echo htmlspecialchars(mb_substr(strip_tags($about_desc??''),0,340));?>…</p>
        <div class="feat-list">
          <div class="fl-item"><div class="fl-icon">🎯</div><span>Our Mission</span></div>
          <div class="fl-item"><div class="fl-icon">👁</div><span>Our Vision</span></div>
          <div class="fl-item"><div class="fl-icon">💚</div><span>Core Values</span></div>
          <div class="fl-item"><div class="fl-icon">🌍</div><span>Our Reach</span></div>
          <div class="fl-item"><div class="fl-icon">📜</div><span>80G Certified</span></div>
          <div class="fl-item"><div class="fl-icon">🏛</div><span>Govt. Registered</span></div>
        </div>
        <a href="about" class="btn btn-purple btn-sm">Read Our Journey →</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ FEATURES ══ -->
<section class="sec" style="background:var(--white)">
  <div class="w">
    <div class="sec-hd">
      <span class="label">Our Platform</span>
      <h2 class="h-xl">Complete <span>Ecosystem</span> for Impact</h2>
      <p class="sec-sub">Donations, volunteers, members, events, certificates — sab admin panel se manage.</p>
    </div>
    <div class="feat-grid">
      <?php foreach([
        ['💰','Donations','donate.php','QR, UPI, bank. 80G receipts. OTP history.'],
        ['🪪','Volunteer Hub','volunteer-register.php','ID cards, certificates, QR verification.'],
        ['👥','Membership','member-register.php','Designations, fees, referral, Razorpay.'],
        ['📋','Projects','projects.php','Real-time funding, galleries, targets.'],
        ['📅','Events','events.php','Registration, photos, video showcases.'],
        ['📜','Certificates','certificates.php','Auto PDF, QR codes, admin control.'],
        ['📊','Admin Panel','admin/dashboard.php','Charts, reports, CSV/PDF, birthday alerts.'],
        ['📱','PWA App','#','Install on mobile, offline, push alerts.'],
      ] as $f): ?>
      <a href="<?php echo $f[2];?>" class="fc">
        <span class="fc-icon"><?php echo $f[0];?></span>
        <div class="fc-title"><?php echo $f[1];?></div>
        <p class="fc-desc"><?php echo $f[3];?></p>
        <span class="fc-lnk">Explore →</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ PROJECTS ══ -->
<section class="sec" style="background:var(--cr)">
  <div class="w">
    <div class="proj-hd">
      <div>
        <span class="label">Active Campaigns</span>
        <h2 class="h-xl">Projects That <span>Need</span> Your Support</h2>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <a href="apply-health-card.php" class="btn btn-outline-gold btn-sm" style="border-color:var(--s);color:var(--s);font-weight:700">🏥 Health Card (Apply / Renew)</a>
        <a href="projects" class="btn btn-purple btn-sm">View All →</a>
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
          <div class="proj-badge">Active</div>
          <div class="proj-pct"><?php echo $pct;?>%</div>
        </div>
        <div class="proj-body">
          <h3 class="proj-title"><?php echo htmlspecialchars($p['title']);?></h3>
          <p class="proj-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($p['description']??''),0,85));?>…</p>
          <div class="pbt"><div class="pbf" style="width:<?php echo $pct;?>%"></div></div>
          <div class="pmeta">
            <span>₹<?php echo number_format($p['raised_amount']);?> raised</span>
            <span><?php echo $pct;?>% of goal</span>
          </div>
          <a href="donate.php?project_id=<?php echo (int)$p['id'];?>" class="btn-donate">Donate Now 💛</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="view-all" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px">
      <a href="projects" class="btn btn-purple btn-sm">Browse All Projects →</a>
      <a href="apply-health-card.php" class="btn btn-outline-gold btn-sm">Apply / Renew / Download Health Card</a>
    </div>
  </div>
</section>

<!-- ══ HOW IT WORKS ══ -->
<section class="hiw">
  <div class="w">
    <div class="sec-hd">
      <span class="label" style="color:rgba(255,255,255,.5)">Easy Steps</span>
      <h2 class="h-xl" style="color:#fff;font-family:'Yeseva One',serif">How to <span>Donate</span></h2>
      <p class="sec-sub" style="color:rgba(255,255,255,.48)">Char simple steps mein donate karein aur 80G receipt paayein</p>
    </div>
    <div class="hiw-grid">
      <?php foreach([
        ['1','Campaign Chunein','Active campaigns browse karein — real-time progress dekhen.'],
        ['2','Pay via QR/UPI','Payment screenshot securely upload karein.'],
        ['3','Admin Verify','24 ghante mein verify hoga — confirmation milega.'],
        ['4','Receipt Download','80G tax-exempt receipt PAN ke saath download karein.'],
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
<section class="sec" style="background:var(--cr)">
  <div class="w">
    <div class="sec-hd">
      <span class="label">Community</span>
      <h2 class="h-xl">What Our <span>Family</span> Says</h2>
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
    ['R','Ramesh Sharma','Donor, Kanpur','★★★★★','इस संस्था की पारदर्शिता देखकर मन प्रसन्न हो गया।'],
    ['P','Priya Gupta','Volunteer, Lucknow','★★★★★','Volunteer experience bahut meaningful laga.'],
    ['A','Anil Verma','Member, Delhi','★★★★★','Membership portal fully automated hai.'],
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
<section class="sec" style="background:var(--white)">
  <div class="w">
    <div class="ev-hd">
      <div>
        <span class="label">Calendar</span>
        <h2 class="h-xl">Upcoming <span>Events</span></h2>
      </div>
      <a href="events" class="btn btn-gold btn-sm">All Events →</a>
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
          LIMIT 3
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
          foreach ($rows as $e) {
            $date = strtotime($e['event_date']);

            $el[] = [
              date('d M', $date),
              'Event',
              htmlspecialchars($e['title']),
              htmlspecialchars($e['location']),
              'event-details.php?id='.(int)$e['id']
            ];
          }
        }

      } catch (Exception $e) {
        error_log($e->getMessage());
      }

      // fallback
      if (empty($el)) {
        $el = [
          ['14 Apr','Health','Free Eye Checkup Camp','District Hospital, Kanpur','events.php'],
          ['21 Apr','Education','Annual Scholarship Distribution','Town Hall, Lucknow','events.php'],
          ['05 May','Environment','100-Tree Plantation Drive','Gomti Riverbank, Lucknow','events.php'],
        ];
      }

      foreach ($el as $ev): ?>

      <a href="<?php echo $ev[4]; ?>" class="ev-card">
        
        <div class="ev-img">
          <div class="ev-img-inner"></div>
          <div class="ev-date-pill"><?php echo htmlspecialchars($ev[0]); ?></div>
        </div>

        <div class="ev-body">
          <div class="ev-cat"><?php echo htmlspecialchars($ev[1]); ?></div>
          <div class="ev-title"><?php echo htmlspecialchars($ev[2]); ?></div>
          <div class="ev-loc">
            <i class="fas fa-map-marker-alt"></i>
            <?php echo htmlspecialchars($ev[3]); ?>
          </div>
        </div>

      </a>

      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ VOLUNTEER CTA ══ -->
<section class="vol-cta">
  <div class="w">
    <div class="vol-inner">
      <div>
        <h2 class="vol-h2">सेवा में जुड़ें।<br>Become a Volunteer Today.</h2>
        <p class="vol-p"><?php echo (int)($activeVolunteers??0);?>+ active volunteers. Official ID card, certificate, and dashboard. Real impact, real recognition.</p>
      </div>
      <div class="vol-btns">
        <a href="volunteer-register" class="btn btn-white-pk btn-sm">Register Now</a>
        <a href="about"           class="btn btn-border-wh btn-sm">Know More →</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ NEWS ══ -->
<section class="sec" style="background:var(--cr)">
  <div class="w">
    <div class="sec-hd left">
      <span class="label">Latest News</span>
      <h2 class="h-xl">Stories of Change</h2>
    </div>
    <div class="news-grid">
      <a href="news" class="news-big">
        <div class="news-big-img">
          <img src="https://placehold.co/680x210/3B0764/FCD34D?text=Community+Story" alt="" loading="lazy">
        </div>
        <div class="news-big-body">
          <div class="news-tag">Impact Story</div>
          <h3 class="news-title">How One Village Got Clean Water After 20 Years of Struggle</h3>
          <p class="news-exc">Through collective effort and volunteer power, a village finally has clean drinking water — a story of perseverance and community solidarity...</p>
        </div>
      </a>
      <div class="news-small">
        <a href="news" class="news-mini">
          <img src="https://placehold.co/360x118/EC4899/ffffff?text=Volunteer+Story" alt="" loading="lazy">
          <div class="news-mini-body">
            <div class="news-tag">Volunteer</div>
            <h4 class="news-title">From College Student to Certified Volunteer Leader</h4>
          </div>
        </a>
        <a href="news" class="news-mini">
          <img src="https://placehold.co/360x118/F59E0B/1C1917?text=Fundraising" alt="" loading="lazy">
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
  <div class="cta-deco" aria-hidden="true">दीप</div>
  <div class="cta-inner">
    <span class="label" style="color:rgba(255,255,255,.48)">Join Us</span>
    <h2 class="cta-h2">Every Act of <em>Giving</em><br>Lights a Thousand Lives</h2>
    <p class="cta-sub">Join our family of change-makers. Your generosity fuels education, nutrition, healthcare, and hope across India.</p>
    <div class="cta-btns">
      <a href="donate"             class="btn btn-gold">Donate Now 💛</a>
      <a href="volunteer-register" class="btn btn-pink">Volunteer With Us</a>
      <a href="projects"           class="btn btn-outline-gold">View Projects →</a>
    </div>
  </div>
</section>

<!-- ══ PARTNERS ══ -->
<?php if(!empty($sponsors)): ?>
<section class="partners">
  <span class="partners-lbl">Our Partners &amp; Supporters</span>
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