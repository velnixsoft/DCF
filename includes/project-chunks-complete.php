<?php
/**
 * project-chunks-complete.php  v2.0
 * ─────────────────────────────────
 * Reusable section-functions for all home themes.
 * Include at top of every theme file:
 *   require __DIR__ . '/../includes/project-chunks-complete.php';
 *
 * Functions available:
 *   chunkImpactBar($totalDonation, $activeVolunteers, $ongoingProjects, $totalLives = 15000)
 *   chunkFeatures()
 *   chunkProjects($projects)
 *   chunkHowItWorks()
 *   chunkGallery($galleryImages)
 *   chunkTestimonials()
 *   chunkEvents($events = [])
 *   chunkVolunteerCTA()
 *   chunkNews($newsItems = [])
 *   chunkPartners($sponsors)
 */

/* ══════════════════════════════════════════════
   1. IMPACT BAR  — 4 animated counters
══════════════════════════════════════════════ */
function chunkImpactBar($totalDonation, $activeVolunteers, $ongoingProjects, $totalLives = 15000) {
?>
<section class="ck-impact"
  x-data="{
    d:0,v:0,p:0,l:0,started:false,
    run(){
      if(this.started)return; this.started=true;
      const ease=t=>1-Math.pow(1-t,3);
      const go=(k,end)=>{
        const dur=2200,s=performance.now();
        const f=n=>{const pr=Math.min((n-s)/dur,1);this[k]=Math.floor(end*ease(pr));if(pr<1)requestAnimationFrame(f);else this[k]=end;};
        requestAnimationFrame(f);
      };
      go('d',<?php echo (int)$totalDonation; ?>);
      go('v',<?php echo (int)$activeVolunteers; ?>);
      go('p',<?php echo (int)$ongoingProjects; ?>);
      go('l',<?php echo (int)$totalLives; ?>);
    }
  }"
  x-intersect.once.threshold.0.2="run()">
  <div class="ck-impact-inner">
    <div class="ck-impact-item">
      <div class="ck-impact-icon">💰</div>
      <div class="ck-impact-num"><sup>₹</sup><span x-text="d.toLocaleString('en-IN')">0</span></div>
      <div class="ck-impact-label">Funds Raised</div>
    </div>
    <div class="ck-impact-item">
      <div class="ck-impact-icon">🙋</div>
      <div class="ck-impact-num" x-text="v.toLocaleString()">0</div>
      <div class="ck-impact-label">Active Volunteers</div>
    </div>
    <div class="ck-impact-item">
      <div class="ck-impact-icon">🏗</div>
      <div class="ck-impact-num" x-text="p">0</div>
      <div class="ck-impact-label">Live Projects</div>
    </div>
    <div class="ck-impact-item">
      <div class="ck-impact-icon">🌟</div>
      <div class="ck-impact-num" x-text="l.toLocaleString()">0</div>
      <div class="ck-impact-label">Lives Touched</div>
    </div>
  </div>
</section>
<?php
}

