<?php
require 'includes/header.php';

$mediaItems = $pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$photos = array_filter($mediaItems, fn($item) => $item['type'] === 'image');
$videos = array_filter($mediaItems, fn($item) => $item['type'] === 'video');
?>

<div class="bg-white min-h-screen"
    x-data="{ 
        tab: 'all', 
        lightboxOpen: false, 
        lightboxImage: '',
        openLightbox(image) {
            this.lightboxImage = image;
            this.lightboxOpen = true;
        }
     }">

    <div class="bg-[#FFF8F1] py-16 text-center border-b border-[#FEECDC]">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl md:text-5xl font-extrabold text-[#0F8B8D]">Our Gallery</h1>
            <p class="mt-3 max-w-2xl mx-auto text-lg text-[#4B5563]">A visual journey through our initiatives and the lives we've touched.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-16">

        <div class="flex justify-center mb-12 border-b border-gray-200">
            <nav class="flex space-x-4" aria-label="Tabs">
                <button @click="tab = 'all'"
                    :class="tab === 'all' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-[#4B5563] hover:text-[#F4A640]'"
                    class="px-4 py-3 font-bold border-b-4 transition duration-300">
                    All
                </button>
                <button @click="tab = 'photos'"
                    :class="tab === 'photos' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-[#4B5563] hover:text-[#F4A640]'"
                    class="px-4 py-3 font-bold border-b-4 transition duration-300">
                    Photos
                </button>
                <button @click="tab = 'videos'"
                    :class="tab === 'videos' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-[#4B5563] hover:text-[#F4A640]'"
                    class="px-4 py-3 font-bold border-b-4 transition duration-300">
                    Videos
                </button>
            </nav>
        </div>

        <div class="columns-1 sm:columns-2 lg:columns-3 gap-6 space-y-6">

            <?php foreach ($photos as $item): ?>
                <div x-show="tab === 'all' || tab === 'photos'"
                    class="break-inside-avoid mb-6 group relative overflow-hidden rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100"
                    x-transition.opacity>

                    <img src="<?php echo htmlspecialchars($item['file_path']); ?>"
                        alt="<?php echo htmlspecialchars($item['title']); ?>"
                        class="w-full h-auto object-contain transform transition duration-500 group-hover:scale-105 cursor-pointer"
                        @click="openLightbox('<?php echo htmlspecialchars($item['file_path']); ?>')">

                    <div class="absolute inset-0 bg-gradient-to-t from-green-900/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4 pointer-events-none">
                        <h3 class="text-white font-bold text-lg translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($videos as $item): ?>
                <div x-show="tab === 'all' || tab === 'videos'"
                    class="break-inside-avoid mb-6 group relative overflow-hidden rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100"
                    x-transition.opacity>

                    <?php
                    $video_id = '';
                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $item['file_path'], $match)) {
                        $video_id = $match[1];
                    }
                    $thumbnail_url = $video_id ? "https://img.youtube.com/vi/{$video_id}/hqdefault.jpg" : "https://placehold.co/500x350?text=Invalid+Link";
                    ?>

                    <div class="relative">
                        <img src="<?php echo $thumbnail_url; ?>"
                            alt="<?php echo htmlspecialchars($item['title']); ?>"
                            class="w-full h-auto object-cover transform transition duration-500 group-hover:scale-105">

                        <a href="<?php echo htmlspecialchars($item['file_path']); ?>" target="_blank" class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/10 transition">
                            <div class="w-14 h-14 bg-red-600 rounded-full flex items-center justify-center text-white shadow-lg transform transition group-hover:scale-110">
                                <i class="fas fa-play text-xl ml-1"></i>
                            </div>
                        </a>
                    </div>

                    <div class="p-3 bg-white border-t border-gray-100">
                        <h3 class="text-gray-800 font-bold text-sm truncate"><?php echo htmlspecialchars($item['title']); ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($mediaItems)): ?>
            <div class="text-center py-20 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                <i class="fas fa-images text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 font-medium">Gallery is currently empty.</p>
            </div>
        <?php endif; ?>
    </div>

    <div x-show="lightboxOpen"
        class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
        @click="lightboxOpen = false"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-end="opacity-0"
        x-cloak>

        <button @click="lightboxOpen = false" class="absolute top-5 right-5 text-white/70 hover:text-white text-4xl transition focus:outline-none">
            &times;
        </button>

        <div class="max-w-5xl max-h-[90vh] relative" @click.stop>
            <img :src="lightboxImage" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl"
                x-show="lightboxOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100">
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>