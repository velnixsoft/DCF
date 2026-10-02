<?php
/**
 * HOME THEME 1 — "WARM EARTH & IMPACT"  v5.0
 * ─────────────────────────────────────────────
 * UX Fixes:
 *  • Hero = 85vh (not 100vh) — user immediately sees "more" below
 *  • All font sizes tightened — readable, not overwhelming
 *  • Section padding halved — page feels content-rich not airy
 *  • Slider image = cover within hero, never overflowH
 *  • Consistent 8px grid spacing throughout
 *  • Mobile-first, fully responsive
 *  • Birthday banner auto-fetches if var missing
 */
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0f766e">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ─────────────────────────────────────
   DESIGN TOKENS
/* ─────────────────────────────────────
   ROOT VARIABLES - UPDATED COLOR THEME
───────────────────────────────────── */
:root{

  /* ───────── BRAND COLORS (FROM LOGO) ───────── */

  /* Primary Blue */
  --s:#1070B0;

  /* Light Blue */
  --sl:#20A0D0;

  /* Dark Blue */
  --sd:#0d5b90;

  /* Primary Green */
  --g:#00B060;

  /* Light Green */
  --gl:#2cd58a;

  /* Orange Accent */
  --o:#F0A010;

  /* Light Orange */
  --ol:#ffd07d;

  /* Sun Yellow */
  --y:#F0A010;

  /* Deep Yellow */
  --yd:#d48b0a;


  /* ───────── SURFACE COLORS ───────── */

  /* Main Background */
  --bg-warm:#F8FAFC;

  /* Alternate Background */
  --bg-warm2:#EEF4F8;

  /* White */
  --bg-white:#FFFFFF;

  /* Soft Blue Background */
  --bg-blue:#EAF4FF;

  /* Soft Green Background */
  --bg-green:#EDF9EE;


  /* ───────── TEXT COLORS ───────── */

  /* Main Heading */
  --ch:#0F172A;

  /* Paragraph Text */
  --muted:#334155;

  /* Subtle Text */
  --subtle:#64748B;

  /* White Text */
  --white:#FFFFFF;


  /* ───────── SHADOWS ───────── */

  --sh1:0 1px 4px rgba(0,87,168,.08);

  --sh2:0 6px 20px rgba(0,87,168,.12);

  --sh3:0 14px 40px rgba(0,87,168,.18);


  /* ───────── BORDER RADIUS ───────── */

  --r:8px;

  --rl:14px;

  --rxl:24px;


  /* ───────── TRANSITIONS ───────── */

  --ease:all .30s cubic-bezier(.4,0,.2,1);


  /* ───────── SPACING SYSTEM ───────── */

  --sp1:8px;

  --sp2:16px;

  --sp3:24px;

  --sp4:32px;

  --sp5:40px;

  --sp6:56px;

  --sp7:72px;
}

/* ─────────────────────────────────────
   RESET & BASE
───────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{
   font-family:'Inter',sans-serif;
   font-size:15px;
   line-height:1.7;
   font-weight:400;
   color:var(--ch);
}
a{text-decoration:none;color:inherit}
img{max-width:100%;height:auto;display:block}
button{cursor:pointer;border:none;background:none;font:inherit}

/* Layout wrapper */
.w{width:100%;max-width:1200px;margin:0 auto;padding:0 20px}

/* ─────────────────────────────────────
   TYPOGRAPHY SCALE
───────────────────────────────────── */
.label{
  font-size:.7rem;font-weight:700;letter-spacing:.16em;
  text-transform:uppercase;color:var(--s);display:block;
  margin-bottom:6px;
}
.h-xl{ /* section headings */
  font-family:'Cormorant Garamond',serif;
  font-size:clamp(1.55rem,3.2vw,2.4rem);
  font-weight:700;line-height:1.22;color:var(--ch);
}
.h-lg{
  font-family:'Cormorant Garamond',serif;
  font-size:clamp(1.35rem,2.5vw,1.9rem);
  font-weight:700;line-height:1.25;color:var(--ch);
}
.body-txt{font-size:.93rem;color:var(--muted);line-height:1.75}

/* Tag line (the orange line + label combo) */
.tag-row{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.tag-line{width:36px;height:3px;background:linear-gradient(90deg,var(--s),var(--sl));border-radius:2px;flex-shrink:0}
.tag-row .label{margin-bottom:0}

/* ─────────────────────────────────────
   BUTTONS
───────────────────────────────────── */
.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  font-weight:600;
  border-radius:12px;
  transition:.35s ease;
  font-size:.9rem;
  padding:12px 26px;
  white-space:nowrap;
  line-height:1;
}