/* ══════════════════════════════════════════════
   2. FEATURES GRID  — 8 platform features
══════════════════════════════════════════════ */
function chunkFeatures() {
    $features = [
        ['💰','Secure Donations',  'donate.php',            'QR, UPI, bank transfer. Upload proof. Get 80G receipt instantly after admin approval.'],
        ['🏥','Health Card Portal','apply-health-card.php', 'Apply, renew & download Health Card with QR. Access 100+ partner hospitals.'],
        ['🪪','Volunteer Hub',     'volunteer-register.php','Register, get approved, download ID card (PDF+QR). Volunteer dashboard included.'],
        ['👥','Membership Portal', 'member-register.php',   'Designation-based fees, Razorpay payment, referral system, appointment letters.'],
        ['📋','Live Projects',     'projects.php',          'Real-time funding progress, donation targets, campaign galleries. Admin-managed.'],
        ['📅','Events & Gallery',  'events.php',            'Upcoming events, photo/video galleries — fully editable from admin panel.'],
        ['📜','Certificates',      'certificates.php',      'Auto-generated volunteer & member certificates with QR verification. PDF download.'],
        ['📊','Admin Dashboard',   'admin/dashboard.php',   'Charts, analytics, birthday alerts, export CSV/PDF, full donor/volunteer control.']
    ];
?>
<section class="ck-features">
  <div class="ck-section-header">
    <div class="ck-overtitle">What We Offer</div>
    <h2 class="ck-section-title">Everything You Need,<br>All in One Place</h2>
    <p class="ck-section-sub">One platform for donations, volunteering, health cards, membership, events, certificates &amp; more.</p>
  </div>
  <div class="ck-feat-grid">
    <?php foreach($features as $f): ?>
    <a href="<?php echo $f[2]; ?>" class="ck-feat-card">
      <div class="ck-feat-icon"><?php echo $f[0]; ?></div>
      <h4 class="ck-feat-title"><?php echo $f[1]; ?></h4>
      <p class="ck-feat-desc"><?php echo $f[3]; ?></p>
      <span class="ck-feat-arrow">Explore →</span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php
}

/* ══════════════════════════════════════════════
   3. PROJECTS GRID  — from DB
══════════════════════════════════════════════ */
function chunkProjects($projects) {
?>
<section class="ck-projects">
  <div class="ck-section-header">
    <div class="ck-overtitle">Active Campaigns</div>
    <h2 class="ck-section-title">Projects Needing<br>Your Support</h2>
  </div>
  <div class="ck-proj-grid">
    <?php foreach($projects as $p):
      $pct = ($p['target_amount'] > 0)
        ? min(100, round(($p['raised_amount'] / $p['target_amount']) * 100))
        : 0;
    ?>
    <div class="ck-proj-card">
      <div class="ck-proj-img-wrap">
        <img src="<?php echo htmlspecialchars($p['thumbnail_image']); ?>"
             alt="<?php echo htmlspecialchars($p['title']); ?>"
             loading="lazy">
        <span class="ck-proj-badge"><?php echo $pct; ?>% Funded</span>
      </div>
      <div class="ck-proj-body">
        <h3 class="ck-proj-title"><?php echo htmlspecialchars($p['title']); ?></h3>
        <p class="ck-proj-desc"><?php echo mb_substr(strip_tags($p['description'] ?? ''), 0, 90); ?>…</p>
        <div class="ck-pbar-wrap">
          <div class="ck-pbar"><div class="ck-pfill" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="ck-pmeta">
            <span>₹<?php echo number_format($p['raised_amount']); ?> raised</span>
            <span>of ₹<?php echo number_format($p['target_amount']); ?></span>
          </div>
        </div>
        <div class="ck-proj-btns">
          <a href="project-details.php?id=<?php echo (int)$p['id']; ?>" class="ck-btn-outline">Details</a>
          <a href="donate.php?project_id=<?php echo (int)$p['id']; ?>" class="ck-btn-fill">Donate 💛</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="ck-view-all" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:1rem;margin-top:2.5rem">
    <a href="projects.php" class="ck-btn-fill" style="padding:1rem 2.5rem">View All Campaigns →</a>
    <a href="apply-health-card.php" class="ck-btn-outline" style="padding:1rem 2rem;border-color:var(--ck-green,#0F8B8D);color:var(--ck-green,#0F8B8D);font-weight:700">🏥 Apply / Renew Health Card</a>
  </div>
</section>
<?php
}

