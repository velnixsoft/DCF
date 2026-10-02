<?php
require 'includes/header.php';

$certificates = $pdo->query("SELECT * FROM certificates ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white min-h-screen"
    x-data="{ lightboxOpen: false, lightboxImage: '', lightboxTitle: '' }">

    <!-- Hero -->
    <div class="bg-[#FFF8F1] py-16 text-center border-b border-[#FEECDC]">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl md:text-5xl font-extrabold text-[#0F8B8D]">
                Our Certificates & Accreditations
            </h1>
            <p class="mt-3 max-w-2xl mx-auto text-lg text-[#4B5563]">
                We are a registered and recognized non-profit organization,
                committed to transparency and accountability.
            </p>
        </div>
    </div>

    <!-- Certificates -->
    <div class="container mx-auto px-4 py-16">

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 md:gap-8">

            <?php if (count($certificates) > 0): ?>
                <?php foreach ($certificates as $cert): ?>

                    <div
                        @click="
                            lightboxImage = '<?php echo htmlspecialchars($cert['image_path']); ?>';
                            lightboxTitle = '<?php echo htmlspecialchars($cert['title']); ?>';
                            lightboxOpen = true
                        "
                        class="group relative bg-white rounded-[18px] shadow-lg border border-gray-100 overflow-hidden cursor-pointer transform hover:-translate-y-2 hover:border-[#F4A640] transition duration-300">

                        <!-- Thumbnail -->
                        <div class="aspect-[3/4] p-4 bg-[#FFF8F1]/40 flex items-center justify-center">
                            <img
                                src="<?php echo htmlspecialchars($cert['image_path']); ?>"
                                alt="<?php echo htmlspecialchars($cert['title']); ?>"
                                class="w-full h-full object-contain transition duration-500 group-hover:scale-105">
                        </div>

                        <!-- Title -->
                        <div class="p-4 border-t-4 border-[#F4A640] bg-white">
                            <h3 class="text-center font-bold text-[#1F2937] text-sm truncate group-hover:text-[#0F8B8D] transition">
                                <?php echo htmlspecialchars($cert['title']); ?>
                            </h3>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>

                <div class="col-span-full text-center py-16 text-gray-500">
                    <p>No certificates have been uploaded yet.</p>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <!-- CTA -->
    <div class="bg-green-800">
        <div class="container mx-auto px-4 py-16 text-center">

            <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                Ready to Make a Difference?
            </h2>

            <p class="mt-4 max-w-2xl mx-auto text-lg text-green-100">
                Your support can turn our vision into reality.
                Join us in our mission to create a better world.
            </p>

            <div class="mt-8 flex flex-row items-center justify-center gap-3">

                <a href="donate.php"
                    class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-5 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base">
                    <i class="fas fa-heart mr-2"></i>
                    <span>Donate Now</span>
                </a>

                <a href="volunteer-register.php"
                    class="inline-flex items-center justify-center bg-white hover:bg-gray-100 text-green-800 font-bold py-3 px-5 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base whitespace-nowrap">
                    <span>Join as Volunteer</span>
                </a>

            </div>
        </div>
    </div>

    <!-- Lightbox -->
<!-- Lightbox -->
<div
    x-show="lightboxOpen"
    x-cloak
    class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
    @click="lightboxOpen = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95">

    <!-- Box -->
    <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl h-[95vh] p-4 flex flex-col"
        @click.stop>

        <!-- Close -->
        <button
            @click="lightboxOpen = false"
            class="absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white rounded-full w-10 h-10 text-xl font-bold shadow-lg z-50">
            ×
        </button>

        <!-- Zoom Buttons -->
        <div class="absolute top-3 left-3 flex gap-2 z-50">

            <button
                onclick="zoomIn()"
                class="bg-black/70 hover:bg-black text-white w-10 h-10 rounded-full text-xl font-bold">
                +
            </button>

            <button
                onclick="zoomOut()"
                class="bg-black/70 hover:bg-black text-white w-10 h-10 rounded-full text-xl font-bold">
                −
            </button>

            <button
                onclick="resetZoom()"
                class="bg-black/70 hover:bg-black text-white px-4 rounded-full text-sm font-semibold">
                Reset
            </button>

        </div>

        <!-- Full Image -->
        <div
    id="imageContainer"
    class="flex-1 overflow-auto bg-gray-100 rounded-xl p-6 flex items-start justify-center">

    <div class="min-w-max min-h-max flex justify-center">

      <img
    :src="lightboxImage"
    :alt="lightboxTitle"
    id="zoomableImage"
    class="object-contain rounded-xl shadow-2xl transition-all duration-300 select-none"
    style="
        transform: scale(1);
        transform-origin: center top;
        width: auto;
        height: auto;
        max-width: 90%;
        max-height: 85vh;
    ">

    </div>

</div>

        <!-- Title -->
        <p
            x-text="lightboxTitle"
            class="text-center font-bold mt-4 text-gray-700 text-lg">
        </p>

    </div>

</div>

<script>

let scale = 1;

function applyZoom() {

    const img = document.getElementById('zoomableImage');

    if (!img) return;

    img.style.transform = `scale(${scale})`;

}

function zoomIn() {

    scale += 0.2;

    if (scale > 5) scale = 5;

    applyZoom();

}

function zoomOut() {

    scale -= 0.2;

    if (scale < 1) scale = 1;

    applyZoom();

}

function resetZoom() {

    scale = 1;

    applyZoom();

}

/* Ctrl + Mouse Wheel Zoom */
document.addEventListener('wheel', function(e) {

    const img = document.getElementById('zoomableImage');

    if (!img || !e.ctrlKey) return;

    e.preventDefault();

    if (e.deltaY < 0) {
        zoomIn();
    } else {
        zoomOut();
    }

}, { passive: false });

/* Reset zoom when modal opens */
document.addEventListener('click', function() {

    setTimeout(() => {

        scale = 1;
        applyZoom();

    }, 100);

});

</script>

<?php require 'includes/footer.php'; ?>