/* Donate Button */
.btn-orange{
  background:linear-gradient(135deg,#D4AF37,#B8860B);
  color:#fff;
  border:none;
  box-shadow:0 10px 25px rgba(184,134,11,.28);
}

.btn-orange:hover{
  transform:translateY(-3px);
  box-shadow:0 18px 35px rgba(184,134,11,.38);
}

/* View Projects Button */
.btn-green{
  background:#fff;
  color:#B8860B;
  border:2px solid #D4AF37;
  box-shadow:0 8px 20px rgba(212,175,55,.18);
}

.btn-green:hover{
  background:linear-gradient(135deg,#D4AF37,#B8860B);
  color:#fff;
  transform:translateY(-3px);
}

/* Join as Volunteer */
.btn-ghost-white{
  background:#fff;
  color:#B8860B !important;
  border:2px solid #D4AF37;
  backdrop-filter:none;
  box-shadow:0 8px 20px rgba(212,175,55,.18);
}

.btn-ghost-white:hover{
  background:linear-gradient(135deg,#D4AF37,#B8860B);
  color:#fff !important;
  border-color:#D4AF37;
  transform:translateY(-3px);
}

/* White Button */
.btn-white{
  background:#fff;
  color:#B8860B;
}

.btn-white:hover{
  transform:translateY(-3px);
  box-shadow:0 15px 30px rgba(184,134,11,.25);
}

.btn-border-white{
  background:#fff;
  color:#B8860B;
  border:2px solid #D4AF37;
}

.btn-border-white:hover{
  background:linear-gradient(135deg,#D4AF37,#B8860B);
  color:#fff;
}

.btn-outline-s{
  border:2px solid #D4AF37;
  color:#B8860B;
  padding:8px 0;
  font-size:.82rem;
  border-radius:12px;
}

.btn-outline-s:hover{
  background:#D4AF37;
  color:#fff;
}

.btn-fill-s{
  background:linear-gradient(135deg,#D4AF37,#B8860B);
  color:#fff;
  padding:8px 0;
  font-size:.82rem;
  border-radius:12px;
}

.btn-fill-s:hover{
  background:linear-gradient(135deg,#E6BC47,#C89215);
}

.btn-sm{
  padding:9px 18px;
  font-size:.82rem;
}
/* ─────────────────────────────────────
   BIRTHDAY BANNER
───────────────────────────────────── */
.bday-bar{
  background:#FFFBEB;border-bottom:2px solid #FCD34D;
  padding:9px 20px;text-align:center;
}
.bday-bar p{font-size:.82rem;color:#92400E;font-weight:500;line-height:1.4}
.bday-bar strong{color:#B45309}

/* ─────────────────────────────────────
   HERO  ← KEY FIX: 85vh, not 100vh
   User sees a peek of next section
   immediately — pulls them to scroll
───────────────────────────────────── */
.hero{
  position:relative;
  width:100%;
  height:46.875vw;
  max-height:780px;
  min-height:480px;
  display:flex;align-items:center;justify-content:center;
  overflow:hidden;background:#0a0a0a;text-align:center;
}
@media(max-width:992px){
  .hero{
    min-height:unset;
  }
}
/* Slider images stay contained inside hero */
.hero-slides{position:absolute;inset:0}
.hero-slide{
  position:absolute;inset:0;opacity:0;
  transition:opacity 1.6s cubic-bezier(.4,0,.2,1);
}
.hero-slide.on{opacity:1}
.hero-slide img{
  width:100%;height:100%;
  object-fit:cover;object-position:center;  /* ← never overflow */
}
.hero-ov{
  position:absolute;inset:0;
  
}
/* Floating particles */
.hero-pts{position:absolute;inset:0;pointer-events:none;overflow:hidden}
.pt{
  position:absolute;width:2px;height:2px;
  background:var(--sl);border-radius:50%;opacity:.45;
  animation:ptf var(--d,9s) ease-in-out infinite var(--dl,0s);
}
@keyframes ptf{
  0%,100%{transform:translate(0,0);opacity:.3}
  50%{transform:translate(14px,-44px);opacity:.7}
}
/* Hero content */
.hero-body{
  position:relative;z-index:5;
  padding:20 0px;max-width:780px;width:100%;
}
.hero-tag{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(232,97,10,.16);border:1px solid rgba(232,97,10,.38);
  color:var(--sl);font-size:.68rem;font-weight:700;letter-spacing:.16em;
  text-transform:uppercase;padding:6px 16px;border-radius:50px;
  margin-bottom:18px;backdrop-filter:blur(8px);
  animation:fu .7s ease .2s both;
}
/* HERO TITLE — tightened significantly */
.hero-h1{
  font-family:'Cormorant Garamond',serif;
  font-size:clamp(1.9rem,5.5vw,3.8rem);  /* ← was 6rem — way too big */
  font-weight:900;color:#fff;
  line-height:1.12;letter-spacing:-.01em;
  margin-bottom:14px;
  animation:fu .7s ease .4s both;
}
.hero-h1 em{
  font-style:normal;
  background:linear-gradient(135deg,var(--sl),#FFD580);
  -webkit-background-clip:text;background-clip:text;
  -webkit-text-fill-color:transparent;
}
.hero-sub{
  color:rgba(255,255,255,.72);
  font-size:clamp(.85rem,2vw,.98rem);  /* ← was 1.1rem — too big */
  line-height:1.72;max-width:520px;margin:0 auto 24px;
  animation:fu .7s ease .6s both;
}
.hero-btns{
  display:flex;gap:10px;justify-content:center;flex-wrap:wrap;
  animation:fu .7s ease .8s both;
}

@keyframes fu{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}

/* Slider dots */
.hero-dots{
  position:absolute;bottom:18px;left:50%;transform:translateX(-50%);
  display:flex;gap:6px;z-index:10;
}
.hero-dot{
  width:7px;height:7px;border-radius:50%;
  background:rgba(255,255,255,.32);cursor:pointer;transition:var(--ease);
}
.hero-dot.on{background:var(--s);width:22px;border-radius:4px}

/* Scroll hint arrow — subtle, not text */
.hero-scroll{
  position:absolute;bottom:14px;left:50%;
  transform:translateX(-50%) translateY(0);
  z-index:10;animation:bob 2s ease infinite;
  /* push it below dots */
  bottom:10px;
}
.hero-scroll svg{
  width:22px;height:22px;stroke:rgba(255,255,255,.35);
  stroke-width:2;fill:none;
}
@keyframes bob{0%,100%{transform:translateX(-50%) translateY(0)}50%{transform:translateX(-50%) translateY(5px)}}
/* Hide scroll hint if dots present */
.has-dots .hero-scroll{display:none}

/* ─────────────────────────────────────
   TICKER
───────────────────────────────────── */

.ticker{
  background: var(--s);
  overflow: hidden;
  padding: 8px 0;
  width: 100%;
}

.ticker-track{
  display: flex;
  align-items: center;
  white-space: nowrap;
  width: max-content;
  animation: tick 35s linear infinite;
}

.ticker-track span{
  color: #fff;
  font-size: .76rem;
  font-weight: 600;
  letter-spacing: .04em;
  padding: 0 18px;
  flex-shrink: 0;
}

.ticker-track .sep{
  color: rgba(255,255,255,.38);
}

/* Animation */
@keyframes tick{
  from{
    transform: translateX(0);
  }
  to{
    transform: translateX(-50%);
  }
}

/* ─────────────────────────────────────
   MOBILE RESPONSIVE
───────────────────────────────────── */

@media (max-width: 768px){

  .ticker{
    padding: 6px 0;
  }

  .ticker-track{
    animation: tick 20s linear infinite;
  }

  .ticker-track span{
    font-size: .68rem;
    padding: 0 12px;
    letter-spacing: .02em;
  }

}

/* ─────────────────────────────────────
   IMPACT BAR
───────────────────────────────────── */
.impact{background:var(--g);padding:36px 20px}
.impact-grid{
  display:grid;grid-template-columns:repeat(4,1fr);
  border:1px solid rgba(255,255,255,.1);
  border-radius:var(--r);overflow:hidden;
}
.impact-item{
  padding:24px 16px;text-align:center;
  border-right:1px solid rgba(255,255,255,.1);
  transition:var(--ease);
}
.impact-item:last-child{border-right:none}
.impact-item:hover{
  background:rgba(255,255,255,.06);
  transform:translateY(-3px);
}
.impact-icon{font-size:1.6rem;margin-bottom:8px;display:block}
.impact-num{
  font-family:'Cormorant Garamond',serif;
  font-size:clamp(1.6rem,3.5vw,2.6rem);  /* ← tightened */
  font-weight:700;color:#fff;line-height:1;
}
.impact-num sup{font-size:.9rem;vertical-align:super}
.impact-lbl{
  color:rgba(255,255,255,.52);font-size:.62rem;
  letter-spacing:.12em;text-transform:uppercase;margin-top:4px;
}

/* ─────────────────────────────────────
   SECTION SPACING  ← KEY FIX
   was 5-7rem — now 3-4rem
───────────────────────────────────── */
.sec{padding:52px 20px}  /* ← was 5-7rem (80-112px) — now 52px */
.sec-sm{padding:36px 20px}
.sec-hd{text-align:center;margin-bottom:32px}
.sec-hd.left{text-align:left}
.sec-sub{
  color:var(--muted);font-size:.88rem;
  max-width:480px;margin:6px auto 0;line-height:1.65;
}

/* ─────────────────────────────────────
   ABOUT
───────────────────────────────────── */
.about-grid{
  display:grid;grid-template-columns:1fr 1fr;
  gap:48px;align-items:center;
}
.img-stack{position:relative;height:380px}  /* ← was 460px */
.img-main{
  position:absolute;top:0;left:0;width:78%;height:320px;
  object-fit:cover;border-radius:var(--r);
  box-shadow:var(--sh3);background:#d5cfc8;
}
.img-accent{
  position:absolute;bottom:0;right:0;width:52%;height:200px;
  object-fit:cover;border-radius:var(--r);
  box-shadow:var(--sh3);border:4px solid var(--bg-warm);
  background:#c8c0b5;
}
.about-badge{
  position:absolute;top:50%;left:60%;transform:translateY(-50%);
  background:linear-gradient(135deg,var(--s),var(--sd));color:#fff;
  padding:12px 16px;border-radius:var(--r);text-align:center;
  box-shadow:0 8px 28px rgba(232,97,10,.48);z-index:3;
}
.about-badge strong{
  display:block;font-family:'Cormorant Garamond',serif;
  font-size:1.7rem;line-height:1;
}
.about-badge span{font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;opacity:.88;display:block;margin-top:2px}
.about-text .h-xl{margin-bottom:12px}
.about-text .body-txt{margin-bottom:18px}
.checks{list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:20px}
.checks li{
  display:flex;align-items:center;gap:7px;
  font-size:.82rem;color:#5a5550;
}
.checks li::before{content:'✓';color:var(--s);font-weight:800;font-size:.85rem;flex-shrink:0}

/* ─────────────────────────────────────
   FEATURES
───────────────────────────────────── */
.feat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.feat-card{
  background:#fff;border-radius:var(--rl);
  padding:20px 16px;border-bottom:3px solid transparent;
  box-shadow:var(--sh1);transition:var(--ease);
}
.feat-card:hover{
  transform:translateY(-5px);
  border-bottom-color:var(--s);
  box-shadow:var(--sh2);
}
.feat-icon{font-size:2rem;margin-bottom:10px;display:block}
.feat-title{
  font-family:'Cormorant Garamond',serif;
  font-size:.95rem;font-weight:700;color:var(--ch);margin-bottom:5px;
}
.feat-desc{font-size:.78rem;color:#8a8075;line-height:1.6;margin-bottom:8px}
.feat-lnk{font-size:.72rem;font-weight:700;color:var(--s)}

/* ─────────────────────────────────────
   PROJECTS
───────────────────────────────────── */
.proj-hd{
  display:flex;justify-content:space-between;
  align-items:flex-end;margin-bottom:24px;
  flex-wrap:wrap;gap:12px;
}
.proj-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.proj-card{
  background:#fff;border-radius:var(--rl);overflow:hidden;
  box-shadow:var(--sh1);transition:var(--ease);display:flex;flex-direction:column;
}
.proj-card:hover{transform:translateY(-5px);box-shadow:var(--sh2)}
.proj-img{position:relative;height:186px;overflow:hidden}  /* ← was 210px */
.proj-img img{
  width:100%;height:100%;object-fit:cover;
  transition:.5s cubic-bezier(.4,0,.2,1);background:#e0d8d0;
}
.proj-card:hover .proj-img img{transform:scale(1.06)}
.proj-cat{
  position:absolute;top:8px;left:8px;background:var(--g);color:#fff;
  font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  padding:3px 8px;border-radius:3px;
}
.proj-pct{
  position:absolute;top:8px;right:8px;
  background:rgba(0,0,0,.6);color:#fff;
  font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:50px;
}
.proj-body{padding:14px;flex:1;display:flex;flex-direction:column}
.proj-title{
  font-family:'Cormorant Garamond',serif;
  font-size:.96rem;font-weight:700;color:var(--ch);
  margin-bottom:5px;line-height:1.28;
}
.proj-desc{font-size:.78rem;color:#8a8075;line-height:1.58;flex:1;margin-bottom:10px}
.pbar{height:4px;background:#EDE8E1;border-radius:2px;overflow:hidden;margin:.35rem 0}
.pfill{height:100%;background:linear-gradient(90deg,var(--g),var(--s));border-radius:2px}
.pmeta{display:flex;justify-content:space-between;font-size:.7rem;color:#bbb;margin-bottom:10px}
.proj-btns{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:auto}
.view-all{text-align:center;margin-top:24px}

/* ─────────────────────────────────────
   HOW IT WORKS
───────────────────────────────────── */
.hiw{padding:20px 1px;background:var(--ch);position:relative;overflow:hidden}
.hiw::before{
  content:'';position:absolute;inset:0;
  background:
    radial-gradient(ellipse at 25% 60%,rgba(27,94,48,.22),transparent 55%),
    radial-gradient(ellipse at 78% 25%,rgba(232,97,10,.09),transparent 48%);
}
.hiw-inner{position:relative;z-index:1}
.hiw-steps{
  display:grid;grid-template-columns:repeat(4,1fr);
  gap:16px;margin-top:10px;position:relative;
}
.hiw-steps::before{
  content:'';position:absolute;top:10px;left:12%;right:12%;
  height:1px;background:linear-gradient(90deg,var(--s),var(--g));opacity:.22;
}
.hiw-step{
  text-align:center;padding:24px 14px;
  background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);
  border-radius:var(--rl);transition:var(--ease);position:relative;z-index:1;
}
.hiw-step:hover{background:rgba(255,255,255,.09);transform:translateY(-4px)}
.step-num{
  width:58px;height:58px;border-radius:50%;  /* ← was 68px */
  background:linear-gradient(135deg,var(--s),var(--sl));
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 14px;
  font-family:'Cormorant Garamond',serif;
  font-size:1.4rem;font-weight:700;color:#fff;  /* ← was 1.7rem */
  box-shadow:0 6px 20px rgba(232,97,10,.38);
}
.step-title{
  font-family:'Cormorant Garamond',serif;
  font-size:.92rem;font-weight:700;color:#fff;margin-bottom:6px;
}
.step-desc{font-size:.76rem;color:rgba(255,255,255,.48);line-height:1.65}

/* ─────────────────────────────────────
   GALLERY
───────────────────────────────────── */
.gallery{padding:52px 0;background:var(--bg-warm2);overflow:hidden}
.gallery-hd{
  max-width:1200px;margin:0 auto 18px;padding:0 20px;
  display:flex;justify-content:space-between;align-items:flex-end;
  flex-wrap:wrap;gap:10px;
}
.gstrip{
  display:flex;gap:10px;overflow-x:auto;
  scroll-snap-type:x mandatory;
  scrollbar-width:thin;scrollbar-color:var(--s) transparent;
  padding:0 20px 10px;
  -webkit-overflow-scrolling:touch;
}
.gstrip::-webkit-scrollbar{height:3px}
.gstrip::-webkit-scrollbar-thumb{background:var(--s);border-radius:2px}
.g-item{
  flex:0 0 220px;height:160px;  /* ← was 260x190 — more items visible */
  border-radius:var(--rl);overflow:hidden;
  scroll-snap-align:start;position:relative;cursor:pointer;
}
.g-item img{
  width:100%;height:100%;object-fit:cover;
  transition:.5s cubic-bezier(.4,0,.2,1);background:#d5cfc8;
}
.g-item:hover img{transform:scale(1.1)}
.g-ov{
  position:absolute;inset:0;
  background:linear-gradient(to top,rgba(26,26,26,.72),transparent 55%);
  opacity:0;transition:var(--ease);display:flex;align-items:flex-end;padding:10px;
}
.g-item:hover .g-ov{opacity:1}
.g-ov span{color:#fff;font-size:.72rem;font-weight:600}

/* ─────────────────────────────────────
   TESTIMONIALS
───────────────────────────────────── */
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:28px}
.testi-card{
  background:linear-gradient(145deg,#fffdf8,#f8f1df);
  border-radius:18px;
  padding:28px 26px;
  border:1px solid #d6ad45;
  box-shadow:0 14px 35px rgba(185,138,30,.16);
  transition:.35s ease;
  position:relative;
  overflow:hidden;
}

.testi-card:hover{
  transform:translateY(-8px);
  box-shadow:0 24px 55px rgba(185,138,30,.28);
}

.testi-quote{
  font-size:3.6rem;
  font-family:'Cormorant Garamond',serif;
  color:#c7961f;
  line-height:.5;
  margin-bottom:18px;
}

.testi-stars{
  color:#c7961f;
}

.testi-txt{
  font-size:.9rem;
  color:#222;
  line-height:1.8;
  font-style:italic;
  margin-bottom:22px;
}

.testi-av{
  width:44px;
  height:44px;
  border-radius:50%;
  background:linear-gradient(135deg,#d6ad45,#a87505);
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:700;
}

.testi-name{
  font-weight:700;
  font-size:.9rem;
  color:#111827;
}

.testi-role{
  font-size:.75rem;
  color:#a87505;
}

/* ─────────────────────────────────────
   EVENTS
───────────────────────────────────── */
.ev-hd{
  display:flex;justify-content:space-between;align-items:flex-end;
  margin-bottom:20px;flex-wrap:wrap;gap:10px;
}
.ev-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.ev-card{
  background:#fff;border-radius:var(--rl);overflow:hidden;
  display:flex;box-shadow:var(--sh1);transition:var(--ease);
  text-decoration:none;color:inherit;
}
.ev-card:hover{transform:translateY(-3px);box-shadow:var(--sh2)}
.ev-date{
  background:linear-gradient(180deg,var(--g),var(--gl));color:#fff;
  padding:14px 18px;display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  min-width:74px;flex-shrink:0;
}
.ev-day{
  font-family:'Cormorant Garamond',serif;
  font-size:2rem;font-weight:700;line-height:1;  /* ← was 2.4rem */
}
.ev-mon{font-size:.6rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;opacity:.82;margin-top:2px}
.ev-info{padding:12px 14px;flex:1;min-width:0}
.ev-tag{font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--s);margin-bottom:3px}
.ev-title{font-weight:700;font-size:.88rem;color:var(--ch);margin-bottom:3px;line-height:1.28;word-break:break-word}
.ev-meta{font-size:.72rem;color:#aaa;display:flex;align-items:center;gap:4px}
.ev-meta i{color:var(--s);font-size:.65rem;flex-shrink:0}

/* ─────────────────────────────────────
   VOLUNTEER CTA
───────────────────────────────────── */
.vol-cta{
  padding:44px 20px;
  background:linear-gradient(135deg,var(--s),var(--sd));
  position:relative;overflow:hidden;
}
.vol-cta::before{
  content:'';position:absolute;right:-60px;top:-60px;
  width:320px;height:320px;border-radius:50%;
  background:rgba(255,255,255,.06);pointer-events:none;
}
.vol-inner{
  max-width:1200px;margin:0 auto;
  display:grid;grid-template-columns:1fr auto;
  gap:24px;align-items:center;position:relative;z-index:1;
}
.vol-h2{
  font-family:'Cormorant Garamond',serif;
  font-size:clamp(1.25rem,2.5vw,1.9rem);  /* ← was 2.4rem */
  color:#fff;line-height:1.22;margin-bottom:6px;
}
.vol-p{color:rgba(255,255,255,.8);font-size:.88rem;line-height:1.65}
.vol-btns{display:flex;flex-direction:column;gap:10px;flex-shrink:0}

/* ─────────────────────────────────────
   NEWS
───────────────────────────────────── */
.news-grid{display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-top:28px}
.news-big{
  border-radius:var(--rl);overflow:hidden;background:#fff;
  box-shadow:var(--sh1);transition:var(--ease);display:block;color:inherit;
}
.news-big:hover{transform:translateY(-3px);box-shadow:var(--sh2)}
.news-big-img{height:220px;overflow:hidden}  /* ← was 265px */
.news-big-img img{
  width:100%;height:100%;object-fit:cover;
  transition:.5s cubic-bezier(.4,0,.2,1);background:#d5cfc8;
}
.news-big:hover .news-big-img img{transform:scale(1.04)}
.news-big-body{padding:16px}
.news-tag{font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--s);margin-bottom:5px}
.news-title{
  font-family:'Cormorant Garamond',serif;
  font-size:1.05rem;font-weight:700;color:var(--ch);
  margin-bottom:7px;line-height:1.32;
}
.news-exc{font-size:.8rem;color:#8a8075;line-height:1.68}
.news-small{display:flex;flex-direction:column;gap:12px}
.news-mini{
  background:#fff;border-radius:var(--rl);overflow:hidden;
  box-shadow:var(--sh1);transition:var(--ease);display:block;color:inherit;
}
.news-mini:hover{transform:translateY(-2px);box-shadow:var(--sh2)}
.news-mini img{width:100%;height:120px;object-fit:cover;background:#d5cfc8}
.news-mini-body{padding:10px 12px}
.news-mini .news-title{font-size:.88rem}

/* ─────────────────────────────────────
   CTA BAND
───────────────────────────────────── */
.cta-band{
  padding:64px 20px;
  background:linear-gradient(135deg,#FFFDF8,#F8F0DF,#FFF7EA);
  position:relative;
  overflow:hidden;
  text-align:center;
}

.cta-band::before{
  content:'';
  position:absolute;
  top:-120px;
  right:-120px;
  width:420px;
  height:420px;
  border-radius:50%;
  background:rgba(212,175,55,.14);
}

.cta-band::after{
  content:'';
  position:absolute;
  bottom:-90px;
  left:-90px;
  width:340px;
  height:340px;
  border-radius:50%;
  background:rgba(184,134,11,.12);
}

.cta-inner{
  position:relative;
  z-index:1;
  max-width:580px;
  margin:0 auto;
}

.cta-h2{
  font-family:'Cormorant Garamond',serif;
  font-size:clamp(1.7rem,4vw,3rem);
  color:#1E1E1E;
  line-height:1.18;
  margin-bottom:12px;
}

.cta-sub{
  color:#5F5F5F;
  font-size:.92rem;
  line-height:1.75;
  margin-bottom:28px;
}

.cta-btns{
  display:flex;
  gap:10px;
  justify-content:center;
  flex-wrap:wrap;
}

/* ─────────────────────────────────────
   PARTNERS
───────────────────────────────────── */
.partners{padding:36px 20px;background:var(--bg-warm);text-align:center}
.partners-lbl{
  font-size:.7rem;font-weight:600;letter-spacing:.18em;
  text-transform:uppercase;color:#C4B5A5;margin-bottom:20px;display:block;
}
.p-track{
  overflow:hidden;
  mask-image:linear-gradient(to right,transparent,black 10%,black 90%,transparent);
}
.p-row{display:flex;gap:32px;animation:tick 34s linear infinite;width:max-content}
.p-item img{
  height:38px;object-fit:contain;
  filter:grayscale(1);opacity:.42;transition:var(--ease);
}
.p-item img:hover{filter:none;opacity:1;transform:scale(1.06)}

/* ─────────────────────────────────────
   RESPONSIVE — TABLET ≤1024px
───────────────────────────────────── */
@media(max-width:1024px){
  .feat-grid{grid-template-columns:repeat(2,1fr)}
  .proj-grid{grid-template-columns:repeat(2,1fr)}
  .testi-grid{grid-template-columns:repeat(2,1fr)}
  .hiw-steps{grid-template-columns:repeat(2,1fr)}
  .hiw-steps::before{display:none}
  .impact-grid{grid-template-columns:repeat(2,1fr)}
  .impact-item:nth-child(2){border-right:none}
  .impact-item:nth-child(3){border-top:1px solid rgba(255,255,255,.1)}
  .about-grid{grid-template-columns:1fr;gap:28px}
  .img-stack{height:280px}
  .img-main{width:76%;height:260px}
  .img-accent{width:50%;height:182px}
  .news-grid{grid-template-columns:1fr}
  .news-small{flex-direction:row}
  .news-mini{flex:1}
}

/* ─────────────────────────────────────
   RESPONSIVE — MOBILE ≤768px
───────────────────────────────────── */
@media(max-width:768px){
  /* Sections */
  .sec{padding:36px 16px}
  .hiw{padding:36px 16px}
  .cta-band{padding:44px 16px}

  /* Impact */
  .impact{padding:28px 16px}
  .impact-grid{grid-template-columns:1fr 1fr}
  .impact-item{padding:18px 12px}
  .impact-item:nth-child(2){border-right:none}
  .impact-item:nth-child(3){border-top:1px solid rgba(255,255,255,.1);border-right:1px solid rgba(255,255,255,.1)}
  .impact-item:nth-child(4){border-top:1px solid rgba(255,255,255,.1)}

  /* About */
  .about-grid{grid-template-columns:1fr}
  .img-stack{height:240px}
  .img-main{width:80%;height:220px}
  .img-accent{width:52%;height:162px}
  .about-badge{top:auto;bottom:-14px;left:50%;transform:translateX(-50%);white-space:nowrap}
  .checks{grid-template-columns:1fr}

  /* Features */
  .feat-grid{grid-template-columns:1fr 1fr}
  .feat-card{padding:16px 12px}

  /* Projects */
  .proj-grid{grid-template-columns:1fr}
  .proj-hd{flex-direction:column;align-items:flex-start}

  /* HIW */
  .hiw-steps{grid-template-columns:1fr 1fr;gap:12px;margin-top:24px}

  /* Gallery */
  .g-item{flex:0 0 180px;height:140px}

  /* Testimonials */
  .testi-grid{grid-template-columns:1fr}

  /* Events */
  .ev-grid{grid-template-columns:1fr}
  .ev-hd{flex-direction:column;align-items:flex-start}

  /* Volunteer CTA */
  .vol-inner{grid-template-columns:1fr;gap:18px}
  .vol-btns{flex-direction:row;flex-wrap:wrap}

  /* News */
  .news-grid{grid-template-columns:1fr}
  .news-small{flex-direction:column}

  /* CTA */
  .cta-btns{flex-direction:column;align-items:center}
  .cta-btns .btn{width:100%;max-width:280px}

  /* Gallery hd */
  .gallery-hd{flex-direction:column;align-items:flex-start}
  .ev-hd,.proj-hd{flex-direction:column;align-items:flex-start}
}

/* ─────────────────────────────────────
   RESPONSIVE — SMALL ≤480px
───────────────────────────────────── */
@media(max-width:480px){
  .feat-grid{grid-template-columns:1fr}
  .hiw-steps{grid-template-columns:1fr}
  .vol-btns{flex-direction:column}
  .vol-btns .btn{width:100%}
  .ev-card{flex-direction:column}
  .ev-date{flex-direction:row;gap:10px;min-width:auto;padding:10px 14px}
  .ev-day{font-size:1.6rem}
  .news-small{flex-direction:column}
  .sec{padding:28px 14px}
}

.hero-tag {
    white-space: nowrap;      /* Keeps text on one line */
    overflow: hidden;         /* Prevents background from stretching */
    text-overflow: ellipsis;  /* Adds '...' if it still doesn't fit */
    display: block;
    width: 100%;              /* Keeps div width locked to screen */
    box-sizing: border-box;
}

/* Mobile Specific Fix */
@media (max-width: 480px) {
    .hero-tag {
        font-size: 11px;      /* Adjust this value until it fits your screen */
        letter-spacing: -0.2px; /* Tightens text to save space */
    }
}
.feat-lnk{
   display:inline-flex;
   align-items:center;
   gap:8px;
}

@media(max-width:480px){
   .feat-lnk{
      gap:12px;   /* mobile me extra gap */
   }
}

.feat-lnk i{
      font-size:.72rem;
   }
   
   h1,h2,h3,h4,h5,h6{
   font-family:'Poppins',sans-serif;
   letter-spacing:-0.5px;
}

.ticker{
   background: #435C4D !important;
}

.feat-card{
    background:linear-gradient(145deg,#F7F3EA,#FFFDF8);
    border:1px solid #D8B15A;
    border-radius:18px;
    color:#222;
    box-shadow:0 10px 30px rgba(185,138,30,.15);
    transition:.35s ease;
}

.feat-card:hover{
    transform:translateY(-8px) scale(1.02);
    box-shadow:0 22px 45px rgba(185,138,30,.28);
    border-color:#C89A2B;
    background:linear-gradient(145deg,#FFFDF9,#F8F1DE);
}

.feat-title{
    color:#A06A00;
}

.feat-desc{
    color:#555;
}

.feat-lnk{
    color:#B8860B;
    font-weight:700;
}

.feat-icon{
    color:#D4AF37;
}
.feat-desc{
    color:#1f2937;
}

.feat-lnk,
.feat-icon{
    color:#c89b2d;
}

.impact-item{
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  width:100%;
  min-height:140px;
  text-decoration:none;
  color:#fff;
  position:relative;
  z-index:2;
  cursor:pointer;
}

.impact-item *{
  pointer-events:none;
}

/* ─────────────────────────────────────
   HERO CONTENT POSITION FIX & RESPONSIVENESS
───────────────────────────────────── */

.hero-body{
  position:absolute;
  inset:0;
  z-index:5;
  pointer-events:none;
}

.hero-body .w{
  height:100%;
  position:relative;
}

/* Buttons container */
.hero-btns{
  position:absolute;
  left:5px;
  top:58%;
  transform:translateY(-50%);
  display:flex;
  gap:12px;
  flex-wrap:wrap;
  pointer-events:auto;
  animation:fu .7s ease .8s both;
}

/* Breakpoints for button size scaling and responsiveness */
@media(max-width:1200px){
  .hero-btns{
    top:58%;
  }
  .hero-btns .btn{
    font-size:0.85rem;
    padding:10px 20px;
    border-radius:10px;
  }
}

@media(max-width:992px){
  .hero-btns{
    top:58%;
    gap:10px;
  }
  .hero-btns .btn{
    font-size:0.8rem;
    padding:8px 16px;
    border-radius:8px;
  }
}

@media(max-width:768px){
  .hero-btns{
    top:58%;
    gap:8px;
    left:24px;
  }
  .hero-btns .btn{
    font-size:0.75rem;
    padding:6px 12px;
    border-radius:6px;
  }
}

@media(max-width:576px){
  .hero-btns{
    top:58%;
    gap:6px;
    left:18px;
  }
  .hero-btns .btn{
    font-size:0.68rem;
    padding:5px 10px;
    border-radius:6px;
    gap:4px;
  }
}

@media(max-width:400px){
  .hero-btns{
    top:58%;
    left:10px;
    right:10px;
    justify-content:flex-start;
    gap:4px;
  }
  .hero-btns .btn{
    font-size:0.65rem;
    padding:4px 8px;
    border-radius:5px;
    gap:3px;
  }
}


.cta-label{
    display:inline-block;
    color:#B8860B !important;
    background:#FFF8E8;
    border:2px solid #D4AF37;
    padding:8px 20px;
    border-radius:50px;
    font-size:.75rem;
    font-weight:700;
    letter-spacing:2px;
    text-transform:uppercase;
    margin-bottom:18px;
    box-shadow:0 8px 20px rgba(212,175,55,.18);
}

/* IMPACT SECTION */
.impact{
    background:var(--s);
    padding:70px 0;
}

.impact-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
}

.impact-item{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.15);
    border-radius:18px;
    padding:30px 20px;
    text-align:center;
    text-decoration:none;
    transition:.35s ease;
    backdrop-filter:blur(8px);
}

.impact-item:hover{
    transform:translateY(-6px);
    background:rgba(255,255,255,0.12);
    border-color:rgba(255,255,255,0.25);
}

.impact-num{
    font-size:52px;
    font-weight:800;
    color:#fff;
    line-height:1;
    margin-bottom:10px;
}

.impact-lbl{
    font-size:13px;
    text-transform:uppercase;
    letter-spacing:2px;
    color:rgba(255,255,255,.85);
}

@media(max-width:900px){
    .impact-grid{
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:576px){
    .impact-grid{
        grid-template-columns:1fr;
    }

    .impact-item{
        padding:24px 15px;
    }

    .impact-num{
        font-size:40px;
    }
}

</style>

<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
}
</script>

<!-- ══ HERO ══ -->
<section class="hero <?php echo count($slides_raw)>1?'has-dots':''; ?>"
  x-data="{
    on:0,
    sl:<?php echo $slides_json ?? '[]'; ?>,
    init(){if(this.sl.length>1)setInterval(()=>{this.on=(this.on+1)%this.sl.length},5500);}
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
      <img src="https://placehold.co/1920x900/1B5E30/ffffff?text=Together+We+Build" alt="Hero" loading="eager">
    </div>
    <?php endif; ?>
  </div>
  <div class="hero-ov"></div>
  <div class="hero-pts" aria-hidden="true">
    <div class="pt" style="left:10%;top:25%;--d:8s;--dl:0s"></div>
    <div class="pt" style="left:25%;top:62%;--d:7s;--dl:1s"></div>
    <div class="pt" style="left:62%;top:30%;--d:10s;--dl:1.8s"></div>
    <div class="pt" style="left:80%;top:68%;--d:9s;--dl:.5s"></div>
    <div class="pt" style="left:46%;top:45%;--d:11s;--dl:2.2s"></div>
  </div>
  <div class="hero-body">
    <div class="w">
      <!-- <div class="hero-btns">
        <a href="donate"             class="btn btn-orange"> Donate Now</a>
        <a href="projects"           class="btn btn-orange">Explore Projects</a>
        <a href="volunteer-register" class="btn btn-orange">Volunteer →</a>
      </div> -->
    </div>
  </div>
   <?php if(count($slides_raw)>1): ?>
    <div class="hero-dots">
     <?php foreach($slides_raw as $i=>$s): ?>
    <div class="hero-dot" :class="{on: on===<?php echo $i;?>}" @click="on=<?php echo $i;?>"></div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="hero-scroll" aria-hidden="true">
    <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
  </div>
  <?php endif; ?>
</section>












<!-- ══ TICKER ══ -->
<div class="ticker" aria-hidden="true">
  <div class="ticker-track">
    <?php $tk=['Education Support',
  'Women Empowerment',
  'Healthcare Programs',
  'Community Welfare',
  'Volunteer Network',
  'Social Impact',
  'Hope Through Action',
  'Together For Change'];
    foreach(array_merge($tk,$tk) as $m): ?>
    <span><?php echo htmlspecialchars($m);?></span><span class="sep">✦</span>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══ IMPACT BAR ══ -->
<div class="impact"
  style="background:var(--s);"
  x-data="{
    d:0,v:0,p:0,l:0,go:false,
    run(){
      if(this.go)return;this.go=true;
      const e=t=>1-Math.pow(1-t,3);
      const a=(k,end)=>{const dur=2000,s=performance.now();
        const f=n=>{
          const pr=Math.min((n-s)/dur,1);
          this[k]=Math.floor(end*e(pr));
          pr<1?requestAnimationFrame(f):this[k]=end;
        };
        requestAnimationFrame(f);
      };
      a('d',<?php echo (int)($totalDonation??0);?>);
      a('v',<?php echo (int)($activeVolunteers??0);?>);
      a('p',<?php echo (int)($ongoingProjects??0);?>);
      a('l',15000);
    }
  }"
  x-intersect.once.threshold.0.2="run()">

<div class="w">
  <div class="impact-grid">

    <a href="donate" class="impact-item">
      <div class="impact-num">
        <span x-text="d.toLocaleString('en-IN')">0</span>
      </div>
      <div class="impact-lbl">Funds Raised</div>
    </a>

    <a href="volunteer-register" class="impact-item">
      <div class="impact-num" x-text="v.toLocaleString()">0</div>
      <div class="impact-lbl">Active Volunteers</div>
    </a>

    <a href="projects" class="impact-item">
      <div class="impact-num" x-text="p">0</div>
      <div class="impact-lbl">Live Projects</div>
    </a>

    <a href="news" class="impact-item">
      <div class="impact-num" x-text="l.toLocaleString()">0</div>
      <div class="impact-lbl">Lives Touched</div>
    </a>

  </div>
</div>
</div>

<!-- ══ ABOUT ══ -->
<section class="sec" style="background:var(--bg-warm)">
  <div class="w">

    <div class="about-grid">

      <!-- Image Section -->
      <div class="img-stack">

        <img class="img-main"
             src="<?php echo htmlspecialchars($about_image ?: 'https://placehold.co/560x320/5F7F6B/ffffff?text=Suchi+Foundation'); ?>"
             alt="About <?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">

        <img class="img-accent"
             src="../aboutjay.png"
             alt="Community Impact">

        <div class="about-badge">
          <strong>15K+</strong>
          <span>Lives Empowered</span>
        </div>

      </div>

      <!-- Content -->
      <div class="about-text">

        <div class="tag-row">
          <div class="tag-line"></div>
          <span class="label">About JAYSMRUTTI FOUNDATION</span>
        </div>

        <h2 class="h-xl" style="margin-bottom:14px">
          Empowering Communities <br>
          Through Hope & Action
        </h2>

        <!-- Backend Description Same -->
        <p class="body-txt" style="margin-bottom:18px">
          <?php echo htmlspecialchars(mb_substr(strip_tags($about_desc ?? ''), 0, 340)); ?>...
        </p>

        <!-- Updated NGO Features -->
        <ul class="checks">

          <li>Educational Support for Needy Children</li>

          <li>Healthcare & Medical Awareness Programs</li>

          <li>Women Empowerment Initiatives</li>

          <li>Career Development & Employment Support</li>

          <li>Child Welfare & Community Care</li>

          <li>Environmental Protection & Sustainability Drives</li>

          <li>Food Distribution & Relief Support</li>

          <li>Social Welfare & Community Outreach Programs</li>

          <li>Volunteer Engagement & Leadership Development</li>

          <li>Rural Development & Livelihood Support</li>

          <li>Awareness Campaigns & Social Initiatives</li>

          <li>Digital Literacy & Empowerment Programs</li>

        </ul>

       <a href="about" class="btn btn-green btn-sm">
        <span>Learn More About Us</span>
      </a>

      </div>

    </div>

  </div>
</section>

<!-- ══ FEATURES ══ -->
<section class="sec" style="background:var(--bg-warm2)">
<div class="feat-grid">
  <?php 
  $features = [
    ['','Secure Donations','donate.php','QR, UPI, bank. 80G receipts. Admin-verified.', 'standard'],
    ['','Volunteer Hub','volunteer-register.php','ID card with QR, dashboard, certificates.', 'standard'],
    ['','Membership Portal','member-register.php','Designations, fees, referral, Razorpay.', 'standard'],
    ['','Live Projects','projects.php','Real-time funding, targets, campaign galleries.', 'standard'],
    ['','Events & Gallery','events.php','Upcoming events, photo/video — admin managed.', 'standard'],
    ['','Certificates','certificates.php','Auto PDF with QR verification.', 'standard'],
    ['','Admin Dashboard','admin','Charts, analytics, birthday alerts, exports.', 'standard'],
    ['',' Mobile App','javascript:void(0);','', 'pwa']
  ];

  foreach($features as $f): 
    $isPwa = ($f[4] === 'pwa');
  ?>
  <a href="<?php echo $f[2];?>" 
     class="feat-card" 
     <?php if($isPwa) echo 'id="pwa-install-btn"'; ?>
    <span class="feat-icon"><?php echo $f[0];?></span>
    <div class="feat-title"><?php echo $f[1];?></div>
    <p class="feat-desc"><?php echo $f[3];?></p>
<span class="feat-lnk">
<?php if($isPwa): ?>
    <span class="ml-4">Install Now</span>
    <i class="fa-solid fa-download"></i>
<?php else: ?>
    Explore →
<?php endif; ?>
</span>
  </a>
  <?php endforeach; ?>
</div>


</section>

<!-- ══ PROJECTS ══ -->
<section class="sec" style="background:var(--bg-warm)">
  <div class="w">

    <div class="proj-hd">
      <div>
        <div class="tag-row">
          <div class="tag-line"></div>
          <span class="label">Our Initiatives</span>
        </div>

        <h2 class="h-xl">
          Creating Impact Through <br>
          Community Development
        </h2>

        <p class="sec-sub" style="margin-top:10px;max-width:620px">
          JAYSMRUTTI FOUNDATION is dedicated to transforming lives through education, healthcare, women empowerment, environmental sustainability, and social welfare initiatives across communities.
        </p>
      </div>

      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <a href="apply-health-card.php" class="btn btn-sm" style="background:#0F8B8D;color:#fff;font-weight:700">
          🏥 Health Card (Apply / Renew)
        </a>
        <a href="projects" class="btn btn-orange btn-sm">
          Explore All Projects →
        </a>
      </div>
    </div>

    <div class="proj-grid">

      <?php
      $cats = [
        'Education',
        'Healthcare',
        'Women Empowerment',
        'Environment',
        'Child Welfare',
        'Community Support'
      ];

      foreach(($projects ?? []) as $idx => $p):

        $pct = ($p['target_amount'] > 0)
          ? min(100, round(($p['raised_amount'] / $p['target_amount']) * 100))
          : 0;
      ?>

      <div class="proj-card">

        <div class="proj-img">

          <a href="project-details.php?id=<?php echo (int)$p['id']; ?>">

            <img
              src="<?php echo htmlspecialchars($p['thumbnail_image']); ?>"
              alt="<?php echo htmlspecialchars($p['title']); ?>"
              loading="lazy"
            >

          </a>

         

          <div class="proj-pct">
            <?php echo $pct; ?>%
          </div>

        </div>

        <div class="proj-body">

          <h3 class="proj-title">
            <?php echo htmlspecialchars($p['title']); ?>
          </h3>

          <p class="proj-desc">
            <?php echo htmlspecialchars(mb_substr(strip_tags($p['description'] ?? ''), 0, 95)); ?>...
          </p>

          <div class="pbar">
            <div class="pfill" style="width:<?php echo $pct; ?>%"></div>
          </div>

          <div class="pmeta">
            <span>
              Raised: ₹<?php echo number_format($p['raised_amount']); ?>
            </span>

            <span>
              Goal: ₹<?php echo number_format($p['target_amount']); ?>
            </span>
          </div>

          <div class="proj-btns">

            <a href="project-details.php?id=<?php echo (int)$p['id']; ?>"
               class="btn btn-outline-s">
              View Details
            </a>

            <a href="donate.php?project_id=<?php echo (int)$p['id']; ?>"
               class="btn btn-fill-s">
              Support Now
            </a>

          </div>

        </div>

      </div>

      <?php endforeach; ?>

    </div>

    <div class="view-all" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px">
      <a href="projects" class="btn btn-green btn-sm">
        View All Initiatives →
      </a>
      <a href="apply-health-card.php" class="btn btn-sm" style="background:#0F8B8D;color:#fff;font-weight:700">
        Apply / Renew / Download Health Card
      </a>
    </div>

  </div>
</section>

<!-- ══ HOW IT WORKS ══ -->
<section class="hiw">
  <div class="w hiw-inner">
    <div class="sec-hd">
      <span class="label" style="color:var(--sl)">Simple Process</span>
      <h2 class="h-xl" style="color:#fff">How Donations Work</h2>
      <p class="sec-sub" style="color:rgba(255,255,255,.48)">Four easy steps to make your contribution count</p>
    </div>
    <div class="hiw-steps">
      <?php foreach([
        ['1','Browse Campaigns','Find a cause from our active projects with real-time progress.'],
        ['2','Make Payment','UPI, QR, or bank transfer. Upload payment screenshot.'],
        ['3','Admin Verifies','Our team verifies within 24 hours. Confirmation sent.'],
        ['4','Get 80G Receipt','Download tax-exempt 80G receipt instantly.'],
      ] as $st): ?>
      <div class="hiw-step">
        <div class="step-num"><?php echo $st[0];?></div>
        <h4 class="step-title"><?php echo $st[1];?></h4>
        <p class="step-desc"><?php echo $st[2];?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ GALLERY ══ -->
<section class="gallery">

  <!-- Gallery Heading -->
  <div class="gallery-hd" 
       style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; margin-bottom:40px;">

    <div style="text-align:center;">

      <span class="btn btn-green btn-sm"
            style="display:block; letter-spacing:3px; margin-bottom:10px;">
        Gallery
      </span>

      <h2 class="h-lg" 
          style="margin-bottom:0; text-align:center;">
        Moments of <span>Seva</span>
      </h2>

    </div>

  </div>

  <!-- Gallery Images -->
  <div class="gstrip">

    <?php

    // ✅ Default fallback data
    $gf = [
      ['file_path'=>'https://placehold.co/218x155/3B0764/FCD34D?text=Health+Camp','title'=>'Health Camp 2024'],
      ['file_path'=>'https://placehold.co/218x155/F59E0B/1C1917?text=School+Drive','title'=>'School Drive'],
      ['file_path'=>'https://placehold.co/218x155/EC4899/ffffff?text=Plantation','title'=>'Tree Plantation'],
      ['file_path'=>'https://placehold.co/218x155/6D28D9/ffffff?text=Award+Night','title'=>'Annual Awards'],
      ['file_path'=>'https://placehold.co/218x155/1C1917/FCD34D?text=Volunteer','title'=>'Volunteer Day'],
      ['file_path'=>'https://placehold.co/218x155/BE185D/ffffff?text=Women+Power','title'=>'Women Empowerment'],
    ];

    try {

        // ✅ Latest gallery images from DB
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

        // ✅ Use fallback if DB empty
        $gi = !empty($galleryImages) ? $galleryImages : $gf;

    } catch (Exception $e) {

        // ✅ Use fallback if DB error
        $gi = $gf;
    }

    // ✅ Safe loop
    foreach ($gi as $g):

        $gp = !empty($g['file_path']) 
              ? $g['file_path'] 
              : 'https://placehold.co/218x155?text=No+Image';

        $gt = !empty($g['title']) 
              ? $g['title'] 
              : 'Gallery Image';

    ?>

    <!-- Gallery Item -->
    <a href="gallery" 
       class="g-item" 
       title="<?php echo htmlspecialchars($gt); ?>">

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

  <!-- Full Gallery Button -->
  <div style="display:flex; justify-content:center; margin-top:40px;">

    <a href="gallery" 
       class="btn btn-green btn-sm"
       style="
            padding:12px 32px;
            border-radius:50px;
            font-weight:600;
            box-shadow:0 10px 25px rgba(0,0,0,0.12);
       ">
        Full Gallery →
    </a>

  </div>

</section>

<!-- ══ TESTIMONIALS ══ -->
<section class="sec" style="background:var(--bg-warm)">
  <div class="w">

    <div class="sec-hd">
      <span class="label">Community Voices</span>
      <h2 class="h-xl">What Our Community Says</h2>
    </div>

    <div class="testi-grid">

      <?php
      try {

        $stmt = $pdo->query("
          SELECT *
          FROM testimonials
          ORDER BY id DESC
          LIMIT 6
        ");

        $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

      } catch (Exception $e) {

        $testimonials = [];

      }
      ?>

      <?php if(!empty($testimonials)): ?>

        <?php foreach($testimonials as $t): ?>

        <div class="testi-card">

          <div class="testi-quote">"</div>

          <div class="testi-stars">
            <?php echo str_repeat('★', (int)($t['stars'] ?? 5)); ?>
          </div>

          <p class="testi-txt">
            <?php echo htmlspecialchars($t['content'] ?? ''); ?>
          </p>

          <div class="testi-auth">

            <div class="testi-av">
              <?php echo strtoupper(substr($t['name'] ?? 'U',0,1)); ?>
            </div>

            <div>

              <div class="testi-name">
                <?php echo htmlspecialchars($t['name'] ?? 'User'); ?>
              </div>

              <div class="testi-role">
                <?php echo htmlspecialchars($t['role'] ?? 'Member'); ?>
              </div>

            </div>

          </div>

        </div>

        <?php endforeach; ?>

      <?php else: ?>

        <div style="grid-column:1/-1;text-align:center;padding:20px;color:#777;">
          No testimonials available
        </div>

      <?php endif; ?>

    </div>

  </div>
</section>
<!-- ══ EVENTS ══ -->
<section class="sec" style="background:var(--bg-warm2)">
  <div class="w">
    <div class="ev-hd">
      <div>
        <div class="tag-row">
          <div class="tag-line"></div>
          <span class="label">Upcoming Events</span>
        </div>
        <h2 class="h-xl">Join Our Next <span style="color:var(--s)">Events</span></h2>
      </div>
      <a href="events" class="btn btn-green btn-sm">All Events →</a>
    </div>

    <div class="ev-grid">
      <?php

      $el = [];

      try {
        $stmt = $pdo->prepare("
          SELECT id, title, location, event_date, status 
          FROM events 
          ORDER BY 
            FIELD(status, 'Live', 'Upcoming', 'Completed', 'Cancelled'),
            event_date ASC,
            created_at DESC
          LIMIT 4
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $today = date('Y-m-d');

        if (!empty($rows)) {
          foreach ($rows as $e) {
            if ($e['status'] === 'Cancelled') {
              continue;
            }
            $eventDate = date('Y-m-d', strtotime($e['event_date']));

            if ($eventDate > $today) {
              $status = 'Upcoming';
            } elseif ($eventDate == $today) {
              $status = 'Live';
            } else {
              $status = 'Completed';
            }

            $date = strtotime($e['event_date']);

            $el[] = [
              date('d', $date),
              date('M', $date),
              $status,
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
        <h2 class="vol-h2">Want to Make a Difference?<br>Become a Volunteer.</h2>
        <p class="vol-p">Join <?php echo (int)($activeVolunteers??0);?>+ active volunteers. Get your official ID card, certificate, and join meaningful missions near you.</p>
      </div>
      <div class="vol-btns">
        <a href="volunteer-register" class="btn btn-white btn-sm">Register as Volunteer</a>
        <a href="about"           class="btn btn-border-white btn-sm">Know More →</a>
      </div>
    </div>
  </div>
</section>

<?php
$latestNews = [];
try {
    $stmtLatest = $pdo->prepare("SELECT title, slug, cover_path, content, published_at, created_at FROM posts WHERE status = 'Published' ORDER BY COALESCE(published_at, created_at) DESC LIMIT 3");
    $stmtLatest->execute();
    $latestNews = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $latestNews = [];
}
$cats = ['Initiatives', 'Education', 'Welfare', 'Impact', 'Health Drives', 'Community'];
?>

<!-- ══ NEWS ══ -->
<section class="sec" style="background:var(--bg-warm)">
  <div class="w">

    <div class="sec-hd left">
      <div class="tag-row">
        <div class="tag-line"></div>
        <span class="label">Latest Updates</span>
      </div>

      <h2 class="h-xl">Stories of Hope & Impact</h2>
    </div>

    <?php if (!empty($latestNews)): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-8">
        <?php foreach ($latestNews as $p): 
            $category = $cats[abs(crc32($p['slug'])) % count($cats)];
        ?>
          <a href="news-details.php?slug=<?php echo urlencode((string)$p['slug']); ?>" class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col h-full group overflow-hidden">
            <div class="relative w-full aspect-[16/10] bg-slate-50 flex items-center justify-center overflow-hidden border-b border-slate-100">
              <span class="absolute top-4 left-4 bg-emerald-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm z-10"><?php echo htmlspecialchars($category); ?></span>
              
              <?php if (!empty($p['cover_path'])): ?>
                <img src="<?php echo htmlspecialchars((string)$p['cover_path']); ?>" 
                     alt="<?php echo htmlspecialchars((string)$p['title']); ?>" 
                     loading="lazy" 
                     class="max-w-full max-h-full w-auto h-auto object-contain transition-transform duration-500 group-hover:scale-105">
              <?php else: ?>
                <div class="w-full h-full bg-gradient-to-br from-emerald-50 to-amber-50/50 flex items-center justify-center">
                  <svg class="w-12 h-12 text-emerald-200" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"/>
                  </svg>
                </div>
              <?php endif; ?>
            </div>

            <div class="p-6 flex flex-col flex-grow">
              <div class="text-xs text-slate-500 font-medium mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                </svg>
                <?php
                $dt = $p['published_at'] ?: $p['created_at'];
                echo $dt ? htmlspecialchars(date('d M Y', strtotime((string)$dt))) : '';
                ?>
              </div>
              
              <h3 class="text-lg font-bold text-slate-900 group-hover:text-emerald-700 transition-colors duration-300 line-clamp-2 leading-snug mb-3">
                <?php echo htmlspecialchars((string)$p['title']); ?>
              </h3>
              
              <?php
              $excerpt = trim((string)($p['content'] ?? ''));
              if (strlen($excerpt) > 120) $excerpt = substr($excerpt, 0, 120) . '...';
              ?>
              <p class="text-sm text-slate-600 line-clamp-3 leading-relaxed mb-6">
                <?php echo htmlspecialchars($excerpt); ?>
              </p>
              
              <div class="mt-auto text-sm font-semibold text-emerald-700 flex items-center gap-1.5 group-hover:gap-2.5 transition-all duration-300">
                <span>Read More</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      
      <div class="flex justify-center mt-12">
        <a href="news" class="btn btn-green btn-sm" style="padding:12px 32px; border-radius:50px; font-weight:600; box-shadow:0 10px 25px rgba(0,0,0,0.08);">
          View All News →
        </a>
      </div>

    <?php else: ?>
      <div class="flex flex-col items-center justify-center text-center py-16 px-4 bg-white rounded-3xl border border-slate-100 shadow-sm max-w-2xl mx-auto mt-8">
        <div class="w-32 h-32 mb-6 text-slate-300">
          <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-full h-full">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"/>
          </svg>
        </div>
        <h3 class="text-xl font-bold text-slate-800 mb-2">No News Available Yet</h3>
        <p class="text-slate-500 max-w-sm text-sm leading-relaxed">Stay tuned! We'll post our latest stories, announcements, and program news here soon.</p>
      </div>
    <?php endif; ?>

  </div>
</section>

<!-- ══ CTA BAND ══ -->
<section class="cta-band">
  <div class="cta-inner">
    <span class="label cta-label">Take Action Today</span>
    <h2 class="cta-h2">Ready to Create<br>Forever Change?</h2>
    <p class="cta-sub">Every contribution, every volunteer hour, every shared story adds up to something extraordinary. Join thousands building a better India.</p>
    <div class="cta-btns">
      <a href="donate"             class="btn btn-orange">Donate Now</a>
      <a href="volunteer-register" class="btn btn-ghost-white">Join as Volunteer →</a>
      <a href="projects"           class="btn btn-green">View Projects</a>
    </div>
  </div>
</section>


<script>
let deferredPrompt = null;
const pwaCard = document.getElementById('pwa-install-btn');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    if (pwaCard) {
        pwaCard.style.display = 'flex';
    }
});

if (pwaCard) {
    pwaCard.addEventListener('click', async (e) => {
        e.preventDefault();

        if (deferredPrompt) {
            deferredPrompt.prompt();

            const { outcome } = await deferredPrompt.userChoice;

            if (outcome === 'accepted') {
                pwaCard.style.display = 'none';
            }

            deferredPrompt = null;
        } else {
            alert("Use Chrome mobile browser and Add to Home Screen.");
        }
    });
}
</script>