/* ══════════════════════════════════════════════
   4. HOW IT WORKS  — 4 steps
══════════════════════════════════════════════ */
function chunkHowItWorks() {
    $steps = [
        ['1','Choose a Project',   'Browse active campaigns with real-time funding progress and impact stories.'],
        ['2','Make Payment',       'UPI, QR code, or bank transfer. Upload your payment screenshot securely.'],
        ['3','Admin Verifies',     'Our team verifies within 24 hours and sends you a confirmation notification.'],
        ['4','Get 80G Receipt',    'Download your tax-exempt 80G receipt instantly — with or without PAN details.'],
    ];
?>
<section class="ck-hiw">
  <div class="ck-section-header" style="--header-color:#fff">
    <div class="ck-overtitle" style="color:rgba(255,255,255,.55)">Simple Process</div>
    <h2 class="ck-section-title" style="color:#fff">How Donations Work</h2>
  </div>
  <div class="ck-hiw-grid">
    <?php foreach($steps as $s): ?>
    <div class="ck-hiw-step">
      <div class="ck-hiw-num"><?php echo $s[0]; ?></div>
      <h4 class="ck-hiw-title"><?php echo $s[1]; ?></h4>
      <p class="ck-hiw-desc"><?php echo $s[2]; ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php
}

/* ══════════════════════════════════════════════
   5. GALLERY STRIP  — horizontal scroll
══════════════════════════════════════════════ */
function chunkGallery($galleryImages = []) {
    // Fallback demo images if DB empty
    $fallback = [
        ['path'=>'https://placehold.co/300x210/1B5E30/ffffff?text=Camp+2024',     'label'=>'Health Camp 2024'],
        ['path'=>'https://placehold.co/300x210/E8610A/ffffff?text=School+Drive',  'label'=>'School Kits Drive'],
        ['path'=>'https://placehold.co/300x210/088395/ffffff?text=Tree+Planting', 'label'=>'Plantation Drive'],
        ['path'=>'https://placehold.co/300x210/6D28D9/ffffff?text=Award+Night',   'label'=>'Annual Awards'],
        ['path'=>'https://placehold.co/300x210/EC4899/ffffff?text=Food+Drive',    'label'=>'Food Distribution'],
        ['path'=>'https://placehold.co/300x210/3B0764/FCD34D?text=Volunteer+Day', 'label'=>'Volunteer Day'],
        ['path'=>'https://placehold.co/300x210/0A4D68/ffffff?text=Women+Empower', 'label'=>'Women Empowerment'],
    ];
    $items = !empty($galleryImages) ? $galleryImages : $fallback;
?>
<section class="ck-gallery">
  <div class="ck-gallery-header">
    <div class="ck-section-header" style="text-align:left">
      <div class="ck-overtitle">Gallery</div>
      <h2 class="ck-section-title">Moments of Impact</h2>
    </div>
    <a href="gallery.php" class="ck-btn-outline" style="flex-shrink:0;align-self:flex-end">Full Gallery →</a>
  </div>
  <div class="ck-gallery-strip">
    <?php foreach($items as $img):
      $path  = isset($img['image_path']) ? $img['image_path'] : $img['path'];
      $label = isset($img['title'])      ? $img['title']      : $img['label'];
    ?>
    <div class="ck-g-item">
      <img src="<?php echo htmlspecialchars($path); ?>"
           alt="<?php echo htmlspecialchars($label); ?>"
           loading="lazy">
      <div class="ck-g-overlay"><span><?php echo htmlspecialchars($label); ?></span></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php
}

/* ══════════════════════════════════════════════
   6. TESTIMONIALS  — 3 cards (hardcoded demo)
══════════════════════════════════════════════ */
function chunkTestimonials() {
    $testimonials = [
        ['initial'=>'R','name'=>'Ramesh Sharma', 'role'=>'Regular Donor, Kanpur',
         'text'=>'The transparency of this NGO is remarkable. I can track exactly where my donation went and see real progress on the projects. Highly recommended!'],
        ['initial'=>'P','name'=>'Priya Gupta',   'role'=>'Volunteer, Lucknow',
         'text'=>'Volunteering here changed my life. My ID card, certificate, and dashboard make it feel professional and meaningful. Proud to be part of this family.'],
        ['initial'=>'S','name'=>'Sunita Devi',   'role'=>'Beneficiary, Unnao',
         'text'=>'My children received scholarships through this organization. The process was simple and dignified. Forever grateful for the education support given.'],
    ];
?>
<section class="ck-testi">
  <div class="ck-section-header">
    <div class="ck-overtitle">Community Voices</div>
    <h2 class="ck-section-title">What Our Community Says</h2>
  </div>
  <div class="ck-testi-grid">
    <?php foreach($testimonials as $t): ?>
    <div class="ck-testi-card">
      <div class="ck-testi-quote">"</div>
      <p class="ck-testi-text"><?php echo $t['text']; ?></p>
      <div class="ck-testi-author">
        <div class="ck-testi-av"><?php echo $t['initial']; ?></div>
        <div>
          <div class="ck-testi-name"><?php echo $t['name']; ?></div>
          <div class="ck-testi-role"><?php echo $t['role']; ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php
}

/* ══════════════════════════════════════════════
   7. EVENTS  — from DB or fallback demo
══════════════════════════════════════════════ */
function chunkEvents($events = []) {
    $fallback = [
        ['day'=>'14','month'=>'Apr','tag'=>'Health Camp', 'title'=>'Free Eye Checkup & Cataract Surgery Camp','location'=>'District Hospital, Kanpur'],
        ['day'=>'21','month'=>'Apr','tag'=>'Education',   'title'=>'Annual Scholarship Distribution Ceremony','location'=>'Town Hall, Lucknow'],
        ['day'=>'05','month'=>'May','tag'=>'Environment', 'title'=>'100-Tree Plantation Drive',               'location'=>'Gomti Riverbank, Lucknow'],
        ['day'=>'12','month'=>'May','tag'=>'Volunteer',   'title'=>'New Volunteer Orientation & ID Card Day', 'location'=>'NGO HQ, Kanpur'],
    ];
    $items = !empty($events) ? $events : $fallback;
?>
<section class="ck-events">
  <div class="ck-events-header">
    <div class="ck-section-header" style="text-align:left">
      <div class="ck-overtitle">Upcoming Events</div>
      <h2 class="ck-section-title">Join Our Next Events</h2>
    </div>
    <a href="events.php" class="ck-btn-fill" style="flex-shrink:0;align-self:flex-end">All Events →</a>
  </div>
  <div class="ck-events-grid">
    <?php foreach($items as $e):
      // Support both DB rows and fallback array
      $day      = isset($e['event_date']) ? date('d', strtotime($e['event_date'])) : $e['day'];
      $month    = isset($e['event_date']) ? date('M', strtotime($e['event_date'])) : $e['month'];
      $tag      = $e['tag']      ?? 'Event';
      $title    = $e['title']    ?? htmlspecialchars($e['title']);
      $location = $e['location'] ?? ($e['venue'] ?? '');
    ?>
    <div class="ck-ev-card">
      <div class="ck-ev-date">
        <span class="ck-ev-day"><?php echo $day; ?></span>
        <span class="ck-ev-month"><?php echo $month; ?></span>
      </div>
      <div class="ck-ev-info">
        <div class="ck-ev-tag"><?php echo htmlspecialchars($tag); ?></div>
        <div class="ck-ev-title"><?php echo htmlspecialchars($title); ?></div>
        <div class="ck-ev-loc">📍 <?php echo htmlspecialchars($location); ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php
}

/* ══════════════════════════════════════════════
   8. VOLUNTEER CTA STRIP
══════════════════════════════════════════════ */
function chunkVolunteerCTA() {
?>
<section class="ck-vol-cta">
  <div class="ck-vol-cta-inner">
    <div class="ck-vol-text">
      <h2 class="ck-vol-title">Want to Make a Direct Difference?<br>Become a Volunteer.</h2>
      <p class="ck-vol-sub">Join our growing family of 300+ active volunteers. Get your official ID card, certificate, and join meaningful missions near you.</p>
    </div>
    <div class="ck-vol-btns">
      <a href="volunteer-register.php" class="ck-vbtn-primary">Register as Volunteer</a>
      <a href="about-us.php"           class="ck-vbtn-ghost">Know More</a>
    </div>
  </div>
</section>
<?php
}

/* ══════════════════════════════════════════════
   9. NEWS / STORIES  — big + 2 small
══════════════════════════════════════════════ */
function chunkNews($newsItems = []) {
    $fallback = [
        ['tag'=>'Impact Story','title'=>'How One Village Got Clean Water After 20 Years of Struggle',
         'excerpt'=>'Through collective effort, fundraising, and volunteer power, the village of Rampur finally has access to clean drinking water — a story of perseverance.',
         'image'=>'https://placehold.co/700x300/1B5E30/ffffff?text=Community+Story','big'=>true],
        ['tag'=>'Volunteer',   'title'=>'From College Student to Certified Volunteer Leader',
         'image'=>'https://placehold.co/360x160/E8610A/ffffff?text=Volunteer+Story','big'=>false],
        ['tag'=>'Fundraising', 'title'=>'₹5 Lakh Raised in 48 Hours for Flood Relief',
         'image'=>'https://placehold.co/360x160/088395/ffffff?text=Donation+News',  'big'=>false],
    ];
    $items = !empty($newsItems) ? $newsItems : $fallback;
    $big   = $items[0];
    $smalls= array_slice($items, 1, 2);
?>
<section class="ck-news">
  <div class="ck-section-header">
    <div class="ck-overtitle">Latest News</div>
    <h2 class="ck-section-title">Stories of Change</h2>
  </div>
  <div class="ck-news-grid">
    <a href="news.php" class="ck-news-big">
      <div class="ck-news-big-img">
        <img src="<?php echo htmlspecialchars($big['image'] ?? $big['thumbnail'] ?? ''); ?>"
             alt="<?php echo htmlspecialchars($big['title']); ?>" loading="lazy">
      </div>
      <div class="ck-news-big-body">
        <div class="ck-news-tag"><?php echo htmlspecialchars($big['tag'] ?? 'Story'); ?></div>
        <h3 class="ck-news-title"><?php echo htmlspecialchars($big['title']); ?></h3>
        <p class="ck-news-excerpt"><?php echo mb_substr(strip_tags($big['excerpt'] ?? ''), 0, 160); ?>…</p>
      </div>
    </a>
    <div class="ck-news-small">
      <?php foreach($smalls as $n): ?>
      <a href="news.php" class="ck-news-mini">
        <img src="<?php echo htmlspecialchars($n['image'] ?? $n['thumbnail'] ?? ''); ?>"
             alt="<?php echo htmlspecialchars($n['title']); ?>" loading="lazy">
        <div class="ck-news-mini-body">
          <div class="ck-news-tag"><?php echo htmlspecialchars($n['tag'] ?? 'News'); ?></div>
          <h4 class="ck-news-title"><?php echo htmlspecialchars($n['title']); ?></h4>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php
}

/* ══════════════════════════════════════════════
   10. PARTNERS  — auto-scroll logos
══════════════════════════════════════════════ */
function chunkPartners($sponsors = []) {
    if (empty($sponsors)) return;
?>
<section class="ck-partners">
  <h3 class="ck-partners-label">Our Trusted Partners &amp; Sponsors</h3>
  <div class="ck-p-track">
    <div class="ck-p-row">
      <?php foreach(array_merge($sponsors, $sponsors, $sponsors) as $s): ?>
      <a href="<?php echo htmlspecialchars($s['website_url'] ?? '#'); ?>" target="_blank"
         title="<?php echo htmlspecialchars($s['name']); ?>" class="ck-p-logo">
        <img src="<?php echo htmlspecialchars($s['logo_path']); ?>"
             alt="<?php echo htmlspecialchars($s['name']); ?>" loading="lazy">
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php
}
